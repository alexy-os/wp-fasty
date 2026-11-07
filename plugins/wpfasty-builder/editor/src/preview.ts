import { chatCopy } from "./chat/content";

/** Keep the real, nonce-protected server preview; change only its cache key. */
export function previewSource(url: string, version: number, reload: number): string {
  if (!url) return "";
  const next = new URL(url);
  next.searchParams.set("wpfb_r", `${version}-${reload}`);
  return next.href;
}

export function requireDraftVersion(actual: unknown, expected: number): void {
  if (!Number.isSafeInteger(expected) || expected < 0 || !Number.isSafeInteger(actual) || Number(actual) < expected) {
    throw new Error(chatCopy.stalePreview);
  }
}

/** Resolves only for the requested iframe document, never an earlier load event. No scripts required. */
export class PreviewReload {
  private pending: { url: string; resolve: () => void; reject: (e: Error) => void; timer: ReturnType<typeof setTimeout> } | null = null;
  constructor(private timeout = 15000) {}
  start(url: string): Promise<void> {
    this.fail();
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => this.fail(), this.timeout);
      this.pending = { url, resolve, reject, timer };
    });
  }
  loaded(url: string, builderPage: boolean): void {
    if (!this.pending || this.pending.url !== url) return;
    if (!builderPage) { this.fail(); return; }
    const pending = this.pending;
    this.pending = null;
    clearTimeout(pending.timer);
    pending.resolve();
  }
  fail(): void {
    if (!this.pending) return;
    const pending = this.pending;
    this.pending = null;
    clearTimeout(pending.timer);
    pending.reject(new Error(chatCopy.previewFailed));
  }
}

/** No retry and no synthetic HTML: apply once, reload persisted layout, then await its real iframe. */
export async function applyAndRefresh(id: string, deps: {
  flush: () => Promise<void>;
  apply: (id: string) => Promise<{ version: number }>;
  load: (minimumVersion: number) => Promise<void>;
  refresh: () => Promise<void>;
}): Promise<void> {
  await deps.flush();
  const applied = await deps.apply(id);
  requireDraftVersion(applied.version, applied.version);
  await deps.load(applied.version);
  await deps.refresh();
}
