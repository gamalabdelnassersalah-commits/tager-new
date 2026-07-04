(function(){
  const env = window.TAGER_ENV || {};
  const url = env.SUPABASE_URL || '';
  const key = env.SUPABASE_ANON_KEY || '';
  const client = (url && key && window.supabase) ? window.supabase.createClient(url, key) : null;

  const stateKey = 'tager_session_v14';
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
