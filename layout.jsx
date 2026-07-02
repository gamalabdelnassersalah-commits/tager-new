import './globals.css';
export const metadata = {
  title: 'Tager Marketplace',
  description: 'منصة تاجر للجملة والقطاعي وجملة الجملة'
};
export default function RootLayout({ children }){
  return <html lang="ar" dir="rtl"><body>{children}</body></html>;
}
