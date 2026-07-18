-- Tager invoice ledger: run through Supabase migrations, not from the browser.
-- This migration keeps the existing invoices/invoice_items tables and adds a
-- payment ledger plus row-level access rules for administrators, customers and suppliers.

begin;

alter table public.invoices
  add column if not exists issued_at timestamptz,
  add column if not exists due_at timestamptz,
  add column if not exists paid_amount numeric(14,2) not null default 0,
  add column if not exists additional_fees numeric(14,2) not null default 0,
  add column if not exists currency text not null default 'EGP',
  add column if not exists payment_status text not null default 'issued',
  add column if not exists voided_at timestamptz,
  add column if not exists void_reason text,
  add column if not exists updated_at timestamptz not null default now();

update public.invoices
set issued_at = coalesce(issued_at, created_at),
    payment_status = case when payment_status = '' then 'issued' else payment_status end
where issued_at is null or payment_status = '';

create table if not exists public.invoice_payments (
  id uuid primary key default gen_random_uuid(),
  invoice_id uuid not null references public.invoices(id) on delete restrict,
  amount numeric(14,2) not null check (amount > 0),
  method text not null,
  reference text,
  note text,
  paid_at timestamptz not null default now(),
  recorded_by uuid references public.users_profile(id),
  created_at timestamptz not null default now()
);

create index if not exists invoices_order_id_idx on public.invoices(order_id);
create index if not exists invoices_status_due_at_idx on public.invoices(payment_status, due_at);
create index if not exists invoice_items_invoice_id_idx on public.invoice_items(invoice_id);
create index if not exists invoice_payments_invoice_id_paid_at_idx on public.invoice_payments(invoice_id, paid_at desc);

create or replace function public.tager_current_role()
returns text
language sql
stable
security definer
set search_path = public
as $$
  select role from public.users_profile where id = auth.uid() limit 1
$$;

create or replace function public.can_access_invoice(target_invoice_id uuid)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select
    public.tager_current_role() = 'admin'
    or exists (
      select 1 from public.invoices i
      join public.orders o on o.id = i.order_id
      where i.id = target_invoice_id and o.buyer_user_id = auth.uid()
    )
    or exists (
      select 1 from public.invoice_items ii
      join public.suppliers s on s.id = ii.supplier_id
      where ii.invoice_id = target_invoice_id and s.user_id = auth.uid()
    )
$$;

create or replace function public.refresh_invoice_payment_totals()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  target_id uuid := coalesce(new.invoice_id, old.invoice_id);
  paid numeric(14,2);
  invoice_total numeric(14,2);
begin
  select coalesce(sum(amount), 0) into paid from public.invoice_payments where invoice_id = target_id;
  select total into invoice_total from public.invoices where id = target_id for update;
  if paid > coalesce(invoice_total, 0) then
    raise exception 'Payment total cannot exceed invoice total';
  end if;
  update public.invoices
  set paid_amount = paid,
      payment_status = case
        when voided_at is not null then 'void'
        when paid >= total then 'paid'
        when paid > 0 then 'partially_paid'
        when due_at is not null and due_at < now() then 'overdue'
        else 'issued'
      end,
      updated_at = now()
  where id = target_id;
  return coalesce(new, old);
end;
$$;

drop trigger if exists invoice_payments_refresh_totals on public.invoice_payments;
create trigger invoice_payments_refresh_totals
after insert or update or delete on public.invoice_payments
for each row execute function public.refresh_invoice_payment_totals();

alter table public.invoices enable row level security;
alter table public.invoice_items enable row level security;
alter table public.invoice_payments enable row level security;

drop policy if exists invoices_read_by_party on public.invoices;
create policy invoices_read_by_party on public.invoices
for select using (public.can_access_invoice(id));

drop policy if exists invoices_admin_write on public.invoices;
create policy invoices_admin_write on public.invoices
for all using (public.tager_current_role() = 'admin')
with check (public.tager_current_role() = 'admin');

drop policy if exists invoice_items_read_by_party on public.invoice_items;
create policy invoice_items_read_by_party on public.invoice_items
for select using (public.can_access_invoice(invoice_id));

drop policy if exists invoice_items_admin_write on public.invoice_items;
create policy invoice_items_admin_write on public.invoice_items
for all using (public.tager_current_role() = 'admin')
with check (public.tager_current_role() = 'admin');

drop policy if exists invoice_payments_read_by_party on public.invoice_payments;
create policy invoice_payments_read_by_party on public.invoice_payments
for select using (public.can_access_invoice(invoice_id));

drop policy if exists invoice_payments_admin_write on public.invoice_payments;
create policy invoice_payments_admin_write on public.invoice_payments
for all using (public.tager_current_role() = 'admin')
with check (public.tager_current_role() = 'admin');

commit;
