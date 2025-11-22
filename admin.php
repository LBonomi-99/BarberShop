<?php
// admin.php - VERSIONE 3.9 (STORICO RICERCABILE INFINITO)
session_start();
date_default_timezone_set('Europe/Rome');

// --- CONFIGURAZIONE ---
$host = 'localhost';
$db   = 'barber_shop';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Errore DB"); }

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php"); exit;
}

// --- LOGIN ---
$password_segreta = "Matteo2025"; 
if (isset($_POST['login']) && $_POST['pass'] == $password_segreta) { $_SESSION['logged_in'] = true; }
if (!isset($_SESSION['logged_in'])) {
    echo '<div style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f0f2f5; font-family:sans-serif;">
            <form method="POST" style="background:white; padding:40px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); text-align:center;">
                <h2 style="color:#1C1C1C; margin-bottom:20px;">Admin Access</h2>
                <input type="password" name="pass" placeholder="Password" style="padding:12px; border:1px solid #ddd; border-radius:5px; width:100%; box-sizing:border-box; margin-bottom:15px;">
                <button type="submit" name="login" style="width:100%; padding:12px; background:#B8860B; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">ENTRA</button>
            </form>
          </div>'; exit;
}

// --- RECUPERO VARIABILI UI ---
$active_tab = isset($_GET['current_tab']) ? $_GET['current_tab'] : 'inbox';
$flow_id = isset($_GET['flow_id']) ? intval($_GET['flow_id']) : 0;
$flow_step = isset($_GET['step']) ? $_GET['step'] : '';

// --- HELPER LOG ---
function aggiungiLog($conn, $id, $messaggio) {
    $timestamp = date("d/m H:i");
    $entry = "[$timestamp] $messaggio\n";
    $stmt = $conn->prepare("UPDATE prenotazioni SET log_azioni = CONCAT(IFNULL(log_azioni, ''), ?) WHERE id=?");
    $stmt->bind_param("si", $entry, $id);
    $stmt->execute();
}

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

// --- DATI E QUERY AGGIORNATA ---
// La funzione real_escape_string pulisce il testo da caratteri pericolosi
$filter_date = isset($_GET['filter_date']) ? $conn->real_escape_string($_GET['filter_date']) : '';

// Query Base per Tab
$sql_attesa = "SELECT * FROM prenotazioni WHERE stato='in_attesa'";
$sql_agenda = "SELECT * FROM prenotazioni WHERE stato='accettato'";
// Query base per Storico (Rifiutati O Accettati passati)
$sql_storico = "SELECT * FROM prenotazioni WHERE (stato='rifiutato' OR (data_appuntamento < CURDATE() AND stato='accettato'))";

if (!empty($filter_date)) {
    // SE C'È IL FILTRO: Applica a TUTTI i tab
    $sql_attesa .= " AND data_appuntamento = '$filter_date'";
    $sql_agenda .= " AND data_appuntamento = '$filter_date'";
    $sql_storico .= " AND data_appuntamento = '$filter_date'"; // Filtra anche lo storico
} else {
    // SE NON C'È FILTRO
    $sql_agenda .= " AND data_appuntamento >= CURDATE()";
    // Allo storico aggiungiamo il limite solo se NON stiamo filtrando
    $sql_storico .= " ORDER BY data_appuntamento DESC LIMIT 200";
}

// Ordinamento standard
$sql_attesa .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";
$sql_agenda .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";

// Se c'era il filtro, l'ordinamento allo storico va aggiunto dopo
if (!empty($filter_date)) {
    $sql_storico .= " ORDER BY data_appuntamento DESC";
}

$res_attesa = $conn->query($sql_attesa);
$res_agenda = $conn->query($sql_agenda);
$res_storico = $conn->query($sql_storico); // Query Dinamica

$slot_full = $conn->query("SELECT * FROM slot_full WHERE data_blocco >= CURDATE() ORDER BY data_blocco ASC LIMIT 50");
$count_total_attesa = $conn->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato='in_attesa'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1C1C1C; --accent: #B8860B; --bg: #f4f7f6; --white: #ffffff; --green: #28a745; --red: #dc3545; --gray: #6c757d; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding-bottom: 80px; color: #333; }
        
        /* HEADER & TABS */
        .header { background: var(--primary); color: white; padding: 15px 20px; position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.2rem; font-weight: 800; letter-spacing: 1px; }
        .logout { color: #ff6b6b; font-size: 0.9rem; font-weight: 600; text-decoration:none; }
        
        .nav-tabs { display: flex; gap: 10px; padding: 15px 15px 0; overflow-x: auto; white-space: nowrap; }
        .tab-btn { background: white; text-decoration: none; padding: 10px 20px; border-radius: 20px; font-weight: 600; color: var(--gray); box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.2s; flex-shrink: 0; display: inline-block; }
        .tab-btn.active { background: var(--accent); color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3); }
        .badge-count { background: var(--red); color: white; padding: 2px 6px; border-radius: 50%; font-size: 0.7rem; margin-left: 5px; vertical-align: top; }
        
        .filter-bar { background: white; margin: 15px; padding: 15px; border-radius: 12px; display: flex; gap: 10px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .filter-input { flex-grow: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; }
        .btn-reset { background: #eee; color: #333; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; text-decoration:none; display:inline-block; }
        
        .tab-content { display: none; padding: 0 15px 15px; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: relative; overflow: hidden; transition: 0.3s; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .time-badge { background: #edf2f7; color: var(--primary); font-weight: 800; font-size: 1.1rem; padding: 5px 12px; border-radius: 8px; }
        .date-badge { font-size: 0.85rem; color: var(--gray); font-weight: 600; text-transform: uppercase; }
        .client-name { font-size: 1.2rem; font-weight: 700; margin: 0; color: var(--primary); }
        .service-info { color: var(--gray); font-size: 0.95rem; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .phone-link { color: var(--accent); font-weight: 600; margin-top: 5px; display: inline-block; text-decoration:none; }
        
        /* STILE PULSANTI CENTRATI */
        .flow-step { margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ddd; animation: fadeIn 0.3s; }
        .step-title { font-size: 0.85rem; font-weight: 700; color: var(--gray); margin-bottom: 10px; display: block; text-transform: uppercase; text-align:center; }
        
        .actions-grid { display: flex; gap: 10px; justify-content: center; }
        .actions-stack { display: flex; flex-direction: column; gap: 10px; align-items: center; }
        
        .btn { 
            display: flex; align-items: center; justify-content: center; gap: 8px; 
            padding: 14px; border-radius: 50px; 
            font-weight: 600; font-size: 0.95rem; 
            cursor: pointer; width: 100%; max-width: 500px; 
            text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            transition: 0.2s;
        }
        
        /* COLORI CHIARI */
        .btn-green { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-red { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-blue { background: #cfe2ff; color: #084298; border: 1px solid #b6d4fe; }
        .btn-wa { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        
        .btn:hover { filter: brightness(0.95); transform: translateY(-1px); }
        .btn-disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(100%); }

        .admin-panel { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 5px; color: var(--gray); }
        input, select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .fab { position: fixed; bottom: 20px; right: 20px; background: var(--primary); color: white; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); cursor: pointer; z-index: 900; text-decoration:none; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-accettato { background: #d4edda; color: #155724; }
        .badge-rifiutato { background: #f8d7da; color: #721c24; }
        
        .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:2000; }
        .modal-box { background:white; padding:30px; border-radius:12px; width:90%; max-width:400px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

    <?php if(date('m') == '01' && !isset($_SESSION['maintenance_shown'])): $_SESSION['maintenance_shown'] = true; ?>
    <div id="maintenanceModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color:var(--red); margin-top:0;"><i class="fas fa-exclamation-triangle"></i> Manutenzione Annuale</h3>
            <p>Elimina storico > 1 anno per GDPR.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px; flex-direction:column;">
                <a href="admin.php?action=clean_old" class="btn btn-red">Esegui Pulizia</a>
                <button onclick="document.getElementById('maintenanceModal').style.display='none'" class="btn" style="background:#eee; color:#333;">Chiudi</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="header">
        <h1>BarberAdmin <i class="fas fa-cut" style="font-size:0.8em; color:var(--accent);"></i></h1>
        <a href="admin.php?logout=true" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="nav-tabs">
        <a href="admin.php?current_tab=inbox" class="tab-btn <?php echo $active_tab=='inbox'?'active':''; ?>">Da Confermare <?php if($count_total_attesa > 0) echo "<span class='badge-count'>$count_total_attesa</span>"; ?></a>
        <a href="admin.php?current_tab=agenda" class="tab-btn <?php echo $active_tab=='agenda'?'active':''; ?>">Agenda</a>
        <a href="admin.php?current_tab=tools" class="tab-btn <?php echo $active_tab=='tools'?'active':''; ?>">Gestione</a>
        <a href="admin.php?current_tab=history" class="tab-btn <?php echo $active_tab=='history'?'active':''; ?>">Storico</a>
    </div>

    <form class="filter-bar" method="GET" action="admin.php">
        <input type="hidden" name="current_tab" value="<?php echo $active_tab; ?>">
        <input type="date" name="filter_date" class="filter-input" value="<?php echo $filter_date; ?>" onchange="this.form.submit()">
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i></button>
        <?php if($filter_date): ?>
            <a href="admin.php?current_tab=<?php echo $active_tab; ?>" class="btn-reset"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>

    <div id="inbox" class="tab-content <?php echo $active_tab=='inbox'?'active':''; ?>">
        <?php if($res_attesa->num_rows == 0): ?>
            <div style="text-align:center; padding:50px 20px; color:#999;"><p>Nessuna richiesta.</p></div>
        <?php endif; ?>

        <?php while($row = $res_attesa->fetch_assoc()): 
            $tel = preg_replace('/[^0-9]/', '', $row['telefono']);
            $row_date = date('d/m/Y', strtotime($row['data_appuntamento']));
            $wa_conf_link = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, confermo il tuo appuntamento per il $row_date alle {$row['ora_appuntamento']}. A presto, Matteo."));
            $wa_rej_link = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, purtroppo per quell'orario non ho disponibilità. Possiamo trovare un'altra data?"));
            
            $start = strtotime($row['data_appuntamento'].' '.$row['ora_appuntamento']);
            $end = $start + 1800;
            $gcal = base64_encode("https://calendar.google.com/calendar/render?action=TEMPLATE&text=Taglio+{$row['nome']}&dates=".date('Ymd\THis',$start)."/".date('Ymd\THis',$end)."&details=Tel:+$tel&src=leonardobonomi949@gmail.com");

            $is_active_card = ($flow_id == $row['id']);
            $current_step = $is_active_card ? $flow_step : 'start';
            $base_url = "admin.php?current_tab=inbox" . ($filter_date ? "&filter_date=$filter_date" : "") . "&flow_id={$row['id']}";
        ?>
        
        <div class="card" style="border-left: 5px solid <?php echo ($current_step=='start') ? 'var(--accent)' : (($current_step=='accept_flow') ? 'var(--green)' : 'var(--red)'); ?>;">
            <div class="card-header">
                <span class="date-badge"><?php echo date('d M', strtotime($row['data_appuntamento'])); ?></span>
                <span class="time-badge"><?php echo $row['ora_appuntamento']; ?></span>
            </div>
            <h3 class="client-name"><?php echo $row['nome']; ?></h3>
            <div class="service-info"><i class="fas fa-cut"></i> <?php echo $row['servizio']; ?></div>
            <a href="tel:<?php echo $tel; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $row['telefono']; ?></a>
            
            <?php if($current_step == 'start'): ?>
                <div class="actions-grid" style="margin-top:20px;">
                    <a href="<?php echo $base_url; ?>&step=accept_flow" class="btn btn-green"><i class="fas fa-check"></i> Accetta</a>
                    <a href="<?php echo $base_url; ?>&step=reject_flow" class="btn btn-red"><i class="fas fa-times"></i> Rifiuta</a>
                </div>
            
            <?php elseif($current_step == 'accept_flow'): ?>
                <div class="flow-step">
                    <span class="step-title">Accettazione</span>
                    <div class="actions-stack">
                        <a href="admin.php?track=gcal&id=<?php echo $row['id']; ?>&url=<?php echo $gcal; ?>" target="_blank" class="btn btn-blue" onclick="enableStep3(<?php echo $row['id']; ?>)">
                            <i class="far fa-calendar-plus"></i> 1. Aggiungi a Google Calendar
                        </a>
                        <a href="admin.php?action=accettato&id=<?php echo $row['id']; ?>&track=wa_conf&url=<?php echo $wa_conf_link; ?>" id="btn-step3-<?php echo $row['id']; ?>" class="btn btn-wa btn-disabled" target="_blank" onclick="reloadAfterDelay()">
                            <i class="fab fa-whatsapp"></i> 2. Conferma WhatsApp & Salva
                        </a>
                        <a href="<?php echo $base_url; ?>&step=start" style="color:#999; font-size:0.8rem; margin-top:5px;">Annulla</a>
                    </div>
                </div>

            <?php elseif($current_step == 'reject_flow'): ?>
                <div class="flow-step">
                    <span class="step-title">Rifiuto</span>
                    <div class="actions-stack">
                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>&track=wa_rej&url=<?php echo $wa_rej_link; ?>" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Declina Appuntamento WhatsApp</a>
                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" class="btn btn-red"><i class="fas fa-times"></i> Declina (Solo Archivia)</a>
                        <a href="<?php echo $base_url; ?>&step=start" style="color:#999; font-size:0.8rem; margin-top:5px;">Annulla</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

    <div id="agenda" class="tab-content <?php echo $active_tab=='agenda'?'active':''; ?>">
        <?php if($res_agenda->num_rows == 0): ?>
            <div style="text-align:center; padding:50px 20px; color:#999;"><p>Nessun appuntamento.</p></div>
        <?php endif; ?>

        <?php while($row = $res_agenda->fetch_assoc()): 
            $tel = preg_replace('/[^0-9]/', '', $row['telefono']);
            $wa_canc = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, devo annullare l'appuntamento causa imprevisto."));
            $date_ymd = date('Y/m/d', strtotime($row['data_appuntamento']));
            $gcal_del = base64_encode("https://calendar.google.com/calendar/r/day/$date_ymd");

            $is_active_card = ($flow_id == $row['id']);
            $current_step = $is_active_card ? $flow_step : 'view';
            $base_url = "admin.php?current_tab=agenda" . ($filter_date ? "&filter_date=$filter_date" : "") . "&flow_id={$row['id']}";
        ?>
        <div class="card" style="border-left: 5px solid var(--green);">
            <div class="card-header">
                <span class="time-badge"><?php echo $row['ora_appuntamento']; ?></span>
                <div style="font-size:0.8rem; color:#999;"><?php echo $row['log_azioni'] ? '<i class="fas fa-history"></i>' : ''; ?></div>
            </div>
            <h3 class="client-name"><?php echo $row['nome']; ?> - <?php echo date('d/m', strtotime($row['data_appuntamento'])); ?></h3>
            <div class="service-info"><?php echo $row['servizio']; ?></div>
            
            <?php if($current_step == 'view'): ?>
                <div class="actions-stack" style="margin-top:15px;">
                    <a href="<?php echo $base_url; ?>&step=cancel_start" class="btn" style="background:white; border:1px solid #eee; color:var(--red) !important;">
                        <i class="fas fa-ban"></i> Avvia Cancellazione
                    </a>
                </div>

            <?php elseif($current_step == 'cancel_start'): ?>
                <div class="flow-step">
                    <span class="step-title">Step 1: Avvisa</span>
                    <div class="actions-stack">
                        <a href="admin.php?track=wa_canc&id=<?php echo $row['id']; ?>&url=<?php echo $wa_canc; ?>&next_step=cancel_gcal" target="_blank" class="btn btn-wa" onclick="setTimeout(function(){ window.location.href = '<?php echo $base_url; ?>&step=cancel_gcal'; }, 1000);">
                            <i class="fab fa-whatsapp"></i> 1. Avvisa WhatsApp (Poi step 2)
                        </a>
                        <a href="<?php echo $base_url; ?>&step=cancel_gcal" class="btn btn-red">
                            <i class="fas fa-user-slash"></i> 1. Salta Messaggio (Vai a step 2)
                        </a>
                        <a href="<?php echo $base_url; ?>&step=view" style="color:#999; font-size:0.8rem;">Annulla</a>
                    </div>
                </div>

            <?php elseif($current_step == 'cancel_gcal'): ?>
                <div class="flow-step">
                    <span class="step-title">Step 2: Calendario</span>
                    <div class="actions-stack">
                        <a href="admin.php?track=gcal_del&id=<?php echo $row['id']; ?>&url=<?php echo $gcal_del; ?>" target="_blank" class="btn btn-blue">
                            <i class="far fa-calendar-minus"></i> 2. Apri GCal per Eliminare
                        </a>
                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" class="btn btn-red">
                            <i class="fas fa-check"></i> Conferma Cancellazione Finale
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>

    <div id="tools" class="tab-content <?php echo $active_tab=='tools'?'active':''; ?>">
        <div class="admin-panel" id="manualForm" style="display:none; border: 2px solid var(--primary);">
            <h3 style="margin-top:0;">Inserimento Manuale</h3>
            <form method="POST">
                <input type="hidden" name="manual_booking" value="1">
                <div class="form-group"><label>Chi:</label><input type="text" name="nome" required></div>
                <div class="form-group"><label>Tel:</label><input type="text" name="telefono" required></div>
                <div class="form-group"><label>Quando:</label>
                    <div style="display:flex; gap:10px;">
                        <input type="date" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                        <select name="ora">
                            <?php $s=strtotime("08:00"); $e=strtotime("19:30"); while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Cosa:</label><input type="text" name="servizio" value="Taglio"></div>
                <button type="submit" class="btn" style="background:var(--primary); color:white !important;">Salva Prenotazione</button>
            </form>
        </div>

        <div class="admin-panel" style="background:#fff3cd;">
            <h3 style="margin-top:0; color:#856404;"><i class="fas fa-ban"></i> Blocca Orari / Ferie</h3>
            <form method="POST">
                <div class="form-group"><label>Giorno:</label><input type="date" name="data_blocco" required></div>
                <div class="form-group"><label>Tipo:</label>
                    <select name="block_type" id="blockType" onchange="toggleInputs()">
                        <option value="single">Solo un orario</option>
                        <option value="full_day">Tutto il giorno (Ferie)</option>
                        <option value="range">Periodo (Più giorni)</option>
                    </select>
                </div>
                <div class="form-group" id="endDateGroup" style="display:none;">
                    <label>Fino al:</label><input type="date" name="data_fine">
                </div>
                <div class="form-group" id="timeSelectGroup">
                    <label>Ora (se singolo):</label>
                    <select name="ora_blocco">
                        <?php $s=strtotime("08:00"); $e=strtotime("19:30"); while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-red">Applica Blocco</button>
            </form>

            <?php if($slot_full->num_rows > 0): ?>
            <div style="margin-top:20px; font-size:0.9rem;">
                <strong>Blocchi attivi:</strong>
                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:5px;">
                <?php while($row = $slot_full->fetch_assoc()): ?>
                    <span style="background:white; padding:4px 8px; border-radius:4px; border:1px solid #ddd;">
                        <?php echo date('d/m', strtotime($row['data_blocco']))." ".$row['ora_blocco']; ?>
                        <a href="admin.php?delete_block=<?php echo $row['id']; ?>" style="color:red; margin-left:5px; font-weight:bold; text-decoration:none;">&times;</a>
                    </span>
                <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-panel" style="background:#f8d7da; border: 2px solid #f5c6cb; margin-top: 30px;">
            <h3 style="margin-top:0; color:#721c24;"><i class="fas fa-trash-alt"></i> Manutenzione Privacy</h3>
            <p style="font-size:0.9rem; color:#721c24;">
                Elimina storico > 1 anno per GDPR.
            </p>
            <a href="admin.php?action=clean_old" class="btn btn-red" onclick="return confirm('Sei sicuro?')">
                Elimina Storico Vecchio
            </a>
        </div>
    </div>

    <div id="history" class="tab-content <?php echo $active_tab=='history'?'active':''; ?>">
        <?php while($row = $res_storico->fetch_assoc()): ?>
            <div class="card" style="opacity:0.7;">
                <div class="card-header">
                    <span><?php echo date('d/m', strtotime($row['data_appuntamento'])); ?></span>
                    <span class="badge badge-<?php echo $row['stato']; ?>"><?php echo $row['stato']; ?></span>
                </div>
                <strong><?php echo $row['nome']; ?></strong>
                
                <?php if($row['stato'] == 'rifiutato'): ?>
                    <div class="actions-stack" style="margin-top:10px;">
                        <a href="admin.php?action=in_attesa&id=<?php echo $row['id']; ?>" class="btn btn-blue" onclick="return confirm('Vuoi ripristinare questa richiesta? Tornerà in Da Confermare.')">
                            <i class="fas fa-undo"></i> Ripristina in Attesa
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; margin-top:10px; color:var(--green); font-weight:bold; font-size:0.85rem;">
                        <i class="fas fa-check-double"></i> Concluso
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <a href="admin.php?current_tab=tools&show_manual=1" class="fab"><i class="fas fa-plus"></i></a>

    <script>
        function enableStep3(id) {
            setTimeout(function() {
                var btn = document.getElementById('btn-step3-' + id);
                if(btn) {
                    btn.classList.remove('btn-disabled');
                    btn.innerHTML = '<i class="fab fa-whatsapp"></i> 2. Conferma WhatsApp & Salva';
                }
            }, 1000);
        }

        function reloadAfterDelay() {
            setTimeout(function() {
                window.location.href = "admin.php?current_tab=inbox";
            }, 2000);
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('show_manual')) {
            document.getElementById('manualForm').style.display = 'block';
        }

        function toggleInputs() {
            const val = document.getElementById('blockType').value;
            const timeGroup = document.getElementById('timeSelectGroup');
            const endGroup = document.getElementById('endDateGroup');
            
            if (val === 'single') {
                timeGroup.style.display = 'flex';
                endGroup.style.display = 'none';
            } else if (val === 'full_day') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'none';
            } else if (val === 'range') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'flex';
            }
        }
    </script>
</body>
</html>