    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-search-location"></i> <?= sanitize(getSetting('site_name', SITE_NAME)) ?></h3>
                    <p><?= sanitize(getSetting('site_description', 'منصة إلكترونية للإبلاغ عن المفقودات والمقتنيات مع نظام ذكاء اصطناعي للمطابقة التلقائية.')) ?></p>
                </div>
                <div class="footer-col">
                    <h4>روابط سريعة</h4>
                    <a href="<?= SITE_URL ?>">الرئيسية</a>
                    <a href="<?= SITE_URL ?>/reports/search.php">بحث</a>
                    <a href="<?= SITE_URL ?>/reports/create.php">بلاغ جديد</a>
                </div>
                <div class="footer-col">
                    <h4>الفئات</h4>
                    <a href="<?= SITE_URL ?>/reports/search.php?category=1">الأشخاص</a>
                    <a href="<?= SITE_URL ?>/reports/search.php?category=2">الوثائق</a>
                    <a href="<?= SITE_URL ?>/reports/search.php?category=3">الهواتف</a>
                    <a href="<?= SITE_URL ?>/reports/search.php?category=4">الإلكترونيات</a>
                </div>
                <div class="footer-col">
                    <h4>تواصل معنا</h4>
                    <p><i class="fas fa-envelope"></i> <?= sanitize(getSetting('contact_email', 'info@mofaqdat.com')) ?></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= sanitize(getSetting('site_name', SITE_NAME)) ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_URL ?>/assets/js/app.js"></script>
    <script>
    function toggleDark() {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkIcon();
    }
    function updateDarkIcon() {
        const icon = document.getElementById('darkIcon');
        const label = document.getElementById('darkLabel');
        if (!icon) return;
        if (document.body.classList.contains('dark')) {
            icon.className = 'fas fa-sun';
            if (label) label.textContent = 'نهاري';
        } else {
            icon.className = 'fas fa-moon';
            if (label) label.textContent = 'ليلي';
        }
    }
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
    }
    updateDarkIcon();
    </script>
</body>
</html>
