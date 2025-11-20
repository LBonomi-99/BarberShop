<?php
// invia_prenotazione.php

// 1. Configurazione
$destinatario = "leonardobonomi5@gmail.com"; // <--- INSERISCI QUI LA TUA EMAIL
$oggetto_email = "Nuova Richiesta Appuntamento - Sito Web";

// 2. Verifica che il form sia stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Raccogli e pulisci i dati (Sicurezza)
    $nome = strip_tags(trim($_POST["name"]));
    $telefono = strip_tags(trim($_POST["phone"]));
    $data = strip_tags(trim($_POST["date"]));
    $orario = strip_tags(trim($_POST["time"]));
    $descrizione = strip_tags(trim($_POST["service-desc"]));

    // 4. Costruisci il corpo dell'email
    $messaggio = "Hai ricevuto una nuova richiesta di appuntamento:\n\n";
    $messaggio .= "--------------------------------------------------\n";
    $messaggio .= "CLIENTE: $nome\n";
    $messaggio .= "TELEFONO: $telefono\n";
    $messaggio .= "--------------------------------------------------\n";
    $messaggio .= "DATA RICHIESTA: $data\n";
    $messaggio .= "FASCIA ORARIA: $orario\n";
    $messaggio .= "SERVIZIO RICHIESTO:\n$descrizione\n";
    $messaggio .= "--------------------------------------------------\n";
    
    // 5. Intestazioni Email (Headers)
    // Il "From" dovrebbe idealmente essere un indirizzo del tuo dominio (es. noreply@tuosito.it)
    // Il "Reply-To" è l'email del cliente (se l'avessi chiesta) o il sistema predefinito.
    $headers = "From: Prenotazioni Web <noreply@MatteoBarabeCapelli.com>\r\n";
    $headers .= "Reply-To: $destinatario\r\n"; // Rispondi a te stesso o aggiungi campo email cliente

    // 6. Invio Email
    if (mail($destinatario, $oggetto_email, $messaggio, $headers)) {
        // Successo: Reindirizza alla home con parametro successo
        header("Location: index.html?status=success#prenota");
    } else {
        // Errore: Reindirizza alla home con parametro errore
        header("Location: index.html?status=error#prenota");
    }

} else {
    // Se qualcuno apre il file direttamente senza inviare il form
    header("Location: index.html");
}
?>