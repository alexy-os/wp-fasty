/**
 * Builds the two builder registries (one per runtime, docs/agent/builder-registry.md) into a static tree:
 *   <out>/latte/wpfasty/{v<version>,latest}/{catalog.json,blocks.zip}
 *   <out>/twig/wpfasty/{v<version>,latest}/{catalog.json,blocks.zip}
 * Usage: bun tools/builder/registry-build.ts <out> [--version x.y.z] [--src plugins/wpfasty-builder/sources/ui8kit]
 * A version directory is immutable: if <out>/<runtime>/wpfasty/v<version>/ exists with different bytes the build fails
 * (bump the version instead). `latest` is always rewritten as a copy of the version just built. Then every directory
 * written is checked with registry-verify.ts.
 */
import { spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdtempSync, readFileSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";

const out = process.argv[2];
if (!out || out.startsWith("--")) throw new Error("usage: registry-build.ts <out> [--version x.y.z] [--src dir]");
const opt = (n: string) => { const i = process.argv.indexOf(n); return i > 0 ? [n, process.argv[i + 1]!] : []; };
const here = import.meta.dir;
const run = (args: string[]) => {
  const r = spawnSync("bun", args, { stdio: ["ignore", "pipe", "inherit"], encoding: "utf8" });
  if (r.status !== 0) throw new Error(`bun ${args.join(" ")} failed`);
  return r.stdout.trim();
};
for (const runtime of ["latte", "twig"] as const) {
  const tmp = mkdtempSync(join(tmpdir(), `wpfb-reg-${runtime}-`));
  console.log(run([join(here, "pack-source.ts"), runtime, tmp, ...opt("--version"), ...opt("--src")]));
  const version = JSON.parse(readFileSync(join(tmp, "catalog.json"), "utf8")).version;
  const base = resolve(out, runtime, "wpfasty");
  const pinned = join(base, `v${version}`);
  if (existsSync(pinned)) {
    for (const f of ["catalog.json", "blocks.zip"]) {
      if (!existsSync(join(pinned, f)) || !readFileSync(join(pinned, f)).equals(readFileSync(join(tmp, f)))) {
        throw new Error(`${pinned} exists with different content: versions are immutable, bump --version`);
      }
    }
  } else {
    cpSync(tmp, pinned, { recursive: true });
  }
  rmSync(join(base, "latest"), { recursive: true, force: true });
  cpSync(tmp, join(base, "latest"), { recursive: true });
  rmSync(tmp, { recursive: true, force: true });
  for (const d of [pinned, join(base, "latest")]) console.log(run([join(here, "registry-verify.ts"), runtime, d]));
}
