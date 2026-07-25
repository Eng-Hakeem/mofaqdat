<?php
$pageTitle = 'إدارة المستخدمين';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

$main_admin_id = 1;

if (isset($_GET['toggle_verify'])) {
    $id = intval($_GET['toggle_verify']);
    if ($id != $main_admin_id) {
        $pdo->prepare("UPDATE users SET id_verified = NOT id_verified WHERE id = ? AND is_deleted = 0")->execute([$id]);
        setFlash('success', 'تم تحديث حالة التحقق');
    }
    redirect('users.php');
}

if (isset($_GET['promote'])) {
    $id = intval($_GET['promote']);
    if ($id != $main_admin_id && $id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND is_deleted = 0")->execute([$id]);
        setFlash('success', 'تم ترقية المستخدم إلى مدير');
    } else {
        setFlash('error', 'لا يمكن ترقية هذا المستخدم');
    }
    redirect('users.php');
}

if (isset($_GET['demote'])) {
    $id = intval($_GET['demote']);
    if ($id != $main_admin_id && $id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ? AND is_deleted = 0 AND role = 'admin'")->execute([$id]);
        setFlash('success', 'تم خفض صلاحيات المستخدم إلى مستخدم عادي');
    } else {
        setFlash('error', 'لا يمكن خفض صلاحيات هذا المستخدم');
    }
    redirect('users.php');
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id != $main_admin_id && $id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND role != 'admin' OR (role = 'admin' AND id != ?)")->execute([$id, $main_admin_id]);
        $pdo->prepare("UPDATE reports SET is_deleted = 1, deleted_at = NOW() WHERE user_id = ?")->execute([$id]);
        setFlash('success', 'تم حذف المستخدم (حذف ناعم) - يمكن استعادته من صفحة المحذوفات');
    } else {
        setFlash('error', 'لا يمكن حذف هذا المستخدم');
    }
    redirect('users.php');
}

if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $pdo->prepare("UPDATE users SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE reports SET is_deleted = 0, deleted_at = NULL WHERE user_id = ?")->execute([$id]);
    setFlash('success', 'تم استعادة المستخدم وبلاغاته بنجاح');
    redirect('users.php');
}

$show_deleted = isset($_GET['deleted']);
$search = trim($_GET['q'] ?? '');

$where = $show_deleted ? "WHERE u.is_deleted = 1" : "WHERE u.is_deleted = 0";
$params = [];

if ($search) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT u.*, (SELECT COUNT(*) FROM reports WHERE user_id = u.id AND is_deleted = 0) as report_count
    FROM users u
    $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h1 style="margin:0;"><i class="fas fa-users"></i> إدارة المستخدمين</h1>
        <a href="?<?= $show_deleted ? '' : 'deleted=1' ?>" class="btn <?= $show_deleted ? 'btn-warning' : 'btn-outline' ?>">
            <i class="fas fa-trash-alt"></i> <?= $show_deleted ? 'العودة للمستخدمين' : 'المحذوفات' ?>
            <?php
            $deleted_count = $pdo->query("SELECT COUNT(*) FROM users WHERE is_deleted = 1")->fetchColumn();
            if ($deleted_count > 0): ?>
                <span class="badge" style="background:var(--danger);color:white;margin-right:4px;"><?= $deleted_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="admin-form-card">
        <form method="GET" style="display:flex;gap:10px;align-items:end;">
            <?php if ($show_deleted): ?><input type="hidden" name="deleted" value="1"><?php endif; ?>
            <div class="admin-form-group" style="flex:3">
                <label>بحث</label>
                <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="اسم، بريد، هاتف...">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <p style="color:#64748b;margin-bottom:10px;"><?= $show_deleted ? 'المحذوفات' : 'النتائج' ?>: <?= $total ?> مستخدم</p>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>البريد</th>
                    <th>الهاتف</th>
                    <th>البلاغات</th>
                    <th>الدور</th>
                    <th>التحقق</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="10" style="text-align:center;padding:30px;color:#94a3b8;">لا يوجد مستخدمين</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="<?= $u['is_deleted'] ? 'row-deleted' : '' ?>">
                            <td><?= $u['id'] ?></td>
                            <td>
                                <strong><?= sanitize($u['full_name']) ?></strong>
                                <?php if ($u['id'] == $main_admin_id): ?>
                                    <span class="badge badge-primary" style="font-size:0.65rem;margin-right:4px;">الرئيسي</span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><?= sanitize($u['phone'] ?? '-') ?></td>
                            <td><span class="badge badge-info"><?= $u['report_count'] ?></span></td>
                            <td>
                                <?php if ($u['id'] == $main_admin_id): ?>
                                    <span class="badge badge-primary"><i class="fas fa-crown"></i> مدير رئيسي</span>
                                <?php elseif ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-primary">مدير</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">مستخدم</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['id_verified']): ?>
                                    <span class="badge badge-success">موثق</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">غير موثق</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_deleted']): ?>
                                    <span class="badge badge-danger">محذوف</span>
                                <?php else: ?>
                                    <span class="badge badge-success">نشط</span>
                                <?php endif; ?>
                            </td>
                            <td><?= timeAgo($u['created_at']) ?></td>
                            <td>
                                <?php if ($show_deleted): ?>
                                    <a href="?restore=<?= $u['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('استعادة هذا المستخدم وبلاغاته؟')">
                                        <i class="fas fa-undo"></i> استعادة
                                    </a>
                                <?php else: ?>
                                    <?php if ($u['id'] != $main_admin_id && $u['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="?promote=<?= $u['id'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('ترقية <?= sanitize($u['full_name']) ?> إلى مدير؟')" title="ترقية لمدير">
                                                <i class="fas fa-arrow-up"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?demote=<?= $u['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('خفض صلاحيات <?= sanitize($u['full_name']) ?> إلى مستخدم عادي؟')" title="خفض الصلاحيات">
                                                <i class="fas fa-arrow-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف <?= sanitize($u['full_name']) ?>؟ (يمكن استعادته لاحقاً من المحذوفات)')" title="حذف ناعم">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
