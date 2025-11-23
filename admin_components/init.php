<?php
// --- RECUPERO VARIABILI UI ---
$active_tab = isset($_GET['current_tab']) ? $_GET['current_tab'] : 'inbox';
$flow_id = isset($_GET['flow_id']) ? intval($_GET['flow_id']) : 0;
$flow_step = isset($_GET['step']) ? $_GET['step'] : '';

// --- HELPER LOG ---
function aggiungiLog($conn, $id, $messaggio) {
    $timestamp = date("d/m H:i");
    $entry = "[$timestamp] $messaggio\n";
    $stmt = $conn->prepare("UPDATE prenotazioni SET log_azioni = CONCAT(IFNULL(log_azioni, ''), ?) WHERE id=?");
    $stmt->bind_param("si", $entry, $id);
    $stmt->execute();
}
?>