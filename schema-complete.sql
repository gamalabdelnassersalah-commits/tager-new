-- Tager Platform - Complete Supabase Schema (Zero localStorage)
-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ===== USERS TABLE =====
CREATE TABLE IF NOT EXISTS public.users (
  id UUID PRIMARY KEY DEFAULT auth.uid(),
  email TEXT NOT NULL UNIQUE,
  phone TEXT NOT NULL UNIQUE,
  name TEXT,
  role TEXT NOT NULL CHECK (role IN ('customer', 'vendor', 'admin')) DEFAULT 'customer',
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'suspended')),
  password_hash TEXT,
  governorate TEXT,
  district TEXT,
  address TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  last_login TIMESTAMPTZ
);

-- ===== VENDORS TABLE =====
CREATE TABLE IF NOT EXISTS public.vendors (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  store_name TEXT NOT NULL,
  description TEXT,
  logo_url TEXT,
  cover_url TEXT,
  rating DECIMAL(3,2) DEFAULT 0,
  total_sales INTEGER DEFAULT 0,
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== PRODUCTS TABLE =====
CREATE TABLE IF NOT EXISTS public.products (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID NOT NULL REFERENCES public.vendors(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  description TEXT,
  sku TEXT,
  category TEXT,
  image_url TEXT,
  price_retail DECIMAL(10,2),
  price_wholesale DECIMAL(10,2),
  price_bulk DECIMAL(10,2),
  min_qty_wholesale INTEGER DEFAULT 6,
  min_qty_bulk INTEGER DEFAULT 20,
  stock INTEGER DEFAULT 0,
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== CART TABLE =====
CREATE TABLE IF NOT EXISTS public.cart (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
  quantity INTEGER NOT NULL CHECK (quantity > 0),
  price DECIMAL(10,2),
  added_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== ORDERS TABLE =====
CREATE TABLE IF NOT EXISTS public.orders (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  order_no TEXT NOT NULL UNIQUE,
  phone TEXT NOT NULL,
  governorate TEXT NOT NULL,
  district TEXT NOT NULL,
  address TEXT NOT NULL,
  items JSONB NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  commission DECIMAL(12,2) DEFAULT 0,
  payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'transfer', 'card')),
  status TEXT DEFAULT 'new' CHECK (status IN ('new', 'accepted', 'processing', 'delivered', 'cancelled')) DEFAULT 'new',
  notes TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== DELIVERY_ZONES TABLE =====
CREATE TABLE IF NOT EXISTS public.delivery_zones (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID NOT NULL REFERENCES public.vendors(id) ON DELETE CASCADE,
  governorate TEXT NOT NULL,
  district TEXT NOT NULL,
  delivery_fee DECIMAL(10,2) DEFAULT 0,
  sla TEXT DEFAULT '24h',
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== PAYMENTS TABLE =====
CREATE TABLE IF NOT EXISTS public.payments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id UUID NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  amount DECIMAL(12,2) NOT NULL,
  method TEXT NOT NULL CHECK (method IN ('cash', 'transfer', 'card')),
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')) DEFAULT 'pending',
  reference_no TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== INVOICES TABLE =====
CREATE TABLE IF NOT EXISTS public.invoices (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id UUID NOT NULL REFERENCES public.orders(id) ON DELETE CASCADE,
  invoice_no TEXT NOT NULL UNIQUE,
  vendor_id UUID NOT NULL REFERENCES public.vendors(id) ON DELETE CASCADE,
  amount DECIMAL(12,2) NOT NULL,
  commission DECIMAL(12,2) DEFAULT 0,
  status TEXT DEFAULT 'draft' CHECK (status IN ('draft', 'issued', 'sent', 'approved', 'paid')) DEFAULT 'draft',
  issued_at TIMESTAMPTZ DEFAULT NOW(),
  paid_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== REPORTS TABLE =====
CREATE TABLE IF NOT EXISTS public.reports (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  report_type TEXT NOT NULL CHECK (report_type IN ('sales', 'vendor_performance', 'customer_activity', 'financial', 'daily')),
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  data JSONB NOT NULL,
  generated_by UUID REFERENCES public.users(id),
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== TICKETS/SUPPORT TABLE =====
CREATE TABLE IF NOT EXISTS public.tickets (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  title TEXT NOT NULL,
  description TEXT,
  category TEXT,
  priority TEXT DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'urgent')),
  status TEXT DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'waiting', 'resolved', 'closed')) DEFAULT 'open',
  assigned_to UUID REFERENCES public.users(id),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== SETTINGS TABLE =====
CREATE TABLE IF NOT EXISTS public.settings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  platform_name TEXT DEFAULT 'Tager',
  currency TEXT DEFAULT 'ج.م',
  commission_rate DECIMAL(5,2) DEFAULT 1.5,
  delivery_base DECIMAL(10,2) DEFAULT 0,
  min_order DECIMAL(10,2) DEFAULT 0,
  support_phone TEXT,
  support_email TEXT,
  whatsapp_number TEXT,
  business_mode TEXT DEFAULT 'B2B' CHECK (business_mode IN ('B2B', 'B2C', 'C2C')),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ===== ROW LEVEL SECURITY (RLS) =====
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.vendors ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cart ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.delivery_zones ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reports ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tickets ENABLE ROW LEVEL SECURITY;

-- ===== RLS POLICIES =====

-- Users: Each user can see their own profile
CREATE POLICY "users_own_profile" ON public.users
  FOR SELECT USING (auth.uid() = id);

-- Cart: Only user can access their own cart
CREATE POLICY "cart_own_cart" ON public.cart
  FOR ALL USING (auth.uid() = user_id);

-- Orders: User can see their orders, Admin can see all
CREATE POLICY "orders_own_orders" ON public.orders
  FOR SELECT USING (auth.uid() = user_id OR 
    (SELECT role FROM public.users WHERE id = auth.uid()) = 'admin');

-- Vendors: Public read, vendor can edit own
CREATE POLICY "vendors_public_read" ON public.vendors
  FOR SELECT USING (TRUE);

CREATE POLICY "vendors_edit_own" ON public.vendors
  FOR UPDATE USING (auth.uid() = user_id);

-- Products: Public read, vendor can edit own
CREATE POLICY "products_public_read" ON public.products
  FOR SELECT USING (TRUE);

CREATE POLICY "products_vendor_edit" ON public.products
  FOR UPDATE USING (
    auth.uid() IN (
      SELECT user_id FROM public.vendors WHERE id = vendor_id
    )
  );

-- ===== INDEXES =====
CREATE INDEX idx_users_email ON public.users(email);
CREATE INDEX idx_users_phone ON public.users(phone);
CREATE INDEX idx_vendors_user_id ON public.vendors(user_id);
CREATE INDEX idx_products_vendor_id ON public.products(vendor_id);
CREATE INDEX idx_cart_user_id ON public.cart(user_id);
CREATE INDEX idx_orders_user_id ON public.orders(user_id);
CREATE INDEX idx_orders_status ON public.orders(status);
CREATE INDEX idx_invoices_vendor_id ON public.invoices(vendor_id);
CREATE INDEX idx_invoices_status ON public.invoices(status);
CREATE INDEX idx_payments_order_id ON public.payments(order_id);
CREATE INDEX idx_tickets_user_id ON public.tickets(user_id);
CREATE INDEX idx_delivery_zones_vendor_id ON public.delivery_zones(vendor_id);

-- ===== TRIGGERS FOR AUTO UPDATE =====
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_users_updated_at
BEFORE UPDATE ON public.users
FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER update_vendors_updated_at
BEFORE UPDATE ON public.vendors
FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER update_products_updated_at
BEFORE UPDATE ON public.products
FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER update_orders_updated_at
BEFORE UPDATE ON public.orders
FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE TRIGGER update_invoices_updated_at
BEFORE UPDATE ON public.invoices
FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ===== VIEWS FOR REPORTS =====
CREATE OR REPLACE VIEW public.daily_sales AS
SELECT
  DATE(created_at) as sale_date,
  COUNT(*) as total_orders,
  SUM(total) as total_revenue,
  SUM(commission) as total_commission,
  COUNT(DISTINCT user_id) as unique_customers
FROM public.orders
WHERE status IN ('accepted', 'processing', 'delivered')
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

CREATE OR REPLACE VIEW public.vendor_performance AS
SELECT
  v.id,
  v.store_name,
  COUNT(o.id) as total_orders,
  SUM(o.total) as total_sales,
  AVG(CAST((o.items->0->>'price') AS DECIMAL)) as avg_order_value,
  v.rating
FROM public.vendors v
LEFT JOIN public.orders o ON o.items @> jsonb_build_array(jsonb_build_object('vendorId', v.id::text))
GROUP BY v.id, v.store_name, v.rating
ORDER BY total_sales DESC;
