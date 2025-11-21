<?php
// admin.php
session_start();
date_default_timezone_set('Europe/Rome'); // Imposta fuso orario corretto

// --- CONFIGURAZIONE DATABASE ---
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Errore connessione DB: " . $conn->connect_error); }

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// --- LOGIN ---
$password_segreta = "Matteo2025"; 
if (isset($_POST['login']) && $_POST['pass'] == $password_segreta) { $_SESSION['logged_in'] = true; }
if (!isset($_SESSION['logged_in'])) {
    echo '<form method="POST" style="text-align:center; margin-top:100px; font-family:sans-serif;">
            <h2>Gestionale Barba & Capelli</h2>
            <input type="password" name="pass" placeholder="Password" style="padding:10px;">
            <button type="submit" name="login" style="padding:10px; background:#1C1C1C; color:white; border:none;">Entra</button>
          </form>'; exit;
}

// --- FUNZIONE LOG ---
function aggiungiLog($conn, $id, $messaggio) {
    $timestamp = date("d/m H:i");
    $entry = "[$timestamp] $messaggio\n";
    $stmt = $conn->prepare("UPDATE prenotazioni SET log_azioni = CONCAT(IFNULL(log_azioni, ''), ?) WHERE id=?");
    $stmt->bind_param("si", $entry, $id);
    $stmt->execute();
}

// --- 1. GESTIONE CAMBIO STATO (Accetta/Rifiuta) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stato = $_GET['action']; 
    $stmt = $conn->prepare("UPDATE prenotazioni SET stato=? WHERE id=?");
    $stmt->bind_param("si", $stato, $id);
    $stmt->execute();
    
    if ($stato == 'accettato') aggiungiLog($conn, $id, "Stato cambiato in: ACCETTATO");
    if ($stato == 'rifiutato') aggiungiLog($conn, $id, "Stato cambiato in: RIFIUTATO");
    
    header("Location: admin.php"); exit;
}

// --- 2. CREAZIONE MANUALE PRENOTAZIONE (NUOVO) ---
if (isset($_POST['manual_booking'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $tel = $conn->real_escape_string($_POST['telefono']);
    $data = $_POST['data'];
    $ora = $_POST['ora'];
    $servizio = $conn->real_escape_string($_POST['servizio']);
    $note = "Inserito manualmente da Admin";

    // Inseriamo direttamente come 'accettato'
    $sql = "INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato, log_azioni) 
            VALUES ('$nome', '$tel', '$data', '$ora', '$servizio', 'accettato', '[$note]\n')";
    
    if($conn->query($sql)) {
        // Opzionale: Potremmo aggiungere un log extra qui
    }
    header("Location: admin.php"); exit;
}

// --- 3. GESTIONE TRACKING LINK ---
if (isset($_GET['track']) && isset($_GET['id']) && isset($_GET['url'])) {
    $id = intval($_GET['id']);
    $tipo = $_GET['track'];
    $url_destinazione = base64_decode($_GET['url']);
    
    $msg = "";
    switch($tipo) {
        case 'wa_conf': $msg = "Inviato WA Conferma"; break;
        case 'wa_rej': $msg = "Inviato WA Rifiuto"; break;
        case 'wa_canc': $msg = "Inviato WA Annullamento"; break;
        case 'gcal': $msg = "Aggiunto a Google Calendar"; break;
    }
    if ($msg) aggiungiLog($conn, $id, $msg);
    header("Location: " . $url_destinazione); exit;
}

// --- 4. GESTIONE BLOCCO SLOT (Ferie) ---
if (isset($_POST['block_type'])) {
    $data_inizio = $_POST['data_blocco'];
    if ($_POST['block_type'] == 'single') {
        $ora = $_POST['ora_blocco'];
        $conn->query("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES ('$data_inizio', '$ora')");
    } elseif ($_POST['block_type'] == 'full_day') {
        bloccaGiornoIntero($conn, $data_inizio);
    } elseif ($_POST['block_type'] == 'range') {
        $data_fine = $_POST['data_fine'];
        $current = strtotime($data_inizio);
        $end = strtotime($data_fine);
        while ($current <= $end) {
            bloccaGiornoIntero($conn, date('Y-m-d', $current));
            $current = strtotime('+1 day', $current);
        }
    }
    header("Location: admin.php"); exit;
}
function bloccaGiornoIntero($conn, $data) {
    $start = strtotime("08:00"); $end = strtotime("19:30");
    while ($start <= $end) {
        $ora = date("H:i", $start);
        $conn->query("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES ('$data', '$ora')");
        $start = strtotime('+30 minutes', $start);
    }
}
if (isset($_GET['delete_block'])) {
    $id = intval($_GET['delete_block']);
    $conn->query("DELETE FROM slot_full WHERE id=$id");
    header("Location: admin.php"); exit;
}

// --- RECUPERO DATI E FILTRI ---

// 1. Gestione Filtri
$where_clause = "1=1"; // Base sempre vera
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

if (!empty($filter_date)) {
    $where_clause .= " AND data_appuntamento = '$filter_date'";
}
if ($filter_status != 'all') {
    $where_clause .= " AND stato = '$filter_status'";
}

// 2. Esecuzione Query Prenotazioni
$sql_prenotazioni = "SELECT * FROM prenotazioni WHERE $where_clause ORDER BY data_appuntamento DESC, ora_appuntamento ASC";
$prenotazioni = $conn->query($sql_prenotazioni);

// 3. Recupero Blocchi (Ferie)
$slot_full = $conn->query("SELECT * FROM slot_full WHERE data_blocco >= CURDATE() ORDER BY data_blocco, ora_blocco LIMIT 100");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Lato', sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .logout-btn { color: red; text-decoration: none; font-weight: bold; border: 1px solid red; padding: 5px 10px; border-radius: 5px; }
        
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h1, h2 { color: #1C1C1C; border-bottom: 2px solid #B8860B; padding-bottom: 10px; margin-top: 0; }
        
        /* Forms Stile */
        .inline-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .yellow-bg { background: #fff3cd; border-left: 5px solid #ffc107; }
        .green-bg { background: #d4edda; border-left: 5px solid #28a745; }
        .gray-bg { background: #f8f9fa; border-left: 5px solid #6c757d; }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: bold; margin-bottom: 3px; }
        input, select { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        
        /* Tabella */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem; }
        th { background-color: #A1B59C; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:hover { background-color: #fafafa; }

        /* Badge */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge.in_attesa { background: #fff3cd; color: #856404; }
        .badge.accettato { background: #d4edda; color: #155724; }
        .badge.rifiutato { background: #f8d7da; color: #721c24; }
        
        .log-box { font-family: monospace; font-size: 0.8rem; color: #555; background: #f8f9fa; padding: 8px; border-radius: 4px; max-height: 100px; overflow-y: auto; white-space: pre-line; }

        /* Pulsanti */
        .btn { text-decoration: none; padding: 8px 12px; border-radius: 5px; color: white; font-size: 0.8rem; margin-right: 5px; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; margin-bottom: 4px; }
        .btn:hover { opacity: 0.9; }
        .btn-accept { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .btn-wa { background-color: #25D366; }
        .btn-cal { background-color: #4285F4; }
        .btn-block { background-color: #1C1C1C; }
        .btn-filter { background-color: #17a2b8; }
        .btn-reset { background-color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-top">
            <h1>Gestionale Barba & Capelli</h1>
            <a href="admin.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Esci</a>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="card yellow-bg">
                <h2><i class="fas fa-calendar-times"></i> Blocca Orari / Ferie</h2>
                <form method="POST" class="inline-form">
                    <div class="form-group">
                        <label>Data Inizio:</label>
                        <input type="date" name="data_blocco" required>
                    </div>
                    
                    <div class="form-group" id="endDateGroup" style="display:none;">
                        <label>Data Fine:</label>
                        <input type="date" name="data_fine">
                    </div>

                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="block_type" id="blockType" onchange="toggleInputs()">
                            <option value="single">Orario Singolo</option>
                            <option value="full_day">Giorno Intero</option>
                            <option value="range">Periodo</option>
                        </select>
                    </div>

                    <div class="form-group" id="timeSelectGroup">
                        <label>Orario:</label>
                        <select name="ora_blocco">
                            <?php 
                            $start = strtotime("08:00"); $end = strtotime("19:30");
                            while ($start <= $end) { echo "<option>".date("H:i", $start)."</option>"; $start = strtotime('+30 minutes', $start); }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-block"><i class="fas fa-ban"></i> Blocca</button>
                </form>
                
                <?php if($slot_full->num_rows > 0): ?>
                <div style="margin-top:10px; font-size:0.8rem;">
                    <a href="#" onclick="document.getElementById('blockedList').style.display='block'; return false;">Vedi blocchi attivi...</a>
                    <div id="blockedList" style="display:none; margin-top:5px; padding:10px; background:white; border:1px solid #ddd; max-height:100px; overflow-y:auto;">
                        <?php while($row = $slot_full->fetch_assoc()): ?>
                            <span style="background:#eee; padding:2px 5px; margin:2px; display:inline-block; border-radius:3px;">
                                <?php echo date('d/m', strtotime($row['data_blocco'])) . " " . $row['ora_blocco']; ?>
                                <a href="admin.php?delete_block=<?php echo $row['id']; ?>" style="color:red; text-decoration:none;">&times;</a>
                            </span>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card green-bg">
                <h2><i class="fas fa-plus-circle"></i> Nuova Prenotazione Manuale</h2>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="manual_booking" value="1">
                    
                    <div class="form-group">
                        <label>Nome Cliente:</label>
                        <input type="text" name="nome" placeholder="Es. Mario Rossi" required>
                    </div>
                    <div class="form-group">
                        <label>Telefono:</label>
                        <input type="text" name="telefono" placeholder="Es. 333..." required>
                    </div>
                    <div class="form-group">
                        <label>Data:</label>
                        <input type="date" name="data" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ora:</label>
                        <select name="ora">
                            <?php 
                            $start = strtotime("08:00"); $end = strtotime("19:30");
                            while ($start <= $end) { echo "<option>".date("H:i", $start)."</option>"; $start = strtotime('+30 minutes', $start); }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Servizio:</label>
                        <input type="text" name="servizio" placeholder="Es. Taglio" required>
                    </div>
                    <button type="submit" class="btn btn-accept"><i class="fas fa-save"></i> Salva</button>
                </form>
            </div>
        </div>

        <div class="card gray-bg">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                <h2><i class="fas fa-list-ul"></i> Lista Appuntamenti</h2>
                
                <form method="GET" class="inline-form" style="background: white; padding: 10px; border-radius: 5px; border:1px solid #ddd;">
                    <div class="form-group">
                        <label>Filtra Data:</label>
                        <input type="date" name="filter_date" value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>Stato:</label>
                        <select name="filter_status">
                            <option value="all" <?php if($filter_status=='all') echo 'selected'; ?>>Tutti</option>
                            <option value="in_attesa" <?php if($filter_status=='in_attesa') echo 'selected'; ?>>In Attesa</option>
                            <option value="accettato" <?php if($filter_status=='accettato') echo 'selected'; ?>>Accettati</option>
                            <option value="rifiutato" <?php if($filter_status=='rifiutato') echo 'selected'; ?>>Rifiutati</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filtra</button>
                    <?php if(!empty($filter_date) || $filter_status != 'all'): ?>
                        <a href="admin.php" class="btn btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="15%">Cliente</th>
                        <th width="15%">Data & Ora</th>
                        <th width="20%">Servizio</th>
                        <th width="10%">Stato</th>
                        <th width="20%">Riepilogo Azioni</th>
                        <th width="20%">Azioni Rapide</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($prenotazioni->num_rows > 0): ?>
                        <?php while($row = $prenotazioni->fetch_assoc()): ?>
                            <?php 
                                $tel_clean = preg_replace('/[^0-9]/', '', $row['telefono']);
                                $data_fmt = date('d/m/Y', strtotime($row['data_appuntamento']));
                                $ora_fmt = $row['ora_appuntamento'];
                                $nome_cli = $row['nome'];
                                
                                // Messaggi
                                $wa_accept = "Ciao $nome_cli, confermo il tuo appuntamento per il $data_fmt alle $ora_fmt. A presto, Matteo.";
                                $wa_reject = "Ciao $nome_cli, purtroppo per il $data_fmt alle $ora_fmt non ho disponibilità.";
                                $wa_cancel = "Ciao $nome_cli, devo annullare l'appuntamento del $data_fmt alle $ora_fmt per un imprevisto.";
                                
                                // Link
                                $link_wa_accept = base64_encode("https://wa.me/39$tel_clean?text=" . urlencode($wa_accept));
                                $link_wa_reject = base64_encode("https://wa.me/39$tel_clean?text=" . urlencode($wa_reject));
                                $link_wa_cancel = base64_encode("https://wa.me/39$tel_clean?text=" . urlencode($wa_cancel));
                                
                                // GCal
                                $start_ts = strtotime($row['data_appuntamento'] . ' ' . $row['ora_appuntamento']);
                                $end_ts = $start_ts + (30 * 60);
                                $gcal_dates = date('Ymd\THis', $start_ts) . "/" . date('Ymd\THis', $end_ts);
                                $calendar_email = "leonardobonomi949@gmail.com"; 
                                $gcal_url = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=Taglio+$nome_cli&dates=$gcal_dates&details=Tel:+$tel_clean+Servizio:+$row[servizio]&src=$calendar_email";
                                $link_gcal = base64_encode($gcal_url);
                            ?>
                            <tr>
                                <td><strong><?php echo $nome_cli; ?></strong><br><small><?php echo $row['telefono']; ?></small></td>
                                <td><?php echo $data_fmt; ?><br><strong><?php echo $ora_fmt; ?></strong></td>
                                <td><?php echo $row['servizio']; ?></td>
                                <td><span class="badge <?php echo $row['stato']; ?>"><?php echo $row['stato']; ?></span></td>
                                <td>
                                    <?php if(!empty($row['log_azioni'])): ?>
                                        <div class="log-box"><?php echo $row['log_azioni']; ?></div>
                                    <?php else: ?>
                                        <small style="color:#999">- Nessuna azione -</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['stato'] == 'in_attesa'): ?>
                                        <a href="admin.php?action=accettato&id=<?php echo $row['id']; ?>" class="btn btn-accept" title="Accetta">OK</a>
                                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" class="btn btn-reject" title="Rifiuta">NO</a>
                                        <a href="admin.php?track=wa_rej&id=<?php echo $row['id']; ?>&url=<?php echo $link_wa_reject; ?>" class="btn btn-wa" title="Avvisa WA"><i class="fab fa-whatsapp"></i></a>

                                    <?php elseif($row['stato'] == 'accettato'): ?>
                                        <a href="admin.php?track=wa_conf&id=<?php echo $row['id']; ?>&url=<?php echo $link_wa_accept; ?>" class="btn btn-wa" title="Conferma WA"><i class="fab fa-whatsapp"></i></a>
                                        <a href="admin.php?track=gcal&id=<?php echo $row['id']; ?>&url=<?php echo $link_gcal; ?>" class="btn btn-cal" title="Agenda"><i class="far fa-calendar-plus"></i></a>
                                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" style="color:red; margin-left:5px;" onclick="return confirm('Annullare?')"><i class="fas fa-ban"></i></a>
                                        <a href="admin.php?track=wa_canc&id=<?php echo $row['id']; ?>&url=<?php echo $link_wa_cancel; ?>" style="color:#25D366;" title="Scuse WA"><i class="fab fa-whatsapp"></i></a>
                                    <?php else: ?>
                                        <small>Rifiutato</small>
                                        <a href="admin.php?action=in_attesa&id=<?php echo $row['id']; ?>" style="font-size:0.8rem;">(Ripristina)</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">Nessuna prenotazione trovata con questi filtri.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function toggleInputs() {
            const val = document.getElementById('blockType').value;
            const timeGroup = document.getElementById('timeSelectGroup');
            const endGroup = document.getElementById('endDateGroup');
            
            if (val === 'single') {
                timeGroup.style.display = 'flex';
                endGroup.style.display = 'none';
            } else if (val === 'full_day') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'none';
            } else if (val === 'range') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'flex';
            }
        }
    </script>
</body>
</html>