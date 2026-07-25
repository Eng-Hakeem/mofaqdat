<?php
if (getSetting('maintenance_mode', '0') === '1' && !isAdmin()) {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>صيانة الموقع | <?= SITE_NAME ?></title>
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fontawesome.min.css">
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
        <style>
            .maintenance-page { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); color:white; text-align:center; padding:20px; }
            .maintenance-box { max-width:500px; }
            .maintenance-box .icon { font-size:4rem; color:#f59e0b; margin-bottom:20px; }
            .maintenance-box h1 { font-size:1.8rem; margin-bottom:12px; }
            .maintenance-box p { color:#94a3b8; font-size:1rem; line-height:1.8; margin-bottom:8px; }
            .maintenance-box .email { color:#60a5fa; font-weight:600; }
        </style>
    </head>
    <body>
        <div class="maintenance-page">
            <div class="maintenance-box">
                <div class="icon"><i class="fas fa-tools"></i></div>
                <h1>الموقع حالياً تحت الصيانة</h1>
                <p>نعمل على تحسين الخدمات وتقديم تجربة أفضل لكم.</p>
                <p>نعتذر عن أي إزعاج، ونعدكم بالعودة قريباً.</p>
                <?php if (getSetting('contact_email')): ?>
                    <p style="margin-top:20px;">للتواصل: <span class="email"><?= sanitize(getSetting('contact_email')) ?></span></p>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= sanitize(getSetting('site_name', SITE_NAME)) ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fontawesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php
    $header_unread = 0;
    if (isLoggedIn() && isset($pdo)) {
        try {
            $stmt_check = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'matched_report_id'");
            if ($stmt_check->fetch()) {
                $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                $stmt_unread->execute([$_SESSION['user_id']]);
                $header_unread = intval($stmt_unread->fetchColumn());
            }
        } catch (Exception $e) {
            $header_unread = 0;
        }
    }

    $current_uri = $_SERVER['REQUEST_URI'];
    $active_home = (strpos($current_uri, '/mofaqdat/') !== false && strpos($current_uri, '/mofaqdat/') === strrpos($current_uri, '/')) || rtrim($current_uri, '/') === '/mofaqdat' || rtrim($current_uri, '/') === '/mofaqdat/' ? 'active' : '';
    $active_search = strpos($current_uri, '/reports/search') !== false ? 'active' : '';
    $active_create = strpos($current_uri, '/reports/create') !== false ? 'active' : '';
    $active_myreports = strpos($current_uri, '/reports/my-reports') !== false ? 'active' : '';
    $active_notifications = strpos($current_uri, '/notifications') !== false ? 'active' : '';
    ?>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="<?= SITE_URL ?>" class="logo">
                <i class="fas fa-search-location"></i>
                <span><?= SITE_NAME ?></span>
            </a>

            <div class="nav-links">
                <a href="<?= SITE_URL ?>" class="nav-link <?= $active_home ?>"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="<?= SITE_URL ?>/reports/search.php" class="nav-link <?= $active_search ?>"><i class="fas fa-search"></i> بحث</a>

                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/reports/create.php" class="nav-link <?= $active_create ?>"><i class="fas fa-plus"></i> بلاغ جديد</a>
                    <a href="<?= SITE_URL ?>/reports/my-reports.php" class="nav-link <?= $active_myreports ?>"><i class="fas fa-list"></i> بلاغاتي</a>
                    <a href="<?= SITE_URL ?>/notifications/" class="nav-link <?= $active_notifications ?>" style="position:relative;display:inline-flex;align-items:center;">
                        <i class="fas fa-bell"></i> إشعارات
                        <?php if ($header_unread > 0): ?>
                            <span class="notif-count-badge"><?= $header_unread > 99 ? '99+' : $header_unread ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= SITE_URL ?>/admin/" class="nav-link"><i class="fas fa-cog"></i> لوحة التحكم</a>
                    <?php endif; ?>
                    <button class="dark-toggle" onclick="toggleDark()" title="تبديل الثيم الليلي">
                        <i class="fas fa-moon" id="darkIcon"></i>
                        <span id="darkLabel" style="font-size:0.8rem;font-weight:600;margin-right:2px;">ليلي</span>
                    </button>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <i class="fas fa-user-circle"></i> <?= sanitize($_SESSION['full_name']) ?>
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?= SITE_URL ?>/profile/"><i class="fas fa-user-circle"></i> الملف الشخصي</a>
                            <?php if (isAdmin()): ?>
                                <a href="<?= SITE_URL ?>/admin/"><i class="fas fa-cog"></i> لوحة التحكم</a>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline btn-sm">تسجيل الدخول</a>
                    <a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">التسجيل</a>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="mobile-nav" id="mobileNav">
        <a href="<?= SITE_URL ?>" class="<?= $active_home ?>"><i class="fas fa-home"></i> الرئيسية</a>
        <a href="<?= SITE_URL ?>/reports/search.php" class="<?= $active_search ?>"><i class="fas fa-search"></i> بحث</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/reports/create.php" class="<?= $active_create ?>"><i class="fas fa-plus"></i> بلاغ جديد</a>
            <a href="<?= SITE_URL ?>/reports/my-reports.php" class="<?= $active_myreports ?>"><i class="fas fa-list"></i> بلاغاتي</a>
            <a href="<?= SITE_URL ?>/notifications/" class="<?= $active_notifications ?>">
                <i class="fas fa-bell"></i> إشعاراتي
                <?php if ($header_unread > 0): ?>
                    <span class="mobile-badge"><?= $header_unread ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= SITE_URL ?>/profile/"><i class="fas fa-user-circle"></i> الملف الشخصي</a>
            <?php if (isAdmin()): ?>
                <a href="<?= SITE_URL ?>/admin/"><i class="fas fa-cog"></i> لوحة التحكم</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a>
            <a href="<?= SITE_URL ?>/auth/register.php"><i class="fas fa-user-plus"></i> التسجيل</a>
        <?php endif; ?>
        <a href="javascript:void(0)" onclick="toggleDark()" style="border-top:1px solid #e2e8f0;margin-top:4px;"><i class="fas fa-moon"></i> تبديل الثيم الليلي</a>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> container">
            <?= sanitize($flash['message']) ?>
        </div>
    <?php endif; ?>

    <main class="main-content">
