<?php
$total_trattate    = $stats_mese['accettato'] + $stats_mese['rifiutato'];
$tasso_accettazione = $total_trattate > 0 ? round(($stats_mese['accettato'] / $total_trattate) * 100) : 0;
$delta_mese        = $stats_mese['accettato'] - $mese_prec_accettate;
$circum            = 201.06; // 2 * pi * 32
$dash_filled       = round($tasso_accettazione * $circum / 100, 1);

// Prepara dati giorni settimana: DAYOFWEEK MySQL 1=Dom..7=Sab, voglio Lun-Sab
$day_keys   = [2,3,4,5,6,7]; // Lun=2,Mar=3,...,Sab=7
$day_labels = [2=>'Lun',3=>'Mar',4=>'Mer',5=>'Gio',6=>'Ven',7=>'Sab'];
$max_day = max(array_map(fn($k) => $stats_giorno[$k] ?? 0, $day_keys) + [0 => 1]);

// Top orari — calcola max per proporzione barre
$max_top = !empty($top_orari) ? max(array_column($top_orari, 'cnt')) : 1;
?>

<div id="dashboard" class="tab-content <?php echo $active_tab=='dashboard'?'active':''; ?>">
    <div style="padding:14px 0 4px 14px;">
        <span style="font-size:0.8rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:1px;">
            <?php echo date('F Y'); ?> &mdash; Riepilogo
        </span>
    </div>

    <div class="bento-grid" style="padding:0 14px 14px;">

        <!-- CARD 1: Prenotazioni mese (grande, 2 righe) -->
        <div class="bento-card bento-tall">
            <span class="bc-label">Prenotazioni accettate &mdash; questo mese</span>
            <div class="bc-number" id="countUpTarget">0</div>
            <?php if ($delta_mese >= 0): ?>
            <div class="bc-delta up">
                <i class="fas fa-arrow-up"></i> +<?php echo $delta_mese; ?> rispetto al mese scorso
            </div>
            <?php else: ?>
            <div class="bc-delta down">
                <i class="fas fa-arrow-down"></i> <?php echo $delta_mese; ?> rispetto al mese scorso
            </div>
            <?php endif; ?>

            <hr style="border:none;border-top:1px solid #f0f0f0;margin:20px 0;">

            <span class="bc-label">Prenotazioni per giorno</span>
            <div class="bar-chart" id="barChart">
                <?php foreach ($day_keys as $dk): ?>
                <?php $cnt = $stats_giorno[$dk] ?? 0; ?>
                <div class="bar-row">
                    <span class="bar-label"><?php echo $day_labels[$dk]; ?></span>
                    <div class="bar-track">
                        <div class="bar-fill" data-pct="<?php echo $max_day > 0 ? round($cnt / $max_day * 100) : 0; ?>"></div>
                    </div>
                    <span class="bar-count"><?php echo $cnt; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <hr style="border:none;border-top:1px solid #f0f0f0;margin:20px 0;">

            <span class="bc-label">Riepilogo stato &mdash; mese corrente</span>
            <div style="display:flex;gap:12px;margin-top:6px;flex-wrap:wrap;">
                <span class="badge badge-attesa"><i class="fas fa-clock"></i> In attesa: <?php echo $stats_mese['in_attesa']; ?></span>
                <span class="badge badge-accettato"><i class="fas fa-check"></i> Accettate: <?php echo $stats_mese['accettato']; ?></span>
                <span class="badge badge-rifiutato"><i class="fas fa-times"></i> Rifiutate: <?php echo $stats_mese['rifiutato']; ?></span>
            </div>
        </div>

        <!-- CARD 2: Tasso accettazione -->
        <div class="bento-card">
            <span class="bc-label">Tasso di accettazione</span>
            <div class="circle-wrap">
                <svg width="84" height="84" viewBox="0 0 84 84">
                    <circle cx="42" cy="42" r="34" fill="none" stroke="#e2e8f0" stroke-width="7"/>
                    <circle cx="42" cy="42" r="34" fill="none" stroke="#B8860B" stroke-width="7"
                        stroke-dasharray="<?php echo $dash_filled; ?> <?php echo $circum; ?>"
                        stroke-linecap="round" transform="rotate(-90 42 42)"/>
                </svg>
                <div class="circle-pct"><?php echo $tasso_accettazione; ?>%</div>
                <div class="circle-sub"><?php echo $total_trattate; ?> trattate totali</div>
            </div>
        </div>

        <!-- CARD 3: Top 5 orari -->
        <div class="bento-card">
            <span class="bc-label">Orari pi&ugrave; richiesti</span>
            <?php if (!empty($top_orari)): ?>
            <ul class="top-list">
                <?php foreach ($top_orari as $t): ?>
                <li>
                    <span class="top-ora"><?php echo htmlspecialchars($t['ora_appuntamento']); ?></span>
                    <div class="top-bar">
                        <div class="top-bar-fill" style="width:<?php echo round($t['cnt'] / $max_top * 100); ?>%"></div>
                    </div>
                    <span class="top-cnt"><?php echo $t['cnt']; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p style="color:#aaa;font-size:0.88rem;margin-top:8px;">Nessun dato disponibile.</p>
            <?php endif; ?>
        </div>

    </div><!-- /bento-grid -->
</div>

<script>
(function () {
    // CountUp animazione
    var target = <?php echo $stats_mese['accettato']; ?>;
    var el = document.getElementById('countUpTarget');
    if (!el) return;
    if (target === 0) { el.textContent = '0'; return; }
    var start = 0, duration = 900, step = target / (duration / 16);
    var timer = setInterval(function () {
        start += step;
        if (start >= target) { el.textContent = target; clearInterval(timer); return; }
        el.textContent = Math.floor(start);
    }, 16);

    // Bar chart animazione
    document.querySelectorAll('.bar-fill').forEach(function (bar) {
        var pct = bar.getAttribute('data-pct');
        setTimeout(function () { bar.style.width = pct + '%'; }, 200);
    });
})();
</script>
