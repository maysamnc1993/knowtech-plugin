<?php
/**
 * Products Management View
 */

if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Handle form submission
if (isset($_POST['kt_save_product'])) {
    check_admin_referer('kt_product_form');
    
    $product_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'slug' => sanitize_title($_POST['name']),
        'brand' => sanitize_text_field($_POST['brand']),
        'login_url' => esc_url_raw($_POST['login_url']),
        'login_method' => sanitize_text_field($_POST['login_method']),
        'username_selector' => sanitize_text_field($_POST['username_selector']),
        'password_selector' => sanitize_text_field($_POST['password_selector']),
        'submit_selector' => sanitize_text_field($_POST['submit_selector']),
        'cookie_domain' => sanitize_text_field($_POST['cookie_domain']),
        'description' => sanitize_textarea_field($_POST['description']),
        'status' => sanitize_text_field($_POST['status'])
    );
    
    if ($product_id > 0) {
        KT_Products::instance()->update_product($product_id, $product_data);
        wp_redirect(admin_url('admin.php?page=kt-products&updated=1'));
    } else {
        $new_id = KT_Products::instance()->create_product($product_data);
        wp_redirect(admin_url('admin.php?page=kt-products&created=1'));
    }
    exit;
}

if ($action === 'edit' || $action === 'add') {
    $product = $product_id > 0 ? KT_Products::instance()->get_product($product_id) : null;
    include KT_SUBS_PLUGIN_DIR . 'admin/views/product-form.php';
    return;
}

// List view
$products = KT_Products::instance()->get_all_products(false);
?>

<div class="wrap kt-admin-products">
    <h1 class="wp-heading-inline">محصولات (سرویس‌ها)</h1>
    <a href="<?php echo admin_url('admin.php?page=kt-products&action=add'); ?>" class="page-title-action">
        افزودن محصول جدید
    </a>
    
    <?php if (isset($_GET['created'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>محصول با موفقیت ایجاد شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>محصول با موفقیت بروزرسانی شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>محصول با موفقیت حذف شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($_GET['error']); ?></p>
        </div>
    <?php endif; ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="60">ID</th>
                <th>نام</th>
                <th>برند</th>
                <th>Slug</th>
                <th>روش ورود</th>
                <th>URL</th>
                <th>وضعیت</th>
                <th width="150">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8">محصولی یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product->id; ?></td>
                        <td><strong><?php echo esc_html($product->name); ?></strong></td>
                        <td><?php echo esc_html($product->brand); ?></td>
                        <td><code><?php echo esc_html($product->slug); ?></code></td>
                        <td>
                            <?php 
                            $methods = KT_Products::instance()->get_login_methods();
                            echo esc_html($methods[$product->login_method] ?? $product->login_method);
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($product->login_url); ?>" target="_blank">
                                <?php echo esc_html(parse_url($product->login_url, PHP_URL_HOST)); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($product->status === 'active'): ?>
                                <span class="kt-badge kt-badge-success">فعال</span>
                            <?php else: ?>
                                <span class="kt-badge kt-badge-secondary">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=kt-products&action=edit&product_id=' . $product->id); ?>" 
                               class="button button-small">
                                ویرایش
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=kt-products&action=delete_product&product_id=' . $product->id), 'kt_action'); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('آیا مطمئن هستید؟')">
                                حذف
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="kt-info-box" style="margin-top: 20px;">
        <h3>راهنما:</h3>
        <p>محصولات، سرویس‌هایی هستند که کاربران می‌توانند اشتراک آن‌ها را خریداری کنند (مانند ChatGPT، Midjourney و...).</p>
        <p>برای هر محصول باید اطلاعات ورود و روش Auto-Login را تعریف کنید.</p>
    </div>
</div>
