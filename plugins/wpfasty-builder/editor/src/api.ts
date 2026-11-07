import type { Catalog, Config, Issue, Item, NewPage, Obj } from "./types";
import type { LinkIndex } from "./links";

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
    chatHistory: () => call<{ turns: import("./chat/client").ChatTurn[] }>(`/chat/${cfg.page}/history`),
    chatTurn: (content: string, requestId: string) => call<import("./chat/client").ChatResult>(`/chat/${cfg.page}/turn`, {method: "POST", body: JSON.stringify({content, requestId})}),
    chatApply: (requestId: string) => call<{version: number}>(`/chat/${cfg.page}/apply`, {method: "POST", body: JSON.stringify({requestId})}),
    /** one block's HTML, as the page template renders it (POST /render-block) */
    renderBlock: (source: string, item: Item) =>
      call<{ id: string; html: string; issues: Issue[] }>("/render-block", {
        method: "POST",
        body: JSON.stringify({ source, page: cfg.page, id: item.id, block: item.block, variant: item.variant, props: item.props, bindings: item.bindings }),
      }),
    catalog: (source: string, collection: string) =>
      call<Catalog>(`/catalog?source=${encodeURIComponent(source)}&collection=${encodeURIComponent(collection)}`),
    /** link-token targets of the site (GET /links), for the link picker */
    links: () => call<LinkIndex>("/links"),
    layout: () => call<{ layout: Obj | null; published: boolean; previewUrl: string; permalink: string }>(`/layout/${cfg.page}`),
    /** a draft page with the starter's blocks in its Builder draft (POST /pages) */
    createPage: (title: string, slug: string, starter: string) =>
      call<NewPage>("/pages", { method: "POST", body: JSON.stringify({ title, slug, starter }) }),
    save: (source: string, collection: string, blocks: Item[], publish: boolean, expectedVersion?: number) =>
      call<{ layout: Obj; issues: Issue[]; published: boolean; previewUrl: string }>(`/layout/${cfg.page}`, {
        method: "POST",
        body: JSON.stringify({ publish, expectedVersion, layout: { source, collection, blocks } }),
      }),
  };
}
