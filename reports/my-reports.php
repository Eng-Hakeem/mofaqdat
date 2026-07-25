<?php
$pageTitle = 'بلاغاتي';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$reports = $pdo->prepare("
    SELECT r.*, c.name_ar as category_name, c.icon as category_icon,
           (SELECT COUNT(*) FROM matches WHERE lost_report_id = r.id OR found_report_id = r.id) as match_count
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = ? AND r.is_deleted = 0
    ORDER BY r.created_at DESC
");
$reports->execute([$_SESSION['user_id']]);
$reports = $reports->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-list"></i> بلاغاتي</h1>
            <p>إدارة جميع بلاغاتك</p>
        </div>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> بلاغ جديد
        </a>
    </div>

    <?php if (empty($reports)): ?>
        <div class="no-results">
            <i class="fas fa-clipboard-list"></i>
            <h3>لا توجد بلاغات بعد</h3>
            <p>لم تقم بأي بلاغ بعد. ابدأ بإنشاء أول بلاغ!</p>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> أنشئ بلاغاً الآن
            </a>
        </div>
    <?php else: ?>
        <div class="my-reports-table">
            <table>
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>العنوان</th>
                        <th>الفئة</th>
                        <th>الموقع</th>
                        <th>التاريخ</th>
                        <th>المطابقات</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?= $report['report_type'] ?>">
                                    <?= getReportTypeText($report['report_type']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $report['id'] ?>"><?= sanitize($report['title']) ?></a>
                            </td>
                            <td>
                                <i class="fas <?= $report['category_icon'] ?>"></i>
                                <?= sanitize($report['category_name']) ?>
                            </td>
                            <td><?= sanitize($report['location_name'] ?? '-') ?></td>
                            <td><?= timeAgo($report['created_at']) ?></td>
                            <td>
                                <?php if ($report['match_count'] > 0): ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-magic"></i> <?= $report['match_count'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= getStatusColor($report['status']) ?>">
                                    <?= getReportStatusText($report['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-info" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" title="حذف" onclick="deleteReport(<?= $report['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteReport(id) {
    if (confirm('هل أنت متأكد من حذف هذا البلاغ؟')) {
        fetch('api/reports.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: id})
        }).then(r => r.json()).then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('حدث خطأ أثناء الحذف');
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
