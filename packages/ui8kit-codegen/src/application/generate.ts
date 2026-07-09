/**
 * Generation pipeline: validate the brick registry, run every requested
 * emitter, add barrels and the runtime support layer, and return (or write)
 * the full generated tree.
 */

import { mkdir, readdir, readFile, writeFile } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

import { fileStem, type BrickDef } from "../domain/model";
import { phpSupported } from "../domain/php-support";
import { validateRegistry } from "../domain/validate";
import { BANNER, type Emitter, type GeneratedFile } from "../emitters/common";
import { LatteEmitter } from "../emitters/latte";
import { ReactEmitter } from "../emitters/react";
import { SvelteEmitter } from "../emitters/svelte";
import { TemplEmitter } from "../emitters/templ";
import { TwigEmitter } from "../emitters/twig";
import { VueEmitter } from "../emitters/vue";

export type RuntimeName = "templ" | "react" | "svelte" | "vue" | "latte" | "twig";

export interface GenerateOptions {
  goModule?: string;
  runtimes?: RuntimeName[];
}

const PACKAGE_ROOT = join(dirname(fileURLToPath(import.meta.url)), "..", "..");
export const DEFAULT_GO_MODULE = "github.com/ui8kit/ui";
export const ALL_RUNTIMES: RuntimeName[] = ["templ", "react", "svelte", "vue", "latte", "twig"];

/** Parts the PHP template emitters skip, with reasons (for the CLI summary). */
export function phpSkippedParts(bricks: BrickDef[]): Array<{ brick: string; part: string; reason: string }> {
  const out: Array<{ brick: string; part: string; reason: string }> = [];
  for (const brick of bricks) {
    for (const part of brick.parts) {
      const support = phpSupported(part);
      if (!support.ok) out.push({ brick: brick.dir, part: part.name, reason: support.reason });
    }
  }
  return out;
}

export async function generateFiles(
  bricks: BrickDef[],
  options: GenerateOptions = {}
): Promise<GeneratedFile[]> {
  validateRegistry(bricks);
  const runtimes = options.runtimes ?? ALL_RUNTIMES;
  const goModule = options.goModule ?? DEFAULT_GO_MODULE;

  const emitters: Emitter[] = [];
  if (runtimes.includes("templ")) emitters.push(new TemplEmitter({ goModule }));
  if (runtimes.includes("react")) emitters.push(new ReactEmitter());
  if (runtimes.includes("svelte")) emitters.push(new SvelteEmitter());
  if (runtimes.includes("vue")) emitters.push(new VueEmitter());
  if (runtimes.includes("latte")) emitters.push(new LatteEmitter());
  if (runtimes.includes("twig")) emitters.push(new TwigEmitter());

  const files: GeneratedFile[] = [];
  const emitTs = runtimes.some((r) => r === "react" || r === "svelte" || r === "vue");

  for (const brick of bricks) {
    // Colocate the shared recipes next to every runtime file.
    for (const [id, file] of Object.entries(brick.recipeFiles)) {
      const recipe = brick.recipes[id]!;
      files.push({
        path: `ui/${brick.dir}/${file}`,
        contents: JSON.stringify(recipe, null, 2) + "\n",
      });
    }
    if (emitTs) {
      const { emitSharedModule } = await import("../emitters/ts-common");
      files.push(emitSharedModule(brick, "../../utils"));
    }
    for (const emitter of emitters) {
      files.push(...emitter.emit(brick));
    }
  }

  files.push(...(await runtimeSupportFiles(runtimes)));
  files.push(...barrels(bricks, runtimes));

  if (runtimes.includes("templ")) {
    const { emitGoParityHarness, emitGoMod } = await import("../emitters/go-harness");
    files.push(emitGoMod(goModule));
    files.push(emitGoParityHarness(bricks, goModule));
  }

  if (runtimes.includes("latte") || runtimes.includes("twig")) {
    const { emitPhpClasses } = await import("../emitters/php-classes");
    const { emitComposerJson, emitLatteParityHarness, emitTwigParityHarness } = await import(
      "../emitters/php-harness"
    );
    files.push(emitPhpClasses(bricks));
    files.push(emitComposerJson());
    if (runtimes.includes("latte")) files.push(emitLatteParityHarness());
    if (runtimes.includes("twig")) files.push(emitTwigParityHarness());
  }

  // Fail loudly on path collisions — one brick must never overwrite another.
  const seen = new Set<string>();
  for (const f of files) {
    if (seen.has(f.path)) throw new Error(`duplicate generated path: ${f.path}`);
    seen.add(f.path);
  }
  return files;
}

export async function writeFiles(files: GeneratedFile[], outDir: string): Promise<void> {
  for (const file of files) {
    const full = join(outDir, file.path);
    await mkdir(dirname(full), { recursive: true });
    await writeFile(full, file.contents, "utf8");
  }
}

async function runtimeSupportFiles(runtimes: RuntimeName[]): Promise<GeneratedFile[]> {
  const files: GeneratedFile[] = [];
  const emitTs = runtimes.some((r) => r === "react" || r === "svelte" || r === "vue");
  if (emitTs) {
    const tsDir = join(PACKAGE_ROOT, "runtime", "ts");
    for (const name of await readdir(tsDir)) {
      files.push({ path: `utils/${name}`, contents: await readFile(join(tsDir, name), "utf8") });
    }
  }
  if (runtimes.includes("templ")) {
    const goDir = join(PACKAGE_ROOT, "runtime", "go");
    for (const name of await readdir(goDir)) {
      files.push({ path: `utils/${name}`, contents: await readFile(join(goDir, name), "utf8") });
    }
  }
  if (runtimes.includes("react")) {
    const slot = await readFile(join(PACKAGE_ROOT, "runtime", "react", "slot.tsx"), "utf8");
    files.push({ path: "ui/slot/slot.tsx", contents: slot });
  }
  if (runtimes.includes("latte") || runtimes.includes("twig")) {
    const phpDir = join(PACKAGE_ROOT, "runtime", "php");
    for (const name of await readdir(phpDir)) {
      if (name === "TwigExtension.php" && !runtimes.includes("twig")) continue;
      files.push({
        path: `php/UI8Kit/${name}`,
        contents: await readFile(join(phpDir, name), "utf8"),
      });
    }
  }
  return files;
}

function barrels(bricks: BrickDef[], runtimes: RuntimeName[]): GeneratedFile[] {
  const files: GeneratedFile[] = [];

  if (runtimes.some((r) => r === "react" || r === "svelte" || r === "vue")) {
    const lines = [`// ${BANNER}`, `// Shared per-brick contracts (types + classes helpers).`, ``];
    for (const brick of bricks) {
      lines.push(`export * from "./${brick.dir}/${fileStem(brick)}.shared";`);
    }
    files.push({ path: "ui/shared.ts", contents: lines.join("\n") + "\n" });
  }

  if (runtimes.includes("react")) {
    const lines = [`// ${BANNER}`, `// React barrel.`, ``];
    for (const brick of bricks) {
      lines.push(`export * from "./${brick.dir}/${fileStem(brick)}";`);
    }
    lines.push(`export * from "./slot/slot";`);
    lines.push(`export * from "./shared";`);
    files.push({ path: "ui/index.ts", contents: lines.join("\n") + "\n" });
  }

  if (runtimes.includes("svelte")) {
    const lines = [`// ${BANNER}`, `// Svelte 5 barrel.`, ``];
    for (const brick of bricks) {
      for (const part of brick.parts) {
        lines.push(`export { default as ${part.name} } from "./${brick.dir}/${part.name}.svelte";`);
        lines.push(`export type { ${part.name}Props } from "./${brick.dir}/${part.name}.svelte";`);
      }
    }
    lines.push(`export * from "./shared";`);
    files.push({ path: "ui/index.svelte.ts", contents: lines.join("\n") + "\n" });
  }

  if (runtimes.includes("vue")) {
    const lines = [`// ${BANNER}`, `// Vue 3 barrel.`, ``];
    for (const brick of bricks) {
      for (const part of brick.parts) {
        lines.push(`export { default as ${part.name} } from "./${brick.dir}/${part.name}.vue";`);
      }
    }
    lines.push(`export * from "./shared";`);
    files.push({ path: "ui/index.vue.ts", contents: lines.join("\n") + "\n" });
  }

  return files;
}
