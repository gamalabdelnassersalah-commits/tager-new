<?php
/**
 * Plugin Name: Tager V49 Complete Pages, Buttons & Navigation
 * Description: Expands missing pages, links all workspaces, repairs empty pages/buttons, and adds role-aware navigation and QA.
 * Version: 49.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V49_Complete_Pages_Buttons {
    const OPT = 'tager_v49_audit';
    const NONCE = 'tager_v49_action';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes'], 120);
        add_action('init', [__CLASS__, 'bootstrap'], 180);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 120);
        add_action('admin_post_tager_v49_repair', [__CLASS__, 'repair_action']);
        add_action('admin_post_tager_v49_audit', [__CLASS__, 'audit_action']);
        add_filter('the_content', [__CLASS__, 'enhance_page_content'], 1000);
        add_filter('wp_nav_menu_items', [__CLASS__, 'role_menu'], 50, 2);
        add_action('wp_footer', [__CLASS__, 'frontend_assets'], 50);
    }

    private static function pages() {
        return [
            // Public discovery
            'home'=>['الرئيسية','[tager_home]','public'],
            'shop'=>['السوق والمنتجات','[tager_v48_product_search]','public'],
            'categories'=>['الأقسام','[tager_v22_categories]','public'],
            'vendors'=>['دليل الموردين','[tager_v48_vendor_search]','public'],
            'offers'=>['العروض والخصومات','[tager_v22_offers]','public'],
            'brands'=>['العلامات التجارية','[tager_v22_brands]','public'],
            'compare'=>['مقارنة المنتجات','[tager_compare]','public'],
            'rfq'=>['طلب عرض سعر','[tager_rfq]','public'],
            'cart'=>['السلة','[tager_cart]','public'],
            'checkout'=>['إتمام الطلب','[tager_cart]','public'],
            'track-order'=>['تتبع الطلب','[tager_pro_tracking]','public'],
            // Authentication
            'login'=>['تسجيل الدخول','[tager_v18_login]','guest'],
            'choose-account'=>['اختيار نوع الحساب','[tager_v18_account_choice]','guest'],
            'customer-register'=>['تسجيل عميل','[tager_customer_register]','guest'],
            'vendor-register'=>['تسجيل مورد','[tager_vendor_register]','guest'],
            'forgot-password'=>['نسيت كلمة المرور','[tager_v45_password_recovery]','guest'],
            'phone-password-reset'=>['استعادة كلمة المرور بالهاتف','[tager_v45_phone_recovery]','guest'],
            // Customer workspace
            'my-account'=>['لوحة العميل','[tager_customer_account]','customer'],
            'customer-orders'=>['طلباتي','[tager_v35_customer_orders]','customer'],
            'customer-order-details'=>['تفاصيل الطلب','[tager_v49_customer_order_details]','customer'],
            'customer-addresses'=>['عناويني','[tager_v35_customer_addresses]','customer'],
            'customer-profile'=>['بيانات الحساب','[tager_v35_customer_profile]','customer'],
            'customer-security'=>['أمان الحساب','[tager_v49_customer_security]','customer'],
            'customer-payments'=>['وسائل الدفع المحفوظة','[tager_v35_customer_payments]','customer'],
            'wishlist'=>['المفضلة','[tager_pro_wishlist]','customer'],
            'saved-carts'=>['السلات المحفوظة','[tager_v49_saved_carts]','customer'],
            'notifications'=>['الإشعارات','[tager_notifications]','customer'],
            'support'=>['الدعم والتذاكر','[tager_support]','customer'],
            'returns'=>['طلبات الاسترجاع','[tager_v34_returns]','customer'],
            'invoices'=>['الفواتير','[tager_v34_invoices]','customer'],
            // Vendor workspace
            'vendor-dashboard'=>['لوحة المورد','[tager_vendor_dashboard]','vendor'],
            'vendor-market'=>['سوق المورد','[tager_v39_vendor_market]','vendor'],
            'vendor-products'=>['منتجاتي','[tager_v35_vendor_products]','vendor'],
            'vendor-add-product'=>['إضافة منتج','[tager_v35_vendor_add_product]','vendor'],
            'vendor-inventory'=>['المخزون','[tager_v49_vendor_inventory]','vendor'],
            'vendor-orders'=>['طلبات المورد','[tager_v42_vendor_orders]','vendor'],
            'vendor-earnings'=>['الأرباح والعمولات','[tager_v35_vendor_earnings]','vendor'],
            'vendor-withdrawals'=>['طلبات السحب','[tager_v35_vendor_withdrawals]','vendor'],
            'vendor-coupons'=>['الكوبونات والعروض','[tager_v35_vendor_coupons]','vendor'],
            'vendor-analytics'=>['تحليلات المتجر','[tager_v35_vendor_analytics]','vendor'],
            'vendor-settings'=>['إعدادات المتجر','[tager_v35_vendor_settings]','vendor'],
            'vendor-location'=>['المحافظة والمراكز','[tager_v47_vendor_location]','vendor'],
            'vendor-media'=>['صور وهوية المتجر','[tager_v44_account_media]','vendor'],
            'vendor-product-media'=>['صور المنتجات','[tager_v44_product_media]','vendor'],
            'vendor-support'=>['دعم المورد','[tager_support]','vendor'],
            // Admin workspace
            'admin-portal'=>['بوابة الإدارة','[tager_v40_admin_portal]','admin'],
            'admin-approvals'=>['مركز الموافقات','[tager_v42_approvals]','admin'],
            'admin-vendors'=>['إدارة الموردين','[tager_v35_admin_vendors]','admin'],
            'admin-products'=>['مراجعة المنتجات','[tager_v35_admin_products]','admin'],
            'admin-orders'=>['إدارة الطلبات','[tager_v35_admin_orders]','admin'],
            'admin-finance'=>['المالية والعمولات','[tager_v35_admin_finance]','admin'],
            'admin-shipping'=>['إعدادات الشحن','[tager_v49_admin_shipping]','admin'],
            'admin-payments'=>['إعدادات الدفع','[tager_v49_admin_payments]','admin'],
            'admin-team'=>['فريق الإدارة والصلاحيات','[tager_v49_admin_team]','admin'],
            'admin-reports'=>['التقارير والتحليلات','[tager_v49_admin_reports]','admin'],
            'admin-content'=>['إدارة المحتوى','[tager_v35_admin_content]','admin'],
            'admin-system'=>['صحة النظام','[tager_v41_system_health]','admin'],
            // Information & legal
            'about'=>['عن تاجر','[tager_v22_about]','public'],
            'how-it-works'=>['كيف تعمل المنصة','[tager_v22_how_it_works]','public'],
            'buyer-guide'=>['دليل المشتري','[tager_v22_buyer_guide]','public'],
            'vendor-guide'=>['دليل المورد','[tager_v22_vendor_guide]','public'],
            'business-solutions'=>['حلول الشركات','[tager_v22_business_solutions]','public'],
            'pricing'=>['الباقات والأسعار','[tager_v22_pricing]','public'],
            'payment-methods'=>['طرق الدفع','[tager_v34_payment_methods]','public'],
            'shipping'=>['الشحن والتوصيل','[tager_v22_shipping]','public'],
            'delivery-areas'=>['مناطق التوصيل','[tager_v34_delivery_areas]','public'],
            'return-policy'=>['سياسة الاسترجاع','[tager_v22_return_policy]','public'],
            'privacy-policy'=>['سياسة الخصوصية','[tager_v22_privacy_policy]','public'],
            'terms'=>['الشروط والأحكام','[tager_v22_terms]','public'],
            'faq'=>['الأسئلة الشائعة','[tager_v22_faq]','public'],
            'contact-us'=>['تواصل معنا','[tager_v22_contact_us]','public'],
            'help-center'=>['مركز المساعدة','[tager_v22_help_center]','public'],
            'complaints'=>['الشكاوى والمقترحات','[tager_v35_complaints]','public'],
            'sitemap'=>['خريطة الموقع','[tager_v49_sitemap]','public'],
        ];
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v49_customer_order_details', [__CLASS__, 'customer_order_details']);
        add_shortcode('tager_v49_customer_security', [__CLASS__, 'customer_security']);
        add_shortcode('tager_v49_saved_carts', [__CLASS__, 'saved_carts']);
        add_shortcode('tager_v49_vendor_inventory', [__CLASS__, 'vendor_inventory']);
        add_shortcode('tager_v49_admin_shipping', [__CLASS__, 'admin_link_page']);
        add_shortcode('tager_v49_admin_payments', [__CLASS__, 'admin_link_page']);
        add_shortcode('tager_v49_admin_team', [__CLASS__, 'admin_link_page']);
        add_shortcode('tager_v49_admin_reports', [__CLASS__, 'admin_link_page']);
        add_shortcode('tager_v49_sitemap', [__CLASS__, 'sitemap']);
        add_shortcode('tager_v49_workspace_nav', [__CLASS__, 'workspace_nav_shortcode']);
    }

    public static function bootstrap() {
        if (get_option('tager_v49_bootstrapped')) return;
        self::repair_all();
        update_option('tager_v49_bootstrapped', current_time('mysql'), false);
    }

    private static function shortcode_tag($content) {
        return preg_match('/^\[([a-zA-Z0-9_-]+)/', trim((string)$content), $m) ? $m[1] : '';
    }

    private static function fallback($title, $slug, $audience) {
        $links = self::context_links($audience, $slug);
        return '<section class="t49-page"><div class="t49-hero"><span>منصة تاجر</span><h1>'.esc_html($title).'</h1><p>صفحة متكاملة ومربوطة بمسارات المنصة الرئيسية.</p></div>'.$links.'</section>';
    }

    private static function page_url($slug) {
        $p = get_page_by_path($slug);
        return $p ? get_permalink($p) : home_url('/'.trim($slug,'/').'/');
    }

    private static function context_links($audience, $active='') {
        $sets = [
            'public'=>['home','shop','categories','vendors','offers','cart','login'],
            'guest'=>['login','choose-account','customer-register','vendor-register','forgot-password'],
            'customer'=>['my-account','customer-orders','customer-addresses','wishlist','saved-carts','notifications','support','shop'],
            'vendor'=>['vendor-dashboard','vendor-market','vendor-products','vendor-add-product','vendor-inventory','vendor-orders','vendor-earnings','vendor-settings','vendor-media'],
            'admin'=>['admin-portal','admin-approvals','admin-vendors','admin-products','admin-orders','admin-finance','admin-shipping','admin-payments','admin-team','admin-reports'],
        ];
        $pages=self::pages(); $slugs=$sets[$audience]??$sets['public'];
        $html='<nav class="t49-context-nav" aria-label="روابط الصفحة">';
        foreach($slugs as $slug){ if(!isset($pages[$slug])) continue; $cls=$slug===$active?' is-active':''; $html.='<a class="'.$cls.'" href="'.esc_url(self::page_url($slug)).'">'.esc_html($pages[$slug][0]).'</a>'; }
        return $html.'</nav>';
    }

    public static function repair_all() {
        global $shortcode_tags;
        $ids=(array)get_option('tager_pages',[]); $stats=['created'=>0,'updated'=>0,'kept'=>0,'menus'=>0];
        foreach(self::pages() as $slug=>$def){
            [$title,$preferred,$audience]=$def; $p=get_page_by_path($slug,OBJECT,'page'); $tag=self::shortcode_tag($preferred);
            $body=($tag && isset($shortcode_tags[$tag])) ? $preferred : self::fallback($title,$slug,$audience);
            if(!$p){
                $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$body]);
                if($id && !is_wp_error($id)){ $ids[$slug]=$id; update_post_meta($id,'_tager_v49_managed',1); $stats['created']++; }
            } else {
                $ids[$slug]=$p->ID; $plain=trim(wp_strip_all_tags(strip_shortcodes($p->post_content))); $broken=false;
                if(preg_match_all('/\[([a-zA-Z0-9_-]+)/',$p->post_content,$m)) foreach($m[1] as $t) if(!isset($shortcode_tags[$t])){$broken=true;break;}
                if(trim($p->post_content)==='' || ($plain==='' && $broken) || $broken){ wp_update_post(['ID'=>$p->ID,'post_title'=>$title,'post_content'=>$body,'post_status'=>'publish']); update_post_meta($p->ID,'_tager_v49_managed',1); $stats['updated']++; }
                else $stats['kept']++;
            }
        }
        update_option('tager_pages',$ids,false);
        if(!empty($ids['home'])){update_option('show_on_front','page');update_option('page_on_front',(int)$ids['home']);}
        $stats['menus']=self::repair_menus($ids);
        flush_rewrite_rules(false);
        $audit=self::audit(); update_option(self::OPT,['time'=>current_time('mysql'),'stats'=>$stats,'audit'=>$audit],false);
        return $stats;
    }

    private static function repair_menus($ids){
        $menus=[
            'Tager Main Menu'=>['home','shop','categories','vendors','offers','brands','about','contact-us'],
            'Tager Customer Menu'=>['my-account','customer-orders','customer-addresses','wishlist','saved-carts','notifications','support'],
            'Tager Vendor Menu'=>['vendor-dashboard','vendor-market','vendor-products','vendor-add-product','vendor-inventory','vendor-orders','vendor-earnings','vendor-settings'],
            'Tager Footer Menu'=>['help-center','faq','payment-methods','shipping','return-policy','privacy-policy','terms','sitemap'],
        ]; $count=0;
        foreach($menus as $name=>$slugs){$obj=wp_get_nav_menu_object($name);$menu_id=$obj?$obj->term_id:wp_create_nav_menu($name);if(is_wp_error($menu_id))continue;$existing=wp_get_nav_menu_items($menu_id)?:[];$linked=[];foreach($existing as $i)$linked[(int)$i->object_id]=true;foreach($slugs as $slug){if(empty($ids[$slug])||isset($linked[(int)$ids[$slug]]))continue;wp_update_nav_menu_item($menu_id,0,['menu-item-title'=>self::pages()[$slug][0],'menu-item-object'=>'page','menu-item-object-id'=>(int)$ids[$slug],'menu-item-type'=>'post_type','menu-item-status'=>'publish']);$count++;}}
        return $count;
    }

    public static function enhance_page_content($content){
        if(is_admin()||!is_singular('page'))return $content; $slug=get_post_field('post_name',get_the_ID()); $pages=self::pages(); if(!isset($pages[$slug]))return $content;
        $aud=$pages[$slug][2]; $visible=trim(wp_strip_all_tags(strip_shortcodes($content))); if($visible==='' && trim($content)==='')$content=self::fallback($pages[$slug][0],$slug,$aud);
        if(strpos($content,'t49-context-nav')===false) $content=self::context_links($aud,$slug).$content;
        return '<div class="t49-page-shell">'.$content.'</div>';
    }

    public static function role_menu($items,$args){
        if(is_admin())return $items; $user=wp_get_current_user();
        if(!is_user_logged_in()){$slug='login';$label='تسجيل الدخول';}
        elseif(current_user_can('manage_options')||in_array('tager_admin',$user->roles,true)){$slug='admin-portal';$label='بوابة الإدارة';}
        elseif(in_array('tager_vendor',$user->roles,true)){$slug='vendor-dashboard';$label='لوحة المورد';}
        else{$slug='my-account';$label='حسابي';}
        return $items.'<li class="menu-item t49-role-menu"><a href="'.esc_url(self::page_url($slug)).'">'.esc_html($label).'</a></li>';
    }

    public static function customer_order_details(){
        if(!is_user_logged_in())return '<div class="t49-alert">سجل الدخول لعرض تفاصيل الطلب.</div>';
        $id=absint($_GET['order_id']??0); if(!$id)return '<div class="t49-empty"><h2>اختر طلبًا</h2><p>افتح الطلب من صفحة طلباتي لعرض التفاصيل.</p><a class="t49-btn" href="'.esc_url(self::page_url('customer-orders')).'">طلباتي</a></div>';
        $p=get_post($id); if(!$p||$p->post_type!=='tager_order'||(int)$p->post_author!==get_current_user_id())return '<div class="t49-alert">الطلب غير موجود أو لا تملك صلاحية عرضه.</div>';
        $total=get_post_meta($id,'total',true);$status=get_post_meta($id,'status',true);$tracking=get_post_meta($id,'tracking_number',true);
        return '<div class="t49-card"><h2>الطلب #'.esc_html($id).'</h2><div class="t49-stats"><div><b>'.esc_html($status?:'new').'</b><span>الحالة</span></div><div><b>'.esc_html($total?:0).' ج.م</b><span>الإجمالي</span></div><div><b>'.esc_html($tracking?:'—').'</b><span>التتبع</span></div></div><a class="t49-btn" href="'.esc_url(self::page_url('support')).'">طلب مساعدة</a></div>';
    }

    public static function customer_security(){
        if(!is_user_logged_in())return '<div class="t49-alert">يلزم تسجيل الدخول.</div>'; $u=wp_get_current_user();
        return '<div class="t49-grid"><section class="t49-card"><h2>أمان الحساب</h2><p>رقم الهاتف: '.esc_html(get_user_meta($u->ID,'phone',true)?:'غير مسجل').'</p><p>البريد: '.esc_html($u->user_email?:'اختياري وغير مسجل').'</p><a class="t49-btn" href="'.esc_url(wp_lostpassword_url()).'">تغيير كلمة المرور</a></section><section class="t49-card"><h2>الجلسة الحالية</h2><p>يمكنك تسجيل الخروج من الجهاز الحالي بأمان.</p><a class="t49-btn secondary" href="'.esc_url(wp_logout_url(home_url('/'))).'">تسجيل الخروج</a></section></div>';
    }

    public static function saved_carts(){
        if(!is_user_logged_in())return '<div class="t49-alert">سجل الدخول لحفظ السلات.</div>';
        return '<div class="t49-card"><h2>السلات المحفوظة</h2><p>احتفظ بقوائم الشراء المتكررة للعودة إليها لاحقًا.</p><div class="t49-empty">لا توجد سلات محفوظة حاليًا.<br><a class="t49-btn" href="'.esc_url(self::page_url('shop')).'">ابدأ التسوق</a></div></div>';
    }

    public static function vendor_inventory(){
        if(!is_user_logged_in())return '<div class="t49-alert">يلزم تسجيل الدخول.</div>'; $u=wp_get_current_user(); if(!in_array('tager_vendor',$u->roles,true))return '<div class="t49-alert">هذه الصفحة للموردين.</div>';
        $q=new WP_Query(['post_type'=>'tager_product','author'=>$u->ID,'post_status'=>['publish','pending','draft'],'posts_per_page'=>50]); ob_start();
        echo '<div class="t49-card"><div class="t49-card-head"><h2>إدارة المخزون</h2><a class="t49-btn" href="'.esc_url(self::page_url('vendor-add-product')).'">إضافة منتج</a></div><table class="t49-table"><thead><tr><th>المنتج</th><th>SKU</th><th>المخزون</th><th>الحالة</th><th>تعديل</th></tr></thead><tbody>';
        if($q->have_posts())while($q->have_posts()){$q->the_post();$id=get_the_ID();echo '<tr><td>'.esc_html(get_the_title()).'</td><td>'.esc_html(get_post_meta($id,'sku',true)?:'—').'</td><td>'.esc_html(get_post_meta($id,'stock',true)?:0).'</td><td>'.esc_html(get_post_status($id)).'</td><td><a href="'.esc_url(add_query_arg('product_id',$id,self::page_url('vendor-add-product'))).'">فتح</a></td></tr>';}else echo '<tr><td colspan="5">لا توجد منتجات.</td></tr>';wp_reset_postdata();echo '</tbody></table></div>';return ob_get_clean();
    }

    public static function admin_link_page($atts=[],$content=null,$tag=''){
        if(!current_user_can('manage_options')&&!current_user_can('tager_manage_platform'))return '<div class="t49-alert">غير مصرح.</div>';
        $map=['tager_v49_admin_shipping'=>['إعدادات الشحن','admin.php?page=tager-shipping-cart-pricing'],'tager_v49_admin_payments'=>['إعدادات الدفع','admin.php?page=tager-payments-checkout'],'tager_v49_admin_team'=>['فريق الإدارة','admin.php?page=tager-admin-team'],'tager_v49_admin_reports'=>['التقارير','admin.php?page=tager-v26-operations']];$d=$map[$tag]??['لوحة الإدارة','admin.php'];
        return '<div class="t49-card"><h2>'.esc_html($d[0]).'</h2><p>افتح لوحة التحكم المخصصة لإدارة هذه الوظيفة.</p><a class="t49-btn" href="'.esc_url(admin_url($d[1])).'">فتح '.esc_html($d[0]).'</a></div>';
    }

    public static function sitemap(){
        $groups=['التسوق'=>['home','shop','categories','vendors','offers','cart'],'حساب العميل'=>['login','customer-register','my-account','customer-orders','customer-addresses','wishlist','support'],'المورد'=>['vendor-register','vendor-dashboard','vendor-products','vendor-add-product','vendor-orders','vendor-earnings','vendor-settings'],'المساعدة'=>['about','how-it-works','faq','contact-us','shipping','return-policy','privacy-policy','terms']];$p=self::pages();$h='<div class="t49-grid">';foreach($groups as $title=>$slugs){$h.='<section class="t49-card"><h2>'.esc_html($title).'</h2><ul>';foreach($slugs as $s)if(isset($p[$s]))$h.='<li><a href="'.esc_url(self::page_url($s)).'">'.esc_html($p[$s][0]).'</a></li>';$h.='</ul></section>';}$h.='</div>';return $h;
    }

    public static function workspace_nav_shortcode(){
        if(!is_user_logged_in())return self::context_links('guest');$u=wp_get_current_user();if(current_user_can('manage_options')||in_array('tager_admin',$u->roles,true))return self::context_links('admin');if(in_array('tager_vendor',$u->roles,true))return self::context_links('vendor');return self::context_links('customer');
    }

    public static function audit(){
        global $shortcode_tags; $rows=[];$sum=['total'=>0,'missing'=>0,'empty'=>0,'broken_shortcode'=>0,'empty_button'=>0,'missing_submit'=>0];
        foreach(self::pages() as $slug=>$def){$sum['total']++;$p=get_page_by_path($slug);$issues=[];if(!$p){$sum['missing']++;$issues[]='غير موجودة';}
            else{$c=(string)$p->post_content;if(trim($c)===''){$sum['empty']++;$issues[]='فارغة';}if(preg_match_all('/\[([a-zA-Z0-9_-]+)/',$c,$m))foreach($m[1] as $t)if(!isset($shortcode_tags[$t])){$sum['broken_shortcode']++;$issues[]='شورت كود غير مسجل: '.$t;break;}if(preg_match('/(?:href|action)\s*=\s*["\']\s*(?:#|javascript:void\(0\)|)["\']/i',$c)){$sum['empty_button']++;$issues[]='زر أو رابط فارغ';}if(stripos($c,'<form')!==false&&!preg_match('/type\s*=\s*["\']submit["\']|<button[^>]*>/i',$c)){$sum['missing_submit']++;$issues[]='نموذج بلا زر إرسال';}}
            $rows[]=['slug'=>$slug,'title'=>$def[0],'id'=>$p?$p->ID:0,'issues'=>$issues];}
        return ['summary'=>$sum,'rows'=>$rows];
    }

    public static function admin_menu(){add_menu_page('Tager V49 QA','Tager V49 QA','manage_options','tager-v49-qa',[__CLASS__,'admin_page'],'dashicons-yes-alt',2);}
    public static function admin_page(){if(!current_user_can('manage_options'))return;$data=get_option(self::OPT);$audit=$data['audit']??self::audit();$s=$audit['summary'];echo '<div class="wrap"><h1>Tager V49 — فحص الصفحات والأزرار</h1><p>ينشئ الصفحات الناقصة، يصلح المحتوى الفارغ، يربط مساحات العميل والمورد والإدارة، ويفحص الأزرار والنماذج.</p><div style="display:flex;gap:10px;flex-wrap:wrap">';foreach(['إجمالي الصفحات'=>'total','ناقصة'=>'missing','فارغة'=>'empty','Shortcode مكسور'=>'broken_shortcode','أزرار فارغة'=>'empty_button','نموذج بلا إرسال'=>'missing_submit'] as $label=>$key)echo '<div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:15px 20px"><b style="font-size:22px">'.(int)$s[$key].'</b><br>'.esc_html($label).'</div>';echo '</div><p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v49_repair'),self::NONCE)).'">إصلاح وربط جميع الصفحات</a> <a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v49_audit'),self::NONCE)).'">تشغيل الفحص</a></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>الملاحظات</th><th>فتح</th></tr></thead><tbody>';foreach($audit['rows'] as $r){echo '<tr><td>'.esc_html($r['title']).'</td><td>/'.esc_html($r['slug']).'/</td><td>'.esc_html($r['issues']?implode('، ',$r['issues']):'سليمة').'</td><td>'.($r['id']?'<a target="_blank" href="'.esc_url(get_permalink($r['id'])).'">فتح</a>':'—').'</td></tr>';}echo '</tbody></table></div>';}
    public static function repair_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);self::repair_all();wp_safe_redirect(admin_url('admin.php?page=tager-v49-qa&repaired=1'));exit;}
    public static function audit_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);update_option(self::OPT,['time'=>current_time('mysql'),'audit'=>self::audit()],false);wp_safe_redirect(admin_url('admin.php?page=tager-v49-qa&audited=1'));exit;}

    public static function frontend_assets(){?>
<style>
.t49-page-shell{max-width:1280px;margin:0 auto;padding:20px}.t49-context-nav{display:flex;gap:9px;overflow:auto;padding:10px 0 18px;scrollbar-width:thin}.t49-context-nav a{white-space:nowrap;text-decoration:none;border:1px solid #d7dfdb;border-radius:999px;padding:9px 14px;background:#fff;color:#173e32;font-weight:700}.t49-context-nav a:hover,.t49-context-nav a.is-active{background:#123f34;color:#fff;border-color:#123f34}.t49-hero{padding:34px;border-radius:24px;background:linear-gradient(135deg,#123f34,#24624f);color:#fff;margin-bottom:20px}.t49-hero span{color:#e7c46a;font-weight:800}.t49-hero h1{color:#fff;margin:8px 0}.t49-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}.t49-card{background:#fff;border:1px solid #e2e8e5;border-radius:18px;padding:20px;box-shadow:0 8px 28px rgba(18,63,52,.07);margin-bottom:16px}.t49-card-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.t49-btn{display:inline-flex;align-items:center;justify-content:center;background:#123f34;color:#fff!important;border-radius:10px;padding:11px 17px;text-decoration:none!important;font-weight:800;border:0;cursor:pointer}.t49-btn.secondary{background:#f0e4b8;color:#123f34!important}.t49-alert,.t49-empty{padding:22px;border-radius:14px;background:#fff8e1;border:1px solid #eed797;margin:16px 0}.t49-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin:15px 0}.t49-stats div{background:#f5f8f7;border-radius:12px;padding:15px}.t49-stats b,.t49-stats span{display:block}.t49-table{width:100%;border-collapse:collapse}.t49-table th,.t49-table td{padding:12px;border-bottom:1px solid #e5ebe8;text-align:right}@media(max-width:700px){.t49-page-shell{padding:12px}.t49-hero{padding:24px 18px}.t49-card-head{align-items:flex-start;flex-direction:column}.t49-table{display:block;overflow:auto}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('a[href="#"],a[href=""],button:not([type]):not([data-action])').forEach(function(el){if(el.tagName==='A'){el.addEventListener('click',function(e){e.preventDefault();console.warn('Tager: empty link prevented',el);});}});
 document.querySelectorAll('form').forEach(function(form){form.addEventListener('submit',function(){var b=form.querySelector('[type="submit"],button');if(b&&!b.dataset.loading){b.dataset.loading='1';b.dataset.old=b.textContent;b.textContent='جاري الحفظ...';b.disabled=true;setTimeout(function(){b.disabled=false;b.textContent=b.dataset.old||'حفظ';delete b.dataset.loading;},7000);}});});
});
</script><?php }
}
Tager_V49_Complete_Pages_Buttons::init();
