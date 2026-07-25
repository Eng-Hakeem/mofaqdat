<?php
$pageTitle = 'تأكيد استلام المفقود';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/email.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect(SITE_URL);

$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$id, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('error', 'البلاغ غير موجود');
    redirect(SITE_URL);
}

if ($report['report_type'] !== 'lost') {
    setFlash('error', 'هذه الصفحة لتأكيد استلام المفقود فقط');
    redirect(SITE_URL);
}

$delivery_match = $pdo->prepare("
    SELECT m.*, r.title as found_title, r.user_id as finder_id, u.full_name as finder_name
    FROM matches m
    JOIN reports r ON m.found_report_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE (m.lost_report_id = ? OR m.found_report_id = ?)
    AND m.status IN ('suggested', 'confirmed')
    ORDER BY m.match_score DESC LIMIT 1
");
$delivery_match->execute([$id, $id]);
$match = $delivery_match->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm_received' && $match) {
        $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$id]);
        $found_report = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
        $found_report->execute([$match['found_report_id']]);
        $found_report = $found_report->fetch();
        if ($found_report) {
            $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$found_report['id']]);
        }

        $pdo->prepare("UPDATE matches SET status = 'confirmed' WHERE id = ?")->execute([$match['id']]);

        createNotification(
            $match['finder_id'],
            'تم تأكيد الاستلام! 🎉',
            "أكد صاحب المفقود \"$report[title]\" استلام مفقوده. شكراً لك!",
            'status',
            $id,
            $match['found_report_id'],
            $match['id']
        );

        sendEmail(
            $match['finder_id'],
            'تأكيد استلام المفقود 🎉',
            "أكد صاحب المفقود \"$report[title]\" استلام مفقوده بنجاح.\n\nشكراً لك على مساعدتك!",
            'delivery_confirmed'
        );

        setFlash('success', 'تم تأكيد الاستلام بنجاح! شكراً للمبلّغ.');
        redirect(SITE_URL . '/reports/view.php?id=' . $id);

    } elseif ($action === 'not_received' && $match) {
        createNotification(
            $match['finder_id'],
            'لم يتم الاستلام بعد',
            "أبلغ صاحب المفقود \"$report[title]\" أنه لم يستلم المفقود بعد. يرجى التواصل لتحديد موعد التسليم.",
            'status',
            $id,
            $match['found_report_id'],
            $match['id']
        );

        sendEmail(
            $match['finder_id'],
            'لم يتم الاستلام بعد',
            "أبلغ صاحب المفقود \"$report[title]\" أنه لم يستلم المفقود بعد.\n\nيرجى التواصل معه لتحديد موعد التسليم.",
            'delivery_pending'
        );

        setFlash('info', 'تم إشعار المبلّغ بأنه لم يتم الاستلام بعد.');
        redirect(SITE_URL . '/reports/view.php?id=' . $id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="report-form" style="max-width:600px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:24px;">
            <i class="fas fa-hand-holding-heart" style="font-size:3rem;color:var(--success);"></i>
            <h2 style="margin-top:10px;">تأكيد استلام المفقود</h2>
            <p style="color:var(--gray);">بلاغ: <strong><?= sanitize($report['title']) ?></strong></p>
        </div>

        <?php if ($match): ?>
            <div class="delivery-box" style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px;margin-bottom:20px;">
                <p><strong>المبلّغ:</strong> <?= sanitize($match['finder_name']) ?></p>
                <p><strong>البلاغ المعثور:</strong> <?= sanitize($match['found_title']) ?></p>
                <p><strong>نسبة التطابق:</strong> <?= $match['match_score'] ?>%</p>
            </div>

            <div style="text-align:center;">
                <h3>هل استلمت مفقودك؟</h3>
                <p style="color:var(--gray);margin-bottom:20px;">اختر الإجابة الصحيحة</p>

                <div style="display:flex;gap:16px;justify-content:center;">
                    <form method="POST">
                        <input type="hidden" name="action" value="confirm_received">
                        <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('تأكيد استلام المفقود؟')">
                            <i class="fas fa-check-circle"></i> نعم، استلمته
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="not_received">
                        <button type="submit" class="btn btn-warning btn-lg" onclick="return confirm('إبلاغ المبلّغ بأنه لم يتم الاستلام؟')">
                            <i class="fas fa-times-circle"></i> لا، لم أستلمه
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align:center;color:var(--gray);">
                <i class="fas fa-info-circle" style="font-size:2rem;"></i>
                <p>لا يوجد مطابق مؤكد لهذا البلاغ</p>
            </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:24px;">
            <a href="view.php?id=<?= $id ?>" class="btn btn-outline">العودة للبلاغ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
