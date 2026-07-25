<?php
$pageTitle = 'لوحة التحكم';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    setFlash('error', 'ليس لديك صلاحية الوصول');
    redirect(SITE_URL);
}

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM reports WHERE status = 'active' AND is_deleted = 0) as active_reports,
        (SELECT COUNT(*) FROM reports WHERE report_type = 'lost' AND status = 'active' AND is_deleted = 0) as lost_count,
        (SELECT COUNT(*) FROM reports WHERE report_type = 'found' AND status = 'active' AND is_deleted = 0) as found_count,
        (SELECT COUNT(*) FROM reports WHERE status = 'matched' AND is_deleted = 0) as matched_count,
        (SELECT COUNT(*) FROM reports WHERE status = 'resolved' AND is_deleted = 0) as resolved_count,
        (SELECT COUNT(*) FROM users WHERE is_deleted = 0) as total_users,
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted = 0) as new_users_week,
        (SELECT COUNT(*) FROM reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted = 0) as new_reports_week,
        (SELECT COUNT(*) FROM matches WHERE status = 'suggested') as pending_matches,
        (SELECT COUNT(*) FROM verification_requests WHERE status = 'pending') as pending_verifications
")->fetch();

$recent_reports = $pdo->query("
    SELECT r.*, c.name_ar as category_name, u.full_name
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE r.is_deleted = 0 AND u.is_deleted = 0
    ORDER BY r.created_at DESC LIMIT 10
")->fetchAll();

$recent_users = $pdo->query("SELECT * FROM users WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>

    <div class="admin-stats-grid">
        <div class="admin-stat-card" style="border-right: 4px solid var(--primary);">
            <div class="admin-stat-icon icon-blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['active_reports'] ?></span>
                <span class="admin-stat-label">بلاغ نشط</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid var(--danger);">
            <div class="admin-stat-icon icon-red">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['lost_count'] ?></span>
                <span class="admin-stat-label">مفقود</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid var(--success);">
            <div class="admin-stat-icon icon-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['found_count'] ?></span>
                <span class="admin-stat-label">معثور عليه</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid var(--warning);">
            <div class="admin-stat-icon icon-yellow">
                <i class="fas fa-users"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['total_users'] ?></span>
                <span class="admin-stat-label">مستخدم</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid #8b5cf6;">
            <div class="admin-stat-icon icon-purple">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['matched_count'] ?></span>
                <span class="admin-stat-label">تمت المطابقة</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid #06b6d4;">
            <div class="admin-stat-icon icon-cyan">
                <i class="fas fa-heart"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['resolved_count'] ?></span>
                <span class="admin-stat-label">تم التسليم</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid #f97316;">
            <div class="admin-stat-icon icon-orange">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['new_users_week'] ?></span>
                <span class="admin-stat-label">مستخدم جديد (أسبوع)</span>
            </div>
        </div>
        <div class="admin-stat-card" style="border-right: 4px solid #10b981;">
            <div class="admin-stat-icon icon-emerald">
                <i class="fas fa-file-medical"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= $stats['new_reports_week'] ?></span>
                <span class="admin-stat-label">بلاغ جديد (أسبوع)</span>
            </div>
        </div>
    </div>

    <?php if ($stats['pending_verifications'] > 0): ?>
    <div class="admin-alert admin-alert-warning">
        <i class="fas fa-id-card"></i>
        <span>هناك <strong><?= $stats['pending_verifications'] ?></strong> طلب تحقق معلق</span>
        <a href="verify.php" class="btn btn-sm btn-warning">مراجعة</a>
    </div>
    <?php endif; ?>

    <div class="admin-grid-2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-clock"></i> أحدث البلاغات</h3>
                <a href="reports.php" class="btn btn-sm btn-outline">عرض الكل</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>النوع</th>
                        <th>العنوان</th>
                        <th>المستخدم</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_reports as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><span class="badge badge-<?= $r['report_type'] ?>"><?= getReportTypeText($r['report_type']) ?></span></td>
                            <td><a href="<?= SITE_URL ?>/reports/view.php?id=<?= $r['id'] ?>"><?= sanitize(substr($r['title'], 0, 30)) ?></a></td>
                            <td><?= sanitize($r['full_name']) ?></td>
                            <td><?= timeAgo($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-users"></i> أحدث المستخدمين</h3>
                <a href="users.php" class="btn btn-sm btn-outline">عرض الكل</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= sanitize($u['full_name']) ?></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td>
                                <?php if ($u['id_verified']): ?>
                                    <span class="badge badge-success">موثق</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">غير موثق</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
