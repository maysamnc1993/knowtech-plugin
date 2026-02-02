<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = KT_Subscriptions::instance()->get_stats();
$recent_subs = KT_Subscriptions::instance()->get_user_subscriptions(null);
$recent_subs = array_slice($recent_subs, 0, 10);
?>

<div class="wrap kt-admin-dashboard">
    <h1>داشبورد اشتراک‌ها</h1>
    
    <!-- Stats Cards -->
    <div class="kt-stats-grid">
        <div class="kt-stat-card">
            <div class="kt-stat-icon" style="background:#4CAF50;">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="kt-stat-content">
                <h3><?php echo number_format($stats['active']); ?></h3>
                <p>اشتراک فعال</p>
            </div>
        </div>
        
        <div class="kt-stat-card">
            <div class="kt-stat-icon" style="background:#2196F3;">
                <span class="dashicons dashicons-tickets-alt"></span>
            </div>
            <div class="kt-stat-content">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p>کل اشتراک‌ها</p>
            </div>
        </div>
        
        <div class="kt-stat-card">
            <div class="kt-stat-icon" style="background:#FF9800;">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="kt-stat-content">
                <h3><?php echo number_format($stats['expiring_soon']); ?></h3>
                <p>در حال انقضا (7 روز)</p>
            </div>
        </div>
        
        <div class="kt-stat-card">
            <div class="kt-stat-icon" style="background:#f44336;">
                <span class="dashicons dashicons-no"></span>
            </div>
            <div class="kt-stat-content">
                <h3><?php echo number_format($stats['expired']); ?></h3>
                <p>منقضی شده</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Subscriptions -->
    <div class="kt-section">
        <h2>آخرین اشتراک‌ها</h2>
        
        <?php if (empty($recent_subs)): ?>
            <p>هنوز اشتراکی ثبت نشده است.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>کاربر</th>
                        <th>سرویس</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>وضعیت</th>
                        <th>پیشرفت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_subs as $sub): ?>
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
                                <?php else: ?>
                                    کاربر حذف شده
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($sub->product_name); ?></strong><br>
                                <small><?php echo esc_html($sub->product_brand); ?></small>
                            </td>
                            <td><?php echo KT_Core::format_persian_date($sub->start_date); ?></td>
                            <td><?php echo KT_Core::format_persian_date($sub->end_date); ?></td>
                            <td>
                                <?php if ($expired): ?>
                                    <span class="kt-badge kt-badge-danger">منقضی شده</span>
                                <?php else: ?>
                                    <span class="kt-badge kt-badge-success">فعال</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="kt-progress-bar">
                                    <div class="kt-progress-fill <?php echo $progress >= 95 ? 'kt-progress-danger' : ''; ?>" 
                                         style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <small><?php echo $progress; ?>%</small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=kt-subscriptions-list'); ?>" class="button">
                    مشاهده همه اشتراک‌ها
                </a>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Links -->
    <div class="kt-section">
        <h2>دسترسی سریع</h2>
        <div class="kt-quick-links">
            <a href="<?php echo admin_url('admin.php?page=kt-subscriptions-list'); ?>" class="kt-quick-link">
                <span class="dashicons dashicons-list-view"></span>
                لیست اشتراک‌ها
            </a>
            <a href="<?php echo admin_url('admin.php?page=kt-products'); ?>" class="kt-quick-link">
                <span class="dashicons dashicons-products"></span>
                مدیریت محصولات
            </a>
            <a href="<?php echo admin_url('admin.php?page=kt-settings'); ?>" class="kt-quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                تنظیمات
            </a>
            <a href="<?php echo rest_url('knowtech/v1/'); ?>" target="_blank" class="kt-quick-link">
                <span class="dashicons dashicons-rest-api"></span>
                مستندات API
            </a>
        </div>
    </div>
</div>
