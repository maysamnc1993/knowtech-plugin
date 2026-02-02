<?php
/**
 * REST API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_API {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        $namespace = 'knowtech/v1';
        
        // Auth endpoints
        register_rest_route($namespace, '/auth/send-otp', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_otp'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/auth/verify-otp', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_otp'),
            'permission_callback' => '__return_true'
        ));
        
        // Subscriptions endpoints
        register_rest_route($namespace, '/subscriptions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscriptions'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscription'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/credentials', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_credentials'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        register_rest_route($namespace, '/subscriptions/(?P<id>\d+)/toggle', array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_subscription'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        // Products endpoints
        register_rest_route($namespace, '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }
    
    /**
     * Send OTP
     */
    public function send_otp($request) {
        $phone = sanitize_text_field($request->get_param('phone'));
        
        if (empty($phone)) {
            return new WP_Error('missing_phone', 'شماره موبایل الزامی است', array('status' => 400));
        }
        
        $result = KT_Auth::instance()->send_otp($phone);
        
        if (!$result['success']) {
            return new WP_Error('otp_failed', $result['message'], array('status' => 400));
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Verify OTP
     */
    public function verify_otp($request) {
        $phone = sanitize_text_field($request->get_param('phone'));
        $otp = sanitize_text_field($request->get_param('otp'));
        
        if (empty($phone) || empty($otp)) {
            return new WP_Error('missing_params', 'شماره موبایل و کد تأیید الزامی است', array('status' => 400));
        }
        
        $result = KT_Auth::instance()->verify_otp($phone, $otp);
        
        if (!$result['success']) {
            return new WP_Error('verify_failed', $result['message'], array('status' => 400));
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Get user subscriptions
     */
    public function get_subscriptions($request) {
        $user_id = $this->get_user_from_token($request);
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'احراز هویت نامعتبر', array('status' => 401));
        }
        
        $subscriptions = KT_Subscriptions::instance()->get_user_subscriptions($user_id);
        
        // Format for extension
        $formatted = array();
        foreach ($subscriptions as $sub) {
            $progress = KT_Core::calculate_progress($sub->start_date, $sub->end_date);
            $expired = KT_Core::is_subscription_expired($sub->end_date);
            
            $formatted[] = array(
                'id' => (int) $sub->id,
                'title' => $sub->product_name . ' - ' . $sub->duration_months . ' ماهه',
                'brand' => $sub->product_brand,
                'product_id' => (int) $sub->product_id,
                'start' => KT_Core::format_persian_date($sub->start_date),
                'end' => KT_Core::format_persian_date($sub->end_date),
                'progress' => $progress,
                'active' => $sub->auto_login_enabled == 1,
                'expired' => $expired,
                'status' => $sub->status,
                'has_credentials' => !empty($sub->service_username)
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'subscriptions' => $formatted
        ), 200);
    }
    
    /**
     * Get single subscription
     */
    public function get_subscription($request) {
        $user_id = $this->get_user_from_token($request);
        $sub_id = $request->get_param('id');
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'احراز هویت نامعتبر', array('status' => 401));
        }
        
        $subscription = KT_Subscriptions::instance()->get_subscription($sub_id, $user_id);
        
        if (!$subscription) {
            return new WP_Error('not_found', 'اشتراک یافت نشد', array('status' => 404));
        }
        
        $progress = KT_Core::calculate_progress($subscription->start_date, $subscription->end_date);
        $expired = KT_Core::is_subscription_expired($subscription->end_date);
        
        return new WP_REST_Response(array(
            'success' => true,
            'subscription' => array(
                'id' => (int) $subscription->id,
                'title' => $subscription->product_name,
                'brand' => $subscription->product_brand,
                'start' => KT_Core::format_persian_date($subscription->start_date),
                'end' => KT_Core::format_persian_date($subscription->end_date),
                'progress' => $progress,
                'active' => $subscription->auto_login_enabled == 1,
                'expired' => $expired,
                'status' => $subscription->status
            )
        ), 200);
    }
    
    /**
     * Get subscription credentials
     */
    public function get_credentials($request) {
        $user_id = $this->get_user_from_token($request);
        $sub_id = $request->get_param('id');
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'احراز هویت نامعتبر', array('status' => 401));
        }
        
        $credentials = KT_Subscriptions::instance()->get_subscription_credentials($sub_id, $user_id);
        
        if (!$credentials) {
            return new WP_Error('not_found', 'اطلاعات ورود یافت نشد', array('status' => 404));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'credentials' => $credentials
        ), 200);
    }
    
    /**
     * Toggle subscription auto-login
     */
    public function toggle_subscription($request) {
        $user_id = $this->get_user_from_token($request);
        $sub_id = $request->get_param('id');
        $enabled = $request->get_param('enabled');
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'احراز هویت نامعتبر', array('status' => 401));
        }
        
        $result = KT_Subscriptions::instance()->toggle_auto_login($sub_id, $user_id, $enabled);
        
        if (!$result) {
            return new WP_Error('failed', 'خطا در ذخیره تغییرات', array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'تغییرات با موفقیت ذخیره شد'
        ), 200);
    }
    
    /**
     * Get products
     */
    public function get_products($request) {
        $user_id = $this->get_user_from_token($request);
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'احراز هویت نامعتبر', array('status' => 401));
        }
        
        $products = KT_Products::instance()->get_all_products();
        
        return new WP_REST_Response(array(
            'success' => true,
            'products' => $products
        ), 200);
    }
    
    /**
     * Check authentication
     */
    public function check_auth($request) {
        $token = $request->get_header('Authorization');
        
        if (empty($token)) {
            return false;
        }
        
        // Remove "Bearer " prefix
        $token = str_replace('Bearer ', '', $token);
        
        $user_id = KT_Auth::instance()->validate_token($token);
        
        return !empty($user_id);
    }
    
    /**
     * Get user ID from token
     */
    private function get_user_from_token($request) {
        $token = $request->get_header('Authorization');
        
        if (empty($token)) {
            return false;
        }
        
        $token = str_replace('Bearer ', '', $token);
        
        return KT_Auth::instance()->validate_token($token);
    }
}
