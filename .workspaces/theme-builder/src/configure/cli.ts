// bun run theme:new-site [--answers answers.json] [--out file.json] [--package]
// Without --answers it asks the questions interactively (Bun prompt()).
import { readFileSync } from "node:fs";
import { relative } from "node:path";
import { packageSite } from "../generate/package";
import { type Answers, ConfigureError, type J } from "./core";
import { loadData, newSite } from "./node";

function ask(q: string, def = ""): string {
  const v = prompt(`${q}${def ? ` [${def}]` : ""}:`);
  return (v ?? "").trim() || def;
}

function interactive(): Answers {
  const data = loadData();
  const archetypes = Object.keys(data.examples);
  const archetype = ask(`Archetype (${archetypes.join(" | ")})`, archetypes[0]);
  const name = ask("Site / theme name", "My Site");
  const palette = ask(`Palette (${Object.keys(data.palettes).join(" | ")})`, Object.keys(data.palettes)[0]);
  const postTypes: J[] = [];
  while (ask("Add a custom post type? (y/N)", "n").toLowerCase().startsWith("y")) {
    const singular = ask("  Singular label", "Event");
    const plural = ask("  Plural label", `${singular}s`);
    const ptName = ask("  Name (lowercase)", singular.toLowerCase().replace(/[^a-z0-9]+/g, "_"));
    const fields: J = {};
    for (const f of ask("  Fields name:type, comma-separated (types: string text url email integer number money boolean date)", "")
      .split(",").map((s) => s.trim()).filter(Boolean)) {
      const [fname, type = "string"] = f.split(":").map((s) => s.trim());
      fields[fname] = { type, label: fname.replace(/_/g, " ").replace(/^\w/, (c) => c.toUpperCase()) };
    }
    postTypes.push({ name: ptName, labels: { singular, plural }, archiveSlug: slugPart(plural), slug: slugPart(singular),
      supports: ["title", "editor", "excerpt", "thumbnail"], fields, ...(Object.keys(fields).length ? { metabox: { title: `${singular} details`, position: "side" } } : {}) });
  }
  return { archetype, name, palette, ...(postTypes.length ? { postTypes } : {}) };
}
const slugPart = (s: string) => s.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");

export async function newSiteCli(args: string[]): Promise<void> {
  const opt = (k: string) => { const i = args.indexOf(k); return i >= 0 ? args[i + 1] : undefined; };
  const answersFile = opt("--answers");
  const answers: Answers = answersFile ? JSON.parse(readFileSync(answersFile, "utf8")) : interactive();
  try {
    const r = newSite(answers, { out: opt("--out") });
    for (const w of r.warnings) console.log(`warning: ${w}`);
    console.log(`manifest ${relative(process.cwd(), r.file)} (valid)`);
    if (args.includes("--package")) {
      const p = await packageSite(r.file);
      console.log(`packaged ${relative(process.cwd(), p.dir)}: ${p.files.join(", ")}`);
    }
  } catch (e) {
    if (e instanceof ConfigureError) { console.error(e.message); process.exitCode = 1; return; }
    throw e;
  }
}
