// NON-LIVE local map/engine/tool contracts. No provider SDK/network imports or model turns.
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync,mkdtempSync,cpSync,writeFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {pathToFileURL} from 'node:url';
import {catalogSession,loadCatalog,catalogTool,validateIndex} from '../catalog-search.mjs';
import {sdkRunner} from '../sdk.mjs';
const catalog=JSON.parse(readFileSync(new URL('../../sources/ui8kit/latte/catalog.json',import.meta.url)));
const context=(target='latte')=>({source:'ui8kit',target,catalog:target==='latte'?catalog.blocks:JSON.parse(readFileSync(new URL('../../sources/ui8kit/twig/catalog.json',import.meta.url))).blocks,records:catalog.records,blocks:[],version:1});
const resource=await loadCatalog();
test('NON-LIVE official Stock query parity after installed-target intersection; facets and soft ranking',async()=>{
 const ctx=context(),session=await catalogSession(ctx,resource);
 const valid=new Set(ctx.catalog.flatMap(b=>b.variants.map(v=>`${b.id}/${v.id}`)));
 const engine={...resource.prepared,items:resource.prepared.items.filter(it=>it.asset.type==='block'&&valid.has(`${it.asset.preview.block}/${it.asset.variant}`))};
 const examples=[{}, {q:'hero'}, {filters:{job:['hero','pricing'],density:['airy']}}, {filters:{industry:['food']}}, {filters:{job:['hero']},variants:'all'}, {q:'перв'}, {q:'zzzznohitszzzz'}, {spec:'block.hero',sort:'name'}, {sort:'newest'}, {sort:'unlabelled'}];
 for(const query of examples){
  const expected=resource.engine.search(engine,{q:'',filters:{},spec:'',variants:'one',sort:null,page:1,...query});
  const actual=session.search({...query,limit:10});
  assert.deepEqual(actual.results.map(x=>x.assetId),expected.results.slice(0,10).map(x=>x.id));assert.equal(actual.total,expected.results.length);
 }
 assert.equal(session.search({filters:{industry:['food']}}).total,session.search({}).total);
 assert.equal(session.descriptor.facets.some(f=>f.id==='runtime'||f.id==='blocks'),false);
 assert.ok(session.descriptor.searchableVariants<resource.index.counts.block);
 assert.equal(session.search({q:'zzzznohitszzzz'}).diagnostic,'no_matches');
});
test('NON-LIVE actual multilingual synonyms, OR/AND and top-k, no full catalog/schema default',async()=>{
 const s=await catalogSession(context(),resource);
 const hero=resource.index.facets.find(f=>f.id==='job').values.find(v=>v.id==='hero');
 for(const word of [hero.label.ru.split(/\s+/)[0],...(hero.synonyms?.ru??[]).slice(0,1)])assert.ok(s.search({q:word}).results.length,word);
 const result=s.search({});assert.equal(result.limit,5);assert.equal(result.results.length,5);assert.ok(result.results.every(r=>!('props'in r)&&!('records'in r)));
 assert.ok(Buffer.byteLength(JSON.stringify(result))<10000);
 assert.ok(s.search({filters:{job:['hero','pricing']}}).total>=s.search({filters:{job:['hero']}}).total);
 assert.ok(s.search({filters:{job:['hero'],density:['airy']}}).total<=s.search({filters:{job:['hero']}}).total);
});
test('NON-LIVE canonical case-preserving mapping, target/variant/source authority and exact details',async()=>{
 for(const target of ['latte','twig']){
  const c=context(target),s=await catalogSession(c,resource);
  const hero=s.details({blockId:'hero',variant:'centered'});assert.equal(hero.assetId,'block.hero/centered');assert.equal(hero.version.target,target);assert.ok(hero.props.length);assert.ok(hero.records.ActionSet,'selected prop types resolve transitively');
  assert.equal(s.details({blockId:'hero',variant:'twoActions'}).variant,'twoActions');
  assert.throws(()=>s.details({blockId:'hero',variant:'twoactions'}),/unavailable/);
  assert.throws(()=>s.details({blockId:'missing',variant:'centered'}),/unavailable/);
  assert.throws(()=>s.details({blockId:'hero'}));
  const mismatch=await catalogSession({...c,catalog:c.catalog.map(b=>({...b,variants:b.variants.map(v=>({...v,template:'blocks/wrong.'+(target==='latte'?'html.twig':'latte')}))}))},resource);
  assert.equal(mismatch.search({}).total,0);
 }
 await assert.rejects(()=>catalogSession({...context(),target:'Latina'},resource),/target_unsupported/);
 const remote=await catalogSession({...context(),source:'other-source'},resource);assert.equal(remote.search({}).availability,'source_unmapped');assert.equal(remote.search({}).total,0);
 const s=await catalogSession(context(),resource),b={id:'hero-main',block:'hero',variant:'centered',props:{title:'NON-LIVE'},bindings:{}};
 assert.throws(()=>s.validateChanges([b]),/details_required/);s.details({blockId:'hero',variant:'centered'});s.validateChanges([b]);
 const preserved={id:'legacy',block:'legacy-block',variant:'legacy',props:{},bindings:{}};
 (await catalogSession({...context(),blocks:[preserved]},resource)).validateChanges([preserved]);
});
test('NON-LIVE invalid, oversized, unknown, prototype and schema inputs fail closed',async()=>{
 const s=await catalogSession(context(),resource);
 for(const query of [null,[],{q:'x'.repeat(121)},{limit:11},{limit:0},{limit:1.5},{filters:{runtime:['react']}},{filters:{industry:['invented']}},{filters:{job:'hero'}},{filters:{job:Array(9).fill('hero')}},{sort:'random'},{variants:'false'},{spec:'../path'},{constructor:{}},JSON.parse('{"filters":{"__proto__":["hero"]}}')])assert.equal(typeof catalogTool(s.search)(query).error,'string');
 assert.equal(Object.prototype.polluted,undefined);
 const dir=mkdtempSync(join(tmpdir(),'wpfb-map-'));cpSync(new URL('../catalog/',import.meta.url),dir,{recursive:true});
 writeFileSync(join(dir,'stock.v0.json'),'{}');await assert.rejects(()=>loadCatalog(pathToFileURL(dir+'/')),/catalog_map_unavailable/);
 writeFileSync(join(dir,'stock.v0.json'),'x'.repeat(3*1024*1024+1));await assert.rejects(()=>loadCatalog(pathToFileURL(dir+'/')),/catalog_map_unavailable/);
 for(const altered of [{...resource.index,version:'999'},JSON.parse('{"version":"0","facets":[],"assets":[],"__proto__":{}}')])assert.throws(()=>validateIndex(altered));
});
test('NON-LIVE SDK uses bounded search then exact details, preserves proposal/usage, no full catalog or secret in prompt',async()=>{
 let options,sent,sends=0;
 const fake={Cursor:{models:{list:async()=>[{id:'grok-4.7'}]}},JsonlLocalAgentStore:class{},Agent:{create:async o=>{options=o;return {send:async prompt=>{sent=prompt;sends++;
  const tools=o.local.customTools;const hit=tools.search_catalog.execute({q:'hero',limit:1}).results[0];assert.ok(hit);
  const block={id:'fixture-hero',block:hit.blockId,variant:hit.variant,props:{title:'NON-LIVE'},bindings:{}};
  assert.throws(()=>tools.propose_layout.execute({blocks:[block]}),/details_required/);
  tools.get_block_details.execute({blockId:hit.blockId,variant:hit.variant});tools.propose_layout.execute({blocks:[block]});
  return {id:'nonlive-search-run',wait:async()=>({status:'finished',result:'NON-LIVE',model:{id:'grok-4.7'}}),cancel:async()=>{}};},close(){}};}}};
 const result=await sdkRunner(mkdtempSync(join(tmpdir(),'wpfb-search-sdk-')),async()=>fake)({requestId:'nonlive-search',context:context(),content:'NON-LIVE',credentials:{cursor:'NON_LIVE_KEY'}});
 assert.equal(sends,1);assert.equal(result.proposal.length,1);assert.equal(result.usage,null);
 assert.ok(!sent.includes('NON_LIVE_KEY')&&!sent.includes('accessible name of the landmark'));
 assert.ok(!('catalog_lookup'in options.local.customTools));
});
test('NON-LIVE copied worker resource loads without npm, source repo or network access',async()=>{
 const {execFileSync}=await import('node:child_process');
 const dir=mkdtempSync(join(tmpdir(),'wpfb-resource-'));
 cpSync(new URL('../',import.meta.url),dir,{recursive:true,filter:p=>!p.includes('node_modules')&&!p.includes('/test/')});
 const url=pathToFileURL(join(dir,'catalog-search.mjs')).href;
 const policyUrl=pathToFileURL(join(dir,'policy.mjs')).href;
 const code=`import {policySession} from ${JSON.stringify(policyUrl)}; if(!policySession().getSkill({id:'page-review'}).instructions)process.exit(1); import {loadCatalog} from ${JSON.stringify(url)}; const r=await loadCatalog(); if(r.index.counts.block!==532)process.exit(1); console.log('NON-LIVE standalone resource OK');`;
 assert.match(execFileSync(process.execPath,['--input-type=module','-e',code],{cwd:tmpdir(),env:{PATH:process.env.PATH},encoding:'utf8'}),/standalone resource OK/);
});
