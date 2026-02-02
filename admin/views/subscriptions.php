<?php
/**
 * Subscriptions List View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all subscriptions
global $wpdb;
$table_subs = $wpdb->prefix . 'kt_subscriptions';
$table_products = $wpdb->prefix . 'kt_products';

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$per_page = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $per_page;

$where = array('1=1');
if ($search) {
    $where[] = $wpdb->prepare("(p.name LIKE %s OR u.display_name LIKE %s)", '%' . $search . '%', '%' . $search . '%');
}
if ($status_filter) {
    $where[] = $wpdb->prepare("s.status = %s", $status_filter);
}

$where_clause = implode(' AND ', $where);

$query = "
    SELECT 
        s.*,
        p.name as product_name,
        p.brand as product_brand
    FROM $table_subs s
    LEFT JOIN $table_products p ON s.product_id = p.id
    WHERE $where_clause
    ORDER BY s.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$subscriptions = $wpdb->get_results($query);
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table_subs s LEFT JOIN $table_products p ON s.product_id = p.id WHERE $where_clause");
$total_pages = ceil($total / $per_page);
?>

<div class="wrap kt-admin-subscriptions">
    <h1 class="wp-heading-inline">اشتراک‌ها</h1>
    <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="page-title-action">
        افزودن محصول جدید
    </a>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>اشتراک با موفقیت حذف شد.</p>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <form method="get" class="kt-filters">
        <input type="hidden" name="page" value="kt-subscriptions-list">
        
        <select name="status" onchange="this.form.submit()">
            <option value="">همه وضعیت‌ها</option>
            <option value="active" <?php selected($status_filter, 'active'); ?>>فعال</option>
            <option value="expired" <?php selected($status_filter, 'expired'); ?>>منقضی شده</option>
            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>لغو شده</option>
        </select>
        
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="جستجو...">
        <button type="submit" class="button">جستجو</button>
    </form>
    
    <!-- Table -->
    <?php if (empty($subscriptions)): ?>
        <p>اشتراکی یافت نشد.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>کاربر</th>
                    <th>سرویس</th>
                    <th>برند</th>
                    <th>شروع</th>
                    <th>پایان</th>
                    <th>مدت</th>
                    <th>وضعیت</th>
                    <th>پیشرفت</th>
                    <th width="150">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                    <?php 
                    $user = get_userdata($sub->user_id);
                    $progress = KT_Core::calculate_progress($sub->start_date, $sub->end_date);
                    $expired = KT_Core::is_subscription_expired($sub->end_date);
                    ?>
                    <tr>
                        <td><?php echo $sub->id; ?></td>
                        <td>
                            <?php if ($user): ?>
                                <a href="<?php echo get_edit_user_link($user->ID); ?>">
                                    <?php echo esc_html($user->display_name); ?>
                                </a>
                                <br>
                                <small><?php echo esc_html($user->user_email); ?></small>
                            <?php else: ?>
                                کاربر حذف شده
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html($sub->product_name); ?></strong></td>
                        <td><?php echo esc_html($sub->product_brand); ?></td>
                        <td><?php echo KT_Core::format_persian_date($sub->start_date); ?></td>
                        <td><?php echo KT_Core::format_persian_date($sub->end_date); ?></td>
                        <td><?php echo $sub->duration_months; ?> ماه</td>
                        <td>
                            <?php if ($expired): ?>
                                <span class="kt-badge kt-badge-danger">منقضی</span>
                            <?php elseif ($sub->status === 'active'): ?>
                                <span class="kt-badge kt-badge-success">فعال</span>
                            <?php else: ?>
                                <span class="kt-badge kt-badge-warning"><?php echo $sub->status; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="kt-progress-bar">
                                <div class="kt-progress-fill <?php echo $progress >= 95 ? 'kt-progress-danger' : ''; ?>" 
                                     style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <small><?php echo $progress; ?>%</small>
                        </td>
                        <td>
                            <a href="#" class="button button-small kt-view-details" data-id="<?php echo $sub->id; ?>">
                                جزئیات
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=kt-subscriptions-list&action=delete_subscription&sub_id=' . $sub->id), 'kt_action'); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('آیا مطمئن هستید؟')">
                                حذف
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div id="kt-subscription-modal" class="kt-modal" style="display:none;">
    <div class="kt-modal-content">
        <span class="kt-modal-close">&times;</span>
        <div id="kt-subscription-details"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.kt-view-details').click(function(e) {
        e.preventDefault();
        var subId = $(this).data('id');
        
        $('#kt-subscription-details').html('<p>در حال بارگذاری...</p>');
        $('#kt-subscription-modal').fadeIn();
        
        // Load details via AJAX
        $.post(ajaxurl, {
            action: 'kt_get_subscription_details',
            sub_id: subId,
            nonce: '<?php echo wp_create_nonce('kt_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $('#kt-subscription-details').html(response.data.html);
            } else {
                $('#kt-subscription-details').html('<p>خطا در بارگذاری اطلاعات</p>');
            }
        });
    });
    
    $('.kt-modal-close').click(function() {
        $('#kt-subscription-modal').fadeOut();
    });
});
</script>
