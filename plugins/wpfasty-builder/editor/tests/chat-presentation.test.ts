import {test, expect} from "bun:test";
import {readFileSync} from "node:fs";
import {messageParts, inlineParts, jsonTokens, usageLabel} from "../../../../modules/builder-agent/editor/presentation";
import {chatCopy as copy} from "../../../../modules/builder-agent/editor/content";
test("NON-LIVE actual usage is not estimated; old/unreported history stays unavailable, reported zero stays zero", () => {
 expect(usageLabel()).toBe(copy.usageUnavailable);
 expect(usageLabel(null)).toBe(copy.usageUnavailable);
 const u={inputTokens:100,outputTokens:20,cacheReadTokens:5,cacheWriteTokens:3,totalTokens:128};
 expect(usageLabel(u)).toContain("128 tokens");
 expect(usageLabel({...u,totalTokens:NaN})).toBe(copy.usageUnavailable);
 expect(usageLabel({...u,totalTokens:0})).toContain("0 tokens");
});
test("NON-LIVE fenced JSON and Markdown subset preserve hostile HTML/URLs as inert text", () => {
 const text='## Draft\n\n**Saved** `hero` <img onerror=alert(1)> [x](javascript:alert(1))\n\n```json\n{"title":"<script>bad</script>","n":12,"ok":true}\n```';
 const blocks=messageParts(text);expect(blocks.map(b=>b.kind)).toEqual(["heading","paragraph","code"]);
 expect(blocks[1]!.text).toContain("javascript:alert(1)");expect(blocks[2]!.json).toBe(true);
 expect(inlineParts('**Saved** `hero`').map(x=>x.kind)).toEqual(["strong","plain","code"]);
 const tokens=jsonTokens(blocks[2]!.text);expect(tokens.map(t=>t.text).join("")).toBe(blocks[2]!.text);
 expect(tokens.some(t=>t.kind==="key")).toBe(true);expect(tokens.some(t=>t.kind==="string"&&t.text.includes("<script>"))).toBe(true);
 expect(messageParts('```\nunterminated')[0]).toMatchObject({kind:"code",text:"unterminated"});
});
test("NON-LIVE JSON lexing is lossless for whitespace, escapes, Unicode and adversarial punctuation", () => {
 for(const value of [JSON.stringify({title:'x\"<>&',emoji:'☀',n:-1.4e20,none:null},null,2),'\n"unterminated \n - foo', '123\ntrue null <tag>']) expect(jsonTokens(value).map(t=>t.text).join("")).toBe(value);
});
test("chat components use escaped primitive composition, real poll callback, independent role cards and telemetry footer", () => {
 const read=(name:string)=>readFileSync(new URL('../../../../modules/builder-agent/editor/'+name,import.meta.url),'utf8');
 for(const name of ["ChatPanel.svelte","MessageBody.svelte","CodeBox.svelte"]){expect(read(name)).not.toContain("{@html");expect(read(name)).not.toMatch(/<(?:div|p|span|pre|code|button)\b/);}
 const panel=read("ChatPanel.svelte");expect(panel).toContain('usageLabel(turn.usage)');expect(panel).toContain('watchProgress(progress, requestId');expect(panel).toContain('disposed = true; stopObserving?.()');expect(panel).toContain('wpfb-chat-user');expect(panel).toContain('wpfb-chat-assistant');
});

test('NON-LIVE policy provenance footer does not invent versions or render instruction text', async () => {
  const {policyLabel} = await import('../../../../modules/builder-agent/editor/presentation');
  expect(policyLabel(null)).toBeNull();
  expect(policyLabel({policyVersion:9,briefVersion:3,policyDigest:'sha256:'+'a'.repeat(64),loadedSkills:[{id:'page-review',version:2}]})).toBe('Policy v9 · brief v3 · skills page-review@2');
  expect(policyLabel({policyVersion:0,briefVersion:1,policyDigest:'',loadedSkills:[]})).toBeNull();
});
