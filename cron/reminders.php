<?php
/**
 * Promemoria appuntamenti — endpoint da chiamare via pinger esterno (cron-job.org):
 *   https://TUO-DOMINIO/cron/reminders.php?key=CRON_TOKEN
 *
 * Manda promemoria per gli appuntamenti di DOMANI (stato accettato, con email),
 * marca promemoria_inviato per non duplicare. Batch limitato + guard sul tempo
 * per rispettare max_execution_time (~30s) di Tophost: il pinger orario svuota la coda.
 */
date_default_timezone_set('Europe/Rome');
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/mailer.php';

// --- Auth: token segreto (query ?key= o argomento CLI) ---
$token = $_GET['key'] ?? ($argv[1] ?? '');
if (!defined('CRON_TOKEN') || !hash_equals(CRON_TOKEN, (string)$token)) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

$conn = db_connect();
if (!$conn) { http_response_code(500); echo "db error\n"; exit; }

$start   = time();
$domani  = date('Y-m-d', strtotime('+1 day'));
$inviati = 0;

$sel = $conn->prepare(
    "SELECT id, nome, email, data_appuntamento, ora_appuntamento
     FROM prenotazioni
     WHERE stato='accettato' AND data_appuntamento=? AND promemoria_inviato=0
       AND email IS NOT NULL AND email<>''
     LIMIT 30"
);
$sel->bind_param("s", $domani);
$sel->execute();
$rows = $sel->get_result()->fetch_all(MYSQLI_ASSOC);

$mark = $conn->prepare("UPDATE prenotazioni SET promemoria_inviato=1 WHERE id=?");
foreach ($rows as $r) {
    if (time() - $start > 20) break; // guard: esci prima che l'host tagli lo script
    [$subj, $html] = mail_promemoria($r['nome'], $r['data_appuntamento'], substr($r['ora_appuntamento'], 0, 5));
    if (invia_email($r['email'], $subj, $html, $r['nome'])) {
        $mark->bind_param("i", $r['id']);
        $mark->execute();
        $inviati++;
    }
}

echo "Promemoria inviati: $inviati / " . count($rows) . " (per domani $domani)\n";
$conn->close();
