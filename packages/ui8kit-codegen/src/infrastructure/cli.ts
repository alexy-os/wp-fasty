#!/usr/bin/env node
/**
 * ui8kit-codegen CLI.
 *
 *   generate [--out <dir>] [--go-module <module>] [--runtimes templ,react,svelte,vue]
 *   check                                  validate all brick definitions
 *   list                                   list bricks and parts
 */

import { bricks } from "../../bricks/index";
import { fileStem } from "../domain/model";
import { validateRegistry } from "../domain/validate";
import {
  ALL_RUNTIMES,
  DEFAULT_GO_MODULE,
  generateFiles,
  phpSkippedParts,
  writeFiles,
  type RuntimeName,
} from "../application/generate";

function parseFlags(args: string[]): Record<string, string> {
  const flags: Record<string, string> = {};
  for (let i = 0; i < args.length; i++) {
    const arg = args[i]!;
    if (arg.startsWith("--")) {
      flags[arg.slice(2)] = args[i + 1] && !args[i + 1]!.startsWith("--") ? args[++i]! : "true";
    }
  }
  return flags;
}

async function main(): Promise<void> {
  const [command, ...rest] = process.argv.slice(2);
  const flags = parseFlags(rest);

  switch (command) {
    case "check": {
      validateRegistry(bricks);
      const parts = bricks.reduce((n, b) => n + b.parts.length, 0);
      console.log(`OK: ${bricks.length} bricks, ${parts} parts validated.`);
      return;
    }
    case "list": {
      for (const brick of bricks) {
        console.log(`${brick.id}  (ui/${brick.dir}/${fileStem(brick)}.*)`);
        for (const part of brick.parts) console.log(`  - ${part.name}`);
      }
      return;
    }
    case "generate": {
      const out = flags["out"] ?? "generated";
      const goModule = flags["go-module"] ?? DEFAULT_GO_MODULE;
      const runtimes = flags["runtimes"]
        ? (flags["runtimes"].split(",") as RuntimeName[])
        : ALL_RUNTIMES;
      for (const r of runtimes) {
        if (!ALL_RUNTIMES.includes(r)) throw new Error(`unknown runtime: ${r}`);
      }
      const files = await generateFiles(bricks, { goModule, runtimes });
      await writeFiles(files, out);
      console.log(
        `Generated ${files.length} files for ${runtimes.join(", ")} into ${out}/ (${bricks.length} bricks).`
      );
      if (runtimes.includes("latte") || runtimes.includes("twig")) {
        const skipped = phpSkippedParts(bricks);
        const total = bricks.reduce((n, b) => n + b.parts.length, 0);
        console.log(`PHP runtimes (latte/twig): ${total - skipped.length}/${total} parts.`);
        for (const s of skipped) console.log(`  skipped ${s.brick}/${s.part}: ${s.reason}`);
      }
      return;
    }
    default:
      console.error("Usage: ui8kit-codegen <generate|check|list> [flags]");
      process.exitCode = 1;
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exitCode = 1;
});
