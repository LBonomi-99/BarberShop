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