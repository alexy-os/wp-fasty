import {toolProgress,tokenUsage,agentFailures,auditWriter} from './telemetry.mjs';
import {fork} from 'node:child_process';
import {mkdirSync} from 'node:fs';
import {resolve} from 'node:path';
/** Minimal child env: no parent secrets. Windows needs SystemRoot etc. for Node/SDK internals; temp/profile dirs point into the private data dir. */
export function childEnv(dataDir,parent=process.env,platform=process.platform){
 const tmp=resolve(dataDir,'tmp');
 const env={PATH:parent.PATH??parent.Path,HOME:dataDir,NODE_ENV:'production',TEMP:tmp,TMP:tmp,TMPDIR:tmp};
 if(platform==='win32'){
  for(const k of ['SystemRoot','SystemDrive','windir','ComSpec','PATHEXT','NUMBER_OF_PROCESSORS','PROCESSOR_ARCHITECTURE'])if(parent[k])env[k]=parent[k];
  env.USERPROFILE=dataDir;env.APPDATA=resolve(dataDir,'appdata');env.LOCALAPPDATA=resolve(dataDir,'localappdata');
 }
 return env;
}
import {WorkerError} from './core.mjs';
/** Dedicated child: a hard wall limit includes SDK import, model discovery and bootstrap. */
export function isolatedRunner(dataDir,spawn=fork,{timeoutMs=95000}={}) {
 return (body,onProgress=()=>{})=>new Promise((resolve,reject)=>{
  const env=childEnv(dataDir);for(const d of [env.TEMP,env.APPDATA,env.LOCALAPPDATA])if(d)mkdirSync(d,{recursive:true,mode:0o700});
  const audit=auditWriter(dataDir,body.requestId);
  let child,timer,settled=false;
  const fail=kind=>new WorkerError('agent_'+kind+'_no_automatic_retry',502);
  const finish=(error,result)=>{if(settled)return;settled=true;clearTimeout(timer);child?.kill('SIGKILL');error?reject(error):resolve(result);};
  try{child=spawn(new URL('./sdk-child.mjs',import.meta.url),[],{execArgv:['--max-old-space-size=512'],env,stdio:['ignore','ignore','ignore','ipc']});}
  catch(error){audit('child_spawn_error');finish(fail('child_spawn_failed'));return;}
  timer=setTimeout(()=>{audit('parent_watchdog');finish(fail('watchdog_timeout'));},timeoutMs);
  child.on('error',()=>{if(!settled){audit('child_spawn_error');finish(fail('child_spawn_failed'));}});
  child.on('exit',(exitCode,signal)=>{if(!settled){audit('child_exit',{exitCode,signal});finish(fail('child_exit'));}});
  child.on('message',msg=>{
   if(settled)return;
   if(msg?.progress){const safe=toolProgress(msg.progress);if(safe)onProgress(safe);return;}
   if(msg?.ok){finish(null,msg.result);return;}
   const config=['configured_grok_model_unavailable','catalog_map_unavailable','catalog_context_invalid','catalog_target_unsupported','policy_context_invalid'];
   const error=new WorkerError([...config,...agentFailures].includes(msg?.code)?msg.code:'agent_failed_no_automatic_retry',config.includes(msg?.code)?503:502);error.usage=tokenUsage(msg?.usage);finish(error);
  });
  try{child.send({dataDir,body,deadlineAt:Date.now()+90000});}
  catch{audit('child_spawn_error');finish(fail('child_spawn_failed'));}
 });
}
