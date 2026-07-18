import assert from 'node:assert/strict';
import test from 'node:test';
import {
  createInvoice,
  invoiceSummary,
  recordPayment,
  statusForInvoice,
  voidInvoice,
} from '../invoice-ledger-core.js';

const order = {
  id: 'order-1', orderNo: 'TG-1001', customerId: 'customer-1', status: 'accepted', shipping: 10,
  items: [{ productId: 'p1', vendorId: 'vendor-1', name: 'سكر', qty: 2, price: 25 }],
};

test('issues a single invoice with calculated totals', () => {
  const invoice = createInvoice(order, [], { id: 'inv-1', issuedAt: '2026-07-18T00:00:00.000Z', taxRate: 14 });
  assert.equal(invoice.number, 'INV-2026-00001');
  assert.equal(invoice.subtotal, 50);
  assert.equal(invoice.tax, 7);
  assert.equal(invoice.total, 67);
  assert.equal(invoice.status, 'issued');
  assert.throws(() => createInvoice(order, [invoice]), /مستند فاتورة نشط/);
});

test('supports partial then final payment without overpayment', () => {
  const invoice = createInvoice(order, [], { id: 'inv-2', issuedAt: '2026-07-18T00:00:00.000Z' });
  const partial = recordPayment(invoice, { id: 'pay-1', amount: 20, method: 'تحويل بنكي' }, '2026-07-18T10:00:00.000Z');
  assert.equal(partial.status, 'partially_paid');
  assert.equal(partial.balance, 40);
  const paid = recordPayment(partial, { id: 'pay-2', amount: 40, method: 'نقدي' }, '2026-07-18T11:00:00.000Z');
  assert.equal(paid.status, 'paid');
  assert.equal(paid.balance, 0);
  assert.throws(() => recordPayment(partial, { amount: 41 }), /أكبر من الرصيد/);
});

test('marks past-due invoices and prevents void after payments', () => {
  const invoice = createInvoice(order, [], { id: 'inv-3', issuedAt: '2026-07-01T00:00:00.000Z', dueAt: '2026-07-05T00:00:00.000Z' });
  assert.equal(statusForInvoice(invoice, '2026-07-18T00:00:00.000Z'), 'overdue');
  assert.equal(voidInvoice(invoice).status, 'void');
  const paid = recordPayment(invoice, { amount: 1 }, '2026-07-02T00:00:00.000Z');
  assert.throws(() => voidInvoice(paid), /دفعات مسجلة/);
});

test('summarises only active invoices', () => {
  const first = createInvoice(order, [], { id: 'inv-4', issuedAt: '2026-07-18T00:00:00.000Z' });
  const paid = recordPayment(first, { amount: 10 }, '2026-07-18T00:00:00.000Z');
  const voided = voidInvoice(createInvoice({ ...order, id: 'order-2', orderNo: 'TG-1002' }, [paid], { id: 'inv-5', issuedAt: '2026-07-18T00:00:00.000Z' }));
  assert.deepEqual(invoiceSummary([paid, voided]), { count: 1, total: 60, paid: 10, balance: 50, overdue: 0 });
});
