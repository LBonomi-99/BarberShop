<?php
// invia_prenotazione.php - VERSIONE CON LIMITI ANTISPAM E VALIDAZIONE NOME
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
    $descrizione = $conn->real_escape_string($_POST['service-desc']);

    // --- 1. CONTROLLO LUNGHEZZA NOME (Max 40) ---
    if (strlen($nome) > 40) {
        header("Location: index.html?status=error_name_len#prenota");
        exit;
    }

    // --- 2. CONTROLLO LUNGHEZZA DETTAGLI (Max 100 - Già presente) ---
    if (strlen($descrizione) > 100) {
        header("Location: index.html?status=error_length#prenota");
        exit;
    }

    // --- 3. CONTROLLO PAROLE OFFENSIVE (BLACKLIST) ---
    $blacklist = ['parolaccia', 'insulto', 'stupido', 'scemo', 'truffa', 'spam', 'casino']; 
    $testo_check = strtolower($descrizione);
    foreach ($blacklist as $word) {
        if (strpos($testo_check, $word) !== false) {
            header("Location: index.html?status=error_badwords#prenota");
            exit;
        }
    }

    // --- 4. CONTROLLO LIMITE GIORNALIERO PER TELEFONO (Max 2) ---
    // Contiamo quante prenotazioni esistono già per questo numero in QUESTA data
    $sql_check_limit = "SELECT COUNT(*) as totale FROM prenotazioni WHERE telefono = '$telefono' AND data_appuntamento = '$data'";
    $result_limit = $conn->query($sql_check_limit);
    $row_limit = $result_limit->fetch_assoc();

    if ($row_limit['totale'] >= 2) {
        // Errore: Limite raggiunto
        header("Location: index.html?status=error_limit#prenota");
        exit;
    }

    // --- 5. INSERIMENTO DATABASE ---
    $sql = "INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato) 
            VALUES ('$nome', '$telefono', '$data', '$ora', '$descrizione', 'in_attesa')";

    if ($conn->query($sql) === TRUE) {
        
        // 6. INVIO EMAIL
        $oggetto = "Nuova Prenotazione: $nome - $data $ora";
        $messaggio = "Nuova richiesta dal sito web.\n\n";
        $messaggio .= "Nome: $nome\n";
        $messaggio .= "Tel: $telefono\n";
        $messaggio .= "Data: $data alle $ora\n";
        $messaggio .= "Servizio: $descrizione\n";
        
        $headers = "From: Sito Web <noreply@matteocavallara.it>\r\n";
        $headers .= "Reply-To: $email_barbiere\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail($email_barbiere, $oggetto, $messaggio, $headers);

        header("Location: index.html?status=success#prenota");

    } else {
        echo "Errore Database: " . $conn->error;
    }
}
$conn->close();
?>