# Tager Platform — الهجرة من localStorage إلى Supabase

## الخطوات بالترتيب

### 1️⃣ إنشاء مشروع Supabase
- اذهب إلى [supabase.com](https://supabase.com) → أنشئ مشروع جديد
- من **Project Settings → API** انسخ:
  - `Project URL` (مثال: `https://xyiyjepwqhukvdnohpgc.supabase.co`)
  - `anon public` key

### 2️⃣ تشغيل Schema في Supabase
- افتح **SQL Editor** في Supabase Dashboard
- انسخ محتوى `supabase_schema.sql` بالكامل
- اضغط **Run** → ستنشأ كل الجداول + السياسات + البذور

### 3️⃣ تحديث ملف الـ Bridge
- افتح `tager-supabase-bridge.js`
- استبدل `PLACEHOLDER_ANON_KEY` بالـ anon key الحقيقي
- استبدل الـ URL إذا مختلف

### 4️⃣ إضافة Bridge إلى index.html
أضف هذين السطرين في `<head>` أو قبل السكربت الرئيسي:

```html
<script src="https://unpkg.com/@supabase/supabase-js@2"></script>
<script src="tager-supabase-bridge.js"></script>
```

### 5️⃣ نقل البيانات الموجودة
- افتح المنصة في المتصفح (ensure you have data in localStorage)
- افتح **DevTools Console** (F12)
- انسخ محتوى `tager-migrate-data.js` والصقه في الكونسول
- أدخل Supabase URL و Anon Key عند الطلب
- انتظر حتى تظهر رسالة "Migration complete"

### 6️⃣ التحقق
- افتح **Table Editor** في Supabase
- تأكد من وجود البيانات في الجداول
- أعد تحميل المنصة → يجب أن تعمل مع Supabase

---

## الجداول المنشأة

| الجدول | الوصف |
|--------|-------|
| `platform_settings` | إعدادات المنصة |
| `categories` | الفئات (12 فئة جاهزة) |
| `users` | المستخدمون (admin, vendor, customer, staff) |
| `vendors` | بيانات الموردين |
| `vendor_delivery_zones` | مناطق التوصيل لكل مورد |
| `products` | المنتجات مع 4 أسعار |
| `orders` | الطلبات |
| `order_items` | بنود الطلبات |
| `deliveries` | التوصيلات |
| `commission_payments` | المدفوعات |
| `financial_entries` | السجل المالي |
| `support_tickets` | تذاكر الدعم |
| `audit_logs` | سجل التدقيق |
| `invoices` | الفواتير |
| `invoice_items` | بنود الفواتير |
| `return_requests` | طلبات المرتجعات |
| `quote_requests` | طلبات الأسعار |
| `promotions` | العروض والكوبونات |
| `customer_favorites` | المفضلات |
| `stock_movements` | حركات المخزون |
| `supplier_documents` | مستندات الموردين |

## سياسات الأمان (RLS)

- **الزوار**: يمكنهم رؤية المنتجات المعتمدة والموردين المعتمدين والفئات
- **العملاء**: يرون طلباتهم فقط
- **الموردين**: يرون منتجاتهم وطلباتهم وتوصيلاتهم
- **الإدارة**: صلاحية كاملة على كل شيء
- **التسجيل**: مسموح بدون تسجيل دخول (anon insert)

## ملاحظات مهمة

- الـ cart (السلة) يبقى في localStorage لأنه مؤقت
- الكود الأصلي في index.html **لا يتغيّر** — الـ Bridge يربطه تلقائياً
- أول تحميل بعد الترحيل قد يكون بطيئاً (sync من Supabase)
- بعد الترحيل، كل العمليات (إضافة منتج، طلب، إلخ) تُحفظ محلياً + تُرسل لـ Supabase
