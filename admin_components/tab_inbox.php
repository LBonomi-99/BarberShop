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