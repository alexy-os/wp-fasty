/**
 * Dev-only warnings switch, bundle-agnostic (no ESM-only meta property, so the utils work in ESM, CJS, IIFE / classic
 * script bundles, e.g. the web components). On when `globalThis.__UI8KIT_DEV__ === true`, or when a bundler
 * defines `process.env.NODE_ENV` as "development".
 */
export function isDevEnv(): boolean {
  const g = globalThis as { __UI8KIT_DEV__?: unknown; process?: { env?: { NODE_ENV?: string } } };
  if (typeof g.__UI8KIT_DEV__ === "boolean") return g.__UI8KIT_DEV__;
  try {
    return g.process?.env?.NODE_ENV === "development";
  } catch {
    return false;
  }
}
