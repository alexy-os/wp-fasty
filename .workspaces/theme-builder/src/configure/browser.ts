// Browser entry of the static configurator form (bundled into configurator/index.html by form.ts).
import { AA, checkTokens } from "./contrast";
import { buildManifest, ConfigureError, type Data, type J, slugify } from "./core";

declare const WPFASTY_DATA: Data & { fieldTypes: string[] };
const data = WPFASTY_DATA;
const $ = <T extends HTMLElement>(id: string) => document.getElementById(id) as T;
const val = (id: string) => ($<HTMLInputElement>(id).value ?? "").trim();
const checked = (id: string) => $<HTMLInputElement>(id).checked;

function landingsFor(arch: string): string[] {
  return (data.examples[arch]?.builder?.landings ?? []).map((l: J) => l.file);
}

function refreshArchetype(): void {
  const arch = val("archetype");
  const ex = data.examples[arch];
  $("arch-info").textContent = `${ex.theme.description} Required post types: ${(ex.postTypes ?? []).map((p: J) => p.name).join(", ") || "none"}.`;
  $("landings").innerHTML = landingsFor(arch)
    .map((f) => `<label><input type="checkbox" name="landing" value="${f}" checked> ${f.split("/").pop()}</label>`).join("");
  $<HTMLInputElement>("comments").checked = Boolean(ex.features?.comments);
  $<HTMLInputElement>("fullWidth").checked = Boolean(ex.features?.fullWidth);
  $<HTMLInputElement>("currency").value = ex.settings?.currency ?? "";
}

function answers(): J {
  const a: J = { archetype: val("archetype"), name: val("name") };
  if (val("slug")) a.slug = val("slug");
  if (val("description")) a.description = val("description");
  if (val("brandMode") === "palette") a.palette = val("palette");
  else a.tokens = JSON.parse(val("tokens") || "{}");
  a.features = { comments: checked("comments"), fullWidth: checked("fullWidth") };
  if (val("currency")) a.currency = val("currency");
  a.landings = [...document.querySelectorAll<HTMLInputElement>("input[name=landing]:checked")].map((i) => i.value);
  const extra = val("postTypes");
  if (extra) a.postTypes = JSON.parse(extra);
  a.output = { wporg: checked("wporg"), includeDemo: checked("includeDemo"), playground: checked("playground") };
  return a;
}

let current: J | null = null;
function build(): void {
  const out = $("result");
  const dl = $<HTMLButtonElement>("download");
  current = null;
  dl.disabled = true;
  try {
    const { manifest, warnings } = buildManifest(answers() as any, data);
    current = manifest;
    out.className = "ok";
    out.textContent = (warnings.length ? `warnings:\n- ${warnings.join("\n- ")}\n\n` : "") + JSON.stringify(manifest, null, 2);
    dl.disabled = false;
  } catch (e) {
    out.className = "err";
    out.textContent = e instanceof ConfigureError ? e.message : `error: ${(e as Error).message}`;
  }
}

function contrastReport(): void {
  try {
    const t = JSON.parse(val("tokens") || "{}");
    const issues = checkTokens({ light: t.light, dark: t.dark });
    $("contrast").textContent = issues.length
      ? issues.map((i) => `✗ ${i.scheme}: ${i.fg} on ${i.bg} = ${i.ratio}:1 (< ${AA})`).join("\n")
      : "✓ all text/surface pairs ≥ 4.5:1";
  } catch { $("contrast").textContent = "tokens: invalid JSON"; }
}

function download(): void {
  if (!current) return;
  const blob = new Blob([JSON.stringify(current, null, 2) + "\n"], { type: "application/json" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `${current.theme.slug}.site.json`;
  a.click();
  URL.revokeObjectURL(a.href);
}

$("archetype").innerHTML = Object.keys(data.examples).map((k) => `<option>${k}</option>`).join("");
$("palette").innerHTML = Object.values(data.palettes).map((p: J) => `<option value="${p.id}">${p.label}</option>`).join("");
$("fieldTypes").textContent = data.fieldTypes.join(", ");
$("tokens").textContent = JSON.stringify({ radius: "0.5rem", light: data.palettes[Object.keys(data.palettes)[0]].light }, null, 2);
$("archetype").addEventListener("change", refreshArchetype);
$("name").addEventListener("input", () => { $<HTMLInputElement>("slug").placeholder = slugify(val("name")); });
$("brandMode").addEventListener("change", () => { $("tokensBox").hidden = val("brandMode") !== "tokens"; });
$("tokens").addEventListener("input", contrastReport);
$("build").addEventListener("click", build);
$("download").addEventListener("click", download);
$("exampleType").addEventListener("click", () => {
  $<HTMLTextAreaElement>("postTypes").value = JSON.stringify([{ name: "event", labels: { singular: "Event", plural: "Events" },
    archiveSlug: "events", slug: "events/event", supports: ["title", "editor", "excerpt", "thumbnail"],
    fields: { starts_on: { type: "date", label: "Starts on" }, venue: { type: "string", label: "Venue" } },
    metabox: { title: "Event details", position: "side" } }], null, 2);
});
refreshArchetype();
contrastReport();
