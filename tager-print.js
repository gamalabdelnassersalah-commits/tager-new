
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

  // Inline icon paths (mirrors assets/img/icon-sprite.svg) so the document
  // never depends on an external fetch and always renders, online or offline.
  const ICONS = {
    calendar:'<rect x="11" y="15" width="42" height="39" rx="5" fill="none" stroke="currentColor" stroke-width="5"/><path d="M20 8v14M44 8v14M11 27h42" stroke="currentColor" stroke-width="5"/><circle cx="22" cy="38" r="3" fill="currentColor"/><circle cx="32" cy="38" r="3" fill="currentColor"/><circle cx="42" cy="38" r="3" fill="currentColor"/><circle cx="22" cy="47" r="3" fill="currentColor"/><circle cx="32" cy="47" r="3" fill="currentColor"/>',
    doc:'<path d="M18 6h22l11 11v41H18z" fill="none" stroke="currentColor" stroke-width="5"/><path d="M40 6v13h11M26 30h18M26 40h18M26 50h12" stroke="currentColor" stroke-width="4"/>',
    clip:'<rect x="15" y="12" width="34" height="45" rx="5" fill="none" stroke="currentColor" stroke-width="5"/><path d="M25 12a7 7 0 0114 0h6v10H19V12zM24 34l7 7 12-14" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>',
    person:'<circle cx="32" cy="21" r="11" fill="none" stroke="currentColor" stroke-width="5"/><path d="M13 56c4-14 13-21 19-21s15 7 19 21" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>',
    people:'<circle cx="24" cy="24" r="7" fill="none" stroke="currentColor" stroke-width="4"/><circle cx="42" cy="24" r="7" fill="none" stroke="currentColor" stroke-width="4"/><path d="M12 50c3-10 8-15 13-15s10 5 13 15M31 50c3-10 8-15 13-15s10 5 13 15" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>',
    location:'<path d="M32 58S13 39 13 24a19 19 0 1138 0c0 15-19 34-19 34z" fill="none" stroke="currentColor" stroke-width="5"/><circle cx="32" cy="24" r="7" fill="currentColor"/>',
    building:'<path d="M17 57V16h30v41M11 57h42M25 25h5M34 25h5M25 35h5M34 35h5M25 45h5M34 45h5" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>',
    wallet:'<rect x="9" y="18" width="46" height="33" rx="5" fill="none" stroke="currentColor" stroke-width="5"/><path d="M44 30h12v11H44a6 6 0 010-11zM18 18l22-8 5 8" fill="none" stroke="currentColor" stroke-width="5"/><circle cx="47" cy="35" r="2.5" fill="currentColor"/>',
    money:'<path d="M45 18a17 17 0 10-.2 28M21 25h22M21 38h22" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><path d="M51 20l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>',
    box:'<path d="M32 6l23 13v26L32 58 9 45V19z" fill="none" stroke="currentColor" stroke-width="5"/><path d="M9 19l23 13 23-13M32 32v26" fill="none" stroke="currentColor" stroke-width="5"/>',
    check:'<path d="M12 33l14 15 26-32" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>'
  };
  function icon(name, cls){ return `<svg viewBox="0 0 64 64" class="tg-svgicon ${cls||''}" aria-hidden="true">${ICONS[name]||''}</svg>`; }

  function num(v){ return Number(v || 0); }
  function ar(v){ return String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function money(v, c){ return `${num(v).toLocaleString('ar-EG')} ${c || 'ج.م'}`; }
  function getPayload(){
    try{
      const direct = window.TAGER_DOCUMENT_DATA;
      if(direct) return {...fallback, ...direct};
      const params = new URLSearchParams(location.search);
      // Used by TagerDocumentFrame.jsx: /tager-documents/invoice.html?data=<json>
      const dataParam = params.get('data');
      if(dataParam) return {...fallback, ...JSON.parse(dataParam)};
      const key = params.get('key') || 'tager_print_payload';
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
        <td class="tg-item-name">${ar(i.name)} ${icon('box','tg-cube-ic')}</td>
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
          <div class="tg-title-block">
            <div class="tg-title-icon">${icon(isOrder?'clip':'doc')}<small>${icon(isOrder?'check':'box')}</small></div>
            <div class="tg-title">
              <h1>${mainTitle}</h1>
              <p>${subtitle}</p>
            </div>
          </div>
          <div class="tg-brand">
            <img src="assets/img/tager-logo-header.png" alt="Tager" onerror="this.src='assets/img/tager-logo.png'">
            <div class="tagline">Trade • Supply • Connect</div>
          </div>
        </header>

        <section class="tg-meta-strip ${isOrder ? 'two' : ''}">
          <div class="tg-meta-item"><i class="tg-icon">${icon(isOrder?'clip':'clip')}</i><div><strong>${ar(d.orderNo)}</strong><b>رقم الطلب</b></div></div>
          ${isOrder ? '' : `<div class="tg-meta-item"><i class="tg-icon">${icon('doc')}</i><div><strong>${ar(d.invoiceNo)}</strong><b>رقم الفاتورة</b></div></div>`}
          <div class="tg-meta-item"><i class="tg-icon">${icon('calendar')}</i><div><span>${ar(isOrder ? d.orderDate : d.date)}</span><b>${isOrder?'تاريخ الطلب':'التاريخ'}</b></div></div>
        </section>

        <section class="tg-party-grid">
          <div class="tg-info-card vendor"><i class="tg-icon">${icon('people')}</i><div><b>المورد</b><h3>${ar(d.vendorName)}</h3><p>${ar(d.vendorDescription)}</p></div></div>
          <div class="tg-info-card"><i class="tg-icon">${icon('person')}</i><div><b>العميل</b><h3>${ar(d.customerName)}</h3><p>${ar(d.customerLocation || ((d.governorate||'') + ' - ' + (d.district||'')))}</p></div></div>
          <div class="tg-info-card"><i class="tg-icon">${icon('box')}</i><div><b>المنصة</b><h3>${ar(d.platformName)}</h3><p>${ar(d.platformDescription)}</p></div></div>
        </section>

        <section class="tg-info-grid">
          <div class="tg-mini-card"><i class="tg-icon">${icon('location')}</i><div><b>المحافظة</b><p>${ar(d.governorate)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">${icon('building')}</i><div><b>المركز</b><p>${ar(d.district)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">${icon('wallet')}</i><div><b>طريقة الدفع</b><p>${ar(d.paymentMethod)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">${icon('box')}</i><div><b>عدد البنود</b><p>${d.items.length.toLocaleString('ar-EG')}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">${icon('clip')}</i><div><b>حالة الطلب</b><p>${ar(d.status)}</p></div></div>
          <div class="tg-mini-card"><i class="tg-icon">${icon('money')}</i><div><b>العملة</b><p>جنيه مصري</p></div></div>
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
            <img class="box-art" src="assets/img/cube.svg" alt="">
            <b>${isOrder?'إجمالي الطلب':'الإجمالي المستحق'}</b>
            <strong>${money(d.total,d.currency)}</strong>
            <p>${ar(d.amountText || 'قيمة المستند بالجنيه المصري')}</p>
          </div>
        </section>

        <section class="tg-sign-panel">
          <img class="gold-seal" src="assets/img/rosette.svg" alt="">
          <div class="gamal-sign">
            <small>اعتماد وتوقيع</small>
            <img class="sign-img" src="assets/img/signature-gamal-stamp.png" alt="Gamal">
            <b>GAMAL</b><span>Authorized Signatory</span>
          </div>
          <img class="stamp" src="assets/img/seal.svg" alt="">
          <div class="truck-art"><img src="assets/img/truck.svg" alt="Tager"></div>
        </section>

        <footer class="tg-footer"><b>Tager</b><em>منصة تجارة ذكية تربط الأسواق وتدعم النمو</em></footer>
      </div>`;
  }
  window.TagerPrintTemplate = { render, getPayload };
  document.addEventListener('DOMContentLoaded', render);
})();
