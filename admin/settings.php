<?php
$pageTitle = 'الإعدادات العامة';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!isAdmin()) redirect(SITE_URL);

function saveSettings($pdo, $settings) {
    global $site_settings;
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings as $key => $value) {
        $stmt->execute([$value, $key]);
        $site_settings[$key] = $value;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'general') {
        saveSettings($pdo, [
            'site_name' => trim($_POST['site_name'] ?? 'مفقودات'),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'reports_per_page' => intval($_POST['reports_per_page'] ?? 15),
            'max_image_size_mb' => intval($_POST['max_image_size_mb'] ?? 5),
            'auto_expire_days' => intval($_POST['auto_expire_days'] ?? 30),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        ]);
        setFlash('success', 'تم حفظ الإعدادات العامة');
    } elseif ($section === 'matching') {
        saveSettings($pdo, [
            'matching_threshold' => intval($_POST['matching_threshold'] ?? 40),
            'new_found_alert_threshold' => intval($_POST['new_found_alert_threshold'] ?? 30),
        ]);
        setFlash('success', 'تم حفظ إعدادات المطابقة');
    } elseif ($section === 'notifications') {
        saveSettings($pdo, [
            'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? '1' : '0',
        ]);
        setFlash('success', 'تم حفظ إعدادات الإشعارات');
    } elseif ($section === 'integrations') {
        saveSettings($pdo, [
            'enable_google_oauth' => isset($_POST['enable_google_oauth']) ? '1' : '0',
            'google_client_id' => trim($_POST['google_client_id'] ?? ''),
            'google_client_secret' => trim($_POST['google_client_secret'] ?? ''),
        ]);
        setFlash('success', 'تم حفظ إعدادات التكامل');
    }
    redirect('settings.php');
}

$settings = [];
foreach ($site_settings as $key => $value) {
    $settings[$key] = ['setting_value' => $value];
}

$active_tab = $_GET['tab'] ?? 'general';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page">
    <h1><i class="fas fa-cog"></i> الإعدادات العامة</h1>

    <div class="admin-settings-tabs">
        <a href="?tab=general" class="settings-tab <?= $active_tab === 'general' ? 'active' : '' ?>">
            <i class="fas fa-sliders-h"></i> عام
        </a>
        <a href="?tab=matching" class="settings-tab <?= $active_tab === 'matching' ? 'active' : '' ?>">
            <i class="fas fa-magic"></i> المطابقة
        </a>
        <a href="?tab=notifications" class="settings-tab <?= $active_tab === 'notifications' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> الإشعارات
        </a>
        <a href="?tab=integrations" class="settings-tab <?= $active_tab === 'integrations' ? 'active' : '' ?>">
            <i class="fas fa-plug"></i> التكامل
        </a>
    </div>

    <?php if ($active_tab === 'general'): ?>
    <form method="POST">
        <input type="hidden" name="section" value="general">
        <div class="admin-settings-grid">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-globe"></i> معلومات الموقع</h3>
                </div>
                <div style="padding:20px;">
                    <div class="admin-form-group">
                        <label>اسم الموقع</label>
                        <input type="text" name="site_name" value="<?= sanitize($settings['site_name']['setting_value'] ?? 'مفقودات') ?>">
                    </div>
                    <div class="admin-form-group">
                        <label>وصف الموقع</label>
                        <input type="text" name="site_description" value="<?= sanitize($settings['site_description']['setting_value'] ?? '') ?>">
                    </div>
                    <div class="admin-form-group">
                        <label>بريد التواصل</label>
                        <input type="email" name="contact_email" value="<?= sanitize($settings['contact_email']['setting_value'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-sliders-h"></i> إعدادات عامة</h3>
                </div>
                <div style="padding:20px;">
                    <div class="admin-form-group">
                        <label>البلاغات لكل صفحة</label>
                        <input type="number" name="reports_per_page" value="<?= $settings['reports_per_page']['setting_value'] ?? 15 ?>" min="5" max="50">
                    </div>
                    <div class="admin-form-group">
                        <label>حجم الصورة الأقصى (MB)</label>
                        <input type="number" name="max_image_size_mb" value="<?= $settings['max_image_size_mb']['setting_value'] ?? 5 ?>" min="1" max="20">
                    </div>
                    <div class="admin-form-group">
                        <label>مدة انتهاء البلاغ (يوم)</label>
                        <input type="number" name="auto_expire_days" value="<?= $settings['auto_expire_days']['setting_value'] ?? 30 ?>" min="1" max="365">
                    </div>
                    <div class="admin-form-group">
                        <label class="toggle-label">
                            <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode']['setting_value'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            وضع الصيانة
                        </label>
                        <small style="color:var(--gray);">عند التفعيل، لا يمكن للمستخدمين الوصول للموقع</small>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </form>

    <?php elseif ($active_tab === 'matching'): ?>
    <form method="POST">
        <input type="hidden" name="section" value="matching">
        <div class="admin-card" style="max-width:700px;">
            <div class="admin-card-header">
                <h3><i class="fas fa-magic"></i> إعدادات المطابقة الذكية</h3>
            </div>
            <div style="padding:20px;">
                <div class="admin-form-group">
                    <label>حد الإشعار بالمطابقة (%)</label>
                    <input type="number" name="matching_threshold" value="<?= $settings['matching_threshold']['setting_value'] ?? 40 ?>" min="10" max="100">
                    <small style="color:var(--gray);">النسبة الدنيا لإرسال إشعار مطابقة للمستخدم (الافتراضي: 40%)</small>
                </div>
                <div class="admin-form-group">
                    <label>حد إشعار العثور الجديد (%)</label>
                    <input type="number" name="new_found_alert_threshold" value="<?= $settings['new_found_alert_threshold']['setting_value'] ?? 30 ?>" min="10" max="100">
                    <small style="color:var(--gray);">النسبة الدنيا لإشعار المستخدم بوجود بلاغ عثور مشابه (الافتراضي: 30%)</small>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </form>

    <?php elseif ($active_tab === 'notifications'): ?>
    <form method="POST">
        <input type="hidden" name="section" value="notifications">
        <div class="admin-card" style="max-width:700px;">
            <div class="admin-card-header">
                <h3><i class="fas fa-bell"></i> إعدادات الإشعارات والبريد</h3>
            </div>
            <div style="padding:20px;">
                <div class="admin-form-group">
                    <label class="toggle-label">
                        <input type="checkbox" name="enable_email_notifications" value="1" <?= ($settings['enable_email_notifications']['setting_value'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span class="toggle-switch"></span>
                        تفعيل إشعارات البريد الإلكتروني
                    </label>
                    <small style="color:var(--gray);">إرسال بريد إلكتروني للمستخدمين عند حدث مهم (مطابقة، تسليم، إلخ)</small>
                </div>
                <div style="margin-top:20px;padding:15px;background:var(--light);border-radius:8px;">
                    <h4 style="margin:0 0 10px;"><i class="fas fa-info-circle"></i> أنواع الإشعارات المرسلة</h4>
                    <ul style="list-style:none;padding:0;">
                        <li style="padding:6px 0;border-bottom:1px solid var(--gray-light);">
                            <span class="badge badge-success">مطابقة</span> إشعار عند العثور على بلاغ مشابه
                        </li>
                        <li style="padding:6px 0;border-bottom:1px solid var(--gray-light);">
                            <span class="badge badge-info">عثور جديد</span> إشعار لمن فقد شيئاً عند وجود بلاغ عثور
                        </li>
                        <li style="padding:6px 0;border-bottom:1px solid var(--gray-light);">
                            <span class="badge badge-primary">طلب تسليم</span> إشعار لصاحب المفقود عند طلب تسليم
                        </li>
                        <li style="padding:6px 0;">
                            <span class="badge badge-warning">تأكيد تسليم</span> إشعار للمعثور عند تأكيد التسليم
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </form>

    <?php elseif ($active_tab === 'integrations'): ?>
    <form method="POST">
        <input type="hidden" name="section" value="integrations">
        <div class="admin-settings-grid">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fab fa-google"></i> تسجيل دخول جوجل</h3>
                </div>
                <div style="padding:20px;">
                    <div class="admin-form-group">
                        <label class="toggle-label">
                            <input type="checkbox" name="enable_google_oauth" value="1" <?= ($settings['enable_google_oauth']['setting_value'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            تفعيل تسجيل الدخول عبر جوجل
                        </label>
                    </div>
                    <div class="admin-form-group">
                        <label>Google Client ID</label>
                        <input type="text" name="google_client_id" value="<?= sanitize($settings['google_client_id']['setting_value'] ?? '') ?>" placeholder="xxxxxxxxxxxx.apps.googleusercontent.com">
                    </div>
                    <div class="admin-form-group">
                        <label>Google Client Secret</label>
                        <input type="password" name="google_client_secret" value="<?= sanitize($settings['google_client_secret']['setting_value'] ?? '') ?>" placeholder="GOCSPX-xxxxxx">
                    </div>
                    <div style="margin-top:10px;padding:12px;background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;font-size:0.85rem;">
                        <i class="fas fa-info-circle" style="color:var(--primary);"></i>
                        للحصول على هذه المفاتيح، أنشئ مشروع في
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>
                        وفعّل OAuth 2.0
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-server"></i> معلومات الخادم</h3>
                </div>
                <div style="padding:20px;">
                    <table style="width:100%;font-size:0.9rem;">
                        <tr style="border-bottom:1px solid var(--gray-light);">
                            <td style="padding:8px 0;color:var(--gray);">إصدار PHP</td>
                            <td style="padding:8px 0;font-weight:600;"><?= phpversion() ?></td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--gray-light);">
                            <td style="padding:8px 0;color:var(--gray);">إصدار MySQL</td>
                            <td style="padding:8px 0;font-weight:600;"><?= $pdo->query("SELECT VERSION()")->fetchColumn() ?></td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--gray-light);">
                            <td style="padding:8px 0;color:var(--gray);">الحد الأقصى للرفع</td>
                            <td style="padding:8px 0;font-weight:600;"><?= ini_get('upload_max_filesize') ?></td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:var(--gray);">اسم المستخدم الحالي</td>
                            <td style="padding:8px 0;font-weight:600;"><?= sanitize($_SESSION['full_name']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;"><i class="fas fa-save"></i> حفظ الإعدادات</button>
    </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
