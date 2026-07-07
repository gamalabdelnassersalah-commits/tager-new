import TagerDocumentFrame from '../components/TagerDocumentFrame';

const sampleOrderData = {
  orderNo: 'TG-1001',
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
    { name: 'بند تجريبي', priceType: 'سعر الشراء', quantity: 5, unitPrice: 1000, total: 5000 }
  ],
  totals: { products: 5000, discount: 0, tax: 0, grandTotal: 5000 }
};

export default function PurchaseOrderPrintPage() {
  return <TagerDocumentFrame type="purchase-order" data={sampleOrderData} />;
}
