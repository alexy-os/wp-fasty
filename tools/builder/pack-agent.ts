/** Unsigned DEVELOPMENT candidate only; production install requires the trusted publisher signing pipeline. */
import {mkdtempSync, mkdirSync, readdirSync, copyFileSync, utimesSync, existsSync, rmSync} from "node:fs";
import {tmpdir} from "node:os";
import {resolve, join, extname} from "node:path";
import {spawnSync} from "node:child_process";
const root = resolve(import.meta.dir, "../.."), source = join(root, "modules/builder-agent");
const output = resolve(process.argv[2] ?? join(root, "var/build/builder-agent-unsigned.zip"));
const stage = mkdtempSync(join(tmpdir(), "wpfb-agent-package-"));
const trackedResult = spawnSync("git", ["-C", root, "ls-files", "-z", "--", "modules/builder-agent"], {encoding: "utf8"});
if (trackedResult.status !== 0) throw Error("Package only from a source checkout with tracked module files");
const tracked = new Set(trackedResult.stdout.split("\0").filter(Boolean).map(file => file.replace(/^modules\/builder-agent\//, "")));
const files: string[] = [];
const extensions = new Set([".php", ".js", ".mjs", ".css", ".json", ".md"]);
function collect(dir: string) {for (const entry of readdirSync(join(source, dir), {withFileTypes: true})) {
  const rel = join(dir, entry.name);
  if (entry.name.startsWith(".") || ["node_modules", "test", "data", "workspace", "var"].includes(entry.name)) continue;
  if (entry.isDirectory()) collect(rel);
  else if (entry.isFile() && tracked.has(rel) && (extensions.has(extname(entry.name)) || entry.name === "LICENSE")) files.push(rel);
}}
for (const dir of ["src", "assets", "worker"]) collect(dir);
for (const file of ["module.json", "LICENSE", "README.md"]) {if (!tracked.has(file) || !existsSync(join(source, file))) throw Error(`Missing ${file}`); files.push(file);}
try {
 for (const file of files.sort()) {const target = join(stage, "builder-agent", file); mkdirSync(resolve(target, ".."), {recursive: true}); copyFileSync(join(source, file), target); utimesSync(target, 315532800, 315532800);}
 mkdirSync(resolve(output, ".."), {recursive: true}); rmSync(output, {force: true});
 const zipped = spawnSync("php", [join(root, "tools/builder/pack-agent-dev.php"), join(stage, "builder-agent"), output], {env: process.env});
 if (zipped.status !== 0) throw Error("Agent candidate ZIP failed: " + zipped.stderr?.toString() + zipped.stdout?.toString());
 console.log(`Unsigned DEVELOPMENT candidate: ${files.length} allowlisted files; no npm/runtime data/secrets. ${output}`);
} finally {rmSync(stage, {recursive: true, force: true});}
