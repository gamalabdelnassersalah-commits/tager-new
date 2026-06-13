<?php
/**
 * Plugin Name: Tager V34 Complete Pages & Content
 * Description: Creates, repairs and enriches every core marketplace page with complete content and working navigation.
 * Version: 34.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V34_Complete_Pages {
    private static $map = [];

    public static function init(){
        add_action('init',[__CLASS__,'register_shortcodes'],300);
        add_action('init',[__CLASS__,'repair_all_pages'],350);
        add_action('after_setup_theme',[__CLASS__,'ensure_theme_support'],50);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],300);
        add_action('admin_menu',[__CLASS__,'admin_menu'],99);
        add_action('admin_post_tager_v34_repair',[__CLASS__,'manual_repair']);
        add_filter('the_content',[__CLASS__,'empty_page_fallback'],99);
    }

    private static function defs(){
        return [
            'home'=>['الرئيسية','[tager_v29_home]'],
            'shop'=>['المنتجات','[tager_shop]'],
            'categories'=>['الأقسام','[tager_v22_categories]'],
            'vendors'=>['دليل الموردين','[tager_v22_vendors_directory]'],
            'deals'=>['العروض','[tager_v22_deals]'],
            'brands'=>['العلامات التجارية','[tager_v22_brands]'],
            'login'=>['تسجيل الدخول','[tager_v34_login_hub]'],
            'register'=>['إنشاء حساب','[tager_v34_register_hub]'],
            'customer-register'=>['تسجيل عميل','[tager_customer_register]'],
            'vendor-register'=>['تسجيل مورد','[tager_vendor_register]'],
            'customer-account'=>['حساب العميل','[tager_customer_account]'],
            'vendor-dashboard'=>['لوحة المورد','[tager_vendor_dashboard]'],
            'cart'=>['السلة وإتمام الطلب','[tager_cart]'],
            'wishlist'=>['المفضلة','[tager_pro_wishlist]'],
            'compare'=>['مقارنة المنتجات','[tager_v7_compare]'],
            'orders'=>['طلباتي','[tager_v34_orders]'],
            'addresses'=>['عناويني','[tager_v34_addresses]'],
            'notifications'=>['الإشعارات','[tager_notifications]'],
            'messages'=>['الرسائل','[tager_v8_messages]'],
            'support'=>['الدعم والتذاكر','[tager_support]'],
            'tracking'=>['تتبع الطلب','[tager_v8_tracking]'],
            'returns'=>['طلبات الاسترجاع','[tager_v7_returns]'],
            'invoices'=>['الفواتير','[tager_v7_invoices]'],
            'saved-lists'=>['قوائم الشراء','[tager_v9_saved_lists]'],
            'rfq'=>['طلب عرض سعر','[tager_v7_rfq]'],
            'vendor-plans'=>['باقات الموردين','[tager_v8_vendor_plans]'],
            'vendor-scorecard'=>['أداء المورد','[tager_v9_vendor_scorecard]'],
            'disputes'=>['النزاعات','[tager_v9_disputes]'],
            'promotions'=>['العروض الترويجية','[tager_v8_promotions]'],
            'about'=>['عن تاجر','[tager_v22_about]'],
            'how-it-works'=>['كيف تعمل المنصة','[tager_v22_how_it_works]'],
            'buyer-guide'=>['دليل المشتري','[tager_v22_buyer_guide]'],
            'seller-guide'=>['دليل المورد','[tager_v22_seller_guide]'],
            'business'=>['حلول الشركات','[tager_v22_business]'],
            'pricing'=>['الأسعار والباقات','[tager_v22_pricing]'],
            'faq'=>['الأسئلة الشائعة','[tager_v22_faq]'],
            'contact'=>['تواصل معنا','[tager_v22_contact]'],
            'help-center'=>['مركز المساعدة','[tager_v22_help_center]'],
            'shipping-info'=>['الشحن والتوصيل','[tager_v22_shipping_info]'],
            'returns-policy'=>['سياسة الاسترجاع','[tager_v22_returns_policy]'],
            'terms'=>['الشروط والأحكام','[tager_v22_terms_page]'],
            'privacy'=>['سياسة الخصوصية','[tager_v22_privacy_page]'],
            'payment-methods'=>['طرق الدفع','[tager_v34_payment_methods]'],
            'egypt-governorates'=>['مناطق التوصيل','[tager_v34_governorates]'],
            'seller-minimums'=>['الحد الأدنى للطلب','[tager_v34_vendor_minimums]'],
            'account-security'=>['أمان الحساب','[tager_v34_account_security]'],
            'admin-help'=>['دليل الإدارة','[tager_v34_admin_help]'],
        ];
    }

    public static function register_shortcodes(){
        foreach(['login_hub','register_hub','orders','addresses','payment_methods','governorates','vendor_minimums','account_security','admin_help'] as $s){
            add_shortcode('tager_v34_'.$s,[__CLASS__,$s]);
        }
    }

    public static function ensure_theme_support(){
        add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('menus');
    }

    public static function repair_all_pages(){
        if (get_option('tager_v34_repaired') && !is_admin()) return;
        $defs=self::defs(); $pages=get_option('tager_pages',[]);
        foreach($defs as $slug=>$d){
            $p=get_page_by_path($slug);
            if(!$p){
                $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$d[0],'post_content'=>$d[1]]);
            } else {
                $id=$p->ID;
                $content=trim((string)$p->post_content);
                if($content==='' || $content==='<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->'){
                    wp_update_post(['ID'=>$id,'post_title'=>$d[0],'post_content'=>$d[1]]);
                }
            }
            if(!empty($id) && !is_wp_error($id)) $pages[$slug]=$id;
        }
        update_option('tager_pages',$pages);
        self::$map=$pages;
        self::ensure_menu($pages);
        if(!empty($pages['home'])){ update_option('show_on_front','page'); update_option('page_on_front',(int)$pages['home']); }
        update_option('tager_v34_repaired',time());
    }

    private static function ensure_menu($pages){
        $name='Tager Main Menu'; $menu=wp_get_nav_menu_object($name);
        $menu_id=$menu?$menu->term_id:wp_create_nav_menu($name);
        if(is_wp_error($menu_id)) return;
        $items=wp_get_nav_menu_items($menu_id); if(!$items) $items=[];
        $linked=[]; foreach($items as $i) $linked[(int)$i->object_id]=1;
        foreach(['home','shop','categories','vendors','deals','business','help-center','login'] as $slug){
            if(!empty($pages[$slug]) && empty($linked[(int)$pages[$slug]])){
                wp_update_nav_menu_item($menu_id,0,['menu-item-title'=>get_the_title($pages[$slug]),'menu-item-object'=>'page','menu-item-object-id'=>$pages[$slug],'menu-item-type'=>'post_type','menu-item-status'=>'publish']);
            }
        }
        $loc=get_theme_mod('nav_menu_locations',[]); if(empty($loc['primary'])){$loc['primary']=$menu_id; set_theme_mod('nav_menu_locations',$loc);}    
    }

    private static function page_url($slug){ $p=get_option('tager_pages',[]); return !empty($p[$slug])?get_permalink($p[$slug]):home_url('/'.$slug.'/'); }
    private static function wrap($title,$lead,$body,$actions=''){
        return '<div class="v34-shell"><section class="v34-hero"><div><span>تاجر</span><h1>'.esc_html($title).'</h1><p>'.esc_html($lead).'</p><div class="v34-actions">'.$actions.'</div></div><div class="v34-mark">T</div></section>'.$body.'</div>';
    }
    private static function cards($items){$o='<div class="v34-grid">';foreach($items as $x){$o.='<article class="v34-card"><div class="v34-icon">'.$x[0].'</div><h3>'.esc_html($x[1]).'</h3><p>'.esc_html($x[2]).'</p>'.(!empty($x[3])?$x[3]:'').'</article>';}return $o.'</div>';}
    private static function button($label,$url,$secondary=false){return '<a class="v34-btn'.($secondary?' alt':'').'" href="'.esc_url($url).'">'.esc_html($label).'</a>';}

    public static function login_hub(){
        if(is_user_logged_in()) return self::wrap('أنت مسجل الدخول','يمكنك الانتقال مباشرة إلى لوحة حسابك.','',self::button('فتح حسابي',self::page_url('customer-account')).self::button('لوحة المورد',self::page_url('vendor-dashboard'),true));
        $body=self::cards([
            ['🛒','دخول العميل','تابع الطلبات والعناوين والمفضلة والدعم.',self::button('دخول العميل',wp_login_url(self::page_url('customer-account')))],
            ['🏪','دخول المورد','إدارة المنتجات والأسعار والمخزون والطلبات والأرباح.',self::button('دخول المورد',wp_login_url(self::page_url('vendor-dashboard')))],
            ['🛡️','دخول الإدارة','لأعضاء الإدارة المصرح لهم فقط.',self::button('دخول الإدارة',wp_login_url(admin_url()),true)],
        ]);
        $body.='<section class="v34-panel"><h2>يمكن تسجيل الدخول برقم الهاتف أو البريد الإلكتروني</h2><p>رقم الهاتف هو الوسيلة الأساسية. البريد الإلكتروني اختياري، ويمكن استخدامه للدخول إذا كان مضافًا للحساب.</p>'.wp_login_form(['echo'=>false,'redirect'=>self::page_url('customer-account'),'label_username'=>'رقم الهاتف أو البريد الإلكتروني','remember'=>true]).'<p><a href="'.esc_url(wp_lostpassword_url()).'">نسيت كلمة المرور؟</a></p></section>';
        return self::wrap('تسجيل الدخول','اختر نوع حسابك ثم ادخل برقم الهاتف أو البريد الإلكتروني.',$body,self::button('إنشاء حساب جديد',self::page_url('register'),true));
    }
    public static function register_hub(){return self::wrap('إنشاء حساب','اختر نوع الحساب المناسب لك. البريد الإلكتروني اختياري، ورقم الهاتف مطلوب.',self::cards([
        ['👤','حساب عميل','تسوق قطاعي أو جملة، احفظ العناوين وتابع الطلبات.',self::button('تسجيل عميل',self::page_url('customer-register'))],
        ['🏬','حساب مورد','أضف المنتجات والأسعار والصور بعد مراجعة الإدارة.',self::button('تسجيل مورد',self::page_url('vendor-register'))],
        ['🏢','حساب شركة','اطلب كميات كبيرة وعروض أسعار مخصصة.',self::button('حلول الشركات',self::page_url('business'),true)],
    ]),'');}
    public static function orders(){return self::protected_page('طلباتي','تابع حالة الطلبات والمدفوعات والشحن والمرتجعات.',['📦'=>'الطلبات الحالية','🚚'=>'الشحن والتتبع','↩️'=>'الاسترجاع','🧾'=>'الفواتير']);}
    public static function addresses(){return self::protected_page('عناويني','أضف عناوين التوصيل وحدد المحافظة والمنطقة والعلامة المميزة.',['🏠'=>'عنوان المنزل','🏢'=>'عنوان الشركة','📍'=>'المحافظة والمنطقة','☎️'=>'هاتف الاستلام']);}
    private static function protected_page($title,$lead,$items){
        if(!is_user_logged_in()) return self::wrap($title,'سجّل الدخول للوصول إلى هذه الصفحة.','',self::button('تسجيل الدخول',self::page_url('login')));
        $arr=[];foreach($items as $i=>$t)$arr[]=[$i,$t,'هذه الخدمة متاحة من لوحة حسابك وتُحفظ بأمان.',''];
        return self::wrap($title,$lead,self::cards($arr),self::button('العودة إلى حسابي',self::page_url('customer-account'),true));
    }
    public static function payment_methods(){return self::wrap('طرق الدفع','اختر الطريقة الأنسب عند إتمام الطلب. قد تظهر رسوم حسب إعدادات الإدارة.',self::cards([
        ['💵','الدفع عند الاستلام','متاح حسب قيمة الطلب والمحافظة وسياسة المورد.',''],['🏦','تحويل بنكي','تحويل مباشر ورفع إثبات الدفع.',''],['⚡','InstaPay','تحويل سريع باستخدام مرجع العملية.',''],['📱','المحافظ الإلكترونية','Vodafone Cash وOrange Cash وe& Cash وWE Pay.',''],['🏪','فوري','سداد من خلال رقم مرجعي عند تفعيل الربط.',''],['💳','بطاقات بنكية','جاهزة للربط مع بوابة دفع معتمدة.','']
    ]),'');}
    public static function governorates(){ $g=['القاهرة','الجيزة','الإسكندرية','الدقهلية','البحر الأحمر','البحيرة','الفيوم','الغربية','الإسماعيلية','المنوفية','المنيا','القليوبية','الوادي الجديد','السويس','أسوان','أسيوط','بني سويف','بورسعيد','دمياط','الشرقية','جنوب سيناء','كفر الشيخ','مطروح','الأقصر','قنا','شمال سيناء','سوهاج'];$b='<div class="v34-tags">';foreach($g as $x)$b.='<span>'.esc_html($x).'</span>';return self::wrap('مناطق التوصيل','يدعم النظام جميع المحافظات المصرية مع رسوم ومدة توصيل مستقلة لكل محافظة.',$b.'</div>',''); }
    public static function vendor_minimums(){return self::wrap('الحد الأدنى للطلب','في السلة العادية يجب تحقيق الحد الأدنى لكل مورد. ويمكن اختيار السلة المختلطة برسوم خدمة محددة من الإدارة.',self::cards([
        ['1️⃣','سلة الموردين المنفصلة','كل مورد يُحسب منفصلًا ويلزم حد أدنى خاص به.',''],['2️⃣','السلة المختلطة','تجمع أصنافًا من عدة موردين دون حد أدنى منفصل، وتُضاف رسوم الخدمة.',''],['3️⃣','أسعار الكمية','يتغير السعر تلقائيًا بين قطاعي وجملة وجملة الجملة.','']
    ]),self::button('فتح السلة',self::page_url('cart')));}
    public static function account_security(){return self::protected_page('أمان الحساب','إدارة كلمة المرور والجلسات وبيانات الاتصال والتنبيهات.',['🔐'=>'تغيير كلمة المرور','📱'=>'رقم الهاتف','✉️'=>'البريد الاختياري','🖥️'=>'الجلسات النشطة']);}
    public static function admin_help(){
        if(!current_user_can('manage_options')) return self::wrap('دليل الإدارة','هذه الصفحة متاحة للإدارة فقط.','','');
        return self::wrap('دليل الإدارة','ملخص إدارة المنصة قبل الإطلاق.',self::cards([
            ['👥','المستخدمون والصلاحيات','إضافة أعضاء الإدارة وتحديد صلاحيات كل دور.',''],['🏪','الموردون','مراجعة الموردين والمستندات والحد الأدنى للطلب.',''],['📦','المنتجات','مراجعة المنتجات والأسعار والمخزون قبل النشر.',''],['🧾','الطلبات','متابعة الطلب والدفع والشحن والمرتجعات.',''],['💰','المدفوعات','إدارة وسائل الدفع والرسوم وإثباتات التحويل.',''],['🚚','الشحن','تحديد رسوم كل محافظة والشحن المجاني والسريع.','']
        ]),self::button('فتح لوحة الإدارة',admin_url(),true));
    }

    public static function empty_page_fallback($content){
        if(!is_page() || trim(wp_strip_all_tags(strip_shortcodes($content)))!=='') return $content;
        $slug=get_post_field('post_name',get_the_ID()); $defs=self::defs();
        if(isset($defs[$slug])) return do_shortcode($defs[$slug][1]);
        return $content;
    }

    public static function assets(){
        $css=':root{--v34-green:#075b43;--v34-dark:#063d2e;--v34-gold:#d7a545;--v34-bg:#f5f8f6;--v34-line:#dce7e1}.v34-shell{max-width:1240px;margin:0 auto;padding:28px 18px 60px}.v34-hero{display:grid;grid-template-columns:1fr auto;gap:30px;align-items:center;background:linear-gradient(135deg,var(--v34-dark),#0a7a57);color:#fff;padding:42px;border-radius:28px;margin-bottom:26px;box-shadow:0 18px 48px rgba(6,61,46,.18)}.v34-hero span{color:#ffe1a1;font-weight:900}.v34-hero h1{font-size:clamp(32px,5vw,52px);margin:7px 0}.v34-hero p{color:#ddf1e8;max-width:760px;font-size:17px}.v34-mark{width:120px;height:120px;border-radius:30px;background:rgba(255,255,255,.12);display:grid;place-items:center;font-size:58px;font-weight:900;color:#ffe1a1}.v34-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.v34-btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;border-radius:12px;background:var(--v34-gold);color:#132b22!important;text-decoration:none!important;font-weight:900}.v34-btn.alt{background:#fff;color:var(--v34-green)!important}.v34-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.v34-card,.v34-panel{background:#fff;border:1px solid var(--v34-line);border-radius:20px;padding:24px;box-shadow:0 8px 25px rgba(12,60,43,.06)}.v34-icon{font-size:38px}.v34-card h3{margin:10px 0 5px}.v34-card p,.v34-panel p{color:#60726a}.v34-panel{margin-top:20px}.v34-panel form{display:grid;gap:12px;max-width:520px}.v34-panel input{width:100%;padding:12px;border:1px solid var(--v34-line);border-radius:10px}.v34-panel input[type=submit]{background:var(--v34-green);color:#fff;font-weight:900;cursor:pointer}.v34-tags{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.v34-tags span{background:#fff;border:1px solid var(--v34-line);border-radius:14px;padding:14px;text-align:center;font-weight:800}@media(max-width:800px){.v34-hero{grid-template-columns:1fr;padding:28px 22px}.v34-mark{display:none}.v34-grid{grid-template-columns:1fr}.v34-tags{grid-template-columns:repeat(2,1fr)}}';
        wp_register_style('tager-v34-inline',false); wp_enqueue_style('tager-v34-inline'); wp_add_inline_style('tager-v34-inline',$css);
    }

    public static function admin_menu(){ add_submenu_page('tager-control','V34 Complete Pages','V34 Complete Pages','manage_options','tager-v34-pages',[__CLASS__,'admin_page']); }
    public static function admin_page(){
        $defs=self::defs(); echo '<div class="wrap"><h1>Tager V34 — Complete Pages</h1><p>فحص وإنشاء وإصلاح جميع الصفحات والمحتوى والقائمة الرئيسية.</p><p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v34_repair'),'tager_v34_repair')).'">إصلاح جميع الصفحات والروابط</a></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>الحالة</th><th>فتح</th></tr></thead><tbody>';
        foreach($defs as $slug=>$d){$p=get_page_by_path($slug);$ok=$p&&trim((string)$p->post_content)!=='';echo '<tr><td>'.esc_html($d[0]).'</td><td>/'.esc_html($slug).'/</td><td>'.($ok?'✅ مكتملة':'❌ ناقصة').'</td><td>'.($p?'<a target="_blank" href="'.esc_url(get_permalink($p)).'">فتح</a>':'—').'</td></tr>';}
        echo '</tbody></table></div>';
    }
    public static function manual_repair(){ if(!current_user_can('manage_options')) wp_die('غير مصرح'); check_admin_referer('tager_v34_repair'); delete_option('tager_v34_repaired'); self::repair_all_pages(); flush_rewrite_rules(); wp_safe_redirect(admin_url('admin.php?page=tager-v34-pages&repaired=1')); exit; }
}
Tager_V34_Complete_Pages::init();
