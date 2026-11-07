import { expect, test } from "bun:test";
import { proposalPresentation } from "../src/chat/proposal";
const turn = { requestId: "fixture-history", state: "done" };
test("applied proposal reload retains original applied revision and cannot apply again", () => {
 const view = proposalPresentation({...turn,application:{state:"applied",baseVersion:1,appliedVersion:2,currentVersion:3,canApply:true}});
 expect(view.appliedVersion).toBe(2);expect(view.currentVersion).toBe(3);expect(view.canApply).toBe(false);
});
test("stale and missing application state fail closed; only matching pending can apply", () => {
 expect(proposalPresentation(turn).canApply).toBe(false);
 expect(proposalPresentation({...turn,application:{state:"stale",baseVersion:1,appliedVersion:null,currentVersion:2,canApply:true}}).canApply).toBe(false);
 expect(proposalPresentation({...turn,application:{state:"pending",baseVersion:2,appliedVersion:null,currentVersion:2,canApply:true}}).canApply).toBe(true);
});
