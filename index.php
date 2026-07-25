<?php
$pageTitle = 'الرئيسية';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$recent_reports = $pdo->query("
    SELECT r.*, c.name_ar as category_name, c.icon as category_icon, u.full_name
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status = 'active' AND r.is_deleted = 0 AND u.is_deleted = 0
    ORDER BY r.created_at DESC
    LIMIT 12
")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM reports WHERE report_type = 'lost' AND status = 'active' AND is_deleted = 0) as total_lost,
        (SELECT COUNT(*) FROM reports WHERE report_type = 'found' AND status = 'active' AND is_deleted = 0) as total_found,
        (SELECT COUNT(*) FROM reports WHERE status = 'matched' AND is_deleted = 0) as total_matched,
        (SELECT COUNT(*) FROM reports WHERE status = 'resolved' AND is_deleted = 0) as total_resolved
")->fetch();

$categories = $pdo->query("SELECT c.*, 
    (SELECT COUNT(*) FROM reports WHERE category_id = c.id AND status = 'active' AND is_deleted = 0) as report_count
    FROM categories c ORDER BY report_count DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>ساعدنا نلقى <span style="color:#fbbf24;">مفقوداتك</span></h1>
        <p>منصة ذكية تربط بين المفقودين والمعثور عليهم باستخدام تقنيات الذكاء الاصطناعي</p>

        <div class="hero-search">
            <form action="reports/search.php" method="GET" class="search-form-large">
                <input type="text" name="q" placeholder="ابحث عن مفقود أو معثور عليه..." class="search-input-lg">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> بحث
                </button>
            </form>
        </div>

        <div class="hero-actions">
            <a href="reports/create.php?type=lost" class="btn btn-danger btn-lg">
                <i class="fas fa-exclamation-triangle"></i> بلاغ مفقود
            </a>
            <a href="reports/create.php?type=found" class="btn btn-success btn-lg">
                <i class="fas fa-check-circle"></i> بلاغ معثور عليه
            </a>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card stat-lost">
                <i class="fas fa-exclamation-circle"></i>
                <span class="stat-number"><?= $stats['total_lost'] ?></span>
                <span class="stat-label">مفقود نشط</span>
            </div>
            <div class="stat-card stat-found">
                <i class="fas fa-check-circle"></i>
                <span class="stat-number"><?= $stats['total_found'] ?></span>
                <span class="stat-label">معثور عليه</span>
            </div>
            <div class="stat-card stat-matched">
                <i class="fas fa-handshake"></i>
                <span class="stat-number"><?= $stats['total_matched'] ?></span>
                <span class="stat-label">تمت المطابقة</span>
            </div>
            <div class="stat-card stat-resolved">
                <i class="fas fa-heart"></i>
                <span class="stat-number"><?= $stats['total_resolved'] ?></span>
                <span class="stat-label">تم التسليم</span>
            </div>
        </div>
    </div>
</section>

<section class="categories-section">
    <div class="container">
        <h2 class="section-title">تصفح حسب الفئة</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="reports/search.php?category=<?= $cat['id'] ?>" class="category-card">
                    <i class="fas <?= $cat['icon'] ?>"></i>
                    <h3><?= sanitize($cat['name_ar']) ?></h3>
                    <span class="badge"><?= $cat['report_count'] ?> بلاغ</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($recent_reports)): ?>
<section class="reports-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">أحدث البلاغات</h2>
            <a href="reports/search.php" class="btn btn-outline">عرض الكل</a>
        </div>
        <div class="reports-grid">
            <?php foreach ($recent_reports as $report): ?>
                <div class="report-card">
                    <div class="report-image">
                        <?php if ($report['image']): ?>
                            <img src="<?= SITE_URL ?>/uploads/<?= $report['image'] ?>" alt="<?= sanitize($report['title']) ?>">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas <?= $report['category_icon'] ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="report-type-badge badge-<?= $report['report_type'] ?>">
                            <?= getReportTypeText($report['report_type']) ?>
                        </span>
                    </div>
                    <div class="report-info">
                        <span class="report-category">
                            <i class="fas <?= $report['category_icon'] ?>"></i>
                            <?= sanitize($report['category_name']) ?>
                        </span>
                        <h3><a href="reports/view.php?id=<?= $report['id'] ?>"><?= sanitize($report['title']) ?></a></h3>
                        <p class="report-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= sanitize($report['location_name'] ?? 'غير محدد') ?>
                        </p>
                        <p class="report-date">
                            <i class="fas fa-clock"></i>
                            <?= timeAgo($report['created_at']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-section">
    <div class="container">
        <h2>وجدت شيئاً؟ لا تتردد في الإبلاغ</h2>
        <p>مساعدتك قد تعيد شخصاً لعائلته أو تعيد ملكيته لصاحبها</p>
        <a href="reports/create.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle"></i> أضف بلاغاً الآن
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
