<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/notifications.php';

echo "<h2>اختبار نظام الإشعارات</h2>";

if (!isLoggedIn()) {
    echo "<p style='color:red'>يجب تسجيل الدخول أولاً</p>";
    echo "<a href='auth/login.php'>تسجيل الدخول</a>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p>المستخدم الحالي: {$_SESSION['full_name']} (ID: $user_id)</p>";

// التحقق من جدول الإشعارات
try {
    $check = $pdo->query("DESCRIBE notifications")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color:green'>✓ جدول الإشعارات موجود</p>";
    echo "<p>الأعمدة: " . implode(', ', $check) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ خطأ في جدول الإشعارات: " . $e->getMessage() . "</p>";
    echo "<p>شغّل ملف <code>database/update_notifications.sql</code> في phpMyAdmin</p>";
    exit;
}

// إنشاء إشعار تجريبي
$result = createNotification(
    $user_id,
    'اختبار نظام الإشعارات',
    'هذا إشعار تجريبي للتأكد من أن النظام يعمل بشكل صحيح!',
    'system'
);

if ($result) {
    echo "<p style='color:green'>✓ تم إنشاء الإشعار بنجاح (ID: $result)</p>";
} else {
    echo "<p style='color:red'>✗ فشل إنشاء الإشعار</p>";
}

// عرض الإشعارات
$notifications = getNotifications($user_id, 10);
$unread = getUnreadCount($user_id);

echo "<h3>الإشعارات الحالية ($unread غير مقروء):</h3>";
echo "<ul>";
foreach ($notifications as $n) {
    $read_status = $n['is_read'] ? '✓ مقروء' : '● غير مقروء';
    echo "<li>[" . $n['type'] . "] " . $n['title'] . " - $read_status</li>";
}
echo "</ul>";

echo "<br><a href='notifications/'>عرض صفحة الإشعارات</a>";
echo " | <a href='index.php'>العودة للرئيسية</a>";
