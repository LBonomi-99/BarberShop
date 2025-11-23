<a href="admin.php?current_tab=tools&show_manual=1" class="fab"><i class="fas fa-plus"></i></a>

    <script>
        function enableStep3(id) {
            setTimeout(function() {
                var btn = document.getElementById('btn-step3-' + id);
                if(btn) {
                    btn.classList.remove('btn-disabled');
                    btn.innerHTML = '<i class="fab fa-whatsapp"></i> 2. Conferma WhatsApp & Salva';
                }
            }, 1000);
        }

        function reloadAfterDelay() {
            setTimeout(function() {
                window.location.href = "admin.php?current_tab=inbox";
            }, 2000);
        }

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('show_manual')) {
            document.getElementById('manualForm').style.display = 'block';
        }

        function toggleInputs() {
            const val = document.getElementById('blockType').value;
            const timeGroup = document.getElementById('timeSelectGroup');
            const endGroup = document.getElementById('endDateGroup');
            
            if (val === 'single') {
                timeGroup.style.display = 'flex';
                endGroup.style.display = 'none';
            } else if (val === 'full_day') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'none';
            } else if (val === 'range') {
                timeGroup.style.display = 'none';
                endGroup.style.display = 'flex';
            }
        }
    </script>
</body>
</html>