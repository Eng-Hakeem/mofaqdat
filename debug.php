<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار الهيدر</title>
    <link rel="stylesheet" href="/mofaqdat/assets/css/fontawesome.min.css">
    <style>
        body { font-family: Arial; padding: 20px; direction: rtl; }
        .test-box { border: 2px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .ok { border-color: green; background: #eaffea; }
        .fail { border-color: red; background: #ffeaea; }
        .info { border-color: blue; background: #eaffff; }
        nav { background: #fff; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; }
        nav a { text-decoration: none; color: #333; padding: 8px 12px; }
    </style>
</head>
<body>
    <h1>تشخيص مشكلة جرس الإشعارات</h1>

    <div class="test-box info">
        <h3>1. هل أنت مسجّل الدخول؟</h3>
        <p>isLoggedIn(): <?= isLoggedIn() ? '<span style="color:green">نعم ✓</span>' : '<span style="color:red">لا ✗</span>' ?></p>
        <p>$_SESSION['user_id']: <?= $_SESSION['user_id'] ?? 'غير موجود' ?></p>
        <p>$_SESSION['full_name']: <?= $_SESSION['full_name'] ?? 'غير موجود' ?></p>
    </div>

    <div class="test-box info">
        <h3>2. هل يوجد اتصال بقاعدة البيانات؟</h3>
        <p>$pdo: <?= isset($pdo) ? '<span style="color:green">موجود ✓</span>' : '<span style="color:red">غير موجود ✗</span>' ?></p>
    </div>

    <?php if (isLoggedIn() && isset($pdo)): ?>
    <div class="test-box info">
        <h3>3. هل جدول الإشعارات جاهز؟</h3>
        <?php
        try {
            $check = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'matched_report_id'");
            $has_col = $check->fetch();
            ?>
            <p>عمود matched_report_id: <?= $has_col ? '<span style="color:green">موجود ✓</span>' : '<span style="color:red">غير موجود ✗</span>' ?></p>
            <?php
        } catch (Exception $e) { ?>
            <p style="color:red">خطأ: <?= $e->getMessage() ?></p>
        <?php } ?>
    </div>

    <div class="test-box info">
        <h3>4. عدد الإشعارات غير المقروءة:</h3>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$_SESSION['user_id']]);
            $count = $stmt->fetchColumn();
            ?>
            <p>العدد: <strong><?= $count ?></strong></p>
        <?php } catch (Exception $e) { ?>
            <p style="color:red">خطأ: <?= $e->getMessage() ?></p>
        <?php } ?>
    </div>
    <?php endif; ?>

    <h2>معاينة الهيدر:</h2>
    <nav>
        <a href="#"><i class="fas fa-home"></i> الرئيسية</a>
        <a href="#"><i class="fas fa-search"></i> بحث</a>
        <?php if (isLoggedIn()): ?>
            <a href="#"><i class="fas fa-plus"></i> بلاغ جديد</a>
            <a href="#"><i class="fas fa-list"></i> بلاغاتي</a>
            <a href="/mofaqdat/notifications/" style="position:relative;display:inline-flex;align-items:center;padding:8px 14px;background:#f0f0f0;border-radius:8px;text-decoration:none;color:#333;">
                <i class="fas fa-bell" style="font-size:1.2rem;"></i>
                <span style="margin-right:6px;">إشعاراتي</span>
                <?php if (isset($count) && $count > 0): ?>
                    <span style="position:absolute;top:-5px;left:-5px;background:red;color:#fff;font-size:0.7rem;font-weight:bold;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= $count ?></span>
                <?php endif; ?>
            </a>
            <a href="#" style="background:#2563eb;color:#fff;padding:8px 14px;border-radius:8px;"><?= $_SESSION['full_name'] ?? '' ?></a>
        <?php else: ?>
            <a href="/mofaqdat/auth/login.php" style="border:2px solid #2563eb;color:#2563eb;padding:8px 14px;border-radius:8px;">تسجيل الدخول</a>
        <?php endif; ?>
    </nav>

    <br>
    <a href="/mofaqdat/">العودة للرئيسية</a> | <a href="/mofaqdat/notifications/">صفحة الإشعارات</a>
</body>
</html>
