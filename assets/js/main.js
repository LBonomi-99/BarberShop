// Contatore caratteri
function updateCount(field) {
    const count = field.value.length;
    document.getElementById('charCount').textContent = count + ' / 100';
}

// Scroll reveal via IntersectionObserver
(function () {
    const observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 }
    );
    document.querySelectorAll('.reveal').forEach(function (el) {
        observer.observe(el);
    });
})();

// Hamburger menu
(function () {
    var btn   = document.getElementById('hamburgerBtn');
    var links = document.getElementById('navLinks');
    if (!btn || !links) return;
    btn.addEventListener('click', function () {
        var open = links.classList.toggle('open');
        btn.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', open);
    });
    // Chiudi al click su un link
    links.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            links.classList.remove('open');
            btn.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });
    });
})();

// Caricamento orari disponibili
function caricaMenuOrari() {
    var dataScelta = document.getElementById('date').value;
    var selectTime = document.getElementById('time');
    if (!dataScelta) return;

    selectTime.innerHTML = '<option value="">Caricamento orari...</option>';
    selectTime.classList.add('select-loading');

    fetch('api_disponibilita.php?data=' + dataScelta)
        .then(function (r) { return r.json(); })
        .then(function (orari) {
            selectTime.classList.remove('select-loading');
            selectTime.innerHTML = '';

            if (orari.length === 0) {
                var d = new Date(dataScelta);
                var day = d.getDay();
                selectTime.innerHTML = (day === 0 || day === 1)
                    ? '<option value="">Siamo chiusi questo giorno</option>'
                    : '<option value="">Nessuna disponibilit&agrave;</option>';
                return;
            }

            var def = document.createElement('option');
            def.value = '';
            def.text  = '-- Seleziona Orario --';
            selectTime.appendChild(def);

            orari.forEach(function (ora) {
                var opt   = document.createElement('option');
                opt.value = ora;
                opt.text  = ora;
                selectTime.appendChild(opt);
            });
        })
        .catch(function () {
            selectTime.classList.remove('select-loading');
            selectTime.innerHTML = '<option value="">Errore caricamento — riprova</option>';
        });
}

// Validazione form
document.getElementById('bookingForm').addEventListener('submit', function (e) {
    var valid = true;

    // Telefono
    var phoneInput = document.getElementById('phone');
    var phoneError = document.getElementById('phoneError');
    var cleaned    = phoneInput.value.replace(/\s/g, '').replace(/-/g, '');
    if (cleaned.startsWith('+39'))   cleaned = cleaned.slice(3);
    if (cleaned.startsWith('0039')) cleaned = cleaned.slice(4);
    if (!/^[3]\d{9}$/.test(cleaned)) {
        e.preventDefault();
        phoneError.style.display = 'block';
        phoneInput.style.borderColor = '#c0392b';
        valid = false;
    } else {
        phoneError.style.display = 'none';
        phoneInput.style.borderColor = '';
    }

    // Email
    var emailInput = document.getElementById('email');
    var emailError = document.getElementById('emailError');
    if (emailInput) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
            e.preventDefault();
            emailError.style.display = 'block';
            emailInput.style.borderColor = '#c0392b';
            valid = false;
        } else {
            emailError.style.display = 'none';
            emailInput.style.borderColor = '';
        }
    }

    // Parole non consentite
    var descInput = document.getElementById('service-desc');
    var textError = document.getElementById('textError');
    var blacklist = ['parolaccia','insulto','stupido','scemo','truffa','spam','casino','troia','cazzo','merda','stronzo','vaffanculo','bastardo','ignorante','idiota','culattone','zecca','balordo','cretino'];
    var hasBad    = blacklist.some(function (w) { return descInput.value.toLowerCase().includes(w); });
    if (hasBad) {
        e.preventDefault();
        textError.style.display = 'block';
        descInput.style.borderColor = '#c0392b';
        valid = false;
    } else {
        textError.style.display = 'none';
        descInput.style.borderColor = '';
    }

    return valid;
});

// Messaggi di ritorno
(function () {
    var params = new URLSearchParams(window.location.search);
    var status = params.get('status');
    if (!status) return;

    var form = document.getElementById('bookingForm');
    if (!form) return;

    if (status === 'success' || status === 'success_confirmed') {
        var msg = document.createElement('div');
        msg.className = 'success-message';
        msg.innerHTML = status === 'success_confirmed'
            ? '<i class="fas fa-check-circle"></i> Prenotazione confermata! Ti abbiamo inviato una email.'
            : '<i class="fas fa-check-circle"></i> Richiesta inviata! Ti confermeremo a breve.';
        form.parentNode.insertBefore(msg, form);
        msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.history.replaceState({}, '', window.location.pathname + '#prenota');
        return;
    }

    var errorMessages = {
        'error_length':   'Testo troppo lungo (max 100 caratteri).',
        'error_badwords': 'Il testo contiene parole non consentite.',
        'error_name_len': 'Nome troppo lungo (max 40 caratteri).',
        'error_limit':    'Hai raggiunto il massimo di prenotazioni giornaliere (2) per questo numero.',
        'error_email':    'Inserisci un indirizzo email valido.',
        'error_phone':    'Inserisci un numero di cellulare valido (Es. 333...).',
        'error_slot':     'Spiacenti, questo orario &egrave; appena stato occupato. Scegline un altro.',
        'error_captcha':  'Verifica anti-bot non superata. Riprova.',
        'error_rate':     'Troppe richieste. Attendi qualche minuto e riprova.',
        'error':          'Si &egrave; verificato un errore. Riprova.'
    };

    var errText = errorMessages[status];
    if (errText) {
        var errDiv = document.createElement('div');
        errDiv.className = 'error-message';
        errDiv.style.display = 'block';
        errDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + errText;
        form.parentNode.insertBefore(errDiv, form);
        errDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.history.replaceState({}, '', window.location.pathname + '#prenota');
    }
})();
