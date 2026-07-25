<?php
$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/email.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_deleted = 0");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'حدث خطأ');
    redirect(SITE_URL);
}

$pref_stmt = $pdo->prepare("SELECT * FROM email_preferences WHERE user_id = ?");
$pref_stmt->execute([$user_id]);
$preferences = $pref_stmt->fetch();

if (!$preferences) {
    $pdo->prepare("INSERT IGNORE INTO email_preferences (user_id) VALUES (?)")->execute([$user_id]);
    $pref_stmt->execute([$user_id]);
    $preferences = $pref_stmt->fetch();
}

$email_stats = ['sent' => 0, 'failed' => 0];
try {
    $stats_stmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt FROM email_log 
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    $stats_stmt->execute([$user_id]);
    foreach ($stats_stmt->fetchAll() as $row) {
        $email_stats[$row['status']] = $row['cnt'];
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $contact_method = $_POST['contact_method'] ?? 'both';

        if (empty($full_name)) {
            setFlash('error', 'الاسم الكامل مطلوب');
            redirect('index.php');
        }

        $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, contact_method = ? WHERE id = ?")
            ->execute([$full_name, $phone, $contact_method, $user_id]);

        $_SESSION['full_name'] = $full_name;
        setFlash('success', 'تم تحديث الملف الشخصي بنجاح');
        redirect('index.php');
    }

    if ($section === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) {
            setFlash('error', 'أدخل كلمة المرور الحالية');
            redirect('index.php#password');
        }
        if (!password_verify($current_password, $user['password'])) {
            setFlash('error', 'كلمة المرور الحالية غير صحيحة');
            redirect('index.php#password');
        }
        if ($new_password !== $confirm_password) {
            setFlash('error', 'كلمتا المرور الجديدتان غير متطابقتين');
            redirect('index.php#password');
        }
        if (strlen($new_password) < 6) {
            setFlash('error', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
            redirect('index.php#password');
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);

        setFlash('success', 'تم تغيير كلمة المرور بنجاح');
        redirect('index.php');
    }

    if ($section === 'email_preferences') {
        $pdo->prepare("
            UPDATE email_preferences SET
                email_on_match = ?,
                email_on_new_found = ?,
                email_on_delivery = ?,
                email_on_delivery_confirmed = ?,
                email_on_status = ?,
                email_on_system = ?
            WHERE user_id = ?
        ")->execute([
            isset($_POST['email_on_match']) ? 1 : 0,
            isset($_POST['email_on_new_found']) ? 1 : 0,
            isset($_POST['email_on_delivery']) ? 1 : 0,
            isset($_POST['email_on_delivery_confirmed']) ? 1 : 0,
            isset($_POST['email_on_status']) ? 1 : 0,
            isset($_POST['email_on_system']) ? 1 : 0,
            $user_id
        ]);

        setFlash('success', 'تم تحديث إعدادات البريد الإلكتروني');
        redirect('index.php');
    }
}

$active_tab = 'info';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
} elseif (isset($_SESSION['flash']) && strpos($_SESSION['flash']['message'] ?? '', 'كلمة المرور') !== false) {
    $active_tab = 'password';
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.profile-layout { display: grid; grid-template-columns: 260px 1fr; gap: 24px; align-items: start; }
.profile-sidebar {
    background: white; border-radius: 12px; padding: 24px; text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06); position: sticky; top: 80px;
}
.profile-avatar {
    width: 90px; height: 90px; border-radius: 50%; background: var(--primary);
    color: white; display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; margin: 0 auto 12px; font-weight: 800;
}
.profile-sidebar h3 { margin: 0 0 4px; color: var(--dark); }
.profile-sidebar p { color: var(--gray); font-size: 0.85rem; margin: 0; }
.profile-nav { margin-top: 16px; text-align: right; }
.profile-nav a {
    display: flex; align-items: center; gap: 8px; padding: 10px 14px;
    color: #64748b; text-decoration: none; border-radius: 8px;
    font-size: 0.9rem; transition: all 0.2s;
}
.profile-nav a:hover, .profile-nav a.active { background: #eff6ff; color: var(--primary); }
.profile-content {
    background: white; border-radius: 12px; padding: 28px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.profile-section { display: none; }
.profile-section.active { display: block; }
.profile-section h2 {
    font-size: 1.15rem; margin: 0 0 20px; display: flex; align-items: center; gap: 8px;
    padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;
}
.profile-section h2 i { color: var(--primary); }
.pref-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 0; border-bottom: 1px solid #f1f5f9;
}
.pref-row:last-child { border-bottom: none; }
.pref-info h4 { margin: 0 0 3px; font-size: 0.9rem; }
.pref-info p { margin: 0; color: #64748b; font-size: 0.8rem; }
.toggle-switch {
    position: relative; width: 44px; height: 24px; flex-shrink: 0;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; cursor: pointer; inset: 0;
    background: #cbd5e1; border-radius: 24px; transition: 0.3s;
}
.toggle-slider:before {
    content: ''; position: absolute; height: 18px; width: 18px;
    left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s;
}
.toggle-switch input:checked + .toggle-slider { background: var(--primary); }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
.email-stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
.email-stat-box {
    padding: 16px; border-radius: 10px; text-align: center;
}
.email-stat-box .stat-num { font-size: 1.8rem; font-weight: 800; display: block; line-height: 1.2; }
.email-stat-box .stat-label { font-size: 0.8rem; opacity: 0.8; }
@media (max-width: 768px) {
    .profile-layout { grid-template-columns: 1fr; }
    .profile-sidebar { position: static; }
}
body.dark .profile-sidebar, body.dark .profile-content { background: #1e293b; }
body.dark .profile-sidebar h3 { color: #e2e8f0; }
body.dark .profile-section h2 { color: #e2e8f0; border-bottom-color: #334155; }
body.dark .pref-row { border-bottom-color: #334155; }
body.dark .pref-info h4 { color: #e2e8f0; }
body.dark .toggle-slider { background: #475569; }
</style>

<div class="container page-container">
    <div class="profile-layout">
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <?= mb_substr($user['full_name'], 0, 1, 'UTF-8') ?>
            </div>
            <h3><?= sanitize($user['full_name']) ?></h3>
            <p><?= sanitize($user['email']) ?></p>
            <div class="profile-nav">
                <a href="#" onclick="showSection('info')" class="<?= $active_tab === 'info' ? 'active' : '' ?>" id="nav-info">
                    <i class="fas fa-user"></i> الملف الشخصي
                </a>
                <a href="#" onclick="showSection('email')" class="<?= $active_tab === 'email' ? 'active' : '' ?>" id="nav-email">
                    <i class="fas fa-envelope"></i> إعدادات البريد
                </a>
                <a href="#" onclick="showSection('password')" class="<?= $active_tab === 'password' ? 'active' : '' ?>" id="nav-password">
                    <i class="fas fa-lock"></i> تغيير كلمة المرور
                </a>
            </div>
        </div>

        <div class="profile-content">
            <div id="section-info" class="profile-section <?= $active_tab === 'info' ? 'active' : '' ?>">
                <h2><i class="fas fa-user"></i> الملف الشخصي</h2>
                <form method="POST">
                    <input type="hidden" name="section" value="profile">
                    <div class="report-form" style="max-width:100%;">
                        <div class="form-group">
                            <label>الاسم الكامل *</label>
                            <input type="text" name="full_name" value="<?= sanitize($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" value="<?= sanitize($user['email']) ?>" disabled style="opacity:0.6;">
                            <small style="color:var(--gray);">لا يمكن تغيير البريد الإلكتروني</small>
                        </div>
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="text" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>طريقة التواصل المفضلة</label>
                            <select name="contact_method">
                                <option value="both" <?= ($user['contact_method'] ?? 'both') === 'both' ? 'selected' : '' ?>>الهاتف والبريد</option>
                                <option value="phone" <?= ($user['contact_method'] ?? '') === 'phone' ? 'selected' : '' ?>>الهاتف فقط</option>
                                <option value="email" <?= ($user['contact_method'] ?? '') === 'email' ? 'selected' : '' ?>>البريد فقط</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>الدور</label>
                            <input type="text" value="<?= $user['role'] === 'admin' ? 'مدير النظام' : 'مستخدم' ?>" disabled style="opacity:0.6;">
                        </div>
                        <div class="form-group">
                            <label>تاريخ التسجيل</label>
                            <input type="text" value="<?= formatDate($user['created_at'], 'ar') ?>" disabled style="opacity:0.6;">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>

            <div id="section-email" class="profile-section <?= $active_tab === 'email' ? 'active' : '' ?>">
                <h2><i class="fas fa-envelope"></i> إعدادات البريد الإلكتروني</h2>

                <div class="email-stats-grid">
                    <div class="email-stat-box" style="background:#dcfce7;color:#166534;">
                        <span class="stat-num"><?= $email_stats['sent'] ?></span>
                        <span class="stat-label">بريد مرسل (30 يوم)</span>
                    </div>
                    <div class="email-stat-box" style="background:#fee2e2;color:#991b1b;">
                        <span class="stat-num"><?= $email_stats['failed'] ?></span>
                        <span class="stat-label">فشل الإرسال (30 يوم)</span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="section" value="email_preferences">

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>مطابقة جديدة مع مفقودي</h4>
                            <p>إشعار بالبريد عند وجود بلاغ عثور يطابق بلاغ مفقودك</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_match" <?= !empty($preferences['email_on_match']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>مطابقة جديدة مع بلاغ عثور</h4>
                            <p>إشعار بالبريد عند وجود بلاغ مفقود يطابق بلاغ عثورك</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_new_found" <?= !empty($preferences['email_on_new_found']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>طلب تسليم</h4>
                            <p>إشعار بالبريد عندما يطلب المبلّغ تسليم المفقود لك</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_delivery" <?= !empty($preferences['email_on_delivery']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>تأكيد الاستلام</h4>
                            <p>إشعار بالبريد عند تأكيد صاحب المفقود استلامه</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_delivery_confirmed" <?= !empty($preferences['email_on_delivery_confirmed']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>تحديثات الحالة</h4>
                            <p>إشعار بالبريد عند أي تحديث على بلاغاتك</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_status" <?= !empty($preferences['email_on_status']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="pref-row">
                        <div class="pref-info">
                            <h4>إشعارات النظام</h4>
                            <p>تحديثات عامة حول المنصة والإشعارات المهمة</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="email_on_system" <?= !empty($preferences['email_on_system']) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التفضيلات
                        </button>
                    </div>
                </form>
            </div>

            <div id="section-password" class="profile-section <?= $active_tab === 'password' ? 'active' : '' ?>">
                <h2><i class="fas fa-lock"></i> تغيير كلمة المرور</h2>
                <form method="POST">
                    <input type="hidden" name="section" value="password">
                    <div class="report-form" style="max-width:100%;">
                        <div class="form-group">
                            <label>كلمة المرور الحالية *</label>
                            <input type="password" name="current_password" required placeholder="أدخل كلمة المرور الحالية">
                        </div>
                        <div class="form-group">
                            <label>كلمة المرور الجديدة * (6 أحرف على الأقل)</label>
                            <input type="password" name="new_password" minlength="6" required placeholder="أدخل كلمة المرور الجديدة">
                        </div>
                        <div class="form-group">
                            <label>تأكيد كلمة المرور الجديدة *</label>
                            <input type="password" name="confirm_password" minlength="6" required placeholder="أعد إدخال كلمة المرور الجديدة">
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> تغيير كلمة المرور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showSection(name) {
    document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.profile-nav a').forEach(a => a.classList.remove('active'));
    document.getElementById('section-' + name).classList.add('active');
    document.getElementById('nav-' + name).classList.add('active');
    return false;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
