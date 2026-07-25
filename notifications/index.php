<?php
$pageTitle = 'إشعاراتي';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireLogin();

if (isset($_GET['mark_read'])) {
    markAsRead(intval($_GET['mark_read']), $_SESSION['user_id']);
    header('Location: ' . SITE_URL . '/notifications/');
    exit;
}

if (isset($_GET['mark_all'])) {
    markAllAsRead($_SESSION['user_id']);
    header('Location: ' . SITE_URL . '/notifications/');
    exit;
}

$notifications = [];
$unread_count = 0;
$db_error = null;

try {
    $notifications = getNotifications($_SESSION['user_id'], 50);
    $unread_count = getUnreadCount($_SESSION['user_id']);
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-bell"></i> إشعاراتي</h1>
            <?php if ($unread_count > 0): ?>
                <p class="text-primary"><?= $unread_count ?> إشعار غير مقروء</p>
            <?php endif; ?>
        </div>
        <?php if ($unread_count > 0): ?>
            <a href="?mark_all=1" class="btn btn-outline btn-sm">
                <i class="fas fa-check-double"></i> تحديد الكل كمقروء
            </a>
        <?php endif; ?>
    </div>

    <?php if ($db_error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>خطأ في قاعدة البيانات:</strong> <?= sanitize($db_error) ?>
            <br><br>
            <strong>الحل:</strong> شغّل ملف التحديث في phpMyAdmin:<br>
            <code>http://localhost/mofaqdat/database/update_notifications.sql</code>
        </div>
    <?php endif; ?>

    <?php if (empty($notifications) && !$db_error): ?>
        <div class="no-results">
            <i class="fas fa-bell-slash"></i>
            <h3>لا توجد إشعارات بعد</h3>
            <p>ستظهر هنا الإشعارات عند وجود مطابقات أو تحديثات على بلاغاتك</p>
            <a href="<?= SITE_URL ?>/reports/create.php" class="btn btn-primary" style="margin-top: 16px">
                <i class="fas fa-plus"></i> أنشئ بلاغاً جديداً
            </a>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                    <div class="notification-icon <?= getNotificationColor($notif['type']) ?>">
                        <i class="fas <?= getNotificationIcon($notif['type']) ?>"></i>
                    </div>

                    <div class="notification-content">
                        <div class="notification-header">
                            <h4><?= sanitize($notif['title']) ?></h4>
                            <span class="notification-time"><?= timeAgo($notif['created_at']) ?></span>
                        </div>
                        <p class="notification-message"><?= sanitize($notif['message']) ?></p>

                        <?php if ($notif['type'] === 'match' || $notif['type'] === 'new_found'): ?>
                            <div class="notification-report-preview">
                                <?php if (!empty($notif['report_image'])): ?>
                                    <img src="<?= SITE_URL ?>/uploads/<?= $notif['report_image'] ?>" alt="" class="notif-thumb">
                                <?php else: ?>
                                    <div class="notif-thumb-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <?php if (!empty($notif['report_type'])): ?>
                                        <span class="badge badge-<?= $notif['report_type'] === 'lost' ? 'danger' : 'success' ?>">
                                            <?= getReportTypeText($notif['report_type']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($notif['report_id'])): ?>
                                        <a href="<?= SITE_URL ?>/reports/view.php?id=<?= $notif['report_id'] ?>" class="notif-report-title">
                                            <?= sanitize($notif['report_title'] ?? 'بلاغ') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="notification-actions">
                                <?php if (!empty($notif['report_id'])): ?>
                                    <a href="<?= SITE_URL ?>/reports/view.php?id=<?= $notif['report_id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> عرض بلاغك
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($notif['matched_report_id'])): ?>
                                    <a href="<?= SITE_URL ?>/reports/view.php?id=<?= $notif['matched_report_id'] ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-handshake"></i> عرض المطابق
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($notif['report_id']) && strpos($notif['title'] ?? '', 'تسليم') !== false): ?>
                                    <a href="<?= SITE_URL ?>/reports/delivery.php?id=<?= $notif['report_id'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-check-double"></i> تأكيد الاستلام
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($notif['report_id'])): ?>
                                    <a href="<?= SITE_URL ?>/reports/matches.php?id=<?= $notif['report_id'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-address-book"></i> بيانات التواصل
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$notif['is_read']): ?>
                        <a href="?mark_read=<?= $notif['id'] ?>" class="notification-read-btn" title="تحديد كمقروء">
                            <i class="fas fa-check"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
