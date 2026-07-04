create extension if not exists pgcrypto;

create table if not exists public.users (
  id uuid primary key default gen_random_uuid(),
  role text not null check (role in ('admin','staff','customer','vendor')),
  status text not null default 'pending' check (status in ('pending','approved','suspended','rejected')),
  name text not null,
  phone text not null unique,
  email text,
  password_hash text not null,
  governorate text,
  district text,
  area text,
  address text,
  permissions jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.vendors (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references public.users(id) on delete cascade,
  status text not null default 'pending' check (status in ('pending','approved','suspended','rejected')),
  store_name text not null,
  commercial_register text,
  tax_number text,
  logo_url text,
  cover_url text,
  governorate text,
  district text,
  description text,
  min_order numeric not null default 0,
  commission_percent numeric not null default 1.5,
  premium_cart_percent numeric not null default 1.5,
  delivery_zones jsonb not null default '[]'::jsonb,
  bank_name text,
  iban text,
  wallet_phone text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.products (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references public.vendors(id) on delete cascade,
  status text not null default 'pending' check (status in ('pending','approved','suspended','rejected','out_of_stock')),
  name_ar text not null,
  name_en text,
  sku text,
  category text,
  brand text,
  unit text not null default 'قطعة',
  description text,
  retail_price numeric not null default 0,
  wholesale_price numeric not null default 0,
  bulk_price numeric not null default 0,
  wholesale_min_qty numeric not null default 1,
  bulk_min_qty numeric not null default 1,
  stock_qty numeric not null default 0,
  image_url text,
  gallery jsonb not null default '[]'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.orders (
  id uuid primary key default gen_random_uuid(),
  customer_id uuid not null references public.users(id) on delete restrict,
  cart_type text not null default 'standard' check (cart_type in ('standard','premium')),
  governorate text not null,
  district text not null,
  area text not null,
  address text not null,
  payment_method text not null default 'cash',
  payment_status text not null default 'unpaid' check (payment_status in ('unpaid','partial','paid','refunded')),
  paid_amount numeric not null default 0,
  paid_at timestamptz,
  shipping_fee numeric not null default 0,
  subtotal numeric not null default 0,
  total numeric not null default 0,
  commission_total numeric not null default 0,
  vendor_net_total numeric not null default 0,
  status text not null default 'new' check (status in ('new','accepted','preparing','out_for_delivery','delivered','cancelled')),
  notes text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references public.orders(id) on delete cascade,
  product_id uuid not null references public.products(id) on delete restrict,
  vendor_id uuid not null references public.vendors(id) on delete restrict,
  qty numeric not null default 1,
  price_tier text not null default 'retail' check (price_tier in ('retail','wholesale','bulk')),
  unit_price numeric not null default 0,
  subtotal numeric not null default 0,
  commission numeric not null default 0,
  vendor_net numeric not null default 0,
  created_at timestamptz not null default now()
);

create table if not exists public.deliveries (
  id uuid primary key default gen_random_uuid(),
  order_id uuid not null references public.orders(id) on delete cascade,
  vendor_id uuid not null references public.vendors(id) on delete restrict,
  status text not null default 'pending' check (status in ('pending','assigned','picked','delivered','cancelled')),
  fee numeric not null default 0,
  duration text,
  governorate text,
  district text,
  area text,
  address text,
  tracking_note text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.commission_payments (
  id uuid primary key default gen_random_uuid(),
  vendor_id uuid not null references public.vendors(id) on delete cascade,
  amount numeric not null default 0,
  method text not null,
  reference text,
  notes text,
  status text not null default 'pending' check (status in ('pending','approved','rejected')),
  admin_note text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.financial_entries (
  id uuid primary key default gen_random_uuid(),
  entry_type text not null,
  source_table text,
  source_id uuid,
  vendor_id uuid references public.vendors(id) on delete set null,
  customer_id uuid references public.users(id) on delete set null,
  debit numeric not null default 0,
  credit numeric not null default 0,
  description text,
  created_at timestamptz not null default now()
);



alter table public.orders add column if not exists payment_status text not null default 'unpaid';
alter table public.orders add column if not exists paid_amount numeric not null default 0;
alter table public.orders add column if not exists paid_at timestamptz;

create index if not exists idx_users_phone on public.users(phone);
create index if not exists idx_vendors_user on public.vendors(user_id);
create index if not exists idx_products_vendor_status on public.products(vendor_id, status);
create index if not exists idx_orders_customer_status on public.orders(customer_id, status);
create index if not exists idx_items_vendor on public.order_items(vendor_id);
create index if not exists idx_deliveries_vendor on public.deliveries(vendor_id);
create index if not exists idx_payments_vendor_status on public.commission_payments(vendor_id, status);

insert into storage.buckets (id, name, public)
values ('product-images','product-images', true)
on conflict (id) do nothing;

insert into storage.buckets (id, name, public)
values ('vendor-images','vendor-images', true)
on conflict (id) do nothing;


create table if not exists public.support_tickets (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references public.users(id) on delete set null,
  name text,
  phone text,
  ticket_type text not null default 'أخرى',
  message text not null,
  status text not null default 'new' check (status in ('new','open','closed')),
  admin_note text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table public.deliveries add column if not exists tracking_note text;
create index if not exists idx_support_tickets_status on public.support_tickets(status);
create index if not exists idx_orders_payment_status on public.orders(payment_status);
create index if not exists idx_orders_status on public.orders(status);


alter table public.orders add column if not exists commission_recorded boolean not null default false;
alter table public.orders add column if not exists stock_released boolean not null default false;
alter table public.orders add column if not exists internal_note text;

create table if not exists public.audit_logs (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references public.users(id) on delete set null,
  action text not null,
  details jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

create table if not exists public.notifications (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references public.users(id) on delete cascade,
  title text not null,
  body text,
  is_read boolean not null default false,
  created_at timestamptz not null default now()
);

create index if not exists idx_audit_logs_user on public.audit_logs(user_id);
create index if not exists idx_notifications_user_read on public.notifications(user_id,is_read);
create index if not exists idx_products_stock on public.products(stock_qty);
