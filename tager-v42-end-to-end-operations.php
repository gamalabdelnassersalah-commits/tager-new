<?php
/**
 * Plugin Name: Tager V42 End-to-End Operations
 * Description: Role-aware workspaces, order lifecycle, approval reasons, notifications, and end-to-end diagnostics.
 * Version: 42.0.0
 */
if (!defined('ABSPATH')) exit;

class Tager_V42_End_To_End {
    const NONCE = 'tager_v42_action';

    public static function boot(){
        add_action('init',[__CLASS__,'register']);
        add_action('init',[__CLASS__,'ensure_pages'],45);
        add_filter('login_redirect',[__CLASS__,'login_redirect'],99,3);
        add_action('template_redirect',[__CLASS__,'guard_pages'],1);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
        add_action('admin_menu',[__CLASS__,'admin_menu']);
        add_action('admin_post_tager_v42_order_status',[__CLASS__,'order_status']);
        add_action('admin_post_tager_v42_cancel_order',[__CLASS__,'cancel_order']);
        add_action('admin_post_tager_v42_approval',[__CLASS__,'approval']);
        add_action('admin_post_tager_v42_repair',[__CLASS__,'repair']);
        add_action('admin_post_tager_v42_run_test',[__CLASS__,'run_test']);
        add_shortcode('tager_v42_customer_orders',[__CLASS__,'customer_orders']);
        add_shortcode('tager_v42_vendor_orders',[__CLASS__,'vendor_orders']);
        add_shortcode('tager_v42_admin_approvals',[__CLASS__,'admin_approvals']);
        add_shortcode('tager_v42_notifications',[__CLASS__,'notifications']);
        add_shortcode('tager_v42_role_home',[__CLASS__,'role_home']);
    }
    public static function user($id=0){ return get_userdata($id ?: get_current_user_id()); }
    public static function is_vendor($id=0){
        $u=self::user($id); if(!$u) return false;
        return (bool)array_intersect((array)$u->roles,['tager_vendor','tager_vendor_pending','wcfm_vendor','vendor']);
    }
    public static function is_admin_team($id=0){
        $u=self::user($id); if(!$u) return false;
        if(user_can($u,'manage_options')) return true;
        foreach((array)$u->roles as $r){ if(strpos($r,'tager_')===0 && !in_array($r,['tager_vendor','tager_vendor_pending','tager_customer'],true)) return true; }
        return false;
    }
    public static function is_customer($id=0){ return self::user($id) && !self::is_vendor($id) && !self::is_admin_team($id); }
    public static function url($slug){ $p=get_page_by_path($slug); return $p?get_permalink($p):home_url('/'.$slug.'/'); }
    public static function pages(){
        return [
            'account-home'=>['مركز الحساب','[tager_v42_role_home]'],
            'customer-orders-center'=>['طلبات العميل','[tager_v42_customer_orders]'],
            'vendor-orders-center'=>['تشغيل طلبات المورد','[tager_v42_vendor_orders]'],
            'admin-approval-center'=>['مركز الموافقات','[tager_v42_admin_approvals]'],
            'notifications-center'=>['الإشعارات','[tager_v42_notifications]'],
        ];
    }
    public static function register(){
        if(!post_type_exists('tager_notification')) register_post_type('tager_notification',['label'=>'Tager Notifications','public'=>false,'show_ui'=>false,'supports'=>['title','editor','author']]);
    }
    public static function ensure_pages(){
        foreach(self::pages() as $slug=>$d){
            $p=get_page_by_path($slug);
            if(!$p) wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$d[0],'post_name'=>$slug,'post_content'=>$d[1]]);
            elseif(trim((string)$p->post_content)==='' || strpos($p->post_content,'[tager_v42_')===false) wp_update_post(['ID'=>$p->ID,'post_content'=>$d[1]]);
        }
    }
    public static function login_redirect($redirect,$requested,$user){
        if(!$user || is_wp_error($user)) return $redirect;
        if(self::is_admin_team($user->ID)) return self::url('admin-portal');
        if(self::is_vendor($user->ID)) return self::url('vendor-dashboard');
        return self::url('account-home');
    }
    public static function guard_pages(){
        if(!is_page()) return;
        $slug=get_post_field('post_name',get_queried_object_id());
        $vendor_pages=['vendor-dashboard','vendor-products','vendor-add-product','vendor-orders','vendor-orders-center','vendor-performance','vendor-market'];
        $admin_pages=['admin-portal','admin-approval-center','admin-health-center'];
        $customer_pages=['customer-dashboard','my-account','customer-orders-center','customer-activity'];
        if(in_array($slug,$vendor_pages,true) && (!is_user_logged_in() || !self::is_vendor())) wp_safe_redirect(self::url('login')) && exit;
        if(in_array($slug,$admin_pages,true) && (!is_user_logged_in() || !self::is_admin_team())) wp_safe_redirect(self::url('login')) && exit;
        if(in_array($slug,$customer_pages,true) && (!is_user_logged_in() || !self::is_customer())) wp_safe_redirect(self::url('login')) && exit;
    }
    public static function notify($uid,$title,$message,$link=''){
        if(!$uid) return;
        $id=wp_insert_post(['post_type'=>'tager_notification','post_status'=>'publish','post_title'=>$title,'post_content'=>$message,'post_author'=>$uid]);
        if($id){ update_post_meta($id,'_tager_user_id',$uid); update_post_meta($id,'_tager_link',$link); update_post_meta($id,'_tager_read',0); }
    }
    public static function order_posts($args=[]){
        $defaults=['post_type'=>['tager_order','shop_order'],'post_status'=>'any','posts_per_page'=>50,'orderby'=>'date','order'=>'DESC'];
        return get_posts(wp_parse_args($args,$defaults));
    }
    public static function order_total($id){ return (float)(get_post_meta($id,'_order_total',true) ?: get_post_meta($id,'total',true)); }
    public static function status_label($s){
        $map=['pending'=>'جديد','on-hold'=>'بانتظار الدفع','processing'=>'جاري التجهيز','confirmed'=>'مؤكد','shipped'=>'تم الشحن','completed'=>'مكتمل','cancelled'=>'ملغي','refunded'=>'مسترجع'];
        $s=str_replace('wc-','',$s); return $map[$s]??$s;
    }
    public static function customer_orders(){
        if(!is_user_logged_in()||!self::is_customer()) return '<div class="t42-alert">هذه الصفحة مخصصة للعملاء.</div>';
        $uid=get_current_user_id();
        $orders=self::order_posts(['meta_query'=>['relation'=>'OR',['key'=>'_customer_user','value'=>$uid],['key'=>'customer_id','value'=>$uid]]]);
        ob_start(); ?>
        <div class="t42-shell"><header class="t42-hero"><div><span>حساب العميل</span><h1>طلباتي ومتابعة التنفيذ</h1><p>راجع حالة الطلب، المبلغ، طريقة الدفع والشحن من شاشة واحدة.</p></div><a class="t42-btn" href="<?php echo esc_url(self::url('products')); ?>">التسوق الآن</a></header>
        <div class="t42-cards"><?php if(!$orders): ?><div class="t42-empty"><h3>لا توجد طلبات بعد</h3><p>ابدأ بإضافة منتجات إلى السلة.</p></div><?php endif; ?>
        <?php foreach($orders as $o): $status=str_replace('wc-','',get_post_status($o)); $payment=get_post_meta($o->ID,'payment_method',true)?:get_post_meta($o->ID,'_payment_method_title',true); $gov=get_post_meta($o->ID,'governorate',true); ?>
        <article class="t42-card"><div class="t42-card-head"><div><b>طلب #<?php echo esc_html($o->ID); ?></b><small><?php echo esc_html(get_the_date('Y-m-d',$o)); ?></small></div><span class="t42-badge"><?php echo esc_html(self::status_label($status)); ?></span></div>
        <div class="t42-grid3"><div><small>الإجمالي</small><b><?php echo number_format(self::order_total($o->ID),2); ?> ج.م</b></div><div><small>الدفع</small><b><?php echo esc_html($payment?:'غير محدد'); ?></b></div><div><small>المحافظة</small><b><?php echo esc_html($gov?:'—'); ?></b></div></div>
        <div class="t42-actions"><a class="t42-btn secondary" href="<?php echo esc_url(add_query_arg('order_id',$o->ID,self::url('track-order'))); ?>">تتبع الطلب</a><?php if(in_array($status,['pending','on-hold','confirmed'],true)): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v42_cancel_order"><input type="hidden" name="order_id" value="<?php echo esc_attr($o->ID); ?>"><?php wp_nonce_field(self::NONCE); ?><button class="t42-btn danger">إلغاء الطلب</button></form><?php endif; ?></div></article>
        <?php endforeach; ?></div></div><?php return ob_get_clean();
    }
    public static function vendor_orders(){
        if(!is_user_logged_in()||!self::is_vendor()) return '<div class="t42-alert">هذه الصفحة مخصصة للموردين.</div>';
        $uid=get_current_user_id();
        $orders=self::order_posts(['meta_query'=>['relation'=>'OR',['key'=>'vendor_id','value'=>$uid],['key'=>'_vendor_id','value'=>$uid],['key'=>'_tager_vendor_ids','value'=>'"'.$uid.'"','compare'=>'LIKE']]]);
        ob_start(); ?>
        <div class="t42-shell"><header class="t42-hero"><div><span>تشغيل المورد</span><h1>إدارة الطلبات والتنفيذ</h1><p>حدّث الحالة خطوة بخطوة، وسيتلقى العميل إشعارًا تلقائيًا.</p></div><a class="t42-btn" href="<?php echo esc_url(self::url('vendor-products')); ?>">منتجاتي</a></header>
        <div class="t42-table"><table><thead><tr><th>الطلب</th><th>التاريخ</th><th>القيمة</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
        <?php if(!$orders): ?><tr><td colspan="5">لا توجد طلبات مرتبطة بحسابك.</td></tr><?php endif; ?>
        <?php foreach($orders as $o): $status=str_replace('wc-','',get_post_status($o)); ?><tr><td>#<?php echo esc_html($o->ID); ?></td><td><?php echo esc_html(get_the_date('Y-m-d',$o)); ?></td><td><?php echo number_format(self::order_total($o->ID),2); ?> ج.م</td><td><span class="t42-badge"><?php echo esc_html(self::status_label($status)); ?></span></td><td><form class="t42-inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v42_order_status"><input type="hidden" name="order_id" value="<?php echo esc_attr($o->ID); ?>"><?php wp_nonce_field(self::NONCE); ?><select name="status"><option value="confirmed">مؤكد</option><option value="processing">جاري التجهيز</option><option value="shipped">تم الشحن</option><option value="completed">مكتمل</option></select><button class="t42-btn small">تحديث</button></form></td></tr><?php endforeach; ?></tbody></table></div></div><?php return ob_get_clean();
    }
    public static function admin_approvals(){
        if(!self::is_admin_team()) return '<div class="t42-alert">غير مصرح.</div>';
        $vendors=get_users(['role'=>'tager_vendor_pending','number'=>100]);
        $products=get_posts(['post_type'=>['tager_product','product'],'post_status'=>'pending','posts_per_page'=>100]);
        ob_start(); ?>
        <div class="t42-shell"><header class="t42-hero"><div><span>الإدارة</span><h1>مركز الموافقات والرفض المسبب</h1><p>وافق أو ارفض مع تسجيل سبب واضح وإرسال إشعار لصاحب الطلب.</p></div></header>
        <div class="t42-two"><section class="t42-panel"><h2>الموردون المنتظرون</h2><?php if(!$vendors)echo '<p>لا توجد طلبات موردين.</p>'; foreach($vendors as $v): ?><div class="t42-review"><div><b><?php echo esc_html(get_user_meta($v->ID,'store_name',true)?:$v->display_name); ?></b><small><?php echo esc_html(get_user_meta($v->ID,'phone',true)); ?></small></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v42_approval"><input type="hidden" name="kind" value="vendor"><input type="hidden" name="object_id" value="<?php echo esc_attr($v->ID); ?>"><?php wp_nonce_field(self::NONCE); ?><input name="reason" placeholder="ملاحظة أو سبب الرفض"><button class="t42-btn small" name="decision" value="approve">قبول</button><button class="t42-btn danger small" name="decision" value="reject">رفض</button></form></div><?php endforeach; ?></section>
        <section class="t42-panel"><h2>المنتجات المنتظرة</h2><?php if(!$products)echo '<p>لا توجد منتجات معلقة.</p>'; foreach($products as $p): ?><div class="t42-review"><div><b><?php echo esc_html($p->post_title); ?></b><small>المورد: <?php echo esc_html(get_the_author_meta('display_name',$p->post_author)); ?></small></div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v42_approval"><input type="hidden" name="kind" value="product"><input type="hidden" name="object_id" value="<?php echo esc_attr($p->ID); ?>"><?php wp_nonce_field(self::NONCE); ?><input name="reason" placeholder="ملاحظة أو سبب الرفض"><button class="t42-btn small" name="decision" value="approve">نشر</button><button class="t42-btn danger small" name="decision" value="reject">رفض</button></form></div><?php endforeach; ?></section></div></div><?php return ob_get_clean();
    }
    public static function notifications(){
        if(!is_user_logged_in()) return '<div class="t42-alert">سجل الدخول لعرض الإشعارات.</div>';
        $uid=get_current_user_id();
        $items=get_posts(['post_type'=>'tager_notification','post_status'=>'publish','posts_per_page'=>50,'meta_key'=>'_tager_user_id','meta_value'=>$uid]);
        ob_start(); ?><div class="t42-shell"><header class="t42-hero"><div><span>الإشعارات</span><h1>آخر التحديثات</h1><p>تحديثات الطلبات والموافقات والحساب.</p></div></header><div class="t42-list"><?php if(!$items)echo '<div class="t42-empty">لا توجد إشعارات.</div>'; foreach($items as $n): $link=get_post_meta($n->ID,'_tager_link',true); ?><article class="t42-card"><div class="t42-card-head"><b><?php echo esc_html($n->post_title); ?></b><small><?php echo esc_html(get_the_date('Y-m-d H:i',$n)); ?></small></div><p><?php echo esc_html(wp_strip_all_tags($n->post_content)); ?></p><?php if($link): ?><a class="t42-btn secondary small" href="<?php echo esc_url($link); ?>">فتح</a><?php endif; ?></article><?php endforeach; ?></div></div><?php return ob_get_clean();
    }
    public static function role_home(){
        if(!is_user_logged_in()) return '<div class="t42-alert">يجب تسجيل الدخول.</div>';
        if(self::is_admin_team()) wp_safe_redirect(self::url('admin-portal')) && exit;
        if(self::is_vendor()) wp_safe_redirect(self::url('vendor-dashboard')) && exit;
        wp_safe_redirect(self::url('customer-dashboard')) && exit;
    }
    public static function cancel_order(){
        if(!is_user_logged_in()) wp_die('Login required'); check_admin_referer(self::NONCE);
        $id=absint($_POST['order_id']??0); $owner=(int)(get_post_meta($id,'_customer_user',true)?:get_post_meta($id,'customer_id',true));
        $status=str_replace('wc-','',get_post_status($id));
        if($owner!==get_current_user_id() || !in_array($status,['pending','on-hold','confirmed'],true)) wp_die('غير مسموح');
        wp_update_post(['ID'=>$id,'post_status'=>get_post_type($id)==='shop_order'?'wc-cancelled':'cancelled']);
        update_post_meta($id,'_tager_cancelled_by_customer',current_time('mysql'));
        wp_safe_redirect(self::url('customer-orders-center')); exit;
    }
    public static function order_status(){
        if(!is_user_logged_in()||(!self::is_vendor()&&!self::is_admin_team())) wp_die('No permission'); check_admin_referer(self::NONCE);
        $id=absint($_POST['order_id']??0); $status=sanitize_key($_POST['status']??''); if(!in_array($status,['confirmed','processing','shipped','completed'],true)) wp_die('Invalid status');
        if(self::is_vendor()&&!self::is_admin_team()){
            $uid=get_current_user_id(); $ok=((int)get_post_meta($id,'vendor_id',true)===$uid)||((int)get_post_meta($id,'_vendor_id',true)===$uid)||in_array($uid,(array)get_post_meta($id,'_tager_vendor_ids',true),true); if(!$ok)wp_die('No permission');
        }
        wp_update_post(['ID'=>$id,'post_status'=>get_post_type($id)==='shop_order'?'wc-'.$status:$status]);
        $timeline=(array)get_post_meta($id,'_tager_status_timeline',true); $timeline[]=['status'=>$status,'time'=>current_time('mysql'),'user'=>get_current_user_id()]; update_post_meta($id,'_tager_status_timeline',$timeline);
        $customer=(int)(get_post_meta($id,'_customer_user',true)?:get_post_meta($id,'customer_id',true)); self::notify($customer,'تحديث حالة الطلب #'.$id,'أصبحت حالة طلبك: '.self::status_label($status),add_query_arg('order_id',$id,self::url('track-order')));
        wp_safe_redirect(wp_get_referer()?:self::url('vendor-orders-center')); exit;
    }
    public static function approval(){
        if(!self::is_admin_team()) wp_die('No permission'); check_admin_referer(self::NONCE);
        $kind=sanitize_key($_POST['kind']??''); $decision=sanitize_key($_POST['decision']??''); $id=absint($_POST['object_id']??0); $reason=sanitize_textarea_field($_POST['reason']??'');
        if($kind==='vendor'){
            $u=new WP_User($id); if(!$u->exists())wp_die('User not found');
            if($decision==='approve'){ $u->set_role('tager_vendor'); update_user_meta($id,'vendor_status','approved'); self::notify($id,'تم قبول حساب المورد','يمكنك الآن إضافة المنتجات ومتابعة الطلبات.',self::url('vendor-dashboard')); }
            else { update_user_meta($id,'vendor_status','rejected'); update_user_meta($id,'vendor_rejection_reason',$reason); self::notify($id,'تم رفض طلب المورد',$reason?:'راجع بيانات التسجيل وتواصل مع الدعم.',self::url('support-center')); }
        } elseif($kind==='product'){
            $p=get_post($id); if(!$p)wp_die('Product not found');
            if($decision==='approve'){ wp_update_post(['ID'=>$id,'post_status'=>'publish']); update_post_meta($id,'_tager_approval_note',$reason); self::notify($p->post_author,'تم نشر المنتج: '.$p->post_title,'أصبح المنتج ظاهرًا في السوق.',get_permalink($id)); }
            else { wp_update_post(['ID'=>$id,'post_status'=>'draft']); update_post_meta($id,'_tager_rejection_reason',$reason); self::notify($p->post_author,'تم رفض المنتج: '.$p->post_title,$reason?:'يرجى مراجعة بيانات المنتج.',self::url('vendor-products')); }
        }
        wp_safe_redirect(self::url('admin-approval-center')); exit;
    }
    public static function admin_menu(){ add_menu_page('Tager V42','Tager V42','read','tager-v42',[__CLASS__,'admin_screen'],'dashicons-shield-alt',2); }
    public static function admin_screen(){
        if(!self::is_admin_team()) wp_die('No permission');
        $checks=[]; foreach(self::pages() as $slug=>$d)$checks[$slug]=(bool)get_page_by_path($slug);
        $checks['customer_role']=(bool)(get_role('tager_customer')||get_role('customer')); $checks['vendor_role']=(bool)(get_role('tager_vendor')||get_role('wcfm_vendor')); $checks['notifications']=post_type_exists('tager_notification');
        echo '<div class="wrap"><h1>Tager V42 — End-to-End Operations</h1><p>فحص المسارات، الحفظ، الموافقات، الطلبات والإشعارات.</p><table class="widefat striped"><thead><tr><th>الفحص</th><th>النتيجة</th></tr></thead><tbody>'; foreach($checks as $k=>$ok)echo '<tr><td>'.esc_html($k).'</td><td>'.($ok?'✅ جاهز':'❌ ناقص').'</td></tr>'; echo '</tbody></table><p style="margin-top:15px"><a class="button button-primary" href="'.esc_url(self::url('admin-approval-center')).'">فتح مركز الموافقات</a> <a class="button" href="'.esc_url(self::url('admin-health-center')).'">مركز صحة النظام</a></p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-right:8px"><input type="hidden" name="action" value="tager_v42_repair">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button class="button">إصلاح الصفحات</button></form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block"><input type="hidden" name="action" value="tager_v42_run_test">'.wp_nonce_field(self::NONCE,'_wpnonce',true,false).'<button class="button">تشغيل اختبار البيانات</button></form>'; if(isset($_GET['test'])) echo '<div class="notice notice-'.($_GET['test']==='ok'?'success':'error').' is-dismissible"><p>'.($_GET['test']==='ok'?'نجح اختبار إنشاء وقراءة وحذف بيانات المستخدم والمنتج والطلب.':'فشل جزء من اختبار البيانات.').'</p></div>'; echo '</div>';
    }
    public static function repair(){ if(!self::is_admin_team())wp_die('No permission');check_admin_referer(self::NONCE);self::ensure_pages();flush_rewrite_rules(false);wp_safe_redirect(admin_url('admin.php?page=tager-v42'));exit; }
    public static function run_test(){
        if(!self::is_admin_team())wp_die('No permission');check_admin_referer(self::NONCE);$ok=true;$stamp=time();
        $uid=wp_insert_user(['user_login'=>'tager_test_'.$stamp,'user_pass'=>wp_generate_password(16),'user_email'=>'tager_test_'.$stamp.'@example.invalid','display_name'=>'Tager Test']); if(is_wp_error($uid))$ok=false; else {update_user_meta($uid,'phone','01000000000');$ok=$ok&&(get_user_meta($uid,'phone',true)==='01000000000');}
        $pid=wp_insert_post(['post_type'=>post_type_exists('tager_product')?'tager_product':'post','post_status'=>'draft','post_title'=>'Tager Test Product '.$stamp,'post_author'=>is_wp_error($uid)?1:$uid]); if(!$pid)$ok=false; else {update_post_meta($pid,'retail_price',100);$ok=$ok&&((float)get_post_meta($pid,'retail_price',true)===100.0);}
        $oid=wp_insert_post(['post_type'=>post_type_exists('tager_order')?'tager_order':'post','post_status'=>'draft','post_title'=>'Tager Test Order '.$stamp]); if(!$oid)$ok=false; else {update_post_meta($oid,'_order_total',250);$ok=$ok&&((float)get_post_meta($oid,'_order_total',true)===250.0);}
        if($pid)wp_delete_post($pid,true);if($oid)wp_delete_post($oid,true);if(!is_wp_error($uid))wp_delete_user($uid);
        wp_safe_redirect(admin_url('admin.php?page=tager-v42&test='.($ok?'ok':'fail')));exit;
    }
    public static function assets(){
        wp_register_style('tager-v42',false); wp_enqueue_style('tager-v42');
        wp_add_inline_style('tager-v42','.t42-shell{max-width:1240px;margin:28px auto;padding:0 16px}.t42-hero{background:linear-gradient(135deg,#0b4d3b,#14755a);color:#fff;border-radius:24px;padding:32px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.t42-hero span{color:#f1d071;font-weight:800}.t42-hero h1{margin:6px 0 8px;font-size:34px}.t42-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;padding:11px 17px;background:#d7ad37;color:#173d31!important;text-decoration:none;font-weight:800;cursor:pointer}.t42-btn.secondary{background:#eef5f2;color:#174c3d!important}.t42-btn.danger{background:#a92d2d;color:#fff!important}.t42-btn.small{padding:7px 11px;font-size:13px}.t42-cards,.t42-list{display:grid;gap:16px}.t42-card,.t42-panel,.t42-empty{background:#fff;border:1px solid #dfe9e4;border-radius:18px;padding:20px;box-shadow:0 10px 28px rgba(16,65,51,.07)}.t42-card-head{display:flex;justify-content:space-between;gap:16px;align-items:center}.t42-card-head small{display:block;color:#6d7e77;margin-top:4px}.t42-badge{background:#e9f3ef;color:#0b5d46;border-radius:999px;padding:6px 10px;font-weight:800;font-size:12px}.t42-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}.t42-grid3 div{background:#f6f9f8;border-radius:12px;padding:12px}.t42-grid3 small{display:block;color:#71817b}.t42-actions{display:flex;gap:10px;flex-wrap:wrap}.t42-actions form{margin:0}.t42-table{overflow:auto;background:#fff;border-radius:18px;border:1px solid #dfe9e4}.t42-table table{width:100%;border-collapse:collapse}.t42-table th,.t42-table td{padding:14px;border-bottom:1px solid #e7eeeb;text-align:right}.t42-inline{display:flex;gap:8px}.t42-inline select,.t42-review input{padding:9px;border:1px solid #cad8d2;border-radius:9px}.t42-two{display:grid;grid-template-columns:1fr 1fr;gap:18px}.t42-review{display:grid;gap:10px;padding:14px 0;border-bottom:1px solid #e5ece9}.t42-review small{display:block;color:#708179;margin-top:4px}.t42-review form{display:flex;gap:8px;flex-wrap:wrap}.t42-alert{max-width:900px;margin:28px auto;padding:18px;background:#fff4d5;border:1px solid #ead37e;border-radius:14px}@media(max-width:800px){.t42-hero{display:block}.t42-hero .t42-btn{margin-top:14px}.t42-grid3,.t42-two{grid-template-columns:1fr}.t42-inline{min-width:260px}.t42-hero h1{font-size:27px}}');
    }
}
Tager_V42_End_To_End::boot();
