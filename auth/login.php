<?php
$pageTitle = 'تسجيل الدخول';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'أدخل البريد الإلكتروني وكلمة المرور';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_deleted']) {
                $pdo->prepare("UPDATE users SET is_deleted = 0, deleted_at = NULL WHERE id = ?")->execute([$user['id']]);
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];

            setFlash('success', 'مرحباً ' . $user['full_name'] . '!');
            redirect(SITE_URL);
        } else {
            $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-sign-in-alt"></i>
            <h2>تسجيل الدخول</h2>
            <p>ادخل إلى حسابك للمتابعة</p>
        </div>

        <a href="google_login.php" class="google-btn">
            <svg viewBox="0 0 24 24" width="20" height="20" style="margin-left:10px;">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            تسجيل الدخول بحساب جوجل
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
                <label><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
                <input type="email" name="email" value="<?= sanitize($email ?? '') ?>" required placeholder="example@email.com" dir="ltr">
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> كلمة المرور</label>
                <input type="password" name="password" required placeholder="أدخل كلمة المرور">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> دخول
            </button>
        </form>

        <div class="auth-footer">
            <p>ليس لديك حساب؟ <a href="register.php">سجل الآن مجاناً</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
