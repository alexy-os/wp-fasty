// Validates builder layout entries with the @ui8kit/contract v0.4 validator (validateCollectionV04) against the
// manifest of the layout kind (WPFastyBuilder\Layout::manifest()).
//   php tools/builder/layout-manifest.php > /tmp/manifest.json
//   wp post meta get <page> _wpfb_layout > /tmp/layout.json
//   bun tools/builder/validate-layouts.ts --contract ../contract --manifest /tmp/manifest.json /tmp/layout.json ...
import { readFileSync } from "node:fs";
import { join, resolve } from "node:path";

const argv = process.argv.slice(2);
const opt = (k: string, d: string) => { const i = argv.indexOf(k); if (i < 0) return d; const v = argv[i + 1]; argv.splice(i, 2); return v; };
const contract = resolve(opt("--contract", process.env.CODEX_CONTRACT ?? "/workspace/contract"));
const manifest = JSON.parse(readFileSync(opt("--manifest", "/dev/stdin"), "utf8"));
const { validateCollectionV04 } = await import(join(contract, "src", "validate-v04.ts"));
const entries = argv.map((f) => ({ file: f, data: JSON.parse(readFileSync(f, "utf8")) }));
const issues = validateCollectionV04({ entries }, { manifest }) as { code: string; path: string; message: string; severity?: string }[];
for (const i of issues) console.log(`${i.severity ?? "error"} ${i.code} ${i.path}: ${i.message}`);
const errors = issues.filter((i) => (i.severity ?? "error") === "error");
console.log(`${entries.length} layout entries, ${issues.length} issues, ${errors.length} errors`);
process.exit(errors.length ? 1 : 0);
