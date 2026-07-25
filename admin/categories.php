<?php
$pageTitle = 'إدارة الفئات';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-folder');
        if ($name && $name_ar) {
            $pdo->prepare("INSERT INTO categories (name, name_ar, icon) VALUES (?, ?, ?)")->execute([$name, $name_ar, $icon]);
            setFlash('success', 'تمت إضافة الفئة');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-folder');
        if ($id && $name && $name_ar) {
            $pdo->prepare("UPDATE categories SET name=?, name_ar=?, icon=? WHERE id=?")->execute([$name, $name_ar, $icon, $id]);
            setFlash('success', 'تم تحديث الفئة');
        }
    }
    redirect('categories.php');
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $report_count = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE category_id = ? AND is_deleted = 0");
    $report_count->execute([$id]);
    if ($report_count->fetchColumn() > 0) {
        setFlash('error', 'لا يمكن حذف فئة تحتوي بلاغات نشطة');
    } else {
        $pdo->prepare("UPDATE categories SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
        setFlash('success', 'تم حذف الفئة (حذف ناعم) - يمكن استعادتها');
    }
    redirect('categories.php');
}

if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $pdo->prepare("UPDATE categories SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$id]);
    setFlash('success', 'تم استعادة الفئة بنجاح');
    redirect('categories.php');
}

$show_deleted = isset($_GET['deleted']);

$where = $show_deleted ? "WHERE c.is_deleted = 1" : "WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)";

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM reports WHERE category_id = c.id AND is_deleted = 0) as report_count
    FROM categories c
    $where
    ORDER BY c.id
")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h1 style="margin:0;"><i class="fas fa-folder"></i> إدارة الفئات</h1>
        <a href="?<?= $show_deleted ? '' : 'deleted=1' ?>" class="btn <?= $show_deleted ? 'btn-warning' : 'btn-outline' ?>">
            <i class="fas fa-trash-alt"></i> <?= $show_deleted ? 'العودة للفئات' : 'المحذوفات' ?>
            <?php
            $deleted_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_deleted = 1")->fetchColumn();
            if ($deleted_count > 0): ?>
                <span class="badge" style="background:var(--danger);color:white;margin-right:4px;"><?= $deleted_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="admin-grid-2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><?= $show_deleted ? 'الفئات المحذوفة' : 'الفئات الحالية' ?> (<?= count($categories) ?>)</h3>
            </div>
            <table class="admin-table">
                <thead><tr><th>#</th><th>الاسم بالعربية</th><th>الاسم بالإنجليزية</th><th>الأيقونة</th><th>البلاغات</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">لا توجد فئات</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="<?= !empty($cat['is_deleted']) ? 'row-deleted' : '' ?>">
                                <td><?= $cat['id'] ?></td>
                                <td><strong><?= sanitize($cat['name_ar']) ?></strong></td>
                                <td><?= sanitize($cat['name']) ?></td>
                                <td><i class="fas <?= sanitize($cat['icon']) ?>"></i> <?= sanitize($cat['icon']) ?></td>
                                <td><span class="badge badge-info"><?= $cat['report_count'] ?></span></td>
                                <td >
                                    <?php if ($show_deleted): ?>
                                        <a href="?restore=<?= $cat['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('استعادة هذه الفئة؟')"><i class="fas fa-undo"></i> استعادة</a>
                                    <?php else: ?>
                                        <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف هذه الفئة؟ (يمكن استعادتها من المحذوفات)')"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$show_deleted): ?>
        <div>
            <div class="admin-form-card">
                <h3><i class="fas fa-plus"></i> إضافة فئة جديدة</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="admin-form-group"><label>الاسم بالإنجليزية</label><input type="text" name="name" required placeholder="phones"></div>
                    <div class="admin-form-group"><label>الاسم بالعربية</label><input type="text" name="name_ar" required placeholder="الهواتف"></div>
                    <div class="admin-form-group"><label>أيقونة Font Awesome</label><input type="text" name="icon" value="fa-folder" placeholder="fa-folder"></div>
                    <div class="admin-form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
