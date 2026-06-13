<?php
/**
 * Plugin Name: Tager V26 Operations & Commercial Control
 * Description: Advanced admin dashboard for orders, payment proof review, vendor minimums, shipping readiness, and commercial controls.
 * Version: 26.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V26_Operations {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu'], 130);
        add_action('admin_post_tager_v26_order_status', [__CLASS__, 'update_order_status']);
        add_action('admin_post_tager_v26_vendor_min', [__CLASS__, 'save_vendor_minimums']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'frontend_assets'], 170);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_footer', [__CLASS__, 'floating_support']);
    }

    public static function menu() {
        add_submenu_page('tager-control', 'V26 Operations', 'V26 Operations', 'manage_options', 'tager-v26-operations', [__CLASS__, 'dashboard']);
        add_submenu_page('tager-control', 'Order Review', 'Order Review', 'manage_options', 'tager-v26-orders', [__CLASS__, 'orders_page']);
        add_submenu_page('tager-control', 'Vendor Minimums', 'Vendor Minimums', 'manage_options', 'tager-v26-vendor-minimums', [__CLASS__, 'vendor_minimums_page']);
    }

    private static function orders($limit = 100) {
        return get_posts([
            'post_type' => 'tager_order',
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    private static function money($value) {
        return number_format((float)$value, 2) . ' EGP';
    }

    public static function dashboard() {
        if (!current_user_can('manage_options')) return;
        $orders = self::orders(500);
        $sales = 0; $awaiting = 0; $new = 0; $completed = 0; $proofs = 0;
        foreach ($orders as $order) {
            $sales += (float)get_post_meta($order->ID, 'total', true);
            $status = (string)get_post_meta($order->ID, 'order_status', true);
            if ($status === 'Awaiting payment') $awaiting++;
            if ($status === 'New') $new++;
            if ($status === 'Completed') $completed++;
            if ((int)get_post_meta($order->ID, 'payment_proof_attachment', true) > 0) $proofs++;
        }
        $vendors = get_users(['role' => 'vendor']);
        $pending_products = (int)wp_count_posts('tager_product')->pending;
        ?>
        <div class="wrap tager-v26-admin">
            <h1>Tager V26 — Operations Command Center</h1>
            <p>Monitor orders, payment verification, vendor rules and commercial readiness from one place.</p>
            <div class="t26-stat-grid">
                <div class="t26-stat"><span>Total sales</span><strong><?php echo esc_html(self::money($sales)); ?></strong></div>
                <div class="t26-stat"><span>Total orders</span><strong><?php echo count($orders); ?></strong></div>
                <div class="t26-stat"><span>New orders</span><strong><?php echo $new; ?></strong></div>
                <div class="t26-stat"><span>Awaiting payment</span><strong><?php echo $awaiting; ?></strong></div>
                <div class="t26-stat"><span>Completed orders</span><strong><?php echo $completed; ?></strong></div>
                <div class="t26-stat"><span>Payment proofs</span><strong><?php echo $proofs; ?></strong></div>
                <div class="t26-stat"><span>Vendors</span><strong><?php echo count($vendors); ?></strong></div>
                <div class="t26-stat"><span>Products pending</span><strong><?php echo $pending_products; ?></strong></div>
            </div>
            <div class="t26-actions">
                <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=tager-v26-orders')); ?>">Review orders</a>
                <a class="button button-hero" href="<?php echo esc_url(admin_url('admin.php?page=tager-v26-vendor-minimums')); ?>">Vendor minimums</a>
                <a class="button button-hero" href="<?php echo esc_url(admin_url('admin.php?page=tager-v24-payments')); ?>">Shipping & payment pricing</a>
            </div>
            <div class="t26-card"><h2>Latest orders</h2><?php self::orders_table(array_slice($orders, 0, 12)); ?></div>
        </div>
        <?php
    }

    private static function status_badge($status) {
        $class = sanitize_html_class(strtolower(str_replace(' ', '-', $status ?: 'new')));
        return '<span class="t26-badge '.$class.'">'.esc_html($status ?: 'New').'</span>';
    }

    private static function orders_table($orders) {
        if (!$orders) { echo '<p>No orders found.</p>'; return; }
        ?>
        <div class="t26-table-wrap"><table class="widefat striped t26-table"><thead><tr><th>Order</th><th>Customer</th><th>Governorate</th><th>Payment</th><th>Delivery</th><th>Total</th><th>Status</th><th>Proof</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($orders as $order):
            $proof = (int)get_post_meta($order->ID, 'payment_proof_attachment', true);
            $status = (string)get_post_meta($order->ID, 'order_status', true);
        ?>
        <tr>
            <td><strong>#<?php echo $order->ID; ?></strong><br><small><?php echo esc_html(get_the_date('Y-m-d H:i', $order)); ?></small></td>
            <td><?php echo esc_html(get_post_meta($order->ID, 'customer_name', true)); ?><br><small><?php echo esc_html(get_post_meta($order->ID, 'phone', true)); ?></small></td>
            <td><?php echo esc_html(get_post_meta($order->ID, 'governorate', true)); ?></td>
            <td><?php echo esc_html(get_post_meta($order->ID, 'payment_method_label', true)); ?><br><small><?php echo esc_html(get_post_meta($order->ID, 'payment_status', true)); ?></small></td>
            <td><?php echo esc_html(get_post_meta($order->ID, 'delivery_method', true) ?: 'standard'); ?><br><small><?php echo esc_html(get_post_meta($order->ID, 'preferred_delivery_date', true)); ?></small></td>
            <td><strong><?php echo esc_html(self::money(get_post_meta($order->ID, 'total', true))); ?></strong></td>
            <td><?php echo self::status_badge($status); ?></td>
            <td><?php if ($proof): ?><a class="button button-small" target="_blank" href="<?php echo esc_url(wp_get_attachment_url($proof)); ?>">Open proof</a><?php else: ?>—<?php endif; ?></td>
            <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="t26-inline-form">
                    <input type="hidden" name="action" value="tager_v26_order_status"><input type="hidden" name="order_id" value="<?php echo $order->ID; ?>">
                    <?php wp_nonce_field('tager_v26_order_status_'.$order->ID); ?>
                    <select name="order_status">
                        <?php foreach (['New','Awaiting payment','Confirmed','Processing','Ready to ship','Shipped','Completed','Cancelled','Refunded'] as $s): ?><option value="<?php echo esc_attr($s); ?>" <?php selected($status,$s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?>
                    </select><button class="button button-primary button-small">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?></tbody></table></div>
        <?php
    }

    public static function orders_page() {
        if (!current_user_can('manage_options')) return;
        $orders = self::orders(500);
        $filter = sanitize_text_field($_GET['status'] ?? '');
        if ($filter) $orders = array_values(array_filter($orders, fn($o) => get_post_meta($o->ID, 'order_status', true) === $filter));
        ?>
        <div class="wrap tager-v26-admin"><h1>Order Review & Payment Verification</h1>
            <div class="t26-filters"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tager-v26-orders')); ?>">All</a><?php foreach(['New','Awaiting payment','Confirmed','Processing','Shipped','Completed','Cancelled'] as $s): ?><a class="button" href="<?php echo esc_url(add_query_arg(['page'=>'tager-v26-orders','status'=>$s],admin_url('admin.php'))); ?>"><?php echo esc_html($s); ?></a><?php endforeach; ?></div>
            <div class="t26-card"><?php self::orders_table($orders); ?></div>
        </div><?php
    }

    public static function update_order_status() {
        $id = absint($_POST['order_id'] ?? 0);
        if (!current_user_can('manage_options') || !$id) wp_die('No permission');
        check_admin_referer('tager_v26_order_status_'.$id);
        $allowed = ['New','Awaiting payment','Confirmed','Processing','Ready to ship','Shipped','Completed','Cancelled','Refunded'];
        $status = sanitize_text_field($_POST['order_status'] ?? 'New');
        if (!in_array($status, $allowed, true)) $status = 'New';
        update_post_meta($id, 'order_status', $status);
        update_post_meta($id, 'status_updated_at', current_time('mysql'));
        update_post_meta($id, 'status_updated_by', get_current_user_id());
        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=tager-v26-orders'));
        exit;
    }

    public static function vendor_minimums_page() {
        if (!current_user_can('manage_options')) return;
        $vendors = get_users(['role' => 'vendor', 'orderby' => 'display_name']);
        ?>
        <div class="wrap tager-v26-admin"><h1>Vendor Minimum Order Rules</h1><p>Set the minimum subtotal required from each vendor in a regular vendor cart. Mixed carts can bypass these limits and use the configured mixed-cart fee.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v26_vendor_min"><?php wp_nonce_field('tager_v26_vendor_min'); ?>
        <div class="t26-card"><table class="widefat striped"><thead><tr><th>Vendor</th><th>Email</th><th>Store name</th><th>Minimum order (EGP)</th></tr></thead><tbody>
        <?php foreach($vendors as $vendor): ?><tr><td><?php echo esc_html($vendor->display_name); ?></td><td><?php echo esc_html($vendor->user_email); ?></td><td><?php echo esc_html(get_user_meta($vendor->ID,'store_name',true)); ?></td><td><input type="number" min="0" step="0.01" name="minimums[<?php echo $vendor->ID; ?>]" value="<?php echo esc_attr((float)get_user_meta($vendor->ID,'tager_vendor_min_order',true)); ?>"></td></tr><?php endforeach; ?>
        </tbody></table></div><p><button class="button button-primary button-large">Save vendor minimums</button></p></form></div>
        <?php
    }

    public static function save_vendor_minimums() {
        if (!current_user_can('manage_options')) wp_die('No permission');
        check_admin_referer('tager_v26_vendor_min');
        foreach ((array)($_POST['minimums'] ?? []) as $vendor_id => $value) {
            $vendor_id = absint($vendor_id);
            if ($vendor_id) update_user_meta($vendor_id, 'tager_vendor_min_order', max(0, (float)$value));
        }
        wp_safe_redirect(admin_url('admin.php?page=tager-v26-vendor-minimums&updated=1'));
        exit;
    }

    public static function admin_assets() {
        wp_register_style('tager-v26-admin', false); wp_enqueue_style('tager-v26-admin');
        wp_add_inline_style('tager-v26-admin', '.tager-v26-admin{max-width:1500px}.t26-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:16px;margin:24px 0}.t26-stat,.t26-card{background:#fff;border:1px solid #dfe5df;border-radius:16px;padding:18px;box-shadow:0 8px 26px rgba(23,63,53,.06)}.t26-stat span{display:block;color:#65736d;font-weight:600}.t26-stat strong{display:block;font-size:25px;color:#173f35;margin-top:8px}.t26-actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}.t26-card{margin-top:18px}.t26-table-wrap{overflow:auto}.t26-table{min-width:1100px}.t26-badge{display:inline-flex;padding:5px 9px;border-radius:99px;background:#edf3ef;color:#173f35;font-weight:700}.t26-badge.awaiting-payment{background:#fff1cf;color:#8a5b00}.t26-badge.completed{background:#dcf7e8;color:#12603b}.t26-badge.cancelled,.t26-badge.refunded{background:#ffe1e1;color:#922}.t26-inline-form{display:flex;gap:6px;align-items:center}.t26-filters{display:flex;gap:8px;flex-wrap:wrap;margin:15px 0}@media(max-width:900px){.t26-stat-grid{grid-template-columns:repeat(2,1fr)}}');
    }

    public static function frontend_assets() {
        wp_add_inline_style('tager-style', '.t26-floating-support{position:fixed;right:18px;bottom:88px;z-index:9999;background:#173f35;color:#fff!important;border-radius:999px;padding:12px 16px;text-decoration:none!important;box-shadow:0 12px 30px rgba(0,0,0,.22);font-weight:800}.t26-floating-support:hover{transform:translateY(-2px);background:#0f2f28}.v24-panel input:focus,.v24-panel select:focus,.v24-panel textarea:focus{outline:3px solid rgba(197,155,66,.18);border-color:#c59b42}.v24-row button{border:0;background:#fff0ed;color:#9d2c1f;border-radius:10px;width:34px;height:34px;font-size:20px;cursor:pointer}.v24-row button:hover{background:#ffdcd6}.v24-payment,.btn,.v24-row button{transition:.18s ease}.v24-payment:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(23,63,53,.08)}');
    }

    public static function floating_support() {
        if (is_admin()) return;
        $phone = preg_replace('/\D+/', '', (string)get_option('tager_support_whatsapp', '201000000000'));
        if (!$phone) return;
        echo '<a class="t26-floating-support" target="_blank" rel="noopener" href="'.esc_url('https://wa.me/'.$phone).'">💬 '.esc_html__('Support','tager').'</a>';
    }
}
Tager_V26_Operations::init();
