<?php
// --- DATI E QUERY AGGIORNATA ---
// La funzione real_escape_string pulisce il testo da caratteri pericolosi
$filter_date = isset($_GET['filter_date']) ? $conn->real_escape_string($_GET['filter_date']) : '';

// Query Base per Tab
$sql_attesa = "SELECT * FROM prenotazioni WHERE stato='in_attesa'";
$sql_agenda = "SELECT * FROM prenotazioni WHERE stato='accettato'";
// Query base per Storico (Rifiutati O Accettati passati)
$sql_storico = "SELECT * FROM prenotazioni WHERE (stato='rifiutato' OR (data_appuntamento < CURDATE() AND stato='accettato'))";

if (!empty($filter_date)) {
    // SE C'È IL FILTRO: Applica a TUTTI i tab
    $sql_attesa .= " AND data_appuntamento = '$filter_date'";
    $sql_agenda .= " AND data_appuntamento = '$filter_date'";
    $sql_storico .= " AND data_appuntamento = '$filter_date'"; // Filtra anche lo storico
} else {
    // SE NON C'È FILTRO
    $sql_agenda .= " AND data_appuntamento >= CURDATE()";
    // Allo storico aggiungiamo il limite solo se NON stiamo filtrando
    $sql_storico .= " ORDER BY data_appuntamento DESC LIMIT 200";
}

// Ordinamento standard
$sql_attesa .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";
$sql_agenda .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";

// Se c'era il filtro, l'ordinamento allo storico va aggiunto dopo
if (!empty($filter_date)) {
    $sql_storico .= " ORDER BY data_appuntamento DESC";
}

$res_attesa = $conn->query($sql_attesa);
$res_agenda = $conn->query($sql_agenda);
$res_storico = $conn->query($sql_storico); // Query Dinamica

$slot_full = $conn->query("SELECT * FROM slot_full WHERE data_blocco >= CURDATE() ORDER BY data_blocco ASC LIMIT 50");
$count_total_attesa = $conn->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato='in_attesa'")->fetch_assoc()['c'];
?>