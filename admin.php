<?php
// admin.php - VERSIONE 3.9 (MODULARE)

// 1. Configurazione Base e Database
require_once 'admin_components/config.php';

// 2. Autenticazione (Login/Logout)
require_once 'admin_components/auth.php';

// 3. Inizializzazione Variabili e Funzioni
require_once 'admin_components/init.php';

// 4. Esecuzione Azioni (Logica che comporta redirect)
require_once 'admin_components/actions.php';

// 5. Recupero Dati (Query)
require_once 'admin_components/data.php';

// 6. Rendering della Vista (HTML)
require_once 'admin_components/header.php';
require_once 'admin_components/tab_inbox.php';
require_once 'admin_components/tab_agenda.php';
require_once 'admin_components/tab_tools.php';
require_once 'admin_components/tab_cms.php';
require_once 'admin_components/tab_history.php';
require_once 'admin_components/footer.php';
?>