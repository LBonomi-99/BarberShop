<?php
$chi_siamo_text = '';
$stmt_cs = $conn->prepare("SELECT content_text FROM site_content WHERE section_key='chi_siamo'");
if ($stmt_cs) { $stmt_cs->execute(); $r = $stmt_cs->get_result()->fetch_assoc(); $chi_siamo_text = $r ? $r['content_text'] : ''; }

$services = $conn->query("SELECT sl.*, sc.name AS cat_name FROM services_list sl LEFT JOIN service_categories sc ON sl.category = sc.name ORDER BY sl.category ASC, sl.sort_order ASC, sl.id ASC");
?>

<div id="cms" class="tab-content <?php echo $active_tab=='cms'?'active':''; ?>">

    <!-- CHI SIAMO -->
    <div class="card">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-align-left"></i> Modifica "Chi Siamo"</h3>
        </div>
        <form method="POST" action="admin.php?current_tab=cms">
            <input type="hidden" name="action" value="update_chi_siamo">
            <?php echo csrf_tag(); ?>
            <div class="form-group">
                <label>Testo descrizione (visibile in home page)</label>
                <textarea name="content_text" rows="8" style="width:100%;padding:14px;border:1px solid #e0e0e0;border-radius:8px;font-family:inherit;line-height:1.6;resize:vertical;font-size:0.94rem;"><?php echo htmlspecialchars($chi_siamo_text); ?></textarea>
                <p style="font-size:0.8rem;color:#aaa;margin-top:6px;">Usa il doppio "Invio" per creare un nuovo paragrafo.</p>
            </div>
            <button type="submit" name="update_chi_siamo" class="btn btn-green" style="max-width:200px;">
                <i class="fas fa-save"></i> Salva Testo
            </button>
        </form>
    </div>

    <!-- SOCIAL LINKS -->
    <div class="card" style="border-left:5px solid var(--accent); margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-share-alt"></i> Social Links</h3>
        </div>
        <form method="POST" action="admin.php?current_tab=cms" style="max-width:440px;">
            <input type="hidden" name="action" value="update_social">
            <?php echo csrf_tag(); ?>
            <div class="form-group">
                <label><i class="fab fa-instagram" style="color:#C13584;margin-right:6px;"></i> Instagram (URL completo)</label>
                <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($social_instagram); ?>" placeholder="https://instagram.com/tuoprofilo">
            </div>
            <div class="form-group">
                <label><i class="fab fa-facebook" style="color:#1877F2;margin-right:6px;"></i> Facebook (URL completo)</label>
                <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($social_facebook); ?>" placeholder="https://facebook.com/tuapagina">
            </div>
            <button type="submit" class="btn btn-green" style="max-width:200px;">
                <i class="fas fa-save"></i> Salva Links
            </button>
        </form>
    </div>

    <!-- GESTIONE CATEGORIE -->
    <div class="card" style="border-left:5px solid #6c757d; margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-tags"></i> Gestione Categorie Listino</h3>
        </div>

        <?php if (!empty($service_categories)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;">
            <?php foreach ($service_categories as $cat): ?>
            <span style="background:#f5f5f5;padding:6px 12px;border-radius:20px;border:1px solid #e0e0e0;font-size:0.9rem;display:flex;align-items:center;gap:8px;">
                <?php echo htmlspecialchars($cat['name']); ?>
                <a href="admin.php?action=delete_category&id=<?php echo (int)$cat['id']; ?>&current_tab=cms<?php echo csrf_q(); ?>"
                   onclick="return confirm('Eliminare la categoria \'<?php echo htmlspecialchars(addslashes($cat['name'])); ?>\'? (solo se non ha servizi associati)')"
                   style="color:var(--red);text-decoration:none;font-weight:700;line-height:1;font-size:1rem;">&times;</a>
            </span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:#aaa;font-size:0.9rem;margin-bottom:16px;">Nessuna categoria trovata.</p>
        <?php endif; ?>

        <form method="POST" action="admin.php?current_tab=cms" style="display:flex;gap:10px;align-items:flex-end;max-width:380px;">
            <input type="hidden" name="action" value="add_category">
            <?php echo csrf_tag(); ?>
            <div class="form-group" style="flex:1;margin:0;">
                <label>Nuova categoria</label>
                <input type="text" name="category_name" placeholder="Es. Trattamenti" required>
            </div>
            <button type="submit" class="btn btn-green" style="white-space:nowrap;height:44px;">
                <i class="fas fa-plus"></i> Aggiungi
            </button>
        </form>
    </div>

    <!-- GESTIONE LISTINO -->
    <div class="card" style="border-left:5px solid var(--accent); margin-top:20px;">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-cut"></i> Gestione Listino</h3>
        </div>

        <?php if ($services && $services->num_rows > 0): ?>
        <table style="width:100%;border-collapse:collapse;margin-bottom:28px;">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid #f0f0f0;">
                    <th style="padding:10px;font-size:0.8rem;color:#999;text-transform:uppercase;letter-spacing:0.5px;">Categoria</th>
                    <th style="padding:10px;font-size:0.8rem;color:#999;text-transform:uppercase;letter-spacing:0.5px;">Servizio</th>
                    <th style="padding:10px;font-size:0.8rem;color:#999;text-transform:uppercase;letter-spacing:0.5px;">Prezzo</th>
                    <th style="padding:10px;text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($srv = $services->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:10px;font-weight:600;color:var(--accent);font-size:0.88rem;"><?php echo htmlspecialchars($srv['category']); ?></td>
                    <td style="padding:10px;">
                        <?php echo htmlspecialchars($srv['name']); ?>
                        <?php if ($srv['description']): ?>
                        <br><small style="color:#aaa;"><?php echo htmlspecialchars($srv['description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px;font-variant-numeric:tabular-nums;">€&nbsp;<?php echo number_format((float)$srv['price'], 2, ',', '.'); ?></td>
                    <td style="padding:10px;text-align:right;">
                        <a href="admin.php?delete_service=<?php echo (int)$srv['id']; ?>&current_tab=cms<?php echo csrf_q(); ?>"
                           onclick="return confirm('Eliminare questo servizio?')"
                           style="color:var(--red);text-decoration:none;font-size:0.88rem;">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#aaa;font-size:0.9rem;margin-bottom:24px;">Nessun servizio presente.</p>
        <?php endif; ?>

        <div style="background:#f9f9f9;padding:20px;border-radius:10px;">
            <h4 style="margin:0 0 16px;font-size:0.95rem;">Aggiungi Nuovo Servizio</h4>
            <form method="POST" action="admin.php?current_tab=cms" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <input type="hidden" name="action" value="add_service">
                <?php echo csrf_tag(); ?>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <?php foreach ($service_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($service_categories)): ?>
                        <option value="Taglio &amp; Styling">Taglio &amp; Styling</option>
                        <option value="Barba">Barba</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nome Servizio</label>
                    <input type="text" name="name" placeholder="Es. Taglio Bambino" required>
                </div>
                <div class="form-group">
                    <label>Prezzo (€)</label>
                    <input type="number" name="price" step="0.50" min="0" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Dettagli (Opzionale)</label>
                    <input type="text" name="description" placeholder="Es. Include shampoo">
                </div>
                <div style="grid-column:1/-1;">
                    <button type="submit" class="btn btn-green" style="width:100%;">
                        <i class="fas fa-plus-circle"></i> Aggiungi al Listino
                    </button>
                </div>
            </form>
        </div>
    </div>

</div><!-- /tab cms -->
