import { DatabaseSync } from 'node:sqlite';
import { createHash, timingSafeEqual } from 'node:crypto';
export class WorkerError extends Error { constructor(code, status=400) { super(code); this.status=status; } }
export function validateProposal(blocks, catalog) {
  if (!Array.isArray(blocks) || blocks.length>100) throw new WorkerError('invalid_blocks');
  if(Buffer.byteLength(JSON.stringify(blocks))>256000) throw new WorkerError('invalid_blocks');
  const ids=new Set();
  for (const b of blocks) {
    const def=catalog.find(x=>x.id===b?.block);
    if (!b || typeof b.id!=='string' || !/^[a-z][a-z0-9_-]{0,62}$/.test(b.id) || ids.has(b.id) || !def?.variants?.some(v=>v.id===b.variant) || !b.props || Array.isArray(b.props) || typeof b.props!=='object' || !b.bindings || Array.isArray(b.bindings) || typeof b.bindings!=='object') throw new WorkerError('invalid_catalog_block');
    ids.add(b.id);
  }
  return blocks;
}
export function authenticated(actual, token) {
 const expected=Buffer.from('Bearer '+token), got=Buffer.from(actual||'');
 return expected.length===got.length && timingSafeEqual(expected,got);
}
export class TurnStore {
 constructor(file) { this.db=new DatabaseSync(file); this.db.exec('PRAGMA journal_mode=WAL; PRAGMA max_page_count=65536; PRAGMA busy_timeout=1000; CREATE TABLE IF NOT EXISTS turns(scope TEXT,request TEXT,hash TEXT,state TEXT,result TEXT, PRIMARY KEY(scope,request))'); }
 history(scope) { const turns=this.db.prepare('SELECT request,state,result FROM turns WHERE scope=? ORDER BY rowid DESC LIMIT 30').all(scope).reverse().map(row=>({requestId:row.request,state:row.state,...(row.result?JSON.parse(row.result):{})})); while(turns.length>1 && Buffer.byteLength(JSON.stringify(turns))>400000) turns.shift();return turns; }
 async turn(body,run) {
  const {site,page,requestId,content,context}=body;
  if (!/^[a-z0-9_-]{8,80}$/i.test(site||'') || !Number.isSafeInteger(page)||page<1 || !/^[a-z0-9_-]{8,100}$/i.test(requestId||'') || typeof content!=='string'||!content.trim()||content.length>8000 || !context || !/^[a-z][a-z0-9_-]{0,62}$/.test(context.source||'') || !Array.isArray(context.catalog)||context.catalog.length>1000 || !Array.isArray(context.blocks)||!Number.isInteger(context.version)) throw new WorkerError('invalid_turn');
  const scope=site+':'+page;
  const hash=createHash('sha256').update(JSON.stringify({content,context})).digest('hex');
  const previous=this.db.prepare('SELECT * FROM turns WHERE scope=? AND request=?').get(scope,requestId);
  if(previous) {
   if(previous.hash!==hash) throw new WorkerError('idempotency_conflict',409);
   if(previous.state!=='done') throw new WorkerError('turn_not_replayable_check_history',409);
   return JSON.parse(previous.result);
  }
  if(this.db.prepare("SELECT 1 FROM turns WHERE scope=? AND state='pending'").get(scope)) throw new WorkerError('page_busy_or_interrupted',409);
  this.db.prepare("INSERT INTO turns VALUES(?,?,?,'pending',NULL)").run(scope,requestId,hash);
  try {
   const result=await run(body);
   if(result.proposal) validateProposal(result.proposal,context.catalog);
   const safe={content,source:context.source,answer:String(result.answer||'').slice(0,32000),proposal:result.proposal||null,baseVersion:context.version,...(result.evidence?{evidence:result.evidence}:{})};
   this.db.prepare("UPDATE turns SET state='done',result=? WHERE scope=? AND request=?").run(JSON.stringify(safe),scope,requestId);
   return safe;
  } catch(e) {
   this.db.prepare("UPDATE turns SET state='failed',result=? WHERE scope=? AND request=?").run(JSON.stringify({content,error:'agent_failed_no_automatic_retry'}),scope,requestId);
   if(e instanceof WorkerError && e.message==='configured_grok_model_unavailable') throw e;
   throw new WorkerError('agent_failed_no_automatic_retry',502);
  }
 }
}
