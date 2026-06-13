<?php
/**
 * Plugin Name: Tager V58 Layout Normalization
 * Description: Final front-end layout, spacing, responsive and duplicate-action cleanup layer.
 * Version: 58.0.0
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    wp_register_style('tager-v58-layout', false, [], '58.0.0');
    wp_enqueue_style('tager-v58-layout');
    wp_add_inline_style('tager-v58-layout', <<<'CSS'
:root{
  --t58-green:#123c2d;--t58-green-2:#1d5f48;--t58-gold:#c79a35;
  --t58-bg:#f6f8f6;--t58-card:#fff;--t58-text:#16231d;--t58-muted:#68766f;
  --t58-line:#dfe7e2;--t58-radius:18px;--t58-shadow:0 10px 30px rgba(18,60,45,.08)
}
html{overflow-x:hidden;scroll-behavior:smooth}
body{overflow-x:hidden;background:var(--t58-bg);color:var(--t58-text)}
*,*::before,*::after{box-sizing:border-box}
img,svg,video,canvas,iframe{max-width:100%;height:auto}
.site-main,.entry-content,.page-content{min-width:0}
.site-main{width:min(100%,1280px);margin-inline:auto;padding-inline:20px}
.entry-content>:first-child{margin-top:0}.entry-content>:last-child{margin-bottom:0}

/* Unified page shells */
:is(.tv57-shell,.tv57-auth,.tv55-shell,.tv54,.tv53-shell,.tv50-shell,.tager-v10-admin,.tager-v11-admin,.tager-v26-admin,.tager-v29-admin){
 width:min(100%,1180px)!important;max-width:1180px!important;margin:28px auto!important;padding:0 20px!important;min-width:0!important
}
:is(.tv57-narrow,.tv55-shell.tv55-narrow,.tv54.tv54-narrow){max-width:760px!important}

/* Prevent overlapping data blocks */
:is(.tv57-grid2,.tv57-grid3,.tv55-grid,.tv54-grid,.tv53-grid,.tv50-grid,.t40-grid,.t42-grid3,.t43-media-grid,.t44-grid,.t49-grid,.tv47-grid,.tv48-grid,.product-grid,.feature-grid,.form-grid,.stat-grid){
 display:grid!important;gap:20px!important;align-items:stretch!important;min-width:0!important
}
.tv57-grid2{grid-template-columns:repeat(2,minmax(0,1fr))!important}
:is(.tv57-grid3,.tv55-grid,.tv54-grid,.tv53-grid,.tv50-grid,.t40-grid,.t42-grid3,.t49-grid,.tv47-grid,.tv48-grid){grid-template-columns:repeat(3,minmax(0,1fr))!important}
:is(.product-grid,.feature-grid){grid-template-columns:repeat(4,minmax(0,1fr))!important}
:is(.form-grid,.t43-media-grid,.t44-grid){grid-template-columns:repeat(2,minmax(0,1fr))!important}

/* Cards */
:is(.tv57-card,.tv57-choice,.tv55-card,.tv54-card,.tv54-panel,.tv53-card,.tv53-panel,.tv50-card,.t40-card,.t41-card,.t42-card,.t44-card,.t49-card,.tv47-card,.tv48-card,.card,.form-card,.product-card,.checkout-card,.notice-card){
 min-width:0!important;max-width:100%!important;overflow:hidden!important;background:var(--t58-card)!important;border:1px solid var(--t58-line)!important;border-radius:var(--t58-radius)!important;box-shadow:var(--t58-shadow)!important
}
:is(.tv57-card,.tv57-choice,.tv55-card,.tv54-card,.tv54-panel,.tv53-card,.tv53-panel,.tv50-card,.t40-card,.t41-card,.t42-card,.t44-card,.t49-card,.tv47-card,.tv48-card,.card,.form-card,.checkout-card,.notice-card){padding:24px!important}
:is(.tv57-card,.tv57-choice,.tv55-card,.tv54-card,.tv53-card,.tv50-card,.t40-card,.t41-card,.t42-card,.t44-card,.t49-card,.tv47-card,.tv48-card) :is(h1,h2,h3,h4,p,li,span,strong,a){overflow-wrap:anywhere}

/* Auth and forms */
.tv57-auth{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:26px!important;align-items:stretch!important}
.tv57-auth-side,.tv57-form-card{min-width:0!important;height:auto!important}
form{min-width:0}
form label{display:block;line-height:1.45}
form :is(input:not([type=checkbox]):not([type=radio]),select,textarea){
 width:100%!important;max-width:100%!important;min-width:0!important;min-height:48px!important;padding:12px 14px!important;border:1px solid #cad7cf!important;border-radius:12px!important;background:#fff!important;color:var(--t58-text)!important;box-shadow:none!important
}
form textarea{min-height:120px!important;resize:vertical}
form :is(input,select,textarea):focus{outline:3px solid rgba(199,154,53,.18)!important;border-color:var(--t58-gold)!important}
.tv57-check{display:flex!important;align-items:flex-start!important;gap:10px!important}.tv57-check input{width:auto!important;min-height:auto!important;margin-top:4px!important;flex:0 0 auto}

/* Buttons never collide */
:is(.tv57-btn,.tv55-btn,.tv54-btn,.tv53-btn,.tv56-btn,.btn,button,input[type=submit],input[type=button],.button){
 max-width:100%;min-height:44px;white-space:normal!important;text-align:center;line-height:1.3!important;overflow-wrap:anywhere
}
:is(.tv57-links,.tv55-actions,.tv54-actions,.tv53-links,.tv50-actions,.hero-actions,.v12-hero-actions){display:flex!important;gap:12px!important;flex-wrap:wrap!important;align-items:center!important}
:is(.tv57-links,.tv55-actions,.tv54-actions,.tv53-links,.tv50-actions)>*{min-width:0}

/* Tables: no overlap on desktop, scroll on narrow screens */
:is(.tv55-table-wrap,.tv50-table-wrap,.tv53-table,.tv54-table,.table-responsive){width:100%;max-width:100%;overflow-x:auto!important;-webkit-overflow-scrolling:touch}
table{width:100%!important;max-width:100%!important;border-collapse:collapse!important;table-layout:auto}
th,td{padding:12px 14px!important;vertical-align:top!important;text-align:start!important;white-space:normal!important;overflow-wrap:anywhere!important;border-bottom:1px solid var(--t58-line)}
th{background:#f0f5f2;font-weight:800}

/* Product cards and media */
.product-card,.tv56-product{display:flex!important;flex-direction:column!important;height:100%!important}
.product-card img,.tv56-product-media img{width:100%!important;aspect-ratio:4/3!important;object-fit:cover!important;display:block!important}
.product-card .product-body,.tv56-product-body{display:flex!important;flex-direction:column!important;gap:10px!important;flex:1!important;min-width:0!important}
.product-card .btn,.tv56-product .tv56-btn{margin-top:auto}

/* Headings and sections */
:is(.tv57-head,.tv55-section-head,.tv54-head,.tv53-head,.tv50-hero){margin-bottom:22px!important;min-width:0}
:is(.tv57-head,.tv55-section-head,.tv54-head,.tv53-head) h1{font-size:clamp(28px,4vw,42px)!important;line-height:1.15!important;margin:8px 0 10px!important}
h1,h2,h3,h4{line-height:1.25;overflow-wrap:anywhere}
p{line-height:1.75}

/* Remove legacy debug/context strips that duplicate navigation/content */
.tv54-context,.tv54-human-note,.tv50-crumb+.tv50-crumb,.tager-admin-check,.tager-note[data-debug="1"]{display:none!important}

/* Header/nav stability */
.header-inner{display:flex!important;align-items:center!important;gap:18px!important;min-width:0!important}
.main-nav{min-width:0!important}.main-nav ul{display:flex;gap:6px;flex-wrap:wrap;margin:0;padding:0;list-style:none}.main-nav a{white-space:normal}
.header-actions{display:flex!important;gap:8px!important;flex-wrap:wrap!important;align-items:center!important}

/* Notices */
:is(.tv57-alert,.tv55-notice,.tv55-error,.tv55-success,.tv54-status,.tv53-alert,.tv50-alert){position:static!important;max-width:100%!important;overflow-wrap:anywhere!important;margin:14px 0!important}

/* Footer */
.site-footer{overflow:hidden}.footer-inner{gap:28px!important}.footer-inner>*{min-width:0}

@media (max-width:1100px){
 :is(.product-grid,.feature-grid){grid-template-columns:repeat(3,minmax(0,1fr))!important}
 :is(.tv57-grid3,.tv55-grid,.tv54-grid,.tv53-grid,.tv50-grid,.t40-grid,.t42-grid3,.t49-grid,.tv47-grid,.tv48-grid){grid-template-columns:repeat(2,minmax(0,1fr))!important}
}
@media (max-width:820px){
 .site-main{padding-inline:14px!important}
 :is(.tv57-shell,.tv57-auth,.tv55-shell,.tv54,.tv53-shell,.tv50-shell){padding-inline:0!important;margin-block:18px!important}
 .tv57-auth{grid-template-columns:1fr!important}.tv57-auth-side{padding:30px!important}.tv57-auth-side h1{font-size:34px!important}
 :is(.product-grid,.feature-grid,.form-grid,.t43-media-grid,.t44-grid){grid-template-columns:repeat(2,minmax(0,1fr))!important}
 .header-inner{flex-wrap:wrap!important}.main-nav{order:3;width:100%}.main-nav ul{flex-direction:column;align-items:stretch}.main-nav a{display:block;width:100%}
}
@media (max-width:560px){
 :is(.tv57-grid2,.tv57-grid3,.tv55-grid,.tv54-grid,.tv53-grid,.tv50-grid,.t40-grid,.t42-grid3,.t49-grid,.tv47-grid,.tv48-grid,.product-grid,.feature-grid,.form-grid,.t43-media-grid,.t44-grid){grid-template-columns:1fr!important}
 :is(.tv57-card,.tv57-choice,.tv55-card,.tv54-card,.tv54-panel,.tv53-card,.tv53-panel,.tv50-card,.t40-card,.t41-card,.t42-card,.t44-card,.t49-card,.tv47-card,.tv48-card,.card,.form-card,.checkout-card,.notice-card){padding:18px!important;border-radius:15px!important}
 :is(.tv57-links,.tv55-actions,.tv54-actions,.tv53-links,.tv50-actions,.hero-actions,.v12-hero-actions){flex-direction:column!important;align-items:stretch!important}
 :is(.tv57-links,.tv55-actions,.tv54-actions,.tv53-links,.tv50-actions,.hero-actions,.v12-hero-actions)>*{width:100%!important}
 :is(.tv57-btn,.tv55-btn,.tv54-btn,.tv53-btn,.tv56-btn,.btn,button,input[type=submit],input[type=button],.button){width:100%;justify-content:center}
 th,td{min-width:130px}
}
CSS);
}, 99999);

add_action('wp_footer', function () {
    if (is_admin()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      // Remove only exact duplicate actions inside the same local action group.
      document.querySelectorAll('.tv57-links,.tv55-actions,.tv54-actions,.tv53-links,.tv50-actions,.hero-actions,.v12-hero-actions').forEach(function(group){
        const seen=new Set();
        group.querySelectorAll(':scope > a,:scope > button').forEach(function(el){
          const key=(el.tagName==='A'?(el.getAttribute('href')||''):'button')+'|'+(el.textContent||'').trim().replace(/\s+/g,' ');
          if(key==='|'||key==='button|') return;
          if(seen.has(key)) el.remove(); else seen.add(key);
        });
      });
      // Wrap loose tables so they cannot break the page width.
      document.querySelectorAll('.entry-content table').forEach(function(table){
        if(!table.parentElement.classList.contains('t58-table-scroll')){
          const wrap=document.createElement('div');wrap.className='t58-table-scroll';
          wrap.style.cssText='width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch';
          table.parentNode.insertBefore(wrap,table);wrap.appendChild(table);
        }
      });
      // Mark genuinely empty links instead of letting them jump to the page top.
      document.querySelectorAll('a[href=""],a[href="#"]').forEach(function(a){
        if(!a.dataset.t58Handled){a.dataset.t58Handled='1';a.setAttribute('aria-disabled','true');a.style.opacity='.55';a.addEventListener('click',function(e){e.preventDefault();});}
      });
    });
    </script>
    <?php
}, 99999);
