<?php
/**
 * Plugin Name: Tager V38 Navigation & Complete Pages QA
 * Description: Canonical page registry, automatic linking, menu repair, missing-content fallback, and admin QA for Tager Marketplace.
 * Version: 38.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V38_Navigation_Page_Audit {
    const OPT = 'tager_v38_last_audit';
    const NONCE = 'tager_v38_repair';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes'], 99);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 99);
        add_action('admin_post_tager_v38_repair', [__CLASS__, 'repair_action']);
        add_action('admin_post_tager_v38_audit', [__CLASS__, 'audit_action']);
        add_filter('the_content', [__CLASS__, 'fallback_content'], 999);
        add_filter('wp_nav_menu_items', [__CLASS__, 'account_menu_items'], 30, 2);
        add_action('wp_footer', [__CLASS__, 'frontend_guard']);
        add_action('admin_notices', [__CLASS__, 'notice']);
        add_action('init', [__CLASS__, 'soft_bootstrap'], 120);
    }

    private static function pages() {
        return [
            'home'=>['الرئيسية','Home','[tager_home]'],
            'shop'=>['المنتجات','Products','[tager_shop]'],
            'categories'=>['الأقسام','Categories','[tager_v22_categories]'],
            'vendors'=>['دليل الموردين','Vendors','[tager_v22_vendors]'],
            'offers'=>['العروض','Offers','[tager_v22_offers]'],
            'brands'=>['العلامات التجارية','Brands','[tager_v22_brands]'],
            'cart'=>['السلة','Cart','[tager_cart]'],
            'checkout'=>['إتمام الطلب','Checkout','[tager_cart]'],
            'login'=>['تسجيل الدخول','Login','[tager_v18_login]'],
            'choose-account'=>['اختيار نوع الحساب','Choose account','[tager_v18_account_choice]'],
            'customer-register'=>['تسجيل العميل','Customer registration','[tager_customer_register]'],
            'vendor-register'=>['تسجيل المورد','Vendor registration','[tager_vendor_register]'],
            'lost-password'=>['استعادة كلمة المرور','Reset password','[tager_v18_lost_password]'],
            'my-account'=>['حساب العميل','Customer account','[tager_customer_account]'],
            'vendor-dashboard'=>['لوحة المورد','Vendor dashboard','[tager_vendor_dashboard]'],
            'vendor-products'=>['منتجات المورد','Vendor products','[tager_v35_vendor_products]'],
            'vendor-add-product'=>['إضافة منتج','Add product','[tager_v35_vendor_add_product]'],
            'vendor-orders'=>['طلبات المورد','Vendor orders','[tager_v35_vendor_orders]'],
            'vendor-earnings'=>['أرباح المورد','Vendor earnings','[tager_v35_vendor_earnings]'],
            'vendor-withdrawals'=>['سحب الأرباح','Withdrawals','[tager_v35_vendor_withdrawals]'],
            'vendor-coupons'=>['كوبونات المورد','Vendor coupons','[tager_v35_vendor_coupons]'],
            'vendor-analytics'=>['تحليلات المورد','Vendor analytics','[tager_v35_vendor_analytics]'],
            'vendor-settings'=>['إعدادات المتجر','Store settings','[tager_v35_vendor_settings]'],
            'customer-orders'=>['طلباتي','My orders','[tager_v35_customer_orders]'],
            'customer-addresses'=>['عناويني','My addresses','[tager_v35_customer_addresses]'],
            'customer-profile'=>['الملف الشخصي','Profile','[tager_v35_customer_profile]'],
            'wishlist'=>['المفضلة','Wishlist','[tager_pro_wishlist]'],
            'notifications'=>['الإشعارات','Notifications','[tager_notifications]'],
            'support'=>['الدعم','Support','[tager_support]'],
            'tracking'=>['تتبع الطلب','Track order','[tager_pro_tracking]'],
            'invoices'=>['الفواتير','Invoices','[tager_v34_invoices]'],
            'returns'=>['الاسترجاع','Returns','[tager_v34_returns]'],
            'rfq'=>['طلب عرض سعر','Request quotation','[tager_rfq]'],
            'compare'=>['مقارنة المنتجات','Compare products','[tager_compare]'],
            'about'=>['عن تاجر','About Tager','[tager_v22_about]'],
            'how-it-works'=>['كيف تعمل المنصة','How it works','[tager_v22_how_it_works]'],
            'buyer-guide'=>['دليل المشتري','Buyer guide','[tager_v22_buyer_guide]'],
            'vendor-guide'=>['دليل المورد','Vendor guide','[tager_v22_vendor_guide]'],
            'business-solutions'=>['حلول الشركات','Business solutions','[tager_v22_business_solutions]'],
            'pricing'=>['الأسعار والباقات','Pricing','[tager_v22_pricing]'],
            'faq'=>['الأسئلة الشائعة','FAQ','[tager_v22_faq]'],
            'contact-us'=>['تواصل معنا','Contact us','[tager_v22_contact_us]'],
            'help-center'=>['مركز المساعدة','Help center','[tager_v22_help_center]'],
            'payment-methods'=>['طرق الدفع','Payment methods','[tager_v34_payment_methods]'],
            'shipping'=>['الشحن والتوصيل','Shipping','[tager_v22_shipping]'],
            'delivery-areas'=>['مناطق التوصيل','Delivery areas','[tager_v34_delivery_areas]'],
            'vendor-minimum-orders'=>['حدود طلبات الموردين','Vendor minimum orders','[tager_v34_vendor_minimum_orders]'],
            'return-policy'=>['سياسة الاسترجاع','Return policy','[tager_v22_return_policy]'],
            'privacy-policy'=>['سياسة الخصوصية','Privacy policy','[tager_v22_privacy_policy]'],
            'terms'=>['الشروط والأحكام','Terms','[tager_v22_terms]'],
            'complaints'=>['الشكاوى والمقترحات','Complaints','[tager_v35_complaints]'],
            'sitemap'=>['خريطة الموقع','Sitemap','[tager_v35_sitemap]'],
            'accessibility'=>['إمكانية الوصول','Accessibility','[tager_v35_accessibility]'],
        ];
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v38_link', [__CLASS__, 'link_shortcode']);
        add_shortcode('tager_v38_page_hub', [__CLASS__, 'page_hub']);
    }

    public static function link_shortcode($atts) {
        $a = shortcode_atts(['page'=>'home','label'=>'','class'=>'btn primary'], $atts);
        $page = get_page_by_path(sanitize_title($a['page']));
        $label = $a['label'] ?: (isset(self::pages()[$a['page']]) ? self::pages()[$a['page']][0] : $a['page']);
        $url = $page ? get_permalink($page) : home_url('/');
        return '<a class="'.esc_attr($a['class']).'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }

    public static function soft_bootstrap() {
        if (get_option('tager_v38_bootstrapped')) return;
        self::repair(false);
        update_option('tager_v38_bootstrapped', current_time('mysql'), false);
    }

    private static function usable_shortcode($content) {
        if (!preg_match_all('/\[([a-zA-Z0-9_-]+)/', (string)$content, $m)) return true;
        global $shortcode_tags;
        foreach ($m[1] as $tag) if (!isset($shortcode_tags[$tag])) return false;
        return true;
    }

    private static function generic_content($title, $en, $slug) {
        $hub = '[tager_v38_page_hub section="'.esc_attr($slug).'"]';
        return '<section class="t38-complete-page"><div class="t38-hero"><span class="t38-kicker">Tager Marketplace</span><h1>'.esc_html($title).'</h1><p>'.esc_html($en).'</p></div>'.$hub.'</section>';
    }

    public static function repair($force = true) {
        $result = ['created'=>0,'updated'=>0,'kept'=>0,'menus'=>0];
        $ids = (array)get_option('tager_pages', []);
        foreach (self::pages() as $slug=>$def) {
            $p = get_page_by_path($slug, OBJECT, 'page');
            $preferred = $def[2];
            $content = shortcode_exists(trim($preferred, '[]')) ? $preferred : self::generic_content($def[0], $def[1], $slug);
            if (!$p) {
                $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$def[0],'post_content'=>$content]);
                if ($id && !is_wp_error($id)) { $ids[$slug]=$id; $result['created']++; }
            } else {
                $ids[$slug]=$p->ID;
                $empty = trim(wp_strip_all_tags(strip_shortcodes($p->post_content)))==='' && trim($p->post_content)==='';
                $broken = !self::usable_shortcode($p->post_content);
                if (($empty || $broken || ($force && get_post_meta($p->ID,'_tager_v38_managed',true))) && $p->post_content !== $content) {
                    wp_update_post(['ID'=>$p->ID,'post_title'=>$def[0],'post_content'=>$content,'post_status'=>'publish']);
                    update_post_meta($p->ID,'_tager_v38_managed',1); $result['updated']++;
                } else $result['kept']++;
            }
        }
        update_option('tager_pages',$ids,false);
        if (!empty($ids['home'])) { update_option('show_on_front','page'); update_option('page_on_front',(int)$ids['home']); }
        $result['menus'] = self::repair_menus($ids);
        flush_rewrite_rules(false);
        update_option(self::OPT, ['time'=>current_time('mysql'),'result'=>$result,'audit'=>self::audit()], false);
        return $result;
    }

    private static function repair_menus($ids) {
        $count=0;
        $menus = [
            'Tager Main Menu'=>['home','shop','categories','vendors','offers','brands','about','contact-us'],
            'Tager Account Menu'=>['login','choose-account','customer-register','vendor-register','my-account','vendor-dashboard'],
            'Tager Footer Menu'=>['help-center','faq','payment-methods','shipping','return-policy','privacy-policy','terms','complaints'],
        ];
        foreach($menus as $name=>$slugs){
            $menu=wp_get_nav_menu_object($name); $menu_id=$menu?$menu->term_id:wp_create_nav_menu($name);
            if(is_wp_error($menu_id)) continue;
            $existing=wp_get_nav_menu_items($menu_id)?:[]; $linked=[];
            foreach($existing as $item) $linked[(int)$item->object_id]=true;
            foreach($slugs as $slug){ if(empty($ids[$slug])||isset($linked[(int)$ids[$slug]]))continue;
                wp_update_nav_menu_item($menu_id,0,['menu-item-title'=>self::pages()[$slug][0],'menu-item-object'=>'page','menu-item-object-id'=>(int)$ids[$slug],'menu-item-type'=>'post_type','menu-item-status'=>'publish']);$count++;
            }
        }
        $loc=get_theme_mod('nav_menu_locations',[]); $main=wp_get_nav_menu_object('Tager Main Menu');
        if($main){$registered=get_registered_nav_menus();$keys=array_keys($registered);if($keys){$loc[$keys[0]]=$main->term_id;set_theme_mod('nav_menu_locations',$loc);}}
        return $count;
    }

    public static function page_hub($atts) {
        $a=shortcode_atts(['section'=>''], $atts); $p=self::pages();
        $groups=[
            'التسوق'=>['shop','categories','vendors','offers','brands','cart','tracking'],
            'الحساب'=>is_user_logged_in()?['my-account','customer-orders','customer-addresses','wishlist','notifications','support']:['login','choose-account','customer-register','vendor-register','lost-password'],
            'المساعدة والسياسات'=>['help-center','faq','payment-methods','shipping','delivery-areas','return-policy','privacy-policy','terms','contact-us'],
        ];
        ob_start(); ?><div class="t38-hub"><?php foreach($groups as $title=>$slugs):?><section><h2><?php echo esc_html($title);?></h2><div class="t38-link-grid"><?php foreach($slugs as $slug):$pg=get_page_by_path($slug);if(!$pg)continue;?><a href="<?php echo esc_url(get_permalink($pg));?>"><strong><?php echo esc_html($p[$slug][0]??$pg->post_title);?></strong><span>فتح الصفحة ←</span></a><?php endforeach;?></div></section><?php endforeach;?></div><?php return ob_get_clean();
    }

    public static function fallback_content($content) {
        if (!is_singular('page') || is_admin()) return $content;
        $slug=get_post_field('post_name',get_the_ID());
        if (!isset(self::pages()[$slug])) return $content;
        $visible=trim(wp_strip_all_tags(strip_shortcodes($content)));
        if ($visible==='' && !has_shortcode($content, trim(self::pages()[$slug][2],'[]'))) return self::generic_content(self::pages()[$slug][0],self::pages()[$slug][1],$slug);
        return $content;
    }

    public static function account_menu_items($items, $args) {
        if (is_admin()) return $items;
        $locations=(array)get_nav_menu_locations();
        if(!empty($args->theme_location) && !isset($locations[$args->theme_location])) return $items;
        $slug=is_user_logged_in()?(current_user_can('manage_options')?'wp-admin':(in_array('tager_vendor',(array)wp_get_current_user()->roles,true)?'vendor-dashboard':'my-account')):'login';
        $url=$slug==='wp-admin'?admin_url():($p=get_page_by_path($slug)?get_permalink($p):home_url('/'.$slug.'/'));
        $label=is_user_logged_in()?'حسابي':'تسجيل الدخول';
        return $items.'<li class="menu-item tager-account-link"><a href="'.esc_url($url).'">'.esc_html($label).'</a></li>';
    }

    public static function audit() {
        $rows=[];$dead=0;$missing=0;$empty=0;$broken=0;
        foreach(self::pages() as $slug=>$def){
            $p=get_page_by_path($slug);
            $state='ok';$notes=[];
            if(!$p){$state='missing';$missing++;$notes[]='الصفحة غير موجودة';}
            else {
                if(trim($p->post_content)===''){$state='empty';$empty++;$notes[]='المحتوى فارغ';}
                if(!self::usable_shortcode($p->post_content)){$state='broken';$broken++;$notes[]='Shortcode غير مسجل';}
                if(preg_match('/href\s*=\s*["\'](?:#|javascript:|)["\']/i',$p->post_content)){$dead++;$notes[]='رابط زر فارغ';if($state==='ok')$state='warning';}
            }
            $rows[]=['slug'=>$slug,'title'=>$def[0],'id'=>$p?$p->ID:0,'state'=>$state,'notes'=>implode('، ',$notes)];
        }
        return ['summary'=>['total'=>count($rows),'missing'=>$missing,'empty'=>$empty,'broken'=>$broken,'dead_links'=>$dead],'rows'=>$rows];
    }

    public static function admin_menu() { add_menu_page('Tager Page QA','Tager Page QA','manage_options','tager-v38-page-qa',[__CLASS__,'admin_page'],'dashicons-admin-links',3); }
    public static function admin_page() {
        if(!current_user_can('manage_options'))return;$data=get_option(self::OPT);$audit=$data['audit']??self::audit();$s=$audit['summary'];
        echo '<div class="wrap"><h1>Tager V38 — ربط الصفحات وفحص المحتوى</h1><p>يفحص الصفحات والـ shortcodes والروابط الفارغة، وينشئ القوائم ويربط الصفحات الأساسية تلقائيًا.</p>';
        echo '<div style="display:flex;gap:12px;flex-wrap:wrap">';foreach(['إجمالي الصفحات'=>$s['total'],'صفحات ناقصة'=>$s['missing'],'محتوى فارغ'=>$s['empty'],'Shortcodes مكسورة'=>$s['broken'],'روابط فارغة'=>$s['dead_links']] as $k=>$v)echo '<div style="background:#fff;padding:16px 22px;border-radius:10px;border:1px solid #ddd"><strong>'.$v.'</strong><br>'.esc_html($k).'</div>';echo '</div><p>';
        echo '<a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v38_repair'),self::NONCE)).'">إصلاح وربط كل الصفحات</a> ';
        echo '<a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v38_audit'),self::NONCE)).'">تشغيل الفحص فقط</a></p>';
        echo '<table class="widefat striped"><thead><tr><th>الصفحة</th><th>الرابط</th><th>الحالة</th><th>الملاحظات</th><th>فتح</th></tr></thead><tbody>';
        foreach($audit['rows'] as $r){$url=$r['id']?get_permalink($r['id']):'';echo '<tr><td>'.esc_html($r['title']).'</td><td>/'.esc_html($r['slug']).'/</td><td>'.esc_html($r['state']).'</td><td>'.esc_html($r['notes']).'</td><td>'.($url?'<a target="_blank" href="'.esc_url($url).'">فتح</a>':'—').'</td></tr>';}
        echo '</tbody></table></div>';
    }
    public static function repair_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);self::repair(true);wp_safe_redirect(admin_url('admin.php?page=tager-v38-page-qa&tager_v38=done'));exit;}
    public static function audit_action(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);update_option(self::OPT,['time'=>current_time('mysql'),'audit'=>self::audit()],false);wp_safe_redirect(admin_url('admin.php?page=tager-v38-page-qa&tager_v38=audit'));exit;}
    public static function notice(){if(empty($_GET['tager_v38']))return;echo '<div class="notice notice-success is-dismissible"><p>تم فحص وربط صفحات Tager بنجاح.</p></div>';}

    public static function frontend_guard(){ if(is_admin())return; ?>
<style>
.t38-complete-page{max-width:1180px;margin:30px auto;padding:0 18px}.t38-hero{padding:48px;border-radius:24px;background:linear-gradient(135deg,#123f34,#1e6a57);color:#fff}.t38-kicker{color:#d6b35a;font-weight:800}.t38-hub{max-width:1180px;margin:28px auto}.t38-hub section{margin:28px 0}.t38-link-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.t38-link-grid a{display:flex;justify-content:space-between;gap:12px;padding:18px;background:#fff;border:1px solid #e7e7e7;border-radius:16px;text-decoration:none;color:#153f35;box-shadow:0 6px 20px rgba(0,0,0,.05)}.t38-link-grid a:hover{transform:translateY(-2px);border-color:#d6b35a}.t38-link-grid span{font-size:12px;color:#777}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('a[href="#"],a[href=""],button[data-href=""]').forEach(function(el){
   el.addEventListener('click',function(e){e.preventDefault();var box=document.createElement('div');box.textContent='هذا الرابط قيد الإعداد — استخدم القائمة الرئيسية أو مركز المساعدة.';box.style.cssText='position:fixed;bottom:24px;left:24px;z-index:99999;background:#173f35;color:#fff;padding:14px 18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.25)';document.body.appendChild(box);setTimeout(function(){box.remove()},3500);});
 });
});
</script><?php }
}
Tager_V38_Navigation_Page_Audit::init();
