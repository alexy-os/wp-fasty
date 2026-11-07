import {test, expect} from "bun:test";
import {watchProgress} from "../../../../modules/builder-agent/editor/progress";
import type {ChatProgress} from "../../../../modules/builder-agent/editor/client";
const event=(id="fixture-request",sequence=1):ChatProgress=>({requestId:id,state:"pending",sequence,tool:{name:"catalog_lookup",status:"running"}});
const drain=()=>new Promise<void>(r=>queueMicrotask(()=>queueMicrotask(r)));
test("NON-LIVE unmount/cancel aborts in-flight GET; late response cannot repaint disposed page",async()=>{
 let resolve!:(v:ChatProgress)=>void;let signal!:AbortSignal;const updates:ChatProgress[]=[];let failures=0;
 const stop=watchProgress(async(id,s)=>{signal=s;return new Promise(r=>resolve=r);},"fixture-request",v=>updates.push(v),()=>failures++);
 stop();resolve(event());await drain();expect(signal.aborted).toBe(true);expect(updates.length).toBe(0);expect(failures).toBe(0);
});
test("NON-LIVE another page/turn response is rejected, not displayed; no request POST/retry",async()=>{
 let calls=0,failures=0;const values:ChatProgress[]=[];
 const stop=watchProgress(async()=>{calls++;return event("other-turn");},"fixture-request",v=>values.push(v),()=>failures++);
 await drain();stop();expect(calls).toBe(1);expect(failures).toBe(1);expect(values).toEqual([]);
});
test("NON-LIVE observation errors stop once; completed state clears previous tool even after sequence reset",async()=>{
 let failures=0,calls=0;const stop=watchProgress(async()=>{calls++;throw Error("offline");},"fixture-request",()=>{},()=>failures++);await drain();stop();expect(calls).toBe(1);expect(failures).toBe(1);
 const values:ChatProgress[]=[];const done=watchProgress(async()=>({...event(),state:"done",tool:null,sequence:0}),"fixture-request",v=>values.push(v),()=>{});await drain();done();expect(values.at(-1)?.tool).toBe(null);expect(values.at(-1)?.state).toBe("done");
});

test("NON-LIVE worker restart sequence reset stops stale tool display; terminal reset is legitimate",async()=>{
 let calls=0,failures=0,failed!:()=>void;const observed=new Promise<void>(r=>failed=r);
 const stop=watchProgress(async()=>event("fixture-request",++calls===1?4:0),"fixture-request",()=>{},()=>{failures++;failed();},0,1000);
 await observed;stop();expect(calls).toBe(2);expect(failures).toBe(1);
 const values:ChatProgress[]=[];let done!:()=>void;const completed=new Promise<void>(r=>done=r);calls=0;
 const end=watchProgress(async()=>++calls===1?event("fixture-request",4):({...event(),state:"done",sequence:0,tool:null}),"fixture-request",v=>{values.push(v);if(v.state==="done")done();},()=>{throw Error("terminal must not fail");},0,1000);
 await completed;end();expect(values.map(v=>v.state)).toEqual(["pending","done"]);expect(values.at(-1)?.tool).toBe(null);
});
