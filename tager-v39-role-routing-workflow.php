<?php
/**
 * Plugin Name: Tager V39 Role Routing & Workflow QA
 * Description: Role-based login destinations, complete vendor/customer/admin workspaces, vendor market preview, product workflow and automated QA.
 * Version: 39.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V39_Role_Routing_Workflow {
    const NONCE = 'tager_v39_nonce';
    public static function boot(){
        add_action('init',[__CLASS__,'ensure_pages'],120);
        add_filter('login_redirect',[__CLASS__,'login_redirect'],999,3);
        add_action('template_redirect',[__CLASS__,'guard_pages'],1);
        add_action('admin_init',[__CLASS__,'guard_admin'],1);
        add_action('admin_menu',[__CLASS__,'admin_menu'],40);
        add_action('admin_post_tager_v39_repair',[__CLASS__,'repair']);
        add_action('admin_post_tager_v39_run_qa',[__CLASS__,'run_qa']);
        add_shortcode('tager_v39_role_gateway',[__CLASS__,'role_gateway']);
        add_shortcode('tager_v39_vendor_market',[__CLASS__,'vendor_market']);
        add_shortcode('tager_v39_admin_portal',[__CLASS__,'admin_portal']);
        add_filter('the_content',[__CLASS__,'append_role_shortcuts'],40);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
    }
    private static function page_url($slug){$p=get_page_by_path($slug);return $p?get_permalink($p):home_url('/'.trim($slug,'/').'/');}
    private static function roles($u=null){$u=$u?:wp_get_current_user();return (array)$u->roles;}
    private static function is_vendor($u=null){return (bool)array_intersect(self::roles($u),['tager_vendor','tager_vendor_pending','vendor','wcfm_vendor','seller']);}
    private static function is_customer($u=null){return (bool)array_intersect(self::roles($u),['tager_customer','customer','subscriber']);}
    private static function is_admin_team($u=null){$u=$u?:wp_get_current_user();return user_can($u,'manage_options') || (bool)array_intersect(self::roles($u),['tager_platform_manager','tager_operations_manager','tager_vendor_manager','tager_catalog_manager','tager_order_manager','tager_finance_manager','tager_support_agent','tager_marketing_manager','tager_readonly_auditor']);}
    private static function destination($u){
        if(!$u || !$u->ID) return self::page_url('login');
        if(self::is_admin_team($u)) return admin_url('admin.php?page=tager-v39-command');
        if(self::is_vendor($u)) return self::page_url('vendor-dashboard');
        return self::page_url('my-account');
    }
    public static function login_redirect($redirect,$requested,$user){return is_wp_error($user)?$redirect:self::destination($user);}
    public static function guard_pages(){
        if(is_admin()||wp_doing_ajax()||!is_singular('page'))return;
        global $post; if(!$post)return; $slug=$post->post_name;
        $vendor=['vendor-dashboard','vendor-products','vendor-add-product','vendor-orders','vendor-earnings','vendor-store-settings','vendor-market'];
        $customer=['my-account','my-orders','my-addresses','wishlist','customer-support'];
        $admin=['admin-portal'];
        if(in_array($slug,$vendor,true) && (!is_user_logged_in()||!self::is_vendor())){wp_safe_redirect(is_user_logged_in()?self::destination(wp_get_current_user()):add_query_arg('redirect_to',rawurlencode(self::page_url($slug)),self::page_url('login')));exit;}
        if(in_array($slug,$customer,true) && (!is_user_logged_in()||self::is_vendor()||self::is_admin_team())){wp_safe_redirect(is_user_logged_in()?self::destination(wp_get_current_user()):add_query_arg('redirect_to',rawurlencode(self::page_url($slug)),self::page_url('login')));exit;}
        if(in_array($slug,$admin,true) && (!is_user_logged_in()||!self::is_admin_team())){wp_safe_redirect(is_user_logged_in()?self::destination(wp_get_current_user()):self::page_url('login'));exit;}
    }
    public static function guard_admin(){
        if(!is_user_logged_in()||wp_doing_ajax())return; $u=wp_get_current_user();
        if(self::is_vendor($u) && !current_user_can('manage_options')){
            $allowed=['admin-post.php','async-upload.php','media-upload.php','profile.php'];$file=basename($_SERVER['PHP_SELF']??'');
            if(!in_array($file,$allowed,true)){wp_safe_redirect(self::page_url('vendor-dashboard'));exit;}
        }
        if(self::is_customer($u) && !current_user_can('manage_options')){wp_safe_redirect(self::page_url('my-account'));exit;}
    }
    private static function pages(){return [
        'role-gateway'=>['بوابة الحساب','Account gateway','[tager_v39_role_gateway]'],
        'vendor-market'=>['السوق للمورد','Vendor market','[tager_v39_vendor_market]'],
        'admin-portal'=>['بوابة الإدارة','Admin portal','[tager_v39_admin_portal]'],
        'vendor-products'=>['منتجات المورد','Vendor products','[tager_vendor_dashboard]'],
        'vendor-add-product'=>['إضافة منتج','Add product','[tager_vendor_dashboard]'],
        'vendor-orders'=>['طلبات المورد','Vendor orders','[tager_vendor_dashboard]'],
        'vendor-earnings'=>['أرباح المورد','Vendor earnings','[tager_vendor_dashboard]'],
        'vendor-store-settings'=>['إعدادات متجر المورد','Vendor store settings','[tager_vendor_dashboard]'],
        'my-orders'=>['طلباتي','My orders','[tager_customer_account]'],
        'my-addresses'=>['عناويني','My addresses','[tager_customer_account]'],
        'customer-support'=>['دعم العميل','Customer support','[tager_customer_account]'],
    ];}
    public static function ensure_pages(){if(get_option('tager_v39_pages_done'))return;self::repair_pages();update_option('tager_v39_pages_done',1);}
    private static function repair_pages(){foreach(self::pages() as $slug=>$d){$p=get_page_by_path($slug);$content=$d[2];if(!$p){wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$content]);}elseif(trim($p->post_content)===''){wp_update_post(['ID'=>$p->ID,'post_content'=>$content]);}}flush_rewrite_rules(false);}
    public static function role_gateway(){
        if(!is_user_logged_in())return '<section class="t39-card"><h1>تسجيل الدخول</h1><p>سجّل برقم الهاتف أو البريد الإلكتروني.</p><a class="t39-btn" href="'.esc_url(self::page_url('login')).'">دخول</a></section>';
        $u=wp_get_current_user();$dest=self::destination($u);$type=self::is_admin_team($u)?'الإدارة':(self::is_vendor($u)?'المورد':'العميل');
        return '<section class="t39-card"><span class="t39-kicker">'.$type.'</span><h1>مرحبًا '.esc_html($u->display_name).'</h1><p>تم توجيه حسابك إلى المساحة الصحيحة حسب الصلاحية.</p><a class="t39-btn" href="'.esc_url($dest).'">فتح لوحة الحساب</a></section>';
    }
    public static function vendor_market(){
        if(!is_user_logged_in()||!self::is_vendor())return '<div class="t39-alert">هذه الصفحة للموردين فقط.</div>';
        $u=wp_get_current_user();$products=get_posts(['post_type'=>'tager_product','post_status'=>'publish','posts_per_page'=>24,'orderby'=>'date','order'=>'DESC']);$cards='';
        foreach($products as $p){$vendor=(int)get_post_meta($p->ID,'vendor_id',true);$store=$vendor?get_user_meta($vendor,'store_name',true):'';$price=get_post_meta($p->ID,'retail_price',true);$img=get_the_post_thumbnail_url($p->ID,'medium');$cards.='<article class="t39-product">'.($img?'<img src="'.esc_url($img).'" alt="">':'<div class="t39-ph">T</div>').'<div><small>'.esc_html($store?:'Tager').'</small><h3>'.esc_html($p->post_title).'</h3><b>'.number_format((float)$price,2).' ج.م</b></div></article>';}
        $mine=count(get_posts(['post_type'=>'tager_product','author'=>$u->ID,'post_status'=>['publish','pending','draft'],'posts_per_page'=>-1,'fields'=>'ids']));
        return '<section class="t39-hero"><div><span class="t39-kicker">سوق تاجر للمورد</span><h1>تابع السوق وأدر منتجاتك من مكان واحد</h1><p>شاهد المنتجات المنشورة والأسعار، ثم انتقل مباشرة لإضافة منتج أو إدارة متجرك.</p><div class="t39-actions"><a class="t39-btn" href="'.esc_url(add_query_arg('tab','add-product',self::page_url('vendor-dashboard'))).'">إضافة منتج</a><a class="t39-btn ghost" href="'.esc_url(add_query_arg('tab','products',self::page_url('vendor-dashboard'))).'">منتجاتي ('.$mine.')</a></div></div></section><div class="t39-grid">'.($cards?:'<div class="t39-empty">لا توجد منتجات منشورة بعد.</div>').'</div>';
    }
    public static function admin_portal(){if(!is_user_logged_in()||!self::is_admin_team())return '<div class="t39-alert">غير مصرح.</div>';return self::admin_dashboard_html(false);}
    public static function append_role_shortcuts($content){if(!is_singular('page')||!is_user_logged_in())return $content;global $post;if(!$post)return $content;if($post->post_name==='vendor-dashboard'&&self::is_vendor())$content='<div class="t39-toplinks"><a href="'.esc_url(self::page_url('vendor-market')).'">عرض السوق</a><a href="'.esc_url(home_url('/shop/')).'">المتجر العام</a></div>'.$content;return $content;}
    public static function admin_menu(){add_menu_page('Tager V39','Tager V39','read','tager-v39-command',[__CLASS__,'admin_screen'],'dashicons-store',2);add_submenu_page('tager-v39-command','اختبار النظام','اختبار النظام','manage_options','tager-v39-qa',[__CLASS__,'qa_screen']);}
    private static function counts(){return [
        'vendors_pending'=>count(get_users(['role'=>'tager_vendor_pending'])),
        'vendors'=>count(get_users(['role'=>'tager_vendor'])),
        'customers'=>count(get_users(['role'=>'tager_customer'])),
        'products_pending'=>(int)(wp_count_posts('tager_product')->pending??0),
        'products_live'=>(int)(wp_count_posts('tager_product')->publish??0),
        'orders'=>(int)(wp_count_posts('tager_order')->publish??0),
    ];}
    private static function admin_dashboard_html($wrap=true){$c=self::counts();ob_start();if($wrap)echo '<div class="wrap">';?><div class="t39-admin"><div class="t39-adminhero"><div><span>V39 Command Center</span><h1>إدارة السوق والموردين والمنتجات</h1><p>الموافقات والمتابعة والاختبارات في لوحة واحدة.</p></div><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=tager-v39-qa')); ?>">تشغيل اختبار النظام</a></div><div class="t39-stats"><?php foreach($c as $k=>$v):?><article><b><?php echo (int)$v;?></b><span><?php echo esc_html(str_replace(['vendors_pending','vendors','customers','products_pending','products_live','orders'],['موردون للمراجعة','موردون معتمدون','عملاء','منتجات للمراجعة','منتجات منشورة','طلبات'],$k));?></span></article><?php endforeach;?></div><div class="t39-links"><a href="<?php echo esc_url(admin_url('admin.php?page=tager-v36-vendors'));?>">موافقات الموردين</a><a href="<?php echo esc_url(admin_url('admin.php?page=tager-v36-products'));?>">مراجعة المنتجات</a><a href="<?php echo esc_url(admin_url('edit.php?post_type=tager_order'));?>">الطلبات</a><a href="<?php echo esc_url(admin_url('admin.php?page=tager-v37-commercial'));?>">العمولات</a><a href="<?php echo esc_url(admin_url('admin.php?page=tager-v38-page-qa'));?>">فحص الصفحات</a></div></div><?php if($wrap)echo '</div>';return ob_get_clean();}
    public static function admin_screen(){echo self::admin_dashboard_html(true);}
    public static function qa_screen(){if(!current_user_can('manage_options'))wp_die('No permission');$r=get_option('tager_v39_qa_results',[]);echo '<div class="wrap"><h1>V39 — اختبار رحلة النظام</h1><p>يفحص الصفحات والأدوار وحفظ المنتج ومسارات الدخول بدون ترك بيانات تجريبية.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v39_run_qa">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button class="button button-primary">تشغيل الاختبار الآن</button></form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="margin-top:10px"><input type="hidden" name="action" value="tager_v39_repair">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button class="button">إصلاح الصفحات والروابط</button></form><hr><table class="widefat striped"><thead><tr><th>الاختبار</th><th>النتيجة</th><th>التفاصيل</th></tr></thead><tbody>';if(!$r)echo '<tr><td colspan="3">لم يُشغّل الاختبار بعد.</td></tr>';foreach($r as $x)echo '<tr><td>'.esc_html($x['name']).'</td><td>'.($x['ok']?'✅ ناجح':'❌ فشل').'</td><td>'.esc_html($x['detail']).'</td></tr>';echo '</tbody></table></div>';}
    public static function repair(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer(self::NONCE);self::repair_pages();delete_option('tager_v39_pages_done');wp_safe_redirect(admin_url('admin.php?page=tager-v39-qa&repaired=1'));exit;}
    public static function run_qa(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer(self::NONCE);$r=[];$add=function($n,$ok,$d)use(&$r){$r[]=['name'=>$n,'ok'=>(bool)$ok,'detail'=>$d];};
        foreach(['login','my-account','vendor-dashboard','vendor-market','admin-portal'] as $s){$p=get_page_by_path($s);$add('صفحة '.$s,(bool)$p,$p?'موجودة ومنشورة':'مفقودة');}
        foreach(['tager_customer','tager_vendor','tager_vendor_pending'] as $role)$add('دور '.$role,(bool)get_role($role),get_role($role)?'موجود':'مفقود');
        $uid=wp_insert_user(['user_login'=>'tagerqa_'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(14),'user_email'=>'qa'.time().'@example.test','role'=>'tager_vendor','display_name'=>'QA Vendor']);
        if(is_wp_error($uid)){$add('إنشاء مورد اختباري',false,$uid->get_error_message());}else{update_user_meta($uid,'vendor_status','approved');$pid=wp_insert_post(['post_type'=>'tager_product','post_status'=>'pending','post_title'=>'QA Product','post_author'=>$uid],true);if(is_wp_error($pid)){$add('حفظ منتج المورد',false,$pid->get_error_message());}else{update_post_meta($pid,'retail_price',100);update_post_meta($pid,'wholesale_price',90);update_post_meta($pid,'bulk_price',80);update_post_meta($pid,'stock',20);$ok=(float)get_post_meta($pid,'retail_price',true)===100.0 && (int)get_post_meta($pid,'stock',true)===20;$add('حفظ وقراءة المنتج',$ok,$ok?'الأسعار والمخزون محفوظة':'تعذر قراءة البيانات');wp_delete_post($pid,true);}wp_delete_user($uid);}
        $add('مسار الإدارة',str_contains(self::destination(wp_get_current_user()),'tager-v39-command'),'يتم توجيه الأدمن إلى مركز القيادة');update_option('tager_v39_qa_results',$r,false);wp_safe_redirect(admin_url('admin.php?page=tager-v39-qa&tested=1'));exit;}
    public static function assets(){wp_register_style('tager-v39',false);wp_enqueue_style('tager-v39');wp_add_inline_style('tager-v39','.t39-card,.t39-hero{background:linear-gradient(135deg,#0b4d3b,#126b51);color:#fff;border-radius:24px;padding:34px;margin:24px 0}.t39-kicker{color:#f4c95d;font-weight:800}.t39-btn{display:inline-flex;padding:12px 20px;border-radius:12px;background:#d7a928;color:#10251f!important;text-decoration:none;font-weight:800}.t39-btn.ghost{background:#fff}.t39-actions{display:flex;gap:12px;flex-wrap:wrap}.t39-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px}.t39-product{background:#fff;border:1px solid #e6ece9;border-radius:18px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,.06)}.t39-product img,.t39-ph{width:100%;height:170px;object-fit:cover;background:#eef5f1;display:grid;place-items:center;font-size:48px;color:#0b4d3b}.t39-product>div{padding:16px}.t39-toplinks{display:flex;gap:10px;margin:0 0 16px}.t39-toplinks a{background:#0b4d3b;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none}.t39-alert,.t39-empty{padding:20px;border-radius:14px;background:#fff4d6}.t39-adminhero{display:flex;justify-content:space-between;align-items:center;background:#0b4d3b;color:#fff;padding:26px;border-radius:18px;margin:20px 0}.t39-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}.t39-stats article{background:#fff;padding:20px;border-radius:14px;border:1px solid #ddd}.t39-stats b{font-size:28px;display:block}.t39-links{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.t39-links a{background:#d7a928;color:#172b24;padding:11px 15px;border-radius:10px;text-decoration:none;font-weight:700}@media(max-width:700px){.t39-adminhero{display:block}.t39-adminhero a{margin-top:12px}.t39-card,.t39-hero{padding:24px}}');}
}
Tager_V39_Role_Routing_Workflow::boot();
