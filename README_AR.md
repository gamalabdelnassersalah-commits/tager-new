# Tager Final Production V7 — Static Overwrite Safe

هذه النسخة مصممة لحل خطأ Vercel:

`Couldn't find any pages or app directory`

النسخة لا تستخدم Next.js في البناء، لذلك لن يبحث Vercel عن مجلد `app` أو `pages`. المنصة تعمل كصفحة إنتاج ثابتة مرتبطة بمتغيرات Supabase في Vercel.

## الاستخدام
1. فك الضغط.
2. ارفع كل الملفات الموجودة داخل المجلد إلى GitHub فوق الملفات القديمة.
3. وافق على الاستبدال.
4. Commit changes.
5. في Supabase شغل `schema_final.sql`.
6. انتظر Vercel Deploy.
7. افتح `/setup` لإنشاء أول حساب إدارة حقيقي.

## مهم
لا يوجد موردون تجريبيون ولا منتجات تجريبية.
