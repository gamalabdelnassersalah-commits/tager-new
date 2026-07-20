/**
 * Tager Supabase Bridge
 * ────────────────────────
 * Drop-in replacement for the localStorage data layer inside index.html.
 * Include this script BEFORE the main index.html inline JS.
 *
 * It patches window state functions (load, save, blank, id, etc.) so that
 * the existing rendering code works unchanged but reads/writes from Supabase.
 *
 * Usage:
 *   <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
 *   <script src="tager-supabase-bridge.js"></script>
 *   <!-- then the rest of index.html -->
 */

;(function () {
  'use strict';

  /* ── Supabase Init ──────────────────────────────────── */
  const SUPABASE_URL  = 'https://xyiyjepwqhukvdnohpgc.supabase.co';
  const SUPABASE_KEY  = 'sb_publishable_gr1mLrOD8MFFKVoEjzv0Zw_ydO8hC5P';

  if (!window.supabase) {
    console.warn('[Tager Bridge] Supabase JS not loaded – falling back to localStorage');
    return; // let the original localStorage code run
  }

  const sb = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
  window.__tagerSB = sb;

  /* ── In-memory cache (single source of truth for this tab) ── */
  const CACHE_KEY = 'tager_preserved_platform_state';
  let _cache = null;

  function loadFromStorage() {
    try { return JSON.parse(localStorage.getItem(CACHE_KEY) || 'null'); }
    catch { return null; }
  }

  function saveToStorage(obj) {
    localStorage.setItem(CACHE_KEY, JSON.stringify(obj));
    updateCartCountGlobal();
  }

  function getDefaultSettings() {
    return {
      platformName: 'Tager',
      supportPhone: '+20 10 24237231',
      whatsapp: '+20 1127512512',
      email: 'support@tager.com',
      defaultCommission: 1.5,
      premiumBasketFee: 1.5,
      minOrder: 0,
      currency: 'ج.م',
      deliveryBase: 0,
      businessMode: 'B2B',
      allowPremiumBasket: true
    };
  }

  const CATEGORY_MASTER = [
    ['مواد غذائية','🛒','أرز، سكر، زيت، مكرونة، معلبات'],
    ['مشروبات','🥤','مياه، عصائر، مشروبات غازية'],
    ['ألبان ومجمدات','🥛','ألبان، جبن، زبدة، مجمدات'],
    ['منظفات','🧼','منظفات منزلية وتجارية'],
    ['ورقيات','🧻','مناديل ورقية ومنتجات صحية'],
    ['عناية شخصية','🧴','منتجات عناية وجمال'],
    ['أدوات منزلية','🍳','مطبخ ومنزل ومستلزمات'],
    ['معدات تشغيل','🧰','أدوات ومعدات للمتاجر'],
    ['تعبئة وتغليف','📦','عبوات، كراتين، أكياس'],
    ['منتجات موسمية','⭐','عروض ومواسم'],
    ['مطاعم وكافيهات','☕','مستلزمات تشغيل'],
    ['تجارة عامة','🏬','موردون متنوعون']
  ];

  function blankState() {
    return {
      settings: getDefaultSettings(),
      users: [],
      vendors: [],
      products: [],
      orders: [],
      tickets: [],
      payments: [],
      cart: [],
      areas: [],
      notifications: [],
      categories: CATEGORY_MASTER.map((c, i) => ({
        id: 'cat_local_' + i,
        name: c[0], icon: c[1], description: c[2], active: true, sort: i + 1
      }))
    };
  }

  function mergeCache(data) {
    const base = blankState();
    if (!data) return base;
    return {
      ...base,
      settings: data.settings ? { ...base.settings, ...data.settings } : base.settings,
      users:    Array.isArray(data.users) ? data.users : [],
      vendors:  Array.isArray(data.vendors) ? data.vendors : [],
      products: Array.isArray(data.products) ? data.products : [],
      orders:   Array.isArray(data.orders) ? data.orders : [],
      tickets:  Array.isArray(data.tickets) ? data.tickets : [],
      payments: Array.isArray(data.payments) ? data.payments : [],
      cart:     Array.isArray(data.cart) ? data.cart : [],
      areas:    Array.isArray(data.areas) ? data.areas : [],
      notifications: Array.isArray(data.notifications) ? data.notifications : [],
      categories: Array.isArray(data.categories) ? data.categories : base.categories
    };
  }

  /* ── Sync from Supabase → Cache ───────────────────────── */
  let _syncPromise = null;

  async function syncFromDB() {
    // Load settings
    const { data: settingsRows } = await sb.from('platform_settings').select('*');
    let settings = getDefaultSettings();
    if (settingsRows && settingsRows.length > 0) {
      const found = settingsRows.find(r => r.key === 'default');
      if (found) settings = { ...settings, ...found.value };
    }

    // Load categories
    const { data: cats } = await sb.from('categories').select('*').order('sort_order');
    const categories = (cats || []).map(c => ({
      id: c.id, name: c.name, icon: c.icon, description: c.description,
      active: c.active, sort: c.sort_order
    }));

    // Load users
    const { data: users } = await sb.from('users').select('*').order('created_at', { ascending: false });
    const mappedUsers = (users || []).map(u => ({
      id: u.id, role: u.role, status: u.status, name: u.name,
      phone: u.phone, email: u.email, password: u.password_hash,
      governorate: u.governorate, district: u.district,
      address: u.address, permissions: u.permissions || {},
      createdAt: u.created_at
    }));

    // Load vendors
    const { data: vendors } = await sb.from('vendors').select('*, users(*)').order('created_at', { ascending: false });
    const mappedVendors = (vendors || []).map(v => ({
      id: v.id, userId: v.user_id, storeName: v.store_name, activity: v.activity,
      commercialRegister: v.commercial_register, taxNumber: v.tax_number,
      governorate: v.governorate, district: v.district, address: v.address,
      description: v.description, minOrder: Number(v.min_order || 0),
      commissionPercent: Number(v.commission_percent || 1.5),
      premiumCartPercent: Number(v.premium_cart_percent || 1.5),
      zones: Array.isArray(v.delivery_zones) ? v.delivery_zones : [],
      status: v.status, createdAt: v.created_at
    }));

    // Load vendor delivery zones (normalized)
    const { data: vdz } = await sb.from('vendor_delivery_zones').select('*');
    if (vdz && vdz.length > 0) {
      for (const v of mappedVendors) {
        v.zones = vdz
          .filter(z => z.vendor_id === v.id)
          .map(z => ({ governorate: z.governorate, district: z.district, notes: z.notes }));
      }
    }

    // Load products
    const { data: prods } = await sb.from('products').select('*, categories(name)').order('created_at', { ascending: false });
    const mappedProducts = (prods || []).map(p => ({
      id: p.id, vendorId: p.vendor_id, category: p.categories?.name || '',
      categoryId: p.category_id, name: p.name_ar, description: p.description,
      price: Number(p.price), priceRetail: Number(p.price_retail),
      priceWholesale: Number(p.price_wholesale), priceBulk: Number(p.price_bulk),
      stock: Number(p.stock_qty), minQty: Number(p.min_qty),
      minQtyRetail: Number(p.min_qty_retail), minQtyWholesale: Number(p.min_qty_wholesale),
      minQtyBulk: Number(p.min_qty_bulk),
      image: p.image_url, gallery: Array.isArray(p.gallery) ? p.gallery : [],
      sku: p.sku, unit: p.unit, leadTime: p.lead_time, reorderLevel: Number(p.reorder_level),
      status: p.status, createdAt: p.created_at
    }));

    // Load orders with items
    const { data: orders } = await sb.from('orders').select('*, order_items(*)').order('created_at', { ascending: false });
    const mappedOrders = (orders || []).map(o => ({
      id: o.id, orderNo: o.order_no, customerId: o.customer_id,
      phone: '', date: new Date(o.created_at).toLocaleDateString('ar-EG'),
      items: (o.order_items || []).map(oi => ({
        id: oi.id, productId: oi.product_id, vendorId: oi.vendor_id,
        name: '', category: '', price: Number(oi.unit_price),
        qty: Number(oi.qty), tier: oi.price_tier,
        priceRetail: 0, priceWholesale: 0, priceBulk: 0
      })),
      governorate: o.governorate, district: o.district, address: o.address,
      paymentMethod: o.payment_method, cartType: o.cart_type || 'normal',
      total: Number(o.total), commission: Number(o.commission_total),
      status: o.status, paymentStatus: o.payment_status,
      createdAt: o.created_at
    }));

    // Load tickets
    const { data: tickets } = await sb.from('support_tickets').select('*').order('created_at', { ascending: false });
    const mappedTickets = (tickets || []).map(t => ({
      id: t.id, userId: t.user_id, name: t.name, phone: t.phone,
      type: t.ticket_type, priority: t.priority, message: t.message,
      status: t.status, createdAt: t.created_at
    }));

    // Load payments
    const { data: payments } = await sb.from('commission_payments').select('*').order('created_at', { ascending: false });
    const mappedPayments = (payments || []).map(p => ({
      id: p.id, vendorId: p.vendor_id, amount: Number(p.amount),
      method: p.method, reference: p.reference, status: p.status,
      adminNote: p.admin_note, createdAt: p.created_at
    }));

    _cache = mergeCache({
      settings, categories, users: mappedUsers, vendors: mappedVendors,
      products: mappedProducts, orders: mappedOrders,
      tickets: mappedTickets, payments: mappedPayments,
      cart: _cache ? _cache.cart : []  // keep cart in localStorage
    });

    saveToStorage(_cache);
    return _cache;
  }

  async function ensureSync() {
    if (_cache) return _cache;
    if (_syncPromise) return _syncPromise;
    _syncPromise = syncFromDB().finally(() => { _syncPromise = null; });
    return _syncPromise;
  }

  /* ── Write helpers: push changes back to Supabase ────── */

  async function pushUser(u) {
    if (!u.id || u.id.startsWith('local_')) return;
    await sb.from('users').upsert({
      id: u.id, role: u.role, status: u.status, name: u.name,
      phone: u.phone, email: u.email, password_hash: u.password || u.password_hash,
      governorate: u.governorate, district: u.district,
      address: u.address, permissions: u.permissions || {},
      updated_at: new Date().toISOString()
    }, { onConflict: 'id' });
  }

  async function pushVendor(v) {
    if (!v.id || v.id.startsWith('local_')) return;
    await sb.from('vendors').upsert({
      id: v.id, user_id: v.userId, store_name: v.storeName,
      activity: v.activity, commercial_register: v.commercialRegister,
      tax_number: v.taxNumber, governorate: v.governorate,
      district: v.district, address: v.address, description: v.description,
      min_order: Number(v.minOrder || 0),
      commission_percent: Number(v.commissionPercent || 1.5),
      premium_cart_percent: Number(v.premiumCartPercent || 1.5),
      delivery_zones: v.zones || [], status: v.status,
      updated_at: new Date().toISOString()
    }, { onConflict: 'id' });
  }

  async function pushProduct(p) {
    if (!p.id || p.id.startsWith('local_')) return;
    await sb.from('products').upsert({
      id: p.id, vendor_id: p.vendorId, category_id: p.categoryId,
      name_ar: p.name, description: p.description,
      price: Number(p.priceWholesale || 0),
      price_retail: Number(p.priceRetail || 0),
      price_wholesale: Number(p.priceWholesale || 0),
      price_bulk: Number(p.priceBulk || 0),
      stock_qty: Number(p.stock || 0),
      min_qty: Number(p.minQty || 1),
      min_qty_retail: Number(p.minQtyRetail || 1),
      min_qty_wholesale: Number(p.minQtyWholesale || 1),
      min_qty_bulk: Number(p.minQtyBulk || 1),
      image_url: p.image, gallery: p.gallery || [],
      sku: p.sku, unit: p.unit, lead_time: p.leadTime,
      reorder_level: Number(p.reorderLevel || 5),
      status: p.status, updated_at: new Date().toISOString()
    }, { onConflict: 'id' });
  }

  async function pushOrder(o) {
    if (!o.id || o.id.startsWith('local_')) return;
    await sb.from('orders').upsert({
      id: o.id, order_no: o.orderNo, customer_id: o.customerId,
      cart_type: o.cartType || 'normal',
      governorate: o.governorate, district: o.district,
      address: o.address, payment_method: o.paymentMethod,
      payment_status: o.paymentStatus || 'unpaid',
      subtotal: Number(o.subtotal || 0),
      shipping_fee: Number(o.shippingFee || 0),
      total: Number(o.total || 0),
      commission_total: Number(o.commission || 0),
      status: o.status, updated_at: new Date().toISOString()
    }, { onConflict: 'id' });

    // Push order items
    if (o.items && o.items.length) {
      for (const item of o.items) {
        if (item.id && !item.id.startsWith('local_')) {
          await sb.from('order_items').upsert({
            id: item.id, order_id: o.id, product_id: item.productId,
            vendor_id: item.vendorId, qty: Number(item.qty),
            price_tier: item.tier || 'wholesale',
            unit_price: Number(item.price), subtotal: Number(item.price * item.qty)
          }, { onConflict: 'id' });
        }
      }
    }
  }

  async function pushSettings(settings) {
    await sb.from('platform_settings').upsert({
      key: 'default', value: settings,
      updated_at: new Date().toISOString()
    }, { onConflict: 'key' });
  }

  async function pushTicket(t) {
    if (!t.id || t.id.startsWith('local_')) return;
    await sb.from('support_tickets').upsert({
      id: t.id, user_id: t.userId || null, name: t.name,
      phone: t.phone, ticket_type: t.type, priority: t.priority,
      message: t.message, status: t.status,
      admin_note: t.adminNote || '', updated_at: new Date().toISOString()
    }, { onConflict: 'id' });
  }

  async function pushPayment(p) {
    if (!p.id || p.id.startsWith('local_')) return;
    await sb.from('commission_payments').upsert({
      id: p.id, vendor_id: p.vendorId, amount: Number(p.amount),
      method: p.method, reference: p.reference,
      status: p.status, admin_note: p.adminNote || '',
      updated_at: new Date().toISOString()
    }, { onConflict: 'id' });
  }

  /* ── Async save wrapper ───────────────────────────────── */
  async function saveAsync(data) {
    _cache = mergeCache(data);
    saveToStorage(_cache); // keep local cache fast

    // Fire-and-forget sync to Supabase (don't block UI)
    (async () => {
      try {
        await pushSettings(_cache.settings);

        // Sync users
        for (const u of _cache.users) await pushUser(u);
        // Sync vendors
        for (const v of _cache.vendors) await pushVendor(v);
        // Sync products
        for (const p of _cache.products) await pushProduct(p);
        // Sync orders
        for (const o of _cache.orders) await pushOrder(o);
        // Sync tickets
        for (const t of _cache.tickets) await pushTicket(t);
        // Sync payments
        for (const p of _cache.payments) await pushPayment(p);
      } catch (err) {
        console.error('[Tager Bridge] Sync error:', err);
      }
    })();
  }


  /* ── Monkey-patch the global functions ──────────────────── */
  // Wait for the page scripts to define these, then override them.
  // We use a MutationObserver on <script> tags or just re-define after DOMContentLoaded.

  function patch() {
    if (typeof window.blank !== 'function') {
      setTimeout(patch, 50);
      return;
    }

    // Override load() to return cached Supabase data
    const _origLoad = window.load;
    window.load = function () {
      if (_cache) return _cache;
      // Fallback to localStorage while sync happens
      const stored = loadFromStorage();
      if (stored) return mergeCache(stored);
      return blankState();
    };

    // Override save() to push to Supabase
    window.save = saveAsync;

    // Override id() to generate UUIDs
    window.id = function () {
      return crypto.randomUUID ? crypto.randomUUID() : 'id_' + Date.now() + '_' + Math.random().toString(16).slice(2);
    };

    // Trigger initial sync
    ensureSync();

    console.log('[Tager Bridge] ✅ Supabase bridge active — data will sync automatically.');
  }

  // Start patching once DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patch);
  } else {
    patch();
  }

  /* ── Expose helpers ──────────────────────────────────── */
  window.tagerRefresh = async function () {
    _cache = null;
    await ensureSync();
    window.render(window.location.hash.replace('#', '') || 'home');
  };

  window.tagerIsConnected = function () {
    return !!window.__tagerSB;
  };

  // Helper to update cart count (used by the original code)
  function updateCartCountGlobal() {
    const el = document.getElementById('cartCount');
    if (!el) return;
    const s = _cache || loadFromStorage() || {};
    const n = (s.cart || []).reduce((a, i) => a + Number(i.qty || 0), 0);
    el.textContent = n;
  }

})();
