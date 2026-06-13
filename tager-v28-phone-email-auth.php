<?php
/**
 * Plugin Name: Tager V28 Phone or Email Authentication
 * Description: Optional email registration and sign-in by Egyptian mobile number or email for customers and vendors.
 * Version: 28.0.0
 */
if (!defined('ABSPATH')) exit;

class Tager_V28_Phone_Email_Auth {
    const VERSION = '28.0.0';

    public static function init() {
        add_action('init', [__CLASS__, 'replace_forms'], 120);
        add_action('admin_post_nopriv_tager_customer_register', [__CLASS__, 'customer_register'], 1);
        add_action('admin_post_tager_customer_register', [__CLASS__, 'customer_register'], 1);
        add_action('admin_post_nopriv_tager_vendor_apply', [__CLASS__, 'vendor_register'], 1);
        add_action('admin_post_tager_vendor_apply', [__CLASS__, 'vendor_register'], 1);
        add_filter('authenticate', [__CLASS__, 'authenticate_by_phone'], 5, 3);
        add_filter('gettext', [__CLASS__, 'login_labels'], 20, 3);
        add_action('wp_enqueue_scripts', [__CLASS__, 'styles'], 120);
    }

    private static function t($ar, $en) {
        return (isset($_GET['lang']) && $_GET['lang'] === 'en') ? $en : $ar;
    }

    private static function url($slug) {
        $p = get_page_by_path($slug);
        return $p ? get_permalink($p) : home_url('/' . trim($slug, '/') . '/');
    }

    public static function replace_forms() {
        remove_shortcode('tager_customer_register');
        remove_shortcode('tager_vendor_register');
        add_shortcode('tager_customer_register', [__CLASS__, 'customer_form']);
        add_shortcode('tager_vendor_register', [__CLASS__, 'vendor_form']);

        // Replace the original handlers loaded by the core Tager module.
        if (class_exists('Tager_Marketplace_Complete')) {
            remove_action('admin_post_nopriv_tager_customer_register', ['Tager_Marketplace_Complete', 'handle_customer_register']);
            remove_action('admin_post_tager_customer_register', ['Tager_Marketplace_Complete', 'handle_customer_register']);
            remove_action('admin_post_nopriv_tager_vendor_apply', ['Tager_Marketplace_Complete', 'handle_vendor_apply']);
            remove_action('admin_post_tager_vendor_apply', ['Tager_Marketplace_Complete', 'handle_vendor_apply']);
        }
    }

    private static function normalize_phone($phone) {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if (strpos($phone, '0020') === 0) $phone = substr($phone, 4);
        if (strpos($phone, '20') === 0 && strlen($phone) === 12) $phone = substr($phone, 2);
        if (strlen($phone) === 10 && strpos($phone, '1') === 0) $phone = '0' . $phone;
        return $phone;
    }

    private static function valid_phone($phone) {
        return (bool) preg_match('/^01[0125][0-9]{8}$/', $phone);
    }

    private static function user_by_phone($phone) {
        $phone = self::normalize_phone($phone);
        if (!$phone) return false;
        $users = get_users([
            'number' => 1,
            'count_total' => false,
            'meta_key' => 'phone_normalized',
            'meta_value' => $phone,
        ]);
        if (!$users) {
            $users = get_users([
                'number' => 1,
                'count_total' => false,
                'meta_key' => 'phone',
                'meta_value' => $phone,
            ]);
        }
        return $users ? $users[0] : false;
    }

    private static function error_box() {
        if (empty($_GET['auth_error'])) return '';
        $messages = [
            'contact_required' => self::t('أدخل رقم الهاتف أو البريد الإلكتروني.', 'Enter a phone number or email address.'),
            'phone_invalid' => self::t('رقم الهاتف المصري غير صحيح. مثال: 01012345678', 'Enter a valid Egyptian mobile number, e.g. 01012345678.'),
            'phone_exists' => self::t('رقم الهاتف مستخدم في حساب آخر.', 'This phone number is already registered.'),
            'email_invalid' => self::t('البريد الإلكتروني غير صحيح.', 'Enter a valid email address.'),
            'email_exists' => self::t('البريد الإلكتروني مستخدم في حساب آخر.', 'This email is already registered.'),
            'password' => self::t('كلمة المرور يجب ألا تقل عن 8 أحرف.', 'Password must be at least 8 characters.'),
            'create_failed' => self::t('تعذر إنشاء الحساب. راجع البيانات وحاول مرة أخرى.', 'The account could not be created. Check the details and try again.'),
        ];
        $key = sanitize_key($_GET['auth_error']);
        return isset($messages[$key]) ? '<div class="notice error">' . esc_html($messages[$key]) . '</div>' : '';
    }

    public static function customer_form() {
        if (is_user_logged_in()) {
            return '<section class="form-wrap"><h1>' . esc_html(self::t('أنت مسجل بالفعل', 'You are already signed in')) . '</h1><a class="btn primary" href="' . esc_url(self::url('my-account')) . '">' . esc_html(self::t('حسابي', 'My account')) . '</a></section>';
        }
        ob_start(); ?>
        <section class="form-wrap tager-auth-v28">
            <?php echo self::error_box(); ?>
            <div class="auth-title"><span>👤</span><div><h1><?php echo esc_html(self::t('تسجيل عميل جديد', 'Create customer account')); ?></h1><p><?php echo esc_html(self::t('سجّل برقم الهاتف، ويمكنك إضافة البريد اختياريًا.', 'Register with your phone; email is optional.')); ?></p></div></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tager-auth-form">
                <input type="hidden" name="action" value="tager_customer_register">
                <?php wp_nonce_field('tager_customer_register'); ?>
                <div class="auth-grid">
                    <label><?php echo esc_html(self::t('الاسم الكامل', 'Full name')); ?><input required name="name" autocomplete="name"></label>
                    <label><?php echo esc_html(self::t('رقم الهاتف', 'Mobile number')); ?><input name="phone" inputmode="tel" autocomplete="tel" placeholder="01012345678"></label>
                    <label class="full"><?php echo esc_html(self::t('البريد الإلكتروني (اختياري)', 'Email (optional)')); ?><input type="email" name="email" autocomplete="email" placeholder="name@example.com"></label>
                    <label class="full"><?php echo esc_html(self::t('كلمة المرور', 'Password')); ?><input required minlength="8" type="password" name="password" autocomplete="new-password"></label>
                </div>
                <p class="auth-note"><?php echo esc_html(self::t('يجب إدخال رقم الهاتف أو البريد على الأقل. يمكنك تسجيل الدخول لاحقًا بأي منهما.', 'Provide at least a phone or email. You can later sign in with either one.')); ?></p>
                <button class="btn primary auth-submit"><?php echo esc_html(self::t('إنشاء الحساب', 'Create account')); ?></button>
            </form>
            <div class="auth-login"><h3><?php echo esc_html(self::t('لديك حساب؟', 'Already registered?')); ?></h3><?php echo self::login_form(self::url('my-account')); ?></div>
        </section>
        <?php return ob_get_clean();
    }

    public static function vendor_form() {
        if (is_user_logged_in()) return '<section class="form-wrap"><a class="btn primary" href="' . esc_url(self::url('vendor-dashboard')) . '">' . esc_html(self::t('فتح لوحة المورد', 'Open vendor dashboard')) . '</a></section>';
        ob_start(); ?>
        <section class="form-wrap tager-auth-v28">
            <?php echo self::error_box(); ?>
            <div class="auth-title"><span>🏪</span><div><h1><?php echo esc_html(self::t('طلب انضمام مورد', 'Vendor application')); ?></h1><p><?php echo esc_html(self::t('رقم الهاتف أو البريد يكفي لإنشاء الحساب. البريد غير إلزامي.', 'A phone number or email is enough. Email is not required.')); ?></p></div></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tager-auth-form">
                <input type="hidden" name="action" value="tager_vendor_apply">
                <?php wp_nonce_field('tager_vendor_apply'); ?>
                <div class="auth-grid">
                    <label><?php echo esc_html(self::t('اسم المسؤول', 'Contact name')); ?><input required name="name" autocomplete="name"></label>
                    <label><?php echo esc_html(self::t('اسم المتجر', 'Store name')); ?><input required name="store_name"></label>
                    <label><?php echo esc_html(self::t('رقم الهاتف', 'Mobile number')); ?><input name="phone" inputmode="tel" autocomplete="tel" placeholder="01012345678"></label>
                    <label><?php echo esc_html(self::t('البريد الإلكتروني (اختياري)', 'Email (optional)')); ?><input type="email" name="email" autocomplete="email" placeholder="store@example.com"></label>
                    <label class="full"><?php echo esc_html(self::t('كلمة المرور', 'Password')); ?><input required minlength="8" type="password" name="password" autocomplete="new-password"></label>
                    <label class="full"><?php echo esc_html(self::t('السجل التجاري / تفاصيل النشاط', 'Commercial registration / business details')); ?><textarea name="notes" rows="4"></textarea></label>
                </div>
                <p class="auth-note"><?php echo esc_html(self::t('يجب إدخال رقم الهاتف أو البريد على الأقل. طلب المورد يظل تحت مراجعة الإدارة.', 'Provide at least a phone or email. Vendor access remains pending admin review.')); ?></p>
                <button class="btn primary auth-submit"><?php echo esc_html(self::t('إرسال طلب المورد', 'Submit vendor application')); ?></button>
            </form>
            <div class="auth-login"><h3><?php echo esc_html(self::t('لديك حساب مورد؟', 'Already have a vendor account?')); ?></h3><?php echo self::login_form(self::url('vendor-dashboard')); ?></div>
        </section>
        <?php return ob_get_clean();
    }

    private static function login_form($redirect) {
        ob_start(); ?>
        <form name="loginform" class="tager-phone-login" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
            <label><?php echo esc_html(self::t('رقم الهاتف أو البريد الإلكتروني', 'Phone number or email')); ?><input type="text" name="log" required autocomplete="username"></label>
            <label><?php echo esc_html(self::t('كلمة المرور', 'Password')); ?><input type="password" name="pwd" required autocomplete="current-password"></label>
            <label class="remember"><input name="rememberme" type="checkbox" value="forever"> <?php echo esc_html(self::t('تذكرني', 'Remember me')); ?></label>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect); ?>">
            <button type="submit" class="btn secondary"><?php echo esc_html(self::t('تسجيل الدخول', 'Sign in')); ?></button>
        </form>
        <?php return ob_get_clean();
    }

    private static function validate_contact() {
        $email_raw = trim((string) ($_POST['email'] ?? ''));
        $phone_raw = trim((string) ($_POST['phone'] ?? ''));
        $email = $email_raw !== '' ? sanitize_email($email_raw) : '';
        $phone = self::normalize_phone($phone_raw);
        if ($email_raw === '' && $phone_raw === '') return new WP_Error('contact_required');
        if ($email_raw !== '' && (!$email || !is_email($email))) return new WP_Error('email_invalid');
        if ($phone_raw !== '' && !self::valid_phone($phone)) return new WP_Error('phone_invalid');
        if ($email && email_exists($email)) return new WP_Error('email_exists');
        if ($phone && self::user_by_phone($phone)) return new WP_Error('phone_exists');
        return ['email' => $email, 'phone' => $phone];
    }

    private static function unique_login($email, $phone) {
        $base = $phone ?: sanitize_user(strstr($email, '@', true), true);
        if (!$base) $base = 'tager';
        $login = $base;
        $i = 1;
        while (username_exists($login)) $login = $base . '_' . $i++;
        return $login;
    }

    private static function redirect_error($page, $error) {
        wp_safe_redirect(add_query_arg('auth_error', $error, self::url($page)));
        exit;
    }

    public static function customer_register() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'tager_customer_register')) wp_die('Invalid request');
        $contact = self::validate_contact();
        if (is_wp_error($contact)) self::redirect_error('customer-register', $contact->get_error_code());
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) self::redirect_error('customer-register', 'password');
        $login = self::unique_login($contact['email'], $contact['phone']);
        $uid = wp_insert_user([
            'user_login' => $login,
            'user_pass' => $password,
            'user_email' => $contact['email'],
            'display_name' => sanitize_text_field($_POST['name'] ?? ''),
            'role' => 'tager_customer',
        ]);
        if (is_wp_error($uid)) self::redirect_error('customer-register', 'create_failed');
        update_user_meta($uid, 'phone', $contact['phone']);
        update_user_meta($uid, 'phone_normalized', $contact['phone']);
        update_user_meta($uid, 'registration_contact', $contact['phone'] ? 'phone' : 'email');
        wp_set_current_user($uid);
        wp_set_auth_cookie($uid, true);
        wp_safe_redirect(self::url('my-account'));
        exit;
    }

    public static function vendor_register() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'tager_vendor_apply')) wp_die('Invalid request');
        $contact = self::validate_contact();
        if (is_wp_error($contact)) self::redirect_error('vendor-register', $contact->get_error_code());
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) self::redirect_error('vendor-register', 'password');
        $settings = wp_parse_args(get_option('tager_settings', []), ['vendor_auto_approve' => 0]);
        $role = !empty($settings['vendor_auto_approve']) ? 'tager_vendor' : 'tager_vendor_pending';
        $login = self::unique_login($contact['email'], $contact['phone']);
        $uid = wp_insert_user([
            'user_login' => $login,
            'user_pass' => $password,
            'user_email' => $contact['email'],
            'display_name' => sanitize_text_field($_POST['name'] ?? ''),
            'role' => $role,
        ]);
        if (is_wp_error($uid)) self::redirect_error('vendor-register', 'create_failed');
        update_user_meta($uid, 'store_name', sanitize_text_field($_POST['store_name'] ?? ''));
        update_user_meta($uid, 'phone', $contact['phone']);
        update_user_meta($uid, 'phone_normalized', $contact['phone']);
        update_user_meta($uid, 'notes', sanitize_textarea_field($_POST['notes'] ?? ''));
        update_user_meta($uid, 'registration_contact', $contact['phone'] ? 'phone' : 'email');
        update_user_meta($uid, 'vendor_status', !empty($settings['vendor_auto_approve']) ? 'approved' : 'pending');
        wp_safe_redirect(add_query_arg('msg', 'vendor_pending', self::url('vendor-register')));
        exit;
    }

    public static function authenticate_by_phone($user, $username, $password) {
        if ($user instanceof WP_User || empty($username) || empty($password)) return $user;
        $normalized = self::normalize_phone($username);
        if (!self::valid_phone($normalized)) return $user;
        $found = self::user_by_phone($normalized);
        if (!$found) return $user;
        return wp_authenticate_username_password(null, $found->user_login, $password);
    }

    public static function login_labels($translated, $text, $domain) {
        if ($text === 'Username or Email Address') return self::t('رقم الهاتف أو البريد الإلكتروني', 'Phone number or email');
        if ($text === 'Username') return self::t('رقم الهاتف أو البريد الإلكتروني', 'Phone number or email');
        return $translated;
    }

    public static function styles() {
        wp_register_style('tager-v28-auth', false, [], self::VERSION);
        wp_enqueue_style('tager-v28-auth');
        wp_add_inline_style('tager-v28-auth', '
        .tager-auth-v28{max-width:820px;margin:35px auto}.auth-title{display:flex;gap:16px;align-items:center;margin-bottom:22px}.auth-title>span{display:grid;place-items:center;width:58px;height:58px;border-radius:18px;background:#f4ead0;font-size:28px}.auth-title h1{margin:0 0 5px}.auth-title p{margin:0;color:#667085}.auth-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.auth-grid label{display:flex;flex-direction:column;gap:7px;font-weight:700}.auth-grid .full{grid-column:1/-1}.auth-grid input,.auth-grid textarea,.tager-phone-login input{width:100%;box-sizing:border-box;border:1px solid #d8dfdc;border-radius:12px;padding:13px;background:#fff}.auth-note{padding:12px 14px;border-radius:12px;background:#f5f8f6;color:#52615b}.auth-submit{width:100%;justify-content:center}.auth-login{margin-top:24px;padding-top:22px;border-top:1px solid #e6ebe8}.tager-phone-login{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end}.tager-phone-login label{display:flex;flex-direction:column;gap:6px;font-weight:700}.tager-phone-login .remember{grid-column:1/-1;display:flex;flex-direction:row;align-items:center;font-weight:400}.notice.error{background:#fff1f1;border:1px solid #f0b5b5;color:#922;padding:12px 15px;border-radius:12px;margin-bottom:16px}@media(max-width:700px){.auth-grid,.tager-phone-login{grid-template-columns:1fr}.auth-grid .full,.tager-phone-login .remember{grid-column:auto}}');
    }
}
Tager_V28_Phone_Email_Auth::init();
