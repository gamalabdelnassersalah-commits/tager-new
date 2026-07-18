import {
  INVOICE_STATUS,
  createInvoice,
  invoiceSummary,
  isInvoiceableOrder,
  money,
  normalizeInvoice,
  recordPayment,
  voidInvoice,
} from './invoice-ledger-core.js';

/*
 * واجهة دفتر الفواتير. تبقى متوافقة مع مخزن localStorage الحالي، وتفصل
 * منطق الفواتير عن ملف index.html الضخم لتسهيل نقلها إلى API لاحقاً.
 */
const STATE_KEY = 'tager_preserved_platform_state';
const $ = (selector, root = document) => root.querySelector(selector);
const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
const todayIso = () => new Date().toISOString();
const currentRole = () => localStorage.getItem('tager_role') || '';
const currentUserId = () => localStorage.getItem('tager_user_id') || '';
const currentUserName = () => localStorage.getItem('tager_name') || '';

function readState() {
  if (typeof window.load === 'function') return window.load();
  try { return JSON.parse(localStorage.getItem(STATE_KEY) || '{}'); } catch { return {}; }
}

function writeState(state) {
  if (typeof window.save === 'function') window.save(state);
  else localStorage.setItem(STATE_KEY, JSON.stringify(state));
}

function formatMoney(value) {
  const state = readState();
  return `${money(value).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${state.settings?.currency || 'ج.م'}`;
}

function dateLabel(value) {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? escapeHtml(value) : date.toLocaleDateString('ar-EG');
}

function uid() {
  return (crypto?.randomUUID?.() || `${Date.now()}_${Math.random().toString(16).slice(2)}`);
}

function audit(state, action, invoice, details) {
  state.invoiceAudit = state.invoiceAudit || [];
  state.invoiceAudit.unshift({
    id: `audit_${uid()}`,
    invoiceId: invoice.id,
    action,
    details,
    by: currentUserName() || 'النظام',
    at: todayIso(),
  });
}

function normalizeStateInvoices(state) {
  state.invoices = Array.isArray(state.invoices) ? state.invoices.map((invoice) => normalizeInvoice(invoice)) : [];
  return state.invoices;
}

function getInvoice(state, invoiceId) {
  return normalizeStateInvoices(state).find((invoice) => String(invoice.id) === String(invoiceId));
}

function roleAllowsInvoice(invoice) {
  const role = currentRole();
  if (role === 'admin') return true;
  if (role === 'customer') return String(invoice.customerId) === String(currentUserId());
  if (role === 'vendor') return invoice.items.some((item) => String(item.vendorId) === String(currentUserId()));
  return false;
}

function visibleInvoices(state) {
  return normalizeStateInvoices(state).filter(roleAllowsInvoice);
}

function vendorItemValue(invoice) {
  return money(invoice.items.filter((item) => String(item.vendorId) === String(currentUserId())).reduce((total, item) => total + Number(item.total || 0), 0));
}

function statusBadge(status) {
  const label = INVOICE_STATUS[status] || status;
  return `<span class="invoice-status invoice-status--${escapeHtml(status)}">${escapeHtml(label)}</span>`;
}

function approvalState(invoice) {
  return invoice?.approval?.status || 'pending';
}

function approvalBadge(invoice) {
  const status = approvalState(invoice);
  const labels = { pending: 'بانتظار الاعتماد', approved: 'معتمدة', needs_revision: 'تحتاج مراجعة' };
  return `<span class="invoice-approval invoice-approval--${escapeHtml(status)}">${escapeHtml(labels[status] || status)}</span>`;
}

function safeImageUrl(value) {
  const image = String(value || '').trim();
  const localAsset = /^[A-Za-z0-9][A-Za-z0-9_.\/-]*$/.test(image) && !image.includes('..');
  return /^(https?:\/\/|data:image\/(?:png|jpe?g|webp|svg\+xml);base64,)/.test(image) || localAsset ? image : 'product-default.svg';
}

function itemImage(item) {
  return safeImageUrl(item?.image || item?.imageUrl || item?.productImage);
}

function approvalPanel(invoice) {
  const approval = invoice.approval || {};
  const isAdmin = currentRole() === 'admin';
  const isApproved = approval.status === 'approved';
  const signature = safeImageUrl(approval.signatureData || '');
  const summary = `<section class="invoice-approval-panel invoice-approval-panel--${escapeHtml(approvalState(invoice))}">
    <div class="invoice-approval-panel__heading"><div><span>حالة الاعتماد</span><h2>${isApproved ? 'الفاتورة معتمدة' : approval.status === 'needs_revision' ? 'الفاتورة تحتاج مراجعة' : 'الفاتورة بانتظار الاعتماد'}</h2></div>${approvalBadge(invoice)}</div>
    ${approval.signerName ? `<div class="invoice-approval-panel__details"><div>${signature !== 'product-default.svg' ? `<img src="${escapeHtml(signature)}" alt="توقيع ${escapeHtml(approval.signerName)}">` : '<span class="invoice-signature-placeholder">✍</span>'}<div><strong>${escapeHtml(approval.signerName)}</strong><span>${escapeHtml(approval.signerTitle || 'المعتمد')}</span></div></div><div><strong>${approval.signedAt ? dateLabel(approval.signedAt) : '—'}</strong><span>تاريخ الإجراء</span></div>${approval.notes ? `<div><strong>${escapeHtml(approval.notes)}</strong><span>ملاحظات الاعتماد</span></div>` : ''}</div>` : '<p class="invoice-approval-panel__empty">لم يكتمل اعتماد هذه الفاتورة بعد.</p>'}
  </section>`;
  if (!isAdmin || invoice.status === 'void') return summary;
  return `${summary}<section class="invoice-panel invoice-panel--approval"><div><h2>اعتماد وتوقيع الفاتورة</h2><p>يُسجّل هذا الإجراء اسم المعتمد ومسمّاه وتوقيعه داخل سجل التدقيق، ويظهر في نسخة الطباعة.</p></div>
    <form id="invoiceApprovalForm" class="invoice-form invoice-form--approval">
      <input type="hidden" name="invoiceId" value="${escapeHtml(invoice.id)}">
      <label>الإجراء<select name="status"><option value="approved" ${isApproved ? 'selected' : ''}>اعتماد الفاتورة</option><option value="needs_revision" ${approval.status === 'needs_revision' ? 'selected' : ''}>إعادتها للمراجعة</option></select></label>
      <label>اسم المعتمد<input name="signerName" value="${escapeHtml(approval.signerName || currentUserName())}" maxlength="120" required></label>
      <label>المسمى الوظيفي<input name="signerTitle" value="${escapeHtml(approval.signerTitle || 'مسؤول الاعتماد')}" maxlength="120" required></label>
      <label class="invoice-form--wide">ملاحظات<input name="notes" value="${escapeHtml(approval.notes || '')}" maxlength="300" placeholder="اختياري"></label>
      <input type="hidden" name="signatureData" id="invoiceSignatureData" value="${escapeHtml(approval.signatureData || '')}">
      <div class="invoice-signature-pad invoice-form--wide"><div><strong>التوقيع الإلكتروني</strong><small>وقّع باللمس أو بالماوس، أو امسح التوقيع لإعادة الإدخال.</small></div><canvas id="invoiceSignaturePad" width="680" height="180" aria-label="مساحة التوقيع"></canvas><button class="btn3" type="button" data-invoice-action="clear-signature">مسح التوقيع</button></div>
      <button class="btn" type="submit">حفظ الاعتماد</button>
    </form></section>`;
}

function invoiceShell(title, subtitle, body) {
  return `<section class="wrap page invoice-ledger" dir="rtl"><div class="invoice-ledger__heading"><div><span class="invoice-eyebrow">المالية والفواتير</span><h1 class="page-title">${escapeHtml(title)}</h1><p class="sub-title">${escapeHtml(subtitle)}</p></div><button class="btn3" type="button" data-invoice-action="back">العودة للمنصة</button></div>${body}</section>`;
}

function summaryCards(invoices) {
  if (currentRole() === 'vendor') {
    const value = money(invoices.reduce((total, invoice) => total + vendorItemValue(invoice), 0));
    const active = invoices.filter((invoice) => invoice.status !== 'void');
    const complete = active.filter((invoice) => invoice.status === 'paid').length;
    return `<div class="invoice-metrics">
      <article><span>فواتير تتضمن منتجاتك</span><strong>${active.length.toLocaleString('ar-EG')}</strong></article>
      <article><span>قيمة بنودك</span><strong>${formatMoney(value)}</strong></article>
      <article><span>مستندات مكتملة السداد</span><strong>${complete.toLocaleString('ar-EG')}</strong></article>
      <article><span>قيد التحصيل</span><strong>${(active.length - complete).toLocaleString('ar-EG')}</strong></article>
      <article class="invoice-metrics__alert"><span>فواتير متأخرة</span><strong>${active.filter((invoice) => invoice.status === 'overdue').length.toLocaleString('ar-EG')}</strong></article>
    </div>`;
  }
  const summary = invoiceSummary(invoices);
  return `<div class="invoice-metrics">
    <article><span>الفواتير النشطة</span><strong>${summary.count.toLocaleString('ar-EG')}</strong></article>
    <article><span>إجمالي الفوترة</span><strong>${formatMoney(summary.total)}</strong></article>
    <article><span>المحصّل</span><strong>${formatMoney(summary.paid)}</strong></article>
    <article><span>الرصيد المتبقي</span><strong>${formatMoney(summary.balance)}</strong></article>
    <article class="invoice-metrics__alert"><span>المتأخر</span><strong>${formatMoney(summary.overdue)}</strong></article>
  </div>`;
}

function eligibleOrders(state) {
  const issuedOrderIds = new Set(normalizeStateInvoices(state).filter((invoice) => invoice.status !== 'void').map((invoice) => String(invoice.orderId)));
  return (state.orders || []).filter((order) => isInvoiceableOrder(order) && !issuedOrderIds.has(String(order.id)));
}

function issueInvoicePanel(state) {
  if (currentRole() !== 'admin') return '';
  const eligible = eligibleOrders(state);
  return `<section class="invoice-panel invoice-panel--issue">
    <div><h2>إصدار فاتورة من طلب</h2><p>يُنشئ النظام مستنداً واحداً لكل طلب ويحفظ الرقم والتاريخ والاستحقاق وسجل التدقيق.</p></div>
    ${eligible.length ? `<form id="invoiceIssueForm" class="invoice-form invoice-form--issue">
      <label>الطلب<select name="orderId" required><option value="">اختر طلباً</option>${eligible.map((order) => `<option value="${escapeHtml(order.id)}">${escapeHtml(order.orderNo || order.id)} — ${formatMoney(order.total)}</option>`).join('')}</select></label>
      <label>تاريخ الاستحقاق<input type="date" name="dueAt"></label>
      <label>ضريبة %<input type="number" min="0" max="100" step="0.01" name="taxRate" value="0"></label>
      <button class="btn" type="submit">إصدار الفاتورة</button>
    </form>` : '<p class="invoice-empty">لا توجد طلبات مؤهلة للإصدار حالياً.</p>'}
  </section>`;
}

function invoiceRows(invoices, state) {
  const role = currentRole();
  if (!invoices.length) return '<div class="invoice-empty">لا توجد فواتير مطابقة. أصدر فاتورة من طلب مقبول أو مكتمل لتظهر هنا.</div>';
  const vendorColumns = role === 'vendor';
  return `<div class="table-wrap invoice-table-wrap"><table class="invoice-table"><thead><tr><th>رقم الفاتورة</th><th>الطلب</th>${vendorColumns ? '' : '<th>العميل</th>'}<th>الإصدار</th><th>${vendorColumns ? 'قيمة بنودك' : 'الإجمالي'}</th>${vendorColumns ? '' : '<th>المتبقي</th>'}<th>السداد</th><th>الاعتماد</th><th>إجراء</th></tr></thead><tbody>${invoices.map((invoice) => {
    const customer = invoice.customerName || (state.orders || []).find((order) => String(order.id) === String(invoice.orderId))?.customerName || '—';
    return `<tr><td><strong>${escapeHtml(invoice.number)}</strong></td><td>${escapeHtml(invoice.orderNo || '—')}</td>${vendorColumns ? '' : `<td>${escapeHtml(customer)}</td>`}<td>${dateLabel(invoice.issuedAt)}</td><td>${formatMoney(vendorColumns ? vendorItemValue(invoice) : invoice.total)}</td>${vendorColumns ? '' : `<td>${formatMoney(invoice.balance)}</td>`}<td>${statusBadge(invoice.status)}</td><td>${approvalBadge(invoice)}</td><td><button class="btn3" type="button" data-invoice-action="view" data-invoice-id="${escapeHtml(invoice.id)}">عرض</button>${role === 'admin' ? ` <button class="btn3" type="button" data-invoice-action="print" data-order-id="${escapeHtml(invoice.orderId)}">طباعة</button>` : ''}</td></tr>`;
  }).join('')}</tbody></table></div>`;
}

function invoicesPage() {
  const role = currentRole();
  if (!['admin', 'customer', 'vendor'].includes(role)) return typeof window.loginRequired === 'function' ? window.loginRequired() : invoiceShell('الفواتير', 'سجّل الدخول للوصول إلى مستنداتك.', '');
  const state = readState();
  const invoices = visibleInvoices(state);
  const title = role === 'admin' ? 'مركز الفواتير' : role === 'vendor' ? 'فواتير المورد' : 'فواتيري';
  const description = role === 'admin'
    ? 'إصدار الفواتير ومتابعة التحصيل والرصيد والتأخر من شاشة واحدة.'
    : role === 'vendor'
      ? 'اطلع على الفواتير التي تتضمن منتجاتك وقيمتها التشغيلية.'
      : 'تابع مستنداتك وحالة السداد والرصيد المتبقي.';
  return invoiceShell(title, description, `${summaryCards(invoices)}${issueInvoicePanel(state)}<section class="invoice-panel"><div class="invoice-toolbar"><h2>سجل الفواتير</h2><div><select id="invoiceStatusFilter"><option value="">كل الحالات</option>${Object.entries(INVOICE_STATUS).map(([key, value]) => `<option value="${key}">${value}</option>`).join('')}</select><input id="invoiceSearch" type="search" placeholder="بحث برقم الفاتورة أو الطلب"></div></div><div id="invoiceRows">${invoiceRows(invoices, state)}</div></section>`);
}

function visibleItems(invoice) {
  if (currentRole() !== 'vendor') return invoice.items;
  return invoice.items.filter((item) => String(item.vendorId) === String(currentUserId()));
}

function invoiceDetailsPage(invoiceId) {
  const state = readState();
  const invoice = getInvoice(state, invoiceId);
  if (!invoice || !roleAllowsInvoice(invoice)) return invoiceShell('الفاتورة', 'تعذر الوصول إلى المستند المطلوب.', '<div class="invoice-empty">الفاتورة غير موجودة أو لا تملك صلاحية عرضها.</div>');
  const items = visibleItems(invoice);
  const vendorView = currentRole() === 'vendor';
  const visibleSubtotal = money(items.reduce((total, item) => total + Number(item.total || 0), 0));
  const canRecordPayment = currentRole() === 'admin' && invoice.status !== 'void' && invoice.balance > 0;
  const auditItems = (state.invoiceAudit || []).filter((entry) => String(entry.invoiceId) === String(invoice.id));
  return invoiceShell(`فاتورة ${invoice.number}`, 'تفاصيل موثقة للفاتورة والتحصيل دون تعديل بنود الطلب الأصلية.', `
    <div class="invoice-detail-actions"><button class="btn3" type="button" data-invoice-action="list">سجل الفواتير</button><button class="btn" type="button" data-invoice-action="print" data-order-id="${escapeHtml(invoice.orderId)}">طباعة الفاتورة</button>${currentRole() === 'admin' && invoice.status !== 'void' && invoice.paidAmount === 0 ? `<button class="btn-danger" type="button" data-invoice-action="void" data-invoice-id="${escapeHtml(invoice.id)}">إلغاء الفاتورة</button>` : ''}</div>
    <section class="invoice-detail-grid"><article><span>حالة السداد</span>${statusBadge(invoice.status)}</article><article><span>حالة الاعتماد</span>${approvalBadge(invoice)}</article><article><span>تاريخ الإصدار</span><strong>${dateLabel(invoice.issuedAt)}</strong></article><article><span>تاريخ الاستحقاق</span><strong>${dateLabel(invoice.dueAt)}</strong></article><article><span>طريقة الدفع</span><strong>${escapeHtml(invoice.paymentMethod || '—')}</strong></article></section>
    <section class="invoice-panel"><h2>بنود الفاتورة</h2><div class="table-wrap invoice-table-wrap"><table class="invoice-table"><thead><tr><th>#</th><th>الصورة</th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${items.map((item, index) => `<tr><td>${(index + 1).toLocaleString('ar-EG')}</td><td><img class="invoice-item-image" src="${escapeHtml(itemImage(item))}" alt="${escapeHtml(item.name || 'صورة المنتج')}" onerror="this.src='product-default.svg'"></td><td><strong>${escapeHtml(item.name)}</strong>${item.sku ? `<small class="invoice-item-sku">${escapeHtml(item.sku)}</small>` : ''}</td><td>${money(item.qty).toLocaleString('ar-EG')}</td><td>${formatMoney(item.price)}</td><td>${formatMoney(item.total)}</td></tr>`).join('') || '<tr><td colspan="6">لا توجد بنود مرئية لهذا الدور.</td></tr>'}</tbody></table></div>
      ${vendorView ? `<div class="invoice-totals"><strong>قيمة بنود المورد <b>${formatMoney(visibleSubtotal)}</b></strong><span>تظهر للمورد بنوده فقط؛ تبقى تفاصيل التحصيل الكاملة للإدارة والعميل.</span></div>` : `<div class="invoice-totals"><span>إجمالي البنود <b>${formatMoney(invoice.subtotal)}</b></span><span>الخصم <b>${formatMoney(invoice.discount)}</b></span><span>الضريبة <b>${formatMoney(invoice.tax)}</b></span><span>الشحن <b>${formatMoney(invoice.shipping)}</b></span>${invoice.additionalFees ? `<span>رسوم إضافية <b>${formatMoney(invoice.additionalFees)}</b></span>` : ''}<strong>إجمالي الفاتورة <b>${formatMoney(invoice.total)}</b></strong><strong>المحصّل <b>${formatMoney(invoice.paidAmount)}</b></strong><strong class="invoice-totals__balance">المتبقي <b>${formatMoney(invoice.balance)}</b></strong></div>`}
    </section>
    ${approvalPanel(invoice)}
    ${canRecordPayment ? `<section class="invoice-panel"><h2>تسجيل دفعة</h2><form id="invoicePaymentForm" class="invoice-form"><input type="hidden" name="invoiceId" value="${escapeHtml(invoice.id)}"><label>المبلغ<input name="amount" type="number" min="0.01" step="0.01" max="${invoice.balance}" required></label><label>طريقة الدفع<select name="method"><option>تحويل بنكي</option><option>نقدي</option><option>محفظة إلكترونية</option><option>بطاقة</option><option>أخرى</option></select></label><label>رقم المرجع<input name="reference" maxlength="120" placeholder="رقم التحويل أو الإيصال"></label><label>ملاحظات<input name="note" maxlength="300" placeholder="اختياري"></label><button class="btn" type="submit">تسجيل الدفعة</button></form><p class="invoice-form-note">لا يسمح النظام بتسجيل مبلغ أكبر من الرصيد المتبقي.</p></section>` : ''}
    <section class="invoice-panel"><h2>سجل التحصيل</h2>${invoice.payments.length ? `<div class="table-wrap invoice-table-wrap"><table class="invoice-table"><thead><tr><th>التاريخ</th><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th>سجلها</th></tr></thead><tbody>${invoice.payments.map((payment) => `<tr><td>${dateLabel(payment.paidAt)}</td><td>${formatMoney(payment.amount)}</td><td>${escapeHtml(payment.method)}</td><td>${escapeHtml(payment.reference || '—')}</td><td>${escapeHtml(payment.recordedBy || '—')}</td></tr>`).join('')}</tbody></table></div>` : '<div class="invoice-empty">لم تسجل أي دفعة على هذه الفاتورة بعد.</div>'}</section>
    ${currentRole() === 'admin' ? `<section class="invoice-panel"><h2>سجل التدقيق</h2>${auditItems.length ? `<ol class="invoice-audit">${auditItems.map((entry) => `<li><strong>${escapeHtml(entry.action)}</strong><span>${escapeHtml(entry.details)} — ${escapeHtml(entry.by)} — ${dateLabel(entry.at)}</span></li>`).join('')}</ol>` : '<div class="invoice-empty">لم تُسجل عمليات تدقيق إضافية بعد.</div>'}</section>` : ''}
    <div id="invoiceMessage" aria-live="polite"></div>`);
}

function refreshList() {
  const state = readState();
  let invoices = visibleInvoices(state);
  const status = $('#invoiceStatusFilter')?.value || '';
  const term = ($('#invoiceSearch')?.value || '').trim().toLowerCase();
  if (status) invoices = invoices.filter((invoice) => invoice.status === status);
  if (term) invoices = invoices.filter((invoice) => `${invoice.number} ${invoice.orderNo} ${invoice.customerName}`.toLowerCase().includes(term));
  const target = $('#invoiceRows');
  if (target) target.innerHTML = invoiceRows(invoices, state);
}

function message(text, type = 'success') {
  const container = $('#invoiceMessage') || $('#invoiceRows');
  if (container) container.insertAdjacentHTML('beforebegin', `<div class="invoice-message invoice-message--${type}">${escapeHtml(text)}</div>`);
}

function go(page, data = {}) {
  if (typeof window.go === 'function') window.go(page, data);
  else window.location.hash = page;
}

function addInvoiceLink() {
  const nav = $('#navlinks');
  if (!nav || $('[data-nav="invoices"]', nav)) return;
  const button = document.createElement('button');
  button.type = 'button';
  button.dataset.nav = 'invoices';
  button.textContent = 'الفواتير';
  button.addEventListener('click', () => go('invoices'));
  nav.append(button);
}

function initializeSignaturePad() {
  const canvas = $('#invoiceSignaturePad');
  const hidden = $('#invoiceSignatureData');
  if (!canvas || !hidden || canvas.dataset.ready === 'true') return;
  const context = canvas.getContext('2d');
  const blank = () => {
    const rect = canvas.getBoundingClientRect();
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const prior = hidden.value;
    canvas.width = Math.max(1, Math.round(rect.width * ratio));
    canvas.height = Math.max(1, Math.round(160 * ratio));
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.fillStyle = '#fff';
    context.fillRect(0, 0, rect.width, 160);
    context.strokeStyle = '#073f39';
    context.lineWidth = 2.3;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    if (prior) {
      const image = new Image();
      image.onload = () => context.drawImage(image, 0, 0, rect.width, 160);
      image.src = prior;
    }
  };
  let drawing = false;
  let hasStroke = Boolean(hidden.value);
  const point = (event) => {
    const rect = canvas.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
  };
  const save = () => { if (hasStroke) hidden.value = canvas.toDataURL('image/png'); };
  canvas.addEventListener('pointerdown', (event) => {
    drawing = true;
    hasStroke = true;
    canvas.setPointerCapture?.(event.pointerId);
    const p = point(event);
    context.beginPath();
    context.moveTo(p.x, p.y);
    event.preventDefault();
  });
  canvas.addEventListener('pointermove', (event) => {
    if (!drawing) return;
    const p = point(event);
    context.lineTo(p.x, p.y);
    context.stroke();
    event.preventDefault();
  });
  ['pointerup', 'pointercancel', 'pointerleave'].forEach((type) => canvas.addEventListener(type, () => {
    if (!drawing) return;
    drawing = false;
    save();
  }));
  canvas.dataset.ready = 'true';
  blank();
  window.addEventListener('resize', blank, { once: true });
}

function renderPage(page, data = {}) {
  if (page === 'invoices') {
    const app = $('#app');
    if (app) app.innerHTML = invoicesPage();
    addInvoiceLink();
    return;
  }
  if (page === 'invoiceLedger') {
    const app = $('#app');
    if (app) app.innerHTML = invoiceDetailsPage(data.id || sessionStorage.getItem('tager_invoice_id'));
    addInvoiceLink();
    requestAnimationFrame(initializeSignaturePad);
    return;
  }
  return null;
}

function installRenderHook() {
  const priorRender = window.render;
  if (typeof priorRender !== 'function' || priorRender.__tagerInvoicesInstalled) return;
  function wrappedRender(page = 'home', data = {}) {
    if (page === 'invoices' || page === 'invoiceLedger') return renderPage(page, data);
    const result = priorRender(page, data);
    addInvoiceLink();
    return result;
  }
  wrappedRender.__tagerInvoicesInstalled = true;
  window.render = wrappedRender;
}

document.addEventListener('click', (event) => {
  const action = event.target.closest('[data-invoice-action]');
  if (!action) return;
  const type = action.dataset.invoiceAction;
  if (type === 'clear-signature') {
    const canvas = $('#invoiceSignaturePad');
    const hidden = $('#invoiceSignatureData');
    if (!canvas || !hidden) return;
    const context = canvas.getContext('2d');
    context.save();
    context.setTransform(1, 0, 0, 1, 0, 0);
    context.fillStyle = '#fff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.restore();
    hidden.value = '';
    return;
  }
  if (type === 'back') return go('home');
  if (type === 'list') return go('invoices');
  if (type === 'view') {
    sessionStorage.setItem('tager_invoice_id', action.dataset.invoiceId);
    return go('invoiceLedger', { id: action.dataset.invoiceId });
  }
  if (type === 'print') return go('invoicePrint', { id: action.dataset.orderId });
  if (type === 'void') {
    if (currentRole() !== 'admin' || !confirm('هل تريد إلغاء هذه الفاتورة؟ لا يمكن الإلغاء بعد تسجيل دفعة.')) return;
    const state = readState();
    const index = normalizeStateInvoices(state).findIndex((invoice) => String(invoice.id) === String(action.dataset.invoiceId));
    try {
      const updated = voidInvoice(state.invoices[index], 'إلغاء من مركز الفواتير');
      state.invoices[index] = updated;
      audit(state, 'إلغاء فاتورة', updated, 'ألغيت الفاتورة قبل تسجيل أي دفعة.');
      writeState(state);
      go('invoiceLedger', { id: updated.id });
    } catch (error) { message(error.message, 'error'); }
  }
});

document.addEventListener('submit', (event) => {
  const form = event.target;
  if (form.id === 'invoiceIssueForm') {
    event.preventDefault();
    if (currentRole() !== 'admin') return;
    const fields = new FormData(form);
    const state = readState();
    const order = (state.orders || []).find((candidate) => String(candidate.id) === String(fields.get('orderId')));
    try {
      const dueAt = fields.get('dueAt') ? new Date(`${fields.get('dueAt')}T23:59:59`).toISOString() : null;
      const customer = (state.users || []).find((user) => String(user.id) === String(order?.customerId));
      const invoiceOrder = {
        ...order,
        customerName: order?.customerName || customer?.name || '',
        items: (order?.items || []).map((item) => {
          const product = (state.products || []).find((candidate) => String(candidate.id) === String(item.productId));
          return { ...item, image: item.image || product?.image || product?.imageUrl || '' };
        }),
      };
      const invoice = createInvoice(invoiceOrder, normalizeStateInvoices(state), { dueAt, taxRate: fields.get('taxRate'), issuedAt: todayIso() });
      invoice.approval = { status: 'pending', requestedAt: invoice.issuedAt };
      state.invoices.unshift(invoice);
      order.invoiceNo = invoice.number;
      order.invoicedAt = invoice.issuedAt;
      order.invoiceItemsSnapshot = invoice.items;
      audit(state, 'إصدار فاتورة', invoice, `أُصدرت الفاتورة من الطلب ${invoice.orderNo}.`);
      writeState(state);
      sessionStorage.setItem('tager_invoice_id', invoice.id);
      go('invoiceLedger', { id: invoice.id });
    } catch (error) { message(error.message, 'error'); }
  }
  if (form.id === 'invoiceApprovalForm') {
    event.preventDefault();
    if (currentRole() !== 'admin') return;
    const fields = new FormData(form);
    const state = readState();
    const index = normalizeStateInvoices(state).findIndex((invoice) => String(invoice.id) === String(fields.get('invoiceId')));
    try {
      if (index < 0) throw new Error('تعذر العثور على الفاتورة.');
      const status = fields.get('status') === 'needs_revision' ? 'needs_revision' : 'approved';
      const signatureData = String(fields.get('signatureData') || '');
      if (status === 'approved' && !signatureData) throw new Error('أضف توقيع المعتمد قبل اعتماد الفاتورة.');
      const invoice = state.invoices[index];
      invoice.approval = {
        status,
        signerName: String(fields.get('signerName') || '').trim(),
        signerTitle: String(fields.get('signerTitle') || '').trim(),
        notes: String(fields.get('notes') || '').trim(),
        signatureData,
        signedAt: todayIso(),
        signedBy: currentUserId(),
      };
      audit(state, status === 'approved' ? 'اعتماد فاتورة' : 'إعادة فاتورة للمراجعة', invoice, `${invoice.approval.signerName} — ${invoice.approval.signerTitle}${invoice.approval.notes ? ` — ${invoice.approval.notes}` : ''}`);
      writeState(state);
      go('invoiceLedger', { id: invoice.id });
    } catch (error) { message(error.message, 'error'); }
    return;
  }
  if (form.id === 'invoicePaymentForm') {
    event.preventDefault();
    if (currentRole() !== 'admin') return;
    const fields = new FormData(form);
    const state = readState();
    const index = normalizeStateInvoices(state).findIndex((invoice) => String(invoice.id) === String(fields.get('invoiceId')));
    try {
      if (index < 0) throw new Error('تعذر العثور على الفاتورة.');
      const updated = recordPayment(state.invoices[index], { amount: fields.get('amount'), method: fields.get('method'), reference: fields.get('reference'), note: fields.get('note'), recordedBy: currentUserName(), paidAt: todayIso() });
      state.invoices[index] = updated;
      audit(state, 'تسجيل دفعة', updated, `سُجلت دفعة بقيمة ${formatMoney(fields.get('amount'))}.`);
      writeState(state);
      go('invoiceLedger', { id: updated.id });
    } catch (error) { message(error.message, 'error'); }
  }
});

document.addEventListener('input', (event) => {
  if (event.target.id === 'invoiceSearch') refreshList();
});
document.addEventListener('change', (event) => {
  if (event.target.id === 'invoiceStatusFilter') refreshList();
});

const css = document.createElement('style');
css.textContent = `
  .invoice-ledger{padding-bottom:48px}.invoice-ledger__heading{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin:10px 0 22px}.invoice-eyebrow{color:#0a6b60;font-weight:900;letter-spacing:.08em;font-size:12px}.invoice-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:0 0 18px}.invoice-metrics article{background:#fff;border:1px solid #dbe9e5;border-radius:16px;padding:16px;box-shadow:0 8px 22px rgba(0,63,59,.05);display:grid;gap:8px}.invoice-metrics span{color:#52716b;font-weight:800;font-size:13px}.invoice-metrics strong{color:#073f39;font-size:18px}.invoice-metrics__alert{border-color:#fecf9e!important;background:#fffaf5!important}.invoice-panel{background:#fff;border:1px solid #dbe9e5;border-radius:18px;padding:20px;margin:18px 0;box-shadow:0 10px 28px rgba(0,63,59,.05)}.invoice-panel h2{margin:0 0 8px;color:#073f39;font-size:20px}.invoice-panel p{color:#5b716b;margin:0}.invoice-panel--issue{display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:end;background:linear-gradient(135deg,#f6fffc,#fff)}.invoice-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;align-items:end}.invoice-form--issue{grid-template-columns:2fr 1fr 1fr auto}.invoice-form label{display:grid;gap:6px;color:#164b43;font-size:13px;font-weight:800}.invoice-form input,.invoice-form select,.invoice-toolbar input,.invoice-toolbar select{min-height:42px;border:1px solid #bdd7d0;border-radius:10px;background:#fff;padding:0 10px;color:#092f2b;font:inherit}.invoice-toolbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:14px}.invoice-toolbar h2{margin:0}.invoice-toolbar>div{display:flex;gap:8px}.invoice-empty{padding:24px;text-align:center;color:#61756f;background:#f7fbfa;border-radius:12px}.invoice-table-wrap{overflow:auto}.invoice-table{min-width:820px}.invoice-table th{white-space:nowrap}.invoice-table td{vertical-align:middle}.invoice-status{display:inline-flex;align-items:center;justify-content:center;padding:5px 9px;border-radius:999px;font-weight:800;font-size:12px;white-space:nowrap}.invoice-status--issued{background:#e8f3ff;color:#12569f}.invoice-status--partially_paid{background:#fff7df;color:#8a5800}.invoice-status--paid{background:#e7f8ee;color:#087443}.invoice-status--overdue{background:#fff0f1;color:#b42318}.invoice-status--draft{background:#edf2f1;color:#49635e}.invoice-status--void{background:#f1f1f1;color:#555}.invoice-detail-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.invoice-detail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.invoice-detail-grid article{background:#fff;border:1px solid #dbe9e5;border-radius:14px;padding:14px;display:grid;gap:8px}.invoice-detail-grid span{color:#55736c;font-size:13px;font-weight:800}.invoice-detail-grid strong{color:#0a3f38}.invoice-totals{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;margin-top:16px}.invoice-totals span,.invoice-totals strong{display:flex;gap:12px;justify-content:space-between;min-width:190px;padding:10px 12px;border-radius:10px;background:#f5faf8;color:#294b45}.invoice-totals strong{background:#073f39;color:#fff}.invoice-totals__balance{background:#ff6b1a!important}.invoice-audit{margin:0;padding:0 22px 0 0;display:grid;gap:10px}.invoice-audit li{display:grid;gap:3px}.invoice-audit span{color:#5b716b;font-size:13px}.invoice-message{margin:10px 0;padding:11px 13px;border-radius:10px;background:#e7f8ee;color:#087443;font-weight:700}.invoice-message--error{background:#fff0f1;color:#b42318}.invoice-form-note{font-size:13px;margin-top:10px!important}@media(max-width:980px){.invoice-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.invoice-panel--issue,.invoice-form,.invoice-form--issue,.invoice-detail-grid{grid-template-columns:1fr 1fr}.invoice-toolbar{align-items:flex-start;flex-direction:column}.invoice-toolbar>div{width:100%;flex-wrap:wrap}.invoice-toolbar input,.invoice-toolbar select{flex:1}}@media(max-width:620px){.invoice-ledger__heading{flex-direction:column}.invoice-metrics,.invoice-panel--issue,.invoice-form,.invoice-form--issue,.invoice-detail-grid{grid-template-columns:1fr}.invoice-totals span,.invoice-totals strong{width:100%;min-width:0}.invoice-toolbar>div{flex-direction:column}}
`;
document.head.append(css);

const approvalCss = document.createElement('style');
approvalCss.textContent = `
  .invoice-approval{display:inline-flex;align-items:center;justify-content:center;padding:5px 9px;border-radius:999px;font-weight:800;font-size:12px;white-space:nowrap}.invoice-approval--pending{background:#fff7df;color:#8a5800}.invoice-approval--approved{background:#e7f8ee;color:#087443}.invoice-approval--needs_revision{background:#fff0f1;color:#b42318}.invoice-item-image{width:48px;height:48px;display:block;object-fit:contain;border-radius:10px;border:1px solid #dbe9e5;background:#fff}.invoice-item-sku{display:block;margin-top:3px;color:#58716a;font-size:11px}.invoice-approval-panel{border:1px solid #dbe9e5;border-radius:18px;margin:18px 0;padding:18px;background:#f9fdfb}.invoice-approval-panel--approved{border-color:#b8e6c9;background:#f1fcf5}.invoice-approval-panel--needs_revision{border-color:#ffd1cc;background:#fff8f7}.invoice-approval-panel__heading{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.invoice-approval-panel__heading span{color:#55736c;font-size:13px;font-weight:800}.invoice-approval-panel__heading h2{margin:4px 0 0;color:#073f39;font-size:20px}.invoice-approval-panel__details{display:flex;gap:18px;align-items:center;flex-wrap:wrap;margin-top:16px}.invoice-approval-panel__details>div{display:flex;align-items:center;gap:10px}.invoice-approval-panel__details strong{display:block;color:#073f39}.invoice-approval-panel__details span{display:block;color:#5b716b;font-size:12px;margin-top:3px}.invoice-approval-panel__details img,.invoice-signature-placeholder{width:110px;height:48px;object-fit:contain;border-radius:8px;background:#fff;border:1px solid #dbe9e5}.invoice-signature-placeholder{display:grid;place-items:center;color:#0a6b60;font-size:24px}.invoice-approval-panel__empty{margin:14px 0 0!important}.invoice-panel--approval{display:grid;gap:18px}.invoice-form--approval{grid-template-columns:repeat(3,minmax(0,1fr))}.invoice-form--wide{grid-column:1/-1}.invoice-signature-pad{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;border:1px dashed #9fc8bc;border-radius:14px;padding:12px;background:#fcfffd}.invoice-signature-pad small{display:block;color:#58716a;margin-top:4px}.invoice-signature-pad canvas{grid-column:1/-1;width:100%;height:160px;touch-action:none;cursor:crosshair;background:#fff;border:1px solid #dbe9e5;border-radius:10px}.invoice-signature-pad .btn3{justify-self:start}.invoice-detail-grid{grid-template-columns:repeat(5,minmax(0,1fr))}@media(max-width:980px){.invoice-form--approval,.invoice-detail-grid{grid-template-columns:1fr 1fr}}@media(max-width:620px){.invoice-form--approval,.invoice-detail-grid{grid-template-columns:1fr}.invoice-approval-panel__heading{flex-direction:column}.invoice-signature-pad{grid-template-columns:1fr}}
`;
document.head.append(approvalCss);

installRenderHook();
addInvoiceLink();
window.TagerInvoiceLedger = { createInvoice, recordPayment, normalizeInvoice, invoiceSummary };
