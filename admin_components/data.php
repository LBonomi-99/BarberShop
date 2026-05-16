<?php
$filter_date = '';
if (isset($_GET['filter_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['filter_date'])) {
    $filter_date = $_GET['filter_date'];
}

if (!empty($filter_date)) {
    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE stato='in_attesa' AND data_appuntamento=? ORDER BY ora_appuntamento ASC");
    $stmt->bind_param("s", $filter_date); $stmt->execute();
    $res_attesa = $stmt->get_result();

    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE stato='accettato' AND data_appuntamento=? ORDER BY ora_appuntamento ASC");
    $stmt->bind_param("s", $filter_date); $stmt->execute();
    $res_agenda = $stmt->get_result();

    $stmt = $conn->prepare("SELECT * FROM prenotazioni WHERE (stato='rifiutato' OR (data_appuntamento < CURDATE() AND stato='accettato')) AND data_appuntamento=? ORDER BY data_appuntamento DESC");
    $stmt->bind_param("s", $filter_date); $stmt->execute();
    $res_storico = $stmt->get_result();
} else {
    $res_attesa  = $conn->query("SELECT * FROM prenotazioni WHERE stato='in_attesa' ORDER BY data_appuntamento ASC, ora_appuntamento ASC");
    $res_agenda  = $conn->query("SELECT * FROM prenotazioni WHERE stato='accettato' AND data_appuntamento >= CURDATE() ORDER BY data_appuntamento ASC, ora_appuntamento ASC");
    $res_storico = $conn->query("SELECT * FROM prenotazioni WHERE (stato='rifiutato' OR (data_appuntamento < CURDATE() AND stato='accettato')) ORDER BY data_appuntamento DESC LIMIT 200");
}

$slot_full          = $conn->query("SELECT * FROM slot_full WHERE data_blocco >= CURDATE() ORDER BY data_blocco ASC LIMIT 50");
$count_total_attesa = $conn->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato='in_attesa'")->fetch_assoc()['c'];
?>
