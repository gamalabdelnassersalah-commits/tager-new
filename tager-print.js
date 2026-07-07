
(function(){
  const fallback = {
    orderNo:'TG-1001', invoiceNo:'INV-1001', date:'2026/7/6', orderDate:'2026/7/6',
    status:'جديد', platformName:'منصة تاجر', platformDescription:'منصة مصرية للتجارة والتوريد',
    vendorName:'شركة الأخوة', vendorDescription:'بيانات المورد داخل المنصة',
    customerName:'Gamal Gemy', customerLocation:'الدقهلية - المنصورة',
    governorate:'الدقهلية', district:'المنصورة', paymentMethod:'نقدي عند الاستلام', currency:'ج.م',
    items:[{name:'بند تجريبي',priceType:'سعر الشراء',qty:5,unitPrice:1000,total:5000}],
    subtotal:5000, discount:0, tax:0, total:5000, amountText:'خمسة آلاف جنيه مصري لا غير'
  };
  function num(v){ return Number(v || 0); }
  function ar(v){ return String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function money(v, c){ return `${num(v).toLocaleString('ar-EG')} ${c || 'ج.م'}`; }
  function getPayload(){
    try{
      const direct = window.TAGER_DOCUMENT_DATA;
      if(direct) return {...fallback, ...direct};
      const key = new URLSearchParams(location.search).get('key') || 'tager_print_payload';
      const saved = localStorage.getItem(key);
      if(saved) return {...fallback, ...JSON.parse(saved)};
    }catch(e){}
    return fallback;
  }
  function normalize(d){
    d.items = Array.isArray(d.items) && d.items.length ? d.items : fallback.items;
    d.subtotal = d.subtotal ?? d.items.reduce((a,i)=>a + num(i.total ?? (num(i.qty)*num(i.unitPrice))),0);
    d.discount = d.discount ?? 0;
    d.tax = d.tax ?? 0;
    d.total = d.total ?? (num(d.subtotal)-num(d.discount)+num(d.tax));
    return d;
  }
  function title(docType){ return docType === 'order' ? ['طلب شراء','تفاصيل الطلب قبل الفوترة'] : ['فاتورة مورد','شكراً لتعاملكم معنا']; }
  function rowHtml(d){
    return d.items.map((i,idx)=>`
      <tr>
        <td>${(idx+1).toLocaleString('ar-EG')}</td>
        <td>${ar(i.name)} <span class="tg-cube">◇</span></td>
        <td>${ar(i.priceType || i.tierName || i.tier || 'سعر الشراء')}</td>
        <td>${num(i.qty).toLocaleString('ar-EG')}</td>
        <td>${money(i.unitPrice ?? i.price, d.currency)}</td>
        <td>${money(i.total ?? (num(i.qty)*num(i.unitPrice ?? i.price)), d.currency)}</td>
      </tr>
    `).join('');
  }
  function render(){
    const docType = document.body.dataset.doc || 'invoice';
    const d = normalize(getPayload());
    const [mainTitle, subtitle] = title(docType);
    const isOrder = docType === 'order';
    document.getElementById('doc-root').innerHTML = `
      <div class="tg-doc-inner">
        <header class="tg-header">
          <div class="tg-brand">
            <img src="assets/img/tager-logo.png" alt="Tager">
            <div class="tagline">Trade • Supply • Connect</div>
          </div>
          <div class="tg-title-block">
            <div class="tg-title">
              <h1>${mainTitle}</h1>
              <p>${subtitle}</p>
            </div>
            <div class="tg-title-icon">${isOrder?'☑':'▤'}<small>${isOrder?'✓':'◇'}</small></div>
          </div>
        </header>

        <section class="tg-meta-strip ${isOrder ? 'two' : ''}">
          <div class="tg-meta-item"><span>${ar(isOrder ? d.orderDate : d.date)}</span><div><b>${isOrder?'تاريخ الطلب':'التاريخ'}</b></div><i class="tg-icon">▦</i></div>
          ${isOrder ? '' : `<div class="tg-meta-item"><strong>${ar(d.invoiceNo)}</strong><div><b>رقم الفاتورة</b></div><i class="tg-icon">▤</i></div>`}
          <div class="tg-meta-item"><strong>${ar(d.orderNo)}</strong><div><b>رقم الطلب</b></div><i class="tg-icon">☑</i></div>
        </section>

        <section class="tg-party-grid">
          <div class="tg-info-card"><i class="tg-icon">◇</i><div><b>المنصة</b><h3>${ar(d.platformName)}</h3><p>${ar(d.platformDescription)}</p></div></div>
          <div class="tg-info-card vendor"><i class="tg-icon">●</i><div><b>المورد</b><h3>${ar(d.vendorName)}</h3><p>${ar(d.vendorDescription)}</p></div></div>
          <div class="tg-info-card"><i class="tg-icon">○</i><div><b>العميل</b><h3>${ar(d.customerName)}</h3><p>${ar(d.customerLocation || ((d.governorate||'') + ' - ' + (d.district||'')))}</p></div></div>
        </section>

        <section class="tg-info-grid">
          <div class="tg-mini-card"><i class="tg-icon">▣</i><div><b>طريقة الدفع</b><p>${ar(d.paymentMethod)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">▤</i><div><b>حالة الطلب</b><p>${ar(d.status)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">⌖</i><div><b>المحافظة</b><p>${ar(d.governorate)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">▥</i><div><b>المركز</b><p>${ar(d.district)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">□</i><div><b>عدد البنود</b><p>${d.items.length.toLocaleString('ar-EG')}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">ج.م</i><div><b>العملة</b><p>جنيه مصري</p></div></div>
        </section>

        <section class="tg-table-wrap">
          <table class="tg-items">
            <thead><tr><th>#</th><th>الصنف</th><th>نوع السعر</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th></tr></thead>
            <tbody>${rowHtml(d)}</tbody>
          </table>
        </section>

        <section class="tg-total-area">
          <div class="tg-summary">
            <div><span>إجمالي المنتجات</span><b>${money(d.subtotal,d.currency)}</b></div>
            <div><span>الخصم</span><b>${money(d.discount,d.currency)}</b></div>
            <div><span>الضريبة</span><b>${money(d.tax,d.currency)}</b></div>
            <div><span>${isOrder?'إجمالي الطلب':'الإجمالي المستحق'}</span><b>${money(d.total,d.currency)}</b></div>
          </div>
          <div class="tg-total-hero">
            <div class="box-art">▧</div>
            <b>${isOrder?'إجمالي الطلب':'الإجمالي المستحق'}</b>
            <strong>${money(d.total,d.currency)}</strong>
            <p>${ar(d.amountText || 'قيمة المستند بالجنيه المصري')}</p>
          </div>
        </section>

        <section class="tg-sign-panel">
          <div class="gold-seal">✓</div>
          <div class="gamal-sign"><small>اعتماد وتوقيع</small><div class="name">Gamal</div><b>GAMAL</b><span>Authorized Signatory</span></div>
          <div class="stamp">معتمد<br>ومختوم</div>
          <div class="truck-art">تم الاعتماد من منصة Tager</div>
        </section>

        <footer class="tg-footer"><b>Tager</b><em>منصة تجارة ذكية تربط الأسواق وتدعم النمو</em></footer>
      </div>`;
  }
  window.TagerPrintTemplate = { render, getPayload };
  document.addEventListener('DOMContentLoaded', render);
})();
