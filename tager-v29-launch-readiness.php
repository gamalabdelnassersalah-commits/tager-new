<?php
/**
 * Plugin Name: Tager V29 Launch Readiness
 * Description: Launch controls, legal pages, SEO basics, support settings, operational checklist and production safety for Tager Marketplace.
 * Version: 29.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V29_Launch_Readiness {
    const OPTION = 'tager_v29_settings';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 80);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_tager_v29_repair', [__CLASS__, 'repair']);
        add_action('admin_post_tager_v29_toggle_launch', [__CLASS__, 'toggle_launch']);
        add_action('wp_head', [__CLASS__, 'seo_meta'], 2);
        add_action('wp_footer', [__CLASS__, 'floating_support'], 40);
        add_action('template_redirect', [__CLASS__, 'maintenance_guard']);
        add_filter('wp_mail_from_name', [__CLASS__, 'mail_from_name']);
        add_filter('login_message', [__CLASS__, 'login_message']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 99);
    }

    private static function defaults() {
        return [
            'launch_enabled' => 0,
            'maintenance_message' => 'نعمل الآن على تجهيز منصة تاجر للإطلاق. برجاء المحاولة بعد قليل.',
            'support_phone' => '01000000000',
            'support_whatsapp' => '201000000000',
            'support_email' => '',
            'company_name' => 'Tager Marketplace',
            'commercial_name' => 'تاجر للتجارة الإلكترونية',
            'tax_number' => '',
            'company_address' => 'القاهرة، مصر',
            'working_hours' => 'السبت إلى الخميس، 9 صباحًا حتى 6 مساءً',
            'default_meta_description' => 'تاجر منصة مصرية للشراء قطاعي وجملة وجملة الجملة من موردين متعددين.',
            'orders_phone_required' => 1,
            'vendor_documents_required' => 1,
            'admin_order_notifications' => 1,
            'low_stock_threshold' => 5,
        ];
    }

    private static function settings() {
        return wp_parse_args(get_option(self::OPTION, []), self::defaults());
    }

    public static function register_settings() {
        register_setting('tager_v29_group', self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
            'default' => self::defaults(),
        ]);
    }

    public static function sanitize_settings($value) {
        $d = self::defaults();
        $out = [];
        $out['launch_enabled'] = empty($value['launch_enabled']) ? 0 : 1;
        $out['maintenance_message'] = sanitize_textarea_field($value['maintenance_message'] ?? $d['maintenance_message']);
        $out['support_phone'] = preg_replace('/[^0-9+]/', '', $value['support_phone'] ?? '');
        $out['support_whatsapp'] = preg_replace('/[^0-9]/', '', $value['support_whatsapp'] ?? '');
        $out['support_email'] = sanitize_email($value['support_email'] ?? '');
        $out['company_name'] = sanitize_text_field($value['company_name'] ?? $d['company_name']);
        $out['commercial_name'] = sanitize_text_field($value['commercial_name'] ?? $d['commercial_name']);
        $out['tax_number'] = sanitize_text_field($value['tax_number'] ?? '');
        $out['company_address'] = sanitize_textarea_field($value['company_address'] ?? '');
        $out['working_hours'] = sanitize_text_field($value['working_hours'] ?? '');
        $out['default_meta_description'] = sanitize_textarea_field($value['default_meta_description'] ?? $d['default_meta_description']);
        $out['orders_phone_required'] = empty($value['orders_phone_required']) ? 0 : 1;
        $out['vendor_documents_required'] = empty($value['vendor_documents_required']) ? 0 : 1;
        $out['admin_order_notifications'] = empty($value['admin_order_notifications']) ? 0 : 1;
        $out['low_stock_threshold'] = max(0, absint($value['low_stock_threshold'] ?? 5));
        return $out;
    }

    public static function admin_menu() {
        add_menu_page('Tager Launch', 'Tager Launch', 'manage_options', 'tager-v29-launch', [__CLASS__, 'dashboard'], 'dashicons-rocket', 2);
        add_submenu_page('tager-v29-launch', 'Launch Dashboard', 'Launch Dashboard', 'manage_options', 'tager-v29-launch', [__CLASS__, 'dashboard']);
        add_submenu_page('tager-v29-launch', 'Business Settings', 'Business Settings', 'manage_options', 'tager-v29-settings', [__CLASS__, 'settings_page']);
        add_submenu_page('tager-v29-launch', 'Launch Checklist', 'Launch Checklist', 'manage_options', 'tager-v29-checklist', [__CLASS__, 'checklist_page']);
    }

    private static function page_exists($slug) {
        return (bool)get_page_by_path($slug);
    }

    private static function checks() {
        $s = self::settings();
        $checks = [
            ['القالب مفعّل', get_stylesheet() === 'tager-marketplace', 'فعّل قالب tager-marketplace'],
            ['الصفحة الرئيسية موجودة', self::page_exists('home') || (int)get_option('page_on_front') > 0, 'استخدم زر الإصلاح لإنشاء الصفحة الرئيسية'],
            ['صفحة تسجيل العميل', self::page_exists('customer-register'), 'أنشئ صفحة customer-register'],
            ['صفحة تسجيل المورد', self::page_exists('vendor-register'), 'أنشئ صفحة vendor-register'],
            ['صفحة تسجيل الدخول', self::page_exists('login'), 'أنشئ صفحة login'],
            ['صفحة المنتجات', self::page_exists('products') || self::page_exists('shop'), 'أنشئ صفحة products'],
            ['صفحة السلة', self::page_exists('cart'), 'أنشئ صفحة cart'],
            ['الشروط والأحكام', self::page_exists('terms-and-conditions'), 'أنشئ صفحة الشروط'],
            ['سياسة الخصوصية', self::page_exists('privacy-policy'), 'أنشئ صفحة الخصوصية'],
            ['سياسة الشحن', self::page_exists('shipping-policy'), 'أنشئ سياسة الشحن'],
            ['سياسة الاسترجاع', self::page_exists('returns-policy'), 'أنشئ سياسة الاسترجاع'],
            ['بيانات دعم صحيحة', !empty($s['support_phone']) || !empty($s['support_email']), 'أضف هاتف أو بريد الدعم'],
            ['اسم الشركة موجود', !empty($s['company_name']), 'أضف اسم الشركة'],
            ['الرابط الدائم ليس Plain', get_option('permalink_structure') !== '', 'اختر Post name من Permalinks'],
            ['الإطلاق مفعّل', !empty($s['launch_enabled']), 'فعّل وضع الإطلاق بعد اكتمال الاختبارات'],
        ];
        return $checks;
    }

    public static function dashboard() {
        if (!current_user_can('manage_options')) return;
        $checks = self::checks();
        $passed = count(array_filter($checks, fn($c) => $c[1]));
        $score = (int)round(($passed / max(1, count($checks))) * 100);
        $s = self::settings();
        ?>
        <div class="wrap tager-v29-admin" dir="rtl">
            <h1>مركز إطلاق Tager V29</h1>
            <div class="tgr-grid">
                <div class="tgr-card tgr-score"><strong><?php echo esc_html($score); ?>%</strong><span>جاهزية الإطلاق</span></div>
                <div class="tgr-card"><strong><?php echo esc_html($passed); ?>/<?php echo esc_html(count($checks)); ?></strong><span>اختبارات ناجحة</span></div>
                <div class="tgr-card"><strong><?php echo $s['launch_enabled'] ? 'مفتوح' : 'صيانة'; ?></strong><span>حالة الموقع</span></div>
            </div>
            <div class="tgr-actions">
                <a class="button button-primary button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v29_repair'), 'tager_v29_repair')); ?>">إصلاح وإنشاء الصفحات</a>
                <a class="button button-secondary button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v29_toggle_launch'), 'tager_v29_toggle_launch')); ?>"><?php echo $s['launch_enabled'] ? 'تفعيل وضع الصيانة' : 'فتح الموقع للإطلاق'; ?></a>
                <a class="button button-secondary button-hero" target="_blank" href="<?php echo esc_url(home_url('/')); ?>">فتح الموقع</a>
            </div>
            <table class="widefat striped tgr-table"><thead><tr><th>الفحص</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
            <?php foreach ($checks as $check): ?>
                <tr><td><?php echo esc_html($check[0]); ?></td><td><span class="tgr-status <?php echo $check[1] ? 'ok' : 'bad'; ?>"><?php echo $check[1] ? 'جاهز' : 'ناقص'; ?></span></td><td><?php echo esc_html($check[2]); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <div class="notice notice-warning inline"><p><strong>قبل الإطلاق الحقيقي:</strong> اربط بوابة دفع مصرية فعلية، اختبر الشحن، فعّل SSL، خذ نسخة احتياطية، واستخدم استضافة حقيقية. Playground مناسب للتجربة فقط.</p></div>
        </div>
        <?php self::admin_css();
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) return;
        $s = self::settings();
        ?>
        <div class="wrap tager-v29-admin" dir="rtl"><h1>إعدادات التشغيل والشركة</h1>
        <form method="post" action="options.php">
        <?php settings_fields('tager_v29_group'); ?>
        <div class="tgr-form-grid">
            <?php self::field('company_name','اسم المنصة',$s['company_name']); ?>
            <?php self::field('commercial_name','الاسم التجاري',$s['commercial_name']); ?>
            <?php self::field('tax_number','الرقم الضريبي',$s['tax_number']); ?>
            <?php self::field('support_phone','هاتف الدعم',$s['support_phone']); ?>
            <?php self::field('support_whatsapp','واتساب الدعم',$s['support_whatsapp']); ?>
            <?php self::field('support_email','بريد الدعم',$s['support_email'],'email'); ?>
            <?php self::field('working_hours','ساعات العمل',$s['working_hours']); ?>
            <?php self::field('low_stock_threshold','حد المخزون المنخفض',$s['low_stock_threshold'],'number'); ?>
        </div>
        <p><label><strong>عنوان الشركة</strong><br><textarea name="<?php echo self::OPTION; ?>[company_address]" rows="3" class="large-text"><?php echo esc_textarea($s['company_address']); ?></textarea></label></p>
        <p><label><strong>وصف SEO الافتراضي</strong><br><textarea name="<?php echo self::OPTION; ?>[default_meta_description]" rows="3" class="large-text"><?php echo esc_textarea($s['default_meta_description']); ?></textarea></label></p>
        <p><label><strong>رسالة وضع الصيانة</strong><br><textarea name="<?php echo self::OPTION; ?>[maintenance_message]" rows="3" class="large-text"><?php echo esc_textarea($s['maintenance_message']); ?></textarea></label></p>
        <div class="tgr-checks">
            <?php self::checkbox('launch_enabled','فتح الموقع للزوار',$s['launch_enabled']); ?>
            <?php self::checkbox('orders_phone_required','رقم الهاتف إلزامي لإتمام الطلب',$s['orders_phone_required']); ?>
            <?php self::checkbox('vendor_documents_required','مستندات المورد إلزامية',$s['vendor_documents_required']); ?>
            <?php self::checkbox('admin_order_notifications','إشعارات الإدارة عند وصول طلب',$s['admin_order_notifications']); ?>
        </div>
        <?php submit_button('حفظ الإعدادات'); ?>
        </form></div>
        <?php self::admin_css();
    }

    private static function field($key,$label,$value,$type='text') {
        echo '<label><strong>'.esc_html($label).'</strong><input class="regular-text" type="'.esc_attr($type).'" name="'.esc_attr(self::OPTION).'['.esc_attr($key).']" value="'.esc_attr($value).'" /></label>';
    }
    private static function checkbox($key,$label,$checked) {
        echo '<label><input type="checkbox" name="'.esc_attr(self::OPTION).'['.esc_attr($key).']" value="1" '.checked($checked,1,false).' /> '.esc_html($label).'</label>';
    }

    public static function checklist_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap tager-v29-admin" dir="rtl"><h1>قائمة الإطلاق النهائي</h1>
        <div class="tgr-checklist">
            <h2>اختبارات الحسابات</h2><p>أنشئ حساب عميل برقم هاتف فقط، حساب عميل بالبريد، حساب مورد، وجرّب تسجيل الدخول بكل طريقة.</p>
            <h2>اختبارات السلة</h2><p>اختبر سلة مورد واحد، عدة موردين، الحد الأدنى لكل مورد، السلة المختلطة، رسوم 1.5%، كوبون، وشحن مجاني.</p>
            <h2>اختبارات الدفع</h2><p>اختبر الدفع عند الاستلام، التحويل البنكي، InstaPay، المحفظة، إثبات الدفع، وحالات الطلب.</p>
            <h2>اختبارات الإدارة</h2><p>قبول ورفض مورد، مراجعة منتج، تعديل العمولة، تعديل أسعار الشحن، تغيير حالة الطلب، وطلبات السحب.</p>
            <h2>متطلبات إنتاج</h2><p>استضافة حقيقية، SSL، نسخ احتياطية يومية، بريد SMTP، بوابة دفع بعقد تاجر، سياسة خصوصية وشروط قانونية معتمدة.</p>
        </div></div>
        <?php self::admin_css();
    }

    public static function repair() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('tager_v29_repair');
        $pages = [
            'home' => ['الرئيسية','[tager_v29_home]'],
            'login' => ['تسجيل الدخول','[tager_login]'],
            'customer-register' => ['تسجيل عميل','[tager_customer_register]'],
            'vendor-register' => ['تسجيل مورد','[tager_vendor_register]'],
            'products' => ['المنتجات','[tager_products]'],
            'cart' => ['السلة','[tager_cart]'],
            'my-account' => ['حسابي','[tager_account]'],
            'vendors' => ['الموردون','[tager_vendors]'],
            'contact-us' => ['تواصل معنا','[tager_v29_contact]'],
            'terms-and-conditions' => ['الشروط والأحكام','[tager_v29_legal type="terms"]'],
            'privacy-policy' => ['سياسة الخصوصية','[tager_v29_legal type="privacy"]'],
            'shipping-policy' => ['سياسة الشحن','[tager_v29_legal type="shipping"]'],
            'returns-policy' => ['سياسة الاسترجاع','[tager_v29_legal type="returns"]'],
        ];
        foreach ($pages as $slug => $data) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(['post_title'=>$data[0],'post_name'=>$slug,'post_content'=>$data[1],'post_status'=>'publish','post_type'=>'page']);
            }
        }
        $home = get_page_by_path('home');
        if ($home) { update_option('show_on_front','page'); update_option('page_on_front',$home->ID); }
        if (!get_option('permalink_structure')) update_option('permalink_structure','/%postname%/');
        flush_rewrite_rules(false);
        wp_safe_redirect(admin_url('admin.php?page=tager-v29-launch&repaired=1')); exit;
    }

    public static function toggle_launch() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('tager_v29_toggle_launch');
        $s = self::settings(); $s['launch_enabled'] = empty($s['launch_enabled']) ? 1 : 0; update_option(self::OPTION,$s);
        wp_safe_redirect(admin_url('admin.php?page=tager-v29-launch')); exit;
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v29_home', [__CLASS__, 'home_shortcode']);
        add_shortcode('tager_v29_contact', [__CLASS__, 'contact_shortcode']);
        add_shortcode('tager_v29_legal', [__CLASS__, 'legal_shortcode']);
    }

    public static function home_shortcode() {
        $shop = self::url('products'); $vendors = self::url('vendors'); $customer = self::url('customer-register'); $vendor = self::url('vendor-register');
        ob_start(); ?>
        <section class="tgr29-hero"><div><span class="tgr29-pill">منصة مصرية للبيع والشراء</span><h1>كل احتياجاتك قطاعي وجملة وجملة الجملة</h1><p>اطلب من مورد واحد أو عدة موردين، واختر نوع السلة وطريقة الدفع والشحن المناسبة.</p><div class="tgr29-buttons"><a href="<?php echo esc_url($shop); ?>" class="tgr29-btn primary">ابدأ التسوق</a><a href="<?php echo esc_url($vendors); ?>" class="tgr29-btn ghost">استعرض الموردين</a></div></div><div class="tgr29-panel"><h3>ابدأ الآن</h3><a href="<?php echo esc_url($customer); ?>">إنشاء حساب عميل</a><a href="<?php echo esc_url($vendor); ?>">سجل كمورد</a><small>يمكن التسجيل برقم الهاتف أو البريد الإلكتروني.</small></div></section>
        <section class="tgr29-features"><article><b>3 مستويات سعر</b><span>قطاعي، جملة، جملة الجملة</span></article><article><b>27 محافظة</b><span>شحن وأسعار ومدة توصيل مستقلة</span></article><article><b>موردون متعددون</b><span>صفحة ومنتجات مستقلة لكل مورد</span></article><article><b>دفع مرن</b><span>كاش، تحويل، InstaPay، محافظ وبطاقات</span></article></section>
        <?php return ob_get_clean();
    }

    public static function contact_shortcode() {
        $s=self::settings(); ob_start(); ?>
        <div class="tgr29-content"><h1>تواصل معنا</h1><div class="tgr29-contact-grid"><div><h3>خدمة العملاء</h3><p>الهاتف: <?php echo esc_html($s['support_phone']); ?></p><p>البريد: <?php echo esc_html($s['support_email']); ?></p><p>ساعات العمل: <?php echo esc_html($s['working_hours']); ?></p></div><div><h3>العنوان</h3><p><?php echo nl2br(esc_html($s['company_address'])); ?></p><p>الاسم التجاري: <?php echo esc_html($s['commercial_name']); ?></p></div></div></div>
        <?php return ob_get_clean();
    }

    public static function legal_shortcode($atts) {
        $a=shortcode_atts(['type'=>'terms'],$atts); $s=self::settings();
        $titles=['terms'=>'الشروط والأحكام','privacy'=>'سياسة الخصوصية','shipping'=>'سياسة الشحن','returns'=>'سياسة الاسترجاع'];
        $body=[
            'terms'=>'باستخدام منصة تاجر، يوافق المستخدم على صحة بياناته والالتزام بسياسات الطلب والدفع والشحن. تحتفظ الإدارة بحق مراجعة الموردين والمنتجات والطلبات المخالفة.',
            'privacy'=>'نستخدم بيانات الحساب والطلب لتنفيذ الخدمة والتواصل والدعم. لا تُباع بيانات المستخدمين للمعلنين، ويجب تأمين الاستضافة وقاعدة البيانات قبل الإطلاق.',
            'shipping'=>'تختلف رسوم ومدة الشحن حسب المحافظة ونوع السلة وعدد الموردين. تظهر الرسوم النهائية قبل تأكيد الطلب، وقد يتوفر شحن مجاني وفق إعدادات الإدارة.',
            'returns'=>'يخضع الاسترجاع لحالة المنتج وسياسة المورد والفترة المحددة. يجب تقديم طلب الاسترجاع من الحساب مع سبب واضح وصور عند الحاجة.',
        ];
        return '<div class="tgr29-content"><h1>'.esc_html($titles[$a['type']] ?? $titles['terms']).'</h1><p>'.esc_html($body[$a['type']] ?? $body['terms']).'</p><p><strong>'.esc_html($s['company_name']).'</strong><br>'.esc_html($s['company_address']).'</p><div class="tgr29-warning">هذه صياغة تشغيلية مبدئية ويجب مراجعتها قانونيًا قبل الإطلاق التجاري.</div></div>';
    }

    private static function url($slug) { $p=get_page_by_path($slug); return $p?get_permalink($p):home_url('/'.$slug.'/'); }

    public static function maintenance_guard() {
        if (is_admin() || wp_doing_ajax() || current_user_can('manage_options')) return;
        $s=self::settings(); if (!empty($s['launch_enabled'])) return;
        if (is_user_logged_in()) return;
        status_header(503); nocache_headers();
        wp_die('<div style="font-family:Arial;text-align:center;max-width:680px;margin:10vh auto;padding:40px;border-radius:24px;background:#fff;box-shadow:0 20px 60px #0002" dir="rtl"><h1 style="color:#174c3c">Tager</h1><h2>الموقع تحت التجهيز</h2><p>'.esc_html($s['maintenance_message']).'</p></div>','Tager - Maintenance',['response'=>503]);
    }

    public static function seo_meta() {
        if (is_admin()) return; $s=self::settings();
        echo "\n<meta name=\"description\" content=\"".esc_attr($s['default_meta_description'])."\">\n";
        echo '<meta name="theme-color" content="#174c3c">' . "\n";
    }

    public static function floating_support() {
        $s=self::settings(); if (empty($s['support_whatsapp'])) return;
        echo '<a class="tgr29-whatsapp" target="_blank" rel="noopener" href="https://wa.me/'.esc_attr($s['support_whatsapp']).'?text='.rawurlencode('مرحبًا، أحتاج مساعدة في منصة Tager').'" aria-label="دعم واتساب">واتساب</a>';
    }

    public static function mail_from_name($name) { $s=self::settings(); return $s['company_name'] ?: $name; }
    public static function login_message($message) { return '<p class="message">يمكنك تسجيل الدخول برقم الهاتف أو البريد الإلكتروني.</p>'.$message; }

    public static function assets() {
        wp_register_style('tager-v29-inline', false); wp_enqueue_style('tager-v29-inline');
        wp_add_inline_style('tager-v29-inline', self::css());
    }

    private static function css() {
        return '.tgr29-hero{display:grid;grid-template-columns:1.5fr .75fr;gap:28px;align-items:center;padding:56px;border-radius:32px;background:linear-gradient(135deg,#103c31,#1e6b53);color:#fff;margin:24px 0}.tgr29-hero h1{font-size:clamp(36px,5vw,68px);line-height:1.05;margin:15px 0}.tgr29-pill{display:inline-block;padding:8px 14px;border-radius:999px;background:#ffffff18;border:1px solid #ffffff35}.tgr29-buttons{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}.tgr29-btn,.tgr29-panel a{display:inline-flex;justify-content:center;padding:14px 22px;border-radius:14px;text-decoration:none;font-weight:800}.tgr29-btn.primary,.tgr29-panel a{background:#d8ad45;color:#173b31}.tgr29-btn.ghost{border:1px solid #fff;color:#fff}.tgr29-panel{background:#fff;color:#173b31;border-radius:24px;padding:26px;display:grid;gap:12px}.tgr29-features{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:24px 0}.tgr29-features article,.tgr29-content{background:#fff;border:1px solid #e6ece9;border-radius:20px;padding:24px;box-shadow:0 12px 32px #143c2e0d}.tgr29-features b,.tgr29-features span{display:block}.tgr29-features b{font-size:20px;color:#174c3c}.tgr29-contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.tgr29-warning{padding:14px;border-radius:12px;background:#fff4d8;color:#684d00}.tgr29-whatsapp{position:fixed;left:20px;bottom:20px;z-index:9999;background:#1f9d63;color:#fff!important;text-decoration:none;padding:13px 19px;border-radius:999px;box-shadow:0 12px 30px #0003;font-weight:800}@media(max-width:800px){.tgr29-hero{grid-template-columns:1fr;padding:28px}.tgr29-features{grid-template-columns:1fr 1fr}.tgr29-contact-grid{grid-template-columns:1fr}}@media(max-width:480px){.tgr29-features{grid-template-columns:1fr}.tgr29-buttons>*{width:100%}}';
    }

    private static function admin_css() { ?>
        <style>.tager-v29-admin{max-width:1200px}.tgr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:20px 0}.tgr-card{background:#fff;border:1px solid #dde5e1;border-radius:18px;padding:24px;display:flex;flex-direction:column;gap:8px}.tgr-card strong{font-size:34px;color:#174c3c}.tgr-score{background:#174c3c;color:#fff}.tgr-score strong{color:#e2bd5d}.tgr-actions{display:flex;gap:12px;flex-wrap:wrap;margin:18px 0}.tgr-table{border-radius:14px;overflow:hidden}.tgr-status{display:inline-block;padding:5px 11px;border-radius:999px;font-weight:700}.tgr-status.ok{background:#dcf7e8;color:#11633d}.tgr-status.bad{background:#ffe4e4;color:#8a1f1f}.tgr-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;background:#fff;padding:22px;border-radius:16px}.tgr-form-grid label{display:grid;gap:8px}.tgr-checks{display:grid;gap:10px;background:#fff;padding:18px;border-radius:14px;margin-top:16px}.tgr-checklist{background:#fff;padding:26px;border-radius:18px;max-width:900px}.tgr-checklist h2{color:#174c3c;border-bottom:1px solid #eee;padding-bottom:10px}@media(max-width:700px){.tgr-grid,.tgr-form-grid{grid-template-columns:1fr}}</style>
    <?php }
}
Tager_V29_Launch_Readiness::init();
