import fs from 'fs';
import path from 'path';

const root = process.cwd();
const outDir = path.join(root, 'public');
const required = ['index.html', 'app.js', 'styles.css', 'package.json'];
for (const file of required) {
  if (!fs.existsSync(path.join(root, file))) {
    console.error(`Missing required file: ${file}`);
    process.exit(1);
  }
}
fs.rmSync(outDir, { recursive: true, force: true });
fs.mkdirSync(outDir, { recursive: true });
const copyFiles = ['index.html','app.js','styles.css','style.css','globals.css','tager-logo.png','favicon.png','logo.svg','schema_final.sql','manifest.webmanifest','README_AR.md','FINAL_CHECKLIST_AR.md','USER_GUIDE_AR.md'];
for (const file of copyFiles) {
  const src = path.join(root, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(outDir, file));
}
for (const dir of ['assets','images','img','static','templates','docs']) {
  const src = path.join(root, dir);
  if (fs.existsSync(src) && fs.statSync(src).isDirectory()) {
    fs.cpSync(src, path.join(outDir, dir), { recursive: true });
  }
}
if (!fs.existsSync(path.join(outDir, 'index.html'))) {
  console.error('Build failed: public/index.html was not created');
  process.exit(1);
}
console.log('Tager build OK');
console.log('Output Directory: public');
