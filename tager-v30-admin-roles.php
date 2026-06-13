<?php
/**
 * Plugin Name: Tager V30 Admin Team & Permissions
 * Description: Multi-admin management, granular roles, permission-aware Tager admin access, audit log and staff dashboard.
 * Version: 30.0.0
 */
if (!defined('ABSPATH')) exit;

final class Tager_V30_Admin_Roles {
    const OPT = 'tager_v30_admin_settings';
    const LOG = 'tager_admin_audit';

    private static $roles = [
        'tager_platform_manager' => [
            'label' => 'مدير المنصة',
            'caps' => ['tager_view_dashboard','tager_manage_staff','tager_manage_vendors','tager_manage_products','tager_manage_orders','tager_manage_customers','tager_manage_finance','tager_manage_shipping','tager_manage_support','tager_manage_marketing','tager_manage_settings','tager_view_reports']
        ],
        'tager_operations_manager' => [
            'label' => 'مدير العمليات',
            'caps' => ['tager_view_dashboard','tager_manage_vendors','tager_manage_products','tager_manage_orders','tager_manage_customers','tager_manage_shipping','tager_manage_support','tager_view_reports']
        ],
        'tager_vendor_manager' => [
            'label' => 'مسؤول الموردين',
            'caps' => ['tager_view_dashboard','tager_manage_vendors','tager_manage_support']
        ],
        'tager_catalog_manager' => [
            'label' => 'مسؤول المنتجات والكتالوج',
            'caps' => ['tager_view_dashboard','tager_manage_products','tager_manage_marketing']
        ],
        'tager_order_manager' => [
            'label' => 'مسؤول الطلبات',
            'caps' => ['tager_view_dashboard','tager_manage_orders','tager_manage_customers','tager_manage_shipping','tager_manage_support']
        ],
        'tager_finance_manager' => [
            'label' => 'المسؤول المالي',
            'caps' => ['tager_view_dashboard','tager_manage_finance','tager_view_reports']
        ],
        'tager_support_agent' => [
            'label' => 'موظف خدمة العملاء',
            'caps' => ['tager_view_dashboard','tager_manage_support','tager_manage_customers','tager_manage_orders']
        ],
        'tager_marketing_manager' => [
            'label' => 'مسؤول التسويق والعروض',
            'caps' => ['tager_view_dashboard','tager_manage_marketing','tager_manage_products','tager_view_reports']
        ],
        'tager_readonly_auditor' => [
            'label' => 'مراجع للقراءة فقط',
            'caps' => ['tager_view_dashboard','tager_view_reports']
        ],
    ];

    public static function init() {
        add_action('init', [__CLASS__, 'install_roles'], 2);
        add_filter('user_has_cap', [__CLASS__, 'virtual_manage_options'], 20, 4);
        add_action('admin_init', [__CLASS__, 'guard_admin']);
        add_action('admin_menu', [__CLASS__, 'menu'], 99);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_post_tager_v30_create_staff', [__CLASS__, 'create_staff']);
        add_action('admin_post_tager_v30_update_staff', [__CLASS__, 'update_staff']);
        add_action('admin_post_tager_v30_delete_staff', [__CLASS__, 'delete_staff']);
        add_action('admin_post_tager_v30_toggle_staff', [__CLASS__, 'toggle_staff']);
        add_action('admin_post_tager_v30_reset_password', [__CLASS__, 'reset_password']);
        add_action('wp_login', [__CLASS__, 'log_login'], 10, 2);
        add_action('wp_login_failed', [__CLASS__, 'log_failed_login']);
        add_filter('login_redirect', [__CLASS__, 'login_redirect'], 50, 3);
        add_action('admin_bar_menu', [__CLASS__, 'admin_bar'], 100);
    }

    public static function install_roles() {
        foreach (self::$roles as $key => $data) {
            $caps = ['read' => true, 'upload_files' => true];
            foreach ($data['caps'] as $cap) $caps[$cap] = true;
            $role = get_role($key);
            if (!$role) $role = add_role($key, $data['label'], $caps);
            if ($role) {
                foreach ($caps as $cap => $grant) $role->add_cap($cap, $grant);
                $role->remove_cap('manage_options');
                $role->remove_cap('edit_plugins');
                $role->remove_cap('install_plugins');
                $role->remove_cap('edit_themes');
                $role->remove_cap('delete_users');
            }
        }
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::all_caps() as $cap) $admin->add_cap($cap);
        }
    }

    private static function all_caps() {
        $caps = [];
        foreach (self::$roles as $role) foreach ($role['caps'] as $cap) $caps[$cap] = $cap;
        return array_values($caps);
    }

    private static function is_staff($user = null) {
        $user = $user ?: wp_get_current_user();
        if (!$user || !$user->exists()) return false;
        foreach (array_keys(self::$roles) as $role) if (in_array($role, (array)$user->roles, true)) return true;
        return false;
    }

    private static function request_page() {
        return sanitize_key($_REQUEST['page'] ?? '');
    }

    private static function request_action() {
        return sanitize_key($_REQUEST['action'] ?? '');
    }

    private static function required_cap_for_page($page = '', $tab = '') {
        $page = $page ?: self::request_page();
        $tab = $tab ?: sanitize_key($_REQUEST['tab'] ?? '');
        if ($page === 'tager-v30-staff' || $page === 'tager-v30-audit' || $page === 'tager-v30-permissions') return 'tager_manage_staff';
        if ($page === 'tager-control') {
            $map = ['vendors'=>'tager_manage_vendors','products'=>'tager_manage_products','orders'=>'tager_manage_orders','customers'=>'tager_manage_customers','settings'=>'tager_manage_settings','dashboard'=>'tager_view_dashboard'];
            return $map[$tab ?: 'dashboard'] ?? 'tager_view_dashboard';
        }
        if (preg_match('/vendor|kyc|supplier|minimum/', $page)) return 'tager_manage_vendors';
        if (preg_match('/product|catalog|content/', $page)) return 'tager_manage_products';
        if (preg_match('/order|operations|return|dispute|shipment/', $page)) return 'tager_manage_orders';
        if (preg_match('/payment|withdraw|finance|shipping|cart-pricing/', $page)) return 'tager_manage_finance';
        if (preg_match('/support|ticket|message/', $page)) return 'tager_manage_support';
        if (preg_match('/campaign|coupon|marketing|offer|promotion/', $page)) return 'tager_manage_marketing';
        if (preg_match('/report|analytics/', $page)) return 'tager_view_reports';
        if (preg_match('/launch|setting|diagnostic|qa|command|setup/', $page)) return 'tager_manage_settings';
        if (strpos($page, 'tager') === 0) return 'tager_view_dashboard';
        return '';
    }

    private static function required_cap_for_action($action) {
        if (!$action || strpos($action, 'tager_') !== 0) return '';
        if (preg_match('/staff|admin_user/', $action)) return 'tager_manage_staff';
        if (preg_match('/vendor|kyc|supplier|minimum/', $action)) return 'tager_manage_vendors';
        if (preg_match('/product|catalog/', $action)) return 'tager_manage_products';
        if (preg_match('/order|return|dispute|shipment/', $action)) return 'tager_manage_orders';
        if (preg_match('/payment|withdraw|shipping|fee/', $action)) return 'tager_manage_finance';
        if (preg_match('/ticket|support|message/', $action)) return 'tager_manage_support';
        if (preg_match('/coupon|campaign|offer|marketing/', $action)) return 'tager_manage_marketing';
        if (preg_match('/setting|repair|launch|save/', $action)) return 'tager_manage_settings';
        return 'tager_view_dashboard';
    }

    public static function virtual_manage_options($allcaps, $caps, $args, $user) {
        if (empty($caps) || !in_array('manage_options', $caps, true) || !self::is_staff($user)) return $allcaps;
        $pagenow = $GLOBALS['pagenow'] ?? '';
        $required = '';
        if ($pagenow === 'admin-post.php') $required = self::required_cap_for_action(self::request_action());
        elseif ($pagenow === 'admin.php') $required = self::required_cap_for_page();
        if ($required && !empty($allcaps[$required])) $allcaps['manage_options'] = true;
        return $allcaps;
    }

    public static function guard_admin() {
        if (!is_admin() || wp_doing_ajax()) return;
        $user = wp_get_current_user();
        if (!self::is_staff($user) || current_user_can('administrator')) return;
        if (get_user_meta($user->ID, 'tager_staff_disabled', true)) {
            wp_logout();
            wp_safe_redirect(wp_login_url()); exit;
        }
        $pagenow = $GLOBALS['pagenow'] ?? '';
        if (in_array($pagenow, ['profile.php','admin-ajax.php','async-upload.php','media-upload.php'], true)) return;
        if ($pagenow === 'admin-post.php') {
            $cap = self::required_cap_for_action(self::request_action());
            if ($cap && current_user_can($cap)) return;
            wp_die('ليس لديك صلاحية لتنفيذ هذا الإجراء.');
        }
        if ($pagenow === 'admin.php') {
            $page = self::request_page();
            $cap = self::required_cap_for_page($page);
            if ($page && strpos($page, 'tager') === 0 && $cap && current_user_can($cap)) return;
        }
        wp_safe_redirect(self::staff_home($user)); exit;
    }

    private static function staff_home($user = null) {
        $user = $user ?: wp_get_current_user();
        if (user_can($user, 'tager_view_dashboard')) return admin_url('admin.php?page=tager-control');
        return home_url('/');
    }

    public static function login_redirect($redirect, $requested, $user) {
        if ($user instanceof WP_User && self::is_staff($user)) return self::staff_home($user);
        return $redirect;
    }

    public static function menu() {
        add_submenu_page('tager-control', 'فريق الإدارة والصلاحيات', 'فريق الإدارة', 'tager_manage_staff', 'tager-v30-staff', [__CLASS__, 'staff_page']);
        add_submenu_page('tager-control', 'مصفوفة الصلاحيات', 'مصفوفة الصلاحيات', 'tager_manage_staff', 'tager-v30-permissions', [__CLASS__, 'permissions_page']);
        add_submenu_page('tager-control', 'سجل الإدارة', 'سجل الإدارة', 'tager_manage_staff', 'tager-v30-audit', [__CLASS__, 'audit_page']);
        if (self::is_staff()) self::hide_unavailable_menus();
    }

    private static function hide_unavailable_menus() {
        global $submenu;
        $allowed = [];
        foreach ((array)($submenu['tager-control'] ?? []) as $item) {
            $slug = $item[2] ?? '';
            $cap = self::required_cap_for_page($slug);
            if ($cap && current_user_can($cap)) $allowed[] = $item;
        }
        if (isset($submenu['tager-control'])) $submenu['tager-control'] = $allowed;
        global $menu;
        foreach ((array)$menu as $i => $item) {
            $slug = $item[2] ?? '';
            if ($slug !== 'tager-control' && strpos($slug, 'tager-') !== 0 && $slug !== 'profile.php') unset($menu[$i]);
        }
    }

    public static function assets($hook) {
        if (strpos((string)self::request_page(), 'tager-v30') !== 0) return;
        wp_register_style('tager-v30-admin', false);
        wp_enqueue_style('tager-v30-admin');
        wp_add_inline_style('tager-v30-admin', self::css());
    }

    private static function css() { return '
    .t30-wrap{direction:rtl;max-width:1280px}.t30-hero{background:linear-gradient(135deg,#0f3d2e,#176b4d);color:#fff;padding:28px;border-radius:18px;margin:20px 0;display:flex;justify-content:space-between;gap:20px;align-items:center}.t30-hero h1{color:#fff;margin:0 0 8px}.t30-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0}.t30-stat,.t30-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(15,61,46,.06)}.t30-stat strong{display:block;font-size:28px;color:#0f3d2e}.t30-layout{display:grid;grid-template-columns:380px 1fr;gap:18px}.t30-form label{display:block;font-weight:700;margin:12px 0 6px}.t30-form input,.t30-form select{width:100%;min-height:42px}.t30-btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;text-decoration:none}.t30-primary{background:#0f6b4f;color:#fff}.t30-danger{background:#b42318;color:#fff}.t30-muted{background:#f2f4f7;color:#344054}.t30-table{width:100%;border-collapse:collapse}.t30-table th,.t30-table td{padding:12px;border-bottom:1px solid #eaecf0;text-align:right;vertical-align:top}.t30-badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#ecfdf3;color:#027a48;font-weight:700;font-size:12px}.t30-off{background:#fef3f2;color:#b42318}.t30-caps{display:flex;flex-wrap:wrap;gap:6px}.t30-cap{padding:4px 8px;border-radius:7px;background:#f2f4f7;font-size:12px}.t30-notice{padding:12px 16px;border-radius:10px;background:#ecfdf3;color:#027a48;margin:12px 0}@media(max-width:900px){.t30-grid{grid-template-columns:repeat(2,1fr)}.t30-layout{grid-template-columns:1fr}.t30-hero{display:block}}'; }

    private static function role_options($selected = '') {
        $out = '';
        foreach (self::$roles as $key => $data) $out .= '<option value="'.esc_attr($key).'" '.selected($selected,$key,false).'>'.esc_html($data['label']).'</option>';
        return $out;
    }

    public static function staff_page() {
        if (!current_user_can('tager_manage_staff')) wp_die('No permission');
        $edit_id = absint($_GET['edit'] ?? 0); $edit = $edit_id ? get_userdata($edit_id) : null;
        $staff = get_users(['role__in'=>array_keys(self::$roles),'orderby'=>'registered','order'=>'DESC']);
        $disabled = 0; foreach ($staff as $u) if (get_user_meta($u->ID,'tager_staff_disabled',true)) $disabled++;
        ?>
        <div class="wrap t30-wrap">
          <div class="t30-hero"><div><h1>فريق الإدارة والصلاحيات</h1><p>أضف أكثر من مدير أو موظف، وحدد لكل شخص صلاحياته ومسؤوليته دون منحه تحكمًا كاملًا في WordPress.</p></div><span class="t30-badge">Tager V30</span></div>
          <?php if(!empty($_GET['updated'])) echo '<div class="t30-notice">تم حفظ بيانات فريق الإدارة بنجاح.</div>'; ?>
          <div class="t30-grid"><div class="t30-stat"><strong><?php echo count($staff); ?></strong>إجمالي فريق الإدارة</div><div class="t30-stat"><strong><?php echo max(0,count($staff)-$disabled); ?></strong>حسابات فعالة</div><div class="t30-stat"><strong><?php echo $disabled; ?></strong>حسابات موقوفة</div><div class="t30-stat"><strong><?php echo count(self::$roles); ?></strong>مستويات صلاحيات</div></div>
          <div class="t30-layout">
            <div class="t30-card">
              <h2><?php echo $edit ? 'تعديل عضو الإدارة' : 'إضافة عضو إدارة جديد'; ?></h2>
              <form class="t30-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo $edit ? 'tager_v30_update_staff' : 'tager_v30_create_staff'; ?>">
                <?php if($edit) echo '<input type="hidden" name="user_id" value="'.(int)$edit->ID.'">'; ?>
                <?php wp_nonce_field($edit?'tager_v30_update_'.$edit_id:'tager_v30_create'); ?>
                <label>الاسم الكامل</label><input required name="display_name" value="<?php echo esc_attr($edit?$edit->display_name:''); ?>">
                <label>رقم الهاتف</label><input name="phone" placeholder="01012345678" value="<?php echo esc_attr($edit?get_user_meta($edit->ID,'tager_phone',true):''); ?>">
                <label>البريد الإلكتروني (اختياري)</label><input type="email" name="email" value="<?php echo esc_attr($edit?$edit->user_email:''); ?>">
                <label>الدور والصلاحيات</label><select required name="role"><?php echo self::role_options($edit?self::staff_role($edit):''); ?></select>
                <label><?php echo $edit?'كلمة مرور جديدة (اتركها فارغة دون تغيير)':'كلمة المرور'; ?></label><input <?php echo $edit?'':'required'; ?> minlength="8" type="password" name="password">
                <p><button class="t30-btn t30-primary"><?php echo $edit?'حفظ التعديلات':'إنشاء الحساب'; ?></button><?php if($edit) echo ' <a class="t30-btn t30-muted" href="'.esc_url(admin_url('admin.php?page=tager-v30-staff')).'">إلغاء</a>'; ?></p>
              </form>
            </div>
            <div class="t30-card"><h2>أعضاء فريق الإدارة</h2><div style="overflow:auto"><table class="t30-table"><thead><tr><th>المستخدم</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th>الإجراءات</th></tr></thead><tbody>
            <?php if(!$staff) echo '<tr><td colspan="5">لا يوجد أعضاء إدارة إضافيون بعد.</td></tr>'; foreach($staff as $u): $role=self::staff_role($u); $off=get_user_meta($u->ID,'tager_staff_disabled',true); ?>
              <tr><td><strong><?php echo esc_html($u->display_name); ?></strong><br><?php echo esc_html(get_user_meta($u->ID,'tager_phone',true)); ?><br><small><?php echo esc_html($u->user_email); ?></small></td><td><?php echo esc_html(self::$roles[$role]['label']??$role); ?><div class="t30-caps"><?php foreach(array_slice(self::$roles[$role]['caps']??[],0,4) as $c) echo '<span class="t30-cap">'.esc_html(self::cap_label($c)).'</span>'; ?></div></td><td><span class="t30-badge <?php echo $off?'t30-off':''; ?>"><?php echo $off?'موقوف':'فعال'; ?></span></td><td><?php echo esc_html(get_user_meta($u->ID,'tager_last_login',true)?:'لم يسجل الدخول'); ?></td><td><a class="t30-btn t30-muted" href="<?php echo esc_url(admin_url('admin.php?page=tager-v30-staff&edit='.$u->ID)); ?>">تعديل</a> <?php echo self::action_link('tager_v30_toggle_staff',$u->ID,$off?'تفعيل':'إيقاف','toggle'); ?> <?php echo self::action_link('tager_v30_reset_password',$u->ID,'تغيير كلمة المرور','reset'); ?> <?php if($u->ID!==get_current_user_id()) echo self::action_link('tager_v30_delete_staff',$u->ID,'حذف','delete',true); ?></td></tr>
            <?php endforeach; ?></tbody></table></div></div>
          </div>
        </div><?php
    }

    private static function action_link($action,$id,$label,$nonce,$danger=false){
        $url=wp_nonce_url(admin_url('admin-post.php?action='.$action.'&user_id='.$id),'tager_v30_'.$nonce.'_'.$id);
        return '<a class="t30-btn '.($danger?'t30-danger':'t30-muted').'" '.($danger?'onclick="return confirm(\'تأكيد حذف الحساب؟\')"':'').' href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }

    private static function staff_role($user){ foreach(array_keys(self::$roles) as $role) if(in_array($role,(array)$user->roles,true)) return $role; return ''; }
    private static function cap_label($cap){$map=['tager_view_dashboard'=>'لوحة المتابعة','tager_manage_staff'=>'إدارة الفريق','tager_manage_vendors'=>'الموردون','tager_manage_products'=>'المنتجات','tager_manage_orders'=>'الطلبات','tager_manage_customers'=>'العملاء','tager_manage_finance'=>'المالية','tager_manage_shipping'=>'الشحن','tager_manage_support'=>'الدعم','tager_manage_marketing'=>'التسويق','tager_manage_settings'=>'الإعدادات','tager_view_reports'=>'التقارير'];return $map[$cap]??$cap;}

    public static function permissions_page(){
        if(!current_user_can('tager_manage_staff'))wp_die('No permission'); ?>
        <div class="wrap t30-wrap"><div class="t30-hero"><div><h1>مصفوفة الأدوار والصلاحيات</h1><p>مرجع واضح لما يستطيع كل دور الوصول إليه داخل إدارة تاجر.</p></div></div><div class="t30-card" style="overflow:auto"><table class="t30-table"><thead><tr><th>الدور</th><?php foreach(self::all_caps() as $c)echo '<th>'.esc_html(self::cap_label($c)).'</th>'; ?></tr></thead><tbody><?php foreach(self::$roles as $r): ?><tr><th><?php echo esc_html($r['label']); ?></th><?php foreach(self::all_caps() as $c)echo '<td>'.(in_array($c,$r['caps'],true)?'✅':'—').'</td>'; ?></tr><?php endforeach; ?></tbody></table></div></div><?php
    }

    public static function audit_page(){
        if(!current_user_can('tager_manage_staff'))wp_die('No permission'); $logs=get_option(self::LOG,[]); ?>
        <div class="wrap t30-wrap"><div class="t30-hero"><div><h1>سجل إجراءات الإدارة</h1><p>يعرض آخر 250 إجراء متعلقًا بحسابات فريق الإدارة وتسجيل الدخول.</p></div></div><div class="t30-card" style="overflow:auto"><table class="t30-table"><thead><tr><th>التاريخ</th><th>المستخدم</th><th>الإجراء</th><th>التفاصيل</th><th>IP</th></tr></thead><tbody><?php if(!$logs)echo '<tr><td colspan="5">لا توجد سجلات بعد.</td></tr>';foreach(array_reverse($logs) as $l)echo '<tr><td>'.esc_html($l['time']).'</td><td>'.esc_html($l['user']).'</td><td>'.esc_html($l['action']).'</td><td>'.esc_html($l['details']).'</td><td>'.esc_html($l['ip']).'</td></tr>'; ?></tbody></table></div></div><?php
    }

    private static function validate_role($role){return isset(self::$roles[$role])?$role:'tager_readonly_auditor';}
    private static function normalized_phone($phone){return preg_replace('/\D+/','',(string)$phone);}
    private static function generated_email($phone){return 'staff-'.($phone?:wp_generate_password(8,false,false)).'@tager.local';}
    private static function username($phone,$email){$base=$phone?:strstr($email,'@',true);$base=sanitize_user($base,true);if(!$base)$base='tagerstaff';$u=$base;$i=1;while(username_exists($u))$u=$base.$i++;return $u;}

    public static function create_staff(){
        if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v30_create');
        $name=sanitize_text_field($_POST['display_name']??'');$phone=self::normalized_phone($_POST['phone']??'');$email=sanitize_email($_POST['email']??'');$password=(string)($_POST['password']??'');$role=self::validate_role(sanitize_key($_POST['role']??''));
        if(!$name||(!$phone&&!$email)||strlen($password)<8)wp_die('أدخل الاسم ورقم الهاتف أو البريد وكلمة مرور لا تقل عن 8 أحرف.');
        if($phone&&self::phone_exists($phone))wp_die('رقم الهاتف مستخدم بالفعل.');if($email&&email_exists($email))wp_die('البريد مستخدم بالفعل.');
        $actual_email=$email?:self::generated_email($phone);$id=wp_create_user(self::username($phone,$actual_email),$password,$actual_email);if(is_wp_error($id))wp_die($id->get_error_message());
        $u=new WP_User($id);$u->set_role($role);wp_update_user(['ID'=>$id,'display_name'=>$name,'first_name'=>$name]);update_user_meta($id,'tager_phone',$phone);update_user_meta($id,'tager_staff_created_by',get_current_user_id());update_user_meta($id,'tager_staff_optional_email',$email?0:1);
        self::log('إنشاء حساب إدارة',$name.' - '.(self::$roles[$role]['label']??$role));wp_safe_redirect(admin_url('admin.php?page=tager-v30-staff&updated=1'));exit;
    }

    public static function update_staff(){
        $id=absint($_POST['user_id']??0);if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v30_update_'.$id);$u=get_userdata($id);if(!$u||!self::is_staff($u))wp_die('Invalid user');
        $name=sanitize_text_field($_POST['display_name']??'');$phone=self::normalized_phone($_POST['phone']??'');$email=sanitize_email($_POST['email']??'');$role=self::validate_role(sanitize_key($_POST['role']??''));$password=(string)($_POST['password']??'');
        if($phone&&self::phone_exists($phone,$id))wp_die('رقم الهاتف مستخدم بالفعل.');if($email&&email_exists($email)!=$id)wp_die('البريد مستخدم بالفعل.');
        $data=['ID'=>$id,'display_name'=>$name];if($email)$data['user_email']=$email;if($password){if(strlen($password)<8)wp_die('كلمة المرور قصيرة.');$data['user_pass']=$password;}wp_update_user($data);$wu=new WP_User($id);$wu->set_role($role);update_user_meta($id,'tager_phone',$phone);
        self::log('تعديل حساب إدارة',$name.' - '.(self::$roles[$role]['label']??$role));wp_safe_redirect(admin_url('admin.php?page=tager-v30-staff&updated=1'));exit;
    }

    public static function toggle_staff(){
        $id=absint($_GET['user_id']??0);if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v30_toggle_'.$id);if($id===get_current_user_id())wp_die('لا يمكنك إيقاف حسابك الحالي.');$old=get_user_meta($id,'tager_staff_disabled',true);update_user_meta($id,'tager_staff_disabled',$old?0:1);self::log($old?'تفعيل حساب إدارة':'إيقاف حساب إدارة','User #'.$id);wp_safe_redirect(admin_url('admin.php?page=tager-v30-staff&updated=1'));exit;
    }
    public static function delete_staff(){
        $id=absint($_GET['user_id']??0);if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v30_delete_'.$id);if($id===get_current_user_id())wp_die('لا يمكنك حذف حسابك الحالي.');$u=get_userdata($id);if(!$u||!self::is_staff($u))wp_die('Invalid user');require_once ABSPATH.'wp-admin/includes/user.php';self::log('حذف حساب إدارة',$u->display_name.' #'.$id);wp_delete_user($id);wp_safe_redirect(admin_url('admin.php?page=tager-v30-staff&updated=1'));exit;
    }
    public static function reset_password(){
        $id=absint($_GET['user_id']??0);if(!current_user_can('tager_manage_staff'))wp_die('No permission');check_admin_referer('tager_v30_reset_'.$id);$u=get_userdata($id);if(!$u||!self::is_staff($u))wp_die('Invalid user');$pass=wp_generate_password(12,true,true);wp_set_password($pass,$id);set_transient('tager_v30_temp_password_'.get_current_user_id(),['user'=>$u->display_name,'pass'=>$pass],300);self::log('إنشاء كلمة مرور مؤقتة',$u->display_name);wp_safe_redirect(admin_url('admin.php?page=tager-v30-staff&updated=1&temp=1'));exit;
    }

    private static function phone_exists($phone,$exclude=0){$users=get_users(['meta_key'=>'tager_phone','meta_value'=>$phone,'fields'=>'ids']);foreach($users as $id)if((int)$id!==$exclude)return true;return false;}
    public static function log_login($login,$user){if(self::is_staff($user)){update_user_meta($user->ID,'tager_last_login',current_time('mysql'));self::log('تسجيل دخول ناجح',$user->display_name,$user);}}
    public static function log_failed_login($login){self::log('محاولة دخول فاشلة',sanitize_text_field($login));}
    private static function log($action,$details='',$user=null){$actor=$user?:wp_get_current_user();$logs=get_option(self::LOG,[]);$logs[]=['time'=>current_time('mysql'),'user'=>$actor&&$actor->exists()?$actor->display_name:'غير معروف','action'=>$action,'details'=>$details,'ip'=>sanitize_text_field($_SERVER['REMOTE_ADDR']??'')];if(count($logs)>250)$logs=array_slice($logs,-250);update_option(self::LOG,$logs,false);}
    public static function admin_bar($bar){if(self::is_staff()){$bar->remove_node('wp-logo');$bar->add_node(['id'=>'tager-team','title'=>'فريق تاجر','href'=>admin_url('admin.php?page=tager-v30-staff')]);}}
}
Tager_V30_Admin_Roles::init();
