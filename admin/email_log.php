<?php
$pageTitle = 'سجل البريد الإلكتروني';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

if (isset($_GET['retry'])) {
    require_once __DIR__ . '/../includes/email.php';
    $log_id = intval($_GET['retry']);
    $log = $pdo->prepare("SELECT * FROM email_log WHERE id = ? AND status = 'failed'");
    $log->execute([$log_id]);
    $log = $log->fetch();

    if ($log) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <no-reply@mofaqdat.com>\r\n";
        $headers .= "Reply-To: info@mofaqdat.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $full_subject = "=?UTF-8?B?" . base64_encode("[" . SITE_NAME . "] " . $log['subject']) . "?=";
        $sent = @mail($log['email'], $full_subject, buildEmailTemplate($log['subject'], $log['body'], '', $log['type']), $headers);

        $pdo->prepare("UPDATE email_log SET status = ?, created_at = NOW() WHERE id = ?")
            ->execute([$sent ? 'sent' : 'failed', $log_id]);

        setFlash($sent ? 'success' : 'error', $sent ? 'تم إعادة الإرسال بنجاح' : 'فشل إعادة الإرسال');
    } else {
        setFlash('error', 'السجل غير موجود أو تم إرساله مسبقاً');
    }
    redirect('email_log.php');
}

if (isset($_GET['retry_failed'])) {
    require_once __DIR__ . '/../includes/email.php';
    $failed = $pdo->prepare("SELECT * FROM email_log WHERE status = 'failed' LIMIT 20");
    $failed->execute();
    $failed = $failed->fetchAll();

    $retried = 0;
    $succeeded = 0;
    foreach ($failed as $log) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <no-reply@mofaqdat.com>\r\n";
        $headers .= "Reply-To: info@mofaqdat.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $full_subject = "=?UTF-8?B?" . base64_encode("[" . SITE_NAME . "] " . $log['subject']) . "?=";
        $sent = @mail($log['email'], $full_subject, buildEmailTemplate($log['subject'], $log['body'], '', $log['type']), $headers);

        $status = $sent ? 'sent' : 'failed';
        $pdo->prepare("UPDATE email_log SET status = ?, created_at = NOW() WHERE id = ?")->execute([$status, $log['id']]);
        $retried++;
        if ($sent) $succeeded++;
    }

    setFlash('success', "تمت محاولة إعادة إرسال $retried بريد، نجح $succeeded");
    redirect('email_log.php');
}

if (isset($_GET['clear_old'])) {
    $pdo->exec("DELETE FROM email_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND status = 'sent'");
    $deleted = $pdo->rowCount();
    setFlash('success', "تم حذف $deleted سجل قديم (أكثر من 90 يوم)");
    redirect('email_log.php');
}

$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($filter_type) {
    $where .= " AND el.type = ?";
    $params[] = $filter_type;
}
if ($filter_status) {
    $where .= " AND el.status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $where .= " AND (el.email LIKE ? OR el.subject LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM email_log el 
    LEFT JOIN users u ON el.user_id = u.id 
    $where
");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT el.*, u.full_name as user_name
    FROM email_log el
    LEFT JOIN users u ON el.user_id = u.id
    $where
    ORDER BY el.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'sent') as sent,
        SUM(status = 'failed') as failed,
        SUM(created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as last_24h,
        SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as last_7d
    FROM email_log
")->fetch();

$type_stats = $pdo->query("
    SELECT type, COUNT(*) as cnt, SUM(status = 'sent') as sent, SUM(status = 'failed') as failed
    FROM email_log
    GROUP BY type ORDER BY cnt DESC
")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h1 style="margin:0;"><i class="fas fa-envelope"></i> سجل البريد الإلكتروني</h1>
        <div style="display:flex;gap:8px;">
            <a href="?retry_failed=1" class="btn btn-warning btn-sm" onclick="return confirm('إعادة إرسال جميع رسائل الفشل (حتى 20)؟')">
                <i class="fas fa-redo"></i> إعادة إرسال الفاشلة
            </a>
            <a href="?clear_old=1" class="btn btn-outline btn-sm" onclick="return confirm('حذف سجلات مرسلة قديمة (أكثر من 90 يوم)؟')">
                <i class="fas fa-trash-alt"></i> تنظيف القديم
            </a>
        </div>
    </div>

    <div class="admin-stats-grid">
        <div class="admin-stat-card" style="border-right: 4px solid var(--primary);">
            <div class="admin-stat-icon icon-blue">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['total'] ?></span>
                <span class="admin-stat-label">إجمالي الرسائل</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid var(--success);">
            <div class="admin-stat-icon icon-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['sent'] ?></span>
                <span class="admin-stat-label">تم الإرسال</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid var(--danger);">
            <div class="admin-stat-icon icon-red">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['failed'] ?></span>
                <span class="admin-stat-label">فشل الإرسال</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid #f59e0b;">
            <div class="admin-stat-icon icon-yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['last_24h'] ?></span>
                <span class="admin-stat-label">آخر 24 ساعة</span>
            </div>
        </div>
    </div>

    <?php if (!empty($type_stats)): ?>
    <div class="admin-grid-2" style="grid-template-columns: 1fr;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-chart-bar"></i> إحصائيات حسب النوع</h3>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>الإجمالي</th>
                        <th>تم الإرسال</th>
                        <th>فشل</th>
                        <th>نسبة النجاح</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($type_stats as $ts): ?>
                        <tr>
                            <td>
                                <?php
                                $type_labels = [
                                    'match' => 'مطابقة', 'new_found' => 'عثور جديد', 'delivery_request' => 'طلب تسليم',
                                    'delivery_confirmed' => 'تأكيد تسليم', 'delivery_pending' => 'تسليم معلق',
                                    'status' => 'تحديث حالة', 'system' => 'نظام'
                                ];
                                echo $type_labels[$ts['type']] ?? $ts['type'];
                                ?>
                            </td>
                            <td><strong><?= $ts['cnt'] ?></strong></td>
                            <td><span class="badge badge-success"><?= $ts['sent'] ?></span></td>
                            <td><span class="badge badge-danger"><?= $ts['failed'] ?></span></td>
                            <td>
                                <?php $rate = $ts['cnt'] > 0 ? round(($ts['sent'] / $ts['cnt']) * 100) : 0; ?>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="progress-bar-bg" style="flex:1;border-radius:4px;height:8px;max-width:100px;">
                                        <div style="height:100%;width:<?= $rate ?>%;background:<?= $rate >= 80 ? '#16a34a' : ($rate >= 50 ? '#f59e0b' : '#dc2626') ?>;border-radius:4px;"></div>
                                    </div>
                                    <span class="progress-rate-text"><?= $rate ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-form-card">
        <form method="GET" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <div class="admin-form-group" style="flex:2">
                <label>بحث</label>
                <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="بريد، عنوان، اسم المستخدم...">
            </div>
            <div class="admin-form-group" style="flex:1">
                <label>النوع</label>
                <select name="type">
                    <option value="">الكل</option>
                    <option value="match" <?= $filter_type === 'match' ? 'selected' : '' ?>>مطابقة</option>
                    <option value="new_found" <?= $filter_type === 'new_found' ? 'selected' : '' ?>>عثور جديد</option>
                    <option value="delivery_request" <?= $filter_type === 'delivery_request' ? 'selected' : '' ?>>طلب تسليم</option>
                    <option value="delivery_confirmed" <?= $filter_type === 'delivery_confirmed' ? 'selected' : '' ?>>تأكيد تسليم</option>
                    <option value="delivery_pending" <?= $filter_type === 'delivery_pending' ? 'selected' : '' ?>>تسليم معلق</option>
                    <option value="status" <?= $filter_type === 'status' ? 'selected' : '' ?>>تحديث حالة</option>
                    <option value="system" <?= $filter_type === 'system' ? 'selected' : '' ?>>نظام</option>
                </select>
            </div>
            <div class="admin-form-group" style="flex:1">
                <label>الحالة</label>
                <select name="status">
                    <option value="">الكل</option>
                    <option value="sent" <?= $filter_status === 'sent' ? 'selected' : '' ?>>تم الإرسال</option>
                    <option value="failed" <?= $filter_status === 'failed' ? 'selected' : '' ?>>فشل</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <p style="color:#64748b;margin-bottom:10px;">النتائج: <?= $total ?> سجل</p>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المستخدم</th>
                    <th>البريد</th>
                    <th>العنوان</th>
                    <th>النوع</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">لا توجد سجلات</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= sanitize($log['user_name'] ?? '-') ?></td>
                            <td style="font-size:0.8rem;"><?= sanitize($log['email']) ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize($log['subject']) ?></td>
                            <td>
                                <?php
                                $type_labels = [
                                    'match' => 'مطابقة', 'new_found' => 'عثور', 'delivery_request' => 'طلب تسليم',
                                    'delivery_confirmed' => 'تأكيد', 'delivery_pending' => 'معلق',
                                    'status' => 'حالة', 'system' => 'نظام'
                                ];
                                $type_colors = [
                                    'match' => 'success', 'new_found' => 'info', 'delivery_request' => 'primary',
                                    'delivery_confirmed' => 'success', 'delivery_pending' => 'warning',
                                    'status' => 'info', 'system' => 'secondary'
                                ];
                                ?>
                                <span class="badge badge-<?= $type_colors[$log['type']] ?? 'secondary' ?>">
                                    <?= $type_labels[$log['type']] ?? $log['type'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.8rem;"><?= timeAgo($log['created_at']) ?></td>
                            <td>
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> مرسل</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> فشل</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['status'] === 'failed'): ?>
                                    <a href="?retry=<?= $log['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('إعادة إرسال هذا البريد؟')">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
