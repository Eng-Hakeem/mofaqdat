<?php
$pageTitle = 'إدارة البلاغات';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("UPDATE reports SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
    setFlash('success', 'تم حذف البلاغ (حذف ناعم)');
    redirect('reports.php');
}

if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $pdo->prepare("UPDATE reports SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$id]);
    setFlash('success', 'تم استعادة البلاغ');
    redirect('reports.php');
}

if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    if (in_array($status, ['active', 'matched', 'resolved', 'expired'])) {
        $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute([$status, $id]);
        setFlash('success', 'تم تحديث الحالة');
    }
    redirect('reports.php');
}

$show_deleted = isset($_GET['deleted']);
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = $show_deleted ? "WHERE r.is_deleted = 1" : "WHERE r.is_deleted = 0";
$params = [];

if ($filter_type && in_array($filter_type, ['lost', 'found'])) {
    $where .= " AND r.report_type = ?";
    $params[] = $filter_type;
}
if ($filter_status && in_array($filter_status, ['active', 'matched', 'resolved', 'expired'])) {
    $where .= " AND r.status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $where .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reports r $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT r.*, c.name_ar as category_name, u.full_name
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.user_id = u.id
    $where
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$reports = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h1 style="margin:0;"><i class="fas fa-file-alt"></i> إدارة البلاغات</h1>
        <a href="?<?= $show_deleted ? '' : 'deleted=1' ?>" class="btn <?= $show_deleted ? 'btn-warning' : 'btn-outline' ?>">
            <i class="fas fa-trash-alt"></i> <?= $show_deleted ? 'العودة للبلاغات' : 'المحذوفات' ?>
            <?php
            $deleted_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted = 1")->fetchColumn();
            if ($deleted_count > 0): ?>
                <span class="badge" style="background:var(--danger);color:white;margin-right:4px;"><?= $deleted_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="admin-form-card">
        <form method="GET" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <?php if ($show_deleted): ?><input type="hidden" name="deleted" value="1"><?php endif; ?>
            <div class="admin-form-group" style="flex:2">
                <label>بحث</label>
                <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="ابحث في العنوان أو الوصف...">
            </div>
            <div class="admin-form-group" style="flex:1">
                <label>النوع</label>
                <select name="type">
                    <option value="">الكل</option>
                    <option value="lost" <?= $filter_type === 'lost' ? 'selected' : '' ?>>مفقود</option>
                    <option value="found" <?= $filter_type === 'found' ? 'selected' : '' ?>>معثور عليه</option>
                </select>
            </div>
            <div class="admin-form-group" style="flex:1">
                <label>الحالة</label>
                <select name="status">
                    <option value="">الكل</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>نشط</option>
                    <option value="matched" <?= $filter_status === 'matched' ? 'selected' : '' ?>>مطابق</option>
                    <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>تم الحل</option>
                    <option value="expired" <?= $filter_status === 'expired' ? 'selected' : '' ?>>منتهي</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
        </form>
    </div>

    <p style="color:#64748b;margin-bottom:10px;"><?= $show_deleted ? 'المحذوفات' : 'النتائج' ?>: <?= $total ?> بلاغ</p>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>النوع</th>
                    <th>العنوان</th>
                    <th>الفئة</th>
                    <th>المستخدم</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">لا توجد بلاغات</td></tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr class="<?= $r['is_deleted'] ? 'row-deleted' : '' ?>">
                            <td><?= $r['id'] ?></td>
                            <td><span class="badge badge-<?= $r['report_type'] ?>"><?= getReportTypeText($r['report_type']) ?></span></td>
                            <td><a href="<?= SITE_URL ?>/reports/view.php?id=<?= $r['id'] ?>" target="_blank"><?= sanitize(substr($r['title'], 0, 30)) ?></a></td>
                            <td><?= sanitize($r['category_name']) ?></td>
                            <td><?= sanitize($r['full_name']) ?></td>
                            <td><?= timeAgo($r['created_at']) ?></td>
                            <td>
                                <?php if ($r['is_deleted']): ?>
                                    <span class="badge badge-danger">محذوف</span>
                                <?php else: ?>
                                    <select onchange="changeStatus(<?= $r['id'] ?>, this.value)" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;font-size:0.8rem;">
                                        <?php foreach (['active'=>'نشط','matched'=>'مطابق','resolved'=>'تم الحل','expired'=>'منتهي'] as $val => $label): ?>
                                            <option value="<?= $val ?>" <?= $r['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($show_deleted): ?>
                                    <a href="?restore=<?= $r['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('استعادة هذا البلاغ؟')"><i class="fas fa-undo"></i></a>
                                <?php else: ?>
                                    <a href="<?= SITE_URL ?>/reports/view.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف هذا البلاغ؟ (يمكن استعادته لاحقاً)')"><i class="fas fa-trash"></i></a>
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

<script>
function changeStatus(id, status) {
    window.location.href = '?id=' + id + '&status=' + status;
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
