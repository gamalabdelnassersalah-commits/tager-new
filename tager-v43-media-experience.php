<?php
/**
 * Plugin Name: Tager V43 Media Experience
 * Description: Product galleries, customer avatars, vendor logos/covers, media validation and connected profile media management.
 * Version: 43.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V43_Media_Experience {
    const NONCE = 'tager_v43_media';
    const MAX_IMAGE_BYTES = 5242880;

    public static function boot(){
        add_action('init',[__CLASS__,'register_pages'],140);
        add_action('admin_menu',[__CLASS__,'admin_menu'],80);
        add_action('admin_post_tager_v43_profile_media',[__CLASS__,'save_profile_media']);
        add_action('admin_post_tager_v43_delete_media',[__CLASS__,'delete_profile_media']);
        add_action('admin_post_tager_v43_product_media',[__CLASS__,'save_product_media']);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets'],99);
        add_filter('get_avatar_url',[__CLASS__,'avatar_url'],20,3);
        add_filter('do_shortcode_tag',[__CLASS__,'append_workspace_media'],30,4);
        add_shortcode('tager_v43_media_library',[__CLASS__,'media_library']);
        add_shortcode('tager_v43_vendor_store_header',[__CLASS__,'vendor_store_header']);
        add_filter('the_content',[__CLASS__,'enhance_product_content'],25);
        add_action('wp_footer',[__CLASS__,'product_form_enhancer'],99);
        add_action('admin_init',[__CLASS__,'replace_product_handler'],999);
    }

    private static function page_url($slug){ $p=get_page_by_path($slug); return $p?get_permalink($p):home_url('/'.$slug.'/'); }
    private static function roles($u=null){ $u=$u?:wp_get_current_user(); return (array)$u->roles; }
    private static function is_vendor($u=null){ return (bool)array_intersect(self::roles($u),['tager_vendor','tager_vendor_pending','vendor','wcfm_vendor','seller']); }
    private static function is_admin_team($u=null){ $u=$u?:wp_get_current_user(); return user_can($u,'manage_options') || (bool)array_intersect(self::roles($u),['tager_platform_manager','tager_operations_manager','tager_vendor_manager','tager_catalog_manager']); }

    public static function register_pages(){
        $pages=['media-library'=>['مكتبة الصور','[tager_v43_media_library]']];
        foreach($pages as $slug=>$data){
            $p=get_page_by_path($slug);
            if(!$p) wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$data[0],'post_name'=>$slug,'post_content'=>$data[1]]);
            elseif(strpos((string)$p->post_content,'tager_v43_')===false) wp_update_post(['ID'=>$p->ID,'post_content'=>$data[1]]);
        }
    }

    private static function allowed_image($file){
        if(empty($file['name']) || !empty($file['error'])) return new WP_Error('upload','لم يتم اختيار صورة صالحة.');
        if((int)$file['size']>self::MAX_IMAGE_BYTES) return new WP_Error('size','حجم الصورة يجب ألا يتجاوز 5 ميجابايت.');
        $check=wp_check_filetype_and_ext($file['tmp_name'],$file['name']);
        if(!in_array($check['type'],['image/jpeg','image/png','image/webp'],true)) return new WP_Error('type','المسموح JPG وPNG وWebP فقط.');
        return true;
    }

    private static function upload($field,$parent=0){
        if(empty($_FILES[$field]['name'])) return 0;
        $valid=self::allowed_image($_FILES[$field]); if(is_wp_error($valid)) return $valid;
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        return media_handle_upload($field,$parent);
    }

    private static function upload_many($field,$parent=0,$limit=8){
        if(empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) return [];
        $ids=[]; $count=min(count($_FILES[$field]['name']),$limit);
        for($i=0;$i<$count;$i++){
            if(empty($_FILES[$field]['name'][$i])) continue;
            $single=['name'=>$_FILES[$field]['name'][$i],'type'=>$_FILES[$field]['type'][$i]??'','tmp_name'=>$_FILES[$field]['tmp_name'][$i]??'','error'=>$_FILES[$field]['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$_FILES[$field]['size'][$i]??0];
            $valid=self::allowed_image($single); if(is_wp_error($valid)) continue;
            $_FILES['tager_v43_single']=$single;
            $id=self::upload('tager_v43_single',$parent);
            unset($_FILES['tager_v43_single']);
            if(!is_wp_error($id) && $id) $ids[]=(int)$id;
        }
        return $ids;
    }

    public static function avatar_url($url,$id_or_email,$args){
        $uid=0;
        if(is_numeric($id_or_email)) $uid=(int)$id_or_email;
        elseif($id_or_email instanceof WP_User) $uid=$id_or_email->ID;
        elseif($id_or_email instanceof WP_Comment) $uid=(int)$id_or_email->user_id;
        elseif(is_string($id_or_email) && is_email($id_or_email)){ $u=get_user_by('email',$id_or_email); if($u)$uid=$u->ID; }
        $aid=$uid?(int)get_user_meta($uid,'tager_profile_image_id',true):0;
        return $aid?(wp_get_attachment_image_url($aid,'thumbnail')?:$url):$url;
    }

    public static function append_workspace_media($output,$tag,$attr,$m){
        if(!is_user_logged_in()) return $output;
        if($tag==='tager_v40_customer_workspace') return $output.self::profile_media_panel(false);
        if($tag==='tager_v40_vendor_workspace') return $output.self::profile_media_panel(true);
        return $output;
    }

    private static function image_preview($id,$class=''){
        if(!$id) return '<div class="t43-placeholder '.$class.'">لا توجد صورة</div>';
        return wp_get_attachment_image($id,'medium',false,['class'=>'t43-preview '.$class]);
    }

    private static function profile_media_panel($vendor=false){
        $u=wp_get_current_user(); if($vendor && !self::is_vendor($u)) return '';
        $avatar=(int)get_user_meta($u->ID,'tager_profile_image_id',true);
        $logo=(int)get_user_meta($u->ID,'tager_vendor_logo_id',true);
        $cover=(int)get_user_meta($u->ID,'tager_vendor_cover_id',true);
        $gallery=(array)get_user_meta($u->ID,'tager_vendor_gallery_ids',true);
        ob_start(); ?>
        <section class="t43-panel">
          <div class="t43-title"><div><span>الهوية والصور</span><h2><?php echo $vendor?'صور المتجر والمورد':'الصورة الشخصية'; ?></h2><p>ارفع صورًا واضحة بصيغة JPG أو PNG أو WebP وبحجم لا يتجاوز 5 ميجابايت للصورة.</p></div><a class="t43-btn secondary" href="<?php echo esc_url(self::page_url('media-library')); ?>">مكتبة الصور</a></div>
          <form class="t43-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tager_v43_profile_media"><?php wp_nonce_field(self::NONCE); ?>
            <div class="t43-media-grid">
              <label class="t43-upload"><b><?php echo $vendor?'صورة مسؤول المتجر':'الصورة الشخصية'; ?></b><?php echo self::image_preview($avatar,'avatar'); ?><input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp"><small>مقاس مربع مفضل 600×600</small></label>
              <?php if($vendor): ?>
              <label class="t43-upload"><b>شعار المتجر</b><?php echo self::image_preview($logo,'logo'); ?><input type="file" name="vendor_logo" accept="image/jpeg,image/png,image/webp"><small>خلفية شفافة أو بيضاء مفضلة</small></label>
              <label class="t43-upload wide"><b>غلاف صفحة المورد</b><?php echo self::image_preview($cover,'cover'); ?><input type="file" name="vendor_cover" accept="image/jpeg,image/png,image/webp"><small>مقاس مقترح 1600×500</small></label>
              <label class="t43-upload wide"><b>معرض المتجر (حتى 8 صور)</b><input type="file" name="vendor_gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>صور المخزن، المعرض، المنتجات أو فريق العمل.</small></label>
              <?php endif; ?>
            </div>
            <button class="t43-btn" type="submit">حفظ الصور</button>
          </form>
          <?php if($vendor && $gallery): ?><div class="t43-gallery"><?php foreach($gallery as $id) echo '<figure>'.wp_get_attachment_image((int)$id,'medium').'</figure>'; ?></div><?php endif; ?>
        </section><?php return ob_get_clean();
    }

    public static function save_profile_media(){
        if(!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.'); check_admin_referer(self::NONCE);
        $u=wp_get_current_user();
        foreach(['profile_image'=>'tager_profile_image_id','vendor_logo'=>'tager_vendor_logo_id','vendor_cover'=>'tager_vendor_cover_id'] as $field=>$meta){
            if($field!=='profile_image' && !self::is_vendor($u)) continue;
            if(!empty($_FILES[$field]['name'])){ $id=self::upload($field); if(is_wp_error($id)) wp_die(esc_html($id->get_error_message())); update_user_meta($u->ID,$meta,(int)$id); }
        }
        if(self::is_vendor($u) && !empty($_FILES['vendor_gallery']['name'][0])){
            $new=self::upload_many('vendor_gallery',0,8); $old=(array)get_user_meta($u->ID,'tager_vendor_gallery_ids',true); update_user_meta($u->ID,'tager_vendor_gallery_ids',array_values(array_unique(array_merge($old,$new))));
        }
        wp_safe_redirect(wp_get_referer()?:self::page_url(self::is_vendor($u)?'vendor-dashboard':'my-account')); exit;
    }

    public static function delete_profile_media(){
        if(!is_user_logged_in()) wp_die('يلزم تسجيل الدخول.'); check_admin_referer(self::NONCE);
        $u=wp_get_current_user(); $type=sanitize_key($_POST['type']??'');
        $map=['avatar'=>'tager_profile_image_id','logo'=>'tager_vendor_logo_id','cover'=>'tager_vendor_cover_id'];
        if(isset($map[$type])){ if($type!=='avatar'&&!self::is_vendor($u))wp_die('غير مصرح'); delete_user_meta($u->ID,$map[$type]); }
        wp_safe_redirect(wp_get_referer()?:home_url('/')); exit;
    }

    public static function replace_product_handler(){
        if(class_exists('Tager_V40_Complete_Workspaces')){
            remove_action('admin_post_tager_v40_product_save',['Tager_V40_Complete_Workspaces','save_product']);
            add_action('admin_post_tager_v40_product_save',[__CLASS__,'save_product_enhanced']);
        }
    }

    public static function save_product_enhanced(){
        if(!is_user_logged_in()||!self::is_vendor()) wp_die('غير مصرح');
        check_admin_referer('tager_v40_action'); $u=wp_get_current_user();
        $id=absint($_POST['product_id']??0); if($id && (int)get_post_field('post_author',$id)!==$u->ID) wp_die('غير مصرح');
        $title=sanitize_text_field($_POST['title']??''); $ret=(float)($_POST['retail_price']??0); $wh=(float)($_POST['wholesale_price']??0); $bulk=(float)($_POST['bulk_price']??0);
        $wmin=max(1,(int)($_POST['wholesale_min']??1)); $bmin=max(1,(int)($_POST['bulk_min']??1)); $stock=max(0,(int)($_POST['stock']??0));
        if(!$title||$ret<=0||$wh<=0||$bulk<=0) wp_die('أكمل بيانات المنتج والأسعار.');
        if(!($ret>=$wh && $wh>=$bulk)) wp_die('ترتيب الأسعار غير صحيح.'); if($bmin<=$wmin) wp_die('حد جملة الجملة يجب أن يكون أكبر من حد الجملة.');
        $data=['post_type'=>'tager_product','post_status'=>'pending','post_title'=>$title,'post_content'=>wp_kses_post($_POST['description']??''),'post_excerpt'=>sanitize_textarea_field($_POST['short_description']??''),'post_author'=>$u->ID]; if($id)$data['ID']=$id;
        $pid=$id?wp_update_post($data,true):wp_insert_post($data,true); if(is_wp_error($pid))wp_die($pid->get_error_message());
        $meta=['vendor_id'=>$u->ID,'sku'=>sanitize_text_field($_POST['sku']??''),'category'=>sanitize_text_field($_POST['category']??''),'brand'=>sanitize_text_field($_POST['brand']??''),'unit'=>sanitize_text_field($_POST['unit']??''),'retail_price'=>$ret,'wholesale_price'=>$wh,'bulk_price'=>$bulk,'wholesale_min'=>$wmin,'bulk_min'=>$bmin,'stock'=>$stock,'max_order'=>max(0,(int)($_POST['max_order']??0)),'lead_days'=>max(0,(int)($_POST['lead_days']??0)),'weight'=>max(0,(float)($_POST['weight']??0)),'approval_status'=>'pending'];
        foreach($meta as $k=>$v) update_post_meta($pid,$k,$v);
        if(!empty($_FILES['product_image']['name'])){ $aid=self::upload('product_image',$pid); if(is_wp_error($aid))wp_die($aid->get_error_message()); set_post_thumbnail($pid,$aid); }
        if(!empty($_FILES['product_gallery']['name'][0])){ $new=self::upload_many('product_gallery',$pid,10); $old=(array)get_post_meta($pid,'tager_product_gallery_ids',true); update_post_meta($pid,'tager_product_gallery_ids',array_values(array_unique(array_merge($old,$new)))); }
        wp_safe_redirect(add_query_arg(['tab'=>'products','saved'=>1],self::page_url('vendor-dashboard'))); exit;
    }

    public static function product_form_enhancer(){
        if(!is_user_logged_in()||!self::is_vendor()) return; ?>
        <script>
        document.addEventListener('DOMContentLoaded',function(){
          const forms=[...document.querySelectorAll('form')].filter(f=>f.querySelector('input[name="action"][value="tager_v40_product_save"]'));
          forms.forEach(form=>{
            form.enctype='multipart/form-data';
            const image=form.querySelector('input[name="product_image"]');
            if(image && !form.querySelector('input[name="product_gallery[]"]')){
              const wrap=document.createElement('label'); wrap.className='full t43-extra-field'; wrap.innerHTML='<b>صور إضافية للمنتج (حتى 10 صور)</b><input type="file" name="product_gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>ستظهر كمعرض صور داخل صفحة المنتج.</small>'; image.closest('label')?.insertAdjacentElement('afterend',wrap);
            }
            const desc=form.querySelector('textarea[name="description"]');
            if(desc && !form.querySelector('[name="short_description"]')){
              const fields=document.createElement('div'); fields.className='t43-extra-fields'; fields.innerHTML='<label><b>وصف مختصر</b><textarea name="short_description" maxlength="300"></textarea></label><label><b>العلامة التجارية</b><input name="brand"></label><label><b>وحدة البيع</b><input name="unit" placeholder="قطعة / كرتونة / كجم"></label><label><b>الوزن بالكيلو</b><input type="number" step="0.01" min="0" name="weight"></label>'; desc.closest('label')?.insertAdjacentElement('afterend',fields);
            }
          });
        });
        </script><?php
    }

    public static function enhance_product_content($content){
        if(!is_singular('tager_product')||!in_the_loop()||!is_main_query()) return $content;
        $id=get_the_ID(); $gallery=(array)get_post_meta($id,'tager_product_gallery_ids',true); $vendor=(int)get_post_field('post_author',$id);
        $logo=(int)get_user_meta($vendor,'tager_vendor_logo_id',true); $store=get_user_meta($vendor,'store_name',true)?:get_the_author_meta('display_name',$vendor);
        $media='<section class="t43-product-media"><div class="t43-main-image">'.(has_post_thumbnail($id)?get_the_post_thumbnail($id,'large'):'<div class="t43-placeholder">لا توجد صورة رئيسية</div>').'</div>';
        if($gallery){$media.='<div class="t43-product-gallery">';foreach($gallery as $aid)$media.=wp_get_attachment_image((int)$aid,'medium');$media.='</div>';}$media.='</section>';
        $vendorbox='<section class="t43-vendor-card">'.($logo?wp_get_attachment_image($logo,'thumbnail'):'<div class="t43-vendor-letter">'.esc_html(mb_substr($store,0,1)).'</div>').'<div><small>يباع بواسطة</small><h3>'.esc_html($store).'</h3><a href="'.esc_url(add_query_arg('vendor',$vendor,self::page_url('vendor-store'))).'">زيارة متجر المورد</a></div></section>';
        return $media.$content.$vendorbox;
    }

    public static function vendor_store_header($atts){
        $id=absint($atts['id']??($_GET['vendor']??0)); if(!$id)return '';
        $u=get_user_by('id',$id); if(!$u)return '';
        $logo=(int)get_user_meta($id,'tager_vendor_logo_id',true);$cover=(int)get_user_meta($id,'tager_vendor_cover_id',true);$gallery=(array)get_user_meta($id,'tager_vendor_gallery_ids',true);$store=get_user_meta($id,'store_name',true)?:$u->display_name;
        ob_start();?><section class="t43-store-hero" <?php if($cover)echo 'style="background-image:linear-gradient(90deg,rgba(6,46,35,.88),rgba(6,46,35,.55)),url('.esc_url(wp_get_attachment_image_url($cover,'full')).')"'; ?>><div><?php echo $logo?wp_get_attachment_image($logo,'medium'):'<div class="t43-vendor-letter">'.esc_html(mb_substr($store,0,1)).'</div>'; ?></div><div><span>مورد معتمد</span><h1><?php echo esc_html($store); ?></h1><p><?php echo esc_html(get_user_meta($id,'store_description',true)); ?></p><strong>الحد الأدنى للطلب: <?php echo number_format((float)get_user_meta($id,'vendor_min_order',true),2); ?> ج.م</strong></div></section><?php if($gallery){echo '<div class="t43-gallery">';foreach($gallery as $aid)echo '<figure>'.wp_get_attachment_image((int)$aid,'medium').'</figure>';echo '</div>';}return ob_get_clean();
    }

    public static function media_library(){
        if(!is_user_logged_in())return '<div class="t43-panel">سجّل الدخول لعرض مكتبة الصور.</div>';
        $u=wp_get_current_user();$args=['post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image','posts_per_page'=>60,'author'=>$u->ID];$items=get_posts($args);
        ob_start();?><section class="t43-panel"><div class="t43-title"><div><span>إدارة الوسائط</span><h1>مكتبة الصور</h1><p>الصور التي رفعتها للمنتجات أو الحساب أو المتجر.</p></div></div><div class="t43-library"><?php if(!$items)echo '<p>لم يتم رفع صور حتى الآن.</p>';foreach($items as $a){echo '<article>'.wp_get_attachment_image($a->ID,'medium').'<small>'.esc_html($a->post_title).'</small></article>';}?></div></section><?php return ob_get_clean();
    }

    public static function save_product_media(){ wp_die('غير مستخدم'); }

    public static function admin_menu(){ add_submenu_page('tager-v40','إدارة الصور','إدارة الصور','upload_files','tager-v43-media',[__CLASS__,'admin_screen']); }
    public static function admin_screen(){
        if(!current_user_can('upload_files'))wp_die('غير مصرح');
        $products=get_posts(['post_type'=>'tager_product','post_status'=>'any','posts_per_page'=>200]);$missing=0;foreach($products as $p)if(!has_post_thumbnail($p->ID))$missing++;
        $vendors=get_users(['role__in'=>['tager_vendor','tager_vendor_pending']]);$without_logo=0;foreach($vendors as $v)if(!get_user_meta($v->ID,'tager_vendor_logo_id',true))$without_logo++;
        echo '<div class="wrap"><h1>إدارة الصور والهوية</h1><div style="display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:16px;max-width:900px"><div class="card"><h2>'.count($products).'</h2><p>إجمالي المنتجات</p></div><div class="card"><h2>'.$missing.'</h2><p>منتجات بدون صورة رئيسية</p></div><div class="card"><h2>'.$without_logo.'</h2><p>موردون بدون شعار</p></div></div><p><a class="button button-primary" href="'.esc_url(admin_url('upload.php')).'">فتح مكتبة الوسائط</a> <a class="button" href="'.esc_url(self::page_url('media-library')).'">فتح مكتبة المستخدم</a></p><table class="widefat striped"><thead><tr><th>المنتج</th><th>المورد</th><th>الصورة الرئيسية</th><th>صور المعرض</th><th>تعديل</th></tr></thead><tbody>';foreach($products as $p){$g=(array)get_post_meta($p->ID,'tager_product_gallery_ids',true);echo '<tr><td>'.esc_html($p->post_title).'</td><td>'.esc_html(get_the_author_meta('display_name',$p->post_author)).'</td><td>'.(has_post_thumbnail($p->ID)?'✅':'❌').'</td><td>'.count($g).'</td><td><a href="'.esc_url(get_edit_post_link($p->ID)).'">فتح</a></td></tr>';}echo '</tbody></table></div>';
    }

    public static function assets(){
        wp_register_style('tager-v43',false);wp_enqueue_style('tager-v43');wp_add_inline_style('tager-v43',
        '.t43-panel{max-width:1280px;margin:24px auto;background:#fff;border:1px solid #e2ebe7;border-radius:22px;padding:24px;box-shadow:0 12px 35px rgba(9,70,52,.08)}.t43-title{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:20px}.t43-title span{color:#a77a12;font-weight:800}.t43-title h1,.t43-title h2{margin:5px 0;color:#0b4d3b}.t43-btn{display:inline-flex;padding:11px 17px;border:0;border-radius:11px;background:#d8ad37;color:#143a30!important;font-weight:800;text-decoration:none;cursor:pointer}.t43-btn.secondary{background:#eef6f2}.t43-media-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.t43-upload{border:1px dashed #9cbdb1;border-radius:16px;padding:16px;display:grid;gap:10px}.t43-upload.wide{grid-column:1/-1}.t43-upload input{padding:10px;background:#f7faf8;border-radius:9px}.t43-preview,.t43-placeholder{width:100%;height:180px;object-fit:cover;border-radius:13px;background:#eef3f1;display:grid;place-items:center}.t43-preview.avatar,.t43-preview.logo{width:130px;height:130px}.t43-preview.avatar{border-radius:50%}.t43-preview.cover{height:220px}.t43-gallery,.t43-product-gallery,.t43-library{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-top:18px}.t43-gallery figure,.t43-library article{margin:0;background:#f7faf8;border-radius:14px;padding:8px}.t43-gallery img,.t43-product-gallery img,.t43-library img{width:100%;height:150px;object-fit:cover;border-radius:10px}.t43-product-media{margin-bottom:22px}.t43-main-image img{width:100%;max-height:600px;object-fit:contain;background:#fff;border-radius:20px}.t43-product-gallery img{cursor:pointer;border:2px solid transparent}.t43-vendor-card{display:flex;align-items:center;gap:16px;border:1px solid #dfebe6;background:#f7fbf9;border-radius:18px;padding:18px;margin:24px 0}.t43-vendor-card img,.t43-vendor-letter{width:72px;height:72px;border-radius:14px;object-fit:cover}.t43-vendor-letter{background:#d8ad37;display:grid;place-items:center;font-size:28px;font-weight:900;color:#0b4d3b}.t43-vendor-card h3{margin:3px 0}.t43-store-hero{min-height:310px;padding:45px;border-radius:24px;background:linear-gradient(135deg,#0b4d3b,#17745a);background-size:cover;background-position:center;color:#fff;display:flex;align-items:center;gap:25px}.t43-store-hero img{width:150px;height:150px;object-fit:contain;background:#fff;border-radius:20px;padding:10px}.t43-store-hero h1{color:#fff;font-size:42px;margin:6px 0}.t43-extra-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;grid-column:1/-1}.t43-extra-field{grid-column:1/-1}@media(max-width:700px){.t43-media-grid,.t43-extra-fields{grid-template-columns:1fr}.t43-title,.t43-store-hero{display:block}.t43-store-hero{padding:28px}.t43-store-hero h1{font-size:30px}.t43-upload.wide{grid-column:auto}}');
    }
}
Tager_V43_Media_Experience::boot();
