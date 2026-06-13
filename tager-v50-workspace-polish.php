<?php
/**
 * Plugin Name: Tager V50 Workspace Polish & Complete Actions
 * Description: Completes customer, vendor and admin workspaces with onboarding, activity, quick actions, page checks, role-aware navigation and safer buttons.
 * Version: 50.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V50_Workspace_Polish {
    const OPT = 'tager_v50_status';
    const NONCE = 'tager_v50_nonce';

    public static function init() {
        add_action('init', [__CLASS__, 'shortcodes'], 210);
        add_action('init', [__CLASS__, 'ensure_pages'], 260);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 160);
        add_action('admin_post_tager_v50_repair', [__CLASS__, 'repair']);
        add_action('admin_post_tager_v50_run_qa', [__CLASS__, 'run_qa']);
        add_action('template_redirect', [__CLASS__, 'protect_pages'], 4);
        add_filter('the_content', [__CLASS__, 'inject_breadcrumbs'], 1200);
        add_action('wp_footer', [__CLASS__, 'assets'], 90);
    }

    private static function page_url($slug) {
        $p = get_page_by_path($slug);
        return $p ? get_permalink($p) : home_url('/'.trim($slug,'/').'/');
    }

    private static function is_vendor($u=null) {
        $u = $u ?: wp_get_current_user();
        return in_array('tager_vendor', (array)$u->roles, true);
    }

    private static function is_admin_user($u=null) {
        $u = $u ?: wp_get_current_user();
        return user_can($u, 'manage_options') || in_array('tager_admin', (array)$u->roles, true) || user_can($u, 'tager_manage_platform');
    }

    private static function pages() {
        return [
            'customer-home' => ['مركز العميل', '[tager_v50_customer_home]'],
            'vendor-home' => ['مركز المورد', '[tager_v50_vendor_home]'],
            'admin-home' => ['مركز الإدارة', '[tager_v50_admin_home]'],
            'vendor-onboarding' => ['تهيئة حساب المورد', '[tager_v50_vendor_onboarding]'],
            'customer-activity' => ['نشاط حسابي', '[tager_v50_customer_activity]'],
            'vendor-product-quality' => ['جودة بيانات المنتجات', '[tager_v50_product_quality]'],
        ];
    }

    public static function shortcodes() {
        add_shortcode('tager_v50_customer_home', [__CLASS__, 'customer_home']);
        add_shortcode('tager_v50_vendor_home', [__CLASS__, 'vendor_home']);
        add_shortcode('tager_v50_admin_home', [__CLASS__, 'admin_home']);
        add_shortcode('tager_v50_vendor_onboarding', [__CLASS__, 'vendor_onboarding']);
        add_shortcode('tager_v50_customer_activity', [__CLASS__, 'customer_activity']);
        add_shortcode('tager_v50_product_quality', [__CLASS__, 'product_quality']);
    }

    public static function ensure_pages() {
        if (get_option('tager_v50_pages_created')) return;
        self::repair_pages();
        update_option('tager_v50_pages_created', current_time('mysql'), false);
    }

    private static function repair_pages() {
        $created=0; $updated=0;
        foreach (self::pages() as $slug=>$def) {
            [$title,$content]=$def;
            $p=get_page_by_path($slug, OBJECT, 'page');
            if (!$p) {
                $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$content]);
                if ($id && !is_wp_error($id)) $created++;
            } elseif (trim((string)$p->post_content)==='' || strpos((string)$p->post_content,'tager_v50_')===false) {
                wp_update_post(['ID'=>$p->ID,'post_title'=>$title,'post_content'=>$content,'post_status'=>'publish']);
                $updated++;
            }
        }
        flush_rewrite_rules(false);
        return compact('created','updated');
    }

    private static function count_posts($type,$args=[]) {
        $q=new WP_Query(array_merge(['post_type'=>$type,'post_status'=>['publish','pending','draft','private'],'posts_per_page'=>1,'fields'=>'ids'], $args));
        return (int)$q->found_posts;
    }

    private static function user_phone($id) {
        foreach (['phone','billing_phone','tager_phone','mobile'] as $key) {
            $v=trim((string)get_user_meta($id,$key,true)); if ($v!=='') return $v;
        }
        return '';
    }

    private static function completion($items) {
        $total=count($items); $done=0; foreach($items as $ok) if($ok) $done++;
        return $total ? (int)round(($done/$total)*100) : 0;
    }

    private static function stat($value,$label,$url='') {
        $inner='<b>'.esc_html($value).'</b><span>'.esc_html($label).'</span>';
        return $url ? '<a class="tv50-stat" href="'.esc_url($url).'">'.$inner.'</a>' : '<div class="tv50-stat">'.$inner.'</div>';
    }

    public static function customer_home() {
        if (!is_user_logged_in()) return self::login_required();
        $u=wp_get_current_user(); if(self::is_vendor($u)||self::is_admin_user($u)) return '<div class="tv50-alert">هذه الصفحة مخصصة للعملاء.</div>';
        $orders=self::count_posts('tager_order',['author'=>$u->ID]);
        $addresses=(array)get_user_meta($u->ID,'tager_addresses',true);
        $completion=self::completion([$u->display_name!=='', self::user_phone($u->ID)!=='', !empty($addresses), $u->user_email!=='' && strpos($u->user_email,'@')!==false]);
        $h='<section class="tv50-hero"><div><span>مرحبًا بك</span><h1>'.esc_html($u->display_name ?: 'عميل تاجر').'</h1><p>تابع طلباتك وعناوينك ومفضلاتك من مكان واحد.</p></div><div class="tv50-progress"><b>'.$completion.'%</b><span>اكتمال الحساب</span><i style="--p:'.$completion.'%"></i></div></section>';
        $h.='<div class="tv50-stats">'.self::stat($orders,'إجمالي الطلبات',self::page_url('customer-orders')).self::stat(count($addresses),'العناوين',self::page_url('customer-addresses')).self::stat('فتح','المفضلة',self::page_url('wishlist')).self::stat('ابدأ','التسوق',self::page_url('shop')).'</div>';
        $h.='<div class="tv50-grid"><section class="tv50-card"><h2>اختصارات الحساب</h2>'.self::action_grid([
            ['طلباتي','تابع حالات الطلبات والفواتير','customer-orders'],['عناويني','أضف عنوانًا ومحافظة ومركزًا','customer-addresses'],['بيانات الحساب','حدّث الهاتف والبريد الاختياري','customer-profile'],['الدعم','افتح تذكرة أو تابع شكوى','support'],['السلات المحفوظة','احتفظ بقوائم الشراء المتكررة','saved-carts'],['نشاط الحساب','شاهد آخر عملياتك','customer-activity']
        ]).'</section><section class="tv50-card"><h2>الخطوات المقترحة</h2>'.self::checklist([
            ['تأكيد رقم الهاتف', self::user_phone($u->ID)!=='', 'customer-profile'],
            ['إضافة عنوان توصيل', !empty($addresses), 'customer-addresses'],
            ['تصفح السوق', true, 'shop'],
            ['حفظ أول سلة', false, 'saved-carts'],
        ]).'</section></div>';
        return '<div class="tv50-shell">'.$h.'</div>';
    }

    public static function vendor_home() {
        if (!is_user_logged_in()) return self::login_required();
        $u=wp_get_current_user(); if(!self::is_vendor($u)) return '<div class="tv50-alert">هذه الصفحة مخصصة للموردين.</div>';
        $products=self::count_posts('tager_product',['author'=>$u->ID]);
        $published=self::count_posts('tager_product',['author'=>$u->ID,'post_status'=>'publish']);
        $pending=self::count_posts('tager_product',['author'=>$u->ID,'post_status'=>'pending']);
        $store=trim((string)get_user_meta($u->ID,'store_name',true));
        $logo=(int)get_user_meta($u->ID,'tager_vendor_logo_id',true);
        $governorate=trim((string)get_user_meta($u->ID,'governorate',true));
        $min_order=(float)get_user_meta($u->ID,'vendor_min_order',true);
        $completion=self::completion([$store!=='',self::user_phone($u->ID)!=='',$logo>0,$governorate!=='',$min_order>0,$products>0]);
        $h='<section class="tv50-hero vendor"><div><span>مساحة المورد</span><h1>'.esc_html($store ?: $u->display_name).'</h1><p>أضف المنتجات وتابع الطلبات والمخزون والأرباح.</p></div><div class="tv50-progress"><b>'.$completion.'%</b><span>جاهزية المتجر</span><i style="--p:'.$completion.'%"></i></div></section>';
        $h.='<div class="tv50-stats">'.self::stat($products,'كل المنتجات',self::page_url('vendor-products')).self::stat($published,'منشور',self::page_url('vendor-products')).self::stat($pending,'تحت المراجعة',self::page_url('vendor-products')).self::stat('إضافة','منتج جديد',self::page_url('vendor-add-product')).'</div>';
        $h.='<div class="tv50-grid"><section class="tv50-card"><h2>إدارة المتجر</h2>'.self::action_grid([
            ['إضافة منتج','الأسعار الثلاثة والمخزون والصور','vendor-add-product'],['منتجاتي','تعديل ومراجعة حالة المنتجات','vendor-products'],['المخزون','تابع الكميات والتنبيهات','vendor-inventory'],['الطلبات','جهّز واشحن طلبات العملاء','vendor-orders'],['الأرباح','العمولة وصافي المستحق','vendor-earnings'],['جودة المنتجات','اكشف البيانات أو الصور الناقصة','vendor-product-quality'],['سوق المورد','تابع السوق وأسعار المنافسين','vendor-market'],['إعدادات المتجر','المحافظة والمركز والحد الأدنى','vendor-settings']
        ]).'</section><section class="tv50-card"><h2>قائمة جاهزية المورد</h2>'.self::checklist([
            ['اسم المتجر', $store!=='', 'vendor-settings'],['رقم الهاتف', self::user_phone($u->ID)!=='', 'vendor-settings'],['شعار المتجر', $logo>0, 'vendor-media'],['المحافظة والمركز', $governorate!=='', 'vendor-location'],['الحد الأدنى للطلب', $min_order>0, 'vendor-settings'],['إضافة أول منتج', $products>0, 'vendor-add-product']
        ]).'</section></div>';
        return '<div class="tv50-shell">'.$h.'</div>';
    }

    public static function admin_home() {
        if (!is_user_logged_in() || !self::is_admin_user()) return '<div class="tv50-alert">غير مصرح بالدخول.</div>';
        $vendors=get_users(['role'=>'tager_vendor','fields'=>'ids']);
        $customers=get_users(['role'=>'tager_customer','fields'=>'ids']);
        $pending_v=0; foreach($vendors as $id) if(get_user_meta($id,'vendor_status',true)!=='approved') $pending_v++;
        $pending_p=self::count_posts('tager_product',['post_status'=>'pending']);
        $orders=self::count_posts('tager_order');
        $h='<section class="tv50-hero admin"><div><span>مركز القيادة</span><h1>إدارة منصة تاجر</h1><p>الموافقات والطلبات والمالية والمحتوى من لوحة واحدة.</p></div><a class="tv50-primary" href="'.esc_url(admin_url()).'">لوحة WordPress</a></section>';
        $h.='<div class="tv50-stats">'.self::stat(count($vendors),'الموردون',self::page_url('admin-vendors')).self::stat($pending_v,'موردون منتظرون',self::page_url('admin-approvals')).self::stat($pending_p,'منتجات للمراجعة',self::page_url('admin-products')).self::stat($orders,'الطلبات',self::page_url('admin-orders')).'</div>';
        $h.='<div class="tv50-grid"><section class="tv50-card"><h2>عمليات الإدارة</h2>'.self::action_grid([
            ['الموافقات','الموردون والمنتجات المنتظرة','admin-approvals'],['إدارة الموردين','الحالة والعمولة والحد الأدنى','admin-vendors'],['مراجعة المنتجات','اعتماد أو رفض المنتجات','admin-products'],['إدارة الطلبات','الدفع والشحن والحالات','admin-orders'],['المالية','العمولات والسحوبات','admin-finance'],['الشحن','المحافظات والأسعار والشحن المجاني','admin-shipping'],['الدفع','تفعيل الطرق وتحديد الرسوم','admin-payments'],['فريق الإدارة','الأدوار والصلاحيات','admin-team'],['التقارير','المبيعات والأداء','admin-reports'],['صحة النظام','فحص الصفحات والروابط','admin-system']
        ]).'</section><section class="tv50-card"><h2>حالة الإطلاق</h2>'.self::system_summary().'</section></div>';
        return '<div class="tv50-shell">'.$h.'</div>';
    }

    private static function action_grid($items) {
        $h='<div class="tv50-actions">';
        foreach($items as $i) $h.='<a href="'.esc_url(self::page_url($i[2])).'"><b>'.esc_html($i[0]).'</b><span>'.esc_html($i[1]).'</span><em>فتح ←</em></a>';
        return $h.'</div>';
    }

    private static function checklist($items) {
        $h='<ul class="tv50-checklist">';
        foreach($items as $i) $h.='<li class="'.($i[1]?'done':'todo').'"><span>'.($i[1]?'✓':'!').'</span><b>'.esc_html($i[0]).'</b><a href="'.esc_url(self::page_url($i[2])).'">'.($i[1]?'عرض':'استكمال').'</a></li>';
        return $h.'</ul>';
    }

    public static function vendor_onboarding() { return self::vendor_home(); }

    public static function customer_activity() {
        if(!is_user_logged_in()) return self::login_required();
        $u=wp_get_current_user();
        $q=new WP_Query(['post_type'=>'tager_order','author'=>$u->ID,'post_status'=>['publish','private','draft'],'posts_per_page'=>10]);
        $h='<div class="tv50-card"><h2>آخر نشاط للحساب</h2><div class="tv50-timeline">';
        if($q->have_posts()) while($q->have_posts()){ $q->the_post(); $id=get_the_ID(); $status=get_post_meta($id,'status',true)?:'new'; $h.='<article><i></i><div><b>طلب #'.$id.'</b><span>'.esc_html($status).' — '.esc_html(get_the_date()).'</span></div><a href="'.esc_url(add_query_arg('order_id',$id,self::page_url('customer-order-details'))).'">التفاصيل</a></article>'; }
        else $h.='<div class="tv50-empty">لا يوجد نشاط بعد. <a href="'.esc_url(self::page_url('shop')).'">ابدأ التسوق</a></div>';
        wp_reset_postdata(); return '<div class="tv50-shell">'.$h.'</div></div>';
    }

    public static function product_quality() {
        if(!is_user_logged_in() || !self::is_vendor()) return '<div class="tv50-alert">هذه الصفحة للموردين.</div>';
        $u=wp_get_current_user(); $q=new WP_Query(['post_type'=>'tager_product','author'=>$u->ID,'post_status'=>['publish','pending','draft'],'posts_per_page'=>100]);
        $h='<div class="tv50-card"><h2>جودة بيانات المنتجات</h2><p>أكمل البيانات الناقصة لتحسين ظهور المنتج وثقة العميل.</p><div class="tv50-table-wrap"><table class="tv50-table"><thead><tr><th>المنتج</th><th>الصورة</th><th>الأسعار</th><th>المخزون</th><th>الوصف</th><th>النتيجة</th><th></th></tr></thead><tbody>';
        if($q->have_posts()) while($q->have_posts()){ $q->the_post(); $id=get_the_ID(); $img=has_post_thumbnail($id); $ret=(float)get_post_meta($id,'retail_price',true); $wh=(float)get_post_meta($id,'wholesale_price',true); $bulk=(float)get_post_meta($id,'bulk_price',true); $prices=$ret>0&&$wh>0&&$bulk>0; $stock=(int)get_post_meta($id,'stock',true)>0; $desc=strlen(trim(wp_strip_all_tags(get_post_field('post_content',$id))))>30; $score=self::completion([$img,$prices,$stock,$desc]); $h.='<tr><td>'.esc_html(get_the_title()).'</td><td>'.($img?'✓':'ناقص').'</td><td>'.($prices?'✓':'ناقص').'</td><td>'.($stock?'✓':'ناقص').'</td><td>'.($desc?'✓':'ناقص').'</td><td><b>'.$score.'%</b></td><td><a href="'.esc_url(add_query_arg('product_id',$id,self::page_url('vendor-add-product'))).'">تعديل</a></td></tr>'; }
        else $h.='<tr><td colspan="7">لا توجد منتجات. <a href="'.esc_url(self::page_url('vendor-add-product')).'">أضف أول منتج</a></td></tr>';
        wp_reset_postdata(); return '<div class="tv50-shell">'.$h.'</tbody></table></div></div></div>';
    }

    private static function system_summary() {
        $checks=[
            'القالب'=>wp_get_theme()->exists(),
            'صفحة السوق'=>(bool)get_page_by_path('shop'),
            'صفحة السلة'=>(bool)get_page_by_path('cart'),
            'تسجيل العميل'=>(bool)get_page_by_path('customer-register'),
            'تسجيل المورد'=>(bool)get_page_by_path('vendor-register'),
            'لوحة المورد'=>(bool)get_page_by_path('vendor-dashboard'),
            'بوابة الإدارة'=>(bool)get_page_by_path('admin-portal'),
        ];
        $h='<ul class="tv50-checklist">'; foreach($checks as $name=>$ok) $h.='<li class="'.($ok?'done':'todo').'"><span>'.($ok?'✓':'!').'</span><b>'.esc_html($name).'</b><em>'.($ok?'جاهز':'يحتاج إصلاح').'</em></li>'; return $h.'</ul>';
    }

    private static function login_required() {
        return '<div class="tv50-empty"><h2>يلزم تسجيل الدخول</h2><p>ادخل برقم الهاتف أو البريد إن كان موجودًا.</p><a class="tv50-primary" href="'.esc_url(self::page_url('login')).'">تسجيل الدخول</a></div>';
    }

    public static function protect_pages() {
        if(is_admin() || wp_doing_ajax()) return;
        $slug=get_post_field('post_name',get_queried_object_id()); if(!$slug) return;
        $customer=['customer-home','customer-activity']; $vendor=['vendor-home','vendor-onboarding','vendor-product-quality']; $admin=['admin-home'];
        if(in_array($slug,array_merge($customer,$vendor,$admin),true) && !is_user_logged_in()) { wp_safe_redirect(self::page_url('login')); exit; }
        if(in_array($slug,$vendor,true) && !self::is_vendor()) { wp_safe_redirect(self::page_url('my-account')); exit; }
        if(in_array($slug,$admin,true) && !self::is_admin_user()) { wp_safe_redirect(home_url('/')); exit; }
    }

    public static function inject_breadcrumbs($content) {
        if(is_admin() || !is_singular('page')) return $content;
        $slug=get_post_field('post_name',get_the_ID());
        if(!isset(self::pages()[$slug])) return $content;
        $crumb='<nav class="tv50-crumb"><a href="'.esc_url(home_url('/')).'">الرئيسية</a><span>›</span><b>'.esc_html(get_the_title()).'</b></nav>';
        return $crumb.$content;
    }

    private static function audit() {
        $rows=[];
        foreach(array_merge(self::pages(), [
            'login'=>['تسجيل الدخول',''], 'customer-register'=>['تسجيل العميل',''], 'vendor-register'=>['تسجيل المورد',''], 'shop'=>['السوق',''], 'cart'=>['السلة',''], 'vendor-add-product'=>['إضافة منتج',''], 'admin-portal'=>['بوابة الإدارة','']
        ]) as $slug=>$d){
            $p=get_page_by_path($slug); $rows[$slug]=['exists'=>(bool)$p,'content'=>$p?trim((string)$p->post_content)!=='':false,'url'=>$p?get_permalink($p):''];
        }
        return $rows;
    }

    public static function admin_menu() {
        add_menu_page('Tager V50','Tager V50','manage_options','tager-v50',[__CLASS__,'admin_page'],'dashicons-screenoptions',2.5);
    }

    public static function admin_page() {
        if(!current_user_can('manage_options')) return;
        $state=get_option(self::OPT,[]); $audit=$state['audit']??self::audit();
        echo '<div class="wrap"><h1>Tager V50 — اكتمال مساحات العمل</h1><p>فحص الصفحات الأساسية، مراكز العميل والمورد والإدارة، وجود المحتوى والروابط.</p>';
        if(!empty($_GET['v50_done'])) echo '<div class="notice notice-success"><p>تم تنفيذ العملية بنجاح.</p></div>';
        echo '<p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v50_repair'),self::NONCE)).'">إصلاح وإنشاء الصفحات</a> <a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v50_run_qa'),self::NONCE)).'">تشغيل الفحص</a></p>';
        echo '<table class="widefat striped"><thead><tr><th>الصفحة</th><th>موجودة</th><th>المحتوى</th><th>فتح</th></tr></thead><tbody>';
        foreach($audit as $slug=>$r) echo '<tr><td><code>'.esc_html($slug).'</code></td><td>'.($r['exists']?'✅':'❌').'</td><td>'.($r['content']?'✅':'❌').'</td><td>'.($r['url']?'<a target="_blank" href="'.esc_url($r['url']).'">فتح</a>':'—').'</td></tr>';
        echo '</tbody></table></div>';
    }

    public static function repair() {
        if(!current_user_can('manage_options')) wp_die('غير مصرح'); check_admin_referer(self::NONCE); self::repair_pages(); update_option(self::OPT,['time'=>current_time('mysql'),'audit'=>self::audit()],false); wp_safe_redirect(admin_url('admin.php?page=tager-v50&v50_done=1')); exit;
    }

    public static function run_qa() {
        if(!current_user_can('manage_options')) wp_die('غير مصرح'); check_admin_referer(self::NONCE); update_option(self::OPT,['time'=>current_time('mysql'),'audit'=>self::audit()],false); wp_safe_redirect(admin_url('admin.php?page=tager-v50&v50_done=1')); exit;
    }

    public static function assets() {
        if(is_admin()) return;
        echo '<style>
        .tv50-shell{max-width:1240px;margin:24px auto;padding:0 18px}.tv50-crumb{max-width:1240px;margin:16px auto 0;padding:0 18px;display:flex;gap:9px;align-items:center;font-size:13px}.tv50-crumb a{color:#176447;text-decoration:none}.tv50-hero{border-radius:26px;padding:30px;background:linear-gradient(135deg,#0d4f39,#176447);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:25px;box-shadow:0 20px 50px rgba(13,79,57,.16)}.tv50-hero.vendor{background:linear-gradient(135deg,#173f30,#b88722)}.tv50-hero.admin{background:linear-gradient(135deg,#162d25,#354d45)}.tv50-hero span{font-size:13px;opacity:.82}.tv50-hero h1{margin:5px 0 8px;font-size:32px}.tv50-hero p{margin:0;opacity:.88}.tv50-progress{min-width:170px;text-align:center;background:rgba(255,255,255,.1);padding:16px;border-radius:18px}.tv50-progress b{font-size:30px;display:block}.tv50-progress span{display:block}.tv50-progress i{display:block;height:8px;background:rgba(255,255,255,.2);border-radius:20px;margin-top:10px;overflow:hidden}.tv50-progress i:before{content:"";display:block;width:var(--p);height:100%;background:#f2c14e}.tv50-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:18px 0}.tv50-stat{background:#fff;border:1px solid #e7ece9;border-radius:18px;padding:18px;text-decoration:none;color:#173f30;box-shadow:0 8px 24px rgba(20,55,42,.06)}.tv50-stat b{display:block;font-size:25px}.tv50-stat span{color:#6d7c76;font-size:13px}.tv50-grid{display:grid;grid-template-columns:1.45fr .75fr;gap:18px}.tv50-card{background:#fff;border:1px solid #e6ece8;border-radius:22px;padding:22px;box-shadow:0 10px 30px rgba(17,62,46,.06)}.tv50-card h2{margin-top:0;color:#173f30}.tv50-actions{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.tv50-actions a{border:1px solid #e3ebe7;border-radius:16px;padding:15px;text-decoration:none;color:#173f30;display:grid;gap:4px;transition:.2s}.tv50-actions a:hover{transform:translateY(-2px);border-color:#b88722;box-shadow:0 10px 24px rgba(184,135,34,.12)}.tv50-actions span{font-size:12px;color:#6c7a75}.tv50-actions em{font-style:normal;color:#b88722;font-size:12px}.tv50-checklist{list-style:none;padding:0;margin:0;display:grid;gap:9px}.tv50-checklist li{display:grid;grid-template-columns:30px 1fr auto;align-items:center;gap:8px;padding:11px;border-radius:13px;background:#f6f9f7}.tv50-checklist li span{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-weight:800}.tv50-checklist .done span{background:#dff4e9;color:#176447}.tv50-checklist .todo span{background:#fff1d0;color:#8e650a}.tv50-checklist a{color:#176447}.tv50-primary{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:12px;background:#d5a431;color:#fff!important;text-decoration:none;font-weight:800}.tv50-alert,.tv50-empty{max-width:760px;margin:35px auto;padding:28px;border-radius:20px;background:#fff8e6;border:1px solid #f0d58c;text-align:center}.tv50-table-wrap{overflow:auto}.tv50-table{width:100%;border-collapse:collapse}.tv50-table th,.tv50-table td{padding:12px;border-bottom:1px solid #edf1ef;text-align:right}.tv50-timeline{display:grid;gap:12px}.tv50-timeline article{display:grid;grid-template-columns:16px 1fr auto;gap:12px;align-items:center;padding:13px;border:1px solid #e7ece9;border-radius:14px}.tv50-timeline i{width:12px;height:12px;border-radius:50%;background:#d5a431}.tv50-timeline span{display:block;color:#718079;font-size:12px}
        @media(max-width:860px){.tv50-hero{align-items:flex-start;flex-direction:column}.tv50-progress{width:100%;box-sizing:border-box}.tv50-stats{grid-template-columns:repeat(2,1fr)}.tv50-grid{grid-template-columns:1fr}.tv50-actions{grid-template-columns:1fr}.tv50-hero h1{font-size:25px}}
        </style><script>(function(){document.addEventListener("click",function(e){var a=e.target.closest("a,button");if(!a)return;if(a.matches("button[type=submit],input[type=submit]")){if(a.dataset.busy)return;a.dataset.busy="1";setTimeout(function(){delete a.dataset.busy},2500)}});})();</script>';
    }
}
Tager_V50_Workspace_Polish::init();
