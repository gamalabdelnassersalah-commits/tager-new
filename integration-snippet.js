
/*
  Tager Print Templates Integration
  ضع مجلد tager-print داخل public أو بجانب ملفات المنصة.
  عند الضغط على طباعة فاتورة أو طلب، استخدم الدوال التالية.
*/

function openTagerInvoice(order) {
  const payload = {
    orderNo: order.orderNo,
    invoiceNo: order.invoiceNo || ('INV-' + order.orderNo),
    date: order.date,
    status: order.statusText || order.status,
    vendorName: order.vendorName,
    customerName: order.customerName,
    customerLocation: [order.governorate, order.district].filter(Boolean).join(' - '),
    governorate: order.governorate,
    district: order.district,
    paymentMethod: order.paymentMethod,
    currency: order.currency || 'ج.م',
    items: order.items.map(i => ({
      name: i.name,
      priceType: i.priceType || i.tierName || 'سعر الشراء',
      qty: i.qty,
      unitPrice: i.unitPrice || i.price,
      total: i.total || (Number(i.qty || 0) * Number(i.unitPrice || i.price || 0))
    })),
    subtotal: order.subtotal,
    discount: order.discount || 0,
    tax: order.tax || 0,
    total: order.total
  };
  localStorage.setItem('tager_print_payload', JSON.stringify(payload));
  window.open('/tager-print/invoice.html', '_blank');
}

function openTagerPurchaseOrder(order) {
  localStorage.setItem('tager_print_payload', JSON.stringify(order));
  window.open('/tager-print/purchase-order.html', '_blank');
}
