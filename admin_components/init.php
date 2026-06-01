<?php
// --- VARIABILI UI ---
$active_tab = isset($_GET['current_tab']) ? $_GET['current_tab'] : 'dashboard';
$flow_id    = isset($_GET['flow_id'])     ? intval($_GET['flow_id']) : 0;
$flow_step  = isset($_GET['step'])        ? $_GET['step'] : '';
$admin_msg  = isset($_GET['msg'])         ? $_GET['msg']  : '';

// --- HELPER LOG ---
function aggiungiLog($conn, $id, $messaggio) {
    $timestamp = date("d/m H:i");
    $entry     = "[$timestamp] $messaggio\n";
    $stmt = $conn->prepare("UPDATE prenotazioni SET log_azioni = CONCAT(IFNULL(log_azioni,''), ?) WHERE id=?");
    $stmt->bind_param("si", $entry, $id);
    $stmt->execute();
}

// --- HELPER OCCUPAZIONE SLOT (fonte unica disponibilita) ---
// Occupa lo slot per la prenotazione $id. Ritorna true se ora e nostro,
// false se gia occupato da un'altra prenotazione (errno 1062) o errore.
function occupaSlot($conn, $id, $data, $ora) {
    $ora  = substr($ora, 0, 5);
    // Robusto sia se mysqli lancia eccezioni (PHP 8.1+ default) sia se ritorna false.
    try {
        $stmt = $conn->prepare("INSERT INTO slot_occupati (data, ora, prenotazione_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $data, $ora, $id);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        if ($conn->errno === 1062) return false; // slot gia preso da un'altra prenotazione
        throw $e;
    }
}

// Libera lo slot occupato dalla prenotazione $id (su rifiuto/annullo/eliminazione).
function liberaSlot($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM slot_occupati WHERE prenotazione_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// --- STATS DASHBOARD ---
$stats_mese = ['accettato' => 0, 'in_attesa' => 0, 'rifiutato' => 0];
$stats_giorno = array_fill(1, 7, 0); // indice DAYOFWEEK MySQL: 1=Dom 2=Lun ... 7=Sab
$top_orari    = [];
$mese_prec_accettate = 0;

$stmt = $conn->prepare("SELECT stato, COUNT(*) as cnt FROM prenotazioni WHERE YEAR(data_richiesta)=YEAR(NOW()) AND MONTH(data_richiesta)=MONTH(NOW()) GROUP BY stato");
if ($stmt) { $stmt->execute(); $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $stats_mese[$row['stato']] = (int)$row['cnt']; }

$stmt = $conn->prepare("SELECT DAYOFWEEK(data_appuntamento) as g, COUNT(*) as cnt FROM prenotazioni WHERE stato='accettato' GROUP BY g");
if ($stmt) { $stmt->execute(); $r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $stats_giorno[$row['g']] = (int)$row['cnt']; }

$stmt = $conn->prepare("SELECT ora_appuntamento, COUNT(*) as cnt FROM prenotazioni WHERE stato='accettato' GROUP BY ora_appuntamento ORDER BY cnt DESC LIMIT 5");
if ($stmt) { $stmt->execute(); $top_orari = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); }

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM prenotazioni WHERE stato='accettato' AND YEAR(data_richiesta)=YEAR(NOW()) AND MONTH(data_richiesta)=IF(MONTH(NOW())=1,12,MONTH(NOW())-1)");
if ($stmt) { $stmt->execute(); $mese_prec_accettate = (int)$stmt->get_result()->fetch_assoc()['cnt']; }

// --- AGENDA JSON per calendario visuale (prossimi 35 giorni) ---
$prenotazioni_agenda_json = [];
$stmt = $conn->prepare("SELECT id, nome, telefono, data_appuntamento, ora_appuntamento, servizio FROM prenotazioni WHERE stato='accettato' AND data_appuntamento >= CURDATE() AND data_appuntamento <= DATE_ADD(CURDATE(), INTERVAL 35 DAY) ORDER BY data_appuntamento, ora_appuntamento");
if ($stmt) { $stmt->execute(); $prenotazioni_agenda_json = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); }

// --- Modalita conferma prenotazioni (auto | approval) ---
$booking_mode = 'auto';
$res_bm = @$conn->query("SELECT config_value FROM admin_config WHERE config_key='booking_mode'");
if ($res_bm && ($r_bm = $res_bm->fetch_assoc()) && in_array($r_bm['config_value'], ['auto','approval'], true)) {
    $booking_mode = $r_bm['config_value'];
}

// --- ORARI DI APERTURA per tools ---
$opening_hours_data = [];
// Default hardcoded come fallback se migrations non eseguite
$oh_default = [
    0 => ['mattina_inizio'=>'','mattina_fine'=>'','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>1],
    1 => ['mattina_inizio'=>'','mattina_fine'=>'','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>1],
    2 => ['mattina_inizio'=>'08:00','mattina_fine'=>'12:30','pomeriggio_inizio'=>'15:00','pomeriggio_fine'=>'19:30','chiuso'=>0],
    3 => ['mattina_inizio'=>'08:30','mattina_fine'=>'18:30','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>0],
    4 => ['mattina_inizio'=>'08:00','mattina_fine'=>'12:30','pomeriggio_inizio'=>'15:00','pomeriggio_fine'=>'19:30','chiuso'=>0],
    5 => ['mattina_inizio'=>'08:00','mattina_fine'=>'19:30','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>0],
    6 => ['mattina_inizio'=>'08:00','mattina_fine'=>'18:30','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>0],
];
$res_oh = @$conn->query("SELECT * FROM opening_hours ORDER BY giorno ASC");
if ($res_oh && $res_oh->num_rows > 0) {
    while ($row = $res_oh->fetch_assoc()) $opening_hours_data[(int)$row['giorno']] = $row;
} else {
    $opening_hours_data = $oh_default;
}

// --- CATEGORIE LISTINO per CMS ---
$service_categories = [];
$res_cats = @$conn->query("SELECT * FROM service_categories ORDER BY sort_order ASC, name ASC");
if ($res_cats && $res_cats->num_rows > 0) {
    while ($row = $res_cats->fetch_assoc()) $service_categories[] = $row;
} else {
    $service_categories = [['id'=>0,'name'=>'Taglio & Styling'],['id'=>0,'name'=>'Barba']];
}

// --- SOCIAL LINKS per CMS ---
$social_instagram = '';
$social_facebook  = '';
$res_s = $conn->query("SELECT section_key, content_text FROM site_content WHERE section_key IN ('social_instagram','social_facebook')");
if ($res_s) {
    while ($s = $res_s->fetch_assoc()) {
        if ($s['section_key'] == 'social_instagram') $social_instagram = $s['content_text'];
        if ($s['section_key'] == 'social_facebook')  $social_facebook  = $s['content_text'];
    }
}
?>
