<?php
/**
 * Plugin Name: Tager V46 Launch Control & Complete Routing
 * Description: Central page registry, role routing, page/link repair, form and data integrity audit, and launch readiness dashboard.
 * Version: 46.0.0
 */
if (!defined('ABSPATH')) { exit; }

final class Tager_V46_Launch_Control {
    const VERSION = '46.0.0';
    const OPTION_LAST_AUDIT = 'tager_v46_last_audit';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes']);
        add_action('init', [__CLASS__, 'ensure_roles']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 99);
        add_action('admin_post_tager_v46_repair', [__CLASS__, 'handle_repair']);
        add_action('admin_post_tager_v46_run_audit', [__CLASS__, 'handle_audit']);
        add_action('admin_notices', [__CLASS__, 'admin_notice']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 100, 3);
        add_action('template_redirect', [__CLASS__, 'protect_role_pages'], 1);
        add_filter('the_content', [__CLASS__, 'prevent_empty_core_pages'], 99);
        add_action('wp_enqueue_scripts', [__CLASS__, 'frontend_assets']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
    }

    public static function pages() {
        return [
            'home' => ['title'=>'الرئيسية','shortcode'=>'[tager_v29_home]','public'=>true],
            'products' => ['title'=>'المنتجات','shortcode'=>'[tager_shop]','public'=>true],
            'cart' => ['title'=>'السلة','shortcode'=>'[tager_v24_cart]','public'=>true],
            'login' => ['title'=>'تسجيل الدخول','shortcode'=>'[tager_v45_login]','public'=>true],
            'choose-account' => ['title'=>'إنشاء حساب','shortcode'=>'[tager_v45_choose_account]','public'=>true],
            'customer-register' => ['title'=>'تسجيل عميل','shortcode'=>'[tager_customer_register]','public'=>true],
            'vendor-register' => ['title'=>'تسجيل مورد','shortcode'=>'[tager_vendor_register]','public'=>true],
            'forgot-password' => ['title'=>'نسيت كلمة المرور','shortcode'=>'[tager_v45_forgot_password]','public'=>true],
            'phone-reset' => ['title'=>'استعادة كلمة المرور بالهاتف','shortcode'=>'[tager_v45_phone_reset]','public'=>true],
            'customer-account' => ['title'=>'حساب العميل','shortcode'=>'[tager_v40_customer_workspace]','role'=>'customer'],
            'customer-orders' => ['title'=>'طلبات العميل','shortcode'=>'[tager_v42_customer_orders]','role'=>'customer'],
            'customer-notifications' => ['title'=>'إشعارات العميل','shortcode'=>'[tager_v42_notifications]','role'=>'customer'],
            'vendor-dashboard' => ['title'=>'لوحة المورد','shortcode'=>'[tager_v40_vendor_workspace]','role'=>'vendor'],
            'vendor-market' => ['title'=>'سوق المورد','shortcode'=>'[tager_v39_vendor_market]','role'=>'vendor'],
            'vendor-orders' => ['title'=>'طلبات المورد','shortcode'=>'[tager_v42_vendor_orders]','role'=>'vendor'],
            'vendor-media' => ['title'=>'استوديو صور المورد','shortcode'=>'[tager_v44_profile_studio]','role'=>'vendor'],
            'product-media' => ['title'=>'استوديو صور المنتجات','shortcode'=>'[tager_v44_product_media_studio]','role'=>'vendor'],
            'admin-portal' => ['title'=>'بوابة الإدارة','shortcode'=>'[tager_v40_admin_workspace]','role'=>'admin'],
            'admin-approvals' => ['title'=>'مركز الموافقات','shortcode'=>'[tager_v42_admin_approvals]','role'=>'admin'],
            'notifications' => ['title'=>'الإشعارات','shortcode'=>'[tager_notifications]','public'=>false],
            'support' => ['title'=>'الدعم','shortcode'=>'[tager_support]','public'=>true],
            'vendor-directory' => ['title'=>'دليل الموردين','shortcode'=>'[tager_v7_vendor_directory]','public'=>true],
            'site-map' => ['title'=>'خريطة الموقع','shortcode'=>'[tager_v45_site_map]','public'=>true],
            'privacy-policy' => ['title'=>'سياسة الخصوصية','shortcode'=>'[tager_v29_legal type="privacy"]','public'=>true],
            'terms' => ['title'=>'الشروط والأحكام','shortcode'=>'[tager_v29_legal type="terms"]','public'=>true],
            'shipping-policy' => ['title'=>'سياسة الشحن','shortcode'=>'[tager_v29_legal type="shipping"]','public'=>true],
            'returns-policy' => ['title'=>'سياسة الاسترجاع','shortcode'=>'[tager_v29_legal type="returns"]','public'=>true],
            'contact' => ['title'=>'تواصل معنا','shortcode'=>'[tager_v29_contact]','public'=>true],
        ];
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v46_page_hub', [__CLASS__, 'page_hub_shortcode']);
    }

    public static function ensure_roles() {
        if (!get_role('customer')) {
            add_role('customer', 'عميل', ['read'=>true, 'upload_files'=>true]);
        }
        if (!get_role('tager_vendor') && !get_role('wcfm_vendor')) {
            add_role('tager_vendor', 'مورد', ['read'=>true, 'upload_files'=>true, 'edit_posts'=>true]);
        }
    }

    public static function get_page_id($slug) {
        $page = get_page_by_path($slug);
        return $page ? (int)$page->ID : 0;
    }

    public static function page_url($slug) {
        $id = self::get_page_id($slug);
        return $id ? get_permalink($id) : home_url('/' . trim($slug, '/') . '/');
    }

    public static function user_kind($user = null) {
        $user = $user ?: wp_get_current_user();
        if (!$user || !$user->exists()) return 'guest';
        $roles = (array)$user->roles;
        if (user_can($user, 'manage_options') || array_intersect($roles, ['administrator','tager_platform_manager','tager_operations_manager'])) return 'admin';
        if (array_intersect($roles, ['wcfm_vendor','vendor','seller','tager_vendor'])) return 'vendor';
        return 'customer';
    }

    public static function login_redirect($redirect_to, $requested, $user) {
        if (is_wp_error($user) || !($user instanceof WP_User)) return $redirect_to;
        $kind = self::user_kind($user);
        if ($kind === 'admin') return self::page_url('admin-portal');
        if ($kind === 'vendor') return self::page_url('vendor-dashboard');
        return self::page_url('customer-account');
    }

    public static function protect_role_pages() {
        if (is_admin() || wp_doing_ajax()) return;
        $pages = self::pages();
        foreach ($pages as $slug => $cfg) {
            if (!empty($cfg['role']) && is_page($slug)) {
                if (!is_user_logged_in()) {
                    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(self::page_url($slug)), self::page_url('login')));
                    exit;
                }
                $kind = self::user_kind();
                if ($cfg['role'] === 'admin' && $kind !== 'admin') {
                    wp_safe_redirect($kind === 'vendor' ? self::page_url('vendor-dashboard') : self::page_url('customer-account'));
                    exit;
                }
                if ($cfg['role'] === 'vendor' && $kind !== 'vendor') {
                    wp_safe_redirect($kind === 'admin' ? self::page_url('admin-portal') : self::page_url('customer-account'));
                    exit;
                }
                if ($cfg['role'] === 'customer' && $kind !== 'customer') {
                    wp_safe_redirect($kind === 'admin' ? self::page_url('admin-portal') : self::page_url('vendor-dashboard'));
                    exit;
                }
            }
        }
    }

    public static function repair_pages() {
        $result = ['created'=>0,'updated'=>0,'unchanged'=>0];
        foreach (self::pages() as $slug => $cfg) {
            $page = get_page_by_path($slug);
            $content = $cfg['shortcode'];
            if (!$page) {
                $id = wp_insert_post([
                    'post_title'=>$cfg['title'], 'post_name'=>$slug, 'post_content'=>$content,
                    'post_status'=>'publish', 'post_type'=>'page', 'comment_status'=>'closed'
                ], true);
                if (!is_wp_error($id)) $result['created']++;
                continue;
            }
            $current = trim((string)$page->post_content);
            $needs = ($current === '' || $current === '&nbsp;' || preg_match('/^\[tager_v45_page_fallback/', $current));
            if ($needs) {
                wp_update_post(['ID'=>$page->ID,'post_content'=>$content,'post_status'=>'publish']);
                $result['updated']++;
            } else {
                $result['unchanged']++;
            }
        }
        update_option('show_on_front', 'page');
        $home = self::get_page_id('home');
        if ($home) update_option('page_on_front', $home);
        self::build_menus();
        flush_rewrite_rules(false);
        return $result;
    }

    public static function build_menus() {
        $menus = [
            'القائمة الرئيسية' => ['home','products','vendor-directory','support','contact'],
            'قائمة الحساب' => ['customer-account','customer-orders','vendor-dashboard','vendor-orders','notifications'],
            'قائمة الفوتر' => ['terms','privacy-policy','shipping-policy','returns-policy','site-map'],
        ];
        foreach ($menus as $menu_name => $slugs) {
            $menu = wp_get_nav_menu_object($menu_name);
            $menu_id = $menu ? $menu->term_id : wp_create_nav_menu($menu_name);
            if (is_wp_error($menu_id)) continue;
            $existing = wp_get_nav_menu_items($menu_id) ?: [];
            $existing_ids = array_map(function($i){ return (int)$i->object_id; }, $existing);
            foreach ($slugs as $slug) {
                $id = self::get_page_id($slug);
                if ($id && !in_array($id, $existing_ids, true)) {
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title'=>get_the_title($id), 'menu-item-object'=>'page',
                        'menu-item-object-id'=>$id, 'menu-item-type'=>'post_type', 'menu-item-status'=>'publish'
                    ]);
                }
            }
        }
    }

    public static function shortcode_exists_in_content($content) {
        if (!preg_match_all('/\[([a-zA-Z0-9_-]+)/', (string)$content, $m)) return [];
        $missing = [];
        foreach (array_unique($m[1]) as $tag) {
            if (!shortcode_exists($tag)) $missing[] = $tag;
        }
        return $missing;
    }

    public static function audit() {
        $checks = [];
        foreach (self::pages() as $slug => $cfg) {
            $page = get_page_by_path($slug);
            $checks[] = [
                'group'=>'pages', 'label'=>$cfg['title'], 'status'=>(bool)$page,
                'detail'=>$page ? ('/' . $slug . '/') : 'الصفحة غير موجودة'
            ];
            if ($page) {
                $missing = self::shortcode_exists_in_content($page->post_content);
                $checks[] = [
                    'group'=>'shortcodes','label'=>'محتوى: '.$cfg['title'],'status'=>empty($missing),
                    'detail'=>empty($missing) ? 'المحتوى مسجل' : 'Shortcodes ناقصة: '.implode(', ', $missing)
                ];
            }
        }
        $checks[] = ['group'=>'auth','label'=>'دور العميل','status'=>(bool)get_role('customer'),'detail'=>'customer'];
        $checks[] = ['group'=>'auth','label'=>'دور المورد','status'=>(bool)(get_role('tager_vendor') || get_role('wcfm_vendor')),'detail'=>'vendor role'];
        $checks[] = ['group'=>'media','label'=>'رفع الملفات','status'=>current_user_can('upload_files'),'detail'=>'صلاحية المستخدم الحالي'];
        $checks[] = ['group'=>'theme','label'=>'قالب Tager','status'=>wp_get_theme('tager-marketplace')->exists(),'detail'=>'tager-marketplace'];

        $broken = 0;
        $pages = get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1]);
        foreach ($pages as $p) {
            if (preg_match('/href\s*=\s*["\'](?:#|javascript:void\(0\)|)["\']/i', $p->post_content)) $broken++;
        }
        $checks[] = ['group'=>'links','label'=>'روابط فارغة داخل محتوى الصفحات','status'=>$broken===0,'detail'=>$broken.' صفحة تحتاج مراجعة'];

        $product_count = post_type_exists('product') ? wp_count_posts('product') : null;
        $checks[] = ['group'=>'data','label'=>'نوع بيانات المنتجات','status'=>post_type_exists('product'),'detail'=>$product_count ? ('منشور: '.(int)$product_count->publish) : 'غير مسجل'];

        $pass = count(array_filter($checks, function($c){return $c['status'];}));
        $audit = ['time'=>current_time('mysql'),'checks'=>$checks,'pass'=>$pass,'total'=>count($checks),'score'=>count($checks) ? round($pass/count($checks)*100) : 0];
        update_option(self::OPTION_LAST_AUDIT, $audit, false);
        return $audit;
    }

    public static function handle_repair() {
        if (!current_user_can('manage_options')) wp_die('غير مصرح');
        check_admin_referer('tager_v46_repair');
        $r = self::repair_pages();
        self::audit();
        wp_safe_redirect(add_query_arg(['page'=>'tager-v46','repaired'=>1,'created'=>$r['created'],'updated'=>$r['updated']], admin_url('admin.php')));
        exit;
    }

    public static function handle_audit() {
        if (!current_user_can('manage_options')) wp_die('غير مصرح');
        check_admin_referer('tager_v46_audit');
        self::audit();
        wp_safe_redirect(add_query_arg(['page'=>'tager-v46','audited'=>1], admin_url('admin.php')));
        exit;
    }

    public static function admin_menu() {
        add_menu_page('Tager V46','Tager V46','manage_options','tager-v46',[__CLASS__,'admin_page'],'dashicons-admin-site-alt3',2);
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) return;
        $audit = get_option(self::OPTION_LAST_AUDIT);
        if (!$audit) $audit = self::audit();
        echo '<div class="wrap tager-v46-admin" dir="rtl">';
        echo '<div class="tv46-hero"><div><h1>مركز اكتمال وتشغيل Tager V46</h1><p>فحص الصفحات، مسارات الدخول، الشورت كود، الروابط، الأدوار وحفظ البيانات قبل الإطلاق.</p></div><div class="tv46-score"><strong>'.esc_html($audit['score']).'%</strong><span>جاهزية النظام</span></div></div>';
        if (!empty($_GET['repaired'])) echo '<div class="notice notice-success"><p>تم الإصلاح: إنشاء '.intval($_GET['created']).' صفحة وتحديث '.intval($_GET['updated']).' صفحة.</p></div>';
        echo '<div class="tv46-actions">';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">'; wp_nonce_field('tager_v46_repair'); echo '<input type="hidden" name="action" value="tager_v46_repair"><button class="button button-primary button-hero">إصلاح وإنشاء وربط كل الصفحات</button></form>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">'; wp_nonce_field('tager_v46_audit'); echo '<input type="hidden" name="action" value="tager_v46_run_audit"><button class="button button-secondary button-hero">تشغيل الفحص الكامل</button></form>';
        echo '</div>';
        echo '<div class="tv46-grid">';
        foreach ($audit['checks'] as $c) {
            echo '<div class="tv46-card '.($c['status']?'ok':'bad').'"><span class="dashicons '.($c['status']?'dashicons-yes-alt':'dashicons-warning').'"></span><div><strong>'.esc_html($c['label']).'</strong><small>'.esc_html($c['detail']).'</small></div></div>';
        }
        echo '</div>';
        echo '<h2>روابط اختبار مباشرة</h2><div class="tv46-links">';
        foreach (self::pages() as $slug=>$cfg) {
            echo '<a target="_blank" href="'.esc_url(self::page_url($slug)).'">'.esc_html($cfg['title']).'</a>';
        }
        echo '</div>';
        echo '<p class="description">آخر فحص: '.esc_html($audit['time']).'. الفحص الآلي يتحقق من البنية والروابط والتسجيل، أما الدفع الحقيقي وSMS وSMTP فيحتاجان مفاتيح خدمات فعلية على الاستضافة.</p>';
        echo '</div>';
    }

    public static function admin_notice() {
        if (!current_user_can('manage_options')) return;
        $audit = get_option(self::OPTION_LAST_AUDIT);
        if ($audit && $audit['score'] < 80 && (!isset($_GET['page']) || $_GET['page'] !== 'tager-v46')) {
            echo '<div class="notice notice-warning"><p><strong>Tager:</strong> جاهزية الصفحات الحالية '.intval($audit['score']).'%. <a href="'.esc_url(admin_url('admin.php?page=tager-v46')).'">فتح مركز الفحص والإصلاح</a></p></div>';
        }
    }

    public static function prevent_empty_core_pages($content) {
        if (!is_page() || !in_the_loop() || !is_main_query()) return $content;
        global $post;
        if (!$post) return $content;
        $slugs = array_keys(self::pages());
        if (!in_array($post->post_name, $slugs, true)) return $content;
        if (trim(wp_strip_all_tags(strip_shortcodes($content))) !== '' || preg_match('/tager[_-]/', $content)) return $content;
        return self::page_hub_shortcode(['title'=>get_the_title($post),'message'=>'هذه الصفحة جاهزة، لكن الوحدة الأساسية لم تُحمّل بعد. استخدم الروابط التالية للمتابعة.']);
    }

    public static function page_hub_shortcode($atts=[]) {
        $atts = shortcode_atts(['title'=>'مركز تاجر','message'=>'اختر الصفحة المطلوبة.'], $atts);
        $kind = self::user_kind();
        $links = $kind === 'vendor'
            ? ['vendor-dashboard','vendor-market','vendor-orders','vendor-media','product-media','products']
            : ($kind === 'admin'
                ? ['admin-portal','admin-approvals','products','vendor-directory']
                : ['products','cart','customer-account','customer-orders','support']);
        ob_start(); ?>
        <section class="tv46-page-hub" dir="rtl">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p><?php echo esc_html($atts['message']); ?></p>
            <div class="tv46-hub-links">
                <?php foreach ($links as $slug): $cfg=self::pages()[$slug] ?? null; if (!$cfg) continue; ?>
                    <a href="<?php echo esc_url(self::page_url($slug)); ?>"><?php echo esc_html($cfg['title']); ?></a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php return ob_get_clean();
    }

    public static function frontend_assets() {
        wp_register_style('tager-v46-inline', false, [], self::VERSION);
        wp_enqueue_style('tager-v46-inline');
        $css = '.tv46-page-hub{max-width:1000px;margin:40px auto;padding:34px;border-radius:24px;background:#fff;box-shadow:0 18px 55px rgba(16,55,38,.12);border:1px solid #e8eee9}.tv46-page-hub h2{color:#123d2b;margin-top:0}.tv46-hub-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:24px}.tv46-hub-links a{display:block;text-align:center;padding:14px;border-radius:14px;background:#123d2b;color:#fff;text-decoration:none;font-weight:700}.tv46-hub-links a:hover{background:#c59a37;color:#102a20}';
        wp_add_inline_style('tager-v46-inline', $css);
    }

    public static function admin_assets($hook) {
        if (strpos((string)$hook, 'tager-v46') === false) return;
        wp_register_style('tager-v46-admin', false, [], self::VERSION);
        wp_enqueue_style('tager-v46-admin');
        $css = '.tager-v46-admin{max-width:1300px}.tv46-hero{display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#103d2a,#1f6849);color:#fff;padding:28px;border-radius:20px;margin:20px 0}.tv46-hero h1{color:#fff;margin:0 0 8px}.tv46-score{text-align:center;background:rgba(255,255,255,.12);padding:18px 26px;border-radius:16px}.tv46-score strong{display:block;font-size:36px}.tv46-score span{font-size:12px}.tv46-actions{display:flex;gap:12px;margin:18px 0}.tv46-actions form{margin:0}.tv46-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.tv46-card{display:flex;gap:12px;background:#fff;border:1px solid #dfe7e1;border-radius:14px;padding:16px;align-items:flex-start}.tv46-card.ok{border-right:5px solid #2f9d66}.tv46-card.bad{border-right:5px solid #d97706}.tv46-card small{display:block;color:#66756c;margin-top:5px}.tv46-links{display:flex;flex-wrap:wrap;gap:8px}.tv46-links a{background:#fff;border:1px solid #dfe7e1;padding:9px 12px;border-radius:10px;text-decoration:none}';
        wp_add_inline_style('tager-v46-admin', $css);
    }
}
Tager_V46_Launch_Control::init();
