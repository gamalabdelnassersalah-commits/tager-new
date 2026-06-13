<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#07543d"><?php wp_head();?></head><body <?php body_class();?>><?php wp_body_open();
$lang=(isset($_GET['lang'])&&$_GET['lang']==='en')?'en':'ar';
$url=function($slug){$p=get_page_by_path($slug);return $p?get_permalink($p):home_url('/'.$slug.'/');};
$account_url=is_user_logged_in() && class_exists('Tager_V56_Production_Preview') ? Tager_V56_Production_Preview::role_home() : $url('login');
?>
<header class="site-header"><div class="topbar"><div>توصيل موثوق وأسعار قطاعي وجملة وجملة الجملة</div><div>Secure marketplace · Verified vendors</div></div><div class="header-inner">
<a class="brand" href="<?php echo esc_url(home_url('/'));?>" aria-label="Tager Home"><span class="brand-mark">T</span><span>Ta<em>ger</em></span></a>
<button class="menu-toggle" type="button" aria-expanded="false" aria-label="فتح القائمة"><span class="dashicons dashicons-menu-alt3"></span></button>
<nav class="main-nav" aria-label="القائمة الرئيسية">
<a href="<?php echo esc_url(home_url('/'));?>"><?php echo $lang==='en'?'Home':'الرئيسية';?></a>
<a href="<?php echo esc_url($url('market'));?>"><?php echo $lang==='en'?'Marketplace':'السوق';?></a>
<a href="<?php echo esc_url($url('categories'));?>"><?php echo $lang==='en'?'Categories':'الأقسام';?></a>
<a href="<?php echo esc_url($url('vendors'));?>"><?php echo $lang==='en'?'Vendors':'الموردون';?></a>
<a href="<?php echo esc_url($url('deals'));?>"><?php echo $lang==='en'?'Deals':'العروض';?></a>
<a href="<?php echo esc_url($url('business'));?>"><?php echo $lang==='en'?'Business':'للشركات';?></a>
</nav>
<div class="header-actions">
<a class="lang-switch" href="<?php echo esc_url(add_query_arg('lang',$lang==='en'?'ar':'en'));?>"><span class="dashicons dashicons-translation"></span><?php echo $lang==='en'?'العربية':'English';?></a>
<a class="icon-action" href="<?php echo esc_url($account_url);?>" title="Account"><span class="dashicons dashicons-admin-users"></span><span><?php echo is_user_logged_in()?($lang==='en'?'Dashboard':'حسابي'):($lang==='en'?'Login':'دخول');?></span></a>
<a class="cart-action" href="<?php echo esc_url($url('cart'));?>"><span class="dashicons dashicons-cart"></span><span><?php echo $lang==='en'?'Cart':'السلة';?></span><b data-cart-count hidden>0</b></a>
</div></div></header><main class="site-main">
