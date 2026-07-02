# إصلاح Build في Vercel

ارفع هذه الملفات الثلاثة في جذر GitHub repo لاستبدال package.json القديم الذي كان يشغل Next.js.

بعد الرفع يجب أن يظهر في Vercel:

```text
> tager-static-production-v7@7.0.0 build
> node build-static.mjs
Tager static build ready. No Next.js build is used.
```

ولا يجب أن يظهر:

```text
next build
Couldn't find any pages or app directory
```
