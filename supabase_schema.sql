-- ============================================================
-- Tager Platform — Complete Supabase Migration
-- Run this in Supabase SQL Editor (all at once)
-- ============================================================

begin;

-- ── 1. Platform Settings ────────────────────────────────────
create table if not exists platform_settings (
  key   text primary key,
  value jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);

-- ── 2. Categories ───────────────────────────────────────────
create table if not exists categories (
  id          uuid primary key default gen_random_uuid(),
  name        text not null,
  description text,
  icon        text,
  active      boolean not null default true,
  sort_order  int not null default 0,
  created_at  timestamptz not null default now()
);

-- ── 3. Users ──────────────────────────────────────────────
create table if not exists users (
  id            uuid primary key default gen_random_uuid(),
  role          text not null check (role in ('admin','vendor','customer','staff')),
  status        text not null default 'pending' check (status in ('pending','approved','rejected','suspended')),
  name          text not null,
  phone         text unique not null,
  email         text,
  password_hash text,
  governorate   text,
  district      text,
  address       text,
  permissions   jsonb not null default '{}'::jsonb,
  created_at    timestamptz not null default now(),
  updated_at    timestamptz not null default now()
);
create index if not exists users_phone_idx on users(phone);
create index if not exists users_role_idx  on users(role);

-- ── 4. Vendors ─────────────────────────────────────────────
create table if not exists vendors (
  id                   uuid primary key default gen_random_uuid(),
  user_id              uuid not null references users(id) on delete cascade,
  store_name           text not null,
  activity             text,
  commercial_register  text,
  tax_number           text,
  governorate          text,
  district             text,
  address              text,
  description          text,
  min_order            numeric(14,2) not null default 0,
  commission_percent   numeric(5,2) not null default 1.5,
  premium_cart_percent numeric(5,2) not null default 1.5,
  delivery_zones       jsonb not null default '[]'::jsonb,
  status               text not null default 'pending' check (status in ('pending','approved','rejected','suspended')),
  created_at           timestamptz not null default now(),
  updated_at           timestamptz not null default now()
);
create index if not exists vendors_user_id_idx   on vendors(user_id);
create index if not exists vendors_status_idx    on vendors(status);

-- ── 5. Vendor Delivery Zones (normalized) ───────────────────
create table if not exists vendor_delivery_zones (
  id           uuid primary key default gen_random_uuid(),
  vendor_id    uuid not null references vendors(id) on delete cascade,
  governorate  text not null,
  district     text not null,
  notes        text,
  is_active    boolean not null default true,
  created_at   timestamptz not null default now()
);
create index if not exists vdz_vendor_idx on vendor_delivery_zones(vendor_id);

-- ── 6. Products ────────────────────────────────────────────
create table if not exists products (
  id              uuid primary key default gen_random_uuid(),
  vendor_id       uuid not null references users(id),
  category_id     uuid references categories(id),
  name_ar         text not null,
  description     text,
  price           numeric(14,2) not null default 0,
  price_retail    numeric(14,2) not null default 0,
  price_wholesale numeric(14,2) not null default 0,
  price_bulk      numeric(14,2) not null default 0,
  stock_qty       numeric(10) not null default 0,
  min_qty         numeric(10) not null default 1,
  min_qty_retail  numeric(10) not null default 1,
  min_qty_wholesale numeric(10) not null default 1,
  min_qty_bulk    numeric(10) not null default 1,
  image_url       text,
  gallery         jsonb not null default '[]'::jsonb,
  sku             text,
  unit            text,
  lead_time       int,
  reorder_level   numeric(10) not null default 5,
  status          text not null default 'pending' check (status in ('pending','approved','rejected','suspended')),
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);
create index if not exists products_vendor_idx   on products(vendor_id);
create index if not exists products_category_idx on products(category_id);
create index if not exists products_status_idx   on products(status);

-- ── 7. Orders ──────────────────────────────────────────────
create table if not exists orders (
  id                  uuid primary key default gen_random_uuid(),
  order_no            text unique not null,
  customer_id         uuid references users(id),
  cart_type           text not null default 'normal',
  governorate         text,
  district            text,
  address             text,
  payment_method      text,
  payment_status      text not null default 'unpaid' check (payment_status in ('unpaid','paid','refunded')),
  paid_amount         numeric(14,2) not null default 0,
  paid_at             timestamptz,
  subtotal            numeric(14,2) not null default 0,
  shipping_fee        numeric(14,2) not null default 0,
  total               numeric(14,2) not null default 0,
  commission_total    numeric(14,2) not null default 0,
  vendor_net_total    numeric(14,2) not null default 0,
  status              text not null default 'new' check (status in ('new','processing','shipped','delivered','cancelled')),
  commission_recorded boolean not null default false,
  stock_released      boolean not null default false,
  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now()
);
create index if not exists orders_customer_idx on orders(customer_id);
create index if not exists orders_status_idx   on orders(status);
create index if not exists orders_no_idx       on orders(order_no);

-- ── 8. Order Items ─────────────────────────────────────────
create table if not exists order_items (
  id              uuid primary key default gen_random_uuid(),
  order_id        uuid not null references orders(id) on delete cascade,
  product_id      uuid references products(id),
  vendor_id       uuid references users(id),
  qty             numeric(10) not null default 1,
  price_tier      text not null default 'wholesale' check (price_tier in ('retail','wholesale','bulk')),
  unit_price      numeric(14,2) not null default 0,
  subtotal        numeric(14,2) not null default 0,
  commission      numeric(14,2) not null default 0,
  vendor_net      numeric(14,2) not null default 0,
  created_at      timestamptz not null default now()
);
create index if not exists order_items_order_idx  on order_items(order_id);
create index if not exists order_items_vendor_idx on order_items(vendor_id);

-- ── 9. Deliveries ─────────────────────────────────────────
create table if not exists deliveries (
  id           uuid primary key default gen_random_uuid(),
  order_id     uuid not null references orders(id) on delete cascade,
  vendor_id    uuid not null references users(id),
  status       text not null default 'pending' check (status in ('pending','preparing','shipped','delivered','cancelled')),
  fee          numeric(14,2) not null default 0,
  duration     text,
  governorate  text,
  district     text,
  area         text,
  address      text,
  tracking_note text,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);
create index if not exists deliveries_order_idx on deliveries(order_id);

-- ── 10. Commission Payments ────────────────────────────────
create table if not exists commission_payments (
  id           uuid primary key default gen_random_uuid(),
  vendor_id    uuid not null references users(id),
  amount       numeric(14,2) not null default 0,
  method       text,
  reference    text,
  status       text not null default 'pending' check (status in ('pending','approved','rejected')),
  admin_note   text,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);
create index if not exists commission_payments_vendor_idx on commission_payments(vendor_id);

-- ── 11. Financial Entries (audit ledger) ───────────────────
create table if not exists financial_entries (
  id            uuid primary key default gen_random_uuid(),
  entry_type    text not null,
  source_table  text,
  source_id     uuid,
  customer_id   uuid references users(id),
  debit         numeric(14,2) not null default 0,
  credit        numeric(14,2) not null default 0,
  description   text,
  created_at    timestamptz not null default now()
);
create index if not exists financial_entries_source_idx on financial_entries(source_table, source_id);

-- ── 12. Support Tickets ────────────────────────────────────
create table if not exists support_tickets (
  id           uuid primary key default gen_random_uuid(),
  user_id      uuid references users(id),
  name         text,
  phone        text,
  ticket_type  text,
  priority     text,
  message      text,
  status       text not null default 'new' check (status in ('new','in_progress','closed')),
  admin_note   text,
  created_at   timestamptz not null default now(),
  updated_at   timestamptz not null default now()
);
create index if not exists support_tickets_status_idx on support_tickets(status);

-- ── 13. Audit Log ──────────────────────────────────────────
create table if not exists audit_logs (
  id         uuid primary key default gen_random_uuid(),
  user_id    uuid references users(id),
  action     text,
  details    jsonb,
  created_at timestamptz not null default now()
);
create index if not exists audit_logs_user_idx on audit_logs(user_id);

-- ── 14. Notifications ──────────────────────────────────────
create table if not exists platform_notifications (
  id         uuid primary key default gen_random_uuid(),
  type       text,
  title      text,
  message    text,
  status     text not null default 'active',
  created_at timestamptz not null default now()
);

-- ── 15. Invoices ──────────────────────────────────────────
create table if not exists invoices (
  id              uuid primary key default gen_random_uuid(),
  invoice_no      text unique,
  order_id        uuid references orders(id),
  total           numeric(14,2) not null default 0,
  status          text not null default 'issued' check (status in ('issued','partially_paid','paid','overdue','void')),
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

-- ── 16. Invoice Items ─────────────────────────────────────
create table if not exists invoice_items (
  id         uuid primary key default gen_random_uuid(),
  invoice_id uuid not null references invoices(id) on delete cascade,
  product_id uuid references products(id),
  vendor_id  uuid references users(id),
  qty        numeric(10) not null default 1,
  unit_price numeric(14,2) not null default 0,
  subtotal   numeric(14,2) not null default 0,
  created_at timestamptz not null default now()
);

-- ── 17. Quote Requests ───────────────────────────────────
create table if not exists quote_requests (
  id           uuid primary key default gen_random_uuid(),
  user_id      uuid references users(id),
  product_id   uuid references products(id),
  vendor_id    uuid references users(id),
  product_name text,
  qty          numeric(10) not null default 1,
  phone        text,
  notes        text,
  status       text not null default 'new',
  created_at   timestamptz not null default now()
);

-- ── 18. Promotions / Coupons ─────────────────────────────
create table if not exists promotions (
  id         uuid primary key default gen_random_uuid(),
  code       text unique,
  title      text,
  type       text,
  value      numeric(14,2) not null default 0,
  min_amount numeric(14,2) not null default 0,
  active     boolean not null default true,
  created_at timestamptz not null default now()
);

-- ── 19. Return Requests ──────────────────────────────────
create table if not exists return_requests (
  id         uuid primary key default gen_random_uuid(),
  user_id    uuid references users(id),
  order_no   text,
  type       text,
  phone      text,
  notes      text,
  status     text not null default 'new',
  created_at timestamptz not null default now(),
  closed_at  timestamptz
);

-- ── 20. Internal Messages ─────────────────────────────────
create table if not exists internal_messages (
  id          uuid primary key default gen_random_uuid(),
  from_user_id uuid references users(id),
  to_user_id   text,
  subject     text,
  priority    text,
  body        text,
  status      text not null default 'new',
  created_at  timestamptz not null default now(),
  closed_at   timestamptz
);

-- ── 21. Delivery Rules ────────────────────────────────────
create table if not exists delivery_rules (
  id          uuid primary key default gen_random_uuid(),
  governorate text not null,
  district    text not null,
  fee         numeric(14,2) not null default 0,
  sla         text,
  active      boolean not null default true,
  created_at  timestamptz not null default now()
);

-- ── 22. Vendor Settlements ───────────────────────────────
create table if not exists vendor_settlements (
  id         uuid primary key default gen_random_uuid(),
  vendor_id  uuid not null references users(id),
  amount     numeric(14,2) not null default 0,
  reference  text,
  status     text not null default 'approved',
  created_at timestamptz not null default now()
);

-- ── 23. Import Batches ───────────────────────────────────
create table if not exists import_batches (
  id         uuid primary key default gen_random_uuid(),
  user_id    uuid references users(id),
  file_name  text,
  count_rows int not null default 0,
  status     text not null default 'completed',
  created_at timestamptz not null default now()
);

-- ── 24. Customer Favorites ──────────────────────────────
create table if not exists customer_favorites (
  id         uuid primary key default gen_random_uuid(),
  user_id    uuid not null references users(id) on delete cascade,
  product_id uuid not null references products(id) on delete cascade,
  created_at timestamptz not null default now(),
  unique(user_id, product_id)
);

-- ── 25. Stock Movements ───────────────────────────────────
create table if not exists stock_movements (
  id         uuid primary key default gen_random_uuid(),
  product_id uuid not null references products(id),
  type       text,
  qty        numeric(10) not null default 0,
  notes      text,
  user_id    uuid references users(id),
  created_at timestamptz not null default now()
);

-- ── 26. Supplier Documents ───────────────────────────────
create table if not exists supplier_documents (
  id            uuid primary key default gen_random_uuid(),
  user_id       uuid not null references users(id),
  title         text,
  type          text,
  url           text,
  notes         text,
  status        text not null default 'new',
  created_at    timestamptz not null default now()
);


-- ============================================================
-- ROW LEVEL SECURITY (RLS)
-- ============================================================

-- Enable RLS on all tables
alter table users enable row level security;
alter table vendors enable row level security;
alter table vendor_delivery_zones enable row level security;
alter table products enable row level security;
alter table orders enable row level security;
alter table order_items enable row level security;
alter table deliveries enable row level security;
alter table commission_payments enable row level security;
alter table financial_entries enable row level security;
alter table support_tickets enable row level security;
alter table audit_logs enable row level security;
alter table invoices enable row level security;
alter table invoice_items enable row level security;
alter table return_requests enable row level security;
alter table quote_requests enable row level security;
alter table promotions enable row level security;
alter table internal_messages enable row level security;
alter table customer_favorites enable row level security;
alter table stock_movements enable row level security;
alter table supplier_documents enable row level security;
alter table vendor_settlements enable row level security;
alter table import_batches enable row level security;
alter table delivery_rules enable row level security;


-- ── Helper function: get role from auth.uid() ─────────────
create or replace function public.tager_current_role()
returns text
language sql
stable
security definer
as $$
  select role from public.users where id = auth.uid() limit 1
$$;

-- Helper: is admin?
create or replace function public.is_admin()
returns boolean
language sql
stable
security definer
as $$
  select exists(select 1 from public.users where id = auth.uid() and role = 'admin')
$$;

-- Helper: is vendor?
create or replace function public.is_vendor()
returns boolean
language sql
stable
security definer
as $$
  select exists(select 1 from public.users where id = auth.uid() and role = 'vendor')
$$;

-- Helper: is the user who owns a vendor record?
create or replace function public.is_vendor_owner(vid uuid)
returns boolean
language sql
stable
security definer
as $$
  select exists(select 1 from public.vendors v where v.id = vid and v.user_id = auth.uid())
$$;


-- ── USERS policies ─────────────────────────────────────────
-- Anon can read only approved vendors (public profiles)
create policy users_public_read on public.users
  for select using (
    role = 'vendor' and status = 'approved'
  );

-- Admin can do everything
create policy users_admin_all on public.users
  for all using (public.is_admin())
  with check (public.is_admin());

-- Users can read their own profile
create policy users_self_read on public.users
  for select using (id = auth.uid());

-- Users can update their own profile
create policy users_self_update on public.users
  for update using (id = auth.uid())
  with check (id = auth.uid());

-- Allow anonymous insert for registration (password_hash required)
create policy users_anon_insert on public.users
  for insert with check (true);


-- ── VENDORS policies ────────────────────────────────────────
-- Anon/public can see approved vendors
create policy vendors_public_read on public.vendors
  for select using (status = 'approved');

-- Admin full access
create policy vendors_admin_all on public.vendors
  for all using (public.is_admin())
  with check (public.is_admin());

-- Vendor can read/update their own
create policy vendors_owner_read on public.vendors
  for select using (public.is_vendor_owner(id));

create policy vendors_owner_update on public.vendors
  for update using (public.is_vendor_owner(id))
  with check (public.is_vendor_owner(id));

-- Allow insert (registration)
create policy vendors_anon_insert on public.vendors
  for insert with check (true);


-- ── VENDOR DELIVERY ZONES ─────────────────────────────────
create policy vdz_admin_all on public.vendor_delivery_zones
  for all using (public.is_admin())
  with check (public.is_admin());

create policy vdz_owner_read on public.vendor_delivery_zones
  for select using (public.is_vendor_owner(vendor_id));

create policy vdz_owner_all on public.vendor_delivery_zones
  for all using (public.is_vendor_owner(vendor_id))
  with check (public.is_vendor_owner(vendor_id));

create policy vdz_public_read on public.vendor_delivery_zones
  for select using (is_active = true);


-- ── CATEGORIES ──────────────────────────────────────────────
-- Everyone can read categories
create policy categories_read on public.categories
  for select using (true);

-- Admin manages categories
create policy categories_admin_all on public.categories
  for all using (public.is_admin())
  with check (public.is_admin());


-- ── PRODUCTS ───────────────────────────────────────────────
-- Public can see approved products
create policy products_public_read on public.products
  for select using (status = 'approved');

-- Admin full access
create policy products_admin_all on public.products
  for all using (public.is_admin())
  with check (public.is_admin());

-- Vendor can read their own products
create policy products_vendor_read on public.products
  for select using (vendor_id = auth.uid());

-- Vendor can insert/update/delete their own
create policy products_vendor_insert on public.products
  for insert with check (vendor_id = auth.uid());

create policy products_vendor_update on public.products
  for update using (vendor_id = auth.uid())
  with check (vendor_id = auth.uid());

create policy products_vendor_delete on public.products
  for delete using (vendor_id = auth.uid());


-- ── ORDERS ─────────────────────────────────────────────────
-- Admin full access
create policy orders_admin_all on public.orders
  for all using (public.is_admin())
  with check (public.is_admin());

-- Customer can see own orders
create policy orders_customer_read on public.orders
  for select using (customer_id = auth.uid());

-- Vendor can see orders containing their items
create policy orders_vendor_read on public.orders
  for select using (
    exists (
      select 1 from public.order_items oi
      where oi.order_id = orders.id and oi.vendor_id = auth.uid()
    )
  );

-- Allow insert (order creation)
create policy orders_anon_insert on public.orders
  for insert with check (true);


-- ── ORDER ITEMS ────────────────────────────────────────────
create policy order_items_admin_all on public.order_items
  for all using (public.is_admin())
  with check (public.is_admin());

create policy order_items_customer_read on public.order_items
  for select using (
    exists (select 1 from public.orders o where o.id = order_items.order_id and o.customer_id = auth.uid())
  );

create policy order_items_vendor_read on public.order_items
  for select using (vendor_id = auth.uid());

create policy order_items_anon_insert on public.order_items
  for insert with check (true);


-- ── DELIVERIES ─────────────────────────────────────────────
create policy deliveries_admin_all on public.deliveries
  for all using (public.is_admin())
  with check (public.is_admin());

create policy deliveries_vendor_read on public.deliveries
  for select using (vendor_id = auth.uid());

create policy deliveries_customer_read on public.deliveries
  for select using (
    exists (select 1 from public.orders o where o.id = deliveries.order_id and o.customer_id = auth.uid())
  );

create policy deliveries_anon_insert on public.deliveries
  for insert with check (true);


-- ── COMMISSION PAYMENTS ───────────────────────────────────
create policy cp_admin_all on public.commission_payments
  for all using (public.is_admin())
  with check (public.is_admin());

create policy cp_vendor_read on public.commission_payments
  for select using (vendor_id = auth.uid());

create policy cp_anon_insert on public.commission_payments
  for insert with check (true);


-- ── FINANCIAL ENTRIES ─────────────────────────────────────
create policy fe_admin_all on public.financial_entries
  for all using (public.is_admin())
  with check (public.is_admin());

create policy fe_customer_read on public.financial_entries
  for select using (customer_id = auth.uid());

create policy fe_anon_insert on public.financial_entries
  for insert with check (true);


-- ── SUPPORT TICKETS ────────────────────────────────────────
create policy tickets_admin_all on public.support_tickets
  for all using (public.is_admin())
  with check (public.is_admin());

create policy tickets_self_read on public.support_tickets
  for select using (user_id = auth.uid());

create policy tickets_anon_insert on public.support_tickets
  for insert with check (true);


-- ── AUDIT LOGS ────────────────────────────────────────────
create policy audit_admin_all on public.audit_logs
  for all using (public.is_admin())
  with check (public.is_admin());

create policy audit_self_read on public.audit_logs
  for select using (user_id = auth.uid());

create policy audit_anon_insert on public.audit_logs
  for insert with check (true);


-- ── INVOICES & INVOICE ITEMS ──────────────────────────────
create policy invoices_admin_all on public.invoices
  for all using (public.is_admin())
  with check (public.is_admin());

create policy invoices_customer_read on public.invoices
  for select using (
    exists (select 1 from public.orders o where o.id = invoices.order_id and o.customer_id = auth.uid())
  );

create policy invoice_items_admin_all on public.invoice_items
  for all using (public.is_admin())
  with check (public.is_admin());

create policy invoice_items_public_read on public.invoice_items
  for select using (
    exists (
      select 1 from public.invoices i
      join public.orders o on o.id = i.order_id
      where i.id = invoice_items.invoice_id and (o.customer_id = auth.uid() or public.is_admin())
    )
  );


-- ── RETURN REQUESTS ──────────────────────────────────────
create policy returns_admin_all on public.return_requests
  for all using (public.is_admin())
  with check (public.is_admin());

create policy returns_self_read on public.return_requests
  for select using (user_id = auth.uid());

create policy returns_anon_insert on public.return_requests
  for insert with check (true);


-- ── PLATFORM SETTINGS ────────────────────────────────────
create policy settings_read on public.platform_settings
  for select using (true);

create policy settings_admin_all on public.platform_settings
  for all using (public.is_admin())
  with check (public.is_admin());


-- ── NOTIFICATIONS ─────────────────────────────────────────
create policy notifications_read on public.platform_notifications
  for select using (true);

create policy notifications_admin_all on public.platform_notifications
  for all using (public.is_admin())
  with check (public.is_admin());


-- ── STOCK MOVEMENTS ───────────────────────────────────────
create policy sm_admin_all on public.stock_movements
  for all using (public.is_admin())
  with check (public.is_admin());

create policy sm_vendor_read on public.stock_movements
  for select using (
    exists (select 1 from public.products p where p.id = product_id and p.vendor_id = auth.uid())
  );

create policy sm_anon_insert on public.stock_movements
  for insert with check (true);


-- ── SUPPLIER DOCUMENTS ────────────────────────────────────
create policy sd_admin_all on public.supplier_documents
  for all using (public.is_admin())
  with check (public.is_admin());

create policy sd_self_read on public.supplier_documents
  for select using (user_id = auth.uid());

create policy sd_anon_insert on public.supplier_documents
  for insert with check (true);


-- ── CUSTOMER FAVORITES ───────────────────────────────────
create policy fav_self_all on public.customer_favorites
  for all using (user_id = auth.uid())
  with check (user_id = auth.uid());

create policy fav_anon_insert on public.customer_favorites
  for insert with check (true);


-- ── QUOTE REQUESTS ───────────────────────────────────────
create policy qr_admin_all on public.quote_requests
  for all using (public.is_admin())
  with check (public.is_admin());

create policy qr_self_read on public.quote_requests
  for select using (user_id = auth.uid());

create policy qr_anon_insert on public.quote_requests
  for insert with check (true);


-- ── PROMOTIONS ──────────────────────────────────────────
create policy promos_read on public.promotions
  for select using (active = true);

create policy promos_admin_all on public.promotions
  for all using (public.is_admin())
  with check (public.is_admin());


-- ── INTERNAL MESSAGES ────────────────────────────────────
create policy msg_admin_all on public.internal_messages
  for all using (public.is_admin())
  with check (public.is_admin());

create policy msg_self_read on public.internal_messages
  for select using (from_user_id = auth.uid());

create policy msg_anon_insert on public.internal_messages
  for insert with check (true);


-- ── DELIVERY RULES ───────────────────────────────────────
create policy dr_read on public.delivery_rules
  for select using (active = true);

create policy dr_admin_all on public.delivery_rules
  for all using (public.is_admin())
  with check (public.is_admin());


-- ── VENDOR SETTLEMENTS ──────────────────────────────────
create policy vs_admin_all on public.vendor_settlements
  for all using (public.is_admin())
  with check (public.is_admin());

create policy vs_vendor_read on public.vendor_settlements
  for select using (vendor_id = auth.uid());

create policy vs_anon_insert on public.vendor_settlements
  for insert with check (true);


-- ── IMPORT BATCHES ──────────────────────────────────────
create policy ib_admin_all on public.import_batches
  for all using (public.is_admin())
  with check (public.is_admin());

create policy ib_self_read on public.import_batches
  for select using (user_id = auth.uid());

create policy ib_anon_insert on public.import_batches
  for insert with check (true);


-- ── Default Platform Settings Seed ─────────────────────────
insert into platform_settings (key, value)
values (
  'default',
  '{"platformName":"Tager","supportPhone":"+20 10 24237231","whatsapp":"+20 1127512512","email":"support@tager.com","defaultCommission":1.5,"premiumBasketFee":1.5,"minOrder":0,"currency":"ج.م","deliveryBase":0,"businessMode":"B2B","allowPremiumBasket":true}'
) on conflict (key) do nothing;

-- ── Default Categories Seed ───────────────────────────────
insert into categories (name, description, icon, active, sort_order) values
  ('مواد غذائية',      'أرز، سكر، زيت، مكرونة، معلبات',   '🛒', true, 1),
  ('مشروبات',          'مياه، عصائر، مشروبات غازية',      '🥤', true, 2),
  ('ألبان ومجمدات',    'ألبان، جبن، زبدة، مجمدات',        '🥛', true, 3),
  ('منظفات',           'منظفات منزلية وتجارية',             '🧼', true, 4),
  ('ورقيات',           'مناديل ورقية ومنتجات صحية',        '🧻', true, 5),
  ('عناية شخصية',      'منتجات عناية وجمال',                '🧴', true, 6),
  ('أدوات منزلية',     'مطبخ ومنزل ومستلزمات',             '🍳', true, 7),
  ('معدات تشغيل',      'أدوات ومعدات للمتاجر',             '🧰', true, 8),
  ('تعبئة وتغليف',     'عبوات، كراتين، أكياس',              '📦', true, 9),
  ('منتجات موسمية',    'عروض ومواسم',                      '⭐', true, 10),
  ('مطاعم وكافيهات',   'مستلزمات تشغيل',                   '☕', true, 11),
  ('تجارة عامة',       'موردون متنوعون',                    '🏬', true, 12)
on conflict do nothing;


commit;
