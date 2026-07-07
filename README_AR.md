# Tager Invoice & Purchase Order Templates

هذه الحزمة تحتوي على ملفات جاهزة للرفع على GitHub Pages أو دمجها داخل منصة Tager:

- `invoice.html` قالب فاتورة مورد قابل للتعديل من البيانات.
- `purchase-order.html` قالب طلب شراء قابل للتعديل من البيانات.
- `assets/css/tager-documents.css` ملف التصميم والطباعة.
- `assets/js/tager-documents.js` ملف إدخال البيانات وطباعة PDF من المتصفح.
- `data/sample-data.json` مثال بيانات يمكن ربطه بالمنصة.
- `assets/reference/` الصور الأصلية كطبقة مرجعية للمراجعة أو للاستخدام كصورة ثابتة.
- `exact/` صفحات تعرض الصور الأصلية كاملة كما هي.

## طريقة الرفع على GitHub Pages

1. فك ضغط الملف.
2. ارفع كل الملفات داخل Repository على GitHub.
3. افتح Settings ثم Pages.
4. اختر Branch: `main` ثم Root.
5. افتح الرابط الناتج وسيعمل `index.html` مباشرة.

## طريقة تغيير البيانات من المنصة

داخل أي صفحة قبل ملف JavaScript يمكن وضع بيانات الطلب أو الفاتورة بهذا الشكل:

```html
<script>
window.TAGER_DOCUMENT_DATA = {
  orderNo: "TG-1001",
  invoiceNo: "INV-1001",
  date: "2026/7/6",
  platform: "منصة تاجر",
  customer: "Gamal Gemy",
  supplier: "شركة الأخوة",
  governorate: "الدقهلية",
  center: "المنصورة",
  paymentMethod: "نقدي عند الاستلام",
  currency: "ج.م",
  status: "جديد",
  items: [
    { name: "بند تجريبي", priceType: "سعر الشراء", quantity: 5, unitPrice: 1000, total: 5000 }
  ],
  totals: { products: 5000, discount: 0, tax: 0, grandTotal: 5000 }
};
</script>
<script src="assets/js/tager-documents.js"></script>
```

## الطباعة

اضغط زر `طباعة / حفظ PDF` داخل الصفحة. الملف مضبوط على A4 ويدعم اتجاه RTL.

## طبقة الصورة الأصلية

داخل صفحات القوالب يوجد زر `إظهار طبقة الصورة الأصلية` للمقارنة بين التصميم المكوّد والصورة الأصلية. هذه الطبقة لا تظهر في الطباعة.
