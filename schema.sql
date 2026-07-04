create table if not exists app_settings (
  id uuid primary key default gen_random_uuid(),
  key text unique not null,
  value jsonb not null default '{}'::jsonb,
  updated_at timestamptz default now()
);

create table if not exists users_profile (
  id uuid primary key default gen_random_uuid(),
  role text not null check (role in ('admin','supplier','buyer')),
  name text not null,
  phone text unique not null,
  email text,
  password_hash text,
  active boolean default true,
  created_at timestamptz default now()
);

create table if not exists buyers (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users_profile(id) on delete cascade,
  name text not null,
  person text,
  phone text,
  whatsapp text,
  email text,
  activity text,
  governorate text,
  center text,
  address text,
  monthly_order text,
  status text default 'نشط',
  created_at timestamptz default now()
);

create table if not exists suppliers (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users_profile(id) on delete cascade,
  name text not null,
  person text,
  phone text,
  whatsapp text,
  email text,
  activity text,
  commercial_register text,
  tax_card text,
  governorate text,
  center text,
  min_order numeric default 0,
  prep_days integer default 0,
  status text default 'قيد المراجعة',
  notes text,
  created_at timestamptz default now()
);

create table if not exists supplier_coverage (
  id uuid primary key default gen_random_uuid(),
  supplier_id uuid references suppliers(id) on delete cascade,
  governorate text not null,
  center text not null,
  shipping_fee numeric default 0,
  min_order numeric default 0,
  delivery_days integer default 0
);

create table if not exists categories (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  icon text,
  active boolean default true
);

create table if not exists products (
  id uuid primary key default gen_random_uuid(),
  supplier_id uuid references suppliers(id) on delete cascade,
  category_id uuid references categories(id),
  sku text,
  name text not null,
  unit text,
  retail_price numeric default 0,
  wholesale_price numeric default 0,
  wholesale_min_qty numeric default 0,
  super_price numeric default 0,
  super_min_qty numeric default 0,
  stock numeric default 0,
  image text,
  description text,
  status text default 'قيد المراجعة',
  created_at timestamptz default now()
);

create table if not exists orders (
  id uuid primary key default gen_random_uuid(),
  number text unique not null,
  buyer_user_id uuid references users_profile(id),
  buyer_name text,
  phone text,
  governorate text,
  center text,
  address text,
  payment_method text,
  subtotal numeric default 0,
  shipping numeric default 0,
  premium_fee numeric default 0,
  platform_commission numeric default 0,
  total numeric default 0,
  status text default 'جديد',
  payment_status text default 'غير مسدد',
  notes text,
  created_at timestamptz default now()
);

create table if not exists order_items (
  id uuid primary key default gen_random_uuid(),
  order_id uuid references orders(id) on delete cascade,
  product_id uuid references products(id),
  supplier_id uuid references suppliers(id),
  name text,
  category text,
  qty numeric default 0,
  price numeric default 0,
  total numeric default 0
);

create table if not exists supplier_payments (
  id uuid primary key default gen_random_uuid(),
  supplier_id uuid references suppliers(id),
  amount numeric default 0,
  reference text,
  notes text,
  paid_at timestamptz default now(),
  created_by uuid references users_profile(id)
);

create table if not exists support_tickets (
  id uuid primary key default gen_random_uuid(),
  number text unique not null,
  user_id uuid references users_profile(id),
  name text,
  phone text,
  type text,
  subject text,
  message text,
  status text default 'مفتوحة',
  created_at timestamptz default now()
);

create table if not exists audit_log (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references users_profile(id),
  user_name text,
  action text,
  details text,
  created_at timestamptz default now()
);

alter table orders add column if not exists coupon_code text;
alter table orders add column if not exists discount numeric default 0;

create table if not exists supplier_settlements (
  id uuid primary key default gen_random_uuid(),
  supplier_id uuid references suppliers(id),
  period_from date,
  period_to date,
  amount numeric default 0,
  reference text,
  notes text,
  status text default 'مسودة',
  created_by uuid references users_profile(id),
  created_at timestamptz default now()
);

create table if not exists coupons (
  id uuid primary key default gen_random_uuid(),
  code text unique not null,
  type text not null check (type in ('percent','fixed')),
  value numeric default 0,
  notes text,
  active boolean default true,
  used_count integer default 0,
  created_at timestamptz default now()
);

create table if not exists return_requests (
  id uuid primary key default gen_random_uuid(),
  order_id uuid references orders(id),
  buyer_user_id uuid references users_profile(id),
  reason text,
  amount numeric default 0,
  status text default 'قيد المراجعة',
  created_at timestamptz default now()
);

alter table orders add column if not exists tax numeric default 0;
alter table orders add column if not exists invoice_id uuid;

create table if not exists invoices (
  id uuid primary key default gen_random_uuid(),
  number text unique not null,
  order_id uuid references orders(id),
  order_number text,
  buyer_name text,
  phone text,
  governorate text,
  center text,
  address text,
  subtotal numeric default 0,
  shipping numeric default 0,
  premium_fee numeric default 0,
  discount numeric default 0,
  tax numeric default 0,
  total numeric default 0,
  status text default 'مصدرة',
  created_by uuid references users_profile(id),
  created_at timestamptz default now()
);

create table if not exists invoice_items (
  id uuid primary key default gen_random_uuid(),
  invoice_id uuid references invoices(id) on delete cascade,
  product_id uuid references products(id),
  supplier_id uuid references suppliers(id),
  name text,
  qty numeric default 0,
  price numeric default 0,
  total numeric default 0
);

create table if not exists stock_movements (
  id uuid primary key default gen_random_uuid(),
  product_id uuid references products(id),
  old_qty numeric default 0,
  new_qty numeric default 0,
  reason text,
  created_by uuid references users_profile(id),
  created_at timestamptz default now()
);

create table if not exists platform_notifications (
  id uuid primary key default gen_random_uuid(),
  type text,
  title text,
  message text,
  status text default 'نشط',
  created_at timestamptz default now()
);

create table if not exists supplier_documents (
  id uuid primary key default gen_random_uuid(),
  supplier_id uuid references suppliers(id) on delete cascade,
  document_type text,
  document_number text,
  file_url text,
  status text default 'قيد المراجعة',
  created_at timestamptz default now()
);
