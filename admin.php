<?php
// admin.php - VERSIONE 2.4 (TESTI INTUITIVI & LAYOUT A PILA)
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

// --- RECUPERO TAB ATTIVO ---
$active_tab = isset($_GET['current_tab']) ? $_GET['current_tab'] : 'inbox';

// --- HELPER LOG ---
function aggiungiLog($conn, $id, $messaggio) {
    $timestamp = date("d/m H:i");
    $entry = "[$timestamp] $messaggio\n";
    $stmt = $conn->prepare("UPDATE prenotazioni SET log_azioni = CONCAT(IFNULL(log_azioni, ''), ?) WHERE id=?");
    $stmt->bind_param("si", $entry, $id);
    $stmt->execute();
}

// --- AZIONI ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stato = $_GET['action']; 
    $conn->query("UPDATE prenotazioni SET stato='$stato' WHERE id=$id");
    if ($stato == 'accettato') aggiungiLog($conn, $id, "✅ Accettato (DB)");
    if ($stato == 'rifiutato') aggiungiLog($conn, $id, "❌ Rifiutato (DB)");
    
    $qs = http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])); 
    header("Location: admin.php?".$qs); exit;
}

if (isset($_GET['track']) && isset($_GET['id']) && isset($_GET['url'])) {
    $id = intval($_GET['id']);
    $tipo = $_GET['track'];
    $url = base64_decode($_GET['url']);
    $msg = match($tipo) { 
        'wa_conf' => "Inviato WA Conferma", 
        'wa_rej' => "Inviato WA Rifiuto", 
        'wa_canc' => "Inviato WA Annullamento", 
        'gcal' => "Aggiunto a Google Calendar", 
        default => "" 
    };
    if ($msg) aggiungiLog($conn, $id, $msg);
    header("Location: " . $url); exit;
}

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

if (isset($_POST['manual_booking'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $tel = $_POST['telefono']; $data = $_POST['data']; $ora = $_POST['ora']; $servizio = $_POST['servizio'];
    $conn->query("INSERT INTO prenotazioni (nome, telefono, data_appuntamento, ora_appuntamento, servizio, stato, log_azioni) VALUES ('$nome', '$tel', '$data', '$ora', '$servizio', 'accettato', '[Admin] Manuale\n')");
    header("Location: admin.php?current_tab=agenda"); exit;
}

// --- DATI E FILTRI ---
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

$sql_attesa = "SELECT * FROM prenotazioni WHERE stato='in_attesa'";
$sql_agenda = "SELECT * FROM prenotazioni WHERE stato='accettato'";

if (!empty($filter_date)) {
    $sql_attesa .= " AND data_appuntamento = '$filter_date'";
    $sql_agenda .= " AND data_appuntamento = '$filter_date'";
} else {
    $sql_agenda .= " AND data_appuntamento >= CURDATE()";
}

$sql_attesa .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";
$sql_agenda .= " ORDER BY data_appuntamento ASC, ora_appuntamento ASC";

$res_attesa = $conn->query($sql_attesa);
$res_agenda = $conn->query($sql_agenda);
$res_storico = $conn->query("SELECT * FROM prenotazioni WHERE stato='rifiutato' OR (data_appuntamento < CURDATE()) ORDER BY data_appuntamento DESC LIMIT 50");
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
        
        /* HEADER */
        .header { background: var(--primary); color: white; padding: 15px 20px; position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.2rem; font-weight: 800; letter-spacing: 1px; }
        .logout { color: #ff6b6b; font-size: 0.9rem; font-weight: 600; text-decoration:none; }
        
        /* TABS */
        .nav-tabs { display: flex; gap: 10px; padding: 15px 15px 0; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
        .tab-btn { 
            background: white; text-decoration: none; padding: 10px 20px; border-radius: 20px; 
            font-weight: 600; color: var(--gray); box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: 0.2s; flex-shrink: 0; display: inline-block;
        }
        .tab-btn.active { background: var(--accent); color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3); }
        .badge-count { background: var(--red); color: white; padding: 2px 6px; border-radius: 50%; font-size: 0.7rem; margin-left: 5px; vertical-align: top; }
        
        /* FILTERS */
        .filter-bar { background: white; margin: 15px; padding: 15px; border-radius: 12px; display: flex; gap: 10px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .filter-input { flex-grow: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; }
        .btn-reset { background: #eee; color: #333; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; text-decoration:none; display:inline-block; }
        
        /* CONTENT */
        .tab-content { display: none; padding: 0 15px 15px; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* CARDS */
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .time-badge { background: #edf2f7; color: var(--primary); font-weight: 800; font-size: 1.1rem; padding: 5px 12px; border-radius: 8px; }
        .date-badge { font-size: 0.85rem; color: var(--gray); font-weight: 600; text-transform: uppercase; }
        .client-name { font-size: 1.2rem; font-weight: 700; margin: 0; color: var(--primary); }
        .service-info { color: var(--gray); font-size: 0.95rem; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .phone-link { color: var(--accent); font-weight: 600; margin-top: 5px; display: inline-block; text-decoration:none; }
        
        /* ACTION BUTTONS - STACKED LAYOUT FOR LONG TEXT */
        .actions-stack { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer; width: 100%; text-decoration:none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .btn-green { background: #e6f4ea; color: var(--green); border: 1px solid #c3e6cb; }
        .btn-red { background: #fce8e6; color: var(--red); border: 1px solid #f5c6cb; }
        .btn-blue { background: #e8f0fe; color: #1a73e8; border: 1px solid #d2e3fc; }
        .btn-wa { background: #dcf8c6; color: #075e54; border: 1px solid #c2eabd; }
        
        /* ADMIN FORMS */
        .admin-panel { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 5px; color: var(--gray); }
        input, select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .fab { position: fixed; bottom: 20px; right: 20px; background: var(--primary); color: white; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); cursor: pointer; z-index: 900; text-decoration:none; }
    </style>
</head>
<body>

    <div class="header">
        <h1>BarberAdmin <i class="fas fa-cut" style="font-size:0.8em; color:var(--accent);"></i></h1>
        <a href="admin.php?logout=true" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="nav-tabs">
        <a href="admin.php?current_tab=inbox" class="tab-btn <?php echo $active_tab=='inbox'?'active':''; ?>">
            Da Confermare <?php if($count_total_attesa > 0) echo "<span class='badge-count'>$count_total_attesa</span>"; ?>
        </a>
        <a href="admin.php?current_tab=agenda" class="tab-btn <?php echo $active_tab=='agenda'?'active':''; ?>">
            Agenda
        </a>
        <a href="admin.php?current_tab=tools" class="tab-btn <?php echo $active_tab=='tools'?'active':''; ?>">
            Gestione & Ferie
        </a>
        <a href="admin.php?current_tab=history" class="tab-btn <?php echo $active_tab=='history'?'active':''; ?>">
            Storico
        </a>
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
            <div style="text-align:center; padding:50px 20px; color:#999;">
                <i class="fas fa-check-circle" style="font-size:3rem; color:#ddd; margin-bottom:10px;"></i>
                <p>Nessuna richiesta <?php echo $filter_date ? "per questa data" : "in attesa"; ?>.</p>
            </div>
        <?php endif; ?>

        <?php while($row = $res_attesa->fetch_assoc()): 
            $tel = preg_replace('/[^0-9]/', '', $row['telefono']);
            $wa_rej = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, purtroppo per quell'orario non riesco."));
            $qs = "&current_tab=$active_tab" . ($filter_date ? "&filter_date=$filter_date" : "");
        ?>
        <div class="card" style="border-left: 5px solid var(--accent);">
            <div class="card-header">
                <span class="date-badge"><?php echo date('d M', strtotime($row['data_appuntamento'])); ?></span>
                <span class="time-badge"><?php echo $row['ora_appuntamento']; ?></span>
            </div>
            <h3 class="client-name"><?php echo $row['nome']; ?></h3>
            <div class="service-info"><i class="fas fa-cut"></i> <?php echo $row['servizio']; ?></div>
            <a href="tel:<?php echo $tel; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $row['telefono']; ?></a>
            
            <div class="actions-stack">
                <a href="admin.php?action=accettato&id=<?php echo $row['id']; ?><?php echo $qs; ?>" class="btn btn-green"><i class="fas fa-check"></i> Accetta</a>
                <a href="admin.php?track=wa_rej&id=<?php echo $row['id']; ?>&url=<?php echo $wa_rej; ?>" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Avvisa che non puoi</a>
                <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?><?php echo $qs; ?>" class="btn btn-red"><i class="fas fa-times"></i> Rifiuta e Archivia</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <div id="agenda" class="tab-content <?php echo $active_tab=='agenda'?'active':''; ?>">
        <?php if($res_agenda->num_rows == 0): ?>
            <div style="text-align:center; padding:50px 20px; color:#999;">
                <p>Nessun appuntamento <?php echo $filter_date ? "il ".date('d/m', strtotime($filter_date)) : "futuro"; ?>.</p>
            </div>
        <?php endif; ?>

        <?php 
        $current_date = "";
        while($row = $res_agenda->fetch_assoc()): 
            $row_date = date('d/m/Y', strtotime($row['data_appuntamento']));
            $tel = preg_replace('/[^0-9]/', '', $row['telefono']);
            $wa_conf = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, confermo appuntamento per il $row_date alle {$row['ora_appuntamento']}."));
            $wa_canc = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, devo annullare l'appuntamento causa imprevisto."));
            
            $start = strtotime($row['data_appuntamento'].' '.$row['ora_appuntamento']);
            $end = $start + 1800;
            $gcal = base64_encode("https://calendar.google.com/calendar/render?action=TEMPLATE&text=Taglio+{$row['nome']}&dates=".date('Ymd\THis',$start)."/".date('Ymd\THis',$end)."&details=Tel:+$tel&src=leonardobonomi949@gmail.com");
            
            if($current_date != $row_date) {
                echo "<h3 style='margin: 20px 0 10px; color:#888; font-size:0.9rem; border-bottom:1px solid #eee; padding-bottom:5px;'>$row_date</h3>";
                $current_date = $row_date;
            }
        ?>
        <div class="card" style="border-left: 5px solid var(--green);">
            <div class="card-header">
                <span class="time-badge"><?php echo $row['ora_appuntamento']; ?></span>
                <div style="font-size:0.8rem; color:#999;"><?php echo $row['log_azioni'] ? '<i class="fas fa-history"></i>' : ''; ?></div>
            </div>
            <h3 class="client-name"><?php echo $row['nome']; ?></h3>
            <div class="service-info"><?php echo $row['servizio']; ?></div>
            
            <div class="actions-stack">
                <a href="admin.php?track=wa_conf&id=<?php echo $row['id']; ?>&url=<?php echo $wa_conf; ?>" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Conferma su WhatsApp</a>
                <a href="admin.php?track=gcal&id=<?php echo $row['id']; ?>&url=<?php echo $gcal; ?>" class="btn btn-blue"><i class="far fa-calendar-plus"></i> Aggiungi a Google Calendar</a>
                
                <details style="width:100%; margin-top:10px;">
                    <summary style="color:var(--red); font-size:0.8rem; cursor:pointer; text-align:center; padding:10px;">Altre Opzioni / Annulla</summary>
                    <div class="actions-stack" style="margin-top:10px;">
                        <a href="admin.php?track=wa_canc&id=<?php echo $row['id']; ?>&url=<?php echo $wa_canc; ?>" class="btn btn-wa"><i class="fab fa-whatsapp"></i> Invia Scuse WhatsApp</a>
                        <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?>" class="btn btn-red" onclick="return confirm('Sicuro?')">Declina Appuntamento</a>
                    </div>
                </details>
            </div>
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
                            <?php 
                            $s=strtotime("08:00"); $e=strtotime("19:30");
                            while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Cosa:</label><input type="text" name="servizio" value="Taglio"></div>
                <button type="submit" class="btn" style="background:var(--primary); color:white;">Salva Prenotazione</button>
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
                        <?php 
                        $s=strtotime("08:00"); $e=strtotime("19:30");
                        while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); }
                        ?>
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
                    <br><a href="admin.php?action=in_attesa&id=<?php echo $row['id']; ?>" style="font-size:0.8rem; color:blue;">Ripristina in attesa</a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <a href="admin.php?current_tab=tools&show_manual=1" class="fab"><i class="fas fa-plus"></i></a>

    <script>
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