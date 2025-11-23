<?php
// --- LOGICA AZIONI UNIFICATA ---
if (isset($_GET['id']) && (isset($_GET['action']) || isset($_GET['track']))) {
    $id = intval($_GET['id']);
    
    // 1. AZIONE DB
    if (isset($_GET['action']) && $_GET['action'] != 'none') {
        $stato = $_GET['action'];
        $stmt = $conn->prepare("UPDATE prenotazioni SET stato=? WHERE id=?");
        $stmt->bind_param("si", $stato, $id);
        $stmt->execute();
        
        if ($stato == 'accettato') aggiungiLog($conn, $id, "✅ Accettato (Spostato in Agenda)");
        if ($stato == 'rifiutato') aggiungiLog($conn, $id, "❌ Rifiutato (Archiviato)");
        if ($stato == 'in_attesa') aggiungiLog($conn, $id, "🔄 Ripristinato in Attesa");
    }

    // 2. TRACKING / REDIRECT
    if (isset($_GET['track']) && isset($_GET['url'])) {
        $tipo = $_GET['track'];
        $url_destinazione = base64_decode($_GET['url']);
        
        $msg = match($tipo) { 
            'wa_conf' => "Inviato WA Conferma", 
            'wa_rej' => "Inviato WA Rifiuto", 
            'wa_canc' => "Inviato WA Annullamento", 
            'gcal' => "Aggiunto a Google Calendar", 
            'gcal_del' => "Aperto GCal per Eliminazione",
            default => "" 
        };
        if ($msg) aggiungiLog($conn, $id, $msg);

        header("Location: " . $url_destinazione);
        exit;
    }

    // 3. NEXT STEP
    if (isset($_GET['next_step'])) {
        $next = $_GET['next_step'];
        header("Location: admin.php?current_tab=$active_tab&flow_id=$id&step=$next");
        exit;
    }

    header("Location: admin.php?current_tab=$active_tab");
    exit;
}

// --- FERIE ---
if (isset($_POST['block_type'])) {
    $data = $_POST['data_blocco'];
    if ($_POST['block_type'] == 'single') {
        $ora = $_POST['ora_blocco'];
        $conn->query("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES ('$data', '$ora')");
    } elseif ($_POST['block_type'] == 'full_day') {
        $start = strtotime("08:00"); $end = strtotime("19:30");
        while ($start <= $end) {
            $ora = date("H:i", $start);
            $conn->query("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES ('$data', '$ora')");
            $start = strtotime('+30 minutes', $start);
        }
    }
    header("Location: admin.php?current_tab=tools"); exit;
}
if (isset($_GET['delete_block'])) {
    $conn->query("DELETE FROM slot_full WHERE id=".intval($_GET['delete_block']));
    header("Location: admin.php?current_tab=tools"); exit;
}

// --- MANUALE ---
if (isset($_POST['manual_booking'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $tel = $_POST['telefono']; $data = $_POST['data']; $ora = $_POST['ora']; $servizio = $_POST['servizio'];
    $conn->query("INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato, log_azioni) VALUES ('$nome', '$tel', '$data', '$ora', '$servizio', 'accettato', '[Admin] Manuale\n')");
    header("Location: admin.php?current_tab=agenda"); exit;
}

// --- PULIZIA ---
if (isset($_GET['action']) && $_GET['action'] == 'clean_old') {
    $conn->query("DELETE FROM prenotazioni WHERE data_appuntamento < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    $conn->query("DELETE FROM slot_full WHERE data_blocco < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    header("Location: admin.php?current_tab=tools&msg=cleaned"); exit;
}
// --- GESTIONE CONTENUTI (CMS) ---

// 1. Aggiorna "Chi Siamo"
if (isset($_POST['update_chi_siamo'])) {
    $content = $conn->real_escape_string($_POST['content_text']);
    $conn->query("UPDATE site_content SET content_text = '$content' WHERE section_key = 'chi_siamo'");
    header("Location: admin.php?current_tab=cms&msg=saved"); exit;
}

// 2. Aggiungi Servizio
if (isset($_POST['add_service'])) {
    $cat = $conn->real_escape_string($_POST['category']);
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $price = floatval(str_replace(',', '.', $_POST['price'])); // Gestione virgola
    
    $stmt = $conn->prepare("INSERT INTO services_list (category, name, description, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $cat, $name, $desc, $price);
    $stmt->execute();
    header("Location: admin.php?current_tab=cms&msg=added"); exit;
}

// 3. Elimina Servizio
if (isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $conn->query("DELETE FROM services_list WHERE id=$id");
    header("Location: admin.php?current_tab=cms&msg=deleted"); exit;
}
?>