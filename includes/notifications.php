<?php
require_once __DIR__ . '/email.php';

function createNotification($user_id, $title, $message, $type = 'system', $report_id = null, $matched_report_id = null, $match_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, report_id, matched_report_id, match_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title, $message, $type, $report_id, $matched_report_id, $match_id]);
        $notif_id = $pdo->lastInsertId();

        if ($notif_id && $type !== 'system') {
            sendEmail($user_id, $title, $message, $type);
        }

        return $notif_id;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

function getUnreadCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function getNotifications($user_id, $limit = 20, $offset = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, r.title as report_title, r.report_type, r.image as report_image,
                   mr.title as matched_report_title, mr.report_type as matched_report_type
            FROM notifications n
            LEFT JOIN reports r ON n.report_id = r.id
            LEFT JOIN reports mr ON n.matched_report_id = mr.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function markAsRead($notification_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    return $stmt->rowCount() > 0;
}

function markAllAsRead($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->rowCount();
}

function notifyMatchFound($match_id, $lost_report, $found_report) {
    $lost_owner_id = $lost_report['user_id'];
    $found_owner_id = $found_report['user_id'];
    $lost_title = $lost_report['title'];
    $found_title = $found_report['title'];
    $lost_id = $lost_report['id'];
    $found_id = $found_report['id'];

    createNotification(
        $lost_owner_id,
        'تم العثور على شيء يشبه مفقودك!',
        "وجدنا بلاغاً يتطابق مع مفقودك \"$lost_title\". تم كشف بيانات التواصل مع المبلّغ.",
        'match',
        $lost_id,
        $found_id,
        $match_id
    );

    createNotification(
        $found_owner_id,
        'الشخص المفقود تم العثور عليه!',
        "وجدنا بلاغاً يتطابق مع \"$found_title\" الذي أبلغت عنه. تم كشف بيانات التواصل مع صاحب المفقود.",
        'match',
        $found_id,
        $lost_id,
        $match_id
    );
}

function notifyNewFoundSimilar($user_id, $lost_report_id, $found_report) {
    createNotification(
        $user_id,
        'تم نشر بلاغ عثور يطابق مفقودك!',
        "نشر شخص بلاغاً عن عثور على \"$found_report[title]\" يبدو مطابقاً لمفقودك. تحقق من التفاصيل وتواصل مع المبلّغ.",
        'new_found',
        $lost_report_id,
        $found_report['id'],
        null
    );
}

function getNotificationIcon($type) {
    $icons = [
        'match' => 'fa-handshake',
        'new_found' => 'fa-search-location',
        'status' => 'fa-info-circle',
        'system' => 'fa-bell'
    ];
    return $icons[$type] ?? 'fa-bell';
}

function getNotificationColor($type) {
    $colors = [
        'match' => 'success',
        'new_found' => 'info',
        'status' => 'warning',
        'system' => 'secondary'
    ];
    return $colors[$type] ?? 'secondary';
}
