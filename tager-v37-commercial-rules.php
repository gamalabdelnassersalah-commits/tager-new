<?php
/**
 * Plugin Name: Tager V37 Vendor Commission & Premium Cart Rules
 * Description: Per-vendor sales commission, per-vendor premium mixed-cart fee, settlement previews and auditable commercial rules.
 * Version: 37.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V37_Commercial_Rules {
    public static function init(){
        add_action('admin_menu',[__CLASS__,'menu'],150);
        add_action('admin_post_tager_v37_save_rules',[__CLASS__,'save']);
        add_action('admin_post_tager_v37_reset_vendor',[__CLASS__,'reset_vendor']);
    }
    private static function vendors(){
        $users=get_users(['orderby'=>'display_name','order'=>'ASC']);
        return array_values(array_filter($users,function($u){
            $roles=(array)$u->roles;
            if(array_intersect($roles,['tager_vendor','vendor','wcfm_vendor'])) return true;
            return (bool)get_user_meta($u->ID,'store_name',true);
        }));
    }
    private static function global_commission(){
        $s=wp_parse_args((array)get_option('tager_settings',[]),['commission_percent'=>10]);
        return max(0,min(100,(float)$s['commission_percent']));
    }
    private static function global_mixed_fee(){
        $s=wp_parse_args((array)get_option('tager_v24_settings',[]),['mixed_cart_fee_percent'=>1.5]);
        return max(0,min(20,(float)$s['mixed_cart_fee_percent']));
    }
    private static function money($n){return number_format((float)$n,2).' EGP';}
    private static function totals($vendor_id){
        $subs=get_posts(['post_type'=>'tager_suborder','post_status'=>'any','author'=>(int)$vendor_id,'numberposts'=>-1,'fields'=>'ids']);
        $gross=$commission=$net=0;
        foreach($subs as $id){$gross+=(float)get_post_meta($id,'gross_total',true);$commission+=(float)get_post_meta($id,'platform_commission',true);$net+=(float)get_post_meta($id,'vendor_net',true);}return compact('gross','commission','net');
    }
    public static function menu(){
        add_submenu_page('tager-control','Vendor Commercial Rules','عمولات ورسوم الموردين','manage_options','tager-v37-commercial',[__CLASS__,'page']);
    }
    public static function page(){
        if(!current_user_can('manage_options')) return;
        $vendors=self::vendors();$gc=self::global_commission();$gm=self::global_mixed_fee();
        ?>
        <div class="wrap"><h1>عمولات ورسوم الموردين</h1>
        <p>حدّد نسبة المنصة من إجمالي مبيعات كل مورد، وحدّد رسوم السلة المميزة لكل مورد. ترك الخانة فارغة يعني استخدام النسبة العامة.</p>
        <?php if(!empty($_GET['updated'])) echo '<div class="notice notice-success"><p>تم حفظ القواعد بنجاح.</p></div>'; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="tager_v37_save_rules"><?php wp_nonce_field('tager_v37_save_rules'); ?>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(260px,1fr));gap:16px;margin:18px 0">
          <div class="postbox" style="padding:20px"><h2>النسبة العامة لعمولة المنصة</h2><input type="number" min="0" max="100" step="0.01" name="global_commission" value="<?php echo esc_attr($gc); ?>"> %<p class="description">تُستخدم لأي مورد ليس له نسبة مخصصة.</p></div>
          <div class="postbox" style="padding:20px"><h2>النسبة العامة للسلة المميزة</h2><input type="number" min="0" max="20" step="0.01" name="global_mixed_fee" value="<?php echo esc_attr($gm); ?>"> %<p class="description">القيمة الافتراضية 1.5% ويمكن تعديلها في أي وقت.</p></div>
        </div>
        <div style="overflow:auto;background:#fff;border:1px solid #dcdcde;border-radius:10px"><table class="widefat striped"><thead><tr><th>المورد</th><th>المتجر</th><th>عمولة المنصة %</th><th>رسوم السلة المميزة %</th><th>إجمالي المبيعات</th><th>عمولة مسجلة</th><th>صافي المورد</th></tr></thead><tbody>
        <?php if(!$vendors): ?><tr><td colspan="7">لا يوجد موردون بعد.</td></tr><?php endif; ?>
        <?php foreach($vendors as $v): $cr=get_user_meta($v->ID,'tager_vendor_commission_percent',true);$mr=get_user_meta($v->ID,'tager_vendor_mixed_fee_percent',true);$t=self::totals($v->ID); ?>
        <tr><td><strong><?php echo esc_html($v->display_name); ?></strong><br><small>#<?php echo (int)$v->ID; ?></small></td><td><?php echo esc_html(get_user_meta($v->ID,'store_name',true)?:'—'); ?></td>
        <td><input type="number" min="0" max="100" step="0.01" name="vendors[<?php echo (int)$v->ID; ?>][commission]" value="<?php echo esc_attr($cr); ?>" placeholder="<?php echo esc_attr($gc); ?>"></td>
        <td><input type="number" min="0" max="20" step="0.01" name="vendors[<?php echo (int)$v->ID; ?>][mixed_fee]" value="<?php echo esc_attr($mr); ?>" placeholder="<?php echo esc_attr($gm); ?>"></td>
        <td><?php echo esc_html(self::money($t['gross'])); ?></td><td><?php echo esc_html(self::money($t['commission'])); ?></td><td><?php echo esc_html(self::money($t['net'])); ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <p><button class="button button-primary button-large">حفظ جميع النسب</button></p></form>
        <div class="notice notice-info inline"><p><strong>طريقة الحساب:</strong> عمولة المنصة = إجمالي مبيعات المورد × نسبة المورد. صافي المورد = إجمالي المبيعات − عمولة المنصة. رسوم السلة المميزة تُحسب على منتجات كل مورد حسب نسبته، ثم تُجمع في الطلب.</p></div>
        </div><?php
    }
    public static function save(){
        if(!current_user_can('manage_options')) wp_die('No permission');check_admin_referer('tager_v37_save_rules');
        $global_commission=max(0,min(100,(float)($_POST['global_commission']??10)));$base=wp_parse_args((array)get_option('tager_settings',[]),['commission_percent'=>10]);$base['commission_percent']=$global_commission;update_option('tager_settings',$base);
        $global_mixed=max(0,min(20,(float)($_POST['global_mixed_fee']??1.5)));$pay=(array)get_option('tager_v24_settings',[]);$pay['mixed_cart_fee_percent']=$global_mixed;update_option('tager_v24_settings',$pay);
        foreach((array)($_POST['vendors']??[]) as $uid=>$row){$uid=(int)$uid;if(!$uid)continue;$c=trim((string)($row['commission']??''));$m=trim((string)($row['mixed_fee']??''));if($c==='')delete_user_meta($uid,'tager_vendor_commission_percent');else update_user_meta($uid,'tager_vendor_commission_percent',max(0,min(100,(float)$c)));if($m==='')delete_user_meta($uid,'tager_vendor_mixed_fee_percent');else update_user_meta($uid,'tager_vendor_mixed_fee_percent',max(0,min(20,(float)$m)));}
        wp_safe_redirect(admin_url('admin.php?page=tager-v37-commercial&updated=1'));exit;
    }
    public static function reset_vendor(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer('tager_v37_reset_vendor');$uid=(int)($_GET['vendor']??0);delete_user_meta($uid,'tager_vendor_commission_percent');delete_user_meta($uid,'tager_vendor_mixed_fee_percent');wp_safe_redirect(admin_url('admin.php?page=tager-v37-commercial&updated=1'));exit;}
}
Tager_V37_Commercial_Rules::init();
