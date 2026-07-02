import './globals.css';

export const metadata = {
  title: 'Tager | Trade Supply Connect',
  description: 'منصة تاجر لإدارة الموردين والطلبات والعمولات والتوصيل'
};

export default function RootLayout({ children }) {
  return (
    <html lang="ar" dir="rtl">
      <body>{children}</body>
    </html>
  );
}
