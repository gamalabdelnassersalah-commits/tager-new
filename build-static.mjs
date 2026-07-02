import fs from 'fs';
import path from 'path';

const root = process.cwd();
const outDir = path.join(root, 'public');

const required = ['index.html', 'package.json'];
for (const file of required) {
  if (!fs.existsSync(path.join(root, file))) {
    console.error(`Missing required file: ${file}`);
    process.exit(1);
  }
}

fs.rmSync(outDir, { recursive: true, force: true });
fs.mkdirSync(outDir, { recursive: true });

const url = process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || '';
const key = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || process.env.SUPABASE_ANON_KEY || '';
const config = `window.TAGER_SUPABASE_URL=${JSON.stringify(url)};\nwindow.TAGER_SUPABASE_ANON_KEY=${JSON.stringify(key)};\nwindow.TAGER_BUILD_TIME=${JSON.stringify(new Date().toISOString())};\n`;
fs.writeFileSync(path.join(outDir, 'config.js'), config);

const copyFiles = [
  'index.html',
  'tager-logo.png',
  'logo.svg',
  'style.css',
  'styles.css',
  'globals.css',
  'favicon.ico'
];

for (const file of copyFiles) {
  const src = path.join(root, file);
  if (fs.existsSync(src)) {
    fs.copyFileSync(src, path.join(outDir, file));
  }
}

for (const dir of ['assets', 'images', 'img', 'static']) {
  const src = path.join(root, dir);
  if (fs.existsSync(src) && fs.statSync(src).isDirectory()) {
    fs.cpSync(src, path.join(outDir, dir), { recursive: true });
  }
}

if (!fs.existsSync(path.join(outDir, 'index.html'))) {
  console.error('Build failed: public/index.html was not created');
  process.exit(1);
}

console.log('Tager V9 static build OK - output directory public created');
console.log('Supabase URL:', url ? 'loaded' : 'missing');
console.log('Supabase key:', key ? 'loaded' : 'missing');
console.log('Output Directory: public');
