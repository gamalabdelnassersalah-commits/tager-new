<?php
/**
 * Plugin Name: Tager V51 UI Buttons & Layout Polish
 * Description: Unified premium buttons, forms, tables, cards, responsive spacing, accessibility and interaction states across Tager pages.
 * Version: 51.0.0
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    wp_register_style('tager-v51-ui', false, [], '51.0.0');
    wp_enqueue_style('tager-v51-ui');
    wp_add_inline_style('tager-v51-ui', <<<'CSS'
:root{
  --tg-green:#0f5a43;--tg-green-2:#16745a;--tg-green-3:#e9f5f0;
  --tg-gold:#d5a82e;--tg-gold-2:#f5e7b8;--tg-ink:#17231f;--tg-muted:#65746f;
  --tg-bg:#f6f8f7;--tg-card:#fff;--tg-line:#dfe7e3;--tg-danger:#b42318;
  --tg-success:#137a51;--tg-warning:#a15c00;--tg-radius:16px;--tg-shadow:0 12px 36px rgba(19,63,49,.09)
}
html{scroll-behavior:smooth} body{background:var(--tg-bg);color:var(--tg-ink);font-family:Tahoma,Arial,sans-serif}
body.rtl{letter-spacing:0}.site-main,.entry-content{min-height:50vh}
/* unified containers */
[class*="t40-shell"],[class*="t41-wrap"],[class*="t42-shell"],[class*="t44-wrap"],[class*="t45-wrap"],
[class*="t46-wrap"],[class*="t47-wrap"],[class*="t48-wrap"],[class*="t49-wrap"],[class*="t50-wrap"]{
  width:min(1180px,calc(100% - 28px));margin:28px auto;padding:0
}
/* cards and panels */
[class*="-card"],[class*="-panel"],.t40-card,.t41-card,.t42-card,.t44-card,.t45-card{
  background:var(--tg-card);border:1px solid var(--tg-line);border-radius:var(--tg-radius);
  box-shadow:var(--tg-shadow);padding:22px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease
}
[class*="-card"]:hover{transform:translateY(-2px);box-shadow:0 16px 42px rgba(19,63,49,.12);border-color:#c9d9d1}
/* headings */
h1,h2,h3{color:var(--tg-ink);line-height:1.35}h1{font-size:clamp(28px,4vw,46px)}h2{font-size:clamp(22px,3vw,32px)}
/* all action buttons */
a[class*="btn"],button[class*="btn"],input[type="submit"],button[type="submit"],.button,.wp-element-button,
.woocommerce a.button,.woocommerce button.button,.woocommerce input.button{
  appearance:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:11px 18px;
  border:1px solid transparent;border-radius:12px;background:linear-gradient(135deg,var(--tg-green),var(--tg-green-2));
  color:#fff!important;font-weight:700;font-size:15px;line-height:1.2;text-decoration:none;cursor:pointer;
  box-shadow:0 7px 18px rgba(15,90,67,.18);transition:transform .16s ease,box-shadow .16s ease,filter .16s ease,border-color .16s ease
}
a[class*="btn"]:hover,button[class*="btn"]:hover,input[type="submit"]:hover,.button:hover,.wp-element-button:hover,
.woocommerce a.button:hover,.woocommerce button.button:hover{transform:translateY(-1px);filter:brightness(1.04);box-shadow:0 11px 24px rgba(15,90,67,.24)}
a[class*="btn"]:active,button[class*="btn"]:active,input[type="submit"]:active,.button:active{transform:translateY(0) scale(.98)}
a[class*="btn"]:focus-visible,button[class*="btn"]:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{
  outline:3px solid rgba(213,168,46,.38);outline-offset:2px
}
[class*="btn"][class*="secondary"],[class*="btn"][class*="ghost"],.button-secondary{
  background:#fff;color:var(--tg-green)!important;border-color:#b8d0c6;box-shadow:none
}
[class*="btn"][class*="danger"]{background:linear-gradient(135deg,#b42318,#d92d20);color:#fff!important}
[class*="btn"][class*="small"],.button-small{min-height:36px;padding:8px 12px;border-radius:10px;font-size:13px}
button[disabled],input[disabled],.is-loading{opacity:.6;cursor:not-allowed;transform:none!important;filter:none!important}
/* forms */
.t40-form,.t42-form,.t43-form,.t45-form,form[class*="t4"],form[class*="t5"]{display:grid;gap:16px}
label{font-weight:700;color:#263a33}input[type="text"],input[type="email"],input[type="tel"],input[type="password"],input[type="number"],input[type="url"],input[type="search"],select,textarea{
  width:100%;min-height:46px;border:1px solid #cad8d1;border-radius:11px;background:#fff;color:var(--tg-ink);padding:11px 13px;
  box-shadow:0 1px 0 rgba(15,90,67,.02);transition:border-color .16s ease,box-shadow .16s ease
}
textarea{min-height:120px;resize:vertical}input:focus,select:focus,textarea:focus{border-color:var(--tg-green-2);box-shadow:0 0 0 4px rgba(22,116,90,.10);outline:0}
.description,.form-hint,.tager-muted{color:var(--tg-muted);font-size:13px}
/* grids */
[class*="-grid"],[class*="-cards"],[class*="-stats"]{gap:18px}
.t40-grid,.t41-grid,.t42-cards,.t42-grid3,.t44-grid,.t45-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr))}
/* tables */
[class*="table-wrap"]{overflow:auto;border:1px solid var(--tg-line);border-radius:14px;background:#fff}
table[class*="t4"],.widefat,.woocommerce table.shop_table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid var(--tg-line);border-radius:14px;overflow:hidden}
table th,.widefat th,.woocommerce table.shop_table th{background:#eef5f2;color:#23483c;font-weight:800;padding:13px 14px;text-align:start;border-bottom:1px solid var(--tg-line)}
table td,.widefat td,.woocommerce table.shop_table td{padding:13px 14px;border-bottom:1px solid #edf1ef;vertical-align:middle}
table tr:last-child td{border-bottom:0}table tbody tr:hover{background:#fbfdfc}
/* badges and notices */
[class*="badge"],.status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:var(--tg-green-3);color:var(--tg-green);font-weight:800;font-size:12px}
[class*="badge"].warn{background:#fff4e5;color:var(--tg-warning)}[class*="badge"].danger{background:#fff0ee;color:var(--tg-danger)}
.notice,.t40-alert,.t42-alert{border-radius:12px!important;border-inline-start-width:4px!important;padding:14px 16px!important}
/* tabs */
[class*="tabs"]{display:flex;gap:8px;flex-wrap:wrap;padding:8px;background:#edf4f1;border-radius:14px}
[class*="tabs"] a{padding:9px 13px;border-radius:10px;color:#35584c;text-decoration:none;font-weight:700}
[class*="tabs"] a.active,[class*="tabs"] .active{background:#fff;color:var(--tg-green);box-shadow:0 3px 12px rgba(15,90,67,.09)}
/* auth pages */
.t45-auth-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.72fr);gap:24px;align-items:stretch}
.t45-auth-card,.t45-auth-aside{border-radius:22px!important}.t45-auth-aside{background:linear-gradient(145deg,#0c4d39,#177457);color:#fff}
.t45-auth-aside h2,.t45-auth-aside h3,.t45-auth-aside p{color:#fff}
/* media */
img{max-width:100%;height:auto}.t43-gallery img,.t44-sortable img{border-radius:12px;object-fit:cover}
/* footer actions and toolbars */
[class*="actions"]{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.tager-sticky-actions{position:sticky;bottom:12px;z-index:12;background:rgba(255,255,255,.94);backdrop-filter:blur(10px);padding:10px;border:1px solid var(--tg-line);border-radius:14px;box-shadow:var(--tg-shadow)}
/* mobile */
@media(max-width:820px){
  .t45-auth-layout{grid-template-columns:1fr}.t45-auth-aside{order:2}
  [class*="-card"],[class*="-panel"],.t40-card,.t41-card,.t42-card,.t44-card,.t45-card{padding:16px;border-radius:14px}
  [class*="-grid"],[class*="-cards"],[class*="-stats"],.t40-grid,.t41-grid,.t42-cards,.t42-grid3,.t44-grid,.t45-grid{grid-template-columns:1fr!important}
  a[class*="btn"],button[class*="btn"],input[type="submit"],.button{width:100%}
  [class*="actions"]{display:grid;grid-template-columns:1fr 1fr;width:100%}
  [class*="actions"]>*{width:100%}
  table{font-size:13px}table th,table td{padding:10px 11px}
}
@media(max-width:520px){[class*="actions"]{grid-template-columns:1fr}.site-main,.entry-content{padding-inline:0}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
CSS);

    wp_register_script('tager-v51-ui', '', [], '51.0.0', true);
    wp_enqueue_script('tager-v51-ui');
    wp_add_inline_script('tager-v51-ui', <<<'JS'
(function(){
  function ready(fn){document.readyState!=='loading'?fn():document.addEventListener('DOMContentLoaded',fn)}
  ready(function(){
    document.querySelectorAll('form').forEach(function(form){
      form.addEventListener('submit',function(){
        if(!form.checkValidity()) return;
        var btn=form.querySelector('button[type="submit"],input[type="submit"]');
        if(!btn || btn.dataset.tgBusy==='1') return;
        btn.dataset.tgBusy='1'; btn.classList.add('is-loading');
        if(btn.tagName==='INPUT'){btn.dataset.original=btn.value;btn.value='جاري الحفظ...';}
        else {btn.dataset.original=btn.innerHTML;btn.innerHTML='<span aria-hidden="true">⏳</span> جاري الحفظ...';}
        setTimeout(function(){
          if(btn.dataset.tgBusy==='1'){btn.classList.remove('is-loading');btn.dataset.tgBusy='0';
            if(btn.tagName==='INPUT')btn.value=btn.dataset.original||'حفظ';else btn.innerHTML=btn.dataset.original||'حفظ';}
        },12000);
      });
    });
    document.querySelectorAll('a,button').forEach(function(el){
      if((el.getAttribute('href')||'').trim()==='#' && !el.dataset.allowHash){el.setAttribute('aria-disabled','true');el.addEventListener('click',function(e){e.preventDefault();});}
    });
    document.querySelectorAll('table').forEach(function(t){if(!t.parentElement.classList.contains('tager-table-scroll')){var w=document.createElement('div');w.className='tager-table-scroll';t.parentNode.insertBefore(w,t);w.appendChild(t);}});
  });
})();
JS);
}, 99);

add_action('admin_enqueue_scripts', function(){
    wp_register_style('tager-v51-admin', false, [], '51.0.0');
    wp_enqueue_style('tager-v51-admin');
    wp_add_inline_style('tager-v51-admin', '.wrap .button-primary{background:#0f5a43;border-color:#0f5a43;border-radius:8px}.wrap .button{border-radius:8px}.wrap .card{border-radius:14px;border-color:#dfe7e3;box-shadow:0 8px 24px rgba(19,63,49,.07)}');
});
