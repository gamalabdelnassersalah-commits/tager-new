/**
 * Tager Data Migration Script
 * ─────────────────────────────
 * Run this ONCE in the browser console to push all existing
 * localStorage data into Supabase tables.
 *
 * Prerequisites:
 *   1. Supabase schema has been applied (supabase_schema.sql)
 *   2. The bridge script (tager-supabase-bridge.js) is loaded
 *   3. You have existing data in localStorage under key 'tager_preserved_platform_state'
 *
 * Usage: Open DevTools console → paste this → press Enter
 */

;(async function () {
  'use strict';

  const SUPABASE_URL = prompt('Supabase URL:', 'https://xyiyjepwqhukvdnohpgc.supabase.co');
  const SUPABASE_KEY = prompt('Supabase Anon Key:', '');

  if (!SUPABASE_URL || !SUPABASE_KEY) {
    alert('Migration cancelled — URL or Key missing');
    return;
  }

  const sb = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

  // Load existing data
  const CACHE_KEY = 'tager_preserved_platform_state';
  let data;
  try {
    data = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
  } catch (e) {
    alert('Failed to read localStorage data: ' + e.message);
    return;
  }

  if (!data) {
    alert('No data found in localStorage to migrate.');
    return;
  }

  const results = { users: 0, vendors: 0, products: 0, orders: 0, tickets: 0, payments: 0, errors: [] };

  // ── 1. Settings ─────────────────────────────────────────
  try {
    await sb.from('platform_settings').upsert({
      key: 'default',
      value: data.settings || {},
      updated_at: new Date().toISOString()
    }, { onConflict: 'key' });
    results.settings = 1;
  } catch (e) { results.errors.push('settings: ' + e.message); }

  // ── 2. Categories (seed — already done in schema) ──────
  // Skipped if already seeded

  // ── 3. Users ───────────────────────────────────────────
  if (Array.isArray(data.users)) {
    for (const u of data.users) {
      try {
        const userId = u.id && !u.id.startsWith('id_') ? u.id : crypto.randomUUID();
        await sb.from('users').upsert({
          id: userId, role: u.role, status: u.status, name: u.name,
          phone: u.phone, email: u.email || null,
          password_hash: u.password || u.password_hash || null,
          governorate: u.governorate || null, district: u.district || null,
          address: u.address || null,
          permissions: u.permissions || {},
          created_at: u.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });
        // Map old id to new id for references
        u._newId = userId;
        results.users++;
      } catch (e) { results.errors.push('user ' + u.name + ': ' + e.message); }
    }
  }

  // ── 4. Vendors ─────────────────────────────────────────
  if (Array.isArray(data.vendors)) {
    for (const v of data.vendors) {
      try {
        const vendorId = v.id && !v.id.startsWith('id_') ? v.id : crypto.randomUUID();
        // Find the matching user
        const matchingUser = data.users.find(u => u.id === v.userId);
        const userId = matchingUser ? matchingUser._newId : null;
        if (!userId) { results.errors.push('vendor ' + v.storeName + ': no matching user'); continue; }

        await sb.from('vendors').upsert({
          id: vendorId, user_id: userId, store_name: v.storeName,
          activity: v.activity || null, commercial_register: v.commercialRegister || null,
          tax_number: v.taxNumber || null, governorate: v.governorate || null,
          district: v.district || null, address: v.address || null,
          description: v.description || null,
          min_order: Number(v.minOrder || 0),
          commission_percent: Number(v.commissionPercent || 1.5),
          premium_cart_percent: Number(v.premiumCartPercent || 1.5),
          delivery_zones: v.zones || [],
          status: v.status || 'pending',
          created_at: v.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });
        v._newId = vendorId;
        results.vendors++;
      } catch (e) { results.errors.push('vendor ' + v.storeName + ': ' + e.message); }
    }
  }

  // ── 5. Products ───────────────────────────────────────
  if (Array.isArray(data.products)) {
    for (const p of data.products) {
      try {
        const productId = p.id && !p.id.startsWith('id_') ? p.id : crypto.randomUUID();
        // Map vendorId
        const matchingUser = data.users.find(u => u.id === p.vendorId);
        const userId = matchingUser ? matchingUser._newId : p.vendorId;

        await sb.from('products').upsert({
          id: productId, vendor_id: userId || null,
          name_ar: p.name, description: p.description || null,
          price: Number(p.priceWholesale || p.price || 0),
          price_retail: Number(p.priceRetail || 0),
          price_wholesale: Number(p.priceWholesale || 0),
          price_bulk: Number(p.priceBulk || 0),
          stock_qty: Number(p.stock || 0),
          min_qty: Number(p.minQty || 1),
          image_url: p.image || null, gallery: [],
          sku: p.sku || null, status: p.status || 'pending',
          created_at: p.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });
        p._newId = productId;
        results.products++;
      } catch (e) { results.errors.push('product ' + p.name + ': ' + e.message); }
    }
  }

  // ── 6. Orders ──────────────────────────────────────────
  if (Array.isArray(data.orders)) {
    for (const o of data.orders) {
      try {
        const orderId = o.id && !o.id.startsWith('id_') ? o.id : crypto.randomUUID();
        const matchingUser = data.users.find(u => u.id === o.customerId);
        const userId = matchingUser ? matchingUser._newId : o.customerId;

        await sb.from('orders').upsert({
          id: orderId, order_no: o.orderNo || 'TG-' + Date.now(),
          customer_id: userId || null,
          cart_type: o.cartType || 'normal',
          governorate: o.governorate || null, district: o.district || null,
          address: o.address || null, payment_method: o.paymentMethod || null,
          payment_status: o.paymentStatus || 'unpaid',
          subtotal: Number(o.subtotal || 0),
          shipping_fee: Number(o.shippingFee || 0),
          total: Number(o.total || 0),
          commission_total: Number(o.commission || 0),
          status: o.status || 'new',
          created_at: o.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });

        // Order items
        if (Array.isArray(o.items) && o.items.length > 0) {
          const items = [];
          for (const item of o.items) {
            const matchingProduct = data.products.find(p => p.id === item.productId);
            const matchingVendor = data.users.find(u => u.id === item.vendorId);
            items.push({
              id: item.id && !item.id.startsWith('id_') ? item.id : crypto.randomUUID(),
              order_id: orderId,
              product_id: matchingProduct ? matchingProduct._newId : item.productId,
              vendor_id: matchingVendor ? matchingVendor._newId : item.vendorId,
              qty: Number(item.qty || 1),
              price_tier: item.tier || 'wholesale',
              unit_price: Number(item.price || 0),
              subtotal: Number(item.price || 0) * Number(item.qty || 1)
            });
          }
          await sb.from('order_items').upsert(items, { onConflict: 'id' });
        }

        results.orders++;
      } catch (e) { results.errors.push('order ' + (o.orderNo || o.id) + ': ' + e.message); }
    }
  }

  // ── 7. Support Tickets ─────────────────────────────────
  if (Array.isArray(data.tickets)) {
    for (const t of data.tickets) {
      try {
        const ticketId = t.id && !t.id.startsWith('id_') ? t.id : crypto.randomUUID();
        const matchingUser = data.users.find(u => u.id === t.userId);
        await sb.from('support_tickets').upsert({
          id: ticketId, user_id: matchingUser ? matchingUser._newId : null,
          name: t.name || null, phone: t.phone || null,
          ticket_type: t.type || null, priority: t.priority || null,
          message: t.message || null, status: t.status || 'new',
          created_at: t.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });
        results.tickets++;
      } catch (e) { results.errors.push('ticket: ' + e.message); }
    }
  }

  // ── 8. Payments ─────────────────────────────────────────
  if (Array.isArray(data.payments)) {
    for (const p of data.payments) {
      try {
        const payId = p.id && !p.id.startsWith('id_') ? p.id : crypto.randomUUID();
        const matchingVendor = data.users.find(u => u.id === p.vendorId);
        await sb.from('commission_payments').upsert({
          id: payId, vendor_id: matchingVendor ? matchingVendor._newId : p.vendorId,
          amount: Number(p.amount || 0), method: p.method || null,
          reference: p.reference || null, status: p.status || 'pending',
          admin_note: p.adminNote || null,
          created_at: p.createdAt || new Date().toISOString()
        }, { onConflict: 'id' });
        results.payments++;
      } catch (e) { results.errors.push('payment: ' + e.message); }
    }
  }

  // ── Report ─────────────────────────────────────────────
  const report = `
  ╔══════════════════════════════════════════╗
  ║   Tager Data Migration Complete! ✅      ║
  ╠══════════════════════════════════════════╣
  ║  Users:     ${String(results.users).padStart(5)}                      ║
  ║  Vendors:   ${String(results.vendors).padStart(5)}                      ║
  ║  Products:  ${String(results.products).padStart(5)}                      ║
  ║  Orders:    ${String(results.orders).padStart(5)}                      ║
  ║  Tickets:   ${String(results.tickets).padStart(5)}                      ║
  ║  Payments:  ${String(results.payments).padStart(5)}                      ║
  ╠══════════════════════════════════════════╣
  ║  Errors:    ${String(results.errors.length).padStart(5)}                      ║
  ╚══════════════════════════════════════════╝
  `;
  console.log(report);
  if (results.errors.length > 0) {
    console.error('Errors:', results.errors);
  }
  alert(
    `Migration complete!\n\n` +
    `Users: ${results.users}\nVendors: ${results.vendors}\n` +
    `Products: ${results.products}\nOrders: ${results.orders}\n` +
    `Tickets: ${results.tickets}\nPayments: ${results.payments}\n` +
    `Errors: ${results.errors.length}` +
    (results.errors.length > 0 ? '\n\nCheck console for details.' : '')
  );
})();
