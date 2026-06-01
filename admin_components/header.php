<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#1C1C1C; --accent:#B8860B; --bg:#f4f7f6; --white:#fff; --green:#28a745; --red:#dc3545; --gray:#6c757d; --spring:cubic-bezier(0.25,0.46,0.45,0.94); }
        *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Outfit',sans-serif; background:var(--bg); padding-bottom:90px; color:#333; }

        /* HEADER */
        .header { background:var(--primary); color:white; padding:14px 20px; position:sticky; top:0; z-index:100; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,0.12); }
        .header h1 { margin:0; font-size:1.15rem; font-weight:800; letter-spacing:1px; }
        .logout { color:#ff6b6b; font-size:0.88rem; font-weight:600; text-decoration:none; transition:opacity 0.2s; }
        .logout:hover { opacity:0.75; }

        /* TABS */
        .nav-tabs { display:flex; gap:8px; padding:14px 14px 0; overflow-x:auto; white-space:nowrap; scrollbar-width:none; }
        .nav-tabs::-webkit-scrollbar { display:none; }
        .tab-btn { background:white; text-decoration:none; padding:9px 18px; border-radius:20px; font-weight:600; color:var(--gray); box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:all 0.2s var(--spring); flex-shrink:0; display:inline-block; font-size:0.88rem; }
        .tab-btn:hover { color:var(--primary); box-shadow:0 3px 8px rgba(0,0,0,0.1); }
        .tab-btn.active { background:var(--accent); color:white; transform:translateY(-2px); box-shadow:0 4px 12px rgba(184,134,11,0.3); }
        .badge-count { background:var(--red); color:white; padding:2px 6px; border-radius:50%; font-size:0.68rem; margin-left:5px; vertical-align:top; }

        /* FILTER BAR */
        .filter-bar { background:white; margin:14px; padding:13px 15px; border-radius:12px; display:flex; gap:10px; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
        .filter-input { flex-grow:1; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit; font-size:0.95rem; }
        .btn-filter { background:var(--primary); color:white; border:none; padding:10px 15px; border-radius:8px; cursor:pointer; transition:0.2s; }
        .btn-filter:hover { background:#333; }
        .btn-reset { background:#eee; color:#333; border:none; padding:10px 15px; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-block; font-size:0.9rem; transition:0.2s; }

        /* FEEDBACK */
        .admin-feedback { margin:0 14px 10px; padding:11px 16px; border-radius:8px; font-size:0.88rem; font-weight:600; }
        .feedback-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .feedback-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        /* TAB CONTENT */
        .tab-content { display:none; padding:0 14px 14px; animation:fadeIn 0.25s var(--spring); }
        .tab-content.active { display:block; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

        /* CARDS */
        .card { background:white; border-radius:12px; padding:20px; margin-bottom:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05); position:relative; overflow:hidden; transition:box-shadow 0.2s; }
        .card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .time-badge { background:#edf2f7; color:var(--primary); font-weight:800; font-size:1.05rem; padding:5px 12px; border-radius:8px; }
        .date-badge { font-size:0.82rem; color:var(--gray); font-weight:600; text-transform:uppercase; }
        .client-name { font-size:1.15rem; font-weight:700; margin:0; color:var(--primary); }
        .service-info { color:var(--gray); font-size:0.92rem; margin-top:4px; display:flex; align-items:center; gap:5px; }
        .phone-link { color:var(--accent); font-weight:600; margin-top:5px; display:inline-block; text-decoration:none; font-size:0.92rem; }

        /* FLOW STEPS */
        .flow-step { margin-top:18px; padding-top:14px; border-top:1px dashed #ddd; animation:fadeIn 0.25s; }
        .step-title { font-size:0.8rem; font-weight:700; color:var(--gray); margin-bottom:10px; display:block; text-transform:uppercase; text-align:center; }
        .actions-grid { display:flex; gap:10px; justify-content:center; }
        .actions-stack { display:flex; flex-direction:column; gap:10px; align-items:center; }

        /* BUTTONS */
        .btn { display:flex; align-items:center; justify-content:center; gap:8px; padding:13px; border-radius:50px; font-weight:600; font-size:0.92rem; cursor:pointer; width:100%; max-width:500px; text-decoration:none; box-shadow:0 1px 3px rgba(0,0,0,0.08); transition:all 0.2s var(--spring); border:none; font-family:inherit; }
        .btn:hover { filter:brightness(0.94); transform:translateY(-1px); }
        .btn:active { transform:translateY(0) scale(0.98); }
        .btn-green    { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .btn-red      { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .btn-blue     { background:#cfe2ff; color:#084298; border:1px solid #b6d4fe; }
        .btn-wa       { background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
        .btn-disabled { opacity:0.45; cursor:not-allowed; filter:grayscale(80%); pointer-events:none; }

        /* ADMIN PANEL (forms) */
        .admin-panel { background:white; padding:20px; border-radius:12px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .admin-panel h3 { font-size:1rem; font-weight:700; margin-bottom:16px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:0.82rem; font-weight:700; margin-bottom:5px; color:var(--gray); }
        input, select, textarea { width:100%; padding:11px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:0.95rem; font-family:inherit; color:#333; transition:border-color 0.2s; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--accent); }

        /* BADGES */
        .badge { padding:4px 10px; border-radius:14px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
        .badge-accettato { background:#d4edda; color:#155724; }
        .badge-rifiutato { background:#f8d7da; color:#721c24; }
        .badge-attesa    { background:#fff3cd; color:#856404; }

        /* FAB */
        .fab { position:fixed; bottom:24px; right:20px; background:var(--primary); color:white; width:54px; height:54px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 4px 16px rgba(0,0,0,0.28); cursor:pointer; z-index:900; text-decoration:none; transition:transform 0.2s var(--spring),background 0.2s; }
        .fab:hover { background:#333; transform:scale(1.08); }

        /* MODAL */
        .modal-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:2000; }
        .modal-box { background:white; padding:30px; border-radius:12px; width:90%; max-width:400px; text-align:center; box-shadow:0 10px 28px rgba(0,0,0,0.2); }

        /* ===== DASHBOARD BENTO ===== */
        .bento-grid { display:grid; grid-template-columns:2fr 1fr; gap:14px; }
        .bento-tall { grid-row:span 2; }
        .bento-card { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border:1px solid rgba(0,0,0,0.04); }
        .bento-card .bc-label { font-size:0.76rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--gray); margin-bottom:12px; display:block; }
        .bento-card .bc-number { font-size:3.2rem; font-weight:800; color:var(--primary); line-height:1; }
        .bento-card .bc-delta { font-size:0.88rem; font-weight:600; margin-top:10px; }
        .bc-delta.up   { color:#28a745; }
        .bc-delta.down { color:var(--red); }
        .bc-delta.flat { color:var(--gray); }

        /* Bar chart */
        .bar-chart { margin-top:10px; }
        .bar-row { display:flex; align-items:center; gap:10px; margin-bottom:7px; }
        .bar-label { font-size:0.78rem; font-weight:600; color:var(--gray); width:28px; flex-shrink:0; }
        .bar-track { flex-grow:1; background:#f0f0f0; border-radius:4px; height:10px; overflow:hidden; }
        .bar-fill { height:100%; background:var(--accent); border-radius:4px; transition:width 1s cubic-bezier(0.25,0.46,0.45,0.94); width:0%; }
        .bar-count { font-size:0.78rem; color:var(--gray); width:20px; text-align:right; flex-shrink:0; }

        /* Circle progress */
        .circle-wrap { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; }
        .circle-pct { font-size:1.9rem; font-weight:800; color:var(--primary); margin-top:12px; }
        .circle-sub { font-size:0.78rem; color:var(--gray); margin-top:4px; }

        /* Top orari */
        .top-list { list-style:none; margin-top:6px; }
        .top-list li { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid #f0f0f0; }
        .top-list li:last-child { border:none; }
        .top-ora { font-weight:700; font-size:0.9rem; color:var(--primary); width:42px; flex-shrink:0; }
        .top-bar { flex-grow:1; background:#f0f0f0; border-radius:3px; height:6px; overflow:hidden; }
        .top-bar-fill { height:100%; background:var(--accent); border-radius:3px; }
        .top-cnt { font-size:0.78rem; color:var(--gray); width:16px; text-align:right; }

        /* ===== SETTIMANA CALENDARIO ===== */
        .view-toggle { display:flex; gap:8px; margin:14px 14px 4px; }
        .view-btn { padding:8px 18px; border-radius:20px; font-weight:600; cursor:pointer; border:1px solid #e2e8f0; background:white; color:var(--gray); font-family:inherit; font-size:0.87rem; transition:all 0.2s; }
        .view-btn.active { background:var(--accent); color:white; border-color:var(--accent); }
        .week-nav { display:flex; align-items:center; justify-content:space-between; margin:0 14px 12px; background:white; padding:11px 16px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
        .week-nav span { font-weight:700; font-size:0.9rem; color:var(--primary); }
        .week-nav button { background:#eee; border:none; border-radius:50%; width:30px; height:30px; cursor:pointer; font-size:0.75rem; transition:0.2s; display:flex; align-items:center; justify-content:center; }
        .week-nav button:hover { background:var(--accent); color:white; }
        .week-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin:0 14px 14px; }
        .week-day { background:white; border-radius:10px; padding:10px 7px; min-height:80px; box-shadow:0 2px 5px rgba(0,0,0,0.04); }
        .week-day-header { font-size:0.7rem; font-weight:700; text-transform:uppercase; color:var(--gray); margin-bottom:4px; text-align:center; }
        .week-day-date { text-align:center; font-weight:800; font-size:1rem; color:var(--primary); margin-bottom:6px; }
        .week-day.today .week-day-date { color:var(--accent); }
        .appt-badge { background:#d4edda; color:#155724; font-size:0.67rem; padding:3px 5px; border-radius:4px; margin-bottom:3px; cursor:pointer; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; transition:0.15s; display:block; }
        .appt-badge:hover { filter:brightness(0.9); transform:translateY(-1px); }
        .week-day-add { display:block; width:100%; margin-top:4px; border:1px dashed #d0d0d0; background:transparent; color:#bbb; border-radius:5px; font-size:0.85rem; line-height:1; padding:3px 0; cursor:pointer; transition:0.15s; font-family:inherit; }
        .week-day-add:hover { border-color:var(--accent); color:var(--accent); }

        /* ===== MODALITA CONFERMA (tools) ===== */
        .mode-option { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:10px; cursor:pointer; transition:0.15s; font-size:0.9rem; line-height:1.45; }
        .mode-option:hover { border-color:var(--accent); }
        .mode-option.selected { border-color:var(--accent); background:#fffaf0; }
        .mode-option input { margin-top:3px; flex-shrink:0; }

        /* ===== MODAL DETTAGLIO APPUNTAMENTO (agenda) ===== */
        .appt-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index:2000; padding:16px; }
        .appt-modal-overlay.open { display:flex; }
        .appt-modal { background:#fff; border-radius:14px; width:100%; max-width:400px; box-shadow:0 12px 32px rgba(0,0,0,0.25); overflow:hidden; }
        .appt-modal-head { background:var(--primary); color:#fff; padding:18px 20px; }
        .appt-modal-head h3 { margin:0; font-size:1.15rem; font-weight:700; }
        .appt-modal-head .am-sub { font-size:0.85rem; color:#bbb; margin-top:3px; }
        .appt-modal-body { padding:18px 20px; }
        .appt-modal-body .am-row { display:flex; align-items:center; gap:8px; font-size:0.92rem; color:#444; margin-bottom:8px; }
        .appt-modal-body .am-row i { color:var(--accent); width:16px; text-align:center; }
        .appt-modal-actions { display:flex; flex-direction:column; gap:10px; margin-top:14px; }
        .am-move-form { display:none; gap:8px; margin-top:6px; padding-top:12px; border-top:1px dashed #ddd; }
        .am-move-form.open { display:block; }
        .am-close { background:transparent; border:none; color:#999; font-size:0.85rem; cursor:pointer; font-family:inherit; padding:6px; }

        /* ===== ORARI APERTURA ===== */
        .hours-table { width:100%; border-collapse:collapse; }
        .hours-table th { text-align:left; padding:8px 10px; font-size:0.78rem; font-weight:700; color:var(--gray); text-transform:uppercase; border-bottom:2px solid #eee; }
        .hours-table td { padding:8px 10px; vertical-align:middle; font-size:0.9rem; border-bottom:1px solid #f5f5f5; }
        .hours-table td input[type="time"] { width:auto; padding:7px 8px; font-size:0.85rem; }
        .hours-table td input[type="checkbox"] { width:auto; }
        .day-closed td input:not([type="checkbox"]) { opacity:0.3; pointer-events:none; }

        @media (max-width:600px) {
            .bento-grid { grid-template-columns:1fr; }
            .bento-tall { grid-row:auto; }
            .week-grid  { grid-template-columns:repeat(7,1fr); gap:4px; }
            .week-day   { padding:6px 4px; min-height:60px; }
            .week-day-header { font-size:0.62rem; }
            .hours-table { font-size:0.78rem; }
            .hours-table td input[type="time"] { width:100%; }
        }
    </style>
</head>
<body>

    <?php if (date('m') == '01' && !isset($_SESSION['maintenance_shown'])): $_SESSION['maintenance_shown'] = true; ?>
    <div id="maintenanceModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color:var(--red);margin-top:0;"><i class="fas fa-exclamation-triangle"></i> Manutenzione Annuale</h3>
            <p style="margin:12px 0 20px;">Elimina storico &gt; 1 anno per GDPR.</p>
            <div style="display:flex;gap:10px;justify-content:center;flex-direction:column;">
                <a href="admin.php?action=clean_old<?php echo csrf_q(); ?>" class="btn btn-red">Esegui Pulizia</a>
                <button onclick="document.getElementById('maintenanceModal').style.display='none'" class="btn" style="background:#eee;color:#333;">Chiudi</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="header">
        <h1>BarberAdmin <i class="fas fa-cut" style="font-size:0.75em;color:var(--accent);"></i></h1>
        <a href="admin.php?logout=true" class="logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="nav-tabs">
        <a href="admin.php?current_tab=dashboard" class="tab-btn <?php echo $active_tab=='dashboard'?'active':''; ?>"><i class="fas fa-chart-bar" style="font-size:0.8em;"></i> Dashboard</a>
        <a href="admin.php?current_tab=inbox"     class="tab-btn <?php echo $active_tab=='inbox'?'active':''; ?>">Da Confermare <?php if ($count_total_attesa > 0) echo "<span class='badge-count'>$count_total_attesa</span>"; ?></a>
        <a href="admin.php?current_tab=agenda"    class="tab-btn <?php echo $active_tab=='agenda'?'active':''; ?>">Agenda</a>
        <a href="admin.php?current_tab=tools"     class="tab-btn <?php echo $active_tab=='tools'?'active':''; ?>">Gestione</a>
        <a href="admin.php?current_tab=cms"       class="tab-btn <?php echo $active_tab=='cms'?'active':''; ?>">Contenuti</a>
        <a href="admin.php?current_tab=history"   class="tab-btn <?php echo $active_tab=='history'?'active':''; ?>">Storico</a>
    </div>

    <?php if ($admin_msg): ?>
    <?php
    $msgs = [
        'saved'        => 'Testo aggiornato.',
        'added'        => 'Servizio aggiunto.',
        'deleted'      => 'Servizio eliminato.',
        'cat_added'    => 'Categoria aggiunta.',
        'cat_deleted'  => 'Categoria eliminata.',
        'social_saved' => 'Link social aggiornati.',
        'hours_saved'  => 'Orari di apertura aggiornati.',
        'pass_changed' => 'Password aggiornata con successo.',
        'pass_error'   => 'Errore: password troppo corta o le due password non coincidono.',
        'pass_wrong'   => 'Errore: password attuale non corretta.',
        'cleaned'      => 'Storico vecchio eliminato.',
        'mode_saved'   => 'Modalità di conferma aggiornata.',
        'moved'        => 'Appuntamento spostato.',
        'slot_taken'   => 'Slot già occupato: scegli un altro orario.',
        'error'        => 'Operazione non riuscita. Riprova.',
        'csrf_error'   => 'Sessione scaduta: ricarica la pagina e riprova.',
    ];
    $is_error = in_array($admin_msg, ['pass_error','pass_wrong','slot_taken','error','csrf_error']);
    echo '<div class="admin-feedback ' . ($is_error ? 'feedback-error' : 'feedback-success') . '">' . ($msgs[$admin_msg] ?? '') . '</div>';
    ?>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="admin.php">
        <input type="hidden" name="current_tab" value="<?php echo $active_tab; ?>">
        <input type="date" name="filter_date" class="filter-input" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()">
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i></button>
        <?php if ($filter_date): ?>
        <a href="admin.php?current_tab=<?php echo $active_tab; ?>" class="btn-reset"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
