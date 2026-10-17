// Validates builder layout entries with the @ui8kit/contract v0.4 validator (validateCollectionV04, from the
// npm package @ui8kit/contract, a devDependency of the repo root) against the manifest of the layout kind
// (WPFastyBuilder\Layout::manifest()).
//   bun install
//   php tools/builder/layout-manifest.php > manifest.json
//   wp post meta get <page> _wpfb_layout > layout.json
//   bun tools/builder/validate-layouts.ts --manifest manifest.json layout.json ...
import { readFileSync } from "node:fs";
import { validateCollectionV04 } from "@ui8kit/contract/validate";

const argv = process.argv.slice(2);
const opt = (k: string, d: string) => { const i = argv.indexOf(k); if (i < 0) return d; const v = argv[i + 1]; argv.splice(i, 2); return v; };
const manifestFile = opt("--manifest", "");
const manifest = JSON.parse(manifestFile ? readFileSync(manifestFile, "utf8") : readFileSync(0, "utf8"));
const entries = argv.map((f) => ({ file: f, data: JSON.parse(readFileSync(f, "utf8")) }));
const issues = validateCollectionV04({ entries }, { manifest }) as { code: string; path: string; message: string; severity?: string }[];
for (const i of issues) console.log(`${i.severity ?? "error"} ${i.code} ${i.path}: ${i.message}`);
const errors = issues.filter((i) => (i.severity ?? "error") === "error");
console.log(`${entries.length} layout entries, ${issues.length} issues, ${errors.length} errors`);
process.exit(errors.length ? 1 : 0);
