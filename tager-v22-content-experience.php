<?php
/**
 * Plugin Name: Tager V22 Content & Page Experience
 * Description: Expanded marketplace pages, richer demo catalog, linked navigation and premium V12-consistent UI.
 * Version: 22.0.0
 */
if (!defined('ABSPATH')) exit;

class Tager_V22_Content_Experience {
    public static function init(){
        add_action('init',[__CLASS__,'register_shortcodes'],120);
        add_action('init',[__CLASS__,'ensure_pages'],180);
        add_action('init',[__CLASS__,'seed_catalog'],220);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],140);
        add_action('admin_menu',[__CLASS__,'admin_menu'],90);
        add_action('admin_post_tager_v22_repair',[__CLASS__,'repair']);
    }
    private static function lang(){ return (isset($_GET['lang']) && $_GET['lang']==='en') ? 'en':'ar'; }
    private static function t($ar,$en){ return self::lang()==='en' ? $en : $ar; }
    private static function pages(){ return get_option('tager_pages',[]); }
    private static function url($slug){ $p=self::pages(); return !empty($p[$slug]) ? get_permalink($p[$slug]) : home_url('/'.$slug.'/'); }
    private static function q($url){ return add_query_arg('lang',self::lang(),$url); }

    public static function register_shortcodes(){
        foreach([
            'categories','vendors_directory','deals','brands','about','how_it_works','buyer_guide','seller_guide',
            'business','pricing','faq','contact','compare','help_center','shipping_info','returns_policy','terms_page','privacy_page'
        ] as $name){ add_shortcode('tager_v22_'.$name,[__CLASS__,$name]); }
    }

    public static function ensure_pages(){
        $defs=[
            'categories'=>['الأقسام','[tager_v22_categories]'],
            'vendors'=>['دليل الموردين','[tager_v22_vendors_directory]'],
            'deals'=>['العروض','[tager_v22_deals]'],
            'brands'=>['العلامات التجارية','[tager_v22_brands]'],
            'about'=>['عن تاجر','[tager_v22_about]'],
            'how-it-works'=>['كيف تعمل تاجر','[tager_v22_how_it_works]'],
            'buyer-guide'=>['دليل المشتري','[tager_v22_buyer_guide]'],
            'seller-guide'=>['دليل المورد','[tager_v22_seller_guide]'],
            'business'=>['حلول الشركات','[tager_v22_business]'],
            'pricing'=>['الأسعار والباقات','[tager_v22_pricing]'],
            'faq'=>['الأسئلة الشائعة','[tager_v22_faq]'],
            'contact'=>['تواصل معنا','[tager_v22_contact]'],
            'compare'=>['مقارنة المنتجات','[tager_v22_compare]'],
            'help-center'=>['مركز المساعدة','[tager_v22_help_center]'],
            'shipping-info'=>['الشحن والتوصيل','[tager_v22_shipping_info]'],
            'returns-policy'=>['سياسة الاسترجاع','[tager_v22_returns_policy]'],
            'terms'=>['الشروط والأحكام','[tager_v22_terms_page]'],
            'privacy'=>['سياسة الخصوصية','[tager_v22_privacy_page]'],
        ];
        $pages=self::pages();
        foreach($defs as $slug=>$d){
            $p=get_page_by_path($slug);
            $id=$p?$p->ID:wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$d[0],'post_content'=>$d[1]]);
            if($id && !is_wp_error($id)){
                $pages[$slug]=$id;
                if($p && trim((string)$p->post_content)==='') wp_update_post(['ID'=>$id,'post_content'=>$d[1]]);
            }
        }
        update_option('tager_pages',$pages);
    }

    public static function seed_catalog(){
        if(get_option('tager_v22_seeded')) return;
        if(!post_type_exists('tager_product')) return;
        $existing=(int)wp_count_posts('tager_product')->publish;
        if($existing>=18){ update_option('tager_v22_seeded',1); return; }
        $items=[
            ['أرز بسمتي فاخر 5 كجم','Premium Basmati Rice 5kg','مواد غذائية',285,260,235,10,50,500,'🌾'],
            ['سكر أبيض ناعم 10 كجم','Fine White Sugar 10kg','مواد غذائية',430,398,365,8,40,400,'🧂'],
            ['زيت دوار الشمس 1 لتر','Sunflower Oil 1L','زيوت',105,96,88,12,60,900,'🫗'],
            ['زيت ذرة 5 لتر','Corn Oil 5L','زيوت',520,485,450,6,30,300,'🛢️'],
            ['مناديل رول أوتوماتيك 150م','Auto-cut Tissue Roll 150m','مناديل',61,56,51,24,120,1200,'🧻'],
            ['مناديل إنترفولد 2 طبقة','Interfold Tissues 2-ply','مناديل',54,49,44,20,100,1000,'📦'],
            ['حليب كامل الدسم 1 لتر','Full Cream Milk 1L','ألبان',42,38,35,12,48,600,'🥛'],
            ['جبنة كريمية 500 جم','Cream Cheese 500g','ألبان',78,71,65,12,48,400,'🧀'],
            ['مسحوق غسيل 10 كجم','Laundry Powder 10kg','منظفات',360,330,295,6,24,250,'🧼'],
            ['مطهر أسطح 5 لتر','Surface Disinfectant 5L','منظفات',185,169,152,8,32,320,'🧴'],
            ['مياه معدنية 330 مل × 40','Mineral Water 330ml x40','مشروبات',95,86,78,10,50,700,'💧'],
            ['عصير برتقال 1 لتر × 12','Orange Juice 1L x12','مشروبات',310,286,258,6,30,300,'🧃'],
            ['قهوة عربية 1 كجم','Arabic Coffee 1kg','قهوة وشاي',145,132,119,10,40,250,'☕'],
            ['شاي أسود 100 كيس','Black Tea 100 Bags','قهوة وشاي',88,79,71,12,48,450,'🫖'],
            ['أكواب ورقية 8 أونص × 1000','Paper Cups 8oz x1000','مستلزمات مطاعم',255,230,205,5,20,180,'🥤'],
            ['أكياس نفايات كبيرة × 100','Large Garbage Bags x100','مستلزمات نظافة',130,118,105,10,40,400,'🗑️'],
            ['قفازات نايترايل 100 قطعة','Nitrile Gloves 100pcs','مستلزمات طبية',72,65,58,20,100,800,'🧤'],
            ['كمامات طبية 50 قطعة','Medical Masks 50pcs','مستلزمات طبية',38,34,29,24,120,1000,'😷'],
        ];
        foreach($items as $x){
            if(get_page_by_title($x[0],OBJECT,'tager_product')) continue;
            $id=wp_insert_post(['post_type'=>'tager_product','post_status'=>'publish','post_title'=>$x[0],'post_content'=>'منتج مختار بعناية، متاح بأسعار قطاعي وجملة وجملة الجملة مع مخزون وحدود طلب واضحة.']);
            if(!$id||is_wp_error($id)) continue;
            $meta=['name_en'=>$x[1],'category_name'=>$x[2],'retail_price'=>$x[3],'wholesale_price'=>$x[4],'bulk_price'=>$x[5],'wholesale_min'=>$x[6],'bulk_min'=>$x[7],'stock'=>$x[8],'max_qty'=>$x[8],'approval_status'=>'approved','demo_icon'=>$x[9],'featured'=>($id%3===0?1:0),'rating'=>4.5];
            foreach($meta as $k=>$v) update_post_meta($id,$k,$v);
        }
        update_option('tager_v22_seeded',1);
    }

    private static function hero($kicker,$title,$text,$actions=''){
        return '<section class="v22-page-hero"><div><span>'.$kicker.'</span><h1>'.$title.'</h1><p>'.$text.'</p>'.$actions.'</div><div class="v22-hero-badge"><b>T</b><small>'.esc_html(self::t('تاجر','Tager')).'</small></div></section>';
    }
    private static function cards($items,$class='v22-card-grid'){
        $out='<div class="'.$class.'">'; foreach($items as $i){$out.='<article class="v22-info-card">'.($i[0]?'<div class="v22-card-icon">'.$i[0].'</div>':'').'<h3>'.$i[1].'</h3><p>'.$i[2].'</p>'.(!empty($i[3])?$i[3]:'').'</article>'; } return $out.'</div>';
    }

    public static function categories(){
        $cats=[['🥫','مواد غذائية','Groceries','أرز، سكر، معلبات واحتياجات يومية','Rice, sugar, canned goods and daily essentials'],['🫗','زيوت','Oils','زيوت طعام بأحجام وأسعار متعددة','Cooking oils in multiple sizes and price tiers'],['🧻','مناديل','Tissues','حلول مناديل للاستخدام المنزلي والتجاري','Tissue solutions for home and business'],['🥛','ألبان','Dairy','حليب، جبن ومنتجات ألبان','Milk, cheese and dairy products'],['🧼','منظفات','Cleaning','منظفات، مطهرات ومستلزمات نظافة','Detergents, disinfectants and cleaning supplies'],['🧃','مشروبات','Beverages','مياه، عصائر ومشروبات','Water, juices and beverages'],['☕','قهوة وشاي','Coffee & Tea','قهوة وشاي للأفراد والضيافة','Coffee and tea for retail and hospitality'],['🥤','مستلزمات مطاعم','Foodservice','أكواب، عبوات ومستلزمات مطاعم','Cups, packaging and foodservice supplies'],['🧤','مستلزمات طبية','Medical Supplies','قفازات، كمامات ومستلزمات وقاية','Gloves, masks and protection supplies']];
        $html=self::hero(self::t('تصفح حسب احتياجك','Browse by need'),self::t('كل الأقسام في مكان واحد','All categories in one place'),self::t('اختر القسم المناسب ثم قارن بين أسعار القطاعي والجملة وجملة الجملة.','Choose a category and compare retail, wholesale and bulk pricing.'));
        $html.='<div class="v22-category-grid">'; foreach($cats as $c){$html.='<a href="'.esc_url(self::q(add_query_arg('category',$c[1],self::url('shop')))).'"><span>'.$c[0].'</span><h3>'.esc_html(self::t($c[1],$c[2])).'</h3><p>'.esc_html(self::t($c[3],$c[4])).'</p><b>'.esc_html(self::t('عرض المنتجات','View products')).' ←</b></a>'; } return $html.'</div>';
    }

    public static function vendors_directory(){
        $vendors=[['الرواد للتوريدات','Al Rowad Supplies','مواد غذائية ومنظفات','Groceries & Cleaning',4.9,420],['النخبة للتجارة','Elite Trading','مناديل ومستلزمات مطاعم','Tissues & Foodservice',4.8,315],['الصفوة للتوزيع','Al Safwa Distribution','مشروبات وألبان','Beverages & Dairy',4.7,268],['ميديكال بلس','Medical Plus','مستلزمات طبية','Medical Supplies',4.9,190],['بيت القهوة','Coffee House Supply','قهوة وشاي','Coffee & Tea',4.6,142],['الشرق للزيوت','Al Sharq Oils','زيوت ومواد غذائية','Oils & Groceries',4.8,356]];
        $html=self::hero(self::t('موردون موثّقون','Verified vendors'),self::t('دليل الموردين','Vendor directory'),self::t('تعرّف على الموردين المعتمدين وتخصصاتهم وتقييماتهم.','Discover approved vendors, specialties and ratings.'),'<a class="btn primary" href="'.esc_url(self::q(self::url('vendor-register'))).'">'.esc_html(self::t('انضم كمورد','Join as vendor')).'</a>');
        $html.='<div class="v22-vendor-grid">'; foreach($vendors as $v){$html.='<article class="v22-vendor-card"><div class="v22-vendor-logo">'.esc_html(mb_substr($v[0],0,1)).'</div><div><span class="v22-verified">✓ '.esc_html(self::t('مورد معتمد','Verified vendor')).'</span><h3>'.esc_html(self::t($v[0],$v[1])).'</h3><p>'.esc_html(self::t($v[2],$v[3])).'</p><div class="v22-vendor-meta"><b>★ '.$v[4].'</b><span>'.$v[5].' '.esc_html(self::t('طلب','orders')).'</span></div><a href="'.esc_url(self::q(self::url('shop'))).'">'.esc_html(self::t('استعرض المنتجات','Browse products')).' ←</a></div></article>'; } return $html.'</div>';
    }

    public static function deals(){
        $html=self::hero(self::t('وفّر أكثر','Save more'),self::t('عروض الجملة والكميات','Wholesale & volume deals'),self::t('عروض مختارة للعملاء والتجار والشركات، بأسعار تقل كلما زادت الكمية.','Selected offers for buyers, traders and businesses, with lower pricing at higher quantities.'));
        $offers=[['خصم 10%','10% OFF','على طلبات مواد النظافة فوق 5,000 جنيه','On cleaning orders above EGP 5,000'],['شحن مخفض','Reduced Shipping','للطلبات المختلطة من مورد واحد','For mixed orders from one vendor'],['سعر خاص للشركات','Corporate Pricing','اطلب عرض سعر للكميات الكبيرة','Request a quote for large quantities']];
        $html.='<div class="v22-deal-grid">'; foreach($offers as $o){$html.='<article><span>'.esc_html(self::t($o[0],$o[1])).'</span><h3>'.esc_html(self::t($o[2],$o[3])).'</h3><a class="btn primary" href="'.esc_url(self::q(self::url('shop'))).'">'.esc_html(self::t('تسوق العرض','Shop deal')).'</a></article>'; } return $html.'</div><section class="v12-section"><div class="v12-section-head"><div><span>'.esc_html(self::t('الأكثر طلبًا','Most ordered')).'</span><h2>'.esc_html(self::t('منتجات بأسعار مميزة','Products with special pricing')).'</h2></div></div>'.do_shortcode('[tager_shop]').'</section>';
    }

    public static function brands(){
        $names=['Tager Select','Prime Food','CleanPro','Fresh Dairy','SafeMed','Hospitality Plus','Golden Cup','AquaPure','Daily Choice','ProPack'];
        $html=self::hero(self::t('اختيارات موثوقة','Trusted choices'),self::t('العلامات التجارية','Brand directory'),self::t('استعرض العلامات المتاحة على المنصة وابحث عن منتجاتك المفضلة.','Browse available brands and find your preferred products.'));
        $html.='<div class="v22-brand-grid">'; foreach($names as $n){$html.='<a href="'.esc_url(self::q(self::url('shop'))).'"><span>'.esc_html(mb_substr($n,0,1)).'</span><b>'.esc_html($n).'</b><small>'.esc_html(self::t('عرض المنتجات','View products')).'</small></a>'; } return $html.'</div>';
    }

    public static function about(){
        $a='<a class="btn primary" href="'.esc_url(self::q(self::url('shop'))).'">'.esc_html(self::t('ابدأ التسوق','Start shopping')).'</a> <a class="btn secondary" href="'.esc_url(self::q(self::url('vendor-register'))).'">'.esc_html(self::t('انضم كمورد','Join as vendor')).'</a>';
        $html=self::hero(self::t('من نحن','Who we are'),self::t('منصة تاجر تربط السوق كله','Tager connects the whole marketplace'),self::t('نبني تجربة تجارة إلكترونية تجمع القطاعي والجملة وجملة الجملة، مع موردين معتمدين وعمليات واضحة.','We build an ecommerce experience for retail, wholesale and bulk trade with approved vendors and transparent operations.'),$a);
        $html.=self::cards([['🎯',self::t('رؤيتنا','Our vision'),self::t('تسهيل الوصول إلى منتجات وأسعار مناسبة لكل حجم طلب.','Make products and suitable pricing accessible for every order size.')],['🛡️',self::t('الثقة أولًا','Trust first'),self::t('مراجعة الموردين والمنتجات قبل الظهور للعملاء.','Vendor and product review before customer visibility.')],['📈',self::t('نمو مشترك','Shared growth'),self::t('مساعدة العملاء على التوفير والموردين على زيادة المبيعات.','Help buyers save and vendors grow sales.')]]);
        return $html;
    }

    public static function how_it_works(){
        $html=self::hero(self::t('من البحث إلى الاستلام','From search to delivery'),self::t('كيف تعمل منصة تاجر؟','How Tager works'),self::t('خطوات واضحة للعميل والمورد والإدارة.','Clear flows for buyers, vendors and admins.'));
        $steps=[['1','🔎',self::t('ابحث وقارن','Search & compare'),self::t('ابحث بالاسم أو القسم وقارن مستويات السعر.','Search by name or category and compare pricing tiers.')],['2','📦',self::t('حدد الكمية','Choose quantity'),self::t('السعر يتغير تلقائيًا حسب الكمية المطلوبة.','Price changes automatically based on quantity.')],['3','🧾',self::t('أكد الطلب','Confirm order'),self::t('سجل بيانات التوصيل وراجع ملخص الطلب.','Enter delivery details and review order summary.')],['4','🚚',self::t('تابع التنفيذ','Track fulfillment'),self::t('تابع حالة الطلب والشحن من حسابك.','Track order and shipping from your account.')]];
        $html.='<div class="v22-process">'; foreach($steps as $s){$html.='<article><i>'.$s[0].'</i><span>'.$s[1].'</span><h3>'.$s[2].'</h3><p>'.$s[3].'</p></article>'; } return $html.'</div>';
    }

    public static function buyer_guide(){ return self::guide('buyer'); }
    public static function seller_guide(){ return self::guide('seller'); }
    private static function guide($type){
        $buyer=$type==='buyer';
        $title=$buyer?self::t('دليل المشتري','Buyer guide'):self::t('دليل المورد','Vendor guide');
        $text=$buyer?self::t('كل ما تحتاجه للتسجيل والشراء والمتابعة.','Everything you need to register, order and track.'):self::t('كل ما تحتاجه لإضافة المنتجات واستقبال الطلبات.','Everything you need to list products and receive orders.');
        $items=$buyer?[
            ['👤',self::t('أنشئ حسابك','Create your account'),self::t('سجل بياناتك وعنوان التوصيل.','Register your details and delivery address.')],
            ['🛒',self::t('أضف للسلة','Add to cart'),self::t('اختر الكمية المناسبة ومستوى السعر.','Choose quantity and the applicable price tier.')],
            ['📍',self::t('تابع طلبك','Track your order'),self::t('تابع الحالة من الحساب حتى التسليم.','Track status from account to delivery.')]
        ]:[
            ['📝',self::t('قدّم طلب الانضمام','Apply to join'),self::t('أدخل بيانات نشاطك ومستنداتك.','Submit your business details and documents.')],
            ['📸',self::t('أضف منتجاتك','List products'),self::t('أضف الصور والأسعار والكميات.','Add images, pricing and quantities.')],
            ['💰',self::t('استقبل الطلبات','Receive orders'),self::t('تابع التنفيذ والأرباح من لوحة المورد.','Manage fulfillment and earnings from vendor dashboard.')]
        ];
        return self::hero(self::t('خطوات عملية','Practical steps'),$title,$text).self::cards($items).'<div class="v22-guide-cta"><a class="btn primary" href="'.esc_url(self::q(self::url($buyer?'customer-register':'vendor-register'))).'">'.esc_html($buyer?self::t('سجل كعميل','Register as buyer'):self::t('سجل كمورد','Register as vendor')).'</a></div>';
    }

    public static function business(){
        $html=self::hero(self::t('للمؤسسات والشركات','For companies & institutions'),self::t('حلول مشتريات للشركات','Business procurement solutions'),self::t('طلبات دورية، عروض أسعار، حسابات متعددة، وتقارير مشتريات في تجربة واحدة.','Recurring orders, quotations, multi-user accounts and purchasing reports in one experience.'),'<a class="btn primary" href="'.esc_url(self::q(self::url('contact'))).'">'.esc_html(self::t('اطلب عرضًا للشركة','Request business offer')).'</a>');
        return $html.self::cards([['📋',self::t('طلبات عروض أسعار','RFQ'),self::t('اطلب تسعيرًا مخصصًا للكميات والمواصفات الكبيرة.','Request custom pricing for large quantities and specifications.')],['👥',self::t('فرق مشتريات','Buying teams'),self::t('قسم الصلاحيات بين الطلب والمراجعة والاعتماد.','Separate permissions for requesting, reviewing and approving.')],['📊',self::t('تقارير ومتابعة','Reports & tracking'),self::t('تابع الإنفاق والموردين والطلبات من لوحة موحدة.','Track spend, vendors and orders from one dashboard.')],['🔁',self::t('طلبات متكررة','Recurring orders'),self::t('احفظ القوائم وكرر الطلبات الدورية بسهولة.','Save lists and repeat recurring orders easily.')]]);
    }

    public static function pricing(){
        $plans=[['مشتري','Buyer','0',self::t('تسجيل مجاني','Free registration'),['تسوق قطاعي وجملة','متابعة الطلبات','قوائم ومفضلة']],['مورد مبتدئ','Starter Vendor','0',self::t('للبداية','For getting started'),['حتى 25 منتج','لوحة مورد','عمولة 10%']],['مورد نمو','Growth Vendor','499',self::t('شهريًا','Monthly'),['حتى 250 منتج','عمولة 8%','منتجات مميزة']],['مؤسسات','Enterprise',self::t('حسب العرض','Custom'),self::t('حلول مخصصة','Tailored solutions'),['صلاحيات فرق','RFQ وتقارير','دعم مخصص']]];
        $html=self::hero(self::t('باقات واضحة','Clear plans'),self::t('الأسعار والباقات','Pricing & plans'),self::t('ابدأ مجانًا واختر الباقة المناسبة عند نمو نشاطك.','Start free and choose the right plan as your business grows.'));
        $html.='<div class="v22-pricing-grid">'; foreach($plans as $i=>$p){$html.='<article class="'.($i===2?'featured':'').'"><small>'.esc_html(self::t($p[0],$p[1])).'</small><h3>'.$p[2].($i===2?' <sup>EGP</sup>':'').'</h3><p>'.$p[3].'</p><ul>'; foreach($p[4] as $f)$html.='<li>✓ '.esc_html($f).'</li>'; $html.='</ul><a class="btn '.($i===2?'primary':'secondary').'" href="'.esc_url(self::q(self::url($i===0?'customer-register':'vendor-register'))).'">'.esc_html(self::t('ابدأ الآن','Get started')).'</a></article>'; } return $html.'</div>';
    }

    public static function faq(){
        $qs=[['هل التسجيل مجاني؟','Is registration free?','نعم، تسجيل العميل مجاني، ويمكن للمورد البدء بالخطة الأساسية.','Yes. Buyer registration is free, and vendors can begin with the starter plan.'],['كيف أعرف سعر الجملة؟','How do I get wholesale pricing?','حدد الكمية في صفحة المنتج وسيتم تطبيق السعر المناسب تلقائيًا.','Choose quantity on the product page and the matching tier is applied automatically.'],['هل يتم مراجعة الموردين؟','Are vendors reviewed?','نعم، المورد والمنتجات يخضعان للمراجعة قبل النشر.','Yes. Vendors and products are reviewed before publishing.'],['كيف أتابع الطلب؟','How do I track an order?','من صفحة حسابي أو صفحة تتبع الطلب باستخدام رقم الطلب.','From My Account or the order tracking page using the order number.'],['هل يمكن طلب عرض سعر؟','Can I request a quote?','نعم، خصوصًا للكميات الكبيرة أو طلبات الشركات.','Yes, especially for large quantities and corporate orders.']];
        $html=self::hero(self::t('إجابات سريعة','Quick answers'),self::t('الأسئلة الشائعة','Frequently asked questions'),self::t('أهم الأسئلة حول التسجيل والطلب والأسعار والموردين.','Common questions about registration, orders, pricing and vendors.'));
        $html.='<div class="v22-faq">'; foreach($qs as $q)$html.='<details><summary>'.esc_html(self::t($q[0],$q[1])).'</summary><p>'.esc_html(self::t($q[2],$q[3])).'</p></details>'; return $html.'</div>';
    }

    public static function contact(){
        $html=self::hero(self::t('نحن هنا للمساعدة','We are here to help'),self::t('تواصل معنا','Contact us'),self::t('اختر قناة التواصل المناسبة لفريق المبيعات أو الموردين أو الدعم.','Choose the right channel for sales, vendor onboarding or support.'));
        $html.=self::cards([['💬',self::t('دعم العملاء','Customer support'),self::t('مساعدة في الحسابات والطلبات والتتبع.','Help with accounts, orders and tracking.'),'<a href="mailto:support@tager.test">support@tager.test</a>'],['🏪',self::t('دعم الموردين','Vendor support'),self::t('مساعدة في التسجيل والمنتجات والطلبات.','Help with onboarding, products and orders.'),'<a href="mailto:vendors@tager.test">vendors@tager.test</a>'],['🏢',self::t('مبيعات الشركات','Business sales'),self::t('طلبات توريد وعروض أسعار للشركات.','Corporate sourcing and quotation requests.'),'<a href="mailto:business@tager.test">business@tager.test</a>']]);
        $html.='<div class="form-card v22-contact-form"><h2>'.esc_html(self::t('أرسل رسالة','Send a message')).'</h2><div class="form-grid"><label>'.esc_html(self::t('الاسم','Name')).'<input type="text"></label><label>'.esc_html(self::t('البريد','Email')).'<input type="email"></label></div><label>'.esc_html(self::t('نوع الطلب','Request type')).'<select><option>'.esc_html(self::t('استفسار عام','General inquiry')).'</option><option>'.esc_html(self::t('دعم طلب','Order support')).'</option><option>'.esc_html(self::t('انضمام مورد','Vendor onboarding')).'</option></select></label><label>'.esc_html(self::t('الرسالة','Message')).'<textarea></textarea></label><button class="btn primary" type="button" onclick="alert(\''.esc_js(self::t('تم استلام رسالتك تجريبيًا','Your demo message was received')).'\')">'.esc_html(self::t('إرسال','Send')).'</button></div>';
        return $html;
    }

    public static function compare(){
        $ps=get_posts(['post_type'=>'tager_product','post_status'=>'publish','numberposts'=>4]);
        $html=self::hero(self::t('اختر الأفضل','Choose the best'),self::t('مقارنة المنتجات','Product comparison'),self::t('قارن الأسعار والحدود والمخزون قبل اتخاذ قرار الشراء.','Compare pricing, quantity limits and stock before buying.'));
        $html.='<div class="table-wrap"><table><tr><th>'.esc_html(self::t('المنتج','Product')).'</th><th>'.esc_html(self::t('قطاعي','Retail')).'</th><th>'.esc_html(self::t('جملة','Wholesale')).'</th><th>'.esc_html(self::t('جملة الجملة','Bulk')).'</th><th>'.esc_html(self::t('المخزون','Stock')).'</th></tr>'; foreach($ps as $p){$html.='<tr><td><b>'.esc_html(get_the_title($p)).'</b></td><td>'.esc_html(get_post_meta($p->ID,'retail_price',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'wholesale_price',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'bulk_price',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'stock',true)).'</td></tr>'; } return $html.'</table></div>';
    }

    public static function help_center(){ return self::hero(self::t('مساعدة منظمة','Organized help'),self::t('مركز المساعدة','Help center'),self::t('روابط سريعة لأهم الأدلة والسياسات والخدمات.','Quick links to guides, policies and key services.')).self::cards([['🛍️',self::t('مساعدة المشتري','Buyer help'),self::t('التسجيل والشراء وتتبع الطلبات.','Registration, shopping and order tracking.'),'<a href="'.esc_url(self::q(self::url('buyer-guide'))).'">'.esc_html(self::t('فتح الدليل','Open guide')).'</a>'],['🏪',self::t('مساعدة المورد','Vendor help'),self::t('التسجيل والمنتجات وإدارة الطلبات.','Onboarding, products and order management.'),'<a href="'.esc_url(self::q(self::url('seller-guide'))).'">'.esc_html(self::t('فتح الدليل','Open guide')).'</a>'],['🚚',self::t('الشحن والاسترجاع','Shipping & returns'),self::t('تعرف على آلية التوصيل والاسترجاع.','Learn about delivery and return procedures.'),'<a href="'.esc_url(self::q(self::url('shipping-info'))).'">'.esc_html(self::t('التفاصيل','Details')).'</a>']]); }
    public static function shipping_info(){ return self::policy('shipping'); }
    public static function returns_policy(){ return self::policy('returns'); }
    public static function terms_page(){ return self::policy('terms'); }
    public static function privacy_page(){ return self::policy('privacy'); }
    private static function policy($type){
        $data=[
            'shipping'=>[self::t('الشحن والتوصيل','Shipping & delivery'),self::t('تختلف مدة ورسوم التوصيل حسب المورد والمنطقة وحجم الطلب. تظهر التفاصيل قبل تأكيد الطلب.','Delivery time and fees vary by vendor, location and order size. Details appear before confirmation.'),[['تجهيز الطلب','Order preparation'],['تأكيد الشحن','Shipping confirmation'],['التتبع','Tracking'],['الاستلام','Delivery']]],
            'returns'=>[self::t('سياسة الاسترجاع','Returns policy'),self::t('يمكن طلب الاسترجاع وفق حالة المنتج وسبب الطلب وسياسة المورد، مع مراجعة الإدارة للنزاعات.','Returns may be requested based on product condition, reason and vendor policy, with admin review for disputes.'),[['طلب الاسترجاع','Submit request'],['مراجعة الطلب','Review'],['استلام المرتجع','Return pickup'],['رد المبلغ','Refund']]],
            'terms'=>[self::t('الشروط والأحكام','Terms & conditions'),self::t('باستخدام المنصة يوافق المستخدم على تقديم بيانات صحيحة والالتزام بسياسات الطلب والدفع والشحن.','By using the marketplace, users agree to provide accurate data and follow order, payment and shipping policies.'),[['الحسابات','Accounts'],['الطلبات','Orders'],['الموردون','Vendors'],['المحتوى','Content']]],
            'privacy'=>[self::t('سياسة الخصوصية','Privacy policy'),self::t('نستخدم البيانات لتشغيل الحسابات والطلبات والدعم وتحسين التجربة، ولا نعرض بياناتك للمعلنين.','We use data to operate accounts, orders and support, and to improve the experience. Your data is not exposed to advertisers.'),[['بيانات الحساب','Account data'],['بيانات الطلب','Order data'],['الأمان','Security'],['حقوق المستخدم','User rights']]],
        ];
        $d=$data[$type]; $html=self::hero(self::t('معلومات مهمة','Important information'),$d[0],$d[1]);
        $html.='<div class="v22-policy-grid">'; foreach($d[2] as $i=>$x)$html.='<article><i>'.($i+1).'</i><h3>'.esc_html(self::t($x[0],$x[1])).'</h3><p>'.esc_html(self::t('تطبق المنصة إجراءات واضحة ومراجعة موثقة لهذه المرحلة.','The platform applies clear and documented procedures for this stage.')).'</p></article>'; return $html.'</div>';
    }

    public static function assets(){
        $css='\n/* ===== V22 Expanded Pages ===== */\n.v22-page-hero{display:grid;grid-template-columns:1fr auto;align-items:center;gap:30px;background:radial-gradient(circle at 85% 20%,rgba(217,164,65,.16),transparent 26%),linear-gradient(125deg,#063d2e,#087251);color:#fff;padding:44px 48px;border-radius:28px;margin-bottom:28px;box-shadow:0 22px 50px rgba(4,70,49,.18)}.v22-page-hero>div:first-child>span{color:#ffd98d;font-weight:900;font-size:13px}.v22-page-hero h1{font-size:clamp(32px,5vw,50px);line-height:1.15;margin:8px 0}.v22-page-hero p{max-width:760px;color:#dcefe7;font-size:16px}.v22-hero-badge{width:150px;height:150px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);border-radius:35px;display:grid;place-items:center;align-content:center;transform:rotate(-3deg)}.v22-hero-badge b{font-size:58px;color:#ffe0a0}.v22-hero-badge small{font-weight:900}.v22-card-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.v22-info-card{background:#fff;border:1px solid var(--line);border-radius:20px;padding:24px;box-shadow:0 8px 24px rgba(12,52,38,.05)}.v22-card-icon{font-size:38px}.v22-info-card h3{margin:9px 0 5px}.v22-info-card p{color:var(--muted)}.v22-info-card a{color:var(--green);font-weight:800}.v22-category-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:17px}.v22-category-grid a{background:#fff;border:1px solid var(--line);border-radius:21px;padding:25px;text-decoration:none;box-shadow:0 8px 24px rgba(12,52,38,.05);transition:.22s}.v22-category-grid a:hover{transform:translateY(-5px);box-shadow:var(--shadow)}.v22-category-grid span{font-size:44px}.v22-category-grid h3{margin:8px 0}.v22-category-grid p{color:var(--muted);min-height:52px}.v22-category-grid b{color:var(--green)}.v22-vendor-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.v22-vendor-card{display:grid;grid-template-columns:82px 1fr;gap:18px;background:#fff;border:1px solid var(--line);border-radius:21px;padding:22px;box-shadow:0 8px 24px rgba(12,52,38,.05)}.v22-vendor-logo{width:82px;height:82px;border-radius:22px;background:linear-gradient(145deg,var(--green),var(--green-2));color:#fff;display:grid;place-items:center;font-size:32px;font-weight:900}.v22-verified{font-size:11px;color:var(--green);background:var(--green-3);padding:4px 9px;border-radius:999px}.v22-vendor-card h3{margin:8px 0 3px}.v22-vendor-card p{color:var(--muted)}.v22-vendor-meta{display:flex;gap:15px;margin:10px 0}.v22-vendor-meta b{color:var(--gold)}.v22-vendor-card a{color:var(--green);font-weight:800;text-decoration:none}.v22-deal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.v22-deal-grid article{background:linear-gradient(135deg,#fff,#f0faf5);border:1px solid #d5e8df;border-radius:22px;padding:27px}.v22-deal-grid span{display:inline-block;color:#6a4a09;background:#fae7b9;padding:5px 10px;border-radius:999px;font-weight:900}.v22-deal-grid h3{min-height:58px}.v22-brand-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}.v22-brand-grid a{background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;text-align:center;text-decoration:none;display:grid;gap:5px}.v22-brand-grid span{width:55px;height:55px;border-radius:16px;background:var(--green-3);color:var(--green);display:grid;place-items:center;margin:auto;font-size:24px;font-weight:900}.v22-brand-grid small{color:var(--muted)}.v22-process{display:grid;grid-template-columns:repeat(4,1fr);gap:17px}.v22-process article{position:relative;background:#fff;border:1px solid var(--line);border-radius:20px;padding:25px;text-align:center}.v22-process i{position:absolute;inset-block-start:12px;inset-inline-start:12px;background:var(--green);color:#fff;border-radius:50%;width:29px;height:29px;display:grid;place-items:center;font-style:normal;font-weight:900}.v22-process span{font-size:45px}.v22-process p{color:var(--muted)}.v22-guide-cta{text-align:center;margin-top:22px}.v22-pricing-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.v22-pricing-grid article{background:#fff;border:1px solid var(--line);border-radius:22px;padding:25px}.v22-pricing-grid article.featured{background:linear-gradient(155deg,#063d2e,#087251);color:#fff;transform:translateY(-8px);box-shadow:0 22px 45px rgba(6,72,51,.2)}.v22-pricing-grid h3{font-size:35px;margin:8px 0}.v22-pricing-grid ul{list-style:none;padding:0;display:grid;gap:8px;min-height:120px}.v22-faq{display:grid;gap:12px}.v22-faq details{background:#fff;border:1px solid var(--line);border-radius:15px;padding:17px 20px}.v22-faq summary{font-weight:900;cursor:pointer}.v22-faq p{color:var(--muted)}.v22-contact-form{max-width:100%;margin-top:22px}.v22-policy-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.v22-policy-grid article{background:#fff;border:1px solid var(--line);border-radius:19px;padding:22px}.v22-policy-grid i{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:var(--gold);font-weight:900;font-style:normal}.v22-policy-grid p{color:var(--muted)}@media(max-width:1000px){.v22-brand-grid{grid-template-columns:repeat(3,1fr)}.v22-pricing-grid{grid-template-columns:repeat(2,1fr)}.v22-process,.v22-policy-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:760px){.v22-page-hero{grid-template-columns:1fr;padding:30px 24px}.v22-hero-badge{display:none}.v22-card-grid,.v22-category-grid,.v22-vendor-grid,.v22-deal-grid{grid-template-columns:1fr}.v22-brand-grid{grid-template-columns:repeat(2,1fr)}.v22-process,.v22-pricing-grid,.v22-policy-grid{grid-template-columns:1fr}.v22-vendor-card{grid-template-columns:64px 1fr}.v22-vendor-logo{width:64px;height:64px}}';
        wp_add_inline_style('tager-style',$css);
    }

    public static function admin_menu(){ add_submenu_page('tager-control','V22 Content Experience','V22 Content Experience','manage_options','tager-v22-content',[__CLASS__,'admin_page']); }
    public static function admin_page(){
        $pages=self::pages(); $slugs=['categories','vendors','deals','brands','about','how-it-works','buyer-guide','seller-guide','business','pricing','faq','contact','compare','help-center','shipping-info','returns-policy','terms','privacy'];
        echo '<div class="wrap"><h1>Tager V22 Content Experience</h1><p>Expanded premium pages and demo catalog while preserving the V12 visual system.</p><table class="widefat striped"><tr><th>Page</th><th>Status</th><th>Open</th></tr>'; foreach($slugs as $s){$ok=!empty($pages[$s])&&get_post($pages[$s]);echo '<tr><td>'.esc_html($s).'</td><td>'.($ok?'✅ Ready':'❌ Missing').'</td><td>'.($ok?'<a href="'.esc_url(get_permalink($pages[$s])).'" target="_blank">Open</a>':'—').'</td></tr>'; } echo '</table><p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=tager_v22_repair'),'tager_v22_repair')).'">Repair pages and seed catalog</a></p></div>';
    }
    public static function repair(){ if(!current_user_can('manage_options'))wp_die('No permission'); check_admin_referer('tager_v22_repair'); delete_option('tager_v22_seeded'); self::ensure_pages(); self::seed_catalog(); flush_rewrite_rules(); wp_safe_redirect(admin_url('admin.php?page=tager-v22-content')); exit; }
}
Tager_V22_Content_Experience::init();
