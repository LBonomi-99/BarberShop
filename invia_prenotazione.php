<?php
session_start();
date_default_timezone_set('Europe/Rome');

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/availability.php';
require_once __DIR__ . '/lib/mailer.php';
require_once __DIR__ . '/lib/security.php';

$conn = db_connect();
if (!$conn) { header("Location: index.php?status=error#prenota"); exit; }

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: index.php"); exit; }

// Anti-abuso: captcha + rate-limit per IP
if (!turnstile_ok()) { header("Location: index.php?status=error_captcha#prenota"); exit; }
$ip = client_ip();
if (rate_too_many($conn, 'form', $ip, 5, 600)) { header("Location: index.php?status=error_rate#prenota"); exit; }
rate_hit($conn, 'form', $ip);

$nome        = trim($_POST['name']         ?? '');
$telefono    = trim($_POST['phone']        ?? '');
$email       = trim($_POST['email']        ?? '');
$data        = trim($_POST['date']         ?? '');
$ora         = trim($_POST['time']         ?? '');
$descrizione = trim($_POST['service-desc'] ?? '');

// 1. Validazione nome
if (strlen($nome) > 40) { header("Location: index.php?status=error_name_len#prenota"); exit; }

// 1b. Validazione email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { header("Location: index.php?status=error_email#prenota"); exit; }

// 1c. Normalizza + valida telefono (chiude il bypass del limite con numeri formattati diversi)
$telefono = normalizePhone($telefono);
if (!preg_match('/^3\d{9}$/', $telefono)) { header("Location: index.php?status=error_phone#prenota"); exit; }

// 2. Lunghezza descrizione
if (strlen($descrizione) > 100) { header("Location: index.php?status=error_length#prenota"); exit; }

// 3. Formato data e ora
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $ora)) {
    header("Location: index.php?status=error#prenota"); exit;
}

// 4. Blacklist parole
$blacklist   = ['parolaccia','insulto','stupido','scemo','truffa','spam','casino','troia','cazzo','merda','stronzo','vaffanculo','bastardo','ignorante','idiota','culattone','zecca','balordo','cretino'];
$testo_check = strtolower($descrizione);
foreach ($blacklist as $word) {
    if (strpos($testo_check, $word) !== false) { header("Location: index.php?status=error_badwords#prenota"); exit; }
}

// 5. Limite giornaliero (max 2 per numero)
$stmt = $conn->prepare("SELECT COUNT(*) as totale FROM prenotazioni WHERE telefono=? AND data_appuntamento=?");
$stmt->bind_param("ss", $telefono, $data);
$stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['totale'] >= 2) {
    header("Location: index.php?status=error_limit#prenota"); exit;
}

// 6. Ri-validazione slot lato server (NON fidarsi del client)
if (!in_array($ora, slot_disponibili($conn, $data), true)) {
    header("Location: index.php?status=error_slot#prenota"); exit;
}

// 7. Modalita conferma: auto => accettato subito, approval => in_attesa
$stato = (getBookingMode($conn) === 'auto') ? 'accettato' : 'in_attesa';

// 8. Inserimento a prova di race: prenotazione + occupazione slot in transazione.
//    slot_occupati.UNIQUE(data,ora) e l'arbitro: se due richieste corrono insieme,
//    solo una passa, l'altra prende errno 1062 e viene respinta.
// NB: mysqli puo lanciare eccezioni (PHP 8.1+ default) o ritornare false: gestiti entrambi.
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO prenotazioni (nome, telefono, email, data_appuntamento, ora_appuntamento, servizio, stato) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nome, $telefono, $email, $data, $ora, $descrizione, $stato);
    if (!$stmt->execute()) throw new Exception('insert_pren');
    $pren_id = $conn->insert_id;

    $occ = $conn->prepare("INSERT INTO slot_occupati (data, ora, prenotazione_id) VALUES (?, ?, ?)");
    $occ->bind_param("ssi", $data, $ora, $pren_id);
    if (!$occ->execute()) throw new Exception('insert_slot');

    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // 1062 = slot appena occupato da una richiesta concorrente
    $dest = ($conn->errno === 1062) ? 'error_slot' : 'error';
    header("Location: index.php?status=$dest#prenota"); exit;
} catch (Exception $e) {
    $conn->rollback();
    header("Location: index.php?status=error#prenota"); exit;
}

// 9. Email automatiche: cliente (conferma o richiesta-ricevuta) + notifica barbiere.
if ($stato === 'accettato') {
    [$subj, $html] = mail_conferma($nome, $data, $ora, $descrizione);
} else {
    [$subj, $html] = mail_richiesta($nome, $data, $ora);
}
invia_email($email, $subj, $html, $nome);

[$bsubj, $bhtml] = mail_notifica_barbiere($nome, $telefono, $email, $data, $ora, $descrizione, $stato);
invia_email(defined('BARBER_EMAIL') ? BARBER_EMAIL : MAIL_REPLY_TO, $bsubj, $bhtml, MAIL_FROM_NAME);

$ok_status = ($stato === 'accettato') ? 'success_confirmed' : 'success';
header("Location: index.php?status=$ok_status#prenota");
$conn->close();
