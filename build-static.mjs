import fs from 'fs';

const required = ['index.html', 'package.json'];
for (const file of required) {
  if (!fs.existsSync(file)) {
    console.error(`Missing required file: ${file}`);
    process.exit(1);
  }
}
const url = process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || '';
const key = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || process.env.SUPABASE_ANON_KEY || '';
const content = `window.TAGER_SUPABASE_URL=${JSON.stringify(url)};\nwindow.TAGER_SUPABASE_ANON_KEY=${JSON.stringify(key)};\nwindow.TAGER_BUILD_TIME=${JSON.stringify(new Date().toISOString())};\n`;
fs.writeFileSync('config.js', content);
console.log('Tager V8 static build OK - no Next.js');
console.log('Supabase URL:', url ? 'loaded' : 'missing');
console.log('Supabase key:', key ? 'loaded' : 'missing');
