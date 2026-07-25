<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'delete':
        requireLogin();
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reports SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    case 'restore':
        requireLogin();
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reports SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    case 'resolve':
        requireLogin();
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->execute([$id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            require_once __DIR__ . '/../includes/notifications.php';

            $report = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
            $report->execute([$id]);
            $report = $report->fetch();

            if ($report) {
                $pdo->prepare("UPDATE matches SET status = 'confirmed' WHERE (lost_report_id = ? OR found_report_id = ?) AND status = 'suggested'")
                    ->execute([$id, $id]);

                $notif_stmt = $pdo->prepare("
                    SELECT DISTINCT r.user_id FROM matches m
                    JOIN reports r ON (m.lost_report_id = r.id OR m.found_report_id = r.id)
                    WHERE (m.lost_report_id = ? OR m.found_report_id = ?) AND r.user_id != ?
                ");
                $notif_stmt->execute([$id, $id, $report['user_id']]);
                $other_users = $notif_stmt->fetchAll();

                foreach ($other_users as $other) {
                    createNotification(
                        $other['user_id'],
                        'تم تأكيد استلام المفقود',
                        "أكد صاحب \"$report[title]\" استلام المفقود بنجاح!",
                        'status',
                        $id
                    );
                }
            }
        }

        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    case 'get_matches':
        requireLogin();
        $id = intval($input['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT m.*, r.title, r.image, r.report_type, r.color, r.brand
            FROM matches m
            JOIN reports r ON (m.found_report_id = r.id OR m.lost_report_id = r.id) AND r.id != ? AND r.is_deleted = 0
            WHERE m.lost_report_id = ? OR m.found_report_id = ?
            ORDER BY m.match_score DESC
        ");
        $stmt->execute([$id, $id, $id]);
        echo json_encode(['success' => true, 'matches' => $stmt->fetchAll()]);
        break;

    case 'get_unread_count':
        requireLogin();
        require_once __DIR__ . '/../includes/notifications.php';
        echo json_encode(['success' => true, 'count' => getUnreadCount($_SESSION['user_id'])]);
        break;

    case 'mark_notification_read':
        requireLogin();
        require_once __DIR__ . '/../includes/notifications.php';
        $nid = intval($input['notification_id'] ?? 0);
        echo json_encode(['success' => markAsRead($nid, $_SESSION['user_id'])]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);
}
