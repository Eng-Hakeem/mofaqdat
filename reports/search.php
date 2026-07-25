<?php
$pageTitle = 'بحث';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$search = trim($_GET['q'] ?? '');
$category = intval($_GET['category'] ?? 0);
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? 'active';

$where = "WHERE r.is_deleted = 0";
$params = [];

if ($search) {
    $where .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location_name LIKE ? OR r.color LIKE ? OR r.brand LIKE ? OR r.model LIKE ?)";
    $searchPattern = "%$search%";
    $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
}

if ($category > 0) {
    $where .= " AND r.category_id = ?";
    $params[] = $category;
}

if (in_array($type, ['lost', 'found'])) {
    $where .= " AND r.report_type = ?";
    $params[] = $type;
}

if ($status && $status !== 'all') {
    $where .= " AND r.status = ?";
    $params[] = $status;
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reports r $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT r.*, c.name_ar as category_name, c.icon as category_icon, u.full_name
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    JOIN users u ON r.user_id = u.id
    $where
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$reports = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name_ar")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1><i class="fas fa-search"></i> بحث عن مفقودات ومقتنيات</h1>
    </div>

    <div class="search-panel">
        <form method="GET" class="search-filters">
            <div class="search-bar">
                <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="اكتب كلمة البحث..." class="search-input">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>

            <div class="filters-row">
                <div class="filter-group">
                    <label>الفئة</label>
                    <select name="category">
                        <option value="">جميع الفئات</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name_ar']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>النوع</label>
                    <select name="type">
                        <option value="">الكل</option>
                        <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>مفقود</option>
                        <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>معثور عليه</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>الحالة</label>
                    <select name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>نشط</option>
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>الكل</option>
                        <option value="matched" <?= $status === 'matched' ? 'selected' : '' ?>>مطابق</option>
                        <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>تم الحل</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="search-results">
        <p class="results-count">تم العثور على <?= $total ?> نتيجة</p>

        <?php if (empty($reports)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>لا توجد نتائج</h3>
                <p>جرب تغيير معايير البحث أو تأكد من الإملاء</p>
            </div>
        <?php else: ?>
            <div class="reports-grid">
                <?php foreach ($reports as $report): ?>
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
                            <h3><a href="view.php?id=<?= $report['id'] ?>"><?= sanitize($report['title']) ?></a></h3>
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

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
