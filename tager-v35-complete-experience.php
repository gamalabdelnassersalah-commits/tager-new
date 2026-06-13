<?php
/**
 * Plugin Name: Tager V35 Complete Experience
 * Description: Completes missing marketplace pages, enriches customer/vendor/admin experiences, and repairs navigation and content automatically.
 * Version: 35.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V35_Complete_Experience {
    public static function init(){
        add_action('init',[__CLASS__,'register_shortcodes'],500);
        add_action('init',[__CLASS__,'ensure_pages'],550);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],500);
        add_action('admin_menu',[__CLASS__,'admin_menu'],120);
        add_action('admin_post_tager_v35_repair',[__CLASS__,'repair_action']);
        add_filter('the_content',[__CLASS__,'content_guard'],120);
        add_filter('body_class',function($c){$c[]='tager-v35';return $c;});
    }
    private static function defs(){ return [
        'dashboard'=>['لوحة الحساب','[tager_v35_dashboard]'],
        'customer-profile'=>['الملف الشخصي','[tager_v35_customer_profile]'],
        'customer-payments'=>['وسائل الدفع المحفوظة','[tager_v35_customer_payments]'],
        'customer-rewards'=>['المكافآت والنقاط','[tager_v35_rewards]'],
        'customer-reviews'=>['تقييماتي','[tager_v35_reviews]'],
        'recently-viewed'=>['شوهد مؤخرًا','[tager_v35_recently_viewed]'],
        'vendor-products'=>['منتجات المورد','[tager_v35_vendor_products]'],
        'vendor-add-product'=>['إضافة منتج','[tager_v35_vendor_add_product]'],
        'vendor-orders'=>['طلبات المورد','[tager_v35_vendor_orders]'],
        'vendor-earnings'=>['أرباح المورد','[tager_v35_vendor_earnings]'],
        'vendor-withdrawals'=>['سحوبات المورد','[tager_v35_vendor_withdrawals]'],
        'vendor-coupons'=>['كوبونات المورد','[tager_v35_vendor_coupons]'],
        'vendor-analytics'=>['تحليلات المورد','[tager_v35_vendor_analytics]'],
        'vendor-settings'=>['إعدادات المتجر','[tager_v35_vendor_settings]'],
        'vendor-onboarding'=>['تهيئة حساب المورد','[tager_v35_vendor_onboarding]'],
        'admin-overview'=>['نظرة عامة للإدارة','[tager_v35_admin_overview]'],
        'admin-vendors'=>['إدارة الموردين','[tager_v35_admin_vendors]'],
        'admin-products'=>['مراجعة المنتجات','[tager_v35_admin_products]'],
        'admin-orders'=>['إدارة الطلبات','[tager_v35_admin_orders]'],
        'admin-finance'=>['الإدارة المالية','[tager_v35_admin_finance]'],
        'admin-content'=>['إدارة المحتوى','[tager_v35_admin_content]'],
        'site-map'=>['خريطة الموقع','[tager_v35_site_map]'],
        'accessibility'=>['إمكانية الوصول','[tager_v35_accessibility]'],
        'complaints'=>['الشكاوى والمقترحات','[tager_v35_complaints]'],
    ]; }
    public static function register_shortcodes(){
        foreach(array_keys(self::defs()) as $slug){ add_shortcode('tager_v35_'.str_replace('-','_',$slug), function() use($slug){ return Tager_V35_Complete_Experience::render($slug); }); }
    }
    public static function ensure_pages(){
        if(get_option('tager_v35_done') && !is_admin()) return;
        $pages=get_option('tager_pages',[]);
        foreach(self::defs() as $slug=>$d){
            $p=get_page_by_path($slug);
            if(!$p){ $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$d[0],'post_content'=>$d[1]]); }
            else { $id=$p->ID; if(trim(wp_strip_all_tags($p->post_content))==='') wp_update_post(['ID'=>$id,'post_content'=>$d[1],'post_title'=>$d[0]]); }
            if(!is_wp_error($id)) $pages[$slug]=(int)$id;
        }
        update_option('tager_pages',$pages); update_option('tager_v35_done',time());
    }
    private static function url($slug){ $p=get_option('tager_pages',[]); return !empty($p[$slug])?get_permalink($p[$slug]):home_url('/'.$slug.'/'); }
    private static function btn($label,$slug,$alt=false){ return '<a class="v35-btn'.($alt?' alt':'').'" href="'.esc_url(self::url($slug)).'">'.esc_html($label).'</a>'; }
    private static function shell($title,$lead,$content,$actions=''){
        return '<main class="v35-shell"><section class="v35-head"><div><span class="v35-kicker">Tager Marketplace</span><h1>'.esc_html($title).'</h1><p>'.esc_html($lead).'</p><div class="v35-actions">'.$actions.'</div></div><div class="v35-emblem">T</div></section>'.$content.'</main>';
    }
    private static function stat($n,$l){return '<article class="v35-stat"><strong>'.$n.'</strong><span>'.$l.'</span></article>';}
    private static function cards($items){$o='<div class="v35-grid">';foreach($items as $i){$o.='<article class="v35-card"><span class="v35-ico">'.$i[0].'</span><h3>'.esc_html($i[1]).'</h3><p>'.esc_html($i[2]).'</p>'.($i[3]??'').'</article>';}return $o.'</div>';}
    private static function protected($role='customer'){
        if(!is_user_logged_in()) return wp_login_form(['echo'=>false,'redirect'=>self::url($role==='vendor'?'vendor-dashboard':'dashboard'),'label_username'=>'رقم الهاتف أو البريد الإلكتروني']);
        return '';
    }
    public static function render($slug){
        $customer=self::protected('customer'); $vendor=self::protected('vendor');
        switch($slug){
            case 'dashboard':
                if($customer) return self::shell('لوحة الحساب','سجّل الدخول لعرض طلباتك وعناوينك ومفضلاتك.',$customer);
                $u=wp_get_current_user();
                $body='<section class="v35-stats">'.self::stat('—','طلبات نشطة').self::stat('—','إجمالي المشتريات').self::stat('—','نقاط المكافآت').self::stat('—','رسائل جديدة').'</section>';
                $body.=self::cards([['📦','طلباتي','متابعة الطلب والدفع والشحن.',self::btn('فتح الطلبات','orders')],['📍','العناوين','إدارة عناوين الاستلام.',self::btn('إدارة العناوين','addresses')],['❤️','المفضلة','المنتجات المحفوظة للشراء لاحقًا.',self::btn('فتح المفضلة','wishlist')],['🎧','الدعم','تواصل مع فريق تاجر.',self::btn('طلب مساعدة','support')]]);
                return self::shell('مرحبًا '.$u->display_name,'كل ما تحتاجه لإدارة مشترياتك في مكان واحد.',$body,self::btn('تصفح المنتجات','shop'));
            case 'customer-profile': return self::shell('الملف الشخصي','حدّث اسمك ورقم الهاتف والبريد الاختياري.',$customer?:'<section class="v35-panel"><h2>بيانات الحساب</h2><div class="v35-form"><label>الاسم الكامل<input value="'.esc_attr(wp_get_current_user()->display_name).'" /></label><label>رقم الهاتف<input placeholder="01xxxxxxxxx" /></label><label>البريد الإلكتروني (اختياري)<input type="email" /></label><button class="v35-btn">حفظ التغييرات</button></div></section>');
            case 'customer-payments': return self::shell('وسائل الدفع','إدارة وسائل الدفع المفضلة دون تخزين بيانات حساسة على الموقع.',self::cards([['💵','الدفع عند الاستلام','ادفع عند استلام طلبك.',''],['🏦','تحويل بنكي','ارفع إثبات التحويل من صفحة الطلب.',''],['📱','InstaPay والمحافظ','استخدم رقم العملية لتأكيد الدفع.',''],['💳','البطاقات','تُفعّل عند ربط بوابة الدفع.','']]));
            case 'customer-rewards': return self::shell('المكافآت والنقاط','تابع نقاطك والعروض المتاحة.',self::cards([['⭐','رصيد النقاط','يظهر بعد تفعيل برنامج الولاء.',''],['🎁','المكافآت','قسائم خصم وعروض خاصة.',''],['📈','سجل النقاط','تفاصيل الإضافة والاستخدام.','']]));
            case 'customer-reviews': return self::shell('تقييماتي','راجع تقييماتك السابقة والمنتجات التي تنتظر تقييمًا.',self::cards([['📝','في انتظار التقييم','الطلبات المكتملة القابلة للتقييم.',''],['🌟','التقييمات المنشورة','إدارة تقييماتك المنشورة.','']]));
            case 'recently-viewed': return self::shell('شوهد مؤخرًا','ارجع سريعًا إلى المنتجات التي شاهدتها.','<section class="v35-empty"><span>👀</span><h2>سيظهر سجل المشاهدة هنا</h2><p>ابدأ بتصفح المنتجات وسيحفظ النظام آخر المنتجات.</p>'.self::btn('تصفح المنتجات','shop').'</section>');
            case 'vendor-products': return self::shell('منتجات المورد','أدر المنتجات وحالات المراجعة والمخزون.',$vendor?:self::cards([['➕','إضافة منتج','أدخل الصور والأسعار والكميات. ',self::btn('إضافة منتج','vendor-add-product')],['📦','المنتجات المنشورة','تعديل الأسعار والمخزون.',''],['⏳','تحت المراجعة','منتجات تنتظر موافقة الإدارة.',''],['⚠️','مخزون منخفض','منتجات تحتاج إعادة توريد.','']]));
            case 'vendor-add-product':
                $form='<section class="v35-panel"><h2>بيانات المنتج</h2><div class="v35-form cols"><label>اسم المنتج بالعربية<input required></label><label>اسم المنتج بالإنجليزية<input></label><label>القسم<select><option>اختر القسم</option></select></label><label>SKU<input></label><label>سعر القطاعي<input type="number"></label><label>سعر الجملة<input type="number"></label><label>سعر جملة الجملة<input type="number"></label><label>المخزون<input type="number"></label><label>حد الجملة<input type="number"></label><label>حد جملة الجملة<input type="number"></label><label>الحد الأقصى للطلب<input type="number"></label><label>مدة التجهيز<select><option>نفس اليوم</option><option>1-2 يوم</option><option>3-5 أيام</option></select></label><label class="wide">وصف المنتج<textarea rows="5"></textarea></label><label class="wide">صور المنتج<input type="file" multiple accept="image/*"></label><button class="v35-btn wide">حفظ وإرسال للمراجعة</button></div></section>';
                return self::shell('إضافة منتج جديد','أكمل البيانات والأسعار والمخزون ثم أرسل المنتج للمراجعة.',$vendor?:$form);
            case 'vendor-orders': return self::shell('طلبات المورد','تابع الطلبات الجديدة والتجهيز والشحن.',$vendor?:self::cards([['🆕','طلبات جديدة','تحتاج قبول وتجهيز.',''],['📦','قيد التجهيز','طلبات تم قبولها.',''],['🚚','تم الشحن','طلبات في الطريق.',''],['✅','مكتملة','طلبات تم تسليمها.','']]));
            case 'vendor-earnings': return self::shell('أرباح المورد','ملخص المبيعات والعمولة وصافي المستحق.',$vendor?:'<section class="v35-stats">'.self::stat('—','إجمالي المبيعات').self::stat('—','عمولة المنصة').self::stat('—','صافي المستحق').self::stat('—','المتاح للسحب').'</section>');
            case 'vendor-withdrawals': return self::shell('طلبات السحب','اطلب تحويل مستحقاتك بعد اكتمال شروط السحب.',$vendor?:'<section class="v35-panel"><h2>طلب سحب جديد</h2><div class="v35-form"><label>المبلغ<input type="number"></label><label>طريقة التحويل<select><option>تحويل بنكي</option><option>محفظة إلكترونية</option></select></label><button class="v35-btn">إرسال الطلب</button></div></section>');
            case 'vendor-coupons': return self::shell('كوبونات المورد','أنشئ خصومات لمنتجات متجرك.',$vendor?:self::cards([['🏷️','كوبون جديد','نسبة أو مبلغ ثابت ومدة صلاحية.',''],['📊','استخدام الكوبونات','تابع عدد مرات الاستخدام.','']]));
            case 'vendor-analytics': return self::shell('تحليلات المورد','قراءة أداء المنتجات والطلبات والعملاء.',$vendor?:'<section class="v35-stats">'.self::stat('—','معدل التحويل').self::stat('—','متوسط الطلب').self::stat('—','أفضل منتج').self::stat('—','عملاء متكررون').'</section>');
            case 'vendor-settings': return self::shell('إعدادات المتجر','حدّث بيانات المتجر والحد الأدنى والبيانات البنكية.',$vendor?:'<section class="v35-panel"><div class="v35-form cols"><label>اسم المتجر<input></label><label>الهاتف<input></label><label>المحافظة<select><option>القاهرة</option><option>الجيزة</option><option>الإسكندرية</option></select></label><label>الحد الأدنى للطلب<input type="number"></label><label>البنك<input></label><label>IBAN<input></label><label class="wide">وصف المتجر<textarea></textarea></label><button class="v35-btn wide">حفظ الإعدادات</button></div></section>');
            case 'vendor-onboarding': return self::shell('تهيئة حساب المورد','أكمل الخطوات لتجهيز متجرك للبيع.','<section class="v35-checklist"><label><input type="checkbox"> بيانات المتجر</label><label><input type="checkbox"> مستندات النشاط</label><label><input type="checkbox"> بيانات التحويل البنكي</label><label><input type="checkbox"> الحد الأدنى للطلب</label><label><input type="checkbox"> إضافة أول منتج</label></section>');
            case 'admin-overview': return self::admin_page('نظرة عامة للإدارة',['موردون بانتظار المراجعة','منتجات بانتظار الموافقة','طلبات تحتاج متابعة','مدفوعات تحتاج تحقق']);
            case 'admin-vendors': return self::admin_page('إدارة الموردين',['طلبات انضمام جديدة','الموردون النشطون','الموردون الموقوفون','المستندات المنتهية']);
            case 'admin-products': return self::admin_page('مراجعة المنتجات',['منتجات جديدة','تعديلات تنتظر الموافقة','منتجات مخالفة','مخزون منخفض']);
            case 'admin-orders': return self::admin_page('إدارة الطلبات',['طلبات جديدة','منتظرة الدفع','قيد الشحن','نزاعات ومرتجعات']);
            case 'admin-finance': return self::admin_page('الإدارة المالية',['إثباتات دفع','طلبات سحب','عمولات المنصة','تقارير التسوية']);
            case 'admin-content': return self::admin_page('إدارة المحتوى',['الصفحة الرئيسية','العروض والبنرات','الصفحات القانونية','الأسئلة الشائعة']);
            case 'site-map':
                $items=''; foreach(array_merge(self::defs(),[]) as $s=>$d){$items.='<a href="'.esc_url(self::url($s)).'">'.esc_html($d[0]).'</a>';}
                return self::shell('خريطة الموقع','جميع الصفحات المهمة في مكان واحد.','<section class="v35-sitemap">'.$items.'</section>');
            case 'accessibility': return self::shell('إمكانية الوصول','التزامنا بتجربة واضحة وسهلة لكل المستخدمين.',self::cards([['⌨️','لوحة المفاتيح','دعم التنقل دون استخدام الماوس.',''],['🔎','وضوح النصوص','تباين وأحجام مناسبة.',''],['📱','تجاوب كامل','موبايل وتابلت وكمبيوتر.','']]));
            case 'complaints': return self::shell('الشكاوى والمقترحات','أرسل شكوى أو اقتراحًا وسيتم متابعته برقم مرجعي.','<section class="v35-panel"><div class="v35-form"><label>نوع الطلب<select><option>شكوى</option><option>اقتراح</option><option>بلاغ</option></select></label><label>رقم الهاتف<input required></label><label>رقم الطلب (اختياري)<input></label><label>التفاصيل<textarea rows="6"></textarea></label><button class="v35-btn">إرسال</button></div></section>');
        }
        return '';
    }
    private static function admin_page($title,$labels){
        if(!current_user_can('read')) return self::shell($title,'هذه الصفحة لفريق الإدارة فقط.','<div class="v35-empty">غير مصرح بالدخول.</div>');
        $c='<section class="v35-stats">'; foreach($labels as $x)$c.=self::stat('—',$x); $c.='</section><section class="v35-panel"><h2>إجراءات سريعة</h2><p>تظهر الإجراءات المتاحة حسب صلاحيات عضو الإدارة.</p></section>';
        return self::shell($title,'لوحة تشغيل موحدة للمتابعة واتخاذ الإجراءات.',$c);
    }
    public static function content_guard($content){
        if(!is_page() || is_admin()) return $content;
        if(trim(wp_strip_all_tags(strip_shortcodes($content)))==='' && strpos($content,'[')===false){
            return self::shell(get_the_title(),'هذه الصفحة جاهزة وسيتم ربط بياناتها بوحدة النظام المناسبة.','<section class="v35-empty"><span>🧩</span><h2>المحتوى قيد التجهيز</h2><p>استخدم الروابط التالية للعودة إلى المتجر أو مركز المساعدة.</p>'.self::btn('المنتجات','shop').self::btn('مركز المساعدة','help-center',true).'</section>');
        }
        return $content;
    }
    public static function assets(){
        wp_register_style('tager-v35',false); wp_enqueue_style('tager-v35');
        wp_add_inline_style('tager-v35','.v35-shell{display:grid;gap:24px}.v35-head{display:grid;grid-template-columns:1fr auto;gap:30px;align-items:center;padding:38px;border-radius:28px;background:linear-gradient(135deg,#063d2e,#0a7452);color:#fff;box-shadow:0 24px 55px rgba(3,66,45,.2)}.v35-head h1{font-size:clamp(30px,5vw,52px);margin:8px 0}.v35-head p{color:#d7eee5;max-width:760px}.v35-kicker{color:#ffd98b;font-weight:900}.v35-emblem{width:110px;height:110px;border-radius:28px;display:grid;place-items:center;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);font-size:55px;font-weight:900;color:#ffd98b}.v35-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.v35-btn{display:inline-flex;align-items:center;justify-content:center;min-height:45px;padding:11px 18px;border:0;border-radius:12px;background:#d6ad55;color:#173328!important;font-weight:900;text-decoration:none;cursor:pointer}.v35-btn.alt{background:#fff;color:#07543d!important;border:1px solid #d8e7df}.v35-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.v35-card,.v35-panel,.v35-stat,.v35-empty,.v35-checklist{background:#fff;border:1px solid #dfe9e4;border-radius:20px;padding:22px;box-shadow:0 10px 28px rgba(5,66,45,.06)}.v35-card{display:grid;align-content:start}.v35-card h3{margin:10px 0 5px}.v35-card p{color:#64766f;min-height:48px}.v35-ico{font-size:34px}.v35-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.v35-stat strong{font-size:27px;color:#07543d;display:block}.v35-stat span{color:#64766f}.v35-form{display:grid;gap:15px}.v35-form.cols{grid-template-columns:repeat(2,1fr)}.v35-form label{display:grid;gap:7px;font-weight:800}.v35-form input,.v35-form select,.v35-form textarea{width:100%;padding:12px;border:1px solid #ccdcd4;border-radius:11px;background:#fff}.v35-form .wide{grid-column:1/-1}.v35-empty{text-align:center;padding:42px}.v35-empty>span{font-size:48px}.v35-checklist{display:grid;gap:12px}.v35-checklist label{padding:14px;border:1px solid #e2ebe6;border-radius:12px}.v35-sitemap{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.v35-sitemap a{background:#fff;border:1px solid #dfe9e4;padding:13px;border-radius:11px;text-decoration:none;color:#07543d;font-weight:800}@media(max-width:950px){.v35-grid,.v35-stats,.v35-sitemap{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.v35-head{grid-template-columns:1fr;padding:25px}.v35-emblem{display:none}.v35-grid,.v35-stats,.v35-sitemap,.v35-form.cols{grid-template-columns:1fr}.v35-actions .v35-btn{width:100%}}');
    }
    public static function admin_menu(){ add_menu_page('Tager V35','Tager V35','manage_options','tager-v35',[__CLASS__,'admin_screen'],'dashicons-admin-site-alt3',3); }
    public static function admin_screen(){
        $defs=self::defs(); $ok=0; foreach($defs as $s=>$d) if(get_page_by_path($s))$ok++; $pct=round($ok/count($defs)*100);
        echo '<div class="wrap"><h1>Tager V35 — اكتمال الصفحات</h1><p>تم العثور على '.$ok.' من '.count($defs).' صفحة إضافية.</p><div style="max-width:700px;height:18px;background:#e5e7eb;border-radius:20px;overflow:hidden"><div style="width:'.$pct.'%;height:100%;background:#087454"></div></div><p><strong>'.$pct.'%</strong></p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v35_repair">'.wp_nonce_field('tager_v35_repair','_wpnonce',true,false).'<button class="button button-primary">إصلاح وإنشاء كل الصفحات</button></form><hr><table class="widefat striped"><thead><tr><th>الصفحة</th><th>الحالة</th><th>فتح</th></tr></thead><tbody>';
        foreach($defs as $s=>$d){$p=get_page_by_path($s);echo '<tr><td>'.esc_html($d[0]).'</td><td>'.($p?'موجودة':'ناقصة').'</td><td>'.($p?'<a href="'.esc_url(get_permalink($p)).'" target="_blank">فتح</a>':'—').'</td></tr>';}
        echo '</tbody></table></div>';
    }
    public static function repair_action(){ if(!current_user_can('manage_options'))wp_die('غير مصرح'); check_admin_referer('tager_v35_repair'); delete_option('tager_v35_done'); self::ensure_pages(); wp_safe_redirect(admin_url('admin.php?page=tager-v35&repaired=1')); exit; }
}
Tager_V35_Complete_Experience::init();
