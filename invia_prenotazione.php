<?php
// invia_prenotazione.php

// 1. CONFIGURAZIONE
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root'; 
$pass = '';     

// Email del Barbiere (dove ricevere le notifiche)
$email_barbiere = "leonardobonomi949@gmail.com"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $conn->real_escape_string($_POST['name']);
    $telefono = $conn->real_escape_string($_POST['phone']);
    $data = $conn->real_escape_string($_POST['date']);
    $ora = $conn->real_escape_string($_POST['time']); // Ora arriva dal menu a tendina
    $descrizione = $conn->real_escape_string($_POST['service-desc']);

    // A. INSERIMENTO NEL DATABASE (Stato: in_attesa)
    $sql = "INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato) 
            VALUES ('$nome', '$telefono', '$data', '$ora', '$descrizione', 'in_attesa')";

    if ($conn->query($sql) === TRUE) {
        
        // B. INVIO EMAIL AL BARBIERE
        $oggetto = "Nuova Richiesta Appuntamento: $nome";
        $messaggio = "Hai ricevuto una nuova richiesta dal sito.\n\n";
        $messaggio .= "Cliente: $nome\n";
        $messaggio .= "Telefono: $telefono\n";
        $messaggio .= "Data: " . date('d/m/Y', strtotime($data)) . "\n";
        $messaggio .= "Ora: $ora\n";
        $messaggio .= "Servizio: $descrizione\n\n";
        $messaggio .= "Vai al pannello admin per accettare o rifiutare.";
        
        $headers = "From: Sito Web <noreply@matteocavallara.it>";

        // Tenta l'invio della mail (funziona se il server è configurato, su XAMPP potrebbe non partire senza config)
        @mail($email_barbiere, $oggetto, $messaggio, $headers);

        // Reindirizza al sito con successo
        header("Location: index.html?status=success#prenota");
    } else {
        echo "Errore: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>