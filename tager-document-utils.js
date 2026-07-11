/**
 * Tager Document Utilities
 * Shared functions for document rendering and data manipulation
 */

export const DEFAULT_DOCUMENT_DATA = {
  orderNo: 'TG-1001',
  invoiceNo: 'INV-1001',
  date: '2026/7/6',
  platform: 'منصة تاجر',
  customer: 'Gamal Gemy',
  supplier: 'شركة الأخوة',
  governorate: 'الدقهلية',
  center: 'المنصورة',
  paymentMethod: 'نقدي عند الاستلام',
  currency: 'ج.م',
  status: 'جديد',
  itemCount: 1,
  totalWords: 'خمسة آلاف جنيه مصري لا غير',
  items: [
    { 
      name: 'بند تجريبي', 
      priceType: 'سعر الشراء', 
      quantity: 5, 
      unitPrice: 1000, 
      total: 5000 
    }
  ],
  totals: {
    products: 5000,
    discount: 0,
    tax: 0,
    grandTotal: 5000
  },
  signatoryName: 'Gamal',
  signatoryTitle: 'GAMAL',
  signatorySub: 'Authorized Signatory'
};

export const ICONS = {
  calendar: (
    <svg viewBox="0 0 24 24" className="icon">
      <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" fill="none" strokeWidth="1.5"/>
      <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" strokeWidth="1.5"/>
      <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" strokeWidth="1.5"/>
      <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" strokeWidth="1.5"/>
    </svg>
  ),
  clipboard: (
    <svg viewBox="0 0 24 24" className="icon">
      <path d="M8 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-3" stroke="currentColor" fill="none" strokeWidth="1.5"/>
      <path d="M8 3v4h8V3M9 13h6M9 17h6" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  ),
  package: (
    <svg viewBox="0 0 24 24" className="icon">
      <path d="M12 2L4 6v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V6l-8-4z" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  ),
  user: (
    <svg viewBox="0 0 24 24" className="icon">
      <circle cx="12" cy="8" r="4" stroke="currentColor" fill="none" strokeWidth="1.5"/>
      <path d="M5 20c0-4 3-8 7-8s7 4 7 8" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  ),
  mapPin: (
    <svg viewBox="0 0 24 24" className="icon">
      <path d="M12 2C7.58 2 4 5.58 4 10c0 5.25 8 13 8 13s8-7.75 8-13c0-4.42-3.58-8-8-8zm0 11c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  ),
  creditCard: (
    <svg viewBox="0 0 24 24" className="icon">
      <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" fill="none" strokeWidth="1.5"/>
      <line x1="2" y1="10" x2="22" y2="10" stroke="currentColor" strokeWidth="1.5"/>
    </svg>
  ),
  checkmark: (
    <svg viewBox="0 0 24 24" className="icon">
      <path d="M9 12l2 2 4-4" stroke="currentColor" strokeWidth="2" fill="none" strokeLinecap="round" strokeLinejoin="round"/>
      <circle cx="12" cy="12" r="10" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  ),
  box: (
    <svg viewBox="0 0 24 24" className="icon">
      <path d="M11 3C10.4477 3 10 3.44772 10 4V20C10 20.5523 10.4477 21 11 21H20V4C20 3.44772 19.5523 3 19 3H11Z" stroke="currentColor" fill="none" strokeWidth="1.5"/>
      <path d="M4 5H8V19H4V5Z" stroke="currentColor" fill="none" strokeWidth="1.5"/>
    </svg>
  )
};

/**
 * Format a number with proper locale formatting
 */
export const formatNumber = (value) => {
  const numericValue = Number(value || 0);
  return numericValue.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

/**
 * Convert number to Arabic words
 */
export const numberToArabicWords = (num) => {
  const ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
  const tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
  const hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
  const teens = ['عشرة', 'احدى عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];

  if (num === 0) return 'صفر';

  const processLessThanThousand = (num) => {
    let result = '';

    const hundred = Math.floor(num / 100);
    if (hundred > 0) result += hundreds[hundred] + ' ';

    const remainder = num % 100;
    if (remainder >= 10 && remainder < 20) {
      result += teens[remainder - 10];
    } else {
      const ten = Math.floor(remainder / 10);
      const one = remainder % 10;
      if (ten > 0) result += tens[ten] + ' ';
      if (one > 0) result += ones[one] + ' ';
    }

    return result.trim();
  };

  if (num < 1000) return processLessThanThousand(num);

  const thousands = Math.floor(num / 1000);
  let result = processLessThanThousand(thousands) + ' ألف ';

  const remainder = num % 1000;
  if (remainder > 0) result += processLessThanThousand(remainder);

  return result.trim();
};

/**
 * Calculate totals from items
 */
export const calculateTotals = (items) => {
  const subtotal = items.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
  return {
    subtotal,
    discount: 0,
    tax: 0,
    grandTotal: subtotal
  };
};

/**
 * Validate document data
 */
export const validateDocumentData = (data) => {
  const errors = [];

  if (!data.orderNo) errors.push('رقم الطلب مطلوب');
  if (!data.customer) errors.push('اسم العميل مطلوب');
  if (!data.supplier) errors.push('اسم المورد مطلوب');
  if (!data.items || data.items.length === 0) errors.push('يجب أن يكون هناك منتج واحد على الأقل');

  return {
    isValid: errors.length === 0,
    errors
  };
};

/**
 * Export data to JSON
 */
export const exportToJSON = (data, filename = 'document-data.json') => {
  const dataStr = JSON.stringify(data, null, 2);
  const dataBlob = new Blob([dataStr], { type: 'application/json' });
  const url = URL.createObjectURL(dataBlob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
};

/**
 * Import data from JSON
 */
export const importFromJSON = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const data = JSON.parse(e.target.result);
        resolve(data);
      } catch (error) {
        reject(error);
      }
    };
    reader.onerror = reject;
    reader.readAsText(file);
  });
};

/**
 * Generate unique ID
 */
export const generateId = () => {
  return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Create a new item
 */
export const createNewItem = () => {
  return {
    name: '',
    priceType: 'سعر الشراء',
    quantity: 1,
    unitPrice: 0,
    total: 0
  };
};

/**
 * Clone data deeply
 */
export const cloneData = (data) => {
  return JSON.parse(JSON.stringify(data));
};
