<?php
$pageTitle = 'تعديل البلاغ';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) redirect(SITE_URL . '/reports/my-reports.php');

$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('error', 'البلاغ غير موجود أو ليس لديك صلاحية التعديل');
    redirect(SITE_URL . '/reports/my-reports.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name_ar")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location_name = trim($_POST['location_name'] ?? '');
    $date_occurred = $_POST['date_occurred'] ?? '';
    $time_occurred = $_POST['time_occurred'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $distinguishing_marks = trim($_POST['distinguishing_marks'] ?? '');
    $contact_method = $_POST['contact_method'] ?? 'both';
    $privacy_level = $_POST['privacy_level'] ?? 'limited';

    if (empty($title)) $errors[] = 'العنوان مطلوب';
    if (empty($description)) $errors[] = 'الوصف مطلوب';

    $image_filename = $report['image'];
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
            UPDATE reports SET title=?, description=?, location_name=?, date_occurred=?, time_occurred=?,
            image=?, color=?, brand=?, model=?, distinguishing_marks=?, contact_method=?, privacy_level=?
            WHERE id=? AND user_id=?
        ");
        $stmt->execute([
            $title, $description, $location_name, $date_occurred ?: null, $time_occurred ?: null,
            $image_filename, $color, $brand, $model, $distinguishing_marks,
            $contact_method, $privacy_level, $id, $_SESSION['user_id']
        ]);

        setFlash('success', 'تم تحديث البلاغ بنجاح!');
        redirect(SITE_URL . '/reports/view.php?id=' . $id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page-container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> تعديل البلاغ</h1>
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
            <div class="form-group full-width">
                <label><i class="fas fa-heading"></i> العنوان *</label>
                <input type="text" name="title" value="<?= sanitize($report['title']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label><i class="fas fa-align-right"></i> الوصف *</label>
                <textarea name="description" required rows="5"><?= sanitize($report['description']) ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> الموقع</label>
                <input type="text" name="location_name" value="<?= sanitize($report['location_name']) ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> التاريخ</label>
                <input type="date" name="date_occurred" value="<?= $report['date_occurred'] ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-clock"></i> الوقت</label>
                <input type="time" name="time_occurred" value="<?= $report['time_occurred'] ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-palette"></i> اللون</label>
                <input type="text" name="color" value="<?= sanitize($report['color']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> العلامة التجارية</label>
                <input type="text" name="brand" value="<?= sanitize($report['brand']) ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-mobile-alt"></i> الطراز</label>
                <input type="text" name="model" value="<?= sanitize($report['model']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group full-width">
                <label><i class="fas fa-fingerprint"></i> علامات مميزة</label>
                <textarea name="distinguishing_marks" rows="3"><?= sanitize($report['distinguishing_marks']) ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-camera"></i> صورة جديدة (اترك فارغ للاحتفاظ بالحالية)</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label><i class="fas fa-share-alt"></i> طريقة التواصل</label>
                <select name="contact_method">
                    <option value="both" <?= $report['contact_method'] === 'both' ? 'selected' : '' ?>>الهاتف والبريد</option>
                    <option value="phone" <?= $report['contact_method'] === 'phone' ? 'selected' : '' ?>>الهاتف فقط</option>
                    <option value="email" <?= $report['contact_method'] === 'email' ? 'selected' : '' ?>>البريد فقط</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-shield-alt"></i> مستوى الخصوصية</label>
                <select name="privacy_level">
                    <option value="full" <?= $report['privacy_level'] === 'full' ? 'selected' : '' ?>>مفتوح للجميع</option>
                    <option value="limited" <?= $report['privacy_level'] === 'limited' ? 'selected' : '' ?>>محدود</option>
                    <option value="hidden" <?= $report['privacy_level'] === 'hidden' ? 'selected' : '' ?>>مخفي (يُكشف عند التحقق)</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> حفظ التعديلات
            </button>
            <a href="view.php?id=<?= $id ?>" class="btn btn-outline btn-lg">إلغاء</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
