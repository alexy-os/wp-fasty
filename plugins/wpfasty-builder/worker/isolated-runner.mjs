import {fork} from 'node:child_process';
import {WorkerError} from './core.mjs';
/** Dedicated child: a hard wall limit includes SDK import, model discovery and bootstrap. */
export function isolatedRunner(dataDir) {
 return body=>new Promise((resolve,reject)=>{
  const child=fork(new URL('./sdk-child.mjs',import.meta.url),[],{execArgv:['--max-old-space-size=512'],env:{PATH:process.env.PATH,HOME:dataDir,NODE_ENV:'production'},stdio:['ignore','ignore','ignore','ipc']});
  let settled=false;
  const finish=(error,result)=>{if(settled)return;settled=true;clearTimeout(timer);child.kill('SIGKILL');error?reject(error):resolve(result);};
  const timer=setTimeout(()=>finish(new WorkerError('agent_failed_no_automatic_retry',502)),95000);
  child.on('error',()=>finish(new WorkerError('agent_failed_no_automatic_retry',502)));
  child.on('exit',()=>finish(new WorkerError('agent_failed_no_automatic_retry',502)));
  child.on('message',msg=>{if(msg?.ok)finish(null,msg.result);else finish(new WorkerError(msg?.code==='configured_grok_model_unavailable'?msg.code:'agent_failed_no_automatic_retry',msg?.code==='configured_grok_model_unavailable'?503:502));});
  child.send({dataDir,body});
 });
}
