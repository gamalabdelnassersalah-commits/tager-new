<?php
/**
 * Plugin Name: Tager V59 Page Data Mapping
 * Description: Corrects every page so its displayed data and buttons match the page purpose; removes mismatched legacy content.
 * Version: 59.0.0
 */
if (!defined('ABSPATH')) exit;
final class Tager_V59_Page_Data_Mapping {
 const VER='59.0.0';
 public static function init(){
  add_action('init',[__CLASS__,'repair_once'],5000);
  add_action('admin_menu',[__CLASS__,'menu'],5000);
  add_action('admin_post_tager_v59_repair',[__CLASS__,'manual']);
  add_filter('the_content',[__CLASS__,'guard_content'],99999);
 }
 public static function pages(){ return [
  'home'=>['الرئيسية','[tager_v56_home]','public'],
  'market'=>['السوق','[tager_v48_market]','public'],
  'vendors'=>['دليل الموردين','[tager_v48_vendor_directory]','public'],
  'login'=>['تسجيل الدخول','[tager_v57_login]','guest'],
  'choose-account'=>['اختيار نوع الحساب','[tager_v57_choose_account]','guest'],
  'customer-register'=>['تسجيل عميل','[tager_v57_customer_register]','guest'],
  'vendor-register'=>['تسجيل مورد','[tager_v57_vendor_register]','guest'],
  'forgot-password'=>['نسيت كلمة المرور','[tager_v57_forgot_password]','guest'],
  'phone-password-reset'=>['استعادة كلمة المرور بالهاتف','[tager_v45_phone_reset]','guest'],
  'customer-home'=>['حساب العميل','[tager_v55_customer_portal]','customer'],
  'customer-profile'=>['بيانات العميل','[tager_v55_customer_profile]','customer'],
  'customer-addresses'=>['عناوين العميل','[tager_v55_customer_addresses]','customer'],
  'customer-orders'=>['طلبات العميل','[tager_v42_customer_orders]','customer'],
  'customer-order-details'=>['تفاصيل الطلب','[tager_v49_customer_order_details]','customer'],
  'customer-security'=>['أمان الحساب','[tager_v49_customer_security]','customer'],
  'customer-activity'=>['نشاط العميل','[tager_v50_customer_activity]','customer'],
  'saved-carts'=>['السلات المحفوظة','[tager_v49_saved_carts]','customer'],
  'wishlist'=>['المفضلة','[tager_pro_wishlist]','customer'],
  'notifications'=>['الإشعارات','[tager_v42_notifications]','member'],
  'vendor-home'=>['لوحة المورد','[tager_v55_vendor_portal]','vendor'],
  'vendor-products'=>['منتجات المورد','[tager_v55_vendor_products]','vendor'],
  'vendor-add-product'=>['إضافة أو تعديل منتج','[tager_v54_vendor_product_form]','vendor'],
  'vendor-orders'=>['طلبات المورد','[tager_v42_vendor_orders]','vendor'],
  'vendor-earnings'=>['أرباح وعمولات المورد','[tager_v55_vendor_earnings]','vendor'],
  'vendor-inventory'=>['مخزون المورد','[tager_v49_vendor_inventory]','vendor'],
  'vendor-market'=>['سوق المورد','[tager_v39_vendor_market]','vendor'],
  'vendor-location-settings'=>['موقع وخدمة المورد','[tager_v47_vendor_location_settings]','vendor'],
  'vendor-media'=>['صور وهوية المورد','[tager_v44_profile_studio]','vendor'],
  'product-media'=>['صور المنتجات','[tager_v44_product_media_studio]','vendor'],
  'vendor-performance'=>['أداء المورد','[tager_v41_vendor_performance]','vendor'],
  'vendor-product-quality'=>['اكتمال بيانات المنتجات','[tager_v50_product_quality]','vendor'],
  'admin-home'=>['بوابة الإدارة','[tager_v55_admin_portal]','admin'],
  'admin-approvals'=>['مركز الموافقات','[tager_v42_admin_approvals]','admin'],
  'admin-shipping'=>['إعدادات الشحن','[tager_v49_admin_shipping]','admin'],
  'admin-payments'=>['إعدادات الدفع','[tager_v49_admin_payments]','admin'],
  'admin-team'=>['فريق الإدارة','[tager_v49_admin_team]','admin'],
  'admin-reports'=>['التقارير','[tager_v49_admin_reports]','admin'],
  'cart'=>['السلة','[tager_v24_cart]','public'],
  'checkout'=>['إتمام الطلب','[tager_v57_checkout]','customer'],
  'product-details'=>['تفاصيل المنتج','[tager_v53_product_details]','public'],
  'vendor-details'=>['صفحة المورد','[tager_v53_vendor_details]','public'],
  'support'=>['الدعم والمساعدة','[tager_support]','member'],
  'tracking'=>['تتبع الطلب','[tager_v8_tracking]','customer'],
  'returns'=>['المرتجعات','[tager_v7_returns]','customer'],
  'rfq'=>['طلب عرض سعر','[tager_v7_rfq]','customer'],
  'invoices'=>['الفواتير','[tager_v7_invoices]','customer'],
  'compare'=>['مقارنة المنتجات','[tager_v7_compare]','public'],
  'payment-methods'=>['طرق الدفع','[tager_v57_info type="payments"]','public'],
  'shipping'=>['الشحن والتوصيل','[tager_v57_info type="shipping"]','public'],
  'return-policy'=>['سياسة الاسترجاع','[tager_v57_info type="returns"]','public'],
  'terms'=>['الشروط والأحكام','[tager_v57_info type="terms"]','public'],
  'privacy'=>['سياسة الخصوصية','[tager_v57_info type="privacy"]','public'],
  'contact'=>['تواصل معنا','[tager_v57_info type="contact"]','public'],
  'site-map'=>['خريطة الموقع','[tager_v49_sitemap]','public'],
 ]; }
 static function valid($content){ if(!preg_match('/\[([a-zA-Z0-9_\-]+)/',$content,$m)) return false; return shortcode_exists($m[1]); }
 public static function repair_once(){ if(get_option('tager_v59_repaired')===self::VER) return; self::repair(); update_option('tager_v59_repaired',self::VER); }
 public static function repair(){
  foreach(self::pages() as $slug=>$d){
   $p=get_page_by_path($slug,OBJECT,'page');
   $data=['post_type'=>'page','post_status'=>'publish','post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1]];
   if($p){$data['ID']=$p->ID;wp_update_post($data);} else wp_insert_post($data);
  }
  $home=get_page_by_path('home',OBJECT,'page'); if($home){update_option('show_on_front','page');update_option('page_on_front',$home->ID);} flush_rewrite_rules(false);
 }
 public static function guard_content($content){
  if(!is_page()) return $content; $slug=get_post_field('post_name',get_the_ID()); $defs=self::pages();
  if(!isset($defs[$slug])) return $content;
  $expected=$defs[$slug][1];
  if(trim($content)!==trim($expected) || !self::valid($content)) return do_shortcode($expected);
  return $content;
 }
 public static function manual(){ if(!current_user_can('manage_options')) wp_die('غير مصرح');check_admin_referer('tager_v59_repair');delete_option('tager_v59_repaired');self::repair();update_option('tager_v59_repaired',self::VER);wp_safe_redirect(admin_url('admin.php?page=tager-v59&repaired=1'));exit; }
 public static function menu(){ add_menu_page('Tager V59','Tager V59','manage_options','tager-v59',[__CLASS__,'screen'],'dashicons-yes-alt',2); }
 public static function screen(){ if(!current_user_can('manage_options'))return; $rows=[];foreach(self::pages() as $slug=>$d){$p=get_page_by_path($slug,OBJECT,'page');$actual=$p?trim($p->post_content):'';$ok=$p&&$actual===trim($d[1])&&self::valid($actual);$rows[]=[$slug,$d[0],$d[2],$d[1],$p,$ok];}
  echo '<div class="wrap"><h1>Tager V59 — مراجعة بيانات كل صفحة</h1><p>يعيد هذا الإصدار ربط كل صفحة بالمحتوى الصحيح الخاص بها، ويمنع ظهور بيانات عميل داخل صفحة مورد أو بيانات إدارة داخل صفحة عامة.</p><p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v59_repair'),'tager_v59_repair')).'">إصلاح وربط جميع الصفحات الآن</a></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>النوع</th><th>المحتوى الصحيح</th><th>الحالة</th><th>فتح</th></tr></thead><tbody>';
  foreach($rows as $r){echo '<tr><td><strong>'.esc_html($r[1]).'</strong></td><td><code>/'.esc_html($r[0]).'/</code></td><td>'.esc_html($r[2]).'</td><td><code>'.esc_html($r[3]).'</code></td><td style="color:'.($r[5]?'green':'#b42318').'">'.($r[5]?'مطابقة':'تحتاج إصلاح').'</td><td>'.($r[4]?'<a target="_blank" href="'.esc_url(get_permalink($r[4])).'">فتح</a>':'—').'</td></tr>';}
  echo '</tbody></table></div>';
 }
}
Tager_V59_Page_Data_Mapping::init();
