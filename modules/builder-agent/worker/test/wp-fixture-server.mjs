import assert from 'node:assert/strict';
// EXPLICIT NON-LIVE WP integration fixture; never deployed by production start command.
import {policySession} from '../policy.mjs';
import {catalogSession} from '../catalog-search.mjs';
import {TurnStore} from '../core.mjs';
import {workerPort} from '../server.mjs';
import {workerServer} from '../server.mjs';
const store=new TurnStore(':memory:');
const server=workerServer({token:'nonlive-'.repeat(5),store,runner:async body=>{
 const policy=policySession(body.context.agentPolicy);policy.getSkill({id:'block-selection'});
 const search=await catalogSession(body.context);
 if(body.content.includes('NON-LIVE rewrite existing text')){
  assert.equal(body.context.blocks.length,3);
  const proposal=body.context.blocks.map(old=>{
   const changed={...old,props:{...old.props,title:'Мастерская: '+old.id}};
   assert.throws(()=>search.validateChanges([changed]),/catalog_details_required/);
   const details=search.details({blockId:old.block,variant:old.variant});
   assert.ok(details.props.some(p=>p.name==='title'&&p.type==='string'));
   search.validateChanges([changed]);return changed;
  });
  return {agentPolicy:policy.provenance(),answer:'NON-LIVE text rewrite only',proposal};
 }
 const assembly=body.content.includes('NON-LIVE page assembly');
 const specs=assembly?['hero','about','cta']:['about'];
 const proposal=specs.map(spec=>{
  const hit=search.search({spec:'block.'+spec,limit:1}).results[0];
  if(!hit)throw Error('NON-LIVE real local map/installed catalog mismatch: '+spec);
  const probe={id:'nonlive-details-probe',block:hit.blockId,variant:hit.variant,props:{},bindings:{}};
  assert.throws(()=>search.validateChanges([probe]),/catalog_details_required/);
  const details=search.details({blockId:hit.blockId,variant:hit.variant});
  const block={id:assembly?'fixture-'+spec:'fixture-about',block:details.blockId,variant:details.variant,props:{},bindings:{}};
  search.validateChanges([block]);
  return block;
 });
 return {agentPolicy:policy.provenance(),answer:'NON-LIVE injected tool contract fixture; search/details/validate before proposal, not a model response',proposal};
}});
server.listen(workerPort(process.env.WPFB_CONTRACT_PORT),'127.0.0.1',()=>console.log('NON-LIVE fixture ready on loopback'));
