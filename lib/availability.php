<?php
/**
 * Motore disponibilita — UNICA fonte di verita.
 * Usato da: api_disponibilita.php (form pubblico) e invia_prenotazione.php (ri-validazione).
 * Online, telefono e presenza condividono per forza questa funzione.
 */

if (!function_exists('generaSlot')) {
    /** Genera slot ogni 30 minuti tra due orari "HH:MM". */
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
}

/**
 * Slot liberi per una data: orari apertura MENO occupati MENO bloccati MENO lead-time.
 * @return array lista "HH:MM" disponibili (vuota se data non valida/chiuso/passata).
 */
function slot_disponibili(mysqli $conn, string $data): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return [];

    $oggi             = date('Y-m-d');
    if ($data < $oggi) return [];
    $ora_attuale      = time();
    $giorno_settimana = (int)date('w', strtotime($data));

    // --- Orari base da opening_hours (fallback hardcoded se tabella assente) ---
    $orari_base = [];
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
        switch ($giorno_settimana) {
            case 0: case 1: $orari_base = []; break;
            case 2: case 4: $orari_base = array_merge(generaSlot("08:00","12:30"), generaSlot("15:00","19:30")); break;
            case 3: $orari_base = generaSlot("08:30","18:30"); break;
            case 5: $orari_base = generaSlot("08:00","19:30"); break;
            case 6: $orari_base = generaSlot("08:00","18:30"); break;
        }
    }
    if (empty($orari_base)) return [];

    // --- Slot non disponibili: bloccati (admin) + occupati (prenotazioni attive) ---
    $non_liberi = [];

    $stmt2 = $conn->prepare("SELECT ora_blocco AS ora FROM slot_full WHERE data_blocco = ?");
    $stmt2->bind_param("s", $data);
    $stmt2->execute();
    $res = $stmt2->get_result();
    while ($r = $res->fetch_assoc()) { $non_liberi[$r['ora']] = true; }

    $stmt3 = $conn->prepare("SELECT ora FROM slot_occupati WHERE data = ?");
    $stmt3->bind_param("s", $data);
    $stmt3->execute();
    $res = $stmt3->get_result();
    while ($r = $res->fetch_assoc()) { $non_liberi[$r['ora']] = true; }

    // --- Filtro finale: rimuovi non-liberi + lead-time (+2h) se oggi ---
    $disponibili = [];
    foreach ($orari_base as $ora) {
        if (isset($non_liberi[$ora])) continue;
        if ($data === $oggi && strtotime("$data $ora") < ($ora_attuale + 7200)) continue;
        $disponibili[] = $ora;
    }
    return $disponibili;
}

/** Modalita conferma: 'auto' (default) | 'approval'. */
function getBookingMode(mysqli $conn): string {
    $res = @$conn->query("SELECT config_value FROM admin_config WHERE config_key='booking_mode'");
    if ($res && ($row = $res->fetch_assoc()) && in_array($row['config_value'], ['auto','approval'], true)) {
        return $row['config_value'];
    }
    return 'auto';
}
