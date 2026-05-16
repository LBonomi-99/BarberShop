<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'barber_shop');
if ($conn->connect_error) { header("Location: index.php?status=error#prenota"); exit; }

if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: index.php"); exit; }

$nome        = trim($_POST['name']         ?? '');
$telefono    = trim($_POST['phone']        ?? '');
$data        = trim($_POST['date']         ?? '');
$ora         = trim($_POST['time']         ?? '');
$descrizione = trim($_POST['service-desc'] ?? '');

// 1. Validazione nome
if (strlen($nome) > 40) { header("Location: index.php?status=error_name_len#prenota"); exit; }

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

// 6. Inserimento
$stmt = $conn->prepare("INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato) VALUES (?, ?, ?, ?, ?, 'in_attesa')");
$stmt->bind_param("sssss", $nome, $telefono, $data, $ora, $descrizione);

if ($stmt->execute()) {
    $email_barbiere = "leonardobonomi949@gmail.com";
    $oggetto        = "Nuova Prenotazione: $nome - $data $ora";
    $messaggio      = "Nuova richiesta dal sito web.\n\nNome: $nome\nTel: $telefono\nData: $data alle $ora\nServizio: $descrizione";
    $headers        = "From: Sito Web <noreply@matteocavallara.it>\r\nX-Mailer: PHP/" . phpversion();
    @mail($email_barbiere, $oggetto, $messaggio, $headers);
    header("Location: index.php?status=success#prenota");
} else {
    header("Location: index.php?status=error#prenota");
}
$conn->close();
?>
