<?php
$day_names = [0=>'Domenica',1=>'Lunedì',2=>'Martedì',3=>'Mercoledì',4=>'Giovedì',5=>'Venerdì',6=>'Sabato'];
$prefill_date = (isset($_GET['new_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['new_date'])) ? $_GET['new_date'] : '';
?>

<div id="tools" class="tab-content <?php echo $active_tab=='tools'?'active':''; ?>">

    <!-- ORARI DI APERTURA -->
    <div class="card" style="border-left:5px solid var(--accent);">
        <div class="card-header">
            <h3 class="client-name"><i class="far fa-clock"></i> Orari di Apertura</h3>
        </div>
        <form method="POST" action="admin.php?current_tab=tools">
            <input type="hidden" name="action" value="update_opening_hours">
            <?php echo csrf_tag(); ?>
            <table class="hours-table">
                <thead>
                    <tr>
                        <th>Giorno</th>
                        <th>Chiuso</th>
                        <th>Mattina inizio</th>
                        <th>Mattina fine</th>
                        <th>Pomeriggio inizio</th>
                        <th>Pomeriggio fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($g = 0; $g <= 6; $g++):
                        $oh     = $opening_hours_data[$g] ?? ['mattina_inizio'=>'','mattina_fine'=>'','pomeriggio_inizio'=>'','pomeriggio_fine'=>'','chiuso'=>1];
                        $chiuso = (bool)($oh['chiuso'] ?? 0);
                    ?>
                    <tr id="hours_row_<?php echo $g; ?>" <?php echo $chiuso ? 'class="day-closed"' : ''; ?>>
                        <td style="font-weight:600;"><?php echo $day_names[$g]; ?></td>
                        <td>
                            <input type="checkbox" name="chiuso[<?php echo $g; ?>]" value="1"
                                style="width:auto;"
                                <?php echo $chiuso ? 'checked' : ''; ?>
                                onchange="toggleHoursRow(<?php echo $g; ?>, this.checked)">
                        </td>
                        <td><input type="time" name="mattina_inizio[<?php echo $g; ?>]"
                            value="<?php echo htmlspecialchars($oh['mattina_inizio'] ?? ''); ?>"
                            <?php echo $chiuso ? 'disabled' : ''; ?>></td>
                        <td><input type="time" name="mattina_fine[<?php echo $g; ?>]"
                            value="<?php echo htmlspecialchars($oh['mattina_fine'] ?? ''); ?>"
                            <?php echo $chiuso ? 'disabled' : ''; ?>></td>
                        <td><input type="time" name="pomeriggio_inizio[<?php echo $g; ?>]"
                            value="<?php echo htmlspecialchars($oh['pomeriggio_inizio'] ?? ''); ?>"
                            <?php echo $chiuso ? 'disabled' : ''; ?>></td>
                        <td><input type="time" name="pomeriggio_fine[<?php echo $g; ?>]"
                            value="<?php echo htmlspecialchars($oh['pomeriggio_fine'] ?? ''); ?>"
                            <?php echo $chiuso ? 'disabled' : ''; ?>></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-green" style="max-width:220px;">
                    <i class="fas fa-save"></i> Salva Orari
                </button>
            </div>
        </form>
    </div>

    <!-- CAMBIO PASSWORD -->
    <div class="card" style="border-left:5px solid var(--gray); margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-lock"></i> Sicurezza — Cambio Password</h3>
        </div>
        <form method="POST" action="admin.php?current_tab=tools" style="max-width:380px;">
            <input type="hidden" name="action" value="change_password">
            <?php echo csrf_tag(); ?>
            <div class="form-group">
                <label>Password attuale</label>
                <input type="password" name="old_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>Nuova password</label>
                <input type="password" name="new_password" required autocomplete="new-password" minlength="6">
            </div>
            <div class="form-group">
                <label>Conferma nuova password</label>
                <input type="password" name="confirm_password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn" style="background:#495057;color:#fff;max-width:200px;">
                <i class="fas fa-key"></i> Aggiorna Password
            </button>
        </form>
    </div>

    <!-- MODALITA CONFERMA PRENOTAZIONI -->
    <div class="card" style="border-left:5px solid var(--accent);margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-bolt"></i> Conferma Prenotazioni Online</h3>
        </div>
        <p style="font-size:0.9rem;color:var(--gray);margin:0 0 14px;">
            Decidi cosa succede quando un cliente prenota dal sito.
        </p>
        <form method="POST" action="admin.php?current_tab=tools">
            <input type="hidden" name="action" value="update_booking_mode">
            <?php echo csrf_tag(); ?>
            <label class="mode-option <?php echo $booking_mode==='auto'?'selected':''; ?>">
                <input type="radio" name="booking_mode" value="auto" <?php echo $booking_mode==='auto'?'checked':''; ?> style="width:auto;">
                <span><strong>Automatica</strong> — la prenotazione è confermata subito e il cliente riceve l'email di conferma. Lo slot viene occupato all'istante.</span>
            </label>
            <label class="mode-option <?php echo $booking_mode==='approval'?'selected':''; ?>">
                <input type="radio" name="booking_mode" value="approval" <?php echo $booking_mode==='approval'?'checked':''; ?> style="width:auto;">
                <span><strong>Su approvazione</strong> — la richiesta arriva in "Da Confermare" e la confermi tu manualmente.</span>
            </label>
            <button type="submit" class="btn btn-green" style="max-width:200px;margin-top:6px;">
                <i class="fas fa-save"></i> Salva Modalità
            </button>
        </form>
    </div>

    <!-- INSERIMENTO MANUALE -->
    <div class="card" style="margin-top:20px;">
        <div class="card-header" style="cursor:pointer;" onclick="toggleManual()">
            <h3 class="client-name"><i class="fas fa-pencil-alt"></i> Inserimento Manuale Prenotazione</h3>
            <span id="manualToggleIcon" style="color:var(--gray);font-size:0.85rem;">Mostra</span>
        </div>
        <div id="manualForm" style="display:none;padding-top:12px;">
            <form method="POST" action="admin.php?current_tab=tools">
                <input type="hidden" name="manual_booking" value="1">
                <?php echo csrf_tag(); ?>
                <div class="form-group"><label>Nome cliente</label><input type="text" name="nome" required></div>
                <div class="form-group"><label>Telefono</label><input type="text" name="telefono" required></div>
                <div class="form-group"><label>Email <span style="font-weight:400;color:#999;">(opzionale, per conferma)</span></label><input type="email" name="email"></div>
                <div class="form-group">
                    <label>Data e Ora</label>
                    <div style="display:flex;gap:10px;">
                        <input type="date" name="data" value="<?php echo htmlspecialchars($prefill_date ?: date('Y-m-d')); ?>" required style="flex:1;">
                        <select name="ora" style="flex:1;">
                            <?php $s=strtotime("08:00"); $e=strtotime("19:30"); while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Servizio</label><input type="text" name="servizio" value="Taglio"></div>
                <button type="submit" class="btn btn-green">
                    <i class="fas fa-plus"></i> Salva Prenotazione
                </button>
            </form>
        </div>
    </div>

    <!-- BLOCCA ORARI / FERIE -->
    <div class="card" style="border-left:5px solid var(--accent);margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-ban"></i> Blocca Orari / Ferie</h3>
        </div>
        <form method="POST" action="admin.php?current_tab=tools">
            <?php echo csrf_tag(); ?>
            <div class="form-group"><label>Giorno</label><input type="date" name="data_blocco" required></div>
            <div class="form-group">
                <label>Tipo blocco</label>
                <select name="block_type" id="blockType" onchange="toggleBlockInputs()">
                    <option value="single">Solo un orario</option>
                    <option value="full_day">Tutto il giorno (Ferie)</option>
                    <option value="range">Periodo (Più giorni)</option>
                </select>
            </div>
            <div class="form-group" id="endDateGroup" style="display:none;">
                <label>Fino al</label><input type="date" name="data_fine">
            </div>
            <div class="form-group" id="timeSelectGroup">
                <label>Ora</label>
                <select name="ora_blocco">
                    <?php $s=strtotime("08:00"); $e=strtotime("19:30"); while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); } ?>
                </select>
            </div>
            <button type="submit" class="btn btn-red">Applica Blocco</button>
        </form>

        <?php if ($slot_full->num_rows > 0): ?>
        <div style="margin-top:20px;">
            <span style="font-size:0.82rem;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.5px;">Blocchi attivi</span>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                <?php while ($row = $slot_full->fetch_assoc()): ?>
                <span style="background:#fff;padding:5px 10px;border-radius:6px;border:1px solid #e0e0e0;font-size:0.88rem;display:flex;align-items:center;gap:6px;">
                    <?php echo date('d/m', strtotime($row['data_blocco']))." ".$row['ora_blocco']; ?>
                    <a href="admin.php?delete_block=<?php echo $row['id']; ?>&current_tab=tools<?php echo csrf_q(); ?>"
                       style="color:var(--red);text-decoration:none;font-weight:700;line-height:1;">&times;</a>
                </span>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- GDPR -->
    <div class="card" style="border-left:5px solid var(--red);margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-trash-alt"></i> Manutenzione Privacy (GDPR)</h3>
        </div>
        <p style="font-size:0.9rem;color:var(--gray);margin:0 0 14px;">
            Elimina le prenotazioni con data superiore a 1 anno per conformità GDPR.
        </p>
        <a href="admin.php?action=clean_old&current_tab=tools<?php echo csrf_q(); ?>"
           class="btn btn-red"
           style="max-width:260px;"
           onclick="return confirm('Eliminare definitivamente i dati storici? L\'operazione è irreversibile.')">
            <i class="fas fa-trash-alt"></i> Elimina Storico Vecchio
        </a>
    </div>

</div><!-- /tab tools -->

<script>
function toggleHoursRow(g, isClosed) {
    var row = document.getElementById('hours_row_' + g);
    if (!row) return;
    row.classList.toggle('day-closed', isClosed);
    row.querySelectorAll('input[type="time"]').forEach(function (el) {
        el.disabled = isClosed;
    });
}

function toggleManual() {
    var form = document.getElementById('manualForm');
    var icon = document.getElementById('manualToggleIcon');
    var open = form.style.display === 'none';
    form.style.display = open ? 'block' : 'none';
    icon.textContent   = open ? 'Nascondi' : 'Mostra';
}

function toggleBlockInputs() {
    var type = document.getElementById('blockType').value;
    document.getElementById('endDateGroup').style.display   = (type === 'range')  ? 'block' : 'none';
    document.getElementById('timeSelectGroup').style.display = (type === 'single') ? 'block' : 'none';
}

// Evidenzia l'opzione modalità conferma selezionata
document.querySelectorAll('.mode-option input[type="radio"]').forEach(function (r) {
    r.addEventListener('change', function () {
        document.querySelectorAll('.mode-option').forEach(function (l) { l.classList.remove('selected'); });
        this.closest('.mode-option').classList.add('selected');
    });
});

// Se arrivo dal calendario con ?new_date=..., apri il form manuale già prefillato
<?php if ($prefill_date): ?>
toggleManual();
document.getElementById('manualForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
<?php endif; ?>
</script>
