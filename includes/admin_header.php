<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?>لوحة التحكم | <?= sanitize(getSetting('site_name', SITE_NAME)) ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fontawesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <a href="<?= SITE_URL ?>/admin/">
                    <i class="fas fa-search-location"></i>
                    <span><?= sanitize(getSetting('site_name', SITE_NAME)) ?></span>
                </a>
                <span class="admin-badge">مدير</span>
            </div>

            <?php
            $current = basename($_SERVER['PHP_SELF']);
            ?>
            <nav class="admin-nav">
                <a href="<?= SITE_URL ?>/admin/" class="admin-nav-item <?= $current === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> الداشبورد
                </a>
                <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-item <?= $current === 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> البلاغات
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-item <?= $current === 'users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> المستخدمين
                </a>
                <a href="<?= SITE_URL ?>/admin/matches.php" class="admin-nav-item <?= $current === 'matches.php' ? 'active' : '' ?>">
                    <i class="fas fa-handshake"></i> المطابقات
                </a>
                <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-item <?= $current === 'categories.php' ? 'active' : '' ?>">
                    <i class="fas fa-folder"></i> الفئات
                </a>
                <a href="<?= SITE_URL ?>/admin/verify.php" class="admin-nav-item <?= $current === 'verify.php' ? 'active' : '' ?>">
                    <i class="fas fa-id-card"></i> التحقق من الهوية
                </a>
                <a href="<?= SITE_URL ?>/admin/email_log.php" class="admin-nav-item <?= $current === 'email_log.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope-open-text"></i> سجل البريد
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <a href="<?= SITE_URL ?>/admin/settings.php" class="admin-nav-item <?= $current === 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> الإعدادات
                </a>
                <a href="<?= SITE_URL ?>" class="admin-nav-item">
                    <i class="fas fa-external-link-alt"></i> زيارة الموقع
                </a>
                <a href="<?= SITE_URL ?>/auth/logout.php" class="admin-nav-item">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <button class="admin-sidebar-toggle" onclick="document.querySelector('.admin-layout').classList.toggle('sidebar-collapsed')">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 class="admin-page-title"><?= $pageTitle ?? '' ?></h2>
                <div class="admin-topbar-left">
                    <button class="admin-dark-toggle" onclick="toggleDark()" title="تبديل الثيم">
                        <i class="fas fa-moon" id="adminDarkIcon"></i>
                    </button>
                    <a href="<?= SITE_URL ?>/profile/" class="admin-topbar-link" title="الملف الشخصي">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <span><?= sanitize($_SESSION['full_name']) ?></span>
                </div>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>

            <div class="admin-content">
