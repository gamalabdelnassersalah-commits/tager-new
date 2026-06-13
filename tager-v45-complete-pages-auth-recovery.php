<?php
/**
 * Plugin Name: Tager V45 Complete Pages, Navigation & Account Recovery
 * Description: Completes missing pages, links every workspace, provides phone/email login and password recovery, and audits empty/broken pages.
 * Version: 45.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V45_Complete_Pages_Auth_Recovery {
    const VERSION = '45.0.0';
    const OPT = 'tager_v45_settings';
    const PAGES_VERSION = '45.0.0';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes'], 1200);
        add_action('init', [__CLASS__, 'maybe_install'], 1500);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 100);
        add_action('admin_post_tager_v45_repair', [__CLASS__, 'repair_action']);
        add_action('admin_post_tager_v45_save_settings', [__CLASS__, 'save_settings']);
        add_action('admin_post_nopriv_tager_v45_login', [__CLASS__, 'login_action']);
        add_action('admin_post_tager_v45_login', [__CLASS__, 'login_action']);
        add_action('admin_post_nopriv_tager_v45_request_reset', [__CLASS__, 'request_reset_action']);
        add_action('admin_post_tager_v45_request_reset', [__CLASS__, 'request_reset_action']);
        add_action('admin_post_nopriv_tager_v45_phone_reset', [__CLASS__, 'phone_reset_action']);
        add_action('admin_post_tager_v45_phone_reset', [__CLASS__, 'phone_reset_action']);
        add_filter('lostpassword_url', [__CLASS__, 'lostpassword_url'], 99, 2);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 999, 3);
        add_filter('show_admin_bar', [__CLASS__, 'hide_admin_bar']);
        add_action('template_redirect', [__CLASS__, 'protect_role_pages'], 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 999);
        add_action('login_enqueue_scripts', [__CLASS__, 'login_style']);
        add_action('wp_head', [__CLASS__, 'meta']);
    }

    private static function t($ar, $en) {
        $lang = isset($_GET['lang']) ? sanitize_key($_GET['lang']) : (isset($_COOKIE['tager_lang']) ? sanitize_key($_COOKIE['tager_lang']) : 'ar');
        return $lang === 'en' ? $en : $ar;
    }

    private static function settings() {
        return wp_parse_args(get_option(self::OPT, []), [
            'sms_webhook' => '', 'sms_bearer' => '', 'sms_sender' => 'Tager', 'sms_test_mode' => 1,
            'otp_expiry' => 10, 'support_phone' => '', 'support_email' => '',
        ]);
    }

    private static function page_defs() {
        return [
            'home' => ['الرئيسية','Home','[tager_v12_home]','public'],
            'products' => ['المنتجات','Products','[tager_v22_products]','public'],
            'categories' => ['الأقسام','Categories','[tager_v22_categories]','public'],
            'vendors' => ['دليل الموردين','Vendors','[tager_v22_vendors]','public'],
            'offers' => ['العروض','Offers','[tager_v22_offers]','public'],
            'brands' => ['العلامات التجارية','Brands','[tager_v22_brands]','public'],
            'login' => ['تسجيل الدخول','Sign in','[tager_v45_login]','guest'],
            'choose-account' => ['إنشاء حساب','Create account','[tager_v45_choose_account]','guest'],
            'customer-register' => ['تسجيل عميل','Customer registration','[tager_customer_register]','guest'],
            'vendor-register' => ['تسجيل مورد','Vendor registration','[tager_vendor_register]','guest'],
            'forgot-password' => ['نسيت كلمة المرور','Forgot password','[tager_v45_forgot_password]','guest'],
            'phone-reset' => ['تغيير كلمة المرور برقم الهاتف','Reset by phone','[tager_v45_phone_reset]','guest'],
            'customer-account' => ['حساب العميل','Customer account','[tager_v40_customer_workspace]','customer'],
            'customer-orders' => ['طلباتي','My orders','[tager_v42_customer_orders]','customer'],
            'customer-addresses' => ['عناويني','My addresses','[tager_v35_customer_addresses]','customer'],
            'customer-profile' => ['بياناتي','My profile','[tager_v35_customer_profile]','customer'],
            'wishlist' => ['المفضلة','Wishlist','[tager_v35_customer_wishlist]','customer'],
            'notifications' => ['الإشعارات','Notifications','[tager_v42_notifications]','logged'],
            'support' => ['الدعم','Support','[tager_v35_customer_support]','logged'],
            'cart' => ['السلة','Cart','[tager_cart]','public'],
            'checkout' => ['إتمام الطلب','Checkout','[tager_checkout]','customer'],
            'track-order' => ['تتبع الطلب','Track order','[tager_v34_track_order]','public'],
            'returns' => ['المرتجعات','Returns','[tager_v34_returns]','customer'],
            'invoices' => ['الفواتير','Invoices','[tager_v34_invoices]','customer'],
            'vendor-dashboard' => ['لوحة المورد','Vendor dashboard','[tager_v40_vendor_workspace]','vendor'],
            'vendor-products' => ['منتجات المورد','Vendor products','[tager_v35_vendor_products]','vendor'],
            'vendor-add-product' => ['إضافة منتج','Add product','[tager_v40_vendor_workspace]','vendor'],
            'vendor-orders' => ['طلبات المورد','Vendor orders','[tager_v42_vendor_orders]','vendor'],
            'vendor-earnings' => ['الأرباح والعمولات','Earnings','[tager_v35_vendor_earnings]','vendor'],
            'vendor-withdrawals' => ['طلبات السحب','Withdrawals','[tager_v35_vendor_withdrawals]','vendor'],
            'vendor-store-settings' => ['إعدادات المتجر','Store settings','[tager_v35_vendor_store_settings]','vendor'],
            'vendor-market' => ['سوق المورد','Vendor market','[tager_v39_vendor_market]','vendor'],
            'vendor-media' => ['صور وهوية المتجر','Store media','[tager_v44_profile_studio]','vendor'],
            'vendor-product-media' => ['صور المنتجات','Product media','[tager_v44_product_media_studio]','vendor'],
            'admin-portal' => ['بوابة الإدارة','Admin portal','[tager_v40_admin_workspace]','admin'],
            'admin-approvals' => ['مركز الموافقات','Approvals','[tager_v42_admin_approvals]','admin'],
            'about' => ['عن تاجر','About Tager','[tager_v22_about]','public'],
            'how-it-works' => ['كيف تعمل المنصة','How it works','[tager_v22_how_it_works]','public'],
            'buyer-guide' => ['دليل المشتري','Buyer guide','[tager_v22_buyer_guide]','public'],
            'vendor-guide' => ['دليل المورد','Vendor guide','[tager_v22_vendor_guide]','public'],
            'business-solutions' => ['حلول الشركات','Business solutions','[tager_v22_business_solutions]','public'],
            'pricing' => ['الأسعار والباقات','Pricing','[tager_v22_pricing]','public'],
            'faq' => ['الأسئلة الشائعة','FAQ','[tager_v22_faq]','public'],
            'contact' => ['تواصل معنا','Contact us','[tager_v22_contact]','public'],
            'shipping' => ['الشحن والتوصيل','Shipping','[tager_v22_shipping]','public'],
            'payment-methods' => ['طرق الدفع','Payment methods','[tager_v34_payment_methods]','public'],
            'return-policy' => ['سياسة الاسترجاع','Return policy','[tager_v22_return_policy]','public'],
            'terms' => ['الشروط والأحكام','Terms','[tager_v22_terms]','public'],
            'privacy' => ['سياسة الخصوصية','Privacy','[tager_v22_privacy]','public'],
            'help-center' => ['مركز المساعدة','Help center','[tager_v22_help_center]','public'],
            'site-map' => ['خريطة الموقع','Site map','[tager_v45_site_map]','public'],
        ];
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v45_login', [__CLASS__, 'login_page']);
        add_shortcode('tager_v45_choose_account', [__CLASS__, 'choose_account']);
        add_shortcode('tager_v45_forgot_password', [__CLASS__, 'forgot_page']);
        add_shortcode('tager_v45_phone_reset', [__CLASS__, 'phone_reset_page']);
        add_shortcode('tager_v45_site_map', [__CLASS__, 'site_map']);
        add_shortcode('tager_v45_page_fallback', [__CLASS__, 'fallback_page']);
    }

    public static function maybe_install() {
        if (get_option('tager_v45_pages_version') !== self::PAGES_VERSION) {
            self::repair_all();
            update_option('tager_v45_pages_version', self::PAGES_VERSION, false);
        }
    }

    private static function valid_shortcode_content($content) {
        if (!preg_match_all('/\[([a-zA-Z0-9_-]+)/', (string)$content, $m)) return trim(wp_strip_all_tags($content)) !== '';
        foreach ($m[1] as $tag) if (shortcode_exists($tag)) return true;
        return false;
    }

    private static function fallback_content($slug, $ar, $en, $role) {
        $links = self::context_links($role);
        $title = self::t($ar, $en);
        $out = '<section class="t45-shell"><div class="t45-hero"><span class="t45-kicker">Tager Marketplace</span><h1>'.esc_html($title).'</h1><p>'.esc_html(self::t('هذه الصفحة جزء من منصة تاجر وتم ربطها بمسار الاستخدام الصحيح.','This page is part of Tager Marketplace and is linked to the correct workflow.')).'</p></div>';
        $out .= '<div class="t45-grid">';
        foreach ($links as $l) $out .= '<a class="t45-card" href="'.esc_url(self::url($l[0])).'"><strong>'.esc_html(self::t($l[1],$l[2])).'</strong><span>←</span></a>';
        $out .= '</div></section>';
        return $out;
    }

    private static function context_links($role) {
        if ($role === 'vendor') return [['vendor-dashboard','لوحة المورد','Vendor dashboard'],['vendor-products','منتجاتي','My products'],['vendor-add-product','إضافة منتج','Add product'],['vendor-orders','الطلبات','Orders'],['vendor-market','السوق','Market']];
        if ($role === 'customer') return [['customer-account','حسابي','My account'],['products','تسوق','Shop'],['customer-orders','طلباتي','My orders'],['customer-addresses','عناويني','Addresses'],['cart','السلة','Cart']];
        if ($role === 'admin') return [['admin-portal','بوابة الإدارة','Admin portal'],['admin-approvals','الموافقات','Approvals'],['products','عرض السوق','View market']];
        return [['products','تصفح المنتجات','Browse products'],['vendors','دليل الموردين','Vendors'],['how-it-works','كيف تعمل المنصة','How it works'],['contact','تواصل معنا','Contact us']];
    }

    public static function repair_all() {
        $ids=[];
        foreach (self::page_defs() as $slug=>$d) {
            [$ar,$en,$content,$role]=$d;
            $p=get_page_by_path($slug, OBJECT, 'page');
            $needs = !$p || trim((string)$p->post_content)==='' || !self::valid_shortcode_content($p->post_content);
            if (!$p) {
                $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$ar,'post_name'=>$slug,'post_content'=>$content]);
            } else {
                $id=$p->ID;
                if ($needs) wp_update_post(['ID'=>$id,'post_title'=>$ar,'post_content'=>$content]);
            }
            if ($id && !is_wp_error($id)) {
                update_post_meta($id,'_tager_v45_role',$role);
                update_post_meta($id,'_tager_v45_title_en',$en);
                $ids[$slug]=$id;
            }
        }
        if (!empty($ids['home'])) { update_option('show_on_front','page'); update_option('page_on_front',$ids['home']); }
        self::menus($ids);
        flush_rewrite_rules(false);
        return $ids;
    }

    private static function menus($ids) {
        $locations=['primary'=>'القائمة الرئيسية','account'=>'قائمة الحساب','footer'=>'قائمة الفوتر'];
        $groups=[
            'primary'=>['home','products','categories','vendors','offers','how-it-works','contact'],
            'account'=>['login','choose-account','customer-account','customer-orders','vendor-dashboard','vendor-products','vendor-orders','forgot-password'],
            'footer'=>['about','buyer-guide','vendor-guide','shipping','payment-methods','return-policy','terms','privacy','help-center','site-map'],
        ];
        foreach($locations as $loc=>$name){
            $menu=wp_get_nav_menu_object($name); $mid=$menu?$menu->term_id:wp_create_nav_menu($name);
            if(is_wp_error($mid)) continue;
            foreach(wp_get_nav_menu_items($mid)?:[] as $it) wp_delete_post($it->ID,true);
            foreach($groups[$loc] as $slug){if(empty($ids[$slug]))continue; wp_update_nav_menu_item($mid,0,['menu-item-title'=>get_the_title($ids[$slug]),'menu-item-object'=>'page','menu-item-object-id'=>$ids[$slug],'menu-item-type'=>'post_type','menu-item-status'=>'publish']);}
            $mods=get_theme_mod('nav_menu_locations',[]);$mods[$loc]=$mid;set_theme_mod('nav_menu_locations',$mods);
        }
    }

    public static function url($slug) {
        $p=get_page_by_path($slug,OBJECT,'page'); return $p?get_permalink($p):home_url('/'.$slug.'/');
    }

    private static function notice() {
        $code=sanitize_key($_GET['tager_notice']??'');
        $map=[
            'login_failed'=>['بيانات الدخول غير صحيحة.','Invalid login details.','error'],
            'phone_required'=>['أدخل رقم الهاتف أو البريد الإلكتروني.','Enter phone or email.','error'],
            'reset_email_sent'=>['تم إرسال رابط تغيير كلمة المرور إلى البريد إن كان الحساب موجودًا.','A reset link was sent if the account exists.','success'],
            'otp_sent'=>['تم إرسال رمز التحقق إلى رقم الهاتف.','Verification code sent to your phone.','success'],
            'otp_test'=>['وضع الاختبار مفعل. رمز التحقق ظاهر أدناه.','Test mode is on. The verification code is shown below.','warning'],
            'otp_invalid'=>['رمز التحقق غير صحيح أو انتهت صلاحيته.','Invalid or expired verification code.','error'],
            'password_changed'=>['تم تغيير كلمة المرور. يمكنك تسجيل الدخول الآن.','Password changed. You can sign in now.','success'],
            'password_mismatch'=>['كلمتا المرور غير متطابقتين أو قصيرتان.','Passwords do not match or are too short.','error'],
            'sms_not_configured'=>['تعذر إرسال الرسالة لأن بوابة SMS غير مفعلة. استخدم البريد أو تواصل مع الدعم.','SMS gateway is not configured. Use email or contact support.','error'],
        ];
        if(!$code||empty($map[$code]))return '';
        $m=$map[$code];$extra='';
        if($code==='otp_test' && current_user_can('manage_options') && !empty($_GET['otp']))$extra='<div class="t45-otp">'.esc_html(sanitize_text_field($_GET['otp'])).'</div>';
        return '<div class="t45-notice '.$m[2].'">'.esc_html(self::t($m[0],$m[1])).$extra.'</div>';
    }

    public static function login_page() {
        if(is_user_logged_in()) return self::role_dashboard_link();
        ob_start(); ?>
        <section class="t45-auth-layout">
          <aside class="t45-auth-aside"><span class="t45-kicker">Tager Marketplace</span><h1><?php echo esc_html(self::t('ادخل إلى حسابك','Welcome back'));?></h1><p><?php echo esc_html(self::t('استخدم رقم الهاتف، أو البريد الإلكتروني إذا كان مضافًا إلى حسابك.','Use your phone number, or your email if it exists on your account.'));?></p><ul><li>✓ <?php echo esc_html(self::t('حساب عميل ومتابعة الطلبات','Customer account and order tracking'));?></li><li>✓ <?php echo esc_html(self::t('لوحة مورد وإدارة المنتجات','Vendor dashboard and product management'));?></li><li>✓ <?php echo esc_html(self::t('بوابة إدارة حسب الصلاحيات','Permission-based admin portal'));?></li></ul></aside>
          <div class="t45-auth-card"><?php echo self::notice();?><h2><?php echo esc_html(self::t('تسجيل الدخول','Sign in'));?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="t45-form">
              <input type="hidden" name="action" value="tager_v45_login"><?php wp_nonce_field('tager_v45_login'); ?>
              <label><?php echo esc_html(self::t('رقم الهاتف أو البريد الإلكتروني','Phone or email'));?><input name="identifier" required autocomplete="username" placeholder="01012345678"></label>
              <label><?php echo esc_html(self::t('كلمة المرور','Password'));?><div class="t45-password"><input id="t45-pass" type="password" name="password" required autocomplete="current-password"><button type="button" data-toggle-password="t45-pass">👁</button></div></label>
              <label class="t45-check"><input type="checkbox" name="remember" value="1"> <?php echo esc_html(self::t('تذكرني','Remember me'));?></label>
              <button class="t45-btn primary" type="submit"><?php echo esc_html(self::t('دخول','Sign in'));?></button>
            </form>
            <div class="t45-auth-links"><a href="<?php echo esc_url(self::url('forgot-password'));?>"><?php echo esc_html(self::t('نسيت كلمة المرور؟','Forgot password?'));?></a><a href="<?php echo esc_url(self::url('choose-account'));?>"><?php echo esc_html(self::t('إنشاء حساب جديد','Create an account'));?></a></div>
          </div>
        </section><?php return ob_get_clean();
    }

    public static function choose_account() {
        if(is_user_logged_in())return self::role_dashboard_link();
        return '<section class="t45-shell"><div class="t45-hero"><span class="t45-kicker">Tager</span><h1>'.esc_html(self::t('اختر نوع الحساب','Choose account type')).'</h1><p>'.esc_html(self::t('رقم الهاتف مطلوب، والبريد الإلكتروني اختياري في جميع أنواع الحسابات.','Phone is required and email is optional for all account types.')).'</p></div><div class="t45-account-grid"><a class="t45-account customer" href="'.esc_url(self::url('customer-register')).'"><span>🛍️</span><h2>'.esc_html(self::t('حساب عميل','Customer account')).'</h2><p>'.esc_html(self::t('تسوق، احفظ العناوين، وتابع طلباتك.','Shop, save addresses and track orders.')).'</p><b>'.esc_html(self::t('سجل كعميل','Register as customer')).' ←</b></a><a class="t45-account vendor" href="'.esc_url(self::url('vendor-register')).'"><span>🏪</span><h2>'.esc_html(self::t('حساب مورد','Vendor account')).'</h2><p>'.esc_html(self::t('أضف المنتجات والأسعار والمخزون بعد موافقة الإدارة.','Add products, prices and stock after approval.')).'</p><b>'.esc_html(self::t('انضم كمورد','Join as vendor')).' ←</b></a></div></section>';
    }

    public static function forgot_page() {
        if(is_user_logged_in()) return self::role_dashboard_link();
        ob_start(); ?>
        <section class="t45-auth-layout"><aside class="t45-auth-aside"><span class="t45-kicker">Account Recovery</span><h1><?php echo esc_html(self::t('استعادة الحساب بسهولة','Recover your account'));?></h1><p><?php echo esc_html(self::t('اختر البريد الإلكتروني أو رقم الهاتف. رمز الهاتف صالح لفترة محدودة.','Choose email or phone. Phone codes expire after a limited time.'));?></p></aside><div class="t45-auth-card"><?php echo self::notice();?><h2><?php echo esc_html(self::t('نسيت كلمة المرور','Forgot password'));?></h2><div class="t45-tabs"><button type="button" class="active" data-tab="phone"><?php echo esc_html(self::t('برقم الهاتف','By phone'));?></button><button type="button" data-tab="email"><?php echo esc_html(self::t('بالبريد','By email'));?></button></div>
        <form id="t45-tab-phone" class="t45-form t45-tab-panel active" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v45_request_reset"><input type="hidden" name="method" value="phone"><?php wp_nonce_field('tager_v45_request_reset');?><label><?php echo esc_html(self::t('رقم الهاتف','Phone number'));?><input name="identifier" required inputmode="tel" placeholder="01012345678"></label><button class="t45-btn primary"><?php echo esc_html(self::t('إرسال رمز التحقق','Send verification code'));?></button></form>
        <form id="t45-tab-email" class="t45-form t45-tab-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v45_request_reset"><input type="hidden" name="method" value="email"><?php wp_nonce_field('tager_v45_request_reset');?><label><?php echo esc_html(self::t('البريد الإلكتروني','Email'));?><input type="email" name="identifier" required></label><button class="t45-btn primary"><?php echo esc_html(self::t('إرسال رابط التغيير','Send reset link'));?></button></form>
        <p class="t45-help"><?php echo esc_html(self::t('لا تملك بريدًا؟ استخدم رقم الهاتف. إرسال SMS الحقيقي يحتاج تفعيل بوابة الرسائل من الإدارة.','No email? Use your phone. Real SMS delivery requires the admin to configure an SMS gateway.'));?></p><a href="<?php echo esc_url(self::url('login'));?>">← <?php echo esc_html(self::t('العودة لتسجيل الدخول','Back to sign in'));?></a></div></section><?php return ob_get_clean();
    }

    public static function phone_reset_page() {
        if(is_user_logged_in()) return self::role_dashboard_link();
        $phone=sanitize_text_field($_GET['phone']??'');
        ob_start(); ?>
        <section class="t45-auth-layout"><aside class="t45-auth-aside"><span class="t45-kicker">Secure Reset</span><h1><?php echo esc_html(self::t('تعيين كلمة مرور جديدة','Set a new password'));?></h1><p><?php echo esc_html(self::t('أدخل الرمز المرسل للهاتف ثم اختر كلمة مرور قوية.','Enter the code sent to your phone and choose a strong password.'));?></p></aside><div class="t45-auth-card"><?php echo self::notice();?><h2><?php echo esc_html(self::t('تغيير كلمة المرور','Change password'));?></h2><form class="t45-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v45_phone_reset"><?php wp_nonce_field('tager_v45_phone_reset');?><label><?php echo esc_html(self::t('رقم الهاتف','Phone'));?><input name="phone" value="<?php echo esc_attr($phone);?>" required inputmode="tel"></label><label><?php echo esc_html(self::t('رمز التحقق','Verification code'));?><input name="otp" required inputmode="numeric" maxlength="6"></label><label><?php echo esc_html(self::t('كلمة المرور الجديدة','New password'));?><input type="password" name="password" required minlength="8"></label><label><?php echo esc_html(self::t('تأكيد كلمة المرور','Confirm password'));?><input type="password" name="confirm_password" required minlength="8"></label><button class="t45-btn primary"><?php echo esc_html(self::t('حفظ كلمة المرور','Save password'));?></button></form></div></section><?php return ob_get_clean();
    }

    public static function site_map() {
        $groups=['التسوق'=>['home','products','categories','vendors','offers','brands'],'الحساب'=>['login','choose-account','forgot-password','customer-account','customer-orders','vendor-dashboard'],'المساعدة والسياسات'=>['how-it-works','buyer-guide','vendor-guide','shipping','payment-methods','return-policy','terms','privacy','contact']];
        $defs=self::page_defs();$out='<section class="t45-shell"><div class="t45-hero"><h1>'.esc_html(self::t('خريطة الموقع','Site map')).'</h1></div><div class="t45-sitemap">';foreach($groups as $g=>$slugs){$out.='<div class="t45-card"><h2>'.esc_html($g).'</h2><ul>';foreach($slugs as $s){if(isset($defs[$s]))$out.='<li><a href="'.esc_url(self::url($s)).'">'.esc_html(self::t($defs[$s][0],$defs[$s][1])).'</a></li>';}$out.='</ul></div>';}$out.='</div></section>';return $out;
    }

    public static function fallback_page($atts=[]) { $a=shortcode_atts(['slug'=>'page','title_ar'=>'صفحة تاجر','title_en'=>'Tager page','role'=>'public'],$atts); return self::fallback_content($a['slug'],$a['title_ar'],$a['title_en'],$a['role']); }

    private static function normalize_phone($phone) { $p=preg_replace('/\D+/','',(string)$phone); if(str_starts_with($p,'20')&&strlen($p)===12)$p='0'.substr($p,2); return $p; }
    private static function user_by_identifier($id) {
        $id=trim((string)$id); if(is_email($id)) return get_user_by('email',$id);
        $phone=self::normalize_phone($id); if(!$phone)return false;
        $q=new WP_User_Query(['number'=>1,'meta_query'=>['relation'=>'OR',['key'=>'phone','value'=>$phone],['key'=>'tager_phone','value'=>$phone],['key'=>'billing_phone','value'=>$phone]]]);$r=$q->get_results();return $r?$r[0]:false;
    }

    public static function login_action() {
        check_admin_referer('tager_v45_login');
        $id=sanitize_text_field(wp_unslash($_POST['identifier']??''));$pass=(string)($_POST['password']??'');$u=self::user_by_identifier($id);
        if(!$u && !is_email($id))$u=get_user_by('login',$id);
        if(!$u){self::go('login','login_failed');}
        $creds=['user_login'=>$u->user_login,'user_password'=>$pass,'remember'=>!empty($_POST['remember'])];$signed=wp_signon($creds,is_ssl());
        if(is_wp_error($signed))self::go('login','login_failed');
        wp_safe_redirect(self::dashboard_url($signed));exit;
    }

    public static function request_reset_action() {
        check_admin_referer('tager_v45_request_reset');
        $method=sanitize_key($_POST['method']??'phone');$id=sanitize_text_field(wp_unslash($_POST['identifier']??''));
        if($method==='email'){
            if(is_email($id)) retrieve_password($id);
            self::go('forgot-password','reset_email_sent');
        }
        $u=self::user_by_identifier($id);$phone=self::normalize_phone($id);
        if(!$u){self::go('forgot-password','otp_sent');}
        $otp=(string)random_int(100000,999999);$s=self::settings();$exp=max(3,min(30,(int)$s['otp_expiry']));
        set_transient('tager_v45_otp_'.$u->ID,['hash'=>wp_hash_password($otp),'phone'=>$phone,'attempts'=>0],$exp*MINUTE_IN_SECONDS);
        $sent=self::send_sms($phone,sprintf(self::t('رمز تغيير كلمة المرور في تاجر هو: %s. صالح لمدة %d دقائق.','Your Tager password reset code is: %s. Valid for %d minutes.'),$otp,$exp),$u->ID);
        if(!$sent && empty($s['sms_test_mode']))self::go('forgot-password','sms_not_configured');
        $url=add_query_arg(['tager_notice'=>!empty($s['sms_test_mode'])?'otp_test':'otp_sent','phone'=>$phone],self::url('phone-reset'));
        if(!empty($s['sms_test_mode']) && current_user_can('manage_options'))$url=add_query_arg('otp',$otp,$url);
        wp_safe_redirect($url);exit;
    }

    private static function send_sms($phone,$message,$uid) {
        $s=self::settings();$ok=false;
        if(!empty($s['sms_webhook'])){
            $headers=['Content-Type'=>'application/json'];if(!empty($s['sms_bearer']))$headers['Authorization']='Bearer '.$s['sms_bearer'];
            $r=wp_remote_post($s['sms_webhook'],['timeout'=>15,'headers'=>$headers,'body'=>wp_json_encode(['to'=>$phone,'message'=>$message,'sender'=>$s['sms_sender'],'user_id'=>$uid])]);
            $ok=!is_wp_error($r)&&wp_remote_retrieve_response_code($r)>=200&&wp_remote_retrieve_response_code($r)<300;
        }
        do_action('tager_v45_send_sms',$phone,$message,$uid);
        return (bool)apply_filters('tager_v45_sms_sent',$ok,$phone,$message,$uid);
    }

    public static function phone_reset_action() {
        check_admin_referer('tager_v45_phone_reset');$phone=self::normalize_phone($_POST['phone']??'');$otp=preg_replace('/\D/','',(string)($_POST['otp']??''));$p=(string)($_POST['password']??'');$c=(string)($_POST['confirm_password']??'');
        if(strlen($p)<8||$p!==$c)self::go('phone-reset','password_mismatch',['phone'=>$phone]);
        $u=self::user_by_identifier($phone);if(!$u)self::go('phone-reset','otp_invalid',['phone'=>$phone]);$data=get_transient('tager_v45_otp_'.$u->ID);
        if(!$data||empty($data['hash'])||!wp_check_password($otp,$data['hash'])){
            if($data){$data['attempts']=(int)($data['attempts']??0)+1;if($data['attempts']>=5)delete_transient('tager_v45_otp_'.$u->ID);else set_transient('tager_v45_otp_'.$u->ID,$data,5*MINUTE_IN_SECONDS);}self::go('phone-reset','otp_invalid',['phone'=>$phone]);
        }
        wp_set_password($p,$u->ID);delete_transient('tager_v45_otp_'.$u->ID);self::go('login','password_changed');
    }

    private static function go($slug,$notice,$args=[]) { $args['tager_notice']=$notice;wp_safe_redirect(add_query_arg($args,self::url($slug)));exit; }
    public static function lostpassword_url($url,$redirect='') { return self::url('forgot-password'); }

    private static function role($u=null) { $u=$u?:wp_get_current_user();$r=(array)$u->roles;if(user_can($u,'manage_options')||array_intersect($r,['administrator','tager_platform_manager','tager_operations_manager','tager_vendor_manager','tager_catalog_manager','tager_order_manager','tager_finance_manager','tager_support_agent','tager_marketing_manager','tager_viewer']))return 'admin';if(in_array('vendor',$r,true)||in_array('tager_vendor',$r,true)||get_user_meta($u->ID,'vendor_status',true))return 'vendor';return 'customer'; }
    private static function dashboard_url($u=null){$r=self::role($u);return self::url($r==='admin'?'admin-portal':($r==='vendor'?'vendor-dashboard':'customer-account'));}
    public static function login_redirect($redirect,$requested,$user){return $user instanceof WP_User?self::dashboard_url($user):$redirect;}
    public static function role_dashboard_link(){return '<section class="t45-shell"><div class="t45-hero"><h1>'.esc_html(self::t('أنت مسجل الدخول','You are signed in')).'</h1><a class="t45-btn primary" href="'.esc_url(self::dashboard_url()).'">'.esc_html(self::t('فتح لوحة الحساب','Open dashboard')).'</a></div></section>';}
    public static function hide_admin_bar($show){if(!is_user_logged_in())return false;return self::role()==='admin'?$show:false;}

    public static function protect_role_pages(){if(is_admin()||wp_doing_ajax())return;$p=get_queried_object();if(!$p||empty($p->ID))return;$need=get_post_meta($p->ID,'_tager_v45_role',true);if(!$need||in_array($need,['public','guest'],true))return;if(!is_user_logged_in()){wp_safe_redirect(add_query_arg('redirect_to',rawurlencode(get_permalink($p->ID)),self::url('login')));exit;}$role=self::role();if($need==='logged')return;if($need!==$role){wp_safe_redirect(self::dashboard_url());exit;}}

    public static function assets(){
        $css=':root{--t45-green:#123c2d;--t45-green2:#1d5b43;--t45-gold:#d2a93b;--t45-bg:#f5f7f5;--t45-text:#18211d;--t45-muted:#65726b;--t45-border:#dfe7e2} .t45-shell,.t45-auth-layout{max-width:1180px;margin:36px auto;padding:0 20px;font-family:inherit;color:var(--t45-text)}.t45-hero{background:linear-gradient(135deg,var(--t45-green),var(--t45-green2));color:#fff;padding:44px;border-radius:24px;margin-bottom:24px}.t45-hero h1{font-size:clamp(30px,5vw,52px);margin:8px 0 14px}.t45-kicker{color:#f0d985;text-transform:uppercase;font-weight:800;letter-spacing:.08em}.t45-grid,.t45-sitemap,.t45-account-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px}.t45-card,.t45-account{display:flex;justify-content:space-between;gap:16px;background:#fff;border:1px solid var(--t45-border);border-radius:18px;padding:22px;text-decoration:none;color:var(--t45-text);box-shadow:0 8px 28px rgba(18,60,45,.06)}.t45-account{display:block;padding:32px}.t45-account>span{font-size:42px}.t45-account b{display:block;margin-top:20px;color:var(--t45-green)}.t45-auth-layout{display:grid;grid-template-columns:1fr 1fr;gap:0;min-height:620px;border-radius:26px;overflow:hidden;box-shadow:0 18px 60px rgba(18,60,45,.14);background:#fff}.t45-auth-aside{background:linear-gradient(145deg,var(--t45-green),#0c2a20);color:#fff;padding:56px}.t45-auth-aside h1{font-size:42px;line-height:1.15}.t45-auth-aside li{margin:15px 0}.t45-auth-card{padding:54px;align-self:center}.t45-auth-card h2{font-size:32px;margin:0 0 24px}.t45-form{display:grid;gap:16px}.t45-form label{display:grid;gap:7px;font-weight:700}.t45-form input,.t45-form select{width:100%;box-sizing:border-box;border:1px solid var(--t45-border);border-radius:12px;padding:13px 14px;font:inherit;background:#fff}.t45-form input:focus{outline:3px solid rgba(210,169,59,.23);border-color:var(--t45-gold)}.t45-password{display:flex;position:relative}.t45-password input{padding-inline-end:52px}.t45-password button{position:absolute;inset-inline-end:6px;top:6px;border:0;background:transparent;padding:8px;cursor:pointer}.t45-check{display:flex!important;grid-template-columns:auto 1fr!important;align-items:center}.t45-check input{width:auto}.t45-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;padding:14px 20px;font-weight:800;text-decoration:none;cursor:pointer}.t45-btn.primary{background:var(--t45-gold);color:#172018}.t45-auth-links{display:flex;justify-content:space-between;gap:14px;margin-top:22px}.t45-auth-links a,.t45-auth-card>a{color:var(--t45-green);font-weight:700}.t45-notice{padding:13px 15px;border-radius:12px;margin-bottom:18px}.t45-notice.error{background:#fff0f0;color:#9c1b1b}.t45-notice.success{background:#eaf8ef;color:#176438}.t45-notice.warning{background:#fff6da;color:#745800}.t45-otp{font-size:28px;font-weight:900;letter-spacing:.25em;margin-top:8px}.t45-tabs{display:flex;gap:8px;margin-bottom:18px}.t45-tabs button{flex:1;padding:11px;border-radius:10px;border:1px solid var(--t45-border);background:#fff;cursor:pointer}.t45-tabs button.active{background:var(--t45-green);color:#fff}.t45-tab-panel{display:none}.t45-tab-panel.active{display:grid}.t45-help{font-size:14px;color:var(--t45-muted);line-height:1.7}.t45-sitemap ul{padding-inline-start:22px}.t45-sitemap li{margin:10px 0}@media(max-width:760px){.t45-auth-layout{grid-template-columns:1fr}.t45-auth-aside{padding:32px}.t45-auth-aside h1{font-size:32px}.t45-auth-card{padding:30px 22px}.t45-auth-links{flex-direction:column}.t45-hero{padding:28px}}';
        wp_register_style('tager-v45',false,[],self::VERSION);wp_enqueue_style('tager-v45');wp_add_inline_style('tager-v45',$css);
        $js="document.addEventListener('click',function(e){var b=e.target.closest('[data-toggle-password]');if(b){var i=document.getElementById(b.dataset.togglePassword);if(i)i.type=i.type==='password'?'text':'password';}var t=e.target.closest('[data-tab]');if(t){var root=t.closest('.t45-auth-card');root.querySelectorAll('[data-tab]').forEach(x=>x.classList.remove('active'));root.querySelectorAll('.t45-tab-panel').forEach(x=>x.classList.remove('active'));t.classList.add('active');var p=root.querySelector('#t45-tab-'+t.dataset.tab);if(p)p.classList.add('active');}});document.addEventListener('submit',function(e){var b=e.target.querySelector('button[type=submit]');if(b&&!b.disabled){b.disabled=true;b.dataset.old=b.textContent;b.textContent='...';}});";
        wp_register_script('tager-v45',false,[],self::VERSION,true);wp_enqueue_script('tager-v45');wp_add_inline_script('tager-v45',$js);
    }

    public static function login_style(){echo '<style>body.login{background:#f5f7f5}.login h1 a{background:none!important;width:auto!important;height:auto!important;text-indent:0!important;font-size:28px;color:#123c2d;font-weight:900}.login h1 a:after{content:"Tager"}.wp-core-ui .button-primary{background:#123c2d;border-color:#123c2d}.login form{border-radius:18px;border:0;box-shadow:0 12px 40px rgba(18,60,45,.12)}</style>';}
    public static function meta(){echo '<meta name="theme-color" content="#123c2d">';}

    public static function admin_menu(){add_menu_page('Tager V45','Tager V45','manage_options','tager-v45',[__CLASS__,'admin_page'],'dashicons-admin-site-alt3',3);add_submenu_page('tager-v45','فحص الصفحات','فحص الصفحات','manage_options','tager-v45',[__CLASS__,'admin_page']);add_submenu_page('tager-v45','استعادة كلمة المرور','إعدادات الاستعادة','manage_options','tager-v45-recovery',[__CLASS__,'settings_page']);}
    public static function admin_page(){if(!current_user_can('manage_options'))return;$defs=self::page_defs();$rows=[];foreach($defs as $slug=>$d){$p=get_page_by_path($slug,OBJECT,'page');$state='ok';$note='جاهزة';if(!$p){$state='bad';$note='غير موجودة';}elseif(trim($p->post_content)===''){$state='bad';$note='فارغة';}elseif(!self::valid_shortcode_content($p->post_content)){$state='warn';$note='المحتوى أو الشورت كود يحتاج إصلاح';}$rows[]=[$slug,$d[0],$d[3],$state,$note,$p];}$ok=count(array_filter($rows,fn($r)=>$r[3]==='ok'));?><div class="wrap"><h1>Tager V45 — اكتمال الصفحات والروابط</h1><p>الصفحات الجاهزة: <strong><?php echo $ok;?> / <?php echo count($rows);?></strong></p><p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v45_repair'),'tager_v45_repair'));?>">إصلاح وإنشاء وربط كل الصفحات</a> <a class="button" target="_blank" href="<?php echo esc_url(self::url('login'));?>">اختبار تسجيل الدخول</a> <a class="button" target="_blank" href="<?php echo esc_url(self::url('forgot-password'));?>">اختبار نسيت كلمة المرور</a></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>الصلاحية</th><th>الحالة</th><th>فتح</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?php echo esc_html($r[1]);?></strong></td><td><code>/<?php echo esc_html($r[0]);?>/</code></td><td><?php echo esc_html($r[2]);?></td><td><span style="color:<?php echo $r[3]==='ok'?'green':($r[3]==='warn'?'#9a6500':'#b42318');?>"><?php echo esc_html($r[4]);?></span></td><td><?php if($r[5]):?><a target="_blank" href="<?php echo esc_url(get_permalink($r[5]));?>">فتح</a> | <a href="<?php echo esc_url(get_edit_post_link($r[5]->ID));?>">تعديل</a><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php }
    public static function settings_page(){$s=self::settings();?><div class="wrap"><h1>إعداد استعادة كلمة المرور</h1><div class="notice notice-warning"><p><strong>مهم:</strong> إرسال رمز فعلي للهاتف يحتاج حساب SMS وWebhook. البريد يستخدم نظام WordPress/SMTP.</p></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v45_save_settings"><?php wp_nonce_field('tager_v45_save_settings');?><table class="form-table"><tr><th>SMS Webhook URL</th><td><input class="regular-text" type="url" name="sms_webhook" value="<?php echo esc_attr($s['sms_webhook']);?>"><p class="description">يستقبل JSON: to, message, sender, user_id</p></td></tr><tr><th>Bearer Token</th><td><input class="regular-text" type="password" name="sms_bearer" value="<?php echo esc_attr($s['sms_bearer']);?>"></td></tr><tr><th>اسم المرسل</th><td><input name="sms_sender" value="<?php echo esc_attr($s['sms_sender']);?>"></td></tr><tr><th>مدة الرمز بالدقائق</th><td><input type="number" min="3" max="30" name="otp_expiry" value="<?php echo (int)$s['otp_expiry'];?>"></td></tr><tr><th>وضع الاختبار</th><td><label><input type="checkbox" name="sms_test_mode" value="1" <?php checked($s['sms_test_mode']);?>> لاستخدامه قبل ربط بوابة SMS</label></td></tr><tr><th>هاتف الدعم</th><td><input name="support_phone" value="<?php echo esc_attr($s['support_phone']);?>"></td></tr><tr><th>بريد الدعم</th><td><input type="email" name="support_email" value="<?php echo esc_attr($s['support_email']);?>"></td></tr></table><?php submit_button('حفظ الإعدادات');?></form></div><?php }
    public static function save_settings(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer('tager_v45_save_settings');update_option(self::OPT,['sms_webhook'=>esc_url_raw($_POST['sms_webhook']??''),'sms_bearer'=>sanitize_text_field($_POST['sms_bearer']??''),'sms_sender'=>sanitize_text_field($_POST['sms_sender']??'Tager'),'otp_expiry'=>max(3,min(30,(int)($_POST['otp_expiry']??10))),'sms_test_mode'=>!empty($_POST['sms_test_mode'])?1:0,'support_phone'=>sanitize_text_field($_POST['support_phone']??''),'support_email'=>sanitize_email($_POST['support_email']??'')],false);wp_safe_redirect(admin_url('admin.php?page=tager-v45-recovery&updated=1'));exit;}
    public static function repair_action(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer('tager_v45_repair');self::repair_all();wp_safe_redirect(admin_url('admin.php?page=tager-v45&repaired=1'));exit;}
}
Tager_V45_Complete_Pages_Auth_Recovery::init();
