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
        <a href="admin.php?current_tab=cms" class="tab-btn <?php echo $active_tab=='cms'?'active':''; ?>">Contenuti Sito</a>
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