<?php
require_once 'admin_components/config.php';

// Chi Siamo
$chi_siamo_qry = $conn->query("SELECT content_text FROM site_content WHERE section_key='chi_siamo'");
$chi_siamo     = $chi_siamo_qry->fetch_assoc()['content_text'];
$chi_siamo_html = nl2br(htmlspecialchars($chi_siamo));

// Listino
$res_listino = $conn->query("SELECT * FROM services_list ORDER BY sort_order ASC, id ASC");
$listino = [];
while ($row = $res_listino->fetch_assoc()) { $listino[$row['category']][] = $row; }

// Recensioni
$res_recensioni = $conn->query("SELECT * FROM recensioni ORDER BY data_recensione DESC LIMIT 3");

// Social links
$social_instagram = $social_facebook = '';
$res_social = $conn->query("SELECT section_key, content_text FROM site_content WHERE section_key IN ('social_instagram','social_facebook')");
if ($res_social) {
    while ($s = $res_social->fetch_assoc()) {
        if ($s['section_key'] == 'social_instagram') $social_instagram = $s['content_text'];
        if ($s['section_key'] == 'social_facebook')  $social_facebook  = $s['content_text'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barba & Capelli Matteo Cavallara</title>

    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <meta property="og:type"        content="website">
    <meta property="og:url"         content="https://www.matteocavallara.it/">
    <meta property="og:title"       content="Barba & Capelli Matteo Cavallara">
    <meta property="og:description" content="Prenota il tuo taglio online. Tradizione e stile a Cerlongo.">
    <meta property="og:image"       content="https://www.matteocavallara.it/img/hero-bg.jpg">
    <meta property="twitter:card"   content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (defined('TURNSTILE_SITEKEY') && TURNSTILE_SITEKEY !== ''): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>

    <header>
        <div class="container">
            <nav>
                <div class="logo">MATTEO CAVALLARA</div>
                <div class="nav-links" id="navLinks">
                    <a href="#filosofia">Chi Siamo</a>
                    <a href="#servizi">Listino</a>
                    <a href="#contatti">Contatti</a>
                    <a href="#prenota" class="nav-cta">Prenota</a>
                </div>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </nav>
        </div>
    </header>

    <!-- HERO — ASYMMETRIC SPLIT -->
    <section class="hero" id="home">
        <div class="hero-content">
            <span class="hero-eyebrow">Cerlongo &middot; Mantova</span>
            <h1>Barba &amp; Capelli<br>Matteo Cavallara</h1>
            <p>L&rsquo;Arte della Tradizione, lo Stile di Oggi.</p>
            <a href="#prenota" class="btn-cta">Prenota Ora</a>
        </div>
        <div class="hero-visual"></div>
    </section>

    <!-- CHI SIAMO -->
    <section class="section-padding philosophy reveal" id="filosofia">
        <div class="container">
            <div class="philosophy-grid">
                <div class="philosophy-img reveal reveal-d1">
                    <img src="img/matteo-profile.jpg" alt="Matteo Cavallara">
                </div>
                <div class="philosophy-text reveal reveal-d2">
                    <h2 class="section-title">Una tradizione di famiglia</h2>
                    <div class="body-text"><?php echo $chi_siamo_html; ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTINO -->
    <section class="section-padding services reveal" id="servizi">
        <div class="container">
            <h2 class="section-title">Il Listino</h2>
            <span class="section-subtitle">Qualit&agrave; e Trasparenza</span>
            <div class="services-list">
                <?php foreach ($listino as $categoria => $servizi): ?>
                <div class="service-category reveal">
                    <h3 class="category-title"><?php echo htmlspecialchars($categoria); ?></h3>
                    <?php foreach ($servizi as $servizio): ?>
                    <div class="service-item">
                        <span class="service-name">
                            <?php echo htmlspecialchars($servizio['name']); ?>
                            <?php if (!empty($servizio['description'])): ?>
                                <br><span style="font-size:0.8em;font-weight:400;font-style:italic;color:#777;"><?php echo htmlspecialchars($servizio['description']); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="service-dots"></span>
                        <span class="service-price">&euro; <?php echo number_format($servizio['price'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- RECENSIONI -->
    <?php if ($res_recensioni && $res_recensioni->num_rows > 0): ?>
    <section class="section-padding reviews reveal" id="recensioni">
        <div class="container">
            <h2 class="section-title">Cosa Dicono i Clienti</h2>
            <span class="section-subtitle">La soddisfazione parla per noi</span>
            <div class="reviews-grid">
                <?php $delay = 1; while ($rec = $res_recensioni->fetch_assoc()): ?>
                <div class="review-card reveal reveal-d<?php echo $delay; ?>">
                    <span class="review-quote">&ldquo;</span>
                    <div class="review-stars">
                        <?php for ($i = 0; $i < (int)$rec['voto']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                    </div>
                    <p class="review-text"><?php echo nl2br(htmlspecialchars($rec['testo'])); ?></p>
                    <span class="review-author"><?php echo htmlspecialchars($rec['nome_cliente']); ?></span>
                </div>
                <?php $delay++; endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- PRENOTAZIONE -->
    <section class="section-padding booking reveal" id="prenota">
        <div class="container">
            <h2 class="section-title">Richiedi Appuntamento</h2>
            <span class="section-subtitle">Compila il modulo. Ti risponder&ograve; per confermare.</span>

            <form class="booking-form" action="invia_prenotazione.php" method="POST" id="bookingForm" novalidate>

                <div class="form-group">
                    <label for="date">Data Desiderata</label>
                    <input type="date" id="date" name="date" required aria-required="true" onchange="caricaMenuOrari()">
                </div>

                <div class="form-group">
                    <label for="time">Orario</label>
                    <select id="time" name="time" required aria-required="true">
                        <option value="">-- Seleziona prima una data --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service-desc">Dettagli Servizio <span style="font-weight:400;color:#999;">(max 100 caratteri)</span></label>
                    <textarea id="service-desc" name="service-desc" placeholder="Es: Taglio sfumato..." required maxlength="100" aria-required="true" oninput="updateCount(this)" rows="3"></textarea>
                    <div class="char-count" id="charCount">0 / 100</div>
                    <div id="textError" class="error-message"><i class="fas fa-ban"></i> Il testo contiene parole non consentite.</div>
                </div>

                <div class="form-group">
                    <label for="name">Nome e Cognome <span style="font-weight:400;color:#999;">(max 40 caratteri)</span></label>
                    <input type="text" id="name" name="name" required maxlength="40" aria-required="true">
                </div>

                <div class="form-group">
                    <label for="phone">Telefono <span style="font-weight:400;color:#999;">(cellulare, per WhatsApp)</span></label>
                    <input type="tel" id="phone" name="phone" placeholder="Es. 333 1234567" required aria-required="true" aria-describedby="phoneError">
                    <div id="phoneError" class="error-message"><i class="fas fa-exclamation-triangle"></i> Inserisci un numero di cellulare valido (Es. 333...).</div>
                </div>

                <div class="form-group">
                    <label for="email">Email <span style="font-weight:400;color:#999;">(per la conferma)</span></label>
                    <input type="email" id="email" name="email" placeholder="nome@email.it" required aria-required="true" aria-describedby="emailError">
                    <div id="emailError" class="error-message"><i class="fas fa-exclamation-triangle"></i> Inserisci un indirizzo email valido.</div>
                </div>

                <?php if (defined('TURNSTILE_SITEKEY') && TURNSTILE_SITEKEY !== ''): ?>
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(TURNSTILE_SITEKEY); ?>" style="margin-bottom:16px;"></div>
                <?php endif; ?>

                <button type="submit" class="btn-cta" style="width:100%;border:none;">Invia Richiesta</button>
            </form>
        </div>
    </section>

    <!-- DISPONIBILITA' -->
    <section class="section-padding calendar-section reveal">
        <div class="container">
            <h2 class="section-title">Disponibilit&agrave;</h2>
            <span class="section-subtitle">Verifica gli orari occupati in tempo reale</span>
            <div class="calendar-container">
                <iframe src="https://calendar.google.com/calendar/embed?src=leonardobonomi949%40gmail.com&ctz=Europe%2FRome" scrolling="no"></iframe>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contatti">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Dove Siamo</h3>
                    <p>Via Chiesa, 18<br>46044 Cerlongo (MN)</p>
                    <iframe src="https://maps.google.com/maps?q=Via+Chiesa+18+Cerlongo&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="180" style="border:0;margin-top:12px;" allowfullscreen loading="lazy"></iframe>
                </div>
                <div class="footer-col">
                    <h3>Orari di Apertura</h3>
                    <ul>
                        <li>Mart &amp; Giov: 08:00&ndash;12:30, 15:00&ndash;19:30</li>
                        <li>Mercoled&igrave;: 08:30&ndash;18:30</li>
                        <li>Venerd&igrave;: 08:00&ndash;19:30</li>
                        <li>Sabato: 08:00&ndash;18:30</li>
                        <li>Dom &amp; Lun: Chiuso</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contatti</h3>
                    <p>0376 607445</p>
                    <p><a href="mailto:leonardobonomi949@gmail.com">leonardobonomi949@gmail.com</a></p>
                    <?php if ($social_instagram || $social_facebook): ?>
                    <div class="social-links">
                        <?php if ($social_instagram): ?>
                        <a href="<?php echo htmlspecialchars($social_instagram); ?>" class="social-link" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($social_facebook): ?>
                        <a href="<?php echo htmlspecialchars($social_facebook); ?>" class="social-link" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; 2025 Matteo Cavallara Barber Shop &nbsp;&middot;&nbsp;
                <a href="privacy.html" style="color:#666;">Privacy Policy</a>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
