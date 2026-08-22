// Decode one design master (.dc.html) into its raw template HTML.
//
// ⚠️ RENAMED .js -> .cjs (2026-08-23), and this is a defect the byte-identical preservation commit could not
// have caught: the repo's package.json declares "type": "module", so a `.js` file here is treated as ESM and
// `require` is undefined. In the scratchpad there was no package.json, so it ran as CommonJS. ⚠️ THE FILE
// BECAME BROKEN BY BEING COMMITTED — nothing about its contents changed. Only running it revealed that, which
// is the whole argument for proving a toolchain rather than preserving it.
//
// Usage: node tools/marketing/decode.cjs "docs/design/front/Vyrobel Home.dc.html" tools/marketing/core/home.html
const fs=require('fs');
const src=fs.readFileSync(process.argv[2],'utf8');
const m=src.match(/<script type="__bundler\/template"[^>]*>([\s\S]*?)<\/script>/);
if(!m){console.error('NO __bundler/template block');process.exit(2);}
let out;
try{ out=JSON.parse(m[1]); }catch(e){ console.error('JSON.parse failed: '+e.message); process.exit(3); }
if(typeof out!=='string'){ out=JSON.stringify(out,null,2); }
fs.writeFileSync(process.argv[3],out);   // write file; do NOT also shell-redirect stdout
console.error('decoded '+process.argv[2]+' -> '+process.argv[3]+' ('+out.length+' chars)');
