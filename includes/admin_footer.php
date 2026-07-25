            </div>
        </main>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
    function toggleDark() {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark);
        updateAdminDarkIcon();
    }
    function updateAdminDarkIcon() {
        const icon = document.getElementById('adminDarkIcon');
        if (!icon) return;
        if (document.body.classList.contains('dark')) {
            icon.className = 'fas fa-sun';
        } else {
            icon.className = 'fas fa-moon';
        }
    }
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
    }
    updateAdminDarkIcon();
    </script>
</body>
</html>
