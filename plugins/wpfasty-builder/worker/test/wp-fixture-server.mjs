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
 const hit=search.search({spec:'block.about',limit:1}).results[0];
 if(!hit)throw Error('NON-LIVE real local map/installed catalog mismatch');
 const details=search.details({blockId:hit.blockId,variant:hit.variant});
 const block={id:details.blockId,variants:[{id:details.variant}]};
 return {agentPolicy:policy.provenance(),answer:'NON-LIVE injected contract fixture; not a model response',proposal:[{id:'fixture-about',block:block.id,variant:block.variants[0].id,props:{},bindings:{}}]};
}});
server.listen(workerPort(process.env.WPFB_CONTRACT_PORT),'127.0.0.1',()=>console.log('NON-LIVE fixture ready on loopback'));
