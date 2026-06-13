<?php
/**
 * Plugin Name: Tager V31 Admin Command Center
 * Description: Enterprise admin command center with per-user permissions, access policies, approval queues, internal tasks and operational notifications.
 * Version: 31.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V31_Admin_Command_Center {
    const OPT_NOTICES = 'tager_v31_admin_notices';
    const LOG = 'tager_v31_audit_log';
    const TASK_POST = 'tager_admin_task';
    const MENU = 'tager-v31-command';

    private static $caps = [
        'tager_manage_staff'      => 'إدارة فريق الإدارة',
        'tager_manage_vendors'    => 'إدارة الموردين',
        'tager_manage_products'   => 'إدارة المنتجات',
        'tager_manage_orders'     => 'إدارة الطلبات',
        'tager_manage_customers'  => 'إدارة العملاء',
        'tager_manage_finance'    => 'الإدارة المالية',
        'tager_manage_shipping'   => 'إدارة الشحن',
        'tager_manage_support'    => 'خدمة العملاء',
        'tager_manage_marketing'  => 'التسويق والعروض',
        'tager_manage_settings'   => 'إعدادات المنصة',
        'tager_view_reports'      => 'التقارير والتحليلات',
    ];

    public static function init() {
        add_action('init', [__CLASS__, 'register_task_type']);
        add_action('admin_menu', [__CLASS__, 'menu'], 110);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_filter('user_has_cap', [__CLASS__, 'apply_user_overrides'], 40, 4);
        add_filter('authenticate', [__CLASS__, 'enforce_access_policy'], 70, 3);
        add_action('wp_login', [__CLASS__, 'record_login'], 20, 2);
        add_action('admin_notices', [__CLASS__, 'show_admin_notices']);

        add_action('admin_post_tager_v31_save_policy', [__CLASS__, 'save_policy']);
        add_action('admin_post_tager_v31_create_task', [__CLASS__, 'create_task']);
        add_action('admin_post_tager_v31_update_task', [__CLASS__, 'update_task']);
        add_action('admin_post_tager_v31_add_notice', [__CLASS__, 'add_notice']);
        add_action('admin_post_tager_v31_dismiss_notice', [__CLASS__, 'dismiss_notice']);
        add_action('admin_post_tager_v31_quick_approve', [__CLASS__, 'quick_approve']);
    }

    public static function register_task_type() {
        register_post_type(self::TASK_POST, [
            'labels' => ['name'=>'مهام الإدارة','singular_name'=>'مهمة إدارية'],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title','editor','author'],
            'capability_type' => 'post',
        ]);
    }

    public static function menu() {
        $cap = current_user_can('tager_view_dashboard') ? 'tager_view_dashboard' : 'manage_options';
        add_submenu_page('tager-control','مركز قيادة الإدارة','مركز قيادة الإدارة',$cap,self::MENU,[__CLASS__,'dashboard_page']);
        add_submenu_page('tager-control','سياسات وصلاحيات الأفراد','صلاحيات الأفراد','tager_manage_staff','tager-v31-access',[__CLASS__,'access_page']);
        add_submenu_page('tager-control','مهام فريق الإدارة','مهام الإدارة',$cap,'tager-v31-tasks',[__CLASS__,'tasks_page']);
        add_submenu_page('tager-control','مركز الموافقات','مركز الموافقات',$cap,'tager-v31-approvals',[__CLASS__,'approvals_page']);
        add_submenu_page('tager-control','إعلانات فريق الإدارة','إعلانات الإدارة','tager_manage_staff','tager-v31-notices',[__CLASS__,'notices_page']);
        add_submenu_page('tager-control','سجل V31','سجل V31','tager_manage_staff','tager-v31-log',[__CLASS__,'log_page']);
    }

    public static function assets() {
        $page = sanitize_key($_GET['page'] ?? '');
        if (strpos($page, 'tager-v31') !== 0) return;
        wp_register_style('tager-v31-admin', false);
        wp_enqueue_style('tager-v31-admin');
        wp_add_inline_style('tager-v31-admin', self::css());
        wp_register_script('tager-v31-admin', '', [], false, true);
        wp_enqueue_script('tager-v31-admin');
        wp_add_inline_script('tager-v31-admin', "document.addEventListener('submit',function(e){var b=e.target.querySelector('button[type=submit]');if(b){b.disabled=true;b.dataset.old=b.textContent;b.textContent='جاري الحفظ...';}});document.addEventListener('click',function(e){var a=e.target.closest('[data-confirm]');if(a&&!confirm(a.dataset.confirm)){e.preventDefault();}});");
    }

    private static function css() {
        return '.t31{direction:rtl;max-width:1380px}.t31 *{box-sizing:border-box}.t31-hero{background:linear-gradient(135deg,#082f24,#0f6b4f 58%,#c79836);color:white;padding:30px;border-radius:22px;margin:18px 0;display:flex;justify-content:space-between;gap:20px;align-items:center;box-shadow:0 18px 42px rgba(8,47,36,.18)}.t31-hero h1{color:#fff;margin:0 0 8px;font-size:30px}.t31-hero p{margin:0;opacity:.9}.t31-pill{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);padding:9px 14px;border-radius:999px;font-weight:800}.t31-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:16px 0}.t31-card{background:#fff;border:1px solid #e6ebe8;border-radius:18px;padding:18px;box-shadow:0 10px 28px rgba(8,47,36,.06)}.t31-card h2,.t31-card h3{margin-top:0;color:#123b30}.t31-stat strong{font-size:30px;color:#0f6b4f;display:block}.t31-layout{display:grid;grid-template-columns:380px 1fr;gap:16px}.t31-table{width:100%;border-collapse:collapse}.t31-table th,.t31-table td{padding:12px;border-bottom:1px solid #edf0ee;text-align:right;vertical-align:top}.t31-table th{background:#f7faf8;color:#35584e}.t31-badge{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;background:#ecfdf3;color:#027a48;font-size:12px;font-weight:800}.t31-warn{background:#fff7e8;color:#9a6700}.t31-danger{background:#fef3f2;color:#b42318}.t31-muted{background:#f2f4f7;color:#475467}.t31-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;padding:10px 15px;text-decoration:none;font-weight:800;cursor:pointer}.t31-primary{background:#0f6b4f;color:#fff}.t31-secondary{background:#edf6f2;color:#0f6b4f}.t31-red{background:#b42318;color:#fff}.t31-form label{display:block;font-weight:800;margin:12px 0 6px}.t31-form input,.t31-form select,.t31-form textarea{width:100%;min-height:42px;border:1px solid #d0d5dd;border-radius:9px}.t31-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.t31-check{border:1px solid #e5e7eb;border-radius:10px;padding:9px;background:#fafcfb}.t31-timeline{border-right:3px solid #d7e7df;padding-right:16px}.t31-event{position:relative;margin:0 0 18px}.t31-event:before{content:"";position:absolute;right:-23px;top:4px;width:11px;height:11px;border-radius:50%;background:#0f6b4f;border:3px solid #fff;box-shadow:0 0 0 1px #0f6b4f}.t31-notice{padding:13px 16px;background:#ecfdf3;color:#027a48;border-radius:10px;margin:12px 0}.t31-progress{height:9px;background:#edf1ef;border-radius:99px;overflow:hidden}.t31-progress span{display:block;height:100%;background:linear-gradient(90deg,#0f6b4f,#d3a64b)}@media(max-width:1000px){.t31-grid{grid-template-columns:repeat(2,1fr)}.t31-layout{grid-template-columns:1fr}}@media(max-width:620px){.t31-grid,.t31-checks{grid-template-columns:1fr}.t31-hero{display:block}.t31-table{font-size:12px}}';
    }

    private static function staff_users() {
        return get_users(['role__in'=>['administrator','tager_platform_manager','tager_operations_manager','tager_vendor_manager','tager_catalog_manager','tager_order_manager','tager_finance_manager','tager_support_agent','tager_marketing_manager','tager_readonly_auditor'],'orderby'=>'display_name']);
    }

    public static function apply_user_overrides($allcaps, $caps, $args, $user) {
        if (!$user || empty($user->ID)) return $allcaps;
        $grant = (array)get_user_meta($user->ID,'tager_v31_caps_grant',true);
        $deny = (array)get_user_meta($user->ID,'tager_v31_caps_deny',true);
        foreach ($grant as $cap) if (isset(self::$caps[$cap])) $allcaps[$cap] = true;
        foreach ($deny as $cap) if (isset(self::$caps[$cap])) $allcaps[$cap] = false;
        return $allcaps;
    }

    public static function enforce_access_policy($user, $username, $password) {
        if (is_wp_error($user) || !($user instanceof WP_User)) return $user;
        if (!self::is_admin_team($user)) return $user;
        if (get_user_meta($user->ID,'tager_staff_disabled',true)) return new WP_Error('tager_disabled','تم إيقاف حساب الإدارة. تواصل مع مدير المنصة.');
        $expires = get_user_meta($user->ID,'tager_v31_access_expires',true);
        if ($expires && strtotime($expires.' 23:59:59') < current_time('timestamp')) return new WP_Error('tager_expired','انتهت صلاحية الوصول لهذا الحساب.');
        $days = (array)get_user_meta($user->ID,'tager_v31_allowed_days',true);
        if ($days && !in_array((int)current_time('N'),array_map('intval',$days),true)) return new WP_Error('tager_day_block','الدخول غير مسموح لهذا الحساب في هذا اليوم.');
        $from = get_user_meta($user->ID,'tager_v31_time_from',true);
        $to = get_user_meta($user->ID,'tager_v31_time_to',true);
        $now = current_time('H:i');
        if ($from && $to && ($now < $from || $now > $to)) return new WP_Error('tager_time_block','الدخول غير مسموح خارج ساعات العمل المحددة.');
        return $user;
    }

    private static function is_admin_team($user) {
        if (!$user instanceof WP_User) return false;
        foreach ((array)$user->roles as $role) if ($role === 'administrator' || strpos($role,'tager_') === 0) return true;
        return false;
    }

    public static function record_login($login, $user) {
        if (!self::is_admin_team($user)) return;
        update_user_meta($user->ID,'tager_v31_last_login',current_time('mysql'));
        self::log('staff_login',['user_id'=>$user->ID,'name'=>$user->display_name]);
    }

    private static function counts() {
        $pending_products = wp_count_posts('product');
        $pending_products = isset($pending_products->pending) ? (int)$pending_products->pending : 0;
        $pending_vendors = 0;
        $vendors = get_users(['role__in'=>['vendor','wcfm_vendor','seller'],'fields'=>'ID']);
        foreach ($vendors as $id) {
            $status = get_user_meta($id,'tager_vendor_status',true);
            if (!$status || in_array($status,['pending','review'],true)) $pending_vendors++;
        }
        $orders = post_type_exists('shop_order') ? wp_count_posts('shop_order') : null;
        $pending_orders = 0;
        if ($orders) foreach (['wc-pending','wc-on-hold','wc-processing'] as $s) $pending_orders += isset($orders->$s)?(int)$orders->$s:0;
        $open_tasks = (int)(new WP_Query(['post_type'=>self::TASK_POST,'post_status'=>'publish','meta_key'=>'tager_task_status','meta_value'=>['open','in_progress'],'meta_compare'=>'IN','fields'=>'ids','posts_per_page'=>1]))->found_posts;
        return compact('pending_products','pending_vendors','pending_orders','open_tasks');
    }

    public static function dashboard_page() {
        $c = self::counts();
        $recent_tasks = get_posts(['post_type'=>self::TASK_POST,'post_status'=>'publish','numberposts'=>6,'orderby'=>'date','order'=>'DESC']);
        $log = array_slice(array_reverse((array)get_option(self::LOG,[])),0,8);
        ?>
        <div class="wrap t31"><div class="t31-hero"><div><h1>مركز قيادة الإدارة</h1><p>لوحة موحدة للموافقات والمهام والصلاحيات والتنبيهات التشغيلية.</p></div><span class="t31-pill">Tager V31 Enterprise</span></div>
        <?php if(!empty($_GET['saved'])) echo '<div class="t31-notice">تم تنفيذ العملية بنجاح.</div>'; ?>
        <div class="t31-grid"><div class="t31-card t31-stat"><strong><?php echo $c['pending_vendors']; ?></strong>موردون بانتظار المراجعة</div><div class="t31-card t31-stat"><strong><?php echo $c['pending_products']; ?></strong>منتجات بانتظار الاعتماد</div><div class="t31-card t31-stat"><strong><?php echo $c['pending_orders']; ?></strong>طلبات تحتاج متابعة</div><div class="t31-card t31-stat"><strong><?php echo $c['open_tasks']; ?></strong>مهام إدارية مفتوحة</div></div>
        <div class="t31-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))"><div class="t31-card"><h3>الموافقات</h3><p>مراجعة الموردين والمنتجات والطلبات الحرجة من مكان واحد.</p><a class="t31-btn t31-primary" href="<?php echo esc_url(admin_url('admin.php?page=tager-v31-approvals')); ?>">فتح مركز الموافقات</a></div><div class="t31-card"><h3>إدارة الفريق</h3><p>صلاحيات فردية وساعات دخول وتاريخ انتهاء لكل حساب.</p><a class="t31-btn t31-secondary" href="<?php echo esc_url(admin_url('admin.php?page=tager-v31-access')); ?>">إدارة الوصول</a></div><div class="t31-card"><h3>المهام الداخلية</h3><p>توزيع مهام واضحة ومواعيد نهائية على أعضاء الإدارة.</p><a class="t31-btn t31-secondary" href="<?php echo esc_url(admin_url('admin.php?page=tager-v31-tasks')); ?>">فتح المهام</a></div></div>
        <div class="t31-layout"><div class="t31-card"><h2>آخر المهام</h2><?php if(!$recent_tasks) echo '<p>لا توجد مهام بعد.</p>'; foreach($recent_tasks as $task){$st=get_post_meta($task->ID,'tager_task_status',true)?:'open';echo '<p><strong>'.esc_html($task->post_title).'</strong><br><span class="t31-badge '.($st==='done'?'':'t31-warn').'">'.esc_html(self::status_label($st)).'</span></p>'; } ?></div><div class="t31-card"><h2>آخر النشاطات</h2><div class="t31-timeline"><?php if(!$log) echo '<p>لا توجد نشاطات مسجلة بعد.</p>'; foreach($log as $item){echo '<div class="t31-event"><strong>'.esc_html($item['label']??$item['action']??'نشاط').'</strong><br><small>'.esc_html($item['time']??'').'</small></div>'; } ?></div></div></div></div><?php
    }

    public static function access_page() {
        if (!current_user_can('tager_manage_staff')) wp_die('ليس لديك صلاحية.');
        $users = self::staff_users();
        $edit_id = absint($_GET['user_id'] ?? ($users[0]->ID ?? 0));
        $edit = $edit_id ? get_userdata($edit_id) : null;
        $grant = $edit ? (array)get_user_meta($edit_id,'tager_v31_caps_grant',true) : [];
        $deny = $edit ? (array)get_user_meta($edit_id,'tager_v31_caps_deny',true) : [];
        $days = $edit ? (array)get_user_meta($edit_id,'tager_v31_allowed_days',true) : [];
        ?>
        <div class="wrap t31"><div class="t31-hero"><div><h1>صلاحيات وسياسات الوصول الفردية</h1><p>أضف استثناءات لكل موظف فوق صلاحيات الدور، وحدد أيام وساعات وانتهاء الوصول.</p></div><span class="t31-pill">Zero Trust Access</span></div>
        <div class="t31-layout"><div class="t31-card"><h2>اختيار عضو الإدارة</h2><?php foreach($users as $u){echo '<p><a class="t31-btn '.($u->ID===$edit_id?'t31-primary':'t31-secondary').'" style="width:100%" href="'.esc_url(admin_url('admin.php?page=tager-v31-access&user_id='.$u->ID)).'">'.esc_html($u->display_name).'</a></p>'; } ?></div><div class="t31-card"><?php if(!$edit){echo '<p>لا يوجد أعضاء إدارة.</p>';}else{ ?><h2><?php echo esc_html($edit->display_name); ?></h2><form class="t31-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v31_save_policy"><input type="hidden" name="user_id" value="<?php echo (int)$edit_id; ?>"><?php wp_nonce_field('tager_v31_policy_'.$edit_id); ?><h3>استثناءات الصلاحيات</h3><div class="t31-checks"><?php foreach(self::$caps as $cap=>$label){ ?><div class="t31-check"><strong><?php echo esc_html($label); ?></strong><br><label><input type="radio" name="cap_<?php echo esc_attr($cap); ?>" value="inherit" <?php checked(!in_array($cap,$grant,true)&&!in_array($cap,$deny,true)); ?>> حسب الدور</label> <label><input type="radio" name="cap_<?php echo esc_attr($cap); ?>" value="grant" <?php checked(in_array($cap,$grant,true)); ?>> سماح</label> <label><input type="radio" name="cap_<?php echo esc_attr($cap); ?>" value="deny" <?php checked(in_array($cap,$deny,true)); ?>> منع</label></div><?php } ?></div><h3>سياسة الدخول</h3><label>انتهاء صلاحية الوصول</label><input type="date" name="expires" value="<?php echo esc_attr(get_user_meta($edit_id,'tager_v31_access_expires',true)); ?>"><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><label>من الساعة</label><input type="time" name="time_from" value="<?php echo esc_attr(get_user_meta($edit_id,'tager_v31_time_from',true)); ?>"></div><div><label>إلى الساعة</label><input type="time" name="time_to" value="<?php echo esc_attr(get_user_meta($edit_id,'tager_v31_time_to',true)); ?>"></div></div><label>أيام السماح</label><div class="t31-checks"><?php $labels=[1=>'الإثنين',2=>'الثلاثاء',3=>'الأربعاء',4=>'الخميس',5=>'الجمعة',6=>'السبت',7=>'الأحد']; foreach($labels as $n=>$label){echo '<label class="t31-check"><input type="checkbox" name="days[]" value="'.$n.'" '.checked(in_array((string)$n,array_map('strval',$days),true),true,false).'> '.$label.'</label>'; } ?></div><p><button class="t31-btn t31-primary" type="submit">حفظ السياسة</button></p></form><?php } ?></div></div></div><?php
    }

    public static function save_policy() {
        if (!current_user_can('tager_manage_staff')) wp_die('No permission');
        $id = absint($_POST['user_id'] ?? 0); check_admin_referer('tager_v31_policy_'.$id);
        if (!$id || !get_userdata($id)) wp_die('Invalid user');
        $grant=[];$deny=[];
        foreach(self::$caps as $cap=>$label){$v=sanitize_key($_POST['cap_'.$cap]??'inherit');if($v==='grant')$grant[]=$cap;elseif($v==='deny')$deny[]=$cap;}
        update_user_meta($id,'tager_v31_caps_grant',$grant);update_user_meta($id,'tager_v31_caps_deny',$deny);
        update_user_meta($id,'tager_v31_access_expires',sanitize_text_field($_POST['expires']??''));
        update_user_meta($id,'tager_v31_time_from',sanitize_text_field($_POST['time_from']??''));
        update_user_meta($id,'tager_v31_time_to',sanitize_text_field($_POST['time_to']??''));
        update_user_meta($id,'tager_v31_allowed_days',array_map('intval',(array)($_POST['days']??[])));
        self::log('access_policy_updated',['target_user'=>$id]);
        wp_safe_redirect(admin_url('admin.php?page=tager-v31-access&user_id='.$id.'&saved=1'));exit;
    }

    public static function tasks_page() {
        $staff = self::staff_users();
        $tasks = get_posts(['post_type'=>self::TASK_POST,'post_status'=>'publish','numberposts'=>50,'orderby'=>'date','order'=>'DESC']);
        ?>
        <div class="wrap t31"><div class="t31-hero"><div><h1>مهام فريق الإدارة</h1><p>أنشئ المهام وعيّن المسؤول والأولوية والموعد النهائي، ثم تابع التنفيذ.</p></div><span class="t31-pill">Team Workflow</span></div><div class="t31-layout"><div class="t31-card"><h2>مهمة جديدة</h2><form class="t31-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v31_create_task"><?php wp_nonce_field('tager_v31_create_task'); ?><label>عنوان المهمة</label><input required name="title"><label>التفاصيل</label><textarea name="description" rows="5"></textarea><label>المسؤول</label><select name="assignee"><option value="0">غير محدد</option><?php foreach($staff as $u)echo '<option value="'.(int)$u->ID.'">'.esc_html($u->display_name).'</option>'; ?></select><label>الأولوية</label><select name="priority"><option value="normal">عادية</option><option value="high">عالية</option><option value="urgent">عاجلة</option></select><label>موعد التسليم</label><input type="date" name="due"><p><button class="t31-btn t31-primary">إنشاء المهمة</button></p></form></div><div class="t31-card"><h2>قائمة المهام</h2><div style="overflow:auto"><table class="t31-table"><thead><tr><th>المهمة</th><th>المسؤول</th><th>الأولوية</th><th>الحالة</th><th>التسليم</th><th>تحديث</th></tr></thead><tbody><?php if(!$tasks)echo '<tr><td colspan="6">لا توجد مهام.</td></tr>';foreach($tasks as $task){$ass=(int)get_post_meta($task->ID,'tager_task_assignee',true);$u=$ass?get_userdata($ass):null;$st=get_post_meta($task->ID,'tager_task_status',true)?:'open';$pr=get_post_meta($task->ID,'tager_task_priority',true)?:'normal'; ?><tr><td><strong><?php echo esc_html($task->post_title); ?></strong><br><small><?php echo esc_html(wp_trim_words($task->post_content,18)); ?></small></td><td><?php echo esc_html($u?$u->display_name:'غير محدد'); ?></td><td><span class="t31-badge <?php echo $pr==='urgent'?'t31-danger':($pr==='high'?'t31-warn':''); ?>"><?php echo esc_html(self::priority_label($pr)); ?></span></td><td><?php echo esc_html(self::status_label($st)); ?></td><td><?php echo esc_html(get_post_meta($task->ID,'tager_task_due',true)); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="tager_v31_update_task"><input type="hidden" name="task_id" value="<?php echo (int)$task->ID; ?>"><?php wp_nonce_field('tager_v31_task_'.$task->ID); ?><select name="status"><option value="open" <?php selected($st,'open'); ?>>مفتوحة</option><option value="in_progress" <?php selected($st,'in_progress'); ?>>جاري التنفيذ</option><option value="done" <?php selected($st,'done'); ?>>مكتملة</option><option value="cancelled" <?php selected($st,'cancelled'); ?>>ملغاة</option></select><button class="t31-btn t31-secondary">حفظ</button></form></td></tr><?php } ?></tbody></table></div></div></div></div><?php
    }

    public static function create_task() {
        if (!current_user_can('tager_view_dashboard')) wp_die('No permission'); check_admin_referer('tager_v31_create_task');
        $id=wp_insert_post(['post_type'=>self::TASK_POST,'post_status'=>'publish','post_title'=>sanitize_text_field($_POST['title']??''),'post_content'=>sanitize_textarea_field($_POST['description']??''),'post_author'=>get_current_user_id()]);
        if($id&&!is_wp_error($id)){update_post_meta($id,'tager_task_assignee',absint($_POST['assignee']??0));update_post_meta($id,'tager_task_priority',sanitize_key($_POST['priority']??'normal'));update_post_meta($id,'tager_task_due',sanitize_text_field($_POST['due']??''));update_post_meta($id,'tager_task_status','open');self::log('task_created',['task_id'=>$id]);}
        wp_safe_redirect(admin_url('admin.php?page=tager-v31-tasks&saved=1'));exit;
    }

    public static function update_task() {
        if (!current_user_can('tager_view_dashboard')) wp_die('No permission');$id=absint($_POST['task_id']??0);check_admin_referer('tager_v31_task_'.$id);$status=sanitize_key($_POST['status']??'open');if(!in_array($status,['open','in_progress','done','cancelled'],true))$status='open';update_post_meta($id,'tager_task_status',$status);self::log('task_updated',['task_id'=>$id,'status'=>$status]);wp_safe_redirect(admin_url('admin.php?page=tager-v31-tasks&saved=1'));exit;
    }

    public static function approvals_page() {
        $pending_products=get_posts(['post_type'=>'product','post_status'=>'pending','numberposts'=>30]);
        $vendors=[];foreach(get_users(['role__in'=>['vendor','wcfm_vendor','seller']]) as $u){$s=get_user_meta($u->ID,'tager_vendor_status',true);if(!$s||in_array($s,['pending','review'],true))$vendors[]=$u;}
        ?><div class="wrap t31"><div class="t31-hero"><div><h1>مركز الموافقات</h1><p>قوائم واضحة للموردين والمنتجات التي تحتاج مراجعة قبل النشر.</p></div><span class="t31-pill">Approval Queue</span></div><div class="t31-layout"><div class="t31-card"><h2>الموردون المنتظرون</h2><?php if(!$vendors)echo '<p>لا توجد طلبات موردين معلقة.</p>';foreach($vendors as $u){echo '<p><strong>'.esc_html($u->display_name).'</strong><br>'.esc_html(get_user_meta($u->ID,'tager_phone',true)).'<br>'.self::approve_link('vendor',$u->ID,'اعتماد المورد').'</p>'; } ?></div><div class="t31-card"><h2>المنتجات المنتظرة</h2><?php if(!$pending_products)echo '<p>لا توجد منتجات معلقة.</p>';foreach($pending_products as $p){echo '<p><strong>'.esc_html($p->post_title).'</strong> <small>#'.(int)$p->ID.'</small><br>'.self::approve_link('product',$p->ID,'نشر المنتج').'</p>'; } ?></div></div></div><?php
    }

    private static function approve_link($type,$id,$label){$url=wp_nonce_url(admin_url('admin-post.php?action=tager_v31_quick_approve&type='.$type.'&id='.$id),'tager_v31_approve_'.$type.'_'.$id);return '<a class="t31-btn t31-primary" data-confirm="تأكيد تنفيذ الموافقة؟" href="'.esc_url($url).'">'.esc_html($label).'</a>';}
    public static function quick_approve(){if(!current_user_can('tager_view_dashboard'))wp_die('No permission');$type=sanitize_key($_GET['type']??'');$id=absint($_GET['id']??0);check_admin_referer('tager_v31_approve_'.$type.'_'.$id);if($type==='product'&&current_user_can('tager_manage_products'))wp_update_post(['ID'=>$id,'post_status'=>'publish']);elseif($type==='vendor'&&current_user_can('tager_manage_vendors'))update_user_meta($id,'tager_vendor_status','approved');else wp_die('ليس لديك صلاحية الموافقة.');self::log('quick_approved',['type'=>$type,'id'=>$id]);wp_safe_redirect(admin_url('admin.php?page=tager-v31-approvals&saved=1'));exit;}

    public static function notices_page(){
        if(!current_user_can('tager_manage_staff')) wp_die('No permission');
        $notices=(array)get_option(self::OPT_NOTICES,[]);
        echo '<div class="wrap t31"><div class="t31-hero"><div><h1>إعلانات فريق الإدارة</h1><p>انشر تعليمات أو تنبيهات تظهر لأعضاء الإدارة.</p></div><span class="t31-pill">Internal Notices</span></div><div class="t31-layout"><div class="t31-card"><h2>إعلان جديد</h2>';
        echo '<form class="t31-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tager_v31_add_notice">';
        wp_nonce_field('tager_v31_notice');
        echo '<label>العنوان</label><input required name="title"><label>الرسالة</label><textarea required name="message" rows="5"></textarea><label>المستوى</label><select name="level"><option value="info">معلومة</option><option value="warning">تنبيه</option><option value="critical">عاجل</option></select><p><button class="t31-btn t31-primary">نشر الإعلان</button></p></form></div><div class="t31-card"><h2>الإعلانات الحالية</h2>';
        if(!$notices) echo '<p>لا توجد إعلانات.</p>';
        foreach(array_reverse($notices,true) as $key=>$n){
            $delete=wp_nonce_url(admin_url('admin-post.php?action=tager_v31_dismiss_notice&id='.rawurlencode((string)$key)),'tager_v31_dismiss_'.$key);
            echo '<div class="t31-card"><strong>'.esc_html($n['title']??'').'</strong><p>'.esc_html($n['message']??'').'</p><small>'.esc_html($n['time']??'').'</small> <a class="t31-btn t31-red" href="'.esc_url($delete).'">حذف</a></div>';
        }
        echo '</div></div></div>';
    }

    public static function add_notice(){if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v31_notice');$all=(array)get_option(self::OPT_NOTICES,[]);$all[wp_generate_uuid4()]=['title'=>sanitize_text_field($_POST['title']??''),'message'=>sanitize_textarea_field($_POST['message']??''),'level'=>sanitize_key($_POST['level']??'info'),'time'=>current_time('mysql'),'author'=>get_current_user_id()];if(count($all)>30)$all=array_slice($all,-30,null,true);update_option(self::OPT_NOTICES,$all,false);self::log('notice_created',[]);wp_safe_redirect(admin_url('admin.php?page=tager-v31-notices&saved=1'));exit;}
    public static function dismiss_notice(){if(!current_user_can('tager_manage_staff'))wp_die('No permission');$id=sanitize_text_field($_GET['id']??'');check_admin_referer('tager_v31_dismiss_'.$id);$all=(array)get_option(self::OPT_NOTICES,[]);unset($all[$id]);update_option(self::OPT_NOTICES,$all,false);wp_safe_redirect(admin_url('admin.php?page=tager-v31-notices&saved=1'));exit;}
    public static function show_admin_notices(){if(!is_admin()||!is_user_logged_in())return;$u=wp_get_current_user();if(!self::is_admin_team($u))return;foreach((array)get_option(self::OPT_NOTICES,[]) as $n){$class=($n['level']??'info')==='critical'?'notice-error':(($n['level']??'info')==='warning'?'notice-warning':'notice-info');echo '<div class="notice '.$class.'"><p><strong>'.esc_html($n['title']??'إعلان الإدارة').':</strong> '.esc_html($n['message']??'').'</p></div>';}}

    public static function log_page(){
        if(!current_user_can('tager_manage_staff')) wp_die('No permission');
        $log=array_reverse((array)get_option(self::LOG,[]));
        echo '<div class="wrap t31"><div class="t31-hero"><div><h1>سجل V31</h1><p>سجل مستقل لأهم عمليات الصلاحيات والموافقات والمهام.</p></div><span class="t31-pill">Audit Trail</span></div><div class="t31-card"><table class="t31-table"><thead><tr><th>الوقت</th><th>المستخدم</th><th>الإجراء</th><th>التفاصيل</th></tr></thead><tbody>';
        if(!$log) echo '<tr><td colspan="4">لا توجد بيانات.</td></tr>';
        foreach(array_slice($log,0,300) as $x){
            echo '<tr><td>'.esc_html($x['time']??'').'</td><td>'.esc_html($x['user_name']??'').'</td><td>'.esc_html($x['action']??'').'</td><td><code>'.esc_html(wp_json_encode($x['data']??[],JSON_UNESCAPED_UNICODE)).'</code></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    private static function log($action,$data){$u=wp_get_current_user();$log=(array)get_option(self::LOG,[]);$log[]=['time'=>current_time('mysql'),'user_id'=>(int)$u->ID,'user_name'=>$u->display_name,'action'=>sanitize_key($action),'label'=>self::action_label($action),'data'=>$data,'ip'=>sanitize_text_field($_SERVER['REMOTE_ADDR']??'')];if(count($log)>1000)$log=array_slice($log,-1000);update_option(self::LOG,$log,false);}
    private static function action_label($a){$m=['staff_login'=>'تسجيل دخول عضو إدارة','access_policy_updated'=>'تحديث سياسة وصول','task_created'=>'إنشاء مهمة','task_updated'=>'تحديث مهمة','quick_approved'=>'تنفيذ موافقة سريعة','notice_created'=>'نشر إعلان داخلي'];return $m[$a]??$a;}
    private static function status_label($s){return ['open'=>'مفتوحة','in_progress'=>'جاري التنفيذ','done'=>'مكتملة','cancelled'=>'ملغاة'][$s]??$s;}
    private static function priority_label($s){return ['normal'=>'عادية','high'=>'عالية','urgent'=>'عاجلة'][$s]??$s;}
}
Tager_V31_Admin_Command_Center::init();
