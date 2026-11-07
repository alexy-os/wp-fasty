// EXPLICIT NON-LIVE WP integration fixture; never deployed by production start command.
import {TurnStore} from '../core.mjs';
import {workerPort} from '../server.mjs';
import {workerServer} from '../server.mjs';
const store=new TurnStore(':memory:');
const server=workerServer({token:'nonlive-'.repeat(5),store,runner:async body=>{
 const block=body.context.catalog[0];
 return {answer:'NON-LIVE injected contract fixture; not a model response',proposal:[{id:'fixture-about',block:block.id,variant:block.variants[0].id,props:{},bindings:{}}]};
}});
server.listen(workerPort(process.env.WPFB_CONTRACT_PORT),'127.0.0.1',()=>console.log('NON-LIVE fixture ready on loopback'));
