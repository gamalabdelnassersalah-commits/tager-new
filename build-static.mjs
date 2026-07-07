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
const config = `window.TAGER_SUPABASE_URL=${JSON.stringify(process.env.NEXT_PUBLIC_SUPABASE_URL || process.env.SUPABASE_URL || '')};
window.TAGER_SUPABASE_ANON_KEY=${JSON.stringify(process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || process.env.SUPABASE_ANON_KEY || '')};
window.TAGER_BUILD_TIME=${JSON.stringify(new Date().toISOString())};
`;
fs.writeFileSync(path.join(outDir, 'config.js'), config);
const copyFiles = ['index.html','tager-logo.png','logo.svg','style.css','styles.css','globals.css','inline.js','app.js','config.js','schema_final.sql',];
for (const file of copyFiles) {
  const src = path.join(root, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(outDir, file));
}
for (const file of fs.readdirSync(root)) {
  if (/^product-.*\.svg$/.test(file)) fs.copyFileSync(path.join(root,file), path.join(outDir,file));
}
for (const dir of ['assets','images','img','static']) {
  const src = path.join(root, dir);
  if (fs.existsSync(src) && fs.statSync(src).isDirectory()) fs.cpSync(src, path.join(outDir, dir), { recursive: true });
}
if (!fs.existsSync(path.join(outDir, 'index.html'))) {
  console.error('Build failed: public/index.html was not created');
  process.exit(1);
}
console.log('Tager build completed');
console.log('Output Directory: public');
