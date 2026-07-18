/**
 * قواعد أعمال الفواتير. هذا الملف مستقل عن الواجهة حتى يبقى حساب الإجماليات
 * والمدفوعات قابلاً للاختبار واستخدامه لاحقاً من API أو Supabase.
 */
export const INVOICE_STATUS = Object.freeze({
  draft: 'مسودة',
  issued: 'مصدرة',
  partially_paid: 'مسددة جزئياً',
  paid: 'مسددة',
  overdue: 'متأخرة',
  void: 'ملغاة',
});

const EPSILON = 0.01;

export function money(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? Math.round((parsed + Number.EPSILON) * 100) / 100 : 0;
}

export function orderLineTotal(item = {}) {
  return money(item.total ?? (Number(item.qty || 0) * Number(item.price || 0)));
}

export function orderSubtotal(order = {}) {
  if (Number.isFinite(Number(order.subtotal))) return money(order.subtotal);
  return money((order.items || []).reduce((total, item) => total + orderLineTotal(item), 0));
}

export function isInvoiceableOrder(order = {}) {
  return Boolean(order.id) && ['accepted', 'approved', 'processing', 'delivering', 'delivered'].includes(order.status);
}

export function nextInvoiceNumber(invoices = [], issuedAt = new Date()) {
  const year = new Date(issuedAt).getFullYear();
  const prefix = `INV-${year}-`;
  const serial = invoices.reduce((highest, invoice) => {
    const value = String(invoice.number || '');
    if (!value.startsWith(prefix)) return highest;
    const candidate = Number(value.slice(prefix.length));
    return Number.isInteger(candidate) ? Math.max(highest, candidate) : highest;
  }, 0) + 1;
  return `${prefix}${String(serial).padStart(5, '0')}`;
}

export function calculateInvoiceAmounts(order = {}, options = {}) {
  const subtotal = orderSubtotal(order);
  const discount = money(options.discount ?? order.discount ?? 0);
  const shipping = money(options.shipping ?? order.shipping ?? order.shippingFee ?? order.deliveryFee ?? 0);
  const taxRate = money(options.taxRate ?? order.taxRate ?? 0);
  const tax = Number.isFinite(Number(options.tax))
    ? money(options.tax)
    : money(order.tax ?? Math.max(subtotal - discount, 0) * taxRate / 100);
  const calculatedTotal = money(Math.max(subtotal - discount, 0) + tax + shipping);
  const suppliedTotal = Number(options.total ?? order.total);
  const additionalFees = Number.isFinite(Number(options.additionalFees ?? order.additionalFees))
    ? money(options.additionalFees ?? order.additionalFees)
    : Number.isFinite(suppliedTotal) && suppliedTotal > calculatedTotal
      ? money(suppliedTotal - calculatedTotal)
      : 0;
  const total = Number.isFinite(suppliedTotal) && suppliedTotal >= 0
    ? money(suppliedTotal)
    : money(calculatedTotal + additionalFees);
  return { subtotal, discount, shipping, tax, additionalFees, total };
}

export function statusForInvoice(invoice = {}, now = new Date()) {
  if (invoice.status === 'void') return 'void';
  const paidAmount = money(invoice.paidAmount ?? (invoice.payments || []).reduce((sum, payment) => sum + Number(payment.amount || 0), 0));
  const balance = money(Number(invoice.total || 0) - paidAmount);
  if (balance <= EPSILON) return 'paid';
  if (paidAmount > EPSILON) return 'partially_paid';
  if (invoice.dueAt && new Date(invoice.dueAt).getTime() < new Date(now).setHours(0, 0, 0, 0)) return 'overdue';
  return invoice.status === 'draft' ? 'draft' : 'issued';
}

export function normalizeInvoice(invoice = {}, now = new Date()) {
  const payments = Array.isArray(invoice.payments) ? invoice.payments.map((payment) => ({ ...payment, amount: money(payment.amount) })) : [];
  const paidAmount = money(payments.reduce((sum, payment) => sum + payment.amount, 0));
  const total = money(invoice.total);
  const normalized = {
    ...invoice,
    subtotal: money(invoice.subtotal),
    discount: money(invoice.discount),
    shipping: money(invoice.shipping),
    tax: money(invoice.tax),
    additionalFees: money(invoice.additionalFees),
    total,
    payments,
    paidAmount,
    balance: Math.max(0, money(total - paidAmount)),
  };
  normalized.status = statusForInvoice(normalized, now);
  return normalized;
}

export function createInvoice(order, invoices = [], options = {}) {
  if (!isInvoiceableOrder(order)) throw new Error('لا يمكن إصدار فاتورة قبل قبول الطلب أو بعد إلغائه.');
  if (invoices.some((invoice) => String(invoice.orderId) === String(order.id) && invoice.status !== 'void')) {
    throw new Error('يوجد بالفعل مستند فاتورة نشط لهذا الطلب.');
  }
  const issuedAt = options.issuedAt || new Date().toISOString();
  const amounts = calculateInvoiceAmounts(order, options);
  const invoice = {
    id: options.id || `inv_${cryptoSafeId()}`,
    number: options.number || nextInvoiceNumber(invoices, issuedAt),
    orderId: order.id,
    orderNo: order.orderNo || order.number || '',
    customerId: order.customerId || order.buyerUserId || '',
    customerName: order.customerName || order.customer || '',
    phone: order.phone || '',
    paymentMethod: order.paymentMethod || '',
    currency: options.currency || order.currency || 'EGP',
    issuedAt,
    dueAt: options.dueAt || null,
    status: 'issued',
    items: (order.items || []).map((item) => ({
      productId: item.productId || item.id || null,
      vendorId: item.vendorId || item.supplierId || null,
      name: item.name || '',
      qty: money(item.qty),
      price: money(item.price),
      total: orderLineTotal(item),
      tier: item.tier || item.tierName || '',
    })),
    payments: [],
    ...amounts,
  };
  return normalizeInvoice(invoice, issuedAt);
}

export function recordPayment(invoice, payment = {}, now = new Date().toISOString()) {
  const current = normalizeInvoice(invoice, now);
  if (current.status === 'void') throw new Error('لا يمكن تسجيل دفعة على فاتورة ملغاة.');
  const amount = money(payment.amount);
  if (amount <= 0) throw new Error('أدخل مبلغ دفعة أكبر من صفر.');
  if (amount - current.balance > EPSILON) throw new Error('قيمة الدفعة أكبر من الرصيد المتبقي للفواتير.');
  const entry = {
    id: payment.id || `pay_${cryptoSafeId()}`,
    amount,
    method: payment.method || 'تحويل بنكي',
    reference: String(payment.reference || '').trim(),
    note: String(payment.note || '').trim(),
    paidAt: payment.paidAt || now,
    recordedBy: payment.recordedBy || '',
  };
  return normalizeInvoice({ ...current, payments: [...current.payments, entry], updatedAt: now }, now);
}

export function voidInvoice(invoice, reason = '', now = new Date().toISOString()) {
  const current = normalizeInvoice(invoice, now);
  if (current.paidAmount > EPSILON) throw new Error('لا يمكن إلغاء فاتورة عليها دفعات مسجلة. استخدم إشعاراً دائنًا أو تسوية مالية.');
  return { ...current, status: 'void', voidReason: String(reason || '').trim(), voidedAt: now, updatedAt: now };
}

export function invoiceSummary(invoices = [], now = new Date()) {
  return invoices.map((invoice) => normalizeInvoice(invoice, now)).reduce((summary, invoice) => {
    if (invoice.status === 'void') return summary;
    summary.count += 1;
    summary.total = money(summary.total + invoice.total);
    summary.paid = money(summary.paid + invoice.paidAmount);
    summary.balance = money(summary.balance + invoice.balance);
    if (invoice.status === 'overdue') summary.overdue = money(summary.overdue + invoice.balance);
    return summary;
  }, { count: 0, total: 0, paid: 0, balance: 0, overdue: 0 });
}

function cryptoSafeId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
  return `${Date.now()}_${Math.random().toString(16).slice(2)}`;
}
