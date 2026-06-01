<?php
/**
 * Connessione DB centralizzata per gli endpoint pubblici
 * (invia_prenotazione.php, api_disponibilita.php, cron/reminders.php).
 *
 * Legge le credenziali da config.local.php (le stesse usate dall'admin),
 * cosi in produzione non restano hardcodate localhost/root/''.
 */

if (!defined('DB_HOST')) {
    $cfg = __DIR__ . '/../config.local.php';
    if (!is_file($cfg)) $cfg = __DIR__ . '/../config.sample.php';
    require_once $cfg;
}

/**
 * Apre la connessione. Ritorna null su errore (NON disattiva il report
 * mode: gli endpoint che dipendono dal throw su errno 1062 restano validi).
 */
function db_connect(): ?mysqli {
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $name = defined('DB_NAME') ? DB_NAME : 'barber_shop';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    try {
        return new mysqli($host, $user, $pass, $name);
    } catch (mysqli_sql_exception $e) {
        return null; // credenziali errate / DB irraggiungibile
    }
}
