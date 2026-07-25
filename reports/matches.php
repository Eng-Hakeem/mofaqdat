<?php
$pageTitle = 'المطابقات وبيانات التواصل';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireLogin();

$report_id = intval($_GET['id'] ?? 0);
if ($report_id <= 0) {
    setFlash('error', 'معرف البلاغ غير صحيح');
    redirect(SITE_URL);
}

$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
$stmt->execute([$report_id, $_SESSION['user_id']]);
$my_report = $stmt->fetch();

if (!$my_report) {
    setFlash('error', 'البلاغ غير موجود أو ليس لديك صلاحية الوصول');
    redirect(SITE_URL . '/reports/my-reports.php');
}

$matched_type = $my_report['report_type'] === 'lost' ? 'found_report_id' : 'lost_report_id';
$my_type = $my_report['report_type'] === 'lost' ? 'lost_report_id' : 'found_report_id';

$stmt = $pdo->prepare("
    SELECT m.*, 
           r.title, r.description, r.image, r.location_name, r.date_occurred, r.report_type,
           r.color, r.brand, r.model, r.distinguishing_marks, r.privacy_level, r.contact_method, r.reward_amount,
           u.full_name, u.email, u.phone
    FROM matches m
    JOIN reports r ON m.{$matched_type} = r.id
    JOIN users u ON r.user_id = u.id
    WHERE m.{$my_type} = ?
    ORDER BY m.match_score DESC
");
$stmt->execute([$report_id]);
$matches = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-handshake"></i> المطابقات وبيانات التواصل</h1>
            <p>
                بلاغك: <strong><?= sanitize($my_report['title']) ?></strong>
                <span class="badge badge-<?= $my_report['report_type'] === 'lost' ? 'danger' : 'success' ?>">
                    <?= getReportTypeText($my_report['report_type']) ?>
                </span>
            </p>
        </div>
        <a href="view.php?id=<?= $report_id ?>" class="btn btn-outline">
            <i class="fas fa-arrow-right"></i> العودة للبلاغ
        </a>
    </div>

    <?php if (empty($matches)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>لا توجد مطابقات بعد</h3>
            <p>سيظهر هنا أي بلاغ يتطابق مع بلاغك مع بيانات التواصل المكشوفة</p>
        </div>
    <?php else: ?>
        <div class="matches-detail-list">
            <?php foreach ($matches as $index => $match): ?>
                <div class="match-detail-card <?= $match['status'] === 'confirmed' ? 'confirmed' : '' ?>">
                    <div class="match-detail-header">
                        <div class="match-score-badge">
                            <span class="score"><?= $match['match_score'] ?>%</span>
                            <span class="label">تطابق</span>
                        </div>
                        <div class="match-status">
                            <?php if ($match['status'] === 'confirmed'): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> تم التأكيد</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> في انتظار التأكيد</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="match-detail-body">
                        <div class="match-report-info">
                            <h3>
                                <a href="view.php?id=<?= $match[($my_report['report_type'] === 'lost' ? 'found_report_id' : 'lost_report_id')] ?? $match['id'] ?>">
                                    <?= sanitize($match['title']) ?>
                                </a>
                            </h3>
                            <p class="match-reason"><i class="fas fa-info-circle"></i> سبب التطابق: <?= sanitize($match['match_reason']) ?></p>
                            <p class="match-location"><i class="fas fa-map-marker-alt"></i> <?= sanitize($match['location_name'] ?? 'غير محدد') ?></p>
                            <p class="match-date"><i class="fas fa-calendar"></i> <?= $match['date_occurred'] ? formatDate($match['date_occurred'], 'ar') : 'غير محدد' ?></p>
                        </div>

                        <div class="match-contact-section">
                            <div class="contact-reveal-banner">
                                <i class="fas fa-unlock-alt"></i>
                                <div>
                                    <h4>بيانات التواصل مكشوفة لك</h4>
                                    <p>بسبب وجود تطابق بين بلاغاتكما، أصبحت بيانات التواصل متاحة</p>
                                </div>
                            </div>

                            <div class="contact-info-revealed">
                                <div class="contact-card">
                                    <div class="contact-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="contact-details">
                                        <h4><?= sanitize($match['full_name']) ?></h4>
                                        <div class="contact-items">
                                            <?php if ($match['contact_method'] !== 'email'): ?>
                                                <a href="tel:<?= $match['phone'] ?>" class="contact-item phone">
                                                    <i class="fas fa-phone-alt"></i>
                                                    <span><?= $match['phone'] ?></span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($match['contact_method'] !== 'phone'): ?>
                                                <a href="mailto:<?= $match['email'] ?>" class="contact-item email">
                                                    <i class="fas fa-envelope"></i>
                                                    <span><?= $match['email'] ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($match['reward_amount'] > 0): ?>
                                    <div class="reward-badge">
                                        <i class="fas fa-coins"></i>
                                        <span>مكافأة: <?= $match['reward_amount'] ?> درهم</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="match-detail-footer">
                        <a href="view.php?id=<?= $match[($my_report['report_type'] === 'lost' ? 'found_report_id' : 'lost_report_id')] ?? $match['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> عرض التفاصيل
                        </a>
                        <a href="tel:<?= $match['phone'] ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-phone-alt"></i> اتصل الآن
                        </a>
                        <a href="mailto:<?= $match['email'] ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-envelope"></i> أرسل بريداً
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
