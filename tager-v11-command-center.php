<?php
/**
 * Plugin Name: Tager V11 Command Center & Workflow
 * Description: Unified operations dashboard, workflow timeline, safer admin actions, data repair and role-aware account hub.
 * Version: 11.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V11_Command_Center {
    const VERSION = '11.0.0';

    public static function init() {
        add_action('init', [__CLASS__, 'bootstrap'], 80);
        add_action('admin_menu', [__CLASS__, 'menu'], 120);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'front_assets'], 120);
        add_shortcode('tager_v11_account_hub', [__CLASS__, 'account_hub']);
        add_action('admin_post_tager_v11_vendor_decision', [__CLASS__, 'vendor_decision']);
        add_action('admin_post_tager_v11_product_decision', [__CLASS__, 'product_decision']);
        add_action('admin_post_tager_v11_order_update', [__CLASS__, 'order_update']);
        add_action('admin_post_tager_v11_repair', [__CLASS__, 'repair']);
        add_action('updated_post_meta', [__CLASS__, 'track_order_change'], 10, 4);
        add_action('added_post_meta', [__CLASS__, 'track_order_change'], 10, 4);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 10, 3);
    }

    private static function pages() { return (array) get_option('tager_pages', []); }
    private static function page_url($slug) {
        $pages = self::pages();
        return !empty($pages[$slug]) ? get_permalink($pages[$slug]) : home_url('/' . trim($slug, '/') . '/');
    }
    private static function lang() { return (!empty($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar'; }
    private static function t($ar, $en) { return self::lang() === 'en' ? $en : $ar; }

    public static function bootstrap() {
        if (get_option('tager_v11_bootstrapped') === self::VERSION) return;
        self::ensure_page('account-hub', 'مركز الحساب', '[tager_v11_account_hub]');
        self::repair_roles();
        self::repair_pages();
        update_option('tager_v11_bootstrapped', self::VERSION);
    }

    private static function ensure_page($slug, $title, $content) {
        $pages = self::pages();
        $page = get_page_by_path($slug);
        $id = $page ? $page->ID : wp_insert_post([
            'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug,
            'post_title' => $title, 'post_content' => $content,
        ]);
        if (!is_wp_error($id) && $id) { $pages[$slug] = (int) $id; update_option('tager_pages', $pages); }
    }

    private static function repair_roles() {
        $customer = get_role('tager_customer');
        if (!$customer) $customer = add_role('tager_customer', 'Customer', ['read' => true]);
        $pending = get_role('tager_vendor_pending');
        if (!$pending) $pending = add_role('tager_vendor_pending', 'Vendor Pending', ['read' => true]);
        $vendor = get_role('tager_vendor');
        if (!$vendor) $vendor = add_role('tager_vendor', 'Vendor', ['read' => true, 'upload_files' => true]);
        if ($vendor) { $vendor->add_cap('upload_files'); $vendor->add_cap('read'); }
    }

    private static function repair_pages() {
        $required = [
            'home' => ['الرئيسية','[tager_home]'], 'shop' => ['المنتجات','[tager_shop]'],
            'customer-register' => ['تسجيل العميل','[tager_customer_register]'], 'my-account' => ['حساب العميل','[tager_customer_account]'],
            'vendor-register' => ['انضم كمورد','[tager_vendor_register]'], 'vendor-dashboard' => ['لوحة المورد','[tager_vendor_dashboard]'],
            'cart' => ['السلة','[tager_cart]'], 'account-hub' => ['مركز الحساب','[tager_v11_account_hub]'],
        ];
        foreach ($required as $slug => $data) self::ensure_page($slug, $data[0], $data[1]);
    }

    public static function login_redirect($redirect_to, $requested, $user) {
        if (!($user instanceof WP_User)) return $redirect_to;
        if (in_array('tager_vendor', $user->roles, true) || in_array('tager_vendor_pending', $user->roles, true)) return self::page_url('vendor-dashboard');
        if (in_array('tager_customer', $user->roles, true)) return self::page_url('account-hub');
        return $redirect_to;
    }

    public static function menu() {
        add_submenu_page('tager-control', 'V11 Command Center', 'V11 Command Center', 'manage_options', 'tager-v11-command', [__CLASS__, 'command_center']);
    }

    public static function admin_assets($hook) {
        if (strpos($hook, 'tager') === false) return;
        wp_register_style('tager-v11-admin', false, [], self::VERSION); wp_enqueue_style('tager-v11-admin');
        wp_add_inline_style('tager-v11-admin', self::admin_css());
    }

    public static function front_assets() {
        wp_register_style('tager-v11-front', false, [], self::VERSION); wp_enqueue_style('tager-v11-front');
        wp_add_inline_style('tager-v11-front', self::front_css());
    }

    private static function count_posts($type, $status = 'any') {
        $q = new WP_Query(['post_type'=>$type, 'post_status'=>$status, 'posts_per_page'=>1, 'fields'=>'ids']);
        return (int) $q->found_posts;
    }

    private static function order_total() {
        $orders = get_posts(['post_type'=>'tager_order','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids']);
        $sum = 0.0; foreach ($orders as $id) $sum += (float) get_post_meta($id, 'total', true);
        return $sum;
    }

    private static function url($action, array $args, $nonce) {
        return wp_nonce_url(add_query_arg(array_merge(['action'=>$action], $args), admin_url('admin-post.php')), $nonce);
    }

    public static function command_center() {
        if (!current_user_can('manage_options')) return;
        $pending_vendors = get_users(['role'=>'tager_vendor_pending']);
        $pending_products = get_posts(['post_type'=>'tager_product','post_status'=>'pending','numberposts'=>10]);
        $orders = get_posts(['post_type'=>'tager_order','post_status'=>'publish','numberposts'=>10,'orderby'=>'date','order'=>'DESC']);
        $health = self::health();
        echo '<div class="wrap tager-v11-admin"><div class="tv11-head"><div><h1>Tager V11 Command Center</h1><p>الموافقات والطلبات وصحة النظام في شاشة واحدة.</p></div><a class="button button-primary" href="'.esc_url(self::url('tager_v11_repair', ['run'=>1], 'tager_v11_repair')).'">Repair configuration</a></div>';
        echo '<div class="tv11-kpis">';
        foreach ([
            ['Pending vendors', count($pending_vendors), 'dashicons-groups'],
            ['Pending products', self::count_posts('tager_product','pending'), 'dashicons-products'],
            ['Total orders', self::count_posts('tager_order','publish'), 'dashicons-clipboard'],
            ['Gross sales', number_format_i18n(self::order_total(),2).' EGP', 'dashicons-chart-area'],
        ] as $k) echo '<div class="tv11-kpi"><span class="dashicons '.esc_attr($k[2]).'"></span><div><b>'.esc_html($k[1]).'</b><small>'.esc_html($k[0]).'</small></div></div>';
        echo '</div>';

        echo '<div class="tv11-grid"><section class="tv11-panel"><h2>Vendor approvals</h2>';
        if (!$pending_vendors) echo '<p class="tv11-empty">No pending vendors.</p>';
        foreach (array_slice($pending_vendors,0,8) as $u) {
            echo '<div class="tv11-row"><div><b>'.esc_html(get_user_meta($u->ID,'store_name',true) ?: $u->display_name).'</b><small>'.esc_html($u->user_email).'</small></div><div class="tv11-actions">';
            echo '<a class="button button-primary" href="'.esc_url(self::url('tager_v11_vendor_decision',['user'=>$u->ID,'decision'=>'approve'],'tv11_vendor_'.$u->ID)).'">Approve</a>';
            echo '<a class="button" href="'.esc_url(self::url('tager_v11_vendor_decision',['user'=>$u->ID,'decision'=>'suspend'],'tv11_vendor_'.$u->ID)).'">Suspend</a></div></div>';
        }
        echo '</section><section class="tv11-panel"><h2>Product moderation</h2>';
        if (!$pending_products) echo '<p class="tv11-empty">No pending products.</p>';
        foreach ($pending_products as $p) {
            echo '<div class="tv11-row"><div><b>'.esc_html($p->post_title).'</b><small>'.esc_html(get_the_author_meta('display_name',$p->post_author)).' · '.esc_html(get_post_meta($p->ID,'retail_price',true)).' EGP</small></div><div class="tv11-actions">';
            echo '<a class="button button-primary" href="'.esc_url(self::url('tager_v11_product_decision',['product'=>$p->ID,'decision'=>'approve'],'tv11_product_'.$p->ID)).'">Approve</a>';
            echo '<a class="button" href="'.esc_url(self::url('tager_v11_product_decision',['product'=>$p->ID,'decision'=>'pending'],'tv11_product_'.$p->ID)).'">Hold</a></div></div>';
        }
        echo '</section></div>';

        echo '<section class="tv11-panel tv11-orders"><h2>Recent orders</h2><div class="tv11-table"><table><thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Workflow</th></tr></thead><tbody>';
        foreach ($orders as $o) {
            $status = get_post_meta($o->ID,'order_status',true) ?: 'New';
            echo '<tr><td>#'.$o->ID.'</td><td>'.esc_html(get_post_meta($o->ID,'customer_name',true)).'</td><td>'.esc_html(number_format_i18n((float)get_post_meta($o->ID,'total',true),2)).' EGP</td><td><span class="tv11-status">'.esc_html($status).'</span></td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            wp_nonce_field('tv11_order_'.$o->ID); echo '<input type="hidden" name="action" value="tager_v11_order_update"><input type="hidden" name="order" value="'.$o->ID.'"><select name="status">';
            foreach (['New','Confirmed','Processing','Shipped','Completed','Cancelled'] as $s) echo '<option '.selected($status,$s,false).'>'.esc_html($s).'</option>';
            echo '</select><button class="button">Save</button></form></td></tr>';
        }
        echo '</tbody></table></div></section>';

        echo '<section class="tv11-panel"><h2>System health</h2><div class="tv11-health">';
        foreach ($health as $label=>$ok) echo '<div class="'.($ok?'ok':'bad').'"><span class="dashicons '.($ok?'dashicons-yes-alt':'dashicons-warning').'"></span><b>'.esc_html($label).'</b><small>'.($ok?'Ready':'Needs repair').'</small></div>';
        echo '</div></section></div>';
    }

    private static function health() {
        $pages = self::pages();
        return [
            'Customer registration' => !empty($pages['customer-register']) && get_post_status($pages['customer-register']) === 'publish',
            'Vendor registration' => !empty($pages['vendor-register']) && get_post_status($pages['vendor-register']) === 'publish',
            'Vendor dashboard' => !empty($pages['vendor-dashboard']) && get_post_status($pages['vendor-dashboard']) === 'publish',
            'Cart and checkout' => !empty($pages['cart']) && get_post_status($pages['cart']) === 'publish',
            'Customer role' => get_role('tager_customer') !== null,
            'Vendor role' => get_role('tager_vendor') !== null,
            'Products data model' => post_type_exists('tager_product'),
            'Orders data model' => post_type_exists('tager_order'),
            'Theme' => wp_get_theme('tager-marketplace')->exists(),
        ];
    }

    public static function vendor_decision() {
        if (!current_user_can('manage_options')) wp_die('Not allowed');
        $id = absint($_GET['user'] ?? 0); check_admin_referer('tv11_vendor_'.$id);
        $decision = sanitize_key($_GET['decision'] ?? ''); $u = new WP_User($id);
        if (!$u->exists()) wp_die('Vendor not found');
        if ($decision === 'approve') { $u->set_role('tager_vendor'); update_user_meta($id,'vendor_status','approved'); self::notify($u->user_email,'Tager vendor account approved','Your vendor account has been approved.'); }
        elseif ($decision === 'suspend') { $u->set_role('tager_vendor_pending'); update_user_meta($id,'vendor_status','suspended'); self::notify($u->user_email,'Tager vendor account suspended','Your vendor account has been suspended.'); }
        wp_safe_redirect(admin_url('admin.php?page=tager-v11-command&updated=vendor')); exit;
    }

    public static function product_decision() {
        if (!current_user_can('manage_options')) wp_die('Not allowed');
        $id = absint($_GET['product'] ?? 0); check_admin_referer('tv11_product_'.$id);
        if (get_post_type($id) !== 'tager_product') wp_die('Product not found');
        $decision = sanitize_key($_GET['decision'] ?? 'pending');
        if ($decision === 'approve') { wp_update_post(['ID'=>$id,'post_status'=>'publish']); update_post_meta($id,'approval_status','approved'); }
        else { wp_update_post(['ID'=>$id,'post_status'=>'pending']); update_post_meta($id,'approval_status','pending'); }
        $email = get_the_author_meta('user_email', (int)get_post_field('post_author',$id));
        self::notify($email, 'Tager product review', 'Product "'.get_the_title($id).'" status: '.$decision.'.');
        wp_safe_redirect(admin_url('admin.php?page=tager-v11-command&updated=product')); exit;
    }

    public static function order_update() {
        if (!current_user_can('manage_options')) wp_die('Not allowed');
        $id = absint($_POST['order'] ?? 0); check_admin_referer('tv11_order_'.$id);
        if (get_post_type($id) !== 'tager_order') wp_die('Order not found');
        $allowed = ['New','Confirmed','Processing','Shipped','Completed','Cancelled'];
        $status = sanitize_text_field($_POST['status'] ?? 'New'); if (!in_array($status,$allowed,true)) $status='New';
        update_post_meta($id,'order_status',$status);
        wp_safe_redirect(admin_url('admin.php?page=tager-v11-command&updated=order')); exit;
    }

    public static function track_order_change($meta_id, $post_id, $meta_key, $value) {
        if ($meta_key !== 'order_status' || get_post_type($post_id) !== 'tager_order') return;
        $timeline = (array) get_post_meta($post_id,'tager_v11_timeline',true);
        $last = end($timeline); if (is_array($last) && ($last['status'] ?? '') === $value) return;
        $timeline[] = ['status'=>sanitize_text_field($value),'time'=>current_time('mysql'),'by'=>get_current_user_id()];
        update_post_meta($post_id,'tager_v11_timeline',$timeline);
        $email = sanitize_email(get_post_meta($post_id,'email',true));
        self::notify($email,'Tager order #'.$post_id.' updated','Your order status is now: '.$value.'.');
    }

    private static function notify($email,$subject,$message) {
        if (is_email($email)) wp_mail($email, wp_specialchars_decode($subject), wp_strip_all_tags($message));
    }

    public static function repair() {
        if (!current_user_can('manage_options')) wp_die('Not allowed'); check_admin_referer('tager_v11_repair');
        self::repair_roles(); self::repair_pages(); flush_rewrite_rules(false);
        wp_safe_redirect(admin_url('admin.php?page=tager-v11-command&repaired=1')); exit;
    }

    public static function account_hub() {
        if (!is_user_logged_in()) return '<section class="tv11-account"><h1>'.esc_html(self::t('مركز الحساب','Account hub')).'</h1><p>'.esc_html(self::t('سجّل الدخول لعرض حسابك وطلباتك.','Sign in to view your account and orders.')).'</p>'.wp_login_form(['echo'=>false,'redirect'=>self::page_url('account-hub')]).'</section>';
        $u = wp_get_current_user(); $is_vendor = in_array('tager_vendor',$u->roles,true) || in_array('tager_vendor_pending',$u->roles,true);
        $orders = get_posts(['post_type'=>'tager_order','post_status'=>'publish','author'=>$u->ID,'numberposts'=>10,'orderby'=>'date','order'=>'DESC']);
        ob_start(); echo '<section class="tv11-account"><div class="tv11-account-head"><div><span>'.esc_html(self::t('مرحبًا','Welcome')).'</span><h1>'.esc_html($u->display_name).'</h1><p>'.esc_html($u->user_email).'</p></div><div class="tv11-account-actions"><a class="btn primary" href="'.esc_url($is_vendor?self::page_url('vendor-dashboard'):self::page_url('shop')).'">'.esc_html($is_vendor?self::t('لوحة المورد','Vendor dashboard'):self::t('تصفح المنتجات','Browse products')).'</a><a class="btn secondary" href="'.esc_url(wp_logout_url(home_url('/'))).'">'.esc_html(self::t('تسجيل الخروج','Sign out')).'</a></div></div>';
        echo '<div class="tv11-account-cards"><div><b>'.count($orders).'</b><span>'.esc_html(self::t('آخر الطلبات','Recent orders')).'</span></div><div><b>'.esc_html($is_vendor ? get_user_meta($u->ID,'vendor_status',true) : 'Customer').'</b><span>'.esc_html(self::t('نوع الحساب','Account type')).'</span></div></div>';
        echo '<div class="tv11-account-table"><h2>'.esc_html(self::t('الطلبات','Orders')).'</h2>';
        if (!$orders) echo '<p>'.esc_html(self::t('لا توجد طلبات حتى الآن.','No orders yet.')).'</p>';
        else { echo '<table><thead><tr><th>#</th><th>'.esc_html(self::t('الإجمالي','Total')).'</th><th>'.esc_html(self::t('الحالة','Status')).'</th><th>'.esc_html(self::t('التاريخ','Date')).'</th></tr></thead><tbody>'; foreach($orders as $o) echo '<tr><td>#'.$o->ID.'</td><td>'.esc_html(number_format_i18n((float)get_post_meta($o->ID,'total',true),2)).' EGP</td><td>'.esc_html(get_post_meta($o->ID,'order_status',true)).'</td><td>'.esc_html(get_the_date('', $o)).'</td></tr>'; echo '</tbody></table>'; }
        echo '</div></section>'; return ob_get_clean();
    }

    private static function admin_css() { return <<<'CSS'
.tager-v11-admin{max-width:1500px}.tv11-head{display:flex;justify-content:space-between;align-items:center;gap:20px;margin:18px 0}.tv11-head h1{margin:0 0 6px}.tv11-head p{margin:0;color:#646970}.tv11-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.tv11-kpi,.tv11-panel{background:#fff;border:1px solid #dcdcde;border-radius:16px;box-shadow:0 6px 24px rgba(0,0,0,.04)}.tv11-kpi{display:flex;align-items:center;gap:14px;padding:20px}.tv11-kpi .dashicons{width:42px;height:42px;font-size:26px;display:grid;place-items:center;background:#eef7f2;border-radius:12px;color:#146c43}.tv11-kpi b{display:block;font-size:25px;line-height:1.1}.tv11-kpi small{display:block;color:#646970;margin-top:5px}.tv11-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.tv11-panel{padding:20px;margin:16px 0}.tv11-panel h2{margin:0 0 15px}.tv11-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 0;border-top:1px solid #eee}.tv11-row:first-of-type{border-top:0}.tv11-row small{display:block;color:#646970;margin-top:3px}.tv11-actions{display:flex;gap:7px;flex-wrap:wrap}.tv11-table{overflow:auto}.tv11-table table{width:100%;border-collapse:collapse}.tv11-table th,.tv11-table td{padding:12px;border-bottom:1px solid #eee;text-align:left}.tv11-table form{display:flex;gap:7px}.tv11-status{background:#eef7f2;color:#146c43;padding:5px 9px;border-radius:20px;font-weight:600}.tv11-health{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.tv11-health>div{padding:13px;border-radius:12px;display:grid;grid-template-columns:28px 1fr;align-items:center;background:#f6f7f7}.tv11-health small{grid-column:2;color:#646970}.tv11-health .ok .dashicons{color:#008a20}.tv11-health .bad .dashicons{color:#d63638}@media(max-width:900px){.tv11-kpis{grid-template-columns:1fr 1fr}.tv11-grid{grid-template-columns:1fr}.tv11-health{grid-template-columns:1fr 1fr}}@media(max-width:580px){.tv11-kpis,.tv11-health{grid-template-columns:1fr}.tv11-head{align-items:flex-start;flex-direction:column}}
CSS; }

    private static function front_css() { return <<<'CSS'
.tv11-account{max-width:1120px;margin:35px auto;padding:0 18px}.tv11-account-head{display:flex;justify-content:space-between;align-items:center;gap:20px;background:linear-gradient(135deg,#0f5132,#1f7a50);color:#fff;padding:30px;border-radius:22px}.tv11-account-head h1{font-size:34px;margin:4px 0}.tv11-account-head p{margin:0;opacity:.86}.tv11-account-actions{display:flex;gap:10px;flex-wrap:wrap}.tv11-account-actions .secondary{background:#fff;color:#164f37}.tv11-account-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin:18px 0}.tv11-account-cards>div,.tv11-account-table{background:#fff;border:1px solid #e7e7e7;border-radius:17px;padding:20px;box-shadow:0 8px 25px rgba(0,0,0,.04)}.tv11-account-cards b{display:block;font-size:27px}.tv11-account-cards span{color:#6b7280}.tv11-account-table{overflow:auto}.tv11-account-table table{width:100%;border-collapse:collapse}.tv11-account-table th,.tv11-account-table td{padding:13px;border-bottom:1px solid #eee;text-align:start}@media(max-width:680px){.tv11-account-head{align-items:flex-start;flex-direction:column}.tv11-account-cards{grid-template-columns:1fr}}
CSS; }
}
Tager_V11_Command_Center::init();
