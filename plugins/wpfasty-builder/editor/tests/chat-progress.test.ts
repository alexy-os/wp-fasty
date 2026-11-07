import {test, expect} from "bun:test";
import {watchProgress} from "../src/chat/progress";
import type {ChatProgress} from "../src/chat/client";
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
