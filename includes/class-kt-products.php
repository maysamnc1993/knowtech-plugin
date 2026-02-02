<?php
/**
 * Products Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_Products {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    /**
     * Get all products
     */
    public function get_all_products($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE status = %s ORDER BY name ASC",
                $status
            ));
        }
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
    }
    
    /**
     * Get product by ID
     */
    public function get_product($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $product_id
        ));
    }
    
    /**
     * Get product by slug
     */
    public function get_product_by_slug($slug) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Create product
     */
    public function create_product($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update product
     */
    public function update_product($product_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        // Update slug if name changed
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $product_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Delete product
     */
    public function delete_product($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'kt_products';
        
        // Check if product has subscriptions
        $table_subs = $wpdb->prefix . 'kt_subscriptions';
        $has_subs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_subs WHERE product_id = %d",
            $product_id
        ));
        
        if ($has_subs > 0) {
            return new WP_Error('has_subscriptions', 'این محصول دارای اشتراک فعال است و نمی‌توان آن را حذف کرد');
        }
        
        return $wpdb->delete($table, array('id' => $product_id));
    }
    
    /**
     * Get products for dropdown
     */
    public function get_products_dropdown() {
        $products = $this->get_all_products();
        
        $options = array();
        foreach ($products as $product) {
            $options[$product->id] = $product->name . ' (' . $product->brand . ')';
        }
        
        return $options;
    }
    
    /**
     * Get login methods
     */
    public function get_login_methods() {
        return array(
            'form' => 'فرم لاگین (Username/Password)',
            'cookie' => 'کوکی (Session Injection)',
            'api' => 'API (Token Based)',
            'custom' => 'سفارشی (Custom Script)'
        );
    }
}
