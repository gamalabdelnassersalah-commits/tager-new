const STORAGE_KEY = 'tager-clean-mvp-v1';
const SESSION_KEY = 'tager-clean-session-v1';
const CART_KEY = 'tager-clean-cart-v1';
const LANG_KEY = 'tager-clean-lang-v1';

const governorates = {
  'القاهرة': ['مدينة نصر','مصر الجديدة','المعادي','التجمع الخامس','المرج','حلوان'],
  'الجيزة': ['الدقي','المهندسين','الهرم','فيصل','6 أكتوبر','الشيخ زايد'],
  'الإسكندرية': ['المنتزه','سيدي جابر','العجمي','برج العرب','سموحة'],
  'القليوبية': ['بنها','شبرا الخيمة','قليوب','الخانكة','العبور'],
  'الشرقية': ['الزقازيق','العاشر من رمضان','بلبيس','منيا القمح','فاقوس'],
  'الدقهلية': ['المنصورة','ميت غمر','طلخا','دكرنس','السنبلاوين'],
  'البحيرة': ['دمنهور','كفر الدوار','رشيد','إدكو','وادي النطرون'],
  'الغربية': ['طنطا','المحلة الكبرى','كفر الزيات','زفتى','السنطة'],
  'المنوفية': ['شبين الكوم','السادات','منوف','أشمون','قويسنا'],
  'كفر الشيخ': ['كفر الشيخ','دسوق','بلطيم','سيدي سالم','الحامول'],
  'دمياط': ['دمياط','دمياط الجديدة','رأس البر','فارسكور','كفر سعد'],
  'بورسعيد': ['بورفؤاد','شرق','العرب','المناخ','الزهور'],
  'الإسماعيلية': ['الإسماعيلية','فايد','القنطرة شرق','القنطرة غرب','التل الكبير'],
  'السويس': ['السويس','الأربعين','عتاقة','فيصل','الجناين'],
  'شمال سيناء': ['العريش','بئر العبد','الشيخ زويد','رفح'],
  'جنوب سيناء': ['شرم الشيخ','طور سيناء','دهب','نويبع','رأس سدر'],
  'بني سويف': ['بني سويف','الواسطى','ناصر','ببا','الفشن'],
  'الفيوم': ['الفيوم','سنورس','إطسا','طامية','يوسف الصديق'],
  'المنيا': ['المنيا','ملوي','سمالوط','بني مزار','أبو قرقاص'],
  'أسيوط': ['أسيوط','ديروط','القوصية','منفلوط','أبنوب'],
  'سوهاج': ['سوهاج','أخميم','جرجا','طهطا','البلينا'],
  'قنا': ['قنا','نجع حمادي','دشنا','قفط','قوص'],
  'الأقصر': ['الأقصر','إسنا','أرمنت','القرنة','الطود'],
  'أسوان': ['أسوان','إدفو','كوم أمبو','دراو','نصر النوبة'],
  'البحر الأحمر': ['الغردقة','سفاجا','القصير','مرسى علم','رأس غارب'],
  'الوادي الجديد': ['الخارجة','الداخلة','الفرافرة','باريس','بلاط'],
  'مطروح': ['مرسى مطروح','العلمين','الحمام','الضبعة','سيوة']
};

const categories = [
  ['مواد غذائية','🍚'],['زيوت','🫗'],['مشروبات','🥤'],['ألبان','🥛'],['منظفات','🧼'],['ورقيات','🧻'],['معلبات','🥫'],['حلويات','🍪']
];

const ui = {
  ar: {
    home:'الرئيسية', market:'المنتجات', vendors:'الموردون', how:'كيف تعمل؟', login:'تسجيل الدخول', account:'حسابي', logout:'خروج', cart:'السلة', search:'ابحث',
    retail:'قطاعي', wholesale:'جملة', bulk:'جملة الجملة', add:'أضف للسلة', view:'عرض', all:'الكل', admin:'الإدارة', vendor:'المورد', customer:'العميل'
  },
  en: {
    home:'Home', market:'Products', vendors:'Vendors', how:'How it works', login:'Login', account:'My Account', logout:'Logout', cart:'Cart', search:'Search',
    retail:'Retail', wholesale:'Wholesale', bulk:'Bulk Wholesale', add:'Add to cart', view:'View', all:'All', admin:'Admin', vendor:'Vendor', customer:'Customer'
  }
};

function t(key){ return ui[getLang()][key] || key; }
function getLang(){ return localStorage.getItem(LANG_KEY) || 'ar'; }
function setLang(lang){ localStorage.setItem(LANG_KEY, lang); document.documentElement.lang=lang; document.documentElement.dir=lang==='ar'?'rtl':'ltr'; document.body.classList.toggle('ltr',lang==='en'); render(); }

function svgData(emoji, bg='#eaf7f1', fg='#07543d'){
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600"><rect width="800" height="600" rx="36" fill="${bg}"/><circle cx="400" cy="285" r="180" fill="#fff" opacity=".62"/><text x="400" y="345" text-anchor="middle" font-size="190">${emoji}</text><path d="M180 500h440" stroke="${fg}" stroke-width="12" opacity=".16" stroke-linecap="round"/></svg>`;
  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function uid(prefix='id'){ return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2,8)}`; }
function money(n){ return new Intl.NumberFormat(getLang()==='ar'?'ar-EG':'en-EG',{style:'currency',currency:'EGP',maximumFractionDigits:2}).format(Number(n)||0); }
function dateText(v){ return new Intl.DateTimeFormat(getLang()==='ar'?'ar-EG':'en-GB',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)); }
function escapeHtml(value=''){ return String(value).replace(/[&<>'"]/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;' }[m])); }
function slugify(v=''){ return v.trim().toLowerCase().replace(/\s+/g,'-').replace(/[^\w\-\u0600-\u06FF]/g,''); }
function normalizePhone(v=''){ return v.replace(/\D/g,'').replace(/^20(?=1[0125]\d{8}$)/,''); }
function validPhone(v){ return /^01[0125]\d{8}$/.test(normalizePhone(v)); }
function validEmail(v){ return !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

function seedState(){
  const adminId='u_admin'; const vendor1='u_vendor_1'; const vendor2='u_vendor_2'; const customer='u_customer_1';
  const products=[
    ['p1',vendor1,'زيت عباد الشمس 1 لتر','Sunflower Oil 1L','زيوت','زيتة','زجاجة',100,90,80,12,48,500,200,2,'🫗'],
    ['p2',vendor1,'أرز مصري فاخر 5 كجم','Premium Egyptian Rice 5kg','مواد غذائية','الخير','كيس',260,235,220,6,30,300,100,2,'🍚'],
    ['p3',vendor1,'سكر أبيض 1 كجم','White Sugar 1kg','مواد غذائية','سكرنا','كيس',38,34,31,12,60,900,300,1,'🧂'],
    ['p4',vendor2,'مناديل مطبخ 6 رول','Kitchen Towels 6 Rolls','ورقيات','نظافة','عبوة',145,130,118,8,40,240,80,3,'🧻'],
    ['p5',vendor2,'مسحوق غسيل 5 كجم','Laundry Powder 5kg','منظفات','بريق','كرتونة',390,355,330,4,20,160,60,3,'🧼'],
    ['p6',vendor2,'مياه معدنية 600 مل × 24','Mineral Water 600ml × 24','مشروبات','نقاء','كرتونة',135,122,112,5,25,400,100,1,'💧'],
    ['p7',vendor1,'لبن كامل الدسم 1 لتر × 12','Full Cream Milk 1L × 12','ألبان','مزارعنا','كرتونة',540,505,470,3,15,140,50,2,'🥛'],
    ['p8',vendor2,'تونة قطع 185 جم × 48','Tuna Chunks 185g × 48','معلبات','بحر','كرتونة',2250,2090,1950,2,10,90,30,4,'🥫']
  ].map(([id,vendorId,nameAr,nameEn,category,brand,unit,retail,wholesale,bulk,wq,bq,stock,maxQty,prep,emoji],i)=>({
    id,vendorId,nameAr,nameEn,category,brand,unit,retail,wholesale,bulk,wholesaleQty:wq,bulkQty:bq,stock,maxQty,prepDays:prep,status:'approved',description:`منتج ${nameAr} بجودة مناسبة للتجارة والقطاعي، متاح بأسعار متدرجة حسب الكمية.`,image:svgData(emoji,i%2?'#eef8f3':'#f7f3e8'),createdAt:Date.now()-i*86400000
  }));
  return {
    version:1,
    settings:{platformName:'Tager',globalCommission:10,premiumCartFee:1.5,shippingEnabled:true,defaultShipping:55,freeShippingThreshold:2500,maintenance:false},
    users:[
      {id:adminId,role:'admin',name:'مدير منصة تاجر',phone:'01000000000',email:'admin@tager.test',password:'Admin@123',status:'approved',permissions:['all'],createdAt:Date.now()},
      {id:vendor1,role:'vendor',name:'أحمد محمود',phone:'01111111111',email:'vendor1@tager.test',password:'Vendor@123',status:'approved',storeName:'مؤسسة الخير للتجارة',governorate:'القاهرة',city:'مدينة نصر',minOrder:1000,commission:8,premiumFee:1.5,verified:true,businessType:'مواد غذائية وزيوت',description:'توريد مواد غذائية وزيوت للمحال والسوبر ماركت بأسعار جملة.',logo:svgData('🏪','#e8f5ef'),cover:svgData('📦','#07543d','#d9a441'),createdAt:Date.now()},
      {id:vendor2,role:'vendor',name:'سارة علي',phone:'01555555555',email:'vendor2@tager.test',password:'Vendor@123',status:'approved',storeName:'شركة النور للتوزيع',governorate:'الجيزة',city:'6 أكتوبر',minOrder:750,commission:9,premiumFee:2,verified:true,businessType:'منظفات وورقيات ومشروبات',description:'موزع جملة للمنظفات والورقيات والمياه والمشروبات.',logo:svgData('🏬','#f7f3e8'),cover:svgData('🚚','#0a7755','#fff'),createdAt:Date.now()},
      {id:customer,role:'customer',name:'عميل تجريبي',phone:'01222222222',email:'customer@tager.test',password:'Customer@123',status:'approved',governorate:'القاهرة',city:'المعادي',address:'شارع 9',createdAt:Date.now()}
    ],
    products,
    orders:[],
    notifications:[],
    adminTeam:[]
  };
}

function getState(){
  try{
    const raw=localStorage.getItem(STORAGE_KEY);
    if(!raw){ const seed=seedState(); localStorage.setItem(STORAGE_KEY,JSON.stringify(seed)); return seed; }
    return JSON.parse(raw);
  }catch(e){ const seed=seedState(); localStorage.setItem(STORAGE_KEY,JSON.stringify(seed)); return seed; }
}
function saveState(state){ localStorage.setItem(STORAGE_KEY,JSON.stringify(state)); }
function getSession(){ try{return JSON.parse(localStorage.getItem(SESSION_KEY)||'null')}catch{return null} }
function setSession(userId){ localStorage.setItem(SESSION_KEY,JSON.stringify({userId,loginAt:Date.now()})); }
function clearSession(){ localStorage.removeItem(SESSION_KEY); }
function currentUser(){ const s=getSession(); if(!s) return null; return getState().users.find(u=>u.id===s.userId)||null; }
function getCart(){ try{return JSON.parse(localStorage.getItem(CART_KEY)||'[]')}catch{return []} }
function saveCart(cart){ localStorage.setItem(CART_KEY,JSON.stringify(cart)); updateCartCount(); }
function cartCount(){ return getCart().reduce((s,i)=>s+Number(i.qty||0),0); }

function productName(p){ return getLang()==='en' ? (p.nameEn||p.nameAr) : p.nameAr; }
function userById(id){ return getState().users.find(u=>u.id===id); }
function productById(id){ return getState().products.find(p=>p.id===id); }
function tierPrice(p,qty){ qty=Number(qty)||1; if(qty>=p.bulkQty) return {tier:'bulk',price:p.bulk}; if(qty>=p.wholesaleQty) return {tier:'wholesale',price:p.wholesale}; return {tier:'retail',price:p.retail}; }
function tierLabel(tier){ return t(tier); }
function route(path){ location.hash=path.startsWith('#')?path:`#${path}`; }
function currentPath(){ return location.hash.replace(/^#/,'')||'/'; }
function routeParts(){ return currentPath().split('/').filter(Boolean); }

function toast(message,type='ok'){
  let el=document.querySelector('.toast');
  if(!el){el=document.createElement('div');el.className='toast';document.body.appendChild(el)}
  el.textContent=message;el.className=`toast ${type==='error'?'error':''} show`;
  clearTimeout(el._timer);el._timer=setTimeout(()=>el.classList.remove('show'),3000);
}

function requireRole(roles){
  const user=currentUser();
  if(!user){ route('/login'); return false; }
  if(!roles.includes(user.role)){ toast('غير مصرح لك بدخول هذه الصفحة','error'); route(dashboardPath(user)); return false; }
  return true;
}
function dashboardPath(user=currentUser()){
  if(!user) return '/login';
  if(user.role==='admin'||user.role==='staff') return '/admin';
  if(user.role==='vendor') return '/vendor-dashboard';
  return '/customer';
}

function updateCartCount(){ const el=document.querySelector('.cart-count'); if(el) el.textContent=cartCount(); }
function activeNav(path){ const current=currentPath(); return current===path||current.startsWith(path+'/')?'active':''; }
function renderHeader(){
  const user=currentUser();
  const accountLabel=user?escapeHtml(user.name.split(' ')[0]):t('login');
  return `
    <div class="demo-banner">نسخة تجريبية تعمل فورًا على Vercel — البيانات محفوظة في هذا المتصفح حتى يتم ربط Supabase.</div>
    <div class="topbar"><div class="container"><span>أسعار قطاعي وجملة وجملة الجملة في منصة واحدة</span><span>الدعم التجريبي: 01000000000</span></div></div>
    <header class="site-header"><div class="container header-row">
      <a class="brand" href="#/"><span class="brand-mark">🛒</span><span>Tager <em>تاجر</em></span></a>
      <button class="icon-btn menu-toggle" data-action="toggle-menu">☰</button>
      <nav class="main-nav" id="mainNav">
        <a class="${activeNav('/')}" href="#/">${t('home')}</a>
        <a class="${activeNav('/market')}" href="#/market">${t('market')}</a>
        <a class="${activeNav('/vendors')}" href="#/vendors">${t('vendors')}</a>
        <a class="${activeNav('/how')}" href="#/how">${t('how')}</a>
      </nav>
      <div class="header-actions">
        <button class="icon-btn" data-action="toggle-lang">${getLang()==='ar'?'EN':'عربي'}</button>
        <a class="nav-btn cart-btn" href="#/cart">🛍️ <span>${t('cart')}</span><b class="cart-count">${cartCount()}</b></a>
        <a class="nav-btn primary" href="#${user?dashboardPath(user):'/login'}">👤 <span>${accountLabel}</span></a>
      </div>
    </div></header>`;
}
function renderFooter(){
  return `<footer class="site-footer"><div class="container footer-grid">
    <div><h3>Tager تاجر</h3><p>منصة تجارة إلكترونية مصرية تجمع القطاعي والجملة وجملة الجملة، وتمنح كل مورد متجرًا مستقلًا ولوحة إدارة خاصة.</p></div>
    <div><h3>التسوق</h3><a href="#/market">المنتجات</a><a href="#/vendors">دليل الموردين</a><a href="#/cart">السلة</a></div>
    <div><h3>الحساب</h3><a href="#/login">تسجيل الدخول</a><a href="#/register/customer">تسجيل عميل</a><a href="#/register/vendor">انضم كمورد</a></div>
    <div><h3>المساعدة</h3><a href="#/how">كيف تعمل المنصة؟</a><a href="#/policies">الشروط والسياسات</a><a href="#/contact">تواصل معنا</a></div>
  </div><div class="copyright">© ${new Date().getFullYear()} Tager Marketplace — نسخة اختبار تفاعلية</div></footer>`;
}

function shell(content){
  document.getElementById('app').innerHTML=`${renderHeader()}<main><div class="container">${content}</div></main>${renderFooter()}`;
  updateCartCount();
}

function homeView(){
  const state=getState(); const approved=state.products.filter(p=>p.status==='approved').slice(0,8);
  const approvedVendors=state.users.filter(u=>u.role==='vendor'&&u.status==='approved');
  return `
  <section class="hero">
    <div><span class="kicker">منصة تجارة متكاملة للسوق المصري</span><h1>اشتري قطاعي أو جملة<br>أو جملة الجملة</h1><p>اختر منتجاتك من موردين موثّقين، وقارن الأسعار حسب الكمية، أو أنشئ سلة مميزة تجمع أصنافًا من عدة موردين.</p>
      <form class="search-box" data-form="hero-search"><input name="q" placeholder="ابحث عن منتج أو مورد..." /><button>بحث الآن</button></form>
      <div class="hero-actions"><a class="btn gold" href="#/market">ابدأ التسوق</a><a class="btn secondary" href="#/register/vendor">انضم كمورد</a></div>
    </div>
    <div class="hero-card"><div class="metric"><b>${state.products.filter(p=>p.status==='approved').length}+</b><span>منتج متاح</span></div><div class="metric"><b>${approvedVendors.length}</b><span>موردون معتمدون</span></div><div class="metric"><b>3</b><span>مستويات تسعير</span></div></div>
  </section>
  <section class="stats"><article><b>قطاعي</b><span>بدون حد أدنى للمنتج</span></article><article><b>جملة</b><span>أسعار أفضل للكميات</span></article><article><b>جملة الجملة</b><span>للتجار والموزعين</span></article><article><b>سلة مميزة</b><span>أصناف من عدة موردين</span></article></section>
  <section class="section"><div class="section-head"><div><h2>الأقسام الرئيسية</h2><p>ابدأ من القسم المناسب لنشاطك</p></div><a href="#/market">عرض الجميع</a></div><div class="grid cols-6">${categories.slice(0,6).map(([name,emoji])=>`<a class="card hover category-card" href="#/market?category=${encodeURIComponent(name)}"><span class="emoji">${emoji}</span><b>${name}</b><small>تصفح المنتجات</small></a>`).join('')}</div></section>
  <section class="section"><div class="section-head"><div><h2>منتجات مقترحة</h2><p>أسعار متدرجة حسب الكمية</p></div><a href="#/market">كل المنتجات</a></div><div class="grid cols-4">${approved.map(productCard).join('')}</div></section>
  <section class="section grid cols-2"><article class="card hover"><h2>للمشترين</h2><p>سجل برقم الهاتف، اختر السعر المناسب، تابع طلباتك وعناوينك من لوحة واحدة.</p><a class="btn primary" href="#/register/customer">إنشاء حساب عميل</a></article><article class="card hover" style="background:linear-gradient(135deg,#063d2e,#087050);color:#fff"><h2>للموردين</h2><p style="color:#d7ebe3">أنشئ متجرك، أضف صور المنتجات والأسعار والمخزون، وانتظر موافقة الإدارة.</p><a class="btn gold" href="#/register/vendor">تسجيل مورد</a></article></section>`;
}

function productCard(p){
  const vendor=userById(p.vendorId); const name=productName(p);
  return `<article class="card product-card hover">
    <a class="product-image" href="#/product/${p.id}"><img src="${p.image}" alt="${escapeHtml(name)}"></a>
    <div class="product-body"><div class="product-meta"><span>${escapeHtml(p.category)}</span><a href="#/vendor/${vendor?.id}">${escapeHtml(vendor?.storeName||'مورد')}</a></div><h3><a href="#/product/${p.id}" style="text-decoration:none">${escapeHtml(name)}</a></h3><div class="price">${money(p.retail)}</div>
    <div class="tiers"><span>${t('retail')}: ${money(p.retail)}</span><span>${t('wholesale')} من ${p.wholesaleQty}: ${money(p.wholesale)}</span><span>${t('bulk')} من ${p.bulkQty}: ${money(p.bulk)}</span></div>
    <span class="stock ${p.stock<20?'low':''}">${p.stock>0?`متاح: ${p.stock} ${escapeHtml(p.unit)}`:'غير متوفر'}</span>
    <div class="qty-add"><input type="number" min="1" max="${p.maxQty}" value="1" data-qty-for="${p.id}"><button class="btn primary" data-action="add-cart" data-product="${p.id}">${t('add')}</button></div></div></article>`;
}

function marketView(){
  const hash=currentPath(); const queryString=hash.includes('?')?hash.split('?')[1]:''; const qs=new URLSearchParams(queryString);
  const state=getState();
  const filters={q:qs.get('q')||'',tier:qs.get('tier')||'',governorate:qs.get('governorate')||'',city:qs.get('city')||'',category:qs.get('category')||''};
  let products=state.products.filter(p=>p.status==='approved'&&p.stock>0);
  if(filters.q){const q=filters.q.toLowerCase();products=products.filter(p=>`${p.nameAr} ${p.nameEn} ${p.brand} ${userById(p.vendorId)?.storeName}`.toLowerCase().includes(q));}
  if(filters.category) products=products.filter(p=>p.category===filters.category);
  if(filters.governorate) products=products.filter(p=>userById(p.vendorId)?.governorate===filters.governorate);
  if(filters.city) products=products.filter(p=>userById(p.vendorId)?.city===filters.city);
  const cities=filters.governorate?governorates[filters.governorate]||[]:[];
  return `<section class="page-hero"><h1>سوق تاجر</h1><p>ابحث حسب المنتج، نوع السعر، المحافظة والمركز.</p></section>
  <form class="toolbar" data-form="market-filter"><label>بحث<input name="q" value="${escapeHtml(filters.q)}" placeholder="اسم المنتج أو المورد"></label><label>نوع السعر<select name="tier"><option value="">الكل</option><option value="retail" ${filters.tier==='retail'?'selected':''}>قطاعي</option><option value="wholesale" ${filters.tier==='wholesale'?'selected':''}>جملة</option><option value="bulk" ${filters.tier==='bulk'?'selected':''}>جملة الجملة</option></select></label><label>القسم<select name="category"><option value="">كل الأقسام</option>${categories.map(([c])=>`<option ${filters.category===c?'selected':''}>${c}</option>`).join('')}</select></label><label>المحافظة<select name="governorate">${governorateOptions(filters.governorate,true)}</select></label><label>المركز<select name="city"><option value="">كل المراكز</option>${cities.map(c=>`<option ${filters.city===c?'selected':''}>${c}</option>`).join('')}</select></label><button class="btn primary">تطبيق</button></form>
  <div class="section-head"><div><h2>${products.length} منتج</h2><p>الأسعار تتغير تلقائيًا عند اختيار الكمية</p></div></div>
  ${products.length?`<div class="grid cols-4">${products.map(productCard).join('')}</div>`:`<div class="empty">لا توجد منتجات مطابقة للبحث.</div>`}`;
}

function productView(id){
  const p=productById(id); if(!p||p.status!=='approved') return notFound('المنتج غير موجود أو لم تتم الموافقة عليه.');
  const vendor=userById(p.vendorId); const name=productName(p);
  return `<div class="grid cols-2"><div class="card"><div class="product-image" style="height:470px"><img src="${p.image}" alt="${escapeHtml(name)}"></div></div><div class="card"><span class="status approved">${escapeHtml(p.category)}</span><h1>${escapeHtml(name)}</h1><p>${escapeHtml(p.description)}</p><div class="price">${money(p.retail)}</div><div class="tiers"><span>قطاعي: ${money(p.retail)}</span><span>جملة من ${p.wholesaleQty}: ${money(p.wholesale)}</span><span>جملة الجملة من ${p.bulkQty}: ${money(p.bulk)}</span></div><p><b>العلامة:</b> ${escapeHtml(p.brand)} — <b>الوحدة:</b> ${escapeHtml(p.unit)} — <b>التجهيز:</b> ${p.prepDays} يوم</p><p><b>المخزون:</b> ${p.stock} — <b>أقصى طلب:</b> ${p.maxQty}</p><div class="qty-add"><input id="productQty" type="number" min="1" max="${p.maxQty}" value="1"><button class="btn primary" data-action="add-cart-detail" data-product="${p.id}">أضف للسلة</button></div><hr style="border:0;border-top:1px solid var(--line);margin:22px 0"><div class="vendor-card"><img class="avatar" src="${vendor.logo}" alt=""><div><b>${escapeHtml(vendor.storeName)}</b><div>${vendor.governorate} - ${vendor.city}</div><a href="#/vendor/${vendor.id}">زيارة متجر المورد</a></div></div></div></div>`;
}

function vendorsView(){
  const qs=new URLSearchParams(currentPath().split('?')[1]||''); const gov=qs.get('governorate')||''; const city=qs.get('city')||''; const q=qs.get('q')||'';
  let vendors=getState().users.filter(u=>u.role==='vendor'&&u.status==='approved');
  if(gov) vendors=vendors.filter(v=>v.governorate===gov); if(city) vendors=vendors.filter(v=>v.city===city); if(q){const s=q.toLowerCase();vendors=vendors.filter(v=>`${v.storeName} ${v.name} ${v.businessType}`.toLowerCase().includes(s));}
  const cities=gov?governorates[gov]||[]:[];
  return `<section class="page-hero"><h1>دليل الموردين</h1><p>ابحث عن المورد حسب المحافظة والمركز ونوع النشاط.</p></section><form class="toolbar" style="grid-template-columns:2fr 1fr 1fr auto" data-form="vendors-filter"><label>بحث<input name="q" value="${escapeHtml(q)}" placeholder="اسم المورد أو النشاط"></label><label>المحافظة<select name="governorate">${governorateOptions(gov,true)}</select></label><label>المركز<select name="city"><option value="">كل المراكز</option>${cities.map(c=>`<option ${city===c?'selected':''}>${c}</option>`).join('')}</select></label><button class="btn primary">بحث</button></form>
  <div class="grid cols-3">${vendors.map(vendorCard).join('')}</div>`;
}
function vendorCard(v){
  const count=getState().products.filter(p=>p.vendorId===v.id&&p.status==='approved').length;
  return `<article class="card hover"><div class="vendor-cover"><img src="${v.cover||svgData('📦')}" alt=""></div><div class="vendor-card"><img class="avatar" src="${v.logo||svgData('🏪')}" alt=""><div><h3 style="margin:0">${escapeHtml(v.storeName)}</h3><span>${v.verified?'✅ مورد موثّق':'مورد'}</span><p>${escapeHtml(v.governorate)} - ${escapeHtml(v.city)}</p></div></div><p>${escapeHtml(v.description||v.businessType||'')}</p><div class="tiers"><span>${count} منتج</span><span>الحد الأدنى للطلب: ${money(v.minOrder)}</span><span>مدة التوصيل المتوقعة: 2–4 أيام</span></div><a class="btn primary block" href="#/vendor/${v.id}">فتح متجر المورد</a></article>`;
}
function vendorStoreView(id){
  const v=userById(id); if(!v||v.role!=='vendor'||v.status!=='approved') return notFound('المورد غير موجود أو غير معتمد.');
  const products=getState().products.filter(p=>p.vendorId===v.id&&p.status==='approved');
  return `<section class="store-hero"><div class="store-cover"><img src="${v.cover||svgData('📦')}" alt=""></div><div class="store-info"><img class="store-logo" src="${v.logo||svgData('🏪')}" alt=""><div><h1>${escapeHtml(v.storeName)}</h1><p>${escapeHtml(v.governorate)} - ${escapeHtml(v.city)} · ${v.verified?'مورد موثّق':'مورد'}</p><p>${escapeHtml(v.description||'')}</p></div></div></section><div class="section-head"><div><h2>منتجات المورد</h2><p>الحد الأدنى للطلب من هذا المورد: ${money(v.minOrder)}</p></div></div>${products.length?`<div class="grid cols-4">${products.map(productCard).join('')}</div>`:`<div class="empty">لا توجد منتجات منشورة حاليًا.</div>`}`;
}

function authView(mode='login'){
  const user=currentUser(); if(user){ route(dashboardPath(user)); return '<div></div>'; }
  const isLogin=mode==='login'; const isVendor=mode==='vendor';
  return `<section class="auth-shell"><div class="auth-side"><div><span class="kicker">Tager Marketplace</span><h2>${isLogin?'مرحبًا بعودتك':isVendor?'ابدأ البيع على تاجر':'تسوق بسهولة وبأسعار أفضل'}</h2><p>${isLogin?'سجّل الدخول برقم الهاتف أو البريد الإلكتروني إن كان موجودًا.':isVendor?'أنشئ حساب المورد ثم انتظر موافقة الإدارة قبل إضافة المنتجات.':'رقم الهاتف إجباري، والبريد الإلكتروني اختياري.'}</p><ul><li>قطاعي وجملة وجملة الجملة</li><li>لوحة مستقلة لكل مستخدم</li><li>مراجعة الموردين والمنتجات</li></ul></div><div class="notice warning">بيانات الاختبار موجودة أسفل النموذج.</div></div><div class="auth-form"><h1>${isLogin?'تسجيل الدخول':isVendor?'تسجيل مورد جديد':'تسجيل عميل جديد'}</h1><p class="form-help">استخدم رقم هاتف مصري صحيح مثل 01012345678.</p><div class="auth-tabs"><a class="${isLogin?'active':''}" href="#/login">دخول</a><a class="${!isLogin&&!isVendor?'active':''}" href="#/register/customer">عميل</a><a class="${isVendor?'active':''}" href="#/register/vendor">مورد</a></div>${isLogin?loginForm():registerForm(isVendor)}${isLogin?`<div class="notice"><b>حسابات جاهزة:</b><br>أدمن: 01000000000 / Admin@123<br>مورد: 01111111111 / Vendor@123<br>عميل: 01222222222 / Customer@123</div>`:''}</div></section>`;
}
function loginForm(){return `<form data-form="login"><label>رقم الهاتف أو البريد<input name="identifier" required autocomplete="username"></label><label>كلمة المرور<input name="password" type="password" required autocomplete="current-password"></label><button class="btn primary block">دخول</button><a style="text-align:center;margin-top:10px" href="#/forgot-password">نسيت كلمة المرور؟</a></form>`;}
function registerForm(isVendor){return `<form data-form="register" data-role="${isVendor?'vendor':'customer'}"><div class="form-grid"><label>الاسم الكامل<input name="name" required></label><label>رقم الهاتف<input name="phone" required placeholder="01012345678"></label><label>البريد الإلكتروني (اختياري)<input name="email" type="email"></label><label>كلمة المرور<input name="password" type="password" required minlength="8"></label>${isVendor?`<label>اسم المتجر<input name="storeName" required></label><label>نوع النشاط<input name="businessType" required></label>`:''}<label>المحافظة<select name="governorate" required>${governorateOptions('',false)}</select></label><label>المركز / المدينة<select name="city" required><option value="">اختر المحافظة أولًا</option></select></label>${isVendor?`<label>الحد الأدنى للطلب بالجنيه<input name="minOrder" type="number" min="0" value="500" required></label><label>وصف النشاط<textarea name="description" required></textarea></label>`:`<label style="grid-column:1/-1">العنوان التفصيلي<textarea name="address"></textarea></label>`}</div><label style="display:flex;grid-template-columns:auto 1fr;align-items:center"><input type="checkbox" name="terms" required style="width:auto"> أوافق على الشروط وسياسة الخصوصية</label><button class="btn primary block">إنشاء الحساب</button></form>`;}
function forgotPasswordView(){return `<section class="form-card"><h1>استعادة كلمة المرور</h1><p>في النسخة التجريبية يمكنك إعادة كلمة المرور باستخدام رقم الهاتف، بدون إرسال SMS.</p><form data-form="forgot"><label>رقم الهاتف<input name="phone" required></label><label>كلمة المرور الجديدة<input name="password" type="password" minlength="8" required></label><button class="btn primary">تغيير كلمة المرور</button></form></section>`;}

function customerView(){
  if(!requireRole(['customer'])) return '';
  const u=currentUser(); const state=getState(); const orders=state.orders.filter(o=>o.customerId===u.id); const spent=orders.reduce((s,o)=>s+o.total,0);
  return dashboardLayout('customer','لوحة العميل',`<div class="kpis"><div class="kpi"><span>عدد الطلبات</span><b>${orders.length}</b></div><div class="kpi"><span>طلبات نشطة</span><b>${orders.filter(o=>!['completed','cancelled'].includes(o.status)).length}</b></div><div class="kpi"><span>إجمالي المشتريات</span><b>${money(spent)}</b></div><div class="kpi"><span>المحافظة</span><b style="font-size:19px">${escapeHtml(u.governorate||'-')}</b></div></div><div class="grid cols-2"><section class="card"><h2>آخر الطلبات</h2>${orders.length?orders.slice(-5).reverse().map(orderMini).join(''):'<div class="empty">لا توجد طلبات بعد.</div>'}</section><section class="card"><h2>بيانات الحساب</h2><form data-form="customer-profile"><div class="form-grid"><label>الاسم<input name="name" value="${escapeHtml(u.name)}" required></label><label>الهاتف<input name="phone" value="${escapeHtml(u.phone)}" required></label><label>البريد اختياري<input name="email" type="email" value="${escapeHtml(u.email||'')}"></label><label>المحافظة<select name="governorate">${governorateOptions(u.governorate,false)}</select></label><label>المركز<input name="city" value="${escapeHtml(u.city||'')}"></label><label>العنوان<input name="address" value="${escapeHtml(u.address||'')}"></label></div><button class="btn primary">حفظ البيانات</button></form></section></div>`);
}
function orderMini(o){return `<div class="summary-row"><span>#${o.id.slice(-6)} · <span class="status ${o.status}">${statusLabel(o.status)}</span></span><b>${money(o.total)}</b></div>`;}

function vendorDashboardView(tab='overview'){
  if(!requireRole(['vendor'])) return '';
  const v=currentUser(); if(v.status!=='approved') return dashboardLayout('vendor','لوحة المورد',`<div class="notice warning"><h2>الحساب تحت المراجعة</h2><p>يمكنك تعديل بيانات المتجر، لكن إضافة المنتجات ستكون متاحة بعد موافقة الإدارة.</p></div>${vendorSettingsForm(v)}`);
  const state=getState(); const products=state.products.filter(p=>p.vendorId===v.id); const orderRows=vendorOrders(v.id); const sales=orderRows.reduce((s,o)=>s+o.vendorSubtotal,0);
  let content='';
  if(tab==='products') content=vendorProductsView(v,products);
  else if(tab==='add-product') content=vendorProductForm();
  else if(tab==='orders') content=vendorOrdersView(v.id);
  else if(tab==='settings') content=vendorSettingsForm(v);
  else content=`<div class="kpis"><div class="kpi"><span>المنتجات</span><b>${products.length}</b></div><div class="kpi"><span>منشور</span><b>${products.filter(p=>p.status==='approved').length}</b></div><div class="kpi"><span>تحت المراجعة</span><b>${products.filter(p=>p.status==='pending').length}</b></div><div class="kpi"><span>المبيعات</span><b>${money(sales)}</b></div></div><div class="grid cols-2"><section class="card"><h2>اختصارات</h2><div class="vendor-actions"><a class="btn primary" href="#/vendor-dashboard/add-product">إضافة منتج</a><a class="btn secondary" href="#/vendor-dashboard/products">إدارة المنتجات</a><a class="btn secondary" href="#/market">مشاهدة السوق</a><a class="btn secondary" href="#/vendor/${v.id}">متجري</a></div></section><section class="card"><h2>حالة المتجر</h2><div class="summary-row"><span>حالة الحساب</span><span class="status approved">معتمد</span></div><div class="summary-row"><span>الحد الأدنى للطلب</span><b>${money(v.minOrder)}</b></div><div class="summary-row"><span>عمولة المنصة</span><b>${v.commission??getState().settings.globalCommission}%</b></div><div class="summary-row"><span>رسوم السلة المميزة</span><b>${v.premiumFee??getState().settings.premiumCartFee}%</b></div></section></div>`;
  return dashboardLayout('vendor','لوحة المورد',content,tab);
}
function vendorProductsView(v,products){
  return `<div class="section-head"><div><h2>منتجاتي</h2><p>كل تعديل يعيد المنتج للمراجعة.</p></div><a class="btn primary" href="#/vendor-dashboard/add-product">إضافة منتج</a></div>${products.length?`<div class="table-wrap"><table><thead><tr><th>المنتج</th><th>قطاعي</th><th>جملة</th><th>جملة الجملة</th><th>المخزون</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>${products.map(p=>`<tr><td><div style="display:flex;gap:10px;align-items:center"><img class="avatar" src="${p.image}"><b>${escapeHtml(productName(p))}</b></div></td><td>${money(p.retail)}</td><td>${money(p.wholesale)}</td><td>${money(p.bulk)}</td><td>${p.stock}</td><td><span class="status ${p.status}">${statusLabel(p.status)}</span></td><td><button class="btn small secondary" data-action="edit-product" data-id="${p.id}">تعديل</button> <button class="btn small danger" data-action="delete-product" data-id="${p.id}">حذف</button></td></tr>`).join('')}</tbody></table></div>`:`<div class="empty">لم تضف أي منتج بعد.</div>`}`;
}
function vendorProductForm(p=null){
  const edit=!!p;
  return `<section class="form-card" style="max-width:none"><h2>${edit?'تعديل المنتج':'إضافة منتج جديد'}</h2><p class="form-help">المنتج سيُرسل لمراجعة الإدارة قبل ظهوره في السوق.</p><form data-form="vendor-product"><input type="hidden" name="id" value="${p?.id||''}"><div class="form-grid"><label>الاسم بالعربي<input name="nameAr" value="${escapeHtml(p?.nameAr||'')}" required></label><label>الاسم بالإنجليزي<input name="nameEn" value="${escapeHtml(p?.nameEn||'')}" required></label><label>القسم<select name="category" required>${categories.map(([c])=>`<option ${p?.category===c?'selected':''}>${c}</option>`).join('')}</select></label><label>العلامة التجارية<input name="brand" value="${escapeHtml(p?.brand||'')}"></label><label>وحدة البيع<input name="unit" value="${escapeHtml(p?.unit||'قطعة')}" required></label><label>مدة التجهيز بالأيام<input type="number" name="prepDays" min="0" value="${p?.prepDays??1}" required></label><label>سعر القطاعي<input type="number" step="0.01" name="retail" min="0" value="${p?.retail??''}" required></label><label>سعر الجملة<input type="number" step="0.01" name="wholesale" min="0" value="${p?.wholesale??''}" required></label><label>سعر جملة الجملة<input type="number" step="0.01" name="bulk" min="0" value="${p?.bulk??''}" required></label><label>حد الجملة<input type="number" name="wholesaleQty" min="2" value="${p?.wholesaleQty??10}" required></label><label>حد جملة الجملة<input type="number" name="bulkQty" min="3" value="${p?.bulkQty??50}" required></label><label>المخزون<input type="number" name="stock" min="0" value="${p?.stock??0}" required></label><label>أقصى كمية للطلب<input type="number" name="maxQty" min="1" value="${p?.maxQty??100}" required></label><label>صورة المنتج<input type="file" name="imageFile" accept="image/jpeg,image/png,image/webp"><small class="form-help">حتى 1.5 ميجابايت في النسخة التجريبية</small></label><label style="grid-column:1/-1">الوصف<textarea name="description" required>${escapeHtml(p?.description||'')}</textarea></label></div><input type="hidden" name="existingImage" value="${p?.image||''}"><button class="btn primary">${edit?'حفظ وإعادة الإرسال للمراجعة':'حفظ وإرسال للمراجعة'}</button></form></section>`;
}
function vendorOrders(vendorId){
  return getState().orders.map(o=>{const items=o.items.filter(i=>productById(i.productId)?.vendorId===vendorId);return items.length?{...o,vendorItems:items,vendorSubtotal:items.reduce((s,i)=>s+i.price*i.qty,0)}:null}).filter(Boolean);
}
function vendorOrdersView(vendorId){
  const rows=vendorOrders(vendorId);
  return `<h2>طلبات متجري</h2>${rows.length?`<div class="table-wrap"><table><thead><tr><th>الطلب</th><th>العميل</th><th>الأصناف</th><th>القيمة</th><th>الحالة</th><th>تحديث</th></tr></thead><tbody>${rows.map(o=>`<tr><td>#${o.id.slice(-6)}</td><td>${escapeHtml(userById(o.customerId)?.name||'')}</td><td>${o.vendorItems.length}</td><td>${money(o.vendorSubtotal)}</td><td><span class="status ${o.status}">${statusLabel(o.status)}</span></td><td><select data-order-status="${o.id}"><option value="confirmed" ${o.status==='confirmed'?'selected':''}>مؤكد</option><option value="processing" ${o.status==='processing'?'selected':''}>جاري التجهيز</option><option value="shipped" ${o.status==='shipped'?'selected':''}>تم الشحن</option><option value="completed" ${o.status==='completed'?'selected':''}>مكتمل</option></select></td></tr>`).join('')}</tbody></table></div>`:`<div class="empty">لا توجد طلبات لمتجرك حتى الآن.</div>`}`;
}
function vendorSettingsForm(v){
  return `<section class="form-card" style="max-width:none"><h2>إعدادات المتجر</h2><form data-form="vendor-settings"><div class="form-grid"><label>اسم المتجر<input name="storeName" value="${escapeHtml(v.storeName||'')}" required></label><label>اسم المسؤول<input name="name" value="${escapeHtml(v.name||'')}" required></label><label>الهاتف<input name="phone" value="${escapeHtml(v.phone||'')}" required></label><label>البريد اختياري<input name="email" type="email" value="${escapeHtml(v.email||'')}"></label><label>المحافظة<select name="governorate">${governorateOptions(v.governorate,false)}</select></label><label>المركز<input name="city" value="${escapeHtml(v.city||'')}"></label><label>الحد الأدنى للطلب<input name="minOrder" type="number" min="0" value="${v.minOrder||0}"></label><label>نوع النشاط<input name="businessType" value="${escapeHtml(v.businessType||'')}"></label><label>شعار المتجر<input name="logoFile" type="file" accept="image/jpeg,image/png,image/webp"></label><label>صورة الغلاف<input name="coverFile" type="file" accept="image/jpeg,image/png,image/webp"></label><label style="grid-column:1/-1">وصف المتجر<textarea name="description">${escapeHtml(v.description||'')}</textarea></label></div><button class="btn primary">حفظ بيانات المتجر</button></form></section>`;
}

function adminView(tab='overview'){
  if(!requireRole(['admin','staff'])) return '';
  const state=getState(); const pendingVendors=state.users.filter(u=>u.role==='vendor'&&u.status==='pending'); const pendingProducts=state.products.filter(p=>p.status==='pending');
  let content='';
  if(tab==='vendors') content=adminVendorsView();
  else if(tab==='products') content=adminProductsView();
  else if(tab==='orders') content=adminOrdersView();
  else if(tab==='settings') content=adminSettingsView();
  else if(tab==='team') content=adminTeamView();
  else content=`<div class="kpis"><div class="kpi"><span>موردون منتظرون</span><b>${pendingVendors.length}</b></div><div class="kpi"><span>منتجات للمراجعة</span><b>${pendingProducts.length}</b></div><div class="kpi"><span>الطلبات</span><b>${state.orders.length}</b></div><div class="kpi"><span>إجمالي المبيعات</span><b>${money(state.orders.reduce((s,o)=>s+o.total,0))}</b></div></div><div class="grid cols-2"><section class="card"><h2>الموافقات المطلوبة</h2><div class="summary-row"><span>طلبات الموردين</span><a href="#/admin/vendors">${pendingVendors.length}</a></div><div class="summary-row"><span>المنتجات</span><a href="#/admin/products">${pendingProducts.length}</a></div></section><section class="card"><h2>اختصارات الإدارة</h2><div class="vendor-actions"><a class="btn primary" href="#/admin/vendors">إدارة الموردين</a><a class="btn secondary" href="#/admin/products">مراجعة المنتجات</a><a class="btn secondary" href="#/admin/settings">العمولات والشحن</a><a class="btn secondary" href="#/admin/team">فريق الإدارة</a></div></section></div>`;
  return dashboardLayout('admin','بوابة الإدارة',content,tab);
}
function adminVendorsView(){
  const vendors=getState().users.filter(u=>u.role==='vendor');
  return `<h2>إدارة الموردين</h2><div class="table-wrap"><table><thead><tr><th>المورد</th><th>الموقع</th><th>الحد الأدنى</th><th>العمولة</th><th>السلة المميزة</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>${vendors.map(v=>`<tr><td><b>${escapeHtml(v.storeName||v.name)}</b><br><small>${escapeHtml(v.phone)}</small></td><td>${escapeHtml(v.governorate||'-')} / ${escapeHtml(v.city||'-')}</td><td><input style="width:110px" type="number" value="${v.minOrder||0}" data-vendor-min="${v.id}"></td><td><input style="width:80px" type="number" step="0.1" value="${v.commission??getState().settings.globalCommission}" data-vendor-commission="${v.id}">%</td><td><input style="width:80px" type="number" step="0.1" value="${v.premiumFee??getState().settings.premiumCartFee}" data-vendor-premium="${v.id}">%</td><td><span class="status ${v.status}">${statusLabel(v.status)}</span></td><td>${v.status!=='approved'?`<button class="btn small primary" data-action="vendor-status" data-id="${v.id}" data-status="approved">قبول</button>`:''} ${v.status!=='rejected'?`<button class="btn small danger" data-action="vendor-status" data-id="${v.id}" data-status="rejected">رفض</button>`:''} <button class="btn small secondary" data-action="save-vendor-rules" data-id="${v.id}">حفظ</button></td></tr>`).join('')}</tbody></table></div>`;
}
function adminProductsView(){
  const products=getState().products;
  return `<h2>مراجعة المنتجات</h2><div class="table-wrap"><table><thead><tr><th>المنتج</th><th>المورد</th><th>الأسعار</th><th>المخزون</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>${products.map(p=>`<tr><td><div style="display:flex;gap:10px;align-items:center"><img class="avatar" src="${p.image}"><b>${escapeHtml(p.nameAr)}</b></div></td><td>${escapeHtml(userById(p.vendorId)?.storeName||'')}</td><td>${money(p.retail)} / ${money(p.wholesale)} / ${money(p.bulk)}</td><td>${p.stock}</td><td><span class="status ${p.status}">${statusLabel(p.status)}</span></td><td>${p.status!=='approved'?`<button class="btn small primary" data-action="product-status" data-id="${p.id}" data-status="approved">نشر</button>`:''} ${p.status!=='rejected'?`<button class="btn small danger" data-action="product-status" data-id="${p.id}" data-status="rejected">رفض</button>`:''}</td></tr>`).join('')}</tbody></table></div>`;
}
function adminOrdersView(){
  const orders=getState().orders;
  return `<h2>إدارة الطلبات</h2>${orders.length?`<div class="table-wrap"><table><thead><tr><th>الطلب</th><th>العميل</th><th>المحافظة</th><th>الدفع</th><th>الإجمالي</th><th>الحالة</th></tr></thead><tbody>${orders.map(o=>`<tr><td>#${o.id.slice(-6)}<br><small>${dateText(o.createdAt)}</small></td><td>${escapeHtml(userById(o.customerId)?.name||'')}</td><td>${escapeHtml(o.governorate)} / ${escapeHtml(o.city)}</td><td>${paymentLabel(o.payment)}</td><td>${money(o.total)}</td><td><select data-admin-order-status="${o.id}">${['new','confirmed','processing','shipped','completed','cancelled'].map(s=>`<option value="${s}" ${o.status===s?'selected':''}>${statusLabel(s)}</option>`).join('')}</select></td></tr>`).join('')}</tbody></table></div>`:`<div class="empty">لا توجد طلبات حتى الآن.</div>`}`;
}
function adminSettingsView(){
  const s=getState().settings;
  return `<section class="form-card" style="max-width:none"><h2>إعدادات العمولات والسلة والشحن</h2><form data-form="admin-settings"><div class="form-grid"><label>العمولة العامة للمنصة %<input type="number" step="0.1" min="0" max="100" name="globalCommission" value="${s.globalCommission}"></label><label>رسوم السلة المميزة %<input type="number" step="0.1" min="0" max="100" name="premiumCartFee" value="${s.premiumCartFee}"></label><label>رسوم الشحن الافتراضية<input type="number" step="0.01" min="0" name="defaultShipping" value="${s.defaultShipping}"></label><label>حد الشحن المجاني<input type="number" step="0.01" min="0" name="freeShippingThreshold" value="${s.freeShippingThreshold}"></label></div><button class="btn primary">حفظ الإعدادات</button></form><hr style="border:0;border-top:1px solid var(--line);margin:25px 0"><button class="btn danger" data-action="reset-demo">إعادة بيانات النسخة التجريبية</button></section>`;
}
function adminTeamView(){
  const state=getState(); const staff=state.users.filter(u=>u.role==='staff');
  return `<div class="grid cols-2"><section class="form-card" style="margin:0"><h2>إضافة موظف إدارة</h2><form data-form="admin-team"><label>الاسم<input name="name" required></label><label>رقم الهاتف<input name="phone" required></label><label>البريد اختياري<input name="email" type="email"></label><label>كلمة المرور<input name="password" type="password" minlength="8" required></label><label>الدور<select name="staffRole"><option>مدير العمليات</option><option>مسؤول الموردين</option><option>مسؤول المنتجات</option><option>مسؤول الطلبات</option><option>المسؤول المالي</option><option>خدمة العملاء</option><option>قراءة فقط</option></select></label><button class="btn primary">إضافة الموظف</button></form></section><section class="card"><h2>فريق الإدارة</h2>${staff.length?staff.map(s=>`<div class="summary-row"><span><b>${escapeHtml(s.name)}</b><br><small>${escapeHtml(s.staffRole||'موظف')}</small></span><span>${escapeHtml(s.phone)}</span></div>`).join(''):'<div class="empty">لا يوجد موظفون إضافيون.</div>'}</section></div>`;
}

function dashboardLayout(type,title,content,tab='overview'){
  const menus={
    customer:[['overview','لوحة الحساب','/customer'],['orders','طلباتي','/customer/orders'],['profile','الملف الشخصي','/customer']],
    vendor:[['overview','الرئيسية','/vendor-dashboard'],['products','المنتجات','/vendor-dashboard/products'],['add-product','إضافة منتج','/vendor-dashboard/add-product'],['orders','الطلبات','/vendor-dashboard/orders'],['settings','إعدادات المتجر','/vendor-dashboard/settings']],
    admin:[['overview','نظرة عامة','/admin'],['vendors','الموردون','/admin/vendors'],['products','المنتجات','/admin/products'],['orders','الطلبات','/admin/orders'],['settings','العمولات والشحن','/admin/settings'],['team','فريق الإدارة','/admin/team']]
  };
  return `<section class="page-hero"><h1>${title}</h1><p>${escapeHtml(currentUser()?.name||'')}</p></section><div class="dashboard"><aside class="sidebar"><h3>${title}</h3>${menus[type].map(([key,label,path])=>`<a class="${tab===key?'active':''}" href="#${path}">${label}</a>`).join('')}<a href="#/market">مشاهدة السوق</a><a href="#/" data-action="logout">تسجيل الخروج</a></aside><div class="dash-content">${content}</div></div>`;
}

function cartView(){
  const cart=getCart(); const state=getState(); const mode=localStorage.getItem('tager-cart-mode')||'separated';
  const items=cart.map(i=>{const p=productById(i.productId);if(!p)return null;const tier=tierPrice(p,i.qty);return {...i,p,tier,subtotal:tier.price*i.qty,vendor:userById(p.vendorId)}}).filter(Boolean);
  const groups=Object.values(items.reduce((acc,i)=>{const id=i.p.vendorId;acc[id]??={vendor:i.vendor,items:[],subtotal:0};acc[id].items.push(i);acc[id].subtotal+=i.subtotal;return acc},{}));
  const subtotal=items.reduce((s,i)=>s+i.subtotal,0); const premiumFee=mode==='premium'?groups.reduce((s,g)=>s+g.subtotal*((g.vendor?.premiumFee??state.settings.premiumCartFee)/100),0):0;
  const shipping=subtotal>=state.settings.freeShippingThreshold?0:(items.length?state.settings.defaultShipping:0); const total=subtotal+premiumFee+shipping;
  const issues=mode==='separated'?groups.filter(g=>g.subtotal<Number(g.vendor?.minOrder||0)):[];
  return `<section class="page-hero"><h1>سلة المشتريات</h1><p>اختر سلة الموردين المنفصلة أو السلة المميزة المختلطة.</p></section>${items.length?`<div class="cart-layout"><div class="cart-groups">${groups.map(g=>`<section class="card"><div class="section-head"><div><h3>${escapeHtml(g.vendor?.storeName||'مورد')}</h3><p>الحد الأدنى: ${money(g.vendor?.minOrder||0)} — إجمالي المورد: ${money(g.subtotal)}</p></div><span class="status ${g.subtotal>=Number(g.vendor?.minOrder||0)?'approved':'pending'}">${g.subtotal>=Number(g.vendor?.minOrder||0)?'مكتمل':'لم يكتمل الحد'}</span></div>${g.items.map(i=>`<div class="cart-item"><img src="${i.p.image}"><div><b>${escapeHtml(productName(i.p))}</b><br><small>${tierLabel(i.tier.tier)} · ${money(i.tier.price)} للوحدة</small></div><input type="number" min="1" max="${i.p.maxQty}" value="${i.qty}" data-cart-qty="${i.p.id}"><div class="item-price"><b>${money(i.subtotal)}</b></div><button class="btn small danger" data-action="remove-cart" data-id="${i.p.id}">حذف</button></div>`).join('')}</section>`).join('')}</div><aside class="card cart-summary"><h2>ملخص الطلب</h2><div class="choice-grid"><label class="choice-card ${mode==='separated'?'active':''}"><input type="radio" name="cartMode" value="separated" ${mode==='separated'?'checked':''}> <b>سلة الموردين المنفصلة</b><small>تحقيق الحد الأدنى لكل مورد.</small></label><label class="choice-card ${mode==='premium'?'active':''}"><input type="radio" name="cartMode" value="premium" ${mode==='premium'?'checked':''}> <b>السلة المميزة</b><small>تجمع كل الموردين برسوم خدمة.</small></label></div>${issues.length?`<div class="notice error">لم يكتمل الحد الأدنى لدى: ${issues.map(g=>g.vendor.storeName).join('، ')}. اختر السلة المميزة أو زد الكميات.</div>`:''}<div class="summary-row"><span>قيمة المنتجات</span><b>${money(subtotal)}</b></div><div class="summary-row"><span>رسوم السلة المميزة</span><b>${money(premiumFee)}</b></div><div class="summary-row"><span>الشحن</span><b>${shipping?money(shipping):'مجاني'}</b></div><div class="summary-row total"><span>الإجمالي</span><b>${money(total)}</b></div><button class="btn primary block" data-action="checkout" ${issues.length&&mode==='separated'?'disabled':''}>متابعة إتمام الطلب</button></aside></div>`:`<div class="empty"><h2>السلة فارغة</h2><a class="btn primary" href="#/market">ابدأ التسوق</a></div>`}`;
}
function checkoutModal(){
  const user=currentUser(); if(!user){toast('سجّل الدخول كعميل لإتمام الطلب','error');route('/login');return}
  if(user.role!=='customer'){toast('إتمام الطلب متاح لحساب العميل فقط','error');return}
  const mode=localStorage.getItem('tager-cart-mode')||'separated';
  document.body.insertAdjacentHTML('beforeend',`<div class="modal-backdrop" id="checkoutModal"><div class="modal"><div class="modal-head"><h2>إتمام الطلب</h2><button class="close-btn" data-action="close-modal">×</button></div><form data-form="checkout"><div class="form-grid"><label>المحافظة<select name="governorate" required>${governorateOptions(user.governorate,false)}</select></label><label>المركز / المدينة<input name="city" value="${escapeHtml(user.city||'')}" required></label><label style="grid-column:1/-1">العنوان التفصيلي<textarea name="address" required>${escapeHtml(user.address||'')}</textarea></label><label>طريقة الدفع<select name="payment"><option value="cod">الدفع عند الاستلام</option><option value="bank">تحويل بنكي</option><option value="instapay">InstaPay</option><option value="wallet">محفظة إلكترونية</option><option value="card">بطاقة بنكية (تجريبي)</option></select></label><label>نوع الفاتورة<select name="invoice"><option value="personal">فردية</option><option value="company">شركة</option></select></label></div><input type="hidden" name="mode" value="${mode}"><div class="notice">هذه نسخة اختبار؛ لن يتم تحصيل أموال حقيقية.</div><button class="btn primary block">تأكيد وإنشاء الطلب</button></form></div></div>`);
}

function howView(){return `<section class="page-hero"><h1>كيف تعمل منصة تاجر؟</h1><p>رحلة واضحة للمشتري والمورد والإدارة.</p></section><div class="grid cols-3"><article class="card"><h2>1. إنشاء حساب</h2><p>العميل أو المورد يسجل برقم الهاتف، والبريد اختياري.</p></article><article class="card"><h2>2. اعتماد المورد</h2><p>الإدارة تراجع بيانات المورد وتحدد العمولة والحد الأدنى.</p></article><article class="card"><h2>3. إضافة المنتجات</h2><p>المورد يضيف الصور والأسعار والمخزون، ثم تنتظر المنتجات المراجعة.</p></article><article class="card"><h2>4. التسوق</h2><p>العميل يبحث ويختار قطاعي أو جملة أو جملة الجملة حسب الكمية.</p></article><article class="card"><h2>5. اختيار السلة</h2><p>سلة منفصلة لكل مورد أو سلة مميزة تجمع الأصناف برسوم تحددها الإدارة.</p></article><article class="card"><h2>6. متابعة الطلب</h2><p>العميل والمورد والإدارة يتابعون الحالة من لوحاتهم.</p></article></div>`;}
function policiesView(){return `<section class="page-hero"><h1>الشروط والسياسات</h1><p>محتوى تجريبي يحتاج مراجعة قانونية قبل الإطلاق التجاري.</p></section><div class="grid cols-2"><article class="card"><h2>سياسة الطلب</h2><p>تحدد المنصة حدود الكميات والأسعار والشحن وفق بيانات كل مورد.</p></article><article class="card"><h2>الاسترجاع</h2><p>يمكن طلب الاسترجاع وفق حالة المنتج وسياسة المورد والمنصة.</p></article><article class="card"><h2>الخصوصية</h2><p>في النسخة التجريبية تحفظ البيانات داخل المتصفح. قبل الإطلاق تُنقل إلى قاعدة بيانات مؤمنة.</p></article><article class="card"><h2>الدفع</h2><p>طرق الدفع الظاهرة تجريبية ولا تنفذ خصمًا حقيقيًا حتى ربط بوابة دفع.</p></article></div>`;}
function contactView(){return `<section class="form-card"><h1>تواصل معنا</h1><p>استخدم النموذج لإرسال استفسار تجريبي.</p><form data-form="contact"><label>الاسم<input name="name" required></label><label>الهاتف<input name="phone" required></label><label>الرسالة<textarea name="message" required></textarea></label><button class="btn primary">إرسال</button></form></section>`;}
function notFound(msg='الصفحة غير موجودة'){return `<div class="empty"><h1>${msg}</h1><a class="btn primary" href="#/">العودة للرئيسية</a></div>`;}

function governorateOptions(selected='',allowAll=false){return `${allowAll?'<option value="">كل المحافظات</option>':'<option value="">اختر المحافظة</option>'}${Object.keys(governorates).map(g=>`<option value="${g}" ${selected===g?'selected':''}>${g}</option>`).join('')}`;}
function statusLabel(s){return ({pending:'تحت المراجعة',approved:'معتمد',rejected:'مرفوض',new:'جديد',confirmed:'مؤكد',processing:'جاري التجهيز',shipped:'تم الشحن',completed:'مكتمل',cancelled:'ملغي'})[s]||s;}
function paymentLabel(p){return ({cod:'الدفع عند الاستلام',bank:'تحويل بنكي',instapay:'InstaPay',wallet:'محفظة إلكترونية',card:'بطاقة بنكية'})[p]||p;}

function render(){
  document.documentElement.lang=getLang();document.documentElement.dir=getLang()==='ar'?'rtl':'ltr';document.body.classList.toggle('ltr',getLang()==='en');
  const parts=routeParts(); const root=parts[0]||''; let html='';
  if(!root) html=homeView();
  else if(root==='market') html=marketView();
  else if(root==='product') html=productView(parts[1]);
  else if(root==='vendors') html=vendorsView();
  else if(root==='vendor'&&parts[1]) html=vendorStoreView(parts[1]);
  else if(root==='login') html=authView('login');
  else if(root==='register'&&parts[1]==='customer') html=authView('customer');
  else if(root==='register'&&parts[1]==='vendor') html=authView('vendor');
  else if(root==='forgot-password') html=forgotPasswordView();
  else if(root==='customer') html=customerView();
  else if(root==='vendor-dashboard') html=vendorDashboardView(parts[1]||'overview');
  else if(root==='admin') html=adminView(parts[1]||'overview');
  else if(root==='cart') html=cartView();
  else if(root==='how') html=howView();
  else if(root==='policies') html=policiesView();
  else if(root==='contact') html=contactView();
  else html=notFound();
  shell(html);
  window.scrollTo({top:0,behavior:'instant'});
}

async function fileToData(file){
  if(!file) return '';
  if(file.size>1.5*1024*1024) throw new Error('حجم الصورة أكبر من 1.5 ميجابايت');
  if(!['image/jpeg','image/png','image/webp'].includes(file.type)) throw new Error('نوع الصورة غير مدعوم');
  return await new Promise((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(r.result);r.onerror=reject;r.readAsDataURL(file)});
}

function addToCart(productId,qty){
  const p=productById(productId); qty=Math.max(1,Math.min(Number(qty)||1,p.maxQty,p.stock));
  const cart=getCart(); const existing=cart.find(i=>i.productId===productId); if(existing) existing.qty=Math.min(existing.qty+qty,p.maxQty,p.stock); else cart.push({productId,qty}); saveCart(cart); toast('تمت إضافة المنتج إلى السلة');
}

function doLogin(form){
  const fd=new FormData(form); const identifier=fd.get('identifier').trim().toLowerCase(); const password=fd.get('password');
  const state=getState(); const phone=normalizePhone(identifier); const user=state.users.find(u=>(u.phone===phone||String(u.email||'').toLowerCase()===identifier)&&u.password===password);
  if(!user) return toast('بيانات الدخول غير صحيحة','error');
  if(user.status==='rejected') return toast('الحساب مرفوض أو موقوف','error');
  setSession(user.id);toast('تم تسجيل الدخول');route(dashboardPath(user));
}
function doRegister(form){
  const fd=new FormData(form); const role=form.dataset.role; const phone=normalizePhone(fd.get('phone')); const email=(fd.get('email')||'').trim().toLowerCase();
  if(!validPhone(phone)) return toast('أدخل رقم هاتف مصري صحيح','error'); if(!validEmail(email)) return toast('البريد الإلكتروني غير صحيح','error');
  const state=getState(); if(state.users.some(u=>u.phone===phone)) return toast('رقم الهاتف مستخدم بالفعل','error'); if(email&&state.users.some(u=>(u.email||'').toLowerCase()===email)) return toast('البريد مستخدم بالفعل','error');
  const user={id:uid('u'),role,name:fd.get('name').trim(),phone,email,password:fd.get('password'),status:role==='vendor'?'pending':'approved',governorate:fd.get('governorate'),city:fd.get('city'),createdAt:Date.now()};
  if(role==='vendor') Object.assign(user,{storeName:fd.get('storeName').trim(),businessType:fd.get('businessType').trim(),minOrder:Number(fd.get('minOrder'))||0,commission:state.settings.globalCommission,premiumFee:state.settings.premiumCartFee,description:fd.get('description').trim(),verified:false,logo:svgData('🏪'),cover:svgData('📦','#07543d','#d9a441')}); else user.address=fd.get('address').trim();
  state.users.push(user);saveState(state);setSession(user.id);toast(role==='vendor'?'تم التسجيل، حسابك تحت مراجعة الإدارة':'تم إنشاء حساب العميل');route(dashboardPath(user));
}

async function saveVendorProduct(form){
  const v=currentUser(); if(!v||v.role!=='vendor'||v.status!=='approved') return toast('يجب اعتماد حساب المورد أولًا','error');
  const fd=new FormData(form); const retail=Number(fd.get('retail')),wholesale=Number(fd.get('wholesale')),bulk=Number(fd.get('bulk')); const wq=Number(fd.get('wholesaleQty')),bq=Number(fd.get('bulkQty'));
  if(!(retail>=wholesale&&wholesale>=bulk)) return toast('يجب أن يكون سعر القطاعي أكبر أو يساوي الجملة، والجملة أكبر أو تساوي جملة الجملة','error');
  if(!(bq>wq)) return toast('حد جملة الجملة يجب أن يكون أكبر من حد الجملة','error');
  let image=fd.get('existingImage')||svgData('📦'); try{const f=fd.get('imageFile');if(f&&f.size) image=await fileToData(f)}catch(e){return toast(e.message,'error')}
  const state=getState(); let p=state.products.find(x=>x.id===fd.get('id')&&x.vendorId===v.id);
  const data={vendorId:v.id,nameAr:fd.get('nameAr').trim(),nameEn:fd.get('nameEn').trim(),category:fd.get('category'),brand:fd.get('brand').trim(),unit:fd.get('unit').trim(),prepDays:Number(fd.get('prepDays')),retail,wholesale,bulk,wholesaleQty:wq,bulkQty:bq,stock:Number(fd.get('stock')),maxQty:Number(fd.get('maxQty')),description:fd.get('description').trim(),image,status:'pending',updatedAt:Date.now()};
  if(p) Object.assign(p,data); else state.products.push({id:uid('p'),...data,createdAt:Date.now()}); saveState(state);toast('تم حفظ المنتج وإرساله للمراجعة');route('/vendor-dashboard/products');
}

function calculateCart(){
  const state=getState(); const mode=localStorage.getItem('tager-cart-mode')||'separated';
  const items=getCart().map(i=>{const p=productById(i.productId);if(!p)return null;const tier=tierPrice(p,i.qty);return {productId:p.id,qty:i.qty,price:tier.price,tier:tier.tier,vendorId:p.vendorId}}).filter(Boolean);
  const subtotal=items.reduce((s,i)=>s+i.price*i.qty,0); const vendors=[...new Set(items.map(i=>i.vendorId))].map(id=>userById(id));
  const premiumFee=mode==='premium'?vendors.reduce((s,v)=>{const vs=items.filter(i=>i.vendorId===v.id).reduce((a,i)=>a+i.price*i.qty,0);return s+vs*((v.premiumFee??state.settings.premiumCartFee)/100)},0):0;
  const shipping=subtotal>=state.settings.freeShippingThreshold?0:(items.length?state.settings.defaultShipping:0);
  return {items,subtotal,premiumFee,shipping,total:subtotal+premiumFee+shipping,mode};
}

function createOrder(form){
  const user=currentUser(); if(!user||user.role!=='customer') return toast('سجّل الدخول كعميل','error');
  const calc=calculateCart(); if(!calc.items.length) return toast('السلة فارغة','error');
  const fd=new FormData(form); const state=getState(); const order={id:uid('ord'),customerId:user.id,items:calc.items,subtotal:calc.subtotal,premiumFee:calc.premiumFee,shipping:calc.shipping,total:calc.total,cartMode:calc.mode,governorate:fd.get('governorate'),city:fd.get('city'),address:fd.get('address').trim(),payment:fd.get('payment'),invoice:fd.get('invoice'),status:'new',createdAt:Date.now(),timeline:[{status:'new',at:Date.now()}]};
  state.orders.push(order); for(const item of calc.items){const p=state.products.find(x=>x.id===item.productId);if(p)p.stock=Math.max(0,p.stock-item.qty)} saveState(state);saveCart([]);document.getElementById('checkoutModal')?.remove();toast('تم إنشاء الطلب بنجاح');route('/customer');
}

function editProductModal(id){
  const p=productById(id); if(!p)return; document.body.insertAdjacentHTML('beforeend',`<div class="modal-backdrop" id="editModal"><div class="modal"><div class="modal-head"><h2>تعديل المنتج</h2><button class="close-btn" data-action="close-modal">×</button></div>${vendorProductForm(p)}</div></div>`);
}

function handleClick(e){
  const el=e.target.closest('[data-action]'); if(!el)return; const action=el.dataset.action;
  if(action==='toggle-menu') document.getElementById('mainNav')?.classList.toggle('open');
  if(action==='toggle-lang') setLang(getLang()==='ar'?'en':'ar');
  if(action==='logout'){e.preventDefault();clearSession();toast('تم تسجيل الخروج');route('/')}
  if(action==='add-cart'){const id=el.dataset.product;const q=document.querySelector(`[data-qty-for="${id}"]`);addToCart(id,q?.value||1)}
  if(action==='add-cart-detail') addToCart(el.dataset.product,document.getElementById('productQty')?.value||1);
  if(action==='remove-cart'){saveCart(getCart().filter(i=>i.productId!==el.dataset.id));render()}
  if(action==='checkout') checkoutModal();
  if(action==='close-modal') el.closest('.modal-backdrop')?.remove();
  if(action==='edit-product') editProductModal(el.dataset.id);
  if(action==='delete-product'){if(confirm('حذف المنتج؟')){const state=getState();state.products=state.products.filter(p=>p.id!==el.dataset.id);saveState(state);render()}}
  if(action==='vendor-status'){const state=getState();const u=state.users.find(x=>x.id===el.dataset.id);if(u){u.status=el.dataset.status;saveState(state);toast('تم تحديث حالة المورد');render()}}
  if(action==='product-status'){const state=getState();const p=state.products.find(x=>x.id===el.dataset.id);if(p){p.status=el.dataset.status;saveState(state);toast('تم تحديث حالة المنتج');render()}}
  if(action==='save-vendor-rules'){const state=getState();const v=state.users.find(x=>x.id===el.dataset.id);if(v){v.minOrder=Number(document.querySelector(`[data-vendor-min="${v.id}"]`).value);v.commission=Number(document.querySelector(`[data-vendor-commission="${v.id}"]`).value);v.premiumFee=Number(document.querySelector(`[data-vendor-premium="${v.id}"]`).value);saveState(state);toast('تم حفظ إعدادات المورد')}}
  if(action==='reset-demo'){if(confirm('سيتم حذف كل التعديلات والطلبات التجريبية. متابعة؟')){localStorage.removeItem(STORAGE_KEY);localStorage.removeItem(CART_KEY);clearSession();toast('تمت إعادة النسخة');route('/')}}
}

async function handleSubmit(e){
  const form=e.target.closest('form'); if(!form)return; e.preventDefault(); const type=form.dataset.form; const btn=form.querySelector('button[type="submit"],button:not([type])'); const original=btn?.textContent;if(btn){btn.disabled=true;btn.textContent='جاري الحفظ...'}
  try{
    if(type==='hero-search'){const q=new FormData(form).get('q');route(`/market?q=${encodeURIComponent(q)}`)}
    if(type==='market-filter'){const fd=new FormData(form);const qs=new URLSearchParams();for(const [k,v] of fd.entries())if(v)qs.set(k,v);route(`/market?${qs}`)}
    if(type==='vendors-filter'){const fd=new FormData(form);const qs=new URLSearchParams();for(const [k,v] of fd.entries())if(v)qs.set(k,v);route(`/vendors?${qs}`)}
    if(type==='login') doLogin(form);
    if(type==='register') doRegister(form);
    if(type==='forgot'){const fd=new FormData(form);const phone=normalizePhone(fd.get('phone'));const state=getState();const u=state.users.find(x=>x.phone===phone);if(!u)toast('رقم الهاتف غير موجود','error');else{u.password=fd.get('password');saveState(state);toast('تم تغيير كلمة المرور');route('/login')}}
    if(type==='customer-profile'){const fd=new FormData(form);const state=getState();const u=state.users.find(x=>x.id===currentUser().id);const phone=normalizePhone(fd.get('phone'));if(!validPhone(phone))toast('رقم الهاتف غير صحيح','error');else if(state.users.some(x=>x.id!==u.id&&x.phone===phone))toast('رقم الهاتف مستخدم','error');else{Object.assign(u,{name:fd.get('name'),phone,email:fd.get('email'),governorate:fd.get('governorate'),city:fd.get('city'),address:fd.get('address')});saveState(state);toast('تم حفظ البيانات');render()}}
    if(type==='vendor-product') await saveVendorProduct(form);
    if(type==='vendor-settings'){const fd=new FormData(form);const state=getState();const v=state.users.find(x=>x.id===currentUser().id);let logo=v.logo,cover=v.cover;try{const lf=fd.get('logoFile'),cf=fd.get('coverFile');if(lf&&lf.size)logo=await fileToData(lf);if(cf&&cf.size)cover=await fileToData(cf)}catch(err){return toast(err.message,'error')}Object.assign(v,{storeName:fd.get('storeName'),name:fd.get('name'),phone:normalizePhone(fd.get('phone')),email:fd.get('email'),governorate:fd.get('governorate'),city:fd.get('city'),minOrder:Number(fd.get('minOrder')),businessType:fd.get('businessType'),description:fd.get('description'),logo,cover});saveState(state);toast('تم حفظ بيانات المتجر');render()}
    if(type==='admin-settings'){const fd=new FormData(form);const state=getState();Object.assign(state.settings,{globalCommission:Number(fd.get('globalCommission')),premiumCartFee:Number(fd.get('premiumCartFee')),defaultShipping:Number(fd.get('defaultShipping')),freeShippingThreshold:Number(fd.get('freeShippingThreshold'))});saveState(state);toast('تم حفظ الإعدادات')}
    if(type==='admin-team'){const fd=new FormData(form);const state=getState();const phone=normalizePhone(fd.get('phone'));if(!validPhone(phone))toast('رقم الهاتف غير صحيح','error');else if(state.users.some(x=>x.phone===phone))toast('رقم الهاتف مستخدم','error');else{state.users.push({id:uid('u'),role:'staff',name:fd.get('name'),phone,email:fd.get('email'),password:fd.get('password'),staffRole:fd.get('staffRole'),status:'approved',createdAt:Date.now()});saveState(state);toast('تم إضافة موظف الإدارة');render()}}
    if(type==='checkout') createOrder(form);
    if(type==='contact'){toast('تم استلام رسالتك التجريبية');form.reset()}
  } finally {if(btn&&document.body.contains(btn)){btn.disabled=false;btn.textContent=original}}
}

function handleChange(e){
  const el=e.target;
  if(el.name==='governorate'){
    const city=el.form?.querySelector('[name="city"]'); if(city&&city.tagName==='SELECT') city.innerHTML=`<option value="">اختر المركز</option>${(governorates[el.value]||[]).map(c=>`<option>${c}</option>`).join('')}`;
  }
  if(el.name==='cartMode'){localStorage.setItem('tager-cart-mode',el.value);render()}
  if(el.dataset.cartQty){const cart=getCart();const item=cart.find(i=>i.productId===el.dataset.cartQty);const p=productById(el.dataset.cartQty);if(item){item.qty=Math.max(1,Math.min(Number(el.value)||1,p.maxQty,p.stock));saveCart(cart);render()}}
  if(el.dataset.orderStatus||el.dataset.adminOrderStatus){const id=el.dataset.orderStatus||el.dataset.adminOrderStatus;const state=getState();const o=state.orders.find(x=>x.id===id);if(o){o.status=el.value;o.timeline.push({status:el.value,at:Date.now()});saveState(state);toast('تم تحديث حالة الطلب');render()}}
}

document.addEventListener('click',handleClick);
document.addEventListener('submit',handleSubmit);
document.addEventListener('change',handleChange);
window.addEventListener('hashchange',render);
window.addEventListener('DOMContentLoaded',()=>{getState();if(!location.hash)location.hash='#/';render()});
