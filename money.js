export function egp(value) {
  const n = Number(value || 0);
  return new Intl.NumberFormat('ar-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 2 }).format(n);
}

export function priceForQty(product, qty) {
  const q = Number(qty || 1);
  if (q >= Number(product.super_wholesale_min || 48)) return Number(product.super_wholesale_price || product.wholesale_price || product.retail_price || 0);
  if (q >= Number(product.wholesale_min || 12)) return Number(product.wholesale_price || product.retail_price || 0);
  return Number(product.retail_price || 0);
}

export function priceTier(product, qty) {
  const q = Number(qty || 1);
  if (q >= Number(product.super_wholesale_min || 48)) return 'جملة الجملة';
  if (q >= Number(product.wholesale_min || 12)) return 'جملة';
  return 'قطاعي';
}
