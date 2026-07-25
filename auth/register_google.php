<?php
$pageTitle = 'إكمال التسجيل';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect(SITE_URL);

if (!isset($_SESSION['google_register'])) {
    setFlash('error', 'انتهت جلسة التسجيل. حاول مرة أخرى.');
    redirect(SITE_URL . '/auth/login.php');
}

$google_data = $_SESSION['google_register'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone)) {
        $errors[] = 'رقم الهاتف مطلوب';
    }

    if (empty($errors)) {
        $hashed_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, phone, password, google_id, avatar_url, is_verified, verification_token)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $google_data['full_name'],
            $google_data['email'],
            $phone,
            $hashed_password,
            $google_data['google_id'],
            $google_data['avatar_url'],
            generateToken()
        ]);

        $user_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT IGNORE INTO email_preferences (user_id) VALUES (?)")->execute([$user_id]);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $google_data['full_name'];
        $_SESSION['email'] = $google_data['email'];
        $_SESSION['role'] = 'user';
        $_SESSION['avatar'] = '';

        unset($_SESSION['google_register']);

        setFlash('success', 'مرحباً ' . $google_data['full_name'] . '! تم إنشاء حسابك بنجاح عبر جوجل.');
        redirect(SITE_URL);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="google-avatar-circle">
                <svg viewBox="0 0 24 24" width="30" height="30">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            </div>
            <h2>إكمال التسجيل</h2>
            <p>حسابك مرتبط بجوجل. أضف رقم الهاتف لإتمام التسجيل</p>
        </div>

        <div class="google-info-box">
            <img src="<?= sanitize($google_data['avatar_url']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;margin-bottom:6px;display:block;margin:0 auto 6px;">
            <strong><?= sanitize($google_data['full_name']) ?></strong><br>
            <span style="color:#64748b;font-size:0.85rem;"><?= sanitize($google_data['email']) ?></span>
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
                <label><i class="fas fa-phone"></i> رقم الهاتف *</label>
                <input type="tel" name="phone" value="<?= sanitize($phone ?? '') ?>" required placeholder="0600000000" dir="ltr">
                <small style="color:var(--gray);">مطلوب للتواصل عند العثور على المفقودات</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-check-circle"></i> إنشاء الحساب
            </button>
        </form>

        <div class="auth-footer">
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
