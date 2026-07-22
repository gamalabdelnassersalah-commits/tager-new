// Supabase Integration - Complete System (Zero localStorage)
// This replaces ALL localStorage operations with Supabase

class TagerSupabaseIntegration {
  constructor() {
    this.client = null;
    this.user = null;
    this.currentSession = null;
    this.cache = {};
    this.listeners = [];
  }

  // Initialize Supabase Client
  async init() {
    const { SUPABASE_URL, SUPABASE_ANON_KEY } = window.TAGER_ENV || {};
    
    if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
      console.error('❌ Missing Supabase credentials');
      return false;
    }

    // Load Supabase client
    const { createClient } = window.supabase || {};
    if (!createClient) {
      console.error('❌ Supabase library not loaded');
      return false;
    }

    this.client = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
    
    // Check current session
    const { data: { session } } = await this.client.auth.getSession();
    if (session) {
      this.currentSession = session;
      this.user = session.user;
      console.log('✅ Session restored:', this.user.email);
    }

    return true;
  }

  // === AUTHENTICATION ===

  async registerUser(email, password, phone, role = 'customer', name = '') {
    if (!this.client) return { error: 'Client not initialized' };

    try {
      // 1. Create Supabase auth account
      const { data: authData, error: authError } = await this.client.auth.signUp({
        email,
        password,
      });

      if (authError) return { error: authError.message };

      const userId = authData.user?.id;
      if (!userId) return { error: 'Failed to create user' };

      // 2. Save user profile in database
      const { error: dbError } = await this.client.from('users').insert({
        id: userId,
        email,
        phone,
        name,
        role,
        status: 'pending',
        created_at: new Date().toISOString(),
      });

      if (dbError) return { error: dbError.message };

      // 3. Set session
      this.currentSession = authData.session;
      this.user = authData.user;

      return { success: true, user: { id: userId, email, phone, role, name } };
    } catch (e) {
      return { error: e.message };
    }
  }

  async loginUser(email, password) {
    if (!this.client) return { error: 'Client not initialized' };

    try {
      const { data: { session }, error } = await this.client.auth.signInWithPassword({
        email,
        password,
      });

      if (error) return { error: error.message };

      this.currentSession = session;
      this.user = session?.user;

      // Get user profile
      const { data: profile } = await this.client
        .from('users')
        .select('*')
        .eq('id', session.user.id)
        .single();

      return { 
        success: true, 
        user: {
          id: session.user.id,
          email: session.user.email,
          ...profile
        }
      };
    } catch (e) {
      return { error: e.message };
    }
  }

  async logoutUser() {
    if (!this.client) return { error: 'Client not initialized' };

    const { error } = await this.client.auth.signOut();
    if (!error) {
      this.currentSession = null;
      this.user = null;
      this.cache = {};
    }
    return error ? { error: error.message } : { success: true };
  }

  async getCurrentUser() {
    if (!this.client) return null;
    if (this.user) return this.user;

    const { data: { session } } = await this.client.auth.getSession();
    if (session) {
      this.user = session.user;
      this.currentSession = session;
    }
    return this.user;
  }

  // === DATA OPERATIONS ===

  async loadAllData() {
    if (!this.client) return null;
    if (!this.user) return null;

    try {
      const [users, vendors, products, orders, cart] = await Promise.all([
        this.client.from('users').select('*'),
        this.client.from('vendors').select('*'),
        this.client.from('products').select('*'),
        this.client.from('orders').select('*'),
        this.client.from('cart').select('*').eq('user_id', this.user.id),
      ]);

      this.cache = {
        users: users.data || [],
        vendors: vendors.data || [],
        products: products.data || [],
        orders: orders.data || [],
        cart: cart.data || [],
        settings: { currency: 'ج.م', commissionRate: 10 }
      };

      return this.cache;
    } catch (e) {
      console.error('❌ Load data error:', e);
      return null;
    }
  }

  async saveData(table, data) {
    if (!this.client) return { error: 'Client not initialized' };

    try {
      // 1. Insert if new, update if exists
      const { error } = await this.client
        .from(table)
        .upsert(data, { onConflict: 'id' });

      if (error) throw error;

      // 2. Sync cache
      if (!this.cache[table]) this.cache[table] = [];
      
      const idx = this.cache[table].findIndex(item => item.id === data.id);
      if (idx >= 0) {
        this.cache[table][idx] = data;
      } else {
        this.cache[table].push(data);
      }

      // 3. Notify listeners
      this.notifyListeners('data-change', { table, data });

      return { success: true };
    } catch (e) {
      return { error: e.message };
    }
  }

  // === CART ===

  async addToCart(productId, quantity) {
    if (!this.user) return { error: 'Not logged in' };

    try {
      const cartItem = {
        id: `cart_${this.user.id}_${productId}_${Date.now()}`,
        user_id: this.user.id,
        product_id: productId,
        quantity,
        added_at: new Date().toISOString(),
      };

      const { error } = await this.client.from('cart').insert(cartItem);
      if (error) throw error;

      // Sync cache
      if (!this.cache.cart) this.cache.cart = [];
      this.cache.cart.push(cartItem);

      this.notifyListeners('cart-change', { action: 'add', item: cartItem });

      return { success: true, cartItem };
    } catch (e) {
      return { error: e.message };
    }
  }

  async removeFromCart(cartItemId) {
    if (!this.user) return { error: 'Not logged in' };

    try {
      const { error } = await this.client.from('cart').delete().eq('id', cartItemId);
      if (error) throw error;

      // Sync cache
      this.cache.cart = this.cache.cart?.filter(item => item.id !== cartItemId) || [];

      this.notifyListeners('cart-change', { action: 'remove', itemId: cartItemId });

      return { success: true };
    } catch (e) {
      return { error: e.message };
    }
  }

  async clearCart() {
    if (!this.user) return { error: 'Not logged in' };

    try {
      const { error } = await this.client.from('cart').delete().eq('user_id', this.user.id);
      if (error) throw error;

      this.cache.cart = [];
      this.notifyListeners('cart-change', { action: 'clear' });

      return { success: true };
    } catch (e) {
      return { error: e.message };
    }
  }

  // === ORDERS ===

  async createOrder(orderData) {
    if (!this.user) return { error: 'Not logged in' };

    try {
      const order = {
        id: `order_${Date.now()}`,
        user_id: this.user.id,
        order_no: `TG-${Date.now()}`,
        ...orderData,
        status: 'new',
        created_at: new Date().toISOString(),
      };

      const { error } = await this.client.from('orders').insert(order);
      if (error) throw error;

      // Sync cache
      if (!this.cache.orders) this.cache.orders = [];
      this.cache.orders.push(order);

      // Clear cart
      await this.clearCart();

      this.notifyListeners('order-created', order);

      return { success: true, order };
    } catch (e) {
      return { error: e.message };
    }
  }

  async getMyOrders() {
    if (!this.user) return [];
    if (this.cache.orders) return this.cache.orders.filter(o => o.user_id === this.user.id);

    try {
      const { data, error } = await this.client
        .from('orders')
        .select('*')
        .eq('user_id', this.user.id);

      if (error) throw error;
      return data || [];
    } catch (e) {
      console.error('❌ Get orders error:', e);
      return [];
    }
  }

  // === REAL-TIME SYNC ===

  subscribe(event, callback) {
    this.listeners.push({ event, callback });
  }

  notifyListeners(event, data) {
    this.listeners
      .filter(listener => listener.event === event)
      .forEach(listener => listener.callback(data));
  }

  // === ADMIN OPERATIONS ===

  async updateOrderStatus(orderId, status) {
    if (!this.user || this.user.role !== 'admin') {
      return { error: 'Not authorized' };
    }

    try {
      const { error } = await this.client
        .from('orders')
        .update({ status, updated_at: new Date().toISOString() })
        .eq('id', orderId);

      if (error) throw error;

      // Sync cache
      const order = this.cache.orders?.find(o => o.id === orderId);
      if (order) order.status = status;

      this.notifyListeners('order-status-change', { orderId, status });

      return { success: true };
    } catch (e) {
      return { error: e.message };
    }
  }

  async getAnalytics() {
    if (!this.user || this.user.role !== 'admin') {
      return { error: 'Not authorized' };
    }

    try {
      const [ordersRes, usersRes, vendorsRes, productsRes] = await Promise.all([
        this.client.from('orders').select('*'),
        this.client.from('users').select('*'),
        this.client.from('vendors').select('*'),
        this.client.from('products').select('*'),
      ]);

      const orders = ordersRes.data || [];
      const users = usersRes.data || [];
      const vendors = vendorsRes.data || [];
      const products = productsRes.data || [];

      return {
        totalOrders: orders.length,
        totalRevenue: orders.reduce((a, o) => a + (o.total || 0), 0),
        totalUsers: users.length,
        totalVendors: vendors.length,
        totalProducts: products.length,
        orders,
        users,
        vendors,
        products,
      };
    } catch (e) {
      return { error: e.message };
    }
  }
}

// Global Instance
window.tagerDB = new TagerSupabaseIntegration();

// Initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
  const initialized = await window.tagerDB.init();
  if (!initialized) {
    console.error('❌ Failed to initialize Supabase');
  } else {
    console.log('✅ Supabase integration initialized');
  }
});
