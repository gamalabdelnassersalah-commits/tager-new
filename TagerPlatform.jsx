'use client';

import { useEffect, useMemo, useState } from 'react';
import { supabase, isSupabaseConfigured } from '@/lib/supabase';
import { GOVERNORATES, districtsOf, defaultAreas } from '@/lib/egypt';
import { egp, priceForQty, qtyTier, tierLabel } from '@/lib/money';

const CATEGORIES=['مواد غذائية','مشروبات','منظفات','ورقيات','بقالة','منتجات مجمدة','أدوات منزلية','تجميل وعناية','أخرى'];
const UNITS=['قطعة','كرتونة','علبة','كيلو','لتر','دستة','باكت','شيكارة'];
const PAYMENT_METHODS=['تحويل بنكي','InstaPay','محفظة إلكترونية','فوري','نقدي عند الاستلام','بطاقة بنكية'];
const ORDER_STATUS={new:'جديد',confirmed:'مؤكد',preparing:'جاري التجهيز',shipped:'تم الشحن',delivered:'مكتمل',cancelled:'ملغي'};
const PAYMENT_STATUS={pending:'غير مدفوع',paid:'مدفوع',failed:'مرفوض',review:'تحت المراجعة'};

function asNum(v){ return Number(v||0); }
function normalizePhone(v=''){ return String(v).trim().replace(/\s+/g,'').replace(/^\+2/,'0'); }
async function sha256(text){ const data=new TextEncoder().encode(text); const hash=await crypto.subtle.digest('SHA-256',data); return Array.from(new Uint8Array(hash)).map(b=>b.toString(16).padStart(2,'0')).join(''); }
function nav(path){ if(typeof window!=='undefined') window.location.href=path; }
function uniq(arr){ return [...new Set((arr||[]).filter(Boolean))]; }
function isAdmin(u){ return u && ['admin','staff'].includes(u.role); }
function isVendor(u){ return u?.role==='vendor'; }
function isCustomer(u){ return u?.role==='customer'; }
function pct(v){ return `${Number(v||0).toFixed(2)}%`; }
function safeJson(v,d=[]){ try{return Array.isArray(v)?v:JSON.parse(v||'[]')}catch{return d} }

async function imageDimensions(file){
  if(!file) return {width:0,height:0};
  return new Promise((resolve,reject)=>{
    const img=new Image();
    img.onload=()=>resolve({width:img.width,height:img.height});
    img.onerror=reject;
    img.src=URL.createObjectURL(file);
  });
}

export default function TagerPlatform({ view='home' }){
  const [loading,setLoading]=useState(false);
  const [error,setError]=useState('');
  const [message,setMessage]=useState('');
  const [currentUser,setCurrentUser]=useState(null);
  const [users,setUsers]=useState([]);
  const [vendors,setVendors]=useState([]);
  const [zones,setZones]=useState([]);
  const [products,setProducts]=useState([]);
  const [orders,setOrders]=useState([]);
  const [items,setItems]=useState([]);
  const [payments,setPayments]=useState([]);
  const [shipments,setShipments]=useState([]);
  const [addresses,setAddresses]=useState([]);
  const [invoices,setInvoices]=useState([]);
  const [cart,setCart]=useState([]);
  const [filters,setFilters]=useState({q:'',tier:'all',category:'all',governorate:'',district:'',area:''});
  const [tab,setTab]=useState('overview');

  useEffect(()=>{
    if(typeof window==='undefined') return;
    const user=localStorage.getItem('tager_current_user');
    const c=localStorage.getItem('tager_cart');
    if(user) setCurrentUser(JSON.parse(user));
    if(c) setCart(JSON.parse(c));
    refreshAll();
  },[]);
  useEffect(()=>{ if(typeof window!=='undefined') localStorage.setItem('tager_cart', JSON.stringify(cart)); },[cart]);

  async function refreshAll(){
    if(!isSupabaseConfigured || !supabase){ setError('لم يتم ربط Supabase بعد. أضف مفاتيح الربط في Vercel ثم أعد النشر.'); return; }
    setLoading(true); setError('');
    try{
      const calls = await Promise.all([
        supabase.from('users').select('*').order('created_at',{ascending:false}),
        supabase.from('vendors').select('*'),
        supabase.from('vendor_delivery_zones').select('*').order('created_at',{ascending:false}),
        supabase.from('products').select('*').order('created_at',{ascending:false}),
        supabase.from('orders').select('*').order('created_at',{ascending:false}),
        supabase.from('order_items').select('*'),
        supabase.from('commission_payments').select('*').order('created_at',{ascending:false}),
        supabase.from('shipments').select('*').order('created_at',{ascending:false}),
        supabase.from('customer_addresses').select('*').order('created_at',{ascending:false}),
        supabase.from('invoices').select('*').order('created_at',{ascending:false})
      ]);
      const err=calls.find(c=>c.error);
      if(err) throw err.error;
      setUsers(calls[0].data||[]); setVendors(calls[1].data||[]); setZones(calls[2].data||[]); setProducts(calls[3].data||[]); setOrders(calls[4].data||[]); setItems(calls[5].data||[]); setPayments(calls[6].data||[]); setShipments(calls[7].data||[]); setAddresses(calls[8].data||[]); setInvoices(calls[9].data||[]);
    }catch(e){ setError(e.message || 'خطأ في تحميل البيانات'); }
    setLoading(false);
  }

  const userMap=useMemo(()=>Object.fromEntries(users.map(u=>[u.id,u])),[users]);
  const vendorMap=useMemo(()=>Object.fromEntries(vendors.map(v=>[v.user_id,v])),[vendors]);
  const approvedVendorIds=useMemo(()=>users.filter(u=>u.role==='vendor' && u.status==='approved').map(u=>u.id),[users]);
  const approvedProducts=useMemo(()=>products.filter(p=>p.status==='approved' && approvedVendorIds.includes(p.vendor_id)),[products,approvedVendorIds]);
  const activeZones=useMemo(()=>zones.filter(z=>z.is_active!==false && approvedVendorIds.includes(z.vendor_id)),[zones,approvedVendorIds]);
  const filterGovs=useMemo(()=>uniq(activeZones.map(z=>z.governorate)),[activeZones]);
  const filterDistricts=useMemo(()=>uniq(activeZones.filter(z=>!filters.governorate || z.governorate===filters.governorate).map(z=>z.district)),[activeZones,filters.governorate]);
  const filterAreas=useMemo(()=>uniq(activeZones.filter(z=>(!filters.governorate || z.governorate===filters.governorate)&&(!filters.district || z.district===filters.district)).map(z=>z.area)),[activeZones,filters.governorate,filters.district]);

  function flash(m){ setMessage(m); setError(''); setTimeout(()=>setMessage(''),3500); }
  function fail(m){ setError(m); setMessage(''); }
  function vendorCovers(vendorId, gov, dist, area){
    if(!gov) return true;
    return zones.some(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && (!dist || z.district===dist) && (!area || z.area===area || z.area==='كل المناطق'));
  }
  function bestZone(vendorId, gov, dist, area){
    return zones.find(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && z.district===dist && z.area===area)
      || zones.find(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && z.district===dist && z.area==='كل المناطق')
      || zones.find(z=>z.vendor_id===vendorId && z.is_active!==false && z.governorate===gov && z.district===dist) || null;
  }
  function filteredProducts(){
    return approvedProducts.filter(p=>{
      const q=filters.q.trim().toLowerCase();
      const blob=[p.name_ar,p.name_en,p.sku,p.brand,p.category,vendorMap[p.vendor_id]?.store_name,userMap[p.vendor_id]?.name].filter(Boolean).join(' ').toLowerCase();
      return (!q || blob.includes(q)) && (filters.category==='all' || p.category===filters.category) && vendorCovers(p.vendor_id,filters.governorate,filters.district,filters.area);
    });
  }
  async function uploadImage(file,bucket,folder){
    if(!file) return '';
    if(!/^image\/(png|jpeg|webp)$/.test(file.type)) throw new Error('الصورة يجب أن تكون JPG أو PNG أو WebP');
    if(file.size>5*1024*1024) throw new Error('حجم الصورة أكبر من 5 ميجابايت');
    const dim=await imageDimensions(file);
    if(dim.width<600 || dim.height<600) throw new Error('الصورة غير واضحة. أقل مقاس 600×600');
    const ext=file.name.split('.').pop();
    const path=`${folder}/${Date.now()}-${Math.random().toString(16).slice(2)}.${ext}`;
    const {error:upErr}=await supabase.storage.from(bucket).upload(path,file,{upsert:false});
    if(upErr) throw upErr;
    const {data}=supabase.storage.from(bucket).getPublicUrl(path);
    return data.publicUrl;
  }

  async function createInitialAdmin(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const name=fd.get('name'), phone=normalizePhone(fd.get('phone')), password=fd.get('password'), email=fd.get('email')||null;
      if(!name || !/^0\d{10}$/.test(phone) || String(password).length<8) throw new Error('أدخل الاسم ورقم هاتف مصري وكلمة مرور 8 أحرف على الأقل');
      const count = await supabase.from('users').select('id',{count:'exact',head:true}).in('role',['admin','staff']);
      if(count.count>0) throw new Error('تم إنشاء حساب إدارة من قبل. استخدم تسجيل الدخول.');
      const password_hash=await sha256(`${phone}:${password}`);
      const {error}=await supabase.from('users').insert({role:'admin',status:'approved',name,phone,email,password_hash,permissions:{all:true}});
      if(error) throw error;
      flash('تم إنشاء حساب الإدارة. يمكنك تسجيل الدخول الآن.'); refreshAll(); nav('/login');
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function login(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const ident=String(fd.get('identifier')||'').trim(); const password=String(fd.get('password')||'');
      const phone=normalizePhone(ident); const isPhone=/^0\d{10}$/.test(phone);
      const {data:user,error:err}=await supabase.from('users').select('*').or(isPhone?`phone.eq.${phone}`:`email.eq.${ident}`).limit(1).maybeSingle();
      if(err) throw err; if(!user) throw new Error('الحساب غير موجود');
      const hash=await sha256(`${user.phone}:${password}`);
      if(hash!==user.password_hash) throw new Error('كلمة المرور غير صحيحة');
      if(['suspended','rejected'].includes(user.status)) throw new Error('الحساب غير مفعل');
      setCurrentUser(user); localStorage.setItem('tager_current_user', JSON.stringify(user));
      if(isAdmin(user)) nav('/admin'); else if(isVendor(user)) nav('/vendor'); else nav('/customer');
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  function logout(){ setCurrentUser(null); localStorage.removeItem('tager_current_user'); nav('/'); }

  async function registerCustomer(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const phone=normalizePhone(fd.get('phone')); const password=String(fd.get('password')||'');
      if(!fd.get('name') || !/^0\d{10}$/.test(phone) || password.length<8) throw new Error('الاسم ورقم الهاتف وكلمة المرور مطلوبة');
      const password_hash=await sha256(`${phone}:${password}`);
      const payload={role:'customer',status:'approved',name:fd.get('name'),phone,email:fd.get('email')||null,password_hash,governorate:fd.get('governorate')||null,district:fd.get('district')||null,area:fd.get('area')||null,address:fd.get('address')||null};
      const {data,error}=await supabase.from('users').insert(payload).select('*').single(); if(error) throw error;
      if(payload.governorate && payload.district && payload.area && payload.address) await supabase.from('customer_addresses').insert({customer_id:data.id,label:'العنوان الرئيسي',governorate:payload.governorate,district:payload.district,area:payload.area,address:payload.address,is_default:true});
      setCurrentUser(data); localStorage.setItem('tager_current_user',JSON.stringify(data)); refreshAll(); nav('/customer');
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function registerVendor(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const phone=normalizePhone(fd.get('phone')); const password=String(fd.get('password')||'');
      if(!fd.get('name') || !fd.get('store_name') || !/^0\d{10}$/.test(phone) || password.length<8) throw new Error('الاسم واسم المتجر ورقم الهاتف وكلمة المرور مطلوبة');
      const logo=await uploadImage(fd.get('logo'), 'vendor-images', 'logos').catch(()=> '');
      const password_hash=await sha256(`${phone}:${password}`);
      const {data:user,error}=await supabase.from('users').insert({role:'vendor',status:'pending',name:fd.get('name'),phone,email:fd.get('email')||null,password_hash,governorate:fd.get('governorate')||null,district:fd.get('district')||null,area:fd.get('area')||null,address:fd.get('address')||null}).select('*').single();
      if(error) throw error;
      const vendorPayload={user_id:user.id,store_name:fd.get('store_name'),commercial_register:fd.get('commercial_register')||null,tax_number:fd.get('tax_number')||null,governorate:fd.get('governorate')||null,district:fd.get('district')||null,area:fd.get('area')||null,description:fd.get('description')||'',min_order:asNum(fd.get('min_order')),commission_percent:10,premium_cart_percent:1.5,logo_url:logo,bank_name:fd.get('bank_name')||null,iban:fd.get('iban')||null,wallet_number:fd.get('wallet_number')||null,instapay_handle:fd.get('instapay_handle')||null};
      await supabase.from('vendors').insert(vendorPayload);
      flash('تم تسجيل المورد وهو الآن تحت مراجعة الإدارة'); refreshAll(); nav('/login');
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function approveUser(userId,status){
    const {error}=await supabase.from('users').update({status}).eq('id',userId); if(error) return fail(error.message); flash('تم تحديث حالة الحساب'); refreshAll();
  }
  async function approveProduct(productId,status){
    const {error}=await supabase.from('products').update({status}).eq('id',productId); if(error) return fail(error.message); flash('تم تحديث حالة المنتج'); refreshAll();
  }
  async function saveVendorSettings(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const v=vendorMap[currentUser.id];
      let logo=v?.logo_url || ''; let cover=v?.cover_url || '';
      if(fd.get('logo')?.size) logo=await uploadImage(fd.get('logo'),'vendor-images','logos');
      if(fd.get('cover')?.size) cover=await uploadImage(fd.get('cover'),'vendor-images','covers');
      const payload={store_name:fd.get('store_name'),governorate:fd.get('governorate'),district:fd.get('district'),area:fd.get('area'),description:fd.get('description'),min_order:asNum(fd.get('min_order')),logo_url:logo,cover_url:cover,bank_name:fd.get('bank_name')||null,iban:fd.get('iban')||null,wallet_number:fd.get('wallet_number')||null,instapay_handle:fd.get('instapay_handle')||null};
      const {error}=await supabase.from('vendors').update(payload).eq('user_id',currentUser.id); if(error) throw error;
      flash('تم حفظ بيانات المورد'); refreshAll();
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function addZone(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const {error}=await supabase.from('vendor_delivery_zones').upsert({vendor_id:currentUser.id,governorate:fd.get('governorate'),district:fd.get('district'),area:fd.get('area')||'كل المناطق',delivery_fee:asNum(fd.get('delivery_fee')),eta_days:asNum(fd.get('eta_days'))||2,is_active:true},{onConflict:'vendor_id,governorate,district,area'});
      if(error) throw error; e.currentTarget.reset(); flash('تم إضافة منطقة التوصيل'); refreshAll();
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function saveProduct(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const retail=asNum(fd.get('retail_price')), wholesale=asNum(fd.get('wholesale_price')), superp=asNum(fd.get('super_wholesale_price'));
      const wMin=asNum(fd.get('wholesale_min')), sMin=asNum(fd.get('super_wholesale_min')), stock=asNum(fd.get('stock'));
      if(!fd.get('name_ar') || !retail || !wholesale || !superp || stock<0) throw new Error('أدخل بيانات المنتج والأسعار والمخزون');
      if(!(retail>=wholesale && wholesale>=superp)) throw new Error('سعر القطاعي يجب أن يكون أكبر أو يساوي الجملة، والجملة أكبر أو يساوي جملة الجملة');
      if(!(sMin>wMin)) throw new Error('حد جملة الجملة يجب أن يكون أكبر من حد الجملة');
      const image=fd.get('image')?.size ? await uploadImage(fd.get('image'),'product-images','products') : '';
      const payload={vendor_id:currentUser.id,status:'pending',name_ar:fd.get('name_ar'),name_en:fd.get('name_en')||null,sku:fd.get('sku')||null,category:fd.get('category'),brand:fd.get('brand')||null,unit:fd.get('unit')||'قطعة',description_ar:fd.get('description_ar')||'',short_description:fd.get('short_description')||'',retail_price:retail,wholesale_price:wholesale,super_wholesale_price:superp,wholesale_min:wMin,super_wholesale_min:sMin,stock:stock,max_qty:asNum(fd.get('max_qty'))||999,lead_time_days:asNum(fd.get('lead_time_days'))||0,image_url:image};
      const {error}=await supabase.from('products').insert(payload); if(error) throw error;
      e.currentTarget.reset(); flash('تم حفظ المنتج وإرساله للمراجعة'); refreshAll();
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  function addToCart(product, qty){
    const q=Math.max(1,Number(qty||1));
    setCart(prev=>{
      const found=prev.find(x=>x.product_id===product.id);
      if(found) return prev.map(x=>x.product_id===product.id?{...x,qty:Math.min(product.max_qty,x.qty+q)}:x);
      return [...prev,{product_id:product.id,qty:Math.min(product.max_qty,q)}];
    }); flash('تمت الإضافة إلى السلة');
  }
  function cartRows(){ return cart.map(c=>({cart:c,product:products.find(p=>p.id===c.product_id)})).filter(x=>x.product); }
  function cartTotals(location, type='separate'){
    const rows=cartRows(); let subtotal=0, commission=0, vendorNet=0, shipping=0, premium=0; const vendorSub={}; const errors=[];
    rows.forEach(({cart,product})=>{ const unit=priceForQty(product,cart.qty); const sub=unit*cart.qty; subtotal+=sub; vendorSub[product.vendor_id]=(vendorSub[product.vendor_id]||0)+sub; });
    Object.entries(vendorSub).forEach(([vendorId,sub])=>{
      const v=vendorMap[vendorId]||{}; const feeZone=bestZone(vendorId,location.governorate,location.district,location.area);
      if(type==='separate'){
        if(!feeZone) errors.push(`المورد ${v.store_name||userMap[vendorId]?.name||''} لا يغطي منطقة التوصيل`);
        if(sub<asNum(v.min_order)) errors.push(`طلب ${v.store_name||''} أقل من الحد الأدنى ${egp(v.min_order)}`);
      }
      shipping += feeZone ? asNum(feeZone.delivery_fee) : (type==='premium' ? 0 : 0);
      premium += type==='premium' ? sub * (asNum(v.premium_cart_percent)||1.5) / 100 : 0;
      commission += sub * (asNum(v.commission_percent)||10) / 100;
      vendorNet += sub - (sub * (asNum(v.commission_percent)||10) / 100);
    });
    return {subtotal,shipping,premium,total:subtotal+shipping+premium,commission,vendorNet,errors};
  }
  async function createOrder(e){
    e.preventDefault(); if(!currentUser) return nav('/login'); if(!isCustomer(currentUser)) return fail('الطلبات متاحة للعميل فقط');
    const fd=new FormData(e.currentTarget); const location={governorate:fd.get('governorate'),district:fd.get('district'),area:fd.get('area')||'كل المناطق',address:fd.get('address')}; const type=fd.get('cart_type')||'separate';
    const totals=cartTotals(location,type); if(cart.length===0) return fail('السلة فارغة'); if(totals.errors.length && type==='separate') return fail(totals.errors.join(' - '));
    setLoading(true);
    try{
      const {data:order,error:oe}=await supabase.from('orders').insert({customer_id:currentUser.id,cart_type:type,governorate:location.governorate,district:location.district,area:location.area,address:location.address,shipping_fee:totals.shipping,premium_fee:totals.premium,payment_method:fd.get('payment_method'),payment_status:'pending',total:totals.total,platform_commission:totals.commission,vendor_net:totals.vendorNet,status:'new',delivery_status:type==='premium'?'premium_review':'pending'}).select('*').single();
      if(oe) throw oe;
      const rows=cartRows();
      for(const {cart:c,product} of rows){
        const unit=priceForQty(product,c.qty); const sub=unit*c.qty; const v=vendorMap[product.vendor_id]||{}; const cp=asNum(v.commission_percent)||10; const ca=sub*cp/100;
        await supabase.from('order_items').insert({order_id:order.id,product_id:product.id,vendor_id:product.vendor_id,qty:c.qty,unit_price:unit,subtotal:sub,commission_percent:cp,commission_amount:ca,vendor_net:sub-ca});
        const zone=bestZone(product.vendor_id,location.governorate,location.district,location.area);
        await supabase.from('shipments').insert({order_id:order.id,vendor_id:product.vendor_id,status:type==='premium' && !zone?'platform_special': 'pending',governorate:location.governorate,district:location.district,area:location.area,delivery_fee:zone?asNum(zone.delivery_fee):0,eta_days:zone?asNum(zone.eta_days):3,tracking_notes:type==='premium' && !zone?'توصيل مميز بواسطة المنصة':'تم إنشاء الشحنة'});
      }
      await supabase.from('invoices').insert({order_id:order.id,customer_id:currentUser.id,invoice_type:'customer_order',amount:totals.total,status:'open',notes:'فاتورة طلب عميل'});
      setCart([]); flash('تم إنشاء الطلب بنجاح'); refreshAll(); nav('/customer');
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function payCommission(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const {error}=await supabase.from('commission_payments').insert({vendor_id:currentUser.id,amount:asNum(fd.get('amount')),method:fd.get('method'),reference:fd.get('reference')||null,notes:fd.get('notes')||null,status:'pending'}); if(error) throw error;
      e.currentTarget.reset(); flash('تم تسجيل الدفعة وهي تحت مراجعة الإدارة'); refreshAll();
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }
  async function updatePayment(id,status){ const {error}=await supabase.from('commission_payments').update({status}).eq('id',id); if(error) return fail(error.message); flash('تم تحديث الدفعة'); refreshAll(); }
  async function updateOrderStatus(id,status){ const {error}=await supabase.from('orders').update({status}).eq('id',id); if(error) return fail(error.message); flash('تم تحديث الطلب'); refreshAll(); }
  async function updateShipment(id,status){ const {error}=await supabase.from('shipments').update({status,delivered_at:status==='delivered'?new Date().toISOString():null}).eq('id',id); if(error) return fail(error.message); flash('تم تحديث التوصيل'); refreshAll(); }
  async function updateVendorFinance(e,vendorId){
    e.preventDefault(); const fd=new FormData(e.currentTarget); const {error}=await supabase.from('vendors').update({commission_percent:asNum(fd.get('commission_percent')),premium_cart_percent:asNum(fd.get('premium_cart_percent')),min_order:asNum(fd.get('min_order'))}).eq('user_id',vendorId);
    if(error) return fail(error.message); flash('تم حفظ نسب المورد'); refreshAll();
  }
  async function addStaff(e){
    e.preventDefault(); const fd=new FormData(e.currentTarget); setLoading(true);
    try{
      const phone=normalizePhone(fd.get('phone')); const password=String(fd.get('password')||''); if(!fd.get('name')||!/^0\d{10}$/.test(phone)||password.length<8) throw new Error('أدخل بيانات الموظف');
      const password_hash=await sha256(`${phone}:${password}`); const permissions={finance:!!fd.get('finance'),vendors:!!fd.get('vendors'),products:!!fd.get('products'),orders:!!fd.get('orders'),delivery:!!fd.get('delivery')};
      const {error}=await supabase.from('users').insert({role:'staff',status:'approved',name:fd.get('name'),phone,email:fd.get('email')||null,password_hash,permissions}); if(error) throw error; flash('تم إضافة موظف إدارة'); refreshAll();
    }catch(e2){ fail(e2.message); }
    setLoading(false);
  }

  function Header(){
    return <header className="top"><div className="container nav"><div className="brand"><img src="/tager-logo.png" alt="Tager"/><div><div>منصة تاجر</div><div className="small" style={{color:'#dceee4'}}>قطاعي | جملة | جملة الجملة</div></div></div><nav className="menu"><a href="/">الرئيسية</a><a href="/market">السوق</a><a href="/vendors">الموردون</a><a href="/cart">السلة ({cart.length})</a>{currentUser? <><button onClick={()=>nav(isAdmin(currentUser)?'/admin':isVendor(currentUser)?'/vendor':'/customer')}>حسابي</button><button onClick={logout}>خروج</button></>:<><a href="/login">دخول</a><a href="/register/customer">عميل</a><a href="/register/vendor">مورد</a></>}</nav></div></header>
  }
  function Status(){ return <>{message&&<div className="container"><div className="alert">{message}</div></div>}{error&&<div className="container"><div className="alert err">{error}</div></div>}{loading&&<div className="container"><div className="alert">جاري التنفيذ...</div></div>}</> }
  function SelectGov({name='governorate', defaultValue='', required=true}){ return <select name={name} className="select" defaultValue={defaultValue} required={required}><option value="">اختر المحافظة</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select> }
  function LocationFields({prefix='', required=true}){
    const [gov,setGov]=useState(''); const [dist,setDist]=useState('');
    return <><div><label>المحافظة</label><select name={`${prefix}governorate`} className="select" value={gov} onChange={e=>{setGov(e.target.value);setDist('')}} required={required}><option value="">اختر المحافظة</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select></div><div><label>المركز / المدينة</label><select name={`${prefix}district`} className="select" value={dist} onChange={e=>setDist(e.target.value)} required={required}><option value="">اختر المركز</option>{districtsOf(gov).map(d=><option key={d}>{d}</option>)}</select></div><div><label>القسم / الحي</label><input name={`${prefix}area`} className="input" placeholder="مثال: كل المناطق / الحي العاشر" required={required}/></div></>
  }

  function Home(){
    return <><section className="hero"><div className="container heroGrid"><div><h1>منصة تاجر لإدارة البيع القطاعي والجملة وجملة الجملة</h1><p>منصة تربط العملاء بالموردين حسب المحافظات والمراكز والأقسام، مع سلة منفصلة أو سلة مميزة، وتتبع مالي كامل لعمولات الموردين.</p><div className="row"><button className="btn gold" onClick={()=>nav('/market')}>ابدأ التسوق</button><button className="btn secondary" onClick={()=>nav('/register/vendor')}>انضم كمورد</button><button className="btn secondary" onClick={()=>nav('/setup')}>إعداد الإدارة</button></div></div><div className="heroCard"><div className="kpis"><div className="kpi"><b>{users.filter(u=>u.role==='vendor'&&u.status==='approved').length}</b>مورد</div><div className="kpi"><b>{approvedProducts.length}</b>منتج</div><div className="kpi"><b>{orders.length}</b>طلب</div></div><hr style={{borderColor:'rgba(255,255,255,.25)'}}/><p>لا يوجد بيانات تجريبية. ابدأ بإنشاء حساب الإدارة ثم اعتماد الموردين الحقيقيين.</p></div></div></section><section className="container section"><div className="grid"><div className="card"><h3>توصيل حسب المورد</h3><p>كل مورد يحدد المحافظات والمراكز والأقسام التي يغطيها، والعميل لا يكمل الطلب إذا كان المكان غير مغطى في السلة العادية.</p></div><div className="card"><h3>سلة مميزة</h3><p>تجميع أصناف من عدة موردين برسوم قابلة للتعديل لكل مورد، مع تتبع خاص للتوصيل.</p></div><div className="card"><h3>قوائم مالية</h3><p>عمولة المنصة، المدفوع، المتبقي، صافي المورد، ودفعات المورد تحت مراجعة الإدارة.</p></div></div><ProductsPreview /></section></>
  }
  function ProductsPreview(){ const list=approvedProducts.slice(0,6); return <div className="card"><div className="between"><h2>أحدث المنتجات</h2><button className="btn secondary" onClick={()=>nav('/market')}>فتح السوق</button></div>{list.length? <div className="grid">{list.map(p=><ProductCard key={p.id} p={p}/>)}</div>:<div className="empty">لا توجد منتجات منشورة حتى الآن.</div>}</div> }
  function ProductCard({p}){
    const [q,setQ]=useState(1); const v=vendorMap[p.vendor_id]||{};
    return <div className="card product"><img src={p.image_url||'/tager-logo.png'} alt={p.name_ar}/><div className="between"><b>{p.name_ar}</b><span className="badge">{p.category}</span></div><div className="small">المورد: {v.store_name||userMap[p.vendor_id]?.name}</div><div className="priceLine"><div className="priceBox">قطاعي<br/><b>{egp(p.retail_price)}</b></div><div className="priceBox">جملة من {p.wholesale_min}<br/><b>{egp(p.wholesale_price)}</b></div><div className="priceBox">جملة الجملة من {p.super_wholesale_min}<br/><b>{egp(p.super_wholesale_price)}</b></div></div><div className="row"><input className="input" style={{maxWidth:100}} type="number" min="1" max={p.max_qty} value={q} onChange={e=>setQ(e.target.value)}/><span className="badge warn">{tierLabel(qtyTier(p,q))}</span></div><button className="btn" onClick={()=>addToCart(p,q)}>إضافة للسلة - {egp(priceForQty(p,q)*q)}</button></div>
  }
  function Setup(){
    const adminExists=users.some(u=>['admin','staff'].includes(u.role));
    return <Main title="إعداد أول حساب إدارة"><div className="card">{adminExists?<><h3>تم إنشاء حساب إدارة بالفعل</h3><p>استخدم صفحة تسجيل الدخول، أو أضف موظفي إدارة من بوابة الإدارة.</p><button className="btn" onClick={()=>nav('/login')}>تسجيل الدخول</button></>:<form onSubmit={createInitialAdmin} className="formGrid"><div><label>اسم المدير</label><input name="name" className="input" required/></div><div><label>رقم الهاتف</label><input name="phone" className="input" placeholder="01000000000" required/></div><div><label>البريد اختياري</label><input name="email" type="email" className="input"/></div><div><label>كلمة المرور</label><input name="password" type="password" className="input" minLength="8" required/></div><div><button className="btn">إنشاء الإدارة</button></div></form>}</div></Main>
  }
  function Login(){ return <Main title="تسجيل الدخول"><div className="card"><form onSubmit={login} className="formGrid"><div><label>رقم الهاتف أو البريد</label><input name="identifier" className="input" required/></div><div><label>كلمة المرور</label><input name="password" type="password" className="input" required/></div><button className="btn">دخول</button><button type="button" className="btn secondary" onClick={()=>nav('/register/customer')}>تسجيل عميل</button><button type="button" className="btn secondary" onClick={()=>nav('/register/vendor')}>تسجيل مورد</button></form></div></Main> }
  function CustomerRegister(){ return <Main title="تسجيل عميل"><div className="card"><form onSubmit={registerCustomer} className="formGrid"><div><label>الاسم</label><input name="name" className="input" required/></div><div><label>رقم الهاتف</label><input name="phone" className="input" required/></div><div><label>البريد اختياري</label><input name="email" type="email" className="input"/></div><div><label>كلمة المرور</label><input name="password" type="password" minLength="8" className="input" required/></div><LocationFields required={false}/><div style={{gridColumn:'1/-1'}}><label>العنوان التفصيلي</label><textarea name="address" className="input"/></div><button className="btn">إنشاء حساب العميل</button></form></div></Main> }
  function VendorRegister(){ return <Main title="تسجيل مورد"><div className="card"><form onSubmit={registerVendor} className="formGrid"><div><label>اسم المسؤول</label><input name="name" className="input" required/></div><div><label>اسم المتجر</label><input name="store_name" className="input" required/></div><div><label>رقم الهاتف</label><input name="phone" className="input" required/></div><div><label>البريد اختياري</label><input name="email" type="email" className="input"/></div><div><label>كلمة المرور</label><input name="password" type="password" minLength="8" className="input" required/></div><div><label>السجل التجاري</label><input name="commercial_register" className="input"/></div><div><label>الرقم الضريبي</label><input name="tax_number" className="input"/></div><div><label>الحد الأدنى للطلب</label><input name="min_order" type="number" className="input" defaultValue="0"/></div><LocationFields required={false}/><div><label>شعار المتجر 600×600</label><input name="logo" type="file" accept="image/png,image/jpeg,image/webp" className="input"/></div><div><label>اسم البنك</label><input name="bank_name" className="input"/></div><div><label>IBAN أو رقم الحساب</label><input name="iban" className="input"/></div><div><label>رقم المحفظة</label><input name="wallet_number" className="input"/></div><div><label>InstaPay</label><input name="instapay_handle" className="input"/></div><div style={{gridColumn:'1/-1'}}><label>وصف النشاط</label><textarea name="description" className="input"/></div><button className="btn">إرسال طلب المورد للمراجعة</button></form></div></Main> }
  function Market(){
    const list=filteredProducts();
    return <Main title="السوق"><SearchFilters/><div className="grid">{list.map(p=><ProductCard key={p.id} p={p}/>)}</div>{!list.length&&<div className="empty">لا توجد منتجات مطابقة. غيّر المحافظة أو المركز أو نوع البحث.</div>}</Main>
  }
  function SearchFilters(){ return <div className="card"><div className="formGrid"><div><label>بحث</label><input className="input" value={filters.q} onChange={e=>setFilters({...filters,q:e.target.value})} placeholder="اسم المنتج أو المورد"/></div><div><label>نوع السعر</label><select className="select" value={filters.tier} onChange={e=>setFilters({...filters,tier:e.target.value})}><option value="all">الكل</option><option value="retail">قطاعي</option><option value="wholesale">جملة</option><option value="super">جملة الجملة</option></select></div><div><label>القسم</label><select className="select" value={filters.category} onChange={e=>setFilters({...filters,category:e.target.value})}><option value="all">كل الأقسام</option>{CATEGORIES.map(c=><option key={c}>{c}</option>)}</select></div><div><label>المحافظة حسب الموردين</label><select className="select" value={filters.governorate} onChange={e=>setFilters({...filters,governorate:e.target.value,district:'',area:''})}><option value="">كل المحافظات</option>{filterGovs.map(g=><option key={g}>{g}</option>)}</select></div><div><label>المركز</label><select className="select" value={filters.district} onChange={e=>setFilters({...filters,district:e.target.value,area:''})}><option value="">كل المراكز</option>{filterDistricts.map(d=><option key={d}>{d}</option>)}</select></div><div><label>القسم / الحي</label><select className="select" value={filters.area} onChange={e=>setFilters({...filters,area:e.target.value})}><option value="">كل الأقسام</option>{filterAreas.map(a=><option key={a}>{a}</option>)}</select></div></div></div> }
  function VendorsPage(){
    const list=vendors.filter(v=>approvedVendorIds.includes(v.user_id));
    return <Main title="دليل الموردين"><SearchFilters/><div className="grid">{list.filter(v=>(!filters.governorate||zones.some(z=>z.vendor_id===v.user_id&&z.governorate===filters.governorate))).map(v=><div className="card" key={v.user_id}><div className="vendorCover" style={{backgroundImage:`url(${v.cover_url||v.logo_url||'/tager-logo.png'})`,backgroundSize:'cover'}}></div><div className="row" style={{marginTop:-35}}><img className="vendorLogo" src={v.logo_url||'/tager-logo.png'} alt={v.store_name}/><div><h3>{v.store_name}</h3><div className="small">{v.governorate} - {v.district} - {v.area}</div></div></div><p>{v.description||'مورد معتمد على منصة تاجر'}</p><div className="row"><span className="badge">حد أدنى {egp(v.min_order)}</span><span className="badge">عمولة {pct(v.commission_percent)}</span><span className="badge">منتجات {products.filter(p=>p.vendor_id===v.user_id&&p.status==='approved').length}</span></div><h4>أماكن التوصيل</h4>{zones.filter(z=>z.vendor_id===v.user_id).slice(0,6).map(z=><span key={z.id} className="badge warn" style={{margin:3}}>{z.governorate} / {z.district} / {z.area}</span>)}</div>)}</div>{!list.length&&<div className="empty">لا يوجد موردون معتمدون حتى الآن.</div>}</Main>
  }
  function CartPage(){
    const [loc,setLoc]=useState({governorate:'',district:'',area:'',address:''}); const [type,setType]=useState('separate');
    const totals=cartTotals(loc,type); const rows=cartRows();
    return <Main title="السلة وإتمام الطلب"><div className="grid2"><div className="card"><h3>المنتجات</h3>{rows.map(({cart:c,product:p})=><div key={p.id} className="between" style={{borderBottom:'1px solid #eee',padding:'10px 0'}}><div><b>{p.name_ar}</b><div className="small">{vendorMap[p.vendor_id]?.store_name} - {tierLabel(qtyTier(p,c.qty))}</div></div><div>{c.qty} × {egp(priceForQty(p,c.qty))}</div><button className="btn danger" onClick={()=>setCart(cart.filter(x=>x.product_id!==p.id))}>حذف</button></div>)}{!rows.length&&<div className="empty">السلة فارغة</div>}</div><div className="card"><form onSubmit={createOrder}><label>نوع السلة</label><div className="grid2"><label className="card"><input type="radio" name="cart_type" value="separate" checked={type==='separate'} onChange={()=>setType('separate')}/> سلة الموردين المنفصلة<br/><span className="small">تطبق الحد الأدنى وتغطية التوصيل لكل مورد</span></label><label className="card"><input type="radio" name="cart_type" value="premium" checked={type==='premium'} onChange={()=>setType('premium')}/> السلة المميزة<br/><span className="small">تجمع الموردين برسوم مميزة حسب كل مورد</span></label></div><div className="formGrid"><div><label>المحافظة</label><select name="governorate" className="select" value={loc.governorate} onChange={e=>setLoc({...loc,governorate:e.target.value,district:'',area:''})} required><option value="">اختر</option>{GOVERNORATES.map(g=><option key={g}>{g}</option>)}</select></div><div><label>المركز</label><select name="district" className="select" value={loc.district} onChange={e=>setLoc({...loc,district:e.target.value,area:''})} required><option value="">اختر</option>{districtsOf(loc.governorate).map(d=><option key={d}>{d}</option>)}</select></div><div><label>القسم/الحي</label><input name="area" className="input" value={loc.area} onChange={e=>setLoc({...loc,area:e.target.value})} required/></div><div><label>طريقة الدفع</label><select name="payment_method" className="select" required>{PAYMENT_METHODS.map(m=><option key={m}>{m}</option>)}</select></div><div style={{gridColumn:'1/-1'}}><label>العنوان التفصيلي</label><textarea name="address" className="input" value={loc.address} onChange={e=>setLoc({...loc,address:e.target.value})} required/></div></div>{totals.errors.length&&type==='separate'&&<div className="alert err">{totals.errors.join(' - ')}</div>}<div className="card"><b>ملخص الطلب</b><p>المنتجات: {egp(totals.subtotal)}</p><p>الشحن: {egp(totals.shipping)}</p><p>رسوم السلة المميزة: {egp(totals.premium)}</p><h3>الإجمالي: {egp(totals.total)}</h3></div><button className="btn" disabled={!rows.length}>تأكيد الطلب</button></form></div></div></Main>
  }
  function financeForVendor(vendorId){
    const its=items.filter(i=>i.vendor_id===vendorId); const gross=its.reduce((s,i)=>s+asNum(i.subtotal),0); const comm=its.reduce((s,i)=>s+asNum(i.commission_amount),0); const approvedPay=payments.filter(p=>p.vendor_id===vendorId&&p.status==='approved').reduce((s,p)=>s+asNum(p.amount),0); const pendingPay=payments.filter(p=>p.vendor_id===vendorId&&p.status==='pending').reduce((s,p)=>s+asNum(p.amount),0); return {gross,comm,approvedPay,pendingPay,remaining:Math.max(0,comm-approvedPay),net:gross-comm};
  }
  function CustomerDashboard(){ if(!currentUser) return <Login/>; if(!isCustomer(currentUser)) return <Forbidden/>; const myOrders=orders.filter(o=>o.customer_id===currentUser.id); return <Workspace title="حساب العميل" tabs={[['overview','الملخص'],['orders','طلباتي'],['addresses','عناويني']]}>{tab==='overview'&&<div className="grid"><div className="card"><h3>طلباتي</h3><b>{myOrders.length}</b></div><div className="card"><h3>إجمالي مشترياتي</h3><b>{egp(myOrders.reduce((s,o)=>s+asNum(o.total),0))}</b></div><div className="card"><h3>بياناتي</h3><p>{currentUser.name}<br/>{currentUser.phone}</p></div></div>}{tab==='orders'&&<OrdersTable list={myOrders}/>} {tab==='addresses'&&<div className="card"><h3>العناوين</h3>{addresses.filter(a=>a.customer_id===currentUser.id).map(a=><div key={a.id} className="alert">{a.governorate} - {a.district} - {a.area}<br/>{a.address}</div>)}</div>}</Workspace> }
  function VendorDashboard(){ if(!currentUser) return <Login/>; if(!isVendor(currentUser)) return <Forbidden/>; const v=vendorMap[currentUser.id]||{}; const f=financeForVendor(currentUser.id); const myProducts=products.filter(p=>p.vendor_id===currentUser.id); return <Workspace title="لوحة المورد" tabs={[['overview','الملخص'],['settings','بيانات المتجر'],['zones','التوصيل'],['products','المنتجات'],['orders','الطلبات'],['finance','الحساب المالي']]}>{currentUser.status!=='approved'&&<div className="alert err">حسابك تحت مراجعة الإدارة. تستطيع تجهيز البيانات، لكن المنتجات لن تنشر قبل الاعتماد.</div>}{tab==='overview'&&<div className="grid4"><div className="card"><h3>المنتجات</h3><b>{myProducts.length}</b></div><div className="card"><h3>منشورة</h3><b>{myProducts.filter(p=>p.status==='approved').length}</b></div><div className="card"><h3>المبيعات</h3><b>{egp(f.gross)}</b></div><div className="card"><h3>المتبقي للمنصة</h3><b>{egp(f.remaining)}</b></div></div>}{tab==='settings'&&<VendorSettings v={v}/>} {tab==='zones'&&<VendorZones/>} {tab==='products'&&<VendorProducts list={myProducts}/>} {tab==='orders'&&<VendorOrders/>} {tab==='finance'&&<VendorFinance f={f}/>}</Workspace> }
  function VendorSettings({v}){ return <div className="card"><form onSubmit={saveVendorSettings} className="formGrid"><div><label>اسم المتجر</label><input name="store_name" className="input" defaultValue={v.store_name||''} required/></div><div><label>الحد الأدنى للطلب</label><input name="min_order" type="number" className="input" defaultValue={v.min_order||0}/></div><LocationFields required={false}/><div><label>شعار جديد 600×600</label><input name="logo" type="file" className="input" accept="image/png,image/jpeg,image/webp"/></div><div><label>غلاف جديد 600×600</label><input name="cover" type="file" className="input" accept="image/png,image/jpeg,image/webp"/></div><div><label>البنك</label><input name="bank_name" className="input" defaultValue={v.bank_name||''}/></div><div><label>IBAN</label><input name="iban" className="input" defaultValue={v.iban||''}/></div><div><label>محفظة</label><input name="wallet_number" className="input" defaultValue={v.wallet_number||''}/></div><div><label>InstaPay</label><input name="instapay_handle" className="input" defaultValue={v.instapay_handle||''}/></div><div style={{gridColumn:'1/-1'}}><label>وصف المتجر</label><textarea name="description" defaultValue={v.description||''}/></div><button className="btn">حفظ</button></form></div> }
  function VendorZones(){ const my=zones.filter(z=>z.vendor_id===currentUser.id); return <div className="grid2"><div className="card"><h3>إضافة منطقة توصيل</h3><form onSubmit={addZone} className="formGrid"><LocationFields/><div><label>رسوم التوصيل</label><input name="delivery_fee" type="number" className="input" defaultValue="0"/></div><div><label>مدة التوصيل بالأيام</label><input name="eta_days" type="number" className="input" defaultValue="2"/></div><button className="btn">إضافة/تحديث المنطقة</button></form></div><div className="card"><h3>مناطقك</h3>{my.map(z=><div className="alert" key={z.id}>{z.governorate} - {z.district} - {z.area}<br/>رسوم: {egp(z.delivery_fee)} - مدة: {z.eta_days} أيام</div>)}</div></div> }
  function ProductForm(){ return <div className="card"><h3>إضافة منتج</h3><form onSubmit={saveProduct} className="formGrid"><div><label>الاسم العربي</label><input name="name_ar" className="input" required/></div><div><label>الاسم الإنجليزي</label><input name="name_en" className="input"/></div><div><label>SKU</label><input name="sku" className="input"/></div><div><label>القسم</label><select name="category" className="select">{CATEGORIES.map(c=><option key={c}>{c}</option>)}</select></div><div><label>العلامة التجارية</label><input name="brand" className="input"/></div><div><label>وحدة البيع</label><select name="unit" className="select">{UNITS.map(u=><option key={u}>{u}</option>)}</select></div><div><label>سعر القطاعي</label><input name="retail_price" type="number" step="0.01" className="input" required/></div><div><label>سعر الجملة</label><input name="wholesale_price" type="number" step="0.01" className="input" required/></div><div><label>سعر جملة الجملة</label><input name="super_wholesale_price" type="number" step="0.01" className="input" required/></div><div><label>حد الجملة</label><input name="wholesale_min" type="number" className="input" defaultValue="12"/></div><div><label>حد جملة الجملة</label><input name="super_wholesale_min" type="number" className="input" defaultValue="48"/></div><div><label>المخزون</label><input name="stock" type="number" className="input" defaultValue="0"/></div><div><label>حد أقصى للطلب</label><input name="max_qty" type="number" className="input" defaultValue="999"/></div><div><label>مدة التجهيز</label><input name="lead_time_days" type="number" className="input" defaultValue="1"/></div><div><label>صورة المنتج 600×600</label><input name="image" type="file" accept="image/png,image/jpeg,image/webp" className="input" required/></div><div style={{gridColumn:'1/-1'}}><label>وصف مختصر</label><input name="short_description" className="input"/></div><div style={{gridColumn:'1/-1'}}><label>الوصف الكامل</label><textarea name="description_ar"/></div><button className="btn">حفظ وإرسال للمراجعة</button></form></div> }
  function VendorProducts({list}){ return <><ProductForm/><div className="tableWrap"><table><thead><tr><th>المنتج</th><th>الحالة</th><th>الأسعار</th><th>المخزون</th></tr></thead><tbody>{list.map(p=><tr key={p.id}><td>{p.name_ar}</td><td><span className="badge">{p.status}</span></td><td>{egp(p.retail_price)} / {egp(p.wholesale_price)} / {egp(p.super_wholesale_price)}</td><td>{p.stock}</td></tr>)}</tbody></table></div></> }
  function VendorOrders(){ const my=items.filter(i=>i.vendor_id===currentUser.id); const orderIds=uniq(my.map(i=>i.order_id)); return <OrdersTable list={orders.filter(o=>orderIds.includes(o.id))} vendorMode/> }
  function VendorFinance({f}){ const myPay=payments.filter(p=>p.vendor_id===currentUser.id); return <div className="grid2"><div className="card"><h3>الحساب المالي</h3><p>إجمالي المبيعات: <b>{egp(f.gross)}</b></p><p>عمولة المنصة: <b>{egp(f.comm)}</b></p><p>مدفوع معتمد: <b>{egp(f.approvedPay)}</b></p><p>مدفوع معلق: <b>{egp(f.pendingPay)}</b></p><p>المتبقي: <b>{egp(f.remaining)}</b></p><p>صافي المورد: <b>{egp(f.net)}</b></p></div><div className="card"><h3>تسجيل دفعة عمولة</h3><form onSubmit={payCommission}><label>المبلغ</label><input name="amount" type="number" step="0.01" className="input" required/><label>طريقة الدفع</label><select name="method" className="select">{PAYMENT_METHODS.map(m=><option key={m}>{m}</option>)}</select><label>رقم العملية</label><input name="reference" className="input"/><label>ملاحظات</label><textarea name="notes"/><button className="btn">إرسال الدفعة للمراجعة</button></form></div><div className="card" style={{gridColumn:'1/-1'}}><h3>دفعاتي</h3>{myPay.map(p=><div className="alert" key={p.id}>{egp(p.amount)} - {p.method} - {p.status} - {p.reference}</div>)}</div></div> }
  function AdminDashboard(){ if(!currentUser) return <Login/>; if(!isAdmin(currentUser)) return <Forbidden/>; const vendorsUsers=users.filter(u=>u.role==='vendor'); return <Workspace title="بوابة الإدارة" tabs={[['overview','الملخص'],['approvals','الموافقات'],['finance','المالية'],['orders','الطلبات والتوصيل'],['staff','فريق الإدارة']]}>{tab==='overview'&&<div className="grid4"><div className="card"><h3>الموردون</h3><b>{vendorsUsers.length}</b></div><div className="card"><h3>موردون منتظرون</h3><b>{vendorsUsers.filter(u=>u.status==='pending').length}</b></div><div className="card"><h3>منتجات للمراجعة</h3><b>{products.filter(p=>p.status==='pending').length}</b></div><div className="card"><h3>طلبات</h3><b>{orders.length}</b></div></div>}{tab==='approvals'&&<AdminApprovals/>}{tab==='finance'&&<AdminFinance/>}{tab==='orders'&&<AdminOrders/>}{tab==='staff'&&<AdminStaff/>}</Workspace> }
  function AdminApprovals(){ return <div className="grid2"><div className="card"><h3>الموردون</h3>{users.filter(u=>u.role==='vendor').map(u=><div className="alert" key={u.id}><b>{vendorMap[u.id]?.store_name||u.name}</b> - {u.phone} - {u.status}<div className="row"><button className="btn" onClick={()=>approveUser(u.id,'approved')}>اعتماد</button><button className="btn danger" onClick={()=>approveUser(u.id,'rejected')}>رفض</button><button className="btn secondary" onClick={()=>approveUser(u.id,'suspended')}>إيقاف</button></div></div>)}</div><div className="card"><h3>المنتجات</h3>{products.filter(p=>p.status!=='approved').map(p=><div className="alert" key={p.id}><b>{p.name_ar}</b> - {vendorMap[p.vendor_id]?.store_name}<div className="row"><button className="btn" onClick={()=>approveProduct(p.id,'approved')}>نشر</button><button className="btn danger" onClick={()=>approveProduct(p.id,'rejected')}>رفض</button></div></div>)}</div></div> }
  function AdminFinance(){ const vendorUsers=users.filter(u=>u.role==='vendor'); return <><div className="tableWrap"><table><thead><tr><th>المورد</th><th>المبيعات</th><th>العمولة</th><th>المدفوع</th><th>المتبقي</th><th>صافي المورد</th><th>النسب</th></tr></thead><tbody>{vendorUsers.map(u=>{const f=financeForVendor(u.id); const v=vendorMap[u.id]||{}; return <tr key={u.id}><td>{v.store_name||u.name}</td><td>{egp(f.gross)}</td><td>{egp(f.comm)}</td><td>{egp(f.approvedPay)}</td><td>{egp(f.remaining)}</td><td>{egp(f.net)}</td><td><form onSubmit={e=>updateVendorFinance(e,u.id)} className="row"><input name="commission_percent" className="input" style={{width:90}} type="number" step="0.1" defaultValue={v.commission_percent||10}/><input name="premium_cart_percent" className="input" style={{width:90}} type="number" step="0.1" defaultValue={v.premium_cart_percent||1.5}/><input name="min_order" className="input" style={{width:110}} type="number" defaultValue={v.min_order||0}/><button className="btn secondary">حفظ</button></form></td></tr>})}</tbody></table></div><div className="card"><h3>دفعات الموردين</h3>{payments.map(p=><div className="alert" key={p.id}>{vendorMap[p.vendor_id]?.store_name} - {egp(p.amount)} - {p.method} - {p.status}<div className="row"><button className="btn" onClick={()=>updatePayment(p.id,'approved')}>اعتماد</button><button className="btn danger" onClick={()=>updatePayment(p.id,'rejected')}>رفض</button></div></div>)}</div></> }
  function AdminOrders(){ return <><OrdersTable list={orders} adminMode/><div className="card"><h3>التوصيلات</h3>{shipments.map(s=><div className="alert" key={s.id}>طلب {s.order_id?.slice(0,8)} - {vendorMap[s.vendor_id]?.store_name} - {s.governorate}/{s.district}/{s.area} - {s.status}<div className="row"><button className="btn secondary" onClick={()=>updateShipment(s.id,'preparing')}>تجهيز</button><button className="btn secondary" onClick={()=>updateShipment(s.id,'shipped')}>شحن</button><button className="btn" onClick={()=>updateShipment(s.id,'delivered')}>تسليم</button></div></div>)}</div></> }
  function AdminStaff(){ return <div className="grid2"><div className="card"><h3>إضافة موظف إدارة</h3><form onSubmit={addStaff} className="formGrid"><input name="name" className="input" placeholder="الاسم" required/><input name="phone" className="input" placeholder="الهاتف" required/><input name="email" className="input" placeholder="البريد اختياري"/><input name="password" type="password" className="input" placeholder="كلمة المرور" required/><label><input type="checkbox" name="finance"/> مالية</label><label><input type="checkbox" name="vendors"/> موردين</label><label><input type="checkbox" name="products"/> منتجات</label><label><input type="checkbox" name="orders"/> طلبات</label><label><input type="checkbox" name="delivery"/> توصيل</label><button className="btn">إضافة</button></form></div><div className="card"><h3>الفريق</h3>{users.filter(u=>u.role==='staff').map(u=><div className="alert" key={u.id}>{u.name} - {u.phone}<br/>{Object.keys(u.permissions||{}).join(' / ')}</div>)}</div></div> }
  function OrdersTable({list,adminMode=false}){ return <div className="tableWrap"><table><thead><tr><th>رقم</th><th>العميل</th><th>المكان</th><th>الدفع</th><th>الإجمالي</th><th>الحالة</th><th>إجراء</th></tr></thead><tbody>{list.map(o=><tr key={o.id}><td>{o.id.slice(0,8)}</td><td>{userMap[o.customer_id]?.name}</td><td>{o.governorate} / {o.district} / {o.area}</td><td>{o.payment_method}<br/>{PAYMENT_STATUS[o.payment_status]||o.payment_status}</td><td>{egp(o.total)}</td><td>{ORDER_STATUS[o.status]||o.status}</td><td>{adminMode&&<select className="select" value={o.status} onChange={e=>updateOrderStatus(o.id,e.target.value)}>{Object.entries(ORDER_STATUS).map(([k,v])=><option key={k} value={k}>{v}</option>)}</select>}</td></tr>)}</tbody></table>{!list.length&&<div className="empty">لا توجد طلبات</div>}</div> }
  function Workspace({title,tabs,children}){ return <Main title={title}><div className="tabs">{tabs.map(t=><button key={t[0]} className={tab===t[0]?'active':''} onClick={()=>setTab(t[0])}>{t[1]}</button>)}</div>{children}</Main> }
  function Main({title,children}){ return <><Header/><Status/><main className="container section"><div className="between"><h1>{title}</h1><button className="btn secondary" onClick={refreshAll}>تحديث البيانات</button></div>{children}</main><Footer/></> }
  function Footer(){ return <footer className="footer"><div className="container grid"><div><h3>تاجر</h3><p>منصة تجارية لإدارة الموردين والعملاء والتوصيلات والعمولات.</p></div><div><h3>روابط</h3><p><a href="/how" style={{color:'#fff'}}>كيف تعمل</a></p><p><a href="/support" style={{color:'#fff'}}>الدعم</a></p></div><div><h3>قانوني</h3><p><a href="/policies" style={{color:'#fff'}}>الشروط والسياسات</a></p></div></div></footer> }
  function Forbidden(){ return <Main title="غير مسموح"><div className="empty">هذه الصفحة ليست مخصصة لنوع حسابك.</div></Main> }
  function Static({title,body}){ return <Main title={title}><div className="card"><p style={{fontSize:18,lineHeight:1.9}}>{body}</p></div></Main> }

  const views={home:<><Header/><Status/><Home/><Footer/></>,setup:<Setup/>,login:<Login/>,customerRegister:<CustomerRegister/>,vendorRegister:<VendorRegister/>,market:<Market/>,vendors:<VendorsPage/>,cart:<CartPage/>,customer:<CustomerDashboard/>,vendor:<VendorDashboard/>,admin:<AdminDashboard/>,how:<Static title="كيف تعمل المنصة" body="يسجل المورد ويحدد مناطق التوصيل وأسعار القطاعي والجملة وجملة الجملة. تعتمد الإدارة المورد والمنتج. يختار العميل موقعه، ولا يتم إتمام الطلب في السلة العادية إلا إذا كان المورد يغطي المنطقة ويحقق الحد الأدنى. السلة المميزة تسمح بتجميع الموردين برسوم محددة."/>,support:<Static title="الدعم والتواصل" body="هذه صفحة الدعم. قبل الإطلاق اربط رقم واتساب وبريد رسمي، وحدد ساعات العمل وسياسة الرد على العملاء والموردين."/>,policies:<Static title="الشروط والسياسات" body="تغطي هذه الصفحة شروط الاستخدام، سياسة الخصوصية، سياسة الاسترجاع، وسياسة الشحن. يجب مراجعتها قانونيًا قبل الإطلاق التجاري."/>};
  return views[view] || views.home;
}
