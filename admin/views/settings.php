<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('kt_sms_api_key', '');
$template = get_option('kt_sms_template', 'verify');
$extension_enabled = get_option('kt_extension_enabled', '1');
?>

<div class="wrap kt-admin-settings">
    <h1>تنظیمات</h1>
    
    <form method="post">
        <?php wp_nonce_field('kt_settings'); ?>
        
        <h2 class="title">تنظیمات پیامک (کاوه نگار)</h2>
        <table class="form-table">
            <tr>
                <th><label for="kt_sms_api_key">API Key *</label></th>
                <td>
                    <input type="text" id="kt_sms_api_key" name="kt_sms_api_key" 
                           class="regular-text" value="<?php echo esc_attr($api_key); ?>" required>
                    <p class="description">
                        کلید API کاوه نگار خود را از 
                        <a href="https://panel.kavenegar.com/client/api/index" target="_blank">پنل کاوه نگار</a> 
                        دریافت کنید
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label for="kt_sms_template">نام الگو (Template)</label></th>
                <td>
                    <input type="text" id="kt_sms_template" name="kt_sms_template" 
                           class="regular-text" value="<?php echo esc_attr($template); ?>">
                    <p class="description">
                        نام الگوی تأییدیه که در پنل کاوه نگار ایجاد کرده‌اید (پیش‌فرض: verify)
                    </p>
                </td>
            </tr>
        </table>
        
        <h2 class="title">تنظیمات Extension</h2>
        <table class="form-table">
            <tr>
                <th><label for="kt_extension_enabled">فعال‌سازی Extension</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="kt_extension_enabled" name="kt_extension_enabled" 
                               value="1" <?php checked($extension_enabled, '1'); ?>>
                        API برای Chrome Extension فعال باشد
                    </label>
                </td>
            </tr>
        </table>
        
        <h2 class="title">اطلاعات API</h2>
        <table class="form-table">
            <tr>
                <th>Base URL</th>
                <td>
                    <code><?php echo rest_url('knowtech/v1/'); ?></code>
                    <p class="description">
                        <a href="<?php echo rest_url('knowtech/v1/'); ?>" target="_blank">
                            مشاهده مستندات API
                        </a>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th>Endpoints</th>
                <td>
                    <ul style="list-style:disc;margin-right:20px;">
                        <li><code>POST /auth/send-otp</code> - ارسال کد تأیید</li>
                        <li><code>POST /auth/verify-otp</code> - تأیید کد</li>
                        <li><code>GET /subscriptions</code> - لیست اشتراک‌ها</li>
                        <li><code>GET /subscriptions/{id}/credentials</code> - دریافت اطلاعات ورود</li>
                        <li><code>POST /subscriptions/{id}/toggle</code> - فعال/غیرفعال کردن</li>
                        <li><code>GET /products</code> - لیست محصولات</li>
                    </ul>
                </td>
            </tr>
        </table>
        
        <h2 class="title">تنظیمات امنیتی</h2>
        <table class="form-table">
            <tr>
                <th>کلید رمزنگاری</th>
                <td>
                    <?php 
                    $has_key = get_option('kt_encryption_key');
                    if ($has_key): 
                    ?>
                        <span style="color:green;">✓ تنظیم شده</span>
                        <p class="description">
                            رمزهای عبور اشتراک‌ها با کلید رمزنگاری AES-256 ذخیره می‌شوند
                        </p>
                    <?php else: ?>
                        <span style="color:orange;">! در اولین ذخیره‌سازی رمز عبور ایجاد خواهد شد</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="kt_save_settings" class="button button-primary">
                ذخیره تنظیمات
            </button>
        </p>
    </form>
    
    <div class="kt-info-box" style="margin-top: 30px;">
        <h3>راهنمای استفاده:</h3>
        <ol style="margin-right: 20px;">
            <li>ابتدا API Key کاوه نگار را در بالا وارد کنید</li>
            <li>یک الگوی پیامک در پنل کاوه نگار با نام <code>verify</code> ایجاد کنید</li>
            <li>متن الگو: <code>کد تأیید شما: {token}</code></li>
            <li>محصولات (سرویس‌ها) را از منوی "محصولات" تعریف کنید</li>
            <li>محصولات WooCommerce را به محصولات KnowTech متصل کنید</li>
            <li>Chrome Extension را نصب کنید و کاربران با موبایل وارد شوند</li>
        </ol>
    </div>
</div>
