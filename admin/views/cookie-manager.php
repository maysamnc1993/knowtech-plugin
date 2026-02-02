<?php
/**
 * Cookie Manager - Admin Page
 * مدیریت Cookie های Auto-Login
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kt_save_cookies_nonce'])) {
    if (wp_verify_nonce($_POST['kt_save_cookies_nonce'], 'kt_save_cookies')) {
        
        $pool_account_id = intval($_POST['pool_account_id']);
        $cookies_json = stripslashes($_POST['cookies']);
        
        $cookies = json_decode($cookies_json, true);
        
        if ($cookies && is_array($cookies) && $pool_account_id) {
            global $wpdb;
            
            $result = $wpdb->update(
                $wpdb->prefix . 'kt_account_pool',
                array('session_cookies' => $cookies_json),
                array('id' => $pool_account_id)
            );
            
            if ($result !== false) {
                echo '<div class="notice notice-success"><p><strong>✅ موفق!</strong> ' . count($cookies) . ' Cookie ذخیره شد.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p><strong>❌ خطا:</strong> ' . $wpdb->last_error . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p><strong>❌ خطا:</strong> داده‌های نامعتبر</p></div>';
        }
    }
}

// Get pool accounts
global $wpdb;
$accounts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kt_account_pool ORDER BY id ASC");
?>

<div class="wrap">
    <h1>🔐 مدیریت Cookie های Auto-Login</h1>
    <p class="description">برای ورود خودکار کاربران، Cookie های Session رو از اکانت‌های Pool ذخیره کنید.</p>
    
    <hr class="wp-header-end">
    
    <!-- Guide Box -->
    <div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">📖 راهنمای سریع</h2>
        <ol style="font-size: 14px; line-height: 1.8;">
            <li><strong>Login کنید:</strong> با اکانت Pool به سرویس (مثلاً ChatGPT) Login کنید</li>
            <li><strong>DevTools باز کنید:</strong> F12 → Tab "Application" → بخش "Cookies"</li>
            <li><strong>Cookie ها را Copy کنید:</strong> تمام Cookie های سایت رو کپی کنید</li>
            <li><strong>Paste کنید:</strong> در فرم پایین Paste کنید و ذخیره کنید</li>
        </ol>
    </div>
    
    <!-- Cookie Extractor Tool -->
    <div class="card" style="max-width: none; margin: 20px 0; padding: 20px;">
        <h2>🔧 ابزار استخراج Cookie</h2>
        <p>این ابزار به شما کمک میکنه Cookie ها رو به راحتی استخراج کنید.</p>
        
        <div class="cookie-steps" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <h3>مرحله 1: Login به سرویس</h3>
            <p>با اکانت Pool به سرویس مورد نظر Login کنید:</p>
            <ul style="list-style: disc; margin-right: 20px;">
                <?php foreach ($accounts as $account): ?>
                    <li>
                        <strong><?php echo esc_html($account->product_name); ?>:</strong>
                        <a href="<?php echo esc_url($account->login_url ?: 'https://chatgpt.com/'); ?>" target="_blank" class="button button-small">
                            🔗 باز کردن <?php echo esc_html($account->product_name); ?>
                        </a>
                        <code style="background: #fff; padding: 5px 10px; border-radius: 4px; margin: 0 10px;">
                            <?php echo esc_html($account->account_username); ?>
                        </code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="cookie-steps" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <h3>مرحله 2: باز کردن DevTools</h3>
            <ol style="list-style: decimal; margin-right: 20px; line-height: 1.8;">
                <li>روی صفحه‌ای که Login کردید، کلید <code>F12</code> را بزنید</li>
                <li>به Tab <strong>"Application"</strong> بروید (در Firefox: "Storage")</li>
                <li>در سمت چپ: <strong>Storage → Cookies</strong></li>
                <li>روی دامنه سایت کلیک کنید (مثلاً <code>chatgpt.com</code>)</li>
            </ol>
        </div>
        
        <div class="cookie-steps" style="background: #d4edda; padding: 20px; border-radius: 8px; border: 2px solid #00a32a; margin: 15px 0;">
            <h3>✅ روش ساده: استفاده از DevTools</h3>
            
            <p style="font-size: 16px; line-height: 1.8;"><strong>این روش همیشه کار میکنه و خیلی ساده‌ست!</strong></p>
            
            <ol style="font-size: 15px; line-height: 2; margin-right: 20px;">
                <li><strong>Login به سرویس:</strong>
                    <br>با اکانت Pool به ChatGPT Login کن
                    <br><a href="https://chatgpt.com/" target="_blank" class="button button-small">🔗 باز کردن ChatGPT</a>
                    <br><br>
                </li>
                
                <li><strong>باز کردن DevTools:</strong>
                    <br>کلید <code>F12</code> بزن
                    <br><br>
                </li>
                
                <li><strong>رفتن به Application:</strong>
                    <br>Tab "Application" → سمت چپ: "Storage" → "Cookies" → کلیک روی <code>https://chatgpt.com</code>
                    <br><br>
                </li>
                
                <li><strong>انتخاب همه Cookie ها:</strong>
                    <br>روی اولین Cookie کلیک کن → <code>Ctrl+A</code> (Select All)
                    <br><br>
                </li>
                
                <li><strong>Copy کردن:</strong>
                    <br>کلیک راست → <strong>Copy</strong> (یا <code>Ctrl+C</code>)
                    <br><br>
                </li>
                
                <li><strong>تبدیل به JSON:</strong>
                    <br>روی دکمه پایین کلیک کن تا Converter باز بشه
                    <br><button type="button" class="button button-primary button-large" onclick="document.getElementById('converter-tool').style.display='block'; this.style.display='none';">
                        🔄 باز کردن Cookie Converter
                    </button>
                    <br><br>
                </li>
            </ol>
        </div>
        
        <!-- Cookie Converter Tool -->
        <div id="converter-tool" style="display: none; background: #f0f6fc; padding: 20px; border-radius: 8px; border: 2px solid #0073aa; margin: 15px 0;">
            <h3>🔄 ابزار تبدیل Cookie به JSON</h3>
            
            <div style="margin: 15px 0;">
                <label for="raw-cookies" style="display: block; font-weight: bold; margin-bottom: 10px;">
                    مرحله 1: Cookie های Copy شده رو اینجا Paste کن:
                </label>
                <textarea id="raw-cookies" rows="8" class="large-text code" placeholder="مثال:
__Secure-next-auth.session-token	ey...	.chatgpt.com	/	2025-12-20...	✓	✓	None	High
__Secure-next-auth.callback-url	...	.chatgpt.com	/	2025-12-20...	✓	✓	Lax	High"></textarea>
            </div>
            
            <div style="margin: 15px 0;">
                <button type="button" class="button button-primary" onclick="convertCookies()">
                    ⚙️ تبدیل به JSON
                </button>
            </div>
            
            <div id="conversion-result" style="margin-top: 15px;"></div>
        </div>
        
        <script>
        function convertCookies() {
            const raw = document.getElementById('raw-cookies').value.trim();
            const result = document.getElementById('conversion-result');
            
            if (!raw) {
                result.innerHTML = '<div class="notice notice-error"><p>❌ لطفاً Cookie ها رو Paste کنید!</p></div>';
                return;
            }
            
            try {
                const lines = raw.split('\n').filter(line => line.trim());
                const cookies = [];
                
                for (const line of lines) {
                    // Split by tab
                    const parts = line.split('\t').map(p => p.trim());
                    
                    if (parts.length >= 2) {
                        const cookie = {
                            name: parts[0],
                            value: parts[1],
                            domain: parts[2] || '.chatgpt.com',
                            path: parts[3] || '/',
                            secure: parts[5] === '✓' || parts[5] === 'true' || true,
                            httpOnly: parts[6] === '✓' || parts[6] === 'true' || true,
                            sameSite: parts[7] && parts[7] !== '✓' ? parts[7].toLowerCase() : 'lax'
                        };
                        
                        // Add expiration if available
                        if (parts[4] && parts[4] !== '✓' && parts[4] !== 'Session') {
                            try {
                                const exp = new Date(parts[4]).getTime() / 1000;
                                if (!isNaN(exp)) {
                                    cookie.expirationDate = exp;
                                }
                            } catch (e) {}
                        }
                        
                        cookies.push(cookie);
                    }
                }
                
                if (cookies.length === 0) {
                    result.innerHTML = '<div class="notice notice-error"><p>❌ هیچ Cookie معتبری یافت نشد!</p></div>';
                    return;
                }
                
                const json = JSON.stringify(cookies, null, 2);
                
                result.innerHTML = `
                    <div class="notice notice-success">
                        <p><strong>✅ موفق!</strong> ${cookies.length} Cookie تبدیل شد.</p>
                    </div>
                    
                    <label style="display: block; font-weight: bold; margin: 15px 0 10px 0;">
                        مرحله 2: این JSON رو Copy کن:
                    </label>
                    <textarea readonly class="large-text code" rows="10" onclick="this.select()">${json}</textarea>
                    
                    <button type="button" class="button button-primary" onclick="copyToClipboard('${json.replace(/'/g, "\\'")}')">
                        📋 Copy به Clipboard
                    </button>
                    
                    <button type="button" class="button button-secondary" onclick="document.getElementById('cookies').value = \`${json.replace(/`/g, '\\`')}\`; document.getElementById('cookies').scrollIntoView({behavior: 'smooth'});">
                        ⬇️ Paste مستقیم در فرم پایین
                    </button>
                `;
                
            } catch (error) {
                result.innerHTML = `<div class="notice notice-error"><p>❌ خطا: ${error.message}</p></div>`;
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ کپی شد! حالا در فرم پایین Paste کن.');
            }).catch(err => {
                alert('❌ خطا در کپی کردن');
            });
        }
        </script>
        
        <div class="cookie-steps" style="background: #e7f5ff; padding: 20px; border-radius: 8px; border: 2px solid #0073aa; margin: 15px 0;">
            <h3>🔄 روش جایگزین (همه مرورگرها)</h3>
            <p>اگر کد بالا کار نکرد، از این روش استفاده کنید:</p>
            <ol style="list-style: decimal; margin-right: 20px; line-height: 1.8;">
                <li>در DevTools → Application → Cookies</li>
                <li>تمام Cookie ها رو Select کنید (Ctrl+A)</li>
                <li>Copy کنید (Ctrl+C)</li>
                <li>در یک Text Editor paste کنید</li>
                <li>هر خط به این فرمت تبدیل کنید:
                    <pre style="background: #fff; padding: 10px; margin: 10px 0; border-radius: 4px;">{"name": "cookie_name", "value": "cookie_value", "domain": ".chatgpt.com", "path": "/", "secure": true, "httpOnly": true}</pre>
                </li>
                <li>همه رو در یک Array قرار بدید: <code>[{...}, {...}]</code></li>
            </ol>
        </div>
    </div>
    
    <!-- Save Form -->
    <div class="card" style="max-width: none; margin: 20px 0;">
        <h2>💾 ذخیره Cookie ها</h2>
        
        <form method="POST" action="">
            <?php wp_nonce_field('kt_save_cookies', 'kt_save_cookies_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pool_account_id">اکانت Pool</label>
                    </th>
                    <td>
                        <select name="pool_account_id" id="pool_account_id" class="regular-text" required>
                            <option value="">-- انتخاب کنید --</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account->id; ?>">
                                    <?php echo esc_html($account->product_name); ?> - 
                                    <?php echo esc_html($account->account_username); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">اکانت Pool که Cookie ها متعلق به اون هست</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cookies">Cookie ها (JSON)</label>
                    </th>
                    <td>
                        <textarea name="cookies" id="cookies" rows="10" class="large-text code" required placeholder='[
  {
    "name": "__Secure-next-auth.session-token",
    "value": "...",
    "domain": ".chatgpt.com",
    "path": "/",
    "secure": true,
    "httpOnly": true
  }
]'></textarea>
                        <p class="description">
                            Cookie ها را به فرمت JSON وارد کنید. 
                            <button type="button" class="button button-small" onclick="validateCookies()">🔍 بررسی</button>
                        </p>
                        <div id="cookie-validation" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary button-large">
                    💾 ذخیره Cookie ها
                </button>
            </p>
        </form>
    </div>
    
    <!-- Current Cookies Status -->
    <div class="card" style="max-width: none; margin: 20px 0;">
        <h2>📊 وضعیت فعلی Cookie ها</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>اکانت Pool</th>
                    <th>Username</th>
                    <th>تعداد Cookie</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><strong><?php echo esc_html($account->product_name); ?></strong></td>
                        <td><code><?php echo esc_html($account->account_username); ?></code></td>
                        <td>
                            <?php
                            if (!empty($account->session_cookies)) {
                                $cookies = json_decode($account->session_cookies, true);
                                if ($cookies && is_array($cookies)) {
                                    echo count($cookies) . ' Cookie';
                                } else {
                                    echo '<span style="color: #d63638;">❌ نامعتبر</span>';
                                }
                            } else {
                                echo '<span style="color: #999;">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($account->session_cookies)) {
                                $cookies = json_decode($account->session_cookies, true);
                                if ($cookies && is_array($cookies)) {
                                    // Check for important cookies
                                    $has_session = false;
                                    foreach ($cookies as $cookie) {
                                        if (strpos($cookie['name'], 'session') !== false || 
                                            strpos($cookie['name'], 'auth') !== false) {
                                            $has_session = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($has_session) {
                                        echo '<span style="color: #00a32a;">✅ فعال</span>';
                                    } else {
                                        echo '<span style="color: #dba617;">⚠️ ناقص</span>';
                                    }
                                } else {
                                    echo '<span style="color: #d63638;">❌ خطا</span>';
                                }
                            } else {
                                echo '<span style="color: #999;">❌ ندارد</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="button button-small" onclick="viewCookies(<?php echo $account->id; ?>)">
                                👁️ مشاهده
                            </button>
                            <?php if (!empty($account->session_cookies)): ?>
                                <button class="button button-small button-link-delete" onclick="deleteCookies(<?php echo $account->id; ?>)">
                                    🗑️ حذف
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function copyCookieScript() {
    const script = document.getElementById('cookie-script').textContent;
    navigator.clipboard.writeText(script).then(() => {
        alert('✅ کد کپی شد! حالا در Console Paste کنید.');
    });
}

function validateCookies() {
    const textarea = document.getElementById('cookies');
    const validation = document.getElementById('cookie-validation');
    
    try {
        const cookies = JSON.parse(textarea.value);
        
        if (!Array.isArray(cookies)) {
            throw new Error('باید یک Array باشد');
        }
        
        validation.innerHTML = `
            <div class="notice notice-success inline">
                <p>✅ معتبر است! ${cookies.length} Cookie شناسایی شد.</p>
            </div>
        `;
        
        // Check for important cookies
        const important = cookies.filter(c => 
            c.name.includes('session') || 
            c.name.includes('auth') ||
            c.name.includes('token')
        );
        
        if (important.length > 0) {
            validation.innerHTML += `
                <div class="notice notice-info inline">
                    <p>ℹ️ ${important.length} Cookie مهم برای احراز هویت یافت شد.</p>
                </div>
            `;
        } else {
            validation.innerHTML += `
                <div class="notice notice-warning inline">
                    <p>⚠️ هیچ Cookie احراز هویتی یافت نشد. مطمئن شوید که Login کرده‌اید.</p>
                </div>
            `;
        }
        
    } catch (error) {
        validation.innerHTML = `
            <div class="notice notice-error inline">
                <p>❌ فرمت نامعتبر: ${error.message}</p>
            </div>
        `;
    }
}

function viewCookies(accountId) {
    // TODO: Implement view modal
    alert('این قابلیت به زودی اضافه میشه');
}

function deleteCookies(accountId) {
    if (confirm('آیا مطمئنید که میخواهید Cookie ها را حذف کنید؟')) {
        // TODO: Implement delete via AJAX
        alert('این قابلیت به زودی اضافه میشه');
    }
}
</script>

<style>
.cookie-steps h3 {
    margin-top: 0;
    color: #0073aa;
}

.cookie-steps code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.cookie-steps pre {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}

#cookie-validation .notice {
    padding: 10px 15px;
    margin: 10px 0;
}
</style>
<?php
