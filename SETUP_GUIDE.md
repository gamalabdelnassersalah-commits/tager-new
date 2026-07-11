# دليل التثبيت والإعداد - منصة Tager

## المتطلبات
- Node.js 16+ أو أحدث
- npm أو yarn
- متصفح حديث

## الخطوة 1: الملفات المطلوبة

تأكد من توجود جميع الملفات التالية في مشروعك:

```
src/components/
├── TagerDocument.jsx           ← المكون الرئيسي
├── DataForm.jsx                ← مكون النموذج
└── AdvancedDocumentExample.jsx ← مثال متقدم

src/utils/
└── tager-document-utils.js     ← دوال المساعدة

src/styles/
└── tager-document.module.css   ← الأنماط

app/
└── documents/
    └── page.jsx                ← صفحة Next.js
```

## الخطوة 2: النسخ والاستيراد

### إذا كنت تستخدم Next.js 13+:

```bash
# انسخ الملفات إلى المسارات الصحيحة
cp TagerDocument.jsx src/components/
cp DataForm.jsx src/components/
cp tager-document-utils.js src/utils/
cp tager-document.module.css src/styles/
```

### في صفحتك (app/documents/page.jsx):

```jsx
import TagerDocument from '@/components/TagerDocument';

export const metadata = {
  title: 'منصة Tager',
  description: 'نظام إدارة الفواتير والطلبات'
};

export default function DocumentsPage() {
  return <TagerDocument />;
}
```

## الخطوة 3: التشغيل

```bash
# تشغيل حل التطوير
npm run dev

# الوصول إلى التطبيق
# http://localhost:3000/documents
```

## الإعدادات المتقدمة

### تخصيص الألوان

عدّل `tager-document.module.css`:

```css
:root {
  --tager-teal: #003b45;      /* لونك الأساسي */
  --tager-orange: #ff6500;    /* لون التركيز */
  --tager-teal-2: #005764;
  --tager-teal-dark: #002a32;
}
```

### إضافة خلفية مخصصة

```css
.tgr-document::before {
  background: linear-gradient(135deg, YOUR_COLOR_1, YOUR_COLOR_2);
}
```

### تعديل البيانات الافتراضية

في `tager-document-utils.js`:

```javascript
export const DEFAULT_DOCUMENT_DATA = {
  orderNo: 'TG-1001',        // غيّر رقم الطلب
  invoiceNo: 'INV-1001',     // غيّر رقم الفاتورة
  // ... باقي البيانات
};
```

## الاستخدام مع قاعدة بيانات

### مثال باستخدام Supabase:

```jsx
'use client';

import { useEffect, useState } from 'react';
import { supabase } from '@/lib/supabaseClient';
import TagerDocument from '@/components/TagerDocument';

export default function DocumentsPage() {
  const [documentData, setDocumentData] = useState(null);

  useEffect(() => {
    const fetchDocument = async () => {
      const { data, error } = await supabase
        .from('documents')
        .select('*')
        .eq('id', documentId)
        .single();

      if (data) setDocumentData(data);
    };

    fetchDocument();
  }, []);

  if (!documentData) return <div>جاري التحميل...</div>;

  return <TagerDocument initialData={documentData} />;
}
```

## ميزات خاصة

### الطباعة والحفظ كـ PDF

الزر مدمج مباشرة في المكون:

```jsx
// استخدام Ctrl+P أو اضغط زر "طباعة"
// سيفتح نافذة الطباعة تلقائياً
```

### تصدير واستيراد البيانات

```javascript
import {
  exportToJSON,
  importFromJSON
} from '@/utils/tager-document-utils';

// التصدير
exportToJSON(documentData, 'my-document.json');

// الاستيراد
const file = new File([jsonData], 'document.json');
const data = await importFromJSON(file);
```

### التحقق من البيانات

```javascript
import { validateDocumentData } from '@/utils/tager-document-utils';

const { isValid, errors } = validateDocumentData(data);
if (!isValid) {
  console.error('أخطاء:', errors);
}
```

## حل المشاكل الشائعة

### المشكلة: الاتجاه غير صحيح (LTR بدلاً من RTL)

**الحل:**
```css
.tager-app {
  direction: rtl;
}
```

### المشكلة: الأيقونات غير مرئية

**الحل:**
تأكد من أن SVG لها `stroke="currentColor"` أو `fill="currentColor"`

```jsx
<svg viewBox="0 0 24 24" style={{ color: '#003b45' }}>
  {/* SVG content */}
</svg>
```

### المشكلة: الطباعة تظهر بشكل خاطئ

**الحل:**
يتم التعامل معها تلقائياً عبر `@media print` في CSS

```css
@media print {
  .no-print {
    display: none !important;
  }
}
```

## الأداء والتحسين

### تقليل حجم الملف

إذا كان لديك العديد من المستندات:

```javascript
// استخدم React.memo لتقليل إعادة الرسم
export default React.memo(TagerDocument);
```

### التخزين المؤقت (Caching)

```jsx
'use client';

import { useMemo } from 'react';

export default function Page() {
  const memoizedData = useMemo(() => ({
    // البيانات هنا
  }), []);

  return <TagerDocument initialData={memoizedData} />;
}
```

## التكامل مع API

```jsx
'use client';

import { useEffect, useState } from 'react';
import TagerDocument from '@/components/TagerDocument';

export default function DocumentPage({ params }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`/api/documents/${params.id}`)
      .then(res => res.json())
      .then(data => {
        setData(data);
        setLoading(false);
      });
  }, [params.id]);

  if (loading) return <div>جاري التحميل...</div>;
  if (!data) return <div>لم يتم العثور على المستند</div>;

  return <TagerDocument initialData={data} />;
}
```

## نصائح الإنتاج

### 1. تفعيل HTTPS
```bash
# استخدم بروتوكول HTTPS في الإنتاج
# لضمان أمان البيانات
```

### 2. إضافة رمز تتبع
```jsx
import { trackEvent } from '@/lib/analytics';

const handlePrint = () => {
  trackEvent('document_printed', { documentId });
  window.print();
};
```

### 3. نسخ احتياطي للبيانات
```javascript
const backupData = () => {
  const data = localStorage.getItem('documentData');
  // أرسل إلى API للنسخ الاحتياطي
};
```

## الدعم الفني

للمزيد من المساعدة أو الإبلاغ عن مشاكل:
- اطلب مساعدة في قسم Issues
- تحقق من الوثائق الكاملة

---

**آخر تحديث:** 2026-07-11
**الإصدار:** 1.0.0
