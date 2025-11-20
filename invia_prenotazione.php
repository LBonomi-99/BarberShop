<?php
// invia_prenotazione.php

// CONFIGURAZIONE DATABASE
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root'; // Cambia con il tuo user (es. su hosting spesso è diverso)
$pass = '';     // Cambia con la tua password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $conn->real_escape_string($_POST['name']);
    $telefono = $conn->real_escape_string($_POST['phone']);
    $data = $conn->real_escape_string($_POST['date']);
    $ora = $conn->real_escape_string($_POST['time']);
    $descrizione = $conn->real_escape_string($_POST['service-desc']);

    // Inserimento nel Database
    $sql = "INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio) 
            VALUES ('$nome', '$telefono', '$data', '$ora', '$descrizione')";

    if ($conn->query($sql) === TRUE) {
        // Reindirizza con successo
        header("Location: index.html?status=success#prenota");
    } else {
        echo "Errore: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>