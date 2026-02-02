# KnowTech Subscription Manager

## 📋 توضیحات

پلاگین مدیریت کامل اشتراک‌ها برای سایت‌های فروش اشتراک سرویس‌های آنلاین (مثل ChatGPT، Midjourney، Claude و...)

### ویژگی‌ها:

✅ **مدیریت کامل اشتراک‌ها**
- ایجاد، ویرایش و حذف اشتراک‌ها
- پشتیبانی از مدت‌های مختلف (1، 3، 6، 12 ماه)
- محاسبه خودکار تاریخ انقضا
- نمایش پیشرفت Timeline

✅ **یکپارچگی با WooCommerce**
- اتصال محصولات WooCommerce به سرویس‌ها
- ایجاد خودکار اشتراک پس از خرید
- نمایش اشتراک‌ها در پنل کاربری

✅ **احراز هویت با موبایل**
- ورود با شماره موبایل + OTP
- پشتیبانی از Kaveh Negar SMS
- Token-based authentication

✅ **REST API کامل**
- Endpoints برای Chrome Extension
- دریافت لیست اشتراک‌ها
- دریافت اطلاعات ورود (Username/Password)
- فعال/غیرفعال کردن Auto-Login

✅ **Auto-Login System**
- پشتیبانی از روش‌های مختلف ورود:
  - فرم (Username/Password)
  - Cookie Injection
  - API Token
  - Custom Script
- رمزنگاری AES-256 برای پسوردها

✅ **پنل مدیریت حرفه‌ای**
- داشبورد با آمار لحظه‌ای
- مدیریت محصولات (سرویس‌ها)
- لیست اشتراک‌ها با فیلتر و جستجو
- تنظیمات یکپارچه

---

## 📥 نصب و راه‌اندازی

### نیازمندی‌ها:
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 3.0+ (اختیاری)
- SSL Certificate (برای API)

### مراحل نصب:

1. **آپلود پلاگین:**
```bash
wp-content/plugins/knowtech-subscriptions/
```

2. **فعال‌سازی:**
از بخش Plugins در وردپرس، پلاگین را فعال کنید.

3. **تنظیمات اولیه:**
   - بروید به: `اشتراک‌ها > تنظیمات`
   - API Key کاوه نگار را وارد کنید
   - نام Template پیامک را تنظیم کنید (پیش‌فرض: `verify`)

4. **ایجاد Template پیامک:**
در پنل کاوه نگار یک Template با نام `verify` ایجاد کنید:
```
کد تأیید شما: {token}
```

5. **تعریف محصولات:**
   - بروید به: `اشتراک‌ها > محصولات`
   - محصولات پیش‌فرض (ChatGPT، Midjourney و...) از قبل ایجاد شده‌اند
   - می‌توانید محصول جدید اضافه کنید

6. **اتصال به WooCommerce:**
   - یک محصول WooCommerce ایجاد کنید
   - در بخش `تنظیمات اشتراک KnowTech`:
     - تیک "این محصول یک اشتراک است" را بزنید
     - سرویس مرتبط را انتخاب کنید
     - مدت اشتراک را تعیین کنید

---

## 🔌 API Documentation

### Base URL:
```
https://your-site.com/wp-json/knowtech/v1/
```

### Authentication:
از Bearer Token استفاده می‌شود:
```
Authorization: Bearer {token}
```

### Endpoints:

#### 1. ارسال OTP
```http
POST /auth/send-otp
Content-Type: application/json

{
  "phone": "09123456789"
}
```

**Response:**
```json
{
  "success": true,
  "message": "کد تأیید به شماره 09123456789 ارسال شد"
}
```

#### 2. تأیید OTP
```http
POST /auth/verify-otp
Content-Type: application/json

{
  "phone": "09123456789",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "ورود موفقیت‌آمیز بود",
  "token": "abc123...",
  "user": {
    "id": 5,
    "name": "میثم",
    "phone": "09123456789",
    "email": "meysam@example.com"
  }
}
```

#### 3. لیست اشتراک‌ها
```http
GET /subscriptions
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "subscriptions": [
    {
      "id": 1,
      "title": "ChatGPT Plus - 6 ماهه",
      "brand": "OpenAI",
      "product_id": 1,
      "start": "1404/05/05",
      "end": "1404/11/05",
      "progress": 28,
      "active": true,
      "expired": false,
      "status": "active",
      "has_credentials": true
    }
  ]
}
```

#### 4. اطلاعات ورود
```http
GET /subscriptions/{id}/credentials
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "credentials": {
    "product_id": 1,
    "product_name": "ChatGPT Plus",
    "login_url": "https://chat.openai.com/auth/login",
    "login_method": "form",
    "username": "user@example.com",
    "password": "pass123",
    "selectors": {
      "username": "input[name='username']",
      "password": "input[type='password']",
      "submit": "button[type='submit']"
    }
  }
}
```

#### 5. فعال/غیرفعال کردن
```http
POST /subscriptions/{id}/toggle
Authorization: Bearer {token}
Content-Type: application/json

{
  "enabled": true
}
```

---

## 🎨 سناریوهای استفاده

### سناریو 1: خرید اشتراک ChatGPT

1. کاربر محصول "اشتراک 3 ماهه ChatGPT" را می‌خرد
2. پس از تکمیل پرداخت، اشتراک خودکار ایجاد می‌شود
3. کاربر Chrome Extension را نصب می‌کند
4. با شماره موبایل + OTP وارد می‌شود
5. لیست اشتراک‌هایش را می‌بیند
6. روی "ChatGPT" کلیک می‌کند
7. Extension مستقیماً او را لاگین می‌کند

### سناریو 2: مدیریت اشتراک‌ها

1. ادمین وارد پنل می‌شود
2. از `اشتراک‌ها > همه اشتراک‌ها` لیست را می‌بیند
3. اشتراک‌های در حال انقضا را فیلتر می‌کند
4. برای کاربر پیام یادآوری می‌فرستد

---

## 🔒 امنیت

### رمزنگاری:
- تمام پسوردها با AES-256-CBC رمزنگاری می‌شوند
- کلید رمزنگاری به صورت خودکار تولید می‌شود
- Tokens معتبر برای 30 روز

### احراز هویت:
- OTP با محدودیت 3 تلاش
- Token-based authentication
- Nonce verification در تمام AJAX calls

---

## 📊 Database Schema

### kt_products
```sql
- id
- name (ChatGPT Plus)
- slug (chatgpt-plus)
- brand (OpenAI)
- login_url
- login_method (form/cookie/api)
- username_selector
- password_selector
- submit_selector
- status (active/inactive)
```

### kt_subscriptions
```sql
- id
- user_id
- product_id
- order_id
- service_username
- service_password (encrypted)
- start_date
- end_date
- duration_months
- status (active/expired)
- auto_login_enabled
```

---

## 🐛 عیب‌یابی

### مشکل: SMS ارسال نمی‌شود
✅ API Key را چک کنید
✅ نام Template را بررسی کنید
✅ موجودی حساب کاوه نگار را چک کنید

### مشکل: اشتراک ایجاد نمی‌شود
✅ محصول WooCommerce به محصول KnowTech متصل است؟
✅ وضعیت سفارش `completed` یا `processing` است؟

### مشکل: Extension متصل نمی‌شود
✅ SSL فعال است؟
✅ REST API فعال است؟ (test: `/wp-json/knowtech/v1/`)

---

## 📞 پشتیبانی

- وب‌سایت: https://knowtech.me
- ایمیل: support@knowtech.me

---

## 📝 نسخه

**نسخه:** 1.0.0  
**تاریخ:** 2024-12-03
**سازنده:** Meysam Khatami

---

## 🚀 مراحل بعدی

پس از نصب پلاگین، مرحله بعدی توسعه Chrome Extension است که:
- از API این پلاگین استفاده می‌کند
- ورود با موبایل دارد
- لیست اشتراک‌ها را نمایش می‌دهد
- قابلیت Auto-Login دارد
# knowtech-plugin
