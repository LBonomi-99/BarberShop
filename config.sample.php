<?php
/**
 * Configurazione segreta — COPIARE in `config.local.php` e compilare.
 * config.local.php NON va versionato (vedi .gitignore).
 *
 * Su Tophost: mettere config.local.php fuori dalla webroot se possibile,
 * altrimenti proteggerlo via .htaccess (Fase 4).
 */

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'barber_shop');
define('DB_USER', 'root');
define('DB_PASS', '');  // su Tophost: password reale del DB

// --- Password admin di default (solo primo avvio se admin_config vuota) ---
// Verra hashata e salvata; cambiarla subito dal pannello.
define('ADMIN_DEFAULT_PASSWORD', 'cambiami-al-primo-accesso');

// --- Captcha Cloudflare Turnstile (anti-bot form pubblico) ---
// Lasciare vuoti in locale: il captcha viene saltato.
define('TURNSTILE_SITEKEY', '');
define('TURNSTILE_SECRET',  '');

// --- Email transazionale (Brevo / Sendinblue API REST) ---
// Lasciare BREVO_API_KEY vuoto in locale: il mailer scrive su mail_log/ invece di inviare.
define('BREVO_API_KEY', '');
define('MAIL_FROM',      'noreply@matteocavallara.it');   // mittente (dominio allineato SPF/DKIM)
define('MAIL_FROM_NAME', 'Barba & Capelli Matteo');
define('MAIL_REPLY_TO',  'leonardobonomi949@gmail.com');  // risposte cliente -> barbiere

// --- Identita attivita (usata nei testi email) ---
define('SHOP_NAME',    'Barba & Capelli Matteo Cavallara');
define('SHOP_PHONE',   '0376 607445');
define('BARBER_EMAIL', 'leonardobonomi949@gmail.com');    // notifiche al barbiere

// --- Cron reminder: token segreto per proteggere l'endpoint pubblico ---
// Generarne uno lungo e casuale, usarlo nell'URL del pinger esterno (cron-job.org).
define('CRON_TOKEN', 'CAMBIA_QUESTO_TOKEN_LUNGO_E_CASUALE');
