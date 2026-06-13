<?php
/**
 * Plugin Name: Tager V52 Button & Data Integrity Audit
 * Description: Removes duplicate actions safely, validates action targets, checks forms and page content, and adds a full admin QA screen.
 * Version: 52.0.0
 */
if (!defined('ABSPATH')) exit;

function tager_v52_normalize_text($text){
    $text = wp_strip_all_tags((string)$text);
    $text = preg_replace('/\s+/u',' ',trim($text));
    return mb_strtolower($text,'UTF-8');
}

add_action('wp_enqueue_scripts', function(){
    wp_register_style('tager-v52', false, [], '52.0.0');
    wp_enqueue_style('tager-v52');
    wp_add_inline_style('tager-v52', <<<'CSS'
.tg-v52-disabled{opacity:.48!important;cursor:not-allowed!important;pointer-events:none!important}
.tg-v52-inline-error{margin-top:8px;padding:9px 11px;border-radius:10px;background:#fff1f0;color:#9f1c16;font-size:13px;font-weight:700}
.tg-v52-valid{border-color:#2f855a!important}.tg-v52-invalid{border-color:#c53030!important;box-shadow:0 0 0 3px rgba(197,48,48,.10)!important}
.tg-v52-hidden-duplicate{display:none!important}
CSS);

    wp_register_script('tager-v52', '', [], '52.0.0', true);
    wp_enqueue_script('tager-v52');
    wp_add_inline_script('tager-v52', <<<'JS'
(function(){
  function ready(fn){document.readyState!=='loading'?fn():document.addEventListener('DOMContentLoaded',fn)}
  function txt(el){return (el.textContent||el.value||'').replace(/\s+/g,' ').trim().toLowerCase()}
  function key(el){
    var href=(el.getAttribute('href')||'').trim();
    var type=(el.getAttribute('type')||'').toLowerCase();
    var name=(el.getAttribute('name')||'').trim();
    var value=(el.getAttribute('value')||'').trim();
    var target=(el.getAttribute('data-target')||el.getAttribute('aria-controls')||'').trim();
    return [el.tagName,href,type,name,value,target,txt(el)].join('|');
  }
  function isRepeatSafe(el){
    return !!el.closest('.products,.product-grid,[class*="product-card"],.pagination,.nav-links,[role="tablist"],.quantity,.woocommerce-pagination');
  }
  function error(el,msg){
    var host=el.closest('.form-row,.field,.input-group,label')||el.parentElement;
    if(!host) return;
    var old=host.querySelector(':scope > .tg-v52-inline-error'); if(old) old.remove();
    var d=document.createElement('div'); d.className='tg-v52-inline-error'; d.textContent=msg; host.appendChild(d);
  }
  ready(function(){
    // Hide only exact duplicates inside the same action container. Product cards and repeated lists are excluded.
    document.querySelectorAll('[class*="actions"],.tager-sticky-actions,.form-actions,.button-group,.wp-block-buttons').forEach(function(parent){
      var seen=new Set();
      parent.querySelectorAll(':scope > a,:scope > button,:scope > input[type="submit"]').forEach(function(el){
        if(isRepeatSafe(el)) return;
        var k=key(el); if(seen.has(k)){el.classList.add('tg-v52-hidden-duplicate');el.setAttribute('aria-hidden','true');}
        else seen.add(k);
      });
    });

    document.querySelectorAll('a,button').forEach(function(el){
      var href=(el.getAttribute('href')||'').trim();
      var target=(el.getAttribute('data-target')||el.getAttribute('aria-controls')||'').trim();
      if(el.tagName==='A' && (!href || href==='#' || /^javascript:\s*void/i.test(href)) && !el.dataset.allowHash){
        el.classList.add('tg-v52-disabled');el.setAttribute('aria-disabled','true');el.addEventListener('click',function(e){e.preventDefault();});
      }
      if(target && !document.getElementById(target) && !document.querySelector(target.charAt(0)==='#'?target:'#'+CSS.escape(target))){
        el.classList.add('tg-v52-disabled');el.setAttribute('aria-disabled','true');el.title='الجزء المرتبط بهذا الزر غير موجود';
      }
    });

    document.querySelectorAll('form').forEach(function(form){
      var submits=form.querySelectorAll('button[type="submit"],input[type="submit"]');
      // Remove duplicate submit controls only when signature is identical.
      var seen=new Set();
      submits.forEach(function(btn){var k=key(btn);if(seen.has(k)){btn.classList.add('tg-v52-hidden-duplicate');btn.setAttribute('aria-hidden','true');}else seen.add(k)});
      form.addEventListener('submit',function(e){
        var invalid=form.querySelector(':invalid');
        if(invalid){e.preventDefault();invalid.classList.add('tg-v52-invalid');invalid.focus();error(invalid,'يرجى استكمال هذا الحقل بشكل صحيح.');return false;}
      },true);
      form.querySelectorAll('input,select,textarea').forEach(function(f){
        f.addEventListener('blur',function(){
          f.classList.toggle('tg-v52-invalid',!f.checkValidity());
          f.classList.toggle('tg-v52-valid',f.checkValidity() && !!String(f.value||'').trim());
        });
      });
    });
  });
})();
JS);
}, 120);

function tager_v52_scan_page($post){
    $issues=[]; $content=(string)$post->post_content;
    if(trim(wp_strip_all_tags(strip_shortcodes($content)))==='' && !preg_match('/\[[^\]]+\]/',$content)) $issues[]='الصفحة بلا محتوى';
    if(preg_match_all('/\[([a-zA-Z0-9_-]+)/',$content,$m)){
        foreach(array_unique($m[1]) as $sc){ if(!shortcode_exists($sc)) $issues[]='Shortcode غير مسجل: '.$sc; }
    }
    if(class_exists('DOMDocument') && trim($content)!==''){
        $dom=new DOMDocument(); libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="tgroot">'.$content.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        libxml_clear_errors(); $xp=new DOMXPath($dom);
        $seen=[];
        foreach($xp->query('//*[@id="tgroot"]//a|//*[@id="tgroot"]//button|//*[@id="tgroot"]//input[@type="submit"]') as $el){
            $href=trim($el->getAttribute('href')); $text=tager_v52_normalize_text($el->textContent ?: $el->getAttribute('value'));
            if($el->nodeName==='a' && ($href===''||$href==='#'||stripos($href,'javascript:void')===0)) $issues[]='زر/رابط بلا وجهة: '.($text?:'بدون عنوان');
            $sig=$el->nodeName.'|'.$href.'|'.$text.'|'.$el->getAttribute('name').'|'.$el->getAttribute('value');
            if(isset($seen[$sig])) $issues[]='زر مكرر: '.($text?:$href);
            $seen[$sig]=1;
        }
        foreach($xp->query('//*[@id="tgroot"]//form') as $form){
            $has=$xp->query('.//button[@type="submit"]|.//input[@type="submit"]',$form)->length;
            if(!$has) $issues[]='نموذج بدون زر حفظ/إرسال';
        }
    }
    return array_values(array_unique($issues));
}

add_action('admin_menu', function(){
    add_menu_page('Tager V52 QA','Tager V52 QA','manage_options','tager-v52-qa','tager_v52_admin_page','dashicons-yes-alt',3);
});

function tager_v52_admin_page(){
    if(!current_user_can('manage_options')) return;
    $pages=get_pages(['post_status'=>'publish','sort_column'=>'post_title']);
    $rows=[];$total=0;
    foreach($pages as $p){$issues=tager_v52_scan_page($p);$total+=count($issues);$rows[]=[$p,$issues];}
    ?>
    <div class="wrap"><h1>Tager V52 — فحص الأزرار والبيانات</h1>
      <p>يفحص الصفحات المنشورة بحثًا عن الأزرار المكررة، الروابط الفارغة، النماذج بدون زر إرسال، الصفحات الفارغة، والـShortcodes غير المسجلة.</p>
      <div class="notice <?php echo $total?'notice-warning':'notice-success'; ?>"><p><strong><?php echo $total?esc_html("تم العثور على {$total} ملاحظة تحتاج مراجعة"): 'لم يتم العثور على مشكلات ثابتة في محتوى الصفحات.'; ?></strong></p></div>
      <table class="widefat striped"><thead><tr><th>الصفحة</th><th>الحالة</th><th>الملاحظات</th><th>اختبار</th></tr></thead><tbody>
      <?php foreach($rows as [$p,$issues]): ?><tr><td><strong><?php echo esc_html($p->post_title); ?></strong></td><td><?php echo $issues?'⚠️ تحتاج مراجعة':'✅ سليمة'; ?></td><td><?php echo $issues?'<ul><li>'.implode('</li><li>',array_map('esc_html',$issues)).'</li></ul>':'لا توجد ملاحظات'; ?></td><td><a class="button" target="_blank" href="<?php echo esc_url(get_permalink($p)); ?>">فتح الصفحة</a> <a class="button" href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>">تعديل</a></td></tr><?php endforeach; ?>
      </tbody></table>
      <hr><h2>ما الذي يتم إصلاحه تلقائيًا في الواجهة؟</h2><ul><li>إخفاء الزر المتكرر المطابق داخل نفس مجموعة الأزرار فقط.</li><li>تعطيل الروابط الفارغة والأزرار التي تشير إلى عنصر غير موجود.</li><li>منع إرسال أي نموذج عند نقص الحقول الإلزامية.</li><li>إظهار الحقل الناقص بوضوح قبل حفظ البيانات.</li></ul>
    </div><?php
}
