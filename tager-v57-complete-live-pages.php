<?php
/**
 * Plugin Name: Tager V57 Complete Live Pages
 * Description: Rebuilds empty core pages with complete, working customer/vendor authentication and connected workspaces.
 * Version: 57.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V57_Complete_Live_Pages {
    const VER='57.0.0';
    public static function init(){
        add_action('init',[__CLASS__,'roles'],1);
        add_action('init',[__CLASS__,'shortcodes'],20);
        add_action('init',[__CLASS__,'repair_pages'],999);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],999);
        add_action('admin_menu',[__CLASS__,'menu'],999);
        add_action('admin_post_tager_v57_repair',[__CLASS__,'manual_repair']);
        add_action('admin_post_nopriv_tager_v57_login',[__CLASS__,'login_action']);
        add_action('admin_post_nopriv_tager_v57_customer_register',[__CLASS__,'customer_register_action']);
        add_action('admin_post_nopriv_tager_v57_vendor_register',[__CLASS__,'vendor_register_action']);
        add_action('admin_post_nopriv_tager_v57_forgot',[__CLASS__,'forgot_action']);
        add_filter('login_redirect',[__CLASS__,'login_redirect'],9999,3);
    }
    public static function roles(){
        if(!get_role('tager_customer')) add_role('tager_customer','Tager Customer',['read'=>true]);
        if(!get_role('tager_vendor')) add_role('tager_vendor','Tager Vendor',['read'=>true,'upload_files'=>true]);
    }
    public static function defs(){return [
        'home'=>['الرئيسية','[tager_v56_home]'],
        'login'=>['تسجيل الدخول','[tager_v57_login]'],
        'choose-account'=>['اختر نوع الحساب','[tager_v57_choose_account]'],
        'customer-register'=>['تسجيل عميل','[tager_v57_customer_register]'],
        'vendor-register'=>['تسجيل مورد','[tager_v57_vendor_register]'],
        'forgot-password'=>['نسيت كلمة المرور','[tager_v57_forgot_password]'],
        'customer-home'=>['حساب العميل','[tager_v57_customer_home]'],
        'vendor-home'=>['لوحة المورد','[tager_v57_vendor_home]'],
        'admin-home'=>['بوابة الإدارة','[tager_v57_admin_home]'],
        'market'=>['السوق','[tager_v48_market]'],
        'vendors'=>['دليل الموردين','[tager_v48_vendor_directory]'],
        'cart'=>['السلة','[tager_v24_cart]'],
        'checkout'=>['إتمام الطلب','[tager_v57_checkout]'],
        'customer-orders'=>['طلباتي','[tager_v42_customer_orders]'],
        'customer-profile'=>['بياناتي','[tager_v55_customer_profile]'],
        'customer-addresses'=>['عناويني','[tager_v55_customer_addresses]'],
        'vendor-products'=>['منتجاتي','[tager_v55_vendor_products]'],
        'vendor-add-product'=>['إضافة منتج','[tager_v54_vendor_product_form]'],
        'vendor-orders'=>['طلبات المورد','[tager_v42_vendor_orders]'],
        'vendor-earnings'=>['الأرباح والعمولات','[tager_v55_vendor_earnings]'],
        'vendor-market'=>['سوق المورد','[tager_v39_vendor_market]'],
        'notifications'=>['الإشعارات','[tager_v42_notifications]'],
        'support'=>['الدعم','[tager_support]'],
        'payment-methods'=>['طرق الدفع','[tager_v57_info type="payments"]'],
        'shipping'=>['الشحن والتوصيل','[tager_v57_info type="shipping"]'],
        'return-policy'=>['سياسة الاسترجاع','[tager_v57_info type="returns"]'],
        'terms'=>['الشروط والأحكام','[tager_v57_info type="terms"]'],
        'privacy'=>['سياسة الخصوصية','[tager_v57_info type="privacy"]'],
        'contact'=>['تواصل معنا','[tager_v57_info type="contact"]'],
    ];}
    public static function shortcodes(){
        add_shortcode('tager_v57_login',[__CLASS__,'login_page']);
        add_shortcode('tager_v57_choose_account',[__CLASS__,'choose_account']);
        add_shortcode('tager_v57_customer_register',[__CLASS__,'customer_register']);
        add_shortcode('tager_v57_vendor_register',[__CLASS__,'vendor_register']);
        add_shortcode('tager_v57_forgot_password',[__CLASS__,'forgot_password']);
        add_shortcode('tager_v57_customer_home',[__CLASS__,'customer_home']);
        add_shortcode('tager_v57_vendor_home',[__CLASS__,'vendor_home']);
        add_shortcode('tager_v57_admin_home',[__CLASS__,'admin_home']);
        add_shortcode('tager_v57_checkout',[__CLASS__,'checkout']);
        add_shortcode('tager_v57_info',[__CLASS__,'info_page']);
    }
    public static function url($slug){$p=get_page_by_path($slug,OBJECT,'page'); return $p?get_permalink($p):home_url('/'.$slug.'/');}
    public static function repair_pages(){
        if(get_option('tager_v57_repaired')===self::VER) return;
        foreach(self::defs() as $slug=>$d){
            $p=get_page_by_path($slug,OBJECT,'page');
            $data=['post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1],'post_status'=>'publish','post_type'=>'page'];
            if(!$p) wp_insert_post($data);
            else { $data['ID']=$p->ID; wp_update_post($data); }
        }
        update_option('tager_v57_repaired',self::VER);
        flush_rewrite_rules(false);
    }
    public static function manual_repair(){check_admin_referer('tager_v57_repair');delete_option('tager_v57_repaired');self::repair_pages();wp_safe_redirect(admin_url('admin.php?page=tager-v57&repaired=1'));exit;}
    private static function notice(){
        $m=sanitize_key($_GET['tv57_msg']??'');
        $map=['login_failed'=>'بيانات الدخول غير صحيحة.','registered'=>'تم إنشاء الحساب بنجاح.','phone_exists'=>'رقم الهاتف مستخدم بالفعل.','email_exists'=>'البريد مستخدم بالفعل.','missing'=>'أكمل الحقول المطلوبة.','reset_sent'=>'تم إرسال تعليمات الاستعادة إذا كانت البيانات صحيحة.'];
        return isset($map[$m])?'<div class="tv57-alert">'.esc_html($map[$m]).'</div>':'';
    }
    public static function login_page(){
        if(is_user_logged_in()) return '<section class="tv57-shell"><div class="tv57-card"><h2>أنت مسجل بالفعل</h2><a class="tv57-btn" href="'.esc_url(self::dashboard_url(wp_get_current_user())).'">الذهاب إلى حسابي</a></div></section>';
        ob_start();?>
        <section class="tv57-auth"><div class="tv57-auth-side"><span class="tv57-kicker">TAGER MARKETPLACE</span><h1>مرحبًا بعودتك</h1><p>ادخل برقم الهاتف أو البريد الإلكتروني إن كان مسجلًا.</p><ul><li>متابعة الطلبات</li><li>إدارة المنتجات للمورد</li><li>لوحات مستقلة حسب نوع الحساب</li></ul></div><div class="tv57-card tv57-form-card"><?php echo self::notice();?><h2>تسجيل الدخول</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v57_login"><?php wp_nonce_field('tager_v57_login');?><label>رقم الهاتف أو البريد</label><input name="identity" required autocomplete="username" placeholder="01XXXXXXXXX أو name@example.com"><label>كلمة المرور</label><input type="password" name="password" required autocomplete="current-password"><label class="tv57-check"><input type="checkbox" name="remember" value="1"> تذكرني</label><button class="tv57-btn tv57-wide" type="submit">دخول الحساب</button></form><div class="tv57-links"><a href="<?php echo esc_url(self::url('forgot-password'));?>">نسيت كلمة المرور؟</a><a href="<?php echo esc_url(self::url('choose-account'));?>">إنشاء حساب جديد</a></div></div></section><?php return ob_get_clean();
    }
    public static function choose_account(){return '<section class="tv57-shell"><div class="tv57-head"><span class="tv57-kicker">ابدأ الآن</span><h1>اختر نوع الحساب</h1><p>كل حساب له مساحة عمل وصلاحيات مختلفة.</p></div><div class="tv57-grid2"><article class="tv57-choice"><h2>حساب عميل</h2><p>التسوق، حفظ العناوين، متابعة الطلبات والمفضلة.</p><a class="tv57-btn" href="'.esc_url(self::url('customer-register')).'">تسجيل عميل</a></article><article class="tv57-choice"><h2>حساب مورد</h2><p>إضافة المنتجات والأسعار والمخزون ومتابعة الأرباح.</p><a class="tv57-btn" href="'.esc_url(self::url('vendor-register')).'">تسجيل مورد</a></article></div></section>';}
    private static function common_register_fields($vendor=false){ob_start();?>
        <label>الاسم الكامل</label><input name="full_name" required>
        <label>رقم الهاتف المصري</label><input name="phone" required inputmode="tel" placeholder="01012345678">
        <label>البريد الإلكتروني <small>(اختياري)</small></label><input type="email" name="email">
        <?php if($vendor):?><label>اسم المتجر</label><input name="store_name" required><label>المحافظة</label><select name="governorate" required><?php foreach(self::governorates() as $g)echo '<option value="'.esc_attr($g).'">'.esc_html($g).'</option>';?></select><label>المركز / المدينة</label><input name="city" required><label>نوع النشاط</label><input name="business_type" required><label>الحد الأدنى للطلب (جنيه)</label><input type="number" min="0" step="1" name="minimum_order" value="0"><?php endif;?>
        <label>كلمة المرور</label><input type="password" name="password" minlength="8" required><label>تأكيد كلمة المرور</label><input type="password" name="confirm_password" minlength="8" required>
        <?php return ob_get_clean();}
    public static function customer_register(){if(is_user_logged_in())return '<section class="tv57-shell"><div class="tv57-card"><h2>لديك حساب بالفعل</h2><a class="tv57-btn" href="'.esc_url(self::dashboard_url(wp_get_current_user())).'">فتح الحساب</a></div></section>';ob_start();?><section class="tv57-auth"><div class="tv57-auth-side"><span class="tv57-kicker">CUSTOMER</span><h1>أنشئ حساب عميل</h1><p>البريد اختياري، ورقم الهاتف هو وسيلة التسجيل الأساسية.</p></div><div class="tv57-card tv57-form-card"><?php echo self::notice();?><h2>بيانات العميل</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v57_customer_register"><?php wp_nonce_field('tager_v57_customer_register');echo self::common_register_fields(false);?><label class="tv57-check"><input type="checkbox" required> أوافق على الشروط وسياسة الخصوصية</label><button class="tv57-btn tv57-wide" type="submit">إنشاء حساب العميل</button></form></div></section><?php return ob_get_clean();}
    public static function vendor_register(){if(is_user_logged_in())return '<section class="tv57-shell"><div class="tv57-card"><h2>لديك حساب بالفعل</h2><a class="tv57-btn" href="'.esc_url(self::dashboard_url(wp_get_current_user())).'">فتح الحساب</a></div></section>';ob_start();?><section class="tv57-auth"><div class="tv57-auth-side"><span class="tv57-kicker">VENDOR</span><h1>انضم كمورد</h1><p>سجل بيانات نشاطك، ثم تراجع الإدارة الحساب قبل تفعيل البيع.</p></div><div class="tv57-card tv57-form-card"><?php echo self::notice();?><h2>بيانات المورد والمتجر</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" enctype="multipart/form-data"><input type="hidden" name="action" value="tager_v57_vendor_register"><?php wp_nonce_field('tager_v57_vendor_register');echo self::common_register_fields(true);?><label>وصف مختصر للنشاط</label><textarea name="description" rows="4"></textarea><label class="tv57-check"><input type="checkbox" required> أوافق على شروط الموردين</label><button class="tv57-btn tv57-wide" type="submit">إرسال طلب المورد</button></form></div></section><?php return ob_get_clean();}
    public static function forgot_password(){ob_start();?><section class="tv57-shell tv57-narrow"><div class="tv57-card"><?php echo self::notice();?><span class="tv57-kicker">ACCOUNT RECOVERY</span><h1>استعادة كلمة المرور</h1><p>اكتب رقم الهاتف أو البريد المسجل. البريد الحقيقي يحتاج إعداد SMTP، والهاتف يحتاج بوابة SMS.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="tager_v57_forgot"><?php wp_nonce_field('tager_v57_forgot');?><label>رقم الهاتف أو البريد</label><input name="identity" required><button class="tv57-btn tv57-wide" type="submit">إرسال تعليمات الاستعادة</button></form><p><a href="<?php echo esc_url(self::url('login'));?>">العودة لتسجيل الدخول</a></p></div></section><?php return ob_get_clean();}
    public static function dashboard_url($u){if(user_can($u,'manage_options')||array_intersect($u->roles,['administrator','tager_admin','shop_manager']))return self::url('admin-home');if(in_array('tager_vendor',$u->roles,true)||in_array('vendor',$u->roles,true))return self::url('vendor-home');return self::url('customer-home');}
    public static function customer_home(){if(!is_user_logged_in())return self::gate();$u=wp_get_current_user();$cards=[['طلباتي','customer-orders','متابعة حالة الطلبات والفواتير'],['بياناتي','customer-profile','تعديل الاسم والهاتف والبريد الاختياري'],['عناويني','customer-addresses','المحافظة والمركز والعنوان'],['السوق','market','تصفح القطاعي والجملة وجملة الجملة'],['السلة','cart','مراجعة المنتجات والرسوم'],['الدعم','support','فتح تذكرة ومتابعة الردود']];return self::workspace('حساب العميل','مرحبًا '.$u->display_name,$cards);}
    public static function vendor_home(){if(!is_user_logged_in())return self::gate();$u=wp_get_current_user();if(!(in_array('tager_vendor',$u->roles,true)||current_user_can('manage_options')))return self::denied();$cards=[['إضافة منتج','vendor-add-product','الصور والأسعار والمخزون وحدود الكمية'],['منتجاتي','vendor-products','تعديل المنتجات ومتابعة حالة المراجعة'],['طلبات المورد','vendor-orders','تجهيز وشحن الطلبات الخاصة بك'],['الأرباح','vendor-earnings','المبيعات والعمولة وصافي المستحق'],['سوق المورد','vendor-market','متابعة السوق والأسعار والمنافسين'],['صور المتجر','vendor-media','الشعار والغلاف ومعرض المتجر']];return self::workspace('لوحة المورد','إدارة متجرك من مكان واحد',$cards);}
    public static function admin_home(){if(!current_user_can('manage_options')&&!current_user_can('tager_manage_orders'))return self::denied();$cards=[['الموردون','users.php','المراجعة والتفعيل والصلاحيات',true],['مراجعة المنتجات','edit.php?post_type=tager_product','اعتماد المنتجات وتعديل حالتها',true],['الطلبات','edit.php?post_type=tager_order','متابعة الدفع والشحن والحالات',true],['الشحن','admin.php?page=tager-shipping','المحافظات والرسوم والشحن المجاني',true],['العمولات','admin.php?page=tager-commissions','نسبة كل مورد ورسوم السلة المميزة',true],['فحص الصفحات','admin.php?page=tager-v57','الصفحات الفارغة والروابط والشورت كود',true]];return self::workspace('بوابة الإدارة','مركز تشغيل المنصة',$cards);}
    private static function workspace($title,$sub,$cards){$o='<section class="tv57-shell"><div class="tv57-head"><span class="tv57-kicker">TAGER WORKSPACE</span><h1>'.esc_html($title).'</h1><p>'.esc_html($sub).'</p></div><div class="tv57-grid3">';foreach($cards as $c){$url=!empty($c[3])?admin_url($c[1]):self::url($c[1]);$o.='<article class="tv57-choice"><h3>'.esc_html($c[0]).'</h3><p>'.esc_html($c[2]).'</p><a class="tv57-btn tv57-secondary" href="'.esc_url($url).'">فتح الصفحة</a></article>';}$o.='</div></section>';return $o;}
    public static function checkout(){return '<section class="tv57-shell"><div class="tv57-head"><h1>إتمام الطلب</h1><p>راجع السلة أولًا، ثم اختر المحافظة وطريقة الشحن والدفع.</p></div><div class="tv57-card"><h3>خطوات الإتمام</h3><ol><li>اختيار نوع السلة: منفصلة حسب المورد أو سلة مميزة.</li><li>إدخال عنوان التوصيل والمحافظة والمركز.</li><li>اختيار طريقة الدفع.</li><li>مراجعة الرسوم والإجمالي ثم تأكيد الطلب.</li></ol><a class="tv57-btn" href="'.esc_url(self::url('cart')).'">فتح السلة وإكمال الطلب</a></div></section>';}
    public static function info_page($a){$a=shortcode_atts(['type'=>'contact'],$a);$content=[
      'payments'=>['طرق الدفع','الدفع عند الاستلام، التحويل البنكي، InstaPay، المحافظ الإلكترونية، فوري، والبطاقات عند ربط بوابة دفع.'],
      'shipping'=>['الشحن والتوصيل','تختلف رسوم ومدة الشحن حسب المحافظة والمركز. يمكن للإدارة تفعيل الشحن المجاني ووضع حدود وقواعد مستقلة.'],
      'returns'=>['سياسة الاسترجاع','يمكن تقديم طلب استرجاع من حساب العميل وفق حالة الطلب، سلامة المنتج، والمدة المحددة في سياسة المنصة.'],
      'terms'=>['الشروط والأحكام','باستخدام المنصة يوافق العميل والمورد على صحة البيانات، سياسات الطلب، الدفع، الشحن، والعمولات.'],
      'privacy'=>['سياسة الخصوصية','تستخدم البيانات لتنفيذ الطلبات وإدارة الحسابات. لا يتم نشر بيانات الاتصال أو الدفع دون حاجة تشغيلية.'],
      'contact'=>['تواصل معنا','تواصل من خلال مركز الدعم أو واتساب المنصة. أضف بيانات الشركة الفعلية من إعدادات الإدارة قبل الإطلاق.']
    ];$d=$content[$a['type']]??$content['contact'];return '<section class="tv57-shell tv57-narrow"><div class="tv57-card"><span class="tv57-kicker">TAGER</span><h1>'.esc_html($d[0]).'</h1><p>'.esc_html($d[1]).'</p><a class="tv57-btn" href="'.esc_url(self::url('support')).'">فتح مركز الدعم</a></div></section>';}
    private static function gate(){return '<section class="tv57-shell tv57-narrow"><div class="tv57-card"><h2>يلزم تسجيل الدخول</h2><p>سجل الدخول للوصول إلى هذه الصفحة.</p><a class="tv57-btn" href="'.esc_url(self::url('login')).'">تسجيل الدخول</a></div></section>';}
    private static function denied(){return '<section class="tv57-shell tv57-narrow"><div class="tv57-card"><h2>غير مصرح</h2><p>هذا الحساب لا يملك صلاحية فتح الصفحة.</p><a class="tv57-btn" href="'.esc_url(home_url('/')).'">العودة للرئيسية</a></div></section>';}
    public static function normalize_phone($p){$p=preg_replace('/\D+/','',$p);if(str_starts_with($p,'20')&&strlen($p)===12)$p='0'.substr($p,2);return $p;}
    private static function find_user($id){$id=trim($id);if(is_email($id))return get_user_by('email',$id);$p=self::normalize_phone($id);$q=get_users(['meta_key'=>'tager_phone','meta_value'=>$p,'number'=>1]);return $q?$q[0]:get_user_by('login',$id);}
    private static function go($slug,$msg){wp_safe_redirect(add_query_arg('tv57_msg',$msg,self::url($slug)));exit;}
    public static function login_action(){check_admin_referer('tager_v57_login');$u=self::find_user(wp_unslash($_POST['identity']??''));if(!$u)self::go('login','login_failed');$s=wp_signon(['user_login'=>$u->user_login,'user_password'=>(string)($_POST['password']??''),'remember'=>!empty($_POST['remember'])],is_ssl());if(is_wp_error($s))self::go('login','login_failed');wp_safe_redirect(self::dashboard_url($s));exit;}
    private static function register($vendor){$action=$vendor?'tager_v57_vendor_register':'tager_v57_customer_register';check_admin_referer($action);$name=sanitize_text_field($_POST['full_name']??'');$phone=self::normalize_phone($_POST['phone']??'');$email=sanitize_email($_POST['email']??'');$pass=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm_password']??'');if(!$name||!preg_match('/^01[0125]\d{8}$/',$phone)||strlen($pass)<8||$pass!==$confirm)self::go($vendor?'vendor-register':'customer-register','missing');if(get_users(['meta_key'=>'tager_phone','meta_value'=>$phone,'number'=>1]))self::go($vendor?'vendor-register':'customer-register','phone_exists');if($email&&email_exists($email))self::go($vendor?'vendor-register':'customer-register','email_exists');$login='u_'.$phone;$internal=$email?:$login.'@phone.tager.local';$id=wp_insert_user(['user_login'=>$login,'user_pass'=>$pass,'user_email'=>$internal,'display_name'=>$name,'role'=>$vendor?'tager_vendor':'tager_customer']);if(is_wp_error($id))self::go($vendor?'vendor-register':'customer-register','missing');update_user_meta($id,'tager_phone',$phone);update_user_meta($id,'tager_email_optional',$email);if($vendor){foreach(['store_name','governorate','city','business_type','minimum_order','description'] as $k)update_user_meta($id,'tager_'.$k,sanitize_text_field($_POST[$k]??''));update_user_meta($id,'tager_vendor_status','pending');}wp_set_current_user($id);wp_set_auth_cookie($id,true);wp_safe_redirect(self::dashboard_url(get_user_by('id',$id)));exit;}
    public static function customer_register_action(){self::register(false);}public static function vendor_register_action(){self::register(true);}
    public static function forgot_action(){check_admin_referer('tager_v57_forgot');$u=self::find_user(wp_unslash($_POST['identity']??''));if($u&&is_email($u->user_email)&&!str_ends_with($u->user_email,'@phone.tager.local'))retrieve_password($u->user_login);self::go('forgot-password','reset_sent');}
    public static function login_redirect($r,$req,$u){return $u instanceof WP_User?self::dashboard_url($u):$r;}
    public static function governorates(){return ['القاهرة','الجيزة','الإسكندرية','الدقهلية','البحر الأحمر','البحيرة','الفيوم','الغربية','الإسماعيلية','المنوفية','المنيا','القليوبية','الوادي الجديد','السويس','أسوان','أسيوط','بني سويف','بورسعيد','دمياط','الشرقية','جنوب سيناء','كفر الشيخ','مطروح','الأقصر','قنا','شمال سيناء','سوهاج'];}
    public static function assets(){
        wp_register_style('tager-v57',false,[],self::VER);wp_enqueue_style('tager-v57');wp_add_inline_style('tager-v57',self::css());
        wp_register_script('tager-v57',false,[],self::VER,true);wp_enqueue_script('tager-v57');wp_add_inline_script('tager-v57',"document.addEventListener('submit',e=>{const b=e.target.querySelector('button[type=submit]');if(b){b.disabled=true;b.dataset.old=b.textContent;b.textContent='جاري الحفظ...';}});",'after');
    }
    private static function css(){return ':root{--tv57-green:#123c2d;--tv57-gold:#c79a35;--tv57-bg:#f5f7f5;--tv57-text:#17211d}.tv57-shell,.tv57-auth{max-width:1180px;margin:28px auto;padding:20px}.tv57-narrow{max-width:720px}.tv57-auth{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:stretch}.tv57-auth-side{background:linear-gradient(145deg,#123c2d,#1f634b);color:#fff;padding:44px;border-radius:28px;box-shadow:0 18px 50px rgba(18,60,45,.2)}.tv57-auth-side h1{font-size:42px;margin:14px 0}.tv57-auth-side li{margin:12px 0}.tv57-card,.tv57-choice{background:#fff;border:1px solid #e2e8e4;border-radius:24px;padding:28px;box-shadow:0 12px 35px rgba(18,60,45,.08)}.tv57-form-card label{display:block;font-weight:800;margin:14px 0 7px}.tv57-form-card input,.tv57-form-card select,.tv57-form-card textarea,.tv57-card input{width:100%;padding:14px;border:1px solid #cfdad3;border-radius:12px;background:#fff}.tv57-check{display:flex!important;gap:8px;align-items:center}.tv57-check input{width:auto!important}.tv57-btn{display:inline-flex;align-items:center;justify-content:center;background:var(--tv57-green);color:#fff!important;text-decoration:none;border:0;border-radius:12px;padding:13px 20px;font-weight:900;cursor:pointer}.tv57-btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(18,60,45,.18)}.tv57-secondary{background:#fff;color:var(--tv57-green)!important;border:1px solid var(--tv57-green)}.tv57-wide{width:100%;margin-top:16px}.tv57-links{display:flex;justify-content:space-between;gap:12px;margin-top:18px}.tv57-kicker{font-size:12px;font-weight:900;letter-spacing:1.6px;color:var(--tv57-gold)}.tv57-head{text-align:center;margin-bottom:24px}.tv57-head h1{font-size:38px;margin:10px 0}.tv57-grid2{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}.tv57-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.tv57-choice h2,.tv57-choice h3{color:var(--tv57-green)}.tv57-alert{padding:12px 15px;background:#fff4d8;border:1px solid #efd28a;border-radius:12px;margin-bottom:16px}@media(max-width:800px){.tv57-auth,.tv57-grid2,.tv57-grid3{grid-template-columns:1fr}.tv57-auth-side{padding:28px}.tv57-auth-side h1,.tv57-head h1{font-size:30px}.tv57-shell,.tv57-auth{padding:12px;margin:12px auto}.tv57-links{flex-direction:column}}';}
    public static function menu(){add_menu_page('Tager V57','Tager V57','manage_options','tager-v57',[__CLASS__,'admin_page'],'dashicons-yes-alt',2);}
    public static function admin_page(){if(!current_user_can('manage_options'))return;$rows=[];foreach(self::defs() as $slug=>$d){$p=get_page_by_path($slug,OBJECT,'page');$short=$p&&preg_match('/\[([^\s\]]+)/',$p->post_content,$m)?$m[1]:'';$ok=$p&&trim($p->post_content)!==''&&(!$short||shortcode_exists($short));$rows[]=[$slug,$d[0],$p,$ok,$short];}?><div class="wrap"><h1>Tager V57 — الصفحات الحقيقية</h1><p>هذا الإصدار يعيد بناء الصفحات الأساسية بمحتوى ونماذج عاملة بدل صفحات العناوين الفارغة.</p><p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v57_repair'),'tager_v57_repair'));?>">إعادة إنشاء وربط الصفحات</a> <a class="button" target="_blank" href="<?php echo esc_url(self::url('login'));?>">اختبار تسجيل الدخول</a> <a class="button" target="_blank" href="<?php echo esc_url(self::url('customer-register'));?>">اختبار تسجيل العميل</a> <a class="button" target="_blank" href="<?php echo esc_url(self::url('vendor-register'));?>">اختبار تسجيل المورد</a></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>الشورت كود</th><th>الحالة</th><th>فتح</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?php echo esc_html($r[1]);?></strong></td><td><code>/<?php echo esc_html($r[0]);?>/</code></td><td><code><?php echo esc_html($r[4]);?></code></td><td style="color:<?php echo $r[3]?'green':'#b42318';?>"><?php echo $r[3]?'جاهزة':'تحتاج إصلاح';?></td><td><?php if($r[2]):?><a target="_blank" href="<?php echo esc_url(get_permalink($r[2]));?>">فتح</a><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php }
}
Tager_V57_Complete_Live_Pages::init();
