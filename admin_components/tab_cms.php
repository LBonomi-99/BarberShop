<?php
// Query per recuperare i dati attuali
$chi_siamo_text = $conn->query("SELECT content_text FROM site_content WHERE section_key='chi_siamo'")->fetch_assoc()['content_text'];
$services = $conn->query("SELECT * FROM services_list ORDER BY category DESC, sort_order ASC, id ASC");
?>

<div id="cms" class="tab-content <?php echo $active_tab=='cms'?'active':''; ?>">
    
    <div class="card">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-align-left"></i> Modifica "Chi Siamo"</h3>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Testo Descrizione (visibile in home page):</label>
                <textarea name="content_text" rows="8" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:8px; font-family:inherit; line-height:1.5; resize:vertical;"><?php echo htmlspecialchars($chi_siamo_text); ?></textarea>
                <p style="font-size:0.8rem; color:#999; margin-top:5px;">Usa il doppio "Invio" per andare a capo e creare paragrafi.</p>
            </div>
            <button type="submit" name="update_chi_siamo" class="btn btn-green" style="max-width:200px;">
                <i class="fas fa-save"></i> Salva Testo
            </button>
        </form>
    </div>

    <div class="card" style="border-left: 5px solid var(--accent);">
        <div class="card-header">
            <h3 class="client-name"><i class="fas fa-cut"></i> Gestione Listino</h3>
        </div>

        <table style="width:100%; border-collapse:collapse; margin-bottom:30px;">
            <thead>
                <tr style="text-align:left; border-bottom:2px solid #eee; color:var(--gray);">
                    <th style="padding:10px;">Categoria</th>
                    <th style="padding:10px;">Nome Servizio</th>
                    <th style="padding:10px;">Prezzo</th>
                    <th style="padding:10px; text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php while($srv = $services->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; font-weight:bold; color:var(--accent);"><?php echo $srv['category']; ?></td>
                    <td style="padding:10px;">
                        <?php echo $srv['name']; ?>
                        <?php if($srv['description']) echo "<br><small style='color:#999'>{$srv['description']}</small>"; ?>
                    </td>
                    <td style="padding:10px;">€ <?php echo number_format($srv['price'], 2, ',', '.'); ?></td>
                    <td style="padding:10px; text-align:right;">
                        <a href="admin.php?delete_service=<?php echo $srv['id']; ?>" onclick="return confirm('Eliminare questo servizio?')" style="color:var(--red); text-decoration:none; font-weight:bold;">
                            <i class="fas fa-trash"></i> Elimina
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="background:#f9f9f9; padding:20px; border-radius:10px;">
            <h4 style="margin-top:0;">Aggiungi Nuovo Servizio</h4>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Categoria:</label>
                    <select name="category">
                        <option value="Taglio & Styling">Taglio & Styling</option>
                        <option value="Barba">Barba</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nome Servizio:</label>
                    <input type="text" name="name" placeholder="Es. Taglio Bambino" required>
                </div>
                <div class="form-group">
                    <label>Prezzo (€):</label>
                    <input type="number" name="price" step="0.50" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Dettagli (Opzionale):</label>
                    <input type="text" name="description" placeholder="Es. Include shampoo">
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_service" class="btn" style="background:var(--primary); color:white!important; width:100%;">
                        <i class="fas fa-plus-circle"></i> Aggiungi al Listino
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>