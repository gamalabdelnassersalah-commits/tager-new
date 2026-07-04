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
