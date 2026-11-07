// NON-LIVE: fake runs and children only. No SDK import, credentials or network.
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {mkdtempSync,readFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {EventEmitter} from 'node:events';
import {observeRun} from '../sdk.mjs';
import {isolatedRunner} from '../isolated-runner.mjs';
import {safeErrorCode} from '../telemetry.mjs';
const never=()=>new Promise(()=>{});
const usage={inputTokens:1,outputTokens:2,cacheReadTokens:0,cacheWriteTokens:0,totalTokens:3};
function fixture(extra={}){let cancels=0;const events=[];return {events,get cancels(){return cancels;},run:{id:'fixture-run',status:'running',wait:async()=>({status:'finished',result:'ok'}),cancel:async()=>{cancels++;},...extra},audit:(stage,metadata)=>events.push({stage,...metadata})};}
const options={timeoutMs:25,cleanupMs:10,drainMs:5};
test('terminal SDK error/cancelled audited before rejection with usage, never raw error',async()=>{
 for(const status of ['error','cancelled']){const f=fixture({wait:async()=>({status,error:{code:'TIMEOUT',message:'PRIVATE_SECRET'},usage})});await assert.rejects(observeRun(f.run,f.audit,()=>{},options),e=>e.message===`agent_sdk_${status}_no_automatic_retry`&&e.usage.totalTokens===3);assert.equal(f.events.find(e=>e.stage==='sdk_terminal').status,status);assert.equal(JSON.stringify(f.events).includes('PRIVATE_SECRET'),false);}
});
test('stream failure and wait failure are distinct and cancel existing run once',async()=>{
 for(const part of ['stream','wait']){const f=fixture(part==='stream'?{async *stream(){throw Object.assign(Error('PRIVATE_SECRET'),{code:'TIMEOUT'});},wait:never}:{wait:async()=>{throw Error('PRIVATE_SECRET');}});await assert.rejects(observeRun(f.run,f.audit,()=>{},options),{message:`agent_${part}_failed_no_automatic_retry`});assert.equal(f.cancels,1);assert.ok(f.events.some(e=>e.stage===`${part}_error`));assert.equal(JSON.stringify(f.events).includes('PRIVATE_SECRET'),false);}
});
test('finished wait is not trapped behind a hanging stream; finished stream does not bypass hanging wait',async()=>{
 const f=fixture({async *stream(){await never();}});assert.equal((await observeRun(f.run,f.audit,()=>{},options)).status,'finished');assert.equal(f.cancels,0);
 const g=fixture({async *stream(){},wait:never,cancel:never});await assert.rejects(observeRun(g.run,g.audit,()=>{},options),{message:'agent_sdk_timeout_no_automatic_retry'});assert.ok(g.events.some(e=>e.stage==='sdk_timeout'));assert.ok(g.events.some(e=>e.stage==='cancel_timeout'));
});
test('adversarial codes and events are not exported',async()=>{
 assert.equal(safeErrorCode({code:'sk_PRIVATE_SECRET'}),'present_redacted');
 const f=fixture({async *stream(){yield {type:'PRIVATE_SECRET',text:'PRIVATE_SECRET'};yield {type:'tool_call',name:'mcp__custom__get_block_details',status:'running',args:'PRIVATE_SECRET'};}});await observeRun(f.run,f.audit,()=>{},options);assert.equal(JSON.stringify(f.events).includes('PRIVATE_SECRET'),false);
});
test('parent diagnoses spawn/exit/watchdog and ignores late terminal messages',async()=>{
 for(const kind of ['spawn','exit','watchdog']){const dir=mkdtempSync(join(tmpdir(),'wpfb-diagnostics-'));const child=new EventEmitter();let kills=0;child.kill=()=>{kills++;};child.send=()=>queueMicrotask(()=>{if(kind==='exit')child.emit('exit',1,null);});
 const spawn=()=>{if(kind==='spawn')throw Error('PRIVATE_SECRET');return child;};
 await assert.rejects(isolatedRunner(dir,spawn,{timeoutMs:15})({requestId:'fixture-request'}),{message:`agent_${kind==='watchdog'?'watchdog_timeout':kind==='spawn'?'child_spawn_failed':'child_exit'}_no_automatic_retry`});
 child.emit('message',{ok:true,result:{answer:'late'}});assert.equal(kills,kind==='spawn'?0:1);
 const log=readFileSync(join(dir,'live-audit.jsonl'),'utf8');assert.equal(log.includes('PRIVATE_SECRET'),false);assert.ok(log.includes(kind==='watchdog'?'parent_watchdog':kind==='spawn'?'child_spawn_error':'child_exit'));
 }
});
test('failed turn persists actual SDK usage and closed code, never becomes replayable',async()=>{
 const {TurnStore,WorkerError}=await import('../core.mjs');const store=new TurnStore(':memory:');
 const body={site:'fixture-site',page:1,requestId:'fixture-request',content:'NON-LIVE',context:{source:'ui8kit',version:1,blocks:[],catalog:[]}};
 const error=new WorkerError('agent_sdk_error_no_automatic_retry',502);error.usage=usage;
 await assert.rejects(store.turn(body,async()=>{throw error;}),{message:error.message});
 const result=store.history('fixture-site:1')[0];assert.equal(result.state,'failed');assert.deepEqual(result.usage,usage);assert.equal(result.error,error.message);
 await assert.rejects(store.turn(body,async()=>{throw Error('must not retry');}),{message:'turn_not_replayable_check_history'});store.db.close();
});
