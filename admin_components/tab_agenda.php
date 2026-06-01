<div id="agenda" class="tab-content <?php echo $active_tab=='agenda'?'active':''; ?>">

    <!-- Toggle Vista -->
    <div class="view-toggle">
        <button class="view-btn active" id="listViewBtn"><i class="fas fa-list" style="font-size:0.8em;"></i> Lista</button>
        <button class="view-btn" id="calViewBtn"><i class="far fa-calendar-alt" style="font-size:0.8em;"></i> Settimana</button>
    </div>

    <!-- VISTA CALENDARIO SETTIMANALE -->
    <div id="weekCalendar" style="display:none;">
        <div class="week-nav">
            <button id="prevWeek"><i class="fas fa-chevron-left"></i></button>
            <span id="weekLabel"></span>
            <button id="nextWeek"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="week-grid" id="weekGrid"></div>
    </div>

    <!-- VISTA LISTA -->
    <div id="listView">
        <?php if ($res_agenda->num_rows == 0): ?>
        <div style="text-align:center;padding:50px 20px;color:#bbb;">
            <i class="far fa-calendar-check" style="font-size:2rem;margin-bottom:12px;display:block;"></i>
            <p>Nessun appuntamento in agenda.</p>
        </div>
        <?php endif; ?>

        <?php while ($row = $res_agenda->fetch_assoc()):
            $tel      = preg_replace('/[^0-9]/', '', $row['telefono']);
            $wa_canc  = base64_encode("https://wa.me/39$tel?text=" . urlencode("Ciao {$row['nome']}, devo annullare l'appuntamento causa imprevisto."));
            $date_ymd = date('Y/m/d', strtotime($row['data_appuntamento']));
            $gcal_del = base64_encode("https://calendar.google.com/calendar/r/day/$date_ymd");

            $is_active = ($flow_id == $row['id']);
            $cur_step  = $is_active ? $flow_step : 'view';
            $base_url  = "admin.php?current_tab=agenda" . ($filter_date ? "&filter_date=$filter_date" : "") . "&flow_id={$row['id']}";
        ?>
        <div class="card" style="border-left:5px solid var(--green);" data-id="<?php echo $row['id']; ?>">
            <div class="card-header">
                <span class="time-badge"><?php echo htmlspecialchars($row['ora_appuntamento']); ?></span>
                <span class="date-badge"><?php echo date('d M', strtotime($row['data_appuntamento'])); ?></span>
            </div>
            <h3 class="client-name"><?php echo htmlspecialchars($row['nome']); ?></h3>
            <div class="service-info"><i class="fas fa-cut"></i> <?php echo htmlspecialchars($row['servizio']); ?></div>
            <a href="tel:<?php echo $tel; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['telefono']); ?></a>

            <?php if ($cur_step == 'view'): ?>
            <div class="actions-stack" style="margin-top:14px;">
                <a href="<?php echo $base_url; ?>&step=cancel_start" class="btn" style="background:white;border:1px solid #eee;color:var(--red);">
                    <i class="fas fa-ban"></i> Avvia Cancellazione
                </a>
            </div>

            <?php elseif ($cur_step == 'cancel_start'): ?>
            <div class="flow-step">
                <span class="step-title">Step 1 — Avvisa Cliente</span>
                <div class="actions-stack">
                    <a href="admin.php?track=wa_canc&id=<?php echo $row['id']; ?>&url=<?php echo $wa_canc; ?>&next_step=cancel_gcal<?php echo csrf_q(); ?>" target="_blank" class="btn btn-wa"
                       onclick="setTimeout(function(){ window.location.href='<?php echo $base_url; ?>&step=cancel_gcal'; },1000);">
                        <i class="fab fa-whatsapp"></i> 1. Avvisa su WhatsApp
                    </a>
                    <a href="<?php echo $base_url; ?>&step=cancel_gcal" class="btn btn-red">
                        <i class="fas fa-user-slash"></i> 1. Salta messaggio
                    </a>
                    <a href="<?php echo $base_url; ?>&step=view" style="color:#999;font-size:0.8rem;">Annulla</a>
                </div>
            </div>

            <?php elseif ($cur_step == 'cancel_gcal'): ?>
            <div class="flow-step">
                <span class="step-title">Step 2 — Rimuovi dal Calendario</span>
                <div class="actions-stack">
                    <a href="admin.php?track=gcal_del&id=<?php echo $row['id']; ?>&url=<?php echo $gcal_del; ?><?php echo csrf_q(); ?>" target="_blank" class="btn btn-blue">
                        <i class="far fa-calendar-minus"></i> 2. Apri Google Calendar
                    </a>
                    <a href="admin.php?action=rifiutato&id=<?php echo $row['id']; ?><?php echo csrf_q(); ?>" class="btn btn-red">
                        <i class="fas fa-check"></i> Conferma Cancellazione
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div><!-- /listView -->

    <!-- MODAL DETTAGLIO / SPOSTA / ANNULLA -->
    <div class="appt-modal-overlay" id="apptModal">
        <div class="appt-modal">
            <div class="appt-modal-head">
                <h3 id="amName">—</h3>
                <div class="am-sub" id="amWhen">—</div>
            </div>
            <div class="appt-modal-body">
                <div class="am-row"><i class="fas fa-cut"></i> <span id="amServ">—</span></div>
                <div class="am-row"><i class="fas fa-phone"></i> <a id="amTel" href="#" class="phone-link" style="margin:0;">—</a></div>

                <div class="appt-modal-actions">
                    <button type="button" class="btn btn-blue" id="amMoveBtn"><i class="far fa-calendar-alt"></i> Sposta</button>
                    <a href="#" class="btn btn-red" id="amCancelBtn"
                       onclick="return confirm('Annullare questo appuntamento? Lo slot tornerà disponibile online.');">
                        <i class="fas fa-ban"></i> Annulla Appuntamento
                    </a>

                    <form class="am-move-form" id="amMoveForm" method="POST" action="admin.php?current_tab=agenda">
                        <input type="hidden" name="action" value="move_booking">
                        <input type="hidden" name="id" id="amMoveId" value="">
                        <?php echo csrf_tag(); ?>
                        <span class="step-title">Nuova data e ora</span>
                        <div style="display:flex;gap:8px;">
                            <input type="date" name="data" id="amMoveDate" required style="flex:1;">
                            <select name="ora" id="amMoveTime" style="flex:1;">
                                <?php $s=strtotime("08:00"); $e=strtotime("19:30"); while($s<=$e){ echo "<option>".date("H:i",$s)."</option>"; $s=strtotime('+30 mins',$s); } ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-green" style="margin-top:10px;">
                            <i class="fas fa-check"></i> Conferma Spostamento
                        </button>
                    </form>

                    <button type="button" class="am-close" id="amClose">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

</div><!-- /tab agenda -->

<script>
var agendaData = <?php echo json_encode($prenotazioni_agenda_json, JSON_HEX_TAG); ?>;
var csrfTok    = <?php echo json_encode(csrf_token()); ?>;
var currentWeekStart = getMonday(new Date());

function getMonday(d) {
    var day  = d.getDay();
    var diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(new Date(d).setDate(diff));
}
function fmtDate(d) {
    var y = d.getFullYear();
    var m = String(d.getMonth()+1).padStart(2,'0');
    var dd = String(d.getDate()).padStart(2,'0');
    return y+'-'+m+'-'+dd;
}
function fmtShort(d) {
    return String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0');
}

function renderCalendar() {
    var grid  = document.getElementById('weekGrid');
    var label = document.getElementById('weekLabel');
    if (!grid) return;

    var weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    label.textContent = fmtShort(currentWeekStart) + ' – ' + fmtShort(weekEnd);

    var giorni = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
    var today  = fmtDate(new Date());
    grid.innerHTML = '';

    for (var i = 0; i < 7; i++) {
        var day = new Date(currentWeekStart);
        day.setDate(day.getDate() + i);
        var dateStr = fmtDate(day);
        var isToday = dateStr === today;

        var cell = document.createElement('div');
        cell.className = 'week-day' + (isToday ? ' today' : '');
        cell.innerHTML = '<div class="week-day-header">' + giorni[i] + '</div><div class="week-day-date">' + day.getDate() + '</div>';

        agendaData
            .filter(function (a) { return a.data_appuntamento === dateStr; })
            .forEach(function (a) {
                var badge = document.createElement('div');
                badge.className = 'appt-badge';
                badge.title     = a.nome + ' — ' + a.servizio;
                badge.textContent = a.ora_appuntamento.substring(0,5) + ' ' + a.nome.split(' ')[0];
                badge.addEventListener('click', function () { openApptModal(a); });
                cell.appendChild(badge);
            });

        // "+" per inserire un appuntamento in questo giorno (apre il form manuale prefillato)
        var addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'week-day-add';
        addBtn.textContent = '+';
        addBtn.title = 'Aggiungi prenotazione';
        addBtn.addEventListener('click', function () {
            window.location.href = 'admin.php?current_tab=tools&new_date=' + dateStr;
        });
        cell.appendChild(addBtn);

        grid.appendChild(cell);
    }
}

document.getElementById('prevWeek') && document.getElementById('prevWeek').addEventListener('click', function () {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    renderCalendar();
});
document.getElementById('nextWeek') && document.getElementById('nextWeek').addEventListener('click', function () {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    renderCalendar();
});

document.getElementById('listViewBtn') && document.getElementById('listViewBtn').addEventListener('click', function () {
    document.getElementById('listView').style.display   = 'block';
    document.getElementById('weekCalendar').style.display = 'none';
    this.classList.add('active');
    document.getElementById('calViewBtn').classList.remove('active');
});
document.getElementById('calViewBtn') && document.getElementById('calViewBtn').addEventListener('click', function () {
    document.getElementById('listView').style.display   = 'none';
    document.getElementById('weekCalendar').style.display = 'block';
    this.classList.add('active');
    document.getElementById('listViewBtn').classList.remove('active');
    renderCalendar();
});

/* ===== Modal dettaglio appuntamento ===== */
function openApptModal(a) {
    var ora = a.ora_appuntamento.substring(0,5);
    var tel = (a.telefono || '').replace(/[^0-9+]/g, '');
    document.getElementById('amName').textContent = a.nome;
    document.getElementById('amWhen').textContent = a.data_appuntamento + ' • ' + ora;
    document.getElementById('amServ').textContent = a.servizio || '—';
    var telEl = document.getElementById('amTel');
    telEl.textContent = a.telefono || '—';
    telEl.href = tel ? ('tel:' + tel) : '#';
    document.getElementById('amCancelBtn').href =
        'admin.php?action=rifiutato&id=' + a.id + '&current_tab=agenda&t=' + encodeURIComponent(csrfTok);
    document.getElementById('amMoveId').value   = a.id;
    document.getElementById('amMoveDate').value = a.data_appuntamento;
    document.getElementById('amMoveTime').value = ora;
    document.getElementById('amMoveForm').classList.remove('open');
    document.getElementById('apptModal').classList.add('open');
}
function closeApptModal() { document.getElementById('apptModal').classList.remove('open'); }

document.getElementById('amClose').addEventListener('click', closeApptModal);
document.getElementById('apptModal').addEventListener('click', function (e) {
    if (e.target === this) closeApptModal();
});
document.getElementById('amMoveBtn').addEventListener('click', function () {
    document.getElementById('amMoveForm').classList.toggle('open');
});
</script>
