create extension if not exists pgcrypto;

-- Tager final production schema. It creates empty production tables only.
-- No demo vendors/products/customers are inserted.

create table if not exists users (
  id uuid primary key default gen_random_uuid(),
  role text not null check (role in ('admin','staff','customer','vendor')),
  status text not null default 'approved' check (status in ('pending','approved','suspended','rejected')),
  name text not null,
  phone text unique not null,
  email text unique,
  password_hash text not null,
  governorate text,
  district text,
  area text,
  address text,
  permissions jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

create table if not exists customer_addresses (
  id uuid primary key default gen_random_uuid(),
  customer_id uuid not null references users(id) on delete cascade,
  label text not null default 'العنوان الرئيسي',
  governorate text not null,
  district text not null,
  area text not null,
  address text not null,
  landmark text,
  is_default boolean not null default false,
  created_at timestamptz not null default now()
);

create table if not exists vendors (
  user_id uuid primary key references users(id) on delete cascade,
  store_name text not null,
  commercial_register text,
  tax_number text,
  logo_url text,
  cover_url text,
  governorate text,
  district text,
  area text,
  description text,
  min_order numeric not null default 0,
  commission_percent numeric not null default 10,
  premium_cart_percent numeric not null default 1.5,
  delivery_zones jsonb not null default '[]'::jsonb,
  bank_name text,
  iban text,
  wallet_number text,
  instapay_handle text,
  created_at timestamptz not null default now()
);

create table if not exists vendor_documents (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references users(id) on delete cascade,
  document_type text not null,
  document_number text,
  file_url text,
  status text not null default 'pending' check (status in ('pending','approved','rejected')),
  notes text,
  created_at timestamptz not null default now()
);

create table if not exists vendor_delivery_zones (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references users(id) on delete cascade,
  governorate text not null,
  district text not null,
  area text not null default 'كل المناطق',
  delivery_fee numeric not null default 0,
  eta_days int not null default 2,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  unique(vendor_id, governorate, district, area)
);

create table if not exists products (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references users(id) on delete cascade,
  status text not null default 'pending' check (status in ('pending','approved','rejected','draft')),
  name_ar text not null,
  name_en text,
  sku text,
  category text,
  brand text,
  unit text,
  description_ar text,
  short_description text,
  retail_price numeric not null default 0,
  wholesale_price numeric not null default 0,
  super_wholesale_price numeric not null default 0,
  wholesale_min int not null default 12,
  super_wholesale_min int not null default 48,
  stock int not null default 0,
  max_qty int not null default 999,
  lead_time_days int not null default 0,
  image_url text,
  gallery jsonb not null default '[]'::jsonb,
  created_at timestamptz not null default now()
);

create table if not exists orders (
  id uuid primary key default gen_random_uuid(),
  customer_id uuid not null references users(id),
  cart_type text not null default 'separate' check (cart_type in ('separate','premium')),
  governorate text,
  district text,
  area text,
  address text,
  shipping_fee numeric not null default 0,
  premium_fee numeric not null default 0,
  payment_method text,
  payment_status text not null default 'pending' check (payment_status in ('pending','paid','failed','review')),
  total numeric not null default 0,
  platform_commission numeric not null default 0,
  vendor_net numeric not null default 0,
  status text not null default 'new' check (status in ('new','confirmed','preparing','shipped','delivered','cancelled')),
  delivery_status text not null default 'pending',
  created_at timestamptz not null default now()
);

create table if not exists order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references orders(id) on delete cascade,
  product_id uuid references products(id),
  vendor_id uuid not null references users(id),
  qty int not null,
  unit_price numeric not null,
  subtotal numeric not null,
  commission_percent numeric not null,
  commission_amount numeric not null,
  vendor_net numeric not null,
  created_at timestamptz not null default now()
);

create table if not exists shipments (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references orders(id) on delete cascade,
  vendor_id uuid not null references users(id),
  status text not null default 'pending',
  governorate text,
  district text,
  area text,
  delivery_fee numeric not null default 0,
  eta_days int not null default 2,
  assigned_to text,
  tracking_notes text,
  delivered_at timestamptz,
  created_at timestamptz not null default now()
);

create table if not exists delivery_events (
  id uuid primary key default gen_random_uuid(),
  shipment_id uuid not null references shipments(id) on delete cascade,
  status text not null,
  notes text,
  created_by uuid references users(id),
  created_at timestamptz not null default now()
);

create table if not exists commission_payments (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references users(id),
  amount numeric not null,
  method text not null,
  reference text,
  notes text,
  status text not null default 'pending' check (status in ('pending','approved','rejected')),
  created_at timestamptz not null default now()
);

create table if not exists vendor_settlements (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references users(id),
  period_from date,
  period_to date,
  gross_sales numeric not null default 0,
  commission_due numeric not null default 0,
  commission_paid numeric not null default 0,
  remaining numeric not null default 0,
  vendor_net numeric not null default 0,
  status text not null default 'open' check (status in ('open','closed','cancelled')),
  created_at timestamptz not null default now()
);

create table if not exists invoices (
  id uuid primary key default gen_random_uuid(),
  order_id uuid references orders(id) on delete set null,
  vendor_id uuid references users(id) on delete set null,
  customer_id uuid references users(id) on delete set null,
  invoice_type text not null check (invoice_type in ('customer_order','vendor_commission','vendor_settlement','delivery_fee')),
  amount numeric not null default 0,
  status text not null default 'open' check (status in ('open','paid','cancelled')),
  notes text,
  created_at timestamptz not null default now()
);

create table if not exists audit_logs (
  id uuid primary key default gen_random_uuid(),
  actor_id uuid references users(id) on delete set null,
  action text not null,
  entity_type text,
  entity_id text,
  before_data jsonb,
  after_data jsonb,
  created_at timestamptz not null default now()
);

create table if not exists platform_settings (
  key text primary key,
  value jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);

insert into platform_settings(key,value) values
('general', '{"default_commission_percent":10,"default_premium_cart_percent":1.5,"currency":"EGP","require_vendor_delivery_zone":true,"min_image_width":600,"min_image_height":600}'::jsonb)
on conflict (key) do nothing;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('product-images','product-images',true,5242880,array['image/jpeg','image/png','image/webp'])
on conflict (id) do nothing;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('vendor-images','vendor-images',true,5242880,array['image/jpeg','image/png','image/webp'])
on conflict (id) do nothing;

create index if not exists idx_users_phone on users(phone);
create index if not exists idx_products_vendor on products(vendor_id);
create index if not exists idx_products_status on products(status);
create index if not exists idx_zones_vendor on vendor_delivery_zones(vendor_id);
create index if not exists idx_zones_place on vendor_delivery_zones(governorate,district,area);
create index if not exists idx_orders_customer on orders(customer_id);
create index if not exists idx_order_items_vendor on order_items(vendor_id);
create index if not exists idx_shipments_vendor on shipments(vendor_id);
