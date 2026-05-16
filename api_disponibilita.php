<?php
header('Content-Type: application/json');
date_default_timezone_set('Europe/Rome');

$conn = new mysqli('localhost', 'root', '', 'barber_shop');

if (!isset($_GET['data'])) { echo json_encode([]); exit; }

$data_richiesta = $_GET['data'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_richiesta)) { echo json_encode([]); exit; }

$oggi            = date('Y-m-d');
$ora_attuale     = time();
$giorno_settimana = (int)date('w', strtotime($data_richiesta));

function generaSlot($start_str, $end_str) {
    $slots = [];
    $start = strtotime($start_str);
    $end   = strtotime($end_str);
    while ($start < $end) {
        $slots[] = date("H:i", $start);
        $start   = strtotime('+30 minutes', $start);
    }
    return $slots;
}

$orari_base = [];

// Tenta di leggere gli orari dal DB (richiede migrazioni eseguite)
$stmt = @$conn->prepare("SELECT * FROM opening_hours WHERE giorno = ?");
if ($stmt) {
    $stmt->bind_param("i", $giorno_settimana);
    $stmt->execute();
    $ore = $stmt->get_result()->fetch_assoc();
    if ($ore && !$ore['chiuso']) {
        if (!empty($ore['mattina_inizio']) && !empty($ore['mattina_fine'])) {
            $orari_base = array_merge($orari_base, generaSlot($ore['mattina_inizio'], $ore['mattina_fine']));
        }
        if (!empty($ore['pomeriggio_inizio']) && !empty($ore['pomeriggio_fine'])) {
            $orari_base = array_merge($orari_base, generaSlot($ore['pomeriggio_inizio'], $ore['pomeriggio_fine']));
        }
    }
} else {
    // Fallback hardcoded se la tabella opening_hours non esiste ancora
    switch ($giorno_settimana) {
        case 0: case 1: $orari_base = []; break;
        case 2: case 4: $orari_base = array_merge(generaSlot("08:00","12:30"), generaSlot("15:00","19:30")); break;
        case 3: $orari_base = generaSlot("08:30","18:30"); break;
        case 5: $orari_base = generaSlot("08:00","19:30"); break;
        case 6: $orari_base = generaSlot("08:00","18:30"); break;
    }
}

if (empty($orari_base)) { echo json_encode([]); exit; }

// Slot bloccati da admin
$blocked = [];
$stmt2 = $conn->prepare("SELECT ora_blocco FROM slot_full WHERE data_blocco = ?");
$stmt2->bind_param("s", $data_richiesta);
$stmt2->execute();
$res = $stmt2->get_result();
while ($row = $res->fetch_assoc()) { $blocked[] = $row['ora_blocco']; }

// Filtro finale
$disponibili = [];
foreach ($orari_base as $ora) {
    if (in_array($ora, $blocked)) continue;
    if ($data_richiesta == $oggi) {
        if (strtotime("$data_richiesta $ora") < ($ora_attuale + 7200)) continue;
    } elseif ($data_richiesta < $oggi) {
        continue;
    }
    $disponibili[] = $ora;
}

echo json_encode($disponibili);
$conn->close();
?>
