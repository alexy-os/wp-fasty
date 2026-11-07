/** Application instructions, NOT an SDK systemPrompt override or a permission grant. */
import {readFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
import {WorkerError} from './core.mjs';
const defaults=JSON.parse(readFileSync(new URL('./policy-default.json',import.meta.url)));
const platformTools=['search_catalog','get_block_details','propose_layout'];
const object=v=>!!v&&typeof v==='object'&&!Array.isArray(v)&&Object.getPrototypeOf(v)===Object.prototype;
const requireValue=ok=>{if(!ok)throw new WorkerError('policy_context_invalid',503);};
const keys=(v,list)=>requireValue(object(v)&&Object.keys(v).length===list.length&&list.every(k=>Object.hasOwn(v,k)));
const text=(v,n)=>requireValue(typeof v==='string'&&Buffer.byteLength(v)<=n&&!/[\x00-\x08\x0B\x0C\x0E-\x1F]/.test(v));
const strings=(v,n,length)=>{requireValue(Array.isArray(v)&&v.length<=n&&new Set(v).size===v.length);v.forEach(s=>text(s,length));};
const version=v=>Number.isSafeInteger(v)&&v>=1&&v<=1e9;
export function policySession(raw=defaults){
 try{raw=JSON.parse(JSON.stringify(raw));}catch{throw new WorkerError('policy_context_invalid',503);}
 requireValue(Buffer.byteLength(JSON.stringify(raw))<=72000);
 keys(raw,['schema','policyVersion','briefVersion','definition','brief']);requireValue(raw.schema==='wpfasty-agent-policy/1'&&version(raw.policyVersion)&&version(raw.briefVersion));text(raw.brief,4000);
 const d=raw.definition;keys(d,['product','site','tools','skills']);text(d.product,4000);keys(d.site,['language','tone','audience','brand','facts']);Object.values(d.site).forEach(v=>text(v,2000));
 strings(d.tools,3,40);requireValue(d.tools.every(t=>platformTools.includes(t)));
 const allowedTools=platformTools.filter(t=>d.tools.includes(t));requireValue(Array.isArray(d.skills)&&d.skills.length<=12);
 const registry=new Map(),ids=new Set();
 for(const s of d.skills){
  keys(s,['id','version','enabled','purpose','triggers','instructions','requiredTools']);requireValue(typeof s.id==='string'&&/^[a-z][a-z0-9-]{0,63}$/.test(s.id)&&!['constructor','prototype'].includes(s.id)&&!ids.has(s.id)&&version(s.version)&&s.version<=1000000&&typeof s.enabled==='boolean');ids.add(s.id);
  text(s.purpose,300);text(s.instructions,6000);strings(s.triggers,6,100);strings(s.requiredTools,3,40);requireValue(s.requiredTools.every(t=>platformTools.includes(t)));
  if(s.enabled&&s.requiredTools.every(t=>allowedTools.includes(t)))registry.set(s.id,s);
 }
 const skillIndex=[...registry.values()].map(({id,version,purpose,triggers,requiredTools})=>({id,version,purpose,triggers,requiredTools}));
 const initial={schema:raw.schema,policyVersion:raw.policyVersion,briefVersion:raw.briefVersion,product:d.product,site:d.site,brief:raw.brief,allowedTools,skillIndex};
 const loaded=new Map();
 return {initial,allowed:name=>name==='get_skill'?registry.size>0:allowedTools.includes(name),getSkill:args=>{
  if(!object(args)||Object.keys(args).length!==1||typeof args.id!=='string'||!registry.has(args.id))return {error:'skill_unavailable'};
  const s=registry.get(args.id);loaded.set(s.id,s.version);return {...skillIndex.find(v=>v.id===s.id),instructions:s.instructions,notice:'Approved app-level instructions only; no tools/permissions are added by loading a skill.'};
 },provenance:()=>({policyVersion:raw.policyVersion,briefVersion:raw.briefVersion,policyDigest:'sha256:'+createHash('sha256').update(JSON.stringify(raw)).digest('hex'),loadedSkills:[...loaded].map(([id,version])=>({id,version}))})};
}
