<?php
// --- AZIONI STATO PRENOTAZIONI (GET) ---
if (isset($_GET['id']) && (isset($_GET['action']) || isset($_GET['track']))) {
    $id = intval($_GET['id']);

    if (isset($_GET['action']) && $_GET['action'] != 'none') {
        $stato = $_GET['action'];
        if (in_array($stato, ['accettato', 'rifiutato', 'in_attesa'])) {
            $stmt = $conn->prepare("UPDATE prenotazioni SET stato=? WHERE id=?");
            $stmt->bind_param("si", $stato, $id);
            $stmt->execute();

            if ($stato == 'accettato') aggiungiLog($conn, $id, "Accettato");
            if ($stato == 'rifiutato') aggiungiLog($conn, $id, "Rifiutato");
            if ($stato == 'in_attesa') aggiungiLog($conn, $id, "Ripristinato in Attesa");
        }
    }

    if (isset($_GET['track']) && isset($_GET['url'])) {
        $tipo = $_GET['track'];
        $url_destinazione = base64_decode($_GET['url']);
        $msg = match($tipo) {
            'wa_conf'  => "Inviato WA Conferma",
            'wa_rej'   => "Inviato WA Rifiuto",
            'wa_canc'  => "Inviato WA Annullamento",
            'gcal'     => "Aggiunto a Google Calendar",
            'gcal_del' => "Aperto GCal per Eliminazione",
            default    => ""
        };
        if ($msg) aggiungiLog($conn, $id, $msg);
        header("Location: " . $url_destinazione);
        exit;
    }

    if (isset($_GET['next_step'])) {
        header("Location: admin.php?current_tab=$active_tab&flow_id=$id&step=" . $_GET['next_step']);
        exit;
    }

    header("Location: admin.php?current_tab=$active_tab");
    exit;
}

// --- BLOCCO ORARI / FERIE (POST) ---
if (isset($_POST['block_type'])) {
    $data = (isset($_POST['data_blocco']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_blocco'])) ? $_POST['data_blocco'] : null;
    if ($data) {
        if ($_POST['block_type'] == 'single' && isset($_POST['ora_blocco']) && preg_match('/^\d{2}:\d{2}$/', $_POST['ora_blocco'])) {
            $ora = $_POST['ora_blocco'];
            $stmt = $conn->prepare("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES (?, ?)");
            $stmt->bind_param("ss", $data, $ora);
            $stmt->execute();
        } elseif ($_POST['block_type'] == 'full_day') {
            $stmt = $conn->prepare("INSERT IGNORE INTO slot_full (data_blocco, ora_blocco) VALUES (?, ?)");
            $start = strtotime("08:00"); $end = strtotime("19:30");
            while ($start <= $end) {
                $ora = date("H:i", $start);
                $stmt->bind_param("ss", $data, $ora);
                $stmt->execute();
                $start = strtotime('+30 minutes', $start);
            }
        }
    }
    header("Location: admin.php?current_tab=tools"); exit;
}

if (isset($_GET['delete_block'])) {
    $bid = intval($_GET['delete_block']);
    $stmt = $conn->prepare("DELETE FROM slot_full WHERE id=?");
    $stmt->bind_param("i", $bid); $stmt->execute();
    header("Location: admin.php?current_tab=tools"); exit;
}

// --- INSERIMENTO MANUALE (POST) ---
if (isset($_POST['manual_booking'])) {
    $nome     = trim($_POST['nome'] ?? '');
    $tel      = trim($_POST['telefono'] ?? '');
    $data     = trim($_POST['data'] ?? '');
    $ora      = trim($_POST['ora'] ?? '');
    $servizio = trim($_POST['servizio'] ?? '');
    if (strlen($nome) <= 100 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && preg_match('/^\d{2}:\d{2}$/', $ora)) {
        $stmt = $conn->prepare("INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato, log_azioni) VALUES (?, ?, ?, ?, ?, 'accettato', '[Admin] Manuale\n')");
        $stmt->bind_param("sssss", $nome, $tel, $data, $ora, $servizio);
        $stmt->execute();
    }
    header("Location: admin.php?current_tab=agenda"); exit;
}

// --- PULIZIA GDPR (GET) ---
if (isset($_GET['action']) && $_GET['action'] == 'clean_old') {
    $conn->query("DELETE FROM prenotazioni WHERE data_appuntamento < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    $conn->query("DELETE FROM slot_full WHERE data_blocco < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    header("Location: admin.php?current_tab=tools&msg=cleaned"); exit;
}

// --- ELIMINA SERVIZIO (GET) ---
if (isset($_GET['delete_service'])) {
    $sid  = intval($_GET['delete_service']);
    $stmt = $conn->prepare("DELETE FROM services_list WHERE id=?");
    $stmt->bind_param("i", $sid); $stmt->execute();
    header("Location: admin.php?current_tab=cms&msg=deleted"); exit;
}

// --- ELIMINA CATEGORIA (GET) ---
if (isset($_GET['delete_category'])) {
    $cid  = intval($_GET['delete_category']);
    $stmt = $conn->prepare("SELECT name FROM service_categories WHERE id=?");
    $stmt->bind_param("i", $cid); $stmt->execute();
    $cat_row = $stmt->get_result()->fetch_assoc();
    if ($cat_row) {
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM services_list WHERE category=?");
        $check->bind_param("s", $cat_row['name']); $check->execute();
        if ((int)$check->get_result()->fetch_assoc()['cnt'] === 0) {
            $del = $conn->prepare("DELETE FROM service_categories WHERE id=?");
            $del->bind_param("i", $cid); $del->execute();
        }
    }
    header("Location: admin.php?current_tab=cms&msg=cat_deleted"); exit;
}

// --- DISPATCH AZIONI POST (action field) ---
$post_action = isset($_POST['action']) ? $_POST['action'] : '';

// CMS: Chi Siamo
if ($post_action === 'update_chi_siamo') {
    $content = trim($_POST['content_text'] ?? '');
    $stmt = $conn->prepare("INSERT INTO site_content (section_key, content_text) VALUES ('chi_siamo',?) ON DUPLICATE KEY UPDATE content_text=?");
    $stmt->bind_param("ss", $content, $content); $stmt->execute();
    header("Location: admin.php?current_tab=cms&msg=saved"); exit;
}

// CMS: Aggiungi Servizio
if ($post_action === 'add_service') {
    $cat   = trim($_POST['category'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = floatval(str_replace(',', '.', $_POST['price'] ?? '0'));
    if (!empty($name) && !empty($cat)) {
        $stmt = $conn->prepare("INSERT INTO services_list (category, name, description, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $cat, $name, $desc, $price); $stmt->execute();
    }
    header("Location: admin.php?current_tab=cms&msg=added"); exit;
}

// CMS: Aggiungi Categoria
if ($post_action === 'add_category') {
    $cat_name = trim($_POST['category_name'] ?? '');
    if (!empty($cat_name) && strlen($cat_name) <= 100) {
        $res_max    = $conn->query("SELECT IFNULL(MAX(sort_order),0)+1 as n FROM service_categories");
        $next_order = $res_max ? (int)$res_max->fetch_assoc()['n'] : 10;
        $stmt = $conn->prepare("INSERT IGNORE INTO service_categories (name, sort_order) VALUES (?, ?)");
        $stmt->bind_param("si", $cat_name, $next_order); $stmt->execute();
    }
    header("Location: admin.php?current_tab=cms&msg=cat_added"); exit;
}

// CMS: Social Links
if ($post_action === 'update_social') {
    $instagram = trim($_POST['social_instagram'] ?? '');
    $facebook  = trim($_POST['social_facebook'] ?? '');
    $stmt = $conn->prepare("INSERT INTO site_content (section_key, content_text) VALUES ('social_instagram',?) ON DUPLICATE KEY UPDATE content_text=?");
    $stmt->bind_param("ss", $instagram, $instagram); $stmt->execute();
    $stmt = $conn->prepare("INSERT INTO site_content (section_key, content_text) VALUES ('social_facebook',?) ON DUPLICATE KEY UPDATE content_text=?");
    $stmt->bind_param("ss", $facebook, $facebook); $stmt->execute();
    header("Location: admin.php?current_tab=cms&msg=social_saved"); exit;
}

// Strumenti: Orari di Apertura
if ($post_action === 'update_opening_hours') {
    $stmt = $conn->prepare("INSERT INTO opening_hours (giorno, chiuso, mattina_inizio, mattina_fine, pomeriggio_inizio, pomeriggio_fine)
        VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE chiuso=VALUES(chiuso), mattina_inizio=VALUES(mattina_inizio), mattina_fine=VALUES(mattina_fine), pomeriggio_inizio=VALUES(pomeriggio_inizio), pomeriggio_fine=VALUES(pomeriggio_fine)");
    $chiuso_map      = $_POST['chiuso']            ?? [];
    $mat_ini_map     = $_POST['mattina_inizio']    ?? [];
    $mat_fin_map     = $_POST['mattina_fine']      ?? [];
    $pom_ini_map     = $_POST['pomeriggio_inizio'] ?? [];
    $pom_fin_map     = $_POST['pomeriggio_fine']   ?? [];
    for ($g = 0; $g <= 6; $g++) {
        $chiuso  = isset($chiuso_map[$g]) ? 1 : 0;
        $mat_ini = !empty($mat_ini_map[$g]) ? $mat_ini_map[$g] : null;
        $mat_fin = !empty($mat_fin_map[$g]) ? $mat_fin_map[$g] : null;
        $pom_ini = !empty($pom_ini_map[$g]) ? $pom_ini_map[$g] : null;
        $pom_fin = !empty($pom_fin_map[$g]) ? $pom_fin_map[$g] : null;
        $stmt->bind_param("iissss", $g, $chiuso, $mat_ini, $mat_fin, $pom_ini, $pom_fin);
        $stmt->execute();
    }
    header("Location: admin.php?current_tab=tools&msg=hours_saved"); exit;
}

// Strumenti: Cambio Password
if ($post_action === 'change_password') {
    $old_pass  = $_POST['old_password']     ?? '';
    $new_pass  = $_POST['new_password']     ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $conf_pass || strlen($new_pass) < 6) {
        header("Location: admin.php?current_tab=tools&msg=pass_error"); exit;
    }
    if (!checkAdminPassword($conn, $old_pass)) {
        header("Location: admin.php?current_tab=tools&msg=pass_wrong"); exit;
    }
    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO admin_config (config_key, config_value) VALUES ('admin_password',?) ON DUPLICATE KEY UPDATE config_value=?");
    $stmt->bind_param("ss", $new_hash, $new_hash); $stmt->execute();
    header("Location: admin.php?current_tab=tools&msg=pass_changed"); exit;
}
?>
