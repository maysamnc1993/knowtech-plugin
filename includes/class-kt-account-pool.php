<?php
/**
 * Account Pool Management Class
 * مدیریت Pool اکانت‌ها برای اشتراک‌ها
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Account_Pool {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook for creating table
        add_action('init', array($this, 'maybe_create_table'));
    }
    
    /**
     * Create accounts pool table
     */
    public function maybe_create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kt_account_pool';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) NOT NULL,
                product_name varchar(200) DEFAULT NULL,
                login_url varchar(500) DEFAULT NULL,
                account_username varchar(255) NOT NULL,
                account_password text NOT NULL,
                session_cookies longtext DEFAULT NULL,
                max_users int(11) DEFAULT 1,
                current_users int(11) DEFAULT 0,
                status varchar(20) DEFAULT 'active',
                notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY product_id (product_id),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Add session_cookies column if doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'session_cookies'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN session_cookies LONGTEXT DEFAULT NULL AFTER account_password");
            }
            
            // Add product_name column if doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'product_name'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN product_name VARCHAR(200) DEFAULT NULL AFTER product_id");
            }
            
            // Add login_url column if doesn't exist
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'login_url'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN login_url VARCHAR(500) DEFAULT NULL AFTER product_name");
            }
        }
    }
    
    /**
     * Add account to pool
     */
    public function add_account($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        // Encrypt password
        $encrypted_password = KT_Core::encrypt_password($data['account_password']);
        
        $insert_data = array(
            'product_id' => $data['product_id'],
            'account_username' => sanitize_text_field($data['account_username']),
            'account_password' => $encrypted_password,
            'max_users' => isset($data['max_users']) ? intval($data['max_users']) : 1,
            'current_users' => 0,
            'status' => 'active',
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : ''
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update account
     */
    public function update_account($account_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        $update_data = array();
        
        if (isset($data['account_username'])) {
            $update_data['account_username'] = sanitize_text_field($data['account_username']);
        }
        
        if (isset($data['account_password'])) {
            $update_data['account_password'] = KT_Core::encrypt_password($data['account_password']);
        }
        
        if (isset($data['max_users'])) {
            $update_data['max_users'] = intval($data['max_users']);
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update($table, $update_data, array('id' => $account_id));
    }
    
    /**
     * Delete account
     */
    public function delete_account($account_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        // Check if account is in use
        $account = $this->get_account($account_id);
        if ($account && $account->current_users > 0) {
            return new WP_Error('in_use', 'این اکانت در حال استفاده است و نمی‌توان آن را حذف کرد');
        }
        
        return $wpdb->delete($table, array('id' => $account_id));
    }
    
    /**
     * Get account by ID
     */
    public function get_account($account_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $account_id
        ));
    }
    
    /**
     * Get all accounts for a product
     */
    public function get_product_accounts($product_id, $status = 'active') {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        $where = array('product_id = %d');
        $values = array($product_id);
        
        if ($status) {
            $where[] = 'status = %s';
            $values[] = $status;
        }
        
        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY current_users ASC, id ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }
    
    /**
     * Get available account (with free slots)
     * استراتژی: اولین اکانتی که هنوز ظرفیت داره
     */
    public function get_available_account($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE product_id = %d 
            AND status = 'active' 
            AND current_users < max_users 
            ORDER BY current_users ASC, id ASC 
            LIMIT 1",
            $product_id
        ));
    }
    
    /**
     * Assign account to subscription
     * اختصاص یک اکانت از pool به اشتراک
     */
    public function assign_to_subscription($subscription_id) {
        global $wpdb;
        
        // Get subscription
        $subscription = KT_Subscriptions::instance()->get_subscription($subscription_id);
        if (!$subscription) {
            return new WP_Error('not_found', 'اشتراک یافت نشد');
        }
        
        // Get available account
        $account = $this->get_available_account($subscription->product_id);
        if (!$account) {
            return new WP_Error('no_account', 'اکانت آزادی برای این محصول موجود نیست');
        }
        
        // Decrypt password
        $password = KT_Core::decrypt_password($account->account_password);
        
        // Update subscription with account credentials
        $result = KT_Subscriptions::instance()->update_subscription($subscription_id, array(
            'service_username' => $account->account_username,
            'service_password' => $password,
            'pool_account_id' => $account->id,
            'auto_login_enabled' => 1
        ));
        
        if ($result) {
            // Increment current_users
            $this->increment_usage($account->id);
            return $account;
        }
        
        return false;
    }
    
    /**
     * Release account from subscription
     * آزاد کردن اکانت وقتی اشتراک منقضی شد
     */
    public function release_from_subscription($subscription_id) {
        global $wpdb;
        $table_subs = $wpdb->prefix . 'kt_subscriptions';
        
        // Get pool_account_id from subscription
        $pool_account_id = $wpdb->get_var($wpdb->prepare(
            "SELECT pool_account_id FROM $table_subs WHERE id = %d",
            $subscription_id
        ));
        
        if ($pool_account_id) {
            // Decrement current_users
            $this->decrement_usage($pool_account_id);
            
            // Clear subscription credentials
            KT_Subscriptions::instance()->update_subscription($subscription_id, array(
                'service_username' => '',
                'service_password' => '',
                'pool_account_id' => null,
                'auto_login_enabled' => 0
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Increment account usage
     */
    private function increment_usage($account_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET current_users = current_users + 1 WHERE id = %d",
            $account_id
        ));
    }
    
    /**
     * Decrement account usage
     */
    private function decrement_usage($account_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET current_users = GREATEST(current_users - 1, 0) WHERE id = %d",
            $account_id
        ));
    }
    
    /**
     * Get pool statistics
     */
    public function get_pool_stats($product_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_account_pool';
        
        if ($product_id) {
            $where = $wpdb->prepare("WHERE product_id = %d", $product_id);
        } else {
            $where = "";
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_accounts,
                SUM(max_users) as total_capacity,
                SUM(current_users) as total_used,
                SUM(max_users - current_users) as total_available
            FROM $table 
            $where
            AND status = 'active'
        ");
        
        return $stats;
    }
}
