<?php
$pageTitle = 'بلاغ جديد';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name_ar")->fetchAll();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $category_id = intval($_POST['category_id'] ?? 0);
    $report_type = $_POST['report_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location_name = trim($_POST['location_name'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $date_occurred = $_POST['date_occurred'] ?? '';
    $time_occurred = $_POST['time_occurred'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $distinguishing_marks = trim($_POST['distinguishing_marks'] ?? '');
    $contact_method = $_POST['contact_method'] ?? 'both';
    $reward_amount = floatval($_POST['reward_amount'] ?? 0);
    $privacy_level = $_POST['privacy_level'] ?? 'limited';

    if (empty($title)) $errors[] = 'عنوان البلاغ مطلوب';
    if (empty($description)) $errors[] = 'وصف البلاغ مطلوب';
    if (!in_array($report_type, ['lost', 'found'])) $errors[] = 'نوع البلاغ غير صحيح';
    if ($category_id <= 0) $errors[] = 'اختر فئة';

    $image_filename = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $image_filename = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO reports (user_id, category_id, report_type, title, description, location_name, latitude, longitude, date_occurred, time_occurred, image, color, brand, model, distinguishing_marks, contact_method, reward_amount, privacy_level)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'], $category_id, $report_type, $title, $description,
            $location_name, $latitude ?: null, $longitude ?: null,
            $date_occurred ?: null, $time_occurred ?: null, $image_filename,
            $color, $brand, $model, $distinguishing_marks,
            $contact_method, $reward_amount, $privacy_level
        ]);

        $report_id = $pdo->lastInsertId();

        runMatchingEngine($report_id);

        setFlash('success', 'تم نشر البلاغ بنجاح!');
        redirect(SITE_URL . '/reports/view.php?id=' . $report_id);
    }
}

function runMatchingEngine($report_id) {
    global $pdo;

    require_once __DIR__ . '/../includes/notifications.php';

    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) return;

    $opposite_type = $report['report_type'] === 'lost' ? 'found' : 'lost';

    $stmt = $pdo->prepare("
        SELECT * FROM reports 
        WHERE report_type = ? AND status = 'active' AND id != ? AND category_id = ? AND is_deleted = 0
        ORDER BY created_at DESC LIMIT 50
    ");
    $stmt->execute([$opposite_type, $report_id, $report['category_id']]);
    $candidates = $stmt->fetchAll();

    foreach ($candidates as $candidate) {
        $match = generateMatchScore($report, $candidate);

        if ($match['score'] >= 40) {
            $lost_id = $report['report_type'] === 'lost' ? $report_id : $candidate['id'];
            $found_id = $report['report_type'] === 'found' ? $report_id : $candidate['id'];

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO matches (lost_report_id, found_report_id, match_score, match_reason)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$lost_id, $found_id, $match['score'], implode(', ', $match['reasons'])]);
            $match_id = $pdo->lastInsertId();

            if ($match_id) {
                $stmt2 = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
                $stmt2->execute([$lost_id]);
                $lost_report = $stmt2->fetch();
                $stmt2 = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
                $stmt2->execute([$found_id]);
                $found_report = $stmt2->fetch();

                if ($lost_report && $found_report) {
                    notifyMatchFound($match_id, $lost_report, $found_report);
                }
            }
        }
    }

    if ($report['report_type'] === 'lost') {
        $stmt = $pdo->prepare("
            SELECT r.*, u.id as owner_id, u.full_name as owner_name
            FROM reports r
            JOIN users u ON r.user_id = u.id
            WHERE r.report_type = 'found' AND r.status = 'active' AND r.category_id = ?
            AND r.user_id != ? AND r.is_deleted = 0 AND u.is_deleted = 0
            ORDER BY r.created_at DESC LIMIT 10
        ");
        $stmt->execute([$report['category_id'], $report['user_id']]);
        $found_reports = $stmt->fetchAll();

        foreach ($found_reports as $found) {
            $score = generateMatchScore($report, $found);
            if ($score['score'] >= 30) {
                $already_notified = $pdo->prepare("
                    SELECT id FROM notifications 
                    WHERE user_id = ? AND report_id = ? AND matched_report_id = ? AND type = 'new_found'
                    LIMIT 1
                ");
                $already_notified->execute([$report['user_id'], $report['id'], $found['id']]);
                if (!$already_notified->fetch()) {
                    notifyNewFoundSimilar($report['user_id'], $report['id'], $found);
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> بلاغ جديد</h1>
        <p>أدخل تفاصيل المفقود أو المعثور عليه</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="report-form">
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> نوع البلاغ *</label>
                <div class="radio-group">
                    <label class="radio-option <?= ($old['report_type'] ?? '') === 'lost' ? 'active' : '' ?>">
                        <input type="radio" name="report_type" value="lost" <?= ($old['report_type'] ?? $_GET['type'] ?? '') === 'lost' ? 'checked' : '' ?> required>
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>مفقود</span>
                    </label>
                    <label class="radio-option <?= ($old['report_type'] ?? '') === 'found' ? 'active' : '' ?>">
                        <input type="radio" name="report_type" value="found" <?= ($old['report_type'] ?? $_GET['type'] ?? '') === 'found' ? 'checked' : '' ?> required>
                        <i class="fas fa-check-circle"></i>
                        <span>معثور عليه</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-folder"></i> الفئة *</label>
                <select name="category_id" required>
                    <option value="">اختر الفئة</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cat['name_ar']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label><i class="fas fa-heading"></i> عنوان البلاغ *</label>
                <input type="text" name="title" value="<?= sanitize($old['title'] ?? '') ?>" required placeholder="مثال: هاتف آيفون مفقود بالقرب من محطة القطار">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label><i class="fas fa-align-right"></i> الوصف التفصيلي *</label>
                <textarea name="description" required rows="5" placeholder="اشرح تفاصيل المفقود أو المعثور عليه..."><?= sanitize($old['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> الموقع</label>
                <input type="text" name="location_name" value="<?= sanitize($old['location_name'] ?? '') ?>" placeholder="مثال: محطة القطار، شارع محمد الخامس">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> تاريخ الفقد/العثور</label>
                <input type="date" name="date_occurred" value="<?= $old['date_occurred'] ?? '' ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-clock"></i> الوقت التقريبي</label>
                <input type="time" name="time_occurred" value="<?= $old['time_occurred'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-palette"></i> اللون</label>
                <input type="text" name="color" value="<?= sanitize($old['color'] ?? '') ?>" placeholder="مثال: أسود، أزرق">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> العلامة التجارية</label>
                <input type="text" name="brand" value="<?= sanitize($old['brand'] ?? '') ?>" placeholder="مثال: Apple, Samsung">
            </div>
            <div class="form-group">
                <label><i class="fas fa-mobile-alt"></i> الطراز/الموديل</label>
                <input type="text" name="model" value="<?= sanitize($old['model'] ?? '') ?>" placeholder="مثال: iPhone 15 Pro">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label><i class="fas fa-fingerprint"></i> علامات مميزة</label>
                <textarea name="distinguishing_marks" rows="3" placeholder="مثال: خدش على الشاشة، غلاف أزرق، ملصق على الخلفية"><?= sanitize($old['distinguishing_marks'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-camera"></i> صورة (اختياري)</label>
                <input type="file" name="image" accept="image/*" class="file-input">
                <small>الحد الأقصى: 5 ميجابايت (JPG, PNG, GIF)</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-coins"></i> مكافأة (اختياري)</label>
                <input type="number" name="reward_amount" value="<?= $old['reward_amount'] ?? '0' ?>" min="0" step="0.01" placeholder="0">
                <small>درهم</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-share-alt"></i> طريقة التواصل</label>
                <select name="contact_method">
                    <option value="both" <?= ($old['contact_method'] ?? '') === 'both' ? 'selected' : '' ?>>الهاتف والبريد</option>
                    <option value="phone" <?= ($old['contact_method'] ?? '') === 'phone' ? 'selected' : '' ?>>الهاتف فقط</option>
                    <option value="email" <?= ($old['contact_method'] ?? '') === 'email' ? 'selected' : '' ?>>البريد فقط</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-shield-alt"></i> مستوى الخصوصية</label>
                <select name="privacy_level">
                    <option value="full" <?= ($old['privacy_level'] ?? '') === 'full' ? 'selected' : '' ?>>مفتوح للجميع</option>
                    <option value="limited" <?= ($old['privacy_level'] ?? 'limited') === 'limited' ? 'selected' : '' ?>>محدود (الأساسي فقط)</option>
                    <option value="hidden" <?= ($old['privacy_level'] ?? '') === 'hidden' ? 'selected' : '' ?>>مخفي (يُكشف عند التحقق)</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane"></i> نشر البلاغ
            </button>
            <a href="<?= SITE_URL ?>" class="btn btn-outline btn-lg">إلغاء</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
