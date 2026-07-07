# ملفات Tager للفواتير وطلبات الشراء

هذه الملفات جاهزة للتركيب في منصة أونلاين بدون كسر المنصة.

## الملفات المهمة

- `invoice.html` قالب فاتورة المورد / العميل
- `purchase-order.html` قالب طلب الشراء
- `assets/css/tager-print.css` تنسيق العرض والطباعة
- `assets/js/tager-print.js` قراءة البيانات وعرضها
- `integration/integration-snippet.js` كود الربط مع المنصة

## طريقة التركيب السريعة

1. ارفع المجلد باسم `tager-print` داخل `public` أو بجانب ملفات المنصة.
2. عند الضغط على طباعة فاتورة:
   - خزّن بيانات الطلب في `localStorage` باسم `tager_print_payload`
   - افتح `/tager-print/invoice.html`
3. عند الضغط على طباعة طلب:
   - افتح `/tager-print/purchase-order.html`

## الطباعة

القوالب مضبوطة على:
- A4 Portrait
- RTL عربي
- إخفاء أزرار الطباعة في وضع الطباعة
- ألوان Tager الأخضر والبرتقالي
