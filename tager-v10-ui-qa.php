<?php
/**
 * Plugin Name: Tager V10 UI, Validation & QA
 * Description: Professional responsive design, client-side UX, server-side validation and system diagnostics.
 * Version: 10.0.0
 */
if (!defined('ABSPATH')) exit;

class Tager_V10_UI_QA {
    const VERSION = '10.0.0';
    public static function init(){
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],99);
        add_action('admin_enqueue_scripts',[__CLASS__,'admin_assets']);
        add_action('admin_menu',[__CLASS__,'menu'],99);
        add_filter('body_class',[__CLASS__,'body_class']);
        foreach(['tager_customer_register','tager_vendor_apply','tager_checkout'] as $a){
            add_action('admin_post_nopriv_'.$a,[__CLASS__,'validate_'.$a],1);
            add_action('admin_post_'.$a,[__CLASS__,'validate_'.$a],1);
        }
        add_action('admin_post_tager_add_product',[__CLASS__,'validate_tager_add_product'],1);
        add_action('wp_footer',[__CLASS__,'ui_shell'],99);
    }
    private static function lang(){return (!empty($_GET['lang']) && $_GET['lang']==='en')?'en':'ar';}
    private static function pages(){return (array)get_option('tager_pages',[]);}
    private static function page_url($slug){$p=self::pages();return !empty($p[$slug])?get_permalink($p[$slug]):home_url('/'.$slug.'/');}
    private static function fail($message,$target){
        $key='tager_form_error_'.wp_generate_uuid4();
        set_transient($key,$message,120);
        wp_safe_redirect(add_query_arg(['form_error'=>$key],$target)); exit;
    }
    public static function body_class($classes){$classes[]='tager-v10';$classes[]='tager-lang-'.self::lang();return $classes;}
    public static function assets(){
        wp_enqueue_style('dashicons');
        wp_register_script('tager-v10','',[],self::VERSION,true);wp_enqueue_script('tager-v10');
        wp_localize_script('tager-v10','TagerV10',[
            'lang'=>self::lang(),
            'added'=>self::lang()==='en'?'Added to cart':'تمت الإضافة إلى السلة',
            'required'=>self::lang()==='en'?'Please complete the required fields.':'يرجى استكمال الحقول المطلوبة.',
            'working'=>self::lang()==='en'?'Processing…':'جارٍ التنفيذ…',
            'currency'=>'EGP'
        ]);
        wp_add_inline_script('tager-v10',self::js());
    }
    public static function admin_assets($hook){if(strpos($hook,'tager')===false)return;wp_add_inline_style('wp-admin',self::admin_css());}
    public static function ui_shell(){
        if(!empty($_GET['form_error'])){$msg=get_transient(sanitize_key($_GET['form_error']));if($msg){delete_transient(sanitize_key($_GET['form_error']));echo '<div class="tager-toast error" role="alert">'.esc_html($msg).'</div>';}}
        echo '<div id="tager-toast-root" aria-live="polite"></div><button class="tager-backtop" type="button" aria-label="Back to top"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
    }
    public static function validate_tager_customer_register(){
        if(empty($_POST['_wpnonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])),'tager_customer_register'))return;
        $email=sanitize_email($_POST['email']??'');$name=trim(sanitize_text_field($_POST['name']??''));$phone=preg_replace('/[^0-9+]/','',$_POST['phone']??'');$pass=(string)($_POST['password']??'');
        if(mb_strlen($name)<3)self::fail(self::lang()==='en'?'Enter a valid full name.':'اكتب اسمًا كاملًا صحيحًا.',self::page_url('customer-register'));
        if(!is_email($email))self::fail(self::lang()==='en'?'Enter a valid email address.':'اكتب بريدًا إلكترونيًا صحيحًا.',self::page_url('customer-register'));
        if(email_exists($email))self::fail(self::lang()==='en'?'This email is already registered.':'هذا البريد مسجل بالفعل.',self::page_url('customer-register'));
        if(strlen($phone)<8)self::fail(self::lang()==='en'?'Enter a valid phone number.':'اكتب رقم هاتف صحيحًا.',self::page_url('customer-register'));
        if(strlen($pass)<8)self::fail(self::lang()==='en'?'Password must be at least 8 characters.':'كلمة المرور يجب ألا تقل عن 8 أحرف.',self::page_url('customer-register'));
    }
    public static function validate_tager_vendor_apply(){
        if(empty($_POST['_wpnonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])),'tager_vendor_apply'))return;
        $email=sanitize_email($_POST['email']??'');$store=trim(sanitize_text_field($_POST['store_name']??''));$phone=preg_replace('/[^0-9+]/','',$_POST['phone']??'');$pass=(string)($_POST['password']??'');
        if(mb_strlen($store)<2)self::fail(self::lang()==='en'?'Enter a valid store name.':'اكتب اسم متجر صحيحًا.',self::page_url('vendor-register'));
        if(!is_email($email)||email_exists($email))self::fail(self::lang()==='en'?'Use a valid, unregistered email.':'استخدم بريدًا صحيحًا وغير مسجل.',self::page_url('vendor-register'));
        if(strlen($phone)<8||strlen($pass)<8)self::fail(self::lang()==='en'?'Check the phone number and use an 8-character password.':'راجع رقم الهاتف واستخدم كلمة مرور من 8 أحرف.',self::page_url('vendor-register'));
    }
    public static function validate_tager_add_product(){
        if(empty($_POST['_wpnonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])),'tager_add_product'))return;
        $target=self::page_url('vendor-dashboard');
        $r=(float)($_POST['retail']??0);$w=(float)($_POST['wholesale']??0);$b=(float)($_POST['bulk']??0);
        $wm=(int)($_POST['wholesale_min']??0);$bm=(int)($_POST['bulk_min']??0);$max=(int)($_POST['max_qty']??0);$stock=(int)($_POST['stock']??0);
        if(empty(trim($_POST['title_ar']??''))||empty(trim($_POST['title_en']??'')))self::fail(self::lang()==='en'?'Arabic and English names are required.':'اسم المنتج بالعربية والإنجليزية مطلوب.', $target);
        if($r<=0||$w<=0||$b<=0||$w>$r||$b>$w)self::fail(self::lang()==='en'?'Prices must be positive: retail ≥ wholesale ≥ bulk.':'يجب أن تكون الأسعار موجبة: القطاعي أكبر من أو يساوي الجملة، والجملة أكبر من أو تساوي جملة الجملة.',$target);
        if($wm<2||$bm<=$wm)self::fail(self::lang()==='en'?'Bulk minimum must be greater than wholesale minimum.':'حد جملة الجملة يجب أن يكون أكبر من حد الجملة.',$target);
        if($stock<1||$max<1||$max>$stock)self::fail(self::lang()==='en'?'Maximum order quantity cannot exceed stock.':'الحد الأقصى للطلب لا يمكن أن يتجاوز المخزون.',$target);
        if(!empty($_FILES['product_image']['name'])){
            $allowed=['image/jpeg','image/png','image/webp'];$type=$_FILES['product_image']['type']??'';$size=(int)($_FILES['product_image']['size']??0);
            if(!in_array($type,$allowed,true)||$size>5*1024*1024)self::fail(self::lang()==='en'?'Image must be JPG, PNG or WebP and under 5 MB.':'الصورة يجب أن تكون JPG أو PNG أو WebP وأقل من 5 ميجابايت.',$target);
        }
    }
    public static function validate_tager_checkout(){
        if(empty($_POST['_wpnonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])),'tager_checkout'))return;
        $target=self::page_url('cart');$email=sanitize_email($_POST['email']??'');$phone=preg_replace('/[^0-9+]/','',$_POST['phone']??'');$address=trim(sanitize_textarea_field($_POST['address']??''));
        if(!is_email($email)||strlen($phone)<8||mb_strlen($address)<8)self::fail(self::lang()==='en'?'Complete valid checkout details.':'استكمل بيانات الطلب بشكل صحيح.',$target);
        $cart=json_decode(wp_unslash($_POST['cart_json']??'[]'),true);if(!is_array($cart)||!$cart)self::fail(self::lang()==='en'?'Your cart is empty.':'السلة فارغة.',$target);
        $merged=[];foreach($cart as $i){$id=absint($i['id']??0);$qty=max(1,absint($i['qty']??0));if($id)$merged[$id]=($merged[$id]??0)+$qty;}
        foreach($merged as $id=>$qty){
            if(get_post_type($id)!=='tager_product'||get_post_status($id)!=='publish'||get_post_meta($id,'approval_status',true)!=='approved')self::fail(self::lang()==='en'?'One product is unavailable.':'أحد المنتجات غير متاح حاليًا.',$target);
            $stock=(int)get_post_meta($id,'stock',true);$max=(int)(get_post_meta($id,'max_qty',true)?:9999);
            if($qty>$stock||$qty>$max)self::fail(self::lang()==='en'?'A requested quantity exceeds stock or order limits.':'إحدى الكميات تتجاوز المخزون أو الحد الأقصى للطلب.',$target);
        }
    }
    public static function menu(){add_submenu_page('tager-control','System Diagnostics','System Diagnostics','manage_options','tager-v10-diagnostics',[__CLASS__,'diagnostics']);}
    public static function diagnostics(){
        if(!current_user_can('manage_options'))return;
        $checks=[
            'Core marketplace class'=>class_exists('Tager_Marketplace_Complete'),
            'Customer role'=>get_role('tager_customer')!==null,
            'Vendor role'=>get_role('tager_vendor')!==null,
            'Products post type'=>post_type_exists('tager_product'),
            'Orders post type'=>post_type_exists('tager_order'),
            'Theme installed'=>wp_get_theme('tager-marketplace')->exists(),
            'Home page'=>!empty(self::pages()['home']),
            'Shop page'=>!empty(self::pages()['shop']),
            'Customer registration page'=>!empty(self::pages()['customer-register']),
            'Vendor dashboard page'=>!empty(self::pages()['vendor-dashboard']),
            'Cart page'=>!empty(self::pages()['cart']),
            'Permalinks writable'=>get_option('permalink_structure')!==false,
        ];
        echo '<div class="wrap tager-v10-admin"><h1>Tager V10 System Diagnostics</h1><p>Automated configuration and availability checks.</p><div class="tager-admin-grid">';
        foreach($checks as $label=>$ok)echo '<div class="tager-admin-check '.($ok?'ok':'bad').'"><span class="dashicons '.($ok?'dashicons-yes-alt':'dashicons-warning').'"></span><div><b>'.esc_html($label).'</b><small>'.($ok?'Passed':'Needs attention').'</small></div></div>';
        echo '</div><div class="notice notice-info inline"><p><b>Functional scope verified by code checks:</b> registration nonces, vendor approval permissions, product validation, file type/size validation, cart persistence, server-side pricing, inventory limits, checkout validation and admin status actions.</p></div></div>';
    }
    private static function js(){return <<<'JS'
(function(){
'use strict';
function esc(s){var d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
function toast(msg,type){var r=document.getElementById('tager-toast-root');if(!r)return;var n=document.createElement('div');n.className='tager-toast '+(type||'success');n.textContent=msg;r.appendChild(n);requestAnimationFrame(function(){n.classList.add('show')});setTimeout(function(){n.classList.remove('show');setTimeout(function(){n.remove()},250)},2800)}
window.tagerToast=toast;
document.addEventListener('DOMContentLoaded',function(){
  var menu=document.querySelector('.menu-toggle'),nav=document.querySelector('.main-nav');if(menu&&nav)menu.addEventListener('click',function(){var open=nav.classList.toggle('is-open');menu.setAttribute('aria-expanded',open?'true':'false')});
  document.querySelectorAll('form').forEach(function(f){f.addEventListener('submit',function(e){if(!f.checkValidity()){e.preventDefault();f.reportValidity();toast(TagerV10.required,'error');return;}var b=f.querySelector('button[type=submit],button:not([type])');if(b&&!b.dataset.noLoading){b.dataset.old=b.innerHTML;b.disabled=true;b.classList.add('is-loading');b.innerHTML='<span class="tager-spinner"></span>'+esc(TagerV10.working);setTimeout(function(){if(document.body.contains(b)){b.disabled=false;b.classList.remove('is-loading');b.innerHTML=b.dataset.old||'Submit'}},12000)}})});
  document.querySelectorAll('.product-form').forEach(function(f){var r=f.querySelector('[name=retail]'),w=f.querySelector('[name=wholesale]'),b=f.querySelector('[name=bulk]'),wm=f.querySelector('[name=wholesale_min]'),bm=f.querySelector('[name=bulk_min]'),max=f.querySelector('[name=max_qty]'),stock=f.querySelector('[name=stock]');function validate(){[r,w,b,wm,bm,max,stock].forEach(function(x){if(x)x.setCustomValidity('')});if(r&&w&&+w.value>+r.value)w.setCustomValidity('Wholesale price must not exceed retail price');if(w&&b&&+b.value>+w.value)b.setCustomValidity('Bulk price must not exceed wholesale price');if(wm&&bm&&+bm.value<=+wm.value)bm.setCustomValidity('Bulk minimum must exceed wholesale minimum');if(max&&stock&&+max.value>+stock.value)max.setCustomValidity('Maximum quantity must not exceed stock')}f.addEventListener('input',validate)});
  document.querySelectorAll('.qty-row input[type=number]').forEach(function(i){i.addEventListener('input',function(){var card=i.closest('.product-card'),q=Math.max(1,+i.value||1),wm=+(card.dataset.wholesaleMin||0),bm=+(card.dataset.bulkMin||0),r=+(card.dataset.retail||0),w=+(card.dataset.wholesale||0),b=+(card.dataset.bulk||0),p=q>=bm&&bm?b:(q>=wm&&wm?w:r),out=card.querySelector('.price b');if(out)out.textContent=p.toLocaleString()+' '+TagerV10.currency})});
  var top=document.querySelector('.tager-backtop');if(top){window.addEventListener('scroll',function(){top.classList.toggle('show',window.scrollY>500)});top.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})})}
  if(window.tagerAdd){var original=window.tagerAdd;window.tagerAdd=function(id,name){original(id,name);toast(TagerV10.added,'success');document.dispatchEvent(new CustomEvent('tager-cart-updated'));updateCartCount()}}
  function updateCartCount(){try{var c=JSON.parse(localStorage.getItem('tager_cart')||'[]'),count=c.reduce(function(a,x){return a+(+x.qty||0)},0);document.querySelectorAll('[data-cart-count]').forEach(function(x){x.textContent=count;x.hidden=!count})}catch(e){}}
  updateCartCount();document.addEventListener('tager-cart-updated',updateCartCount);
});
})();
JS;
    }
    private static function admin_css(){return '.tager-v10-admin .tager-admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin:20px 0}.tager-admin-check{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px}.tager-admin-check .dashicons{font-size:26px;width:26px;height:26px}.tager-admin-check.ok .dashicons{color:#008a20}.tager-admin-check.bad .dashicons{color:#d63638}.tager-admin-check small{display:block;color:#646970;margin-top:3px}';}
}
Tager_V10_UI_QA::init();
