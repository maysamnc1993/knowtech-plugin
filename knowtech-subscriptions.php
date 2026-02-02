<?php
/**
 * Plugin Name: KnowTech Subscription Manager
 * Plugin URI: https://knowtech.me
 * Description: مدیریت کامل اشتراک‌ها با Chrome Extension و Auto-Login
 * Version: 1.0.0
 * Author: Meysam Khatami
 * Author URI: https://hostlino.com
 * Text Domain: knowtech-subs
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KT_SUBS_VERSION', '1.0.0');
define('KT_SUBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KT_SUBS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KT_SUBS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class KnowTech_Subscriptions {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-core.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-auth.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-api.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-subscriptions.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-products.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-account-pool.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-admin.php';
        require_once KT_SUBS_PLUGIN_DIR . 'includes/class-kt-woocommerce.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        KT_Core::create_tables();
        KT_Core::insert_default_products();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes
        KT_Auth::instance();
        KT_API::instance();
        KT_Subscriptions::instance();
        KT_Products::instance();
        KT_Account_Pool::instance();
        KT_Admin::instance();
        KT_WooCommerce::instance();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'knowtech-subs',
            false,
            dirname(KT_SUBS_PLUGIN_BASENAME) . '/languages'
        );
    }
}

/**
 * Initialize plugin
 */
function knowtech_subscriptions() {
    return KnowTech_Subscriptions::instance();
}

// Start the plugin
knowtech_subscriptions();
