<?php
/**
 * Helper sicurezza condivisi: normalizzazione telefono, rate-limit (DB),
 * captcha Turnstile. Usati dal form pubblico e dal login admin.
 */

/** Normalizza un numero italiano a sole cifre, rimuovendo +39 / 0039. */
function normalizePhone(string $raw): string {
    $p = preg_replace('/\D+/', '', $raw); // solo cifre
    if (strpos($p, '0039') === 0)              $p = substr($p, 4);
    elseif (strpos($p, '39') === 0 && strlen($p) > 10) $p = substr($p, 2);
    return $p;
}

/**
 * IP del client per rate-limit / lockout login.
 * Usa SOLO REMOTE_ADDR: e l'IP della connessione TCP, non falsificabile.
 * X-Forwarded-For e scrivibile dal client -> usarlo permetterebbe di
 * cambiare "ident" a ogni richiesta e aggirare rate-limit e lockout.
 * Tophost serve via Apache diretto: REMOTE_ADDR = client reale.
 * (Dietro Cloudflare/proxy fidato: sostituire con CF-Connecting-IP o
 *  l'ultimo hop di XFF validato contro l'IP del proxy.)
 */
function client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr($ip, 0, 45);
}

/** True se gli hit recenti per (scope, ident) hanno raggiunto il limite. */
function rate_too_many(mysqli $conn, string $scope, string $ident, int $max, int $window_sec): bool {
    $stmt = @$conn->prepare("SELECT COUNT(*) AS n FROM rate_hits WHERE scope=? AND ident=? AND created_at > (NOW() - INTERVAL ? SECOND)");
    if (!$stmt) return false; // tabella assente => non bloccare
    $stmt->bind_param("ssi", $scope, $ident, $window_sec);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['n'] >= $max;
}

/** Registra un hit per (scope, ident). Pulisce a campione i record vecchi. */
function rate_hit(mysqli $conn, string $scope, string $ident): void {
    $stmt = @$conn->prepare("INSERT INTO rate_hits (scope, ident) VALUES (?, ?)");
    if ($stmt) { $stmt->bind_param("ss", $scope, $ident); $stmt->execute(); }
    if (random_int(1, 50) === 1) @$conn->query("DELETE FROM rate_hits WHERE created_at < (NOW() - INTERVAL 1 DAY)");
}

/** Verifica captcha Cloudflare Turnstile. True se valido O se non configurato (locale). */
function turnstile_ok(): bool {
    if (!defined('TURNSTILE_SECRET') || TURNSTILE_SECRET === '') return true; // skip in dev
    $token = $_POST['cf-turnstile-response'] ?? '';
    if ($token === '') return false;
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => TURNSTILE_SECRET,
            'response' => $token,
            'remoteip' => client_ip(),
        ]),
        CURLOPT_TIMEOUT        => 8,
    ]);
    $res = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    return !empty($res['success']);
}
