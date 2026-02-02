<?php
/**
 * Account Pool Form (Add/Edit)
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($account);
$title = $is_edit ? 'ویرایش اکانت' : 'افزودن اکانت جدید';
$products = KT_Products::instance()->get_products_dropdown();
?>

<div class="wrap">
    <h1><?php echo $title; ?></h1>
    
    <form method="post" class="kt-account-form">
        <?php wp_nonce_field('kt_account_form'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="product_id">محصول (سرویس) *</label></th>
                <td>
                    <select name="product_id" id="product_id" class="regular-text" required>
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($products as $id => $name): ?>
                            <option value="<?php echo esc_attr($id); ?>" 
                                    <?php echo $is_edit ? selected($account->product_id, $id) : ''; ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">این اکانت برای کدام محصول است؟</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="account_username">Username (یوزرنیم) *</label></th>
                <td>
                    <input type="text" id="account_username" name="account_username" 
                           class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($account->account_username) : ''; ?>" 
                           required>
                    <p class="description">مثال: user@example.com یا username123</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="account_password">Password (رمز عبور) <?php echo $is_edit ? '' : '*'; ?></label></th>
                <td>
                    <input type="password" id="account_password" name="account_password" 
                           class="regular-text" 
                           <?php echo $is_edit ? '' : 'required'; ?>>
                    <?php if ($is_edit): ?>
                        <p class="description">برای تغییر رمز عبور، رمز جدید را وارد کنید. در غیر این صورت خالی بگذارید.</p>
                    <?php else: ?>
                        <p class="description">رمز عبور اکانت واقعی سرویس</p>
                    <?php endif; ?>
                    
                    <label style="display:block;margin-top:10px;">
                        <input type="checkbox" id="show_password">
                        نمایش رمز عبور
                    </label>
                </td>
            </tr>
            
            <tr>
                <th><label for="max_users">حداکثر کاربران *</label></th>
                <td>
                    <input type="number" id="max_users" name="max_users" 
                           class="small-text" 
                           value="<?php echo $is_edit ? esc_attr($account->max_users) : '1'; ?>" 
                           min="1" max="100" required>
                    <p class="description">
                        این اکانت می‌تواند به چند نفر اختصاص داده شود؟<br>
                        <strong>توصیه:</strong> برای هر سرویس متفاوت است. ChatGPT: 1-3، سایر سرویس‌ها: 1-5
                    </p>
                </td>
            </tr>
            
            <?php if ($is_edit): ?>
            <tr>
                <th>استفاده فعلی:</th>
                <td>
                    <strong><?php echo $account->current_users; ?></strong> نفر در حال استفاده
                    <?php if ($account->current_users >= $account->max_users): ?>
                        <span class="kt-badge kt-badge-danger">پر</span>
                    <?php else: ?>
                        <span class="kt-badge kt-badge-success">آزاد</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <th><label for="notes">یادداشت</label></th>
                <td>
                    <textarea id="notes" name="notes" rows="3" class="large-text"><?php 
                        echo $is_edit ? esc_textarea($account->notes) : ''; 
                    ?></textarea>
                    <p class="description">یادداشت‌های داخلی (مثلاً: خریداری شده از X، تاریخ انقضا، و...)</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="kt_save_account" class="button button-primary">
                <?php echo $is_edit ? 'بروزرسانی اکانت' : 'افزودن اکانت'; ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=kt-account-pool'); ?>" class="button">
                انصراف
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/Hide password
    $('#show_password').change(function() {
        var type = $(this).is(':checked') ? 'text' : 'password';
        $('#account_password').attr('type', type);
    });
});
</script>

<style>
.kt-account-form .form-table th {
    width: 200px;
}
</style>
