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