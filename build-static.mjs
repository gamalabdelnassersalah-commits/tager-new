import fs from 'fs';
import path from 'path';
const root=process.cwd();
const out=path.join(root,'public');
fs.rmSync(out,{recursive:true,force:true});
fs.mkdirSync(out,{recursive:true});
for(const item of ['index.html','style.css','app.js','manifest.webmanifest','favicon.png']) fs.copyFileSync(path.join(root,item),path.join(out,item));
fs.cpSync(path.join(root,'assets'),path.join(out,'assets'),{recursive:true});
console.log('public folder ready');
