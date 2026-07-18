-- Invoice approval, e-signature metadata and immutable merchandise snapshots.
-- Run after 20260718_invoice_ledger.sql.

begin;

alter table public.invoices
  add column if not exists approval_status text not null default 'pending'
    check (approval_status in ('pending', 'approved', 'needs_revision')),
  add column if not exists approved_at timestamptz,
  add column if not exists approved_by uuid references public.users_profile(id),
  add column if not exists approval_notes text,
  add column if not exists signer_name text,
  add column if not exists signer_title text;

alter table public.invoice_items
  add column if not exists product_image_url text,
  add column if not exists product_sku text;

create table if not exists public.invoice_approvals (
  id uuid primary key default gen_random_uuid(),
  invoice_id uuid not null references public.invoices(id) on delete restrict,
  status text not null check (status in ('approved', 'needs_revision')),
  signer_name text not null,
  signer_title text not null,
  signature_data text,
  notes text,
  approved_by uuid references public.users_profile(id),
  approved_at timestamptz not null default now(),
  created_at timestamptz not null default now()
);

create index if not exists invoices_approval_status_idx
  on public.invoices(approval_status, issued_at desc);
create index if not exists invoice_approvals_invoice_id_approved_at_idx
  on public.invoice_approvals(invoice_id, approved_at desc);

create or replace function public.sync_invoice_approval_summary()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  update public.invoices
  set approval_status = new.status,
      approved_at = new.approved_at,
      approved_by = new.approved_by,
      approval_notes = new.notes,
      signer_name = new.signer_name,
      signer_title = new.signer_title,
      updated_at = now()
  where id = new.invoice_id;
  return new;
end;
$$;

drop trigger if exists invoice_approvals_sync_summary on public.invoice_approvals;
create trigger invoice_approvals_sync_summary
after insert on public.invoice_approvals
for each row execute function public.sync_invoice_approval_summary();

alter table public.invoice_approvals enable row level security;

drop policy if exists invoice_approvals_read_by_party on public.invoice_approvals;
create policy invoice_approvals_read_by_party on public.invoice_approvals
for select using (public.can_access_invoice(invoice_id));

drop policy if exists invoice_approvals_admin_write on public.invoice_approvals;
create policy invoice_approvals_admin_write on public.invoice_approvals
for all using (public.tager_current_role() = 'admin')
with check (public.tager_current_role() = 'admin');

commit;
