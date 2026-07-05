-- Tager database schema reference
create table if not exists users (
  id uuid primary key default gen_random_uuid(),
  role text not null check (role in ('admin','vendor','customer')),
  status text not null default 'pending',
  name text not null,
  phone text unique not null,
  email text,
  password_hash text,
  governorate text,
  district text,
  address text,
  created_at timestamptz default now()
);

create table if not exists vendors (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  store_name text not null,
  activity text,
  governorate text,
  district text,
  address text,
  min_order numeric default 0,
  created_at timestamptz default now()
);

create table if not exists categories (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  description text,
  icon text,
  active boolean default true,
  sort_order int default 0
);

create table if not exists vendor_delivery_zones (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid references vendors(id),
  governorate text not null,
  district text not null,
  notes text,
  is_active boolean default true
);

create table if not exists products (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid references users(id),
  category_id uuid references categories(id),
  name_ar text not null,
  description text,
  price numeric default 0,
  price_retail numeric default 0,
  price_wholesale numeric default 0,
  price_bulk numeric default 0,
  stock numeric default 0,
  min_qty numeric default 1,
  image_url text,
  status text default 'pending',
  created_at timestamptz default now()
);

create table if not exists orders (
  id uuid primary key default gen_random_uuid(),
  order_no text unique not null,
  customer_id uuid references users(id),
  total numeric default 0,
  commission_amount numeric default 0,
  governorate text,
  district text,
  address text,
  payment_method text,
  status text default 'new',
  created_at timestamptz default now()
);

create table if not exists order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid references orders(id),
  product_id uuid references products(id),
  vendor_id uuid references users(id),
  qty numeric default 1,
  unit_price numeric default 0,
  price_tier text default 'wholesale',
  subtotal numeric default 0,
  commission_percent numeric default 0,
  commission_amount numeric default 0
);

create table if not exists commission_payments (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid references users(id),
  amount numeric default 0,
  method text,
  reference text,
  status text default 'pending',
  created_at timestamptz default now()
);

create table if not exists support_tickets (
  id uuid primary key default gen_random_uuid(),
  name text,
  phone text,
  type text,
  priority text,
  message text,
  status text default 'new',
  created_at timestamptz default now()
);

create table if not exists platform_settings (
  key text primary key,
  value jsonb,
  updated_at timestamptz default now()
);

-- Additional operating tables for the completed platform experience
create table if not exists quote_requests (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  product_id uuid references products(id),
  vendor_id uuid references users(id),
  product_name text,
  qty numeric default 1,
  phone text,
  notes text,
  status text default 'new',
  created_at timestamptz default now()
);

create table if not exists customer_favorites (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  product_id uuid references products(id),
  created_at timestamptz default now()
);

create table if not exists platform_audit_log (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  action text,
  details jsonb,
  created_at timestamptz default now()
);


-- Operational development additions
create table if not exists delivery_rules (
  id uuid primary key default gen_random_uuid(),
  governorate text not null,
  district text not null,
  fee numeric default 0,
  sla text,
  active boolean default true,
  created_at timestamptz default now()
);

create table if not exists vendor_settlements (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid references users(id),
  amount numeric default 0,
  reference text,
  status text default 'approved',
  created_at timestamptz default now()
);

create table if not exists invoices (
  id uuid primary key default gen_random_uuid(),
  order_id uuid references orders(id),
  invoice_no text unique,
  total numeric default 0,
  status text default 'issued',
  created_at timestamptz default now()
);

create table if not exists import_batches (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  file_name text,
  count_rows int default 0,
  status text default 'completed',
  created_at timestamptz default now()
);

-- Full platform development additions
create table if not exists promotions (
  id uuid primary key default gen_random_uuid(),
  code text unique,
  title text,
  type text,
  value numeric default 0,
  min_amount numeric default 0,
  active boolean default true,
  created_at timestamptz default now()
);

create table if not exists return_requests (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  order_no text,
  type text,
  phone text,
  notes text,
  status text default 'new',
  created_at timestamptz default now(),
  closed_at timestamptz
);

create table if not exists internal_messages (
  id uuid primary key default gen_random_uuid(),
  from_user_id uuid references users(id),
  to_user_id text,
  subject text,
  priority text,
  body text,
  status text default 'new',
  created_at timestamptz default now(),
  closed_at timestamptz
);

create table if not exists stock_movements (
  id uuid primary key default gen_random_uuid(),
  product_id uuid references products(id),
  type text,
  qty numeric default 0,
  notes text,
  user_id uuid references users(id),
  created_at timestamptz default now()
);

create table if not exists supplier_documents (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users(id),
  title text,
  type text,
  url text,
  notes text,
  status text default 'new',
  created_at timestamptz default now()
);


-- Tier minimums and coverage additions
-- products: min_qty_retail, min_qty_wholesale, min_qty_bulk, unit, lead_time, reorder_point, delivery_mode
-- product_delivery_zones: product_id, governorate, district
-- vendor_delivery_zones: vendor_id, governorate, district, fee, sla, notes
