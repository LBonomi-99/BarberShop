<?php
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root';      // Su XAMPP l'utente è sempre 'root'
$pass = '';          // Su XAMPP la password è vuota (lascia le virgolette vuote)
// api_disponibilita.php
header('Content-Type: application/json');

// Connessione al DB
$conn = new mysqli('localhost', 'root', '', 'barber_shop');

if (!isset($_GET['data'])) {
    echo json_encode([]);
    exit;
}

$data_richiesta = $_GET['data'];
$oggi = date('Y-m-d');
$ora_attuale = time();

// 1. Genera tutti gli slot possibili (Intervalli di 30 min)
// Modifica qui gli orari di apertura/chiusura base
$orari_base = [];
$start = strtotime("08:00");
$end = strtotime("19:30");

while ($start <= $end) {
    $orari_base[] = date("H:i", $start);
    $start = strtotime('+30 minutes', $start);
}

// 2. Recupera gli slot segnati come FULL dal database
$blocked = [];
$sql = "SELECT ora_blocco FROM slot_full WHERE data_blocco = '$data_richiesta'";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
    $blocked[] = $row['ora_blocco'];
}

// 3. Filtra gli orari
$disponibili = [];

foreach ($orari_base as $ora) {
    // A. Controllo se è FULL
    if (in_array($ora, $blocked)) {
        continue; // Salta questo orario
    }

    // B. Controllo Regola delle 2 Ore (Solo se la data è oggi)
    if ($data_richiesta == $oggi) {
        $timestamp_slot = strtotime("$data_richiesta $ora");
        // Se l'orario dello slot è minore di (adesso + 2 ore), saltalo
        if ($timestamp_slot < ($ora_attuale + 7200)) { // 7200 secondi = 2 ore
            continue;
        }
    } 
    // C. Controllo se la data è nel passato (ieri, ecc)
    elseif ($data_richiesta < $oggi) {
        continue; 
    }

    // Se passa i controlli, è disponibile
    $disponibili[] = $ora;
}

echo json_encode($disponibili);
$conn->close();
?>