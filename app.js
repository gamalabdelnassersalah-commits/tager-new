'use strict';

const STORE_KEY = 'tager_v26_elite_production_state';
const SESSION_KEY = 'tager_v26_session';
const CART_KEY = 'tager_v26_cart';

const EGYPT = {
  'القاهرة':['مدينة نصر','مصر الجديدة','المعادي','التجمع الخامس','الزمالك','شبرا','حلوان','السلام','المرج','عين شمس'],
  'الجيزة':['الدقي','المهندسين','الهرم','فيصل','6 أكتوبر','الشيخ زايد','العجوزة','البدرشين','العياط'],
  'الإسكندرية':['سيدي جابر','محرم بك','العجمي','المنتزه','سموحة','برج العرب','كرموز','العامرية'],
  'القليوبية':['بنها','قليوب','شبرا الخيمة','الخانكة','طوخ','القناطر الخيرية','كفر شكر'],
  'الشرقية':['الزقازيق','العاشر من رمضان','بلبيس','منيا القمح','أبو حماد','فاقوس','أبو كبير'],
  'الدقهلية':['المنصورة','طلخا','ميت غمر','السنبلاوين','أجا','منية النصر','دكرنس'],
  'الغربية':['طنطا','المحلة الكبرى','كفر الزيات','زفتى','السنطة','بسيون'],
  'المنوفية':['شبين الكوم','مدينة السادات','منوف','أشمون','قويسنا','تلا','الباجور'],
  'البحيرة':['دمنهور','كفر الدوار','رشيد','إدكو','أبو حمص','وادي النطرون','إيتاي البارود'],
  'كفر الشيخ':['كفر الشيخ','دسوق','فوه','بيلا','بلطيم','سيدي سالم'],
  'دمياط':['دمياط','رأس البر','فارسكور','كفر سعد','الزرقا','دمياط الجديدة'],
  'بورسعيد':['حي الشرق','حي العرب','حي المناخ','حي الزهور','بورفؤاد'],
  'الإسماعيلية':['الإسماعيلية','فايد','القنطرة شرق','القنطرة غرب','التل الكبير'],
  'السويس':['السويس','الأربعين','عتاقة','فيصل','الجناين'],
  'شمال سيناء':['العريش','بئر العبد','الشيخ زويد','رفح','الحسنة'],
  'جنوب سيناء':['طور سيناء','شرم الشيخ','دهب','نويبع','رأس سدر','سانت كاترين'],
  'بني سويف':['بني سويف','الواسطى','ناصر','إهناسيا','ببا','الفشن'],
  'الفيوم':['الفيوم','سنورس','طامية','إطسا','أبشواي','يوسف الصديق'],
  'المنيا':['المنيا','ملوي','سمالوط','بني مزار','مطاي','أبو قرقاص','دير مواس'],
  'أسيوط':['أسيوط','ديروط','منفلوط','القوصية','أبنوب','أبو تيج','صدفا'],
  'سوهاج':['سوهاج','أخميم','المنشأة','جرجا','طهطا','طما','البلينا','دار السلام'],
  'قنا':['قنا','نجع حمادي','دشنا','قوص','أبو تشت','فرشوط','نقادة'],
  'الأقصر':['الأقصر','إسنا','أرمنت','القرنة','الزينية','البياضية'],
  'أسوان':['أسوان','دراو','كوم أمبو','نصر النوبة','إدفو','أبو سمبل'],
  'البحر الأحمر':['الغردقة','رأس غارب','سفاجا','القصير','مرسى علم','حلايب'],
  'الوادي الجديد':['الخارجة','الداخلة','الفرافرة','باريس','بلاط'],
  'مطروح':['مرسى مطروح','الحمام','العلمين','الضبعة','سيوة','السلوم']
};

const DEFAULT_STATE = () => ({
  version:'V26 Elite Production Platform',
  settings:{
    platformName:'Tager',
    supportPhone:'+20 10 24237231',
    supportPhone2:'+20 10 16135495',
    supportWhatsapp:'+20 112 751 2512',
    supportEmail:'support@tager.local',
    currency:'EGP',
    defaultCommissionPercent:10,
    premiumCartPercent:1.5,
    requireDeliveryCoverage:true,
    requireProductApproval:true,
    requireVendorApproval:true,
    paymentMethods:['نقدي عند الاستلام','تحويل بنكي','محفظة إلكترونية','Instapay'],
    platformStatus:'جاهزة للإطلاق',
    operationsSlaHours:24,
    categories:['أغذية','مشروبات','ألبان','منظفات','ورقيات','معلبات','مواد خام','تعبئة وتغليف']
  },
  users:[], vendors:[], vendor_documents:[], vendor_delivery_zones:[], products:[],
  customer_addresses:[], orders:[], order_items:[], shipments:[], commission_payments:[], support_tickets:[], audit_logs:[]
});

let state = loadState();
let cart = loadCart();

function loadState(){
  try{
    const raw = localStorage.getItem(STORE_KEY);
    if(!raw) return DEFAULT_STATE();
    const data = JSON.parse(raw);
    const base = DEFAULT_STATE();
    const merged = {...base, ...data, settings:{...base.settings, ...(data.settings||{})}};
    Object.keys(base).forEach(k=>{ if(Array.isArray(base[k]) && !Array.isArray(merged[k])) merged[k]=[]; });
    return merged;
  }catch(e){ console.error(e); return DEFAULT_STATE(); }
}
function saveState(){ localStorage.setItem(STORE_KEY, JSON.stringify(state)); }
function loadCart(){ try{return JSON.parse(localStorage.getItem(CART_KEY)||'[]')}catch(e){return []} }
function saveCart(){ localStorage.setItem(CART_KEY, JSON.stringify(cart)); updateShell(); }
function id(){ return (typeof crypto !== 'undefined' && crypto.randomUUID) ? crypto.randomUUID() : 'id_' + Date.now().toString(36) + Math.random().toString(36).slice(2); }
function now(){ return new Date().toISOString(); }
function esc(v){ return String(v ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch])); }
function money(n){ return `${Number(n||0).toLocaleString('ar-EG',{maximumFractionDigits:2})} ج.م`; }
function num(n){ return Number(n||0); }
function app(){ return document.getElementById('app'); }
function qs(s,root=document){ return root.querySelector(s); }
function qsa(s,root=document){ return Array.from(root.querySelectorAll(s)); }
function session(){ try{return JSON.parse(localStorage.getItem(SESSION_KEY)||'null')}catch(e){return null} }
function setSession(user){ localStorage.setItem(SESSION_KEY, JSON.stringify({id:user.id, role:user.role, name:user.name})); updateShell(); }
function clearSession(){ localStorage.removeItem(SESSION_KEY); updateShell(); }
function me(){ const s=session(); return s ? state.users.find(u=>u.id===s.id) : null; }
function myRole(){ const u=me(); return u ? u.role : ''; }
function isAdmin(){ return myRole()==='admin' || myRole()==='staff'; }
function isVendor(){ return myRole()==='vendor'; }
function isCustomer(){ return myRole()==='customer'; }
function admins(){ return state.users.filter(u=>u.role==='admin' || u.role==='staff'); }
function go(path){ history.pushState(null,'',path); route(); window.scrollTo({top:0,behavior:'smooth'}); }
function log(action, entity_type='', entity_id='', after_data=null){ state.audit_logs.unshift({id:id(), actor_id:me()?.id||null, action, entity_type, entity_id, after_data, created_at:now()}); state.audit_logs = state.audit_logs.slice(0,500); saveState(); }

function govOptions(selected=''){
  return Object.keys(EGYPT).map(g=>`<option value="${esc(g)}" ${g===selected?'selected':''}>${esc(g)}</option>`).join('');
}
function distOptions(g, selected=''){
  const list = EGYPT[g] || [];
  return list.map(d=>`<option value="${esc(d)}" ${d===selected?'selected':''}>${esc(d)}</option>`).join('');
}
function bindGov(form){
  const g = qs('[name="governorate"]', form);
  const d = qs('[name="district"]', form);
  if(!g || !d) return;
  const sync = () => { const current = d.dataset.selected || d.value; d.innerHTML = distOptions(g.value, current); d.dataset.selected=''; };
  sync();
  g.addEventListener('change', sync);
}
function input(name,label,type='text',value='',attrs=''){
  return `<div class="field"><label>${esc(label)}</label><input name="${esc(name)}" type="${esc(type)}" value="${esc(value)}" ${attrs}></div>`;
}
function textarea(name,label,value='',attrs=''){
  return `<div class="field"><label>${esc(label)}</label><textarea name="${esc(name)}" ${attrs}>${esc(value)}</textarea></div>`;
}
function select(name,label,options,value=''){
  return `<div class="field"><label>${esc(label)}</label><select name="${esc(name)}">${options.map(o=>`<option value="${esc(o)}" ${o===value?'selected':''}>${esc(o)}</option>`).join('')}</select></div>`;
}
function notice(text,type=''){
  return `<div class="notice ${type}">${text}</div>`;
}
function page(title, body, subtitle=''){
  app().innerHTML = `<section class="page"><div class="section-head"><div><h1 class="page-title">${esc(title)}</h1>${subtitle?`<p>${esc(subtitle)}</p>`:''}</div></div>${body}</section>`;
}
function table(headers, rows){
  if(!rows.length) return `<div class="empty">لا توجد بيانات حالياً.</div>`;
  return `<div class="table-wrap"><table class="table"><thead><tr>${headers.map(h=>`<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`;
}
function badge(status){
  const map = {pending:'قيد المراجعة',approved:'معتمد',rejected:'مرفوض',suspended:'موقوف',new:'جديد',confirmed:'مؤكد',preparing:'قيد التجهيز',shipped:'تم الشحن',delivered:'تم التسليم',cancelled:'ملغي',paid:'مسدد',review:'تحت المراجعة',open:'مفتوح',closed:'مغلق'};
  return `<span class="badge ${esc(status)}">${esc(map[status]||status||'غير محدد')}</span>`;
}
function metric(title,value,sub=''){
  return `<div class="metric"><span>${esc(title)}</span><strong>${esc(value)}</strong>${sub?`<small class="muted">${esc(sub)}</small>`:''}</div>`;
}
function getVendor(userId){ return state.vendors.find(v=>v.user_id===userId); }
function getUser(userId){ return state.users.find(u=>u.id===userId); }
function approvedProducts(){ return state.products.filter(p=>p.status==='approved' && num(p.stock)>0 && getUser(p.vendor_id)?.status==='approved'); }
function approvedVendors(){ return state.vendors.filter(v=>getUser(v.user_id)?.status==='approved'); }
function productPrice(p, type='wholesale_price'){ return num(p[type] || p.wholesale_price || p.retail_price); }
function productImage(p){ return p.image_url ? `<img class="product-image" src="${esc(p.image_url)}" alt="${esc(p.name_ar)}" onerror="this.outerHTML='<div class=&quot;product-placeholder&quot;>📦</div>'">` : `<div class="product-placeholder">📦</div>`; }
function publicVendorCard(v){
  const u = getUser(v.user_id) || {};
  const zones = state.vendor_delivery_zones.filter(z=>z.vendor_id===v.user_id && z.is_active!==false);
  const products = state.products.filter(p=>p.vendor_id===v.user_id && p.status==='approved');
  return `<div class="card"><div class="supplier-header"><div class="supplier-avatar">${v.logo_url?`<img src="${esc(v.logo_url)}" alt="${esc(v.store_name)}">`:'🏬'}</div><div><h3>${esc(v.store_name||u.name)}</h3><p class="muted">${esc(v.description||'مورد معتمد داخل منصة Tager')}</p></div></div><p><b>المحافظة:</b> ${esc(v.governorate||u.governorate||'-')} — <b>المركز:</b> ${esc(v.district||u.district||'-')}</p><p><b>أقل طلب:</b> ${money(v.min_order)}</p><p><b>مناطق التغطية:</b> ${zones.length} منطقة — <b>منتجات منشورة:</b> ${products.length}</p><button class="btn3" onclick="go('/vendor-page/${esc(v.user_id)}')">عرض صفحة المورد</button></div>`;
}

function updateShell(){
  const c = qs('#cartCount'); if(c) c.textContent = String(cart.reduce((s,i)=>s+num(i.qty),0));
  const a = qs('#accountLink');
  if(a){
    const u = me();
    if(u){ a.textContent = u.role==='admin'||u.role==='staff' ? 'الإدارة' : u.role==='vendor' ? 'لوحة المورد' : 'حسابي'; a.setAttribute('href', u.role==='admin'||u.role==='staff' ? '/admin' : u.role==='vendor' ? '/vendor' : '/customer'); }
    else if(!admins().length){ a.textContent = 'إنشاء الإدارة'; a.setAttribute('href','/setup'); }
    else { a.textContent='دخول'; a.setAttribute('href','/login'); }
  }
  qsa('.nav-links a').forEach(link=>{
    const href = link.getAttribute('href');
    link.classList.toggle('active', href==='/' ? location.pathname==='/' : location.pathname.startsWith(href));
  });
}

function home(){
  const totalVendors = approvedVendors().length;
  const totalProducts = approvedProducts().length;
  const delivered = state.orders.filter(o=>o.status==='delivered').length;
  const openOrders = state.orders.filter(o=>!['delivered','cancelled'].includes(o.status)).length;
  const sales = state.orders.reduce((sum,o)=>sum+num(o.total),0);
  const tickets = state.support_tickets.filter(t=>t.status==='open').length;
  app().innerHTML = `
  <section class="page hero">
    <div class="hero-card">
      <img class="hero-logo" src="/assets/tager-logo-full.png" alt="Tager">
      <span class="eyebrow">تصميم إنتاجي راقٍ — بدون بيانات تجريبية</span>
      <h1>منصة تجارة وتوريد متكاملة من التسجيل حتى إقفال الحسابات</h1>
      <p>تم تصميم Tager كمنصة تشغيل كاملة: موردين، منتجات، فئات، مناطق تغطية، سلة، طلبات، دعم، لوحة إدارة، لوحة مورد، حساب عميل، وتقارير مالية بدون عناصر ناقصة أو صفحات فارغة غير مفهومة.</p>
      <div class="hero-actions">
        <button class="btn2" onclick="go('/setup')">بدء تشغيل الإدارة</button>
        <button class="btn3" onclick="go('/products')">استعراض السوق</button>
        <button class="btn3" onclick="go('/register/vendor')">انضم كمورد</button>
      </div>
    </div>
    <aside class="ops-card">
      <div class="ops-head"><strong>مركز التشغيل</strong><span class="status-pill">${esc(state.settings.platformStatus||'جاهزة')}</span></div>
      <div class="metric-grid">
        ${metric('موردون معتمدون', totalVendors)}
        ${metric('منتجات منشورة', totalProducts)}
        ${metric('طلبات مفتوحة', openOrders)}
        ${metric('قيمة الطلبات', money(sales))}
      </div>
      <div class="steps">
        <div class="step"><b>1</b><div><strong>إنشاء الإدارة</strong><p>أول تشغيل من صفحة الإعداد، بدون أي حسابات جاهزة داخل النسخة.</p></div></div>
        <div class="step"><b>2</b><div><strong>اعتماد المورد والمنتج</strong><p>لا يظهر أي مورد أو منتج للعامة قبل مراجعة الإدارة.</p></div></div>
        <div class="step"><b>3</b><div><strong>تشغيل ومالية</strong><p>الإعدادات المالية داخل الإدارة فقط، مع أرصدة ومدفوعات وتصدير.</p></div></div>
      </div>
    </aside>
  </section>
  <section class="page compact">
    <div class="grid-4">
      ${metric('طلبات مسلمة', delivered)}
      ${metric('تذاكر مفتوحة', tickets)}
      ${metric('فئات مفعلة', state.settings.categories.length)}
      ${metric('طرق دفع', state.settings.paymentMethods.length)}
    </div>
  </section>
  <section class="page compact">
    <div class="section-head"><div><h2>منصة كاملة صفحة بصفحة</h2><p>كل قسم له وظيفة واضحة داخل دورة العمل.</p></div></div>
    <div class="grid-3">
      <div class="card lift"><div class="icon">🏬</div><h3>إدارة الموردين</h3><p class="muted">تسجيل مورد، اعتماد، صفحة عامة، مناطق تغطية، بيانات بنكية، حالة الحساب، ومتابعة المنتجات.</p></div>
      <div class="card lift"><div class="icon">📦</div><h3>سوق المنتجات</h3><p class="muted">فئات، أسعار قطاعي وجملة وجملة الجملة، مخزون، صور، تفاصيل منتج، وربط بالمورد.</p></div>
      <div class="card lift"><div class="icon">🛒</div><h3>السلة والطلب</h3><p class="muted">تحقق من المخزون والتغطية قبل إرسال الطلب، مع فصل الشحن حسب المورد.</p></div>
      <div class="card lift"><div class="icon">💳</div><h3>المالية</h3><p class="muted">مستحقات المنصة، صافي المورد، الدفعات، اعتماد السداد، وتصدير الأرصدة CSV.</p></div>
      <div class="card lift"><div class="icon">🎧</div><h3>الدعم</h3><p class="muted">تذاكر دعم تظهر داخل الإدارة، مع أرقام التواصل والواتساب.</p></div>
      <div class="card lift"><div class="icon">🧭</div><h3>جاهزية الإطلاق</h3><p class="muted">قائمة تشغيل واضحة، إعدادات، سياسات، وحفظ نسخة احتياطية من البيانات.</p></div>
    </div>
  </section>
  <section class="page compact">
    <div class="section-head"><div><h2>اختصارات التشغيل</h2><p>الوصول السريع للصفحات الأساسية.</p></div></div>
    <div class="grid">
      <a class="card lift" href="/products"><h3>المنتجات</h3><p class="muted">بحث، فئات، أسعار، وإضافة للسلة.</p></a>
      <a class="card lift" href="/categories"><h3>الفئات</h3><p class="muted">عرض فئات المنصة وحالة كل فئة.</p></a>
      <a class="card lift" href="/vendors"><h3>الموردون</h3><p class="muted">موردون معتمدون وصفحات عامة فقط.</p></a>
      <a class="card lift" href="/admin"><h3>لوحة الإدارة</h3><p class="muted">تشغيل، اعتماد، مالية، إعدادات، بيانات.</p></a>
    </div>
  </section>`;
}

function setup(){
  if(admins().length && !isAdmin()) return go('/login');
  page('إعداد المنصة', `<div class="grid-2"><div class="card form"><h3>إنشاء أول حساب إدارة</h3>${admins().length?notice('يوجد حساب إدارة بالفعل. يمكن تعديل الإعدادات من لوحة الإدارة.','warn'):`<form id="setupForm" class="form">${input('name','اسم المدير','','','required')}${input('phone','رقم الهاتف','tel','','required')}${input('email','البريد الإلكتروني','email','','')}${input('password','كلمة المرور','password','','required minlength="6"')}<button class="btn">إنشاء الإدارة</button></form><div id="out"></div>`}</div><div class="card"><h3>خطوات التشغيل الصحيحة</h3><div class="steps"><div class="step"><b>1</b><div><strong>إنشاء المدير</strong><p>لا توجد حسابات جاهزة داخل النسخة.</p></div></div><div class="step"><b>2</b><div><strong>فتح تسجيل الموردين</strong><p>المورد يسجل ثم الإدارة تعتمد الحساب والمنتجات.</p></div></div><div class="step"><b>3</b><div><strong>تشغيل الطلبات</strong><p>الطلب لا يتم إلا إذا كانت منطقة التوصيل مغطاة.</p></div></div></div></div></div>`);
  const form = qs('#setupForm');
  if(form) form.addEventListener('submit', e=>{
    e.preventDefault();
    const f = Object.fromEntries(new FormData(form));
    if(state.users.some(u=>u.phone===f.phone)) return qs('#out').innerHTML = notice('رقم الهاتف مستخدم بالفعل.','error');
    const u = {id:id(), role:'admin', status:'approved', name:f.name.trim(), phone:f.phone.trim(), email:f.email.trim(), password:f.password, permissions:{all:true}, created_at:now()};
    state.users.push(u); saveState(); setSession(u); log('create_admin','users',u.id,{name:u.name}); go('/admin');
  });
}

function login(){
  page('تسجيل الدخول', `<div class="grid-2"><form class="card form" id="loginForm"><h3>الدخول إلى حسابك</h3>${input('phone','رقم الهاتف','tel','','required')}${input('password','كلمة المرور','password','','required')}<button class="btn">دخول</button><div id="out"></div></form><div class="card"><h3>ليس لديك حساب؟</h3><p class="muted">اختر نوع الحساب المطلوب. حساب المورد يحتاج اعتماد الإدارة قبل الظهور في السوق.</p><div class="action-row"><a class="btn3" href="/register/customer">تسجيل عميل</a><a class="btn2" href="/register/vendor">تسجيل مورد</a>${admins().length?'':'<a class="btn" href="/setup">إنشاء الإدارة</a>'}</div></div></div>`);
  qs('#loginForm').addEventListener('submit', e=>{
    e.preventDefault(); const f=Object.fromEntries(new FormData(e.target));
    const u = state.users.find(x=>x.phone===f.phone.trim() && x.password===f.password);
    if(!u) return qs('#out').innerHTML = notice('رقم الهاتف أو كلمة المرور غير صحيحة.','error');
    if(u.status!=='approved') return qs('#out').innerHTML = notice('الحساب غير معتمد أو موقوف. تواصل مع الإدارة.','error');
    setSession(u); log('login','users',u.id); go(u.role==='admin'||u.role==='staff'?'/admin':u.role==='vendor'?'/vendor':'/customer');
  });
}

function register(type){
  const vendor = type==='vendor';
  const title = vendor ? 'تسجيل مورد جديد' : 'تسجيل عميل جديد';
  page(title, `<form class="card form" id="regForm"><div class="row">${input('name',vendor?'اسم المسؤول':'اسم العميل','text','','required')}${vendor?input('store_name','اسم الشركة / المتجر','text','','required'):''}${input('phone','رقم الهاتف','tel','','required')}${input('email','البريد الإلكتروني','email','','')}</div><div class="row">${input('password','كلمة المرور','password','','required minlength="6"')}<div class="field"><label>المحافظة</label><select name="governorate">${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district"></select></div>${input('area','القسم / الحي','text','','')}</div>${input('address','العنوان التفصيلي','text','','')}${vendor?`<div class="row">${input('commercial_register','السجل التجاري','text','','')}${input('tax_number','الرقم الضريبي','text','','')}${input('min_order','أقل قيمة طلب','number','0','min="0"')}</div>${textarea('description','وصف المورد / النشاط','','')}`:''}<button class="btn">${vendor?'إرسال طلب الانضمام':'إنشاء حساب العميل'}</button><div id="out"></div></form>`, vendor?'بعد التسجيل سيظهر الطلب داخل لوحة الإدارة للاعتماد.':'يمكن للعميل تسجيل الدخول مباشرة بعد إنشاء الحساب.');
  bindGov(qs('#regForm'));
  qs('#regForm').addEventListener('submit', e=>{
    e.preventDefault(); const f=Object.fromEntries(new FormData(e.target));
    if(state.users.some(u=>u.phone===f.phone.trim())) return qs('#out').innerHTML=notice('رقم الهاتف مسجل بالفعل.','error');
    const u = {id:id(), role:vendor?'vendor':'customer', status:vendor?'pending':'approved', name:f.name.trim(), phone:f.phone.trim(), email:f.email.trim(), password:f.password, governorate:f.governorate, district:f.district, area:f.area.trim(), address:f.address.trim(), created_at:now()};
    state.users.push(u);
    if(vendor){
      state.vendors.push({user_id:u.id, store_name:f.store_name.trim(), commercial_register:f.commercial_register.trim(), tax_number:f.tax_number.trim(), description:f.description.trim(), min_order:num(f.min_order), commission_percent:num(state.settings.defaultCommissionPercent), logo_url:'', cover_url:'', governorate:f.governorate, district:f.district, area:f.area.trim(), bank_name:'', iban:'', wallet_number:'', created_at:now()});
      log('vendor_register','vendors',u.id,{store_name:f.store_name});
      qs('#out').innerHTML = notice('تم إرسال طلب المورد للإدارة. لن يظهر المورد للعامة قبل الاعتماد.','ok');
    } else {
      state.customer_addresses.push({id:id(), customer_id:u.id, label:'العنوان الرئيسي', governorate:f.governorate, district:f.district, area:f.area.trim()||'كل المناطق', address:f.address.trim(), is_default:true, created_at:now()});
      log('customer_register','users',u.id,{name:u.name});
      qs('#out').innerHTML = notice('تم إنشاء حساب العميل. يمكنك تسجيل الدخول الآن.','ok');
    }
    saveState(); e.target.reset(); bindGov(e.target);
  });
}

function productsPage(){
  const url = new URL(location.href);
  const query = url.searchParams.get('q') || '';
  const selectedCategory = url.searchParams.get('category') || 'كل الفئات';
  const priceType = url.searchParams.get('price') || 'wholesale_price';
  const products = approvedProducts();
  const categories = ['كل الفئات', ...new Set([...state.settings.categories, ...products.map(p=>p.category).filter(Boolean)])];
  page('المنتجات', `<div class="card form"><div class="row"><div class="field"><label>بحث</label><input id="searchBox" value="${esc(query)}" placeholder="اسم المنتج أو المورد أو الفئة"></div><div class="field"><label>الفئة</label><select id="catFilter">${categories.map(c=>`<option value="${esc(c)}" ${c===selectedCategory?'selected':''}>${esc(c)}</option>`).join('')}</select></div><div class="field"><label>نوع السعر</label><select id="priceType"><option value="retail_price" ${priceType==='retail_price'?'selected':''}>قطاعي</option><option value="wholesale_price" ${priceType==='wholesale_price'?'selected':''}>جملة</option><option value="super_wholesale_price" ${priceType==='super_wholesale_price'?'selected':''}>جملة الجملة</option></select></div></div></div><div id="productsGrid" class="grid"></div>`, 'المنتجات لا تظهر هنا إلا بعد اعتماد الإدارة واعتماد المورد.');
  const render = () => {
    const q = qs('#searchBox').value.trim().toLowerCase();
    const cat = qs('#catFilter').value;
    const pt = qs('#priceType').value;
    let list = products.filter(p=>{
      const v=getVendor(p.vendor_id); const u=getUser(p.vendor_id);
      const hay = `${p.name_ar} ${p.category||''} ${p.brand||''} ${v?.store_name||''} ${u?.name||''}`.toLowerCase();
      return (!q || hay.includes(q)) && (cat==='كل الفئات' || p.category===cat);
    });
    qs('#productsGrid').innerHTML = list.length ? list.map(p=>{
      const v=getVendor(p.vendor_id)||{};
      return `<div class="card product-card">${productImage(p)}<div class="product-body"><h3>${esc(p.name_ar)}</h3><p class="muted">${esc(p.category||'بدون فئة')} — ${esc(v.store_name||'مورد')}</p><div class="price">${money(productPrice(p,pt))}</div><p class="small"><b>المخزون:</b> ${esc(p.stock)} | <b>الوحدة:</b> ${esc(p.unit||'قطعة')}</p><div class="action-row"><button class="btn" onclick="addToCart('${esc(p.id)}','${esc(pt)}')">إضافة للسلة</button><button class="btn3" onclick="go('/product/${esc(p.id)}')">التفاصيل</button><button class="btn3" onclick="go('/vendor-page/${esc(p.vendor_id)}')">المورد</button></div></div></div>`;
    }).join('') : `<div class="empty">لا توجد منتجات منشورة حالياً.<br>بعد تسجيل المورد واعتماد المنتجات ستظهر هنا مباشرة.<div style="margin-top:14px"><a class="btn2" href="/register/vendor">تسجيل مورد</a></div></div>`;
  };
  ['searchBox','catFilter','priceType'].forEach(id=>qs('#'+id).addEventListener('input',render));
  ['catFilter','priceType'].forEach(id=>qs('#'+id).addEventListener('change',render));
  render();
}

function categoriesPage(){
  const products = approvedProducts();
  const cards = state.settings.categories.map(cat=>{
    const count = products.filter(p=>p.category===cat).length;
    const vendors = new Set(products.filter(p=>p.category===cat).map(p=>p.vendor_id)).size;
    return `<a class="card lift" href="/products?category=${encodeURIComponent(cat)}"><div class="icon">${count? '📦':'◌'}</div><h3>${esc(cat)}</h3><p class="muted">منتجات منشورة: ${count} — موردون: ${vendors}</p></a>`;
  });
  page('الفئات', `<div class="grid">${cards.join('') || '<div class="empty">لم يتم إضافة فئات بعد. يمكن للإدارة إضافة الفئات من الإعدادات.</div>'}</div>`, 'الفئات يتم التحكم فيها من لوحة الإدارة، والمنتجات تظهر بعد الاعتماد فقط.');
}

function productDetailPage(productId){
  const p = state.products.find(x=>x.id===productId);
  if(!p || p.status!=='approved' || getUser(p.vendor_id)?.status!=='approved') return page('تفاصيل المنتج', '<div class="empty">المنتج غير متاح حالياً.</div>');
  const v=getVendor(p.vendor_id)||{};
  const zones=state.vendor_delivery_zones.filter(z=>z.vendor_id===p.vendor_id && z.is_active!==false);
  page(p.name_ar, `<div class="grid-2"><div class="card">${productImage(p)}<div class="divider"></div><h2>${esc(p.name_ar)}</h2><p class="muted">${esc(p.description_ar||'لا يوجد وصف مضاف لهذا المنتج بعد.')}</p></div><div class="card"><span class="chip">${esc(p.category||'بدون فئة')}</span><h3>الأسعار والمخزون</h3><div class="mini-list"><span>قطاعي <b>${money(p.retail_price)}</b></span><span>جملة <b>${money(p.wholesale_price)}</b></span><span>جملة الجملة <b>${money(p.super_wholesale_price)}</b></span><span>المخزون <b>${esc(p.stock)} ${esc(p.unit||'قطعة')}</b></span></div><div class="divider"></div><h3>المورد</h3><p class="muted">${esc(v.store_name||'مورد معتمد')}</p><div class="action-row"><button class="btn" onclick="addToCart('${esc(p.id)}','wholesale_price')">إضافة بسعر الجملة</button><button class="btn3" onclick="go('/vendor-page/${esc(p.vendor_id)}')">صفحة المورد</button></div></div></div><div class="section-head"><div><h2>مناطق التغطية</h2><p>الطلب يتم فقط إذا كان عنوان العميل داخل مناطق تغطية المورد.</p></div></div>${zones.length?table(['المحافظة','المركز','القسم','رسوم','مدة'], zones.map(z=>`<tr><td>${esc(z.governorate)}</td><td>${esc(z.district)}</td><td>${esc(z.area)}</td><td>${money(z.delivery_fee)}</td><td>${esc(z.eta_days||2)} يوم</td></tr>`)):'<div class="empty">لم يحدد المورد مناطق التغطية بعد.</div>'}`);
}

function calcShippingFee(addr){
  const vendorIds=[...new Set(cart.map(i=>i.vendor_id))];
  return vendorIds.reduce((sum,vendorId)=>{
    const zones=state.vendor_delivery_zones.filter(z=>z.vendor_id===vendorId && z.is_active!==false);
    const zone=zones.find(z=>z.governorate===addr.governorate && z.district===addr.district && (!z.area || z.area==='كل المناطق' || z.area===addr.area));
    return sum + num(zone?.delivery_fee||0);
  },0);
}

function addToCart(productId, priceType='wholesale_price'){
  const p = state.products.find(x=>x.id===productId);
  if(!p || p.status!=='approved') return alert('المنتج غير متاح حالياً.');
  const price = productPrice(p, priceType);
  const ex = cart.find(i=>i.product_id===productId && i.price_type===priceType);
  if(ex) ex.qty += 1;
  else cart.push({product_id:productId, vendor_id:p.vendor_id, name:p.name_ar, price_type:priceType, unit_price:price, qty:1});
  saveCart(); alert('تمت الإضافة للسلة.');
}
function removeCart(k){ cart.splice(k,1); saveCart(); cartPage(); }
function setCartQty(k,val){ cart[k].qty = Math.max(1, num(val)); saveCart(); cartPage(); }

function cartPage(){
  const rows = cart.map((i,k)=>{
    const p = state.products.find(x=>x.id===i.product_id);
    const available = p && p.status==='approved' && num(p.stock)>=num(i.qty);
    return `<tr><td>${esc(i.name)}</td><td><input type="number" min="1" value="${esc(i.qty)}" onchange="setCartQty(${k},this.value)" style="width:92px"></td><td>${money(i.unit_price)}</td><td>${money(i.unit_price*i.qty)}</td><td>${available?badge('approved'):badge('rejected')}</td><td><button class="btn-danger" onclick="removeCart(${k})">حذف</button></td></tr>`;
  });
  page('السلة', `<div class="card"><h3>محتويات السلة</h3>${cart.length?table(['الصنف','الكمية','السعر','الإجمالي','الحالة','إجراء'], rows):'<div class="empty">السلة فارغة.</div>'}<h2>الإجمالي: ${money(cart.reduce((s,i)=>s+i.unit_price*i.qty,0))}</h2><div class="action-row"><a class="btn3" href="/products">استكمال التسوق</a>${cart.length?'<a class="btn" href="/checkout">إتمام الطلب</a>':''}</div></div>`);
}

function checkoutPage(){
  const u = me(); if(!u) return go('/login');
  if(!cart.length) return go('/cart');
  const addr = state.customer_addresses.find(a=>a.customer_id===u.id && a.is_default) || {};
  page('إتمام الطلب', `<form class="card form" id="checkoutForm"><h3>بيانات التوصيل والدفع</h3><div class="row"><div class="field"><label>المحافظة</label><select name="governorate">${govOptions(addr.governorate||u.governorate)}</select></div><div class="field"><label>المركز</label><select name="district" data-selected="${esc(addr.district||u.district||'')}"></select></div>${input('area','القسم / الحي','text',addr.area||u.area||'','')}${input('address','العنوان التفصيلي','text',addr.address||u.address||'','required')}</div><div class="row">${select('payment_method','طريقة الدفع',state.settings.paymentMethods,state.settings.paymentMethods[0])}${select('cart_type','نوع السلة',['سلة عادية','سلة مميزة'],'سلة عادية')}</div><button class="btn">تأكيد الطلب</button><div id="out"></div></form><div class="card"><h3>ملخص الطلب</h3>${table(['الصنف','الكمية','الإجمالي'], cart.map(i=>`<tr><td>${esc(i.name)}</td><td>${esc(i.qty)}</td><td>${money(i.qty*i.unit_price)}</td></tr>`))}</div>`);
  bindGov(qs('#checkoutForm'));
  qs('#checkoutForm').addEventListener('submit', e=>{
    e.preventDefault(); const f=Object.fromEntries(new FormData(e.target));
    const result = validateCartCoverage(f);
    if(!result.ok) return qs('#out').innerHTML = notice(result.message,'error');
    const subtotal = cart.reduce((s,i)=>s+i.unit_price*i.qty,0);
    const premiumFee = f.cart_type==='سلة مميزة' ? subtotal*num(state.settings.premiumCartPercent)/100 : 0;
    const shippingFee = calcShippingFee(f);
    const commissionTotal = cart.reduce((s,i)=>{
      const v=getVendor(i.vendor_id)||{}; return s + (i.unit_price*i.qty*num(v.commission_percent||state.settings.defaultCommissionPercent)/100);
    },0);
    const order = {id:id(), customer_id:u.id, cart_type:f.cart_type, governorate:f.governorate, district:f.district, area:f.area, address:f.address, payment_method:f.payment_method, payment_status:'pending', subtotal, premium_fee:premiumFee, shipping_fee:shippingFee, total:subtotal+premiumFee+shippingFee, platform_commission:commissionTotal+premiumFee, vendor_net:subtotal-commissionTotal, status:'new', delivery_status:'pending', created_at:now()};
    state.orders.unshift(order);
    cart.forEach(i=>{
      const p=state.products.find(x=>x.id===i.product_id); if(p) p.stock = Math.max(0, num(p.stock)-num(i.qty));
      const v=getVendor(i.vendor_id)||{}; const sub=i.unit_price*i.qty; const pct=num(v.commission_percent||state.settings.defaultCommissionPercent); const comm=sub*pct/100;
      state.order_items.push({id:id(), order_id:order.id, product_id:i.product_id, vendor_id:i.vendor_id, qty:i.qty, unit_price:i.unit_price, subtotal:sub, commission_percent:pct, commission_amount:comm, vendor_net:sub-comm, created_at:now()});
    });
    [...new Set(cart.map(i=>i.vendor_id))].forEach(vendor_id=>state.shipments.push({id:id(), order_id:order.id, vendor_id, status:'pending', governorate:f.governorate, district:f.district, area:f.area, created_at:now()}));
    state.customer_addresses.push({id:id(), customer_id:u.id, label:'عنوان طلب', governorate:f.governorate, district:f.district, area:f.area||'كل المناطق', address:f.address, is_default:false, created_at:now()});
    cart=[]; saveCart(); saveState(); log('create_order','orders',order.id,{total:order.total}); qs('#out').innerHTML = notice('تم إنشاء الطلب بنجاح. يمكنك متابعته من حساب العميل.','ok'); setTimeout(()=>go('/customer'),900);
  });
}
function validateCartCoverage(addr){
  for(const i of cart){
    const p = state.products.find(x=>x.id===i.product_id);
    if(!p || p.status!=='approved') return {ok:false, message:`المنتج ${i.name} غير متاح.`};
    if(num(p.stock)<num(i.qty)) return {ok:false, message:`المخزون غير كافٍ للمنتج ${i.name}.`};
    const vu = getUser(i.vendor_id);
    if(!vu || vu.status!=='approved') return {ok:false, message:`مورد المنتج ${i.name} غير معتمد حالياً.`};
    if(state.settings.requireDeliveryCoverage){
      const zones = state.vendor_delivery_zones.filter(z=>z.vendor_id===i.vendor_id && z.is_active!==false);
      const covered = zones.some(z=>z.governorate===addr.governorate && z.district===addr.district && (!z.area || z.area==='كل المناطق' || z.area===addr.area));
      if(!covered) return {ok:false, message:`لا يمكن إتمام الطلب: مورد ${i.name} لا يغطي العنوان المختار.`};
    }
  }
  return {ok:true};
}

function vendorsPage(){
  const list = approvedVendors();
  page('الموردون', list.length?`<div class="grid">${list.map(publicVendorCard).join('')}</div>`:`<div class="empty">لا يوجد موردون معتمدون حالياً.<br>بعد تسجيل المورد واعتماده من الإدارة سيظهر هنا بصفحته ومناطق تغطيته.</div><div class="card"><h3>إضافة مورد جديد</h3><p class="muted">المورد يسجل بياناته، يضيف مناطق التوصيل والمنتجات، ثم تقوم الإدارة بالمراجعة والاعتماد.</p><a class="btn2" href="/register/vendor">تسجيل مورد</a></div>`, 'قائمة الموردين المعتمدين فقط. لا تظهر أي إعدادات مالية داخل الصفحة العامة.');
}
function vendorPublicPage(vendorId){
  const v = getVendor(vendorId); const u = getUser(vendorId);
  if(!v || !u || u.status!=='approved') return page('صفحة المورد', '<div class="empty">المورد غير متاح حالياً.</div>');
  const products = state.products.filter(p=>p.vendor_id===vendorId && p.status==='approved');
  const zones = state.vendor_delivery_zones.filter(z=>z.vendor_id===vendorId && z.is_active!==false);
  page(v.store_name, `<div class="card supplier-header"><div class="supplier-avatar">${v.logo_url?`<img src="${esc(v.logo_url)}" alt="${esc(v.store_name)}">`:'🏬'}</div><div><h2>${esc(v.store_name)}</h2><p class="muted">${esc(v.description||'مورد معتمد داخل Tager')}</p><p><b>أقل طلب:</b> ${money(v.min_order)} — <b>المحافظة:</b> ${esc(v.governorate||u.governorate||'-')}</p></div></div><div class="section-head"><div><h2>مناطق التغطية</h2><p>الطلب يتم فقط داخل المناطق المعتمدة من المورد.</p></div></div>${zones.length?table(['المحافظة','المركز','القسم','رسوم التوصيل','مدة التوصيل'], zones.map(z=>`<tr><td>${esc(z.governorate)}</td><td>${esc(z.district)}</td><td>${esc(z.area)}</td><td>${money(z.delivery_fee)}</td><td>${esc(z.eta_days||2)} يوم</td></tr>`)):'<div class="empty">لم يتم إضافة مناطق تغطية بعد.</div>'}<div class="section-head"><div><h2>منتجات المورد</h2><p>منتجات معتمدة ومنشورة.</p></div></div><div class="grid">${products.length?products.map(p=>`<div class="card product-card">${productImage(p)}<div class="product-body"><h3>${esc(p.name_ar)}</h3><p class="muted">${esc(p.category||'')}</p><div class="price">${money(productPrice(p))}</div><button class="btn" onclick="addToCart('${esc(p.id)}','wholesale_price')">إضافة للسلة</button></div></div>`).join(''):'<div class="empty">لا توجد منتجات منشورة لهذا المورد.</div>'}</div>`);
}

function customerDashboard(){
  const u=me(); if(!u || !isCustomer()) return go('/login');
  const orders = state.orders.filter(o=>o.customer_id===u.id);
  const rows = orders.map(o=>`<tr><td>${esc(o.id.slice(0,8))}</td><td>${badge(o.status)}</td><td>${esc(o.governorate)} - ${esc(o.district)}</td><td>${money(o.total)}</td><td>${new Date(o.created_at).toLocaleDateString('ar-EG')}</td></tr>`);
  page('حساب العميل', `<div class="grid-3">${metric('طلباتي',orders.length)}${metric('إجمالي المشتريات',money(orders.reduce((s,o)=>s+num(o.total),0)))}${metric('الطلبات المفتوحة',orders.filter(o=>!['delivered','cancelled'].includes(o.status)).length)}</div><div class="tabs"><button class="tab active" onclick="showPanel('orders')">طلباتي</button><button class="tab" onclick="showPanel('profile')">بياناتي</button><button class="tab" onclick="logout()">خروج</button></div><div id="panel-orders" class="panel">${table(['رقم','الحالة','العنوان','الإجمالي','التاريخ'],rows)}</div><div id="panel-profile" class="panel hidden"><div class="card"><h3>${esc(u.name)}</h3><p><b>الهاتف:</b> ${esc(u.phone)}</p><p><b>العنوان:</b> ${esc(u.governorate||'')} - ${esc(u.district||'')} - ${esc(u.address||'')}</p></div></div>`);
}

function vendorDashboard(){
  const u=me(); if(!u || !isVendor()) return go('/login');
  const v=getVendor(u.id) || {};
  const products=state.products.filter(p=>p.vendor_id===u.id);
  const zones=state.vendor_delivery_zones.filter(z=>z.vendor_id===u.id);
  const items=state.order_items.filter(i=>i.vendor_id===u.id);
  const payments=state.commission_payments.filter(p=>p.vendor_id===u.id);
  const sales=items.reduce((s,i)=>s+num(i.subtotal),0); const commission=items.reduce((s,i)=>s+num(i.commission_amount),0); const paid=payments.filter(p=>p.status==='approved').reduce((s,p)=>s+num(p.amount),0); const pending=payments.filter(p=>p.status==='pending').reduce((s,p)=>s+num(p.amount),0);
  page('لوحة المورد', `${u.status==='approved'?'':notice('حساب المورد قيد المراجعة. يمكن تجهيز البيانات، ولكن لن تظهر المنتجات للعامة قبل اعتماد الإدارة.','warn')}<div class="grid-3">${metric('المبيعات',money(sales))}${metric('مستحقات المنصة',money(commission-paid))}${metric('منتجاتي',products.length)}</div><div class="tabs"><button class="tab active" onclick="showPanel('summary')">الملخص</button><button class="tab" onclick="showPanel('profile')">بيانات المورد</button><button class="tab" onclick="showPanel('zones')">مناطق التوصيل</button><button class="tab" onclick="showPanel('products')">المنتجات</button><button class="tab" onclick="showPanel('finance')">الحساب المالي</button><button class="tab" onclick="logout()">خروج</button></div><div id="panel-summary" class="panel"><div class="card"><h3>${esc(v.store_name||u.name)}</h3><p class="muted">${esc(v.description||'')}</p><p>${badge(u.status)}</p></div></div><div id="panel-profile" class="panel hidden">${vendorProfileForm(v,u)}</div><div id="panel-zones" class="panel hidden">${zoneForm()}${zonesTable(zones)}</div><div id="panel-products" class="panel hidden">${productForm()}${vendorProductsTable(products)}</div><div id="panel-finance" class="panel hidden"><div class="grid-3">${metric('إجمالي المبيعات',money(sales))}${metric('مستحقات المنصة',money(commission))}${metric('مدفوع ومعتمد',money(paid))}${metric('مدفوع قيد المراجعة',money(pending))}${metric('المتبقي',money(commission-paid))}${metric('صافي المورد',money(sales-commission))}</div>${paymentForm()}${paymentsTable(payments)}</div><div id="out"></div>`);
  bindAllDashboardForms();
}
function showPanel(name){
  qsa('.tab').forEach(b=>b.classList.remove('active'));
  const btn = [...qsa('.tab')].find(b=>b.getAttribute('onclick')?.includes(`'${name}'`)); if(btn) btn.classList.add('active');
  qsa('.panel').forEach(p=>p.classList.add('hidden'));
  const el=qs('#panel-'+name); if(el) el.classList.remove('hidden');
}
function vendorProfileForm(v,u){
  return `<form class="card form" id="vendorProfileForm"><h3>بيانات المورد</h3><div class="row">${input('store_name','اسم الشركة / المتجر','text',v.store_name||'','required')}${input('logo_url','رابط لوجو المورد','url',v.logo_url||'','')}${input('min_order','أقل طلب','number',v.min_order||0,'min="0"')}</div><div class="row"><div class="field"><label>المحافظة</label><select name="governorate">${govOptions(v.governorate||u.governorate)}</select></div><div class="field"><label>المركز</label><select name="district" data-selected="${esc(v.district||u.district||'')}"></select></div>${input('area','القسم / الحي','text',v.area||u.area||'','')}</div>${textarea('description','وصف المورد',v.description||'','')}<div class="row">${input('bank_name','البنك','text',v.bank_name||'','')}${input('iban','IBAN','text',v.iban||'','')}${input('wallet_number','رقم المحفظة','text',v.wallet_number||'','')}</div><button class="btn">حفظ البيانات</button></form>`;
}
function zoneForm(){ return `<form class="card form" id="zoneForm"><h3>إضافة منطقة توصيل</h3><div class="row"><div class="field"><label>المحافظة</label><select name="governorate">${govOptions()}</select></div><div class="field"><label>المركز</label><select name="district"></select></div>${input('area','القسم / الحي','text','كل المناطق','')}</div><div class="row">${input('delivery_fee','رسوم التوصيل','number','0','min="0"')}${input('eta_days','مدة التوصيل بالأيام','number','2','min="1"')}</div><button class="btn">حفظ منطقة التوصيل</button></form>`; }
function zonesTable(zones){
  return zones.length ? table(['المحافظة','المركز','القسم','رسوم','مدة','إجراء'], zones.map(z=>`<tr><td>${esc(z.governorate)}</td><td>${esc(z.district)}</td><td>${esc(z.area)}</td><td>${money(z.delivery_fee)}</td><td>${esc(z.eta_days)} يوم</td><td><button class="btn-danger" onclick="deleteZone('${esc(z.id)}')">حذف</button></td></tr>`)) : '<div class="empty">لم تضف مناطق توصيل بعد. أضف منطقة واحدة على الأقل حتى يتم قبول الطلبات.</div>';
}
function productForm(){
  const cats = state.settings.categories.length ? state.settings.categories : ['أغذية','مشروبات','منظفات'];
  return `<form class="card form" id="productForm"><h3>إضافة منتج</h3><div class="row">${input('name_ar','اسم المنتج','text','','required')}${select('category','الفئة',cats,cats[0])}${input('brand','العلامة التجارية','text','','')}${input('unit','الوحدة','text','قطعة','')}</div><div class="row">${input('retail_price','سعر القطاعي','number','0','min="0" step="0.01"')}${input('wholesale_price','سعر الجملة','number','0','min="0" step="0.01"')}${input('super_wholesale_price','سعر جملة الجملة','number','0','min="0" step="0.01"')}</div><div class="row">${input('stock','المخزون','number','0','min="0"')}${input('wholesale_min','حد الجملة','number','12','min="1"')}${input('super_wholesale_min','حد جملة الجملة','number','48','min="1"')}</div>${input('image_url','رابط صورة المنتج','url','','')}${textarea('description_ar','وصف المنتج','','')}<button class="btn">إرسال المنتج للمراجعة</button></form>`;
}
function vendorProductsTable(products){
  return products.length ? table(['المنتج','الفئة','الحالة','السعر','المخزون','إجراء'], products.map(p=>`<tr><td>${esc(p.name_ar)}</td><td>${esc(p.category||'')}</td><td>${badge(p.status)}</td><td>${money(p.wholesale_price)}</td><td>${esc(p.stock)}</td><td><button class="btn-danger" onclick="deleteProduct('${esc(p.id)}')">حذف</button></td></tr>`)) : '<div class="empty">لا توجد منتجات. أضف منتج وسيظهر للإدارة للمراجعة.</div>';
}
function paymentForm(){
  return `<form class="card form" id="paymentForm"><h3>تسجيل دفعة مستحقات المنصة</h3><div class="row">${input('amount','المبلغ','number','0','min="0" step="0.01"')}${select('method','طريقة الدفع',['تحويل بنكي','محفظة إلكترونية','Instapay','نقدي'],'تحويل بنكي')}${input('reference','رقم العملية / المرجع','text','','')}</div>${textarea('notes','ملاحظات','','')}<button class="btn">إرسال الدفعة للمراجعة</button></form>`;
}
function paymentsTable(payments){
  return payments.length ? table(['المبلغ','الطريقة','المرجع','الحالة','التاريخ'], payments.map(p=>`<tr><td>${money(p.amount)}</td><td>${esc(p.method)}</td><td>${esc(p.reference||'')}</td><td>${badge(p.status)}</td><td>${new Date(p.created_at).toLocaleDateString('ar-EG')}</td></tr>`)) : '<div class="empty">لا توجد دفعات مسجلة.</div>';
}
function bindAllDashboardForms(){
  const profile=qs('#vendorProfileForm');
  if(profile){ bindGov(profile); profile.addEventListener('submit', e=>{ e.preventDefault(); const f=Object.fromEntries(new FormData(profile)); const u=me(); const v=getVendor(u.id); Object.assign(v,{store_name:f.store_name,logo_url:f.logo_url,min_order:num(f.min_order),governorate:f.governorate,district:f.district,area:f.area,description:f.description,bank_name:f.bank_name,iban:f.iban,wallet_number:f.wallet_number}); saveState(); log('update_vendor_profile','vendors',u.id); qs('#out').innerHTML=notice('تم حفظ بيانات المورد.','ok'); }); }
  const zone=qs('#zoneForm');
  if(zone){ bindGov(zone); zone.addEventListener('submit', e=>{ e.preventDefault(); const f=Object.fromEntries(new FormData(zone)); const exists=state.vendor_delivery_zones.some(z=>z.vendor_id===me().id && z.governorate===f.governorate && z.district===f.district && z.area===f.area); if(exists) return qs('#out').innerHTML=notice('منطقة التوصيل مسجلة بالفعل.','error'); state.vendor_delivery_zones.push({id:id(), vendor_id:me().id, governorate:f.governorate, district:f.district, area:f.area||'كل المناطق', delivery_fee:num(f.delivery_fee), eta_days:num(f.eta_days||2), is_active:true, created_at:now()}); saveState(); log('create_zone','vendor_delivery_zones',me().id); vendorDashboard(); }); }
  const prod=qs('#productForm');
  if(prod){ prod.addEventListener('submit', e=>{ e.preventDefault(); const f=Object.fromEntries(new FormData(prod)); state.products.unshift({id:id(), vendor_id:me().id, status:state.settings.requireProductApproval?'pending':'approved', name_ar:f.name_ar, category:f.category, brand:f.brand, unit:f.unit, description_ar:f.description_ar, retail_price:num(f.retail_price), wholesale_price:num(f.wholesale_price), super_wholesale_price:num(f.super_wholesale_price), wholesale_min:num(f.wholesale_min), super_wholesale_min:num(f.super_wholesale_min), stock:num(f.stock), image_url:f.image_url, created_at:now()}); saveState(); log('create_product','products',me().id,{name:f.name_ar}); vendorDashboard(); }); }
  const pay=qs('#paymentForm');
  if(pay){ pay.addEventListener('submit', e=>{ e.preventDefault(); const f=Object.fromEntries(new FormData(pay)); if(num(f.amount)<=0) return qs('#out').innerHTML=notice('أدخل مبلغ صحيح.','error'); state.commission_payments.unshift({id:id(), vendor_id:me().id, amount:num(f.amount), method:f.method, reference:f.reference, notes:f.notes, status:'pending', created_at:now()}); saveState(); log('create_payment','commission_payments',me().id); vendorDashboard(); }); }
}
function deleteZone(zoneId){ state.vendor_delivery_zones = state.vendor_delivery_zones.filter(z=>z.id!==zoneId || z.vendor_id!==me().id); saveState(); vendorDashboard(); }
function deleteProduct(productId){ const p=state.products.find(x=>x.id===productId); if(!p || p.vendor_id!==me().id) return; if(confirm('هل تريد حذف المنتج؟')){ state.products = state.products.filter(x=>x.id!==productId); saveState(); vendorDashboard(); } }

function adminDashboard(){
  const u=me(); if(!u || !isAdmin()) return go('/login');
  const ordersTotal = state.orders.reduce((s,o)=>s+num(o.total),0);
  const commission = state.order_items.reduce((s,i)=>s+num(i.commission_amount),0) + state.orders.reduce((s,o)=>s+num(o.premium_fee),0);
  const paid = state.commission_payments.filter(p=>p.status==='approved').reduce((s,p)=>s+num(p.amount),0);
  page('لوحة الإدارة', `<div class="grid-3">${metric('المستخدمون',state.users.length)}${metric('قيمة الطلبات',money(ordersTotal))}${metric('مستحقات المنصة',money(commission-paid))}${metric('موردون قيد المراجعة',state.users.filter(x=>x.role==='vendor'&&x.status==='pending').length)}${metric('منتجات قيد المراجعة',state.products.filter(x=>x.status==='pending').length)}${metric('تذاكر دعم مفتوحة',state.support_tickets.filter(t=>t.status==='open').length)}</div><div class="tabs"><button class="tab active" onclick="showPanel('overview')">الملخص</button><button class="tab" onclick="showPanel('users')">المستخدمون</button><button class="tab" onclick="showPanel('vendors')">الموردون</button><button class="tab" onclick="showPanel('products')">المنتجات</button><button class="tab" onclick="showPanel('orders')">الطلبات</button><button class="tab" onclick="showPanel('finance')">المالية</button><button class="tab" onclick="showPanel('settings')">الإعدادات</button><button class="tab" onclick="showPanel('support')">الدعم</button><button class="tab" onclick="showPanel('data')">البيانات</button><button class="tab" onclick="logout()">خروج</button></div><div id="panel-overview" class="panel">${adminOverview()}</div><div id="panel-users" class="panel hidden">${adminUsers()}</div><div id="panel-vendors" class="panel hidden">${adminVendors()}</div><div id="panel-products" class="panel hidden">${adminProducts()}</div><div id="panel-orders" class="panel hidden">${adminOrders()}</div><div id="panel-finance" class="panel hidden">${adminFinance()}</div><div id="panel-settings" class="panel hidden">${adminSettings()}</div><div id="panel-support" class="panel hidden">${adminSupport()}</div><div id="panel-data" class="panel hidden">${adminData()}</div><div id="out"></div>`);
  bindAdminForms();
}
function adminOverview(){
  return `<div class="grid"><div class="card"><h3>جاهزية التشغيل</h3><div class="steps"><div class="step"><b>✓</b><div><strong>لا توجد بيانات جاهزة</strong><p>النظام يبدأ من الصفر، وكل شيء يتم إدخاله من خلال النماذج.</p></div></div><div class="step"><b>✓</b><div><strong>الإعدادات المالية داخل الإدارة</strong><p>نسب العمولة والسلة المميزة لا تظهر في الصفحات العامة.</p></div></div><div class="step"><b>✓</b><div><strong>مراجعة قبل النشر</strong><p>اعتماد المورد والمنتج قبل الظهور في السوق.</p></div></div></div></div><div class="card"><h3>تدفق العمل</h3><p class="muted">مورد يسجل ← الإدارة تعتمد المورد ← المورد يضيف مناطق ومنتجات ← الإدارة تعتمد المنتجات ← العميل يطلب ← النظام يتحقق من التغطية ← الطلب يدخل التشغيل والمالية.</p></div></div>`;
}
function adminUsers(){
  const rows = state.users.map(u=>`<tr><td>${esc(u.name)}</td><td>${esc(u.role)}</td><td>${esc(u.phone)}</td><td>${badge(u.status)}</td><td>${new Date(u.created_at).toLocaleDateString('ar-EG')}</td><td class="inline-actions"><button class="btn-ok" onclick="setUserStatus('${esc(u.id)}','approved')">اعتماد</button><button class="btn-warn" onclick="setUserStatus('${esc(u.id)}','suspended')">إيقاف</button><button class="btn-danger" onclick="setUserStatus('${esc(u.id)}','rejected')">رفض</button></td></tr>`);
  return `<div class="grid-2"><form class="card form" id="staffForm"><h3>إضافة مشرف إدارة</h3><div class="row">${input('name','الاسم','text','','required')}${input('phone','رقم الهاتف','tel','','required')}${input('email','البريد','email','','')}${input('password','كلمة المرور','password','','required minlength="6"')}</div><button class="btn">إضافة المشرف</button></form><div class="card"><h3>صلاحيات الإدارة</h3><p class="muted">يمكن إضافة أكثر من مشرف للتشغيل والمتابعة. كل مشرف يظهر داخل سجل العمليات عند تنفيذ أي إجراء.</p></div></div>${table(['الاسم','الدور','الهاتف','الحالة','تاريخ','إجراء'], rows)}`;
}
function adminVendors(){
  const rows = state.vendors.map(v=>{ const u=getUser(v.user_id)||{}; const zones=state.vendor_delivery_zones.filter(z=>z.vendor_id===v.user_id).length; const prods=state.products.filter(p=>p.vendor_id===v.user_id).length; return `<tr><td>${esc(v.store_name)}</td><td>${esc(u.name||'')}</td><td>${esc(u.phone||'')}</td><td>${badge(u.status)}</td><td>${esc(zones)}</td><td>${esc(prods)}</td><td class="inline-actions"><button class="btn-ok" onclick="setUserStatus('${esc(v.user_id)}','approved')">اعتماد</button><button class="btn-danger" onclick="setUserStatus('${esc(v.user_id)}','rejected')">رفض</button><button class="btn3" onclick="go('/vendor-page/${esc(v.user_id)}')">عرض</button></td></tr>`; });
  return `<div class="card"><h3>إدارة الموردين</h3><p class="muted">المورد غير المعتمد لا يظهر في صفحة الموردين ولا تظهر منتجاته في السوق.</p></div>${table(['المورد','المسؤول','الهاتف','الحالة','مناطق','منتجات','إجراء'],rows)}`;
}
function adminProducts(){
  const rows = state.products.map(p=>{ const v=getVendor(p.vendor_id)||{}; return `<tr><td>${esc(p.name_ar)}</td><td>${esc(v.store_name||'')}</td><td>${esc(p.category||'')}</td><td>${badge(p.status)}</td><td>${money(p.wholesale_price)}</td><td>${esc(p.stock)}</td><td class="inline-actions"><button class="btn-ok" onclick="setProductStatus('${esc(p.id)}','approved')">اعتماد</button><button class="btn-danger" onclick="setProductStatus('${esc(p.id)}','rejected')">رفض</button></td></tr>`; });
  return table(['المنتج','المورد','الفئة','الحالة','سعر الجملة','المخزون','إجراء'], rows);
}
function adminOrders(){
  const rows = state.orders.map(o=>{ const u=getUser(o.customer_id)||{}; return `<tr><td>${esc(o.id.slice(0,8))}</td><td>${esc(u.name||'')}</td><td>${badge(o.status)}</td><td>${esc(o.governorate)} - ${esc(o.district)}</td><td>${money(o.total)}</td><td>${new Date(o.created_at).toLocaleDateString('ar-EG')}</td><td><select onchange="setOrderStatus('${esc(o.id)}',this.value)"><option value="new" ${o.status==='new'?'selected':''}>جديد</option><option value="confirmed" ${o.status==='confirmed'?'selected':''}>مؤكد</option><option value="preparing" ${o.status==='preparing'?'selected':''}>قيد التجهيز</option><option value="shipped" ${o.status==='shipped'?'selected':''}>تم الشحن</option><option value="delivered" ${o.status==='delivered'?'selected':''}>تم التسليم</option><option value="cancelled" ${o.status==='cancelled'?'selected':''}>ملغي</option></select></td></tr>`; });
  return table(['رقم','العميل','الحالة','العنوان','الإجمالي','التاريخ','تحديث'], rows);
}
function vendorBalance(vendorId){
  const items=state.order_items.filter(i=>i.vendor_id===vendorId);
  const payments=state.commission_payments.filter(p=>p.vendor_id===vendorId);
  const sales=items.reduce((s,i)=>s+num(i.subtotal),0); const commission=items.reduce((s,i)=>s+num(i.commission_amount),0); const paid=payments.filter(p=>p.status==='approved').reduce((s,p)=>s+num(p.amount),0); const pending=payments.filter(p=>p.status==='pending').reduce((s,p)=>s+num(p.amount),0);
  return {sales,commission,paid,pending,remaining:commission-paid,vendorNet:sales-commission};
}
function adminFinance(){
  const vendorRows = state.vendors.map(v=>{ const b=vendorBalance(v.user_id); return `<tr><td>${esc(v.store_name)}</td><td>${money(b.sales)}</td><td>${money(b.commission)}</td><td>${money(b.paid)}</td><td>${money(b.pending)}</td><td>${money(b.remaining)}</td><td>${money(b.vendorNet)}</td></tr>`; });
  const payRows = state.commission_payments.map(p=>{ const v=getVendor(p.vendor_id)||{}; return `<tr><td>${esc(v.store_name||p.vendor_id.slice(0,8))}</td><td>${money(p.amount)}</td><td>${esc(p.method)}</td><td>${esc(p.reference||'')}</td><td>${badge(p.status)}</td><td class="inline-actions"><button class="btn-ok" onclick="setPaymentStatus('${esc(p.id)}','approved')">اعتماد</button><button class="btn-danger" onclick="setPaymentStatus('${esc(p.id)}','rejected')">رفض</button></td></tr>`; });
  return `<div class="card"><h3>الإعدادات المالية الإدارية</h3><p class="muted">هذه الإعدادات داخل الإدارة فقط ولا تظهر في الصفحات العامة للموردين أو العملاء.</p><div class="action-row"><button class="btn3" onclick="showPanel('settings')">تعديل الإعدادات</button><button class="btn2" onclick="exportCSV('vendor_balances')">تصدير أرصدة الموردين CSV</button></div></div><h2>أرصدة الموردين</h2>${table(['المورد','مبيعات','مستحق المنصة','مدفوع','قيد المراجعة','متبقي','صافي المورد'],vendorRows)}<h2>دفعات الموردين</h2>${table(['المورد','المبلغ','الطريقة','المرجع','الحالة','إجراء'],payRows)}`;
}
function adminSettings(){
  return `<form class="card form" id="settingsForm"><h3>إعدادات المنصة</h3><div class="row">${input('platformName','اسم المنصة','text',state.settings.platformName,'required')}${input('platformStatus','حالة المنصة','text',state.settings.platformStatus||'جاهزة للإطلاق','')}${input('supportPhone','رقم الدعم','tel',state.settings.supportPhone,'')}${input('supportPhone2','رقم دعم إضافي','tel',state.settings.supportPhone2||'','')}${input('supportWhatsapp','واتساب','tel',state.settings.supportWhatsapp,'')}${input('supportEmail','البريد','email',state.settings.supportEmail,'')}</div><div class="row">${input('defaultCommissionPercent','نسبة مستحق المنصة %','number',state.settings.defaultCommissionPercent,'min="0" step="0.01"')}${input('premiumCartPercent','رسوم السلة المميزة %','number',state.settings.premiumCartPercent,'min="0" step="0.01"')}${input('operationsSlaHours','SLA التشغيل بالساعة','number',state.settings.operationsSlaHours||24,'min="1"')}${input('currency','العملة','text',state.settings.currency,'')}</div>${textarea('categories','الفئات - كل فئة في سطر',state.settings.categories.join('\n'),'')}${textarea('paymentMethods','طرق الدفع - كل طريقة في سطر',(state.settings.paymentMethods||[]).join('\n'),'')}<button class="btn">حفظ الإعدادات</button></form>`;
}
function adminSupport(){
  const rows = state.support_tickets.map(t=>`<tr><td>${esc(t.name)}</td><td>${esc(t.phone)}</td><td>${esc(t.subject)}</td><td>${badge(t.status)}</td><td>${new Date(t.created_at).toLocaleDateString('ar-EG')}</td><td><button class="btn-ok" onclick="closeTicket('${esc(t.id)}')">إغلاق</button></td></tr>`);
  return table(['الاسم','الهاتف','الموضوع','الحالة','التاريخ','إجراء'], rows);
}
function adminData(){
  return `<div class="grid"><div class="card"><h3>تصدير البيانات</h3><p class="muted">احتفظ بنسخة من البيانات المدخلة من المتصفح.</p><div class="action-row"><button class="btn" onclick="exportJSON()">تصدير JSON</button><button class="btn2" onclick="exportCSV('products')">منتجات CSV</button><button class="btn2" onclick="exportCSV('orders')">طلبات CSV</button><button class="btn2" onclick="exportCSV('users')">مستخدمون CSV</button></div></div><div class="card"><h3>استيراد نسخة JSON</h3><p class="muted">استخدم ملف تم تصديره سابقاً من نفس النسخة.</p><input type="file" id="importFile" accept="application/json"><button class="btn3" onclick="importJSON()">استيراد</button></div><div class="card"><h3>سجل العمليات</h3>${table(['العملية','الكيان','التاريخ'], state.audit_logs.slice(0,20).map(l=>`<tr><td>${esc(l.action)}</td><td>${esc(l.entity_type)}</td><td>${new Date(l.created_at).toLocaleString('ar-EG')}</td></tr>`))}</div></div>`;
}
function bindAdminForms(){
  const f=qs('#settingsForm');
  if(f){ f.addEventListener('submit', e=>{ e.preventDefault(); const data=Object.fromEntries(new FormData(f)); Object.assign(state.settings,{platformName:data.platformName,platformStatus:data.platformStatus,supportPhone:data.supportPhone,supportPhone2:data.supportPhone2,supportWhatsapp:data.supportWhatsapp,supportEmail:data.supportEmail,currency:data.currency,operationsSlaHours:num(data.operationsSlaHours),defaultCommissionPercent:num(data.defaultCommissionPercent),premiumCartPercent:num(data.premiumCartPercent),categories:data.categories.split(/\n|,/).map(x=>x.trim()).filter(Boolean),paymentMethods:data.paymentMethods.split(/\n|,/).map(x=>x.trim()).filter(Boolean)}); saveState(); log('update_settings','platform_settings','general'); qs('#out').innerHTML=notice('تم حفظ الإعدادات.','ok'); }); }
  const staff=qs('#staffForm');
  if(staff){ staff.addEventListener('submit', e=>{ e.preventDefault(); const data=Object.fromEntries(new FormData(staff)); if(state.users.some(u=>u.phone===data.phone.trim())) return qs('#out').innerHTML=notice('رقم الهاتف مستخدم بالفعل.','error'); const u={id:id(),role:'staff',status:'approved',name:data.name.trim(),phone:data.phone.trim(),email:data.email.trim(),password:data.password,permissions:{admin:true},created_at:now()}; state.users.push(u); saveState(); log('create_staff','users',u.id,{name:u.name}); adminDashboard(); }); }
}
function setUserStatus(userId,status){ const u=getUser(userId); if(!u) return; u.status=status; saveState(); log('set_user_status','users',userId,{status}); adminDashboard(); }
function setProductStatus(productId,status){ const p=state.products.find(x=>x.id===productId); if(!p) return; p.status=status; saveState(); log('set_product_status','products',productId,{status}); adminDashboard(); }
function setOrderStatus(orderId,status){ const o=state.orders.find(x=>x.id===orderId); if(!o) return; o.status=status; if(status==='delivered') o.payment_status='paid'; saveState(); log('set_order_status','orders',orderId,{status}); adminDashboard(); }
function setPaymentStatus(paymentId,status){ const p=state.commission_payments.find(x=>x.id===paymentId); if(!p) return; p.status=status; saveState(); log('set_payment_status','commission_payments',paymentId,{status}); adminDashboard(); }
function closeTicket(ticketId){ const t=state.support_tickets.find(x=>x.id===ticketId); if(t){t.status='closed'; saveState(); adminDashboard();} }

function supportPage(){
  page('الدعم', `<div class="grid-2"><form class="card form" id="ticketForm"><h3>فتح تذكرة دعم</h3>${input('name','الاسم','text',me()?.name||'','required')}${input('phone','رقم الهاتف','tel',me()?.phone||'','required')}${input('subject','الموضوع','text','','required')}${textarea('message','الرسالة','','required')}<button class="btn">إرسال التذكرة</button><div id="out"></div></form><div class="card"><h3>بيانات التواصل</h3><p><b>الهاتف:</b> ${esc(state.settings.supportPhone)}</p><p><b>الهاتف 2:</b> ${esc(state.settings.supportPhone2||'-')}</p><p><b>واتساب:</b> ${esc(state.settings.supportWhatsapp)}</p><p><b>البريد:</b> ${esc(state.settings.supportEmail)}</p><p class="muted">تظهر التذاكر داخل لوحة الإدارة للمراجعة والإغلاق.</p></div></div>`);
  qs('#ticketForm').addEventListener('submit', e=>{ e.preventDefault(); const f=Object.fromEntries(new FormData(e.target)); state.support_tickets.unshift({id:id(), user_id:me()?.id||null, name:f.name, phone:f.phone, subject:f.subject, message:f.message, status:'open', created_at:now()}); saveState(); qs('#out').innerHTML=notice('تم إرسال التذكرة للإدارة.','ok'); e.target.reset(); });
}
function howPage(){ page('كيف تعمل المنصة؟', `<div class="grid"><div class="card"><div class="icon">1</div><h3>تسجيل المورد</h3><p class="muted">المورد يدخل بيانات الشركة ومناطق التغطية والمنتجات.</p></div><div class="card"><div class="icon">2</div><h3>اعتماد الإدارة</h3><p class="muted">الإدارة تراجع المورد والمنتجات قبل الظهور للعامة.</p></div><div class="card"><div class="icon">3</div><h3>طلب العميل</h3><p class="muted">العميل يختار المنتجات، والمنصة تتحقق من التغطية قبل تأكيد الطلب.</p></div><div class="card"><div class="icon">4</div><h3>التشغيل والمالية</h3><p class="muted">متابعة الحالة، الشحن، التسليم، المدفوعات، ومستحقات المنصة من الإدارة.</p></div></div>`); }
function policiesPage(){ page('السياسات', `<div class="grid"><div class="card"><h3>شروط الاستخدام</h3><p class="muted">استخدام المنصة مشروط بصحة بيانات الحساب والعنوان والالتزام بسياسات البيع والتوريد.</p></div><div class="card"><h3>سياسة الموردين</h3><p class="muted">لا يتم نشر المورد أو المنتجات إلا بعد مراجعة الإدارة واعتمادها.</p></div><div class="card"><h3>سياسة الطلبات</h3><p class="muted">لا يتم تأكيد الطلب إذا لم تكن منطقة العميل ضمن مناطق تغطية المورد.</p></div><div class="card"><h3>سياسة الخصوصية</h3><p class="muted">بيانات المستخدمين محفوظة داخل تخزين المنصة. عند الربط بقاعدة بيانات يتم تطبيق صلاحيات الوصول المناسبة.</p></div></div>`); }
function aboutPage(){ page('عن Tager', `<div class="card"><h3>منصة تجارة وتوريد وربط</h3><p class="muted">Tager مصممة لتشغيل سوق توريد منظم بين الموردين والعملاء مع إدارة داخلية للمالية والتشغيل والاعتماد.</p></div>`); }
function logout(){ clearSession(); go('/login'); }

function download(filename, content, type='text/plain;charset=utf-8'){
  const blob = new Blob([content], {type}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=filename; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
}
function exportJSON(){ download(`tager-v26-backup-${new Date().toISOString().slice(0,10)}.json`, JSON.stringify(state,null,2), 'application/json'); }
function importJSON(){
  const file = qs('#importFile')?.files?.[0]; if(!file) return alert('اختر ملف JSON أولاً.');
  const reader = new FileReader(); reader.onload = () => { try{ const data=JSON.parse(reader.result); if(!data || !data.settings || !Array.isArray(data.users)) throw new Error('ملف غير صحيح'); state={...DEFAULT_STATE(), ...data, settings:{...DEFAULT_STATE().settings, ...(data.settings||{})}}; saveState(); alert('تم الاستيراد بنجاح.'); adminDashboard(); }catch(e){ alert('تعذر استيراد الملف: '+e.message); } }; reader.readAsText(file);
}
function csvValue(v){ return `"${String(v??'').replace(/"/g,'""')}"`; }
function toCSV(rows){ if(!rows.length) return ''; const keys=Object.keys(rows[0]); return [keys.map(csvValue).join(','), ...rows.map(r=>keys.map(k=>csvValue(r[k])).join(','))].join('\n'); }
function exportCSV(type){
  let rows=[];
  if(type==='products') rows = state.products.map(p=>({id:p.id,name:p.name_ar,vendor:getVendor(p.vendor_id)?.store_name||'',status:p.status,category:p.category,retail_price:p.retail_price,wholesale_price:p.wholesale_price,stock:p.stock}));
  if(type==='orders') rows = state.orders.map(o=>({id:o.id,customer:getUser(o.customer_id)?.name||'',status:o.status,total:o.total,governorate:o.governorate,district:o.district,created_at:o.created_at}));
  if(type==='users') rows = state.users.map(u=>({id:u.id,name:u.name,role:u.role,phone:u.phone,status:u.status,created_at:u.created_at}));
  if(type==='vendor_balances') rows = state.vendors.map(v=>({vendor:v.store_name,...vendorBalance(v.user_id)}));
  download(`tager-${type}-${new Date().toISOString().slice(0,10)}.csv`, '\ufeff'+toCSV(rows), 'text/csv;charset=utf-8');
}

function route(){
  updateShell();
  const p = location.pathname.replace(/\/$/,'') || '/';
  if(p==='/') return home();
  if(p==='/setup') return setup();
  if(p==='/login') return login();
  if(p==='/logout') return logout();
  if(p==='/register/customer') return register('customer');
  if(p==='/register/vendor') return register('vendor');
  if(p==='/products' || p==='/market') return productsPage();
  if(p==='/categories') return categoriesPage();
  if(p.startsWith('/product/')) return productDetailPage(decodeURIComponent(p.split('/').pop()));
  if(p==='/vendors') return vendorsPage();
  if(p.startsWith('/vendor-page/')) return vendorPublicPage(decodeURIComponent(p.split('/').pop()));
  if(p==='/cart') return cartPage();
  if(p==='/checkout') return checkoutPage();
  if(p==='/customer') return customerDashboard();
  if(p==='/vendor') return vendorDashboard();
  if(p==='/admin') return adminDashboard();
  if(p==='/support') return supportPage();
  if(p==='/how') return howPage();
  if(p==='/policies') return policiesPage();
  if(p==='/about') return aboutPage();
  return home();
}

window.go = go;
window.logout = logout;
window.addToCart = addToCart;
window.removeCart = removeCart;
window.setCartQty = setCartQty;
window.showPanel = showPanel;
window.deleteZone = deleteZone;
window.deleteProduct = deleteProduct;
window.setUserStatus = setUserStatus;
window.setProductStatus = setProductStatus;
window.setOrderStatus = setOrderStatus;
window.setPaymentStatus = setPaymentStatus;
window.closeTicket = closeTicket;
window.exportJSON = exportJSON;
window.importJSON = importJSON;
window.exportCSV = exportCSV;

document.addEventListener('click', e=>{
  const a = e.target.closest('a');
  if(a && a.getAttribute('href') && a.getAttribute('href').startsWith('/')){ e.preventDefault(); go(a.getAttribute('href')); }
});
window.addEventListener('popstate', route);
document.getElementById('menuButton').addEventListener('click', ()=>document.getElementById('navLinks').classList.toggle('open'));
window.addEventListener('error', e=>{ console.error(e.error||e.message); try{ page('حدث خطأ في الصفحة', notice(esc(e.message||'خطأ غير معروف'),'error')); }catch(_){} });
window.addEventListener('unhandledrejection', e=>{ console.error(e.reason); try{ page('حدث خطأ في التشغيل', notice(esc(e.reason?.message||e.reason||'خطأ غير معروف'),'error')); }catch(_){} });
route();
