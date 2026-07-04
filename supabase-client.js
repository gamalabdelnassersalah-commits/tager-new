(function(){
  const env = window.TAGER_ENV || {};
  const url = env.SUPABASE_URL || '';
  const key = env.SUPABASE_ANON_KEY || '';
  const client = (url && key && window.supabase) ? window.supabase.createClient(url, key) : null;

  const stateKey = 'tager_session_v22_production';
  const money = v => Number(v || 0);
  const nowIso = () => new Date().toISOString();

  async function hash(text){
    const enc = new TextEncoder().encode(String(text || ''));
    const digest = await crypto.subtle.digest('SHA-256', enc);
    return Array.from(new Uint8Array(digest)).map(b => b.toString(16).padStart(2,'0')).join('');
  }

  function session(){
    try { return JSON.parse(localStorage.getItem(stateKey) || 'null'); } catch { return null; }
  }
  function setSession(user){ localStorage.setItem(stateKey, JSON.stringify(user)); }
  function clearSession(){ localStorage.removeItem(stateKey); }
  function needClient(){ if(!client) throw new Error('لم يتم تحميل إعدادات الاتصال بقاعدة البيانات. راجع متغيرات Vercel.'); return client; }


  // وضع تشغيل إنتاجي محلي بدون أي بيانات جاهزة.
  // عند عدم ربط Supabase تعمل المنصة بقاعدة محلية فارغة داخل المتصفح.
  // أول خطوة هي فتح /setup وإنشاء حساب الإدارة الحقيقي، ثم تسجيل الموردين والمنتجات من الواجهات.
  if(!client){
    const localKey = 'tager_production_local_db_v22';
    function clone(x){ return JSON.parse(JSON.stringify(x)); }
    function uid(prefix='id'){ return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2,8)}`; }
    function emptyData(){ return {users:[],vendors:[],products:[],orders:[],order_items:[],deliveries:[],commission_payments:[],support_tickets:[],financial_entries:[],audit_logs:[],notifications:[]}; }
    function normalize(db){
      const base=emptyData();
      return {...base,...(db||{}), users:Array.isArray(db?.users)?db.users:[], vendors:Array.isArray(db?.vendors)?db.vendors:[], products:Array.isArray(db?.products)?db.products:[], orders:Array.isArray(db?.orders)?db.orders:[], order_items:Array.isArray(db?.order_items)?db.order_items:[], deliveries:Array.isArray(db?.deliveries)?db.deliveries:[], commission_payments:Array.isArray(db?.commission_payments)?db.commission_payments:[], support_tickets:Array.isArray(db?.support_tickets)?db.support_tickets:[], financial_entries:Array.isArray(db?.financial_entries)?db.financial_entries:[], audit_logs:Array.isArray(db?.audit_logs)?db.audit_logs:[], notifications:Array.isArray(db?.notifications)?db.notifications:[]};
    }
    function load(){
      try { const raw=JSON.parse(localStorage.getItem(localKey) || 'null'); return normalize(raw || emptyData()); } catch { return emptyData(); }
    }
    function save(db){ localStorage.setItem(localKey, JSON.stringify(normalize(db))); }
    if(!localStorage.getItem(localKey)) save(emptyData());
    function publicUser(u){ const x=clone(u); delete x.password; delete x.password_hash; return x; }
    function getUser(db,id){ const u=db.users.find(u=>u.id===id) || null; return u ? publicUser(u) : null; }
    function getVendorRaw(db,id){ return db.vendors.find(v=>v.id===id) || null; }
    function vendorWithUser(db,v){ const x=clone(v); x.users=getUser(db,v.user_id) || null; return x; }
    function productWithVendor(db,p){ const x=clone(p); x.vendors = getVendorRaw(db,p.vendor_id) ? vendorWithUser(db,getVendorRaw(db,p.vendor_id)) : null; return x; }
    function itemWithRelations(db,i){ const x=clone(i); const p=db.products.find(p=>p.id===i.product_id); const v=getVendorRaw(db,i.vendor_id); x.products=p?clone(p):null; x.vendors=v?vendorWithUser(db,v):null; return x; }
    function orderWithRelations(db,o){ const x=clone(o); x.users=getUser(db,o.customer_id); x.order_items=db.order_items.filter(i=>i.order_id===o.id).map(i=>itemWithRelations(db,i)); x.deliveries=db.deliveries.filter(d=>d.order_id===o.id).map(clone); return x; }
    function localPriceFor(item, qty, tier){ const p=item.product || item; if(tier==='bulk' && Number(qty)>=Number(p.bulk_min_qty||0)) return Number(p.bulk_price||p.wholesale_price||p.retail_price||0); if(tier==='wholesale' && Number(qty)>=Number(p.wholesale_min_qty||0)) return Number(p.wholesale_price||p.retail_price||0); return Number(p.retail_price||0); }
    function localZoneMatches(vendor,address){ const zones=Array.isArray(vendor?.delivery_zones)?vendor.delivery_zones:[]; return zones.find(z=>z.governorate===address?.governorate && z.district===address?.district && z.area===address?.area); }
    async function localCountAdmins(){ const db=load(); return db.users.filter(u=>u.role==='admin').length; }
    async function localCreateAdmin(data){
      const db=load();
      if(db.users.some(u=>u.role==='admin')) throw new Error('تم إنشاء حساب الإدارة الرئيسي من قبل.');
      if(db.users.some(u=>u.phone===data.phone)) throw new Error('رقم الهاتف مسجل من قبل.');
      const user={id:uid('u'),role:'admin',status:'approved',name:data.name,phone:data.phone,email:data.email||'',password_hash:await hash(data.password),permissions:{full:true},created_at:nowIso()};
      db.users.push(user); save(db); const clean=publicUser(user); setSession(clean); return clean;
    }
    async function localLogin(phone,password){
      const db=load(); const password_hash=await hash(password);
      const u=db.users.find(x=>x.phone===phone && (x.password_hash===password_hash || String(x.password||'')===String(password)));
      if(!u) throw new Error('بيانات الدخول غير صحيحة.');
      if(u.status!=='approved') throw new Error('الحساب لم يتم اعتماده بعد أو تم إيقافه.');
      const clean=publicUser(u); setSession(clean); return clean;
    }
    async function localRegisterCustomer(d){
      const db=load(); if(db.users.some(u=>u.phone===d.phone)) throw new Error('رقم الهاتف مسجل من قبل.');
      const u={id:uid('u'),role:'customer',status:'approved',name:d.name,phone:d.phone,email:d.email||'',password_hash:await hash(d.password),governorate:d.governorate,district:d.district,area:d.area,address:d.address,permissions:{},created_at:nowIso()};
      db.users.push(u); save(db); const clean=publicUser(u); setSession(clean); return clean;
    }
    async function localRegisterVendor(d){
      const db=load(); if(db.users.some(u=>u.phone===d.phone)) throw new Error('رقم الهاتف مسجل من قبل.');
      const u={id:uid('u'),role:'vendor',status:'pending',name:d.owner_name,phone:d.phone,email:d.email||'',password_hash:await hash(d.password),governorate:d.governorate,district:d.district,area:d.area,address:d.address,permissions:{},created_at:nowIso()};
      const v={id:uid('v'),user_id:u.id,status:'pending',store_name:d.store_name,commercial_register:d.commercial_register||'',tax_number:d.tax_number||'',governorate:d.governorate,district:d.district,description:d.description||'',min_order:Number(d.min_order||0),commission_percent:Number(d.commission_percent||1.5),premium_cart_percent:Number(d.premium_cart_percent||1.5),delivery_zones:[],bank_name:d.bank_name||'',iban:d.iban||'',wallet_phone:d.wallet_phone||'',created_at:nowIso()};
      db.users.push(u); db.vendors.push(v); save(db); return {user:publicUser(u),vendor:vendorWithUser(db,v)};
    }
    async function localVendors(status){ const db=load(); return db.vendors.filter(v=>!status || v.status===status).map(v=>vendorWithUser(db,v)).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localProducts(status){ const db=load(); return db.products.filter(p=>!status || p.status===status).map(p=>productWithVendor(db,p)).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localVendorByUser(userId){ const db=load(); const v=db.vendors.find(v=>v.user_id===userId); return v?vendorWithUser(db,v):null; }
    async function localUpdateVendor(id,patch){ const db=load(); const i=db.vendors.findIndex(v=>v.id===id); if(i<0) throw new Error('المورد غير موجود.'); db.vendors[i]={...db.vendors[i],...patch,updated_at:nowIso()}; save(db); return vendorWithUser(db,db.vendors[i]); }
    async function localUpdateUser(id,patch){ const db=load(); const i=db.users.findIndex(u=>u.id===id); if(i<0) throw new Error('المستخدم غير موجود.'); db.users[i]={...db.users[i],...patch,updated_at:nowIso()}; save(db); return publicUser(db.users[i]); }
    async function localSaveProduct(product){ const db=load(); if(product.id){ const i=db.products.findIndex(p=>p.id===product.id); if(i<0) throw new Error('المنتج غير موجود.'); db.products[i]={...db.products[i],...product,updated_at:nowIso()}; save(db); return productWithVendor(db,db.products[i]); } const p={id:uid('p'),gallery:Array.isArray(product.gallery)?product.gallery:[],reorder_level:Number(product.reorder_level||5),status:product.status||'pending',created_at:nowIso(),...product}; db.products.push(p); save(db); return productWithVendor(db,p); }
    async function localCreateOrder(cart,address,cartType='standard',paymentMethod='cash'){
      const db=load(); const customer=session(); if(!customer || customer.role!=='customer') throw new Error('يجب دخول العميل قبل إتمام الطلب.'); if(!cart.length) throw new Error('السلة فارغة.');
      const groups=new Map();
      for(const line of cart){ const p=db.products.find(x=>x.id===line.product.id); if(!p || p.status!=='approved') throw new Error('يوجد منتج غير معتمد داخل السلة.'); const v=getVendorRaw(db,p.vendor_id); if(!v) throw new Error('بيانات المورد غير موجودة.'); const zone=localZoneMatches(v,address); if(!zone) throw new Error(`المورد ${v.store_name} لا يغطي هذا العنوان.`); const qty=Number(line.qty||0); if(qty<=0) throw new Error('الكمية يجب أن تكون أكبر من صفر.'); if(qty>Number(p.stock_qty||0)) throw new Error(`المخزون غير كافٍ للمنتج ${p.name_ar}.`); const price=localPriceFor(p,qty,line.tier); const subtotal=price*qty; const current=groups.get(p.vendor_id)||{vendor:v,zone,subtotal:0,items:[]}; current.subtotal+=subtotal; current.items.push({product:p,line,qty,price,subtotal}); groups.set(p.vendor_id,current); }
      for(const g of groups.values()){ if(g.subtotal<Number(g.vendor.min_order||0)) throw new Error(`الطلب من ${g.vendor.store_name} أقل من الحد الأدنى.`); }
      const shipping=[...groups.values()].reduce((s,g)=>s+Number(g.zone.fee||0),0); const subtotal=[...groups.values()].reduce((s,g)=>s+g.subtotal,0); const commissionTotal=[...groups.values()].reduce((s,g)=>s+(g.subtotal*Number(cartType==='premium'?g.vendor.premium_cart_percent:g.vendor.commission_percent)/100),0);
      const order={id:uid('o'),customer_id:customer.id,cart_type:cartType,governorate:address.governorate,district:address.district,area:address.area,address:address.address,payment_method:paymentMethod,payment_status:'unpaid',paid_amount:0,shipping_fee:shipping,subtotal,total:subtotal+shipping,commission_total:commissionTotal,vendor_net_total:subtotal-commissionTotal,status:'new',commission_recorded:false,stock_released:false,created_at:nowIso()}; db.orders.push(order);
      db.financial_entries.push({id:uid('fe'),entry_type:'customer_order',source_table:'orders',source_id:order.id,customer_id:customer.id,debit:order.total,credit:0,description:'طلب عميل',created_at:nowIso()});
      for(const [vendorId,g] of groups.entries()){ for(const it of g.items){ const rate=Number(cartType==='premium'?g.vendor.premium_cart_percent:g.vendor.commission_percent); const comm=it.subtotal*rate/100; db.order_items.push({id:uid('oi'),order_id:order.id,product_id:it.product.id,vendor_id:vendorId,qty:it.qty,price_tier:it.line.tier,unit_price:it.price,subtotal:it.subtotal,commission:comm,vendor_net:it.subtotal-comm,created_at:nowIso()}); const pi=db.products.findIndex(p=>p.id===it.product.id); if(pi>=0) db.products[pi].stock_qty=Math.max(Number(db.products[pi].stock_qty||0)-it.qty,0); }
        db.deliveries.push({id:uid('d'),order_id:order.id,vendor_id:vendorId,status:'pending',fee:Number(g.zone.fee||0),duration:g.zone.duration||'',governorate:address.governorate,district:address.district,area:address.area,address:address.address,tracking_note:'',created_at:nowIso()}); }
      save(db); return orderWithRelations(db,order);
    }
    async function localMyOrders(){ const db=load(); const u=session(); if(!u) return []; return db.orders.filter(o=>u.role==='customer'?o.customer_id===u.id:true).map(o=>orderWithRelations(db,o)).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localVendorOrders(vendorId){ const db=load(); return db.order_items.filter(i=>i.vendor_id===vendorId).map(i=>{ const x=itemWithRelations(db,i); x.orders=orderWithRelations(db,db.orders.find(o=>o.id===i.order_id)); return x; }).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localAllOrders(){ const db=load(); return db.orders.map(o=>orderWithRelations(db,o)).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localUpdateOrderStatus(id,status){ const db=load(); const o=db.orders.find(o=>o.id===id); if(!o) throw new Error('الطلب غير موجود.'); if(status==='cancelled' && !o.stock_released){ db.order_items.filter(i=>i.order_id===id).forEach(i=>{ const p=db.products.find(p=>p.id===i.product_id); if(p) p.stock_qty=Number(p.stock_qty||0)+Number(i.qty||0); }); o.stock_released=true; } o.status=status; o.updated_at=nowIso(); if(status==='delivered') { o.commission_recorded=true; db.deliveries.filter(d=>d.order_id===id).forEach(d=>{d.status='delivered';d.updated_at=nowIso();}); db.financial_entries.push({id:uid('fe'),entry_type:'platform_commission_after_delivery',source_table:'orders',source_id:id,customer_id:o.customer_id,debit:0,credit:Number(o.commission_total||0),description:'عمولة منصة بعد التسليم',created_at:nowIso()}); } if(status==='cancelled') db.deliveries.filter(d=>d.order_id===id).forEach(d=>{d.status='cancelled';d.updated_at=nowIso();}); save(db); }
    async function localUpdateOrderPayment(id,payment_status,paid_amount=0){ const db=load(); const o=db.orders.find(o=>o.id===id); if(!o) throw new Error('الطلب غير موجود.'); o.payment_status=payment_status; o.paid_amount=Number(paid_amount||0); if(payment_status==='paid') o.paid_at=nowIso(); o.updated_at=nowIso(); save(db); }
    async function localUpdateDeliveryStatus(id,status,tracking_note=''){ const db=load(); const d=db.deliveries.find(d=>d.id===id); if(!d) throw new Error('التوصيل غير موجود.'); d.status=status; d.tracking_note=tracking_note; d.updated_at=nowIso(); const orderDeliveries=db.deliveries.filter(x=>x.order_id===d.order_id); if(orderDeliveries.length && orderDeliveries.every(x=>x.status==='delivered')){ const o=db.orders.find(o=>o.id===d.order_id); if(o){ o.status='delivered'; o.commission_recorded=true; }} save(db); }
    async function localCreateSupportTicket(t){ const db=load(); const row={id:uid('t'),user_id:t.user_id||null,name:t.name||'',phone:t.phone||'',ticket_type:t.ticket_type||'أخرى',message:t.message||'',status:t.status||'new',admin_note:'',created_at:nowIso()}; db.support_tickets.push(row); save(db); return clone(row); }
    async function localSupportTickets(){ const db=load(); return db.support_tickets.map(clone).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localUpdateSupportTicket(id,status,admin_note=''){ const db=load(); const t=db.support_tickets.find(t=>t.id===id); if(!t) throw new Error('طلب الدعم غير موجود.'); t.status=status; t.admin_note=admin_note; t.updated_at=nowIso(); save(db); }
    async function localUsersByRole(role){ const db=load(); return db.users.filter(u=>!role || u.role===role).map(publicUser).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localCreateStaff(d){ const db=load(); if(db.users.some(u=>u.phone===d.phone)) throw new Error('رقم الهاتف مسجل من قبل.'); const u={id:uid('u'),role:'staff',status:'approved',name:d.name,phone:d.phone,email:d.email||'',password_hash:await hash(d.password),permissions:d.permissions||{},created_at:nowIso()}; db.users.push(u); save(db); return publicUser(u); }
    async function localAudit(action,details={}){ const db=load(); db.audit_logs.push({id:uid('a'),user_id:session()?.id||null,action,details,created_at:nowIso()}); save(db); }
    function localVendorFinancialCalc(db,vendorId){ const items=db.order_items.filter(i=>i.vendor_id===vendorId).map(i=>{ const x=clone(i); x.orders=db.orders.find(o=>o.id===i.order_id); return x; }); const pays=db.commission_payments.filter(p=>p.vendor_id===vendorId); const sales=items.reduce((s,x)=>s+Number(x.subtotal||0),0); const delivered=items.filter(x=>x.orders?.status==='delivered'); const paidRows=delivered.filter(x=>x.orders?.payment_status==='paid'); const cancelled=items.filter(x=>x.orders?.status==='cancelled'); const commission=delivered.reduce((s,x)=>s+Number(x.commission||0),0); const net=delivered.reduce((s,x)=>s+Number(x.vendor_net||0),0); const paid=pays.filter(x=>x.status==='approved').reduce((s,x)=>s+Number(x.amount||0),0); const pending=pays.filter(x=>x.status==='pending').reduce((s,x)=>s+Number(x.amount||0),0); const orderIds=[...new Set(items.map(x=>x.order_id))]; const deliveredIds=[...new Set(delivered.map(x=>x.order_id))]; const paidIds=[...new Set(paidRows.map(x=>x.order_id))]; const cancelledIds=[...new Set(cancelled.map(x=>x.order_id))]; return {sales,deliveredSales:delivered.reduce((s,x)=>s+Number(x.subtotal||0),0),paidSales:paidRows.reduce((s,x)=>s+Number(x.subtotal||0),0),cancelledSales:cancelled.reduce((s,x)=>s+Number(x.subtotal||0),0),commission,net,paid,pending,remaining:Math.max(commission-paid,0),payments:pays.map(clone),orderStats:{total:orderIds.length,delivered:deliveredIds.length,paid:paidIds.length,cancelled:cancelledIds.length,active:Math.max(orderIds.length-deliveredIds.length-cancelledIds.length,0)}}; }
    async function localVendorFinancial(vendorId){ const db=load(); return localVendorFinancialCalc(db,vendorId); }
    async function localSavePayment(p){ const db=load(); const row={id:uid('pay'),created_at:nowIso(),status:'pending',...p,amount:Number(p.amount||0)}; db.commission_payments.push(row); save(db); return clone(row); }
    async function localPayments(){ const db=load(); return db.commission_payments.map(p=>{ const x=clone(p); const v=getVendorRaw(db,p.vendor_id); x.vendors=v?{store_name:v.store_name}:null; return x; }).sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))); }
    async function localUpdatePayment(id,status,admin_note=''){ const db=load(); const p=db.commission_payments.find(p=>p.id===id); if(!p) throw new Error('الدفعة غير موجودة.'); p.status=status; p.admin_note=admin_note; p.updated_at=nowIso(); save(db); }
    async function localAdminSummary(){ const [vs,ps,os,pay,tickets,staff]=await Promise.all([localVendors(),localProducts(),localAllOrders(),localPayments(),localSupportTickets(),localUsersByRole('staff')]); return {vendors:vs,products:ps,orders:os,payments:pay,tickets,staff}; }
    function resetProduction(){ const db=emptyData(); save(db); clearSession(); return db; }
    window.TagerDB = { ready:true, mode:'local-production-empty', client:null, session, setSession, clearSession, countAdmins:localCountAdmins, createAdmin:localCreateAdmin, login:localLogin, registerCustomer:localRegisterCustomer, registerVendor:localRegisterVendor, vendors:localVendors, products:localProducts, vendorByUser:localVendorByUser, updateVendor:localUpdateVendor, updateUser:localUpdateUser, saveProduct:localSaveProduct, createOrder:localCreateOrder, myOrders:localMyOrders, vendorOrders:localVendorOrders, allOrders:localAllOrders, updateOrderStatus:localUpdateOrderStatus, updateOrderPayment:localUpdateOrderPayment, updateDeliveryStatus:localUpdateDeliveryStatus, createSupportTicket:localCreateSupportTicket, supportTickets:localSupportTickets, updateSupportTicket:localUpdateSupportTicket, usersByRole:localUsersByRole, createStaff:localCreateStaff, audit:localAudit, vendorFinancial:localVendorFinancial, savePayment:localSavePayment, payments:localPayments, updatePayment:localUpdatePayment, adminSummary:localAdminSummary, zoneMatches:localZoneMatches, priceFor:localPriceFor, money, resetProduction };
    return;
  }

  async function countAdmins(){
    const sb = needClient();
    const { count, error } = await sb.from('users').select('id', { count:'exact', head:true }).eq('role','admin');
    if(error) throw error;
    return count || 0;
  }

  async function createAdmin(data){
    const admins = await countAdmins();
    if(admins > 0) throw new Error('تم إنشاء حساب الإدارة الرئيسي من قبل.');
    const password_hash = await hash(data.password);
    const sb = needClient();
    const { data: user, error } = await sb.from('users').insert({
      role:'admin', status:'approved', name:data.name, phone:data.phone, email:data.email || null, password_hash,
      permissions:{full:true}
    }).select('*').single();
    if(error) throw error;
    setSession(user);
    return user;
  }

  async function login(phone, password){
    const sb = needClient();
    const password_hash = await hash(password);
    const { data, error } = await sb.from('users').select('*').eq('phone', phone).eq('password_hash', password_hash).maybeSingle();
    if(error) throw error;
    if(!data) throw new Error('بيانات الدخول غير صحيحة.');
    if(data.status !== 'approved') throw new Error('الحساب لم يتم اعتماده بعد أو تم إيقافه.');
    setSession(data);
    return data;
  }

  async function registerCustomer(d){
    const sb = needClient();
    const password_hash = await hash(d.password);
    const { data, error } = await sb.from('users').insert({
      role:'customer', status:'approved', name:d.name, phone:d.phone, email:d.email || null, password_hash,
      governorate:d.governorate, district:d.district, area:d.area, address:d.address
    }).select('*').single();
    if(error) throw error;
    setSession(data);
    return data;
  }

  async function registerVendor(d){
    const sb = needClient();
    const password_hash = await hash(d.password);
    const { data:user, error:uerr } = await sb.from('users').insert({
      role:'vendor', status:'pending', name:d.owner_name, phone:d.phone, email:d.email || null, password_hash,
      governorate:d.governorate, district:d.district, area:d.area, address:d.address
    }).select('*').single();
    if(uerr) throw uerr;
    const { data:vendor, error:verr } = await sb.from('vendors').insert({
      user_id:user.id, status:'pending', store_name:d.store_name, commercial_register:d.commercial_register || null,
      tax_number:d.tax_number || null, governorate:d.governorate, district:d.district, description:d.description || '',
      min_order:money(d.min_order), commission_percent:money(d.commission_percent || 1.5), premium_cart_percent:money(d.premium_cart_percent || 1.5),
      delivery_zones:[]
    }).select('*').single();
    if(verr) throw verr;
    return {user, vendor};
  }

  async function vendors(status){
    const sb = needClient();
    let q = sb.from('vendors').select('*, users(name,phone,email,status)');
    if(status) q = q.eq('status', status);
    const { data, error } = await q.order('created_at', {ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function products(status){
    const sb = needClient();
    let q = sb.from('products').select('*, vendors(store_name,status,commission_percent,min_order,premium_cart_percent,delivery_zones)');
    if(status) q = q.eq('status', status);
    const { data, error } = await q.order('created_at', {ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function vendorByUser(userId){
    const sb = needClient();
    const { data, error } = await sb.from('vendors').select('*').eq('user_id', userId).maybeSingle();
    if(error) throw error;
    return data;
  }

  async function updateVendor(id, patch){
    const sb = needClient();
    const { data, error } = await sb.from('vendors').update({...patch, updated_at:nowIso()}).eq('id', id).select('*').single();
    if(error) throw error;
    return data;
  }

  async function updateUser(id, patch){
    const sb = needClient();
    const { data, error } = await sb.from('users').update({...patch, updated_at:nowIso()}).eq('id', id).select('*').single();
    if(error) throw error;
    return data;
  }

  async function saveProduct(product){
    const sb = needClient();
    if(product.id){
      const { data, error } = await sb.from('products').update({...product, updated_at:nowIso()}).eq('id', product.id).select('*').single();
      if(error) throw error; return data;
    }
    const { data, error } = await sb.from('products').insert(product).select('*').single();
    if(error) throw error;
    return data;
  }

  function priceFor(item, qty, tier){
    const p = item.product || item;
    if(tier === 'bulk' && qty >= money(p.bulk_min_qty)) return money(p.bulk_price || p.wholesale_price || p.retail_price);
    if(tier === 'wholesale' && qty >= money(p.wholesale_min_qty)) return money(p.wholesale_price || p.retail_price);
    return money(p.retail_price);
  }

  function zoneMatches(vendor, address){
    const zones = Array.isArray(vendor.delivery_zones) ? vendor.delivery_zones : [];
    return zones.find(z => z.governorate === address.governorate && z.district === address.district && z.area === address.area);
  }

  async function createOrder(cart, address, cartType, paymentMethod){
    const sb = needClient();
    const customer = session();
    if(!customer || customer.role !== 'customer') throw new Error('يجب دخول العميل قبل إتمام الطلب.');
    if(!cart.length) throw new Error('السلة فارغة.');
    const vendorGroups = new Map();
    for(const line of cart){
      if(!line.product || line.product.status !== 'approved') throw new Error('يوجد منتج غير معتمد داخل السلة.');
      const vendor = line.product.vendors;
      const zone = zoneMatches(vendor, address);
      if(!zone) throw new Error(`المورد ${vendor.store_name} لا يغطي هذا العنوان.`);
      const qty = money(line.qty);
      if(qty <= 0) throw new Error('الكمية يجب أن تكون أكبر من صفر.');
      if(qty > money(line.product.stock_qty)) throw new Error(`المخزون غير كافٍ للمنتج ${line.product.name_ar}.`);
      const price = priceFor(line, qty, line.tier);
      const subtotal = price * qty;
      const current = vendorGroups.get(line.product.vendor_id) || { vendor, zone, subtotal:0, items:[] };
      current.subtotal += subtotal;
      current.items.push({line, qty, price, subtotal});
      vendorGroups.set(line.product.vendor_id, current);
    }
    for(const group of vendorGroups.values()){
      if(group.subtotal < money(group.vendor.min_order || 0)) throw new Error(`الطلب من ${group.vendor.store_name} أقل من الحد الأدنى.`);
    }
    const shipping = Array.from(vendorGroups.values()).reduce((s,g)=>s+money(g.zone.fee),0);
    const subtotal = Array.from(vendorGroups.values()).reduce((s,g)=>s+g.subtotal,0);
    const commissionTotal = Array.from(vendorGroups.values()).reduce((s,g)=>s + (g.subtotal * money(cartType === 'premium' ? g.vendor.premium_cart_percent : g.vendor.commission_percent) / 100),0);
    const vendorNet = subtotal - commissionTotal;

    const { data:order, error:oerr } = await sb.from('orders').insert({
      customer_id:customer.id, cart_type:cartType, governorate:address.governorate, district:address.district,
      area:address.area, address:address.address, payment_method:paymentMethod, payment_status:'unpaid', paid_amount:0,
      shipping_fee:shipping, subtotal, total:subtotal+shipping, commission_total:commissionTotal, vendor_net_total:vendorNet, status:'new'
    }).select('*').single();
    if(oerr) throw oerr;

    const items = [];
    const deliveries = [];
    for(const [vendorId, group] of vendorGroups.entries()){
      for(const item of group.items){
        const rate = money(cartType === 'premium' ? group.vendor.premium_cart_percent : group.vendor.commission_percent);
        const commission = item.subtotal * rate / 100;
        items.push({order_id:order.id, product_id:item.line.product.id, vendor_id:vendorId, qty:item.qty, price_tier:item.line.tier, unit_price:item.price, subtotal:item.subtotal, commission, vendor_net:item.subtotal-commission});
      }
      deliveries.push({order_id:order.id, vendor_id:vendorId, status:'pending', fee:money(group.zone.fee), duration:group.zone.duration || '', governorate:address.governorate, district:address.district, area:address.area, address:address.address});
    }
    const { error:ierr } = await sb.from('order_items').insert(items);
    if(ierr) throw ierr;
    const { error:derr } = await sb.from('deliveries').insert(deliveries);
    if(derr) throw derr;
    for(const group of vendorGroups.values()){
      for(const item of group.items){
        const remainingStock = Math.max(money(item.line.product.stock_qty) - item.qty, 0);
        await sb.from('products').update({stock_qty:remainingStock, updated_at:nowIso()}).eq('id', item.line.product.id);
      }
    }
    await sb.from('financial_entries').insert({entry_type:'customer_order', source_table:'orders', source_id:order.id, customer_id:customer.id, debit:order.total, credit:0, description:'طلب عميل'});
    return order;
  }

  async function myOrders(){
    const sb = needClient();
    const user = session();
    if(!user) return [];
    let q = sb.from('orders').select('*, order_items(*, products(name_ar), vendors(store_name)), deliveries(*)').order('created_at',{ascending:false});
    if(user.role === 'customer') q = q.eq('customer_id', user.id);
    const { data, error } = await q;
    if(error) throw error;
    return data || [];
  }

  async function vendorOrders(vendorId){
    const sb = needClient();
    const { data, error } = await sb.from('order_items').select('*, orders(*), products(name_ar), vendors(store_name)').eq('vendor_id', vendorId).order('created_at', {ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function allOrders(){
    const sb = needClient();
    const { data, error } = await sb.from('orders').select('*, users(name,phone), order_items(*, vendors(store_name), products(name_ar)), deliveries(*)').order('created_at',{ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function updateOrderStatus(id, status){
    const sb = needClient();
    const { data:before } = await sb.from('orders').select('id,customer_id,commission_total,commission_recorded,stock_released,total,status').eq('id', id).maybeSingle();
    const { error } = await sb.from('orders').update({status, updated_at:nowIso()}).eq('id', id);
    if(error) throw error;
    if(status === 'delivered') {
      await sb.from('deliveries').update({status:'delivered', updated_at:nowIso()}).eq('order_id', id);
      if(before && !before.commission_recorded){
        await sb.from('financial_entries').insert({entry_type:'platform_commission_after_delivery', source_table:'orders', source_id:id, customer_id:before.customer_id, debit:0, credit:money(before.commission_total), description:'عمولة منصة بعد التسليم'});
        await sb.from('orders').update({commission_recorded:true, updated_at:nowIso()}).eq('id', id);
      }
    }
    if(status === 'cancelled') {
      await sb.from('deliveries').update({status:'cancelled', updated_at:nowIso()}).eq('order_id', id);
      if(before && !before.stock_released){
        const { data:items } = await sb.from('order_items').select('product_id,qty,products(stock_qty)').eq('order_id', id);
        for(const item of (items||[])){
          await sb.from('products').update({stock_qty:money(item.products?.stock_qty)+money(item.qty), updated_at:nowIso()}).eq('id', item.product_id);
        }
        await sb.from('orders').update({stock_released:true, updated_at:nowIso()}).eq('id', id);
      }
    }
  }

  async function updateOrderPayment(id, payment_status, paid_amount=0){
    const sb = needClient();
    const payload = {payment_status, paid_amount:money(paid_amount), updated_at:nowIso()};
    if(payment_status === 'paid') payload.paid_at = nowIso();
    const { error } = await sb.from('orders').update(payload).eq('id', id);
    if(error) throw error;
  }


  async function updateDeliveryStatus(id, status, tracking_note=''){
    const sb = needClient();
    const { data:delivery, error:derr } = await sb.from('deliveries').update({status, tracking_note, updated_at:nowIso()}).eq('id', id).select('order_id').single();
    if(derr) throw derr;
    if(status === 'delivered' && delivery?.order_id){
      const { data:rows } = await sb.from('deliveries').select('status').eq('order_id', delivery.order_id);
      if((rows||[]).length && (rows||[]).every(r=>r.status==='delivered')) await updateOrderStatus(delivery.order_id, 'delivered');
    }
  }

  async function createSupportTicket(t){
    const sb = needClient();
    const { data, error } = await sb.from('support_tickets').insert({
      user_id:t.user_id||null, name:t.name||'', phone:t.phone||'', ticket_type:t.ticket_type||'أخرى', message:t.message||'', status:t.status||'new'
    }).select('*').single();
    if(error) throw error;
    return data;
  }

  async function supportTickets(){
    const sb = needClient();
    const { data, error } = await sb.from('support_tickets').select('*').order('created_at',{ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function updateSupportTicket(id, status, admin_note=''){
    const sb = needClient();
    const { error } = await sb.from('support_tickets').update({status, admin_note, updated_at:nowIso()}).eq('id', id);
    if(error) throw error;
  }

  async function usersByRole(role){
    const sb = needClient();
    let q = sb.from('users').select('*').order('created_at',{ascending:false});
    if(role) q = q.eq('role', role);
    const { data, error } = await q;
    if(error) throw error;
    return data || [];
  }

  async function createStaff(d){
    const sb = needClient();
    const password_hash = await hash(d.password);
    const { data, error } = await sb.from('users').insert({
      role:'staff', status:'approved', name:d.name, phone:d.phone, email:d.email || null, password_hash, permissions:d.permissions || {}
    }).select('*').single();
    if(error) throw error;
    return data;
  }

  async function audit(action, details={}){
    const sb = needClient();
    const user = session();
    await sb.from('audit_logs').insert({user_id:user?.id||null, action, details});
  }

  async function vendorFinancial(vendorId){
    const sb = needClient();
    const { data:items, error:ierr } = await sb.from('order_items').select('*, orders(status,payment_status,paid_amount,total)').eq('vendor_id', vendorId);
    if(ierr) throw ierr;
    const { data:payments, error:perr } = await sb.from('commission_payments').select('*').eq('vendor_id', vendorId).order('created_at',{ascending:false});
    if(perr) throw perr;
    const rows = items || [];
    const sales = rows.reduce((s,x)=>s+money(x.subtotal),0);
    const delivered = rows.filter(x=>x.orders?.status==='delivered');
    const paidRows = delivered.filter(x=>x.orders?.payment_status==='paid');
    const cancelled = rows.filter(x=>x.orders?.status==='cancelled');
    const deliveredSales = delivered.reduce((s,x)=>s+money(x.subtotal),0);
    const paidSales = paidRows.reduce((s,x)=>s+money(x.subtotal),0);
    const cancelledSales = cancelled.reduce((s,x)=>s+money(x.subtotal),0);
    const commission = delivered.reduce((s,x)=>s+money(x.commission),0);
    const net = delivered.reduce((s,x)=>s+money(x.vendor_net),0);
    const paid = (payments||[]).filter(x=>x.status==='approved').reduce((s,x)=>s+money(x.amount),0);
    const pending = (payments||[]).filter(x=>x.status==='pending').reduce((s,x)=>s+money(x.amount),0);
    const orderIds = [...new Set(rows.map(x=>x.order_id))];
    const deliveredIds = [...new Set(delivered.map(x=>x.order_id))];
    const paidIds = [...new Set(paidRows.map(x=>x.order_id))];
    const cancelledIds = [...new Set(cancelled.map(x=>x.order_id))];
    return {sales, deliveredSales, paidSales, cancelledSales, commission, net, paid, pending, remaining:Math.max(commission-paid,0), payments:payments||[], orderStats:{total:orderIds.length, delivered:deliveredIds.length, paid:paidIds.length, cancelled:cancelledIds.length, active:Math.max(orderIds.length-deliveredIds.length-cancelledIds.length,0)}};
  }

  async function savePayment(p){
    const sb = needClient();
    const { data, error } = await sb.from('commission_payments').insert(p).select('*').single();
    if(error) throw error;
    return data;
  }

  async function payments(){
    const sb = needClient();
    const { data, error } = await sb.from('commission_payments').select('*, vendors(store_name)').order('created_at',{ascending:false});
    if(error) throw error;
    return data || [];
  }

  async function updatePayment(id, status, admin_note=''){
    const sb = needClient();
    const { error } = await sb.from('commission_payments').update({status, admin_note, updated_at:nowIso()}).eq('id', id);
    if(error) throw error;
  }

  async function adminSummary(){
    const [vs, ps, os, pay, tickets, staff] = await Promise.all([vendors(), products(), allOrders(), payments(), supportTickets(), usersByRole('staff')]);
    return {vendors:vs, products:ps, orders:os, payments:pay, tickets, staff};
  }

  window.TagerDB = { ready:!!client, client, session, setSession, clearSession, countAdmins, createAdmin, login, registerCustomer, registerVendor, vendors, products, vendorByUser, updateVendor, updateUser, saveProduct, createOrder, myOrders, vendorOrders, allOrders, updateOrderStatus, updateOrderPayment, updateDeliveryStatus, createSupportTicket, supportTickets, updateSupportTicket, usersByRole, createStaff, audit, vendorFinancial, savePayment, payments, updatePayment, adminSummary, zoneMatches, priceFor, money };
})();
