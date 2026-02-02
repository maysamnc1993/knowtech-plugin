<?php
/**
 * Admin Panel Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('wp_ajax_kt_get_subscription_details', array($this, 'ajax_get_subscription_details'));
    }
    
    /**
     * Add menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'KnowTech اشتراک‌ها',
            'اشتراک‌ها',
            'manage_options',
            'kt-subscriptions',
            array($this, 'dashboard_page'),
            'dashicons-tickets-alt',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'kt-subscriptions',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'kt-subscriptions',
            array($this, 'dashboard_page')
        );
        
        // Subscriptions list
        add_submenu_page(
            'kt-subscriptions',
            'لیست اشتراک‌ها',
            'همه اشتراک‌ها',
            'manage_options',
            'kt-subscriptions-list',
            array($this, 'subscriptions_list_page')
        );
        
        // Account Pool
        add_submenu_page(
            'kt-subscriptions',
            'مدیریت اکانت‌ها',
            'Account Pool',
            'manage_options',
            'kt-account-pool',
            array($this, 'account_pool_page')
        );
        
        // Products
        add_submenu_page(
            'kt-subscriptions',
            'محصولات',
            'محصولات',
            'manage_options',
            'kt-products',
            array($this, 'products_page')
        );
        
        // Cookie Manager
        add_submenu_page(
            'kt-subscriptions',
            'مدیریت Cookie ها',
            '🔐 Cookie Manager',
            'manage_options',
            'kt-cookie-manager',
            array($this, 'cookie_manager_page')
        );
        
        // Settings
        add_submenu_page(
            'kt-subscriptions',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'kt-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'kt-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'kt-admin-css',
            KT_SUBS_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            KT_SUBS_VERSION
        );
        
        wp_enqueue_script(
            'kt-admin-js',
            KT_SUBS_PLUGIN_URL . 'admin/assets/js/admin.js',
            array('jquery'),
            KT_SUBS_VERSION,
            true
        );
        
        wp_localize_script('kt-admin-js', 'ktAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kt_admin_nonce')
        ));
    }
    
    /**
     * Handle actions
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'kt-') === false) {
            return;
        }
        
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        // Actions that don't need nonce (just viewing)
        $safe_actions = array('edit', 'add', 'view');
        if (in_array($action, $safe_actions)) {
            return;
        }
        
        // Actions that need nonce verification
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'kt_action')) {
            wp_die('Security check failed');
        }
        
        switch ($action) {
            case 'delete_subscription':
                $this->delete_subscription();
                break;
            case 'delete_product':
                $this->delete_product();
                break;
            case 'delete_account':
                $this->delete_account();
                break;
        }
    }
    
    /**
     * Delete account
     */
    private function delete_account() {
        if (!isset($_GET['account_id'])) {
            return;
        }
        
        $account_id = intval($_GET['account_id']);
        $result = KT_Account_Pool::instance()->delete_account($account_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=kt-account-pool&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=kt-account-pool&deleted=1'));
        }
        exit;
    }
    
    /**
     * Delete subscription
     */
    private function delete_subscription() {
        if (!isset($_GET['sub_id'])) {
            return;
        }
        
        $sub_id = intval($_GET['sub_id']);
        KT_Subscriptions::instance()->delete_subscription($sub_id);
        
        wp_redirect(admin_url('admin.php?page=kt-subscriptions-list&deleted=1'));
        exit;
    }
    
    /**
     * Delete product
     */
    private function delete_product() {
        if (!isset($_GET['product_id'])) {
            return;
        }
        
        $product_id = intval($_GET['product_id']);
        $result = KT_Products::instance()->delete_product($product_id);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=kt-products&error=' . urlencode($result->get_error_message())));
        } else {
            wp_redirect(admin_url('admin.php?page=kt-products&deleted=1'));
        }
        exit;
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include KT_SUBS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Subscriptions list page
     */
    public function subscriptions_list_page() {
        include KT_SUBS_PLUGIN_DIR . 'admin/views/subscriptions.php';
    }
    
    /**
     * Products page
     */
    public function products_page() {
        include KT_SUBS_PLUGIN_DIR . 'admin/views/products.php';
    }
    
    /**
     * Account Pool page
     */
    public function account_pool_page() {
        include KT_SUBS_PLUGIN_DIR . 'admin/views/account-pool.php';
    }
    
    /**
     * Cookie Manager page
     */
    public function cookie_manager_page() {
        include KT_SUBS_PLUGIN_DIR . 'admin/views/cookie-manager.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Save settings
        if (isset($_POST['kt_save_settings'])) {
            check_admin_referer('kt_settings');
            
            update_option('kt_sms_api_key', sanitize_text_field($_POST['kt_sms_api_key']));
            update_option('kt_sms_template', sanitize_text_field($_POST['kt_sms_template']));
            update_option('kt_extension_enabled', isset($_POST['kt_extension_enabled']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }
        
        include KT_SUBS_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * AJAX: Get subscription details
     */
    public function ajax_get_subscription_details() {
        check_ajax_referer('kt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }
        
        $sub_id = intval($_POST['sub_id']);
        $subscription = KT_Subscriptions::instance()->get_subscription($sub_id);
        
        if (!$subscription) {
            wp_send_json_error(array('message' => 'اشتراک یافت نشد'));
        }
        
        $user = get_userdata($subscription->user_id);
        $progress = KT_Core::calculate_progress($subscription->start_date, $subscription->end_date);
        $expired = KT_Core::is_subscription_expired($subscription->end_date);
        
        // Decrypt password for display
        $password = KT_Core::decrypt_password($subscription->service_password);
        
        ob_start();
        ?>
        <h2>جزئیات اشتراک #<?php echo $subscription->id; ?></h2>
        
        <table class="widefat">
            <tr>
                <th width="30%">کاربر:</th>
                <td>
                    <?php if ($user): ?>
                        <strong><?php echo esc_html($user->display_name); ?></strong><br>
                        <small><?php echo esc_html($user->user_email); ?></small>
                    <?php else: ?>
                        کاربر حذف شده
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>سرویس:</th>
                <td><strong><?php echo esc_html($subscription->product_name); ?></strong></td>
            </tr>
            <tr>
                <th>برند:</th>
                <td><?php echo esc_html($subscription->product_brand); ?></td>
            </tr>
            <tr>
                <th>تاریخ شروع:</th>
                <td><?php echo KT_Core::format_persian_date($subscription->start_date); ?></td>
            </tr>
            <tr>
                <th>تاریخ پایان:</th>
                <td><?php echo KT_Core::format_persian_date($subscription->end_date); ?></td>
            </tr>
            <tr>
                <th>مدت اشتراک:</th>
                <td><?php echo $subscription->duration_months; ?> ماه</td>
            </tr>
            <tr>
                <th>وضعیت:</th>
                <td>
                    <?php if ($expired): ?>
                        <span class="kt-badge kt-badge-danger">منقضی شده</span>
                    <?php elseif ($subscription->status === 'active'): ?>
                        <span class="kt-badge kt-badge-success">فعال</span>
                    <?php else: ?>
                        <span class="kt-badge kt-badge-warning"><?php echo $subscription->status; ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>پیشرفت:</th>
                <td>
                    <div class="kt-progress-bar">
                        <div class="kt-progress-fill <?php echo $progress >= 95 ? 'kt-progress-danger' : ''; ?>" 
                             style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <?php echo $progress; ?>%
                </td>
            </tr>
            <tr>
                <th>Auto-Login:</th>
                <td>
                    <?php if ($subscription->auto_login_enabled): ?>
                        <span style="color:green;">✓ فعال</span>
                    <?php else: ?>
                        <span style="color:red;">✗ غیرفعال</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($subscription->service_username): ?>
            <tr>
                <th>Username:</th>
                <td><code><?php echo esc_html($subscription->service_username); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ($password): ?>
            <tr>
                <th>Password:</th>
                <td><code><?php echo esc_html($password); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ($subscription->order_id): ?>
            <tr>
                <th>سفارش:</th>
                <td>
                    <a href="<?php echo admin_url('post.php?post=' . $subscription->order_id . '&action=edit'); ?>">
                        #<?php echo $subscription->order_id; ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>تاریخ ایجاد:</th>
                <td><?php echo date_i18n('Y/m/d H:i', strtotime($subscription->created_at)); ?></td>
            </tr>
        </table>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}
