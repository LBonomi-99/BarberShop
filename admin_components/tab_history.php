<div id="history" class="tab-content <?php echo $active_tab=='history'?'active':''; ?>">
    <?php while($row = $res_storico->fetch_assoc()): ?>
        <div class="card" style="opacity:0.7;">
            <div class="card-header">
                <span><?php echo date('d/m', strtotime($row['data_appuntamento'])); ?></span>
                <span class="badge badge-<?php echo $row['stato']; ?>"><?php echo $row['stato']; ?></span>
            </div>
            <strong><?php echo $row['nome']; ?></strong>
            
            <?php if($row['stato'] == 'rifiutato'): ?>
                
                <?php 
                // Controllo Data: Se la data è oggi o futura, mostro il tasto
                if($row['data_appuntamento'] >= date('Y-m-d')): 
                ?>
                    <div class="actions-stack" style="margin-top:10px;">
                        <a href="admin.php?action=in_attesa&id=<?php echo $row['id']; ?>" class="btn btn-blue" onclick="return confirm('Vuoi ripristinare questa richiesta? Tornerà in Da Confermare.')">
                            <i class="fas fa-undo"></i> Ripristina in Attesa
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; margin-top:10px; color:var(--gray); font-weight:bold; font-size:0.85rem;">
                        <i class="fas fa-lock"></i> Archiviato (Data passata)
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="text-align:center; margin-top:10px; color:var(--green); font-weight:bold; font-size:0.85rem;">
                    <i class="fas fa-check-double"></i> Concluso
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>