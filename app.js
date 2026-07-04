(function(){
  'use strict';
  const STORE='tager_store_complete_system';
  const SESSION='tager_session_complete_system';
  const CART='tager_cart_complete_system';
  const app=document.getElementById('app');
  const toastRoot=document.getElementById('toastRoot');

  const defaultCategories=[
    {id:'cat-food',name:'أغذية أساسية',icon:'🌾',active:true},
    {id:'cat-drinks',name:'مشروبات',icon:'🥤',active:true},
    {id:'cat-dairy',name:'ألبان',icon:'🥛',active:true},
    {id:'cat-clean',name:'منظفات',icon:'🧼',active:true},
    {id:'cat-paper',name:'ورقيات',icon:'🧻',active:true},
    {id:'cat-cans',name:'معلبات',icon:'🥫',active:true},
    {id:'cat-sweets',name:'حلويات',icon:'🍬',active:true},
    {id:'cat-rest',name:'مستلزمات مطاعم',icon:'🍽️',active:true},
    {id:'cat-veg',name:'خضروات وفاكهة',icon:'🥬',active:true},
    {id:'cat-meat',name:'لحوم ودواجن',icon:'🍗',active:true}
  ];
  const governorates={
    'القاهرة':['مدينة نصر','مصر الجديدة','المعادي','حلوان','التجمع','شبرا','وسط البلد','كل المراكز'],
    'الجيزة':['الدقي','المهندسين','الهرم','فيصل','6 أكتوبر','الشيخ زايد','إمبابة','كل المراكز'],
    'الإسكندرية':['سيدي جابر','سموحة','العجمي','محرم بك','المنتزه','برج العرب','كل المراكز'],
    'القليوبية':['بنها','شبرا الخيمة','القناطر','الخانكة','قليوب','كل المراكز'],
    'الشرقية':['الزقازيق','العاشر من رمضان','بلبيس','منيا القمح','أبو حماد','كل المراكز'],
    'الدقهلية':['المنصورة','طلخا','ميت غمر','دكرنس','السنبلاوين','كل المراكز'],
    'الغربية':['طنطا','المحلة الكبرى','زفتى','كفر الزيات','سمنود','كل المراكز'],
    'المنوفية':['شبين الكوم','منوف','أشمون','السادات','قويسنا','كل المراكز'],
    'البحيرة':['دمنهور','كفر الدوار','رشيد','إدكو','وادي النطرون','كل المراكز'],
    'الإسماعيلية':['الإسماعيلية','فايد','القنطرة','التل الكبير','كل المراكز'],
    'السويس':['السويس','عتاقة','الجناين','الأربعين','كل المراكز'],
    'بورسعيد':['بورفؤاد','الضواحي','العرب','المناخ','كل المراكز'],
    'دمياط':['دمياط','رأس البر','فارسكور','كفر سعد','كل المراكز'],
    'كفر الشيخ':['كفر الشيخ','دسوق','فوه','بيلا','بلطيم','كل المراكز'],
    'الفيوم':['الفيوم','سنورس','طامية','إطسا','كل المراكز'],
    'بني سويف':['بني سويف','الواسطى','ناصر','إهناسيا','كل المراكز'],
    'المنيا':['المنيا','ملوي','سمالوط','أبو قرقاص','كل المراكز'],
    'أسيوط':['أسيوط','ديروط','القوصية','منفلوط','كل المراكز'],
    'سوهاج':['سوهاج','أخميم','جرجا','المنشاة','طهطا','كل المراكز'],
    'قنا':['قنا','نجع حمادي','دشنا','قفط','كل المراكز'],
    'الأقصر':['الأقصر','إسنا','أرمنت','البياضية','كل المراكز'],
    'أسوان':['أسوان','كوم أمبو','إدفو','دراو','كل المراكز'],
    'البحر الأحمر':['الغردقة','سفاجا','رأس غارب','القصير','كل المراكز'],
    'مطروح':['مرسى مطروح','الحمام','العلمين','سيوة','كل المراكز'],
    'شمال سيناء':['العريش','بئر العبد','الشيخ زويد','رفح','كل المراكز'],
    'جنوب سيناء':['شرم الشيخ','طور سيناء','دهب','نويبع','كل المراكز'],
    'الوادي الجديد':['الخارجة','الداخلة','الفرافرة','بلاط','كل المراكز']
  };
  const orderStatuses=['جديد','قيد المراجعة','قيد التجهيز','تم الشحن','تم التسليم','ملغي','مرتجع'];
  const paymentStatuses=['غير مسدد','مسدد جزئي','مسدد','معلق'];
  const supplierStatuses=['قيد المراجعة','معتمد','موقوف','مرفوض'];
  const productStatuses=['قيد المراجعة','منشور','متوقف','مرفوض'];

  function initialState(){
    return {
      settings:{
        brand:'Tager',
        language:'العربية',
        currency:'EGP',
        currencyLabel:'جنيه',
        supportPhones:['+201024237231','+201016135495','+201127512512'],
        whatsapp:'+201127512512',
        email:'support@tager.local',
        country:'مصر',
        defaultCommission:1.5,
        premiumBasketFee:1.5,
        shippingBase:0,
        freeShippingLimit:0,
        settlementCycle:'أسبوعي',
        orderPrefix:'TG',
        invoicePrefix:'INV',
        quotationPrefix:'QTN',
        taxRate:0,
        lowStockThreshold:5,
        returnWindowDays:7,
        paymentMethods:['تحويل بنكي','نقد عند الاستلام','محفظة إلكترونية'],
        allowGuestCheckout:false,
        supplierApprovalRequired:true,
        productApprovalRequired:true,
        terms:'يتم تنفيذ الطلبات بعد مراجعة بيانات المورد والمشتري ومناطق التغطية، وتتم التسوية المالية بعد تأكيد التسليم ومراجعة المرتجعات والخصومات المعتمدة.',
        privacy:'تستخدم البيانات المسجلة لغرض تشغيل الطلبات والتحقق من الحسابات والدعم فقط.'
      },
      content:{
        homeTitle:'رحلة شراء وتوريد راقية من أول اختيار المورد حتى إقفال الحسابات',
        homeText:'منصة عربية منظمة تربط المشترين بالموردين، وتجمع المنتجات والتغطية والطلبات والسداد والتقارير في واجهة واحدة.',
        welcome:'ابدأ بتسجيل الإدارة، ثم اعتماد الموردين والمنتجات، وبعدها تظهر البيانات الحقيقية داخل السوق.',
        supplierMessage:'سجل بيانات المورد ومناطق التغطية والمنتجات، ثم تتم مراجعة الحساب قبل ظهوره للمشترين.',
        buyerMessage:'سجل بيانات النشاط والعنوان لتسهيل الطلبات وتتبع حالة الشراء والسداد.'
      },
      categories:defaultCategories,
      users:[],
      buyers:[],
      suppliers:[],
      products:[],
      orders:[],
      payments:[],
      settlements:[],
      coupons:[],
      returns:[],
      tickets:[],
      invoices:[],
      quotations:[],
      notifications:[],
      documents:[],
      warehouses:[],
      audit:[]
    };
  }

  let db=load();
  let session=readSession();

  function load(){
    try{
      const raw=JSON.parse(localStorage.getItem(STORE));
      if(!raw) return initialState();
      const base=initialState();
      return {
        ...base,
        ...raw,
        settings:{...base.settings,...(raw.settings||{})},
        content:{...base.content,...(raw.content||{})},
        categories:(raw.categories&&raw.categories.length)?raw.categories:base.categories,
        users:raw.users||[], buyers:raw.buyers||[], suppliers:raw.suppliers||[], products:raw.products||[], orders:raw.orders||[], payments:raw.payments||[], settlements:raw.settlements||[], coupons:raw.coupons||[], returns:raw.returns||[], tickets:raw.tickets||[], invoices:raw.invoices||[], quotations:raw.quotations||[], notifications:raw.notifications||[], documents:raw.documents||[], warehouses:raw.warehouses||[], audit:raw.audit||[]
      };
    }catch(e){return initialState();}
  }
  function save(){localStorage.setItem(STORE,JSON.stringify(db));}
  function readSession(){try{return JSON.parse(localStorage.getItem(SESSION))||null}catch(e){return null}}
  function saveSession(){session?localStorage.setItem(SESSION,JSON.stringify(session)):localStorage.removeItem(SESSION)}
  function uid(prefix){return prefix+'-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,8)}
  function today(){return new Date().toLocaleString('ar-EG')}
  function dateOnly(){return new Date().toLocaleDateString('ar-EG')}
  function esc(v){return String(v??'').replace(/[&<>"']/g,function(s){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]})}
  function clean(v){return String(v??'').trim()}
  function money(n){return `${Number(n||0).toLocaleString('ar-EG',{maximumFractionDigits:2})} ${db.settings.currencyLabel||'جنيه'}`}
  function currentUser(){return db.users.find(u=>u.id===session?.userId && u.active!==false)||null}
  function isAdmin(){return currentUser()?.role==='admin'}
  function isSupplierRole(){return currentUser()?.role==='supplier'}
  function isBuyerRole(){return currentUser()?.role==='buyer'}
  function mySupplier(){return db.suppliers.find(s=>s.userId===currentUser()?.id)||null}
  function myBuyer(){return db.buyers.find(b=>b.userId===currentUser()?.id)||null}
  function approvedSuppliers(){return db.suppliers.filter(s=>s.status==='معتمد')}
  function publicProducts(){return db.products.filter(p=>p.status==='منشور' && approvedSuppliers().some(s=>s.id===p.supplierId))}
  function categoryName(idOrName){const c=db.categories.find(c=>c.id===idOrName||c.name===idOrName);return c?c.name:idOrName}
  function categoryIcon(idOrName){const c=db.categories.find(c=>c.id===idOrName||c.name===idOrName);return c?c.icon:'📦'}
  function val(id){return clean(document.getElementById(id)?.value)}
  function checked(id){return !!document.getElementById(id)?.checked}
  function log(action,details){db.audit.unshift({id:uid('log'),date:today(),user:currentUser()?.name||'النظام',action,details:details||''});db.audit=db.audit.slice(0,600);save()}
  function toast(msg,type){const t=document.createElement('div');t.className='toast '+(type||'');t.textContent=msg;toastRoot.appendChild(t);setTimeout(()=>t.remove(),3000)}
  function statusClass(status){if(['معتمد','منشور','تم التسليم','مسدد','مغلقة','نشط','معتمدة','مكتملة'].includes(status))return 'green';if(['قيد المراجعة','جديد','قيد التجهيز','مسدد جزئي','مفتوحة','مسودة','تحت التحصيل','تحتاج إجراء'].includes(status))return 'gold';if(['موقوف','مرفوض','ملغي','مرتجع','غير مسدد'].includes(status))return 'red';return 'gray'}
  function badge(t){return `<span class="badge ${statusClass(t)}">${esc(t)}</span>`}
  function download(name,text,type){const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([text],{type:type||'text/plain;charset=utf-8'}));a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1500)}
  function csv(rows){return '\ufeff'+rows.map(r=>r.map(x=>'"'+String(x??'').replace(/"/g,'""')+'"').join(',')).join('\n')}

  function cart(){try{return JSON.parse(localStorage.getItem(CART))||[]}catch(e){return []}}
  function setCart(lines){localStorage.setItem(CART,JSON.stringify(lines))}
  function cartCount(){return cart().reduce((a,l)=>a+Number(l.qty||0),0)}
  function priceFor(product,qty){
    const q=Number(qty||0);
    if(product.superPrice && product.superMinQty && q>=Number(product.superMinQty)) return Number(product.superPrice);
    if(product.wholesalePrice && product.wholesaleMinQty && q>=Number(product.wholesaleMinQty)) return Number(product.wholesalePrice);
    return Number(product.retailPrice||product.price||0);
  }

  function go(page,params){
    const hash=params?.tab?'#'+page+'='+params.tab:(params?.id?'#'+page+'='+params.id:(page==='home'?'#home':'#'+page));
    if(location.hash!==hash) history.pushState({page,params},'',hash);
    render(page,params||{});
  }
  window.onpopstate=route;
  function route(){
    const hash=(location.hash||'#home').replace('#','');
    const parts=hash.split('=');
    const page=parts[0]||'home';
    const value=decodeURIComponent(parts.slice(1).join('=')||'');
    if(!hasAdmin() && page!=='setup') return render('setup');
    const key=(page==='admin'||page==='vendor'||page==='account')?'tab':'id';
    render(page,{[key]:value});
  }
  function hasAdmin(){return db.users.some(u=>u.role==='admin')}

  const publicNav=[['home','الرئيسية'],['products','المنتجات'],['categories','الفئات'],['suppliers','الموردون'],['track','تتبع طلب'],['support','الدعم'],['policies','السياسات']];
  function shell(active,content){
    const u=currentUser();
    app.innerHTML=`
      <div class="topbar"><div class="container"><span><strong>Tager</strong> · تجارة وتوريد وربط موردين بمشترين</span><span>واتساب <span class="ltr">${esc(db.settings.whatsapp)}</span> · الدعم ${esc(db.settings.supportPhones.join(' / '))}</span></div></div>
      <div class="header-wrap"><nav class="nav" id="mainNav">
        <a class="brand" onclick="Tager.go('home')"><img src="./assets/tager-logo.png" alt="Tager"></a>
        <button class="menu-toggle" onclick="document.getElementById('mainNav').classList.toggle('open')">القائمة</button>
        <div class="navlinks">
          ${publicNav.map(n=>`<button class="${active===n[0]?'active':''}" onclick="Tager.go('${n[0]}')">${n[1]}</button>`).join('')}
          <button class="${active==='cart'?'active':''}" onclick="Tager.go('cart')">السلة <span class="badge orange">${cartCount()}</span></button>
          ${u?loggedButtons(u,active):guestButtons(active)}
        </div>
      </nav></div>
      ${content}
      ${footerHtml()}
    `;
  }
  function guestButtons(active){return `<button onclick="Tager.go('buyerRegister')" class="${active==='buyerRegister'?'active':''}">سجل كمشتري</button><button onclick="Tager.go('supplierRegister')" class="${active==='supplierRegister'?'active':''}">انضم كمورد</button><button class="primary-nav ${active==='login'?'active':''}" onclick="Tager.go('login')">دخول</button>`}
  function loggedButtons(u,active){
    const target=u.role==='admin'?'admin':u.role==='supplier'?'vendor':'account';
    const label=u.role==='admin'?'لوحة الإدارة':u.role==='supplier'?'لوحة المورد':'لوحة المشتري';
    return `<button class="primary-nav ${active===target?'active':''}" onclick="Tager.go('${target}')">${label}</button><button onclick="Tager.logout()">خروج</button>`;
  }
  function footerHtml(){return `<footer class="footer"><div class="container footer-grid"><div><img src="./assets/tager-logo.png" alt="Tager"><p>${esc(db.content.homeText)}</p></div><div><h3>السوق</h3><p><a onclick="Tager.go('products')">المنتجات</a><br><a onclick="Tager.go('categories')">الفئات</a><br><a onclick="Tager.go('suppliers')">الموردون</a></p></div><div><h3>الحسابات</h3><p><a onclick="Tager.go('buyerRegister')">سجل كمشتري</a><br><a onclick="Tager.go('supplierRegister')">انضم كمورد</a><br><a onclick="Tager.go('login')">تسجيل الدخول</a></p></div><div><h3>التواصل</h3><p>واتساب: <span class="ltr">${esc(db.settings.whatsapp)}</span><br>هاتف: ${esc(db.settings.supportPhones.join(' / '))}<br>البريد: ${esc(db.settings.email)}</p></div></div></footer>`}
  function pageHead(title,sub){return `<section class="page"><div class="container page-title"><div><h1>${esc(title)}</h1>${sub?`<p>${esc(sub)}</p>`:''}</div><img src="./assets/tager-logo.png" alt="Tager"></div></section>`}
  function empty(title,sub,action){return `<div class="empty"><h3>${esc(title)}</h3><p>${esc(sub||'')}</p>${action?`<div class="actions" style="justify-content:center">${action}</div>`:''}</div>`}
  function table(headers,rows){return `<div class="table-wrap"><table><thead><tr>${headers.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.length?rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join('')}</tr>`).join(''):`<tr><td colspan="${headers.length}">${empty('لا توجد بيانات','ستظهر البيانات هنا بعد التسجيل أو الإضافة.')}</td></tr>`}</tbody></table></div>`}

  function home(){
    const st=stats();
    const cats=db.categories.filter(c=>c.active!==false).slice(0,8);
    const approved=approvedSuppliers().slice(0,4);
    const featured=publicProducts().slice(0,6);
    shell('home',`
      <main class="hero">
        <div class="container hero-layout grid">
          <section class="panel hero-card">
            <span class="kicker"><span class="dot"></span> منصة B2B راقية للتجارة والتوريد</span>
            <h1>${esc(db.content.homeTitle)}</h1>
            <p>${esc(db.content.homeText)}</p>
            <div class="actions">
              <button class="primary" onclick="Tager.go('products')">استعراض المنتجات</button>
              <button class="secondary" onclick="Tager.go('supplierRegister')">تسجيل مورد جديد</button>
              <button class="softbtn" onclick="Tager.go('buyerRegister')">سجل كمشتري</button>
            </div>
          </section>
          ${heroVisual()}
        </div>
      </main>
      <section class="search-suite"><div class="container"><div class="search-box">
        <input id="homeSearch" class="input" placeholder="ابحث عن منتج أو مورد...">
        <select id="homeCat"><option value="">كل الفئات</option>${db.categories.filter(c=>c.active!==false).map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>
        ${govSelect('homeGov',true)}
        <select id="homeCenter"><option value="">كل المراكز</option></select>
        <button class="primary" onclick="Tager.homeSearch()">بحث</button>
      </div></div></section>
      <section class="section"><div class="container dashboard-grid grid">
        <div class="metric"><span>موردون معتمدون</span><b>${st.approvedSuppliers}</b></div>
        <div class="metric"><span>منتجات منشورة</span><b>${st.publicProducts}</b></div>
        <div class="metric"><span>طلبات تم تسليمها</span><b>${st.deliveredOrders}</b></div>
        <div class="metric"><span>قيمة الطلبات المسلمة</span><b>${money(st.deliveredRevenue)}</b></div>
      </div></section>
      <section class="section"><div class="container hero-grid grid">
        <div>
          <div class="section-head"><div><h2>الفئات الرئيسية</h2><p>تصنيفات واضحة تساعد المشتري على الوصول للمواد بسرعة.</p></div><button class="softbtn" onclick="Tager.go('categories')">عرض الكل</button></div>
          <div class="cards grid">${cats.map(c=>`<div class="card"><div class="icon">${esc(c.icon)}</div><h3>${esc(c.name)}</h3><p>منتجات منشورة: ${publicProducts().filter(p=>p.categoryId===c.id).length}</p><button class="softbtn smallbtn" onclick="Tager.filterByCategory('${c.id}')">عرض المنتجات</button></div>`).join('')}</div>
        </div>
        <aside class="panel">
          <h2>مركز تشغيل Tager</h2>
          <p>لوحة مختصرة توضح خطوات التشغيل الأساسية بدون أي بيانات مسبقة.</p>
          <div class="flow-list">
            <div class="flow-item"><i>1</i><div><b>اعتماد الموردين</b>لا يظهر المورد للعامة إلا بعد مراجعة الإدارة.</div></div>
            <div class="flow-item"><i>2</i><div><b>نشر المنتجات</b>المنتجات والأسعار والمخزون تحت اعتماد واضح.</div></div>
            <div class="flow-item"><i>3</i><div><b>فحص التغطية</b>مطابقة المحافظة والمركز قبل إرسال الطلب.</div></div>
            <div class="flow-item"><i>4</i><div><b>التسوية المالية</b>العمولات والدفعات داخل الإدارة فقط.</div></div>
          </div>
        </aside>
      </div></section>
      <section class="section"><div class="container">
        <div class="section-head"><div><h2>موردون معتمدون</h2><p>صفحات موردين مستقلة مع التغطية والحد الأدنى والمنتجات.</p></div><button class="softbtn" onclick="Tager.go('suppliers')">عرض الموردين</button></div>
        ${approved.length?`<div class="wide-grid grid">${approved.map(supplierCard).join('')}</div>`:empty('لا يوجد موردون معتمدون حاليًا','بعد تسجيل المورد واعتماده سيظهر هنا بتصميم كامل وصفحة مستقلة.',`<button class="primary" onclick="Tager.go('supplierRegister')">تسجيل مورد</button>`)}
      </div></section>
      <section class="section"><div class="container">
        <div class="section-head"><div><h2>منتجات مميزة</h2><p>المنتجات المنشورة فقط تظهر في السوق، بدون بيانات تجريبية.</p></div><button class="softbtn" onclick="Tager.go('products')">عرض المنتجات</button></div>
        ${featured.length?`<div class="product-grid grid">${featured.map(productCard).join('')}</div>`:empty('لا توجد منتجات منشورة حاليًا','أضف منتجات من لوحة المورد أو الإدارة ثم اعتمدها للنشر.',`<button class="primary" onclick="Tager.go('admin',{tab:'products'})">إدارة المنتجات</button>`)}
      </div></section>
      ${homeSections()}`);
    bindGov('homeGov','homeCenter',true);
  }

  function heroVisual(){
    return `<section class="panel lux-visual">
      <div class="float-icon fi1">📦</div><div class="float-icon fi2">🤝</div><div class="float-icon fi3">📊</div>
      <div class="globe"><img src="./assets/tager-logo.png" alt="Tager"></div>
      <div class="ship-box"><span class="cube"></span><span>توريد منظم</span></div>
      <div class="forklift"></div>
    </section>`;
  }

  function homeSections(){
    const cards=[
      ['🛒','سوق منتجات كامل','منتجات، فئات، تفاصيل، أسعار جملة وجملة الجملة، وسلة وإتمام طلب.'],
      ['🏭','موردون وتغطية','صفحة مورد مستقلة، مناطق توصيل، حد أدنى، ومستندات اعتماد.'],
      ['📦','تشغيل الطلبات','مراحل تشغيل واضحة من الطلب الجديد حتى التسليم والمرتجع.'],
      ['💳','إدارة مالية','عمولات، سلة مميزة، مستحقات الموردين، دفعات، وتقارير داخلية.'],
      ['🔐','صلاحيات منفصلة','إدارة، مورد، ومشتري مع صفحات وصلاحيات واضحة.'],
      ['📊','تقارير وتصدير','CSV ونسخ احتياطي وقوالب استيراد للبيانات الأساسية.'],
      ['☎️','دعم ومتابعة','تذاكر دعم للعملاء والموردين وحالات متابعة داخل الإدارة.'],
      ['⚙️','إعدادات كاملة','بيانات المنصة، الشعار، الدعم، السياسات، العملة، والحسابات.']
    ];
    return `<section class="section"><div class="container">
      <div class="section-head"><div><h2>منصة كاملة للتشغيل اليومي</h2><p>واجهة عامة راقية ولوحات تشغيل داخلية بدون إظهار الإعدادات المالية للعامة.</p></div><button class="softbtn" onclick="Tager.go('policies')">السياسات</button></div>
      <div class="cards grid">${cards.map(c=>`<div class="card"><div class="icon">${c[0]}</div><h3>${c[1]}</h3><p>${c[2]}</p></div>`).join('')}</div>
    </div></section>
    <section class="section"><div class="container wide-grid grid">
      <div class="panel"><h2>للمشتري</h2><p>${esc(db.content.buyerMessage)}</p><div class="actions"><button class="primary" onclick="Tager.go('buyerRegister')">سجل كمشتري</button></div></div>
      <div class="panel"><h2>للمورد</h2><p>${esc(db.content.supplierMessage)}</p><div class="actions"><button class="primary" onclick="Tager.go('supplierRegister')">انضم كمورد</button></div></div>
      <div class="panel"><h2>للإدارة</h2><p>إدارة الموردين والمنتجات والطلبات والحسابات والتقارير من لوحة داخلية لا تظهر في القائمة العامة.</p><div class="actions"><button class="softbtn" onclick="Tager.go('login')">دخول الإدارة</button></div></div>
    </div></section>`;
  }

  function homeSearch(){
    sessionStorage.setItem('fSearch',val('homeSearch'));
    sessionStorage.setItem('fCat',val('homeCat'));
    sessionStorage.setItem('fGov',val('homeGov'));
    sessionStorage.setItem('fCenter',val('homeCenter'));
    go('products');
  }

  function stats(){
    const delivered=db.orders.filter(o=>o.status==='تم التسليم');
    return {
      deliveredRevenue:delivered.reduce((a,o)=>a+Number(o.total||0),0),
      deliveredOrders:delivered.length,
      approvedSuppliers:approvedSuppliers().length,
      publicProducts:publicProducts().length,
      allOrders:db.orders.length,
      allUsers:db.users.length,
      ticketsOpen:db.tickets.filter(t=>t.status!=='مغلقة').length
    };
  }

  function setup(){
    app.innerHTML=`<main class="hero"><div class="container hero-grid grid"><section class="panel hero-card"><span class="kicker"><span class="dot"></span> إعداد أول حساب إدارة</span><h1>Tager</h1><p>المنصة تبدأ بدون بيانات مسبقة. أنشئ حساب الإدارة الحقيقي ثم أكمل الإعدادات واعتماد الموردين والمنتجات.</p><img src="./assets/tager-logo.png" alt="Tager" style="width:260px;max-width:100%;margin-top:20px"></section><section class="panel"><h2>بيانات الإدارة</h2><div class="field"><label>اسم المسؤول</label><input class="input" id="setupName" placeholder="اسم المسؤول"></div><div class="field"><label>الهاتف</label><input class="input" id="setupPhone" placeholder="01xxxxxxxxx"></div><div class="field"><label>البريد</label><input class="input" id="setupEmail" placeholder="email@example.com"></div><div class="field"><label>كلمة المرور</label><input class="input" id="setupPass" type="password" placeholder="كلمة مرور قوية"></div><button class="primary" style="width:100%" onclick="Tager.createFirstAdmin()">إنشاء حساب الإدارة</button><p class="muted">بعد الإنشاء ستظهر لوحة الإدارة والإعدادات المالية والصلاحيات.</p></section></div></main>`;
  }
  function createFirstAdmin(){
    const name=val('setupName'), phone=val('setupPhone'), pass=val('setupPass');
    if(!name||!phone||!pass) return toast('استكمل اسم المسؤول والهاتف وكلمة المرور','danger');
    const u={id:uid('usr'),role:'admin',name,phone,email:val('setupEmail'),password:pass,active:true,createdAt:today()};
    db.users.push(u); save(); session={userId:u.id,role:u.role}; saveSession(); log('إنشاء حساب الإدارة الأول',name); toast('تم إنشاء حساب الإدارة'); go('admin');
  }

  function login(){
    shell('login',pageHead('تسجيل الدخول','الدخول بالهاتف أو البريد وكلمة المرور')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><h2>الدخول</h2><div class="field"><label>الهاتف أو البريد</label><input id="loginId" class="input" placeholder="الهاتف أو البريد"></div><div class="field"><label>كلمة المرور</label><input id="loginPass" class="input" type="password" placeholder="كلمة المرور"></div><div class="actions"><button class="primary" onclick="Tager.loginAction()">دخول</button><button class="softbtn" onclick="Tager.go('forgot')">نسيت كلمة المرور</button></div></div><div class="panel"><h2>ليس لديك حساب؟</h2><p>اختر نوع الحساب المناسب. المورد يحتاج اعتماد الإدارة قبل ظهور منتجاته في السوق.</p><div class="actions"><button class="primary" onclick="Tager.go('buyerRegister')">سجل كمشتري</button><button class="secondary" onclick="Tager.go('supplierRegister')">انضم كمورد</button></div></div></div></section>`);
  }
  function loginAction(){
    const idv=val('loginId'), pass=val('loginPass');
    const u=db.users.find(u=>(u.phone===idv||u.email===idv) && u.password===pass);
    if(!u) return toast('بيانات الدخول غير صحيحة','danger');
    if(u.active===false) return toast('الحساب موقوف من الإدارة','danger');
    session={userId:u.id,role:u.role}; saveSession(); log('تسجيل دخول',u.name); toast('تم تسجيل الدخول'); go(u.role==='admin'?'admin':u.role==='supplier'?'vendor':'account');
  }
  function forgot(){
    shell('forgot',pageHead('استعادة كلمة المرور','طلب مراجعة الحساب من الإدارة')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><div class="field"><label>الهاتف أو البريد</label><input id="forgotId" class="input"></div><div class="field"><label>ملاحظات</label><textarea id="forgotMsg" rows="4"></textarea></div><button class="primary" onclick="Tager.forgotAction()">إرسال طلب</button></div><div class="panel"><h2>سياسة الاستعادة</h2><p>يتم تسجيل طلب دعم للإدارة لمراجعة هوية صاحب الحساب قبل تغيير كلمة المرور.</p></div></div></section>`);
  }
  function forgotAction(){
    if(!val('forgotId')) return toast('اكتب الهاتف أو البريد','danger');
    db.tickets.unshift({id:uid('tic'),number:'SUP-'+Date.now().toString().slice(-6),name:'طلب استعادة',phone:val('forgotId'),type:'استعادة كلمة المرور',subject:'استعادة كلمة المرور',message:val('forgotMsg'),status:'مفتوحة',createdAt:today()});
    save(); toast('تم إرسال الطلب'); go('login');
  }
  function logout(){session=null;saveSession();toast('تم الخروج');go('home')}

  function buyerRegister(){
    shell('buyerRegister',pageHead('سجل كمشتري','أنشئ حساب مشتري لإدارة الطلبات والعناوين والفواتير')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><h2>بيانات المشتري</h2><div class="form-grid"><div class="field"><label>اسم النشاط / الشركة</label><input id="bName" class="input"></div><div class="field"><label>المسؤول</label><input id="bPerson" class="input"></div><div class="field"><label>الهاتف</label><input id="bPhone" class="input"></div><div class="field"><label>واتساب</label><input id="bWhatsapp" class="input"></div><div class="field"><label>البريد</label><input id="bEmail" class="input"></div><div class="field"><label>نوع النشاط</label><input id="bActivity" class="input" placeholder="سوبر ماركت / مطعم / تاجر"></div><div class="field"><label>المحافظة</label>${govSelect('bGov')}</div><div class="field"><label>المركز</label><select id="bCenter"></select></div><div class="field full"><label>العنوان</label><input id="bAddress" class="input"></div><div class="field"><label>حجم الطلب الشهري المتوقع</label><input id="bMonthly" class="input"></div><div class="field"><label>كلمة المرور</label><input id="bPass" type="password" class="input"></div></div><button class="primary" onclick="Tager.registerBuyer()">إنشاء حساب مشتري</button></div><div class="panel"><h2>ماذا بعد التسجيل؟</h2><div class="flow-list"><div class="flow-item"><i>1</i><div><b>تفعيل الحساب</b>تستطيع الدخول ومتابعة الطلبات.</div></div><div class="flow-item"><i>2</i><div><b>اختيار المنتجات</b>السعر يتغير حسب كمية الجملة وجملة الجملة.</div></div><div class="flow-item"><i>3</i><div><b>تتبع الطلب</b>كل حالة تظهر في لوحة المشتري.</div></div></div></div></div></section>`);
    bindGov('bGov','bCenter');
  }
  function registerBuyer(){
    const name=val('bName'), phone=val('bPhone'), pass=val('bPass');
    if(!name||!phone||!pass) return toast('استكمل اسم النشاط والهاتف وكلمة المرور','danger');
    if(db.users.some(u=>u.phone===phone)) return toast('رقم الهاتف مسجل من قبل','danger');
    const u={id:uid('usr'),role:'buyer',name,phone,email:val('bEmail'),password:pass,active:true,createdAt:today()};
    db.users.push(u);
    db.buyers.push({id:uid('buy'),userId:u.id,name,person:val('bPerson'),phone,whatsapp:val('bWhatsapp'),email:val('bEmail'),activity:val('bActivity'),gov:val('bGov'),center:val('bCenter'),address:val('bAddress'),monthlyOrder:val('bMonthly'),status:'نشط',createdAt:today()});
    save(); session={userId:u.id,role:u.role}; saveSession(); log('تسجيل مشتري',name); toast('تم إنشاء حساب المشتري'); go('account');
  }
  function supplierRegister(){
    shell('supplierRegister',pageHead('انضم كمورد','سجل بيانات المورد والمنتجات ومناطق التغطية للمراجعة')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><h2>بيانات المورد</h2><div class="form-grid"><div class="field"><label>اسم الشركة / النشاط</label><input id="sName" class="input"></div><div class="field"><label>المسؤول</label><input id="sPerson" class="input"></div><div class="field"><label>الهاتف</label><input id="sPhone" class="input"></div><div class="field"><label>واتساب</label><input id="sWhatsapp" class="input"></div><div class="field"><label>البريد</label><input id="sEmail" class="input"></div><div class="field"><label>نوع النشاط</label><input id="sActivity" class="input" placeholder="أغذية / مشروبات / منظفات"></div><div class="field"><label>السجل التجاري</label><input id="sCR" class="input"></div><div class="field"><label>البطاقة الضريبية</label><input id="sTax" class="input"></div><div class="field"><label>المحافظة الرئيسية</label>${govSelect('sGov')}</div><div class="field"><label>المركز الرئيسي</label><select id="sCenter"></select></div><div class="field"><label>الحد الأدنى للطلب</label><input id="sMin" type="number" class="input"></div><div class="field"><label>مدة التجهيز بالأيام</label><input id="sPrep" type="number" class="input"></div><div class="field full"><label>المنتجات الرئيسية</label><textarea id="sProducts" rows="4" placeholder="اكتب خطوط المنتجات والأسعار المبدئية"></textarea></div><div class="field full"><label>ملاحظات التوصيل والدفع</label><textarea id="sNotes" rows="4"></textarea></div><div class="field"><label>كلمة المرور</label><input id="sPass" type="password" class="input"></div></div><button class="primary" onclick="Tager.registerSupplier()">إرسال طلب المورد</button></div><div class="panel"><h2>مراجعة المورد</h2><p>${esc(db.content.supplierMessage)}</p><div class="notice">عمولة المنصة ورسوم السلة المميزة لا تظهر هنا لأنها إعدادات داخلية في لوحة الإدارة فقط.</div><div class="flow-list"><div class="flow-item"><i>1</i><div><b>تسجيل البيانات</b>بيانات الشركة والتواصل والمستندات.</div></div><div class="flow-item"><i>2</i><div><b>اعتماد الإدارة</b>الإدارة تراجع المورد ومناطق التغطية.</div></div><div class="flow-item"><i>3</i><div><b>إضافة المنتجات</b>المورد يضيف المنتجات ثم تعتمد للنشر.</div></div></div></div></div></section>`);
    bindGov('sGov','sCenter');
  }
  function registerSupplier(){
    const name=val('sName'), phone=val('sPhone'), pass=val('sPass');
    if(!name||!phone||!pass) return toast('استكمل اسم المورد والهاتف وكلمة المرور','danger');
    if(db.users.some(u=>u.phone===phone)) return toast('رقم الهاتف مسجل من قبل','danger');
    const u={id:uid('usr'),role:'supplier',name,phone,email:val('sEmail'),password:pass,active:true,createdAt:today()};
    const sup={id:uid('sup'),userId:u.id,name,person:val('sPerson'),phone,whatsapp:val('sWhatsapp'),email:val('sEmail'),activity:val('sActivity'),cr:val('sCR'),tax:val('sTax'),gov:val('sGov'),center:val('sCenter'),minOrder:Number(val('sMin')||0),prepDays:Number(val('sPrep')||0),productsText:val('sProducts'),notes:val('sNotes'),status:'قيد المراجعة',coverage:[],documents:[],createdAt:today()};
    if(sup.gov){sup.coverage.push({id:uid('cov'),gov:sup.gov,center:sup.center||'كل المراكز',shipping:Number(db.settings.shippingBase||0),minOrder:sup.minOrder||0,days:sup.prepDays||0});}
    db.users.push(u); db.suppliers.push(sup); save(); session={userId:u.id,role:u.role}; saveSession(); log('تسجيل مورد',name); toast('تم إرسال طلب المورد'); go('vendor');
  }

  function products(){
    const list=filterProductsFromInputs();
    shell('products',pageHead('المنتجات','بحث وفلاتر حسب الفئة والمورد ومنطقة التغطية')+filterBar()+`<section class="section"><div class="container">${list.length?`<div class="product-grid grid">${list.map(productCard).join('')}</div>`:empty('لا توجد منتجات منشورة حاليًا','بعد اعتماد الموردين ونشر المنتجات ستظهر هنا بدون بيانات مسبقة.',`<button class="primary" onclick="Tager.go('supplierRegister')">انضم كمورد</button>`)}</div></section>`);
    restoreFilters(); bindFilters();
  }
  function filterBar(){return `<section><div class="container"><div class="filters"><input id="fSearch" class="input" placeholder="بحث باسم المنتج أو المورد"><select id="fCat"><option value="">كل الفئات</option>${db.categories.filter(c=>c.active!==false).map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>${govSelect('fGov',true)}<select id="fCenter"><option value="">كل المراكز</option></select><button class="primary" onclick="Tager.go('products')">بحث</button></div></div></section>`}
  function filterProductsFromInputs(){
    const q=clean(sessionStorage.getItem('fSearch')||'').toLowerCase();
    const cat=sessionStorage.getItem('fCat')||'';
    const gov=sessionStorage.getItem('fGov')||'';
    const center=sessionStorage.getItem('fCenter')||'';
    return publicProducts().filter(p=>{
      const s=db.suppliers.find(x=>x.id===p.supplierId);
      const text=[p.name,p.sku,p.description,categoryName(p.categoryId),s?.name,s?.activity].join(' ').toLowerCase();
      const coverage=!gov || (s?.coverage||[]).some(c=>c.gov===gov && (!center || c.center===center || c.center==='كل المراكز'));
      return (!q||text.includes(q)) && (!cat||p.categoryId===cat) && coverage;
    });
  }
  function restoreFilters(){
    ['fSearch','fCat','fGov','fCenter'].forEach(id=>{const el=document.getElementById(id); if(el && sessionStorage.getItem(id)!=null) el.value=sessionStorage.getItem(id);});
    bindGov('fGov','fCenter',true);
    const center=document.getElementById('fCenter'); if(center && sessionStorage.getItem('fCenter')) center.value=sessionStorage.getItem('fCenter');
  }
  function bindFilters(){['fSearch','fCat','fGov','fCenter'].forEach(id=>{const el=document.getElementById(id); if(el){el.oninput=()=>{sessionStorage.setItem(id,el.value);}; el.onchange=()=>{sessionStorage.setItem(id,el.value); if(id==='fGov') sessionStorage.setItem('fCenter','');};}})}
  function productCard(p){
    const s=db.suppliers.find(x=>x.id===p.supplierId);
    return `<article class="card product-card"><div class="product-img">${p.image?`<img src="${esc(p.image)}" alt="${esc(p.name)}">`:`<div class="placeholder-mark"><img src="./assets/tager-icon.png" alt="Tager"></div>`}</div><div class="product-body"><div class="product-meta"><span class="badge gold">${esc(categoryName(p.categoryId))}</span><span class="badge gray">${esc(p.unit||'قطعة')}</span></div><h3>${esc(p.name)}</h3><div class="supplier-line">${esc(s?.name||'مورد')}</div><div class="price">${money(p.retailPrice)}</div><p class="muted">جملة من ${Number(p.wholesaleMinQty||0).toLocaleString('ar-EG')} · جملة الجملة من ${Number(p.superMinQty||0).toLocaleString('ar-EG')}</p><div class="actions"><button class="primary" onclick="Tager.addToCart('${p.id}')">إضافة للسلة</button><button class="softbtn" onclick="Tager.go('product',{id:'${p.id}'})">التفاصيل</button></div></div></article>`;
  }
  function product(params){
    const p=publicProducts().find(p=>p.id===params.id)||db.products.find(p=>p.id===params.id);
    if(!p) return shell('products',pageHead('المنتج','')+`<div class="container">${empty('المنتج غير موجود','قد يكون غير منشور أو تم إيقافه.')}</div>`);
    const s=db.suppliers.find(x=>x.id===p.supplierId);
    const priceRows=[['قطاعي / أقل كمية',money(p.retailPrice),`أقل من ${p.wholesaleMinQty||0}`],['جملة',money(p.wholesalePrice||p.retailPrice),`من ${p.wholesaleMinQty||0}`],['جملة الجملة',money(p.superPrice||p.wholesalePrice||p.retailPrice),`من ${p.superMinQty||0}`]];
    shell('products',pageHead(p.name,'تفاصيل المنتج والأسعار حسب الكمية')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><div class="product-img" style="height:340px;border-radius:24px">${p.image?`<img src="${esc(p.image)}" alt="${esc(p.name)}">`:`<div class="placeholder-mark"><img src="./assets/tager-icon.png" alt="Tager"></div>`}</div></div><div class="panel"><div class="product-meta"><span class="badge gold">${esc(categoryName(p.categoryId))}</span><span class="badge gray">${esc(p.unit||'قطعة')}</span><span class="badge green">${esc(s?.name||'مورد')}</span></div><h2>${esc(p.name)}</h2><p>${esc(p.description||'لا يوجد وصف إضافي.')}</p><div class="price">${money(p.retailPrice)}</div><div class="actions"><button class="primary" onclick="Tager.addToCart('${p.id}')">إضافة للسلة</button><button class="softbtn" onclick="Tager.go('supplier',{id:'${s?.id||''}'})">صفحة المورد</button></div></div></div></section><section class="section"><div class="container">${table(['نوع السعر','السعر','الكمية'],priceRows)}<br>${coverageBox(s)}</div></section>`);
  }
  function coverageBox(s){if(!s) return ''; return `<div class="card"><h3>تغطية المورد</h3>${(s.coverage||[]).length?`<div class="mini-list">${s.coverage.map(c=>`<div class="mini-item"><span>${esc(c.gov)} - ${esc(c.center||'كل المراكز')}</span><b>${money(c.shipping||0)}</b></div>`).join('')}</div>`:empty('لم يتم تسجيل تغطية','يجب على الإدارة أو المورد إضافة مناطق التغطية.')}</div>`}
  function categories(){
    shell('categories',pageHead('الفئات','تصنيفات المواد والمنتجات داخل السوق')+`<section class="section"><div class="container"><div class="cards grid">${db.categories.filter(c=>c.active!==false).map(c=>`<div class="card"><div class="icon">${esc(c.icon)}</div><h3>${esc(c.name)}</h3><p>عدد المنتجات المنشورة: ${publicProducts().filter(p=>p.categoryId===c.id).length}</p><button class="softbtn" onclick="Tager.filterByCategory('${c.id}')">عرض المنتجات</button></div>`).join('')}</div></div></section>`);
  }
  function filterByCategory(id){sessionStorage.setItem('fCat',id); go('products')}
  function suppliers(){
    const list=approvedSuppliers();
    shell('suppliers',pageHead('الموردون','قائمة الموردين المعتمدين ومناطق التغطية وصفحة مستقلة لكل مورد')+`<section class="section"><div class="container">${list.length?`<div class="wide-grid grid">${list.map(supplierCard).join('')}</div>`:empty('لا يوجد موردون معتمدون حاليًا','بعد تسجيل المورد واعتماده من الإدارة سيظهر هنا بصفحته ومنتجاته ومناطق تغطيته.',`<button class="primary" onclick="Tager.go('supplierRegister')">تسجيل مورد</button>`)}</div></section>`);
  }
  function supplierCard(s){return `<div class="card"><div class="icon">🏭</div><h3>${esc(s.name)}</h3><p>${esc(s.activity||'مورد')}</p><div class="product-meta"><span class="badge green">${esc(s.gov||'مصر')}</span><span class="badge gray">${(s.coverage||[]).length} منطقة</span><span class="badge gold">حد أدنى ${money(s.minOrder||0)}</span></div><div class="actions"><button class="primary" onclick="Tager.go('supplier',{id:'${s.id}'})">عرض صفحة المورد</button></div></div>`}
  function supplier(params){
    const s=db.suppliers.find(s=>s.id===params.id && s.status==='معتمد');
    if(!s) return shell('suppliers',pageHead('المورد','')+`<div class="container">${empty('المورد غير متاح','المورد غير معتمد أو غير موجود.')}</div>`);
    const prods=publicProducts().filter(p=>p.supplierId===s.id);
    shell('suppliers',pageHead(s.name,s.activity||'مورد معتمد')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><h2>بيانات المورد</h2><p>المسؤول: ${esc(s.person||'-')}</p><p>المحافظة: ${esc(s.gov||'-')} · المركز: ${esc(s.center||'-')}</p><p>الحد الأدنى للطلب: ${money(s.minOrder||0)}</p><p>مدة التجهيز: ${Number(s.prepDays||0).toLocaleString('ar-EG')} يوم</p><button class="softbtn" onclick="Tager.go('products')">عرض السوق</button></div><div class="panel">${coverageBox(s)}</div></div></section><section class="section"><div class="container"><div class="section-head"><div><h2>منتجات المورد</h2><p>المنتجات المنشورة والمعتمدة فقط.</p></div></div>${prods.length?`<div class="product-grid grid">${prods.map(productCard).join('')}</div>`:empty('لا توجد منتجات منشورة لهذا المورد','تظهر المنتجات بعد اعتمادها من الإدارة.')}</div></section>`);
  }

  function cartPage(){
    const lines=cart().map(l=>({line:l,product:publicProducts().find(p=>p.id===l.productId)})).filter(x=>x.product);
    const rows=lines.map(x=>{const price=priceFor(x.product,x.line.qty);return {...x,price,total:price*x.line.qty};});
    const subtotal=rows.reduce((a,x)=>a+x.total,0);
    shell('cart',pageHead('السلة','راجع الكميات والأسعار قبل إتمام الطلب')+`<section class="section"><div class="container hero-grid grid"><div class="panel">${rows.length?`<div class="mini-list">${rows.map(x=>`<div class="cart-line"><div><b>${esc(x.product.name)}</b><br><span class="muted">${esc(categoryName(x.product.categoryId))} · ${money(x.price)}</span></div><div class="qtybox"><button onclick="Tager.changeQty('${x.product.id}',-1)">-</button><b>${x.line.qty}</b><button onclick="Tager.changeQty('${x.product.id}',1)">+</button></div><b>${money(x.total)}</b><button class="dangerbtn smallbtn" onclick="Tager.removeCart('${x.product.id}')">حذف</button></div>`).join('')}</div>`:empty('السلة فارغة','أضف منتجات من السوق لإتمام الطلب.',`<button class="primary" onclick="Tager.go('products')">استعراض المنتجات</button>`)}</div><div class="panel"><h2>ملخص السلة</h2><div class="mini-list"><div class="mini-item"><span>عدد الأصناف</span><b>${rows.length}</b></div><div class="mini-item"><span>عدد الوحدات</span><b>${cartCount()}</b></div><div class="mini-item"><span>الإجمالي قبل التوصيل</span><b>${money(subtotal)}</b></div></div><div class="actions"><button class="primary" ${rows.length?'':'disabled'} onclick="Tager.go('checkout')">إتمام الطلب</button><button class="softbtn" onclick="Tager.go('products')">متابعة الشراء</button></div></div></div></section>`);
  }
  function addToCart(id){const p=publicProducts().find(p=>p.id===id); if(!p) return toast('المنتج غير متاح','danger'); const c=cart(); const line=c.find(l=>l.productId===id); if(line) line.qty+=1; else c.push({productId:id,qty:1}); setCart(c); toast('تمت الإضافة للسلة'); render('cart');}
  function changeQty(id,delta){const c=cart(); const line=c.find(l=>l.productId===id); if(!line)return; line.qty=Math.max(1,Number(line.qty||1)+delta); setCart(c); render('cart')}
  function removeCart(id){setCart(cart().filter(l=>l.productId!==id)); render('cart')}
  function checkout(){
    const u=currentUser();
    if(!u && !db.settings.allowGuestCheckout) return shell('checkout',pageHead('إتمام الطلب','')+`<section class="section"><div class="container">${empty('تسجيل الدخول مطلوب','سجل كمشتري أو ادخل لحسابك لإتمام الطلب.',`<button class="primary" onclick="Tager.go('buyerRegister')">سجل كمشتري</button><button class="softbtn" onclick="Tager.go('login')">دخول</button>`)}</div></section>`);
    const b=myBuyer(); const lines=cart().filter(l=>publicProducts().some(p=>p.id===l.productId));
    if(!lines.length) return go('cart');
    shell('checkout',pageHead('إتمام الطلب','فحص العنوان والتغطية وطريقة الدفع')+`<section class="section"><div class="container hero-grid grid"><div class="panel"><h2>بيانات التسليم</h2><div class="form-grid"><div class="field"><label>اسم المستلم</label><input id="coName" class="input" value="${esc(b?.name||u?.name||'')}"></div><div class="field"><label>الهاتف</label><input id="coPhone" class="input" value="${esc(b?.phone||u?.phone||'')}"></div><div class="field"><label>المحافظة</label>${govSelect('coGov')}</div><div class="field"><label>المركز</label><select id="coCenter"></select></div><div class="field full"><label>العنوان التفصيلي</label><input id="coAddress" class="input" value="${esc(b?.address||'')}"></div><div class="field"><label>طريقة الدفع</label><select id="coPay"><option>كاش عند الاستلام</option><option>تحويل بنكي</option><option>محفظة إلكترونية</option></select></div><div class="field"><label>كود خصم / اتفاقية</label><input id="coCoupon" class="input" placeholder="اختياري"></div><div class="field"><label>ملاحظات</label><input id="coNotes" class="input"></div><div class="field full"><label><input id="coPremium" type="checkbox"> سلة مميزة لتجميع الموردين في متابعة واحدة</label></div></div><button class="primary" onclick="Tager.placeOrder()">إرسال الطلب</button></div><div class="panel"><h2>مراجعة قبل الإرسال</h2><p>سيتم فحص تغطية كل مورد على المحافظة والمركز قبل اعتماد الطلب.</p><div id="checkoutSummary">${checkoutSummaryHtml()}</div></div></div></section>`);
    bindGov('coGov','coCenter');
    const gov=document.getElementById('coGov'), cen=document.getElementById('coCenter'); if(b?.gov){gov.value=b.gov; bindGov('coGov','coCenter'); if(b.center) cen.value=b.center;}
  }
  function checkoutSummaryHtml(){
    const raw=cart().map(l=>({p:publicProducts().find(p=>p.id===l.productId),qty:Number(l.qty||1)})).filter(x=>x.p);
    const subtotal=raw.reduce((a,x)=>a+priceFor(x.p,x.qty)*x.qty,0);
    const rows=raw.map(x=>[esc(x.p.name),x.qty,money(priceFor(x.p,x.qty)),money(priceFor(x.p,x.qty)*x.qty)]);
    const tax=Math.max(0,subtotal*Number(db.settings.taxRate||0)/100); return table(['الصنف','الكمية','السعر','الإجمالي'],rows)+`<br><div class="mini-item"><span>الإجمالي قبل التوصيل</span><b>${money(subtotal)}</b></div><div class="mini-item"><span>ضريبة تقديرية</span><b>${money(tax)}</b></div>`;
  }
  function placeOrder(){
    const name=val('coName'), phone=val('coPhone'), gov=val('coGov'), center=val('coCenter'), address=val('coAddress');
    if(!name||!phone||!gov||!center||!address) return toast('استكمل بيانات التسليم','danger');
    const lines=[]; let subtotal=0; let shipping=0; const supplierTotals={}; const issues=[];
    cart().forEach(l=>{
      const p=publicProducts().find(p=>p.id===l.productId); if(!p)return;
      const s=db.suppliers.find(s=>s.id===p.supplierId); const coverage=(s?.coverage||[]).find(c=>c.gov===gov && (c.center===center || c.center==='كل المراكز'));
      if(!coverage) issues.push(`${p.name} - ${s?.name||'مورد'} لا يغطي ${gov} / ${center}`);
      const qty=Number(l.qty||1); const price=priceFor(p,qty); const total=price*qty; subtotal+=total; supplierTotals[s.id]=(supplierTotals[s.id]||0)+total;
      lines.push({productId:p.id,supplierId:s.id,name:p.name,category:categoryName(p.categoryId),qty,price,total});
    });
    if(issues.length) return toast(issues[0],'danger');
    Object.keys(supplierTotals).forEach(sid=>{const s=db.suppliers.find(s=>s.id===sid); const c=(s.coverage||[]).find(c=>c.gov===gov && (c.center===center || c.center==='كل المراكز')); shipping+=Number(c?.shipping||db.settings.shippingBase||0);});
    if(db.settings.freeShippingLimit && subtotal>=Number(db.settings.freeShippingLimit)) shipping=0;
    const premium=checked('coPremium')?subtotal*Number(db.settings.premiumBasketFee||0)/100:0;
    const couponCode=val('coCoupon').toUpperCase();
    const coupon=(db.coupons||[]).find(c=>String(c.code||'').toUpperCase()===couponCode && c.active!==false);
    let discount=0;
    if(coupon){discount=coupon.type==='percent'?subtotal*Number(coupon.value||0)/100:Number(coupon.value||0); discount=Math.min(discount,subtotal); coupon.used=Number(coupon.used||0)+1;}
    const commission=Math.max(0,(subtotal-discount)*Number(db.settings.defaultCommission||0)/100);
    const tax=Math.max(0,(subtotal-discount)*Number(db.settings.taxRate||0)/100);
    const total=Math.max(0,subtotal+shipping+premium-discount+tax);
    const order={id:uid('ord'),number:(db.settings.orderPrefix||'TG')+'-'+Date.now().toString().slice(-8),buyerUserId:currentUser()?.id||null,buyerName:name,phone,gov,center,address,paymentMethod:val('coPay'),notes:val('coNotes'),couponCode:coupon?coupon.code:'',discount,premium:checked('coPremium'),items:lines,subtotal,shipping,premiumFee:premium,platformCommission:commission,tax,total,status:'جديد',paymentStatus:'غير مسدد',createdAt:today()};
    db.orders.unshift(order); setCart([]); save(); log('إنشاء طلب',order.number); toast('تم إرسال الطلب'); go('account');
  }

  function account(params){
    if(!isBuyerRole()) return accessDenied('لوحة المشتري');
    const tab=params.tab||'overview'; const b=myBuyer(); const orders=db.orders.filter(o=>o.buyerUserId===currentUser().id);
    shell('account',pageHead('لوحة المشتري',b?.name||currentUser().name)+`<section class="section"><div class="container"><div class="tabs">${tabBtn('account','overview','ملخص',tab)}${tabBtn('account','orders','طلباتي',tab)}${tabBtn('account','addresses','العناوين',tab)}${tabBtn('account','support','الدعم',tab)}</div>${buyerTab(tab,orders,b)}</div></section>`);
  }
  function buyerTab(tab,orders,b){
    if(tab==='orders') return table(['الرقم','التاريخ','العنوان','الحالة','السداد','الإجمالي','طباعة'],orders.map(o=>[o.number,o.createdAt,`${esc(o.gov)} - ${esc(o.center)}`,badge(o.status),badge(o.paymentStatus),money(o.total),`<button class="softbtn smallbtn" onclick="Tager.printOrder('${o.id}')">عرض</button>`]));
    if(tab==='addresses') return `<div class="panel"><h2>العنوان المسجل</h2><p>${esc(b?.gov||'-')} - ${esc(b?.center||'-')} - ${esc(b?.address||'-')}</p><p class="muted">يمكن تحديث العنوان الافتراضي من الإدارة أو عند الطلب القادم.</p></div>`;
    if(tab==='support') return supportFormHtml('buyer');
    return `<div class="dashboard-grid grid"><div class="metric"><span>عدد الطلبات</span><b>${orders.length}</b></div><div class="metric"><span>طلبات مسلمة</span><b>${orders.filter(o=>o.status==='تم التسليم').length}</b></div><div class="metric"><span>إجمالي مشتريات</span><b>${money(orders.reduce((a,o)=>a+Number(o.total||0),0))}</b></div><div class="metric"><span>طلبات مفتوحة</span><b>${orders.filter(o=>!['تم التسليم','ملغي','مرتجع'].includes(o.status)).length}</b></div></div><br><div class="panel"><h2>آخر الطلبات</h2>${orders.length?table(['الرقم','الحالة','الإجمالي'],orders.slice(0,5).map(o=>[o.number,badge(o.status),money(o.total)])):empty('لا توجد طلبات بعد','ابدأ من صفحة المنتجات.')}</div>`;
  }

  function vendor(params){
    if(!isSupplierRole()) return accessDenied('لوحة المورد');
    const s=mySupplier(); const tab=params.tab||'overview';
    shell('vendor',pageHead('لوحة المورد',s?.name||currentUser().name)+`<section class="section"><div class="container"><div class="tabs">${['overview:ملخص','profile:بيانات المورد','coverage:مناطق التغطية','products:المنتجات','orders:الطلبات','finance:الحساب المالي','documents:المستندات','support:الدعم'].map(x=>{const [id,label]=x.split(':');return tabBtn('vendor',id,label,tab)}).join('')}</div>${vendorTab(tab,s)}</div></section>`);
  }
  function vendorTab(tab,s){
    if(!s) return empty('لا يوجد ملف مورد','تواصل مع الإدارة.');
    const prods=db.products.filter(p=>p.supplierId===s.id);
    const orders=db.orders.filter(o=>o.items.some(i=>i.supplierId===s.id));
    if(tab==='profile') return `<div class="panel"><h2>بيانات المورد</h2><div class="form-grid"><div><b>الحالة</b><p>${badge(s.status)}</p></div><div><b>النشاط</b><p>${esc(s.activity||'-')}</p></div><div><b>الهاتف</b><p>${esc(s.phone||'-')}</p></div><div><b>واتساب</b><p>${esc(s.whatsapp||'-')}</p></div><div><b>السجل التجاري</b><p>${esc(s.cr||'-')}</p></div><div><b>البطاقة الضريبية</b><p>${esc(s.tax||'-')}</p></div><div class="full"><b>ملاحظات</b><p>${esc(s.notes||'-')}</p></div></div></div>`;
    if(tab==='coverage') return `<div class="section-head"><div><h2>مناطق التغطية</h2><p>أضف المحافظة والمركز ورسوم التوصيل والحد الأدنى.</p></div><button class="primary" onclick="Tager.coverageModal()">إضافة منطقة</button></div>${s.coverage.length?table(['المحافظة','المركز','رسوم التوصيل','حد أدنى','أيام'],s.coverage.map(c=>[esc(c.gov),esc(c.center),money(c.shipping),money(c.minOrder),esc(c.days||0)])):empty('لا توجد مناطق تغطية','أضف مناطق التغطية حتى يتم قبول الطلبات.')}`;
    if(tab==='products') return `<div class="section-head"><div><h2>منتجات المورد</h2><p>المنتجات تحتاج اعتماد الإدارة قبل النشر.</p></div><button class="primary" onclick="Tager.productModal()">إضافة منتج</button></div>${table(['SKU','المنتج','الفئة','السعر','المخزون','الحالة'],prods.map(p=>[esc(p.sku),esc(p.name),esc(categoryName(p.categoryId)),money(p.retailPrice),Number(p.stock||0).toLocaleString('ar-EG'),badge(p.status)]))}`;
    if(tab==='orders') return table(['الطلب','التاريخ','العميل','الحالة','السداد','إجمالي المورد'],orders.map(o=>[o.number,o.createdAt,esc(o.buyerName),badge(o.status),badge(o.paymentStatus),money(o.items.filter(i=>i.supplierId===s.id).reduce((a,i)=>a+i.total,0))]));
    if(tab==='finance') return financeSupplierPanel(s.id);
    if(tab==='documents') return `<div class="panel"><h2>مستندات المورد</h2><p>السجل التجاري: ${esc(s.cr||'-')}</p><p>البطاقة الضريبية: ${esc(s.tax||'-')}</p><div class="notice">رفع الملفات الفعلية يتم عند ربط التخزين السحابي. البيانات الأساسية محفوظة داخل ملف المورد.</div></div>`;
    if(tab==='support') return supportFormHtml('supplier');
    return `<div class="dashboard-grid grid"><div class="metric"><span>الحالة</span><b>${esc(s.status)}</b></div><div class="metric"><span>المنتجات</span><b>${prods.length}</b></div><div class="metric"><span>المناطق</span><b>${s.coverage.length}</b></div><div class="metric"><span>طلبات المورد</span><b>${orders.length}</b></div></div><br>${s.status!=='معتمد'?`<div class="notice">الحساب قيد مراجعة الإدارة. المنتجات لن تظهر في السوق إلا بعد الاعتماد.</div>`:''}`;
  }
  function financeSupplierPanel(sid){
    const l=supplierLedger(sid);
    return `<div class="dashboard-grid grid"><div class="metric"><span>مبيعات مسلمة</span><b>${money(l.sales)}</b></div><div class="metric"><span>استقطاع المنصة</span><b>${money(l.commission)}</b></div><div class="metric"><span>مدفوع للمورد</span><b>${money(l.paid)}</b></div><div class="metric"><span>المتبقي</span><b>${money(l.due)}</b></div></div><br>${table(['التاريخ','المرجع','المبلغ','ملاحظات'],db.payments.filter(p=>p.supplierId===sid).map(p=>[p.date,esc(p.ref),money(p.amount),esc(p.notes)]))}`;
  }

  function admin(params){
    if(!isAdmin()) return accessDenied('لوحة الإدارة');
    const tab=params.tab||'dashboard';
    shell('admin',pageHead('لوحة الإدارة','إدارة السوق والموردين والمنتجات والطلبات والحسابات والإعدادات')+`<section class="section"><div class="container admin-layout grid"><aside class="side">${adminMenu(tab)}</aside><main class="workspace">${adminTab(tab)}</main></div></section>`);
  }
  function adminMenu(tab){
    const items=[['dashboard','الرئيسية'],['users','المستخدمون'],['buyers','المشترون'],['suppliers','الموردون'],['products','المنتجات'],['materials','المواد والفئات'],['orders','الطلبات'],['operations','التشغيل'],['approvals','الاعتمادات'],['inventory','المخزون'],['invoices','الفواتير'],['returns','المرتجعات'],['finance','المالية'],['settlements','التسويات'],['reports','التقارير'],['promotions','العروض'],['notifications','التنبيهات'],['documents','المستندات'],['controls','الرقابة'],['coverage','التغطية'],['support','الدعم'],['content','المحتوى'],['settings','الإعدادات'],['data','البيانات'],['audit','سجل الحركة']];
    return items.map(i=>`<button class="${tab===i[0]?'active':''}" onclick="Tager.go('admin',{tab:'${i[0]}'})"><span>${i[1]}</span><span>›</span></button>`).join('');
  }
  function adminTab(tab){
    if(tab==='users') return adminUsers();
    if(tab==='buyers') return adminBuyers();
    if(tab==='suppliers') return adminSuppliers();
    if(tab==='products') return adminProducts();
    if(tab==='materials') return adminMaterials();
    if(tab==='orders') return adminOrders();
    if(tab==='operations') return adminOperations();
    if(tab==='approvals') return adminApprovals();
    if(tab==='inventory') return adminInventory();
    if(tab==='invoices') return adminInvoices();
    if(tab==='returns') return adminReturns();
    if(tab==='finance') return adminFinance();
    if(tab==='settlements') return adminSettlements();
    if(tab==='reports') return adminReports();
    if(tab==='promotions') return adminPromotions();
    if(tab==='notifications') return adminNotifications();
    if(tab==='documents') return adminDocuments();
    if(tab==='controls') return adminControls();
    if(tab==='coverage') return adminCoverage();
    if(tab==='support') return adminSupport();
    if(tab==='content') return adminContent();
    if(tab==='settings') return adminSettings();
    if(tab==='data') return adminData();
    if(tab==='audit') return table(['التاريخ','المستخدم','الإجراء','التفاصيل'],db.audit.map(l=>[l.date,esc(l.user),esc(l.action),esc(l.details)]));
    return adminDashboard();
  }
  function adminDashboard(){
    const st=stats();
    const ready=[
      ['حساب الإدارة',db.users.some(u=>u.role==='admin'),'تم إنشاء حساب إدارة'],
      ['بيانات التواصل',!!db.settings.whatsapp && db.settings.supportPhones.length>0,'أرقام الدعم والواتساب موجودة'],
      ['الفئات',db.categories.filter(c=>c.active!==false).length>0,'الفئات الرئيسية جاهزة'],
      ['الموردون',db.suppliers.length>0,'سجل الموردين يبدأ بعد التسجيل'],
      ['مناطق التغطية',db.suppliers.some(s=>(s.coverage||[]).length>0),'يجب إضافة التغطية لكل مورد'],
      ['المنتجات',db.products.length>0,'تظهر بعد الإضافة والاعتماد'],
      ['السياسات',!!db.settings.terms && !!db.settings.privacy,'الشروط والخصوصية محفوظة']
    ];
    return `<div class="dashboard-grid grid">
      <div class="metric"><span>إجمالي الطلبات</span><b>${st.allOrders}</b></div>
      <div class="metric"><span>الموردون المعتمدون</span><b>${st.approvedSuppliers}</b></div>
      <div class="metric"><span>المنتجات المنشورة</span><b>${st.publicProducts}</b></div>
      <div class="metric"><span>طلبات دعم مفتوحة</span><b>${st.ticketsOpen}</b></div>
      <div class="metric"><span>اعتمادات معلقة</span><b>${db.suppliers.filter(s=>s.status==='قيد المراجعة').length + db.products.filter(p=>p.status==='قيد المراجعة').length}</b></div>
      <div class="metric"><span>طلبات مفتوحة</span><b>${db.orders.filter(o=>!['تم التسليم','ملغي','مرتجع'].includes(o.status)).length}</b></div>
      <div class="metric"><span>مستحقات غير مسددة</span><b>${money(financeTotals().supplierDue)}</b></div>
      <div class="metric"><span>تنبيهات تحتاج إجراء</span><b>${systemAlerts().length}</b></div>
      <div class="metric"><span>فواتير جاهزة</span><b>${invoiceCandidates().length}</b></div>
      <div class="metric"><span>مرتجعات مفتوحة</span><b>${(db.returns||[]).filter(r=>r.status!=='مغلقة').length}</b></div>
    </div><br>
    <div class="hero-grid grid">
      <div class="panel"><h2>جاهزية التشغيل</h2><p class="muted">قائمة تساعد الإدارة على استكمال المنصة قبل الإطلاق الفعلي.</p><div class="readiness">${ready.map(r=>`<div class="ready-row"><div><b>${r[0]}</b><br><span class="muted">${r[2]}</span></div><span class="check-dot ${r[1]?'ok':'warn'}">${r[1]?'✓':'!'}</span></div>`).join('')}</div></div>
      <div class="panel"><h2>آخر حركة</h2>${db.audit.length?table(['التاريخ','الإجراء'],db.audit.slice(0,6).map(l=>[l.date,esc(l.action)])):empty('لا يوجد سجل حركة','سيتم تسجيل العمليات المهمة هنا.')}</div>
    </div>
    <br><div class="panel"><h2>اختصارات الإدارة</h2><div class="actions"><button class="primary" onclick="Tager.go('admin',{tab:'suppliers'})">إدارة الموردين</button><button class="primary" onclick="Tager.go('admin',{tab:'products'})">إدارة المنتجات</button><button class="softbtn" onclick="Tager.go('admin',{tab:'orders'})">الطلبات</button><button class="softbtn" onclick="Tager.go('admin',{tab:'finance'})">المالية</button><button class="softbtn" onclick="Tager.go('admin',{tab:'settings'})">الإعدادات</button></div></div>`;
  }
  function adminUsers(){return `<div class="section-head"><div><h2>المستخدمون</h2><p>حسابات الإدارة والموردين والمشترين.</p></div><button class="primary" onclick="Tager.userModal()">إضافة مشرف</button></div>${table(['الاسم','الدور','الهاتف','البريد','الحالة','إجراء'],db.users.map(u=>[esc(u.name),roleName(u.role),esc(u.phone),esc(u.email),badge(u.active!==false?'نشط':'موقوف'),`<button class="softbtn smallbtn" onclick="Tager.toggleUser('${u.id}')">${u.active!==false?'إيقاف':'تفعيل'}</button>`]))}`}
  function adminBuyers(){return table(['النشاط','المسؤول','الهاتف','المحافظة','المركز','العنوان','الحالة'],db.buyers.map(b=>[esc(b.name),esc(b.person),esc(b.phone),esc(b.gov),esc(b.center),esc(b.address),badge(b.status||'نشط')]))}
  function adminSuppliers(){return `<div class="section-head"><div><h2>الموردون</h2><p>اعتماد ورفض ومتابعة بيانات الموردين.</p></div></div>${table(['المورد','النشاط','الهاتف','المحافظة','التغطية','الحالة','إجراء'],db.suppliers.map(s=>[esc(s.name),esc(s.activity),esc(s.phone),esc(s.gov),s.coverage.length,badge(s.status),supplierActions(s)]))}`}
  function supplierActions(s){return `<button class="softbtn smallbtn" onclick="Tager.setSupplierStatus('${s.id}','معتمد')">اعتماد</button> <button class="dangerbtn smallbtn" onclick="Tager.setSupplierStatus('${s.id}','موقوف')">إيقاف</button> <button class="softbtn smallbtn" onclick="Tager.go('supplier',{id:'${s.id}'})">عرض</button>`}
  function adminProducts(){return `<div class="section-head"><div><h2>المنتجات</h2><p>مراجعة ونشر وإيقاف المنتجات.</p></div><button class="primary" onclick="Tager.adminProductModal()">إضافة منتج</button></div>${table(['SKU','المنتج','المورد','الفئة','السعر','المخزون','الحالة','إجراء'],db.products.map(p=>{const s=db.suppliers.find(s=>s.id===p.supplierId);return [esc(p.sku),esc(p.name),esc(s?.name),esc(categoryName(p.categoryId)),money(p.retailPrice),Number(p.stock||0).toLocaleString('ar-EG'),badge(p.status),productActions(p)]}))}`}
  function productActions(p){return `<button class="softbtn smallbtn" onclick="Tager.setProductStatus('${p.id}','منشور')">نشر</button> <button class="dangerbtn smallbtn" onclick="Tager.setProductStatus('${p.id}','متوقف')">إيقاف</button> <button class="softbtn smallbtn" onclick="Tager.go('product',{id:'${p.id}'})">عرض</button>`}
  function adminMaterials(){return `<div class="section-head"><div><h2>المواد والفئات</h2><p>تصنيف المنتجات والمواد الرئيسية.</p></div><button class="primary" onclick="Tager.categoryModal()">إضافة فئة</button></div>${table(['الأيقونة','الفئة','الحالة','عدد المنتجات','إجراء'],db.categories.map(c=>[esc(c.icon),esc(c.name),badge(c.active!==false?'نشط':'موقوف'),db.products.filter(p=>p.categoryId===c.id).length,`<button class="softbtn smallbtn" onclick="Tager.toggleCategory('${c.id}')">${c.active!==false?'إيقاف':'تفعيل'}</button>`]))}`}
  function adminOrders(){return table(['رقم الطلب','التاريخ','العميل','العنوان','الحالة','السداد','الإجمالي','إجراء'],db.orders.map(o=>[o.number,o.createdAt,esc(o.buyerName),`${esc(o.gov)} - ${esc(o.center)}`,statusSelect(o),paymentSelect(o),money(o.total),`<button class="softbtn smallbtn" onclick="Tager.printOrder('${o.id}')">عرض</button>`]))}
  function statusSelect(o){return `<select onchange="Tager.updateOrderStatus('${o.id}',this.value)">${orderStatuses.map(s=>`<option ${o.status===s?'selected':''}>${s}</option>`).join('')}</select>`}
  function paymentSelect(o){return `<select onchange="Tager.updatePaymentStatus('${o.id}',this.value)">${paymentStatuses.map(s=>`<option ${o.paymentStatus===s?'selected':''}>${s}</option>`).join('')}</select>`}
  function adminFinance(){
    const totals=financeTotals();
    return `<div class="dashboard-grid grid"><div class="metric"><span>مبيعات مسلمة</span><b>${money(totals.sales)}</b></div><div class="metric"><span>عمولة المنصة</span><b>${money(totals.commission)}</b></div><div class="metric"><span>رسوم السلة المميزة</span><b>${money(totals.premium)}</b></div><div class="metric"><span>مستحقات الموردين</span><b>${money(totals.supplierDue)}</b></div></div><br><div class="section-head"><div><h2>حسابات الموردين</h2><p>الحساب المالي داخلي ولا يظهر للعامة.</p></div><button class="primary" onclick="Tager.paymentModal()">تسجيل دفعة</button></div>${table(['المورد','مبيعات مسلمة','استقطاع المنصة','مدفوع','المتبقي'],db.suppliers.map(s=>{const l=supplierLedger(s.id);return [esc(s.name),money(l.sales),money(l.commission),money(l.paid),money(l.due)]}))}<br><h2>الدفعات</h2>${table(['التاريخ','المورد','المبلغ','المرجع','ملاحظات'],db.payments.map(p=>[p.date,esc(db.suppliers.find(s=>s.id===p.supplierId)?.name),money(p.amount),esc(p.ref),esc(p.notes)]))}`;
  }
  function financeTotals(){
    const delivered=db.orders.filter(o=>o.status==='تم التسليم');
    const sales=delivered.reduce((a,o)=>a+Number(o.subtotal||0),0); const commission=delivered.reduce((a,o)=>a+Number(o.platformCommission||0),0); const premium=delivered.reduce((a,o)=>a+Number(o.premiumFee||0),0); const paid=db.payments.reduce((a,p)=>a+Number(p.amount||0),0);
    return {sales,commission,premium,supplierDue:Math.max(0,sales-commission-paid),paid};
  }
  function supplierLedger(sid){
    let sales=0; db.orders.filter(o=>o.status==='تم التسليم').forEach(o=>o.items.filter(i=>i.supplierId===sid).forEach(i=>sales+=Number(i.total||0)));
    const commission=sales*Number(db.settings.defaultCommission||0)/100; const paid=db.payments.filter(p=>p.supplierId===sid).reduce((a,p)=>a+Number(p.amount||0),0); return {sales,commission,paid,due:Math.max(0,sales-commission-paid)};
  }

  function adminOperations(){
    const open=db.orders.filter(o=>!['تم التسليم','ملغي','مرتجع'].includes(o.status));
    const byStatus=orderStatuses.map(st=>[st,db.orders.filter(o=>o.status===st).length, money(db.orders.filter(o=>o.status===st).reduce((a,o)=>a+Number(o.total||0),0))]);
    const supplierIssues=db.suppliers.filter(s=>s.status==='معتمد' && !(s.coverage||[]).length);
    const stockIssues=db.products.filter(p=>Number(p.stock||0)<=0);
    return `<div class="dashboard-grid grid"><div class="metric"><span>طلبات مفتوحة</span><b>${open.length}</b></div><div class="metric"><span>قيد التجهيز</span><b>${db.orders.filter(o=>o.status==='قيد التجهيز').length}</b></div><div class="metric"><span>بدون تغطية</span><b>${supplierIssues.length}</b></div><div class="metric"><span>مخزون صفر</span><b>${stockIssues.length}</b></div></div><br><div class="hero-grid grid"><div class="panel"><h2>مسار الطلبات</h2>${table(['الحالة','عدد الطلبات','القيمة'],byStatus)}</div><div class="panel"><h2>تنبيهات تشغيل</h2>${operationAlerts()}</div></div><br><div class="panel"><h2>قائمة تشغيل الطلبات</h2>${adminOrders()}</div>`;
  }
  function operationAlerts(){
    const rows=[];
    db.suppliers.filter(s=>s.status==='معتمد' && !(s.coverage||[]).length).forEach(s=>rows.push(['مورد بدون تغطية',esc(s.name),'إضافة مناطق تغطية قبل قبول الطلبات']));
    db.products.filter(p=>Number(p.stock||0)<=0).forEach(p=>rows.push(['منتج بدون مخزون',esc(p.name),'تحديث المخزون أو إيقاف المنتج']));
    db.products.filter(p=>!Number(p.retailPrice||0)).forEach(p=>rows.push(['سعر غير مكتمل',esc(p.name),'تحديث السعر قبل النشر']));
    db.orders.filter(o=>o.status==='جديد').slice(0,8).forEach(o=>rows.push(['طلب جديد',esc(o.number),'مراجعة وتغيير الحالة']));
    return rows.length?table(['النوع','العنصر','المطلوب'],rows):empty('لا توجد تنبيهات تشغيل','النظام لا يرصد مشاكل تشغيلية حالية.');
  }
  function adminApprovals(){
    const ps=db.suppliers.filter(s=>s.status==='قيد المراجعة');
    const pp=db.products.filter(p=>p.status==='قيد المراجعة');
    return `<div class="dashboard-grid grid"><div class="metric"><span>موردون ينتظرون الاعتماد</span><b>${ps.length}</b></div><div class="metric"><span>منتجات تنتظر النشر</span><b>${pp.length}</b></div><div class="metric"><span>موردون موقوفون</span><b>${db.suppliers.filter(s=>s.status==='موقوف').length}</b></div><div class="metric"><span>منتجات موقوفة</span><b>${db.products.filter(p=>p.status==='متوقف').length}</b></div></div><br><div class="hero-grid grid"><div class="panel"><h2>اعتماد الموردين</h2>${ps.length?table(['المورد','النشاط','الهاتف','المحافظة','إجراء'],ps.map(s=>[esc(s.name),esc(s.activity),esc(s.phone),esc(s.gov),supplierActions(s)])):empty('لا يوجد موردون معلقون','كل طلبات الموردين تمت مراجعتها.')}</div><div class="panel"><h2>اعتماد المنتجات</h2>${pp.length?table(['SKU','المنتج','المورد','السعر','إجراء'],pp.map(p=>{const s=db.suppliers.find(s=>s.id===p.supplierId);return [esc(p.sku),esc(p.name),esc(s?.name||'-'),money(p.retailPrice),productActions(p)]})):empty('لا توجد منتجات معلقة','كل المنتجات تمت مراجعتها.')}</div></div>`;
  }
  function adminSettlements(){
    const rows=db.suppliers.map(s=>{const l=supplierLedger(s.id);return [esc(s.name),money(l.sales),money(l.commission),money(l.paid),money(l.due),`<button class="softbtn smallbtn" onclick="Tager.settlementModal('${s.id}')">أمر تسوية</button>`]});
    return `<div class="section-head"><div><h2>تسويات الموردين</h2><p>إنشاء أوامر تسوية داخلية ومتابعة المدفوع والمتبقي.</p></div></div>${table(['المورد','مبيعات مسلمة','استقطاع المنصة','مدفوع','المتبقي','إجراء'],rows)}<br><div class="panel"><h2>أوامر التسوية</h2>${(db.settlements||[]).length?table(['التاريخ','المورد','الفترة','المبلغ','الحالة','مرجع'],db.settlements.map(x=>[x.date,esc(db.suppliers.find(s=>s.id===x.supplierId)?.name||'-'),esc((x.from||'-')+' إلى '+(x.to||'-')),money(x.amount),badge(x.status),esc(x.ref||'-')])):empty('لا توجد أوامر تسوية','أنشئ أمر تسوية من جدول الموردين.')}</div>`;
  }
  function settlementModal(sid){
    const s=db.suppliers.find(s=>s.id===sid); if(!s)return;
    const l=supplierLedger(sid);
    modal(`<h2>أمر تسوية مورد</h2><p class="muted">${esc(s.name)} - المتبقي الحالي ${money(l.due)}</p><div class="form-grid"><div class="field"><label>من تاريخ</label><input id="stFrom" type="date" class="input"></div><div class="field"><label>إلى تاريخ</label><input id="stTo" type="date" class="input"></div><div class="field"><label>المبلغ</label><input id="stAmount" type="number" class="input" value="${Number(l.due||0).toFixed(2)}"></div><div class="field"><label>مرجع داخلي</label><input id="stRef" class="input"></div><div class="field full"><label>ملاحظات</label><textarea id="stNotes" rows="3"></textarea></div></div><div class="actions"><button class="primary" onclick="Tager.saveSettlement('${sid}')">حفظ أمر التسوية</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`);
  }
  function saveSettlement(sid){
    const amount=Number(val('stAmount')||0); if(!amount)return toast('اكتب مبلغ التسوية','danger');
    db.settlements=db.settlements||[];
    db.settlements.unshift({id:uid('set'),supplierId:sid,from:val('stFrom'),to:val('stTo'),amount,ref:val('stRef'),notes:val('stNotes'),status:'مسودة',date:today(),createdBy:currentUser()?.id});
    save(); log('إنشاء أمر تسوية',money(amount)); closeModal(); go('admin',{tab:'settlements'});
  }
  function adminReports(){
    const delivered=db.orders.filter(o=>o.status==='تم التسليم');
    const sales=delivered.reduce((a,o)=>a+Number(o.total||0),0);
    const avg=delivered.length?sales/delivered.length:0;
    const supplierRows=db.suppliers.map(s=>{let v=0,c=0; delivered.forEach(o=>o.items.filter(i=>i.supplierId===s.id).forEach(i=>{v+=Number(i.total||0);c+=Number(i.qty||0)})); return [esc(s.name),c,money(v),money(v*Number(db.settings.defaultCommission||0)/100)]}).filter(r=>r[1]||r[2]!==money(0));
    const catMap={}; delivered.forEach(o=>o.items.forEach(i=>{catMap[i.category]=(catMap[i.category]||0)+Number(i.total||0)}));
    const catRows=Object.keys(catMap).map(k=>[esc(k),money(catMap[k])]);
    return `<div class="dashboard-grid grid"><div class="metric"><span>طلبات مسلمة</span><b>${delivered.length}</b></div><div class="metric"><span>إجمالي المبيعات</span><b>${money(sales)}</b></div><div class="metric"><span>متوسط الطلب</span><b>${money(avg)}</b></div><div class="metric"><span>الخصومات</span><b>${money(db.orders.reduce((a,o)=>a+Number(o.discount||0),0))}</b></div></div><br><div class="hero-grid grid"><div class="panel"><h2>أداء الموردين</h2>${supplierRows.length?table(['المورد','الكمية','القيمة','عمولة تقديرية'],supplierRows):empty('لا توجد مبيعات مسلمة','ستظهر بعد تسليم الطلبات.')}</div><div class="panel"><h2>المبيعات حسب الفئة</h2>${catRows.length?table(['الفئة','القيمة'],catRows):empty('لا توجد بيانات فئات','ستظهر بعد تسليم الطلبات.')}</div></div><br><div class="panel"><h2>تصدير التقارير</h2><div class="actions"><button class="primary" onclick="Tager.exportCsv('orders')">تصدير الطلبات</button><button class="softbtn" onclick="Tager.exportCsv('products')">تصدير المنتجات</button><button class="softbtn" onclick="Tager.exportCsv('suppliers')">تصدير الموردين</button><button class="softbtn" onclick="Tager.exportCsv('payments')">تصدير الدفعات</button></div></div>`;
  }
  function adminPromotions(){
    return `<div class="section-head"><div><h2>العروض والكوبونات</h2><p>أكواد خصم اختيارية يتم إدخالها عند إتمام الطلب.</p></div><button class="primary" onclick="Tager.couponModal()">إضافة كود</button></div>${(db.coupons||[]).length?table(['الكود','النوع','القيمة','الاستخدام','الحالة','إجراء'],db.coupons.map(c=>[esc(c.code),c.type==='percent'?'نسبة':'مبلغ',c.type==='percent'?esc(c.value+'%'):money(c.value),Number(c.used||0).toLocaleString('ar-EG'),badge(c.active!==false?'نشط':'موقوف'),`<button class="softbtn smallbtn" onclick="Tager.toggleCoupon('${c.id}')">${c.active!==false?'إيقاف':'تفعيل'}</button>`])):empty('لا توجد أكواد خصم','يمكن إضافة كود خصم بدون بيانات مسبقة.')}`;
  }
  function couponModal(){modal(`<h2>إضافة كود خصم</h2><div class="form-grid"><div class="field"><label>الكود</label><input id="cpCode" class="input" placeholder="مثال: TAGER10"></div><div class="field"><label>النوع</label><select id="cpType"><option value="percent">نسبة</option><option value="fixed">مبلغ</option></select></div><div class="field"><label>القيمة</label><input id="cpValue" type="number" class="input"></div><div class="field"><label>ملاحظات</label><input id="cpNotes" class="input"></div></div><div class="actions"><button class="primary" onclick="Tager.saveCoupon()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function saveCoupon(){const code=val('cpCode').toUpperCase(); const value=Number(val('cpValue')||0); if(!code||!value)return toast('استكمل الكود والقيمة','danger'); db.coupons=db.coupons||[]; db.coupons.unshift({id:uid('cup'),code,type:val('cpType'),value,notes:val('cpNotes'),active:true,used:0,createdAt:today()}); save(); log('إضافة كود خصم',code); closeModal(); go('admin',{tab:'promotions'});}
  function toggleCoupon(id){const c=(db.coupons||[]).find(c=>c.id===id); if(!c)return; c.active=!(c.active!==false); save(); log('تغيير حالة كود خصم',c.code); go('admin',{tab:'promotions'});}
  function adminControls(){
    const rows=[];
    rows.push(['اللوجو والهوية',true,'اللوجو موجود في الهيدر والفوتر والأيقونة']);
    rows.push(['أرقام الدعم',!!db.settings.whatsapp && db.settings.supportPhones.length>0,'واتساب وأرقام الدعم داخل الإعدادات']);
    rows.push(['فئات نشطة',db.categories.filter(c=>c.active!==false).length>0,'وجود فئات رئيسية للمواد']);
    rows.push(['موردون معتمدون لهم تغطية',!db.suppliers.some(s=>s.status==='معتمد' && !(s.coverage||[]).length),'أي مورد معتمد يجب أن يملك مناطق تغطية']);
    rows.push(['منتجات منشورة لها سعر',!db.products.some(p=>p.status==='منشور' && !Number(p.retailPrice||0)),'أي منتج منشور يجب أن يملك سعر صحيح']);
    rows.push(['منتجات منشورة لها مورد معتمد',!db.products.some(p=>p.status==='منشور' && !approvedSuppliers().some(s=>s.id===p.supplierId)),'المنتج لا يظهر إلا مع مورد معتمد']);
    rows.push(['إعدادات مالية داخلية',Number(db.settings.defaultCommission||0)>=0,'العمولة والرسوم داخل لوحة الإدارة فقط']);
    return `<div class="panel"><h2>رقابة جاهزية النظام</h2><p class="muted">قائمة فحص داخلية قبل التشغيل الفعلي.</p>${table(['البند','الحالة','الملاحظة'],rows.map(r=>[esc(r[0]),r[1]?badge('نشط'):badge('قيد المراجعة'),esc(r[2])]))}</div><br><div class="hero-grid grid"><div class="panel"><h2>نواقص الموردين</h2>${db.suppliers.filter(s=>s.status==='معتمد' && !(s.coverage||[]).length).length?table(['المورد','المشكلة'],db.suppliers.filter(s=>s.status==='معتمد' && !(s.coverage||[]).length).map(s=>[esc(s.name),'لا توجد مناطق تغطية'])):empty('لا توجد نواقص موردين','كل الموردين المعتمدين لديهم تغطية أو لا يوجد موردون معتمدون.')}</div><div class="panel"><h2>نواقص المنتجات</h2>${db.products.filter(p=>!Number(p.retailPrice||0)||Number(p.stock||0)<=0).length?table(['SKU','المنتج','المشكلة'],db.products.filter(p=>!Number(p.retailPrice||0)||Number(p.stock||0)<=0).map(p=>[esc(p.sku),esc(p.name),!Number(p.retailPrice||0)?'سعر غير مكتمل':'مخزون صفر'])):empty('لا توجد نواقص منتجات','لا توجد منتجات بسعر ناقص أو مخزون صفر.')}</div></div>`;
  }

  function adminCoverage(){return table(['المورد','المحافظة','المركز','رسوم التوصيل','حد أدنى','أيام'],db.suppliers.flatMap(s=>(s.coverage||[]).map(c=>[esc(s.name),esc(c.gov),esc(c.center),money(c.shipping),money(c.minOrder),esc(c.days||0)])))}
  function adminSupport(){return table(['رقم','التاريخ','النوع','الاسم','الهاتف','الموضوع','الحالة','إجراء'],db.tickets.map(t=>[t.number,t.createdAt,esc(t.type),esc(t.name),esc(t.phone),esc(t.subject),badge(t.status),`<button class="softbtn smallbtn" onclick="Tager.closeTicket('${t.id}')">إغلاق</button>`]))}
  function adminContent(){return `<div class="panel"><h2>محتوى الصفحة الرئيسية والسياسات</h2><div class="field"><label>عنوان الصفحة الرئيسية</label><input id="cHomeTitle" class="input" value="${esc(db.content.homeTitle)}"></div><div class="field"><label>وصف الصفحة الرئيسية</label><textarea id="cHomeText" rows="3">${esc(db.content.homeText)}</textarea></div><div class="field"><label>رسالة المورد</label><textarea id="cSupplier" rows="3">${esc(db.content.supplierMessage)}</textarea></div><div class="field"><label>رسالة المشتري</label><textarea id="cBuyer" rows="3">${esc(db.content.buyerMessage)}</textarea></div><div class="field"><label>الشروط</label><textarea id="cTerms" rows="5">${esc(db.settings.terms)}</textarea></div><div class="field"><label>الخصوصية</label><textarea id="cPrivacy" rows="5">${esc(db.settings.privacy)}</textarea></div><button class="primary" onclick="Tager.saveContent()">حفظ المحتوى</button></div>`}
  function adminSettings(){return `<div class="panel"><h2>إعدادات المنصة</h2><div class="form-grid"><div class="field"><label>اسم المنصة</label><input id="setBrand" class="input" value="${esc(db.settings.brand)}"></div><div class="field"><label>الدولة</label><input id="setCountry" class="input" value="${esc(db.settings.country)}"></div><div class="field"><label>العملة</label><input id="setCurrency" class="input" value="${esc(db.settings.currency)}"></div><div class="field"><label>اسم العملة الظاهر</label><input id="setCurrencyLabel" class="input" value="${esc(db.settings.currencyLabel)}"></div><div class="field"><label>واتساب</label><input id="setWhatsapp" class="input" value="${esc(db.settings.whatsapp)}"></div><div class="field"><label>البريد</label><input id="setEmail" class="input" value="${esc(db.settings.email)}"></div><div class="field full"><label>أرقام الدعم - رقم في كل سطر</label><textarea id="setPhones" rows="3">${esc(db.settings.supportPhones.join('\n'))}</textarea></div></div><h2>الإعدادات المالية الداخلية</h2><div class="notice">هذه الإعدادات تظهر للإدارة فقط ولا تظهر في تسجيل المورد أو الصفحات العامة.</div><div class="form-grid"><div class="field"><label>عمولة المنصة %</label><input id="setCommission" type="number" step="0.01" class="input" value="${db.settings.defaultCommission}"></div><div class="field"><label>رسوم السلة المميزة %</label><input id="setPremium" type="number" step="0.01" class="input" value="${db.settings.premiumBasketFee}"></div><div class="field"><label>رسوم توصيل افتراضية</label><input id="setShip" type="number" class="input" value="${db.settings.shippingBase}"></div><div class="field"><label>حد الشحن المجاني</label><input id="setFree" type="number" class="input" value="${db.settings.freeShippingLimit}"></div><div class="field"><label>دورة تسوية المورد</label><input id="setCycle" class="input" value="${esc(db.settings.settlementCycle)}"></div><div class="field"><label>بادئة رقم الطلب</label><input id="setPrefix" class="input" value="${esc(db.settings.orderPrefix)}"></div><div class="field"><label>بادئة رقم الفاتورة</label><input id="setInvoicePrefix" class="input" value="${esc(db.settings.invoicePrefix||'INV')}"></div><div class="field"><label>نسبة الضريبة %</label><input id="setTax" type="number" step="0.01" class="input" value="${Number(db.settings.taxRate||0)}"></div><div class="field"><label>حد تنبيه المخزون</label><input id="setLowStock" type="number" class="input" value="${Number(db.settings.lowStockThreshold||5)}"></div><div class="field"><label>مدة المرتجع بالأيام</label><input id="setReturnDays" type="number" class="input" value="${Number(db.settings.returnWindowDays||7)}"></div></div><button class="primary" onclick="Tager.saveSettings()">حفظ الإعدادات</button></div>`}
  function adminData(){return `<div class="hero-grid grid"><div class="panel"><h2>تصدير البيانات</h2><div class="actions"><button class="primary" onclick="Tager.exportJson()">ملف احتياطي JSON</button><button class="softbtn" onclick="Tager.exportCsv('orders')">طلبات CSV</button><button class="softbtn" onclick="Tager.exportCsv('products')">منتجات CSV</button><button class="softbtn" onclick="Tager.exportCsv('suppliers')">موردون CSV</button><button class="softbtn" onclick="Tager.exportCsv('payments')">دفعات CSV</button><button class="softbtn" onclick="Tager.exportCsv('settlements')">تسويات CSV</button><button class="softbtn" onclick="Tager.exportCsv('coupons')">كوبونات CSV</button></div></div><div class="panel"><h2>استيراد ملف احتياطي</h2><input id="importFile" type="file" accept="application/json" class="input"><br><br><button class="primary" onclick="Tager.importJson()">استيراد</button><div class="notice" style="margin-top:14px">سيتم استبدال البيانات الحالية بمحتوى الملف بعد التحقق.</div></div></div><br><div class="panel"><h2>قوالب فارغة</h2><div class="actions"><button class="softbtn" onclick="Tager.templateCsv('products')">قالب منتجات</button><button class="softbtn" onclick="Tager.templateCsv('suppliers')">قالب موردين</button><button class="softbtn" onclick="Tager.templateCsv('coverage')">قالب تغطية</button><button class="softbtn" onclick="Tager.templateCsv('payments')">قالب دفعات</button></div></div>`}

  function support(){shell('support',pageHead('الدعم','تواصل مع فريق المنصة أو سجل طلب متابعة')+`<section class="section"><div class="container hero-grid grid">${supportFormHtml('public')}<div class="panel"><h2>قنوات التواصل</h2><div class="mini-list"><div class="mini-item"><span>واتساب</span><b class="ltr">${esc(db.settings.whatsapp)}</b></div><div class="mini-item"><span>الهاتف</span><b>${esc(db.settings.supportPhones.join(' / '))}</b></div><div class="mini-item"><span>البريد</span><b>${esc(db.settings.email)}</b></div></div></div></div></section>`)}
  function supportFormHtml(type){return `<div class="panel"><h2>طلب دعم</h2><div class="form-grid"><div class="field"><label>الاسم</label><input id="tName" class="input" value="${esc(currentUser()?.name||'')}"></div><div class="field"><label>الهاتف</label><input id="tPhone" class="input" value="${esc(currentUser()?.phone||'')}"></div><div class="field"><label>النوع</label><select id="tType"><option>${type==='supplier'?'مورد':type==='buyer'?'مشتري':'طلب عام'}</option><option>طلب</option><option>حساب مالي</option><option>مشكلة تقنية</option><option>اقتراح</option></select></div><div class="field"><label>الموضوع</label><input id="tSubject" class="input"></div><div class="field full"><label>التفاصيل</label><textarea id="tMessage" rows="5"></textarea></div></div><button class="primary" onclick="Tager.sendTicket()">إرسال</button></div>`}
  function sendTicket(){const name=val('tName'),phone=val('tPhone'),subject=val('tSubject'); if(!name||!phone||!subject) return toast('استكمل بيانات الدعم','danger'); db.tickets.unshift({id:uid('tic'),number:'SUP-'+Date.now().toString().slice(-6),name,phone,type:val('tType'),subject,message:val('tMessage'),status:'مفتوحة',createdAt:today(),userId:currentUser()?.id||null}); save(); log('طلب دعم',subject); toast('تم إرسال طلب الدعم'); go('support')}
  function policies(){shell('policies',pageHead('السياسات والشروط','إطار التعامل بين المنصة والمورد والمشتري')+`<section class="section"><div class="container"><div class="panel"><h2>الشروط العامة</h2><p>${esc(db.settings.terms)}</p><h2>سياسة الخصوصية</h2><p>${esc(db.settings.privacy)}</p><h2>سياسة المورد</h2><p>يلتزم المورد بصحة الأسعار والمخزون ومناطق التغطية والحد الأدنى ومدة التجهيز، ولا تظهر منتجاته إلا بعد اعتماد الإدارة.</p><h2>سياسة المشتري</h2><p>يلتزم المشتري بصحة العنوان وبيانات التواصل واستلام الطلب حسب الحالة المعتمدة.</p><h2>الحسابات</h2><p>المستحقات والعمولات والدفعات تتم مراجعتها داخل لوحة الإدارة، ولا تظهر الإعدادات المالية الداخلية في الصفحات العامة.</p></div></div></section>`)}

  function accessDenied(title){shell('login',pageHead(title,'الدخول مطلوب')+`<section class="section"><div class="container">${empty('غير مسموح بالدخول','سجل الدخول بالحساب المناسب للوصول لهذه الصفحة.',`<button class="primary" onclick="Tager.go('login')">تسجيل الدخول</button>`)}</div></section>`)}
  function tabBtn(page,id,label,active){return `<button class="tab ${active===id?'active':''}" onclick="Tager.go('${page}',{tab:'${id}'})">${label}</button>`}
  function roleName(r){return r==='admin'?'إدارة':r==='supplier'?'مورد':'مشتري'}
  function govSelect(id,allowBlank){return `<select id="${id}">${allowBlank?'<option value="">كل المحافظات</option>':''}${Object.keys(governorates).map(g=>`<option value="${g}">${g}</option>`).join('')}</select>`}
  function bindGov(govId,centerId,blank){const gov=document.getElementById(govId),center=document.getElementById(centerId); if(!gov||!center)return; function fill(){const arr=governorates[gov.value]||[]; center.innerHTML=(blank?'<option value="">كل المراكز</option>':'')+arr.map(c=>`<option value="${c}">${c}</option>`).join('');} gov.onchange=fill; fill();}

  function modal(html){closeModal();const d=document.createElement('div');d.className='modal-backdrop';d.id='modal';d.innerHTML=`<div class="modal">${html}</div>`;document.body.appendChild(d)}
  function closeModal(){document.getElementById('modal')?.remove()}
  function userModal(){modal(`<h2>إضافة مشرف</h2><div class="form-grid"><div class="field"><label>الاسم</label><input id="uName" class="input"></div><div class="field"><label>الهاتف</label><input id="uPhone" class="input"></div><div class="field"><label>البريد</label><input id="uEmail" class="input"></div><div class="field"><label>كلمة المرور</label><input id="uPass" type="password" class="input"></div></div><div class="actions"><button class="primary" onclick="Tager.saveAdminUser()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function saveAdminUser(){const name=val('uName'),phone=val('uPhone'),pass=val('uPass'); if(!name||!phone||!pass)return toast('استكمل البيانات','danger'); db.users.push({id:uid('usr'),role:'admin',name,phone,email:val('uEmail'),password:pass,active:true,createdAt:today()}); save(); log('إضافة مشرف',name); closeModal(); go('admin',{tab:'users'});}
  function coverageModal(){const s=mySupplier(); if(!s)return; modal(`<h2>إضافة منطقة تغطية</h2><div class="form-grid"><div class="field"><label>المحافظة</label>${govSelect('covGov')}</div><div class="field"><label>المركز</label><select id="covCenter"></select></div><div class="field"><label>رسوم التوصيل</label><input id="covShip" type="number" class="input"></div><div class="field"><label>حد أدنى للطلب</label><input id="covMin" type="number" class="input"></div><div class="field"><label>أيام التوصيل</label><input id="covDays" type="number" class="input"></div></div><div class="actions"><button class="primary" onclick="Tager.saveCoverage()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`); bindGov('covGov','covCenter');}
  function saveCoverage(){const s=mySupplier(); if(!s)return; s.coverage.push({id:uid('cov'),gov:val('covGov'),center:val('covCenter'),shipping:Number(val('covShip')||0),minOrder:Number(val('covMin')||0),days:Number(val('covDays')||0)}); save(); log('إضافة تغطية',s.name); closeModal(); go('vendor',{tab:'coverage'});}
  function productModal(adminMode){
    let supplierSelect='';
    if(adminMode){supplierSelect=`<div class="field full"><label>المورد</label><select id="pSupplier">${db.suppliers.map(s=>`<option value="${s.id}">${esc(s.name)}</option>`).join('')}</select></div>`; if(!db.suppliers.length)return toast('أضف أو اعتمد مورد أولًا','danger');}
    modal(`<h2>إضافة منتج</h2><div class="form-grid">${supplierSelect}<div class="field"><label>اسم المنتج</label><input id="pName" class="input"></div><div class="field"><label>SKU</label><input id="pSku" class="input"></div><div class="field"><label>الفئة</label><select id="pCat">${db.categories.filter(c=>c.active!==false).map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select></div><div class="field"><label>الوحدة</label><input id="pUnit" class="input" placeholder="كرتونة / قطعة / كيلو"></div><div class="field"><label>سعر أقل كمية</label><input id="pRetail" type="number" class="input"></div><div class="field"><label>سعر الجملة</label><input id="pWhole" type="number" class="input"></div><div class="field"><label>حد الجملة</label><input id="pWholeMin" type="number" class="input"></div><div class="field"><label>سعر جملة الجملة</label><input id="pSuper" type="number" class="input"></div><div class="field"><label>حد جملة الجملة</label><input id="pSuperMin" type="number" class="input"></div><div class="field"><label>المخزون</label><input id="pStock" type="number" class="input"></div><div class="field full"><label>رابط صورة المنتج</label><input id="pImage" class="input"></div><div class="field full"><label>الوصف</label><textarea id="pDesc" rows="4"></textarea></div></div><div class="actions"><button class="primary" onclick="Tager.saveProduct(${adminMode?'true':'false'})">حفظ المنتج</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`);
  }
  function adminProductModal(){productModal(true)}
  function saveProduct(adminMode){
    const name=val('pName'), price=Number(val('pRetail')||0); if(!name||!price)return toast('استكمل اسم المنتج والسعر','danger');
    const sid=adminMode?val('pSupplier'):mySupplier()?.id; if(!sid)return toast('لا يوجد مورد مرتبط','danger');
    const p={id:uid('prd'),supplierId:sid,name,sku:val('pSku')||('SKU-'+Date.now().toString().slice(-6)),categoryId:val('pCat'),unit:val('pUnit')||'قطعة',retailPrice:price,wholesalePrice:Number(val('pWhole')||price),wholesaleMinQty:Number(val('pWholeMin')||0),superPrice:Number(val('pSuper')||val('pWhole')||price),superMinQty:Number(val('pSuperMin')||0),stock:Number(val('pStock')||0),image:val('pImage'),description:val('pDesc'),status:adminMode?'منشور':'قيد المراجعة',createdAt:today()};
    db.products.unshift(p); save(); log('إضافة منتج',name); closeModal(); go(adminMode?'admin':'vendor',{tab:'products'});
  }
  function categoryModal(){modal(`<h2>إضافة فئة</h2><div class="field"><label>اسم الفئة</label><input id="catName" class="input"></div><div class="field"><label>الأيقونة</label><input id="catIcon" class="input" value="📦"></div><div class="actions"><button class="primary" onclick="Tager.saveCategory()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function saveCategory(){const name=val('catName'); if(!name)return toast('اكتب اسم الفئة','danger'); db.categories.push({id:uid('cat'),name,icon:val('catIcon')||'📦',active:true}); save(); log('إضافة فئة',name); closeModal(); go('admin',{tab:'materials'});}
  function paymentModal(){if(!db.suppliers.length)return toast('لا يوجد موردون','danger'); modal(`<h2>تسجيل دفعة مورد</h2><div class="form-grid"><div class="field"><label>المورد</label><select id="paySupplier">${db.suppliers.map(s=>`<option value="${s.id}">${esc(s.name)}</option>`).join('')}</select></div><div class="field"><label>المبلغ</label><input id="payAmount" type="number" class="input"></div><div class="field"><label>المرجع</label><input id="payRef" class="input"></div><div class="field full"><label>ملاحظات</label><textarea id="payNotes" rows="3"></textarea></div></div><div class="actions"><button class="primary" onclick="Tager.savePayment()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function savePayment(){const amount=Number(val('payAmount')||0); if(!amount)return toast('اكتب المبلغ','danger'); db.payments.unshift({id:uid('pay'),supplierId:val('paySupplier'),amount,ref:val('payRef'),notes:val('payNotes'),date:today(),createdBy:currentUser()?.id}); save(); log('تسجيل دفعة',money(amount)); closeModal(); go('admin',{tab:'finance'});}
  function setSupplierStatus(id,status){const s=db.suppliers.find(s=>s.id===id); if(!s)return; s.status=status; save(); log('تغيير حالة مورد',`${s.name} - ${status}`); go('admin',{tab:'suppliers'});}
  function setProductStatus(id,status){const p=db.products.find(p=>p.id===id); if(!p)return; p.status=status; save(); log('تغيير حالة منتج',`${p.name} - ${status}`); go('admin',{tab:'products'});}
  function toggleUser(id){const u=db.users.find(u=>u.id===id); if(!u)return; u.active=!(u.active!==false); save(); log('تغيير حالة مستخدم',u.name); go('admin',{tab:'users'});}
  function toggleCategory(id){const c=db.categories.find(c=>c.id===id); if(!c)return; c.active=!(c.active!==false); save(); log('تغيير حالة فئة',c.name); go('admin',{tab:'materials'});}
  function updateOrderStatus(id,status){const o=db.orders.find(o=>o.id===id); if(!o)return; o.status=status; save(); log('تغيير حالة طلب',`${o.number} - ${status}`); go('admin',{tab:'orders'});}
  function updatePaymentStatus(id,status){const o=db.orders.find(o=>o.id===id); if(!o)return; o.paymentStatus=status; save(); log('تغيير حالة سداد',`${o.number} - ${status}`); go('admin',{tab:'orders'});}
  function saveContent(){db.content.homeTitle=val('cHomeTitle'); db.content.homeText=val('cHomeText'); db.content.supplierMessage=val('cSupplier'); db.content.buyerMessage=val('cBuyer'); db.settings.terms=val('cTerms'); db.settings.privacy=val('cPrivacy'); save(); log('تحديث المحتوى'); toast('تم حفظ المحتوى'); go('admin',{tab:'content'});}
  function saveSettings(){Object.assign(db.settings,{brand:val('setBrand'),country:val('setCountry'),currency:val('setCurrency'),currencyLabel:val('setCurrencyLabel'),whatsapp:val('setWhatsapp'),email:val('setEmail'),supportPhones:val('setPhones').split(/\n|,/).map(x=>x.trim()).filter(Boolean),defaultCommission:Number(val('setCommission')||0),premiumBasketFee:Number(val('setPremium')||0),shippingBase:Number(val('setShip')||0),freeShippingLimit:Number(val('setFree')||0),settlementCycle:val('setCycle'),orderPrefix:val('setPrefix'), invoicePrefix:val('setInvoicePrefix')||'INV', taxRate:Number(val('setTax')||0), lowStockThreshold:Number(val('setLowStock')||5), returnWindowDays:Number(val('setReturnDays')||7)}); save(); log('تحديث الإعدادات'); toast('تم حفظ الإعدادات'); go('admin',{tab:'settings'});}
  function closeTicket(id){const t=db.tickets.find(t=>t.id===id); if(!t)return; t.status='مغلقة'; save(); log('إغلاق طلب دعم',t.number); go('admin',{tab:'support'});}
  function printOrder(id){const o=db.orders.find(o=>o.id===id); if(!o)return; modal(`<div class="print-area"><div style="text-align:center"><img src="./assets/tager-logo.png" style="width:220px;max-width:100%"></div><h2>طلب رقم ${esc(o.number)}</h2><p>التاريخ: ${esc(o.createdAt)}</p><p>العميل: ${esc(o.buyerName)} · ${esc(o.phone)}</p><p>العنوان: ${esc(o.gov)} - ${esc(o.center)} - ${esc(o.address)}</p>${table(['الصنف','الكمية','السعر','الإجمالي'],o.items.map(i=>[esc(i.name),i.qty,money(i.price),money(i.total)]))}<div class="mini-list" style="margin-top:16px"><div class="mini-item"><span>الإجمالي قبل التوصيل</span><b>${money(o.subtotal)}</b></div><div class="mini-item"><span>التوصيل</span><b>${money(o.shipping)}</b></div><div class="mini-item"><span>السلة المميزة</span><b>${money(o.premiumFee)}</b></div><div class="mini-item"><span>الخصم</span><b>${money(o.discount||0)}</b></div><div class="mini-item"><span>الضريبة</span><b>${money(o.tax||0)}</b></div><div class="mini-item"><span>الإجمالي</span><b>${money(o.total)}</b></div></div></div><div class="actions no-print"><button class="primary" onclick="window.print()">طباعة</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function exportJson(){download('tager-backup.json',JSON.stringify(db,null,2),'application/json;charset=utf-8')}
  function importJson(){const f=document.getElementById('importFile')?.files?.[0]; if(!f)return toast('اختر ملف JSON','danger'); const r=new FileReader(); r.onload=()=>{try{const data=JSON.parse(r.result); if(!data.settings||!Array.isArray(data.users)) throw new Error('invalid'); db=data; save(); log('استيراد بيانات'); toast('تم الاستيراد'); route();}catch(e){toast('ملف غير صالح','danger')}}; r.readAsText(f);}
  function exportCsv(type){
    const rows={
      orders:[['number','date','buyer','phone','governorate','center','status','payment_status','subtotal','shipping','tax','total'],...db.orders.map(o=>[o.number,o.createdAt,o.buyerName,o.phone,o.gov,o.center,o.status,o.paymentStatus,o.subtotal,o.shipping,o.tax||0,o.total])],
      products:[['sku','name','supplier','category','unit','retail_price','wholesale_price','wholesale_min_qty','super_price','super_min_qty','stock','status'],...db.products.map(p=>[p.sku,p.name,db.suppliers.find(s=>s.id===p.supplierId)?.name,categoryName(p.categoryId),p.unit,p.retailPrice,p.wholesalePrice,p.wholesaleMinQty,p.superPrice,p.superMinQty,p.stock,p.status])],
      suppliers:[['name','person','phone','whatsapp','email','activity','governorate','center','min_order','status'],...db.suppliers.map(s=>[s.name,s.person,s.phone,s.whatsapp,s.email,s.activity,s.gov,s.center,s.minOrder,s.status])],
      payments:[['date','supplier','amount','ref','notes'],...db.payments.map(p=>[p.date,db.suppliers.find(s=>s.id===p.supplierId)?.name,p.amount,p.ref,p.notes])],
      settlements:[['date','supplier','from','to','amount','status','ref'],...(db.settlements||[]).map(x=>[x.date,db.suppliers.find(s=>s.id===x.supplierId)?.name,x.from,x.to,x.amount,x.status,x.ref])],
      coupons:[['code','type','value','used','active'],...(db.coupons||[]).map(c=>[c.code,c.type,c.value,c.used,c.active!==false])]
    }[type]||[]; download(`tager-${type}.csv`,csv(rows),'text/csv;charset=utf-8');
  }
  function templateCsv(type){
    const rows={products:[['sku','name','supplier_phone','category','unit','retail_price','wholesale_price','wholesale_min_qty','super_price','super_min_qty','stock','description']],suppliers:[['name','person','phone','whatsapp','email','activity','commercial_register','tax_card','governorate','center','min_order']],coverage:[['supplier_phone','governorate','center','shipping_fee','min_order','delivery_days']],payments:[['supplier_phone','amount','reference','notes']]}[type];
    download(`template-${type}.csv`,csv(rows),'text/csv;charset=utf-8');
  }


  function systemAlerts(){
    const rows=[];
    db.suppliers.filter(s=>s.status==='قيد المراجعة').forEach(s=>rows.push({type:'اعتماد مورد',title:s.name,detail:'مورد جديد يحتاج مراجعة واعتماد.',action:`Tager.go('admin',{tab:'approvals'})`}));
    db.products.filter(p=>p.status==='قيد المراجعة').forEach(p=>rows.push({type:'اعتماد منتج',title:p.name,detail:'منتج جديد يحتاج مراجعة قبل النشر.',action:`Tager.go('admin',{tab:'approvals'})`}));
    db.suppliers.filter(s=>s.status==='معتمد' && !(s.coverage||[]).length).forEach(s=>rows.push({type:'تغطية ناقصة',title:s.name,detail:'مورد معتمد بدون مناطق تغطية.',action:`Tager.go('admin',{tab:'coverage'})`}));
    db.products.filter(p=>p.status==='منشور' && Number(p.stock||0)<=Number(db.settings.lowStockThreshold||5)).forEach(p=>rows.push({type:'مخزون منخفض',title:p.name,detail:`المخزون الحالي ${Number(p.stock||0).toLocaleString('ar-EG')}.`,action:`Tager.go('admin',{tab:'inventory'})`}));
    db.orders.filter(o=>o.status==='جديد').forEach(o=>rows.push({type:'طلب جديد',title:o.number,detail:'طلب جديد يحتاج مراجعة تشغيلية.',action:`Tager.go('admin',{tab:'orders'})`}));
    (db.tickets||[]).filter(t=>t.status==='مفتوحة').forEach(t=>rows.push({type:'دعم مفتوح',title:t.number,detail:t.subject||'طلب دعم مفتوح',action:`Tager.go('admin',{tab:'support'})`}));
    (db.returns||[]).filter(r=>r.status!=='مغلقة').forEach(r=>rows.push({type:'مرتجع مفتوح',title:r.number,detail:r.reason||'مرتجع يحتاج مراجعة',action:`Tager.go('admin',{tab:'returns'})`}));
    return rows;
  }

  function adminNotifications(){
    const alerts=systemAlerts();
    const rows=alerts.map(a=>[esc(a.type),esc(a.title),esc(a.detail),`<button class="softbtn smallbtn" onclick="${a.action}">فتح</button>`]);
    const saved=(db.notifications||[]).map(n=>[esc(n.type||'تنبيه'),esc(n.title),esc(n.message),badge(n.status||'نشط')]);
    return `<div class="dashboard-grid grid"><div class="metric"><span>تنبيهات النظام</span><b>${alerts.length}</b></div><div class="metric"><span>تنبيهات محفوظة</span><b>${(db.notifications||[]).length}</b></div><div class="metric"><span>طلبات دعم مفتوحة</span><b>${(db.tickets||[]).filter(t=>t.status==='مفتوحة').length}</b></div><div class="metric"><span>مخزون منخفض</span><b>${db.products.filter(p=>Number(p.stock||0)<=Number(db.settings.lowStockThreshold||5)).length}</b></div></div><br><div class="panel"><h2>التنبيهات المطلوبة الآن</h2>${rows.length?table(['النوع','العنصر','التفاصيل','إجراء'],rows):empty('لا توجد تنبيهات','النظام لا يرصد عناصر تحتاج إجراء حاليًا.')}</div><br><div class="panel"><h2>سجل التنبيهات</h2>${saved.length?table(['النوع','العنوان','الرسالة','الحالة'],saved):empty('لا توجد تنبيهات محفوظة','سيتم عرض التنبيهات المحفوظة هنا.')}</div>`;
  }

  function adminInventory(){
    const rows=db.products.map(p=>{const s=db.suppliers.find(s=>s.id===p.supplierId);const low=Number(p.stock||0)<=Number(db.settings.lowStockThreshold||5);return [esc(p.sku),esc(p.name),esc(s?.name||'-'),esc(categoryName(p.categoryId)),Number(p.stock||0).toLocaleString('ar-EG'),low?badge('تحتاج إجراء'):badge('نشط'),`<button class="softbtn smallbtn" onclick="Tager.stockModal('${p.id}')">تعديل المخزون</button>`]});
    const lowRows=db.products.filter(p=>Number(p.stock||0)<=Number(db.settings.lowStockThreshold||5)).map(p=>[esc(p.sku),esc(p.name),Number(p.stock||0).toLocaleString('ar-EG'),`الحد ${Number(db.settings.lowStockThreshold||5)}`]);
    return `<div class="section-head"><div><h2>إدارة المخزون</h2><p>متابعة أرصدة المنتجات والتنبيه عند الانخفاض.</p></div></div><div class="dashboard-grid grid"><div class="metric"><span>إجمالي المنتجات</span><b>${db.products.length}</b></div><div class="metric"><span>منتجات منخفضة</span><b>${lowRows.length}</b></div><div class="metric"><span>إجمالي الرصيد</span><b>${db.products.reduce((a,p)=>a+Number(p.stock||0),0).toLocaleString('ar-EG')}</b></div><div class="metric"><span>قيمة رصيد تقديرية</span><b>${money(db.products.reduce((a,p)=>a+Number(p.stock||0)*Number(p.retailPrice||0),0))}</b></div></div><br><div class="panel"><h2>تنبيهات المخزون</h2>${lowRows.length?table(['SKU','المنتج','المخزون','الملاحظة'],lowRows):empty('لا توجد نواقص مخزون','كل المنتجات أعلى من حد التنبيه.')}</div><br><div class="panel"><h2>أرصدة المنتجات</h2>${table(['SKU','المنتج','المورد','الفئة','المخزون','الحالة','إجراء'],rows)}</div>`;
  }
  function stockModal(id){const p=db.products.find(p=>p.id===id); if(!p)return; modal(`<h2>تعديل المخزون</h2><p class="muted">${esc(p.name)}</p><div class="form-grid"><div class="field"><label>المخزون الحالي</label><input id="stockQty" type="number" class="input" value="${Number(p.stock||0)}"></div><div class="field"><label>سبب التعديل</label><input id="stockReason" class="input" placeholder="توريد / جرد / تصحيح"></div></div><div class="actions"><button class="primary" onclick="Tager.saveStock('${id}')">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function saveStock(id){const p=db.products.find(p=>p.id===id); if(!p)return; const old=Number(p.stock||0); p.stock=Number(val('stockQty')||0); log('تعديل مخزون',`${p.sku} من ${old} إلى ${p.stock} - ${val('stockReason')}`); save(); closeModal(); go('admin',{tab:'inventory'});}

  function invoiceCandidates(){return db.orders.filter(o=>o.status==='تم التسليم' && !((db.invoices||[]).some(i=>i.orderId===o.id)));}
  function adminInvoices(){
    const candidates=invoiceCandidates();
    const invoices=db.invoices||[];
    return `<div class="section-head"><div><h2>الفواتير</h2><p>إنشاء فواتير من الطلبات المسلمة وطباعتها.</p></div></div><div class="dashboard-grid grid"><div class="metric"><span>طلبات جاهزة للفوترة</span><b>${candidates.length}</b></div><div class="metric"><span>فواتير مصدرة</span><b>${invoices.length}</b></div><div class="metric"><span>قيمة الفواتير</span><b>${money(invoices.reduce((a,i)=>a+Number(i.total||0),0))}</b></div><div class="metric"><span>ضريبة فواتير</span><b>${money(invoices.reduce((a,i)=>a+Number(i.tax||0),0))}</b></div></div><br><div class="panel"><h2>طلبات جاهزة للفوترة</h2>${candidates.length?table(['رقم الطلب','العميل','التاريخ','الإجمالي','إجراء'],candidates.map(o=>[esc(o.number),esc(o.buyerName),esc(o.createdAt),money(o.total),`<button class="primary smallbtn" onclick="Tager.createInvoice('${o.id}')">إصدار فاتورة</button>`])):empty('لا توجد طلبات جاهزة للفوترة','الفاتورة تصدر بعد تحويل الطلب إلى تم التسليم.')}</div><br><div class="panel"><h2>الفواتير المصدرة</h2>${invoices.length?table(['رقم الفاتورة','رقم الطلب','التاريخ','العميل','الإجمالي','الحالة','إجراء'],invoices.map(i=>[esc(i.number),esc(i.orderNumber),esc(i.date),esc(i.buyerName),money(i.total),badge(i.status),`<button class="softbtn smallbtn" onclick="Tager.printInvoice('${i.id}')">طباعة</button>`])):empty('لا توجد فواتير','ستظهر الفواتير بعد إصدارها من الطلبات المسلمة.')}</div>`;
  }
  function createInvoice(orderId){const o=db.orders.find(o=>o.id===orderId); if(!o)return; db.invoices=db.invoices||[]; const num=`${db.settings.invoicePrefix||'INV'}-${String(db.invoices.length+1).padStart(6,'0')}`; db.invoices.unshift({id:uid('inv'),number:num,orderId:o.id,orderNumber:o.number,date:today(),buyerName:o.buyerName,phone:o.phone,gov:o.gov,center:o.center,address:o.address,items:o.items,subtotal:o.subtotal,shipping:o.shipping,premiumFee:o.premiumFee,discount:o.discount||0,tax:o.tax||0,total:o.total,status:'مصدرة',createdBy:currentUser()?.id}); save(); log('إصدار فاتورة',num); go('admin',{tab:'invoices'});}
  function printInvoice(id){const i=(db.invoices||[]).find(i=>i.id===id); if(!i)return; modal(`<div class="print-area"><div style="display:flex;justify-content:space-between;gap:16px;align-items:center"><div><h2>فاتورة ${esc(i.number)}</h2><p>طلب: ${esc(i.orderNumber)} · التاريخ: ${esc(i.date)}</p></div><img src="./assets/tager-logo.png" style="width:190px;max-width:45%"></div><hr><p><b>العميل:</b> ${esc(i.buyerName)} · ${esc(i.phone)}</p><p><b>العنوان:</b> ${esc(i.gov)} - ${esc(i.center)} - ${esc(i.address)}</p>${table(['الصنف','الكمية','السعر','الإجمالي'],(i.items||[]).map(x=>[esc(x.name),x.qty,money(x.price),money(x.total)]))}<div class="mini-list" style="margin-top:16px"><div class="mini-item"><span>الإجمالي قبل التوصيل</span><b>${money(i.subtotal)}</b></div><div class="mini-item"><span>التوصيل</span><b>${money(i.shipping)}</b></div><div class="mini-item"><span>السلة المميزة</span><b>${money(i.premiumFee)}</b></div><div class="mini-item"><span>الخصم</span><b>${money(i.discount||0)}</b></div><div class="mini-item"><span>الضريبة</span><b>${money(i.tax||0)}</b></div><div class="mini-item"><span>الإجمالي النهائي</span><b>${money(i.total)}</b></div></div></div><div class="actions no-print"><button class="primary" onclick="window.print()">طباعة</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}

  function adminReturns(){
    const returns=db.returns||[];
    const delivered=db.orders.filter(o=>o.status==='تم التسليم');
    return `<div class="section-head"><div><h2>المرتجعات</h2><p>تسجيل ومتابعة طلبات المرتجع والخصومات.</p></div><button class="primary" onclick="Tager.returnModal()">تسجيل مرتجع</button></div><div class="dashboard-grid grid"><div class="metric"><span>مرتجعات مفتوحة</span><b>${returns.filter(r=>r.status!=='مغلقة').length}</b></div><div class="metric"><span>إجمالي مرتجعات</span><b>${returns.length}</b></div><div class="metric"><span>قيمة مرتجعات</span><b>${money(returns.reduce((a,r)=>a+Number(r.amount||0),0))}</b></div><div class="metric"><span>طلبات مسلمة</span><b>${delivered.length}</b></div></div><br>${returns.length?table(['رقم المرتجع','الطلب','التاريخ','السبب','المبلغ','الحالة','إجراء'],returns.map(r=>[esc(r.number),esc(r.orderNumber),esc(r.date),esc(r.reason),money(r.amount),badge(r.status),`<button class="softbtn smallbtn" onclick="Tager.closeReturn('${r.id}')">إغلاق</button>`])):empty('لا توجد مرتجعات','يمكن تسجيل مرتجع من أي طلب تم تسليمه.')}`;
  }
  function returnModal(){const delivered=db.orders.filter(o=>o.status==='تم التسليم'); if(!delivered.length)return toast('لا توجد طلبات مسلمة','danger'); modal(`<h2>تسجيل مرتجع</h2><div class="form-grid"><div class="field"><label>الطلب</label><select id="retOrder">${delivered.map(o=>`<option value="${o.id}">${esc(o.number)} - ${esc(o.buyerName)}</option>`).join('')}</select></div><div class="field"><label>المبلغ</label><input id="retAmount" type="number" class="input"></div><div class="field full"><label>السبب</label><textarea id="retReason" rows="3"></textarea></div></div><div class="actions"><button class="primary" onclick="Tager.saveReturn()">حفظ</button><button class="softbtn" onclick="Tager.closeModal()">إغلاق</button></div>`)}
  function saveReturn(){const o=db.orders.find(o=>o.id===val('retOrder')); if(!o)return; const amount=Number(val('retAmount')||0); if(!amount)return toast('اكتب قيمة المرتجع','danger'); db.returns=db.returns||[]; const num=`RET-${String(db.returns.length+1).padStart(6,'0')}`; db.returns.unshift({id:uid('ret'),number:num,orderId:o.id,orderNumber:o.number,date:today(),reason:val('retReason'),amount,status:'مفتوحة',createdBy:currentUser()?.id}); save(); log('تسجيل مرتجع',`${num} - ${o.number}`); closeModal(); go('admin',{tab:'returns'});}
  function closeReturn(id){const r=(db.returns||[]).find(r=>r.id===id); if(!r)return; r.status='مغلقة'; save(); log('إغلاق مرتجع',r.number); go('admin',{tab:'returns'});}

  function adminDocuments(){
    const supplierDocs=db.suppliers.map(s=>[esc(s.name),esc(s.cr||'-'),esc(s.tax||'-'),badge(s.status), (s.cr&&s.tax)?badge('مكتملة'):badge('تحتاج إجراء')]);
    const platformDocs=[['الشروط والأحكام',!!db.settings.terms],['سياسة الخصوصية',!!db.settings.privacy],['أرقام الدعم',!!db.settings.whatsapp],['إعدادات الفوترة',!!db.settings.invoicePrefix]];
    return `<div class="hero-grid grid"><div class="panel"><h2>مستندات الموردين</h2>${table(['المورد','السجل التجاري','البطاقة الضريبية','الحالة','الاكتمال'],supplierDocs)}</div><div class="panel"><h2>مستندات المنصة</h2>${table(['المستند','الحالة'],platformDocs.map(x=>[x[0],x[1]?badge('مكتملة'):badge('تحتاج إجراء')]))}</div></div><br><div class="notice">رفع الملفات الفعلية يكون عند ربط التخزين السحابي. المنصة تحفظ بيانات المستندات وتراقب اكتمالها داخل ملف المورد.</div>`;
  }

  function track(params){
    shell('track',pageHead('تتبع الطلب','استعلم عن حالة الطلب برقم الطلب ورقم الهاتف')+`<section class="section"><div class="container"><div class="panel"><div class="form-grid"><div class="field"><label>رقم الطلب</label><input id="trackNo" class="input" placeholder="TG-000001"></div><div class="field"><label>رقم الهاتف</label><input id="trackPhone" class="input" placeholder="01..."></div></div><div class="actions"><button class="primary" onclick="Tager.trackOrder()">استعلام</button></div><div id="trackResult" style="margin-top:18px"></div></div></div></section>`);
  }
  function trackOrder(){const no=val('trackNo'); const phone=val('trackPhone'); const o=db.orders.find(o=>o.number===no && String(o.phone||'').replace(/\s/g,'')===String(phone||'').replace(/\s/g,'')); const root=document.getElementById('trackResult'); if(!root)return; if(!o){root.innerHTML=empty('لم يتم العثور على الطلب','راجع رقم الطلب ورقم الهاتف.'); return;} const steps=orderStatuses.map(st=>`<div class="timeline-step ${orderStatuses.indexOf(st)<=orderStatuses.indexOf(o.status)?'done':''}"><b>${esc(st)}</b><span>${st===o.status?'الحالة الحالية':''}</span></div>`).join(''); root.innerHTML=`<div class="notice"><b>${esc(o.number)}</b> · ${esc(o.buyerName)} · ${money(o.total)}</div><div class="timeline">${steps}</div>`;}

  function render(page,params){
    const pages={home,setup,login,forgot,buyerRegister,supplierRegister,products,product,categories,suppliers,supplier,cart:cartPage,checkout,account,vendor,admin,support,policies,track};
    (pages[page]||home)(params||{});
    setTimeout(()=>window.scrollTo({top:0,behavior:'smooth'}),0);
  }

  window.Tager={go,homeSearch,createFirstAdmin,loginAction,forgotAction,logout,registerBuyer,registerSupplier,filterByCategory,addToCart,changeQty,removeCart,placeOrder,sendTicket,closeTicket,closeModal,userModal,saveAdminUser,coverageModal,saveCoverage,productModal,adminProductModal,saveProduct,categoryModal,saveCategory,paymentModal,savePayment,settlementModal,saveSettlement,couponModal,saveCoupon,toggleCoupon,setSupplierStatus,setProductStatus,toggleUser,toggleCategory,updateOrderStatus,updatePaymentStatus,saveContent,saveSettings,printOrder,exportJson,importJson,exportCsv,templateCsv,stockModal,saveStock,createInvoice,printInvoice,returnModal,saveReturn,closeReturn,trackOrder};
  if(!hasAdmin()) render('setup'); else route();
})();
