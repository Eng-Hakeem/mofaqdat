<?php
$pageTitle = 'طلبات التحقق من الهوية';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    $action = $_GET['action'];
    $note = trim($_GET['note'] ?? '');

    if ($id && in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare("UPDATE verification_requests SET status = ?, admin_note = ? WHERE id = ?")->execute([$status, $note, $id]);

        if ($status === 'approved') {
            $req = $pdo->prepare("SELECT user_id FROM verification_requests WHERE id = ?");
            $req->execute([$id]);
            $r = $req->fetch();
            if ($r) {
                $pdo->prepare("UPDATE users SET id_verified = 1 WHERE id = ?")->execute([$r['user_id']]);
                createNotification($r['user_id'], 'تم التحقق من هويتك بنجاح!', 'يمكنك الآن الوصول لبيانات التواصل في البلاغات المطابقة.', 'status');
            }
        }
        setFlash('success', $status === 'approved' ? 'تم قبول الطلب وتحقق المستخدم' : 'تم رفض الطلب');
    }
    redirect('verify.php');
}

$requests = $pdo->query("
    SELECT vr.*, u.full_name, u.email, u.phone, r.title as report_title, r.report_type
    FROM verification_requests vr
    JOIN users u ON vr.user_id = u.id
    JOIN reports r ON vr.report_id = r.id
    ORDER BY vr.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <h1><i class="fas fa-id-card"></i> طلبات التحقق من الهوية</h1>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المستخدم</th>
                    <th>البريد</th>
                    <th>البلاغ</th>
                    <th>نوع الهوية</th>
                    <th>صورة الهوية</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">لا توجد طلبات معلقة</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req):
                        $doc_types = ['national_id' => 'بطاقة وطنية', 'passport' => 'جواز سفر', 'other' => 'أخرى'];
                        $status_badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                        $status_labels = ['pending' => 'معلق', 'approved' => 'مقبول', 'rejected' => 'مرفوض'];
                    ?>
                        <tr>
                            <td><?= $req['id'] ?></td>
                            <td><?= sanitize($req['full_name']) ?></td>
                            <td><?= sanitize($req['email']) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/reports/view.php?id=<?= $req['report_id'] ?>" target="_blank">
                                    <?= sanitize(substr($req['report_title'], 0, 25)) ?>
                                </a>
                            </td>
                            <td><?= $doc_types[$req['document_type']] ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/uploads/<?= $req['document_image'] ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-image"></i> عرض
                                </a>
                            </td>
                            <td><?= timeAgo($req['created_at']) ?></td>
                            <td><span class="badge badge-<?= $status_badges[$req['status']] ?>"><?= $status_labels[$req['status']] ?></span></td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <a href="?action=approve&id=<?= $req['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('قبول التحقق؟')"><i class="fas fa-check"></i></a>
                                    <a href="?action=reject&id=<?= $req['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('رفض التحقق؟')"><i class="fas fa-times"></i></a>
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
