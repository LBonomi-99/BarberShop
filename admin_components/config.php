<?php
session_start();
date_default_timezone_set('Europe/Rome');

// --- CONFIGURAZIONE ---
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Errore DB"); }
?>