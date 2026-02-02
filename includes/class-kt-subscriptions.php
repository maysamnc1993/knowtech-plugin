<?php
/**
 * Subscriptions Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Subscriptions {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook for auto-expire subscriptions
        add_action('kt_check_expired_subscriptions', array($this, 'check_expired_subscriptions'));
        
        // Schedule daily check
        if (!wp_next_scheduled('kt_check_expired_subscriptions')) {
            wp_schedule_event(time(), 'daily', 'kt_check_expired_subscriptions');
        }
    }
    
    /**
     * Get user subscriptions
     */
    public function get_user_subscriptions($user_id) {
        global $wpdb;
        
        $table_subs = $wpdb->prefix . 'kt_subscriptions';
        $table_products = $wpdb->prefix . 'kt_products';
        
        $query = "
            SELECT 
                s.*,
                p.name as product_name,
                p.brand as product_brand,
                p.icon_url as product_icon,
                p.login_url as product_login_url,
                p.login_method as product_login_method
            FROM $table_subs s
            LEFT JOIN $table_products p ON s.product_id = p.id
            WHERE s.user_id = %d
            ORDER BY s.created_at DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $user_id));
    }
    
    /**
     * Get single subscription
     */
    public function get_subscription($sub_id, $user_id = null) {
        global $wpdb;
        
        $table_subs = $wpdb->prefix . 'kt_subscriptions';
        $table_products = $wpdb->prefix . 'kt_products';
        
        $query = "
            SELECT 
                s.*,
                p.name as product_name,
                p.brand as product_brand,
                p.icon_url as product_icon,
                p.login_url as product_login_url,
                p.login_method as product_login_method,
                p.username_selector,
                p.password_selector,
                p.submit_selector,
                p.cookie_domain,
                p.auto_login_script
            FROM $table_subs s
            LEFT JOIN $table_products p ON s.product_id = p.id
            WHERE s.id = %d
        ";
        
        if ($user_id) {
            $query .= " AND s.user_id = %d";
            return $wpdb->get_row($wpdb->prepare($query, $sub_id, $user_id));
        }
        
        return $wpdb->get_row($wpdb->prepare($query, $sub_id));
    }
    
    /**
     * Get subscription credentials
     */
    public function get_subscription_credentials($sub_id, $user_id) {
        $subscription = $this->get_subscription($sub_id, $user_id);
        
        if (!$subscription) {
            return false;
        }
        
        // Check if subscription is active
        if ($subscription->status !== 'active') {
            return false;
        }
        
        // Check if expired
        if (KT_Core::is_subscription_expired($subscription->end_date)) {
            return false;
        }
        
        // Check if auto-login is enabled
        if (!$subscription->auto_login_enabled) {
            return false;
        }
        
        // Get Pool Account if assigned
        $cookies = array();
        $pool_login_url = null;
        
        if (!empty($subscription->pool_account_id)) {
            $pool_account = KT_Account_Pool::instance()->get_account($subscription->pool_account_id);
            
            if ($pool_account && !empty($pool_account->session_cookies)) {
                $cookies = json_decode($pool_account->session_cookies, true);
                if (!is_array($cookies)) {
                    $cookies = array();
                }
                
                // Use pool account's login URL if available
                if (!empty($pool_account->login_url)) {
                    $pool_login_url = $pool_account->login_url;
                }
            }
        }
        
        // Decrypt password
        $password = KT_Core::decrypt_password($subscription->service_password);
        
        // Determine login method based on cookies availability
        $login_method = !empty($cookies) ? 'cookie' : $subscription->product_login_method;
        
        return array(
            'product_id' => (int) $subscription->product_id,
            'product_name' => $subscription->product_name,
            'product_brand' => $subscription->product_brand,
            'login_url' => $pool_login_url ?: $subscription->product_login_url,
            'login_method' => $login_method,
            'cookies' => $cookies,
            'username' => $subscription->service_username,
            'password' => $password,
            'selectors' => array(
                'username' => $subscription->username_selector,
                'password' => $subscription->password_selector,
                'submit' => $subscription->submit_selector
            ),
            'cookie_domain' => $subscription->cookie_domain,
            'auto_login_script' => $subscription->auto_login_script
        );
    }
    
    /**
     * Create subscription
     */
    public function create_subscription($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        
        // Encrypt password if provided
        if (!empty($data['service_password'])) {
            $data['service_password'] = KT_Core::encrypt_password($data['service_password']);
        }
        
        // Calculate end date
        if (empty($data['end_date']) && !empty($data['start_date']) && !empty($data['duration_months'])) {
            $start = new DateTime($data['start_date']);
            $start->modify('+' . $data['duration_months'] . ' months');
            $data['end_date'] = $start->format('Y-m-d H:i:s');
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update subscription
     */
    public function update_subscription($sub_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        
        // Encrypt password if being updated
        if (isset($data['service_password']) && !empty($data['service_password'])) {
            $data['service_password'] = KT_Core::encrypt_password($data['service_password']);
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $sub_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Delete subscription
     */
    public function delete_subscription($sub_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        
        return $wpdb->delete($table, array('id' => $sub_id));
    }
    
    /**
     * Toggle auto-login
     */
    public function toggle_auto_login($sub_id, $user_id, $enabled) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        
        return $wpdb->update(
            $table,
            array('auto_login_enabled' => $enabled ? 1 : 0),
            array(
                'id' => $sub_id,
                'user_id' => $user_id
            )
        );
    }
    
    /**
     * Check and mark expired subscriptions
     */
    public function check_expired_subscriptions() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        $now = current_time('mysql');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table 
             SET status = 'expired' 
             WHERE status = 'active' 
             AND end_date < %s",
            $now
        ));
    }
    
    /**
     * Get subscription stats
     */
    public function get_stats($user_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_subscriptions';
        $where = $user_id ? $wpdb->prepare("WHERE user_id = %d", $user_id) : "";
        
        $stats = array(
            'total' => 0,
            'active' => 0,
            'expired' => 0,
            'expiring_soon' => 0 // Expires in 7 days
        );
        
        $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
        $stats['active'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where AND status = 'active'");
        $stats['expired'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where AND status = 'expired'");
        
        $seven_days = date('Y-m-d H:i:s', strtotime('+7 days'));
        $stats['expiring_soon'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table $where AND status = 'active' AND end_date <= %s",
            $seven_days
        ));
        
        return $stats;
    }
}
