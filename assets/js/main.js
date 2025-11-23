// Funzione Contatore Caratteri
function updateCount(field) {
    const count = field.value.length;
    document.getElementById('charCount').innerText = count + "/100";
}

// Event Listener Submit Form
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    let valid = true;

    // A. VALIDAZIONE TELEFONO
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phoneError');
    const phoneVal = phoneInput.value.replace(/\s/g, '').replace(/-/g, '');
    let cleanNumber = phoneVal;
    if (cleanNumber.startsWith('+39')) cleanNumber = cleanNumber.substring(3);
    else if (cleanNumber.startsWith('0039')) cleanNumber = cleanNumber.substring(4);
    const isMobile = /^[3]\d{9}$/.test(cleanNumber);

    if (!isMobile) {
        e.preventDefault();
        phoneError.style.display = 'block';
        phoneInput.style.borderColor = 'red';
        valid = false;
    } else {
        phoneError.style.display = 'none';
        phoneInput.style.borderColor = '#ddd';
    }

    // B. VALIDAZIONE PAROLE
    const descInput = document.getElementById('service-desc');
    const textError = document.getElementById('textError');
    const textVal = descInput.value.toLowerCase();
    const blacklist = ['parolaccia', 'insulto', 'stupido', 'scemo', 'truffa', 'spam', 'casino', 'troia', 'cazzo', 'merda', 'stronzo', 'vaffanculo', 'bastardo', 'ignorante', 'idiota', 'culattone', 'zecca', 'balordo', 'cretino']; 
    
    let hasBadWords = false;
    for (let word of blacklist) {
        if (textVal.includes(word)) {
            hasBadWords = true;
            break;
        }
    }

    if (hasBadWords) {
        e.preventDefault();
        textError.style.display = 'block';
        descInput.style.borderColor = 'red';
        valid = false;
    } else {
        textError.style.display = 'none';
        descInput.style.borderColor = '#ddd';
    }

    return valid;
});

// Funzione Caricamento Orari (AJAX/Fetch)
function caricaMenuOrari() {
    const dataScelta = document.getElementById('date').value;
    const selectTime = document.getElementById('time');
    
    if (!dataScelta) return;

    const dateObj = new Date(dataScelta);
    const day = dateObj.getDay(); 
    
    selectTime.innerHTML = '<option>Verifica disponibilità...</option>';

    fetch(`api_disponibilita.php?data=${dataScelta}`)
        .then(response => response.json())
        .then(orari => {
            selectTime.innerHTML = ''; 
            
            if (orari.length === 0) {
                if (day === 0 || day === 1) {
                    selectTime.innerHTML = '<option value="">Siamo Chiusi in questo giorno</option>';
                } else {
                    selectTime.innerHTML = '<option value="">Tutto esaurito / Nessuna disponibilità</option>';
                }
                return;
            }

            const defaultOpt = document.createElement('option');
            defaultOpt.value = "";
            defaultOpt.text = "-- Seleziona Orario --";
            selectTime.appendChild(defaultOpt);

            orari.forEach(ora => {
                const opt = document.createElement('option');
                opt.value = ora;
                opt.text = ora;
                selectTime.appendChild(opt);
            });
        })
        .catch(err => {
            console.error(err);
            selectTime.innerHTML = '<option>Errore caricamento</option>';
        });
}

// Utility Lettura Parametri URL
function getParameterByName(name) {
    const url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

// Logica OnLoad (Gestione messaggi di ritorno)
window.onload = function() {
    const status = getParameterByName('status');
    const form = document.getElementById('bookingForm');

    if (status === 'success') {
        const msg = document.createElement('div');
        msg.style.backgroundColor = '#A1B59C';
        msg.style.color = 'white';
        msg.style.padding = '20px';
        msg.style.marginBottom = '20px';
        msg.style.textAlign = 'center';
        msg.style.fontWeight = 'bold';
        msg.style.border = '1px solid #B8860B';
        msg.innerHTML = '<i class="fas fa-check-circle"></i> Richiesta inviata! Ti confermeremo a breve.';
        
        form.parentNode.insertBefore(msg, form);
        msg.scrollIntoView({behavior: "smooth", block: "center"});
        window.history.replaceState({}, document.title, window.location.pathname + "#prenota");
    }
    else if (status === 'error_length') {
        alert("Errore: Testo troppo lungo.");
    }
    else if (status === 'error_badwords') {
        alert("Errore: Testo non consentito.");
    }
    else if (status === 'error_name_len') {
        alert("Errore: Nome troppo lungo (Max 40 caratteri).");
    }
    else if (status === 'error_limit') {
        alert("ATTENZIONE: Hai raggiunto il numero massimo di prenotazioni giornaliere (2) per questo numero di telefono.");
    }
};