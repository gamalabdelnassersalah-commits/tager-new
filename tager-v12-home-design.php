<?php
/**
 * Plugin Name: Tager V12 Premium Homepage
 * Description: Premium storefront homepage, navigation UX and fully linked calls to action.
 * Version: 12.0.0
 */
if (!defined('ABSPATH')) exit;

class Tager_V12_Home_Design {
    public static function init(){
        add_action('init',[__CLASS__,'replace_home_shortcode'],999);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],99);
    }
    public static function replace_home_shortcode(){
        remove_shortcode('tager_home');
        add_shortcode('tager_home',[__CLASS__,'home']);
    }
    private static function lang(){ return (isset($_GET['lang']) && $_GET['lang']==='en') ? 'en':'ar'; }
    private static function t($ar,$en){ return self::lang()==='en' ? $en : $ar; }
    private static function url($slug){ $p=get_option('tager_pages',[]); return isset($p[$slug]) ? get_permalink($p[$slug]) : home_url('/'.$slug.'/'); }
    private static function count_type($type){ $c=wp_count_posts($type); return isset($c->publish)?(int)$c->publish:0; }
    public static function assets(){
        wp_add_inline_script('tager-js',"document.addEventListener('DOMContentLoaded',function(){var f=document.querySelector('.v12-search');if(f){f.addEventListener('submit',function(e){e.preventDefault();var q=f.querySelector('input').value.trim();var u=f.dataset.shop;if(q)u+=(u.indexOf('?')>-1?'&':'?')+'search='+encodeURIComponent(q);window.location.href=u;});}document.querySelectorAll('[data-scroll-products]').forEach(function(b){b.addEventListener('click',function(e){var x=document.querySelector('#featured-products');if(x){e.preventDefault();x.scrollIntoView({behavior:'smooth'});}})});});");
    }
    public static function home(){
        $shop=self::url('shop'); $customer=self::url('customer-register'); $vendor=self::url('vendor-register'); $account=self::url('my-account');
        $products=self::count_type('tager_product');
        $vendors=count(get_users(['role'=>'tager_vendor']));
        ob_start(); ?>
        <div class="v12-home">
          <section class="v12-hero">
            <div class="v12-hero-copy">
              <span class="v12-kicker"><span class="dashicons dashicons-shield-alt"></span><?php echo esc_html(self::t('موردون موثّقون وأسعار تناسب كل كمية','Verified vendors and pricing for every quantity'));?></span>
              <h1><?php echo esc_html(self::t('كل احتياجاتك… من قطعة واحدة إلى أكبر طلب جملة','Everything you need — from one item to your largest wholesale order'));?></h1>
              <p><?php echo esc_html(self::t('تسوّق قطاعي، جملة، أو جملة الجملة في منصة واحدة. قارن الأسعار، اختر الكمية، واطلب بسهولة من موردين معتمدين.','Shop retail, wholesale or bulk wholesale in one marketplace. Compare pricing, choose quantity and order easily from approved vendors.'));?></p>
              <form class="v12-search" data-shop="<?php echo esc_url($shop);?>">
                <span class="dashicons dashicons-search"></span>
                <input type="search" placeholder="<?php echo esc_attr(self::t('ابحث عن منتج، قسم، أو مورد…','Search products, categories or vendors…'));?>" aria-label="Search">
                <button type="submit"><?php echo esc_html(self::t('بحث','Search'));?></button>
              </form>
              <div class="v12-hero-actions">
                <a class="btn primary v12-btn-lg" href="<?php echo esc_url($shop);?>"><span class="dashicons dashicons-store"></span><?php echo esc_html(self::t('ابدأ التسوق','Start shopping'));?></a>
                <a class="btn v12-btn-light v12-btn-lg" href="<?php echo esc_url($vendor);?>"><span class="dashicons dashicons-businessman"></span><?php echo esc_html(self::t('ابدأ البيع على تاجر','Start selling on Tager'));?></a>
              </div>
              <div class="v12-trust-row"><span>✓ <?php echo esc_html(self::t('مراجعة الموردين','Vendor verification'));?></span><span>✓ <?php echo esc_html(self::t('أسعار متدرجة','Tiered pricing'));?></span><span>✓ <?php echo esc_html(self::t('طلبات آمنة','Secure ordering'));?></span></div>
            </div>
            <div class="v12-hero-visual" aria-hidden="true">
              <div class="v12-orbit orbit-a"></div><div class="v12-orbit orbit-b"></div>
              <div class="v12-shopping-card main-card"><span class="dashicons dashicons-cart"></span><b><?php echo esc_html(self::t('سلة واحدة','One cart'));?></b><small><?php echo esc_html(self::t('لكل احتياجاتك','For all your needs'));?></small></div>
              <div class="v12-floating-card card-retail"><b>1+</b><span><?php echo esc_html(self::t('قطاعي','Retail'));?></span></div>
              <div class="v12-floating-card card-wholesale"><b>10+</b><span><?php echo esc_html(self::t('جملة','Wholesale'));?></span></div>
              <div class="v12-floating-card card-bulk"><b>50+</b><span><?php echo esc_html(self::t('جملة الجملة','Bulk'));?></span></div>
            </div>
          </section>

          <section class="v12-stats" aria-label="Marketplace statistics">
            <article><span class="dashicons dashicons-products"></span><div><b><?php echo esc_html(max(4,$products));?>+</b><small><?php echo esc_html(self::t('منتجات متاحة','Available products'));?></small></div></article>
            <article><span class="dashicons dashicons-groups"></span><div><b><?php echo esc_html(max(1,$vendors));?>+</b><small><?php echo esc_html(self::t('موردون معتمدون','Approved vendors'));?></small></div></article>
            <article><span class="dashicons dashicons-tag"></span><div><b>3</b><small><?php echo esc_html(self::t('مستويات تسعير','Pricing levels'));?></small></div></article>
            <article><span class="dashicons dashicons-admin-site-alt3"></span><div><b>24/7</b><small><?php echo esc_html(self::t('تسوق في أي وقت','Shop anytime'));?></small></div></article>
          </section>

          <section class="v12-section">
            <div class="v12-section-head"><div><span><?php echo esc_html(self::t('تصفح بسهولة','Browse easily'));?></span><h2><?php echo esc_html(self::t('أهم الأقسام','Popular categories'));?></h2></div><a href="<?php echo esc_url($shop);?>"><?php echo esc_html(self::t('عرض كل المنتجات','View all products'));?> ←</a></div>
            <div class="v12-categories">
              <?php $cats=[['🥫','مواد غذائية','Groceries'],['🧴','زيوت','Oils'],['🧻','مناديل','Tissues'],['🥛','ألبان','Dairy'],['🧼','منظفات','Cleaning'],['📦','عروض الجملة','Wholesale deals']]; foreach($cats as $c): ?>
              <a href="<?php echo esc_url($shop);?>"><span><?php echo $c[0];?></span><b><?php echo esc_html(self::t($c[1],$c[2]));?></b><small><?php echo esc_html(self::t('استكشف المنتجات','Explore products'));?></small></a>
              <?php endforeach;?>
            </div>
          </section>

          <section id="featured-products" class="v12-section v12-featured">
            <div class="v12-section-head"><div><span><?php echo esc_html(self::t('مختارة لك','Picked for you'));?></span><h2><?php echo esc_html(self::t('منتجات مميزة','Featured products'));?></h2></div><a href="<?php echo esc_url($shop);?>"><?php echo esc_html(self::t('تسوق الكل','Shop all'));?> ←</a></div>
            <?php echo do_shortcode('[tager_shop]'); ?>
          </section>

          <section class="v12-how">
            <div class="v12-section-head centered"><div><span><?php echo esc_html(self::t('تجربة بسيطة','Simple experience'));?></span><h2><?php echo esc_html(self::t('كيف تعمل منصة تاجر؟','How Tager works'));?></h2></div></div>
            <div class="v12-steps">
              <article><i>1</i><span class="dashicons dashicons-search"></span><h3><?php echo esc_html(self::t('ابحث واختر','Search and choose'));?></h3><p><?php echo esc_html(self::t('اعثر على المنتج المناسب وقارن مستويات السعر.','Find the right product and compare pricing levels.'));?></p></article>
              <article><i>2</i><span class="dashicons dashicons-cart"></span><h3><?php echo esc_html(self::t('حدد الكمية','Choose quantity'));?></h3><p><?php echo esc_html(self::t('السعر يتغير تلقائيًا حسب كمية الطلب.','Pricing updates automatically based on order quantity.'));?></p></article>
              <article><i>3</i><span class="dashicons dashicons-yes-alt"></span><h3><?php echo esc_html(self::t('أكد طلبك','Confirm your order'));?></h3><p><?php echo esc_html(self::t('راجع بياناتك وأرسل الطلب في خطوات واضحة.','Review your details and submit in clear steps.'));?></p></article>
              <article><i>4</i><span class="dashicons dashicons-location-alt"></span><h3><?php echo esc_html(self::t('تابع التوصيل','Track delivery'));?></h3><p><?php echo esc_html(self::t('تابع حالة الطلب من حسابك حتى الاستلام.','Track your order status from account to delivery.'));?></p></article>
            </div>
          </section>

          <section class="v12-split-cta">
            <article class="buyer"><span class="dashicons dashicons-admin-users"></span><div><small><?php echo esc_html(self::t('للأفراد والشركات','For individuals and businesses'));?></small><h2><?php echo esc_html(self::t('أنشئ حساب مشتري وابدأ الطلب','Create a buyer account and start ordering'));?></h2><p><?php echo esc_html(self::t('احفظ عناوينك، تابع طلباتك، واستفد من أسعار الكميات.','Save addresses, track orders and benefit from quantity pricing.'));?></p><a class="btn primary" href="<?php echo esc_url($customer);?>"><?php echo esc_html(self::t('تسجيل العميل','Customer registration'));?></a><a class="v12-text-link" href="<?php echo esc_url($account);?>"><?php echo esc_html(self::t('لدي حساب بالفعل','I already have an account'));?></a></div></article>
            <article class="seller"><span class="dashicons dashicons-store"></span><div><small><?php echo esc_html(self::t('للموردين والمصنعين','For vendors and manufacturers'));?></small><h2><?php echo esc_html(self::t('وسّع مبيعاتك مع تاجر','Grow your sales with Tager'));?></h2><p><?php echo esc_html(self::t('أضف منتجاتك وأسعارك ومخزونك، واستقبل الطلبات من لوحة واحدة.','Add products, pricing and stock, and receive orders from one dashboard.'));?></p><a class="btn v12-gold-btn" href="<?php echo esc_url($vendor);?>"><?php echo esc_html(self::t('انضم كمورد','Become a vendor'));?></a></div></article>
          </section>

          <section class="v12-final-cta"><div><span><?php echo esc_html(self::t('جاهز للبدء؟','Ready to begin?'));?></span><h2><?php echo esc_html(self::t('اكتشف أسعار أفضل لكل كمية','Discover better pricing for every quantity'));?></h2></div><a class="btn v12-gold-btn v12-btn-lg" href="<?php echo esc_url($shop);?>"><?php echo esc_html(self::t('تصفح المنتجات الآن','Browse products now'));?></a></section>
        </div>
        <?php return ob_get_clean();
    }
}
Tager_V12_Home_Design::init();
