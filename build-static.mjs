import fs from 'fs';
import path from 'path';

const root = process.cwd();
const out = path.join(root, 'public');
const keep = ['index.html', 'style.css', 'app.js', 'supabase-client.js', 'robots.txt', 'manifest.webmanifest'];
fs.rmSync(out, { recursive: true, force: true });
fs.mkdirSync(out, { recursive: true });
fs.mkdirSync(path.join(out, 'assets'), { recursive: true });
for (const file of keep) {
  const src = path.join(root, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(out, file));
}
const assetDir = path.join(root, 'assets');
if (fs.existsSync(assetDir)) {
  for (const asset of fs.readdirSync(assetDir)) fs.copyFileSync(path.join(assetDir, asset), path.join(out, 'assets', asset));
}
const env = {
  SUPABASE_URL: process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || '',
  SUPABASE_ANON_KEY: process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || process.env.SUPABASE_ANON_KEY || ''
};
fs.writeFileSync(path.join(out, 'env.js'), `window.TAGER_ENV=${JSON.stringify(env)};
`);
fs.writeFileSync(path.join(out, '.nojekyll'), '');
console.log('Tager enterprise production release V24 completed - public output ready');
console.log('Supabase URL:', env.SUPABASE_URL ? 'loaded' : 'missing');
console.log('Supabase key:', env.SUPABASE_ANON_KEY ? 'loaded' : 'missing');
