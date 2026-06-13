<?php
/**
 * Plugin Name: Tager V41 Operational Experience
 * Description: Customer/vendor/admin workspaces, activity feed, reorder, product completeness, and end-to-end health checks.
 * Version: 41.0.0
 */
if (!defined('ABSPATH')) exit;

function tager_v41_is_vendor($user_id = 0){
    $u = get_userdata($user_id ?: get_current_user_id());
    if (!$u) return false;
    return in_array('tager_vendor', (array)$u->roles, true) || in_array('wcfm_vendor', (array)$u->roles, true) || in_array('vendor', (array)$u->roles, true);
}
function tager_v41_is_admin_staff($user_id = 0){
    $u = get_userdata($user_id ?: get_current_user_id());
    if (!$u) return false;
    if (user_can($u, 'manage_options')) return true;
    foreach ((array)$u->roles as $r) if (strpos($r,'tager_')===0 && !in_array($r,['tager_vendor','tager_customer'],true)) return true;
    return false;
}
function tager_v41_page_url($slug){
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : home_url('/'.$slug.'/');
}

add_action('init', function(){
    $pages = [
        'customer-activity' => ['نشاط الحساب','[tager_v41_customer_activity]'],
        'vendor-performance' => ['أداء المتجر','[tager_v41_vendor_performance]'],
        'vendor-product-check' => ['فحص اكتمال المنتجات','[tager_v41_vendor_product_check]'],
        'admin-health-center' => ['مركز صحة النظام','[tager_v41_admin_health]'],
    ];
    foreach($pages as $slug=>$data){
        $p=get_page_by_path($slug);
        if(!$p){ wp_insert_post(['post_title'=>$data[0],'post_name'=>$slug,'post_content'=>$data[1],'post_status'=>'publish','post_type'=>'page']); }
        elseif(trim($p->post_content)===''){ wp_update_post(['ID'=>$p->ID,'post_content'=>$data[1]]); }
    }
}, 30);

add_shortcode('tager_v41_customer_activity', function(){
    if(!is_user_logged_in()) return '<div class="tager-note">يجب تسجيل الدخول لعرض النشاط.</div>';
    if(tager_v41_is_vendor() || tager_v41_is_admin_staff()) return '<div class="tager-note">هذه الصفحة مخصصة للعملاء.</div>';
    $uid=get_current_user_id();
    $orders=get_posts(['post_type'=>['shop_order','tager_order'],'post_status'=>'any','numberposts'=>20,'meta_query'=>[['key'=>'_customer_user','value'=>$uid]]]);
    ob_start(); ?>
    <section class="t41-wrap"><div class="t41-hero"><div><span class="t41-kicker">حساب العميل</span><h1>نشاطك وطلباتك في مكان واحد</h1><p>راجع آخر الطلبات، أعد الطلب، وتابع الحالة بدون البحث في صفحات متعددة.</p></div><a class="t41-btn" href="<?php echo esc_url(tager_v41_page_url('products')); ?>">متابعة التسوق</a></div>
    <div class="t41-grid">
    <?php if(!$orders): ?><div class="t41-empty"><h3>لا توجد طلبات حتى الآن</h3><p>ابدأ بإضافة المنتجات إلى السلة ثم أكمل الطلب.</p></div><?php endif; ?>
    <?php foreach($orders as $o): $total=get_post_meta($o->ID,'_order_total',true); $status=get_post_status($o); ?>
      <article class="t41-card"><div class="t41-card-head"><strong>طلب #<?php echo esc_html($o->ID); ?></strong><span><?php echo esc_html($status); ?></span></div><p>الإجمالي: <b><?php echo esc_html($total ?: '—'); ?> ج.م</b></p><div class="t41-actions"><a class="t41-btn secondary" href="<?php echo esc_url(add_query_arg('order_id',$o->ID,tager_v41_page_url('track-order'))); ?>">تتبع الطلب</a><form method="post" style="display:inline"><input type="hidden" name="t41_reorder" value="<?php echo esc_attr($o->ID); ?>"><?php wp_nonce_field('t41_reorder_'.$o->ID,'t41_nonce'); ?><button class="t41-btn" type="submit">إعادة الطلب</button></form></div></article>
    <?php endforeach; ?></div></section><?php return ob_get_clean();
});

add_action('template_redirect', function(){
    if(empty($_POST['t41_reorder']) || !is_user_logged_in()) return;
    $oid=absint($_POST['t41_reorder']);
    if(!wp_verify_nonce($_POST['t41_nonce'] ?? '', 't41_reorder_'.$oid)) return;
    $owner=(int)get_post_meta($oid,'_customer_user',true);
    if($owner!==get_current_user_id()) return;
    $snapshot=get_post_meta($oid,'_tager_cart_snapshot',true);
    if(is_array($snapshot)){
        update_user_meta(get_current_user_id(),'_tager_reorder_snapshot',$snapshot);
        wp_safe_redirect(tager_v41_page_url('cart')); exit;
    }
    wp_safe_redirect(add_query_arg('reorder','unavailable',tager_v41_page_url('customer-activity'))); exit;
});

add_shortcode('tager_v41_vendor_performance', function(){
    if(!is_user_logged_in() || !tager_v41_is_vendor()) return '<div class="tager-note">هذه الصفحة مخصصة للموردين.</div>';
    $uid=get_current_user_id();
    $products=get_posts(['post_type'=>'product','author'=>$uid,'post_status'=>'any','numberposts'=>-1]);
    $published=0;$pending=0;$stock=0;$low=0;
    foreach($products as $p){ if($p->post_status==='publish')$published++; else $pending++; $s=(int)get_post_meta($p->ID,'_stock',true);$stock+=$s;if($s>0&&$s<=5)$low++; }
    $sales=(float)get_user_meta($uid,'_tager_vendor_sales_total',true); $commission=(float)get_user_meta($uid,'_tager_platform_commission_total',true);
    ob_start(); ?>
    <section class="t41-wrap"><div class="t41-hero"><div><span class="t41-kicker">لوحة المورد</span><h1>أداء المتجر</h1><p>مؤشرات واضحة تساعدك على متابعة المنتجات والمخزون والمبيعات.</p></div><a class="t41-btn" href="<?php echo esc_url(tager_v41_page_url('vendor-add-product')); ?>">إضافة منتج</a></div>
    <div class="t41-stats"><div><b><?php echo count($products); ?></b><span>إجمالي المنتجات</span></div><div><b><?php echo $published; ?></b><span>منشور</span></div><div><b><?php echo $pending; ?></b><span>تحت المراجعة</span></div><div><b><?php echo $low; ?></b><span>مخزون منخفض</span></div><div><b><?php echo number_format($sales,2); ?></b><span>إجمالي المبيعات</span></div><div><b><?php echo number_format(max(0,$sales-$commission),2); ?></b><span>صافي المستحق</span></div></div>
    <div class="t41-panel"><h2>اختصارات التشغيل</h2><div class="t41-actions"><a class="t41-btn" href="<?php echo esc_url(tager_v41_page_url('vendor-products')); ?>">إدارة المنتجات</a><a class="t41-btn secondary" href="<?php echo esc_url(tager_v41_page_url('vendor-orders')); ?>">طلبات المورد</a><a class="t41-btn secondary" href="<?php echo esc_url(tager_v41_page_url('vendor-product-check')); ?>">فحص اكتمال المنتجات</a><a class="t41-btn secondary" href="<?php echo esc_url(tager_v41_page_url('vendor-market')); ?>">مشاهدة السوق</a></div></div></section><?php return ob_get_clean();
});

add_shortcode('tager_v41_vendor_product_check', function(){
    if(!is_user_logged_in() || !tager_v41_is_vendor()) return '<div class="tager-note">هذه الصفحة مخصصة للموردين.</div>';
    $uid=get_current_user_id(); $products=get_posts(['post_type'=>'product','author'=>$uid,'post_status'=>'any','numberposts'=>-1]);
    ob_start(); ?><section class="t41-wrap"><div class="t41-hero"><div><span class="t41-kicker">جودة البيانات</span><h1>فحص اكتمال المنتجات</h1><p>أي نقص في السعر أو المخزون أو الصورة يظهر هنا قبل أن يؤثر على المبيعات.</p></div></div><div class="t41-table-wrap"><table class="t41-table"><thead><tr><th>المنتج</th><th>الحالة</th><th>السعر</th><th>المخزون</th><th>الصورة</th><th>النتيجة</th></tr></thead><tbody>
    <?php foreach($products as $p): $price=get_post_meta($p->ID,'_regular_price',true); $stock=get_post_meta($p->ID,'_stock',true); $thumb=has_post_thumbnail($p->ID); $ok=($price!=='' && $stock!=='' && $thumb && trim($p->post_title)!==''); ?>
    <tr><td><?php echo esc_html($p->post_title); ?></td><td><?php echo esc_html($p->post_status); ?></td><td><?php echo $price!==''?'✓':'ناقص'; ?></td><td><?php echo $stock!==''?'✓':'ناقص'; ?></td><td><?php echo $thumb?'✓':'ناقص'; ?></td><td><span class="t41-badge <?php echo $ok?'ok':'warn'; ?>"><?php echo $ok?'مكتمل':'يحتاج استكمال'; ?></span></td></tr>
    <?php endforeach; if(!$products): ?><tr><td colspan="6">لا توجد منتجات.</td></tr><?php endif; ?></tbody></table></div></section><?php return ob_get_clean();
});

add_shortcode('tager_v41_admin_health', function(){
    if(!tager_v41_is_admin_staff()) return '<div class="tager-note">غير مصرح.</div>';
    $checks=[];
    foreach(['login','customer-dashboard','vendor-dashboard','admin-portal','products','cart','checkout'] as $slug) $checks[]=['الصفحة: '.$slug,(bool)get_page_by_path($slug)];
    $checks[]=['دور المورد', get_role('tager_vendor') || get_role('wcfm_vendor')];
    $checks[]=['دور العميل', get_role('tager_customer') || get_role('customer')];
    $checks[]=['القالب Tager', wp_get_theme('tager-marketplace')->exists()];
    ob_start(); ?><section class="t41-wrap"><div class="t41-hero"><div><span class="t41-kicker">الإدارة</span><h1>مركز صحة النظام</h1><p>فحص سريع للصفحات والأدوار والقالب قبل الإطلاق.</p></div><form method="post"><?php wp_nonce_field('t41_repair','t41_admin_nonce'); ?><button class="t41-btn" name="t41_repair" value="1">إصلاح المكونات الناقصة</button></form></div><div class="t41-grid">
    <?php foreach($checks as $c): ?><div class="t41-card"><div class="t41-card-head"><strong><?php echo esc_html($c[0]); ?></strong><span class="t41-badge <?php echo $c[1]?'ok':'warn'; ?>"><?php echo $c[1]?'جاهز':'يحتاج إصلاح'; ?></span></div></div><?php endforeach; ?></div></section><?php return ob_get_clean();
});

add_action('template_redirect', function(){
    if(empty($_POST['t41_repair']) || !tager_v41_is_admin_staff()) return;
    if(!wp_verify_nonce($_POST['t41_admin_nonce'] ?? '', 't41_repair')) return;
    $required=[
      'login'=>['تسجيل الدخول','[tager_login]'], 'customer-dashboard'=>['حساب العميل','[tager_customer_dashboard]'],
      'vendor-dashboard'=>['لوحة المورد','[tager_vendor_dashboard]'], 'admin-portal'=>['بوابة الإدارة','[tager_admin_portal]'],
      'products'=>['المنتجات','[tager_products]'], 'cart'=>['السلة','[tager_cart]'], 'checkout'=>['إتمام الطلب','[tager_checkout]']
    ];
    foreach($required as $slug=>$d){ if(!get_page_by_path($slug)) wp_insert_post(['post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1],'post_status'=>'publish','post_type'=>'page']); }
    flush_rewrite_rules(false); wp_safe_redirect(tager_v41_page_url('admin-health-center')); exit;
});

add_action('wp_enqueue_scripts', function(){
    $css=':root{--t41-green:#0f3d2e;--t41-gold:#d6a83f;--t41-bg:#f5f7f6;--t41-text:#17221d}.t41-wrap{max-width:1200px;margin:30px auto;padding:0 18px;color:var(--t41-text)}.t41-hero{background:linear-gradient(135deg,#0f3d2e,#1d5a45);color:#fff;border-radius:24px;padding:28px;display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:22px}.t41-hero h1{margin:5px 0 8px;font-size:34px}.t41-kicker{color:#f7d888;font-weight:700}.t41-btn{display:inline-flex;align-items:center;justify-content:center;background:var(--t41-gold);color:#17221d!important;border:0;border-radius:12px;padding:11px 16px;font-weight:800;text-decoration:none;cursor:pointer}.t41-btn.secondary{background:#fff;color:var(--t41-green)!important;border:1px solid #d7e1dc}.t41-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}.t41-card,.t41-panel,.t41-empty{background:#fff;border:1px solid #e3e9e6;border-radius:18px;padding:18px;box-shadow:0 8px 28px rgba(15,61,46,.06)}.t41-card-head{display:flex;justify-content:space-between;gap:12px;align-items:center}.t41-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.t41-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px}.t41-stats>div{background:#fff;border-radius:18px;padding:18px;border:1px solid #e3e9e6}.t41-stats b{font-size:26px;color:var(--t41-green);display:block}.t41-stats span{color:#627168}.t41-table-wrap{overflow:auto;background:#fff;border-radius:18px;border:1px solid #e3e9e6}.t41-table{width:100%;border-collapse:collapse}.t41-table th,.t41-table td{padding:13px;border-bottom:1px solid #edf1ef;text-align:right;white-space:nowrap}.t41-badge{display:inline-block;padding:5px 10px;border-radius:999px;font-weight:700}.t41-badge.ok{background:#e7f7ee;color:#176a3d}.t41-badge.warn{background:#fff2df;color:#9a5b00}@media(max-width:700px){.t41-hero{align-items:flex-start;flex-direction:column}.t41-hero h1{font-size:27px}.t41-btn{width:100%}.t41-actions .t41-btn{width:auto;flex:1}}';
    wp_register_style('tager-v41-inline',false); wp_enqueue_style('tager-v41-inline'); wp_add_inline_style('tager-v41-inline',$css);
});

add_action('admin_menu', function(){
    add_menu_page('Tager V41','Tager V41','manage_options','tager-v41',function(){ echo '<div class="wrap"><h1>Tager V41 Operational Experience</h1><p>تمت إضافة صفحات النشاط، أداء المورد، فحص المنتجات، ومركز صحة النظام.</p><p><a class="button button-primary" href="'.esc_url(tager_v41_page_url('admin-health-center')).'">فتح مركز صحة النظام</a></p></div>'; },'dashicons-chart-area',3);
});
