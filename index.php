<?php
// Assicurati che questo percorso sia corretto rispetto a dove si trova index.php
require_once 'admin_components/config.php'; 

// Recupera Chi Siamo
$chi_siamo_qry = $conn->query("SELECT content_text FROM site_content WHERE section_key='chi_siamo'");
$chi_siamo = $chi_siamo_qry->fetch_assoc()['content_text'];
$chi_siamo_html = nl2br(htmlspecialchars($chi_siamo));

// Recupera Listino
$res_listino = $conn->query("SELECT * FROM services_list ORDER BY sort_order ASC, id ASC");
$listino = [];
while($row = $res_listino->fetch_assoc()){
    $listino[$row['category']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barba & Capelli Matteo Cavallara</title>
    
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.matteocavallara.it/">
    <meta property="og:title" content="Barba & Capelli Matteo Cavallara">
    <meta property="og:description" content="Prenota il tuo taglio online. Tradizione e stile a Cerlongo.">
    <meta property="og:image" content="https://www.matteocavallara.it/img/hero-bg.jpg">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://www.matteocavallara.it/">
    <meta property="twitter:title" content="Barba & Capelli Matteo Cavallara">
    <meta property="twitter:description" content="Prenota il tuo taglio online. Tradizione e stile a Cerlongo.">
    <meta property="twitter:image" content="https://www.matteocavallara.it/img/hero-bg.jpg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header>
        <div class="container">
            <nav>
                <div class="logo">MATTEO CAVALLARA</div>
                <div class="nav-links">
                    <a href="#filosofia">Chi Siamo</a>
                    <a href="#servizi">Listino</a>
                    <a href="#contatti">Contatti</a>
                    <a href="#prenota" style="color: var(--col-accent); font-weight: bold;">PRENOTA</a>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Barba & Capelli<br>Matteo Cavallara</h1>
            <p>L'Arte della Tradizione, lo Stile di Oggi.</p>
            <a href="#prenota" class="btn-cta">Prenota Ora</a>
        </div>
    </section>

    <section class="section-padding philosophy" id="filosofia">
        <div class="container">
            <div class="philosophy-grid">
                <div class="philosophy-img">
                    <img src="img/matteo-profile.jpg" alt="Matteo Cavallara">
                </div>
                <div class="philosophy-text">
                    <h2 class="section-title" style="text-align: left;">Una tradizione di famiglia</h2>
                    <div style="font-size: 1.1rem; color: #555;">
                        <?php echo $chi_siamo_html; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding services" id="servizi">
        <div class="container">
            <h2 class="section-title">Il Listino</h2>
            <span class="section-subtitle">Qualità e Trasparenza</span>

            <div class="services-list">
                <?php foreach($listino as $categoria => $servizi): ?>
                    <div class="service-category">
                        <h3 class="category-title"><?php echo $categoria; ?></h3>
                        
                        <?php foreach($servizi as $servizio): ?>
                        <div class="service-item">
                            <span class="service-name">
                                <?php echo $servizio['name']; ?>
                                <?php if(!empty($servizio['description'])): ?>
                                    <br><span style="font-size:0.8em; font-weight:normal; font-style:italic; color:#777;"><?php echo $servizio['description']; ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="service-dots"></span>
                            <span class="service-price">€ <?php echo number_format($servizio['price'], 2, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>
    </section>

    <section class="section-padding booking" id="prenota">
        <div class="container">
            <h2 class="section-title">Richiedi Appuntamento</h2>
            <span class="section-subtitle">Compila il modulo. Ti risponderò per confermare.</span>

            <form class="booking-form" action="invia_prenotazione.php" method="POST" id="bookingForm">
                
                <div class="form-group">
                    <label for="date">Data Desiderata</label>
                    <input type="date" id="date" name="date" required onchange="caricaMenuOrari()">
                </div>

                <div class="form-group">
                    <label for="time">Orario (30 min intervalli)</label>
                    <select id="time" name="time" required>
                        <option value="">-- Seleziona prima una data --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service-desc">Dettagli Servizio (Max 100 caratteri)</label>
                    <textarea id="service-desc" name="service-desc" placeholder="Es: Taglio sfumato..." required maxlength="100" oninput="updateCount(this)"></textarea>
                    <div class="char-count" id="charCount">0/100</div>
                    
                    <div id="textError" class="error-message">
                        <i class="fas fa-ban"></i> Il testo contiene parole non consentite. Modifica i dettagli.
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Nome e Cognome (Max 40 caratteri)</label>
                    <input type="text" id="name" name="name" required maxlength="40">
                </div>

                <div class="form-group">
                    <label for="phone">Telefono (Mobile, per WhatsApp)</label>
                    <input type="tel" id="phone" name="phone" placeholder="Es. 333 1234567" required>
                    <div id="phoneError" class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> Impossibile inviare: inserisci un numero di cellulare valido per WhatsApp (Es. 333...).
                    </div>
                </div>

                <button type="submit" class="btn-cta" style="width: 100%; border: none;">Invia Richiesta</button>
            </form>
        </div>
    </section>

    <section class="section-padding calendar-section">
        <div class="container">
            <h2 class="section-title">Disponibilità</h2>
            <span class="section-subtitle">Verifica gli orari occupati in tempo reale</span>
            <div class="calendar-container" style="position: relative; padding-bottom: 75%; height: 0; overflow: hidden; max-width: 800px; margin: 0 auto; border: 2px solid var(--col-accent);">
                <iframe src="https://calendar.google.com/calendar/embed?src=leonardobonomi949%40gmail.com&ctz=Europe%2FRome" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" frameborder="0" scrolling="no"></iframe>
            </div>
        </div>
    </section>

    <footer id="contatti">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Dove Siamo</h3>
                    <p>Via Chiesa, 18, 46044 Cerlongo (MN)</p>
                    <iframe src="https://maps.google.com/maps?q=Via+Chiesa+18+Cerlongo&t=&z=15&ie=UTF8&iwloc=&output=embed" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="footer-col">
                    <h3>Orari Apertura</h3>
                    <ul>
                        <li>Mar-Gio: 08:00–12:30, 15:00–19:30</li>
                        <li>Mer-Sab: 08:30–18:30 (Sab fino 18:30)</li>
                        <li>Ven: 08:00–19:30</li>
                        <li>Dom-Lun: Chiuso</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contatti</h3>
                    <p>0376 607445</p>
                    <p>leonardobonomi949@gmail.com</p>
                </div>
            </div>
            <div style="margin-top: 50px; font-size: 0.8rem; text-align: center; border-top: 1px solid #333; padding-top: 20px;">
                © 2025 Matteo Cavallara Barber Shop. <a href="privacy.html" style="color: #999;">Privacy Policy</a>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>

</body>
</html>