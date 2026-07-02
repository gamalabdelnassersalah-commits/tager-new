export function egp(value){
  return new Intl.NumberFormat('ar-EG',{style:'currency',currency:'EGP',maximumFractionDigits:2}).format(Number(value || 0));
}
export function qtyTier(product, qty){
  const q = Number(qty || 1);
  if (q >= Number(product.super_wholesale_min || 999999)) return 'super';
  if (q >= Number(product.wholesale_min || 999999)) return 'wholesale';
  return 'retail';
}
export function priceForQty(product, qty){
  const tier = qtyTier(product, qty);
  if (tier === 'super') return Number(product.super_wholesale_price || product.wholesale_price || product.retail_price || 0);
  if (tier === 'wholesale') return Number(product.wholesale_price || product.retail_price || 0);
  return Number(product.retail_price || 0);
}
export function tierLabel(tier){
  return tier === 'super' ? 'جملة الجملة' : tier === 'wholesale' ? 'جملة' : 'قطاعي';
}
