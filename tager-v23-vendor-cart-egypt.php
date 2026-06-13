<?php
/**
 * Plugin Name: Tager V23 Vendor Cart & Egypt Commerce
 * Description: Vendor minimum orders, Egypt governorates, vendor-separated wholesale cart, optional mixed-vendor cart with 1.5% service fee, and dedicated vendor storefronts.
 * Version: 23.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V23_Vendor_Cart_Egypt {
    const VERSION = '23.0.0';
    const DEFAULT_VENDOR_MIN = 500;
    const MIXED_FEE_PERCENT = 1.5;

    public static function init() {
        add_action('init', [__CLASS__, 'bootstrap'], 180);
        add_action('init', [__CLASS__, 'replace_flows'], 999);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets'], 120);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 95);

        add_action('admin_post_tager_v23_save_vendor_min', [__CLASS__, 'save_vendor_min']);
        add_action('admin_post_tager_v23_admin_vendor_min', [__CLASS__, 'admin_save_vendor_min']);
        add_action('admin_post_tager_checkout', [__CLASS__, 'checkout']);
        add_action('admin_post_nopriv_tager_checkout', [__CLASS__, 'checkout']);

        add_shortcode('tager_v23_cart', [__CLASS__, 'cart_page']);
        add_shortcode('tager_v23_vendor_store', [__CLASS__, 'vendor_store']);
    }

    private static function lang() {
        return (!empty($_GET['lang']) && sanitize_key($_GET['lang']) === 'en') ? 'en' : 'ar';
    }
    private static function t($ar, $en) { return self::lang() === 'en' ? $en : $ar; }
    private static function pages() { return (array) get_option('tager_pages', []); }
    private static function url($slug) {
        $pages = self::pages();
        return !empty($pages[$slug]) ? get_permalink((int)$pages[$slug]) : home_url('/'.$slug.'/');
    }
    private static function governorates() {
        return [
            'القاهرة'=>'Cairo','الجيزة'=>'Giza','الإسكندرية'=>'Alexandria','القليوبية'=>'Qalyubia',
            'البحيرة'=>'Beheira','الدقهلية'=>'Dakahlia','دمياط'=>'Damietta','الشرقية'=>'Sharqia',
            'الغربية'=>'Gharbia','كفر الشيخ'=>'Kafr El Sheikh','المنوفية'=>'Monufia','الفيوم'=>'Fayoum',
            'بني سويف'=>'Beni Suef','المنيا'=>'Minya','أسيوط'=>'Assiut','سوهاج'=>'Sohag',
            'قنا'=>'Qena','الأقصر'=>'Luxor','أسوان'=>'Aswan','البحر الأحمر'=>'Red Sea',
            'الوادي الجديد'=>'New Valley','مطروح'=>'Matrouh','شمال سيناء'=>'North Sinai','جنوب سيناء'=>'South Sinai',
            'بورسعيد'=>'Port Said','السويس'=>'Suez','الإسماعيلية'=>'Ismailia'
        ];
    }
    private static function vendor_min($vendor_id) {
        $value = (float) get_user_meta($vendor_id, 'tager_vendor_min_order', true);
        return $value > 0 ? $value : (float) self::DEFAULT_VENDOR_MIN;
    }
    private static function vendor_name($vendor_id) {
        if (!$vendor_id) return self::t('متجر تاجر','Tager Store');
        return get_user_meta($vendor_id, 'store_name', true) ?: get_the_author_meta('display_name', $vendor_id);
    }
    private static function product_price($id, $qty) {
        $retail = (float)get_post_meta($id,'retail_price',true);
        $wholesale = (float)get_post_meta($id,'wholesale_price',true);
        $bulk = (float)get_post_meta($id,'bulk_price',true);
        $wholesale_min = (int)get_post_meta($id,'wholesale_min',true);
        $bulk_min = (int)get_post_meta($id,'bulk_min',true);
        if ($bulk_min > 0 && $qty >= $bulk_min) return $bulk;
        if ($wholesale_min > 0 && $qty >= $wholesale_min) return $wholesale;
        return $retail;
    }

    public static function bootstrap() {
        if (get_option('tager_v23_bootstrap') === self::VERSION) return;
        $pages = self::pages();
        $defs = [
            'cart' => ['السلة الذكية','[tager_v23_cart]'],
            'vendor-store' => ['متجر المورد','[tager_v23_vendor_store]'],
        ];
        foreach ($defs as $slug=>$data) {
            $page = get_page_by_path($slug);
            if (!$page) {
                $id = wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$data[0],'post_content'=>$data[1]]);
            } else {
                $id = $page->ID;
                wp_update_post(['ID'=>$id,'post_content'=>$data[1]]);
            }
            $pages[$slug] = $id;
        }
        update_option('tager_pages',$pages);
        update_option('tager_v23_bootstrap', self::VERSION);
    }

    public static function replace_flows() {
        remove_shortcode('tager_cart');
        add_shortcode('tager_cart', [__CLASS__, 'cart_page']);

        if (class_exists('Tager_Marketplace_Complete')) {
            remove_action('admin_post_tager_checkout', ['Tager_Marketplace_Complete','handle_checkout']);
            remove_action('admin_post_nopriv_tager_checkout', ['Tager_Marketplace_Complete','handle_checkout']);
        }
        // Ensure our handlers are present once after removing the legacy flow.
        if (!has_action('admin_post_tager_checkout', [__CLASS__, 'checkout'])) add_action('admin_post_tager_checkout', [__CLASS__, 'checkout']);
        if (!has_action('admin_post_nopriv_tager_checkout', [__CLASS__, 'checkout'])) add_action('admin_post_nopriv_tager_checkout', [__CLASS__, 'checkout']);

        // Extend the existing vendor dashboard without discarding its product/order tools.
        remove_shortcode('tager_vendor_dashboard');
        add_shortcode('tager_vendor_dashboard', [__CLASS__, 'vendor_dashboard']);
    }

    public static function assets() {
        wp_add_inline_style('tager-style', '
        .v23-cart-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(300px,.75fr);gap:24px;align-items:start}.v23-vendor-cart{background:#fff;border:1px solid #e6e2d6;border-radius:22px;margin-bottom:18px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,.06)}.v23-vendor-head{display:flex;justify-content:space-between;gap:16px;align-items:center;padding:18px 20px;background:linear-gradient(135deg,#f6f9f1,#fffaf0);border-bottom:1px solid #eee7d5}.v23-vendor-head h3{margin:0}.v23-min-progress{font-size:13px;margin-top:6px}.v23-progress{height:7px;background:#e9ece5;border-radius:99px;overflow:hidden;margin-top:7px}.v23-progress i{display:block;height:100%;background:linear-gradient(90deg,#18634b,#c59b42)}.v23-cart-row{display:grid;grid-template-columns:minmax(180px,1fr) 120px 130px 45px;gap:12px;align-items:center;padding:14px 20px;border-bottom:1px solid #f0eee7}.v23-cart-row:last-child{border-bottom:0}.v23-cart-row input{width:100%}.v23-summary{position:sticky;top:100px;background:#fff;border:1px solid #e6e2d6;border-radius:22px;padding:22px;box-shadow:0 14px 36px rgba(15,23,42,.08)}.v23-mode{display:grid;gap:10px;margin:15px 0}.v23-mode label{display:block;border:1px solid #e3dfd2;border-radius:15px;padding:13px;cursor:pointer}.v23-mode label:has(input:checked){border-color:#b18a36;background:#fffaf0}.v23-total-line{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px dashed #ddd}.v23-total-line.grand{font-size:20px;font-weight:800;border:0;padding-top:16px}.v23-chip{display:inline-flex;padding:5px 10px;border-radius:99px;background:#edf6f1;color:#175c47;font-size:12px;font-weight:700}.v23-chip.warn{background:#fff2df;color:#995b00}.v23-governorate{width:100%}.v23-store-hero{background:linear-gradient(135deg,#123f35,#1d6c54);color:#fff;border-radius:28px;padding:32px;margin-bottom:28px;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}.v23-store-hero h1{color:#fff;margin:0 0 8px}.v23-store-stats{display:flex;gap:12px;flex-wrap:wrap}.v23-store-stats span{background:rgba(255,255,255,.12);padding:9px 13px;border-radius:12px}.v23-vendor-settings{background:#fff;border:1px solid #e6e2d6;border-radius:20px;padding:20px;margin:0 0 24px}.v23-vendor-settings form{display:flex;gap:12px;align-items:end;flex-wrap:wrap}.v23-vendor-settings label{flex:1;min-width:230px}@media(max-width:850px){.v23-cart-layout{grid-template-columns:1fr}.v23-summary{position:static}.v23-cart-row{grid-template-columns:1fr 90px}.v23-cart-row .v23-line-total{grid-column:1}.v23-store-hero{grid-template-columns:1fr}}
        ');
    }

    public static function vendor_dashboard() {
        if (!class_exists('Tager_Marketplace_Complete')) return '<div class="notice">Marketplace core is unavailable.</div>';
        $base = Tager_Marketplace_Complete::vendor_dashboard();
        if (!is_user_logged_in()) return $base;
        $user = wp_get_current_user();
        if (!in_array('tager_vendor', (array)$user->roles, true) && !current_user_can('manage_options')) return $base;
        $minimum = self::vendor_min($user->ID);
        $store_url = add_query_arg('vendor',$user->ID,self::url('vendor-store'));
        ob_start(); ?>
        <section class="v23-vendor-settings">
            <h2><?php echo esc_html(self::t('إعدادات الحد الأدنى للطلب','Minimum order settings')); ?></h2>
            <p><?php echo esc_html(self::t('حدد أقل قيمة طلب يجب أن يحققها العميل عند الشراء من متجرك في السلة العادية. السلة الخاصة المختلطة تتجاوز هذا الشرط مقابل رسوم خدمة 1.5%.','Set the minimum order value customers must reach in the normal vendor cart. The special mixed cart can bypass this rule with a 1.5% service fee.')); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tager_v23_save_vendor_min">
                <?php wp_nonce_field('tager_v23_save_vendor_min'); ?>
                <label><?php echo esc_html(self::t('الحد الأدنى للطلب بالجنيه','Minimum order value (EGP)')); ?>
                    <input required min="0" step="0.01" type="number" name="minimum" value="<?php echo esc_attr($minimum); ?>">
                </label>
                <button class="btn primary"><?php echo esc_html(self::t('حفظ الحد الأدنى','Save minimum')); ?></button>
                <a class="btn secondary" href="<?php echo esc_url($store_url); ?>"><?php echo esc_html(self::t('عرض صفحة متجري','View my store')); ?></a>
            </form>
        </section>
        <?php return ob_get_clean().$base;
    }

    public static function save_vendor_min() {
        if (!is_user_logged_in()) wp_die('Login required');
        check_admin_referer('tager_v23_save_vendor_min');
        $user = wp_get_current_user();
        if (!in_array('tager_vendor',(array)$user->roles,true) && !current_user_can('manage_options')) wp_die('Not allowed');
        update_user_meta($user->ID,'tager_vendor_min_order',max(0,(float)($_POST['minimum']??0)));
        wp_safe_redirect(add_query_arg('v23_saved','1',self::url('vendor-dashboard'))); exit;
    }

    public static function admin_menu() {
        add_submenu_page('tager-control','Vendor Order Rules','Vendor Order Rules','manage_options','tager-v23-vendor-rules',[__CLASS__,'admin_rules']);
    }
    public static function admin_rules() {
        if (!current_user_can('manage_options')) return;
        $vendors = get_users(['role__in'=>['tager_vendor','tager_vendor_pending']]);
        echo '<div class="wrap"><h1>Vendor Minimum Orders & Mixed Cart</h1><p>Normal carts are validated independently per vendor. Mixed special carts can combine any vendors and add a fixed 1.5% service fee.</p><table class="widefat striped"><tr><th>Vendor</th><th>Store</th><th>Minimum order (EGP)</th><th>Store page</th><th>Save</th></tr>';
        foreach($vendors as $v){
            $url=add_query_arg('vendor',$v->ID,self::url('vendor-store'));
            echo '<tr><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><td>'.esc_html($v->display_name).'</td><td>'.esc_html(self::vendor_name($v->ID)).'</td><td><input type="number" min="0" step="0.01" name="minimum" value="'.esc_attr(self::vendor_min($v->ID)).'"></td><td><a target="_blank" href="'.esc_url($url).'">Open store</a></td><td><input type="hidden" name="action" value="tager_v23_admin_vendor_min"><input type="hidden" name="vendor" value="'.$v->ID.'">'.wp_nonce_field('tager_v23_admin_vendor_min_'.$v->ID,'_wpnonce',true,false).'<button class="button button-primary">Save</button></td></form></tr>';
        }
        echo '</table></div>';
    }
    public static function admin_save_vendor_min() {
        if(!current_user_can('manage_options')) wp_die('No');
        $vendor=(int)($_POST['vendor']??0); check_admin_referer('tager_v23_admin_vendor_min_'.$vendor);
        update_user_meta($vendor,'tager_vendor_min_order',max(0,(float)($_POST['minimum']??0)));
        wp_safe_redirect(admin_url('admin.php?page=tager-v23-vendor-rules')); exit;
    }

    private static function product_catalog_json() {
        $posts=get_posts(['post_type'=>'tager_product','post_status'=>'publish','numberposts'=>-1,'meta_query'=>[['key'=>'approval_status','value'=>'approved']]]);
        $data=[];
        foreach($posts as $p){
            $vendor=(int)$p->post_author;
            $data[$p->ID]=[
                'id'=>$p->ID,'name'=>$p->post_title,'vendor_id'=>$vendor,'vendor_name'=>self::vendor_name($vendor),
                'vendor_min'=>self::vendor_min($vendor),'stock'=>(int)get_post_meta($p->ID,'stock',true),
                'max'=>(int)(get_post_meta($p->ID,'max_qty',true)?:9999),
                'retail'=>(float)get_post_meta($p->ID,'retail_price',true),
                'wholesale'=>(float)get_post_meta($p->ID,'wholesale_price',true),
                'bulk'=>(float)get_post_meta($p->ID,'bulk_price',true),
                'wholesale_min'=>(int)get_post_meta($p->ID,'wholesale_min',true),
                'bulk_min'=>(int)get_post_meta($p->ID,'bulk_min',true),
                'store_url'=>add_query_arg('vendor',$vendor,self::url('vendor-store')),
            ];
        }
        return $data;
    }

    public static function cart_page() {
        $catalog=self::product_catalog_json(); $user=wp_get_current_user();
        ob_start(); ?>
        <section class="page-hero"><span class="eyebrow"><?php echo esc_html(self::t('شراء مرن من مورد واحد أو عدة موردين','Flexible purchasing from one or multiple vendors')); ?></span><h1><?php echo esc_html(self::t('السلة الذكية','Smart cart')); ?></h1><p><?php echo esc_html(self::t('السلة العادية تحاسب كل مورد بشكل مستقل حسب الحد الأدنى الخاص به. اختر السلة الخاصة لدمج جميع الموردين مقابل 1.5% فقط.','The normal cart validates each vendor independently. Choose the special mixed cart to combine all vendors for only 1.5%.')); ?></p></section>
        <div class="v23-cart-layout">
            <main id="v23-cart-groups"></main>
            <aside class="v23-summary">
                <h2><?php echo esc_html(self::t('ملخص الطلب','Order summary')); ?></h2>
                <div class="v23-mode">
                    <label><input type="radio" name="v23_mode" value="vendor" checked> <b><?php echo esc_html(self::t('سلة الموردين المنفصلة','Separate vendor carts')); ?></b><small><br><?php echo esc_html(self::t('يجب استيفاء الحد الأدنى لكل مورد','Each vendor minimum must be met')); ?></small></label>
                    <label><input type="radio" name="v23_mode" value="mixed"> <b><?php echo esc_html(self::t('سلة خاصة مختلطة','Special mixed cart')); ?></b><small><br><?php echo esc_html(self::t('اطلب أي أصناف من أي مورد + رسوم 1.5%','Any products from any vendor + 1.5% fee')); ?></small></label>
                </div>
                <div class="v23-total-line"><span><?php echo esc_html(self::t('قيمة المنتجات','Products subtotal')); ?></span><b id="v23-subtotal">0.00 EGP</b></div>
                <div class="v23-total-line" id="v23-fee-row" style="display:none"><span><?php echo esc_html(self::t('رسوم السلة الخاصة 1.5%','Mixed cart fee 1.5%')); ?></span><b id="v23-fee">0.00 EGP</b></div>
                <div class="v23-total-line grand"><span><?php echo esc_html(self::t('الإجمالي','Total')); ?></span><b id="v23-grand">0.00 EGP</b></div>
                <div id="v23-validation"></div>
            </aside>
        </div>
        <section class="form-wrap checkout"><h2><?php echo esc_html(self::t('بيانات التوصيل','Delivery details')); ?></h2>
        <?php if(!is_user_logged_in()): ?><div class="notice"><?php echo esc_html(self::t('يجب تسجيل الدخول لإتمام الطلب.','Please sign in to place the order.')); ?> <a href="<?php echo esc_url(self::url('customer-register')); ?>"><?php echo esc_html(self::t('تسجيل/دخول','Register / sign in')); ?></a></div>
        <?php else: ?>
        <form id="v23-checkout" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tager_checkout"><input type="hidden" name="cart_json" id="cart_json"><input type="hidden" name="cart_mode" id="cart_mode" value="vendor"><?php wp_nonce_field('tager_checkout'); ?>
            <div class="form-grid"><label><?php echo esc_html(self::t('الاسم','Name')); ?><input required name="customer_name" value="<?php echo esc_attr($user->display_name); ?>"></label><label><?php echo esc_html(self::t('الهاتف','Phone')); ?><input required name="phone" value="<?php echo esc_attr(get_user_meta($user->ID,'phone',true)); ?>"></label><label><?php echo esc_html(self::t('البريد','Email')); ?><input required type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>"></label><label><?php echo esc_html(self::t('المحافظة','Governorate')); ?><select class="v23-governorate" required name="governorate"><option value=""><?php echo esc_html(self::t('اختر المحافظة','Choose governorate')); ?></option><?php foreach(self::governorates() as $ar=>$en) echo '<option value="'.esc_attr($ar).'">'.esc_html(self::t($ar,$en)).'</option>'; ?></select></label></div>
            <label><?php echo esc_html(self::t('العنوان التفصيلي','Detailed address')); ?><textarea required name="address" placeholder="<?php echo esc_attr(self::t('المدينة، المنطقة، الشارع، رقم المبنى','City, district, street, building number')); ?>"></textarea></label>
            <button class="btn primary" id="v23-place-order"><?php echo esc_html(self::t('تأكيد الطلب','Place order')); ?></button>
        </form><?php endif; ?></section>
        <script>
        (function(){
          const CATALOG=<?php echo wp_json_encode($catalog, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
          const TXT={empty:<?php echo wp_json_encode(self::t('السلة فارغة','Your cart is empty')); ?>, min:<?php echo wp_json_encode(self::t('متبقي للوصول للحد الأدنى','Remaining to minimum')); ?>, met:<?php echo wp_json_encode(self::t('تم تحقيق الحد الأدنى','Minimum reached')); ?>, invalid:<?php echo wp_json_encode(self::t('لا يمكن إتمام السلة العادية قبل تحقيق الحد الأدنى لكل مورد. يمكنك زيادة الطلب أو اختيار السلة الخاصة.','Normal checkout requires every vendor minimum. Add more products or choose the special mixed cart.')); ?>};
          function cart(){try{return JSON.parse(localStorage.getItem('tager_cart')||'[]')}catch(e){return []}}
          function price(p,q){return p.bulk_min&&q>=p.bulk_min?p.bulk:(p.wholesale_min&&q>=p.wholesale_min?p.wholesale:p.retail)}
          function money(v){return Number(v||0).toFixed(2)+' EGP'}
          function groups(){let g={};cart().forEach((x,i)=>{let p=CATALOG[x.id];if(!p)return;let q=Math.max(1,Math.min(parseInt(x.qty||1),p.max||9999,p.stock||9999));if(!g[p.vendor_id])g[p.vendor_id]={vendor:p.vendor_name,min:Number(p.vendor_min||0),url:p.store_url,items:[],subtotal:0};let unit=price(p,q),line=unit*q;g[p.vendor_id].items.push({index:i,p,q,unit,line});g[p.vendor_id].subtotal+=line});return g}
          window.v23Remove=function(i){let c=cart();c.splice(i,1);localStorage.setItem('tager_cart',JSON.stringify(c));render()}
          window.v23Qty=function(i,v){let c=cart(),p=CATALOG[c[i].id];c[i].qty=Math.max(1,Math.min(parseInt(v||1),p.max||9999,p.stock||9999));localStorage.setItem('tager_cart',JSON.stringify(c));render()}
          function render(){let root=document.getElementById('v23-cart-groups'),g=groups(),keys=Object.keys(g),subtotal=0,valid=true;if(!keys.length){root.innerHTML='<div class="notice">'+TXT.empty+'</div>'}else{root.innerHTML=keys.map(k=>{let v=g[k];subtotal+=v.subtotal;let pct=v.min?Math.min(100,(v.subtotal/v.min)*100):100,ok=v.subtotal>=v.min;if(!ok)valid=false;return '<section class="v23-vendor-cart"><header class="v23-vendor-head"><div><h3><a href="'+v.url+'">'+v.vendor+'</a></h3><div class="v23-min-progress">'+(ok?'<span class="v23-chip">'+TXT.met+'</span>':'<span class="v23-chip warn">'+TXT.min+': '+money(v.min-v.subtotal)+'</span>')+'<div class="v23-progress"><i style="width:'+pct+'%"></i></div></div></div><b>'+money(v.subtotal)+'</b></header>'+v.items.map(it=>'<div class="v23-cart-row"><div><b>'+it.p.name+'</b><small><br>'+money(it.unit)+' / unit</small></div><input type="number" min="1" max="'+Math.min(it.p.max,it.p.stock)+'" value="'+it.q+'" onchange="v23Qty('+it.index+',this.value)"><b class="v23-line-total">'+money(it.line)+'</b><button type="button" onclick="v23Remove('+it.index+')">×</button></div>').join('')+'</section>'}).join('')}
            let mixed=document.querySelector('input[name=v23_mode]:checked')?.value==='mixed',fee=mixed?subtotal*0.015:0;document.getElementById('v23-subtotal').textContent=money(subtotal);document.getElementById('v23-fee').textContent=money(fee);document.getElementById('v23-fee-row').style.display=mixed?'flex':'none';document.getElementById('v23-grand').textContent=money(subtotal+fee);document.getElementById('cart_mode').value=mixed?'mixed':'vendor';document.getElementById('v23-validation').innerHTML=(!mixed&&!valid)?'<div class="notice" style="margin-top:12px">'+TXT.invalid+'</div>':'';document.getElementById('v23-place-order')?.toggleAttribute('disabled',!keys.length||(!mixed&&!valid));}
          document.querySelectorAll('input[name=v23_mode]').forEach(x=>x.addEventListener('change',render));
          document.getElementById('v23-checkout')?.addEventListener('submit',function(e){let c=cart(),g=groups(),mixed=document.querySelector('input[name=v23_mode]:checked').value==='mixed';if(!c.length){e.preventDefault();return}if(!mixed&&Object.values(g).some(v=>v.subtotal<v.min)){e.preventDefault();alert(TXT.invalid);return}document.getElementById('cart_json').value=JSON.stringify(c);document.getElementById('cart_mode').value=mixed?'mixed':'vendor';});render();
        })();
        </script>
        <?php return ob_get_clean();
    }

    public static function checkout() {
        check_admin_referer('tager_checkout');
        if (!is_user_logged_in()) wp_die('Login required');
        $cart=json_decode(stripslashes($_POST['cart_json']??'[]'),true);
        if (!$cart || !is_array($cart)) wp_die('Empty cart');
        $mode=sanitize_key($_POST['cart_mode']??'vendor'); if(!in_array($mode,['vendor','mixed'],true))$mode='vendor';
        $groups=[];$lines=[];$subtotal=0;
        foreach($cart as $item){
            $id=(int)($item['id']??0);$product=get_post($id);if(!$product||$product->post_type!=='tager_product'||$product->post_status!=='publish')wp_die('Invalid product');
            $qty=max(1,(int)($item['qty']??1));$stock=(int)get_post_meta($id,'stock',true);$max=(int)(get_post_meta($id,'max_qty',true)?:9999);if($qty>$stock||$qty>$max)wp_die('Quantity exceeds stock or limits');
            $vendor=(int)$product->post_author;$unit=self::product_price($id,$qty);$line=$unit*$qty;$subtotal+=$line;
            if(!isset($groups[$vendor]))$groups[$vendor]=['vendor_id'=>$vendor,'vendor_name'=>self::vendor_name($vendor),'minimum'=>self::vendor_min($vendor),'subtotal'=>0,'items'=>[]];
            $row=['id'=>$id,'name'=>$product->post_title,'qty'=>$qty,'unit_price'=>$unit,'line_total'=>$line,'vendor_id'=>$vendor];$groups[$vendor]['items'][]=$row;$groups[$vendor]['subtotal']+=$line;$lines[]=$row;
        }
        if($mode==='vendor')foreach($groups as $group)if($group['subtotal']+0.0001<$group['minimum'])wp_die(sprintf('Vendor minimum not reached for %s. Minimum %.2f EGP.',esc_html($group['vendor_name']),$group['minimum']));
        $fee=$mode==='mixed'?round($subtotal*(self::MIXED_FEE_PERCENT/100),2):0;$total=$subtotal+$fee;
        // Deduct stock only after every validation succeeds.
        foreach($lines as $row){$stock=(int)get_post_meta($row['id'],'stock',true);update_post_meta($row['id'],'stock',max(0,$stock-$row['qty']));}
        $uid=get_current_user_id();$order=wp_insert_post(['post_type'=>'tager_order','post_status'=>'publish','post_title'=>'Order '.current_time('Ymd-His'),'post_author'=>$uid]);
        if(is_wp_error($order)||!$order)wp_die('Unable to create order');
        update_post_meta($order,'customer_name',sanitize_text_field($_POST['customer_name']??''));update_post_meta($order,'phone',sanitize_text_field($_POST['phone']??''));update_post_meta($order,'email',sanitize_email($_POST['email']??''));update_post_meta($order,'governorate',sanitize_text_field($_POST['governorate']??''));update_post_meta($order,'address',sanitize_textarea_field($_POST['address']??''));
        update_post_meta($order,'customer_user',$uid);update_post_meta($order,'items',$lines);update_post_meta($order,'vendor_groups',array_values($groups));update_post_meta($order,'cart_mode',$mode);update_post_meta($order,'subtotal',$subtotal);update_post_meta($order,'mixed_cart_fee',$fee);update_post_meta($order,'mixed_cart_fee_percent',$mode==='mixed'?self::MIXED_FEE_PERCENT:0);update_post_meta($order,'total',$total);update_post_meta($order,'order_status','New');
        wp_safe_redirect(add_query_arg(['msg'=>'order_done','order'=>$order],self::url('my-account')));exit;
    }

    public static function vendor_store() {
        $vendor=(int)($_GET['vendor']??0);$user=$vendor?get_userdata($vendor):false;
        if(!$user||(!in_array('tager_vendor',(array)$user->roles,true)&&!current_user_can('manage_options')))return '<div class="notice">'.esc_html(self::t('المورد غير موجود','Vendor not found')).'</div>';
        $store=self::vendor_name($vendor);$minimum=self::vendor_min($vendor);$rating=get_user_meta($vendor,'vendor_score_rating',true)?:5;
        $q=new WP_Query(['post_type'=>'tager_product','post_status'=>'publish','author'=>$vendor,'posts_per_page'=>60,'meta_query'=>[['key'=>'approval_status','value'=>'approved']]]);
        ob_start(); ?>
        <section class="v23-store-hero"><div><span class="eyebrow"><?php echo esc_html(self::t('متجر مورد معتمد','Approved vendor store')); ?></span><h1><?php echo esc_html($store); ?></h1><p><?php echo esc_html(get_user_meta($vendor,'notes',true)); ?></p><div class="v23-store-stats"><span>★ <?php echo esc_html($rating); ?>/5</span><span><?php echo esc_html($q->found_posts.' '.self::t('منتج','products')); ?></span><span><?php echo esc_html(self::t('الحد الأدنى للطلب','Minimum order')); ?>: <?php echo esc_html(number_format($minimum,2)); ?> EGP</span></div></div><a class="btn secondary" href="<?php echo esc_url(self::url('vendors')); ?>"><?php echo esc_html(self::t('كل الموردين','All vendors')); ?></a></section>
        <?php if(!$q->have_posts()): ?><div class="notice"><?php echo esc_html(self::t('لا توجد منتجات منشورة لهذا المورد حاليًا.','This vendor has no published products yet.')); ?></div><?php else: ?><div class="product-grid">
        <?php while($q->have_posts()):$q->the_post();$id=get_the_ID();$name=self::lang()==='en'?(get_post_meta($id,'name_en',true)?:get_the_title()):get_the_title();$r=(float)get_post_meta($id,'retail_price',true);$w=(float)get_post_meta($id,'wholesale_price',true);$b=(float)get_post_meta($id,'bulk_price',true);$wm=(int)get_post_meta($id,'wholesale_min',true);$bm=(int)get_post_meta($id,'bulk_min',true);$max=(int)(get_post_meta($id,'max_qty',true)?:9999); ?>
        <article class="product-card"><a class="product-image" href="<?php the_permalink(); ?>"><?php echo has_post_thumbnail()?get_the_post_thumbnail($id,'medium'):'<span>📦</span>'; ?></a><div class="product-body"><span class="v23-chip"><?php echo esc_html($store); ?></span><h3><a href="<?php the_permalink(); ?>"><?php echo esc_html($name); ?></a></h3><div class="price"><b><?php echo esc_html(number_format($r,2)); ?> EGP</b></div><div class="tiers"><span><?php echo esc_html(self::t('جملة','Wholesale').': '.number_format($w,2).' / '.$wm.'+'); ?></span><span><?php echo esc_html(self::t('جملة الجملة','Bulk').': '.number_format($b,2).' / '.$bm.'+'); ?></span></div><div class="qty-row"><input type="number" min="1" max="<?php echo esc_attr($max); ?>" value="1" id="qty-<?php echo $id; ?>"><button class="btn primary" onclick="tagerAdd(<?php echo $id; ?>,'<?php echo esc_js($name); ?>')"><?php echo esc_html(self::t('أضف للسلة','Add to cart')); ?></button></div></div></article>
        <?php endwhile;wp_reset_postdata(); ?></div><?php endif; ?>
        <?php return ob_get_clean();
    }
}
Tager_V23_Vendor_Cart_Egypt::init();
