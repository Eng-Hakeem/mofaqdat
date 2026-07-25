<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireLogin();
if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard_stats':
        $stats = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM reports WHERE status = 'active') as active_reports,
                (SELECT COUNT(*) FROM reports WHERE report_type = 'lost' AND status = 'active') as lost_count,
                (SELECT COUNT(*) FROM reports WHERE report_type = 'found' AND status = 'active') as found_count,
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM matches WHERE status = 'suggested') as pending_matches,
                (SELECT COUNT(*) FROM verification_requests WHERE status = 'pending') as pending_verifications
        ")->fetch();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    case 'update_report_status':
        $id = intval($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        if (in_array($status, ['active', 'matched', 'resolved', 'expired'])) {
            $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute([$status, $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'حالة غير صالحة']);
        }
        break;

    case 'delete_report':
        $id = intval($input['id'] ?? 0);
        $pdo->prepare("UPDATE reports SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_user':
        $id = intval($input['id'] ?? 0);
        $main_admin_id = 1;
        if ($id != $_SESSION['user_id'] && $id != $main_admin_id) {
            $pdo->prepare("UPDATE users SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND role != 'admin' OR (role = 'admin' AND id != ?)")->execute([$id, $main_admin_id]);
            $pdo->prepare("UPDATE reports SET is_deleted = 1, deleted_at = NOW() WHERE user_id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'لا يمكن حذف هذا المستخدم']);
        }
        break;

    case 'toggle_user_verify':
        $id = intval($input['id'] ?? 0);
        $pdo->prepare("UPDATE users SET id_verified = NOT id_verified WHERE id = ? AND role != 'admin'")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);
}
