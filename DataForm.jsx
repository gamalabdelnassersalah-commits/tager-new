import React from 'react';

const DataForm = ({ formData, onInputChange, onTotalChange, onItemChange }) => {
  const formSections = [
    {
      title: 'معلومات الطلب',
      fields: [
        { name: 'orderNo', label: 'رقم الطلب', type: 'text' },
        { name: 'invoiceNo', label: 'رقم الفاتورة', type: 'text' },
        { name: 'date', label: 'التاريخ', type: 'text' },
        { name: 'platform', label: 'المنصة', type: 'text' },
      ]
    },
    {
      title: 'معلومات الأطراف',
      fields: [
        { name: 'customer', label: 'العميل', type: 'text' },
        { name: 'supplier', label: 'المورد', type: 'text' },
        { name: 'paymentMethod', label: 'طريقة الدفع', type: 'text' },
        { name: 'status', label: 'الحالة', type: 'text' },
      ]
    },
    {
      title: 'معلومات الموقع',
      fields: [
        { name: 'governorate', label: 'المحافظة', type: 'text' },
        { name: 'center', label: 'المركز', type: 'text' },
        { name: 'currency', label: 'العملة', type: 'text' },
      ]
    },
    {
      title: 'معلومات المستخدم',
      fields: [
        { name: 'signatoryName', label: 'اسم الموقّع', type: 'text' },
        { name: 'signatoryTitle', label: 'اسم الموقّع (إنجليزي)', type: 'text' },
        { name: 'signatorySub', label: 'الصفة', type: 'text' },
      ]
    },
    {
      title: 'الإجماليات',
      fields: [
        { name: 'products', label: 'إجمالي المنتجات', type: 'number', onChange: onTotalChange },
        { name: 'discount', label: 'الخصم', type: 'number', onChange: onTotalChange },
        { name: 'tax', label: 'الضريبة', type: 'number', onChange: onTotalChange },
        { name: 'grandTotal', label: 'الإجمالي النهائي', type: 'number', onChange: onTotalChange },
      ]
    }
  ];

  return (
    <div className="data-form">
      <style>{`
        .data-form {
          background: white;
          border-radius: 8px;
          padding: 20px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-section {
          margin-bottom: 25px;
        }
        
        .form-section:last-child {
          margin-bottom: 0;
        }
        
        .section-title {
          font-weight: 700;
          color: #003b45;
          margin: 0 0 15px 0;
          font-size: 14px;
          text-transform: uppercase;
          padding-bottom: 10px;
          border-bottom: 2px solid #ff6500;
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
          margin-bottom: 6px;
          color: #003b45;
          font-size: 12px;
        }
        
        .form-group input {
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 13px;
          background: white;
          transition: all 0.3s;
        }
        
        .form-group input:focus {
          outline: none;
          border-color: #ff6500;
          box-shadow: 0 0 0 2px rgba(255,101,0,0.1);
        }
      `}</style>

      {formSections.map((section, idx) => (
        <div key={idx} className="form-section">
          <h3 className="section-title">{section.title}</h3>
          <div className="form-grid">
            {section.fields.map((field) => {
              let value;
              if (field.name === 'products' || field.name === 'discount' || field.name === 'tax' || field.name === 'grandTotal') {
                value = formData.totals[field.name];
              } else {
                value = formData[field.name];
              }

              return (
                <div key={field.name} className="form-group">
                  <label>{field.label}</label>
                  <input
                    type={field.type}
                    name={field.name}
                    value={value}
                    onChange={field.onChange || onInputChange}
                  />
                </div>
              );
            })}
          </div>
        </div>
      ))}
    </div>
  );
};

export default DataForm;
