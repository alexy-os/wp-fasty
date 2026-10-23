// Rendered-HTML gate: html-validate (HTML5 + content model, e.g. no <div> in <p>, no interactive inside interactive)
// and axe-core in jsdom (ARIA, landmarks, form labels). Input: [{ id, html }] fragments or full documents.
import { HtmlValidate } from "html-validate";
import { JSDOM, VirtualConsole } from "jsdom";
import { createRequire } from "node:module";
import { readFileSync } from "node:fs";

const axeSource = readFileSync(createRequire(import.meta.url).resolve("axe-core/axe.min.js"), "utf8");

/** A fragment becomes a minimal valid document: lang, title, one <main> landmark. */
export const wrap = (html) => (/^\s*<!doctype/i.test(html) ? html : `<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>case</title></head><body><main>${html}</main></body></html>`);

export const HTML_VALIDATE_CONFIG = {
  extends: ["html-validate:recommended"],
  rules: {
    // presentation choices of a design system, not validity
    "no-inline-style": "off", "attribute-boolean-style": "off", "attribute-empty-style": "off", "no-trailing-whitespace": "off",
    "void-style": "off", "no-redundant-role": "off", "prefer-native-element": "off", "no-implicit-button-type": "off",
    "tel-non-breaking": "off", "text-content": "error", "wcag/h37": "error", "no-dup-id": "error",
  },
};

export async function checkHtml(cases, { config = HTML_VALIDATE_CONFIG, axeRules = {} } = {}) {
  const hv = new HtmlValidate(config), out = [];
  for (const c of cases) {
    const doc = wrap(c.html);
    const report = await hv.validateString(doc);
    for (const r of report.results) for (const m of r.messages) out.push({ id: c.id, tool: "html-validate", rule: m.ruleId, message: m.message });
    const dom = new JSDOM(doc, { runScripts: "outside-only", virtualConsole: new VirtualConsole() });
    dom.window.eval(axeSource);
    const res = await dom.window.axe.run(dom.window.document, { rules: { "color-contrast": { enabled: false }, ...axeRules }, resultTypes: ["violations"] });
    for (const v of res.violations) out.push({ id: c.id, tool: "axe", rule: v.id, message: `${v.help}: ${v.nodes.map((n) => n.html).join(" | ").slice(0, 300)}` });
    dom.window.close();
  }
  return out;
}
