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
