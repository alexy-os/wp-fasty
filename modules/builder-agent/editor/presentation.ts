import type { TokenUsage, AgentPolicyProvenance } from "./client";
import { chatCopy as copy } from "./content";
export function usageLabel(usage?: TokenUsage | null): string {
  if (!usage || [usage.totalTokens, usage.inputTokens, usage.outputTokens, usage.cacheReadTokens, usage.cacheWriteTokens].some(n => !Number.isSafeInteger(n) || n < 0)) return copy.usageUnavailable;
  return copy.usage(usage);
}
export type MessagePart = { kind: "paragraph" | "heading" | "code"; text: string; json?: boolean };
/** Deliberately small Markdown subset. HTML and URLs remain escaped text, never injected or interpreted. */
export function messageParts(value: string): MessagePart[] {
  const lines = value.slice(0, 32000).replace(/\r\n?/g, "\n").split("\n");
  const parts: MessagePart[] = [];
  let buffer: string[] = [], fenced = false, json = false;
  const flush = () => { if (buffer.length) parts.push({kind: fenced ? "code" : "paragraph", text: buffer.join("\n"), json}); buffer = []; };
  for (const line of lines) {
    if (/^\s*```/.test(line)) { flush(); if (!fenced) json = /^\s*```json\s*$/i.test(line); fenced = !fenced; continue; }
    if (!fenced && /^#{1,6}\s+/.test(line)) { flush(); parts.push({kind: "heading", text: line.replace(/^#{1,6}\s+/, "")}); }
    else if (!fenced && !line.trim()) flush();
    else buffer.push(line);
  }
  flush(); return parts;
}
export function inlineParts(text: string): {text: string; kind: "plain" | "strong" | "code"}[] {
  return text.split(/(\*\*[^*\n]+\*\*|`[^`\n]+`)/g).filter(Boolean).map(text => text.startsWith("**") ? {kind: "strong", text: text.slice(2, -2)} : text.startsWith("`") ? {kind: "code", text: text.slice(1, -1)} : {kind: "plain", text});
}
/** Lexical colour only; all token text is escaped by Svelte. Joining tokens reproduces the input byte-for-byte. */
export function jsonTokens(text: string): {text: string; kind: string}[] {
  const pattern = /"(?:\\.|[^"\\])*"(?:\s*:)?|\b(?:true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|[^"\w\d-]+|./g;
  return (text.match(pattern) ?? []).map(text => ({text, kind: text.startsWith('"') ? (text.endsWith(":") ? "key" : "string") : /^-?\d/.test(text) ? "number" : /^(true|false|null)$/.test(text) ? "literal" : "plain"}));
}

/** Version-only provenance; legacy/unreported policy is not invented. */
export function policyLabel(value?: AgentPolicyProvenance | null): string | null {
  if (!value || !Number.isSafeInteger(value.policyVersion) || value.policyVersion < 1 || !Number.isSafeInteger(value.briefVersion) || value.briefVersion < 1 || !Array.isArray(value.loadedSkills) || value.loadedSkills.length > 12 || value.loadedSkills.some(s => !s || typeof s.id !== "string" || !/^[a-z][a-z0-9-]{0,63}$/.test(s.id) || !Number.isSafeInteger(s.version) || s.version < 1)) return null;
  return copy.policy(value.policyVersion, value.briefVersion, value.loadedSkills.map(s => `${s.id}@${s.version}`).join(", "));
}
