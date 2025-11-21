<?php
// api_disponibilita.php
header('Content-Type: application/json');
date_default_timezone_set('Europe/Rome');

$conn = new mysqli('localhost', 'root', '', 'barber_shop');

if (!isset($_GET['data'])) {
    echo json_encode([]);
    exit;
}

$data_richiesta = $_GET['data'];
$oggi = date('Y-m-d');
$ora_attuale = time();

// --- 1. DEFINIZIONE ORARI IN BASE AL GIORNO ---
// w: 0 (Domenica) - 6 (Sabato)
$giorno_settimana = date('w', strtotime($data_richiesta));

$orari_base = [];

// Funzione helper per generare intervalli
function generaSlot($start_str, $end_str) {
    $slots = [];
    $start = strtotime($start_str);
    $end = strtotime($end_str); // Orario di chiusura (l'ultimo taglio deve finire entro quest'ora)
    
    while ($start < $end) { // Nota: < strettamente minore, così se chiudi alle 12:30, l'ultimo slot è 12:00
        $slots[] = date("H:i", $start);
        $start = strtotime('+30 minutes', $start);
    }
    return $slots;
}

switch ($giorno_settimana) {
    case 0: // Domenica
    case 1: // Lunedì
        // CHIUSO: Restituisce array vuoto
        $orari_base = []; 
        break;

    case 2: // Martedì (08–12:30, 15–19:30)
    case 4: // Giovedì (08–12:30, 15–19:30)
        $mattina = generaSlot("08:00", "12:30");
        $pomeriggio = generaSlot("15:00", "19:30");
        $orari_base = array_merge($mattina, $pomeriggio);
        break;

    case 3: // Mercoledì (08:30–18:30 Continuato)
        $orari_base = generaSlot("08:30", "18:30");
        break;

    case 5: // Venerdì (08:00–19:30 Continuato)
        $orari_base = generaSlot("08:00", "19:30");
        break;

    case 6: // Sabato (08:00–18:30 Continuato)
        $orari_base = generaSlot("08:00", "18:30");
        break;
}

// Se è giorno di chiusura, fermati subito
if (empty($orari_base)) {
    echo json_encode([]);
    exit;
}

// --- 2. RECUPERO SLOT BLOCCATI (Ferie/Occupati da Admin) ---
$blocked = [];
$sql = "SELECT ora_blocco FROM slot_full WHERE data_blocco = '$data_richiesta'";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
    $blocked[] = $row['ora_blocco'];
}

// --- 3. RECUPERO PRENOTAZIONI GIÀ ACCETTATE (Opzionale) ---
// Nota: Se vuoi permettere più tagli allo stesso orario, lascia questa parte commentata.
// Se vuoi che 1 slot = 1 cliente, decommenta le righe sotto.
/*
$sql_booked = "SELECT ora_appuntamento FROM prenotazioni WHERE data_appuntamento = '$data_richiesta' AND stato != 'rifiutato'";
$res_booked = $conn->query($sql_booked);
while($row = $res_booked->fetch_assoc()){
    $blocked[] = $row['ora_appuntamento'];
}
*/

// --- 4. FILTRO FINALE ---
$disponibili = [];

foreach ($orari_base as $ora) {
    // A. Controllo se è FULL (Bloccato da Admin)
    if (in_array($ora, $blocked)) {
        continue; 
    }

    // B. Controllo Regola delle 2 Ore (Solo se la data è oggi)
    if ($data_richiesta == $oggi) {
        $timestamp_slot = strtotime("$data_richiesta $ora");
        // Se l'orario dello slot è minore di (adesso + 2 ore), saltalo
        if ($timestamp_slot < ($ora_attuale + 7200)) { 
            continue;
        }
    } 
    // C. Controllo date passate
    elseif ($data_richiesta < $oggi) {
        continue; 
    }

    // Slot valido
    $disponibili[] = $ora;
}

echo json_encode($disponibili);
$conn->close();
?>