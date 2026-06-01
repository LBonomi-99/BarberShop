<?php
/**
 * Email transazionale via Brevo (Sendinblue) API REST.
 * Se BREVO_API_KEY e vuoto (locale): scrive su mail_log/ invece di inviare.
 * Niente PHPMailer / SMTP: solo curl -> evita porte bloccate e CVE da patchare a mano.
 */

// Carica config segreta (locale o, in mancanza, sample come ultima spiaggia).
if (!defined('BREVO_API_KEY')) {
    $cfg = __DIR__ . '/../config.local.php';
    if (!is_file($cfg)) $cfg = __DIR__ . '/../config.sample.php';
    require_once $cfg;
}

/** Invia una email. Ritorna true se accettata (o loggata in locale). */
function invia_email(string $to, string $subject, string $html, string $to_name = ''): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    // --- Modalita locale / fallback: log su file, nessun invio reale ---
    if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '') {
        $dir = __DIR__ . '/../mail_log';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
            @file_put_contents($dir . '/.htaccess', "Require all denied\n"); // log non accessibili via URL
        }
        $line = "==== " . date('Y-m-d H:i:s') . " ====\nTO: $to_name <$to>\nSUBJ: $subject\n\n$html\n\n";
        @file_put_contents($dir . '/' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    // --- Invio reale via Brevo ---
    $payload = [
        'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
        'to'          => [['email' => $to, 'name' => $to_name ?: $to]],
        'replyTo'     => ['email' => MAIL_REPLY_TO, 'name' => MAIL_FROM_NAME],
        'subject'     => $subject,
        'htmlContent' => $html,
    ];
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 12,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}

/** Data ITA leggibile: "2026-06-02" -> "martedi 2 giugno 2026". */
function fmt_data_it(string $ymd): string {
    $ts = strtotime($ymd);
    if (!$ts) return $ymd;
    $giorni = ['domenica','lunedi','martedi','mercoledi','giovedi','venerdi','sabato'];
    $mesi   = ['','gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'];
    return $giorni[(int)date('w',$ts)] . ' ' . (int)date('j',$ts) . ' ' . $mesi[(int)date('n',$ts)] . ' ' . date('Y',$ts);
}

/** Wrapper HTML brandizzato attorno al corpo del messaggio. */
function email_layout(string $titolo, string $corpo_html): string {
    $shop = htmlspecialchars(defined('SHOP_NAME') ? SHOP_NAME : 'Barber Shop');
    return '<!DOCTYPE html><html lang="it"><body style="margin:0;background:#f4f1ec;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">'
        . '<div style="max-width:560px;margin:0 auto;padding:24px;">'
        . '<div style="background:#1b1b1b;color:#fff;padding:20px 24px;border-radius:14px 14px 0 0;">'
        . '<div style="font-size:13px;letter-spacing:2px;color:#B8860B;text-transform:uppercase;">' . $shop . '</div>'
        . '<h1 style="margin:6px 0 0;font-size:21px;font-weight:600;">' . htmlspecialchars($titolo) . '</h1></div>'
        . '<div style="background:#fff;padding:24px;border-radius:0 0 14px 14px;line-height:1.6;font-size:15px;">'
        . $corpo_html
        . '</div>'
        . '<p style="text-align:center;color:#999;font-size:12px;margin-top:18px;">'
        . $shop . (defined('SHOP_PHONE') ? ' &middot; ' . htmlspecialchars(SHOP_PHONE) : '') . '</p>'
        . '</div></body></html>';
}

/** [subject, html] per ogni evento. $nome/$servizio gia sanificati a monte o via htmlspecialchars qui. */
function mail_conferma(string $nome, string $data, string $ora, string $servizio): array {
    $n = htmlspecialchars($nome); $s = htmlspecialchars($servizio);
    $corpo = "<p>Ciao <strong>$n</strong>,</p>"
        . "<p>il tuo appuntamento &egrave; <strong>confermato</strong>:</p>"
        . "<p style='background:#f4f1ec;border-left:4px solid #B8860B;padding:12px 16px;border-radius:6px;'>"
        . "📅 " . fmt_data_it($data) . "<br>🕐 ore <strong>$ora</strong>" . ($s ? "<br>✂️ $s" : "") . "</p>"
        . "<p>Se non puoi venire, avvisaci in anticipo. A presto!</p>";
    return ["Appuntamento confermato — $ora del " . fmt_data_it($data), email_layout("Appuntamento confermato", $corpo)];
}

function mail_richiesta(string $nome, string $data, string $ora): array {
    $n = htmlspecialchars($nome);
    $corpo = "<p>Ciao <strong>$n</strong>,</p>"
        . "<p>abbiamo ricevuto la tua richiesta per <strong>" . fmt_data_it($data) . "</strong> alle <strong>$ora</strong>.</p>"
        . "<p>Ti invieremo una conferma appena possibile.</p>";
    return ["Richiesta ricevuta — " . fmt_data_it($data), email_layout("Richiesta ricevuta", $corpo)];
}

function mail_rifiuto(string $nome, string $data, string $ora): array {
    $n = htmlspecialchars($nome);
    $corpo = "<p>Ciao <strong>$n</strong>,</p>"
        . "<p>purtroppo non possiamo confermare l'appuntamento del <strong>" . fmt_data_it($data) . "</strong> alle <strong>$ora</strong>.</p>"
        . "<p>Riprova con un altro orario dal sito, ci scusiamo per il disagio.</p>";
    return ["Richiesta non disponibile — " . fmt_data_it($data), email_layout("Orario non disponibile", $corpo)];
}

function mail_annullo(string $nome, string $data, string $ora): array {
    $n = htmlspecialchars($nome);
    $corpo = "<p>Ciao <strong>$n</strong>,</p>"
        . "<p>dobbiamo annullare l'appuntamento del <strong>" . fmt_data_it($data) . "</strong> alle <strong>$ora</strong> causa imprevisto.</p>"
        . "<p>Ci dispiace. Puoi riprenotare quando vuoi dal sito.</p>";
    return ["Appuntamento annullato — " . fmt_data_it($data), email_layout("Appuntamento annullato", $corpo)];
}

function mail_promemoria(string $nome, string $data, string $ora): array {
    $n = htmlspecialchars($nome);
    $corpo = "<p>Ciao <strong>$n</strong>,</p>"
        . "<p>ti ricordiamo il tuo appuntamento di <strong>domani</strong>:</p>"
        . "<p style='background:#f4f1ec;border-left:4px solid #B8860B;padding:12px 16px;border-radius:6px;'>"
        . "📅 " . fmt_data_it($data) . "<br>🕐 ore <strong>$ora</strong></p>"
        . "<p>A domani!</p>";
    return ["Promemoria appuntamento — domani alle $ora", email_layout("Promemoria appuntamento", $corpo)];
}

function mail_notifica_barbiere(string $nome, string $tel, string $email, string $data, string $ora, string $servizio, string $stato): array {
    $n = htmlspecialchars($nome); $s = htmlspecialchars($servizio);
    $t = htmlspecialchars($tel);  $e = htmlspecialchars($email);
    $etichetta = $stato === 'accettato' ? 'CONFERMATA (auto)' : 'DA APPROVARE';
    $corpo = "<p><strong>Nuova prenotazione — $etichetta</strong></p>"
        . "<p style='background:#f4f1ec;padding:12px 16px;border-radius:6px;'>"
        . "👤 $n<br>📞 $t<br>" . ($e ? "✉️ $e<br>" : "")
        . "📅 " . fmt_data_it($data) . "<br>🕐 $ora" . ($s ? "<br>✂️ $s" : "") . "</p>";
    return ["Nuova prenotazione: $nome — $data $ora", email_layout("Nuova prenotazione", $corpo)];
}
