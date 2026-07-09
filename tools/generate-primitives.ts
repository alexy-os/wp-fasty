/**
 * Generate UI8Kit Latte primitives into `.workspaces/ui8kit-latte`.
 *
 * Resolves the codegen package from (in order):
 * 1. packages/ui8kit-codegen (git submodule)
 * 2. UI8KIT_CODEGEN env path
 * 3. Sibling ../Codegen or ../../UI8Kit/Codegen (local monorepo layout)
 */

import { existsSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { spawnSync } from "node:child_process";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const outDir = join(root, ".workspaces", "ui8kit-latte");

function resolveCodegen(): string {
  const candidates = [
    join(root, "packages", "ui8kit-codegen"),
    process.env.UI8KIT_CODEGEN,
    join(root, "..", "Codegen"),
    join(root, "..", "UI8Kit", "Codegen"),
    "D:/UI8Kit/Codegen",
  ].filter(Boolean) as string[];

  for (const c of candidates) {
    const abs = resolve(c);
    if (existsSync(join(abs, "src", "application", "generate-latte-bundle.ts"))) {
      return abs;
    }
  }

  throw new Error(
    "ui8kit/codegen not found. Add submodule packages/ui8kit-codegen, " +
      "set UI8KIT_CODEGEN, or place Codegen as a sibling checkout."
  );
}

const codegen = resolveCodegen();
console.log(`Using codegen: ${codegen}`);
console.log(`Output: ${outDir}`);

const result = spawnSync(
  "bun",
  ["src/application/generate-latte-bundle.ts", "--out", outDir],
  { cwd: codegen, stdio: "inherit", shell: true }
);

process.exit(result.status ?? 1);
