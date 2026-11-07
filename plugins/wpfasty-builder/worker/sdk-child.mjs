import {sdkRunner} from './sdk.mjs';
// Exit when the worker dies (IPC channel closes): no orphaned SDK child after a crash.
process.on('disconnect',()=>process.exit(1));
process.once('message',async({dataDir,body})=>{
 try{const result=await sdkRunner(dataDir)(body);process.send({ok:true,result});}
 catch(error){process.send({ok:false,code:error?.message==='configured_grok_model_unavailable'?error.message:'agent_failed_no_automatic_retry'});}
});
