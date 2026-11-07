/** Offline reproducible build. Sources fetched/reviewed separately; never contacts npm/GitHub. */
import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {resolve} from 'node:path';
import {createHash} from 'node:crypto';
const root=resolve(import.meta.dir,'../..');
const vendor=resolve(root,'tools/vendor/facet-search');
const out=resolve(root,'plugins/wpfasty-builder/worker/catalog');
const lock=JSON.parse(readFileSync(resolve(vendor,'provenance.json'),'utf8'));
const sha=(b:string|Buffer)=>createHash('sha256').update(b).digest('hex');
const check=process.argv.includes('--check');
const files:Record<string,string>={};
const emit=(name:string,body:string|Buffer)=>{files[name]=sha(body);const path=resolve(out,name);if(check){if(!readFileSync(path).equals(Buffer.from(body)))throw Error('Local catalog drift: '+name);}else writeFileSync(path,body);};
mkdirSync(resolve(out,'engine'),{recursive:true});
const transpiler=new Bun.Transpiler({loader:'ts',target:'node',minifyWhitespace:false});
for(const name of ['model','query','search']){
 const source=readFileSync(resolve(vendor,name+'.ts'),'utf8');
 if(sha(source)!==lock.sources[name+'.ts'])throw Error('Vendored source drift: '+name);
 const js=transpiler.transformSync(source).replace(/from "\.\/model"/g,'from "./model.mjs"');
 emit('engine/'+name+'.mjs','// Generated offline by tools/builder/local-search-build.ts; MIT CodeGen-X. Do not edit.\n'+js);
}
const license=readFileSync(resolve(vendor,'LICENSE'));if(sha(license)!==lock.sources.LICENSE)throw Error('License drift');emit('LICENSE',license);
const stock=readFileSync(resolve(out,'stock.v0.json'));
if(stock.length>3*1024*1024||sha(stock)!==lock.stockSha256)throw Error('Stock snapshot drift/oversize');
files['stock.v0.json']=sha(stock);
const index=JSON.parse(stock.toString());
const {digest,...body}=index;
if(digest!==lock.stockDigest||digest!=='sha256:'+sha(JSON.stringify(body)))throw Error('Stock canonical digest mismatch');
const manifest={schema:'wpfasty-local-search/1',engine:'@ui8kit/facet-search@'+lock.packageVersion,source:{repository:lock.repository,commit:lock.commit,stockPath:lock.stockPath},license:lock.license,copyright:lock.copyright,stockVersion:index.version,stockDigest:digest,stockBytes:stock.length,counts:index.counts,files};
const serialized=JSON.stringify(manifest,null,2)+'\n';
if(check){if(readFileSync(resolve(out,'manifest.json'),'utf8')!==serialized)throw Error('Local catalog manifest drift');}else writeFileSync(resolve(out,'manifest.json'),serialized);
console.log('Local search: pinned MIT sources, canonical digest, generated ESM and '+stock.length+' snapshot bytes verified (offline).');
