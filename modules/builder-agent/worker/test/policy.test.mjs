// NON-LIVE policy/skills contracts. No real SDK/provider calls.
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync,mkdtempSync} from 'node:fs';
import {join} from 'node:path';
import {tmpdir} from 'node:os';
import {policySession} from '../policy.mjs';
import {sdkRunner} from '../sdk.mjs';
import {TurnStore} from '../core.mjs';
const defaults=()=>JSON.parse(readFileSync(new URL('../policy-default.json',import.meta.url)));
test('NON-LIVE progressive index has no instructions; locked enabled skill loads explicitly, per-run isolation',()=>{
 const data=defaults(),first=policySession(data),second=policySession(data);
 assert.equal(JSON.stringify(first.initial).includes(data.definition.skills[0].instructions),false);
 assert.equal(first.initial.skillIndex.length,3);
 data.definition.skills[0].instructions='MUTATED_AFTER_SNAPSHOT';
 const skill=first.getSkill({id:'block-selection'});assert.equal(skill.version,1);assert.notEqual(skill.instructions,'MUTATED_AFTER_SNAPSHOT');
 assert.equal(first.getSkill({id:'missing'}).error,'skill_unavailable');assert.equal(first.getSkill({id:'block-selection',path:'/tmp'}).error,'skill_unavailable');
 assert.equal(first.provenance().loadedSkills.length,1);assert.equal(second.provenance().loadedSkills.length,0);
});
test('NON-LIVE enabled tools are immutable platform intersection; disabled requirements cannot load/enable skills',()=>{
 const data=defaults();data.definition.tools=['get_block_details'];
 const p=policySession(data);assert.equal(p.allowed('propose_layout'),false);assert.equal(p.allowed('shell'),false);assert.equal(p.allowed('get_skill'),true);
 assert.deepEqual(p.initial.skillIndex.map(s=>s.id),['page-review']);assert.equal(p.getSkill({id:'block-selection'}).error,'skill_unavailable');
 data.definition.tools=[];const disabled=policySession(data);assert.equal(disabled.allowed('get_skill'),false);assert.equal(disabled.initial.skillIndex.length,0);
});
test('NON-LIVE policy inputs cannot add platform, SDK options, keys, arbitrary tools/paths or oversized skills',()=>{
 const cases=[{...defaults(),platform:'override'},{...defaults(),cursor_key:'NONLIVE'}, {...defaults(),brief:'x'.repeat(4001)}, {...defaults(),policyVersion:0}];
 for(const mutate of [d=>d.definition.tools.push('shell'),d=>d.definition.skills[0].requiredTools.push('network'),d=>d.definition.skills[0].path='/tmp',d=>d.definition.skills[0].instructions='x'.repeat(6001),d=>d.definition.site.constructor='polluted',d=>d.definition.skills.push(d.definition.skills[0])]){const d=defaults();mutate(d);cases.push(d);}
 for(const bad of cases)assert.throws(()=>policySession(bad),/policy_context_invalid/);assert.equal(Object.prototype.polluted,undefined);
});
test('NON-LIVE agent receives only permitted custom tools; active version/skills persist and replay without another send',async()=>{
 const data=defaults();data.policyVersion=9;data.briefVersion=3;data.brief='Only page A';data.definition.tools=['get_block_details'];let sends=0;
 const fake={Cursor:{models:{list:async()=>[{id:'grok-4.7'}]}},JsonlLocalAgentStore:class{},Agent:{create:async o=>{
  assert.deepEqual(Object.keys(o.local.customTools).sort(),['get_block_details','get_skill']);
  assert.equal(o.local.customTools.get_skill.execute({id:'page-review'}).version,1);
  return {agentId:'nonlive-agent',send:async text=>{sends++;assert.ok(text.includes('Only page A'));assert.ok(!text.includes(data.definition.skills[2].instructions));return {id:'nonlive-policy',wait:async()=>({status:'finished',result:'NON-LIVE policy response'}),cancel:async()=>{}};},close(){}};
 }}};
 const store=new TurnStore(':memory:'),body={site:'nonlive-site',page:12,requestId:'nonlive-policy-turn',content:'NON-LIVE',credentials:{cursor:'TEST_ONLY'},context:{source:'ui8kit',target:'latte',version:1,catalog:[],blocks:[],agentPolicy:data}};
 const run=sdkRunner(mkdtempSync(join(tmpdir(),'wpfb-policy-')),async()=>fake);
 const result=await store.turn(body,run);assert.equal(result.agentPolicy.policyVersion,9);assert.equal(result.agentPolicy.briefVersion,3);assert.deepEqual(result.agentPolicy.loadedSkills,[{id:'page-review',version:1}]);
 assert.ok(!JSON.stringify(result.agentPolicy).includes('Only page A'));
 assert.deepEqual(await store.turn(body,()=>{throw Error('must replay');}),result);assert.equal(sends,1);await assert.rejects(()=>store.turn({...body,context:{...body.context,agentPolicy:{...data,policyVersion:10}}},()=>{throw Error('must not resend');}),/idempotency_conflict/);assert.equal(store.history('nonlive-site:13').length,0);store.db.close();
});

test('NON-LIVE PHP effective-preview golden equals actual worker initial payload',()=>{assert.deepEqual(policySession().initial,JSON.parse(readFileSync(new URL('../../../../tests/fixtures/agent-policy/effective.v1.json',import.meta.url))));});
