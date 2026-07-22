# Tager Platform - Supabase Integration

## الملفات الجاهزة للرفع:

### ملفات المشروع الأساسية:
- **index.html.js** - الملف الرئيسي (يحتاج تحديث)
- **env.js** - بيانات Supabase
- **config.js** - إعدادات المشروع
- **platform.html** - صفحة التشغيل (إن وجدت)
- **build-static.mjs** - script البناء (إن وجد)

### ملفات Supabase الجديدة:
- **supabase-integration-complete.js** - نظام Supabase متكامل
- **schema-complete.sql** - جداول Supabase

## خطوات الرفع:

```bash
# 1. انسخ كل الملفات إلى مجلد المشروع
# 2. حدّث env.js ببيانات Supabase الخاصة بك:
window.TAGER_ENV = {
  SUPABASE_URL: "https://YOUR_PROJECT.supabase.co",
  SUPABASE_ANON_KEY: "sb_YOUR_KEY"
};

# 3. شغّل schema-complete.sql على Supabase
# 4. حدّث index.html.js (احذف localStorage، استخدم Supabase)
# 5. رفع على GitHub:
git add .
git commit -m "feat: Supabase integration - zero localStorage"
git push
```

## ملفات إضافية (توثيق):

اطلب الملفات التوثيقية:
- SETUP_SUPABASE_AR.md
- INTEGRATION_GUIDE.md
- FINAL_CHECKLIST.md
- README_SUPABASE.md
