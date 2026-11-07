/** Public telemetry DTO. No SDK arguments, tool results, thinking, prompts or prices cross this boundary. */
const counts=['inputTokens','outputTokens','cacheReadTokens','cacheWriteTokens','totalTokens'];
export function tokenUsage(value) {
 if(!value||typeof value!=='object'||counts.some(k=>!Number.isSafeInteger(value[k])||value[k]<0||value[k]>1e12))return null;
 const safe=Object.fromEntries(counts.map(k=>[k,value[k]]));
 if(value.reasoningTokens!==undefined){if(!Number.isSafeInteger(value.reasoningTokens)||value.reasoningTokens<0||value.reasoningTokens>1e12)return null;safe.reasoningTokens=value.reasoningTokens;}
 return safe;
}
export function toolProgress(value) {
 if(!value||!['catalog_lookup','search_catalog','get_block_details','propose_layout'].includes(value.name)||!['running','completed','error'].includes(value.status))return null;
 return {name:value.name,status:value.status};
}
/** Only real tool_call SDK events, allowlisting known custom tools; never forward arbitrary event text. */
export function sdkToolProgress(message) {
 if(message?.type!=='tool_call'||typeof message.name!=='string')return null;
 const name=['catalog_lookup','search_catalog','get_block_details','propose_layout'].find(n=>message.name===n||message.name.endsWith('__'+n));
 return toolProgress({name,status:message.status});
}
