import {sdkRunner} from './sdk.mjs';
// Exit when the worker dies (IPC channel closes): no orphaned SDK child after a crash.
process.on('disconnect',()=>process.exit(1));
process.once('message',async({dataDir,body})=>{
 try{const result=await sdkRunner(dataDir,undefined,progress=>{if(process.connected)process.send({progress});})(body);process.send({ok:true,result});}
 catch(error){process.send({ok:false,code:['configured_grok_model_unavailable','catalog_map_unavailable','catalog_context_invalid','catalog_target_unsupported','policy_context_invalid'].includes(error?.message)?error.message:'agent_failed_no_automatic_retry'});}
});
