# منصة Tager - نظام إدارة الفواتير والطلبات

## نظرة عامة
منصة متكاملة لإدارة فواتير المورد وطلبات الشراء مع واجهة تفاعلية احترافية.

## المكونات الجديدة

### 1. **TagerDocument.jsx** - المكون الرئيسي
مكون React شامل يعرض طلبات الشراء والفواتير بتصميم احترافي متجاوب.

**الميزات:**
- ✅ عرض طلب شراء وفاتورة مورد
- ✅ تبديل بين النموذجين بسهولة
- ✅ نموذج ديناميكي لتحرير البيانات
- ✅ طباعة وحفظ كـ PDF
- ✅ تصميم RTL كامل (دعم العربية)
- ✅ متجاوب مع جميع الأجهزة

**الاستخدام:**
```jsx
import TagerDocument from '@/components/TagerDocument';

export default function Page() {
  return <TagerDocument />;
}
```

### 2. **DataForm.jsx** - مكون النموذج
مكون منفصل لإدارة نموذج إدخال البيانات بتنظيم أفضل.

**الأقسام:**
- معلومات الطلب (الرقم، التاريخ، إلخ)
- معلومات الأطراف (عميل، مورد)
- معلومات الموقع (محافظة، مركز)
- معلومات المستخدم (الموقّع)
- الإجماليات

### 3. **tager-document-utils.js** - ملف المساعدات
يحتوي على دوال مساعدة ومرافق للعمل مع البيانات.

**الدوال المتاحة:**
- `formatNumber(value)` - تنسيق الأرقام
- `numberToArabicWords(num)` - تحويل الأرقام إلى كلمات عربية
- `calculateTotals(items)` - حساب الإجماليات
- `validateDocumentData(data)` - التحقق من صحة البيانات
- `exportToJSON(data)` - تصدير البيانات
- `importFromJSON(file)` - استيراد البيانات

### 4. **tager-document.module.css** - الأنماط
ملف CSS شامل مع:
- متغيرات الألوان (Tager Design System)
- تصميم متجاوب كامل
- أنماط طباعة متقدمة
- تأثيرات ومراوح

**المتغيرات الرئيسية:**
```css
--tager-teal: #003b45
--tager-orange: #ff6500
--tager-teal-2: #005764
--tager-ink: #082c35
```

### 5. **documents-page.jsx** - صفحة Next.js
صفحة جاهزة للاستخدام في Next.js App Router.

## البيانات الافتراضية

```javascript
{
  orderNo: 'TG-1001',
  invoiceNo: 'INV-1001',
  date: '2026/7/6',
  platform: 'منصة تاجر',
  customer: 'Gamal Gemy',
  supplier: 'شركة الأخوة',
  governorate: 'الدقهلية',
  center: 'المنصورة',
  paymentMethod: 'نقدي عند الاستلام',
  currency: 'ج.م',
  status: 'جديد',
  items: [
    {
      name: 'بند تجريبي',
      priceType: 'سعر الشراء',
      quantity: 5,
      unitPrice: 1000,
      total: 5000
    }
  ],
  totals: {
    products: 5000,
    discount: 0,
    tax: 0,
    grandTotal: 5000
  }
}
```

## الميزات المتقدمة

### 1. التصميم المتجاوب
- يعمل بشكل مثالي على الهاتف والتابلت والحاسوب
- تخطيط شبكة ذكي يتكيف مع حجم الشاشة

### 2. الطباعة والحفظ كـ PDF
- زر مدمج لطباعة وحفظ كـ PDF
- تنسيق مخصص للطباعة
- يخفي العناصر غير الضرورية

### 3. النموذج الديناميكي
- تعديل جميع البيانات مباشرة
- التحديث الفوري للعرض
- حفظ النموذج تلقائياً

### 4. الأيقونات والرموز
- رموز SVG مدمجة
- تصميم متسق
- ألوان متناسقة

## التخصيص

### تغيير الألوان
قم بتعديل متغيرات CSS في `tager-document.module.css`:

```css
:root {
  --tager-teal: #003b45;    /* اللون الأساسي */
  --tager-orange: #ff6500;  /* لون التركيز */
}
```

### إضافة حقول جديدة
في `TagerDocument.jsx`، أضف الحقل إلى `formData`:

```javascript
const [formData, setFormData] = useState({
  // الحقول الموجودة...
  customField: 'القيمة الافتراضية' // حقل جديد
});
```

### تعديل البيانات الافتراضية
عدّل `DEFAULT_DOCUMENT_DATA` في `tager-document-utils.js`

## التكامل مع Next.js

### الخطوة 1: استيراد المكون
```jsx
import TagerDocument from '@/components/TagerDocument';
```

### الخطوة 2: استخدام المكون
```jsx
export default function DocumentsPage() {
  return <TagerDocument />;
}
```

### الخطوة 3: إضافة البيانات الديناميكية
```jsx
'use client';
import { useState } from 'react';
import TagerDocument from '@/components/TagerDocument';

export default function DocumentsPage() {
  const [documentData, setDocumentData] = useState({
    // البيانات هنا
  });

  return <TagerDocument initialData={documentData} />;
}
```

## المتطلبات

- React 16.8+
- Next.js 12+ (اختياري)
- متصفح حديث (Chrome, Firefox, Safari, Edge)

## الملفات المرفقة

| الملف | الوصف |
|------|-------|
| `TagerDocument.jsx` | المكون الرئيسي |
| `DataForm.jsx` | مكون النموذج |
| `tager-document-utils.js` | دوال مساعدة |
| `tager-document.module.css` | الأنماط |
| `documents-page.jsx` | صفحة Next.js |

## أمثلة الاستخدام

### استيراد البيانات من JSON
```javascript
import { importFromJSON } from '@/utils/tager-document-utils';

const handleImport = async (file) => {
  const data = await importFromJSON(file);
  setFormData(data);
};
```

### تصدير البيانات
```javascript
import { exportToJSON } from '@/utils/tager-document-utils';

const handleExport = () => {
  exportToJSON(formData, 'document-data.json');
};
```

### التحقق من البيانات
```javascript
import { validateDocumentData } from '@/utils/tager-document-utils';

const { isValid, errors } = validateDocumentData(formData);
if (!isValid) {
  console.error('أخطاء التحقق:', errors);
}
```

## الدعم والتحسينات المستقبلية

- [ ] إضافة المزيد من أنواع المستندات
- [ ] دعم التوقيع الرقمي
- [ ] التخزين السحابي
- [ ] التصدير إلى تنسيقات أخرى (Excel, Word)
- [ ] نظام إدارة المستخدمين

## الترخيص

هذا المشروع مرخص تحت رخصة MIT.

---

**آخر تحديث:** 2026-07-11
**الإصدار:** 1.0.0
