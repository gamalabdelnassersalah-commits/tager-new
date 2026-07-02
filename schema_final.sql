-- Tager Final Production Candidate Schema
-- Run this in Supabase SQL Editor.
-- It is idempotent and safe to run more than once.

create extension if not exists pgcrypto;

-- Remove old demo rows from previous test builds only.
do $$
begin
  delete from commission_payments where vendor_id in (select id from users where phone in ('01000000000','01111111111','01222222222'));
  delete from order_items where order_id in (select id from orders where customer_id in (select id from users where phone in ('01000000000','01111111111','01222222222')));
  delete from orders where customer_id in (select id from users where phone in ('01000000000','01111111111','01222222222'));
  delete from products where vendor_id in (select id from users where phone in ('01000000000','01111111111','01222222222'));
  delete from vendors where user_id in (select id from users where phone in ('01000000000','01111111111','01222222222'));
  delete from users where phone in ('01000000000','01111111111','01222222222');
exception when undefined_table then null;
end $$;

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
  address text,
  permissions jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

alter table users add column if not exists permissions jsonb not null default '{}'::jsonb;

create table if not exists vendors (
  user_id uuid primary key references users(id) on delete cascade,
  store_name text not null,
  commercial_register text,
  tax_number text,
  logo_url text,
  cover_url text,
  governorate text,
  district text,
  description text,
  min_order numeric not null default 0,
  commission_percent numeric not null default 10,
  premium_cart_percent numeric not null default 1.5,
  delivery_zones jsonb not null default '[]'::jsonb,
  bank_name text,
  iban text,
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
  cart_type text not null default 'separate',
  governorate text,
  district text,
  area text,
  address text,
  shipping_fee numeric not null default 0,
  premium_fee numeric not null default 0,
  payment_method text,
  payment_status text not null default 'pending',
  total numeric not null default 0,
  platform_commission numeric not null default 0,
  vendor_net numeric not null default 0,
  status text not null default 'new',
  delivery_status text not null default 'pending',
  created_at timestamptz not null default now()
);

alter table orders add column if not exists area text;
alter table orders add column if not exists address text;
alter table orders add column if not exists premium_fee numeric not null default 0;
alter table orders add column if not exists delivery_status text not null default 'pending';

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
  tracking_notes text,
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

create table if not exists platform_settings (
  key text primary key,
  value jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);

insert into platform_settings(key,value) values
('general', '{"default_commission_percent":10,"default_premium_cart_percent":1.5,"currency":"EGP"}'::jsonb)
on conflict (key) do nothing;

create index if not exists idx_products_vendor on products(vendor_id);
create index if not exists idx_products_status on products(status);
create index if not exists idx_zones_vendor on vendor_delivery_zones(vendor_id);
create index if not exists idx_zones_place on vendor_delivery_zones(governorate,district,area);
create index if not exists idx_orders_customer on orders(customer_id);
create index if not exists idx_items_vendor on order_items(vendor_id);
create index if not exists idx_payments_vendor on commission_payments(vendor_id);

create or replace view vendor_finance_summary as
select
  u.id as vendor_id,
  u.name as vendor_user_name,
  v.store_name,
  coalesce(sum(oi.subtotal),0) as gross_sales,
  coalesce(sum(oi.commission_amount),0) as commission_due,
  coalesce(sum(oi.vendor_net),0) as vendor_net,
  coalesce((select sum(cp.amount) from commission_payments cp where cp.vendor_id=u.id and cp.status='approved'),0) as commission_paid,
  coalesce(sum(oi.commission_amount),0) - coalesce((select sum(cp.amount) from commission_payments cp where cp.vendor_id=u.id and cp.status='approved'),0) as commission_remaining
from users u
left join vendors v on v.user_id = u.id
left join order_items oi on oi.vendor_id = u.id
where u.role='vendor'
group by u.id,u.name,v.store_name;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('product-images','product-images',true,5242880,array['image/jpeg','image/png','image/webp'])
on conflict (id) do nothing;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('vendor-images','vendor-images',true,5242880,array['image/jpeg','image/png','image/webp'])
on conflict (id) do nothing;
