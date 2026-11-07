import {appendFileSync} from 'node:fs';
import {resolve} from 'node:path';
/** Public telemetry DTO. No SDK arguments, tool results, thinking, prompts or prices cross this boundary. */
const counts=['inputTokens','outputTokens','cacheReadTokens','cacheWriteTokens','totalTokens'];
export function tokenUsage(value) {
 if(!value||typeof value!=='object'||counts.some(k=>!Number.isSafeInteger(value[k])||value[k]<0||value[k]>1e12))return null;
 const safe=Object.fromEntries(counts.map(k=>[k,value[k]]));
 if(value.reasoningTokens!==undefined){if(!Number.isSafeInteger(value.reasoningTokens)||value.reasoningTokens<0||value.reasoningTokens>1e12)return null;safe.reasoningTokens=value.reasoningTokens;}
 return safe;
}
export function toolProgress(value) {
 if(!value||!['catalog_lookup','search_catalog','get_block_details','get_skill','propose_layout'].includes(value.name)||!['running','completed','error'].includes(value.status))return null;
 return {name:value.name,status:value.status};
}
/** Only real tool_call SDK events, allowlisting known custom tools; never forward arbitrary event text. */
export function sdkToolProgress(message) {
 if(message?.type!=='tool_call'||typeof message.name!=='string')return null;
 const name=['catalog_lookup','search_catalog','get_block_details','get_skill','propose_layout'].find(n=>message.name===n||message.name.endsWith('__'+n));
 return toolProgress({name,status:message.status});
}

/** Version-only audit metadata. Never persist full instruction or skill text in public history. */
export function policyProvenance(value){
 if(!value||!Number.isSafeInteger(value.policyVersion)||value.policyVersion<1||!Number.isSafeInteger(value.briefVersion)||value.briefVersion<1||!/^sha256:[a-f0-9]{64}$/.test(value.policyDigest)||!Array.isArray(value.loadedSkills)||value.loadedSkills.length>12)return null;
 if(value.loadedSkills.some(s=>!s||typeof s.id!=='string'||!/^[a-z][a-z0-9-]{0,63}$/.test(s.id)||!Number.isSafeInteger(s.version)||s.version<1))return null;
 return {policyVersion:value.policyVersion,briefVersion:value.briefVersion,policyDigest:value.policyDigest,loadedSkills:value.loadedSkills.map(s=>({id:s.id,version:s.version}))};
}

/** Closed public failure vocabulary; SDK messages, stacks and arbitrary codes are never public. */
export const agentFailures=['agent_sdk_timeout_no_automatic_retry','agent_watchdog_timeout_no_automatic_retry','agent_stream_failed_no_automatic_retry','agent_wait_failed_no_automatic_retry','agent_sdk_error_no_automatic_retry','agent_sdk_cancelled_no_automatic_retry','agent_child_exit_no_automatic_retry','agent_child_spawn_failed_no_automatic_retry'];
const knownErrors=new Set(['TIMEOUT','UNAUTHORIZED','BAD_API_KEY','BAD_MODEL_NAME','MODEL_BLOCKED','RATE_LIMITED','RESOURCE_EXHAUSTED','AUTH_TOKEN_EXPIRED','ABORT_ERR','connection_stalled','agent_not_found','canceled','unknown','invalid_argument','deadline_exceeded','not_found','already_exists','permission_denied','resource_exhausted','failed_precondition','aborted','out_of_range','unimplemented','internal','unavailable','data_loss','unauthenticated']);
export function safeErrorCode(error){return error?.code==null?null:knownErrors.has(error.code)?error.code:'present_redacted';}
export function diagnosticEvent(message){return {lastEventType:['thinking','user','assistant','tool_call','usage','status','summary','system','task'].includes(message?.type)?message.type:'other',...(sdkToolProgress(message)?{tool:sdkToolProgress(message)}:{})};}
/** Audit is local metadata only. It never contains prompts, results, errors.message or raw child output. */
export function auditWriter(dataDir,requestId){
 const started=performance.now();
 return (stage,extra={})=>{
  const safe={at:new Date().toISOString(),requestId:/^[a-f0-9-]{36}$/i.test(requestId??'')?requestId:null,stage,elapsedMs:Math.round(performance.now()-started)};
  for(const key of ['agentId','runId'])if(new RegExp('^'+(key==='runId'?'run':'agent')+'-[a-f0-9-]{36}$','i').test(extra[key]??''))safe[key]=extra[key];
  for(const key of ['status','lastEventType'])if(['running','finished','error','cancelled','unknown','thinking','user','assistant','tool_call','usage','summary','system','task','other'].includes(extra[key]))safe[key]=extra[key];
  if(extra.selectedModelId==='grok-4.7')safe.selectedModelId=extra.selectedModelId;
  if(extra.errorCode===null||knownErrors.has(extra.errorCode)||extra.errorCode==='present_redacted')safe.errorCode=extra.errorCode;
  if(toolProgress(extra.tool))safe.tool=toolProgress(extra.tool);
  if(tokenUsage(extra.usage))safe.usage=tokenUsage(extra.usage);
  if(Number.isSafeInteger(extra.exitCode)&&extra.exitCode>=0&&extra.exitCode<=0xffffffff)safe.exitCode=extra.exitCode;
  if(['SIGKILL','SIGTERM','SIGABRT','SIGSEGV'].includes(extra.signal))safe.signal=extra.signal;
  appendFileSync(resolve(dataDir,'live-audit.jsonl'),JSON.stringify(safe)+'\n',{mode:0o600});
 };
}
