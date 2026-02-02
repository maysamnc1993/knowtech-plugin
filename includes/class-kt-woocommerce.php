<?php
/**
 * WooCommerce Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KT_WooCommerce {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Add product meta box
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
        
        // Order completed hook
        add_action('woocommerce_order_status_completed', array($this, 'create_subscription_on_order'));
        add_action('woocommerce_order_status_processing', array($this, 'create_subscription_on_order'));
        
        // Add subscription fields to order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_subscription_info'));
        
        // Add manual create button to order
        add_action('woocommerce_order_actions', array($this, 'add_order_action_create_subscription'));
        add_action('woocommerce_order_action_kt_create_subscription', array($this, 'process_order_action_create_subscription'));
        
        // Add custom product type
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'));
        
        // My Account - Subscriptions tab
        add_filter('woocommerce_account_menu_items', array($this, 'add_subscriptions_tab'));
        add_action('woocommerce_account_kt-subscriptions_endpoint', array($this, 'subscriptions_endpoint_content'));
        add_action('init', array($this, 'add_endpoints'));
    }
    
    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'kt_subscription_product',
            'تنظیمات اشتراک KnowTech',
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }
    
    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        wp_nonce_field('kt_product_meta', 'kt_product_meta_nonce');
        
        $is_subscription = get_post_meta($post->ID, '_kt_is_subscription', true);
        $product_id = get_post_meta($post->ID, '_kt_product_id', true);
        $duration = get_post_meta($post->ID, '_kt_duration_months', true);
        $credentials_required = get_post_meta($post->ID, '_kt_credentials_required', true);
        $use_account_pool = get_post_meta($post->ID, '_kt_use_account_pool', true);
        
        $products = KT_Products::instance()->get_products_dropdown();
        
        // Add nonce field
        wp_nonce_field('kt_product_meta', 'kt_product_meta_nonce');
        
        ?>
        <div class="kt-subscription-settings">
            <p>
                <label>
                    <input type="checkbox" name="kt_is_subscription" value="1" <?php checked($is_subscription, '1'); ?>>
                    این محصول یک اشتراک KnowTech است
                </label>
            </p>
            
            <div class="kt-subscription-fields" style="<?php echo $is_subscription ? '' : 'display:none;'; ?>">
                <p>
                    <label for="kt_product_id"><strong>سرویس مرتبط:</strong></label>
                    <select name="kt_product_id" id="kt_product_id" class="widefat">
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($products as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>" <?php selected($product_id, $id); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                
                <p>
                    <label for="kt_duration_months"><strong>مدت اشتراک (ماه):</strong></label>
                    <input type="number" name="kt_duration_months" id="kt_duration_months" 
                           value="<?php echo esc_attr($duration ?: '1'); ?>" 
                           min="1" max="24" class="small-text">
                    <span class="description">تعداد ماه‌های اشتراک (1، 3، 6، 12)</span>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="kt_credentials_required" value="1" 
                               <?php checked($credentials_required, '1'); ?>>
                        نیاز به دریافت اطلاعات ورود از مشتری دارد
                    </label>
                    <span class="description" style="display:block;margin-top:5px;">
                        اگر فعال باشد، پس از خرید از مشتری Username و Password درخواست می‌شود
                    </span>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="kt_use_account_pool" value="1" 
                               <?php checked($use_account_pool, '1'); ?>>
                        استفاده از Account Pool (اختصاص خودکار)
                    </label>
                    <span class="description" style="display:block;margin-top:5px;">
                        <strong>توصیه می‌شود!</strong> اگر فعال باشد، سیستم خودکار از Pool اکانت‌ها یک اکانت را اختصاص می‌دهد و مشتری یوزر/پس نمی‌بیند
                    </span>
                </p>
            </div>
        </div>
        
        <style>
            .kt-subscription-settings {
                padding: 12px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .kt-subscription-settings p {
                margin: 15px 0;
            }
            .kt-subscription-settings label {
                display: inline-block;
                margin-bottom: 5px;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="kt_is_subscription"]').change(function() {
                if ($(this).is(':checked')) {
                    $('.kt-subscription-fields').slideDown();
                } else {
                    $('.kt-subscription-fields').slideUp();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save product meta
     */
    public function save_product_meta($post_id) {
        if (!isset($_POST['kt_product_meta_nonce']) || 
            !wp_verify_nonce($_POST['kt_product_meta_nonce'], 'kt_product_meta')) {
            return;
        }
        
        $is_subscription = isset($_POST['kt_is_subscription']) ? '1' : '0';
        update_post_meta($post_id, '_kt_is_subscription', $is_subscription);
        
        if ($is_subscription === '1') {
            update_post_meta($post_id, '_kt_product_id', sanitize_text_field($_POST['kt_product_id']));
            update_post_meta($post_id, '_kt_duration_months', intval($_POST['kt_duration_months']));
            
            $credentials_required = isset($_POST['kt_credentials_required']) ? '1' : '0';
            update_post_meta($post_id, '_kt_credentials_required', $credentials_required);
            
            $use_account_pool = isset($_POST['kt_use_account_pool']) ? '1' : '0';
            update_post_meta($post_id, '_kt_use_account_pool', $use_account_pool);
        }
    }
    
    /**
     * Create subscription when order is completed
     */
    public function create_subscription_on_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            return;
        }
        
        // Check if subscription already created
        $subscription_created = get_post_meta($order_id, '_kt_subscription_created', true);
        if ($subscription_created) {
            return;
        }
        
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            // Check for variation first, then product
            $check_id = $variation_id ? $variation_id : $product_id;
            
            $is_subscription = get_post_meta($check_id, '_kt_is_subscription', true);
            
            if ($is_subscription !== '1') {
                continue;
            }
            
            $kt_product_id = get_post_meta($check_id, '_kt_product_id', true);
            $duration_months = get_post_meta($check_id, '_kt_duration_months', true);
            $credentials_required = get_post_meta($check_id, '_kt_credentials_required', true);
            $use_account_pool = get_post_meta($check_id, '_kt_use_account_pool', true);
            
            if (empty($kt_product_id) || empty($duration_months)) {
                continue;
            }
            
            // Get credentials from order meta (if provided by customer)
            $username = get_post_meta($order_id, '_kt_service_username_' . $item_id, true);
            $password = get_post_meta($order_id, '_kt_service_password_' . $item_id, true);
            
            // Create subscription
            $subscription_data = array(
                'user_id' => $user_id,
                'product_id' => $kt_product_id,
                'order_id' => $order_id,
                'service_username' => $username,
                'service_password' => $password,
                'start_date' => current_time('mysql'),
                'duration_months' => $duration_months,
                'status' => 'active',
                'auto_login_enabled' => !empty($username) ? 1 : 0
            );
            
            $sub_id = KT_Subscriptions::instance()->create_subscription($subscription_data);
            
            if ($sub_id) {
                // If using account pool and no credentials provided, assign from pool
                if ($use_account_pool === '1' && empty($username)) {
                    $account = KT_Account_Pool::instance()->assign_to_subscription($sub_id);
                    
                    if (is_wp_error($account)) {
                        $order->add_order_note(
                            'خطا در اختصاص اکانت از Pool: ' . $account->get_error_message()
                        );
                    } else {
                        $order->add_order_note(
                            sprintf('اکانت #%d از Pool به اشتراک اختصاص داده شد', $account->id)
                        );
                    }
                }
                
                // Store subscription ID in order meta
                add_post_meta($order_id, '_kt_subscription_id', $sub_id);
                
                // Add order note
                $product = KT_Products::instance()->get_product($kt_product_id);
                $order->add_order_note(
                    sprintf('اشتراک %s به مدت %d ماه برای کاربر ایجاد شد (ID: %d)', 
                        $product->name, 
                        $duration_months, 
                        $sub_id
                    )
                );
            }
        }
        
        // Mark as created
        update_post_meta($order_id, '_kt_subscription_created', '1');
    }
    
    /**
     * Display subscription info in order admin
     */
    public function display_subscription_info($order) {
        $subscription_ids = get_post_meta($order->get_id(), '_kt_subscription_id');
        
        if (empty($subscription_ids)) {
            return;
        }
        
        echo '<div class="kt-order-subscriptions" style="margin-top:20px;">';
        echo '<h3>اشتراک‌های ایجاد شده:</h3>';
        
        foreach ($subscription_ids as $sub_id) {
            $subscription = KT_Subscriptions::instance()->get_subscription($sub_id);
            
            if (!$subscription) {
                continue;
            }
            
            $progress = KT_Core::calculate_progress($subscription->start_date, $subscription->end_date);
            $status_label = $subscription->status === 'active' ? 'فعال' : 'منقضی شده';
            
            echo '<div style="background:#f9f9f9;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:4px;">';
            echo '<strong>' . esc_html($subscription->product_name) . '</strong><br>';
            echo 'وضعیت: <span style="color:' . ($subscription->status === 'active' ? 'green' : 'red') . '">' . $status_label . '</span><br>';
            echo 'شروع: ' . esc_html(KT_Core::format_persian_date($subscription->start_date)) . '<br>';
            echo 'پایان: ' . esc_html(KT_Core::format_persian_date($subscription->end_date)) . '<br>';
            echo 'پیشرفت: ' . $progress . '%<br>';
            
            if ($subscription->pool_account_id) {
                $pool_account = KT_Account_Pool::instance()->get_account($subscription->pool_account_id);
                if ($pool_account) {
                    echo '<strong style="color:#0073aa;">✓ از Account Pool:</strong> ' . esc_html($pool_account->account_username) . '<br>';
                }
            } elseif ($subscription->service_username) {
                echo 'Username: ' . esc_html($subscription->service_username) . '<br>';
            }
            
            echo '<a href="' . admin_url('admin.php?page=kt-subscriptions-list') . '" class="button button-small" style="margin-top:5px;">مشاهده جزئیات</a>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add manual create subscription action to order actions dropdown
     */
    public function add_order_action_create_subscription($actions) {
        global $theorder;
        
        if (!$theorder) {
            return $actions;
        }
        
        // Check if subscription already created
        $subscription_created = get_post_meta($theorder->get_id(), '_kt_subscription_created', true);
        
        if (!$subscription_created) {
            $actions['kt_create_subscription'] = '🎯 ساخت دستی اشتراک KnowTech';
        }
        
        return $actions;
    }
    
    /**
     * Process manual create subscription action
     */
    public function process_order_action_create_subscription($order) {
        $order_id = $order->get_id();
        
        // Remove the flag if exists to allow recreation
        delete_post_meta($order_id, '_kt_subscription_created');
        
        // Run the subscription creation
        $this->create_subscription_on_order($order_id);
        
        // Add order note
        $order->add_order_note('✅ اشتراک به صورت دستی ساخته شد توسط Admin', false, true);
        
        // Display admin notice
        add_action('admin_notices', function() use ($order_id) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>موفق!</strong> اشتراک برای سفارش #' . $order_id . ' ساخته شد.</p>';
            echo '</div>';
        });
    }
    
    /**
     * Add subscription product type
     */
    public function add_subscription_product_type($types) {
        $types['kt_subscription'] = 'اشتراک KnowTech';
        return $types;
    }
    
    /**
     * Add endpoints
     */
    public function add_endpoints() {
        add_rewrite_endpoint('kt-subscriptions', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Add subscriptions tab to My Account
     */
    public function add_subscriptions_tab($items) {
        $new_items = array();
        
        foreach ($items as $key => $item) {
            $new_items[$key] = $item;
            
            // Add after dashboard
            if ($key === 'dashboard') {
                $new_items['kt-subscriptions'] = 'اشتراک‌های من';
            }
        }
        
        return $new_items;
    }
    
    /**
     * Subscriptions tab content
     */
    public function subscriptions_endpoint_content() {
        $user_id = get_current_user_id();
        $subscriptions = KT_Subscriptions::instance()->get_user_subscriptions($user_id);
        
        ?>
        <div class="kt-my-subscriptions">
            <h2>اشتراک‌های من</h2>
            
            <?php if (empty($subscriptions)): ?>
                <p>شما هیچ اشتراکی ندارید.</p>
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="button">
                    مشاهده محصولات
                </a>
            <?php else: ?>
                <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
                    <thead>
                        <tr>
                            <th>سرویس</th>
                            <th>برند</th>
                            <th>تاریخ شروع</th>
                            <th>تاریخ پایان</th>
                            <th>وضعیت</th>
                            <th>پیشرفت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                            <?php 
                            $progress = KT_Core::calculate_progress($sub->start_date, $sub->end_date);
                            $expired = KT_Core::is_subscription_expired($sub->end_date);
                            ?>
                            <tr>
                                <td data-title="سرویس"><?php echo esc_html($sub->product_name); ?></td>
                                <td data-title="برند"><?php echo esc_html($sub->product_brand); ?></td>
                                <td data-title="شروع"><?php echo KT_Core::format_persian_date($sub->start_date); ?></td>
                                <td data-title="پایان"><?php echo KT_Core::format_persian_date($sub->end_date); ?></td>
                                <td data-title="وضعیت">
                                    <?php if ($expired): ?>
                                        <span style="color:red;">منقضی شده</span>
                                    <?php else: ?>
                                        <span style="color:green;">فعال</span>
                                    <?php endif; ?>
                                </td>
                                <td data-title="پیشرفت">
                                    <div style="background:#eee;height:20px;border-radius:10px;overflow:hidden;">
                                        <div style="background:<?php echo $progress >= 95 ? '#f44336' : '#4CAF50'; ?>;height:100%;width:<?php echo $progress; ?>%;"></div>
                                    </div>
                                    <small><?php echo $progress; ?>%</small>
                                </td>
                                <td data-title="عملیات">
                                    <?php if ($expired): ?>
                                        <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="button">
                                            تمدید
                                        </a>
                                    <?php else: ?>
                                        <button class="button" disabled>فعال</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <style>
            .kt-my-subscriptions {
                margin: 20px 0;
            }
            .kt-my-subscriptions table {
                width: 100%;
            }
        </style>
        <?php
    }
}
