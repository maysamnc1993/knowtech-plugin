<?php
/**
 * Account Pool Management View
 */

if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

// Handle form submission
if (isset($_POST['kt_save_account'])) {
    check_admin_referer('kt_account_form');
    
    $account_data = array(
        'product_id' => intval($_POST['product_id']),
        'account_username' => sanitize_text_field($_POST['account_username']),
        'account_password' => $_POST['account_password'], // Will be encrypted
        'max_users' => intval($_POST['max_users']),
        'notes' => sanitize_textarea_field($_POST['notes'])
    );
    
    if ($account_id > 0) {
        // Update
        if (empty($_POST['account_password'])) {
            unset($account_data['account_password']); // Don't update if empty
        }
        KT_Account_Pool::instance()->update_account($account_id, $account_data);
        wp_redirect(admin_url('admin.php?page=kt-account-pool&updated=1'));
    } else {
        // Create
        $new_id = KT_Account_Pool::instance()->add_account($account_data);
        wp_redirect(admin_url('admin.php?page=kt-account-pool&created=1'));
    }
    exit;
}

// Show form for add/edit
if ($action === 'edit' || $action === 'add') {
    $account = $account_id > 0 ? KT_Account_Pool::instance()->get_account($account_id) : null;
    include KT_SUBS_PLUGIN_DIR . 'admin/views/account-pool-form.php';
    return;
}

// List view
global $wpdb;
$table = $wpdb->prefix . 'kt_account_pool';

$product_filter = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$where = '1=1';
if ($product_filter) {
    $where .= $wpdb->prepare(' AND product_id = %d', $product_filter);
}

$accounts = $wpdb->get_results("
    SELECT a.*, p.name as product_name, p.brand as product_brand
    FROM $table a
    LEFT JOIN {$wpdb->prefix}kt_products p ON a.product_id = p.id
    WHERE $where
    ORDER BY a.product_id, a.id
");

$products = KT_Products::instance()->get_products_dropdown();
?>

<div class="wrap kt-admin-account-pool">
    <h1 class="wp-heading-inline">مدیریت اکانت‌ها (Account Pool)</h1>
    <a href="<?php echo admin_url('admin.php?page=kt-account-pool&action=add'); ?>" class="page-title-action">
        افزودن اکانت جدید
    </a>
    
    <?php if (isset($_GET['created'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>اکانت با موفقیت اضافه شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>اکانت با موفقیت بروزرسانی شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>اکانت با موفقیت حذف شد.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($_GET['error']); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="kt-info-box" style="margin: 20px 0;">
        <h3>📌 راهنما:</h3>
        <p><strong>Account Pool چیست؟</strong></p>
        <p>شما می‌توانید چندین اکانت واقعی برای هر سرویس (مثل ChatGPT) اضافه کنید. وقتی مشتری خرید می‌کند، از این Pool یک اکانت به او اختصاص داده می‌شود.</p>
        <ul style="list-style: disc; margin-right: 20px;">
            <li><strong>حداکثر کاربران:</strong> هر اکانت می‌تواند به چند نفر اختصاص داده شود</li>
            <li><strong>Auto-Assign:</strong> سیستم خودکار اکانت آزاد را انتخاب می‌کند</li>
            <li><strong>امنیت:</strong> مشتری فقط با Extension لاگین می‌کند و یوزر/پس نمی‌بیند</li>
        </ul>
    </div>
    
    <!-- Filter -->
    <form method="get" class="kt-filters">
        <input type="hidden" name="page" value="kt-account-pool">
        <select name="product_id" onchange="this.form.submit()">
            <option value="">همه محصولات</option>
            <?php foreach ($products as $id => $name): ?>
                <option value="<?php echo $id; ?>" <?php selected($product_filter, $id); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <!-- Statistics -->
    <div class="kt-stats-grid" style="margin: 20px 0;">
        <?php foreach ($products as $product_id => $product_name): ?>
            <?php $stats = KT_Account_Pool::instance()->get_pool_stats($product_id); ?>
            <?php if ($stats->total_accounts > 0): ?>
                <div class="kt-stat-card">
                    <div class="kt-stat-content">
                        <h4><?php echo esc_html($product_name); ?></h4>
                        <p>
                            اکانت‌ها: <?php echo $stats->total_accounts; ?> | 
                            ظرفیت: <?php echo $stats->total_capacity; ?> | 
                            استفاده: <?php echo $stats->total_used; ?> | 
                            آزاد: <?php echo $stats->total_available; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Table -->
    <?php if (empty($accounts)): ?>
        <div class="kt-info-box">
            <p>هنوز اکانتی اضافه نشده است.</p>
            <a href="<?php echo admin_url('admin.php?page=kt-account-pool&action=add'); ?>" class="button button-primary">
                افزودن اولین اکانت
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>محصول</th>
                    <th>Username</th>
                    <th width="120">استفاده</th>
                    <th width="100">ظرفیت</th>
                    <th width="100">وضعیت</th>
                    <th>یادداشت</th>
                    <th width="150">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <?php 
                    $usage_percent = $account->max_users > 0 ? ($account->current_users / $account->max_users) * 100 : 0;
                    $is_full = $account->current_users >= $account->max_users;
                    ?>
                    <tr style="<?php echo $is_full ? 'background:#fff3cd;' : ''; ?>">
                        <td><?php echo $account->id; ?></td>
                        <td>
                            <strong><?php echo esc_html($account->product_name); ?></strong><br>
                            <small><?php echo esc_html($account->product_brand); ?></small>
                        </td>
                        <td>
                            <code><?php echo esc_html($account->account_username); ?></code>
                        </td>
                        <td>
                            <div class="kt-progress-bar">
                                <div class="kt-progress-fill <?php echo $usage_percent >= 100 ? 'kt-progress-danger' : ''; ?>" 
                                     style="width: <?php echo $usage_percent; ?>%"></div>
                            </div>
                            <small><?php echo $account->current_users; ?> / <?php echo $account->max_users; ?></small>
                        </td>
                        <td>
                            <?php if ($is_full): ?>
                                <span class="kt-badge kt-badge-danger">پر</span>
                            <?php else: ?>
                                <span class="kt-badge kt-badge-success">آزاد</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($account->status === 'active'): ?>
                                <span class="kt-badge kt-badge-success">فعال</span>
                            <?php else: ?>
                                <span class="kt-badge kt-badge-secondary">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($account->notes): ?>
                                <small><?php echo esc_html(mb_substr($account->notes, 0, 50)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=kt-account-pool&action=edit&account_id=' . $account->id); ?>" 
                               class="button button-small">
                                ویرایش
                            </a>
                            
                            <?php if ($account->current_users == 0): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=kt-account-pool&action=delete_account&account_id=' . $account->id), 'kt_action'); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('آیا مطمئن هستید؟')">
                                    حذف
                                </a>
                            <?php else: ?>
                                <span class="button button-small" disabled style="opacity:0.5;" title="در حال استفاده">حذف</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
