'use client';

import React, { useState, useRef } from 'react';
import TagerDocument from '@/components/TagerDocument';
import {
  exportToJSON,
  importFromJSON,
  validateDocumentData,
  DEFAULT_DOCUMENT_DATA,
  cloneData,
  formatNumber
} from '@/utils/tager-document-utils';

/**
 * Advanced example of TagerDocument usage with all features
 */
export default function AdvancedDocumentExample() {
  const [formData, setFormData] = useState(cloneData(DEFAULT_DOCUMENT_DATA));
  const [showForm, setShowForm] = useState(false);
  const [activeTab, setActiveTab] = useState('po');
  const [message, setMessage] = useState('');
  const fileInputRef = useRef(null);

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

  const handleItemChange = (index, field, value) => {
    const newItems = [...formData.items];
    newItems[index] = {
      ...newItems[index],
      [field]: field === 'quantity' || field === 'unitPrice' 
        ? parseFloat(value) || 0 
        : value
    };

    if (field === 'quantity' || field === 'unitPrice') {
      newItems[index].total = newItems[index].quantity * newItems[index].unitPrice;
    }

    setFormData(prev => ({
      ...prev,
      items: newItems
    }));
  };

  const handleAddItem = () => {
    setFormData(prev => ({
      ...prev,
      items: [
        ...prev.items,
        { name: '', priceType: 'سعر الشراء', quantity: 1, unitPrice: 0, total: 0 }
      ]
    }));
    showMessage('تم إضافة بند جديد');
  };

  const handleRemoveItem = (index) => {
    setFormData(prev => ({
      ...prev,
      items: prev.items.filter((_, i) => i !== index)
    }));
    showMessage('تم حذف البند');
  };

  const handleExport = () => {
    const { isValid, errors } = validateDocumentData(formData);
    
    if (!isValid) {
      showMessage(`أخطاء: ${errors.join(', ')}`, 'error');
      return;
    }

    exportToJSON(formData, `tager-${activeTab}-${Date.now()}.json`);
    showMessage('تم تصدير البيانات بنجاح');
  };

  const handleImport = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    try {
      const importedData = await importFromJSON(file);
      setFormData(importedData);
      showMessage('تم استيراد البيانات بنجاح');
    } catch (error) {
      showMessage('فشل استيراد البيانات. تأكد من صيغة JSON', 'error');
    }
  };

  const handleReset = () => {
    if (confirm('هل تريد إعادة تعيين البيانات للقيم الافتراضية؟')) {
      setFormData(cloneData(DEFAULT_DOCUMENT_DATA));
      showMessage('تم إعادة تعيين البيانات');
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const showMessage = (msg, type = 'success') => {
    setMessage(msg);
    setTimeout(() => setMessage(''), 3000);
  };

  const getTotalItemsPrice = () => {
    return formData.items.reduce((sum, item) => sum + (item.total || 0), 0);
  };

  return (
    <div className="advanced-example">
      <style>{`
        .advanced-example {
          background: #f5f5f5;
          min-height: 100vh;
          padding: 20px;
          direction: rtl;
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .example-header {
          background: linear-gradient(135deg, #003b45, #005764);
          color: white;
          padding: 30px;
          border-radius: 12px;
          margin-bottom: 30px;
          text-align: center;
        }

        .example-header h1 {
          margin: 0 0 10px 0;
          font-size: 28px;
        }

        .example-header p {
          margin: 0;
          font-size: 14px;
          opacity: 0.9;
        }

        .toolbar {
          background: white;
          padding: 20px;
          border-radius: 8px;
          margin-bottom: 20px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          justify-content: center;
        }

        .toolbar button,
        .toolbar label {
          padding: 10px 16px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 600;
          transition: all 0.3s;
          font-size: 12px;
        }

        .btn-primary {
          background: #003b45;
          color: white;
          box-shadow: 0 2px 8px rgba(0,59,69,0.2);
        }

        .btn-primary:hover {
          background: #ff6500;
          transform: translateY(-2px);
        }

        .btn-secondary {
          background: #f5f5f5;
          color: #003b45;
          border: 2px solid #003b45;
        }

        .btn-secondary:hover {
          background: #003b45;
          color: white;
        }

        .btn-danger {
          background: #ff6b6b;
          color: white;
        }

        .btn-danger:hover {
          background: #ff5252;
        }

        .message {
          position: fixed;
          top: 20px;
          right: 20px;
          padding: 12px 20px;
          border-radius: 6px;
          background: #4caf50;
          color: white;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          z-index: 9999;
          animation: slideIn 0.3s ease;
        }

        .message.error {
          background: #ff6b6b;
        }

        @keyframes slideIn {
          from {
            transform: translateX(100%);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }

        .tabs {
          display: flex;
          gap: 10px;
          margin-bottom: 20px;
          justify-content: center;
        }

        .tab {
          padding: 10px 20px;
          background: white;
          border: 2px solid #ddd;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 600;
          transition: all 0.3s;
        }

        .tab.active {
          background: #003b45;
          color: white;
          border-color: #003b45;
        }

        .stats {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 15px;
          margin: 20px 0;
          background: white;
          padding: 20px;
          border-radius: 8px;
        }

        .stat-item {
          text-align: center;
          padding: 15px;
          background: #f9f9f9;
          border-radius: 6px;
          border-right: 3px solid #ff6500;
        }

        .stat-label {
          color: #666;
          font-size: 12px;
          font-weight: 600;
          margin-bottom: 5px;
        }

        .stat-value {
          color: #003b45;
          font-size: 18px;
          font-weight: 700;
        }

        .items-list {
          background: white;
          padding: 20px;
          border-radius: 8px;
          margin-bottom: 20px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .items-list h3 {
          color: #003b45;
          margin-top: 0;
        }

        .item-row {
          display: grid;
          grid-template-columns: 2fr 1fr 1fr 1fr 100px;
          gap: 10px;
          padding: 10px;
          border-bottom: 1px solid #ddd;
          align-items: center;
        }

        .item-row:last-child {
          border-bottom: none;
        }

        .item-row input {
          padding: 6px 8px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 12px;
        }

        .remove-btn {
          background: #ff6b6b;
          color: white;
          border: none;
          padding: 6px 10px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 12px;
        }

        .remove-btn:hover {
          background: #ff5252;
        }

        input[type="file"] {
          display: none;
        }

        .form-section {
          background: white;
          padding: 20px;
          border-radius: 8px;
          margin-bottom: 20px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-section h3 {
          color: #003b45;
          margin-top: 0;
          border-bottom: 2px solid #ff6500;
          padding-bottom: 10px;
        }

        .form-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 15px;
        }

        .form-group {
          display: flex;
          flex-direction: column;
        }

        .form-group label {
          font-weight: 600;
          color: #003b45;
          font-size: 12px;
          margin-bottom: 5px;
        }

        .form-group input {
          padding: 8px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 12px;
        }

        .form-group input:focus {
          outline: none;
          border-color: #ff6500;
          box-shadow: 0 0 0 2px rgba(255,101,0,0.1);
        }
      `}</style>

      {message && (
        <div className={`message ${message.includes('فشل') ? 'error' : ''}`}>
          {message}
        </div>
      )}

      <div className="example-header">
        <h1>منصة Tager المتقدمة</h1>
        <p>نظام متكامل لإدارة الفواتير والطلبات مع جميع الميزات</p>
      </div>

      <div className="toolbar">
        <button className="btn-primary" onClick={() => setShowForm(!showForm)}>
          {showForm ? 'إخفاء النموذج' : 'عرض النموذج'}
        </button>
        <button className="btn-primary" onClick={handlePrint}>
          🖨️ طباعة / حفظ PDF
        </button>
        <button className="btn-primary" onClick={handleExport}>
          📥 تصدير البيانات
        </button>
        <label className="btn-primary" style={{ cursor: 'pointer' }}>
          📤 استيراد البيانات
          <input
            ref={fileInputRef}
            type="file"
            accept=".json"
            onChange={handleImport}
          />
        </label>
        <button className="btn-danger" onClick={handleReset}>
          🔄 إعادة تعيين
        </button>
      </div>

      <div className="tabs">
        <div className={`tab ${activeTab === 'po' ? 'active' : ''}`} onClick={() => setActiveTab('po')}>
          طلب شراء
        </div>
        <div className={`tab ${activeTab === 'invoice' ? 'active' : ''}`} onClick={() => setActiveTab('invoice')}>
          فاتورة مورد
        </div>
      </div>

      <div className="stats">
        <div className="stat-item">
          <div className="stat-label">عدد البنود</div>
          <div className="stat-value">{formData.items.length}</div>
        </div>
        <div className="stat-item">
          <div className="stat-label">إجمالي المنتجات</div>
          <div className="stat-value">{formatNumber(getTotalItemsPrice())}</div>
        </div>
        <div className="stat-item">
          <div className="stat-label">الإجمالي النهائي</div>
          <div className="stat-value">{formatNumber(formData.totals.grandTotal)}</div>
        </div>
      </div>

      {showForm && (
        <>
          <div className="form-section">
            <h3>معلومات الطلب</h3>
            <div className="form-grid">
              <div className="form-group">
                <label>رقم الطلب</label>
                <input type="text" name="orderNo" value={formData.orderNo} onChange={handleInputChange} />
              </div>
              <div className="form-group">
                <label>رقم الفاتورة</label>
                <input type="text" name="invoiceNo" value={formData.invoiceNo} onChange={handleInputChange} />
              </div>
              <div className="form-group">
                <label>التاريخ</label>
                <input type="text" name="date" value={formData.date} onChange={handleInputChange} />
              </div>
              <div className="form-group">
                <label>المنصة</label>
                <input type="text" name="platform" value={formData.platform} onChange={handleInputChange} />
              </div>
              <div className="form-group">
                <label>العميل</label>
                <input type="text" name="customer" value={formData.customer} onChange={handleInputChange} />
              </div>
              <div className="form-group">
                <label>المورد</label>
                <input type="text" name="supplier" value={formData.supplier} onChange={handleInputChange} />
              </div>
            </div>
          </div>

          <div className="items-list">
            <h3>البنود</h3>
            {formData.items.map((item, idx) => (
              <div key={idx} className="item-row">
                <input
                  type="text"
                  placeholder="اسم البند"
                  value={item.name}
                  onChange={(e) => handleItemChange(idx, 'name', e.target.value)}
                />
                <input
                  type="number"
                  placeholder="الكمية"
                  value={item.quantity}
                  onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                />
                <input
                  type="number"
                  placeholder="السعر"
                  value={item.unitPrice}
                  onChange={(e) => handleItemChange(idx, 'unitPrice', e.target.value)}
                />
                <input
                  type="number"
                  placeholder="الإجمالي"
                  value={item.total}
                  disabled
                />
                <button className="remove-btn" onClick={() => handleRemoveItem(idx)}>
                  حذف
                </button>
              </div>
            ))}
            <button className="btn-primary" style={{ marginTop: '10px', width: '100%' }} onClick={handleAddItem}>
              ➕ إضافة بند جديد
            </button>
          </div>

          <div className="form-section">
            <h3>الإجماليات</h3>
            <div className="form-grid">
              <div className="form-group">
                <label>إجمالي المنتجات</label>
                <input type="number" name="products" value={formData.totals.products} onChange={handleTotalChange} />
              </div>
              <div className="form-group">
                <label>الخصم</label>
                <input type="number" name="discount" value={formData.totals.discount} onChange={handleTotalChange} />
              </div>
              <div className="form-group">
                <label>الضريبة</label>
                <input type="number" name="tax" value={formData.totals.tax} onChange={handleTotalChange} />
              </div>
              <div className="form-group">
                <label>الإجمالي النهائي</label>
                <input type="number" name="grandTotal" value={formData.totals.grandTotal} onChange={handleTotalChange} />
              </div>
            </div>
          </div>
        </>
      )}

      <TagerDocument initialData={formData} activeTab={activeTab} />
    </div>
  );
}
