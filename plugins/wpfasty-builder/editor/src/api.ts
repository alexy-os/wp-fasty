import type { Catalog, Config, Issue, Item, Obj } from "./types";

export function api(cfg: Config) {
  async function call<T>(path: string, init: RequestInit = {}): Promise<T> {
    const res = await fetch(cfg.rest.replace(/\/$/, "") + path, {
      credentials: "same-origin",
      ...init,
      headers: { "X-WP-Nonce": cfg.nonce, "Content-Type": "application/json", ...(init.headers ?? {}) },
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
      const issues = (body?.data?.issues ?? []) as Issue[];
      throw new Error([body?.message ?? `HTTP ${res.status}`, ...issues.map((i) => `${i.path}: ${i.message}`)].join("\n"));
    }
    return body as T;
  }
  return {
    catalog: (source: string, collection: string) =>
      call<Catalog>(`/catalog?source=${encodeURIComponent(source)}&collection=${encodeURIComponent(collection)}`),
    layout: () => call<{ layout: Obj | null; published: boolean; previewUrl: string; permalink: string }>(`/layout/${cfg.page}`),
    save: (source: string, collection: string, blocks: Item[], publish: boolean) =>
      call<{ layout: Obj; issues: Issue[]; published: boolean; previewUrl: string }>(`/layout/${cfg.page}`, {
        method: "POST",
        body: JSON.stringify({ publish, layout: { source, collection, blocks } }),
      }),
  };
}
