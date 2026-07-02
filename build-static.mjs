import fs from 'fs';

const required = ['index.html'];
for (const file of required) {
  if (!fs.existsSync(file)) {
    console.error(`Missing required file: ${file}`);
    process.exit(1);
  }
}
console.log('Tager static build ready. No Next.js build is used.');
