'use client';

import React, { useState } from 'react';

const TagerDocument = ({ initialData = null, activeTab: initialTab = 'po' }) => {
  const [activeTab, setActiveTab] = useState(initialTab);
  const [showForm, setShowForm] = useState(false);
  
  const defaultData = {
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
    items: [
      { name: 'بند تجريبي', priceType: 'سعر الشراء', quantity: 5, unitPrice: 1000, total: 5000 }
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

  const [formData, setFormData] = useState(initialData || defaultData);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleTotalChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      totals: {
        ...prev.totals,
        [name]: parseFloat(value) || 0
      }
    }));
  };

  const formatNumber = (value) => {
    return Number(value || 0).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  const isPO = activeTab === 'po';
  const docTitle = isPO ? 'طلب شراء' : 'فاتورة مورد';
  const docNo = isPO ? formData.orderNo : formData.invoiceNo;

  return (
    <div style={{ direction: 'rtl', minHeight: '100vh', background: '#f5f5f5', padding: '20px', fontFamily: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }}>
      <div style={{ maxWidth: '1200px', margin: '0 auto' }}>
        
        {/* Header */}
        <div style={{ background: 'white', padding: '30px', marginBottom: '20px', borderRadius: '8px', textAlign: 'center', boxShadow: '0 2px 8px rgba(0,0,0,0.1)', borderTop: '4px solid #ff6500' }}>
          <h1 style={{ color: '#003b45', fontSize: '28px', margin: '0 0 10px 0', fontWeight: 700 }}>منصة Tager</h1>
          <p style={{ color: '#666', margin: 0, fontSize: '14px' }}>نظام إدارة طلبات الشراء والفواتير</p>
        </div>

        {/* Tabs */}
        <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', marginBottom: '20px', flexWrap: 'wrap' }}>
          <button 
            onClick={() => setActiveTab('po')}
            style={{
              padding: '10px 20px',
              border: activeTab === 'po' ? 'none' : '2px solid #ddd',
              background: activeTab === 'po' ? '#003b45' : 'white',
              color: activeTab === 'po' ? 'white' : '#003b45',
              borderRadius: '6px',
              cursor: 'pointer',
              fontWeight: 600,
              fontSize: '14px',
              transition: 'all 0.3s'
            }}
          >
            طلب شراء
          </button>
          <button 
            onClick={() => setActiveTab('invoice')}
            style={{
              padding: '10px 20px',
              border: activeTab === 'invoice' ? 'none' : '2px solid #ddd',
              background: activeTab === 'invoice' ? '#003b45' : 'white',
              color: activeTab === 'invoice' ? 'white' : '#003b45',
              borderRadius: '6px',
              cursor: 'pointer',
              fontWeight: 600,
              fontSize: '14px',
              transition: 'all 0.3s'
            }}
          >
            فاتورة مورد
          </button>
        </div>

        {/* Controls */}
        <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', marginBottom: '20px', flexWrap: 'wrap' }}>
          <button 
            onClick={() => setShowForm(!showForm)}
            style={{
              padding: '10px 20px',
              background: '#003b45',
              color: 'white',
              border: 'none',
              borderRadius: '6px',
              cursor: 'pointer',
              fontWeight: 600,
              fontSize: '14px'
            }}
          >
            {showForm ? 'إخفاء النموذج' : 'عرض النموذج'}
          </button>
          <button 
            onClick={() => window.print()}
            style={{
              padding: '10px 20px',
              background: 'white',
              color: '#003b45',
              border: '2px solid #003b45',
              borderRadius: '6px',
              cursor: 'pointer',
              fontWeight: 600,
              fontSize: '14px'
            }}
          >
            طباعة / PDF
          </button>
        </div>

        {/* Form */}
        {showForm && (
          <div style={{ background: 'white', padding: '20px', marginBottom: '20px', borderRadius: '8px', boxShadow: '0 2px 8px rgba(0,0,0,0.1)' }}>
            <h3 style={{ color: '#003b45', marginTop: 0 }}>تعديل البيانات</h3>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '15px' }}>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>رقم الطلب</label>
                <input type="text" name="orderNo" value={formData.orderNo} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>رقم الفاتورة</label>
                <input type="text" name="invoiceNo" value={formData.invoiceNo} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>التاريخ</label>
                <input type="text" name="date" value={formData.date} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>العميل</label>
                <input type="text" name="customer" value={formData.customer} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>المورد</label>
                <input type="text" name="supplier" value={formData.supplier} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
              <div>
                <label style={{ display: 'block', fontWeight: 600, marginBottom: '5px', color: '#003b45', fontSize: '12px' }}>المحافظة</label>
                <input type="text" name="governorate" value={formData.governorate} onChange={handleInputChange} style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }} />
              </div>
            </div>
          </div>
        )}

        {/* Document */}
        <div style={{ background: 'white', padding: '40px', borderRadius: '8px', position: 'relative', overflow: 'hidden', boxShadow: '0 10px 28px rgba(0,0,0,0.08)' }}>
          
          {/* Decorative header */}
          <div style={{ position: 'absolute', top: 0, left: 0, width: '220px', height: '160px', background: 'linear-gradient(135deg, rgba(0,59,69,0.95) 0%, rgba(0,87,100,0.85) 100%)', clipPath: 'polygon(0 0, 100% 0, 0 100%)', zIndex: 0 }} />
          <div style={{ position: 'absolute', top: 0, left: '190px', width: '8px', height: '200px', background: '#ff6500', transform: 'rotate(45deg)', zIndex: 1 }} />

          {/* Document Header */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '30px', position: 'relative', zIndex: 2 }}>
            <div>
              <h2 style={{ color: '#003b45', fontSize: '26px', margin: '0 0 8px 0', fontWeight: 700 }}>{docTitle}</h2>
              <p style={{ color: '#ff6500', margin: 0, fontSize: '12px', fontWeight: 600 }}>تفاصيل {isPO ? 'الطلب' : 'الفاتورة'} قبل القائورة</p>
            </div>
            <div style={{ width: '50px', height: '50px', background: 'linear-gradient(135deg, #ff6500, #ff8a2a)', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <span style={{ color: 'white', fontSize: '24px', fontWeight: 700 }}>✓</span>
            </div>
          </div>

          {/* Info Grid */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '30px', padding: '20px', background: '#f9f9f9', borderRadius: '6px' }}>
            <div>
              <label style={{ display: 'block', color: '#666', fontSize: '12px', fontWeight: 600, marginBottom: '8px' }}>التاريخ</label>
              <div style={{ padding: '10px 12px', background: 'white', borderRadius: '6px', border: '1px solid #ddd', borderRight: '3px solid #ff6500' }}>{formData.date}</div>
            </div>
            <div>
              <label style={{ display: 'block', color: '#666', fontSize: '12px', fontWeight: 600, marginBottom: '8px' }}>رقم {isPO ? 'الطلب' : 'الفاتورة'}</label>
              <div style={{ padding: '10px 12px', background: 'white', borderRadius: '6px', border: '1px solid #ddd', borderRight: '3px solid #ff6500', color: '#003b45', fontWeight: 700, fontSize: '14px' }}>{docNo}</div>
            </div>
          </div>

          {/* Details Grid */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: '15px', marginBottom: '30px' }}>
            {[
              { label: 'المنصة', value: formData.platform },
              { label: 'العميل', value: formData.customer },
              { label: 'المورد', value: formData.supplier },
              { label: 'طريقة الدفع', value: formData.paymentMethod },
              { label: 'المركز', value: formData.center },
              { label: 'المحافظة', value: formData.governorate },
              { label: 'العملة', value: formData.currency },
              { label: 'الحالة', value: formData.status },
            ].map((item, idx) => (
              <div key={idx}>
                <label style={{ display: 'block', color: '#999', fontSize: '11px', fontWeight: 700, marginBottom: '8px', textTransform: 'uppercase' }}>{item.label}</label>
                <div style={{ padding: '10px 12px', background: '#f9f9f9', borderRadius: '6px', borderRight: '3px solid #ff6500', fontSize: '13px', color: '#003b45' }}>{item.value}</div>
              </div>
            ))}
          </div>

          {/* Items Table */}
          <div style={{ marginBottom: '30px', borderRadius: '8px', overflow: 'hidden' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ background: 'linear-gradient(135deg, #003b45, #005764)', color: 'white' }}>
                  <th style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontSize: '13px' }}>الصنف</th>
                  <th style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontSize: '13px' }}>نوع السعر</th>
                  <th style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontSize: '13px' }}>الكمية</th>
                  <th style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontSize: '13px' }}>سعر الوحدة</th>
                  <th style={{ padding: '12px', textAlign: 'right', fontWeight: 600, fontSize: '13px' }}>الإجمالي</th>
                </tr>
              </thead>
              <tbody>
                {formData.items.map((item, idx) => (
                  <tr key={idx} style={{ borderBottom: '1px solid #ddd' }}>
                    <td style={{ padding: '12px', fontSize: '13px', color: '#003b45', fontWeight: 600 }}>{item.name}</td>
                    <td style={{ padding: '12px', fontSize: '13px' }}>{item.priceType}</td>
                    <td style={{ padding: '12px', fontSize: '13px' }}>{item.quantity}</td>
                    <td style={{ padding: '12px', fontSize: '13px' }}>{formData.currency} {formatNumber(item.unitPrice)}</td>
                    <td style={{ padding: '12px', fontSize: '13px' }}>{formData.currency} {formatNumber(item.total)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Totals */}
          <div style={{ background: 'linear-gradient(135deg, rgba(0,59,69,0.05) 0%, rgba(255,101,0,0.05) 100%)', padding: '25px', borderRadius: '8px', marginBottom: '30px' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '15px', marginBottom: '20px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 15px', background: 'white', borderRadius: '4px' }}>
                <span style={{ fontWeight: 600, color: '#666', fontSize: '13px' }}>إجمالي المنتجات</span>
                <span style={{ color: '#003b45', fontWeight: 700, fontSize: '13px' }}>{formData.currency} {formatNumber(formData.totals.products)}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 15px', background: 'white', borderRadius: '4px' }}>
                <span style={{ fontWeight: 600, color: '#666', fontSize: '13px' }}>الخصم</span>
                <span style={{ color: '#003b45', fontWeight: 700, fontSize: '13px' }}>{formData.currency} {formatNumber(formData.totals.discount)}</span>
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 15px', background: 'white', borderRadius: '4px' }}>
                <span style={{ fontWeight: 600, color: '#666', fontSize: '13px' }}>الضريبة</span>
                <span style={{ color: '#003b45', fontWeight: 700, fontSize: '13px' }}>{formData.currency} {formatNumber(formData.totals.tax)}</span>
              </div>
            </div>
            <div style={{ background: 'linear-gradient(135deg, #003b45, #005764)', color: 'white', padding: '25px', borderRadius: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div style={{ fontWeight: 600, fontSize: '15px' }}>الإجمالي النهائي</div>
              <div style={{ fontSize: '26px', fontWeight: 700 }}>{formData.currency} {formatNumber(formData.totals.grandTotal)}</div>
            </div>
          </div>

          {/* Signatory */}
          <div style={{ marginTop: '40px', paddingTop: '30px', borderTop: '2px solid #ddd', textAlign: 'center' }}>
            <div style={{ display: 'inline-block', width: '60px', height: '60px', background: 'linear-gradient(135deg, #ff6500, #ff8a2a)', borderRadius: '50%', color: 'white', fontSize: '32px', lineHeight: '60px', marginBottom: '12px', fontWeight: 700, boxShadow: '0 4px 12px rgba(255,101,0,0.3)' }}>✓</div>
            <div style={{ fontWeight: 700, color: '#003b45', fontSize: '14px', marginBottom: '4px' }}>{formData.signatoryName}</div>
            <div style={{ color: '#666', fontSize: '12px', marginBottom: '2px' }}>{formData.signatoryTitle}</div>
            <div style={{ color: '#999', fontSize: '11px' }}>{formData.signatorySub}</div>
          </div>
        </div>
      </div>

      {/* Print Styles */}
      <style>{`
        @media print {
          button { display: none !important; }
          .form-container { display: none !important; }
          body { margin: 0; padding: 0; }
        }
      `}</style>
    </div>
  );
};

export default TagerDocument;
