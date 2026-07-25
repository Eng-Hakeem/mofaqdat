<?php
$pageTitle = 'تفاصيل البلاغ';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect(SITE_URL);

$stmt = $pdo->prepare("
    SELECT r.*, c.name_ar as category_name, c.icon as category_icon, u.full_name, u.email as user_email, u.phone as user_phone
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.is_deleted = 0
");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('error', 'البلاغ غير موجود');
    redirect(SITE_URL);
}

$pdo->prepare("UPDATE reports SET views = views + 1 WHERE id = ?")->execute([$id]);

$isOwner = isLoggedIn() && $_SESSION['user_id'] == $report['user_id'];
$isVerifiedOwner = $isOwner && $report['privacy_level'] === 'hidden';

$hasMatch = false;
if (isLoggedIn()) {
    $checkMatch = $pdo->prepare("
        SELECT m.id FROM matches m
        JOIN reports my_r ON (m.lost_report_id = my_r.id OR m.found_report_id = my_r.id)
        WHERE my_r.user_id = ? AND (m.lost_report_id = ? OR m.found_report_id = ?)
        AND m.match_score >= 40
        LIMIT 1
    ");
    $checkMatch->execute([$_SESSION['user_id'], $id, $id]);
    $hasMatch = $checkMatch->fetch() !== false;
}

$showFullDetails = true;
if ($report['privacy_level'] === 'hidden' && !$isVerifiedOwner && !$hasMatch) {
    $showFullDetails = false;
} elseif ($report['privacy_level'] === 'limited' && !$isOwner && !$hasMatch) {
    $showFullDetails = false;
}

$showContact = $isOwner || $hasMatch || $report['privacy_level'] === 'full';

$matches = [];
if ($isOwner || isAdmin()) {
    $stmt = $pdo->prepare("
        SELECT m.*, r.title, r.description, r.image, r.location_name, r.date_occurred, r.report_type, r.color, r.brand, r.model,
               u.full_name as match_user_name, c.name_ar as category_name, c.icon as category_icon
        FROM matches m
        JOIN reports r ON (m.found_report_id = r.id OR m.lost_report_id = r.id) AND r.id != ?
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        WHERE m.lost_report_id = ? OR m.found_report_id = ?
        ORDER BY m.match_score DESC
    ");
    $stmt->execute([$id, $id, $id]);
    $matches = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="report-detail">
        <div class="report-detail-header">
            <div class="report-type <?= $report['report_type'] ?>">
                <i class="fas <?= $report['report_type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                <?= getReportTypeText($report['report_type']) ?>
            </div>
            <span class="badge badge-<?= getStatusColor($report['status']) ?>">
                <?= getReportStatusText($report['status']) ?>
            </span>
        </div>

        <div class="report-detail-grid">
            <div class="report-detail-image">
                <?php if ($report['image']): ?>
                    <img src="<?= SITE_URL ?>/uploads/<?= $report['image'] ?>" alt="<?= sanitize($report['title']) ?>">
                <?php else: ?>
                    <div class="no-image-large">
                        <i class="fas <?= $report['category_icon'] ?>"></i>
                        <span><?= sanitize($report['category_name']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="report-detail-info">
                <h1><?= sanitize($report['title']) ?></h1>

                <div class="info-badges">
                    <span class="badge badge-info">
                        <i class="fas <?= $report['category_icon'] ?>"></i>
                        <?= sanitize($report['category_name']) ?>
                    </span>
                    <span class="badge badge-secondary">
                        <i class="fas fa-eye"></i>
                        <?= $report['views'] + 1 ?> مشاهدة
                    </span>
                    <?php if ($report['reward_amount'] > 0): ?>
                        <span class="badge badge-warning">
                            <i class="fas fa-coins"></i>
                            مكافأة: <?= $report['reward_amount'] ?> درهم
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($showFullDetails): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-align-right"></i> الوصف</h3>
                        <p><?= nl2br(sanitize($report['description'])) ?></p>
                    </div>

                    <?php if ($report['location_name']): ?>
                        <div class="detail-section">
                            <h3><i class="fas fa-map-marker-alt"></i> الموقع</h3>
                            <p><?= sanitize($report['location_name']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="detail-row">
                        <?php if ($report['date_occurred']): ?>
                            <div class="detail-item">
                                <h4><i class="fas fa-calendar"></i> التاريخ</h4>
                                <p><?= formatDate($report['date_occurred'], 'ar') ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($report['time_occurred']): ?>
                            <div class="detail-item">
                                <h4><i class="fas fa-clock"></i> الوقت</h4>
                                <p><?= $report['time_occurred'] ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-row">
                        <?php if ($report['color']): ?>
                            <div class="detail-item">
                                <h4><i class="fas fa-palette"></i> اللون</h4>
                                <p><?= sanitize($report['color']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($report['brand']): ?>
                            <div class="detail-item">
                                <h4><i class="fas fa-tag"></i> العلامة التجارية</h4>
                                <p><?= sanitize($report['brand']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($report['model']): ?>
                            <div class="detail-item">
                                <h4><i class="fas fa-mobile-alt"></i> الطراز</h4>
                                <p><?= sanitize($report['model']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($report['distinguishing_marks']): ?>
                        <div class="detail-section">
                            <h3><i class="fas fa-fingerprint"></i> علامات مميزة</h3>
                            <p><?= nl2br(sanitize($report['distinguishing_marks'])) ?></p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="privacy-notice">
                        <i class="fas fa-lock"></i>
                        <h3>التفاصيل مخفية للخصوصية</h3>
                        <p>تفاصيل البلاغ مخفية من قبل صاحبه. يمكنك التواصل معه عبر:</p>
                    </div>
                <?php endif; ?>

                <div class="report-contact-info">
                    <h3><i class="fas fa-user"></i> معلومات المبلّغ</h3>
                    <div class="contact-details">
                        <span><i class="fas fa-user-circle"></i> <?= sanitize($report['full_name']) ?></span>
                        <span><i class="fas fa-clock"></i> <?= timeAgo($report['created_at']) ?></span>

                        <?php if ($showContact): ?>
                            <?php if ($hasMatch && !$isOwner): ?>
                                <div class="match-contact-reveal">
                                    <div class="reveal-banner">
                                        <i class="fas fa-unlock-alt"></i>
                                        <span>بيانات التواصل مكشوفة بسبب وجود تطابق</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($report['contact_method'] !== 'email'): ?>
                                <span class="contact-revealed"><i class="fas fa-phone"></i> <?= $report['user_phone'] ?></span>
                            <?php endif; ?>
                            <?php if ($report['contact_method'] !== 'phone'): ?>
                                <span class="contact-revealed"><i class="fas fa-envelope"></i> <?= $report['user_email'] ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="locked"><i class="fas fa-lock"></i> بيانات التواصل مخفية - سيتم كشفها عند وجود مطابقة</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasMatch): ?>
                    <div class="match-contact-actions">
                        <a href="tel:<?= $report['user_phone'] ?>" class="btn btn-success">
                            <i class="fas fa-phone-alt"></i> اتصل الآن
                        </a>
                        <a href="mailto:<?= $report['user_email'] ?>" class="btn btn-info">
                            <i class="fas fa-envelope"></i> أرسل بريداً
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                    <div class="owner-actions">
                        <a href="edit.php?id=<?= $report['id'] ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        <?php if ($report['report_type'] === 'found' && $report['status'] === 'active'): ?>
                            <button class="btn btn-success" onclick="deliverReport(<?= $report['id'] ?>)">
                                <i class="fas fa-hand-holding-heart"></i> تسليم للمالك
                            </button>
                        <?php endif; ?>
                        <?php if ($report['report_type'] === 'lost' && $report['status'] === 'matched'): ?>
                            <a href="delivery.php?id=<?= $report['id'] ?>" class="btn btn-success">
                                <i class="fas fa-check-double"></i> تأكيد الاستلام
                            </a>
                        <?php endif; ?>
                        <?php if ($report['status'] === 'active'): ?>
                            <a href="matches.php?id=<?= $report['id'] ?>" class="btn btn-info">
                                <i class="fas fa-handshake"></i> المطابقات
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($matches)): ?>
        <div class="matches-section">
            <h2><i class="fas fa-magic"></i> مطابقات مقترحة (<?= count($matches) ?>)</h2>
            <div class="matches-grid">
                <?php foreach ($matches as $match): ?>
                    <div class="match-card">
                        <div class="match-score">
                            <div class="score-circle" data-score="<?= $match['match_score'] ?>">
                                <?= $match['match_score'] ?>%
                            </div>
                        </div>
                        <div class="match-info">
                            <h4><a href="view.php?id=<?= $match['id'] ?>"><?= sanitize($match['title']) ?></a></h4>
                            <p><i class="fas fa-info-circle"></i> <?= sanitize($match['match_reason']) ?></p>
                            <span class="badge badge-info"><?= getReportTypeText($match['report_type']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deliverReport(id) {
    if (confirm('هل تريد إرسال طلب تسليم لصاحب المفقود؟')) {
        fetch('../api/delivery.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'request_delivery', report_id: id})
        }).then(r => r.json()).then(data => {
            if (data.success) {
                alert('تم إرسال طلب التسليم لصاحب المفقود!');
                location.reload();
            } else {
                alert(data.error || 'حدث خطأ');
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
