<?php
/**
 * Plugin Name: Tager V36 Data Integrity & Full Workflows
 * Description: Reliable phone-first registration, persistent customer/vendor profiles, product CRUD, approvals, and live database diagnostics.
 * Version: 36.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V36_Data_Integrity {
    const VERSION = '36.0.0';
    const NONCE = 'tager_v36_nonce';

    public static function init() {
        add_action('init', [__CLASS__, 'register_types'], 1);
        add_action('init', [__CLASS__, 'override_shortcodes'], 9999);
        add_action('admin_post_nopriv_tager_customer_register', [__CLASS__, 'register_customer'], 0);
        add_action('admin_post_nopriv_tager_vendor_apply', [__CLASS__, 'register_vendor'], 0);
        add_action('admin_post_tager_v36_save_customer', [__CLASS__, 'save_customer']);
        add_action('admin_post_tager_v36_save_vendor', [__CLASS__, 'save_vendor']);
        add_action('admin_post_tager_v36_save_product', [__CLASS__, 'save_product']);
        add_action('admin_post_tager_v36_delete_product', [__CLASS__, 'delete_product']);
        add_action('admin_post_tager_v36_vendor_approval', [__CLASS__, 'vendor_approval']);
        add_action('admin_post_tager_v36_product_approval', [__CLASS__, 'product_approval']);
        add_action('admin_post_tager_v36_run_tests', [__CLASS__, 'run_tests']);
        add_filter('authenticate', [__CLASS__, 'phone_login'], 1, 3);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 9999);
    }

    public static function register_types() {
        if (!post_type_exists('tager_product')) {
            register_post_type('tager_product', [
                'labels' => ['name'=>'منتجات تاجر','singular_name'=>'منتج'],
                'public'=>true,'show_ui'=>true,'show_in_menu'=>true,'supports'=>['title','editor','thumbnail','author'],
                'rewrite'=>['slug'=>'product'],'has_archive'=>true,'menu_icon'=>'dashicons-products'
            ]);
        }
        if (!post_type_exists('tager_order')) {
            register_post_type('tager_order', [
                'labels'=>['name'=>'طلبات تاجر','singular_name'=>'طلب'],
                'public'=>false,'show_ui'=>true,'supports'=>['title','author'],'menu_icon'=>'dashicons-cart'
            ]);
        }
        if (!get_role('tager_customer')) add_role('tager_customer','Tager Customer',['read'=>true,'upload_files'=>true]);
        if (!get_role('tager_vendor_pending')) add_role('tager_vendor_pending','Tager Vendor Pending',['read'=>true,'upload_files'=>true]);
        if (!get_role('tager_vendor')) add_role('tager_vendor','Tager Vendor',['read'=>true,'upload_files'=>true]);
    }

    public static function override_shortcodes() {
        foreach (['tager_customer_register','tager_vendor_register','tager_customer_account','tager_vendor_dashboard'] as $s) remove_shortcode($s);
        add_shortcode('tager_customer_register',[__CLASS__,'customer_register_form']);
        add_shortcode('tager_vendor_register',[__CLASS__,'vendor_register_form']);
        add_shortcode('tager_customer_account',[__CLASS__,'customer_account']);
        add_shortcode('tager_vendor_dashboard',[__CLASS__,'vendor_dashboard']);
    }

    private static function url($slug) { $p=get_page_by_path($slug); return $p?get_permalink($p):home_url('/'.$slug.'/'); }
    private static function phone($raw) {
        $p=preg_replace('/\D+/','',(string)$raw);
        if (strpos($p,'0020')===0) $p=substr($p,4);
        if (strpos($p,'20')===0 && strlen($p)===12) $p=substr($p,2);
        if (strlen($p)===10 && substr($p,0,1)==='1') $p='0'.$p;
        return $p;
    }
    private static function valid_phone($p) { return (bool)preg_match('/^01[0125]\d{8}$/',$p); }
    private static function user_by_phone($p,$exclude=0) {
        $users=get_users(['number'=>2,'meta_key'=>'phone_normalized','meta_value'=>$p]);
        foreach($users as $u) if((int)$u->ID!== (int)$exclude) return $u;
        return false;
    }
    private static function real_email($u) {
        $e=$u instanceof WP_User?$u->user_email:(string)$u;
        return (strpos($e,'@phone.tager')!==false || strpos($e,'@tager.local')!==false)?'':$e;
    }
    private static function generated_email($phone) { return 'u'.$phone.'@phone.tager'; }
    private static function redirect_notice($url,$type,$msg) { wp_safe_redirect(add_query_arg(['tager_notice'=>$type,'tager_msg'=>rawurlencode($msg)],$url)); exit; }
    private static function notice() {
        if(empty($_GET['tager_msg'])) return '';
        $type=($_GET['tager_notice']??'success')==='error'?'error':'success';
        return '<div class="v36-notice '.$type.'">'.esc_html(rawurldecode(sanitize_text_field(wp_unslash($_GET['tager_msg'])))).'</div>';
    }

    public static function customer_register_form() { return self::registration_form(false); }
    public static function vendor_register_form() { return self::registration_form(true); }
    private static function registration_form($vendor) {
        if (is_user_logged_in()) return '<div class="v36-panel">أنت مسجل الدخول بالفعل.</div>';
        ob_start(); echo self::notice(); ?>
        <div class="v36-auth">
          <aside><span class="v36-mark">T</span><h1><?php echo $vendor?'ابدأ البيع على تاجر':'أنشئ حساب عميل'; ?></h1><p>رقم الهاتف إجباري، والبريد الإلكتروني اختياري. تُحفظ بياناتك في قاعدة بيانات الموقع ويمكن تعديلها لاحقًا.</p></aside>
          <main><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="v36-form">
            <input type="hidden" name="action" value="<?php echo $vendor?'tager_vendor_apply':'tager_customer_register'; ?>">
            <?php wp_nonce_field($vendor?'tager_vendor_apply':'tager_customer_register'); ?>
            <label>الاسم الكامل<input name="name" required maxlength="120"></label>
            <?php if($vendor): ?><label>اسم المتجر<input name="store_name" required maxlength="150"></label><?php endif; ?>
            <label>رقم الهاتف<input name="phone" required inputmode="tel" placeholder="01012345678" pattern="01[0125][0-9]{8}"></label>
            <label>البريد الإلكتروني (اختياري)<input name="email" type="email"></label>
            <label>كلمة المرور<input name="password" type="password" required minlength="8"></label>
            <label>المحافظة<select name="governorate" required><?php echo self::governorates_options(''); ?></select></label>
            <?php if($vendor): ?>
              <label>نوع النشاط<input name="business_type" required></label>
              <label>رقم السجل التجاري<input name="commercial_registration"></label>
              <label>الرقم الضريبي<input name="tax_number"></label>
              <label class="wide">وصف النشاط والمنتجات<textarea name="notes" rows="4" required></textarea></label>
            <?php endif; ?>
            <label class="wide check"><input type="checkbox" name="terms" value="1" required> أوافق على الشروط وسياسة الخصوصية</label>
            <button class="v36-btn wide" type="submit"><?php echo $vendor?'إرسال طلب المورد':'إنشاء الحساب'; ?></button>
          </form></main>
        </div><?php return ob_get_clean();
    }

    private static function register_user($vendor) {
        check_admin_referer($vendor?'tager_vendor_apply':'tager_customer_register');
        $name=sanitize_text_field(wp_unslash($_POST['name']??''));
        $phone=self::phone($_POST['phone']??'');
        $email=sanitize_email(wp_unslash($_POST['email']??''));
        $pass=(string)($_POST['password']??'');
        $gov=sanitize_text_field(wp_unslash($_POST['governorate']??''));
        $back=self::url($vendor?'vendor-register':'customer-register');
        if(!$name || !self::valid_phone($phone) || strlen($pass)<8 || empty($_POST['terms'])) self::redirect_notice($back,'error','راجع الاسم ورقم الهاتف وكلمة المرور والموافقة على الشروط.');
        if(self::user_by_phone($phone)) self::redirect_notice($back,'error','رقم الهاتف مستخدم بالفعل.');
        if($email && (!is_email($email) || email_exists($email))) self::redirect_notice($back,'error','البريد الإلكتروني غير صحيح أو مستخدم بالفعل.');
        $login=$phone; $i=1; while(username_exists($login)) $login=$phone.'_'.$i++;
        $role=$vendor?'tager_vendor_pending':'tager_customer';
        $id=wp_insert_user(['user_login'=>$login,'user_pass'=>$pass,'user_email'=>$email?:self::generated_email($phone),'display_name'=>$name,'role'=>$role]);
        if(is_wp_error($id)) self::redirect_notice($back,'error',$id->get_error_message());
        $meta=['phone'=>$phone,'phone_normalized'=>$phone,'governorate'=>$gov,'email_optional'=>$email?0:1,'terms_accepted_at'=>current_time('mysql'),'profile_completed'=>1];
        if($vendor) $meta+=['store_name'=>sanitize_text_field(wp_unslash($_POST['store_name']??'')),'business_type'=>sanitize_text_field(wp_unslash($_POST['business_type']??'')),'commercial_registration'=>sanitize_text_field(wp_unslash($_POST['commercial_registration']??'')),'tax_number'=>sanitize_text_field(wp_unslash($_POST['tax_number']??'')),'notes'=>sanitize_textarea_field(wp_unslash($_POST['notes']??'')),'vendor_status'=>'pending','vendor_min_order'=>0];
        foreach($meta as $k=>$v) update_user_meta($id,$k,$v);
        if($vendor) self::redirect_notice($back,'success','تم تسجيل طلب المورد وحفظ جميع البيانات. الطلب الآن تحت مراجعة الإدارة.');
        wp_set_auth_cookie($id,true); self::redirect_notice(self::url('my-account'),'success','تم إنشاء حساب العميل وحفظ بياناته بنجاح.');
    }
    public static function register_customer(){ self::register_user(false); }
    public static function register_vendor(){ self::register_user(true); }

    public static function phone_login($user,$username,$password) {
        if($user instanceof WP_User || !$username || !$password) return $user;
        $p=self::phone($username); if(!self::valid_phone($p)) return $user;
        $u=self::user_by_phone($p); return $u?wp_authenticate_username_password(null,$u->user_login,$password):$user;
    }

    private static function require_login(){ if(!is_user_logged_in()) auth_redirect(); return wp_get_current_user(); }
    private static function is_vendor($u=null){ $u=$u?:wp_get_current_user(); return array_intersect((array)$u->roles,['tager_vendor','tager_vendor_pending']); }
    private static function is_approved_vendor($u=null){ $u=$u?:wp_get_current_user(); return in_array('tager_vendor',(array)$u->roles,true) && get_user_meta($u->ID,'vendor_status',true)==='approved'; }

    public static function customer_account() {
        if(!is_user_logged_in()) return do_shortcode('[tager_customer_register]');
        $u=wp_get_current_user(); $tab=sanitize_key($_GET['tab']??'overview');
        ob_start(); echo self::notice(); ?>
        <div class="v36-shell"><aside><?php echo self::account_nav('customer',$tab); ?></aside><main>
        <header class="v36-head"><div><small>حساب العميل</small><h1>مرحبًا، <?php echo esc_html($u->display_name); ?></h1><p>كل بياناتك وطلباتك وعناوينك محفوظة في مكان واحد.</p></div></header>
        <?php if($tab==='profile' || $tab==='addresses') echo self::customer_form($u); else echo self::customer_overview($u,$tab); ?>
        </main></div><?php return ob_get_clean();
    }
    private static function customer_overview($u,$tab){
        $orders=get_posts(['post_type'=>'tager_order','posts_per_page'=>50,'meta_key'=>'customer_user','meta_value'=>$u->ID]);
        if($tab==='orders') return self::orders_table($orders);
        $total=0; foreach($orders as $o)$total+=(float)get_post_meta($o->ID,'total',true);
        return '<div class="v36-stats"><article><b>'.count($orders).'</b><span>عدد الطلبات</span></article><article><b>'.number_format($total,2).'</b><span>إجمالي المشتريات</span></article><article><b>'.esc_html(get_user_meta($u->ID,'governorate',true)?:'—').'</b><span>المحافظة</span></article></div><section class="v36-panel"><h2>آخر الطلبات</h2>'.self::orders_table(array_slice($orders,0,5)).'</section>';
    }
    private static function customer_form($u){
        ob_start(); ?><section class="v36-panel"><h2>بيانات العميل والعنوان</h2><form class="v36-form cols" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v36_save_customer"><?php wp_nonce_field(self::NONCE); ?>
        <label>الاسم<input name="name" required value="<?php echo esc_attr($u->display_name); ?>"></label><label>رقم الهاتف<input name="phone" required value="<?php echo esc_attr(get_user_meta($u->ID,'phone_normalized',true)); ?>"></label><label>البريد (اختياري)<input type="email" name="email" value="<?php echo esc_attr(self::real_email($u)); ?>"></label><label>المحافظة<select name="governorate"><?php echo self::governorates_options(get_user_meta($u->ID,'governorate',true)); ?></select></label><label>المدينة / المنطقة<input name="city" value="<?php echo esc_attr(get_user_meta($u->ID,'city',true)); ?>"></label><label>العنوان بالتفصيل<input name="address" value="<?php echo esc_attr(get_user_meta($u->ID,'address',true)); ?>"></label><label>علامة مميزة<input name="landmark" value="<?php echo esc_attr(get_user_meta($u->ID,'landmark',true)); ?>"></label><button class="v36-btn wide">حفظ جميع البيانات</button></form></section><?php return ob_get_clean();
    }
    public static function save_customer(){
        $u=self::require_login(); check_admin_referer(self::NONCE);
        $phone=self::phone($_POST['phone']??''); $email=sanitize_email(wp_unslash($_POST['email']??''));
        if(!self::valid_phone($phone) || self::user_by_phone($phone,$u->ID)) self::redirect_notice(self::url('my-account'),'error','رقم الهاتف غير صحيح أو مستخدم.');
        if($email && (!is_email($email) || (($eid=email_exists($email)) && (int)$eid!==$u->ID))) self::redirect_notice(self::url('my-account'),'error','البريد غير صحيح أو مستخدم.');
        wp_update_user(['ID'=>$u->ID,'display_name'=>sanitize_text_field(wp_unslash($_POST['name']??'')),'user_email'=>$email?:self::generated_email($phone)]);
        foreach(['governorate','city','address','landmark'] as $k) update_user_meta($u->ID,$k,sanitize_text_field(wp_unslash($_POST[$k]??'')));
        update_user_meta($u->ID,'phone',$phone); update_user_meta($u->ID,'phone_normalized',$phone); update_user_meta($u->ID,'email_optional',$email?0:1);
        self::redirect_notice(add_query_arg('tab','profile',self::url('my-account')),'success','تم حفظ بيانات العميل والعنوان بنجاح.');
    }

    public static function vendor_dashboard() {
        if(!is_user_logged_in()) return do_shortcode('[tager_vendor_register]');
        $u=wp_get_current_user(); if(!self::is_vendor($u)) return '<div class="v36-panel">هذه الصفحة مخصصة للموردين.</div>';
        $tab=sanitize_key($_GET['tab']??'overview');
        ob_start(); echo self::notice(); ?><div class="v36-shell"><aside><?php echo self::account_nav('vendor',$tab); ?></aside><main><header class="v36-head"><div><small>لوحة المورد</small><h1><?php echo esc_html(get_user_meta($u->ID,'store_name',true)?:$u->display_name); ?></h1><p>إدارة المنتجات والأسعار والمخزون والطلبات والبيانات.</p></div></header><?php
        if(!self::is_approved_vendor($u)) echo '<div class="v36-notice error">حساب المورد تحت المراجعة. يمكنك استكمال بيانات المتجر، لكن النشر والبيع يبدأ بعد موافقة الإدارة.</div>';
        if($tab==='products') echo self::vendor_products($u);
        elseif($tab==='add-product' || $tab==='edit-product') echo self::product_form($u,(int)($_GET['product_id']??0));
        elseif($tab==='store' || $tab==='profile') echo self::vendor_form($u);
        elseif($tab==='orders') echo self::vendor_orders($u);
        else echo self::vendor_overview($u);
        ?></main></div><?php return ob_get_clean();
    }
    private static function vendor_overview($u){
        $products=get_posts(['post_type'=>'tager_product','posts_per_page'=>-1,'author'=>$u->ID,'post_status'=>['publish','pending','draft']]);
        $sales=0; return '<div class="v36-stats"><article><b>'.count($products).'</b><span>المنتجات</span></article><article><b>'.count(array_filter($products,function($p){return $p->post_status==='pending';})).'</b><span>تحت المراجعة</span></article><article><b>'.number_format($sales,2).'</b><span>المبيعات</span></article></div><section class="v36-panel"><h2>اختصارات</h2><a class="v36-btn" href="'.esc_url(add_query_arg('tab','add-product',self::url('vendor-dashboard'))).'">إضافة منتج</a></section>';
    }
    private static function vendor_products($u){
        $ps=get_posts(['post_type'=>'tager_product','posts_per_page'=>100,'author'=>$u->ID,'post_status'=>['publish','pending','draft']]);
        $rows=''; foreach($ps as $p){$edit=add_query_arg(['tab'=>'edit-product','product_id'=>$p->ID],self::url('vendor-dashboard')); $rows.='<tr><td>'.esc_html($p->post_title).'</td><td>'.esc_html(get_post_meta($p->ID,'retail_price',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'stock',true)).'</td><td>'.esc_html($p->post_status).'</td><td><a href="'.esc_url($edit).'">تعديل</a></td></tr>';}
        return '<section class="v36-panel"><div class="v36-toolbar"><h2>منتجاتي</h2><a class="v36-btn" href="'.esc_url(add_query_arg('tab','add-product',self::url('vendor-dashboard'))).'">إضافة منتج</a></div><div class="v36-table"><table><thead><tr><th>المنتج</th><th>سعر القطاعي</th><th>المخزون</th><th>الحالة</th><th></th></tr></thead><tbody>'.($rows?:'<tr><td colspan="5">لا توجد منتجات حتى الآن.</td></tr>').'</tbody></table></div></section>';
    }
    private static function product_form($u,$id){
        $p=$id?get_post($id):null; if($p && ((int)$p->post_author!==$u->ID || $p->post_type!=='tager_product')) return '<div class="v36-notice error">غير مصرح بتعديل هذا المنتج.</div>';
        $m=function($k,$d='')use($id){return $id?get_post_meta($id,$k,true):$d;};
        ob_start(); ?><section class="v36-panel"><h2><?php echo $id?'تعديل المنتج':'إضافة منتج جديد'; ?></h2><form class="v36-form cols" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v36_save_product"><input type="hidden" name="product_id" value="<?php echo (int)$id; ?>"><?php wp_nonce_field(self::NONCE); ?>
        <label>اسم المنتج بالعربية<input name="title" required value="<?php echo esc_attr($p?$p->post_title:''); ?>"></label><label>SKU<input name="sku" value="<?php echo esc_attr($m('sku')); ?>"></label><label>سعر القطاعي<input type="number" step="0.01" min="0" name="retail_price" required value="<?php echo esc_attr($m('retail_price')); ?>"></label><label>سعر الجملة<input type="number" step="0.01" min="0" name="wholesale_price" required value="<?php echo esc_attr($m('wholesale_price')); ?>"></label><label>سعر جملة الجملة<input type="number" step="0.01" min="0" name="bulk_price" required value="<?php echo esc_attr($m('bulk_price')); ?>"></label><label>المخزون<input type="number" min="0" name="stock" required value="<?php echo esc_attr($m('stock',0)); ?>"></label><label>حد الجملة<input type="number" min="1" name="wholesale_min" required value="<?php echo esc_attr($m('wholesale_min',10)); ?>"></label><label>حد جملة الجملة<input type="number" min="1" name="bulk_min" required value="<?php echo esc_attr($m('bulk_min',50)); ?>"></label><label>الحد الأقصى للطلب<input type="number" min="1" name="max_order" required value="<?php echo esc_attr($m('max_order',1000)); ?>"></label><label>مدة التجهيز بالأيام<input type="number" min="0" name="lead_time" value="<?php echo esc_attr($m('lead_time',1)); ?>"></label><label>صورة المنتج<input type="file" name="product_image" accept="image/jpeg,image/png,image/webp"></label><label class="wide">الوصف<textarea name="description" rows="6" required><?php echo esc_textarea($p?$p->post_content:''); ?></textarea></label><button class="v36-btn wide">حفظ وإرسال للمراجعة</button></form></section><?php return ob_get_clean();
    }
    public static function save_product(){
        $u=self::require_login(); if(!self::is_vendor($u)) wp_die('غير مصرح'); check_admin_referer(self::NONCE);
        $id=(int)($_POST['product_id']??0); if($id){$old=get_post($id); if(!$old || (int)$old->post_author!==$u->ID || $old->post_type!=='tager_product') wp_die('غير مصرح');}
        $r=(float)($_POST['retail_price']??0); $w=(float)($_POST['wholesale_price']??0); $b=(float)($_POST['bulk_price']??0); $wm=(int)($_POST['wholesale_min']??0); $bm=(int)($_POST['bulk_min']??0); $stock=(int)($_POST['stock']??0); $max=(int)($_POST['max_order']??0);
        if($r<=0 || $w<=0 || $b<=0 || !($r >= $w && $w >= $b) || $wm<1 || $bm<=$wm || $stock<0 || $max<1) self::redirect_notice(add_query_arg('tab',$id?'edit-product':'add-product',self::url('vendor-dashboard')),'error','راجع الأسعار والحدود والمخزون. يجب أن يكون القطاعي ≥ الجملة ≥ جملة الجملة، وحد جملة الجملة أكبر من حد الجملة.');
        $data=['post_type'=>'tager_product','post_title'=>sanitize_text_field(wp_unslash($_POST['title']??'')),'post_content'=>sanitize_textarea_field(wp_unslash($_POST['description']??'')),'post_status'=>'pending','post_author'=>$u->ID]; if($id)$data['ID']=$id;
        $pid=$id?wp_update_post($data,true):wp_insert_post($data,true); if(is_wp_error($pid)) wp_die($pid->get_error_message());
        $fields=['sku'=>'text','retail_price'=>'float','wholesale_price'=>'float','bulk_price'=>'float','stock'=>'int','wholesale_min'=>'int','bulk_min'=>'int','max_order'=>'int','lead_time'=>'int'];
        foreach($fields as $k=>$type){$v=$_POST[$k]??''; $v=$type==='text'?sanitize_text_field(wp_unslash($v)):($type==='float'?(float)$v:(int)$v); update_post_meta($pid,$k,$v);} update_post_meta($pid,'vendor_id',$u->ID); update_post_meta($pid,'approval_status','pending'); update_post_meta($pid,'last_vendor_update',current_time('mysql'));
        if(!empty($_FILES['product_image']['name'])){require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';$aid=media_handle_upload('product_image',$pid);if(!is_wp_error($aid))set_post_thumbnail($pid,$aid);}
        self::redirect_notice(add_query_arg('tab','products',self::url('vendor-dashboard')),'success','تم حفظ المنتج وجميع أسعاره ومخزونه وإرساله للمراجعة.');
    }
    public static function delete_product(){ $u=self::require_login(); check_admin_referer(self::NONCE); $id=(int)($_POST['product_id']??0); $p=get_post($id); if(!$p || (int)$p->post_author!==$u->ID)wp_die('غير مصرح'); wp_trash_post($id); self::redirect_notice(add_query_arg('tab','products',self::url('vendor-dashboard')),'success','تم حذف المنتج.'); }

    private static function vendor_form($u){ ob_start(); ?><section class="v36-panel"><h2>بيانات المتجر والمورد</h2><form class="v36-form cols" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v36_save_vendor"><?php wp_nonce_field(self::NONCE); ?><label>اسم المورد<input name="name" required value="<?php echo esc_attr($u->display_name); ?>"></label><label>اسم المتجر<input name="store_name" required value="<?php echo esc_attr(get_user_meta($u->ID,'store_name',true)); ?>"></label><label>الهاتف<input name="phone" required value="<?php echo esc_attr(get_user_meta($u->ID,'phone_normalized',true)); ?>"></label><label>البريد (اختياري)<input type="email" name="email" value="<?php echo esc_attr(self::real_email($u)); ?>"></label><label>المحافظة<select name="governorate"><?php echo self::governorates_options(get_user_meta($u->ID,'governorate',true)); ?></select></label><label>الحد الأدنى لطلبية المورد<input type="number" min="0" step="0.01" name="vendor_min_order" value="<?php echo esc_attr(get_user_meta($u->ID,'vendor_min_order',true)); ?>"></label><label>السجل التجاري<input name="commercial_registration" value="<?php echo esc_attr(get_user_meta($u->ID,'commercial_registration',true)); ?>"></label><label>الرقم الضريبي<input name="tax_number" value="<?php echo esc_attr(get_user_meta($u->ID,'tax_number',true)); ?>"></label><label>البنك<input name="bank_name" value="<?php echo esc_attr(get_user_meta($u->ID,'bank_name',true)); ?>"></label><label>IBAN<input name="iban" value="<?php echo esc_attr(get_user_meta($u->ID,'iban',true)); ?>"></label><label class="wide">وصف المتجر<textarea name="notes"><?php echo esc_textarea(get_user_meta($u->ID,'notes',true)); ?></textarea></label><button class="v36-btn wide">حفظ بيانات المورد</button></form></section><?php return ob_get_clean(); }
    public static function save_vendor(){
        $u=self::require_login(); if(!self::is_vendor($u))wp_die('غير مصرح'); check_admin_referer(self::NONCE); $phone=self::phone($_POST['phone']??''); $email=sanitize_email(wp_unslash($_POST['email']??''));
        if(!self::valid_phone($phone) || self::user_by_phone($phone,$u->ID)) self::redirect_notice(add_query_arg('tab','store',self::url('vendor-dashboard')),'error','رقم الهاتف غير صحيح أو مستخدم.');
        if($email && (!is_email($email)||(($eid=email_exists($email))&&(int)$eid!==$u->ID))) self::redirect_notice(add_query_arg('tab','store',self::url('vendor-dashboard')),'error','البريد غير صحيح أو مستخدم.');
        wp_update_user(['ID'=>$u->ID,'display_name'=>sanitize_text_field(wp_unslash($_POST['name']??'')),'user_email'=>$email?:self::generated_email($phone)]);
        foreach(['store_name','governorate','commercial_registration','tax_number','bank_name','iban'] as $k)update_user_meta($u->ID,$k,sanitize_text_field(wp_unslash($_POST[$k]??''))); update_user_meta($u->ID,'notes',sanitize_textarea_field(wp_unslash($_POST['notes']??''))); update_user_meta($u->ID,'vendor_min_order',(float)($_POST['vendor_min_order']??0)); update_user_meta($u->ID,'phone',$phone);update_user_meta($u->ID,'phone_normalized',$phone); update_user_meta($u->ID,'profile_completed',1);
        self::redirect_notice(add_query_arg('tab','store',self::url('vendor-dashboard')),'success','تم حفظ جميع بيانات المورد والمتجر.');
    }
    private static function vendor_orders($u){ $orders=get_posts(['post_type'=>'tager_order','posts_per_page'=>100,'meta_key'=>'vendor_ids','meta_value'=>'"'.$u->ID.'"','meta_compare'=>'LIKE']); return '<section class="v36-panel"><h2>طلبات المورد</h2>'.self::orders_table($orders).'</section>'; }
    private static function orders_table($orders){$r='';foreach($orders as $o)$r.='<tr><td>#'.$o->ID.'</td><td>'.esc_html(get_post_meta($o->ID,'status',true)?:$o->post_status).'</td><td>'.number_format((float)get_post_meta($o->ID,'total',true),2).'</td><td>'.esc_html(get_the_date('', $o)).'</td></tr>';return '<div class="v36-table"><table><thead><tr><th>الطلب</th><th>الحالة</th><th>الإجمالي</th><th>التاريخ</th></tr></thead><tbody>'.($r?:'<tr><td colspan="4">لا توجد طلبات.</td></tr>').'</tbody></table></div>';}

    private static function account_nav($type,$tab){$items=$type==='vendor'?['overview'=>'الرئيسية','products'=>'المنتجات','add-product'=>'إضافة منتج','orders'=>'الطلبات','store'=>'إعدادات المتجر']:['overview'=>'الرئيسية','orders'=>'طلباتي','addresses'=>'العناوين','profile'=>'البيانات'];$slug=$type==='vendor'?'vendor-dashboard':'my-account';$o='<nav class="v36-nav">';foreach($items as $k=>$v)$o.='<a class="'.($tab===$k?'active':'').'" href="'.esc_url(add_query_arg('tab',$k,self::url($slug))).'">'.esc_html($v).'</a>';$o.='<a href="'.esc_url(wp_logout_url(home_url())).'">تسجيل الخروج</a></nav>';return $o;}
    private static function governorates_options($selected){$list=['القاهرة','الجيزة','الإسكندرية','الدقهلية','البحر الأحمر','البحيرة','الفيوم','الغربية','الإسماعيلية','المنوفية','المنيا','القليوبية','الوادي الجديد','السويس','أسوان','أسيوط','بني سويف','بورسعيد','دمياط','الشرقية','جنوب سيناء','كفر الشيخ','مطروح','الأقصر','قنا','شمال سيناء','سوهاج'];$o='<option value="">اختر المحافظة</option>';foreach($list as $g)$o.='<option '.selected($selected,$g,false).'>'.esc_html($g).'</option>';return $o;}

    public static function admin_menu(){ add_menu_page('Tager Data QA','Tager Data QA','manage_options','tager-v36-data',[__CLASS__,'admin_screen'],'dashicons-database',2); add_submenu_page('tager-v36-data','موافقات الموردين','موافقات الموردين','manage_options','tager-v36-vendors',[__CLASS__,'vendors_screen']); add_submenu_page('tager-v36-data','مراجعة المنتجات','مراجعة المنتجات','manage_options','tager-v36-products',[__CLASS__,'products_screen']); }
    public static function admin_screen(){
        $last=get_option('tager_v36_test_results',[]); echo '<div class="wrap"><h1>Tager V36 — فحص حفظ البيانات</h1><p>هذا الفحص ينشئ بيانات مؤقتة في قاعدة البيانات، يقرأها، يعدلها، ثم يحذفها للتأكد أن عمليات التسجيل والحفظ تعمل.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v36_run_tests">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button class="button button-primary">تشغيل اختبار قاعدة البيانات الآن</button></form><hr><h2>آخر نتيجة</h2><table class="widefat striped"><thead><tr><th>الاختبار</th><th>النتيجة</th><th>التفاصيل</th></tr></thead><tbody>';
        if(!$last) echo '<tr><td colspan="3">لم يتم تشغيل الاختبار بعد.</td></tr>'; else foreach($last as $x)echo '<tr><td>'.esc_html($x['name']).'</td><td>'.($x['ok']?'✅ ناجح':'❌ فشل').'</td><td>'.esc_html($x['detail']).'</td></tr>';
        echo '</tbody></table><h2>إحصائيات حقيقية</h2><ul><li>العملاء: '.count(get_users(['role'=>'tager_customer'])).'</li><li>الموردون المعتمدون: '.count(get_users(['role'=>'tager_vendor'])).'</li><li>الموردون تحت المراجعة: '.count(get_users(['role'=>'tager_vendor_pending'])).'</li><li>المنتجات: '.wp_count_posts('tager_product')->publish.' منشور / '.wp_count_posts('tager_product')->pending.' مراجعة</li></ul></div>';
    }
    public static function run_tests(){ if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);$res=[];$phone='010'.wp_rand(10000000,99999999);$uid=wp_insert_user(['user_login'=>$phone,'user_pass'=>wp_generate_password(14),'user_email'=>self::generated_email($phone),'display_name'=>'V36 Test Customer','role'=>'tager_customer']);$ok=!is_wp_error($uid);$res[]=['name'=>'إنشاء عميل','ok'=>$ok,'detail'=>$ok?'تم إنشاء سجل مستخدم مؤقت':'فشل إنشاء المستخدم'];if($ok){update_user_meta($uid,'phone_normalized',$phone);$read=get_user_meta($uid,'phone_normalized',true)===$phone;$res[]=['name'=>'حفظ وقراءة بيانات العميل','ok'=>$read,'detail'=>$read?'تمت القراءة بنفس القيمة':'القيمة غير متطابقة'];$pid=wp_insert_post(['post_type'=>'tager_product','post_title'=>'V36 Test Product','post_status'=>'draft','post_author'=>$uid],true);$pok=!is_wp_error($pid);$res[]=['name'=>'إنشاء منتج','ok'=>$pok,'detail'=>$pok?'تم إنشاء المنتج المؤقت':'فشل إنشاء المنتج'];if($pok){update_post_meta($pid,'retail_price',100);update_post_meta($pid,'stock',50);$same=((float)get_post_meta($pid,'retail_price',true)===100.0&&(int)get_post_meta($pid,'stock',true)===50);$res[]=['name'=>'حفظ أسعار ومخزون المنتج','ok'=>$same,'detail'=>$same?'تم حفظ وقراءة الأسعار والمخزون':'فشل تطابق البيانات'];wp_delete_post($pid,true);}wp_delete_user($uid);}update_option('tager_v36_test_results',$res,false);wp_safe_redirect(admin_url('admin.php?page=tager-v36-data'));exit; }
    public static function vendors_screen(){ $users=get_users(['role__in'=>['tager_vendor_pending','tager_vendor']]);echo '<div class="wrap"><h1>موافقات الموردين</h1><table class="widefat striped"><thead><tr><th>المورد</th><th>الهاتف</th><th>المتجر</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>';foreach($users as $u){echo '<tr><td>'.esc_html($u->display_name).'</td><td>'.esc_html(get_user_meta($u->ID,'phone_normalized',true)).'</td><td>'.esc_html(get_user_meta($u->ID,'store_name',true)).'</td><td>'.esc_html(get_user_meta($u->ID,'vendor_status',true)).'</td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v36_vendor_approval"><input type="hidden" name="user_id" value="'.$u->ID.'">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button name="decision" value="approve" class="button button-primary">موافقة</button> <button name="decision" value="reject" class="button">رفض</button></form></td></tr>';}echo '</tbody></table></div>'; }
    public static function vendor_approval(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);$id=(int)$_POST['user_id'];$u=get_user_by('id',$id);if($u){$d=sanitize_key($_POST['decision']??'');if($d==='approve'){$u->set_role('tager_vendor');update_user_meta($id,'vendor_status','approved');}else{update_user_meta($id,'vendor_status','rejected');}}wp_safe_redirect(admin_url('admin.php?page=tager-v36-vendors'));exit;}
    public static function products_screen(){ $ps=get_posts(['post_type'=>'tager_product','post_status'=>'pending','posts_per_page'=>100]);echo '<div class="wrap"><h1>مراجعة المنتجات</h1><table class="widefat striped"><thead><tr><th>المنتج</th><th>المورد</th><th>الأسعار</th><th>المخزون</th><th>إجراء</th></tr></thead><tbody>';foreach($ps as $p){$u=get_user_by('id',$p->post_author);echo '<tr><td>'.esc_html($p->post_title).'</td><td>'.esc_html($u?$u->display_name:'—').'</td><td>'.esc_html(get_post_meta($p->ID,'retail_price',true)).' / '.esc_html(get_post_meta($p->ID,'wholesale_price',true)).' / '.esc_html(get_post_meta($p->ID,'bulk_price',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'stock',true)).'</td><td><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v36_product_approval"><input type="hidden" name="product_id" value="'.$p->ID.'">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button name="decision" value="approve" class="button button-primary">نشر</button> <button name="decision" value="reject" class="button">رفض</button></form></td></tr>';}echo '</tbody></table></div>'; }
    public static function product_approval(){if(!current_user_can('manage_options'))wp_die('غير مصرح');check_admin_referer(self::NONCE);$id=(int)$_POST['product_id'];$d=sanitize_key($_POST['decision']??'');if($d==='approve'){wp_update_post(['ID'=>$id,'post_status'=>'publish']);update_post_meta($id,'approval_status','approved');}else{wp_update_post(['ID'=>$id,'post_status'=>'draft']);update_post_meta($id,'approval_status','rejected');}wp_safe_redirect(admin_url('admin.php?page=tager-v36-products'));exit;}

    public static function assets(){wp_register_style('tager-v36',false);wp_enqueue_style('tager-v36');wp_add_inline_style('tager-v36','.v36-auth{display:grid;grid-template-columns:1fr 1.4fr;background:#fff;border:1px solid #dce8e2;border-radius:28px;overflow:hidden;box-shadow:0 22px 60px rgba(4,70,48,.12)}.v36-auth aside{padding:42px;background:linear-gradient(145deg,#063c2d,#087454);color:#fff}.v36-auth main{padding:34px}.v36-mark{display:grid;width:64px;height:64px;place-items:center;background:#d9b45f;color:#173328;border-radius:18px;font-size:34px;font-weight:900}.v36-form{display:grid;gap:14px}.v36-form.cols{grid-template-columns:repeat(2,minmax(0,1fr))}.v36-form label{display:grid;gap:7px;font-weight:800}.v36-form input,.v36-form select,.v36-form textarea{width:100%;padding:12px;border:1px solid #cadbd2;border-radius:11px}.v36-form .wide{grid-column:1/-1}.v36-form .check{display:flex;align-items:center;gap:8px}.v36-form .check input{width:auto}.v36-btn{display:inline-flex;justify-content:center;align-items:center;padding:12px 18px;border:0;border-radius:12px;background:#d5ad54;color:#173328!important;font-weight:900;text-decoration:none;cursor:pointer}.v36-shell{display:grid;grid-template-columns:250px 1fr;gap:24px}.v36-shell>aside{background:#073f2f;border-radius:20px;padding:18px;height:max-content}.v36-nav{display:grid;gap:6px}.v36-nav a{color:#dcefe7;text-decoration:none;padding:12px;border-radius:10px}.v36-nav a.active,.v36-nav a:hover{background:#d5ad54;color:#173328}.v36-head,.v36-panel,.v36-stats article{background:#fff;border:1px solid #dce8e2;border-radius:20px;padding:22px;box-shadow:0 10px 28px rgba(4,70,48,.06)}.v36-head{margin-bottom:18px;background:linear-gradient(135deg,#064932,#087454);color:#fff}.v36-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}.v36-stats b{font-size:28px;color:#07543d;display:block}.v36-notice{padding:13px 16px;border-radius:11px;margin:12px 0;font-weight:800}.v36-notice.success{background:#e7f8ef;color:#07543d}.v36-notice.error{background:#fff0f0;color:#9b1c1c}.v36-table{overflow:auto}.v36-table table{width:100%;border-collapse:collapse}.v36-table th,.v36-table td{padding:12px;border-bottom:1px solid #e4ece8;text-align:right}.v36-toolbar{display:flex;justify-content:space-between;align-items:center;gap:15px}@media(max-width:850px){.v36-auth,.v36-shell{grid-template-columns:1fr}.v36-form.cols,.v36-stats{grid-template-columns:1fr}.v36-shell>aside{position:static}}');}
}
Tager_V36_Data_Integrity::init();
