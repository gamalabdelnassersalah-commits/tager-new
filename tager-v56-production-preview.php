<?php
/**
 * Plugin Name: Tager V56 Production Preview & UX Cleanup
 * Description: Production-style homepage, clean role navigation, duplicate UI cleanup, page/action audit and preview tools.
 * Version: 56.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V56_Production_Preview {
    const VERSION = '56.0.0';
    const OPT = 'tager_v56_ready';
    const NONCE = 'tager_v56_nonce';

    public static function init() {
        add_action('init', [__CLASS__, 'cleanup_legacy_frontend'], 10000);
        add_action('init', [__CLASS__, 'register_shortcodes'], 10001);
        add_action('init', [__CLASS__, 'ensure_home'], 10002);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 200);
        add_action('wp_footer', [__CLASS__, 'footer_script'], 200);
        add_filter('body_class', [__CLASS__, 'body_classes']);
        add_filter('show_admin_bar', [__CLASS__, 'preview_admin_bar']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 120);
        add_action('admin_post_tager_v56_repair', [__CLASS__, 'repair_action']);
        add_action('admin_post_tager_v56_smoke_test', [__CLASS__, 'smoke_test_action']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 200, 3);
    }

    public static function cleanup_legacy_frontend() {
        $removals = [
            ['filter','the_content','Tager_V54_Human_Page_Audit','human_context_bar',120],
            ['action','wp_footer','Tager_V54_Human_Page_Audit','frontend_qa',10],
            ['filter','the_content','Tager_V53_Complete_Data_Pages','append_context_navigation',99],
            ['filter','the_content','Tager_V39_Role_Routing_Workflow','append_role_shortcuts',40],
        ];
        foreach ($removals as $r) {
            [$type,$hook,$class,$method,$priority] = $r;
            if (class_exists($class)) {
                if ($type === 'filter') remove_filter($hook, [$class,$method], $priority);
                else remove_action($hook, [$class,$method], $priority);
            }
        }
    }

    public static function register_shortcodes() {
        add_shortcode('tager_v56_home', [__CLASS__, 'home']);
        add_shortcode('tager_v56_account_entry', [__CLASS__, 'account_entry']);
    }

    private static function page_url($slug) {
        $p = get_page_by_path($slug);
        return $p ? get_permalink($p) : home_url('/'.trim($slug,'/').'/');
    }

    private static function is_vendor($u = null) {
        $u = $u ?: wp_get_current_user();
        return (bool) array_intersect((array)$u->roles, ['tager_vendor','wcfm_vendor','vendor','tager_vendor_pending']);
    }

    private static function is_admin_team($u = null) {
        $u = $u ?: wp_get_current_user();
        return user_can($u, 'manage_options') || (bool) array_intersect((array)$u->roles, [
            'administrator','tager_platform_manager','tager_operations_manager','tager_vendor_manager',
            'tager_catalog_manager','tager_order_manager','tager_finance_manager','tager_support_agent',
            'tager_marketing_manager','tager_readonly_auditor','tager_admin','tager_ops_manager'
        ]);
    }

    public static function role_home($u = null) {
        $u = $u ?: wp_get_current_user();
        if (!($u instanceof WP_User) || !$u->exists()) return self::page_url('login');
        if (self::is_admin_team($u)) return self::page_url('admin-home');
        if (self::is_vendor($u)) return self::page_url('vendor-home');
        return self::page_url('customer-home');
    }

    public static function login_redirect($redirect, $requested, $user) {
        if (is_wp_error($user) || !($user instanceof WP_User)) return $redirect;
        return self::role_home($user);
    }

    public static function ensure_home() {
        if (get_option(self::OPT)) return;
        $home = get_page_by_path('home');
        if (!$home) {
            $id = wp_insert_post([
                'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'الرئيسية',
                'post_name' => 'home', 'post_content' => '[tager_v56_home]'
            ]);
        } else {
            $id = $home->ID;
            wp_update_post(['ID'=>$id, 'post_content'=>'[tager_v56_home]']);
        }
        if ($id && !is_wp_error($id)) {
            update_option('show_on_front','page');
            update_option('page_on_front',(int)$id);
        }
        update_option(self::OPT, 1, false);
        flush_rewrite_rules(false);
    }

    private static function count_posts_safe($type, $status='publish') {
        if (!post_type_exists($type)) return 0;
        $c = wp_count_posts($type);
        return isset($c->$status) ? (int)$c->$status : 0;
    }

    private static function product_price($id, $tier='retail') {
        $keys = $tier === 'bulk' ? ['bulk_price','price_bulk'] : ($tier === 'wholesale' ? ['wholesale_price','price_wholesale'] : ['retail_price','price_retail','price']);
        foreach ($keys as $k) {
            $v = get_post_meta($id,$k,true);
            if ($v !== '' && is_numeric($v)) return (float)$v;
        }
        return 0;
    }

    private static function product_card($p) {
        $ret = self::product_price($p->ID,'retail');
        $wh  = self::product_price($p->ID,'wholesale');
        $bulk= self::product_price($p->ID,'bulk');
        $stock = (int)get_post_meta($p->ID,'stock',true);
        $vendor = get_userdata($p->post_author);
        $vendor_name = get_user_meta($p->post_author,'tager_store_name',true) ?: get_user_meta($p->post_author,'store_name',true) ?: ($vendor ? $vendor->display_name : 'مورد تاجر');
        $img = get_the_post_thumbnail_url($p->ID,'medium_large');
        $url = self::page_url('product-details');
        $url = add_query_arg('product_id',$p->ID,$url);
        ob_start(); ?>
        <article class="tv56-product">
          <a class="tv56-product-media" href="<?php echo esc_url($url); ?>">
            <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->post_title); ?>" loading="lazy"><?php else: ?><span>📦</span><?php endif; ?>
            <em><?php echo $stock > 0 ? 'متاح' : 'غير متاح'; ?></em>
          </a>
          <div class="tv56-product-body">
            <small><?php echo esc_html($vendor_name); ?></small>
            <h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($p->post_title); ?></a></h3>
            <div class="tv56-tier"><span>قطاعي</span><b><?php echo $ret ? number_format_i18n($ret,2).' ج.م' : '—'; ?></b></div>
            <div class="tv56-tier"><span>جملة</span><b><?php echo $wh ? number_format_i18n($wh,2).' ج.م' : '—'; ?></b></div>
            <div class="tv56-tier"><span>جملة الجملة</span><b><?php echo $bulk ? number_format_i18n($bulk,2).' ج.م' : '—'; ?></b></div>
            <a class="tv56-btn tv56-btn-small" href="<?php echo esc_url($url); ?>">عرض التفاصيل</a>
          </div>
        </article><?php return ob_get_clean();
    }

    public static function home() {
        $products = get_posts(['post_type'=>'tager_product','post_status'=>'publish','posts_per_page'=>8,'orderby'=>'date','order'=>'DESC']);
        $vendors = count(get_users(['role__in'=>['tager_vendor','wcfm_vendor','vendor'],'fields'=>'ID']));
        $product_count = self::count_posts_safe('tager_product');
        $account_url = is_user_logged_in() ? self::role_home() : self::page_url('choose-account');
        ob_start(); ?>
        <div class="tv56-home">
          <section class="tv56-hero">
            <div class="tv56-hero-copy">
              <span class="tv56-kicker">سوق مصري موثوق للقطاعي والجملة</span>
              <h1>كل احتياجاتك من موردين موثوقين وبسعر يناسب الكمية</h1>
              <p>ابحث عن المنتج، اختر مستوى السعر: قطاعي أو جملة أو جملة الجملة، وقارن الموردين حسب المحافظة والمركز وحد الطلب الأدنى.</p>
              <form class="tv56-search" action="<?php echo esc_url(self::page_url('market')); ?>" method="get">
                <input name="q" type="search" placeholder="ابحث باسم المنتج أو المورد أو القسم" aria-label="بحث">
                <select name="tier" aria-label="نوع السعر"><option value="">كل أنواع الأسعار</option><option value="retail">قطاعي</option><option value="wholesale">جملة</option><option value="bulk">جملة الجملة</option></select>
                <button type="submit">بحث في السوق</button>
              </form>
              <div class="tv56-hero-actions">
                <a class="tv56-btn" href="<?php echo esc_url(self::page_url('market')); ?>">ابدأ التسوق</a>
                <a class="tv56-btn tv56-btn-light" href="<?php echo esc_url(self::page_url('vendors')); ?>">دليل الموردين</a>
                <a class="tv56-btn tv56-btn-ghost" href="<?php echo esc_url($account_url); ?>"><?php echo is_user_logged_in()?'فتح حسابي':'إنشاء حساب'; ?></a>
              </div>
            </div>
            <div class="tv56-hero-panel">
              <div><span>قطاعي</span><b>من قطعة واحدة</b></div>
              <div><span>جملة</span><b>أسعار حسب الكمية</b></div>
              <div><span>جملة الجملة</span><b>للتجار والموزعين</b></div>
            </div>
          </section>

          <section class="tv56-stats">
            <article><b><?php echo number_format_i18n($product_count); ?>+</b><span>منتج متاح</span></article>
            <article><b><?php echo number_format_i18n($vendors); ?>+</b><span>مورد مسجل</span></article>
            <article><b>27</b><span>محافظة مصرية</span></article>
            <article><b>3</b><span>مستويات سعر</span></article>
          </section>

          <section class="tv56-section">
            <header><div><span>تسوق حسب احتياجك</span><h2>طرق شراء مرنة</h2></div></header>
            <div class="tv56-buy-modes">
              <a href="<?php echo esc_url(add_query_arg('tier','retail',self::page_url('market'))); ?>"><i>🛍️</i><h3>قطاعي</h3><p>بدون تعقيدات وحدود كبيرة.</p></a>
              <a href="<?php echo esc_url(add_query_arg('tier','wholesale',self::page_url('market'))); ?>"><i>📦</i><h3>جملة</h3><p>سعر أقل عند الوصول للحد الأدنى.</p></a>
              <a href="<?php echo esc_url(add_query_arg('tier','bulk',self::page_url('market'))); ?>"><i>🏭</i><h3>جملة الجملة</h3><p>أسعار خاصة للتجار والموزعين.</p></a>
            </div>
          </section>

          <section class="tv56-section">
            <header><div><span>وصل حديثًا</span><h2>منتجات مختارة من السوق</h2></div><a href="<?php echo esc_url(self::page_url('market')); ?>">عرض كل المنتجات</a></header>
            <?php if($products): ?><div class="tv56-products"><?php foreach($products as $p) echo self::product_card($p); ?></div><?php else: ?>
              <div class="tv56-empty"><h3>لا توجد منتجات منشورة بعد</h3><p>أضف موردًا ومنتجًا تجريبيًا، ثم وافق عليه من الإدارة ليظهر هنا.</p><?php if(self::is_admin_team()): ?><a class="tv56-btn" href="<?php echo esc_url(admin_url('admin.php?page=tager-v36-products')); ?>">مراجعة المنتجات</a><?php endif; ?></div>
            <?php endif; ?>
          </section>

          <section class="tv56-section tv56-how">
            <header><div><span>خطوات بسيطة</span><h2>كيف تعمل تاجر؟</h2></div></header>
            <div class="tv56-steps"><article><b>1</b><h3>ابحث وقارن</h3><p>حسب المنتج والسعر والموقع.</p></article><article><b>2</b><h3>اختَر المورد</h3><p>راجع حد الطلب والتوصيل.</p></article><article><b>3</b><h3>حدّد نوع السلة</h3><p>منفصلة أو مميزة مختلطة.</p></article><article><b>4</b><h3>ادفع وتابع</h3><p>اختَر الشحن وطريقة الدفع.</p></article></div>
          </section>

          <section class="tv56-cta">
            <div><span>لديك منتجات وتريد الوصول لعملاء أكثر؟</span><h2>ابدأ البيع على تاجر</h2><p>أنشئ متجر المورد، أضف المنتجات والأسعار والمخزون، وتابع الطلبات والأرباح من لوحة واحدة.</p></div>
            <a class="tv56-btn tv56-btn-gold" href="<?php echo esc_url(self::page_url('vendor-register')); ?>">سجل كمورد</a>
          </section>
        </div><?php return ob_get_clean();
    }

    public static function account_entry() {
        if (!is_user_logged_in()) return '<a class="tv56-account-link" href="'.esc_url(self::page_url('login')).'">تسجيل الدخول</a>';
        return '<a class="tv56-account-link" href="'.esc_url(self::role_home()).'">فتح لوحة حسابي</a>';
    }

    public static function body_classes($classes) {
        if (is_front_page()) $classes[] = 'tager-v56-front';
        if (isset($_GET['tager_preview'])) $classes[] = 'tager-preview-mode';
        return $classes;
    }

    public static function preview_admin_bar($show) {
        if (isset($_GET['tager_preview']) && current_user_can('manage_options')) return false;
        return $show;
    }

    private static function audit() {
        $essential = ['home','login','choose-account','customer-register','vendor-register','customer-home','vendor-home','admin-home','market','vendors','cart','checkout','forgot-password'];
        $rows=[];
        foreach($essential as $slug){
            $p=get_page_by_path($slug);
            $issues=[];
            if(!$p) $issues[]='الصفحة غير موجودة';
            else {
                if(trim((string)$p->post_content)==='') $issues[]='المحتوى فارغ';
                preg_match_all('/\[([a-zA-Z0-9_-]+)/',(string)$p->post_content,$m);
                foreach(array_unique($m[1]??[]) as $sc) if(!shortcode_exists($sc)) $issues[]='Shortcode غير مسجل: '.$sc;
                if(preg_match('/href=["\'](?:#|javascript:void\(0\)|)["\']/i',(string)$p->post_content)) $issues[]='رابط فارغ داخل المحتوى';
            }
            $rows[]=['slug'=>$slug,'page'=>$p,'issues'=>$issues];
        }
        return $rows;
    }

    public static function admin_menu() {
        add_menu_page('Tager V56','Tager V56','manage_options','tager-v56',[__CLASS__,'admin_page'],'dashicons-visibility',2);
    }

    public static function admin_page() {
        if(!current_user_can('manage_options')) return;
        $rows=self::audit(); $bad=0; foreach($rows as $r) if($r['issues'])$bad++;
        $test=get_transient('tager_v56_smoke_test');
        $preview=add_query_arg('tager_preview','1',home_url('/'));
        echo '<div class="wrap"><h1>Tager V56 — معاينة ما قبل النشر</h1><p>نسخة نظيفة للتجربة البشرية: بدون شريط فحص داخل الواجهة وبدون أزرار مكررة في الرئيسية.</p>';
        echo '<p><a class="button button-primary button-hero" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v56_repair'),self::NONCE)).'">إصلاح الصفحة الرئيسية والروابط</a> <a class="button button-hero" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v56_smoke_test'),self::NONCE)).'">تشغيل اختبار البيانات</a> <a class="button button-hero" target="_blank" href="'.esc_url($preview).'">معاينة كزائر</a></p>';
        echo '<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;max-width:850px;margin:18px 0"><div class="card"><h2>'.count($rows).'</h2><p>صفحات أساسية</p></div><div class="card"><h2>'.($bad?'<span style="color:#b32d2e">'.$bad.'</span>':'<span style="color:#138a4b">0</span>').'</h2><p>مشكلات مكتشفة</p></div><div class="card"><h2>V56</h2><p>واجهة المعاينة</p></div></div>';
        if($test){echo '<div class="notice notice-info"><h3>نتيجة الاختبار</h3>';foreach($test as $x)echo '<p>'.($x['ok']?'✅':'❌').' '.esc_html($x['name']).' — '.esc_html($x['detail']).'</p>';echo '</div>';}
        echo '<table class="widefat striped"><thead><tr><th>الصفحة</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>';
        foreach($rows as $r){$p=$r['page'];echo '<tr><td><code>/'.esc_html($r['slug']).'/</code></td><td>'.($r['issues']?'<span style="color:#b32d2e">'.esc_html(implode(' — ',$r['issues'])).'</span>':'✅ جاهزة').'</td><td>'.($p?'<a target="_blank" href="'.esc_url(get_permalink($p)).'">فتح</a> | <a href="'.esc_url(get_edit_post_link($p->ID)).'">تعديل</a>':'—').'</td></tr>';}
        echo '</tbody></table></div>';
    }

    public static function repair_action() {
        if(!current_user_can('manage_options')) wp_die('No permission');
        check_admin_referer(self::NONCE);
        delete_option(self::OPT);
        self::ensure_home();
        flush_rewrite_rules(false);
        wp_safe_redirect(admin_url('admin.php?page=tager-v56&repaired=1')); exit;
    }

    public static function smoke_test_action() {
        if(!current_user_can('manage_options')) wp_die('No permission');
        check_admin_referer(self::NONCE);
        $r=[]; $add=function($n,$ok,$d)use(&$r){$r[]=['name'=>$n,'ok'=>(bool)$ok,'detail'=>$d];};
        $uid=wp_insert_user(['user_login'=>'v56_'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(14),'user_email'=>'v56_'.time().'@example.test','role'=>'tager_customer','display_name'=>'V56 Test Customer']);
        if(is_wp_error($uid)){$add('إنشاء عميل',false,$uid->get_error_message());}
        else {
            update_user_meta($uid,'tager_phone','010'.wp_rand(10000000,99999999));
            $add('حفظ العميل والهاتف',get_user_meta($uid,'tager_phone',true)!=='','تم الحفظ والقراءة');
            $pid=wp_insert_post(['post_type'=>'tager_product','post_status'=>'draft','post_title'=>'V56 Test Product','post_author'=>$uid],true);
            if(is_wp_error($pid))$add('إنشاء منتج',false,$pid->get_error_message());
            else {
                update_post_meta($pid,'retail_price',100);update_post_meta($pid,'wholesale_price',90);update_post_meta($pid,'bulk_price',80);update_post_meta($pid,'stock',10);
                $ok=(float)get_post_meta($pid,'bulk_price',true)===80.0 && (int)get_post_meta($pid,'stock',true)===10;
                $add('حفظ أسعار المنتج والمخزون',$ok,$ok?'البيانات سليمة':'تعذر قراءة البيانات');
                wp_delete_post($pid,true);
            }
            wp_delete_user($uid);
        }
        $home=(int)get_option('page_on_front');$add('تعيين الصفحة الرئيسية',$home>0,$home?'صفحة ثابتة مفعلة':'غير مفعلة');
        set_transient('tager_v56_smoke_test',$r,300);
        wp_safe_redirect(admin_url('admin.php?page=tager-v56&tested=1'));exit;
    }

    public static function assets() {
        wp_register_style('tager-v56', false, [], self::VERSION);
        wp_enqueue_style('tager-v56');
        wp_add_inline_style('tager-v56', self::css());
    }

    public static function footer_script() { ?>
      <script>(function(){
        var toggle=document.querySelector('.menu-toggle'),nav=document.querySelector('.main-nav');
        if(toggle&&nav&&!toggle.dataset.v56){toggle.dataset.v56='1';toggle.addEventListener('click',function(){var on=nav.classList.toggle('is-open');toggle.setAttribute('aria-expanded',on?'true':'false');});}
        document.querySelectorAll('form').forEach(function(form){if(form.dataset.v56)return;form.dataset.v56='1';form.addEventListener('submit',function(e){var bad=form.querySelector(':invalid');if(bad){e.preventDefault();bad.focus();return;}var btn=form.querySelector('button[type=submit],input[type=submit]');if(btn&&!btn.disabled){btn.disabled=true;btn.dataset.label=btn.tagName==='BUTTON'?btn.innerHTML:btn.value;if(btn.tagName==='BUTTON')btn.innerHTML='جاري التنفيذ...';else btn.value='جاري التنفيذ...';setTimeout(function(){btn.disabled=false;if(btn.tagName==='BUTTON')btn.innerHTML=btn.dataset.label;else btn.value=btn.dataset.label;},12000);}});});
      })();</script>
    <?php }

    private static function css() { return '
      html,body{max-width:100%;overflow-x:hidden}.tager-v56-front .site-main{max-width:none;padding:0}.tager-v56-front article>h1{display:none}.tager-v56-front article{margin:0}.tv54-context,.tv53-links,.t39-toplinks{display:none!important}
      .tv56-home{max-width:1320px;margin:auto;padding:28px 22px 60px;display:grid;gap:34px}.tv56-hero{background:radial-gradient(circle at 80% 15%,rgba(231,190,91,.18),transparent 28%),linear-gradient(125deg,#063d2e,#087353);color:#fff;border-radius:30px;padding:58px;display:grid;grid-template-columns:minmax(0,1.55fr) minmax(260px,.55fr);gap:35px;align-items:center;box-shadow:0 28px 70px rgba(5,67,48,.24)}.tv56-kicker{display:inline-flex;padding:7px 13px;border:1px solid rgba(255,226,160,.3);border-radius:999px;background:rgba(255,226,160,.1);color:#ffe2a0;font-weight:800}.tv56-hero h1{font-size:clamp(38px,5vw,66px);line-height:1.1;letter-spacing:-2px;margin:18px 0}.tv56-hero p{font-size:17px;color:#d9eee6;max-width:780px}.tv56-search{display:grid;grid-template-columns:minmax(0,1fr) 170px auto;gap:8px;background:#fff;padding:7px;border-radius:15px;margin-top:24px}.tv56-search input,.tv56-search select{border:0!important;box-shadow:none!important}.tv56-search button{border:0;border-radius:10px;background:#d9a441;color:#183327;font-weight:900;padding:12px 20px;cursor:pointer}.tv56-hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:17px}.tv56-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;padding:12px 18px;background:#fff;color:#07543d!important;text-decoration:none!important;font-weight:900;cursor:pointer}.tv56-btn-light{background:#d9a441;color:#183327!important}.tv56-btn-ghost{background:transparent;border:1px solid rgba(255,255,255,.38);color:#fff!important}.tv56-btn-small{background:#07543d;color:#fff!important;width:100%;padding:9px 12px;font-size:13px}.tv56-btn-gold{background:#d9a441;color:#183327!important}.tv56-hero-panel{display:grid;gap:12px}.tv56-hero-panel div{padding:18px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);border-radius:16px;backdrop-filter:blur(6px)}.tv56-hero-panel span{display:block;color:#ffe2a0;font-weight:900}.tv56-hero-panel b{font-size:18px}.tv56-stats{margin-top:-58px;width:calc(100% - 80px);justify-self:center;background:#fff;border:1px solid #dce5e0;border-radius:20px;padding:20px;display:grid;grid-template-columns:repeat(4,1fr);box-shadow:0 18px 45px rgba(8,72,51,.12)}.tv56-stats article{text-align:center;border-inline-end:1px solid #e2e9e5}.tv56-stats article:last-child{border:0}.tv56-stats b{display:block;color:#07543d;font-size:27px}.tv56-stats span{color:#66736d;font-size:13px}.tv56-section>header{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:17px}.tv56-section>header span{color:#c18d29;font-weight:900}.tv56-section>header h2{margin:3px 0 0;font-size:30px}.tv56-section>header>a{color:#07543d;font-weight:900;text-decoration:none}.tv56-buy-modes{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.tv56-buy-modes a{background:#fff;border:1px solid #dce5e0;border-radius:19px;padding:25px;text-decoration:none;color:#14231d;transition:.2s;box-shadow:0 8px 24px rgba(12,52,38,.05)}.tv56-buy-modes a:hover{transform:translateY(-4px);border-color:#caa754}.tv56-buy-modes i{font-style:normal;font-size:38px}.tv56-buy-modes h3{margin:8px 0}.tv56-buy-modes p{margin:0;color:#66736d}.tv56-products{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:17px}.tv56-product{background:#fff;border:1px solid #dce5e0;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(12,52,38,.05)}.tv56-product-media{height:200px;display:grid;place-items:center;background:#edf5f1;position:relative;text-decoration:none;font-size:52px}.tv56-product-media img{width:100%;height:100%;object-fit:cover}.tv56-product-media em{position:absolute;top:11px;right:11px;background:#fff;color:#07543d;border-radius:999px;padding:4px 9px;font-size:11px;font-style:normal;font-weight:900}.tv56-product-body{padding:16px}.tv56-product-body small{color:#66736d}.tv56-product-body h3{margin:5px 0 12px;font-size:17px;min-height:52px}.tv56-product-body h3 a{text-decoration:none}.tv56-tier{display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px dashed #e3e9e6;font-size:12px}.tv56-tier b{color:#07543d}.tv56-product .tv56-btn{margin-top:12px}.tv56-how{background:#eef8f3;border:1px solid #d9ebe2;border-radius:24px;padding:32px}.tv56-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.tv56-steps article{background:#fff;border:1px solid #dce5e0;border-radius:16px;padding:20px}.tv56-steps b{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:#07543d;color:#fff}.tv56-steps h3{margin:12px 0 5px}.tv56-steps p{margin:0;color:#66736d;font-size:13px}.tv56-cta{display:flex;justify-content:space-between;align-items:center;gap:25px;background:linear-gradient(120deg,#10291f,#07543d);color:#fff;border-radius:24px;padding:35px 40px}.tv56-cta span{color:#ffd989;font-weight:900}.tv56-cta h2{margin:5px 0}.tv56-cta p{margin:0;color:#d6e9e1}.tv56-empty{text-align:center;background:#fff;border:1px dashed #cbd8d2;border-radius:18px;padding:42px}
      @media(max-width:1050px){.tv56-products{grid-template-columns:repeat(3,1fr)}.tv56-hero{grid-template-columns:1fr}.tv56-hero-panel{grid-template-columns:repeat(3,1fr)}}
      @media(max-width:820px){.main-nav{display:none;position:absolute;top:100%;left:12px;right:12px;background:#fff;border:1px solid #dce5e0;border-radius:15px;padding:10px;box-shadow:0 18px 45px rgba(0,0,0,.12);flex-direction:column;align-items:stretch}.main-nav.is-open{display:flex}.main-nav a{text-align:center}.menu-toggle{display:inline-flex}.header-inner{flex-wrap:wrap}.header-actions{margin-inline-start:auto}.tv56-search{grid-template-columns:1fr}.tv56-stats{grid-template-columns:repeat(2,1fr);width:calc(100% - 24px)}.tv56-stats article:nth-child(2){border:0}.tv56-stats article:nth-child(-n+2){border-bottom:1px solid #e2e9e5}.tv56-buy-modes,.tv56-steps{grid-template-columns:repeat(2,1fr)}.tv56-products{grid-template-columns:repeat(2,1fr)}}
      @media(max-width:560px){.tv56-home{padding:16px 12px 45px;gap:25px}.tv56-hero{padding:30px 20px;border-radius:21px}.tv56-hero h1{font-size:37px;letter-spacing:-1px}.tv56-hero-panel{grid-template-columns:1fr}.tv56-hero-actions .tv56-btn{width:100%}.tv56-stats{margin-top:-40px;padding:12px 6px}.tv56-buy-modes,.tv56-products,.tv56-steps{grid-template-columns:1fr}.tv56-how{padding:23px 14px}.tv56-cta{align-items:stretch;flex-direction:column;padding:28px 22px}.tv56-cta .tv56-btn{width:100%}.header-actions .icon-action span:last-child,.header-actions .cart-action span:nth-child(2){display:none}.topbar>div:last-child{display:none}}
    '; }
}
Tager_V56_Production_Preview::init();
