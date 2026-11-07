// NON-LIVE contract tests. Injected runner never imports SDK or calls a model.
import {test} from 'node:test';
import {request} from 'node:http';
import assert from 'node:assert/strict';
import {mkdtempSync,readFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {TurnStore,validateProposal} from '../core.mjs';
import {workerServer} from '../server.mjs';
const body=()=>({site:'site-test-a',page:10,requestId:'request-0001',content:'build draft',context:{source:'ui8kit',version:1,blocks:[],catalog:[{id:'hero',variants:[{id:'centered'}]}]}});
const proposal=[{id:'hero-main',block:'hero',variant:'centered',props:{title:'Hello'},bindings:{}}];
test('NON-LIVE durable replay and mismatched key conflict; credentials never stored',async()=>{
 const file=join(mkdtempSync(join(tmpdir(),'wpfb-')),'turns.sqlite');let calls=0;
 let store=new TurnStore(file);
 const first=await store.turn({...body(),credentials:{cursor:'TEST_SENTINEL_KEY'}},async()=>{calls++;return {answer:'fixture only',proposal};});
 store.db.close();store=new TurnStore(file);
 assert.deepEqual(await store.turn(body(),async()=>{calls++;}),first);assert.equal(calls,1);
 await assert.rejects(()=>store.turn({...body(),content:'different'},()=>{}),/idempotency_conflict/);
 assert.equal(JSON.stringify(store.history('site-test-a:10')).includes('TEST_SENTINEL_KEY'),false);
 assert.equal(store.history('site-test-b:10').length,0);store.db.close();
});
test('NON-LIVE invalid catalog references rejected, no retries after failed turn',async()=>{
 assert.throws(()=>validateProposal([{...proposal[0],variant:'fake'}],body().context.catalog),/invalid_catalog/);
 const store=new TurnStore(':memory:');let calls=0;
 await assert.rejects(()=>store.turn(body(),async()=>{calls++;throw new Error('private provider failure');}),/agent_failed/);
 await assert.rejects(()=>store.turn(body(),async()=>{calls++;}),/turn_not_replayable/);assert.equal(calls,1);store.db.close();
});
test('NON-LIVE HTTP token, host, Origin, paths, history and create/reload',async()=>{
 const store=new TurnStore(':memory:');const token='test-token-not-a-provider-key-000000000';
 const server=workerServer({store,token,runner:async()=>({answer:'NON-LIVE fixture',proposal})});
 await new Promise(r=>server.listen(0,'127.0.0.1',r));const base='http://127.0.0.1:'+server.address().port;
 const req=(path,b,headers={})=>new Promise((resolve,reject)=>{
  const r=request(base+path,{method:'POST',headers:{Host:'127.0.0.1:'+server.address().port,'Content-Type':'application/json',...headers}},res=>{let raw='';res.on('data',c=>raw+=c);res.on('end',()=>resolve({status:res.statusCode,json:async()=>JSON.parse(raw)}));});r.on('error',reject);r.end(JSON.stringify(b));
 });
 try{
 assert.equal((await req('/turn',body())).status,401);
 assert.equal((await req('/turn',body(),{Authorization:'Bearer '+token,Origin:'https://evil.invalid'})).status,403);
 assert.equal((await req('/turn',body(),{Authorization:'Bearer '+token,Host:'evil.invalid'})).status,403);
 assert.equal((await req('/shell',body(),{Authorization:'Bearer '+token})).status,404);
 assert.equal((await req('/turn',body(),{Authorization:'Bearer '+token})).status,200);
 const history=await (await req('/history',body(),{Authorization:'Bearer '+token})).json();assert.equal(history.turns.length,1);
 }finally{await new Promise(r=>server.close(r));store.db.close();}
});

test('NON-LIVE schema caps and null/duplicate references are rejected',()=>{
 const c=body().context.catalog;
 for(const invalid of [[null],[proposal[0],proposal[0]],[{...proposal[0],props:[]}],[{...proposal[0],props:{title:'x'.repeat(256000)}}]]) assert.throws(()=>validateProposal(invalid,c));
});
test('NON-LIVE interrupted durable page fails closed after process restart',async()=>{
 const file=join(mkdtempSync(join(tmpdir(),'wpfb-pending-')),'turns.sqlite');let store=new TurnStore(file);
 store.db.prepare("INSERT INTO turns VALUES(?,?,?,'pending',NULL)").run('site-test-a:10','interrupted-request','unknown');store.db.close();store=new TurnStore(file);
 await assert.rejects(()=>store.turn(body(),()=>{throw Error('must not run');}),/page_busy_or_interrupted/);store.db.close();
});
test('NON-LIVE SDK adapter propagates exact Grok id, custom-only tools and no ambient config',async()=>{
 const {sdkRunner}=await import('../sdk.mjs');let options,sent,closed=false;
 const fake={Cursor:{models:{list:async o=>{assert.equal(o.apiKey,'TEST_ONLY');return [{id:'grok-4.7'}];}}},JsonlLocalAgentStore:class{},Agent:{create:async o=>{options=o;return {send:async text=>{sent=text;return {wait:async()=>({status:'finished',model:{id:'grok-4.7'},result:'NON-LIVE SDK contract'}),cancel:async()=>{}};},close:()=>{closed=true;}};}}};
 const dir=mkdtempSync(join(tmpdir(),'wpfb-sdk-'));const run=sdkRunner(dir,async()=>fake);
 const result=await run({...body(),credentials:{cursor:'TEST_ONLY'}});
 assert.equal(result.evidence.selectedModelId,'grok-4.7');assert.equal(result.evidence.reportedModelId,'grok-4.7');assert.ok(result.evidence.elapsedMs>=0);assert.equal(readFileSync(join(dir,'live-audit.jsonl'),'utf8').includes('TEST_ONLY'),false);
 assert.equal(options.model.id,'grok-4.7');assert.deepEqual(options.tools,['mcp']);assert.deepEqual(options.mcpServers,{});assert.deepEqual(options.local.settingSources,[]);assert.equal(options.local.enableAgentRetries,false);assert.equal(closed,true);
 assert.deepEqual(Object.keys(options.local.customTools).sort(),['catalog_lookup','propose_layout']);assert.equal(sent.includes('TEST_ONLY'),false);
 fake.Cursor.models.list=async()=>[{id:'other-model'}];await assert.rejects(()=>run({...body(),credentials:{cursor:'TEST_ONLY'}}),/configured_grok_model_unavailable/);
});
test('NON-LIVE WPFB_WORKER_PORT accepts only integer 1024-65535; default 18991',async()=>{
 const {workerPort}=await import('../server.mjs');
 assert.equal(workerPort(undefined),18991);assert.equal(workerPort(''),18991);assert.equal(workerPort('19000'),19000);assert.equal(workerPort('65535'),65535);
 for(const bad of ['80','1023','65536','0','-1','19000.5',' 19000','0x4a38','127.0.0.1:19000','http://127.0.0.1:19000','19000/x','abc'])assert.throws(()=>workerPort(bad),/WPFB_WORKER_PORT/);
});
