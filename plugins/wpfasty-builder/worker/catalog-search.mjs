/** Release-bundled local retrieval; no HTTP, npm, provider, updater or filesystem tools. */
import {readFileSync,statSync} from 'node:fs';
import {createHash} from 'node:crypto';
import {WorkerError} from './core.mjs';
const sha=b=>createHash('sha256').update(b).digest('hex');
const plain=v=>!!v&&typeof v==='object'&&!Array.isArray(v)&&[Object.prototype,null].includes(Object.getPrototypeOf(v));
const own=(v,k)=>Object.prototype.hasOwnProperty.call(v,k);
const badKey=k=>['__proto__','constructor','prototype'].includes(k);
const id=v=>typeof v==='string'&&/^[a-z][a-zA-Z0-9_-]{0,79}$/.test(v)&&!badKey(v);
const text=(v,n)=>typeof v==='string'&&v.length<=n;
const check=(ok,code='invalid_catalog_query')=>{if(!ok)throw new WorkerError(code);};
function safeTree(value,depth=0){
 check(depth<=24);
 if(value&&typeof value==='object'){
  check(Array.isArray(value)||plain(value));
  for(const [key,v] of Object.entries(value)){check(!badKey(key));safeTree(v,depth+1);}
 }
}
export function validateIndex(index){
 check(plain(index)&&index.version==='0'&&Array.isArray(index.facets)&&index.facets.length<=24&&Array.isArray(index.assets)&&index.assets.length<=10000);
 safeTree(index);
 const facets=new Map();
 for(const f of index.facets){
  check(id(f.id)&&!facets.has(f.id)&&Array.isArray(f.values)&&f.values.length<=1000&&plain(f.label)&&text(f.label.en,200));
  const values=new Set();for(const v of f.values){check(id(v.id)&&!values.has(v.id)&&plain(v.label)&&text(v.label.en,200));values.add(v.id);}
  facets.set(f.id,values);
 }
 const ids=new Set();
 for(const a of index.assets){
  check(text(a.id,200)&&!ids.has(a.id)&&text(a.group,120)&&text(a.spec,120)&&text(a.title,500)&&text(a.subtitle,1000)&&Array.isArray(a.fields)&&plain(a.labels));ids.add(a.id);
  check(Array.isArray(a.weak)&&Array.isArray(a.missing)&&Number.isSafeInteger(a.order));
  for(const [f,values] of Object.entries(a.labels))check(facets.has(f)&&Array.isArray(values)&&values.every(v=>v==='*'||facets.get(f).has(v)));
  for(const field of a.fields)check(Number.isFinite(field.w)&&field.w>=0&&field.w<=100&&Array.isArray(field.words)&&field.words.length<=2000&&field.words.every(w=>text(w,4000)));
  if(a.type==='block')check(id(a.preview?.block)&&id(a.variant)&&a.id===`block.${a.preview.block}/${a.variant}`&&a.group===`block.${a.preview.block}`);
 }
 check(Array.isArray(index.sorts)&&index.sorts.every(s=>['relevance','catalog','newest','name','unlabelled'].includes(s.id)));
 return index;
}
/** The optional directory is a test seam, never exposed as a tool/input/HTTP parameter. */
export async function loadCatalog(directory=new URL('./catalog/',import.meta.url)){
 try{
  const read=(name,limit)=>{const url=new URL(name,directory);check(statSync(url).size<=limit);return readFileSync(url);};
  const manifest=JSON.parse(read('manifest.json',16000));
  check(manifest.schema==='wpfasty-local-search/1'&&manifest.license==='MIT'&&plain(manifest.files));
  for(const name of ['stock.v0.json','engine/model.mjs','engine/query.mjs','engine/search.mjs','LICENSE'])check(sha(read(name,name==='stock.v0.json'?3*1024*1024:100000))===manifest.files[name]);
  const index=validateIndex(JSON.parse(read('stock.v0.json',3*1024*1024)));
  const {digest,...body}=index;check(digest===manifest.stockDigest&&digest==='sha256:'+sha(JSON.stringify(body)));
  const engine=await import(new URL('engine/search.mjs',directory).href);
  return {index,manifest,engine,prepared:engine.prepare(index)};
 }catch{throw new WorkerError('catalog_map_unavailable',503);}
}
let bundled;
const bundledCatalog=()=>bundled??=loadCatalog();
const pair=(b,v)=>`${b}/${v}`;
const same=(a,b)=>JSON.stringify(a)===JSON.stringify(b);
const byteCap=value=>{check(Buffer.byteLength(JSON.stringify(value))<=48000,'catalog_detail_too_large');return value;};
export async function catalogSession(context,resource){
 check(plain(context)&&Array.isArray(context.catalog)&&context.catalog.length<=1000,'catalog_context_invalid');
 check(['latte','twig'].includes(context.target),'catalog_target_unsupported');
 const {index,manifest,engine,prepared}=resource??await bundledCatalog();
 const available=new Map(),blocks=new Map();let installed=0;
 for(const b of context.catalog){
  check(plain(b)&&id(b.id)&&!blocks.has(b.id)&&Array.isArray(b.variants)&&b.variants.length<=200,'catalog_context_invalid');blocks.set(b.id,b);
  const variants=new Set();
  for(const v of b.variants){
   check(plain(v)&&id(v.id)&&!variants.has(v.id),'catalog_context_invalid');variants.add(v.id);installed++;
   const template=v.template;
   const suffix=context.target==='latte'?'.latte':'.html.twig';
   if(context.source==='ui8kit'&&typeof template==='string'&&template.startsWith('blocks/')&&!template.includes('..')&&template.endsWith(suffix))available.set(pair(b.id,v.id),{block:b,variant:v});
  }
 }
 const eligible=prepared.items.filter(it=>it.asset.type==='block'&&available.has(pair(it.asset.preview.block,it.asset.variant)));
 const eligiblePairs=new Set(eligible.map(it=>pair(it.asset.preview.block,it.asset.variant)));
 const scoped={...prepared,items:eligible};
 const facets=index.facets.filter(f=>!f.types||f.types.includes('block')).map(f=>({...f,values:f.values.filter(v=>f.id!=='type'||v.id==='block')})).filter(f=>f.values.length);
 const valueSets=new Map(facets.map(f=>[f.id,new Set(f.values.map(v=>v.id))]));
 const inspected=new Set();
 const version={sourceCommit:manifest.source.commit,stockDigest:manifest.stockDigest,target:context.target};
 const descriptor={...version,availability:context.source==='ui8kit'?'local':'source_unmapped',installedVariants:installed,searchableVariants:eligible.length,excludedInstalledVariants:installed-eligible.length,excludedStockBlockVariants:index.counts.block-eligible.length,facets:facets.map(f=>({id:f.id,label:f.label.en,mode:f.mode??'hard',draft:!!f.draft,values:f.values.map(v=>({id:v.id,label:v.label.en}))})),sorts:index.sorts.map(s=>s.id),semantics:'OR within a facet; AND across facets. industry ranks only. Prefix words and multilingual label synonyms, not semantic search. Default one best variant per block. Runtime is fixed by WordPress.'};
 function search(args={}){
  check(plain(args)&&Buffer.byteLength(JSON.stringify(args))<=8000);safeTree(args);
  check(Object.keys(args).every(k=>['q','filters','variants','sort','limit','spec'].includes(k)));
  check(args.q===undefined||text(args.q,120));check(args.limit===undefined||Number.isInteger(args.limit)&&args.limit>=1&&args.limit<=10);
  check(args.variants===undefined||['one','all'].includes(args.variants));check(args.sort===undefined||descriptor.sorts.includes(args.sort));
  check(args.spec===undefined||typeof args.spec==='string'&&/^block\.[a-z][a-z0-9_-]{0,79}$/.test(args.spec));
  const filters=args.filters??{};check(plain(filters));
  for(const [f,values] of Object.entries(filters))check(valueSets.has(f)&&Array.isArray(values)&&values.length<=8&&values.every(v=>valueSets.get(f).has(v)));
  const state={q:(args.q??'').trim().replace(/\s+/g,' '),filters,spec:args.spec??'',variants:args.variants??'one',sort:args.sort??null,page:1};
  const out=engine.search(scoped,state),limit=args.limit??5;
  return {version,availability:descriptor.availability,total:out.results.length,matchedVariants:out.matched,limit,results:out.results.slice(0,limit).map(a=>({assetId:a.id,blockId:a.preview.block,variant:a.variant,title:a.title,description:blocks.get(a.preview.block).docs??a.subtitle,variantDescription:a.subtitle,labels:a.labels,draftFacets:a.weak})),...(out.results.length?{}:{diagnostic:eligible.length?'no_matches':'no_compatible_installed_assets'})};
 }
 function details(args){
  check(plain(args)&&Object.keys(args).every(k=>['blockId','variant'].includes(k))&&id(args.blockId)&&id(args.variant));
  const key=pair(args.blockId,args.variant);check(eligiblePairs.has(key),'catalog_block_unavailable');
  const {block,variant}=available.get(key);
  const records=Object.fromEntries((block.data??[]).filter(d=>id(d.record?.toLowerCase())).map(d=>[d.record,context.records?.[d.record]]).filter(([,v])=>v!==undefined));
  const result=byteCap({version,assetId:'block.'+key,blockId:block.id,variant:variant.id,title:block.title??index.assets.find(a=>a.id==='block.'+key)?.title,description:block.docs??'',props:block.props??[],defaults:block.defaults??{},data:block.data??[],variantSet:variant.set??{},records});
  safeTree(result);inspected.add(key);return result;
 }
 function validateChanges(proposal){
  for(const b of proposal){
   if((context.blocks??[]).some(old=>old.id===b.id&&same(old,b)))continue;
   check(eligiblePairs.has(pair(b.block,b.variant))&&inspected.has(pair(b.block,b.variant)),'catalog_details_required');
  }
 }
 const filters=Object.fromEntries(facets.map(f=>[f.id,{type:'array',maxItems:8,items:{type:'string',enum:f.values.map(v=>v.id)}}]));
 return {descriptor,search,details,validateChanges,searchSchema:{type:'object',properties:{q:{type:'string',maxLength:120},filters:{type:'object',properties:filters,additionalProperties:false},spec:{type:'string',pattern:'^block\\.[a-z][a-z0-9_-]{0,79}$'},variants:{type:'string',enum:['one','all']},sort:{type:'string',enum:descriptor.sorts},limit:{type:'integer',minimum:1,maximum:10}},additionalProperties:false}};
}
/** Invalid tool arguments become bounded diagnostics, not an SDK retry or a catalog dump. */
export const catalogTool=fn=>args=>{try{return fn(args);}catch(error){return {error:error instanceof WorkerError?error.message:'invalid_catalog_query'};}};
