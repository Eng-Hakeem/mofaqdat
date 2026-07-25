<?php
$pageTitle = 'المطابقات';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

if (isset($_GET['update_status'])) {
    $id = intval($_GET['update_status']);
    $status = $_GET['st'] ?? '';
    if (in_array($status, ['suggested', 'confirmed', 'rejected'])) {
        $pdo->prepare("UPDATE matches SET status = ? WHERE id = ?")->execute([$status, $id]);
        setFlash('success', 'تم تحديث الحالة');
    }
    redirect('matches.php');
}

$matches = $pdo->query("
    SELECT m.*,
           lr.title as lost_title, lr.report_type as lost_type, lr.user_id as lost_user_id,
           fr.title as found_title, fr.report_type as found_type, fr.user_id as found_user_id,
           lu.full_name as lost_user_name, fu.full_name as found_user_name
    FROM matches m
    JOIN reports lr ON m.lost_report_id = lr.id
    JOIN reports fr ON m.found_report_id = fr.id
    JOIN users lu ON lr.user_id = lu.id
    JOIN users fu ON fr.user_id = fu.id
    ORDER BY m.match_score DESC
    LIMIT 50
")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <h1><i class="fas fa-handshake"></i> المطابقات</h1>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>البلاغ المفقود</th>
                    <th>صاحب المفقود</th>
                    <th>البلاغ المعثور</th>
                    <th>صاحب المعثور</th>
                    <th>نسبة التطابق</th>
                    <th>السبب</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matches)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">لا توجد مطابقات</td></tr>
                <?php else: ?>
                    <?php foreach ($matches as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><a href="<?= SITE_URL ?>/reports/view.php?id=<?= $m['lost_report_id'] ?>" target="_blank"><?= sanitize(substr($m['lost_title'], 0, 25)) ?></a></td>
                            <td><?= sanitize($m['lost_user_name']) ?></td>
                            <td><a href="<?= SITE_URL ?>/reports/view.php?id=<?= $m['found_report_id'] ?>" target="_blank"><?= sanitize(substr($m['found_title'], 0, 25)) ?></a></td>
                            <td><?= sanitize($m['found_user_name']) ?></td>
                            <td><strong style="color:var(--primary);"><?= $m['match_score'] ?>%</strong></td>
                            <td style="max-width:200px;"><?= sanitize(substr($m['match_reason'], 0, 40)) ?></td>
                            <td>
                                <?php
                                $status_colors = ['suggested' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
                                $status_labels = ['suggested' => 'مقترح', 'confirmed' => 'مؤكد', 'rejected' => 'مرفوض'];
                                ?>
                                <span class="badge badge-<?= $status_colors[$m['status']] ?>"><?= $status_labels[$m['status']] ?></span>
                            </td>
                            <td>
                                <?php if ($m['status'] !== 'confirmed'): ?>
                                    <a href="?update_status=<?= $m['id'] ?>&st=confirmed" class="btn btn-sm btn-success" title="تأكيد"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <?php if ($m['status'] !== 'rejected'): ?>
                                    <a href="?update_status=<?= $m['id'] ?>&st=rejected" class="btn btn-sm btn-danger" title="رفض"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
