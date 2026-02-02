<?php
/**
 * Authentication Class - Mobile OTP Login
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Auth {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook for OTP cleanup
        add_action('wp_loaded', array($this, 'cleanup_expired_otps'));
    }
    
    /**
     * Send OTP to phone number
     */
    public function send_otp($phone) {
        // Validate phone
        if (!$this->validate_phone($phone)) {
            return array(
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است'
            );
        }
        
        // Check if user exists
        $user = $this->get_user_by_phone($phone);
        
        if (!$user) {
            return array(
                'success' => false,
                'message' => 'کاربری با این شماره موبایل یافت نشد'
            );
        }
        
        // Generate OTP
        $otp = $this->generate_otp();
        
        // Store OTP in transient (10 minutes)
        set_transient('kt_otp_' . $phone, array(
            'otp' => $otp,
            'user_id' => $user->ID,
            'attempts' => 0
        ), 600);
        
        // Send SMS
        $sms_result = $this->send_sms($phone, $otp);
        
        if (!$sms_result['success']) {
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $sms_result['message']
            );
        }
        
        return array(
            'success' => true,
            'message' => 'کد تأیید به شماره ' . $phone . ' ارسال شد'
        );
    }
    
    /**
     * Verify OTP
     */
    public function verify_otp($phone, $otp) {
        $stored = get_transient('kt_otp_' . $phone);
        
        if (!$stored) {
            return array(
                'success' => false,
                'message' => 'کد تأیید منقضی شده است'
            );
        }
        
        // Check attempts
        if ($stored['attempts'] >= 3) {
            delete_transient('kt_otp_' . $phone);
            return array(
                'success' => false,
                'message' => 'تعداد تلاش‌های شما به حداکثر رسید'
            );
        }
        
        // Verify OTP
        if ($stored['otp'] !== $otp) {
            $stored['attempts']++;
            set_transient('kt_otp_' . $phone, $stored, 600);
            
            return array(
                'success' => false,
                'message' => 'کد تأیید نامعتبر است'
            );
        }
        
        // Login user
        $user = get_user_by('ID', $stored['user_id']);
        
        if (!$user) {
            return array(
                'success' => false,
                'message' => 'کاربر یافت نشد'
            );
        }
        
        // Generate auth token
        $token = $this->generate_auth_token($user->ID);
        
        // Delete OTP
        delete_transient('kt_otp_' . $phone);
        
        return array(
            'success' => true,
            'message' => 'ورود موفقیت‌آمیز بود',
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'phone' => get_user_meta($user->ID, 'billing_phone', true),
                'email' => $user->user_email
            )
        );
    }
    
    /**
     * Validate auth token
     */
    public function validate_token($token) {
        $stored = get_transient('kt_token_' . $token);
        
        if (!$stored) {
            return false;
        }
        
        return $stored['user_id'];
    }
    
    /**
     * Generate OTP
     */
    private function generate_otp() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate auth token
     */
    private function generate_auth_token($user_id) {
        $token = bin2hex(random_bytes(32));
        
        set_transient('kt_token_' . $token, array(
            'user_id' => $user_id,
            'created_at' => current_time('timestamp')
        ), 30 * DAY_IN_SECONDS); // 30 days
        
        return $token;
    }
    
    /**
     * Get user by phone
     */
    private function get_user_by_phone($phone) {
        global $wpdb;
        
        // Search in user meta
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'billing_phone' 
             AND meta_value = %s 
             LIMIT 1",
            $phone
        ));
        
        if (!$user_id) {
            // Also try with user_login
            $user = get_user_by('login', $phone);
            if ($user) {
                return $user;
            }
            return false;
        }
        
        return get_user_by('ID', $user_id);
    }
    
    /**
     * Validate phone number
     */
    private function validate_phone($phone) {
        // Iranian mobile format: 09xxxxxxxxx
        return preg_match('/^09\d{9}$/', $phone);
    }
    
    /**
     * Send SMS via Kaveh Negar
     */
    private function send_sms($phone, $otp) {
        $api_key = get_option('kt_sms_api_key');
        $template = get_option('kt_sms_template', 'verify');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'API Key پیامک تنظیم نشده است'
            );
        }
        
        // Use Kaveh Negar API
        $url = 'https://api.kavenegar.com/v1/' . $api_key . '/verify/lookup.json';
        
        $params = array(
            'receptor' => $phone,
            'token' => $otp,
            'template' => $template
        );
        
        $response = wp_remote_post($url, array(
            'body' => $params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['return']['status']) && $body['return']['status'] == 200) {
            return array('success' => true);
        }
        
        return array(
            'success' => false,
            'message' => $body['return']['message'] ?? 'خطای ناشناخته'
        );
    }
    
    /**
     * Cleanup expired OTPs
     */
    public function cleanup_expired_otps() {
        // Transients auto-expire, no cleanup needed
    }
}
