import {randomUUID} from 'node:crypto';
import { createServer } from 'node:http';
import { mkdirSync,realpathSync,readFileSync,statSync } from 'node:fs';
import { resolve,isAbsolute,relative,sep } from 'node:path';
import { pathToFileURL } from 'node:url';
import { TurnStore,WorkerError,authenticated } from './core.mjs';
import {isolatedRunner} from './isolated-runner.mjs';
export const DEFAULT_WORKER_PORT=18991;
/** WPFB_WORKER_PORT: optional decimal integer 1024-65535 (default 18991). Host is never configurable: always 127.0.0.1. */
export function workerPort(value){
 if(value===undefined||value==='')return DEFAULT_WORKER_PORT;
 if(typeof value!=='string'||!/^[1-9][0-9]{3,4}$/.test(value))throw new Error('WPFB_WORKER_PORT must be an integer 1024-65535');
 const port=Number(value);
 if(port<1024||port>65535)throw new Error('WPFB_WORKER_PORT must be an integer 1024-65535');
 return port;
}
/**
 * Secrets source, in order: WPFB_WORKER_SECRETS=stdin (one JSON object {token, secrets?: {NAME: value}} on stdin; legacy cursorKey/jevKey map to CURSOR_API_KEY/JEV_API_KEY, then EOF;
 * used by local-wp, values come from its age vault), WPFB_WORKER_TOKEN_FILE (0600 file holding the token),
 * legacy WPFB_WORKER_TOKEN env. Secrets are removed from process.env after reading.
 */
export function readSecrets(env=process.env,stdin=()=>readFileSync(0,'utf8')){
 let token='',cursorKey='',jevKey='';const secrets={};
 if(env.WPFB_WORKER_SECRETS==='stdin'){
  let v;try{v=JSON.parse(stdin());}catch{throw new Error('WPFB_WORKER_SECRETS=stdin expects one JSON object on stdin');}
  token=typeof v?.token==='string'?v.token:'';
  if(v?.secrets!=null&&(typeof v.secrets!=='object'||Array.isArray(v.secrets)))throw new Error('secrets must be an object of NAME: string');
  for(const [k,val] of Object.entries(v?.secrets||{})){
   if(!/^[A-Z0-9_]{1,64}$/.test(k)||typeof val!=='string')throw new Error('secrets must be an object of NAME: string');
   secrets[k]=val;
  }
  if(typeof v?.cursorKey==='string'&&v.cursorKey&&!secrets.CURSOR_API_KEY)secrets.CURSOR_API_KEY=v.cursorKey;
  if(typeof v?.jevKey==='string'&&v.jevKey&&!secrets.JEV_API_KEY)secrets.JEV_API_KEY=v.jevKey;
  cursorKey=secrets.CURSOR_API_KEY||'';jevKey=secrets.JEV_API_KEY||''; // other secrets: kept in memory only, never used or reported
 }else if(env.WPFB_WORKER_TOKEN_FILE){
  const f=env.WPFB_WORKER_TOKEN_FILE;
  if(process.platform!=='win32'&&(statSync(f).mode&0o077))throw new Error('WPFB_WORKER_TOKEN_FILE must not be readable by group/others (chmod 600)');
  token=readFileSync(f,'utf8').trim();
 }else token=env.WPFB_WORKER_TOKEN||'';
 delete env.WPFB_WORKER_TOKEN;delete env.WPFB_WORKER_SECRETS;
 return {token,cursorKey,jevKey,secrets};
}
export function workerServer({token,store,runner,cursorKey='',jevKey='',secrets={}}) {
 cursorKey=secrets.CURSOR_API_KEY||cursorKey;
 const held=new Set([...Object.keys(secrets),...(cursorKey?['CURSOR_API_KEY']:[]),...(jevKey?['JEV_API_KEY']:[])]).size;
 let active=false,activeTurnId=null;
 return createServer(async(req,res)=>{
  const reply=(status,data)=>{res.writeHead(status,{'Content-Type':'application/json','Cache-Control':'no-store'});res.end(JSON.stringify(data));};
  try {
   if(!['127.0.0.1:'+req.socket.localPort,'localhost:'+req.socket.localPort].includes(req.headers.host)||req.headers.origin) throw new WorkerError('host_or_origin_forbidden',403);
   if(!authenticated(req.headers.authorization,token)) throw new WorkerError('worker_unauthorized',401);
   if(req.method==='POST'&&req.url==='/status')return reply(200,{active,activeTurnId,credentials:cursorKey?'managed':'request',secrets:held});
   if(req.method!=='POST'||!['/turn','/history','/progress'].includes(req.url)) throw new WorkerError('not_found',404);
   const chunks=[];let bytes=0;for await(const chunk of req){bytes+=chunk.length;if(bytes>768000)throw new WorkerError('payload_too_large',413);chunks.push(chunk);}
   const raw=Buffer.concat(chunks).toString('utf8');
   let body;try{body=JSON.parse(raw);}catch{throw new WorkerError('invalid_json');}
   if(!body || typeof body!=='object' || Array.isArray(body) || !/^[a-z0-9_-]{8,80}$/i.test(body.site||'')||!Number.isSafeInteger(body.page)||body.page<1)throw new WorkerError('invalid_scope');
   const scope=body.site+':'+body.page;
   if(req.url==='/history')return reply(200,{turns:store.history(scope)});
   if(req.url==='/progress'){if(typeof body.requestId!=='string'||!/^[a-z0-9_-]{8,100}$/i.test(body.requestId))throw new WorkerError('invalid_request');return reply(200,store.progress(scope,body.requestId));}
   if(active)throw new WorkerError('worker_busy',429);
   active=true;activeTurnId=randomUUID();
   try{return reply(200,await store.turn({...body,...(!body.credentials?.cursor&&cursorKey?{credentials:{cursor:cursorKey}}:{}),history:store.history(scope)},runner));}finally{active=false;activeTurnId=null;}
  }catch(e){reply(e instanceof WorkerError?e.status:500,{error:e instanceof WorkerError?e.message:'worker_failure'});}
 });
}
if(process.argv[1] && import.meta.url===pathToFileURL(process.argv[1]).href){
 const port=workerPort(process.env.WPFB_WORKER_PORT);
 const {token,cursorKey,jevKey,secrets}=readSecrets();
 if(!token||token.length<32)throw new Error('worker token must contain at least 32 characters');
 process.umask(0o077);
 if(!isAbsolute(process.env.WPFB_WORKER_DATA||'')||!isAbsolute(process.env.WPFB_WORDPRESS_ROOT||'')) throw new Error('Absolute WPFB_WORKER_DATA and WPFB_WORDPRESS_ROOT are required. Data MUST be outside the web root.');
 const wp=realpathSync(process.env.WPFB_WORDPRESS_ROOT);
 mkdirSync(process.env.WPFB_WORKER_DATA,{recursive:true,mode:0o700});
 const dir=realpathSync(process.env.WPFB_WORKER_DATA),rel=relative(wp,dir);
 if(!(rel==='..'||rel.startsWith('..'+sep)) || isAbsolute(rel)) throw new Error('Worker data must be outside WordPress web root');
 const store=new TurnStore(resolve(dir,'turns.sqlite'));
 const server=workerServer({token,store,cursorKey,jevKey,secrets,runner:isolatedRunner(dir)});
 server.requestTimeout=100000;server.headersTimeout=10000;server.maxConnections=8;
 server.on('error',e=>{console.error('Builder worker cannot listen on 127.0.0.1:'+port+': '+(e.code||'error'));process.exit(1);});
 server.listen(port,'127.0.0.1',()=>console.log('Builder worker listening on loopback:'+port+'; no live acceptance performed'));
}
