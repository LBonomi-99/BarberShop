<?php
// invia_prenotazione.php - VERSIONE CON VALIDAZIONE TESTO E SICUREZZA
session_start();

// CONFIGURAZIONE
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root'; 
$pass = '';     

$email_barbiere = "leonardobonomi949@gmail.com"; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccogliamo i dati
    $nome = $conn->real_escape_string($_POST['name']);
    $telefono = $conn->real_escape_string($_POST['phone']);
    $data = $conn->real_escape_string($_POST['date']);
    $ora = $conn->real_escape_string($_POST['time']);
    $descrizione = $conn->real_escape_string($_POST['service-desc']); // Pulisce SQL Injection base

    // --- 1. CONTROLLO LUNGHEZZA ---
    if (strlen($descrizione) > 100) {
        // Errore: Testo troppo lungo
        header("Location: index.html?status=error_length#prenota");
        exit;
    }

    // --- 2. CONTROLLO PAROLE OFFENSIVE (BLACKLIST) ---
    // Aggiungi qui le parole che vuoi bloccare (tutto minuscolo)
    $blacklist = ['parolaccia', 'insulto', 'stupido', 'scemo', 'truffa', 'spam', 'casino', 'frocio',  'puttana', 'cazzo', 'merda', 'stronzo', 'vaffanculo', 'bastardo', 'ignorante', 'idiota', 'troia', 'culattone', 'zecca', 'balordo', 'cretino']; 
    
    $testo_check = strtolower($descrizione);
    foreach ($blacklist as $word) {
        if (strpos($testo_check, $word) !== false) {
            // Errore: Parola trovata
            header("Location: index.html?status=error_badwords#prenota");
            exit;
        }
    }

    // --- 3. INSERIMENTO DATABASE ---
    $sql = "INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato) 
            VALUES ('$nome', '$telefono', '$data', '$ora', '$descrizione', 'in_attesa')";

    if ($conn->query($sql) === TRUE) {
        
        // 4. INVIO EMAIL
        $oggetto = "Nuova Prenotazione: $nome - $data $ora";
        $messaggio = "Nuova richiesta dal sito web.\n\n";
        $messaggio .= "Nome: $nome\n";
        $messaggio .= "Tel: $telefono\n";
        $messaggio .= "Data: $data alle $ora\n";
        $messaggio .= "Servizio: $descrizione\n";
        
        $headers = "From: Sito Web <noreply@matteocavallara.it>\r\n";
        $headers .= "Reply-To: $email_barbiere\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Invio mail (silenzioso per l'utente, loggato dal sistema)
        @mail($email_barbiere, $oggetto, $messaggio, $headers);

        // Successo
        header("Location: index.html?status=success#prenota");

    } else {
        echo "Errore Database: " . $conn->error;
    }
}
$conn->close();
?>