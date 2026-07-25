<?php
$pageTitle = 'التسجيل';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($full_name)) $errors[] = 'الاسم الكامل مطلوب';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (empty($phone)) $errors[] = 'رقم الهاتف مطلوب';
    if (strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    if ($password !== $password_confirm) $errors[] = 'كلمتا المرور غير متطابقتين';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مسجل مسبقاً';
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $token = generateToken();

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, verification_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $token]);

        $user_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT IGNORE INTO email_preferences (user_id) VALUES (?)")->execute([$user_id]);

        setFlash('success', 'تم التسجيل بنجاح! يمكنك تسجيل الدخول الآن.');
        redirect(SITE_URL . '/auth/login.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-user-plus"></i>
            <h2>إنشاء حساب جديد</h2>
            <p>انضم إلى منصة <?= sanitize(getSetting('site_name', SITE_NAME)) ?> لمساعدة المجتمع في إيجاد المفقودات</p>
        </div>

        <a href="google_login.php" class="google-btn">
            <svg viewBox="0 0 24 24" width="20" height="20" style="margin-left:10px;">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            التسجيل بحساب جوجل
        </a>

        <div class="auth-divider">
            <span>أو</span>
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

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label><i class="fas fa-user"></i> الاسم الكامل</label>
                <input type="text" name="full_name" value="<?= sanitize($full_name ?? '') ?>" required placeholder="أدخل اسمك الكامل">
            </div>

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= sanitize($email ?? '') ?>" required placeholder="example@email.com" dir="ltr">
            </div>

            <div class="form-group">
                <label><i class="fas fa-phone"></i> رقم الهاتف</label>
                <input type="tel" name="phone" value="<?= sanitize($phone ?? '') ?>" required placeholder="0600000000" dir="ltr">
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> كلمة المرور</label>
                <input type="password" name="password" required placeholder="6 أحرف على الأقل">
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> تأكيد كلمة المرور</label>
                <input type="password" name="password_confirm" required placeholder="أعد إدخال كلمة المرور">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> التسجيل
            </button>
        </form>

        <div class="auth-footer">
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
