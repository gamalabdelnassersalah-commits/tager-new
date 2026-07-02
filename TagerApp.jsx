'use client';

import { useEffect, useMemo, useState } from 'react';
import Image from 'next/image';
import { supabase, isSupabaseConfigured } from '@/lib/supabase';
import { GOVERNORATES, districtsOf, areasOf } from '@/lib/egypt';
import { egp, priceForQty, priceTier } from '@/lib/money';

const CATEGORIES = ['مواد غذائية','مشروبات','منظفات','ورقيات','تجميل وعناية','بقالة','منتجات مجمدة','أدوات منزلية','أخرى'];
const PAYMENT_METHODS = ['تحويل بنكي','InstaPay','محفظة إلكترونية','فوري','نقدي عند الاستلام','بطاقة بنكية'];
const ORDER_STATUS = ['new','confirmed','preparing','shipped','delivered','cancelled'];
const ORDER_STATUS_AR = { new:'جديد', confirmed:'مؤكد', preparing:'جاري التجهيز', shipped:'تم الشحن', delivered:'مكتمل', cancelled:'ملغي' };

function go(route){ if (typeof window !== 'undefined') window.location.hash = route === 'home' ? '#/' : `#/${route}`; }
function normalizePhone(phone=''){ return String(phone).replace(/\s+/g,'').replace(/^\+2/,'0'); }
async function sha256(text){ const data=new TextEncoder().encode(text); const hash=await crypto.subtle.digest('SHA-256',data); return Array.from(new Uint8Array(hash)).map(b=>b.toString(16).padStart(2,'0')).join(''); }
function getRouteFromHash(){ const h=(typeof window==='undefined'?'':window.location.hash).replace(/^#\/?/,''); return h || 'home'; }
function uniq(list){ return [...new Set((list||[]).filter(Boolean))]; }
function asNum(v){ return Number(v||0); }
function isAdmin(u){ return u && (u.role === 'admin' || u.role === 'staff'); }
function isVendor(u){ return u?.role === 'vendor'; }
function isCustomer(u){ return u?.role === 'customer'; }
function calcLine(product, qty){ const unit=priceForQty(product, qty); const subtotal=unit*Number(qty||1); return {unit, subtotal, tier:priceTier(product, qty)}; }

async function imageDimensions(file){
  return new Promise((resolve,reject)=>{
    const img = new window.Image();
    img.onload=()=>resolve({width:img.width,height:img.height});
    img.onerror=reject;
    img.src=URL.createObjectURL(file);
  });
}

export default function TagerApp({ initialRoute='home' }){
  const [route,setRoute]=useState(initialRoute);
  const [loading,setLoading]=useState(false);
  const [message,setMessage]=useState('');
  const [error,setError]=useState('');
  const [currentUser,setCurrentUser]=useState(null);
  const [users,setUsers]=useState([]);
  const [vendors,setVendors]=useState([]);
  const [zones,setZones]=useState([]);
  const [products,setProducts]=useState([]);
  const [orders,setOrders]=useState([]);
  const [orderItems,setOrderItems]=useState([]);
  const [payments,setPayments]=useState([]);
  const [cart,setCart]=useState([]);
  const [filter,setFilter]=useState({q:'',tier:'all',category:'all',governorate:'',district:'',area:''});

  useEffect(()=>{
    const saved = localStorage.getItem('tager_current_user');
    const savedCart = localStorage.getItem('tager_cart');
    if(saved) setCurrentUser(JSON.parse(saved));
    if(savedCart) setCart(JSON.parse(savedCart));
    const applyRoute=()=>setRoute(getRouteFromHash() || initialRoute);
    applyRoute();
    window.addEventListener('hashchange', applyRoute);
    return ()=>window.removeEventListener('hashchange', applyRoute);
  },[initialRoute]);

  useEffect(()=>{ localStorage.setItem('tager_cart', JSON.stringify(cart)); },[cart]);

  useEffect(()=>{ if(isSupabaseConfigured) refreshAll(); },[]);

  async function refreshAll(){
    if(!supabase) return;
    setLoading(true); setError('');
    try{
      const [u,v,z,p,o,oi,cp] = await Promise.all([
        supabase.from('users').select('*').order('created_at',{ascending:false}),
        supabase.from('vendors').select('*'),
        supabase.from('vendor_delivery_zones').select('*').order('created_at',{ascending:false}),
        supabase.from('products').select('*').order('created_at',{ascending:false}),
        supabase.from('orders').select('*').order('created_at',{ascending:false}),
        supabase.from('order_items').select('*'),
        supabase.from('commission_payments').select('*').order('created_at',{ascending:false})
      ]);
      const anyErr=[u,v,z,p,o,oi,cp].find(x=>x.error);
      if(anyErr) throw anyErr.error;
      setUsers(u.data||[]); setVendors(v.data||[]); setZones(z.data||[]); setProducts(p.data||[]); setOrders(o.data||[]); setOrderItems(oi.data||[]); setPayments(cp.data||[]);
    }catch(e){ setError(e.message || 'حدث خطأ في تحميل البيانات'); }
    setLoading(false);
  }

  const vendorMap = useMemo(()=>Object.fromEntries(vendors.map(v=>[v.user_id,v])),[vendors]);
  const userMap = useMemo(()=>Object.fromEntries(users.map(u=>[u.id,u])),[users]);
  const approvedVendorIds = useMemo(()=>users.filter(u=>u.role==='vendor' && u.status==='approved').map(u=>u.id),[users]);
  const activeZones = useMemo(()=>zones.filter(z=>z.is_active !== false && approvedVendorIds.includes(z.vendor_id)),[zones,approvedVendorIds]);
  const availableGovs = useMemo(()=>uniq(activeZones.map(z=>z.governorate)),[activeZones]);
  const availableDistricts = useMemo(()=>uniq(activeZones.filter(z=>!filter.governorate || z.governorate===filter.governorate).map(z=>z.district)),[activeZones,filter.governorate]);
  const availableAreas = useMemo(()=>uniq(activeZones.filter(z=>(!filter.governorate || z.governorate===filter.governorate) && (!filter.district || z.district===filter.district)).map(z=>z.area)),[activeZones,filter.governorate,filter.district]);

  function vendorCovers(vendorId, gov, dist, area){
    if(!gov) return true;
    return zones.some(z=>z.vendor_id===vendorId && z.is_active !== false && z.governorate===gov && (!dist || z.district===dist) && (!area || z.area===area));
  }
  function vendorDeliveryFee(vendorId, gov, dist, area){
    const exact = zones.find(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && z.district===dist && z.area===area);
    const partial = zones.find(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && z.district===dist);
    return Number((exact||partial)?.delivery_fee || 0);
  }
  const approvedProducts = useMemo(()=>products.filter(p=>p.status==='approved' && approvedVendorIds.includes(p.vendor_id)),[products,approvedVendorIds]);
  const filteredProducts = useMemo(()=>{
    return approvedProducts.filter(p=>{
      const v=userMap[p.vendor_id]; const vendor=vendorMap[p.vendor_id];
      const q=filter.q.trim().toLowerCase();
      const matchesQ=!q || [p.name_ar,p.name_en,p.sku,p.category,p.brand,vendor?.store_name,v?.name].filter(Boolean).join(' ').toLowerCase().includes(q);
      const matchesCat=filter.category==='all' || p.category===filter.category;
      const matchesDelivery=vendorCovers(p.vendor_id,filter.governorate,filter.district,filter.area);
      return matchesQ && matchesCat && matchesDelivery;
    });
  },[approvedProducts,filter,userMap,vendorMap,zones]);

  function flash(msg){ setMessage(msg); setError(''); setTimeout(()=>setMessage(''),4000); }
  function fail(msg){ setError(msg); setMessage(''); }

  async function uploadImage(file,bucket,folder){
    if(!file) return '';
    if(!file.type.match(/^image\/(png|jpeg|webp)$/)) throw new Error('الصورة يجب أن تكون PNG أو JPG أو WebP');
    if(file.size > 5*1024*1024) throw new Error('حجم الصورة أكبر من 5 ميجابايت');
    const dims = await imageDimensions(file);
    if(dims.width < 600 || dims.height < 600) throw new Error('الصورة غير واضحة: أقل مقاس مسموح 600×600');
    const ext = file.name.split('.').pop();
    const path = `${folder}/${Date.now()}-${Math.random().toString(16).slice(2)}.${ext}`;
    const { error: upErr } = await supabase.storage.from(bucket).upload(path,file,{upsert:false});
    if(upErr) throw upErr;
    const { data } = supabase.storage.from(bucket).getPublicUrl(path);
    return data.publicUrl;
  }

  async function login(form){
    if(!supabase) return fail('Supabase غير مربوط');
    setLoading(true); setError('');
    try{
      const identifier = normalizePhone(form.identifier || '');
      const isPhone = /^0\d{9,10}$/.test(identifier);
      const query = supabase.from('users').select('*').or(isPhone ? `phone.eq.${identifier}` : `email.eq.${form.identifier}` ).limit(1).maybeSingle();
      const { data:user, error:err } = await query;
      if(err) throw err;
      if(!user) throw new Error('الحساب غير موجود');
      const passHash = await sha256(`${user.phone}:${form.password}`);
      if(passHash !== user.password_hash) throw new Error('كلمة المرور غير صحيحة');
      if(user.status === 'suspended' || user.status === 'rejected') throw new Error('الحساب غير مفعل');
      setCurrentUser(user); localStorage.setItem('tager_current_user', JSON.stringify(user));
      flash('تم تسجيل الدخول بنجاح');
      if(user.role==='admin' || user.role==='staff') go('admin');
      else if(user.role==='vendor') go('vendor-dashboard');
      else go('customer-dashboard');
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  async function logout(){ setCurrentUser(null); localStorage.removeItem('tager_current_user'); go('home'); }

  async function registerCustomer(form){
    if(!supabase) return fail('Supabase غير مربوط');
    setLoading(true);
    try{
      const phone = normalizePhone(form.phone);
      if(!phone) throw new Error('رقم الهاتف مطلوب');
      const password_hash=await sha256(`${phone}:${form.password}`);
      const { error:err } = await supabase.from('users').insert({role:'customer',status:'approved',name:form.name,phone,email:form.email||null,password_hash,governorate:form.governorate,district:form.district,address:form.address});
      if(err) throw err;
      flash('تم تسجيل العميل. يمكنك تسجيل الدخول الآن.'); go('login'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  async function registerVendor(form){
    if(!supabase) return fail('Supabase غير مربوط');
    setLoading(true);
    try{
      const phone=normalizePhone(form.phone); if(!phone) throw new Error('رقم الهاتف مطلوب');
      const password_hash=await sha256(`${phone}:${form.password}`);
      const logo_url = form.logo ? await uploadImage(form.logo,'vendor-images','logos') : '';
      const { data:user, error:err } = await supabase.from('users').insert({role:'vendor',status:'pending',name:form.name,phone,email:form.email||null,password_hash,governorate:form.governorate,district:form.district,address:form.address}).select().single();
      if(err) throw err;
      const { error:vErr } = await supabase.from('vendors').insert({user_id:user.id,store_name:form.store_name,commercial_register:form.commercial_register||null,tax_number:form.tax_number||null,logo_url,governorate:form.governorate,district:form.district,description:form.description||'',min_order:Number(form.min_order||0),commission_percent:Number(form.commission_percent||10),premium_cart_percent:Number(form.premium_cart_percent||1.5)});
      if(vErr) throw vErr;
      if(form.governorate && form.district){
        await supabase.from('vendor_delivery_zones').insert({vendor_id:user.id,governorate:form.governorate,district:form.district,area:form.area||'كل المناطق',delivery_fee:Number(form.delivery_fee||0),eta_days:Number(form.eta_days||2),is_active:true});
      }
      flash('تم تسجيل المورد، والحساب الآن تحت مراجعة الإدارة.'); go('login'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  async function setupAdmin(form){
    if(!supabase) return fail('Supabase غير مربوط');
    setLoading(true);
    try{
      const { count, error:cErr } = await supabase.from('users').select('*',{count:'exact',head:true}).in('role',['admin','staff']);
      if(cErr) throw cErr;
      if(count>0) throw new Error('يوجد حساب إدارة بالفعل. استخدم تسجيل الدخول.');
      const phone=normalizePhone(form.phone); const password_hash=await sha256(`${phone}:${form.password}`);
      const { error:err } = await supabase.from('users').insert({role:'admin',status:'approved',name:form.name||'مدير تاجر',phone,email:form.email||null,password_hash});
      if(err) throw err;
      flash('تم إنشاء حساب الإدارة بدون أي موردين تجريبيين.'); go('login'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  async function approveUser(userId,status){
    setLoading(true);
    const {error:err}=await supabase.from('users').update({status}).eq('id',userId);
    if(err) fail(err.message); else flash('تم تحديث حالة الحساب');
    await refreshAll(); setLoading(false);
  }

  async function approveProduct(productId,status){
    setLoading(true);
    const {error:err}=await supabase.from('products').update({status}).eq('id',productId);
    if(err) fail(err.message); else flash('تم تحديث حالة المنتج');
    await refreshAll(); setLoading(false);
  }

  async function addDeliveryZone(form){
    if(!isVendor(currentUser)) return fail('هذه الصفحة للمورد فقط');
    setLoading(true);
    const record={vendor_id:currentUser.id,governorate:form.governorate,district:form.district,area:form.area||'كل المناطق',delivery_fee:Number(form.delivery_fee||0),eta_days:Number(form.eta_days||2),is_active:true};
    const {error:err}=await supabase.from('vendor_delivery_zones').insert(record);
    if(err) fail(err.message); else flash('تمت إضافة منطقة التوصيل');
    await refreshAll(); setLoading(false);
  }

  async function removeZone(id){
    setLoading(true);
    const {error:err}=await supabase.from('vendor_delivery_zones').delete().eq('id',id);
    if(err) fail(err.message); else flash('تم حذف منطقة التوصيل');
    await refreshAll(); setLoading(false);
  }

  async function addProduct(form){
    if(!isVendor(currentUser)) return fail('هذه الصفحة للمورد فقط');
    setLoading(true);
    try{
      const retail=Number(form.retail_price||0), wholesale=Number(form.wholesale_price||0), superp=Number(form.super_wholesale_price||0);
      if(!form.name_ar) throw new Error('اسم المنتج مطلوب');
      if(retail<=0 || wholesale<=0 || superp<=0) throw new Error('كل الأسعار مطلوبة');
      if(retail < wholesale || wholesale < superp) throw new Error('يجب أن يكون القطاعي أكبر أو يساوي الجملة، والجملة أكبر أو تساوي جملة الجملة');
      if(Number(form.stock||0)<1) throw new Error('المخزون مطلوب');
      const image_url = form.image ? await uploadImage(form.image,'product-images','products') : '';
      const record={vendor_id:currentUser.id,status:'pending',name_ar:form.name_ar,name_en:form.name_en||'',sku:form.sku||'',category:form.category||'أخرى',brand:form.brand||'',unit:form.unit||'قطعة',description_ar:form.description_ar||'',short_description:form.short_description||'',retail_price:retail,wholesale_price:wholesale,super_wholesale_price:superp,wholesale_min:Number(form.wholesale_min||12),super_wholesale_min:Number(form.super_wholesale_min||48),stock:Number(form.stock||0),max_qty:Number(form.max_qty||999),lead_time_days:Number(form.lead_time_days||0),image_url};
      const {error:err}=await supabase.from('products').insert(record);
      if(err) throw err;
      flash('تم حفظ المنتج وإرساله للمراجعة'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  function addToCart(product, qty=1){
    const q=Number(qty||1);
    if(q<1) return fail('الكمية غير صحيحة');
    if(q>Number(product.stock||0)) return fail('الكمية أكبر من المخزون');
    if(filter.governorate && !vendorCovers(product.vendor_id,filter.governorate,filter.district,filter.area)) return fail('هذا المورد لا يغطي مكان التوصيل المختار');
    setCart(prev=>{
      const idx=prev.findIndex(i=>i.product_id===product.id);
      if(idx>=0){ const next=[...prev]; next[idx]={...next[idx],qty:next[idx].qty+q}; return next; }
      return [...prev,{product_id:product.id,qty:q}];
    });
    flash('تمت إضافة المنتج للسلة');
  }

  function updateCartQty(productId, qty){ setCart(prev=>prev.map(i=>i.product_id===productId?{...i,qty:Number(qty||1)}:i).filter(i=>i.qty>0)); }
  function removeCart(productId){ setCart(prev=>prev.filter(i=>i.product_id!==productId)); }

  function cartDetails(delivery={}){
    const items=cart.map(c=>({cart:c,product:products.find(p=>p.id===c.product_id)})).filter(x=>x.product);
    const vendorIds=uniq(items.map(x=>x.product.vendor_id));
    const byVendor=vendorIds.map(vendorId=>{
      const vendor=vendorMap[vendorId]||{}; const user=userMap[vendorId]||{};
      const vendorItems=items.filter(x=>x.product.vendor_id===vendorId).map(x=>{ const line=calcLine(x.product,x.cart.qty); return {...x,...line}; });
      const subtotal=vendorItems.reduce((s,x)=>s+x.subtotal,0);
      const covers=vendorCovers(vendorId,delivery.governorate,delivery.district,delivery.area);
      const fee=delivery.governorate ? vendorDeliveryFee(vendorId,delivery.governorate,delivery.district,delivery.area) : 0;
      const minOk=subtotal>=Number(vendor.min_order||0);
      return {vendorId,vendor,user,items:vendorItems,subtotal,covers,fee,minOk};
    });
    const subtotal=byVendor.reduce((s,v)=>s+v.subtotal,0);
    const shipping=byVendor.reduce((s,v)=>s+v.fee,0);
    const premium=byVendor.reduce((s,v)=>s+(v.subtotal*Number(v.vendor.premium_cart_percent||1.5)/100),0);
    return {items,byVendor,subtotal,shipping,premium};
  }

  async function placeOrder(form){
    if(!isCustomer(currentUser)) return fail('سجل دخول كعميل أولاً');
    setLoading(true);
    try{
      const details=cartDetails(form);
      if(!details.items.length) throw new Error('السلة فارغة');
      if(!form.governorate || !form.district || !form.area) throw new Error('اختر المحافظة والمركز والقسم/الحي');
      const blocked=details.byVendor.find(v=>!v.covers || !v.minOk);
      if(blocked){
        if(!blocked.covers) throw new Error(`المورد ${blocked.vendor.store_name||blocked.user.name} لا يغطي مكان التوصيل المختار`);
        throw new Error(`طلب المورد ${blocked.vendor.store_name||blocked.user.name} أقل من الحد الأدنى ${egp(blocked.vendor.min_order)}`);
      }
      const premiumFee=form.cart_type==='premium' ? details.premium : 0;
      const commissionTotal=details.byVendor.reduce((sum,v)=>sum+v.items.reduce((s,i)=>s+i.subtotal*Number(v.vendor.commission_percent||10)/100,0),0);
      const vendorNet=details.subtotal-commissionTotal;
      const orderRecord={customer_id:currentUser.id,cart_type:form.cart_type||'separate',governorate:form.governorate,district:form.district,area:form.area,address:form.address||currentUser.address||'',shipping_fee:details.shipping,premium_fee:premiumFee,payment_method:form.payment_method||'نقدي عند الاستلام',payment_status:'pending',total:details.subtotal+details.shipping+premiumFee,platform_commission:commissionTotal+premiumFee,vendor_net:vendorNet,status:'new',delivery_status:'pending'};
      const {data:order,error:oErr}=await supabase.from('orders').insert(orderRecord).select().single();
      if(oErr) throw oErr;
      const rows=[];
      details.byVendor.forEach(v=>v.items.forEach(i=>{
        const commission= i.subtotal*Number(v.vendor.commission_percent||10)/100;
        rows.push({order_id:order.id,product_id:i.product.id,vendor_id:v.vendorId,qty:i.cart.qty,unit_price:i.unit,subtotal:i.subtotal,commission_percent:Number(v.vendor.commission_percent||10),commission_amount:commission,vendor_net:i.subtotal-commission});
      }));
      const {error:iErr}=await supabase.from('order_items').insert(rows);
      if(iErr) throw iErr;
      const shipments=details.byVendor.map(v=>({order_id:order.id,vendor_id:v.vendorId,status:'pending',governorate:form.governorate,district:form.district,area:form.area,delivery_fee:v.fee,eta_days:Number(v.vendor?.eta_days||2)}));
      await supabase.from('shipments').insert(shipments);
      setCart([]); flash('تم إنشاء الطلب بنجاح'); go('customer-dashboard'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  async function submitCommissionPayment(form){
    if(!isVendor(currentUser)) return fail('هذه الصفحة للمورد فقط');
    setLoading(true);
    const {error:err}=await supabase.from('commission_payments').insert({vendor_id:currentUser.id,amount:Number(form.amount||0),method:form.method,reference:form.reference||'',notes:form.notes||'',status:'pending'});
    if(err) fail(err.message); else flash('تم إرسال الدفعة للمراجعة');
    await refreshAll(); setLoading(false);
  }

  async function updatePaymentStatus(id,status){
    setLoading(true); const {error:err}=await supabase.from('commission_payments').update({status}).eq('id',id);
    if(err) fail(err.message); else flash('تم تحديث حالة الدفعة');
    await refreshAll(); setLoading(false);
  }

  async function updateOrderStatus(orderId,status){
    setLoading(true); const {error:err}=await supabase.from('orders').update({status}).eq('id',orderId);
    if(err) fail(err.message); else flash('تم تحديث حالة الطلب');
    await refreshAll(); setLoading(false);
  }

  async function addStaff(form){
    if(!isAdmin(currentUser)) return;
    setLoading(true);
    try{
      const phone=normalizePhone(form.phone); const password_hash=await sha256(`${phone}:${form.password}`);
      const permissions={vendors:!!form.vendors,products:!!form.products,finance:!!form.finance,orders:!!form.orders,shipping:!!form.shipping};
      const {error:err}=await supabase.from('users').insert({role:'staff',status:'approved',name:form.name,phone,email:form.email||null,password_hash,permissions});
      if(err) throw err; flash('تم إضافة موظف إدارة'); await refreshAll();
    }catch(e){ fail(e.message); }
    setLoading(false);
  }

  const finance = useMemo(()=>{
    const result={};
    users.filter(u=>u.role==='vendor').forEach(u=>{ result[u.id]={vendorId:u.id,user:u,vendor:vendorMap[u.id]||{},sales:0,commissionDue:0,paid:0,pending:0,remaining:0,vendorNet:0,orders:0}; });
    orderItems.forEach(i=>{ const r=result[i.vendor_id]; if(r){ r.sales+=asNum(i.subtotal); r.commissionDue+=asNum(i.commission_amount); r.vendorNet+=asNum(i.vendor_net); r.orders+=1; } });
    payments.forEach(p=>{ const r=result[p.vendor_id]; if(r){ if(p.status==='approved') r.paid+=asNum(p.amount); if(p.status==='pending') r.pending+=asNum(p.amount); } });
    Object.values(result).forEach(r=>{ r.remaining=Math.max(0,r.commissionDue-r.paid); });
    return result;
  },[users,vendorMap,orderItems,payments]);

  if(!isSupabaseConfigured){
    return <Shell currentUser={currentUser} logout={logout} route={route} setRoute={go} cartCount={cart.length}><div className="container section"><div className="error"><h2>Supabase غير مربوط</h2><p>أضف متغيرات Vercel: NEXT_PUBLIC_SUPABASE_URL و NEXT_PUBLIC_SUPABASE_ANON_KEY ثم اعمل Redeploy.</p></div></div></Shell>;
  }

  let content;
  if(route==='setup') content=<SetupAdmin onSubmit={setupAdmin} users={users} loading={loading}/>;
  else if(route==='login') content=<Login onSubmit={login} loading={loading}/>;
  else if(route==='register-customer') content=<RegisterCustomer onSubmit={registerCustomer} loading={loading}/>;
  else if(route==='register-vendor') content=<RegisterVendor onSubmit={registerVendor} loading={loading}/>;
  else if(route==='market') content=<Market products={filteredProducts} userMap={userMap} vendorMap={vendorMap} addToCart={addToCart} filter={filter} setFilter={setFilter} availableGovs={availableGovs} availableDistricts={availableDistricts} availableAreas={availableAreas}/>;
  else if(route==='vendors') content=<Vendors users={users} vendors={vendors} zones={zones}/>;
  else if(route.startsWith('vendor-store:')) content=<VendorStore vendorId={route.split(':')[1]} users={users} vendors={vendors} zones={zones} products={approvedProducts} addToCart={addToCart}/>;
  else if(route==='cart') content=<Cart cart={cart} products={products} vendorMap={vendorMap} userMap={userMap} zones={activeZones} updateCartQty={updateCartQty} removeCart={removeCart} placeOrder={placeOrder} details={cartDetails} currentUser={currentUser}/>;
  else if(route==='customer-dashboard') content=<CustomerDashboard currentUser={currentUser} orders={orders} orderItems={orderItems} products={products} userMap={userMap}/>;
  else if(route==='vendor-dashboard') content=<VendorDashboard currentUser={currentUser} vendor={vendorMap[currentUser?.id]} zones={zones.filter(z=>z.vendor_id===currentUser?.id)} products={products.filter(p=>p.vendor_id===currentUser?.id)} orders={orders} orderItems={orderItems.filter(i=>i.vendor_id===currentUser?.id)} finance={finance[currentUser?.id]} payments={payments.filter(p=>p.vendor_id===currentUser?.id)} addProduct={addProduct} addDeliveryZone={addDeliveryZone} removeZone={removeZone} submitCommissionPayment={submitCommissionPayment} updateOrderStatus={updateOrderStatus} loading={loading}/>;
  else if(route==='admin') content=<AdminDashboard users={users} vendors={vendors} products={products} zones={zones} orders={orders} orderItems={orderItems} payments={payments} finance={finance} approveUser={approveUser} approveProduct={approveProduct} updatePaymentStatus={updatePaymentStatus} addStaff={addStaff} currentUser={currentUser} updateOrderStatus={updateOrderStatus}/>;
  else if(route==='how') content=<HowItWorks/>;
  else content=<Home products={filteredProducts.slice(0,6)} users={users} userMap={userMap} vendorMap={vendorMap} addToCart={addToCart} zones={activeZones}/>;

  return <Shell currentUser={currentUser} logout={logout} route={route} setRoute={go} cartCount={cart.length}>{message&&<div className="container success">{message}</div>}{error&&<div className="container error">{error}</div>}{loading&&<div className="container notice">جاري التنفيذ...</div>}{content}</Shell>;
}

function Shell({children,currentUser,logout,route,setRoute,cartCount}){
  return <>
    <div className="topbar"><div className="container"><span>قطاعي وجملة وجملة الجملة</span><span>الدعم: 01000000000</span></div></div>
    <header className="header"><div className="container nav">
      <button className="brand" onClick={()=>setRoute('home')}><img src="/tager-logo.png" alt="Tager"/><span>Tager</span></button>
      <nav className="navlinks">
        <button className={route==='home'?'active':''} onClick={()=>setRoute('home')}>الرئيسية</button>
        <button className={route==='market'?'active':''} onClick={()=>setRoute('market')}>المنتجات</button>
        <button className={route==='vendors'?'active':''} onClick={()=>setRoute('vendors')}>الموردون</button>
        <button className={route==='how'?'active':''} onClick={()=>setRoute('how')}>كيف تعمل؟</button>
        {currentUser?.role==='admin' || currentUser?.role==='staff' ? <button className="active" onClick={()=>setRoute('admin')}>الإدارة</button> : null}
        {currentUser?.role==='vendor' ? <button className="active" onClick={()=>setRoute('vendor-dashboard')}>لوحة المورد</button> : null}
        {currentUser?.role==='customer' ? <button className="active" onClick={()=>setRoute('customer-dashboard')}>حسابي</button> : null}
      </nav>
      <div className="row">
        <button className="btn" onClick={()=>setRoute('cart')}>السلة <span className="pill warn">{cartCount}</span></button>
        {currentUser ? <><span className="pill">{currentUser.name}</span><button className="btn danger" onClick={logout}>خروج</button></> : <button className="btn primary" onClick={()=>setRoute('login')}>دخول</button>}
      </div>
    </div></header>
    {children}
    <button className="btn primary cart-float" onClick={()=>setRoute('cart')}>السلة ({cartCount})</button>
    <footer className="footer"><div className="container grid three"><div><h3>Tager</h3><p>Trade • Supply • Connect</p></div><div><b>منصة موردين</b><p>مناطق توصيل واضحة، أسعار متدرجة، ومتابعة مالية.</p></div><div><b>جاهز للربط النهائي</b><p>لا توجد بيانات موردين تجريبية. ابدأ بإنشاء حساب الإدارة من /setup.</p></div></div></footer>
  </>;
}

function Home({products,users,userMap,vendorMap,addToCart,zones}){
  const approvedVendors=users.filter(u=>u.role==='vendor'&&u.status==='approved').length;
  const govs=uniq(zones.map(z=>z.governorate)).length;
  return <>
    <section className="hero"><div className="container hero-box"><div><span className="badge">منصة تجارة السوق المصري</span><h1>قطاعي وجملة وجملة الجملة</h1><p>اختار المورد ومكان التوصيل ونوع السعر، وتابع الطلبات والأرصدة والعمولات من لوحة واحدة.</p><div className="hero-actions"><button className="btn gold" onClick={()=>go('market')}>ابدأ التسوق</button><button className="btn" onClick={()=>go('register-vendor')}>انضم كمورد</button></div></div><div className="hero-stats"><div className="stat"><b>{products.length}</b><span>منتج معتمد</span></div><div className="stat"><b>{approvedVendors}</b><span>مورد معتمد</span></div><div className="stat"><b>{govs}</b><span>محافظة توصيل</span></div></div></div></section>
    <main className="container section"><div className="section-title"><h2>أحدث المنتجات</h2><button className="btn" onClick={()=>go('market')}>عرض السوق</button></div><ProductGrid products={products} userMap={userMap} vendorMap={vendorMap} addToCart={addToCart}/></main>
  </>;
}

function ProductGrid({products,userMap,vendorMap,addToCart}){
  if(!products.length) return <div className="empty"><h3>لا توجد منتجات معتمدة بعد</h3><p>سجل كمورد وأضف منتجات، ثم توافق الإدارة عليها.</p></div>;
  return <div className="grid three">{products.map(p=><ProductCard key={p.id} p={p} userMap={userMap} vendorMap={vendorMap} addToCart={addToCart}/>)}</div>;
}
function ProductCard({p,userMap,vendorMap,addToCart}){
  const [qty,setQty]=useState(1); const vendor=vendorMap[p.vendor_id]||{}; const line=calcLine(p,qty);
  return <div className="card product-card"><div className="product-img">{p.image_url?<img src={p.image_url} alt={p.name_ar}/>:<span className="muted">صورة المنتج</span>}</div><div><h3>{p.name_ar}</h3><p className="muted">{vendor.store_name || userMap[p.vendor_id]?.name || 'مورد'}</p></div><div className="price-line"><div><b>{egp(p.retail_price)}</b><br/>قطاعي</div><div><b>{egp(p.wholesale_price)}</b><br/>جملة من {p.wholesale_min}</div><div><b>{egp(p.super_wholesale_price)}</b><br/>جملة الجملة من {p.super_wholesale_min}</div></div><div className="row"><input style={{width:90}} type="number" min="1" value={qty} onChange={e=>setQty(e.target.value)}/><span className="pill">{line.tier}</span><span>{egp(line.subtotal)}</span></div><div className="between"><button className="btn primary" onClick={()=>addToCart(p,qty)}>إضافة للسلة</button><button className="btn" onClick={()=>go(`vendor-store:${p.vendor_id}`)}>المورد</button></div></div>;
}

function Market({products,userMap,vendorMap,addToCart,filter,setFilter,availableGovs,availableDistricts,availableAreas}){
  return <main className="container section"><div className="section-title"><h2>السوق والمنتجات</h2><p className="muted">المراكز والأقسام تظهر حسب مناطق الموردين الفعلية فقط.</p></div><div className="filters"><Field label="بحث"><input value={filter.q} onChange={e=>setFilter({...filter,q:e.target.value})} placeholder="منتج أو مورد"/></Field><Field label="القسم"><select value={filter.category} onChange={e=>setFilter({...filter,category:e.target.value})}><option value="all">كل الأقسام</option>{CATEGORIES.map(c=><option key={c}>{c}</option>)}</select></Field><Field label="المحافظة"><select value={filter.governorate} onChange={e=>setFilter({...filter,governorate:e.target.value,district:'',area:''})}><option value="">كل المحافظات</option>{availableGovs.map(g=><option key={g}>{g}</option>)}</select></Field><Field label="المركز"><select value={filter.district} onChange={e=>setFilter({...filter,district:e.target.value,area:''})}><option value="">كل المراكز</option>{availableDistricts.map(d=><option key={d}>{d}</option>)}</select></Field><Field label="القسم/الحي"><select value={filter.area} onChange={e=>setFilter({...filter,area:e.target.value})}><option value="">كل الأقسام</option>{availableAreas.map(a=><option key={a}>{a}</option>)}</select></Field></div><ProductGrid products={products} userMap={userMap} vendorMap={vendorMap} addToCart={addToCart}/></main>;
}

function Vendors({users,vendors,zones}){
  const rows=users.filter(u=>u.role==='vendor'&&u.status==='approved');
  return <main className="container section"><div className="section-title"><h2>دليل الموردين</h2><button className="btn primary" onClick={()=>go('register-vendor')}>انضم كمورد</button></div><div className="grid three">{rows.map(u=>{const v=vendors.find(x=>x.user_id===u.id)||{}; const z=zones.filter(x=>x.vendor_id===u.id); return <div className="card" key={u.id}><div className="row">{v.logo_url&&<img className="logo-preview" src={v.logo_url} alt={v.store_name}/>}<div><h3>{v.store_name||u.name}</h3><p className="muted">{u.governorate} - {u.district}</p></div></div><p>{v.description}</p><p><b>الحد الأدنى:</b> {egp(v.min_order)}</p><p><b>مناطق التوصيل:</b> {z.length}</p><button className="btn primary" onClick={()=>go(`vendor-store:${u.id}`)}>فتح صفحة المورد</button></div>})}</div>{!rows.length&&<div className="empty">لا يوجد موردون معتمدون بعد.</div>}</main>;
}
function VendorStore({vendorId,users,vendors,zones,products,addToCart}){
  const u=users.find(x=>x.id===vendorId); const v=vendors.find(x=>x.user_id===vendorId)||{}; const z=zones.filter(x=>x.vendor_id===vendorId); const ps=products.filter(p=>p.vendor_id===vendorId);
  if(!u) return <main className="container section"><div className="empty">المورد غير موجود</div></main>;
  return <main className="container section"><div className="card"><div className="row">{v.logo_url&&<img className="logo-preview" src={v.logo_url} alt={v.store_name}/>}<div><h2>{v.store_name||u.name}</h2><p className="muted">{u.governorate} - {u.district}</p></div></div><p>{v.description}</p><div className="row"><span className="pill">حد أدنى {egp(v.min_order)}</span><span className="pill">عمولة {v.commission_percent}%</span></div></div><div className="section-title"><h2>مناطق التوصيل</h2></div><div className="table-wrap"><table className="table"><thead><tr><th>المحافظة</th><th>المركز</th><th>القسم/الحي</th><th>الرسوم</th><th>المدة</th></tr></thead><tbody>{z.map(a=><tr key={a.id}><td>{a.governorate}</td><td>{a.district}</td><td>{a.area}</td><td>{egp(a.delivery_fee)}</td><td>{a.eta_days} يوم</td></tr>)}</tbody></table></div><div className="section-title"><h2>منتجات المورد</h2></div><ProductGrid products={ps} userMap={Object.fromEntries(users.map(x=>[x.id,x]))} vendorMap={{[vendorId]:v}} addToCart={addToCart}/></main>;
}

function Cart({cart,products,vendorMap,userMap,zones,updateCartQty,removeCart,placeOrder,details,currentUser}){
  const [form,setForm]=useState({cart_type:'separate',governorate:'',district:'',area:'',address:'',payment_method:'نقدي عند الاستلام'});
  const det=details(form); const districts=uniq(zones.filter(z=>!form.governorate || z.governorate===form.governorate).map(z=>z.district)); const areas=uniq(zones.filter(z=>(!form.governorate||z.governorate===form.governorate)&&(!form.district||z.district===form.district)).map(z=>z.area));
  return <main className="container section"><div className="section-title"><h2>السلة</h2><p className="muted">يتم منع الطلب إذا كان المورد لا يغطي مكان التوصيل.</p></div>{!det.items.length&&<div className="empty">السلة فارغة</div>}{det.byVendor.map(v=><div className="card" key={v.vendorId}><div className="between"><h3>{v.vendor.store_name||v.user.name}</h3><span className={v.covers&&v.minOk?'pill ok':'pill danger'}>{v.covers?'يغطي المكان':'لا يغطي المكان'} / {v.minOk?'الحد الأدنى مكتمل':'أقل من الحد الأدنى'}</span></div>{v.items.map(i=><div className="between" key={i.product.id}><div><b>{i.product.name_ar}</b><p className="muted">{i.tier} — {egp(i.unit)}</p></div><div className="row"><input style={{width:80}} type="number" min="1" value={i.cart.qty} onChange={e=>updateCartQty(i.product.id,e.target.value)}/><b>{egp(i.subtotal)}</b><button className="btn small danger" onClick={()=>removeCart(i.product.id)}>حذف</button></div></div>)}<p>إجمالي المورد: <b>{egp(v.subtotal)}</b> — شحن: <b>{egp(v.fee)}</b> — حد أدنى: <b>{egp(v.vendor.min_order)}</b></p></div>)}{det.items.length>0&&<div className="card"><h3>بيانات الطلب</h3><div className="grid three"><Field label="نوع السلة"><select value={form.cart_type} onChange={e=>setForm({...form,cart_type:e.target.value})}><option value="separate">سلة كل مورد منفصلة</option><option value="premium">سلة مميزة تجمع الموردين</option></select></Field><Field label="المحافظة"><select value={form.governorate} onChange={e=>setForm({...form,governorate:e.target.value,district:'',area:''})}><option value="">اختر</option>{uniq(zones.map(z=>z.governorate)).map(g=><option key={g}>{g}</option>)}</select></Field><Field label="المركز"><select value={form.district} onChange={e=>setForm({...form,district:e.target.value,area:''})}><option value="">اختر</option>{districts.map(d=><option key={d}>{d}</option>)}</select></Field><Field label="القسم/الحي"><select value={form.area} onChange={e=>setForm({...form,area:e.target.value})}><option value="">اختر</option>{areas.map(a=><option key={a}>{a}</option>)}</select></Field><Field label="طريقة الدفع"><select value={form.payment_method} onChange={e=>setForm({...form,payment_method:e.target.value})}>{PAYMENT_METHODS.map(m=><option key={m}>{m}</option>)}</select></Field><Field label="العنوان التفصيلي"><input value={form.address} onChange={e=>setForm({...form,address:e.target.value})}/></Field></div><div className="notice"><p>إجمالي المنتجات: <b>{egp(det.subtotal)}</b></p><p>الشحن: <b>{egp(det.shipping)}</b></p>{form.cart_type==='premium'&&<p>رسوم السلة المميزة: <b>{egp(det.premium)}</b></p>}<h3>الإجمالي: {egp(det.subtotal+det.shipping+(form.cart_type==='premium'?det.premium:0))}</h3></div><button className="btn primary" disabled={!currentUser} onClick={()=>placeOrder(form)}>تأكيد الطلب</button>{!currentUser&&<p className="muted">سجل دخول كعميل لإتمام الطلب.</p>}</div>}</main>;
}

function Login({onSubmit,loading}){const [f,setF]=useState({identifier:'',password:''}); return <main className="container section"><div className="card" style={{maxWidth:520,margin:'auto'}}><h2>تسجيل الدخول</h2><div className="form"><Field label="رقم الهاتف أو البريد"><input value={f.identifier} onChange={e=>setF({...f,identifier:e.target.value})}/></Field><Field label="كلمة المرور"><input type="password" value={f.password} onChange={e=>setF({...f,password:e.target.value})}/></Field><button className="btn primary" disabled={loading} onClick={()=>onSubmit(f)}>دخول</button><div className="row"><button className="btn" onClick={()=>go('register-customer')}>تسجيل عميل</button><button className="btn" onClick={()=>go('register-vendor')}>تسجيل مورد</button></div></div></div></main>}
function RegisterCustomer({onSubmit,loading}){const [f,setF]=useState({name:'',phone:'',email:'',password:'',governorate:'',district:'',address:''}); return <FormCustomer title="تسجيل عميل" f={f} setF={setF} loading={loading} onSubmit={()=>onSubmit(f)} />}
function FormCustomer({title,f,setF,loading,onSubmit}){return <main className="container section"><div className="card"><h2>{title}</h2><div className="grid two"><Field label="الاسم"><input value={f.name} onChange={e=>setF({...f,name:e.target.value})}/></Field><Field label="الهاتف"><input value={f.phone} onChange={e=>setF({...f,phone:e.target.value})}/></Field><Field label="البريد اختياري"><input value={f.email} onChange={e=>setF({...f,email:e.target.value})}/></Field><Field label="كلمة المرور"><input type="password" value={f.password} onChange={e=>setF({...f,password:e.target.value})}/></Field><Field label="المحافظة"><select value={f.governorate} onChange={e=>setF({...f,governorate:e.target.value,district:''})}><option value="">اختر</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select></Field><Field label="المركز"><select value={f.district} onChange={e=>setF({...f,district:e.target.value})}><option value="">اختر</option>{districtsOf(f.governorate).map(d=><option key={d}>{d}</option>)}</select></Field><Field label="العنوان"><input value={f.address} onChange={e=>setF({...f,address:e.target.value})}/></Field></div><button className="btn primary" disabled={loading} onClick={onSubmit}>حفظ</button></div></main>}
function RegisterVendor({onSubmit,loading}){const [f,setF]=useState({name:'',phone:'',email:'',password:'',store_name:'',commercial_register:'',tax_number:'',governorate:'',district:'',area:'',address:'',description:'',min_order:0,delivery_fee:0,eta_days:2,commission_percent:10,premium_cart_percent:1.5,logo:null}); return <main className="container section"><div className="card"><h2>تسجيل مورد</h2><div className="grid two"><Field label="اسم المسؤول"><input value={f.name} onChange={e=>setF({...f,name:e.target.value})}/></Field><Field label="اسم المتجر"><input value={f.store_name} onChange={e=>setF({...f,store_name:e.target.value})}/></Field><Field label="الهاتف"><input value={f.phone} onChange={e=>setF({...f,phone:e.target.value})}/></Field><Field label="البريد اختياري"><input value={f.email} onChange={e=>setF({...f,email:e.target.value})}/></Field><Field label="كلمة المرور"><input type="password" value={f.password} onChange={e=>setF({...f,password:e.target.value})}/></Field><Field label="السجل التجاري"><input value={f.commercial_register} onChange={e=>setF({...f,commercial_register:e.target.value})}/></Field><Field label="الرقم الضريبي"><input value={f.tax_number} onChange={e=>setF({...f,tax_number:e.target.value})}/></Field><Field label="الحد الأدنى للطلب"><input type="number" value={f.min_order} onChange={e=>setF({...f,min_order:e.target.value})}/></Field><Field label="المحافظة"><select value={f.governorate} onChange={e=>setF({...f,governorate:e.target.value,district:'',area:''})}><option value="">اختر</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select></Field><Field label="المركز"><select value={f.district} onChange={e=>setF({...f,district:e.target.value,area:''})}><option value="">اختر</option>{districtsOf(f.governorate).map(d=><option key={d}>{d}</option>)}</select></Field><Field label="القسم/الحي"><select value={f.area} onChange={e=>setF({...f,area:e.target.value})}><option value="">اختر</option>{areasOf(f.governorate,f.district).map(a=><option key={a}>{a}</option>)}</select></Field><Field label="رسوم التوصيل"><input type="number" value={f.delivery_fee} onChange={e=>setF({...f,delivery_fee:e.target.value})}/></Field><Field label="مدة التوصيل بالأيام"><input type="number" value={f.eta_days} onChange={e=>setF({...f,eta_days:e.target.value})}/></Field><Field label="شعار المورد 600×600"><input type="file" accept="image/png,image/jpeg,image/webp" onChange={e=>setF({...f,logo:e.target.files[0]})}/></Field><Field label="وصف النشاط"><textarea value={f.description} onChange={e=>setF({...f,description:e.target.value})}/></Field></div><button className="btn primary" disabled={loading} onClick={()=>onSubmit(f)}>إرسال للمراجعة</button></div></main>}

function SetupAdmin({onSubmit,users,loading}){const admins=users.filter(u=>u.role==='admin'||u.role==='staff'); const [f,setF]=useState({name:'',phone:'',email:'',password:''}); return <main className="container section"><div className="card admin-only" style={{maxWidth:680,margin:'auto'}}><h2>إعداد الإدارة النهائي</h2><p>هذه الصفحة تنشئ أول حساب إدارة فقط، بدون أي موردين أو عملاء أو منتجات تجريبية.</p>{admins.length>0?<div className="notice">يوجد حساب إدارة بالفعل. اذهب إلى تسجيل الدخول.</div>:<div className="form"><Field label="اسم المدير"><input value={f.name} onChange={e=>setF({...f,name:e.target.value})}/></Field><Field label="هاتف المدير"><input value={f.phone} onChange={e=>setF({...f,phone:e.target.value})}/></Field><Field label="البريد اختياري"><input value={f.email} onChange={e=>setF({...f,email:e.target.value})}/></Field><Field label="كلمة المرور"><input type="password" value={f.password} onChange={e=>setF({...f,password:e.target.value})}/></Field><button className="btn primary" disabled={loading} onClick={()=>onSubmit(f)}>إنشاء الإدارة</button></div>}</div></main>}
function CustomerDashboard({currentUser,orders,orderItems,products,userMap}){if(!isCustomer(currentUser))return <Access/>; const my=orders.filter(o=>o.customer_id===currentUser.id); return <main className="container section"><h2>حساب العميل</h2><div className="grid three"><Kpi label="عدد الطلبات" value={my.length}/><Kpi label="إجمالي المشتريات" value={egp(my.reduce((s,o)=>s+asNum(o.total),0))}/><Kpi label="طلبات نشطة" value={my.filter(o=>!['delivered','cancelled'].includes(o.status)).length}/></div><OrdersTable orders={my} orderItems={orderItems} products={products} userMap={userMap}/></main>}
function VendorDashboard({currentUser,vendor,zones,products,orders,orderItems,finance,payments,addProduct,addDeliveryZone,removeZone,submitCommissionPayment,updateOrderStatus,loading}){if(!isVendor(currentUser))return <Access/>; const [tab,setTab]=useState('summary'); return <main className="container section"><div className="section-title"><h2>لوحة المورد — {vendor?.store_name}</h2><span className="pill">{currentUser.status==='approved'?'معتمد':'تحت المراجعة'}</span></div>{currentUser.status!=='approved'&&<div className="notice">حسابك قيد مراجعة الإدارة، يمكنك تجهيز البيانات حتى يتم الاعتماد.</div>}<div className="tabs">{['summary','products','add','zones','orders','finance'].map(t=><button key={t} className={tab===t?'tab active':'tab'} onClick={()=>setTab(t)}>{({summary:'الملخص',products:'منتجاتي',add:'إضافة منتج',zones:'التوصيل',orders:'الطلبات',finance:'الحساب المالي'})[t]}</button>)}</div>{tab==='summary'&&<div className="grid four"><Kpi label="منتجات" value={products.length}/><Kpi label="تحت المراجعة" value={products.filter(p=>p.status==='pending').length}/><Kpi label="مناطق توصيل" value={zones.length}/><Kpi label="المتبقي للمنصة" value={egp(finance?.remaining||0)}/></div>}{tab==='products'&&<ProductsAdminTable products={products} showVendor={false}/>} {tab==='add'&&<AddProductForm onSubmit={addProduct} loading={loading}/>} {tab==='zones'&&<VendorZones zones={zones} addDeliveryZone={addDeliveryZone} removeZone={removeZone}/>} {tab==='orders'&&<VendorOrders orderItems={orderItems} orders={orders} updateOrderStatus={updateOrderStatus}/>} {tab==='finance'&&<VendorFinance finance={finance} payments={payments} submitCommissionPayment={submitCommissionPayment}/>}</main>}
function AddProductForm({onSubmit,loading}){const [f,setF]=useState({category:CATEGORIES[0],unit:'قطعة',retail_price:0,wholesale_price:0,super_wholesale_price:0,wholesale_min:12,super_wholesale_min:48,stock:0,max_qty:999,lead_time_days:1,image:null}); return <div className="card"><h3>إضافة منتج</h3><div className="grid two"><Field label="اسم المنتج"><input value={f.name_ar||''} onChange={e=>setF({...f,name_ar:e.target.value})}/></Field><Field label="SKU"><input value={f.sku||''} onChange={e=>setF({...f,sku:e.target.value})}/></Field><Field label="القسم"><select value={f.category} onChange={e=>setF({...f,category:e.target.value})}>{CATEGORIES.map(c=><option key={c}>{c}</option>)}</select></Field><Field label="الوحدة"><input value={f.unit} onChange={e=>setF({...f,unit:e.target.value})}/></Field><Field label="سعر القطاعي"><input type="number" value={f.retail_price} onChange={e=>setF({...f,retail_price:e.target.value})}/></Field><Field label="سعر الجملة"><input type="number" value={f.wholesale_price} onChange={e=>setF({...f,wholesale_price:e.target.value})}/></Field><Field label="سعر جملة الجملة"><input type="number" value={f.super_wholesale_price} onChange={e=>setF({...f,super_wholesale_price:e.target.value})}/></Field><Field label="حد الجملة"><input type="number" value={f.wholesale_min} onChange={e=>setF({...f,wholesale_min:e.target.value})}/></Field><Field label="حد جملة الجملة"><input type="number" value={f.super_wholesale_min} onChange={e=>setF({...f,super_wholesale_min:e.target.value})}/></Field><Field label="المخزون"><input type="number" value={f.stock} onChange={e=>setF({...f,stock:e.target.value})}/></Field><Field label="صورة واضحة 600×600"><input type="file" accept="image/png,image/jpeg,image/webp" onChange={e=>setF({...f,image:e.target.files[0]})}/><span className="image-hint">لن تقبل المنصة الصور الصغيرة أو غير الواضحة.</span></Field><Field label="وصف قصير"><input value={f.short_description||''} onChange={e=>setF({...f,short_description:e.target.value})}/></Field><Field label="وصف كامل"><textarea value={f.description_ar||''} onChange={e=>setF({...f,description_ar:e.target.value})}/></Field></div><button className="btn primary" disabled={loading} onClick={()=>onSubmit(f)}>حفظ وإرسال للمراجعة</button></div>}
function VendorZones({zones,addDeliveryZone,removeZone}){const [f,setF]=useState({governorate:'',district:'',area:'',delivery_fee:0,eta_days:2}); return <div className="grid two"><div className="card"><h3>إضافة منطقة توصيل</h3><div className="form"><Field label="المحافظة"><select value={f.governorate} onChange={e=>setF({...f,governorate:e.target.value,district:'',area:''})}><option value="">اختر</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select></Field><Field label="المركز"><select value={f.district} onChange={e=>setF({...f,district:e.target.value,area:''})}><option value="">اختر</option>{districtsOf(f.governorate).map(d=><option key={d}>{d}</option>)}</select></Field><Field label="القسم/الحي"><select value={f.area} onChange={e=>setF({...f,area:e.target.value})}><option value="">كل المناطق</option>{areasOf(f.governorate,f.district).map(a=><option key={a}>{a}</option>)}</select></Field><Field label="رسوم الشحن"><input type="number" value={f.delivery_fee} onChange={e=>setF({...f,delivery_fee:e.target.value})}/></Field><Field label="مدة التوصيل"><input type="number" value={f.eta_days} onChange={e=>setF({...f,eta_days:e.target.value})}/></Field><button className="btn primary" onClick={()=>addDeliveryZone(f)}>إضافة</button></div></div><div className="card"><h3>مناطقك الحالية</h3><div className="table-wrap"><table className="table"><thead><tr><th>محافظة</th><th>مركز</th><th>قسم</th><th>شحن</th><th></th></tr></thead><tbody>{zones.map(z=><tr key={z.id}><td>{z.governorate}</td><td>{z.district}</td><td>{z.area}</td><td>{egp(z.delivery_fee)}</td><td><button className="btn small danger" onClick={()=>removeZone(z.id)}>حذف</button></td></tr>)}</tbody></table></div></div></div>}
function VendorOrders({orderItems,orders,updateOrderStatus}){const orderIds=uniq(orderItems.map(i=>i.order_id)); const list=orders.filter(o=>orderIds.includes(o.id)); return <OrdersTable orders={list} orderItems={orderItems} vendorMode updateOrderStatus={updateOrderStatus}/>}
function VendorFinance({finance,payments,submitCommissionPayment}){const [f,setF]=useState({amount:0,method:'تحويل بنكي',reference:'',notes:''}); return <div className="grid two"><div className="card"><h3>رصيد المورد</h3><p>إجمالي المبيعات: <b>{egp(finance?.sales)}</b></p><p>عمولة المنصة المستحقة: <b>{egp(finance?.commissionDue)}</b></p><p>المدفوع المعتمد: <b>{egp(finance?.paid)}</b></p><p>المتبقي: <b>{egp(finance?.remaining)}</b></p><p>صافي المورد: <b>{egp(finance?.vendorNet)}</b></p></div><div className="card"><h3>تسجيل دفع عمولة</h3><div className="form"><Field label="المبلغ"><input type="number" value={f.amount} onChange={e=>setF({...f,amount:e.target.value})}/></Field><Field label="طريقة الدفع"><select value={f.method} onChange={e=>setF({...f,method:e.target.value})}>{PAYMENT_METHODS.map(m=><option key={m}>{m}</option>)}</select></Field><Field label="رقم العملية"><input value={f.reference} onChange={e=>setF({...f,reference:e.target.value})}/></Field><Field label="ملاحظات"><textarea value={f.notes} onChange={e=>setF({...f,notes:e.target.value})}/></Field><button className="btn primary" onClick={()=>submitCommissionPayment(f)}>إرسال للمراجعة</button></div></div><div className="card" style={{gridColumn:'1/-1'}}><PaymentsTable payments={payments}/></div></div>}
function AdminDashboard({users,vendors,products,zones,orders,orderItems,payments,finance,approveUser,approveProduct,updatePaymentStatus,addStaff,currentUser,updateOrderStatus}){if(!isAdmin(currentUser))return <Access/>; const [tab,setTab]=useState('summary'); return <main className="container section"><div className="section-title"><h2>بوابة الإدارة النهائية</h2><button className="btn" onClick={()=>go('setup')}>إعداد الإدارة</button></div><div className="tabs">{['summary','vendors','products','orders','finance','staff','delivery'].map(t=><button key={t} className={tab===t?'tab active':'tab'} onClick={()=>setTab(t)}>{({summary:'الملخص',vendors:'الموردون',products:'المنتجات',orders:'الطلبات',finance:'الأرصدة والعمولات',staff:'فريق الإدارة',delivery:'التوصيلات'})[t]}</button>)}</div>{tab==='summary'&&<div className="grid four"><Kpi label="موردين" value={users.filter(u=>u.role==='vendor').length}/><Kpi label="موردين منتظرين" value={users.filter(u=>u.role==='vendor'&&u.status==='pending').length}/><Kpi label="منتجات منتظرة" value={products.filter(p=>p.status==='pending').length}/><Kpi label="متبقي عمولات" value={egp(Object.values(finance).reduce((s,r)=>s+r.remaining,0))}/></div>}{tab==='vendors'&&<AdminVendors users={users} vendors={vendors} zones={zones} approveUser={approveUser}/>} {tab==='products'&&<AdminProducts products={products} userMap={Object.fromEntries(users.map(u=>[u.id,u]))} vendorMap={Object.fromEntries(vendors.map(v=>[v.user_id,v]))} approveProduct={approveProduct}/>} {tab==='orders'&&<OrdersTable orders={orders} orderItems={orderItems} products={products} userMap={Object.fromEntries(users.map(u=>[u.id,u]))} updateOrderStatus={updateOrderStatus}/>} {tab==='finance'&&<AdminFinance finance={finance} payments={payments} updatePaymentStatus={updatePaymentStatus}/>} {tab==='staff'&&<StaffForm addStaff={addStaff} users={users}/>} {tab==='delivery'&&<DeliveryTable users={users} vendors={vendors} zones={zones}/>}</main>}
function AdminVendors({users,vendors,zones,approveUser}){return <div className="table-wrap"><table className="table"><thead><tr><th>المورد</th><th>الحالة</th><th>الحد الأدنى</th><th>مناطق التوصيل</th><th>إجراء</th></tr></thead><tbody>{users.filter(u=>u.role==='vendor').map(u=>{const v=vendors.find(x=>x.user_id===u.id)||{}; return <tr key={u.id}><td>{v.store_name||u.name}<br/><span className="muted">{u.phone}</span></td><td>{u.status}</td><td>{egp(v.min_order)}</td><td>{zones.filter(z=>z.vendor_id===u.id).length}</td><td className="row"><button className="btn small primary" onClick={()=>approveUser(u.id,'approved')}>اعتماد</button><button className="btn small danger" onClick={()=>approveUser(u.id,'rejected')}>رفض</button></td></tr>})}</tbody></table></div>}
function AdminProducts({products,userMap,vendorMap,approveProduct}){return <div className="table-wrap"><table className="table"><thead><tr><th>المنتج</th><th>المورد</th><th>الحالة</th><th>الأسعار</th><th>إجراء</th></tr></thead><tbody>{products.map(p=><tr key={p.id}><td>{p.name_ar}</td><td>{vendorMap[p.vendor_id]?.store_name||userMap[p.vendor_id]?.name}</td><td>{p.status}</td><td>{egp(p.retail_price)} / {egp(p.wholesale_price)} / {egp(p.super_wholesale_price)}</td><td className="row"><button className="btn small primary" onClick={()=>approveProduct(p.id,'approved')}>نشر</button><button className="btn small danger" onClick={()=>approveProduct(p.id,'rejected')}>رفض</button></td></tr>)}</tbody></table></div>}
function AdminFinance({finance,payments,updatePaymentStatus}){return <div className="grid two"><div className="card" style={{gridColumn:'1/-1'}}><h3>أرصدة الموردين</h3><div className="table-wrap"><table className="table"><thead><tr><th>المورد</th><th>المبيعات</th><th>العمولة</th><th>مدفوع</th><th>متبقي</th><th>صافي المورد</th></tr></thead><tbody>{Object.values(finance).map(r=><tr key={r.vendorId}><td>{r.vendor?.store_name||r.user?.name}</td><td>{egp(r.sales)}</td><td>{egp(r.commissionDue)}</td><td>{egp(r.paid)}</td><td>{egp(r.remaining)}</td><td>{egp(r.vendorNet)}</td></tr>)}</tbody></table></div></div><div className="card" style={{gridColumn:'1/-1'}}><h3>دفعات الموردين</h3><div className="table-wrap"><table className="table"><thead><tr><th>المورد</th><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>{payments.map(p=><tr key={p.id}><td>{finance[p.vendor_id]?.vendor?.store_name||finance[p.vendor_id]?.user?.name}</td><td>{egp(p.amount)}</td><td>{p.method}<br/><span className="muted">{p.reference}</span></td><td>{p.status}</td><td className="row"><button className="btn small primary" onClick={()=>updatePaymentStatus(p.id,'approved')}>اعتماد</button><button className="btn small danger" onClick={()=>updatePaymentStatus(p.id,'rejected')}>رفض</button></td></tr>)}</tbody></table></div></div></div>}
function StaffForm({addStaff,users}){const [f,setF]=useState({name:'',phone:'',email:'',password:'',vendors:true,products:true,orders:true,finance:false,shipping:true}); return <div className="grid two"><div className="card"><h3>إضافة موظف إدارة</h3><div className="form"><Field label="الاسم"><input value={f.name} onChange={e=>setF({...f,name:e.target.value})}/></Field><Field label="الهاتف"><input value={f.phone} onChange={e=>setF({...f,phone:e.target.value})}/></Field><Field label="كلمة المرور"><input type="password" value={f.password} onChange={e=>setF({...f,password:e.target.value})}/></Field><div className="row"><label><input type="checkbox" checked={f.vendors} onChange={e=>setF({...f,vendors:e.target.checked})}/> الموردين</label><label><input type="checkbox" checked={f.products} onChange={e=>setF({...f,products:e.target.checked})}/> المنتجات</label><label><input type="checkbox" checked={f.orders} onChange={e=>setF({...f,orders:e.target.checked})}/> الطلبات</label><label><input type="checkbox" checked={f.finance} onChange={e=>setF({...f,finance:e.target.checked})}/> المالية</label></div><button className="btn primary" onClick={()=>addStaff(f)}>إضافة</button></div></div><div className="card"><h3>فريق الإدارة</h3>{users.filter(u=>u.role==='admin'||u.role==='staff').map(u=><p key={u.id}>{u.name} — {u.role} — {u.phone}</p>)}</div></div>}
function DeliveryTable({users,vendors,zones}){return <div className="table-wrap"><table className="table"><thead><tr><th>المورد</th><th>المحافظة</th><th>المركز</th><th>القسم</th><th>رسوم</th><th>مدة</th></tr></thead><tbody>{zones.map(z=><tr key={z.id}><td>{vendors.find(v=>v.user_id===z.vendor_id)?.store_name||users.find(u=>u.id===z.vendor_id)?.name}</td><td>{z.governorate}</td><td>{z.district}</td><td>{z.area}</td><td>{egp(z.delivery_fee)}</td><td>{z.eta_days} يوم</td></tr>)}</tbody></table></div>}
function ProductsAdminTable({products,showVendor=true}){return <div className="table-wrap"><table className="table"><thead><tr><th>المنتج</th><th>الحالة</th><th>الأسعار</th><th>المخزون</th></tr></thead><tbody>{products.map(p=><tr key={p.id}><td>{p.name_ar}</td><td>{p.status}</td><td>{egp(p.retail_price)} / {egp(p.wholesale_price)} / {egp(p.super_wholesale_price)}</td><td>{p.stock}</td></tr>)}</tbody></table></div>}
function OrdersTable({orders,orderItems,products=[],userMap={},vendorMode=false,updateOrderStatus}){return <div className="table-wrap"><table className="table"><thead><tr><th>رقم</th><th>الحالة</th><th>العميل</th><th>المكان</th><th>الإجمالي</th><th>المنتجات</th><th>تحديث</th></tr></thead><tbody>{orders.map(o=>{const items=orderItems.filter(i=>i.order_id===o.id); return <tr key={o.id}><td>{o.id.slice(0,8)}</td><td>{ORDER_STATUS_AR[o.status]||o.status}</td><td>{userMap[o.customer_id]?.name||'-'}</td><td>{o.governorate} / {o.district} / {o.area}</td><td>{egp(o.total)}</td><td>{items.map(i=>products.find(p=>p.id===i.product_id)?.name_ar || i.product_id).join('، ')}</td><td>{updateOrderStatus&&<select value={o.status} onChange={e=>updateOrderStatus(o.id,e.target.value)}>{ORDER_STATUS.map(s=><option key={s} value={s}>{ORDER_STATUS_AR[s]}</option>)}</select>}</td></tr>})}</tbody></table></div>}
function PaymentsTable({payments}){return <div className="table-wrap"><table className="table"><thead><tr><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th>الحالة</th></tr></thead><tbody>{payments.map(p=><tr key={p.id}><td>{egp(p.amount)}</td><td>{p.method}</td><td>{p.reference}</td><td>{p.status}</td></tr>)}</tbody></table></div>}
function HowItWorks(){return <main className="container section"><div className="grid three"><div className="card"><h3>1. العميل يحدد مكانه</h3><p>المحافظة والمركز والقسم تظهر بناءً على الموردين الذين يغطونها فقط.</p></div><div className="card"><h3>2. المورد يدير التوصيل</h3><p>كل مورد يضيف مناطق التوصيل والرسوم والمدة والحد الأدنى للطلب.</p></div><div className="card"><h3>3. الإدارة تتابع الماليات</h3><p>المبيعات، العمولة، المدفوع، المتبقي، وصافي المورد من لوحة واحدة.</p></div></div></main>}
function Kpi({label,value}){return <div className="card kpi"><b>{value}</b><span>{label}</span></div>}
function Field({label,children}){return <div className="field"><label>{label}</label>{children}</div>}
function Access(){return <main className="container section"><div className="error">ليس لديك صلاحية لهذه الصفحة.</div></main>}
