// NON-LIVE SDK/event fixtures only. Never imports the real SDK or calls a provider.
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {mkdtempSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {EventEmitter} from 'node:events';
import {TurnStore} from '../core.mjs';
import {workerServer} from '../server.mjs';
import {sdkRunner} from '../sdk.mjs';
import {isolatedRunner} from '../isolated-runner.mjs';
import {tokenUsage,sdkToolProgress} from '../telemetry.mjs';
const usage={inputTokens:100,outputTokens:20,cacheReadTokens:5,cacheWriteTokens:3,totalTokens:128};
const body=()=>({site:'site-fixture-a',page:12,requestId:'fixture-request-1',content:'NON-LIVE fixture',context:{source:'ui8kit',target:'latte',version:2,blocks:[],catalog:[]}});
test('NON-LIVE allowlisted SDK tool events and unknown usage; no arguments, results, thinking, arbitrary names',()=>{
 assert.equal(tokenUsage(null),null);assert.equal(tokenUsage({...usage,inputTokens:'100'}),null);assert.equal(tokenUsage({...usage,totalTokens:Infinity}),null);
 assert.deepEqual(tokenUsage({...usage,secret:'PRIVATE_FIXTURE'}),usage);
 assert.deepEqual(sdkToolProgress({type:'tool_call',name:'mcp__custom__catalog_lookup',status:'running',args:'PRIVATE_FIXTURE'}),{name:'catalog_lookup',status:'running'});
 for(const m of [{type:'thinking',name:'catalog_lookup',status:'running'},{type:'tool_call',name:'secret_name',status:'running'},{type:'tool_call',name:'catalog_lookup',status:'invalid'}])assert.equal(sdkToolProgress(m),null);
});
test('NON-LIVE one SDK send streams same run, persists actual cumulative usage; does not sum duplicate usage events',async()=>{
 let sends=0,closes=0;const seen=[];
 const fake={Cursor:{models:{list:async()=>[{id:'grok-4.7'}]}},JsonlLocalAgentStore:class{},Agent:{create:async()=>({send:async()=>{sends++;return {id:'fixture-run',supports:()=>true,async *stream(){yield {type:'thinking',text:'PRIVATE_FIXTURE'};yield {type:'tool_call',name:'catalog_lookup',status:'running',args:'PRIVATE_FIXTURE'};yield {type:'usage',usage};yield {type:'usage',usage};yield {type:'tool_call',name:'propose_layout',status:'completed',result:'PRIVATE_FIXTURE'};},wait:async()=>({status:'finished',model:{id:'grok-4.7'},result:'NON-LIVE response',usage}),cancel:async()=>{}};},close:()=>{closes++;}})}};
 const runner=sdkRunner(mkdtempSync(join(tmpdir(),'wpfb-telemetry-')),async()=>fake,p=>seen.push(p));
 const result=await runner({...body(),credentials:{cursor:'NON_LIVE_ONLY'}});
 assert.equal(sends,1);assert.equal(closes,1);assert.deepEqual(result.usage,usage);
 assert.deepEqual(seen,[{name:'catalog_lookup',status:'running'},{name:'propose_layout',status:'completed'}]);assert.equal(JSON.stringify(seen).includes('PRIVATE_FIXTURE'),false);
 const file=join(mkdtempSync(join(tmpdir(),'wpfb-usage-')),'history.sqlite');let store=new TurnStore(file);
 await store.turn(body(),async()=>result);store.db.close();store=new TurnStore(file);
 assert.deepEqual(store.history('site-fixture-a:12')[0].usage,usage);
 assert.deepEqual((await store.turn(body(),()=>{throw Error('must replay');})).usage,usage);store.db.close();
});
test('NON-LIVE child progress IPC is not terminal, is sanitized, terminal result settles once',async()=>{
 const seen=[];let kills=0;
 const child=new EventEmitter();child.kill=()=>{kills++;};child.send=()=>queueMicrotask(()=>{child.emit('message',{progress:{name:'catalog_lookup',status:'running',args:'PRIVATE_FIXTURE'}});child.emit('message',{ok:true,result:{answer:'NON-LIVE',usage}});child.emit('exit');});
 const result=await isolatedRunner(mkdtempSync(join(tmpdir(),'wpfb-ipc-')),()=>child)(body(),p=>seen.push(p));
 assert.equal(result.answer,'NON-LIVE');assert.deepEqual(seen,[{name:'catalog_lookup',status:'running'}]);assert.equal(kills,1);
});
test('NON-LIVE authenticated progress is per site/page/request, non-overlapping with live turn; replay and failure cleanup',async()=>{
 const store=new TurnStore(':memory:');let release,started;const ready=new Promise(r=>started=r);let calls=0;
 const runner=async(b,progress)=>{calls++;progress({name:'catalog_lookup',status:'running',args:'PRIVATE_FIXTURE'});started();await new Promise(r=>release=r);return {answer:'NON-LIVE',usage};};
 const token='NON_LIVE_SHARED_TOKEN_000000000000000';const server=workerServer({store,token,runner});
 await new Promise(r=>server.listen(0,'127.0.0.1',r));const base='http://127.0.0.1:'+server.address().port;
 const req=(path,b=body(),auth=true,headers={})=>fetch(base+path,{method:'POST',headers:{'Content-Type':'application/json',...(auth?{Authorization:'Bearer '+token}:{}),...headers},body:JSON.stringify(b)});
 try{
  assert.equal((await req('/progress',body(),false)).status,401);assert.equal((await req('/progress',body(),true,{Origin:'https://invalid.test'})).status,403);
  const pending=req('/turn');await ready;
  const progress=await (await req('/progress')).json();assert.deepEqual(progress,{requestId:body().requestId,state:'pending',sequence:1,tool:{name:'catalog_lookup',status:'running'}});
  for(const changed of [{site:'site-fixture-b'},{page:13},{requestId:'another-request'}]){const p=await(await req('/progress',{...body(),...changed})).json();assert.equal(p.state,'unknown');assert.equal(p.tool,null);}
  assert.equal((await req('/progress',{...body(),requestId:'bad'})).status,400);
  release();assert.equal((await pending).status,200);assert.equal((await(await req('/progress')).json()).tool,null);assert.equal(store.activity.size,0);
  assert.equal((await req('/turn')).status,200);assert.equal(calls,1);assert.equal(JSON.stringify(store.history('site-fixture-a:12')).includes('PRIVATE_FIXTURE'),false);
  await assert.rejects(()=>store.turn({...body(),requestId:'fixture-failed'},async(b,progress)=>{progress({name:'propose_layout',status:'running'});throw Error('PRIVATE_FIXTURE');}));
  assert.equal(store.progress('site-fixture-a:12','fixture-failed').state,'failed');assert.equal(store.activity.size,0);
 }finally{release?.();await new Promise(r=>server.close(r));store.db.close();}
});
