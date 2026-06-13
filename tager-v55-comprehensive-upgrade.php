<?php
/**
 * Plugin Name: Tager V55 Comprehensive Upgrade
 * Description: Unified role portals, complete page registry, navigation, data completeness and launch audit.
 * Version: 55.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V55_Comprehensive_Upgrade {
    const VERSION = '55.0.0';
    const OPT = 'tager_v55_repaired';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes'], 99);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 99);
        add_action('admin_post_tager_v55_repair', [__CLASS__, 'repair_action']);
        add_action('admin_post_tager_v55_run_test', [__CLASS__, 'test_action']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 100, 3);
        add_action('template_redirect', [__CLASS__, 'guard_workspaces'], 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 100);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets'], 100);
        add_action('wp_footer', [__CLASS__, 'frontend_script'], 100);
        add_filter('the_content', [__CLASS__, 'never_empty_content'], 999);
        if (!get_option(self::OPT)) add_action('init', [__CLASS__, 'repair_once'], 999);
    }

    public static function pages() {
        return [
            'login' => ['تسجيل الدخول','[tager_v45_login]'],
            'choose-account' => ['إنشاء حساب','[tager_v45_choose_account]'],
            'customer-register' => ['تسجيل عميل','[tager_customer_register]'],
            'vendor-register' => ['تسجيل مورد','[tager_vendor_register]'],
            'forgot-password' => ['نسيت كلمة المرور','[tager_v45_forgot_password]'],
            'phone-password-reset' => ['استعادة كلمة المرور بالهاتف','[tager_v45_phone_reset]'],
            'customer-account' => ['حساب العميل','[tager_v55_customer_portal]'],
            'customer-orders' => ['طلبات العميل','[tager_v42_customer_orders]'],
            'customer-order-details' => ['تفاصيل الطلب','[tager_v49_customer_order_details]'],
            'customer-addresses' => ['عناويني','[tager_v55_customer_addresses]'],
            'customer-profile' => ['بياناتي الشخصية','[tager_v55_customer_profile]'],
            'customer-security' => ['أمان الحساب','[tager_v49_customer_security]'],
            'wishlist' => ['المفضلة','[tager_pro_wishlist]'],
            'saved-carts' => ['السلات المحفوظة','[tager_v49_saved_carts]'],
            'notifications' => ['الإشعارات','[tager_v42_notifications]'],
            'support' => ['الدعم','[tager_support]'],
            'vendor-dashboard' => ['لوحة المورد','[tager_v55_vendor_portal]'],
            'vendor-products' => ['منتجات المورد','[tager_v55_vendor_products]'],
            'vendor-add-product' => ['إضافة منتج','[tager_v54_product_form]'],
            'vendor-orders' => ['طلبات المورد','[tager_v42_vendor_orders]'],
            'vendor-inventory' => ['مخزون المورد','[tager_v49_vendor_inventory]'],
            'vendor-earnings' => ['أرباح المورد','[tager_v55_vendor_earnings]'],
            'vendor-market' => ['سوق المورد','[tager_v48_market]'],
            'vendor-settings' => ['إعدادات المتجر','[tager_v47_vendor_location_settings]'],
            'vendor-media' => ['صور المورد','[tager_v44_profile_studio]'],
            'vendor-product-media' => ['صور المنتجات','[tager_v44_product_media_studio]'],
            'vendors' => ['دليل الموردين','[tager_v48_vendor_directory]'],
            'market' => ['السوق','[tager_v48_market]'],
            'shop' => ['المنتجات','[tager_shop]'],
            'cart' => ['السلة','[tager_v24_cart]'],
            'checkout' => ['إتمام الطلب','[tager_v24_cart]'],
            'admin-portal' => ['بوابة الإدارة','[tager_v55_admin_portal]'],
            'admin-approvals' => ['مركز الموافقات','[tager_v42_admin_approvals]'],
            'site-map' => ['خريطة الموقع','[tager_v45_site_map]'],
            'about' => ['عن تاجر','[tager_v22_about]'],
            'how-it-works' => ['كيف تعمل المنصة','[tager_v22_how_it_works]'],
            'contact' => ['تواصل معنا','[tager_v29_contact]'],
            'faq' => ['الأسئلة الشائعة','[tager_v22_faq]'],
            'shipping-policy' => ['سياسة الشحن','[tager_v29_legal type="shipping"]'],
            'returns-policy' => ['سياسة الاسترجاع','[tager_v29_legal type="returns"]'],
            'privacy-policy' => ['سياسة الخصوصية','[tager_v29_legal type="privacy"]'],
            'terms' => ['الشروط والأحكام','[tager_v29_legal type="terms"]'],
        ];
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v55_customer_portal', [__CLASS__, 'customer_portal']);
        add_shortcode('tager_v55_vendor_portal', [__CLASS__, 'vendor_portal']);
        add_shortcode('tager_v55_admin_portal', [__CLASS__, 'admin_portal']);
        add_shortcode('tager_v55_customer_addresses', [__CLASS__, 'customer_addresses']);
        add_shortcode('tager_v55_customer_profile', [__CLASS__, 'customer_profile']);
        add_shortcode('tager_v55_vendor_products', [__CLASS__, 'vendor_products']);
        add_shortcode('tager_v55_vendor_earnings', [__CLASS__, 'vendor_earnings']);
        add_shortcode('tager_v55_workspace_nav', [__CLASS__, 'workspace_nav']);
    }

    private static function url($slug) {
        $p = get_page_by_path($slug);
        return $p ? get_permalink($p) : home_url('/'.$slug.'/');
    }
    private static function is_vendor($u=null) {
        $u = $u ?: wp_get_current_user();
        return in_array('tager_vendor',(array)$u->roles,true) || in_array('wcfm_vendor',(array)$u->roles,true) || in_array('vendor',(array)$u->roles,true);
    }
    private static function is_admin_team($u=null) {
        $u = $u ?: wp_get_current_user();
        return user_can($u,'manage_options') || user_can($u,'tager_view_admin') || array_intersect((array)$u->roles,['administrator','tager_platform_manager','tager_operations_manager','tager_vendor_manager','tager_catalog_manager','tager_order_manager','tager_finance_manager','tager_support_agent','tager_marketing_manager','tager_readonly_auditor']);
    }
    private static function card($title,$text,$slug,$icon='↗',$badge='') {
        $badge_html = $badge !== '' ? '<span class="tv55-badge">'.esc_html($badge).'</span>' : '';
        return '<a class="tv55-card" href="'.esc_url(self::url($slug)).'"><span class="tv55-icon">'.$icon.'</span><div><h3>'.esc_html($title).$badge_html.'</h3><p>'.esc_html($text).'</p></div><span class="tv55-arrow">←</span></a>';
    }
    private static function require_login() {
        if (is_user_logged_in()) return '';
        return '<div class="tv55-empty"><h2>يلزم تسجيل الدخول</h2><p>سجّل الدخول للوصول إلى هذه الصفحة وحفظ بياناتك.</p><a class="tv55-btn" href="'.esc_url(self::url('login')).'">تسجيل الدخول</a></div>';
    }

    public static function customer_portal() {
        if (!is_user_logged_in()) return self::require_login();
        $u=wp_get_current_user(); if (self::is_vendor($u)) return '<div class="tv55-empty">هذه الصفحة للعملاء. <a href="'.esc_url(self::url('vendor-dashboard')).'">افتح لوحة المورد</a></div>';
        $orders=get_posts(['post_type'=>'tager_order','author'=>$u->ID,'posts_per_page'=>-1,'post_status'=>'any','fields'=>'ids']);
        $spent=0;$active=0; foreach($orders as $id){$spent+=(float)get_post_meta($id,'total',true);$s=get_post_meta($id,'status',true);if(!in_array($s,['completed','cancelled','refunded'],true))$active++;}
        $phone=get_user_meta($u->ID,'tager_phone',true); $gov=get_user_meta($u->ID,'tager_governorate',true); $addr=get_user_meta($u->ID,'tager_address',true);
        $complete=0; foreach([$u->display_name,$phone,$gov,$addr] as $v) if($v)$complete+=25;
        ob_start(); ?>
        <section class="tv55-shell"><header class="tv55-hero"><div><span>مساحة العميل</span><h1>مرحبًا <?php echo esc_html($u->display_name); ?></h1><p>تابع طلباتك، عناوينك، المفضلة والسلات المحفوظة من مكان واحد.</p></div><div class="tv55-progress"><b><?php echo $complete; ?>%</b><small>اكتمال الحساب</small></div></header>
        <div class="tv55-stats"><div><b><?php echo count($orders); ?></b><span>كل الطلبات</span></div><div><b><?php echo $active; ?></b><span>طلبات نشطة</span></div><div><b><?php echo number_format($spent,2); ?></b><span>إجمالي المشتريات</span></div><div><b><?php echo esc_html($gov ?: 'غير محددة'); ?></b><span>المحافظة</span></div></div>
        <div class="tv55-grid">
        <?php echo self::card('طلباتي','عرض الطلبات والحالات والتتبع','customer-orders','📦',count($orders));
        echo self::card('بياناتي','الاسم والهاتف والبريد الاختياري','customer-profile','👤');
        echo self::card('عناويني','إدارة المحافظة والمركز وعناوين التوصيل','customer-addresses','📍');
        echo self::card('المفضلة','المنتجات التي حفظتها','wishlist','♡');
        echo self::card('السلات المحفوظة','العودة إلى مشتريات متكررة','saved-carts','🛒');
        echo self::card('الإشعارات','آخر تحديثات الطلب والحساب','notifications','🔔');
        echo self::card('أمان الحساب','كلمة المرور والجلسات','customer-security','🔒');
        echo self::card('الدعم','فتح ومتابعة طلبات المساعدة','support','💬'); ?>
        </div></section><?php return ob_get_clean();
    }

    public static function vendor_portal() {
        if (!is_user_logged_in()) return self::require_login();
        $u=wp_get_current_user(); if (!self::is_vendor($u) && !self::is_admin_team($u)) return '<div class="tv55-empty">هذه الصفحة للموردين فقط.</div>';
        $products=get_posts(['post_type'=>'tager_product','author'=>$u->ID,'posts_per_page'=>-1,'post_status'=>'any','fields'=>'ids']);
        $published=0;$pending=0;$low=0; foreach($products as $id){$p=get_post_status($id); if($p==='publish')$published++; else $pending++; if((int)get_post_meta($id,'stock',true)<=5)$low++;}
        $store=get_user_meta($u->ID,'tager_store_name',true);$phone=get_user_meta($u->ID,'tager_phone',true);$gov=get_user_meta($u->ID,'tager_vendor_governorate',true);$logo=get_user_meta($u->ID,'tager_vendor_logo_id',true);
        $ready=0;foreach([$store,$phone,$gov,$logo,count($products)>0] as $v)if($v)$ready+=20;
        ob_start(); ?>
        <section class="tv55-shell"><header class="tv55-hero vendor"><div><span>مساحة المورد</span><h1><?php echo esc_html($store ?: $u->display_name); ?></h1><p>أضف منتجاتك، حدّث الأسعار والمخزون، وتابع الطلبات والأرباح.</p></div><div class="tv55-progress"><b><?php echo $ready; ?>%</b><small>جاهزية المتجر</small></div></header>
        <div class="tv55-stats"><div><b><?php echo count($products); ?></b><span>إجمالي المنتجات</span></div><div><b><?php echo $published; ?></b><span>منتج منشور</span></div><div><b><?php echo $pending; ?></b><span>تحت المراجعة</span></div><div><b><?php echo $low; ?></b><span>مخزون منخفض</span></div></div>
        <?php if($ready<100): ?><div class="tv55-notice"><b>أكمل بيانات متجرك:</b> <?php echo !$store?'اسم المتجر، ':''; echo !$phone?'الهاتف، ':''; echo !$gov?'المحافظة والمركز، ':''; echo !$logo?'الشعار، ':''; echo !count($products)?'أول منتج':''; ?></div><?php endif; ?>
        <div class="tv55-grid">
        <?php echo self::card('إضافة منتج','الصور والأسعار الثلاثة والمخزون والحدود','vendor-add-product','＋');
        echo self::card('منتجاتي','تعديل المنتجات ومتابعة حالة المراجعة','vendor-products','▦',count($products));
        echo self::card('طلبات المورد','الطلبات المرتبطة بمنتجاتك فقط','vendor-orders','📦');
        echo self::card('المخزون','متابعة الكميات والتنبيهات','vendor-inventory','📊',$low);
        echo self::card('الأرباح والعمولات','إجمالي المبيعات وصافي المستحق','vendor-earnings','💰');
        echo self::card('سوق المورد','مشاهدة السوق ومقارنة الأسعار','vendor-market','🏪');
        echo self::card('إعدادات المتجر','المحافظة والمركز والحد الأدنى والتوصيل','vendor-settings','⚙');
        echo self::card('هوية وصور المتجر','الشعار والغلاف والمعرض','vendor-media','🖼');
        echo self::card('صور المنتجات','إدارة الصورة الرئيسية والمعارض','vendor-product-media','📷');
        echo self::card('الإشعارات','قرارات الإدارة وتحديثات الطلبات','notifications','🔔'); ?>
        </div></section><?php return ob_get_clean();
    }

    public static function admin_portal() {
        if (!is_user_logged_in() || !self::is_admin_team()) return '<div class="tv55-empty">ليس لديك صلاحية دخول الإدارة.</div>';
        $vendors=get_users(['role__in'=>['tager_vendor','wcfm_vendor','vendor'],'fields'=>'ids']);$pending=0;foreach($vendors as $id)if(get_user_meta($id,'tager_vendor_status',true)!=='approved')$pending++;
        $pending_products=(int)wp_count_posts('tager_product')->pending; $orders=wp_count_posts('tager_order'); $order_count=array_sum(array_map('intval',(array)$orders));
        ob_start(); ?><section class="tv55-shell"><header class="tv55-hero admin"><div><span>مركز قيادة الإدارة</span><h1>تشغيل ومراقبة منصة تاجر</h1><p>الموافقات والطلبات والمالية والشحن والدفع وفريق الإدارة.</p></div></header>
        <div class="tv55-stats"><div><b><?php echo count($vendors); ?></b><span>الموردون</span></div><div><b><?php echo $pending; ?></b><span>موردون بانتظار المراجعة</span></div><div><b><?php echo $pending_products; ?></b><span>منتجات بانتظار المراجعة</span></div><div><b><?php echo $order_count; ?></b><span>الطلبات</span></div></div>
        <div class="tv55-grid">
        <?php echo self::card('مركز الموافقات','قبول الموردين ونشر المنتجات','admin-approvals','✓',$pending+$pending_products);
        echo self::card('فريق الإدارة','إضافة أدمن وصلاحيات منفصلة','admin-team','👥');
        echo self::card('الطلبات والتشغيل','متابعة الحالات والمدفوعات والشحن','admin-orders','📦');
        echo self::card('العمولات','نسبة كل مورد ورسوم السلة المميزة','vendor-commissions','٪');
        echo self::card('الشحن والتسعير','المحافظات والرسوم والشحن المجاني','admin-shipping','🚚');
        echo self::card('الدفع','تفعيل طرق الدفع ورسوم كل طريقة','admin-payments','💳');
        echo self::card('التقارير','المبيعات والعمولات والأداء','admin-reports','📈');
        echo self::card('فحص النظام','الصفحات والأزرار والبيانات','v55-system-audit','🛠'); ?>
        </div></section><?php return ob_get_clean();
    }

    public static function customer_addresses(){
        if(!is_user_logged_in())return self::require_login();$u=wp_get_current_user();$saved=false;
        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tv55_addr_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tv55_addr_nonce'])),'tv55_addr')){
            foreach(['tager_governorate','tager_city','tager_address','tager_landmark'] as $k)update_user_meta($u->ID,$k,sanitize_text_field(wp_unslash($_POST[$k]??'')));$saved=true;
        }
        ob_start(); ?><div class="tv55-form"><h2>عنوان التوصيل</h2><?php if($saved):?><div class="tv55-success">تم حفظ العنوان بنجاح.</div><?php endif;?><form method="post"><?php wp_nonce_field('tv55_addr','tv55_addr_nonce');?><label>المحافظة *</label><input required name="tager_governorate" value="<?php echo esc_attr(get_user_meta($u->ID,'tager_governorate',true));?>"><label>المركز / المدينة *</label><input required name="tager_city" value="<?php echo esc_attr(get_user_meta($u->ID,'tager_city',true));?>"><label>العنوان التفصيلي *</label><textarea required name="tager_address"><?php echo esc_textarea(get_user_meta($u->ID,'tager_address',true));?></textarea><label>علامة مميزة</label><input name="tager_landmark" value="<?php echo esc_attr(get_user_meta($u->ID,'tager_landmark',true));?>"><button class="tv55-btn" type="submit">حفظ العنوان</button></form></div><?php return ob_get_clean();
    }
    public static function customer_profile(){
        if(!is_user_logged_in())return self::require_login();$u=wp_get_current_user();$saved=false;$err='';
        if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['tv55_profile_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tv55_profile_nonce'])),'tv55_profile')){
            $name=sanitize_text_field(wp_unslash($_POST['display_name']??''));$phone=sanitize_text_field(wp_unslash($_POST['phone']??''));$email=sanitize_email(wp_unslash($_POST['email']??''));
            if(!$name||!$phone)$err='الاسم ورقم الهاتف مطلوبان.'; else {$other=get_users(['meta_key'=>'tager_phone','meta_value'=>$phone,'exclude'=>[$u->ID],'fields'=>'ids']);if($other)$err='رقم الهاتف مستخدم بالفعل.';else{$args=['ID'=>$u->ID,'display_name'=>$name];if($email)$args['user_email']=$email;$res=wp_update_user($args);if(is_wp_error($res))$err=$res->get_error_message();else{update_user_meta($u->ID,'tager_phone',$phone);$saved=true;}}}
        }
        $u=wp_get_current_user();ob_start();?><div class="tv55-form"><h2>بياناتي الشخصية</h2><?php if($saved):?><div class="tv55-success">تم حفظ البيانات.</div><?php endif;?><?php if($err):?><div class="tv55-error"><?php echo esc_html($err);?></div><?php endif;?><form method="post"><?php wp_nonce_field('tv55_profile','tv55_profile_nonce');?><label>الاسم *</label><input required name="display_name" value="<?php echo esc_attr($u->display_name);?>"><label>رقم الهاتف *</label><input required name="phone" value="<?php echo esc_attr(get_user_meta($u->ID,'tager_phone',true));?>"><label>البريد الإلكتروني (اختياري)</label><input type="email" name="email" value="<?php echo esc_attr(strpos($u->user_email,'@tager.local')===false?$u->user_email:'');?>"><button class="tv55-btn" type="submit">حفظ البيانات</button></form></div><?php return ob_get_clean();
    }
    public static function vendor_products(){
        if(!is_user_logged_in()||(!self::is_vendor()&&!self::is_admin_team()))return '<div class="tv55-empty">هذه الصفحة للموردين فقط.</div>';$uid=get_current_user_id();$ps=get_posts(['post_type'=>'tager_product','author'=>$uid,'post_status'=>'any','posts_per_page'=>100]);
        ob_start();?><div class="tv55-table-wrap"><div class="tv55-section-head"><h2>منتجاتي</h2><a class="tv55-btn" href="<?php echo esc_url(self::url('vendor-add-product'));?>">إضافة منتج</a></div><?php if(!$ps):?><div class="tv55-empty"><h3>لا توجد منتجات بعد</h3><p>أضف أول منتج بالصور والأسعار والكميات.</p></div><?php else:?><table class="tv55-table"><thead><tr><th>المنتج</th><th>الحالة</th><th>قطاعي</th><th>جملة</th><th>جملة الجملة</th><th>المخزون</th><th>إجراء</th></tr></thead><tbody><?php foreach($ps as $p):?><tr><td><b><?php echo esc_html($p->post_title);?></b></td><td><?php echo esc_html(get_post_status_object($p->post_status)->label??$p->post_status);?></td><td><?php echo esc_html(get_post_meta($p->ID,'retail_price',true));?></td><td><?php echo esc_html(get_post_meta($p->ID,'wholesale_price',true));?></td><td><?php echo esc_html(get_post_meta($p->ID,'bulk_price',true));?></td><td><?php echo esc_html(get_post_meta($p->ID,'stock',true));?></td><td><a class="tv55-small" href="<?php echo esc_url(add_query_arg('product_id',$p->ID,self::url('vendor-add-product')));?>">تعديل</a></td></tr><?php endforeach;?></tbody></table><?php endif;?></div><?php return ob_get_clean();
    }
    public static function vendor_earnings(){
        if(!is_user_logged_in()||(!self::is_vendor()&&!self::is_admin_team()))return '<div class="tv55-empty">هذه الصفحة للموردين فقط.</div>';$uid=get_current_user_id();$orders=get_posts(['post_type'=>'tager_order','posts_per_page'=>-1,'post_status'=>'any']);$gross=0;$commission=0;$net=0;$count=0;foreach($orders as $o){$rows=get_post_meta($o->ID,'vendor_totals',true);if(is_array($rows)&&isset($rows[$uid])){$r=$rows[$uid];$gross+=(float)($r['gross_total']??$r['total']??0);$commission+=(float)($r['platform_commission']??0);$net+=(float)($r['vendor_net']??0);$count++;}}
        return '<div class="tv55-shell"><h2>الأرباح والعمولات</h2><div class="tv55-stats"><div><b>'.number_format($gross,2).'</b><span>إجمالي المبيعات</span></div><div><b>'.number_format($commission,2).'</b><span>عمولة المنصة</span></div><div><b>'.number_format($net,2).'</b><span>صافي المستحق</span></div><div><b>'.$count.'</b><span>طلبات</span></div></div><div class="tv55-notice">تُحتسب العمولة وفق النسبة التي حددتها الإدارة لهذا المورد، وتظهر التسوية بعد اكتمال الطلب.</div></div>';
    }
    public static function workspace_nav(){return '';}

    public static function login_redirect($redirect,$requested,$user){
        if(!$user||is_wp_error($user))return $redirect;if(self::is_admin_team($user))return self::url('admin-portal');if(self::is_vendor($user))return self::url('vendor-dashboard');return self::url('customer-account');
    }
    public static function guard_workspaces(){
        if(is_admin()||wp_doing_ajax())return;$slug=get_post_field('post_name',get_queried_object_id());if(!$slug)return;
        if(strpos($slug,'vendor-')===0 && is_user_logged_in()&&!self::is_vendor()&&!self::is_admin_team()){wp_safe_redirect(self::url('customer-account'));exit;}
        if(strpos($slug,'customer-')===0 && is_user_logged_in()&&self::is_vendor()){wp_safe_redirect(self::url('vendor-dashboard'));exit;}
        if(strpos($slug,'admin-')===0 && (!is_user_logged_in()||!self::is_admin_team())){wp_safe_redirect(self::url('login'));exit;}
    }

    public static function repair_once(){self::repair_pages();update_option(self::OPT,time(),false);}
    public static function repair_pages(){
        foreach(self::pages() as $slug=>$d){$p=get_page_by_path($slug);$data=['post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1],'post_status'=>'publish','post_type'=>'page'];if(!$p)wp_insert_post($data);elseif(trim($p->post_content)===''||preg_match('/^\s*\[[^\]]+\]\s*$/',$p->post_content)&&!self::content_shortcode_exists($p->post_content)){$data['ID']=$p->ID;wp_update_post($data);}}
        $home=get_page_by_path('home'); if(!$home){$id=wp_insert_post(['post_title'=>'الرئيسية','post_name'=>'home','post_content'=>'[tager_home]','post_status'=>'publish','post_type'=>'page']);}else$id=$home->ID; update_option('show_on_front','page');update_option('page_on_front',$id);
        flush_rewrite_rules(false);
    }
    private static function content_shortcode_exists($content){if(preg_match('/\[([a-zA-Z0-9_-]+)/',$content,$m))return shortcode_exists($m[1]);return true;}
    public static function repair_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer('tager_v55_repair');self::repair_pages();wp_safe_redirect(add_query_arg('repaired','1',admin_url('admin.php?page=tager-v55')));exit;}
    public static function test_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer('tager_v55_test');$result=self::run_data_test();set_transient('tager_v55_test_result',$result,120);wp_safe_redirect(add_query_arg('tested','1',admin_url('admin.php?page=tager-v55')));exit;}
    private static function run_data_test(){
        $out=[];$phone='010'.wp_rand(10000000,99999999);$uid=wp_create_user('v55test'.wp_rand(1000,9999),wp_generate_password(16), 'v55'.wp_rand(1000,9999).'@tager.local');
        if(is_wp_error($uid)){$out[]=['فشل إنشاء مستخدم',false];return $out;}update_user_meta($uid,'tager_phone',$phone);$out[]=['حفظ وقراءة هاتف المستخدم',get_user_meta($uid,'tager_phone',true)===$phone];
        $pid=wp_insert_post(['post_type'=>'tager_product','post_title'=>'V55 Test Product','post_status'=>'draft','post_author'=>$uid]);update_post_meta($pid,'retail_price',100);update_post_meta($pid,'wholesale_price',90);update_post_meta($pid,'bulk_price',80);update_post_meta($pid,'stock',50);$out[]=['حفظ المنتج والأسعار والمخزون',$pid&&get_post_meta($pid,'bulk_price',true)==80&&get_post_meta($pid,'stock',true)==50];
        wp_delete_post($pid,true);wp_delete_user($uid);return $out;
    }

    public static function admin_menu(){add_menu_page('Tager V55','Tager V55','manage_options','tager-v55',[__CLASS__,'admin_page'],'dashicons-store',2);}
    public static function admin_page(){if(!current_user_can('manage_options'))return;$pages=self::pages();$missing=[];$empty=[];$broken=[];foreach($pages as $slug=>$d){$p=get_page_by_path($slug);if(!$p)$missing[]=$slug;elseif(trim(wp_strip_all_tags(strip_shortcodes($p->post_content)))===''&&trim($p->post_content)==='')$empty[]=$slug;elseif(!self::content_shortcode_exists($p->post_content))$broken[]=$slug;}$test=get_transient('tager_v55_test_result');
        ?><div class="wrap tv55-admin"><h1>Tager V55 — التطوير الشامل</h1><p>فحص الصفحات والمسارات والبيانات من منظور العميل والمورد والإدارة.</p><div class="tv55-admin-stats"><div><b><?php echo count($pages);?></b><span>صفحات مسجلة</span></div><div><b><?php echo count($missing);?></b><span>ناقصة</span></div><div><b><?php echo count($empty);?></b><span>فارغة</span></div><div><b><?php echo count($broken);?></b><span>Shortcodes مكسورة</span></div></div>
        <?php if(isset($_GET['repaired'])):?><div class="notice notice-success"><p>تم إصلاح وربط الصفحات.</p></div><?php endif;?>
        <p><a class="button button-primary button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v55_repair'),'tager_v55_repair'));?>">إصلاح وإنشاء وربط الصفحات</a> <a class="button button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v55_run_test'),'tager_v55_test'));?>">اختبار حفظ البيانات</a></p>
        <?php if($test):?><div class="tv55-test"><h2>نتيجة اختبار البيانات</h2><?php foreach($test as $r):?><p><?php echo $r[1]?'✅':'❌';?> <?php echo esc_html($r[0]);?></p><?php endforeach;?></div><?php endif;?>
        <table class="widefat striped"><thead><tr><th>الصفحة</th><th>الرابط</th><th>الحالة</th><th>فتح</th></tr></thead><tbody><?php foreach($pages as $slug=>$d):$p=get_page_by_path($slug);$status=!$p?'ناقصة':(!self::content_shortcode_exists($p->post_content)?'Shortcode غير موجود':'جاهزة');?><tr><td><b><?php echo esc_html($d[0]);?></b><br><code><?php echo esc_html($d[1]);?></code></td><td>/<?php echo esc_html($slug);?>/</td><td><?php echo esc_html($status);?></td><td><?php if($p):?><a class="button" target="_blank" href="<?php echo esc_url(get_permalink($p));?>">فتح</a> <a class="button" href="<?php echo esc_url(get_edit_post_link($p->ID));?>">تعديل</a><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php
    }

    public static function never_empty_content($content){if(is_admin()||!is_singular('page')||trim($content)!=='')return $content;return '<div class="tv55-empty"><h2>هذه الصفحة قيد التجهيز</h2><p>لم تتم إضافة محتوى لهذه الصفحة بعد. استخدم مركز Tager V55 لإصلاح الصفحات.</p><a class="tv55-btn" href="'.esc_url(home_url('/')).'">العودة للرئيسية</a></div>';}

    public static function assets(){wp_register_style('tager-v55',false,[],self::VERSION);wp_enqueue_style('tager-v55');wp_add_inline_style('tager-v55',self::css());}
    public static function admin_assets($hook){if(strpos($hook,'tager-v55')===false)return;wp_register_style('tager-v55-admin',false,[],self::VERSION);wp_enqueue_style('tager-v55-admin');wp_add_inline_style('tager-v55-admin','.tv55-admin{max-width:1250px}.tv55-admin-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin:20px 0}.tv55-admin-stats div,.tv55-test{background:#fff;border:1px solid #ddd;border-radius:12px;padding:18px}.tv55-admin-stats b{display:block;font-size:26px;color:#174f3a}.tv55-admin-stats span{color:#666}');}
    public static function frontend_script(){?>
    <script>(function(){document.addEventListener('submit',function(e){var f=e.target;if(!f.matches('form'))return;var bad=f.querySelector(':invalid');if(bad){bad.focus();return;}var b=f.querySelector('button[type="submit"],input[type="submit"]');if(b&&!b.disabled){b.dataset.old=b.innerHTML||b.value;b.disabled=true;if(b.tagName==='BUTTON')b.innerHTML='جاري الحفظ...';else b.value='جاري الحفظ...';setTimeout(function(){b.disabled=false;if(b.tagName==='BUTTON')b.innerHTML=b.dataset.old;else b.value=b.dataset.old;},10000);}},true);document.querySelectorAll('a[href="#"],a[href=""],button:not([type]):empty').forEach(function(x){x.setAttribute('aria-disabled','true');x.addEventListener('click',function(e){e.preventDefault();});});})();</script><?php }
    private static function css(){return '
    :root{--tv55-green:#174f3a;--tv55-gold:#c99b3d;--tv55-bg:#f6f8f7;--tv55-text:#17231f;--tv55-muted:#64716c;--tv55-border:#dfe7e3}
    .tv55-shell,.tv55-form,.tv55-table-wrap,.tv55-empty{max-width:1180px;margin:28px auto;padding:0 18px;box-sizing:border-box}.tv55-hero{background:linear-gradient(135deg,#123f30,#21684e);color:#fff;border-radius:24px;padding:32px;display:flex;justify-content:space-between;align-items:center;gap:24px;box-shadow:0 18px 45px rgba(23,79,58,.18)}.tv55-hero.vendor{background:linear-gradient(135deg,#173d4f,#235e72)}.tv55-hero.admin{background:linear-gradient(135deg,#3e3420,#776126)}.tv55-hero span{color:#f0d99c;font-weight:700}.tv55-hero h1{margin:8px 0;font-size:clamp(26px,4vw,42px)}.tv55-hero p{margin:0;opacity:.9}.tv55-progress{width:118px;height:118px;border:8px solid rgba(255,255,255,.22);border-top-color:#f0c76e;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;flex:none}.tv55-progress b{font-size:25px}.tv55-progress small{font-size:11px}.tv55-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:20px 0}.tv55-stats div{background:#fff;border:1px solid var(--tv55-border);border-radius:16px;padding:20px;box-shadow:0 8px 24px rgba(15,54,40,.05)}.tv55-stats b{display:block;font-size:25px;color:var(--tv55-green)}.tv55-stats span{color:var(--tv55-muted);font-size:13px}.tv55-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.tv55-card{display:flex;align-items:center;gap:14px;text-decoration:none!important;color:var(--tv55-text)!important;background:#fff;border:1px solid var(--tv55-border);border-radius:18px;padding:20px;min-height:105px;transition:.2s;box-shadow:0 8px 24px rgba(15,54,40,.04)}.tv55-card:hover{transform:translateY(-3px);border-color:var(--tv55-gold);box-shadow:0 14px 30px rgba(15,54,40,.1)}.tv55-card h3{margin:0 0 5px;font-size:17px}.tv55-card p{margin:0;color:var(--tv55-muted);font-size:13px;line-height:1.7}.tv55-icon{width:46px;height:46px;border-radius:14px;background:#eef5f1;color:var(--tv55-green);display:grid;place-items:center;font-size:21px;flex:none}.tv55-arrow{margin-inline-start:auto;color:var(--tv55-gold);font-size:20px}.tv55-badge{display:inline-block;background:#fff1cf;color:#76580d;font-size:11px;padding:3px 7px;border-radius:20px;margin-inline-start:8px}.tv55-notice,.tv55-success,.tv55-error{border-radius:12px;padding:14px 16px;margin:15px 0}.tv55-notice{background:#fff7e2;border:1px solid #f0d18a}.tv55-success{background:#eaf8ef;border:1px solid #a9d9b9;color:#175a32}.tv55-error{background:#fff0f0;border:1px solid #efb3b3;color:#8b1d1d}.tv55-form{max-width:720px;background:#fff;border:1px solid var(--tv55-border);border-radius:20px;padding:26px;box-shadow:0 12px 35px rgba(15,54,40,.08)}.tv55-form label{display:block;font-weight:700;margin:14px 0 6px}.tv55-form input,.tv55-form select,.tv55-form textarea{width:100%;box-sizing:border-box;border:1px solid #cfdad5;border-radius:11px;padding:12px 14px;background:#fff}.tv55-form textarea{min-height:105px}.tv55-btn,.tv55-small{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:11px;background:var(--tv55-green);color:#fff!important;text-decoration:none!important;padding:12px 20px;font-weight:700;cursor:pointer}.tv55-small{padding:7px 11px;font-size:12px}.tv55-btn:hover,.tv55-small:hover{background:#0f3d2c}.tv55-btn:disabled{opacity:.65;cursor:wait}.tv55-table-wrap{background:#fff;border:1px solid var(--tv55-border);border-radius:20px;padding:22px;overflow:auto}.tv55-section-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:15px}.tv55-table{width:100%;border-collapse:collapse;min-width:780px}.tv55-table th,.tv55-table td{padding:13px;border-bottom:1px solid #edf1ef;text-align:right}.tv55-table th{background:#f5f8f6;color:#3a4c44}.tv55-empty{text-align:center;background:#fff;border:1px dashed #cbd8d2;border-radius:18px;padding:45px 20px}.tv55-empty h2,.tv55-empty h3{color:var(--tv55-green)}
    @media(max-width:900px){.tv55-grid{grid-template-columns:repeat(2,1fr)}.tv55-stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.tv55-hero{padding:24px;align-items:flex-start}.tv55-progress{width:82px;height:82px;border-width:6px}.tv55-progress b{font-size:18px}.tv55-grid{grid-template-columns:1fr}.tv55-card{min-height:86px}.tv55-stats{gap:9px}.tv55-stats div{padding:15px}.tv55-section-head{align-items:stretch;flex-direction:column}.tv55-btn{width:100%}}
    ';}
}
Tager_V55_Comprehensive_Upgrade::init();
