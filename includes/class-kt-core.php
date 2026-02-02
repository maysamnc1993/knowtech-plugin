<?php
/**
 * Core Class - Database & Core Functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Core {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Products table
        $table_products = $wpdb->prefix . 'kt_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            brand varchar(100) DEFAULT NULL,
            icon_url varchar(500) DEFAULT NULL,
            login_url varchar(500) NOT NULL,
            login_method varchar(50) DEFAULT 'form',
            username_selector varchar(200) DEFAULT NULL,
            password_selector varchar(200) DEFAULT NULL,
            submit_selector varchar(200) DEFAULT NULL,
            cookie_domain varchar(200) DEFAULT NULL,
            auto_login_script text DEFAULT NULL,
            description text DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) $charset_collate;";
        
        // Subscriptions table
        $table_subscriptions = $wpdb->prefix . 'kt_subscriptions';
        $sql_subscriptions = "CREATE TABLE IF NOT EXISTS $table_subscriptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            pool_account_id bigint(20) UNSIGNED DEFAULT NULL,
            service_username varchar(200) DEFAULT NULL,
            service_password text DEFAULT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            duration_months int(11) NOT NULL,
            status varchar(20) DEFAULT 'active',
            auto_login_enabled tinyint(1) DEFAULT 1,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY order_id (order_id),
            KEY pool_account_id (pool_account_id),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_products);
        dbDelta($sql_subscriptions);
    }
    
    /**
     * Insert default products
     */
    public static function insert_default_products() {
        global $wpdb;
        $table = $wpdb->prefix . 'kt_products';
        
        // Check if already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }
        
        $products = array(
            array(
                'name' => 'ChatGPT Plus',
                'slug' => 'chatgpt-plus',
                'brand' => 'OpenAI',
                'login_url' => 'https://chat.openai.com/auth/login',
                'login_method' => 'form',
                'username_selector' => 'input[name="username"]',
                'password_selector' => 'input[name="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'دسترسی به ChatGPT Plus با GPT-4'
            ),
            array(
                'name' => 'Midjourney',
                'slug' => 'midjourney',
                'brand' => 'Midjourney',
                'login_url' => 'https://www.midjourney.com/auth/signin',
                'login_method' => 'form',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'تولید تصویر با هوش مصنوعی'
            ),
            array(
                'name' => 'Claude Pro',
                'slug' => 'claude-pro',
                'brand' => 'Anthropic',
                'login_url' => 'https://claude.ai/login',
                'login_method' => 'form',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'دسترسی به Claude Pro'
            ),
            array(
                'name' => 'Gemini Advanced',
                'slug' => 'gemini-advanced',
                'brand' => 'Google',
                'login_url' => 'https://gemini.google.com/',
                'login_method' => 'cookie',
                'cookie_domain' => '.google.com',
                'description' => 'دسترسی به Gemini Advanced'
            ),
            array(
                'name' => 'Canva Pro',
                'slug' => 'canva-pro',
                'brand' => 'Canva',
                'login_url' => 'https://www.canva.com/login',
                'login_method' => 'form',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'طراحی گرافیک حرفه‌ای'
            ),
            array(
                'name' => 'Grammarly Premium',
                'slug' => 'grammarly-premium',
                'brand' => 'Grammarly',
                'login_url' => 'https://app.grammarly.com/signin',
                'login_method' => 'form',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'ویرایشگر متن پیشرفته'
            ),
            array(
                'name' => 'Notion Plus',
                'slug' => 'notion-plus',
                'brand' => 'Notion',
                'login_url' => 'https://www.notion.so/login',
                'login_method' => 'form',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'button[type="submit"]',
                'description' => 'یادداشت‌برداری و مدیریت پروژه'
            )
        );
        
        foreach ($products as $product) {
            $wpdb->insert($table, $product);
        }
    }
    
    /**
     * Encrypt password
     */
    public static function encrypt_password($password) {
        if (empty($password)) {
            return '';
        }
        
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt password
     */
    public static function decrypt_password($encrypted_password) {
        if (empty($encrypted_password)) {
            return '';
        }
        
        $key = self::get_encryption_key();
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_password), 2);
        
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private static function get_encryption_key() {
        $key = get_option('kt_encryption_key');
        
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            update_option('kt_encryption_key', $key, false);
        }
        
        return $key;
    }
    
    /**
     * Calculate subscription progress
     */
    public static function calculate_progress($start_date, $end_date) {
        $now = current_time('timestamp');
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        
        if ($now < $start) {
            return 0;
        }
        
        if ($now > $end) {
            return 100;
        }
        
        $total = $end - $start;
        $elapsed = $now - $start;
        
        return min(100, round(($elapsed / $total) * 100));
    }
    
    /**
     * Check if subscription is expired
     */
    public static function is_subscription_expired($end_date) {
        $now = current_time('timestamp');
        $end = strtotime($end_date);
        
        return $now > $end;
    }
    
    /**
     * Format Persian date
     */
    public static function format_persian_date($date) {
        // Use parsidate plugin if available
        if (function_exists('parsidate')) {
            return parsidate('Y/m/d', $date);
        }
        
        return date('Y/m/d', strtotime($date));
    }
}
