import React, { useMemo } from 'react';

function buildDocumentUrl(type, data) {
  const fileName = type === 'invoice' ? 'invoice.html' : 'purchase-order.html';
  const baseUrl = `/tager-documents/${fileName}`;
  if (!data) return baseUrl;
  return `${baseUrl}?data=${encodeURIComponent(JSON.stringify(data))}`;
}

export default function TagerDocumentFrame({ type = 'invoice', data, title }) {
  const src = useMemo(() => buildDocumentUrl(type, data), [type, data]);
  const label = title || (type === 'invoice' ? 'فاتورة مورد' : 'طلب شراء');

  return (
    <iframe
      title={label}
      src={src}
      style={{
        width: '100%',
        minHeight: '1600px',
        border: 0,
        background: '#e9eef1'
      }}
    />
  );
}

export function TagerDocumentOpenButton({ type = 'invoice', data, children }) {
  const href = buildDocumentUrl(type, data);
  const label = children || (type === 'invoice' ? 'طباعة الفاتورة' : 'طباعة طلب الشراء');

  return (
    <a
      href={href}
      target="_blank"
      rel="noreferrer"
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        minHeight: 40,
        padding: '0 16px',
        borderRadius: 10,
        background: '#003b45',
        color: '#fff',
        textDecoration: 'none',
        fontWeight: 800
      }}
    >
      {label}
    </a>
  );
}
