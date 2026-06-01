<?php
date_default_timezone_set('Europe/Rome');

// --- Segreti / costanti app (DB creds, mail, captcha, token) ---
$cfg = __DIR__ . '/../config.local.php';
if (!is_file($cfg)) $cfg = __DIR__ . '/../config.sample.php';
require_once $cfg;

// --- Sessione hardened ---
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
session_start();

// --- CSRF (synchronizer token) ---
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_token(): string { return $_SESSION['csrf'] ?? ''; }
/** Hidden input per i form POST. */
function csrf_tag(): string { return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">'; }
/** Query da appendere ai link GET-azione (es. ...&id=5 . csrf_q()). */
function csrf_q(): string { return '&t=' . urlencode(csrf_token()); }
/** Valida il token da form (csrf) o link (t). */
function csrf_ok(): bool {
    $t = $_POST['csrf'] ?? $_GET['t'] ?? '';
    return is_string($t) && $t !== '' && hash_equals(csrf_token(), $t);
}

// --- Connessione DB ---
$db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
$db_name = defined('DB_NAME') ? DB_NAME : 'barber_shop';
$db_user = defined('DB_USER') ? DB_USER : 'root';
$db_pass = defined('DB_PASS') ? DB_PASS : '';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Errore DB"); }
