const $ = s => document.querySelector(s);
const app = $('#app');
const nav = $('#mainNav');
const toastBox = $('#toast');
const DB = window.TagerDB;
const fmt = n => `${Number(n||0).toLocaleString('ar-EG')} ج.م`;
const short = id => String(id||'').slice(0,8);

const GOV = {
  "القاهرة": [
    "مدينة نصر",
    "مصر الجديدة",
    "المعادي",
    "حلوان",
    "التجمع الخامس",
    "الشروق",
    "بدر",
    "الرحاب",
    "مدينتي",
    "شبرا",
    "الساحل",
    "روض الفرج",
    "حدائق القبة",
    "الزيتون",
    "عين شمس",
    "المطرية",
    "المرج",
    "السلام",
    "النزهة",
    "المقطم",
    "الخليفة",
    "السيدة زينب",
    "وسط البلد"
  ],
  "الجيزة": [
    "الدقي",
    "العجوزة",
    "المهندسين",
    "الهرم",
    "فيصل",
    "حدائق الأهرام",
    "6 أكتوبر",
    "الشيخ زايد",
    "الحوامدية",
    "البدرشين",
    "العياط",
    "الصف",
    "أطفيح",
    "إمبابة",
    "الوراق",
    "بولاق الدكرور",
    "أوسيم",
    "كرداسة"
  ],
  "الإسكندرية": [
    "سيدي جابر",
    "سموحة",
    "محرم بك",
    "العجمي",
    "المنتزه",
    "الرمل",
    "العامرية",
    "برج العرب",
    "الجمرك",
    "اللبان",
    "كرموز",
    "العطارين",
    "الدخيلة",
    "أبو قير"
  ],
  "القليوبية": [
    "بنها",
    "شبرا الخيمة",
    "قليوب",
    "القناطر الخيرية",
    "الخانكة",
    "طوخ",
    "قها",
    "كفر شكر",
    "شبين القناطر",
    "العبور",
    "الخصوص"
  ],
  "الشرقية": [
    "الزقازيق",
    "العاشر من رمضان",
    "بلبيس",
    "منيا القمح",
    "أبو حماد",
    "فاقوس",
    "أبو كبير",
    "الحسينية",
    "ديرب نجم",
    "ههيا",
    "كفر صقر",
    "أولاد صقر",
    "الإبراهيمية",
    "مشتول السوق",
    "الصالحية الجديدة"
  ],
  "الدقهلية": [
    "المنصورة",
    "طلخا",
    "ميت غمر",
    "السنبلاوين",
    "أجا",
    "دكرنس",
    "منية النصر",
    "بلقاس",
    "شربين",
    "الجمالية",
    "المطرية",
    "تمي الأمديد",
    "بني عبيد",
    "نبروه"
  ],
  "البحيرة": [
    "دمنهور",
    "كفر الدوار",
    "إدكو",
    "رشيد",
    "أبو حمص",
    "حوش عيسى",
    "الدلنجات",
    "كوم حمادة",
    "إيتاي البارود",
    "أبو المطامير",
    "وادي النطرون",
    "بدر",
    "النوبارية"
  ],
  "الغربية": [
    "طنطا",
    "المحلة الكبرى",
    "كفر الزيات",
    "زفتى",
    "سمنود",
    "قطور",
    "بسيون",
    "السنطة"
  ],
  "المنوفية": [
    "شبين الكوم",
    "منوف",
    "أشمون",
    "قويسنا",
    "السادات",
    "تلا",
    "الباجور",
    "بركة السبع",
    "الشهداء",
    "سرس الليان"
  ],
  "كفر الشيخ": [
    "كفر الشيخ",
    "دسوق",
    "فوه",
    "بيلا",
    "سيدي سالم",
    "بلطيم",
    "الحامول",
    "مطوبس",
    "قلين",
    "الرياض",
    "برج البرلس"
  ],
  "دمياط": [
    "دمياط",
    "رأس البر",
    "فارسكور",
    "كفر سعد",
    "الزرقا",
    "كفر البطيخ",
    "دمياط الجديدة",
    "عزبة البرج",
    "الروضة",
    "ميت أبو غالب"
  ],
  "بورسعيد": [
    "حي الشرق",
    "حي العرب",
    "حي المناخ",
    "حي الزهور",
    "حي الضواحي",
    "حي الجنوب",
    "حي غرب",
    "بورفؤاد"
  ],
  "الإسماعيلية": [
    "الإسماعيلية",
    "فايد",
    "القنطرة شرق",
    "القنطرة غرب",
    "التل الكبير",
    "أبو صوير",
    "القصاصين",
    "نفيشة"
  ],
  "السويس": [
    "حي السويس",
    "حي الأربعين",
    "حي فيصل",
    "حي الجناين",
    "عتاقة",
    "القطاع الريفي"
  ],
  "بني سويف": [
    "بني سويف",
    "الواسطى",
    "ناصر",
    "إهناسيا",
    "ببا",
    "الفشن",
    "سمسطا",
    "بني سويف الجديدة"
  ],
  "الفيوم": [
    "الفيوم",
    "سنورس",
    "إطسا",
    "طامية",
    "أبشواي",
    "يوسف الصديق",
    "الفيوم الجديدة"
  ],
  "المنيا": [
    "المنيا",
    "ملوي",
    "سمالوط",
    "أبو قرقاص",
    "بني مزار",
    "مطاي",
    "دير مواس",
    "مغاغة",
    "العدوة",
    "المنيا الجديدة"
  ],
  "أسيوط": [
    "أسيوط",
    "ديروط",
    "منفلوط",
    "القوصية",
    "أبنوب",
    "أبو تيج",
    "الغنايم",
    "ساحل سليم",
    "البداري",
    "صدفا",
    "أسيوط الجديدة"
  ],
  "سوهاج": [
    "سوهاج",
    "أخميم",
    "المنشاة",
    "جرجا",
    "طهطا",
    "طما",
    "البلينا",
    "دار السلام",
    "جهينة",
    "ساقلتة",
    "المراغة",
    "سوهاج الجديدة"
  ],
  "قنا": [
    "قنا",
    "نجع حمادي",
    "دشنا",
    "قوص",
    "نقادة",
    "فرشوط",
    "أبو تشت",
    "الوقف",
    "قفط",
    "قنا الجديدة"
  ],
  "الأقصر": [
    "الأقصر",
    "إسنا",
    "أرمنت",
    "البياضية",
    "الزينية",
    "الطود",
    "القرنة"
  ],
  "أسوان": [
    "أسوان",
    "دراو",
    "كوم أمبو",
    "إدفو",
    "نصر النوبة",
    "كلابشة",
    "أبو سمبل",
    "أسوان الجديدة"
  ],
  "البحر الأحمر": [
    "الغردقة",
    "رأس غارب",
    "سفاجا",
    "القصير",
    "مرسى علم",
    "الشلاتين",
    "حلايب"
  ],
  "مطروح": [
    "مرسى مطروح",
    "الحمام",
    "العلمين",
    "الضبعة",
    "النجيلة",
    "سيدي براني",
    "السلوم",
    "سيوة"
  ],
  "شمال سيناء": [
    "العريش",
    "بئر العبد",
    "الشيخ زويد",
    "رفح",
    "الحسنة",
    "نخل"
  ],
  "جنوب سيناء": [
    "شرم الشيخ",
    "دهب",
    "نويبع",
    "طور سيناء",
    "رأس سدر",
    "أبو رديس",
    "أبو زنيمة",
    "سانت كاترين",
    "طابا"
  ],
  "الوادي الجديد": [
    "الخارجة",
    "الداخلة",
    "الفرافرة",
    "باريس",
    "بلاط"
  ]
};
const govOptions = () => Object.keys(GOV).map(g=>`<option value="${g}">${g}</option>`).join('');
const districtOptions = g => (GOV[g]||[]).map(d=>`<option value="${d}">${d}</option>`).join('');
function areasFor(g,d){ return ['المركز الرئيسي','الحي الأول','الحي الثاني','الحي الثالث','المنطقة الصناعية','السوق التجاري','القرى التابعة'].map(a=>`${a}`); }
function areaOptions(g,d){ return areasFor(g,d).map(a=>`<option value="${a}">${a}</option>`).join(''); }

function toast(msg){ toastBox.textContent = msg; toastBox.classList.add('show'); setTimeout(()=>toastBox.classList.remove('show'), 3500); }
function formData(form){ return Object.fromEntries(new FormData(form).entries()); }
function route(){ return location.pathname === '/' ? '/' : location.pathname.replace(/\/$/,''); }
function go(path){ history.pushState(null,'',path); render(); }
function esc(s){ return String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function requireUser(role){ const u=DB.session(); if(!u){ go('/login'); return null; } if(role && u.role !== role && u.role !== 'admin'){ toast('لا توجد صلاحية لهذه الصفحة.'); go('/'); return null; } return u; }
function getCart(){ try{return JSON.parse(localStorage.getItem('tager_cart')||'[]')}catch{return[]} }
function setCart(c){ localStorage.setItem('tager_cart', JSON.stringify(c)); updateNav(); }
function addCart(product){ const c=getCart(); const f=c.find(x=>x.product.id===product.id); if(f) f.qty=Number(f.qty)+1; else c.push({product, qty:1, tier:'retail'}); setCart(c); toast('تمت الإضافة إلى السلة.'); }

function updateNav(){
  const u = DB.session();
  const c = getCart().length;
  const links = [
    ['/', 'الرئيسية'], ['/market','السوق'], ['/vendors','الموردون'], ['/cart',`السلة ${c?`(${c})`:''}`], ['/support','الدعم']
  ];
  if(u?.role==='customer') links.push(['/customer','حساب العميل']);
  if(u?.role==='vendor') links.push(['/vendor','لوحة المورد']);
  if(u?.role==='admin' || u?.role==='staff') links.push(['/admin','لوحة الإدارة']);
  nav.innerHTML = links.map(([href,label])=>`<a class="${route()===href?'active':''}" href="${href}">${label}</a>`).join('') +
    (u ? `<button id="logoutBtn">خروج</button>` : `<a class="${route()==='/login'?'active':''}" href="/login">دخول</a>`);
  $('#logoutBtn')?.addEventListener('click',()=>{DB.clearSession(); toast('تم تسجيل الخروج.'); go('/');});
}

function bindAddressSelectors(scope=document){
  const g = scope.querySelector('[data-gov]'); const d = scope.querySelector('[data-district]'); const a = scope.querySelector('[data-area]');
  if(!g || !d || !a) return;
  const refreshD = ()=>{ d.innerHTML = districtOptions(g.value); refreshA(); };
  const refreshA = ()=>{ a.innerHTML = areaOptions(g.value, d.value); };
  g.addEventListener('change', refreshD); d.addEventListener('change', refreshA); refreshD();
}
function statusPill(s){ const map={approved:['معتمد','ok'],pending:['قيد المراجعة','warn'],rejected:['مرفوض','bad'],suspended:['موقوف','bad'],new:['جديد','warn'],accepted:['مقبول','ok'],preparing:['تجهيز','warn'],out_for_delivery:['في التوصيل','warn'],delivered:['تم التسليم','ok'],cancelled:['ملغي','bad'],out_of_stock:['غير متوفر','bad'],paid:['مسدد','ok'],unpaid:['غير مسدد','warn'],partial:['مسدد جزئياً','warn'],refunded:['مرتجع','bad'],assigned:['مسند','warn'],picked:['تم الاستلام','warn']}; const m=map[s]||[s||'غير محدد','']; return `<span class="pill ${m[1]}">${m[0]}</span>`; }
function orderValue(orders, predicate){ return (orders||[]).filter(predicate).reduce((s,o)=>s+Number(o.total||0),0); }
function orderCount(orders, status){ return (orders||[]).filter(o=>o.status===status).length; }
function paymentValue(orders){ return (orders||[]).filter(o=>o.payment_status==='paid' || Number(o.paid_amount||0)>0).reduce((s,o)=>s+Number(o.paid_amount || (o.payment_status==='paid'?o.total:0) || 0),0); }
function financialCards(cards){ return `<div class="financeGrid">${cards.map(c=>`<div class="financeCard ${c.kind||''}"><span>${c.label}</span><strong>${c.value}</strong>${c.note?`<small>${c.note}</small>`:''}</div>`).join('')}</div>`; }

function statusLabel(s){
  const map={approved:'معتمد',pending:'قيد المراجعة',rejected:'مرفوض',suspended:'موقوف',new:'جديد',accepted:'مقبول',preparing:'تجهيز',out_for_delivery:'في التوصيل',delivered:'تم التسليم',cancelled:'ملغي',out_of_stock:'غير متوفر',paid:'مسدد',unpaid:'غير مسدد',partial:'مسدد جزئياً',refunded:'مرتجع',assigned:'مسند',picked:'تم الاستلام'};
  return map[s] || s || 'غير محدد';
}
function dateFmt(d){ try{return new Date(d).toLocaleDateString('ar-EG')}catch{return '-'} }
function csvCell(v){ return '"'+String(v ?? '').replace(/"/g,'""')+'"'; }
function exportCSV(filename, rows){
  if(!rows || !rows.length){ toast('لا توجد بيانات للتصدير.'); return; }
  const headers = Object.keys(rows[0]);
  const csv = '\ufeff' + [headers.map(csvCell).join(','), ...rows.map(r=>headers.map(h=>csvCell(r[h])).join(','))].join('\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href=url; a.download=filename; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
}
function customerFinanceRows(orders){
  const map=new Map();
  (orders||[]).forEach(o=>{const k=o.customer_id || 'unknown'; if(!map.has(k)) map.set(k,{العميل:o.users?.name||'', الهاتف:o.users?.phone||'', 'عدد الطلبات':0, 'إجمالي الطلبات':0, 'منفذ':0, 'مسدد':0, 'ملغي':0, 'متبقي':0});
    const r=map.get(k); r['عدد الطلبات']++; r['إجمالي الطلبات']+=Number(o.total||0); if(o.status==='delivered') r['منفذ']+=Number(o.total||0); if(o.status==='cancelled') r['ملغي']+=Number(o.total||0); const paid=Number(o.paid_amount || (o.payment_status==='paid'?o.total:0) || 0); r['مسدد']+=paid; r['متبقي']+=Math.max(Number(o.total||0)-paid,0);
  });
  return [...map.values()];
}
function vendorFinanceRows(s){
  return (s.vendors||[]).map(v=>{ const items=(s.orders||[]).flatMap(o=>(o.order_items||[]).filter(i=>i.vendor_id===v.id).map(i=>({i,o}))); const delivered=items.filter(x=>x.o.status==='delivered'); const sales=items.reduce((a,x)=>a+Number(x.i.subtotal||0),0); const delSales=delivered.reduce((a,x)=>a+Number(x.i.subtotal||0),0); const cancelled=items.filter(x=>x.o.status==='cancelled').reduce((a,x)=>a+Number(x.i.subtotal||0),0); const comm=delivered.reduce((a,x)=>a+Number(x.i.commission||0),0); const paid=(s.payments||[]).filter(p=>p.vendor_id===v.id && p.status==='approved').reduce((a,p)=>a+Number(p.amount||0),0); const pending=(s.payments||[]).filter(p=>p.vendor_id===v.id && p.status==='pending').reduce((a,p)=>a+Number(p.amount||0),0); return {المورد:v.store_name, الهاتف:v.users?.phone||'', 'إجمالي الطلبات':sales, 'منفذ':delSales, 'ملغي':cancelled, 'عمولة بعد التوصيل':comm, 'مدفوع معتمد':paid, 'تحت المراجعة':pending, 'متبقي':Math.max(comm-paid,0)}; });
}
function deliveryRows(s){
  const vendors = new Map((s.vendors||[]).map(v=>[v.id,v]));
  return (s.orders||[]).flatMap(o=>(o.deliveries||[]).map(d=>({delivery:d, order:o, vendor:vendors.get(d.vendor_id)})));
}
function moneyOrDash(n){ return Number(n||0) ? fmt(n) : '-'; }

function invoiceHTML(o){
  const lines = (o.order_items||[]).map(i=>`<tr><td>${esc(i.products?.name_ar||'')}</td><td>${esc(i.vendors?.store_name||'')}</td><td>${i.qty}</td><td>${fmt(i.unit_price)}</td><td>${fmt(i.subtotal)}</td></tr>`).join('');
  return `<html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>فاتورة ${short(o.id)}</title><style>body{font-family:Arial;padding:24px;color:#172033}table{width:100%;border-collapse:collapse;margin-top:16px}td,th{border:1px solid #d8dee9;padding:9px;text-align:right}.head{display:flex;justify-content:space-between;gap:16px}.box{border:1px solid #d8dee9;border-radius:12px;padding:14px}h1{margin:0 0 8px}</style></head><body><div class="head"><div><h1>Tager</h1><p>فاتورة طلب رقم ${short(o.id)}</p></div><div class="box"><b>الإجمالي: ${fmt(o.total)}</b><br>السداد: ${statusLabel(o.payment_status)}<br>الطلب: ${statusLabel(o.status)}</div></div><p>العميل: ${esc(o.users?.name||'')} - ${esc(o.users?.phone||'')}</p><p>العنوان: ${esc([o.governorate,o.district,o.area,o.address].filter(Boolean).join(' - '))}</p><table><thead><tr><th>المنتج</th><th>المورد</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${lines}</tbody></table><h2>الشحن: ${fmt(o.shipping_fee)}</h2><h2>الإجمالي النهائي: ${fmt(o.total)}</h2></body></html>`;
}
function printOrder(o){ const w=window.open('', '_blank'); if(!w){toast('اسمح بفتح النافذة للطباعة.'); return;} w.document.write(invoiceHTML(o)); w.document.close(); w.focus(); setTimeout(()=>w.print(), 300); }
function timeline(status){ const steps=[['new','جديد'],['accepted','مقبول'],['preparing','تجهيز'],['out_for_delivery','توصيل'],['delivered','تسليم']]; const idx=steps.findIndex(x=>x[0]===status); if(status==='cancelled') return '<div class="timeline cancelled"><span>ملغي</span></div>'; return `<div class="timeline">${steps.map((s,i)=>`<span class="${i<=idx?'done':''}">${s[1]}</span>`).join('')}</div>`; }

function phoneLink(n){ return `tel:${String(n).replace(/\s+/g,'')}`; }
function whatsappLink(n){ return `https://wa.me/${String(n).replace(/\D/g,'')}`; }

async function home(){
  const ready = DB.ready;
  let summary = {vendors:[],products:[],orders:[]};
  try{ if(ready) summary = await DB.adminSummary(); }catch{}
  app.innerHTML = `
    <section class="hero">
      <div class="heroPanel">
        <span class="eyebrow">منصة تشغيل تجاري متكاملة</span>
        <h1>إدارة الجملة والتجزئة والموردين والتوصيل من مكان واحد</h1>
        <p>نظام واضح للعميل والمورد والإدارة: مناطق تغطية دقيقة، طلبات منظمة، حسابات عمولة، وتتبع مالي وتشغيلي.</p>
        <div class="actions">
          <a class="btn" href="/market">دخول السوق</a>
          <a class="btn secondary" href="/register/vendor">انضم كمورد</a>
          <a class="btn light" href="/register/customer">تسجيل عميل</a>
        </div>
      </div>
      <div class="heroSide card">
        <h3>مسار تشغيل واضح</h3>
        <p class="muted">كل طلب يتم التحقق من عنوانه قبل الإتمام حتى لا يتم إرسال الطلب إلى مورد لا يغطي المنطقة.</p>
        <div class="badgeList"><span class="pill ok">تغطية حسب المحافظة</span><span class="pill ok">المركز</span><span class="pill ok">القسم / الحي</span></div>
        <hr />
        <h3>حسابات منظمة</h3>
        <p class="muted">مبيعات المورد، عمولة المنصة، المدفوع، المتبقي، وصافي الاستحقاق.</p>
      </div>
    </section>
    <section class="statGrid">
      <div class="stat"><b>${summary.vendors?.filter(v=>v.status==='approved').length||0}</b><span>مورد معتمد</span></div>
      <div class="stat"><b>${summary.products?.filter(p=>p.status==='approved').length||0}</b><span>منتج متاح</span></div>
      <div class="stat"><b>${summary.orders?.length||0}</b><span>طلب مسجل</span></div>
      <div class="stat"><b>${fmt(summary.orders?.reduce((s,o)=>s+Number(o.total||0),0)||0)}</b><span>قيمة الطلبات</span></div>
    </section>
    <section class="sectionTitle"><div><h2>مكونات المنصة</h2><p>صفحات واضحة لكل طرف في دورة الطلب.</p></div></section>
    <div class="grid three">
      ${['السوق والمنتجات','إدارة الموردين','تتبع الطلبات','مناطق التوصيل','الحسابات المالية','تقارير الإدارة'].map(x=>`<div class="card"><h3>${x}</h3><p class="muted">واجهة مرتبة وسهلة الاستخدام مع تحكم كامل في البيانات.</p></div>`).join('')}
    </div>`;
}

async function setup(){
  app.innerHTML = `<div class="split"><section class="card"><h2>إنشاء حساب الإدارة الأول</h2><p class="muted">هذه الصفحة تستخدم مرة واحدة فقط عند بداية التشغيل.</p><form id="setupForm" class="form"><div class="field"><label>اسم المدير</label><input name="name" required></div><div class="field"><label>رقم الهاتف</label><input name="phone" required></div><div class="field"><label>البريد</label><input name="email" type="email"></div><div class="field"><label>كلمة المرور</label><input name="password" type="password" minlength="8" required></div><button class="btn">إنشاء الحساب</button></form></section><section class="card"><h2>بعد إنشاء الحساب</h2><p class="muted">ادخل لوحة الإدارة لاعتماد الموردين والمنتجات ومراجعة الطلبات والحسابات.</p><a class="btn secondary" href="/login">تسجيل الدخول</a></section></div>`;
  $('#setupForm').addEventListener('submit', async e=>{e.preventDefault(); try{ await DB.createAdmin(formData(e.target)); toast('تم إنشاء حساب الإدارة.'); go('/admin'); }catch(err){toast(err.message);} });
}

function loginPage(){
  app.innerHTML = `<div class="split"><section class="card"><h2>تسجيل الدخول</h2><form id="loginForm" class="form"><div class="field"><label>رقم الهاتف</label><input name="phone" required></div><div class="field"><label>كلمة المرور</label><input name="password" type="password" required></div><button class="btn">دخول</button></form></section><section class="card"><h2>حساب جديد</h2><p class="muted">اختر نوع الحساب المناسب.</p><div class="actions"><a class="btn secondary" href="/register/customer">عميل</a><a class="btn secondary" href="/register/vendor">مورد</a></div></section></div>`;
  $('#loginForm').addEventListener('submit', async e=>{e.preventDefault(); try{ const u=await DB.login(e.target.phone.value,e.target.password.value); toast('تم الدخول.'); go(u.role==='admin'||u.role==='staff'?'/admin':u.role==='vendor'?'/vendor':'/customer'); }catch(err){toast(err.message);} });
}

function customerRegister(){
  app.innerHTML = `<section class="card"><h2>تسجيل عميل</h2><form id="customerForm" class="form"><div class="grid two"><div class="field"><label>الاسم</label><input name="name" required></div><div class="field"><label>رقم الهاتف</label><input name="phone" required></div><div class="field"><label>البريد</label><input name="email" type="email"></div><div class="field"><label>كلمة المرور</label><input name="password" type="password" minlength="8" required></div><div class="field"><label>المحافظة</label><select name="governorate" data-gov>${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district" data-district></select></div><div class="field"><label>القسم / الحي</label><select name="area" data-area></select></div><div class="field"><label>العنوان التفصيلي</label><input name="address" required></div></div><button class="btn">إنشاء الحساب</button></form></section>`;
  bindAddressSelectors();
  $('#customerForm').addEventListener('submit', async e=>{e.preventDefault(); try{ await DB.registerCustomer(formData(e.target)); toast('تم إنشاء حساب العميل.'); go('/customer'); }catch(err){toast(err.message);} });
}

function vendorRegister(){
  app.innerHTML = `<section class="card"><h2>تسجيل مورد</h2><form id="vendorForm" class="form"><div class="grid two"><div class="field"><label>اسم المسؤول</label><input name="owner_name" required></div><div class="field"><label>اسم المتجر</label><input name="store_name" required></div><div class="field"><label>رقم الهاتف</label><input name="phone" required></div><div class="field"><label>البريد</label><input name="email" type="email"></div><div class="field"><label>كلمة المرور</label><input name="password" type="password" minlength="8" required></div><div class="field"><label>السجل التجاري</label><input name="commercial_register"></div><div class="field"><label>الرقم الضريبي</label><input name="tax_number"></div><div class="field"><label>الحد الأدنى للطلب</label><input name="min_order" type="number" value="0"></div><div class="field"><label>عمولة المنصة %</label><input name="commission_percent" type="number" step="0.1" value="1.5"></div><div class="field"><label>رسوم السلة المميزة %</label><input name="premium_cart_percent" type="number" step="0.1" value="1.5"></div><div class="field"><label>المحافظة</label><select name="governorate" data-gov>${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district" data-district></select></div><div class="field"><label>القسم / الحي</label><select name="area" data-area></select></div><div class="field"><label>العنوان</label><input name="address"></div></div><div class="field"><label>نبذة عن المورد</label><textarea name="description"></textarea></div><button class="btn">إرسال طلب الانضمام</button></form></section>`;
  bindAddressSelectors();
  $('#vendorForm').addEventListener('submit', async e=>{e.preventDefault(); try{ await DB.registerVendor(formData(e.target)); toast('تم إرسال طلب المورد للإدارة.'); go('/login'); }catch(err){toast(err.message);} });
}

async function market(){
  let products=[]; try{products=await DB.products('approved')}catch(err){toast(err.message)}
  const vendors = [...new Set(products.map(p=>p.vendors?.store_name).filter(Boolean))];
  app.innerHTML = `<section class="sectionTitle"><div><h2>السوق</h2><p>تصفح المنتجات حسب المورد والمنطقة ونوع السعر.</p></div></section><section class="card"><div class="grid four"><div class="field"><label>بحث</label><input id="q" placeholder="اسم المنتج أو المورد"></div><div class="field"><label>المحافظة</label><select id="fg"><option value="">كل المحافظات</option>${govOptions()}</select></div><div class="field"><label>المركز</label><select id="fd"><option value="">كل المراكز</option></select></div><div class="field"><label>نوع السعر</label><select id="tier"><option value="retail">قطاعي</option><option value="wholesale">جملة</option><option value="bulk">جملة الجملة</option></select></div></div></section><div id="productGrid" class="grid three" style="margin-top:16px"></div>`;
  $('#fg').addEventListener('change',()=>{$('#fd').innerHTML='<option value="">كل المراكز</option>'+districtOptions($('#fg').value); draw();});
  ['q','fd','tier'].forEach(id=>$('#'+id).addEventListener('input',draw));
  function draw(){
    const q=$('#q').value.trim(); const g=$('#fg').value; const d=$('#fd').value; const tier=$('#tier').value;
    const filtered=products.filter(p=>{
      const okQ=!q || `${p.name_ar} ${p.name_en||''} ${p.vendors?.store_name||''}`.includes(q);
      const zones=p.vendors?.delivery_zones||[]; const okG=!g || zones.some(z=>z.governorate===g && (!d || z.district===d));
      return okQ && okG;
    });
    $('#productGrid').innerHTML = filtered.length ? filtered.map(p=>{ const price=DB.priceFor(p, Number(tier==='retail'?1:tier==='wholesale'?p.wholesale_min_qty:p.bulk_min_qty), tier); return `<article class="card product"><div class="productImg">${p.image_url?`<img src="${esc(p.image_url)}" alt="${esc(p.name_ar)}">`:'منتج'}</div><h3>${esc(p.name_ar)}</h3><p class="muted">${esc(p.vendors?.store_name||'')}</p><div class="priceLine"><span class="pill">قطاعي ${fmt(p.retail_price)}</span><span class="pill">جملة ${fmt(p.wholesale_price)}</span><span class="pill">جملة الجملة ${fmt(p.bulk_price)}</span></div><p class="muted">المخزون: ${Number(p.stock_qty||0).toLocaleString('ar-EG')} ${esc(p.unit)}</p><button class="btn" data-add="${p.id}">إضافة للسلة - ${fmt(price)}</button></article>`}).join('') : `<div class="empty">لا توجد منتجات مطابقة.</div>`;
    document.querySelectorAll('[data-add]').forEach(b=>b.onclick=()=>addCart(products.find(p=>p.id===b.dataset.add)));
  } draw();
}

async function vendorsPage(){
  let vs=[]; try{vs=await DB.vendors('approved')}catch(err){toast(err.message)}
  app.innerHTML = `<section class="sectionTitle"><div><h2>الموردون</h2><p>قائمة الموردين المعتمدين ومناطق التغطية.</p></div></section><div class="grid three">${vs.length?vs.map(v=>`<article class="card"><div class="vendorImg">${v.logo_url?`<img src="${esc(v.logo_url)}">`:'مورد'}</div><h3>${esc(v.store_name)}</h3><p class="muted">${esc(v.description||'')}</p><div class="badgeList">${(v.delivery_zones||[]).slice(0,6).map(z=>`<span class="pill">${esc(z.governorate)} - ${esc(z.district)} - ${esc(z.area)}</span>`).join('')||'<span class="pill warn">لم يحدد مناطق</span>'}</div><p>الحد الأدنى: <b>${fmt(v.min_order)}</b></p></article>`).join(''):'<div class="empty">لا يوجد موردون معتمدون حالياً.</div>'}</div>`;
}

function cartPage(){
  const cart=getCart(); const user=DB.session();
  app.innerHTML = `<section class="sectionTitle"><div><h2>السلة</h2><p>الطلب لا يكتمل إلا بعد التحقق من تغطية المورد للعنوان.</p></div></section><section class="card"><div id="cartList"></div><hr><form id="orderForm" class="form"><div class="grid two"><div class="field"><label>نوع السلة</label><select name="cart_type"><option value="standard">سلة عادية</option><option value="premium">سلة مميزة</option></select></div><div class="field"><label>طريقة الدفع</label><select name="payment_method"><option value="cash">دفع عند الاستلام</option><option value="transfer">تحويل بنكي</option><option value="wallet">محفظة</option></select></div><div class="field"><label>المحافظة</label><select name="governorate" data-gov>${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district" data-district></select></div><div class="field"><label>القسم / الحي</label><select name="area" data-area></select></div><div class="field"><label>العنوان التفصيلي</label><input name="address" value="${esc(user?.address||'')}" required></div></div><button class="btn">إتمام الطلب</button></form></section>`;
  bindAddressSelectors();
  const g=$('[data-gov]'); if(user?.governorate && GOV[user.governorate]){g.value=user.governorate; g.dispatchEvent(new Event('change')); if(user.district){$('[data-district]').value=user.district; $('[data-district]').dispatchEvent(new Event('change'));} if(user.area)$('[data-area]').value=user.area;}
  function draw(){
    const c=getCart();
    $('#cartList').innerHTML = c.length ? c.map((x,i)=>`<div class="cartItem"><strong>${esc(x.product.name_ar)}</strong><input type="number" min="1" value="${x.qty}" data-qty="${i}"><select data-tier="${i}"><option value="retail" ${x.tier==='retail'?'selected':''}>قطاعي</option><option value="wholesale" ${x.tier==='wholesale'?'selected':''}>جملة</option><option value="bulk" ${x.tier==='bulk'?'selected':''}>جملة الجملة</option></select><b>${fmt(DB.priceFor(x,x.qty,x.tier)*x.qty)}</b><button class="btn small danger" data-del="${i}">حذف</button></div>`).join('') + `<h3>الإجمالي قبل الشحن: ${fmt(c.reduce((s,x)=>s+DB.priceFor(x,x.qty,x.tier)*Number(x.qty),0))}</h3>` : '<div class="empty">السلة فارغة.</div>';
    document.querySelectorAll('[data-qty]').forEach(el=>el.onchange=()=>{const c=getCart(); c[el.dataset.qty].qty=Number(el.value); setCart(c); draw();});
    document.querySelectorAll('[data-tier]').forEach(el=>el.onchange=()=>{const c=getCart(); c[el.dataset.tier].tier=el.value; setCart(c); draw();});
    document.querySelectorAll('[data-del]').forEach(el=>el.onclick=()=>{const c=getCart(); c.splice(el.dataset.del,1); setCart(c); draw();});
  } draw();
  $('#orderForm').addEventListener('submit', async e=>{e.preventDefault(); try{ const d=formData(e.target); await DB.createOrder(getCart(), d, d.cart_type, d.payment_method); setCart([]); toast('تم تسجيل الطلب بنجاح.'); go('/customer'); }catch(err){toast(err.message);} });
}

async function customerPage(){
  const u=requireUser('customer'); if(!u)return;
  let orders=[]; try{orders=await DB.myOrders()}catch(err){toast(err.message)}
  const total=orders.reduce((s,o)=>s+Number(o.total||0),0);
  const delivered=orders.filter(o=>o.status==='delivered');
  const cancelled=orders.filter(o=>o.status==='cancelled');
  const paidTotal=paymentValue(orders);
  const active=orders.filter(o=>!['delivered','cancelled'].includes(o.status));
  app.innerHTML = `<section class="sectionTitle"><div><h2>حساب العميل</h2><p>${esc(u.name)} - ${esc(u.phone)}</p></div><a class="btn secondary" href="/market">متابعة التسوق</a></section>
  <div class="grid two">
    <div class="card"><h3>العنوان المسجل</h3><p>${esc([u.governorate,u.district,u.area,u.address].filter(Boolean).join(' - '))}</p><p class="muted">يتم التحقق من هذا العنوان عند إتمام الطلب حتى لا يتم اختيار مورد خارج نطاق التغطية.</p></div>
    <div class="card"><h3>ملخص حساب العميل</h3><p class="muted">إجمالي الطلبات وقيمتها وحالات السداد تظهر هنا بشكل مباشر.</p></div>
  </div>
  ${financialCards([
    {label:'إجمالي الطلبات', value:orders.length.toLocaleString('ar-EG'), note:fmt(total)},
    {label:'طلبات منفذة', value:delivered.length.toLocaleString('ar-EG'), note:fmt(orderValue(orders,o=>o.status==='delivered')), kind:'ok'},
    {label:'طلبات مسددة', value:orders.filter(o=>o.payment_status==='paid').length.toLocaleString('ar-EG'), note:fmt(paidTotal), kind:'ok'},
    {label:'طلبات ملغية', value:cancelled.length.toLocaleString('ar-EG'), note:fmt(orderValue(orders,o=>o.status==='cancelled')), kind:'bad'},
    {label:'طلبات جارية', value:active.length.toLocaleString('ar-EG'), note:fmt(orderValue(active,()=>true)), kind:'warn'}
  ])}
  <section class="sectionTitle"><h2>قائمة الطلبات</h2></section>
  <div class="tableWrap"><table><thead><tr><th>رقم</th><th>القيمة</th><th>حالة الطلب</th><th>السداد</th><th>المسدد</th><th>التوصيل</th><th>العنوان</th><th>تاريخ</th></tr></thead><tbody>${orders.map(o=>`<tr><td>${short(o.id)}</td><td>${fmt(o.total)}</td><td>${statusPill(o.status)}${timeline(o.status)}</td><td>${statusPill(o.payment_status||'unpaid')}</td><td>${fmt(o.paid_amount || (o.payment_status==='paid'?o.total:0))}</td><td>${(o.deliveries||[]).map(d=>statusPill(d.status)).join(' ')||'-'}</td><td>${esc(o.governorate)} - ${esc(o.district)} - ${esc(o.area)}</td><td>${new Date(o.created_at).toLocaleDateString('ar-EG')}</td></tr>`).join('')||'<tr><td colspan="8">لا توجد طلبات.</td></tr>'}</tbody></table></div>`;
}

async function vendorPage(){
  const u=requireUser('vendor'); if(!u)return; let vendor=null; try{vendor=await DB.vendorByUser(u.id)}catch(err){toast(err.message)}
  if(!vendor){app.innerHTML='<div class="empty">لم يتم العثور على حساب المورد.</div>';return;}
  let products=[], orders=[], fin={sales:0,deliveredSales:0,paidSales:0,cancelledSales:0,commission:0,net:0,paid:0,pending:0,remaining:0,payments:[],orderStats:{total:0,delivered:0,paid:0,cancelled:0,active:0}};
  try{products=(await DB.products()).filter(p=>p.vendor_id===vendor.id); orders=await DB.vendorOrders(vendor.id); fin=await DB.vendorFinancial(vendor.id);}catch(err){toast(err.message)}
  app.innerHTML = `<section class="sectionTitle"><div><h2>لوحة المورد</h2><p>${esc(vendor.store_name)} ${statusPill(vendor.status)}</p></div></section><div class="tabs"><button class="tab active" data-tab="summary">الملخص</button><button class="tab" data-tab="zones">التوصيل</button><button class="tab" data-tab="products">المنتجات</button><button class="tab" data-tab="orders">الطلبات</button><button class="tab" data-tab="finance">المالية</button></div><div id="vendorContent"></div>`;
  const setTab=(name)=>{document.querySelectorAll('.tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===name)); const c=$('#vendorContent');
    if(name==='summary') c.innerHTML=`${financialCards([
      {label:'الطلبات الإجمالية', value:(fin.orderStats.total||0).toLocaleString('ar-EG'), note:fmt(fin.sales)},
      {label:'طلبات منفذة', value:(fin.orderStats.delivered||0).toLocaleString('ar-EG'), note:fmt(fin.deliveredSales), kind:'ok'},
      {label:'طلبات مسددة', value:(fin.orderStats.paid||0).toLocaleString('ar-EG'), note:fmt(fin.paidSales), kind:'ok'},
      {label:'طلبات ملغية', value:(fin.orderStats.cancelled||0).toLocaleString('ar-EG'), note:fmt(fin.cancelledSales), kind:'bad'},
      {label:'عمولة مستحقة بعد التوصيل', value:fmt(fin.commission), note:'تحتسب على الطلبات المنفذة فقط', kind:'warn'},
      {label:'متبقي على المورد', value:fmt(fin.remaining), note:'بعد خصم الدفعات المعتمدة'}
    ])}<section class="card"><h3>بيانات المورد</h3><p>${esc(vendor.description||'')}</p><p>الحد الأدنى: <b>${fmt(vendor.min_order)}</b> — نسبة المنصة بعد التوصيل: <b>${vendor.commission_percent}%</b></p></section><section class="card"><h3>تنبيهات المخزون</h3><div class="badgeList">${products.filter(p=>Number(p.stock_qty||0)<=5).slice(0,8).map(p=>`<span class="pill warn">${esc(p.name_ar)}: ${Number(p.stock_qty||0).toLocaleString('ar-EG')}</span>`).join('')||'<span class="pill ok">لا توجد نواقص حرجة</span>'}</div></section>`;
    if(name==='zones') { c.innerHTML=`<section class="card"><h3>مناطق التوصيل</h3><p class="muted">أضف المناطق بدقة حتى لا يستطيع العميل إتمام طلب خارج تغطيتك.</p><div id="zonesList" class="grid"></div><hr><form id="zoneForm" class="form"><div class="zoneRow"><div class="field"><label>المحافظة</label><select name="governorate" data-gov>${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district" data-district></select></div><div class="field"><label>القسم / الحي</label><select name="area" data-area></select></div><div class="field"><label>رسوم</label><input name="fee" type="number" value="0"></div><div class="field"><label>المدة</label><input name="duration" value="24-48 ساعة"></div><button class="btn">إضافة</button></div></form></section>`; bindAddressSelectors(c); const drawZ=()=>{$('#zonesList').innerHTML=(vendor.delivery_zones||[]).map((z,i)=>`<div class="card"><strong>${esc(z.governorate)} - ${esc(z.district)} - ${esc(z.area)}</strong><p class="muted">${fmt(z.fee)} - ${esc(z.duration)}</p><button class="btn small danger" data-rz="${i}">حذف</button></div>`).join('')||'<div class="empty">لم يتم تحديد مناطق بعد.</div>'; document.querySelectorAll('[data-rz]').forEach(b=>b.onclick=async()=>{vendor.delivery_zones.splice(Number(b.dataset.rz),1); vendor=await DB.updateVendor(vendor.id,{delivery_zones:vendor.delivery_zones}); drawZ();});}; drawZ(); $('#zoneForm').onsubmit=async e=>{e.preventDefault(); const d=formData(e.target); vendor.delivery_zones=[...(vendor.delivery_zones||[]),{governorate:d.governorate,district:d.district,area:d.area,fee:Number(d.fee||0),duration:d.duration}]; vendor=await DB.updateVendor(vendor.id,{delivery_zones:vendor.delivery_zones}); e.target.reset(); bindAddressSelectors(c); drawZ(); toast('تم حفظ منطقة التوصيل.');}; }
    if(name==='products') { c.innerHTML=`<section class="card"><h3>إضافة منتج</h3><form id="productForm" class="form"><div class="grid three"><div class="field"><label>اسم المنتج</label><input name="name_ar" required></div><div class="field"><label>التصنيف</label><input name="category"></div><div class="field"><label>الوحدة</label><input name="unit" value="قطعة"></div><div class="field"><label>سعر قطاعي</label><input name="retail_price" type="number" required></div><div class="field"><label>سعر جملة</label><input name="wholesale_price" type="number"></div><div class="field"><label>سعر جملة الجملة</label><input name="bulk_price" type="number"></div><div class="field"><label>حد الجملة</label><input name="wholesale_min_qty" type="number" value="1"></div><div class="field"><label>حد جملة الجملة</label><input name="bulk_min_qty" type="number" value="1"></div><div class="field"><label>المخزون</label><input name="stock_qty" type="number" value="0"></div><div class="field"><label>رابط الصورة</label><input name="image_url" placeholder="https://..."></div></div><div class="field"><label>الوصف</label><textarea name="description"></textarea></div><button class="btn">حفظ المنتج</button></form></section><section class="sectionTitle"><h2>منتجاتي</h2></section><div class="grid three">${products.map(p=>`<article class="card"><h3>${esc(p.name_ar)}</h3><p>${statusPill(p.status)}</p><p>${fmt(p.retail_price)} / ${esc(p.unit)}</p><p>المخزون: <b>${Number(p.stock_qty||0).toLocaleString('ar-EG')}</b></p></article>`).join('')||'<div class="empty">لا توجد منتجات.</div>'}</div>`; $('#productForm').onsubmit=async e=>{e.preventDefault(); const d=formData(e.target); await DB.saveProduct({vendor_id:vendor.id,status:'pending',name_ar:d.name_ar,category:d.category,unit:d.unit,retail_price:Number(d.retail_price||0),wholesale_price:Number(d.wholesale_price||d.retail_price||0),bulk_price:Number(d.bulk_price||d.wholesale_price||d.retail_price||0),wholesale_min_qty:Number(d.wholesale_min_qty||1),bulk_min_qty:Number(d.bulk_min_qty||1),stock_qty:Number(d.stock_qty||0),image_url:d.image_url,description:d.description}); toast('تم حفظ المنتج وينتظر اعتماد الإدارة.'); render();}; }
    if(name==='orders') c.innerHTML=`<div class="tableWrap"><table><thead><tr><th>طلب</th><th>منتج</th><th>كمية</th><th>قيمة</th><th>حالة الطلب</th><th>السداد</th><th>عمولة بعد التوصيل</th></tr></thead><tbody>${orders.map(x=>`<tr><td>${short(x.order_id)}</td><td>${esc(x.products?.name_ar||'')}</td><td>${x.qty}</td><td>${fmt(x.subtotal)}</td><td>${statusPill(x.orders?.status)}</td><td>${statusPill(x.orders?.payment_status||'unpaid')}</td><td>${x.orders?.status==='delivered'?fmt(x.commission):'-'}</td></tr>`).join('')||'<tr><td colspan="7">لا توجد طلبات.</td></tr>'}</tbody></table></div>`;
    if(name==='finance') c.innerHTML=`${financialCards([
      {label:'مبيعات منفذة', value:fmt(fin.deliveredSales), note:'طلبات تم تسليمها', kind:'ok'},
      {label:'نسبة المنصة بعد التوصيل', value:vendor.commission_percent+'%', note:fmt(fin.commission), kind:'warn'},
      {label:'مدفوع ومعتمد', value:fmt(fin.paid), note:'دفعات مقبولة', kind:'ok'},
      {label:'دفعات تحت المراجعة', value:fmt(fin.pending), note:'لا تخصم من المتبقي حتى الاعتماد', kind:'warn'},
      {label:'المتبقي الإجمالي', value:fmt(fin.remaining), note:'المبلغ المطلوب سداده'},
      {label:'صافي المورد بعد العمولة', value:fmt(fin.net), note:'على الطلبات المنفذة'}
    ])}<section class="card"><h3>سداد المتبقي الإجمالي</h3><p class="muted">يمكن للمورد إرسال دفعة بقيمة المتبقي بالكامل أو إدخال مبلغ جزئي، ولا يتم خصمها إلا بعد اعتماد الإدارة.</p><form id="payForm" class="form"><div class="grid three"><div class="field"><label>المبلغ</label><input name="amount" type="number" step="0.01" min="0" max="${Math.max(fin.remaining,0)}" value="${Math.max(fin.remaining,0)}" required></div><div class="field"><label>طريقة الدفع</label><select name="method"><option>تحويل بنكي</option><option>محفظة</option><option>نقدي</option></select></div><div class="field"><label>رقم العملية</label><input name="reference"></div></div><div class="field"><label>ملاحظات</label><textarea name="notes"></textarea></div><button class="btn">إرسال الدفعة للإدارة</button></form></section><section class="sectionTitle"><h2>دفعات العمولة</h2></section><div class="tableWrap"><table><thead><tr><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>رقم العملية</th><th>التاريخ</th></tr></thead><tbody>${fin.payments.map(p=>`<tr><td>${fmt(p.amount)}</td><td>${esc(p.method)}</td><td>${statusPill(p.status)}</td><td>${esc(p.reference||'-')}</td><td>${new Date(p.created_at).toLocaleDateString('ar-EG')}</td></tr>`).join('')||'<tr><td colspan="5">لا توجد دفعات.</td></tr>'}</tbody></table></div>`; $('#payForm')?.addEventListener('submit',async e=>{e.preventDefault(); const d=formData(e.target); const amount=Number(d.amount||0); if(amount<=0){toast('أدخل مبلغ صحيح.');return;} if(amount>Math.max(fin.remaining,0)){toast('المبلغ أكبر من المتبقي.');return;} await DB.savePayment({vendor_id:vendor.id,amount,method:d.method,reference:d.reference,notes:d.notes,status:'pending'}); toast('تم إرسال الدفعة للإدارة.'); render();});
  };
  document.querySelectorAll('.tab').forEach(t=>t.onclick=()=>setTab(t.dataset.tab)); setTab('summary');
}

async function adminPage(){
  const u=requireUser('admin'); if(!u)return; let s={vendors:[],products:[],orders:[],payments:[],tickets:[]}; try{s=await DB.adminSummary()}catch(err){toast(err.message)}
  const deliveredOrders=s.orders.filter(o=>o.status==='delivered');
  const paidOrders=s.orders.filter(o=>o.payment_status==='paid');
  const cancelledOrders=s.orders.filter(o=>o.status==='cancelled');
  const commissionDelivered=deliveredOrders.reduce((a,o)=>a+Number(o.commission_total||0),0);
  const paymentsApproved=s.payments.filter(p=>p.status==='approved').reduce((a,p)=>a+Number(p.amount||0),0);
  const paymentsPending=s.payments.filter(p=>p.status==='pending').reduce((a,p)=>a+Number(p.amount||0),0);
  const vendorRows=vendorFinanceRows(s); const customerRows=customerFinanceRows(s.orders); const delRows=deliveryRows(s);
  app.innerHTML = `<section class="sectionTitle"><div><h2>لوحة الإدارة</h2><p>متابعة الموردين، المنتجات، الطلبات، التوصيل، والسداد.</p></div><button class="btn secondary" id="exportAllFinance">تصدير القوائم المالية</button></section><div class="tabs"><button class="tab active" data-tab="dash">الملخص</button><button class="tab" data-tab="vendors">الموردون</button><button class="tab" data-tab="products">المنتجات</button><button class="tab" data-tab="orders">الطلبات</button><button class="tab" data-tab="deliveries">التوصيلات</button><button class="tab" data-tab="customers">العملاء</button><button class="tab" data-tab="payments">دفعات الموردين</button><button class="tab" data-tab="finance">القوائم المالية</button><button class="tab" data-tab="support">الدعم</button><button class="tab" data-tab="staff">فريق الإدارة</button></div><div id="adminContent"></div>`;
  $('#exportAllFinance')?.addEventListener('click',()=>exportCSV('tager_financial_summary.csv', vendorRows));
  const setTab=(name)=>{document.querySelectorAll('.tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===name)); const c=$('#adminContent');
    if(name==='dash') c.innerHTML=`${financialCards([
      {label:'الموردون', value:s.vendors.length.toLocaleString('ar-EG'), note:`${s.vendors.filter(v=>v.status==='approved').length} معتمد`},
      {label:'المنتجات', value:s.products.length.toLocaleString('ar-EG'), note:`${s.products.filter(p=>p.status==='approved').length} معتمد`},
      {label:'إجمالي الطلبات', value:s.orders.length.toLocaleString('ar-EG'), note:fmt(s.orders.reduce((a,o)=>a+Number(o.total||0),0))},
      {label:'طلبات منفذة', value:deliveredOrders.length.toLocaleString('ar-EG'), note:fmt(orderValue(s.orders,o=>o.status==='delivered')), kind:'ok'},
      {label:'طلبات مسددة', value:paidOrders.length.toLocaleString('ar-EG'), note:fmt(paymentValue(s.orders)), kind:'ok'},
      {label:'طلبات ملغية', value:cancelledOrders.length.toLocaleString('ar-EG'), note:fmt(orderValue(s.orders,o=>o.status==='cancelled')), kind:'bad'},
      {label:'عمولة بعد التوصيل', value:fmt(commissionDelivered), note:'تستحق بعد التسليم', kind:'warn'},
      {label:'متبقي من الموردين', value:fmt(Math.max(commissionDelivered-paymentsApproved,0)), note:`تحت المراجعة ${fmt(paymentsPending)}`}
    ])}<div class="grid three"><div class="card"><h3>مراجعة يومية</h3><p class="muted">راجع الطلبات الجديدة والتوصيلات المفتوحة والدفعات المعلقة.</p></div><div class="card"><h3>منع أخطاء العنوان</h3><p class="muted">الطلب لا يتم إلا إذا كانت منطقة العميل مغطاة من المورد.</p></div><div class="card"><h3>التحصيل</h3><p class="muted">المتبقي يظهر بعد خصم الدفعات المعتمدة فقط.</p></div></div>`;
    if(name==='vendors') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportVendors">تصدير الموردين</button></div><div class="tableWrap"><table><thead><tr><th>المورد</th><th>الهاتف</th><th>الحالة</th><th>نسبة المنصة</th><th>حد الطلب</th><th>مناطق التغطية</th><th>إجراء</th></tr></thead><tbody>${s.vendors.map(v=>`<tr><td>${esc(v.store_name)}</td><td>${esc(v.users?.phone||'')}</td><td>${statusPill(v.status)}</td><td>${v.commission_percent}%</td><td>${fmt(v.min_order)}</td><td>${(v.delivery_zones||[]).length.toLocaleString('ar-EG')}</td><td><button class="btn small" data-av="${v.id}">اعتماد</button> <button class="btn small danger" data-rv="${v.id}">رفض</button></td></tr>`).join('')||'<tr><td colspan="7">لا توجد بيانات.</td></tr>'}</tbody></table></div>`;
    if(name==='products') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportProducts">تصدير المنتجات</button></div><div class="tableWrap"><table><thead><tr><th>المنتج</th><th>المورد</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>${s.products.map(p=>`<tr><td>${esc(p.name_ar)}</td><td>${esc(p.vendors?.store_name||'')}</td><td>${esc(p.category||'-')}</td><td>${fmt(p.retail_price)}</td><td>${Number(p.stock_qty||0).toLocaleString('ar-EG')} ${esc(p.unit||'')}</td><td>${statusPill(p.status)}</td><td><button class="btn small" data-ap="${p.id}">اعتماد</button> <button class="btn small danger" data-rp="${p.id}">رفض</button></td></tr>`).join('')||'<tr><td colspan="7">لا توجد بيانات.</td></tr>'}</tbody></table></div>`;
    if(name==='orders') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportOrders">تصدير الطلبات</button></div><div class="tableWrap"><table><thead><tr><th>رقم</th><th>العميل</th><th>القيمة</th><th>حالة الطلب</th><th>السداد</th><th>المسدد</th><th>تغيير الطلب</th><th>تغيير السداد</th><th>مبلغ السداد</th><th>فاتورة</th></tr></thead><tbody>${s.orders.map(o=>`<tr><td>${short(o.id)}</td><td>${esc(o.users?.name||'')}<br><span class="muted">${esc(o.users?.phone||'')}</span></td><td>${fmt(o.total)}</td><td>${statusPill(o.status)}${timeline(o.status)}</td><td>${statusPill(o.payment_status||'unpaid')}</td><td>${fmt(o.paid_amount || (o.payment_status==='paid'?o.total:0))}</td><td><select data-os="${o.id}"><option value="new" ${o.status==='new'?'selected':''}>جديد</option><option value="accepted" ${o.status==='accepted'?'selected':''}>مقبول</option><option value="preparing" ${o.status==='preparing'?'selected':''}>تجهيز</option><option value="out_for_delivery" ${o.status==='out_for_delivery'?'selected':''}>في التوصيل</option><option value="delivered" ${o.status==='delivered'?'selected':''}>تم التسليم</option><option value="cancelled" ${o.status==='cancelled'?'selected':''}>ملغي</option></select></td><td><select data-op="${o.id}" data-total="${o.total}"><option value="unpaid" ${(o.payment_status||'unpaid')==='unpaid'?'selected':''}>غير مسدد</option><option value="partial" ${o.payment_status==='partial'?'selected':''}>مسدد جزئياً</option><option value="paid" ${o.payment_status==='paid'?'selected':''}>مسدد</option><option value="refunded" ${o.payment_status==='refunded'?'selected':''}>مرتجع</option></select></td><td><input class="miniInput" type="number" data-paid="${o.id}" value="${Number(o.paid_amount||0)}" min="0" max="${Number(o.total||0)}"></td><td><button class="btn small secondary" data-invoice="${o.id}">طباعة</button></td></tr>`).join('')||'<tr><td colspan="10">لا توجد طلبات.</td></tr>'}</tbody></table></div>`;
    if(name==='deliveries') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportDeliveries">تصدير التوصيلات</button></div><div class="tableWrap"><table><thead><tr><th>طلب</th><th>العميل</th><th>المورد</th><th>العنوان</th><th>رسوم</th><th>المدة</th><th>الحالة</th><th>تغيير</th><th>ملاحظة</th></tr></thead><tbody>${delRows.map(x=>`<tr><td>${short(x.order.id)}</td><td>${esc(x.order.users?.name||'')}</td><td>${esc(x.vendor?.store_name||'-')}</td><td>${esc(x.delivery.governorate)} - ${esc(x.delivery.district)} - ${esc(x.delivery.area)}</td><td>${fmt(x.delivery.fee)}</td><td>${esc(x.delivery.duration||'-')}</td><td>${statusPill(x.delivery.status)}</td><td><select data-ds="${x.delivery.id}"><option value="pending" ${x.delivery.status==='pending'?'selected':''}>جديد</option><option value="assigned" ${x.delivery.status==='assigned'?'selected':''}>مسند</option><option value="picked" ${x.delivery.status==='picked'?'selected':''}>تم الاستلام</option><option value="delivered" ${x.delivery.status==='delivered'?'selected':''}>تم التسليم</option><option value="cancelled" ${x.delivery.status==='cancelled'?'selected':''}>ملغي</option></select></td><td><input class="miniInput" data-dn="${x.delivery.id}" value="${esc(x.delivery.tracking_note||'')}"></td></tr>`).join('')||'<tr><td colspan="9">لا توجد توصيلات.</td></tr>'}</tbody></table></div>`;
    if(name==='customers') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportCustomers">تصدير العملاء</button></div><div class="tableWrap"><table><thead><tr><th>العميل</th><th>الهاتف</th><th>عدد الطلبات</th><th>إجمالي الطلبات</th><th>منفذ</th><th>مسدد</th><th>ملغي</th><th>متبقي</th></tr></thead><tbody>${customerRows.map(r=>`<tr><td>${esc(r['العميل'])}</td><td>${esc(r['الهاتف'])}</td><td>${r['عدد الطلبات']}</td><td>${fmt(r['إجمالي الطلبات'])}</td><td>${fmt(r['منفذ'])}</td><td>${fmt(r['مسدد'])}</td><td>${fmt(r['ملغي'])}</td><td>${fmt(r['متبقي'])}</td></tr>`).join('')||'<tr><td colspan="8">لا توجد بيانات عملاء.</td></tr>'}</tbody></table></div>`;
    if(name==='payments') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportPayments">تصدير الدفعات</button></div><div class="tableWrap"><table><thead><tr><th>المورد</th><th>المبلغ</th><th>الطريقة</th><th>رقم العملية</th><th>الحالة</th><th>ملاحظات</th><th>إجراء</th></tr></thead><tbody>${s.payments.map(p=>`<tr><td>${esc(p.vendors?.store_name||'')}</td><td>${fmt(p.amount)}</td><td>${esc(p.method)}</td><td>${esc(p.reference||'-')}</td><td>${statusPill(p.status)}</td><td>${esc(p.notes||'')}</td><td><button class="btn small" data-payok="${p.id}">اعتماد</button> <button class="btn small danger" data-payno="${p.id}">رفض</button></td></tr>`).join('')||'<tr><td colspan="7">لا توجد دفعات.</td></tr>'}</tbody></table></div>`;
    if(name==='finance') c.innerHTML=`${financialCards([
      {label:'إجمالي طلبات العملاء', value:fmt(s.orders.reduce((a,o)=>a+Number(o.total||0),0)), note:`عدد ${s.orders.length}`},
      {label:'إجمالي المنفذ', value:fmt(orderValue(s.orders,o=>o.status==='delivered')), note:`عدد ${deliveredOrders.length}`, kind:'ok'},
      {label:'إجمالي المسدد', value:fmt(paymentValue(s.orders)), note:`عدد ${paidOrders.length}`, kind:'ok'},
      {label:'إجمالي الملغي', value:fmt(orderValue(s.orders,o=>o.status==='cancelled')), note:`عدد ${cancelledOrders.length}`, kind:'bad'},
      {label:'عمولات مستحقة بعد التوصيل', value:fmt(commissionDelivered), note:'بعد التسليم فقط', kind:'warn'},
      {label:'مدفوع من الموردين', value:fmt(paymentsApproved), note:`متبقي ${fmt(Math.max(commissionDelivered-paymentsApproved,0))}`}
    ])}<div class="toolbar"><button class="btn small secondary" id="exportFinanceVendors">تصدير مالية الموردين</button><button class="btn small secondary" id="exportFinanceCustomers">تصدير مالية العملاء</button></div><section class="sectionTitle"><h2>قائمة مالية حسب المورد</h2></section><div class="tableWrap"><table><thead><tr><th>المورد</th><th>إجمالي الطلبات</th><th>منفذ</th><th>ملغي</th><th>عمولة بعد التوصيل</th><th>مدفوع</th><th>تحت المراجعة</th><th>متبقي</th></tr></thead><tbody>${vendorRows.map(r=>`<tr><td>${esc(r['المورد'])}</td><td>${fmt(r['إجمالي الطلبات'])}</td><td>${fmt(r['منفذ'])}</td><td>${fmt(r['ملغي'])}</td><td>${fmt(r['عمولة بعد التوصيل'])}</td><td>${fmt(r['مدفوع معتمد'])}</td><td>${fmt(r['تحت المراجعة'])}</td><td>${fmt(r['متبقي'])}</td></tr>`).join('')||'<tr><td colspan="8">لا توجد بيانات مالية.</td></tr>'}</tbody></table></div><section class="sectionTitle"><h2>قائمة مالية حسب العميل</h2></section><div class="tableWrap"><table><thead><tr><th>العميل</th><th>عدد الطلبات</th><th>إجمالي</th><th>منفذ</th><th>مسدد</th><th>ملغي</th><th>متبقي</th></tr></thead><tbody>${customerRows.map(r=>`<tr><td>${esc(r['العميل'])}</td><td>${r['عدد الطلبات']}</td><td>${fmt(r['إجمالي الطلبات'])}</td><td>${fmt(r['منفذ'])}</td><td>${fmt(r['مسدد'])}</td><td>${fmt(r['ملغي'])}</td><td>${fmt(r['متبقي'])}</td></tr>`).join('')||'<tr><td colspan="7">لا توجد بيانات عملاء.</td></tr>'}</tbody></table></div>`;
    if(name==='support') c.innerHTML=`<div class="toolbar"><button class="btn small secondary" id="exportSupport">تصدير الدعم</button></div><div class="tableWrap"><table><thead><tr><th>الاسم</th><th>الهاتف</th><th>النوع</th><th>الحالة</th><th>الرسالة</th><th>تغيير الحالة</th><th>ملاحظة الإدارة</th><th>التاريخ</th></tr></thead><tbody>${(s.tickets||[]).map(t=>`<tr><td>${esc(t.name||'')}</td><td>${esc(t.phone||'')}</td><td>${esc(t.ticket_type||'')}</td><td>${statusPill(t.status||'new')}</td><td>${esc(t.message||'')}</td><td><select data-ts="${t.id}"><option value="new" ${(t.status||'new')==='new'?'selected':''}>جديد</option><option value="open" ${t.status==='open'?'selected':''}>مفتوح</option><option value="closed" ${t.status==='closed'?'selected':''}>مغلق</option></select></td><td><input class="miniInput" data-tn="${t.id}" value="${esc(t.admin_note||'')}"></td><td>${dateFmt(t.created_at)}</td></tr>`).join('')||'<tr><td colspan="8">لا توجد رسائل دعم.</td></tr>'}</tbody></table></div>`;
    if(name==='staff') c.innerHTML=`<section class="card"><h3>إضافة مسؤول تشغيل</h3><form id="staffForm" class="form"><div class="grid three"><div class="field"><label>الاسم</label><input name="name" required></div><div class="field"><label>الهاتف</label><input name="phone" required></div><div class="field"><label>البريد</label><input name="email" type="email"></div><div class="field"><label>كلمة المرور</label><input name="password" type="password" minlength="8" required></div><div class="field"><label>الصلاحية</label><select name="permission"><option value="operations">تشغيل</option><option value="finance">مالية</option><option value="support">دعم</option><option value="full">كامل</option></select></div></div><button class="btn">حفظ المسؤول</button></form></section><section class="sectionTitle"><h2>فريق الإدارة</h2></section><div class="tableWrap"><table><thead><tr><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>الحالة</th><th>الصلاحيات</th></tr></thead><tbody>${(s.staff||[]).map(x=>`<tr><td>${esc(x.name)}</td><td>${esc(x.phone)}</td><td>${esc(x.email||'')}</td><td>${statusPill(x.status)}</td><td>${esc(Object.keys(x.permissions||{}).join('، ')||'-')}</td></tr>`).join('')||'<tr><td colspan="5">لا يوجد فريق إدارة.</td></tr>'}</tbody></table></div>`;
    $('#exportVendors')?.addEventListener('click',()=>exportCSV('tager_vendors.csv', s.vendors.map(v=>({المورد:v.store_name, الهاتف:v.users?.phone||'', الحالة:statusLabel(v.status), النسبة:v.commission_percent, 'حد الطلب':v.min_order, 'مناطق التغطية':(v.delivery_zones||[]).length}))));
    $('#exportProducts')?.addEventListener('click',()=>exportCSV('tager_products.csv', s.products.map(p=>({المنتج:p.name_ar, المورد:p.vendors?.store_name||'', التصنيف:p.category||'', السعر:p.retail_price, المخزون:p.stock_qty, الحالة:statusLabel(p.status)}))));
    $('#exportOrders')?.addEventListener('click',()=>exportCSV('tager_orders.csv', s.orders.map(o=>({رقم:short(o.id), العميل:o.users?.name||'', الهاتف:o.users?.phone||'', الإجمالي:o.total, الحالة:statusLabel(o.status), السداد:statusLabel(o.payment_status), المسدد:o.paid_amount||0, العنوان:[o.governorate,o.district,o.area,o.address].filter(Boolean).join(' - ')}))));
    $('#exportDeliveries')?.addEventListener('click',()=>exportCSV('tager_deliveries.csv', delRows.map(x=>({رقم_الطلب:short(x.order.id), العميل:x.order.users?.name||'', المورد:x.vendor?.store_name||'', العنوان:[x.delivery.governorate,x.delivery.district,x.delivery.area].join(' - '), الرسوم:x.delivery.fee, الحالة:statusLabel(x.delivery.status), الملاحظة:x.delivery.tracking_note||''}))));
    $('#exportCustomers')?.addEventListener('click',()=>exportCSV('tager_customers_finance.csv', customerRows));
    $('#exportPayments')?.addEventListener('click',()=>exportCSV('tager_vendor_payments.csv', s.payments.map(p=>({المورد:p.vendors?.store_name||'', المبلغ:p.amount, الطريقة:p.method, المرجع:p.reference||'', الحالة:statusLabel(p.status), ملاحظات:p.notes||''}))));
    $('#exportSupport')?.addEventListener('click',()=>exportCSV('tager_support_tickets.csv', (s.tickets||[]).map(t=>({الاسم:t.name||'', الهاتف:t.phone||'', النوع:t.ticket_type||'', الحالة:statusLabel(t.status), الرسالة:t.message||'', ملاحظة:t.admin_note||''}))));
    $('#exportFinanceVendors')?.addEventListener('click',()=>exportCSV('tager_vendor_finance.csv', vendorRows));
    $('#exportFinanceCustomers')?.addEventListener('click',()=>exportCSV('tager_customer_finance.csv', customerRows));
    document.querySelectorAll('[data-av]').forEach(b=>b.onclick=async()=>{const v=s.vendors.find(x=>x.id===b.dataset.av); await DB.updateVendor(v.id,{status:'approved'}); await DB.updateUser(v.user_id,{status:'approved'}); toast('تم اعتماد المورد.'); render();});
    document.querySelectorAll('[data-rv]').forEach(b=>b.onclick=async()=>{const v=s.vendors.find(x=>x.id===b.dataset.rv); await DB.updateVendor(v.id,{status:'rejected'}); await DB.updateUser(v.user_id,{status:'rejected'}); toast('تم رفض المورد.'); render();});
    document.querySelectorAll('[data-ap]').forEach(b=>b.onclick=async()=>{await DB.saveProduct({id:b.dataset.ap,status:'approved'}); toast('تم اعتماد المنتج.'); render();});
    document.querySelectorAll('[data-rp]').forEach(b=>b.onclick=async()=>{await DB.saveProduct({id:b.dataset.rp,status:'rejected'}); toast('تم رفض المنتج.'); render();});
    document.querySelectorAll('[data-payok]').forEach(b=>b.onclick=async()=>{await DB.updatePayment(b.dataset.payok,'approved','تم الاعتماد'); toast('تم اعتماد الدفعة.'); render();});
    document.querySelectorAll('[data-payno]').forEach(b=>b.onclick=async()=>{await DB.updatePayment(b.dataset.payno,'rejected','تم الرفض'); toast('تم رفض الدفعة.'); render();});
    document.querySelectorAll('[data-invoice]').forEach(b=>b.onclick=()=>{const o=s.orders.find(x=>x.id===b.dataset.invoice); if(o) printOrder(o);});
    document.querySelectorAll('[data-os]').forEach(x=>x.onchange=async()=>{await DB.updateOrderStatus(x.dataset.os,x.value); toast('تم تحديث حالة الطلب.'); render();});
    document.querySelectorAll('[data-op]').forEach(x=>x.onchange=async()=>{const paidInput=document.querySelector(`[data-paid="${x.dataset.op}"]`); const total=Number(x.dataset.total||0); const amount=x.value==='paid'?total:Number(paidInput?.value||0); await DB.updateOrderPayment(x.dataset.op,x.value,amount); toast('تم تحديث السداد.'); render();});
    document.querySelectorAll('[data-paid]').forEach(x=>x.onchange=async()=>{const statusSel=document.querySelector(`[data-op="${x.dataset.paid}"]`); const status=statusSel?.value || 'partial'; await DB.updateOrderPayment(x.dataset.paid,status,Number(x.value||0)); toast('تم تحديث مبلغ السداد.'); render();});
    document.querySelectorAll('[data-ds]').forEach(x=>x.onchange=async()=>{const note=document.querySelector(`[data-dn="${x.dataset.ds}"]`)?.value||''; await DB.updateDeliveryStatus(x.dataset.ds,x.value,note); toast('تم تحديث حالة التوصيل.'); render();});
    document.querySelectorAll('[data-dn]').forEach(x=>x.onchange=async()=>{const status=document.querySelector(`[data-ds="${x.dataset.dn}"]`)?.value||'pending'; await DB.updateDeliveryStatus(x.dataset.dn,status,x.value); toast('تم تحديث ملاحظة التوصيل.');});
    document.querySelectorAll('[data-ts]').forEach(x=>x.onchange=async()=>{const note=document.querySelector(`[data-tn="${x.dataset.ts}"]`)?.value||''; await DB.updateSupportTicket(x.dataset.ts,x.value,note); toast('تم تحديث طلب الدعم.'); render();});
    document.querySelectorAll('[data-tn]').forEach(x=>x.onchange=async()=>{const status=document.querySelector(`[data-ts="${x.dataset.tn}"]`)?.value||'open'; await DB.updateSupportTicket(x.dataset.tn,status,x.value); toast('تم تحديث ملاحظة الدعم.');});
    $('#staffForm')?.addEventListener('submit',async e=>{e.preventDefault(); const d=formData(e.target); await DB.createStaff({name:d.name,phone:d.phone,email:d.email,password:d.password,permissions:{[d.permission]:true}}); toast('تم إضافة مسؤول التشغيل.'); render();});
  };
  document.querySelectorAll('.tab').forEach(t=>t.onclick=()=>setTab(t.dataset.tab)); setTab('dash');
}

function support(){ app.innerHTML=`<section class="sectionTitle"><div><h2>الدعم</h2><p>قنوات تواصل واضحة للطلبات والموردين والحسابات.</p></div></section>
<div class="grid three">
  <a class="supportCard" href="${phoneLink('+20 10 24237231')}"><span>اتصال مباشر</span><strong>+20 10 24237231</strong><small>خدمة العملاء والطلبات</small></a>
  <a class="supportCard" href="${phoneLink('+20 10 16135495')}"><span>اتصال مباشر</span><strong>+20 10 16135495</strong><small>متابعة الموردين والتوصيل</small></a>
  <a class="supportCard" href="${phoneLink('+20 11 27512512')}"><span>اتصال مباشر</span><strong>+20 11 27512512</strong><small>الشؤون المالية والدعم العام</small></a>
</div>
<section class="card" style="margin-top:16px"><h3>واتساب الدعم</h3><p class="muted">لإرسال رقم الطلب أو بيانات السداد أو متابعة المورد.</p><a class="btn" target="_blank" rel="noopener" href="${whatsappLink('+201127512512')}">فتح واتساب +20 11 27512512</a></section>
<section class="card" style="margin-top:16px"><h3>إرسال طلب دعم</h3><form id="supportForm" class="form"><div class="grid three"><div class="field"><label>الاسم</label><input name="name" required></div><div class="field"><label>رقم الهاتف</label><input name="phone" required></div><div class="field"><label>نوع الطلب</label><select name="ticket_type"><option>طلب</option><option>توصيل</option><option>مورد</option><option>مالي</option><option>أخرى</option></select></div></div><div class="field"><label>الرسالة</label><textarea name="message" required></textarea></div><button class="btn">إرسال</button></form></section>
<div class="grid three" style="margin-top:16px"><div class="card"><h3>مشكلة طلب</h3><p>أرسل رقم الطلب وحالة التوصيل.</p></div><div class="card"><h3>مشكلة مورد</h3><p>أرسل اسم المورد والمنطقة المطلوب تغطيتها.</p></div><div class="card"><h3>مشكلة مالية</h3><p>أرسل رقم العملية والمبلغ والمورد.</p></div></div>`; $('#supportForm')?.addEventListener('submit',async e=>{e.preventDefault(); try{const d=formData(e.target); const u=DB.session(); await DB.createSupportTicket({...d,user_id:u?.id||null,status:'new'}); e.target.reset(); toast('تم إرسال طلب الدعم.');}catch(err){toast(err.message)}}); }
function policies(){ app.innerHTML=`<section class="card"><h2>السياسات</h2><div class="grid two"><div><h3>اعتماد المورد</h3><p class="muted">لا يظهر المورد في السوق إلا بعد اعتماد الإدارة.</p></div><div><h3>اعتماد المنتج</h3><p class="muted">لا يظهر المنتج للعميل إلا بعد مراجعته واعتماده.</p></div><div><h3>التوصيل</h3><p class="muted">إتمام الطلب مرتبط بتغطية المورد للمحافظة والمركز والقسم المختار.</p></div><div><h3>العمولة</h3><p class="muted">تستحق عمولة المنصة بعد التسليم فقط وتخصم منها الدفعات المعتمدة.</p></div><div><h3>السداد</h3><p class="muted">الدفعات المرسلة من الموردين لا تخصم من المتبقي إلا بعد اعتماد الإدارة.</p></div><div><h3>الطلبات الملغية</h3><p class="muted">الطلبات الملغية تظهر في القوائم المالية للمتابعة ولا تدخل ضمن المنفذ.</p></div></div></section>`; }

async function render(){
  updateNav(); nav.classList.remove('open');
  if(!DB.ready){ app.innerHTML=`<section class="card"><h2>إعداد الاتصال مطلوب</h2><p class="muted">أضف متغيرات قاعدة البيانات في Vercel ثم أعد النشر.</p><p><span class="kbd">NEXT_PUBLIC_SUPABASE_URL</span></p><p><span class="kbd">NEXT_PUBLIC_SUPABASE_ANON_KEY</span></p></section>`; return; }
  const r=route();
  try{
    if(r==='/') return home(); if(r==='/setup') return setup(); if(r==='/login') return loginPage();
    if(r==='/register/customer') return customerRegister(); if(r==='/register/vendor') return vendorRegister();
    if(r==='/market') return market(); if(r==='/vendors') return vendorsPage(); if(r==='/cart') return cartPage();
    if(r==='/customer') return customerPage(); if(r==='/vendor') return vendorPage(); if(r==='/admin') return adminPage();
    if(r==='/support') return support(); if(r==='/policies') return policies();
    app.innerHTML=`<div class="empty">الصفحة غير موجودة. <br><a class="btn secondary" href="/">الرجوع للرئيسية</a></div>`;
  }catch(err){ app.innerHTML=`<section class="card"><h2>تعذر تحميل الصفحة</h2><p class="muted">${esc(err.message)}</p></section>`; }
}

document.addEventListener('click', e=>{ const a=e.target.closest('a[href^="/"]'); if(a){ e.preventDefault(); go(a.getAttribute('href')); } });
window.addEventListener('popstate', render);
$('#menuBtn').onclick=()=>nav.classList.toggle('open');
render();
