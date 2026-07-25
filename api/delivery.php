<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/email.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'request_delivery':
        requireLogin();
        $report_id = intval($input['report_id'] ?? 0);

        $report = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $report->execute([$report_id, $_SESSION['user_id']]);
        $report = $report->fetch();

        if (!$report) {
            echo json_encode(['success' => false, 'error' => 'البلاغ غير موجود']);
            break;
        }

        $match_stmt = $pdo->prepare("
            SELECT m.*, r.user_id as lost_owner_id, r.title as lost_title,
                   fr.user_id as finder_id, fr.title as found_title, fr.id as found_id
            FROM matches m
            JOIN reports r ON m.lost_report_id = r.id
            JOIN reports fr ON m.found_report_id = fr.id
            WHERE m.found_report_id = ? AND m.status IN ('suggested', 'confirmed')
            ORDER BY m.match_score DESC LIMIT 1
        ");
        $match_stmt->execute([$report_id]);
        $match = $match_stmt->fetch();

        if (!$match) {
            echo json_encode(['success' => false, 'error' => 'لا يوجد مفقود مطابق']);
            break;
        }

        $pdo->prepare("UPDATE reports SET status = 'matched' WHERE id = ?")->execute([$report_id]);

        createNotification(
            $match['lost_owner_id'],
            'جاهزية التسليم - تأكيد الاستلام 📦',
            "المبلّغ \"$report[title]\" على استعداد لتسليم \"$match[lost_title]\". يرجى تأكيد ما إذا كنت قد استلمت مفقودك.",
            'match',
            $match['lost_report_id'],
            $report_id,
            $match['id']
        );

        sendEmail(
            $match['lost_owner_id'],
            'جاهزية التسليم - تأكيد الاستلام',
            "مرحباً،\n\nالمبلّغ أبلغ عن جاهزيته لتسليم \"$match[lost_title]\".\n\nيرجى تسجيل الدخول والذهاب لتأكيد الاستلام.\n\n" . SITE_URL . "/reports/delivery.php?id={$match['lost_report_id']}",
            'delivery_request'
        );

        echo json_encode(['success' => true, 'message' => 'تم إشعار صاحب المفقود']);
        break;

    case 'confirm_delivery':
        requireLogin();
        $match_id = intval($input['match_id'] ?? 0);

        $match_stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $match_stmt->execute([$match_id]);
        $match = $match_stmt->fetch();

        if (!$match) {
            echo json_encode(['success' => false, 'error' => 'المطابقة غير موجودة']);
            break;
        }

        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$match['lost_report_id']]);
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$match['found_report_id']]);
        $pdo->prepare("UPDATE matches SET status = 'confirmed' WHERE id = ?")->execute([$match_id]);

        $lost = $pdo->prepare("SELECT * FROM reports WHERE id = ?")->execute([$match['lost_report_id']]);

        $finder = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
        $finder->execute([$match['found_report_id']]);
        $finder_id = $finder->fetchColumn();

        createNotification(
            $finder_id,
            'تم تأكيد الاستلام! 🎉',
            'أكد صاحب المفقود استلامه. شكراً لك!',
            'status',
            $match['lost_report_id'],
            $match['found_report_id'],
            $match_id
        );

        sendEmail(
            $finder_id,
            'تأكيد استلام المفقود',
            'أكد صاحب المفقود استلامه بنجاح. شكراً لك على مساعدتك!',
            'delivery_confirmed'
        );

        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);
}
