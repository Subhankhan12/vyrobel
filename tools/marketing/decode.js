const fs=require('fs');
const src=fs.readFileSync(process.argv[2],'utf8');
const m=src.match(/<script type="__bundler\/template"[^>]*>([\s\S]*?)<\/script>/);
if(!m){console.error('NO __bundler/template block');process.exit(2);}
let out;
try{ out=JSON.parse(m[1]); }catch(e){ console.error('JSON.parse failed: '+e.message); process.exit(3); }
if(typeof out!=='string'){ out=JSON.stringify(out,null,2); }
fs.writeFileSync(process.argv[3],out);   // write file; do NOT also shell-redirect stdout
console.error('decoded '+process.argv[2]+' -> '+process.argv[3]+' ('+out.length+' chars)');
