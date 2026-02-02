<?php
/**
 * Product Form View (Add/Edit)
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($product);
$title = $is_edit ? 'ویرایش محصول' : 'افزودن محصول جدید';
$login_methods = KT_Products::instance()->get_login_methods();
?>

<div class="wrap">
    <h1><?php echo $title; ?></h1>
    
    <form method="post" class="kt-product-form">
        <?php wp_nonce_field('kt_product_form'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="name">نام محصول *</label></th>
                <td>
                    <input type="text" id="name" name="name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($product->name) : ''; ?>" required>
                    <p class="description">مثال: ChatGPT Plus</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="brand">برند *</label></th>
                <td>
                    <input type="text" id="brand" name="brand" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($product->brand) : ''; ?>" required>
                    <p class="description">مثال: OpenAI</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="login_url">URL ورود *</label></th>
                <td>
                    <input type="url" id="login_url" name="login_url" class="large-text" 
                           value="<?php echo $is_edit ? esc_url($product->login_url) : ''; ?>" required>
                    <p class="description">آدرس صفحه ورود سرویس</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="login_method">روش ورود *</label></th>
                <td>
                    <select name="login_method" id="login_method" class="regular-text">
                        <?php foreach ($login_methods as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" 
                                    <?php echo $is_edit ? selected($product->login_method, $key) : ''; ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <div id="form-login-fields" style="display:none;">
            <h2>تنظیمات فرم ورود</h2>
            <table class="form-table">
                <tr>
                    <th><label for="username_selector">CSS Selector Username</label></th>
                    <td>
                        <input type="text" id="username_selector" name="username_selector" class="large-text" 
                               value="<?php echo $is_edit ? esc_attr($product->username_selector) : ''; ?>">
                        <p class="description">مثال: input[name="username"] یا #email</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="password_selector">CSS Selector Password</label></th>
                    <td>
                        <input type="text" id="password_selector" name="password_selector" class="large-text" 
                               value="<?php echo $is_edit ? esc_attr($product->password_selector) : ''; ?>">
                        <p class="description">مثال: input[type="password"]</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="submit_selector">CSS Selector دکمه Submit</label></th>
                    <td>
                        <input type="text" id="submit_selector" name="submit_selector" class="large-text" 
                               value="<?php echo $is_edit ? esc_attr($product->submit_selector) : ''; ?>">
                        <p class="description">مثال: button[type="submit"]</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="cookie-login-fields" style="display:none;">
            <h2>تنظیمات کوکی</h2>
            <table class="form-table">
                <tr>
                    <th><label for="cookie_domain">Domain کوکی</label></th>
                    <td>
                        <input type="text" id="cookie_domain" name="cookie_domain" class="regular-text" 
                               value="<?php echo $is_edit ? esc_attr($product->cookie_domain) : ''; ?>">
                        <p class="description">مثال: .google.com</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <table class="form-table">
            <tr>
                <th><label for="description">توضیحات</label></th>
                <td>
                    <textarea id="description" name="description" rows="4" class="large-text"><?php 
                        echo $is_edit ? esc_textarea($product->description) : ''; 
                    ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th><label for="status">وضعیت</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="active" <?php echo $is_edit ? selected($product->status, 'active') : 'selected'; ?>>
                            فعال
                        </option>
                        <option value="inactive" <?php echo $is_edit ? selected($product->status, 'inactive') : ''; ?>>
                            غیرفعال
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="kt_save_product" class="button button-primary">
                <?php echo $is_edit ? 'بروزرسانی محصول' : 'ایجاد محصول'; ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=kt-products'); ?>" class="button">
                انصراف
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    function toggleFields() {
        var method = $('#login_method').val();
        
        $('#form-login-fields').hide();
        $('#cookie-login-fields').hide();
        
        if (method === 'form') {
            $('#form-login-fields').show();
        } else if (method === 'cookie') {
            $('#cookie-login-fields').show();
        }
    }
    
    $('#login_method').change(toggleFields);
    toggleFields();
});
</script>
