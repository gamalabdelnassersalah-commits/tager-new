<?php
/**
 * Plugin Name: Tager V53 Complete Data Pages
 * Description: Completes customer, vendor and admin pages; ensures buttons lead to populated pages and audits missing data.
 * Version: 53.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V53_Complete_Data_Pages {
    const NONCE='tager_v53_nonce';
    public static function init(){
        add_action('init',[__CLASS__,'register']);
        add_action('admin_menu',[__CLASS__,'admin_menu']);
        add_action('admin_post_tager_v53_repair',[__CLASS__,'repair_action']);
        add_shortcode('tager_v53_customer_hub',[__CLASS__,'customer_hub']);
        add_shortcode('tager_v53_vendor_hub',[__CLASS__,'vendor_hub']);
        add_shortcode('tager_v53_admin_hub',[__CLASS__,'admin_hub']);
        add_shortcode('tager_v53_product_details',[__CLASS__,'product_details']);
        add_shortcode('tager_v53_vendor_details',[__CLASS__,'vendor_details']);
        add_shortcode('tager_v53_data_status',[__CLASS__,'data_status']);
        add_filter('the_content',[__CLASS__,'append_context_navigation'],99);
        add_action('wp_footer',[__CLASS__,'frontend_guard']);
    }
    private static function url($slug,$args=[]){$p=get_page_by_path($slug);$u=$p?get_permalink($p):home_url('/'.trim($slug,'/').'/');return $args?add_query_arg($args,$u):$u;}
    private static function roles(){ if(!is_user_logged_in()) return []; $u=wp_get_current_user(); return (array)$u->roles; }
    private static function is_vendor(){return (bool)array_intersect(self::roles(),['tager_vendor','wcfm_vendor','vendor']);}
    private static function is_admin_team(){return current_user_can('manage_options') || (bool)array_intersect(self::roles(),['tager_admin','tager_ops_manager','tager_vendor_manager','tager_catalog_manager','tager_orders_manager','tager_finance','tager_support','tager_marketing','tager_viewer']);}
    private static function pages(){
        return [
            'customer-center'=>['مركز العميل','[tager_v53_customer_hub]'],
            'vendor-center'=>['مركز المورد','[tager_v53_vendor_hub]'],
            'admin-center'=>['مركز الإدارة','[tager_v53_admin_hub]'],
            'product-details'=>['تفاصيل المنتج','[tager_v53_product_details]'],
            'vendor-details'=>['تفاصيل المورد','[tager_v53_vendor_details]'],
            'data-status'=>['حالة اكتمال البيانات','[tager_v53_data_status]'],
        ];
    }
    public static function register(){
        foreach(self::pages() as $slug=>$d){
            $p=get_page_by_path($slug);
            if(!$p){wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1]]);}
            elseif(trim((string)$p->post_content)==='' || strpos((string)$p->post_content,'tager_v53_')===false){wp_update_post(['ID'=>$p->ID,'post_content'=>$d[1]]);}
        }
    }
    private static function card($title,$value,$link='',$note=''){
        $v=is_numeric($value)?number_format_i18n((float)$value):$value;
        return '<div class="tv53-card"><div class="tv53-label">'.esc_html($title).'</div><div class="tv53-value">'.esc_html($v).'</div>'.($note?'<div class="tv53-note">'.esc_html($note).'</div>':'').($link?'<a class="tv53-btn" href="'.esc_url($link).'">فتح التفاصيل</a>':'').'</div>';
    }
    private static function user_profile_completeness($uid){
        $fields=['first_name','tager_phone','tager_governorate','tager_city','tager_address']; $ok=0;
        foreach($fields as $f){$v=$f==='first_name'?get_user_meta($uid,'first_name',true):get_user_meta($uid,$f,true);if(trim((string)$v)!=='')$ok++;}
        return (int)round($ok/count($fields)*100);
    }
    public static function customer_hub(){
        if(!is_user_logged_in()) return self::login_notice();
        if(self::is_vendor()||self::is_admin_team()) return '<div class="tv53-alert">هذه الصفحة مخصصة للعملاء.</div>';
        $uid=get_current_user_id();
        $orders=get_posts(['post_type'=>'tager_order','author'=>$uid,'posts_per_page'=>-1,'post_status'=>'any']);
        $total=0;$active=0;foreach($orders as $o){$total+=(float)get_post_meta($o->ID,'total',true);$s=get_post_meta($o->ID,'status',true);if(!in_array($s,['completed','cancelled'],true))$active++;}
        $addresses=get_user_meta($uid,'tager_addresses',true);$addr_count=is_array($addresses)?count($addresses):0;
        $html=self::style().'<section class="tv53-shell"><div class="tv53-head"><div><h1>مركز العميل</h1><p>كل بيانات حسابك وطلباتك وروابط المتابعة في مكان واحد.</p></div><a class="tv53-btn secondary" href="'.esc_url(self::url('products')).'">تصفح السوق</a></div><div class="tv53-grid">';
        $html.=self::card('اكتمال الحساب',self::user_profile_completeness($uid).'%',self::url('customer-profile'),'أكمل بياناتك لتسريع الطلبات');
        $html.=self::card('إجمالي الطلبات',count($orders),self::url('customer-orders'));
        $html.=self::card('طلبات نشطة',$active,self::url('customer-orders'));
        $html.=self::card('إجمالي المشتريات',$total.' ج.م',self::url('customer-orders'));
        $html.=self::card('العناوين المحفوظة',$addr_count,self::url('customer-addresses'));
        $html.=self::card('المفضلة',count((array)get_user_meta($uid,'tager_favorites',true)),self::url('favorites'));
        $html.='</div><div class="tv53-links">'.self::linkset('customer').'</div></section>';
        return $html;
    }
    public static function vendor_hub(){
        if(!is_user_logged_in()) return self::login_notice();
        if(!self::is_vendor()&&!self::is_admin_team()) return '<div class="tv53-alert">هذه الصفحة مخصصة للموردين.</div>';
        $uid=get_current_user_id();
        $products=get_posts(['post_type'=>'tager_product','author'=>$uid,'posts_per_page'=>-1,'post_status'=>'any']);
        $published=0;$pending=0;$low=0;$sales=0;foreach($products as $p){if($p->post_status==='publish')$published++;else$pending++;$stock=(float)get_post_meta($p->ID,'stock',true);if($stock<=5)$low++;}
        $orders=get_posts(['post_type'=>'tager_order','posts_per_page'=>-1,'post_status'=>'any','meta_query'=>[['key'=>'vendor_ids','value'=>'"'.$uid.'"','compare'=>'LIKE']]]);
        foreach($orders as $o){$sales+=(float)get_post_meta($o->ID,'vendor_total_'.$uid,true);}
        $store_fields=['tager_store_name','tager_phone','tager_vendor_logo','tager_governorate','tager_city','tager_vendor_min_order'];$ok=0;foreach($store_fields as $f)if(trim((string)get_user_meta($uid,$f,true))!=='')$ok++;$ready=(int)round($ok/count($store_fields)*100);
        $html=self::style().'<section class="tv53-shell"><div class="tv53-head"><div><h1>مركز المورد</h1><p>متابعة المنتجات والطلبات والمبيعات وتجهيز المتجر.</p></div><a class="tv53-btn" href="'.esc_url(self::url('vendor-product-form')).'">إضافة منتج</a></div><div class="tv53-grid">';
        $html.=self::card('جاهزية المتجر',$ready.'%',self::url('vendor-store-settings'));
        $html.=self::card('إجمالي المنتجات',count($products),self::url('vendor-products'));
        $html.=self::card('منتجات منشورة',$published,self::url('vendor-products'));
        $html.=self::card('تحت المراجعة',$pending,self::url('vendor-products'));
        $html.=self::card('مخزون منخفض',$low,self::url('vendor-inventory'));
        $html.=self::card('إجمالي المبيعات',$sales.' ج.م',self::url('vendor-finance'));
        $html.='</div><div class="tv53-links">'.self::linkset('vendor').'</div></section>';
        return $html;
    }
    public static function admin_hub(){
        if(!self::is_admin_team()) return '<div class="tv53-alert">ليس لديك صلاحية الإدارة.</div>';
        $vendors=get_users(['role__in'=>['tager_vendor','wcfm_vendor','vendor']]);$customers=get_users(['role__in'=>['customer','subscriber','tager_customer']]);
        $pending_v=0;foreach($vendors as $v)if(get_user_meta($v->ID,'tager_vendor_status',true)!=='approved')$pending_v++;
        $pending_p=wp_count_posts('tager_product')->pending??0;$published=wp_count_posts('tager_product')->publish??0;$orders=wp_count_posts('tager_order');$order_count=0;foreach((array)$orders as $k=>$v)if(!in_array($k,['auto-draft','trash'],true))$order_count+=(int)$v;
        $html=self::style().'<section class="tv53-shell"><div class="tv53-head"><div><h1>مركز الإدارة</h1><p>مؤشرات التشغيل وروابط التحكم الرئيسية.</p></div><a class="tv53-btn" href="'.esc_url(admin_url('admin.php?page=tager-v53')).'">فحص البيانات والصفحات</a></div><div class="tv53-grid">';
        $html.=self::card('الموردون',count($vendors),self::url('admin-vendors'));
        $html.=self::card('موردون ينتظرون الموافقة',$pending_v,self::url('admin-vendors'));
        $html.=self::card('العملاء',count($customers),self::url('admin-customers'));
        $html.=self::card('منتجات تحت المراجعة',$pending_p,self::url('admin-products'));
        $html.=self::card('منتجات منشورة',$published,self::url('admin-products'));
        $html.=self::card('الطلبات',$order_count,self::url('admin-orders'));
        $html.='</div><div class="tv53-links">'.self::linkset('admin').'</div></section>';
        return $html;
    }
    public static function product_details(){
        $id=absint($_GET['product_id']??0);if(!$id)return self::empty_state('اختر منتجًا من صفحة السوق لعرض كل بياناته.',self::url('products'),'فتح السوق');
        $p=get_post($id);if(!$p||$p->post_type!=='tager_product')return self::empty_state('المنتج غير موجود.',self::url('products'),'العودة للسوق');
        $fields=['retail_price'=>'سعر القطاعي','wholesale_price'=>'سعر الجملة','bulk_price'=>'سعر جملة الجملة','wholesale_min'=>'حد الجملة','bulk_min'=>'حد جملة الجملة','max_order'=>'الحد الأقصى','stock'=>'المخزون','lead_time'=>'مدة التجهيز','brand'=>'العلامة التجارية','unit'=>'وحدة البيع'];
        $vendor=get_userdata((int)$p->post_author);$html=self::style().'<section class="tv53-shell"><div class="tv53-head"><div><h1>'.esc_html($p->post_title).'</h1><p>'.esc_html(wp_trim_words(wp_strip_all_tags($p->post_content),35)).'</p></div><a class="tv53-btn secondary" href="'.esc_url(self::url('vendor-details',['vendor_id'=>$p->post_author])).'">عرض المورد</a></div><div class="tv53-grid">';
        foreach($fields as $key=>$label){$v=get_post_meta($id,$key,true);if($v==='')$v='غير محدد';$html.=self::card($label,$v);}
        $html.='</div><div class="tv53-panel"><h2>بيانات المورد</h2><p><strong>'.esc_html(get_user_meta($p->post_author,'tager_store_name',true)?:($vendor?$vendor->display_name:'مورد')).'</strong></p><p>'.esc_html(get_user_meta($p->post_author,'tager_governorate',true)).' — '.esc_html(get_user_meta($p->post_author,'tager_city',true)).'</p></div></section>';
        return $html;
    }
    public static function vendor_details(){
        $id=absint($_GET['vendor_id']??0);$u=$id?get_userdata($id):false;if(!$u)return self::empty_state('اختر موردًا من دليل الموردين.',self::url('vendors'),'فتح دليل الموردين');
        $name=get_user_meta($id,'tager_store_name',true)?:$u->display_name;$products=get_posts(['post_type'=>'tager_product','author'=>$id,'post_status'=>'publish','posts_per_page'=>12]);
        $html=self::style().'<section class="tv53-shell"><div class="tv53-head"><div><h1>'.esc_html($name).'</h1><p>'.esc_html(get_user_meta($id,'tager_store_description',true)).'</p></div><a class="tv53-btn" href="'.esc_url(self::url('products',['vendor_id'=>$id])).'">كل منتجات المورد</a></div><div class="tv53-grid">';
        foreach(['tager_governorate'=>'المحافظة','tager_city'=>'المركز/المدينة','tager_vendor_min_order'=>'الحد الأدنى للطلب','tager_phone'=>'الهاتف','tager_delivery_days'=>'مدة التوصيل'] as $k=>$l){$html.=self::card($l,get_user_meta($id,$k,true)?:'غير محدد');}
        $html.=self::card('عدد المنتجات',count($products));$html.='</div><h2>منتجات المورد</h2><div class="tv53-grid">';foreach($products as $p){$price=get_post_meta($p->ID,'retail_price',true);$html.='<div class="tv53-card"><h3>'.esc_html($p->post_title).'</h3><p>'.esc_html($price).' ج.م</p><a class="tv53-btn" href="'.esc_url(self::url('product-details',['product_id'=>$p->ID])).'">عرض كل البيانات</a></div>';}$html.='</div></section>';return $html;
    }
    public static function data_status(){
        if(!is_user_logged_in())return self::login_notice();$uid=get_current_user_id();
        $fields=self::is_vendor()?['tager_store_name'=>'اسم المتجر','tager_phone'=>'الهاتف','tager_governorate'=>'المحافظة','tager_city'=>'المركز','tager_vendor_min_order'=>'الحد الأدنى','tager_vendor_logo'=>'الشعار']:['first_name'=>'الاسم','tager_phone'=>'الهاتف','tager_governorate'=>'المحافظة','tager_city'=>'المدينة','tager_address'=>'العنوان'];
        $html=self::style().'<section class="tv53-shell"><h1>حالة اكتمال البيانات</h1><div class="tv53-panel"><table class="tv53-table"><thead><tr><th>البيان</th><th>الحالة</th><th>القيمة</th></tr></thead><tbody>';
        foreach($fields as $k=>$l){$v=$k==='first_name'?get_user_meta($uid,'first_name',true):get_user_meta($uid,$k,true);$html.='<tr><td>'.esc_html($l).'</td><td>'.($v!==''?'✅ مكتمل':'⚠️ ناقص').'</td><td>'.esc_html($v!==''?$v:'لم يتم إدخاله').'</td></tr>';}
        $html.='</tbody></table></div></section>';return $html;
    }
    private static function linkset($type){
        $sets=['customer'=>[['customer-profile','الملف الشخصي'],['customer-orders','طلباتي'],['customer-addresses','العناوين'],['favorites','المفضلة'],['saved-carts','السلات المحفوظة'],['support','الدعم'],['data-status','اكتمال البيانات']], 'vendor'=>[['vendor-products','منتجاتي'],['vendor-product-form','إضافة منتج'],['vendor-orders','الطلبات'],['vendor-inventory','المخزون'],['vendor-finance','الأرباح'],['vendor-store-settings','إعدادات المتجر'],['vendor-market','السوق'],['data-status','اكتمال البيانات']], 'admin'=>[['admin-vendors','الموردون'],['admin-products','المنتجات'],['admin-orders','الطلبات'],['admin-finance','المالية'],['admin-shipping','الشحن'],['admin-payments','الدفع'],['admin-team','فريق الإدارة'],['admin-reports','التقارير']]];
        $h='';foreach($sets[$type]??[] as $x)$h.='<a href="'.esc_url(self::url($x[0])).'">'.esc_html($x[1]).'</a>';return $h;
    }
    private static function login_notice(){return self::empty_state('سجّل الدخول للوصول إلى هذه الصفحة.',self::url('login'),'تسجيل الدخول');}
    private static function empty_state($msg,$url,$label){return self::style().'<div class="tv53-empty"><p>'.esc_html($msg).'</p><a class="tv53-btn" href="'.esc_url($url).'">'.esc_html($label).'</a></div>';}
    private static function style(){static $done=false;if($done)return '';$done=true;return '<style>.tv53-shell{max-width:1180px;margin:24px auto;padding:24px}.tv53-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.tv53-head h1{margin:0 0 6px}.tv53-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin:18px 0}.tv53-card,.tv53-panel,.tv53-empty{background:#fff;border:1px solid #e7e3d8;border-radius:18px;padding:18px;box-shadow:0 8px 24px rgba(0,0,0,.05)}.tv53-label{font-size:14px;color:#667085}.tv53-value{font-size:26px;font-weight:800;color:#173d2d;margin:8px 0}.tv53-note{font-size:12px;color:#777;margin-bottom:10px}.tv53-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border-radius:10px;background:#173d2d;color:#fff!important;text-decoration:none;font-weight:700}.tv53-btn.secondary{background:#b99342}.tv53-links{display:flex;flex-wrap:wrap;gap:10px}.tv53-links a{padding:10px 14px;border:1px solid #d9d2c3;border-radius:10px;text-decoration:none;background:#fff}.tv53-alert{padding:16px;border-radius:12px;background:#fff4e5;border:1px solid #f1c27d}.tv53-table{width:100%;border-collapse:collapse}.tv53-table th,.tv53-table td{padding:12px;border-bottom:1px solid #eee;text-align:right}@media(max-width:700px){.tv53-head{align-items:flex-start;flex-direction:column}.tv53-shell{padding:14px}.tv53-btn{width:100%}}</style>';}
    public static function append_context_navigation($content){if(!is_singular('page')||is_admin())return $content;$slug=get_post_field('post_name',get_queried_object_id());if(in_array($slug,array_keys(self::pages()),true))return $content; $nav='';if(self::is_admin_team())$nav='<div class="tv53-links"><a href="'.esc_url(self::url('admin-center')).'">مركز الإدارة</a></div>';elseif(self::is_vendor())$nav='<div class="tv53-links"><a href="'.esc_url(self::url('vendor-center')).'">مركز المورد</a><a href="'.esc_url(self::url('vendor-market')).'">السوق</a></div>';elseif(is_user_logged_in())$nav='<div class="tv53-links"><a href="'.esc_url(self::url('customer-center')).'">مركز العميل</a><a href="'.esc_url(self::url('products')).'">السوق</a></div>';return self::style().$nav.$content;}
    public static function frontend_guard(){if(is_admin())return;?><script>(function(){document.addEventListener('click',function(e){var a=e.target.closest('a,button');if(!a)return;var href=a.getAttribute('href');if(a.tagName==='A'&&(!href||href==='#'||href==='javascript:void(0)')){e.preventDefault();a.setAttribute('aria-disabled','true');a.title='هذا الرابط لم يكتمل بعد';} });document.querySelectorAll('form').forEach(function(f){f.addEventListener('submit',function(e){var bad=f.querySelector(':invalid');if(bad){e.preventDefault();bad.focus();return;}var b=f.querySelector('button[type=submit],input[type=submit]');if(b){b.disabled=true;b.dataset.oldText=b.innerHTML||b.value;if(b.tagName==='BUTTON')b.innerHTML='جاري الحفظ...';else b.value='جاري الحفظ...';setTimeout(function(){b.disabled=false;if(b.tagName==='BUTTON')b.innerHTML=b.dataset.oldText;else b.value=b.dataset.oldText;},8000);}});});})();</script><?php }
    public static function admin_menu(){add_menu_page('Tager V53','Tager V53','manage_options','tager-v53',[__CLASS__,'admin_screen'],'dashicons-yes-alt',3);}
    public static function admin_screen(){if(!current_user_can('manage_options'))return;$rows=[];foreach(self::pages() as $slug=>$d){$p=get_page_by_path($slug);$rows[]=[$slug,$d[0],$p,($p&&trim((string)$p->post_content)!=='')];}
        $products=get_posts(['post_type'=>'tager_product','posts_per_page'=>-1,'post_status'=>'any']);$missing=0;foreach($products as $p){foreach(['retail_price','wholesale_price','bulk_price','stock'] as $k)if(get_post_meta($p->ID,$k,true)===''){$missing++;break;}}
        echo '<div class="wrap"><h1>Tager V53 — اكتمال الصفحات والبيانات</h1><p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v53_repair'),self::NONCE)).'">إصلاح وإنشاء الصفحات الآن</a> <a class="button" target="_blank" href="'.esc_url(self::url('customer-center')).'">مركز العميل</a> <a class="button" target="_blank" href="'.esc_url(self::url('vendor-center')).'">مركز المورد</a> <a class="button" target="_blank" href="'.esc_url(self::url('admin-center')).'">مركز الإدارة</a></p><p><strong>منتجات تحتاج استكمال بيانات أساسية: '.intval($missing).'</strong></p><table class="widefat striped"><thead><tr><th>الصفحة</th><th>المسار</th><th>الحالة</th><th>فتح</th></tr></thead><tbody>';foreach($rows as $r){echo '<tr><td>'.esc_html($r[1]).'</td><td><code>/'.esc_html($r[0]).'/</code></td><td>'.($r[3]?'✅ مكتملة':'❌ ناقصة').'</td><td>'.($r[2]?'<a target="_blank" href="'.esc_url(get_permalink($r[2])).'">فتح</a>':'—').'</td></tr>';}echo '</tbody></table></div>';}
    public static function repair_action(){if(!current_user_can('manage_options'))wp_die('No permission');check_admin_referer(self::NONCE);foreach(self::pages() as $slug=>$d){$p=get_page_by_path($slug);if(!$p)wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1]]);else wp_update_post(['ID'=>$p->ID,'post_content'=>$d[1]]);}flush_rewrite_rules(false);wp_safe_redirect(admin_url('admin.php?page=tager-v53&repaired=1'));exit;}
}
Tager_V53_Complete_Data_Pages::init();
