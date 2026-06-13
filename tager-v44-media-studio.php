<?php
/**
 * Plugin Name: Tager V44 Media Studio
 * Description: Advanced media management for products, vendors and customers: image previews, galleries, ordering, deletion, alt text, lightbox and media QA.
 * Version: 44.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V44_Media_Studio {
    const NONCE = 'tager_v44_media_studio';

    public static function boot(){
        add_action('init',[__CLASS__,'pages'],180);
        add_action('admin_menu',[__CLASS__,'admin_menu'],95);
        add_action('admin_post_tager_v44_save_profile',[__CLASS__,'save_profile']);
        add_action('admin_post_tager_v44_manage_product_media',[__CLASS__,'manage_product_media']);
        add_action('admin_post_tager_v44_update_attachment',[__CLASS__,'update_attachment']);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],120);
        add_filter('big_image_size_threshold',[__CLASS__,'big_image_threshold']);
        add_filter('jpeg_quality',[__CLASS__,'jpeg_quality']);
        add_filter('wp_editor_set_quality',[__CLASS__,'editor_quality'],10,2);
        add_action('add_attachment',[__CLASS__,'set_default_alt']);
        add_shortcode('tager_v44_profile_studio',[__CLASS__,'profile_studio']);
        add_shortcode('tager_v44_product_media_studio',[__CLASS__,'product_media_studio']);
        add_filter('do_shortcode_tag',[__CLASS__,'append_studio_links'],40,4);
        add_action('wp_footer',[__CLASS__,'lightbox_markup'],120);
    }

    private static function page_url($slug){ $p=get_page_by_path($slug); return $p?get_permalink($p):home_url('/'.$slug.'/'); }
    private static function roles($u=null){ $u=$u?:wp_get_current_user(); return (array)$u->roles; }
    private static function is_vendor($u=null){ return (bool)array_intersect(self::roles($u),['tager_vendor','tager_vendor_pending','vendor','wcfm_vendor','seller']); }
    private static function can_manage_product($product_id){
        $p=get_post($product_id); if(!$p || $p->post_type!=='tager_product') return false;
        return current_user_can('manage_options') || (is_user_logged_in() && (int)$p->post_author===get_current_user_id());
    }

    public static function pages(){
        $pages=[
            'profile-media-studio'=>['استوديو صور الحساب','[tager_v44_profile_studio]'],
            'product-media-studio'=>['استوديو صور المنتجات','[tager_v44_product_media_studio]'],
        ];
        foreach($pages as $slug=>$data){
            $p=get_page_by_path($slug);
            if(!$p) wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$data[0],'post_name'=>$slug,'post_content'=>$data[1]]);
            elseif(strpos((string)$p->post_content,'tager_v44_')===false) wp_update_post(['ID'=>$p->ID,'post_content'=>$data[1]]);
        }
    }

    public static function big_image_threshold(){ return 2560; }
    public static function jpeg_quality(){ return 84; }
    public static function editor_quality($quality,$mime){ return in_array($mime,['image/jpeg','image/webp'],true)?84:$quality; }

    public static function set_default_alt($attachment_id){
        if(!wp_attachment_is_image($attachment_id)) return;
        $alt=get_post_meta($attachment_id,'_wp_attachment_image_alt',true);
        if($alt===''){
            $title=get_the_title($attachment_id);
            if($title) update_post_meta($attachment_id,'_wp_attachment_image_alt',sanitize_text_field($title));
        }
    }

    private static function upload_image($field,$parent=0){
        if(empty($_FILES[$field]['name'])) return 0;
        $f=$_FILES[$field];
        if(!empty($f['error'])) return new WP_Error('upload','تعذر رفع الصورة.');
        if((int)$f['size']>5*1024*1024) return new WP_Error('size','الحد الأقصى للصورة 5 ميجابايت.');
        $check=wp_check_filetype_and_ext($f['tmp_name'],$f['name']);
        if(!in_array($check['type'],['image/jpeg','image/png','image/webp'],true)) return new WP_Error('type','الصيغ المقبولة JPG وPNG وWebP فقط.');
        $size=@getimagesize($f['tmp_name']);
        if(!$size || $size[0]<300 || $size[1]<300) return new WP_Error('dimensions','أقل مقاس مقبول للصورة 300×300 بكسل.');
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        return media_handle_upload($field,$parent);
    }

    private static function upload_many($field,$parent=0,$limit=10){
        if(empty($_FILES[$field]['name'])||!is_array($_FILES[$field]['name'])) return [];
        $ids=[]; $count=min(count($_FILES[$field]['name']),$limit);
        for($i=0;$i<$count;$i++){
            if(empty($_FILES[$field]['name'][$i])) continue;
            $_FILES['t44_single']=[
                'name'=>$_FILES[$field]['name'][$i],
                'type'=>$_FILES[$field]['type'][$i]??'',
                'tmp_name'=>$_FILES[$field]['tmp_name'][$i]??'',
                'error'=>$_FILES[$field]['error'][$i]??UPLOAD_ERR_NO_FILE,
                'size'=>$_FILES[$field]['size'][$i]??0,
            ];
            $id=self::upload_image('t44_single',$parent);
            unset($_FILES['t44_single']);
            if(!is_wp_error($id)&&$id) $ids[]=(int)$id;
        }
        return $ids;
    }

    private static function img($id,$size='medium',$class=''){
        if(!$id) return '<div class="t44-empty">لا توجد صورة</div>';
        return wp_get_attachment_image((int)$id,$size,false,['class'=>$class,'data-t44-lightbox'=>wp_get_attachment_image_url((int)$id,'full')]);
    }

    public static function profile_studio(){
        if(!is_user_logged_in()) return '<section class="t44-card"><h2>يلزم تسجيل الدخول</h2><a class="t44-btn" href="'.esc_url(self::page_url('login')).'">تسجيل الدخول</a></section>';
        $u=wp_get_current_user(); $vendor=self::is_vendor($u);
        $avatar=(int)get_user_meta($u->ID,'tager_profile_image_id',true);
        $logo=(int)get_user_meta($u->ID,'tager_vendor_logo_id',true);
        $cover=(int)get_user_meta($u->ID,'tager_vendor_cover_id',true);
        $gallery=array_values(array_filter(array_map('absint',(array)get_user_meta($u->ID,'tager_vendor_gallery_ids',true))));
        $score=0; $total=$vendor?4:1;
        foreach([$avatar,$vendor?$logo:1,$vendor?$cover:1,$vendor?count($gallery):1] as $v) if($v) $score++;
        $percent=(int)round(($score/$total)*100);
        ob_start(); ?>
        <section class="t44-wrap">
          <header class="t44-hero"><div><span>Media Studio</span><h1>استوديو صور <?php echo $vendor?'المورد والمتجر':'الحساب'; ?></h1><p>إدارة الهوية البصرية والصور من مكان واحد، مع معاينة فورية وجودة مناسبة للموقع.</p></div><div class="t44-score"><b><?php echo $percent; ?>%</b><small>اكتمال الصور</small></div></header>
          <?php if(isset($_GET['media_saved'])) echo '<div class="t44-success">تم حفظ الصور بنجاح.</div>'; ?>
          <form class="t44-card t44-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tager_v44_save_profile"><?php wp_nonce_field(self::NONCE); ?>
            <div class="t44-grid">
              <label class="t44-upload"><b>الصورة الشخصية</b><?php echo self::img($avatar,'medium','t44-avatar'); ?><input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp"><small>مربع 600×600 أو أكبر</small><label class="t44-check"><input type="checkbox" name="remove_avatar" value="1"> حذف الصورة الحالية</label></label>
              <?php if($vendor): ?>
              <label class="t44-upload"><b>شعار المتجر</b><?php echo self::img($logo,'medium','t44-logo'); ?><input type="file" name="vendor_logo" accept="image/jpeg,image/png,image/webp"><small>يفضل PNG أو WebP بخلفية نظيفة</small><label class="t44-check"><input type="checkbox" name="remove_logo" value="1"> حذف الشعار الحالي</label></label>
              <label class="t44-upload t44-wide"><b>غلاف المتجر</b><?php echo self::img($cover,'large','t44-cover'); ?><input type="file" name="vendor_cover" accept="image/jpeg,image/png,image/webp"><small>المقاس المقترح 1600×500</small><label class="t44-check"><input type="checkbox" name="remove_cover" value="1"> حذف الغلاف الحالي</label></label>
              <label class="t44-upload t44-wide"><b>إضافة صور لمعرض المتجر</b><input type="file" name="vendor_gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>حتى 12 صورة. يمكن ترتيبها وحذفها بعد الرفع.</small></label>
              <?php endif; ?>
            </div>
            <?php if($vendor&&$gallery): ?><h3>ترتيب معرض المتجر</h3><div class="t44-sortable" data-sortable><?php foreach($gallery as $id): ?><article data-id="<?php echo $id; ?>"><?php echo self::img($id); ?><label><input type="checkbox" name="remove_gallery[]" value="<?php echo $id; ?>"> حذف</label><input type="hidden" name="gallery_order[]" value="<?php echo $id; ?>"></article><?php endforeach; ?></div><?php endif; ?>
            <button class="t44-btn" type="submit">حفظ التغييرات</button>
          </form>
        </section><?php return ob_get_clean();
    }

    public static function save_profile(){
        if(!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.'); check_admin_referer(self::NONCE);
        $uid=get_current_user_id(); $vendor=self::is_vendor();
        $map=['profile_image'=>'tager_profile_image_id'];
        if($vendor){$map['vendor_logo']='tager_vendor_logo_id';$map['vendor_cover']='tager_vendor_cover_id';}
        foreach($map as $field=>$meta){
            if(!empty($_FILES[$field]['name'])){
                $id=self::upload_image($field); if(is_wp_error($id)) wp_die(esc_html($id->get_error_message()));
                update_user_meta($uid,$meta,(int)$id);
            }
        }
        if(!empty($_POST['remove_avatar'])) delete_user_meta($uid,'tager_profile_image_id');
        if($vendor){
            if(!empty($_POST['remove_logo'])) delete_user_meta($uid,'tager_vendor_logo_id');
            if(!empty($_POST['remove_cover'])) delete_user_meta($uid,'tager_vendor_cover_id');
            $old=array_values(array_filter(array_map('absint',(array)get_user_meta($uid,'tager_vendor_gallery_ids',true))));
            $order=array_values(array_filter(array_map('absint',(array)($_POST['gallery_order']??$old))));
            $remove=array_values(array_filter(array_map('absint',(array)($_POST['remove_gallery']??[]))));
            $order=array_values(array_diff($order,$remove));
            if(!empty($_FILES['vendor_gallery']['name'][0])) $order=array_merge($order,self::upload_many('vendor_gallery',0,max(0,12-count($order))));
            update_user_meta($uid,'tager_vendor_gallery_ids',array_slice(array_values(array_unique($order)),0,12));
        }
        wp_safe_redirect(add_query_arg('media_saved',1,self::page_url('profile-media-studio'))); exit;
    }

    public static function product_media_studio(){
        if(!is_user_logged_in()||!self::is_vendor()) return '<section class="t44-card"><h2>هذه الصفحة للموردين فقط.</h2></section>';
        $uid=get_current_user_id();
        $products=get_posts(['post_type'=>'tager_product','post_status'=>['publish','pending','draft'],'posts_per_page'=>200,'author'=>$uid,'orderby'=>'date','order'=>'DESC']);
        $selected=absint($_GET['product_id']??($products[0]->ID??0));
        if($selected&&!self::can_manage_product($selected)) $selected=0;
        ob_start(); ?>
        <section class="t44-wrap"><header class="t44-hero"><div><span>Product Media</span><h1>استوديو صور المنتجات</h1><p>اختر منتجًا لإدارة صورته الرئيسية ومعرضه والنص البديل للصور.</p></div><a class="t44-btn ghost" href="<?php echo esc_url(self::page_url('vendor-dashboard')); ?>?tab=add-product">إضافة منتج جديد</a></header>
        <div class="t44-card"><form method="get"><label><b>اختر المنتج</b><select name="product_id" onchange="this.form.submit()"><option value="">اختر</option><?php foreach($products as $p)echo '<option value="'.$p->ID.'" '.selected($selected,$p->ID,false).'>'.esc_html($p->post_title).' — '.esc_html($p->post_status).'</option>'; ?></select></label></form></div>
        <?php if($selected): $featured=(int)get_post_thumbnail_id($selected); $gallery=array_values(array_filter(array_map('absint',(array)get_post_meta($selected,'tager_product_gallery_ids',true)))); ?>
        <form class="t44-card t44-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v44_manage_product_media"><input type="hidden" name="product_id" value="<?php echo $selected; ?>"><?php wp_nonce_field(self::NONCE); ?>
          <div class="t44-grid"><label class="t44-upload"><b>الصورة الرئيسية</b><?php echo self::img($featured,'large'); ?><input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><small>صورة واضحة بخلفية نظيفة</small></label><label class="t44-upload"><b>إضافة صور للمعرض</b><input type="file" name="product_gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>حتى 12 صورة للمنتج</small></label></div>
          <?php if($gallery): ?><h3>معرض المنتج — اسحب لترتيب الصور</h3><div class="t44-sortable" data-sortable><?php foreach($gallery as $id): ?><article data-id="<?php echo $id; ?>"><?php echo self::img($id); ?><label><input type="radio" name="make_featured" value="<?php echo $id; ?>"> اجعلها رئيسية</label><label><input type="checkbox" name="remove_gallery[]" value="<?php echo $id; ?>"> حذف من المعرض</label><input type="hidden" name="gallery_order[]" value="<?php echo $id; ?>"></article><?php endforeach; ?></div><?php endif; ?>
          <button class="t44-btn" type="submit">حفظ صور المنتج</button>
        </form><?php endif; ?></section><?php return ob_get_clean();
    }

    public static function manage_product_media(){
        if(!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.'); check_admin_referer(self::NONCE);
        $pid=absint($_POST['product_id']??0); if(!self::can_manage_product($pid)) wp_die('غير مصرح.');
        if(!empty($_FILES['featured_image']['name'])){
            $id=self::upload_image('featured_image',$pid); if(is_wp_error($id))wp_die(esc_html($id->get_error_message())); set_post_thumbnail($pid,$id);
        }
        $old=array_values(array_filter(array_map('absint',(array)get_post_meta($pid,'tager_product_gallery_ids',true))));
        $order=array_values(array_filter(array_map('absint',(array)($_POST['gallery_order']??$old))));
        $remove=array_values(array_filter(array_map('absint',(array)($_POST['remove_gallery']??[]))));
        $order=array_values(array_diff($order,$remove));
        if(!empty($_FILES['product_gallery']['name'][0])) $order=array_merge($order,self::upload_many('product_gallery',$pid,max(0,12-count($order))));
        update_post_meta($pid,'tager_product_gallery_ids',array_slice(array_values(array_unique($order)),0,12));
        $new_featured=absint($_POST['make_featured']??0); if($new_featured&&in_array($new_featured,$order,true)) set_post_thumbnail($pid,$new_featured);
        update_post_meta($pid,'tager_media_updated_at',current_time('mysql'));
        wp_update_post(['ID'=>$pid,'post_status'=>'pending']);
        wp_safe_redirect(add_query_arg(['product_id'=>$pid,'saved'=>1],self::page_url('product-media-studio'))); exit;
    }

    public static function update_attachment(){
        if(!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.'); check_admin_referer(self::NONCE);
        $id=absint($_POST['attachment_id']??0); $a=get_post($id);
        if(!$a||$a->post_type!=='attachment'||(!current_user_can('manage_options')&&(int)$a->post_author!==get_current_user_id())) wp_die('غير مصرح.');
        update_post_meta($id,'_wp_attachment_image_alt',sanitize_text_field($_POST['alt']??''));
        wp_update_post(['ID'=>$id,'post_excerpt'=>sanitize_textarea_field($_POST['caption']??'')]);
        wp_safe_redirect(wp_get_referer()?:self::page_url('media-library')); exit;
    }

    public static function append_studio_links($output,$tag,$attr,$m){
        if(!is_user_logged_in()) return $output;
        if(in_array($tag,['tager_v40_customer_workspace','tager_v40_vendor_workspace'],true)){
            $links='<section class="t44-quick"><a href="'.esc_url(self::page_url('profile-media-studio')).'">إدارة صورة الحساب</a>';
            if(self::is_vendor()) $links.='<a href="'.esc_url(self::page_url('product-media-studio')).'">استوديو صور المنتجات</a>';
            $links.='</section>';
            return $output.$links;
        }
        return $output;
    }

    public static function admin_menu(){ add_submenu_page('tager-v40','فحص الوسائط V44','فحص الوسائط V44','upload_files','tager-v44-media-qa',[__CLASS__,'admin_screen']); }
    public static function admin_screen(){
        if(!current_user_can('upload_files')) wp_die('غير مصرح');
        $products=get_posts(['post_type'=>'tager_product','post_status'=>'any','posts_per_page'=>-1]);
        $missing_featured=[];$missing_alt=[];$small=[];
        foreach($products as $p){
            if(!has_post_thumbnail($p->ID))$missing_featured[]=$p;
            $ids=array_merge([(int)get_post_thumbnail_id($p->ID)],(array)get_post_meta($p->ID,'tager_product_gallery_ids',true));
            foreach(array_filter(array_map('absint',$ids)) as $id){
                if(get_post_meta($id,'_wp_attachment_image_alt',true)==='')$missing_alt[$id]=$p->ID;
                $meta=wp_get_attachment_metadata($id); if(!empty($meta['width'])&&($meta['width']<600||$meta['height']<600))$small[$id]=$p->ID;
            }
        }
        echo '<div class="wrap"><h1>فحص الوسائط V44</h1><p>مراجعة جاهزية صور المنتجات والموردين قبل إطلاق الموقع.</p><div style="display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:16px;max-width:950px"><div class="card"><h2>'.count($missing_featured).'</h2><p>منتجات بدون صورة رئيسية</p></div><div class="card"><h2>'.count($missing_alt).'</h2><p>صور بدون نص بديل</p></div><div class="card"><h2>'.count($small).'</h2><p>صور أقل من 600×600</p></div></div><h2>المنتجات التي تحتاج مراجعة</h2><table class="widefat striped"><thead><tr><th>المنتج</th><th>صورة رئيسية</th><th>المعرض</th><th>فتح</th></tr></thead><tbody>';
        foreach($products as $p){$g=(array)get_post_meta($p->ID,'tager_product_gallery_ids',true);echo '<tr><td>'.esc_html($p->post_title).'</td><td>'.(has_post_thumbnail($p->ID)?'✅':'❌').'</td><td>'.count($g).'</td><td><a href="'.esc_url(get_edit_post_link($p->ID)).'">تعديل</a></td></tr>';}
        echo '</tbody></table></div>';
    }

    public static function lightbox_markup(){ echo '<div class="t44-lightbox" id="t44-lightbox" hidden><button type="button" aria-label="إغلاق">×</button><img alt="معاينة الصورة"></div>'; }

    public static function assets(){
        wp_register_style('tager-v44',false); wp_enqueue_style('tager-v44');
        wp_add_inline_style('tager-v44','.t44-wrap{max-width:1280px;margin:28px auto;padding:0 16px}.t44-hero{display:flex;justify-content:space-between;align-items:center;gap:24px;background:linear-gradient(135deg,#073e31,#116a52);color:#fff;border-radius:26px;padding:32px;margin-bottom:20px}.t44-hero h1{color:#fff;margin:6px 0}.t44-hero span{color:#f3cb62;font-weight:800}.t44-score{width:110px;height:110px;border-radius:50%;background:#fff;color:#0b4d3b;display:grid;place-items:center;text-align:center}.t44-score b{font-size:28px}.t44-score small{display:block}.t44-card{background:#fff;border:1px solid #dfeae6;border-radius:22px;padding:24px;margin:18px 0;box-shadow:0 12px 34px rgba(7,62,49,.07)}.t44-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.t44-wide{grid-column:1/-1}.t44-upload{display:grid;gap:10px;border:1px dashed #98b9ad;border-radius:16px;padding:16px}.t44-upload input,.t44-card select{width:100%;padding:12px;border:1px solid #ccdcd6;border-radius:10px}.t44-upload img,.t44-empty{width:100%;height:220px;object-fit:contain;background:#f4f8f6;border-radius:13px}.t44-upload .t44-avatar,.t44-upload .t44-logo{width:150px;height:150px}.t44-upload .t44-avatar{border-radius:50%;object-fit:cover}.t44-upload .t44-cover{height:280px;object-fit:cover}.t44-btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;border:0;border-radius:12px;background:#d8ad37;color:#123d31!important;font-weight:800;text-decoration:none;cursor:pointer}.t44-btn.ghost{background:#fff}.t44-success{padding:14px 18px;background:#e7f8ef;border:1px solid #9fd9b9;border-radius:12px;color:#17663d}.t44-sortable{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin:14px 0 22px}.t44-sortable article{background:#f7faf8;border:1px solid #dce9e4;border-radius:15px;padding:10px;cursor:grab}.t44-sortable article.dragging{opacity:.45}.t44-sortable img{width:100%;height:150px;object-fit:cover;border-radius:10px}.t44-sortable label,.t44-check{display:block;margin-top:8px;font-size:13px}.t44-quick{display:flex;gap:12px;flex-wrap:wrap;max-width:1280px;margin:18px auto}.t44-quick a{padding:11px 15px;border-radius:10px;background:#eef6f2;color:#0b4d3b;font-weight:800;text-decoration:none}.t44-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:999999;display:grid;place-items:center;padding:30px}.t44-lightbox[hidden]{display:none}.t44-lightbox img{max-width:95vw;max-height:88vh;object-fit:contain}.t44-lightbox button{position:absolute;top:18px;right:22px;width:46px;height:46px;border:0;border-radius:50%;font-size:30px;cursor:pointer}@media(max-width:760px){.t44-grid{grid-template-columns:1fr}.t44-wide{grid-column:auto}.t44-hero{display:block}.t44-score{margin-top:18px}.t44-sortable{grid-template-columns:repeat(2,minmax(0,1fr))}}');
        wp_register_script('tager-v44',false,[],false,true); wp_enqueue_script('tager-v44');
        wp_add_inline_script('tager-v44',"document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-sortable]').forEach(box=>{let drag=null;box.querySelectorAll('article').forEach(item=>{item.draggable=true;item.addEventListener('dragstart',()=>{drag=item;item.classList.add('dragging')});item.addEventListener('dragend',()=>{item.classList.remove('dragging');drag=null});item.addEventListener('dragover',e=>{e.preventDefault();if(drag&&drag!==item){const r=item.getBoundingClientRect();box.insertBefore(drag,e.clientY<r.top+r.height/2?item:item.nextSibling)}})});});const lb=document.getElementById('t44-lightbox');document.querySelectorAll('[data-t44-lightbox]').forEach(img=>img.addEventListener('click',()=>{if(!lb)return;lb.querySelector('img').src=img.dataset.t44Lightbox;lb.hidden=false}));if(lb){lb.querySelector('button').addEventListener('click',()=>lb.hidden=true);lb.addEventListener('click',e=>{if(e.target===lb)lb.hidden=true});}document.querySelectorAll('input[type=file]').forEach(input=>input.addEventListener('change',()=>{const f=input.files&&input.files[0];if(!f)return;const img=input.parentElement.querySelector('img');if(img)img.src=URL.createObjectURL(f);}));});");
    }
}
Tager_V44_Media_Studio::boot();
