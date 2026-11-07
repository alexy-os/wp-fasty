import { createServer } from 'node:http';
import { mkdirSync,realpathSync } from 'node:fs';
import { resolve,isAbsolute,relative,sep } from 'node:path';
import { pathToFileURL } from 'node:url';
import { TurnStore,WorkerError,authenticated } from './core.mjs';
import {isolatedRunner} from './isolated-runner.mjs';
export function workerServer({token,store,runner}) {
 let active=false;
 return createServer(async(req,res)=>{
  const reply=(status,data)=>{res.writeHead(status,{'Content-Type':'application/json','Cache-Control':'no-store'});res.end(JSON.stringify(data));};
  try {
   if(!['127.0.0.1:'+req.socket.localPort,'localhost:'+req.socket.localPort].includes(req.headers.host)||req.headers.origin) throw new WorkerError('host_or_origin_forbidden',403);
   if(!authenticated(req.headers.authorization,token)) throw new WorkerError('worker_unauthorized',401);
   if(req.method!=='POST'||!['/turn','/history'].includes(req.url)) throw new WorkerError('not_found',404);
   const chunks=[];let bytes=0;for await(const chunk of req){bytes+=chunk.length;if(bytes>768000)throw new WorkerError('payload_too_large',413);chunks.push(chunk);}
   const raw=Buffer.concat(chunks).toString('utf8');
   let body;try{body=JSON.parse(raw);}catch{throw new WorkerError('invalid_json');}
   if(!body || typeof body!=='object' || Array.isArray(body) || !/^[a-z0-9_-]{8,80}$/i.test(body.site||'')||!Number.isSafeInteger(body.page)||body.page<1)throw new WorkerError('invalid_scope');
   const scope=body.site+':'+body.page;
   if(req.url==='/history')return reply(200,{turns:store.history(scope)});
   if(active)throw new WorkerError('worker_busy',429);
   active=true;
   try{return reply(200,await store.turn({...body,history:store.history(scope)},runner));}finally{active=false;}
  }catch(e){reply(e instanceof WorkerError?e.status:500,{error:e instanceof WorkerError?e.message:'worker_failure'});}
 });
}
if(process.argv[1] && import.meta.url===pathToFileURL(process.argv[1]).href){
 const token=process.env.WPFB_WORKER_TOKEN;
 if(!token||token.length<32)throw new Error('WPFB_WORKER_TOKEN must contain at least 32 characters');
 process.umask(0o077);
 if(!isAbsolute(process.env.WPFB_WORKER_DATA||'')||!isAbsolute(process.env.WPFB_WORDPRESS_ROOT||'')) throw new Error('Absolute WPFB_WORKER_DATA and WPFB_WORDPRESS_ROOT are required. Data MUST be outside the web root.');
 const wp=realpathSync(process.env.WPFB_WORDPRESS_ROOT);
 mkdirSync(process.env.WPFB_WORKER_DATA,{recursive:true,mode:0o700});
 const dir=realpathSync(process.env.WPFB_WORKER_DATA),rel=relative(wp,dir);
 if(!(rel==='..'||rel.startsWith('..'+sep)) || isAbsolute(rel)) throw new Error('Worker data must be outside WordPress web root');
 const store=new TurnStore(resolve(dir,'turns.sqlite'));
 const server=workerServer({token,store,runner:isolatedRunner(dir)});
 server.requestTimeout=100000;server.headersTimeout=10000;server.maxConnections=8;
 server.listen(18991,'127.0.0.1',()=>console.log('Builder worker listening on loopback:18991; no live acceptance performed'));
}
