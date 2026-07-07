# إصلاح آمن لقوالب Tager — فاتورة المورد وطلب الشراء

راجعت الحزمة السابقة. سبب كسر المنصة غالبًا أن ملفات الفاتورة وطلب الشراء كانت **ملفات HTML/CSS/JS مستقلة**، ولو تم وضعها داخل المنصة مباشرة أو تم رفع `index.html` في جذر المشروع، ممكن تغيّر الصفحة الرئيسية أو تؤثر على تصميم المنصة بالكامل.

هذه الحزمة الجديدة معمولة بطريقة آمنة:

- لا يوجد `index.html` في جذر الحزمة حتى لا يستبدل الصفحة الرئيسية للمنصة.
- CSS معزول بالكامل تحت `.tgr-doc-scope` ولا يغيّر تصميم المنصة.
- JavaScript معزول ولا يكتب في صفحات المنصة كلها.
- الصور والأصول داخل `public/tager-documents` فقط.
- يوجد تكامل آمن مع Next.js عن طريق iframe أو رابط طباعة.

## أفضل طريقة آمنة للتركيب داخل منصة Next.js / Vercel

### 1) انسخ هذا المجلد فقط إلى مشروع المنصة

انسخ:

```txt
public/tager-documents
```

ليصبح داخل المنصة:

```txt
your-platform/public/tager-documents
```

بعد ذلك افتح:

```txt
/tager-documents/index.html
/tager-documents/invoice.html
/tager-documents/purchase-order.html
```

## 2) زر طباعة الفاتورة داخل المنصة

استخدم الرابط بهذا الشكل من أي صفحة طلب:

```jsx
const data = {
  orderNo: order.orderNo,
  invoiceNo: order.invoiceNo,
  date: order.date,
  platform: 'منصة تاجر',
  customer: order.customerName,
  supplier: order.supplierName,
  governorate: order.governorate,
  center: order.center,
  paymentMethod: order.paymentMethod,
  currency: 'ج.م',
  status: order.status,
  items: order.items.map((item) => ({
    name: item.name,
    priceType: item.priceType || 'سعر الشراء',
    quantity: item.quantity,
    unitPrice: item.unitPrice,
    total: item.quantity * item.unitPrice
  })),
  totals: {
    products: order.productsTotal,
    discount: order.discount || 0,
    tax: order.tax || 0,
    grandTotal: order.grandTotal
  }
};

const invoiceUrl = `/tager-documents/invoice.html?data=${encodeURIComponent(JSON.stringify(data))}`;

<a href={invoiceUrl} target="_blank" rel="noreferrer">طباعة الفاتورة</a>
```

## 3) زر طباعة طلب الشراء

```jsx
const purchaseOrderUrl = `/tager-documents/purchase-order.html?data=${encodeURIComponent(JSON.stringify(data))}`;

<a href={purchaseOrderUrl} target="_blank" rel="noreferrer">طباعة طلب الشراء</a>
```

## 4) طريقة iframe داخل المنصة

انسخ من:

```txt
nextjs-integration/components/TagerDocumentFrame.jsx
```

إلى:

```txt
components/TagerDocumentFrame.jsx
```

ثم استخدمه:

```jsx
import TagerDocumentFrame from '@/components/TagerDocumentFrame';

export default function InvoicePreview({ order }) {
  return <TagerDocumentFrame type="invoice" data={order} />;
}
```

## مهم جدًا

لا ترفع ملفات HTML القديمة في جذر المنصة. لا تستبدل ملفات:

```txt
index.html
package.json
next.config.js
pages
app
components
```

استخدم فقط `public/tager-documents` أو ملفات التكامل الموجودة في `nextjs-integration`.
