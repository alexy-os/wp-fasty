<script lang="ts">
  import { onMount, untrack } from "svelte";
  import Field from "./Field.svelte";
  import { api } from "./api";
  import type { BlockSpec, Catalog, Config, Item, Json, Obj } from "./types";
  import { clone, enumOf, getPath, human, isObj, kindOf, merge, newId, setPath } from "./util";

  let { config: initial }: { config: Config } = $props();
  // the config comes once from wp_add_inline_script and never changes
  const config = untrack(() => initial);
  const client = api(config);

  let catalog = $state<Catalog | null>(null);
  let items = $state<Item[]>([]);
  let selected = $state<string | null>(null);
  let source = $state(config.source || "ui8kit");
  let collection = $state(config.collection || "");
  let previewUrl = $state("");
  let published = $state(false);
  let status = $state<"loading" | "idle" | "saving" | "saved" | "error">("loading");
  let message = $state("");
  let search = $state("");
  let openBlock = $state<string | null>(null);
  let frame = $state<HTMLIFrameElement | null>(null);
  let list = $state<HTMLOListElement | null>(null);
  let reloadKey = $state(0);
  let scrollY = 0;
  let timer: ReturnType<typeof setTimeout> | undefined;
  /** the preview must reload after the next save (it does not show the saved layout yet) */
  let reloadAfterSave = false;
  let renderMs = $state<number | null>(null);

  const blocks = $derived(new Map((catalog?.blocks ?? []).map((b) => [b.id, b])));
  const filtered = $derived(
    (catalog?.blocks ?? []).filter((b) => !search || `${b.title} ${b.id} ${b.group}`.toLowerCase().includes(search.toLowerCase())),
  );
  const current = $derived(items.find((i) => i.id === selected) ?? null);
  const currentBlock = $derived(current ? blocks.get(current.block) ?? null : null);
  const currentVariant = $derived(currentBlock?.variants.find((v) => v.id === current?.variant) ?? null);
  const currentProps = $derived(current && currentVariant ? merge(currentVariant.props, current.props) : {});
  const frameSrc = $derived(previewUrl ? `${previewUrl}&wpfb_r=${reloadKey}` : "");

  onMount(async () => {
    try {
      const [layout] = await Promise.all([client.layout()]);
      const meta = (layout.layout?.metadata ?? {}) as Obj;
      const saved = (getPath(meta, ["blocks", "value"]) ?? []) as unknown as Item[];
      if (!config.source && typeof getPath(meta, ["source", "value"]) === "string") source = getPath(meta, ["source", "value"]) as string;
      items = saved.map((b) => ({ ...b, props: isObj(b.props) ? b.props : {}, bindings: isObj(b.bindings) ? b.bindings : {} }));
      previewUrl = layout.previewUrl;
      published = layout.published;
      await loadCatalog();
      status = "idle";
    } catch (e) {
      fail(e);
    }
  });

  async function loadCatalog() {
    catalog = await client.catalog(source, collection);
    if (catalog.collection === null) collection = "";
  }

  function fail(e: unknown) {
    status = "error";
    message = (e as Error).message;
  }

  /**
   * A change: the preview updates in place (one block re-rendered by POST /render-block, or the block wrappers
   * re-ordered), and the draft is saved after a short pause. `ids`: blocks whose HTML changed; none for a pure
   * re-order or removal. When the preview cannot be patched (no builder page in it yet) it reloads after the save.
   */
  function changed(ids: string[] = [], reload = false) {
    const fresh = reload ? null : syncPreview();
    if (fresh === null) reloadAfterSave = true;
    else ids.filter((id) => !fresh.includes(id)).forEach(renderBlock);
    status = "saving";
    clearTimeout(timer);
    timer = setTimeout(() => save(false), 600);
  }

  const renderSeq = new Map<string, number>();

  /** re-renders one block of the preview in place; the latest request per block wins */
  async function renderBlock(id: string) {
    const it = items.find((i) => i.id === id);
    if (!it) return;
    const n = (renderSeq.get(id) ?? 0) + 1;
    renderSeq.set(id, n);
    const t = performance.now();
    try {
      const res = await client.renderBlock(source, clone(it));
      if (renderSeq.get(id) !== n) return;
      const el = wrapper(id);
      if (!el) {
        reloadAfterSave = true;
        return;
      }
      el.innerHTML = res.html;
      renderMs = Math.round(performance.now() - t);
      mark();
    } catch {
      reloadAfterSave = true;
    }
  }

  function wrapper(id: string): HTMLElement | null {
    return frame?.contentDocument?.querySelector<HTMLElement>(`[data-wpfb-block="${CSS.escape(id)}"]`) ?? null;
  }

  /**
   * Puts the block wrappers of the preview in the order of the layout: removed blocks go, new blocks get an empty
   * wrapper (filled by renderBlock). Returns the ids of the new wrappers; null when the preview has no builder page
   * to patch.
   */
  function syncPreview(): string[] | null {
    const doc = frame?.contentDocument;
    const page = doc?.querySelector<HTMLElement>(".wpfb-page");
    if (!doc || !page) return null;
    const fresh: string[] = [];
    const have = new Map([...page.querySelectorAll<HTMLElement>(":scope > [data-wpfb-block]")].map((el) => [el.dataset.wpfbBlock ?? "", el]));
    let prev: Element | null = null;
    for (const it of items) {
      let el = have.get(it.id);
      have.delete(it.id);
      if (!el) {
        el = doc.createElement("div");
        el.className = "wpfb-block";
        el.dataset.wpfbBlock = it.id;
        fresh.push(it.id);
        renderBlock(it.id);
      }
      const next: Element | null = prev ? prev.nextElementSibling : page.firstElementChild;
      if (next !== el) page.insertBefore(el, next);
      prev = el;
    }
    have.forEach((el) => el.remove());
    return fresh;
  }

  async function save(publish: boolean) {
    clearTimeout(timer);
    status = "saving";
    try {
      const res = await client.save(source, collection, clone(items), publish);
      published = res.published;
      previewUrl = res.previewUrl;
      const warn = res.issues.filter((i) => !i.code.startsWith("core."));
      message = warn.length ? warn.map((i) => i.message).join("; ") : publish ? "Published." : "Draft saved.";
      status = "saved";
      if (reloadAfterSave) {
        reloadAfterSave = false;
        scrollY = frame?.contentWindow?.scrollY ?? 0;
        reloadKey++;
      }
    } catch (e) {
      fail(e);
    }
  }

  /** a new block; lists bind to WordPress data by default when a source fits the kind */
  function makeItem(block: string, variant: string, taken: Item[]): Item {
    const item: Item = { id: newId(taken, block), block, variant, props: {}, bindings: {} };
    const b = blocks.get(block);
    // bind lists to WordPress data by default when a source fits the kind
    for (const bind of b?.variants.find((v) => v.id === variant)?.bindings ?? []) {
      const kind = b ? kindOf(b, bind.data) : "";
      const src = catalog?.dataSources.find((d) => d.kinds.includes(kind) && (kind !== "post" || d.id === "posts") && (kind !== "product" || d.id === "products"));
      if (src && ["post", "product", "project", "category", "topic"].includes(kind)) item.bindings[bind.path.join(".")] = { source: src.id, limit: 6 };
    }
    return item;
  }

  function add(block: string, variant: string, at = items.length) {
    const item = makeItem(block, variant, items);
    items = [...items.slice(0, at), item, ...items.slice(at)];
    selected = item.id;
    changed([item.id]);
  }

  function move(from: number, to: number) {
    if (from === to || to < 0 || to > items.length) return;
    const next = [...items];
    const [it] = next.splice(from, 1);
    next.splice(to > from ? to - 1 : to, 0, it);
    items = next;
    changed();
  }

  function remove(id: string) {
    items = items.filter((i) => i.id !== id);
    if (selected === id) selected = null;
    changed();
  }

  function duplicate(id: string) {
    const i = items.findIndex((x) => x.id === id);
    if (i < 0) return;
    const copy = { ...clone(items[i]), id: newId(items, items[i].block) };
    items = [...items.slice(0, i + 1), copy, ...items.slice(i + 1)];
    selected = copy.id;
    changed([copy.id]);
  }

  function update(fn: (it: Item) => Item) {
    if (!current) return;
    const id = current.id;
    items = items.map((it) => (it.id === id ? fn(clone(it)) : it));
    changed([id]);
  }

  function setProp(path: string[], value: Json | undefined) {
    update((it) => ({ ...it, props: setPath(it.props, path, value) }));
  }

  function overridden(path: string[]): boolean {
    return current ? getPath(current.props, path) !== undefined : false;
  }

  function usePage(page: NonNullable<Catalog["collection"]>["pages"] extends (infer P)[] | undefined ? P : never) {
    if (items.length && !confirm("Replace the blocks of this page with the template page?")) return;
    const next: Item[] = [];
    for (const b of page.blocks) next.push({ ...makeItem(b.block, b.variant, next), props: clone(b.props) });
    items = next;
    selected = null;
    changed([], true);
  }

  async function switchCollection(id: string) {
    collection = id;
    await loadCatalog();
    const url = new URL(location.href);
    if (id) url.searchParams.set("collection", id);
    else url.searchParams.delete("collection");
    history.replaceState(null, "", url);
  }

  // ---- drag and drop (pointer events): from the palette or the structure list onto the list or the preview
  type Drag = { kind: "new"; block: string; variant: string; label: string } | { kind: "move"; from: number; label: string };
  let drag = $state<Drag | null>(null);
  let pending: { d: Drag; x: number; y: number } | null = null;
  let ghost = $state({ x: 0, y: 0 });
  let dropAt = $state<number | null>(null);

  function pointerDown(e: PointerEvent, d: Drag) {
    if (e.button !== 0) return;
    pending = { d, x: e.clientX, y: e.clientY };
    window.addEventListener("pointermove", pointerMove);
    window.addEventListener("pointerup", pointerUp, { once: true });
  }

  function pointerMove(e: PointerEvent) {
    if (pending && !drag && Math.hypot(e.clientX - pending.x, e.clientY - pending.y) > 5) drag = pending.d;
    if (!drag) return;
    e.preventDefault();
    ghost = { x: e.clientX, y: e.clientY };
    dropAt = dropIndex(e.clientX, e.clientY);
  }

  function pointerUp() {
    window.removeEventListener("pointermove", pointerMove);
    const d = drag;
    const at = dropAt;
    drag = null;
    pending = null;
    dropAt = null;
    if (!d || at === null) return;
    if (d.kind === "new") add(d.block, d.variant, at);
    else move(d.from, at);
  }

  function indexBy(ys: { top: number; bottom: number }[], y: number): number {
    for (let i = 0; i < ys.length; i++) if (y < (ys[i].top + ys[i].bottom) / 2) return i;
    return ys.length;
  }

  function dropIndex(x: number, y: number): number | null {
    const inside = (r: DOMRect) => x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
    if (list && inside(list.getBoundingClientRect())) {
      return indexBy([...list.querySelectorAll<HTMLElement>("[data-index]")].map((el) => el.getBoundingClientRect()), y);
    }
    const doc = frame?.contentDocument;
    if (frame && doc && inside(frame.getBoundingClientRect())) {
      const top = frame.getBoundingClientRect().top;
      const rects = [...doc.querySelectorAll<HTMLElement>("[data-wpfb-block]")].map((el) => {
        const r = el.getBoundingClientRect();
        return { top: r.top + top, bottom: r.bottom + top };
      });
      return indexBy(rects, y);
    }
    return null;
  }

  // ---- preview: same-origin iframe of the real page; the editor outlines and selects blocks in it
  function framed() {
    const doc = frame?.contentDocument;
    if (!doc) return;
    const style = doc.createElement("style");
    style.textContent =
      "[data-wpfb-block]{position:relative;cursor:pointer}[data-wpfb-block]:hover{outline:2px dashed #2271b1;outline-offset:-2px}" +
      "[data-wpfb-block].wpfb-selected{outline:2px solid #2271b1;outline-offset:-2px}";
    doc.head.appendChild(style);
    doc.addEventListener("click", (e) => {
      const el = (e.target as HTMLElement).closest("[data-wpfb-block]");
      e.preventDefault();
      if (el) selected = el.getAttribute("data-wpfb-block");
    }, true);
    doc.addEventListener("submit", (e) => e.preventDefault(), true);
    frame?.contentWindow?.scrollTo(0, scrollY);
    mark();
  }

  function mark(scroll = false) {
    const doc = frame?.contentDocument;
    if (!doc) return;
    doc.querySelectorAll(".wpfb-selected").forEach((el) => el.classList.remove("wpfb-selected"));
    const el = selected ? doc.querySelector(`[data-wpfb-block="${CSS.escape(selected)}"]`) : null;
    el?.classList.add("wpfb-selected");
    if (scroll) el?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  $effect(() => {
    void selected;
    mark(true);
  });

  let device = $state<"desktop" | "mobile">("desktop");

  function bindable(block: BlockSpec, variantId: string) {
    return (block.variants.find((v) => v.id === variantId)?.bindings ?? []).map((b) => ({ path: b.path.join("."), kind: kindOf(block, b.data) }));
  }
</script>

<div class="wpfb-app" class:is-dragging={drag !== null}>
  <header class="wpfb-bar">
    <div class="wpfb-title">
      <strong>{config.title || "Untitled page"}</strong>
      <span class="wpfb-muted">{published ? "published layout" : "not published"}</span>
    </div>
    <div class="wpfb-status" role="status" aria-live="polite">
      {#if status === "loading"}Loading…{:else if status === "saving"}Saving draft…{:else if status === "error"}<span class="wpfb-error">{message}</span>{:else}{message}{/if}
      {#if renderMs !== null && status !== "error"}<span class="wpfb-muted"> · block updated in {renderMs} ms</span>{/if}
    </div>
    <div class="wpfb-actions">
      <div class="wpfb-seg" role="group" aria-label="Preview width">
        <button type="button" class:is-on={device === "desktop"} onclick={() => (device = "desktop")}>Desktop</button>
        <button type="button" class:is-on={device === "mobile"} onclick={() => (device = "mobile")}>Mobile</button>
      </div>
      <a class="button" href={config.permalink} target="_blank" rel="noopener">View page</a>
      <button type="button" class="button" onclick={() => save(false)}>Save draft</button>
      {#if config.canPublish}<button type="button" class="button button-primary" onclick={() => save(true)}>Publish</button>{/if}
    </div>
  </header>

  <div class="wpfb-main">
    <aside class="wpfb-palette" aria-label="Blocks">
      <label class="wpfb-field">
        <span>Collection</span>
        <select value={collection} onchange={(e) => switchCollection((e.currentTarget as HTMLSelectElement).value)}>
          <option value="">All blocks</option>
          {#each catalog?.collections ?? [] as c (c.id)}
            <option value={c.id}>{c.type === "template" ? "Template: " : ""}{c.label} ({c.blocks})</option>
          {/each}
        </select>
      </label>
      {#if catalog?.collection?.pages?.length}
        <div class="wpfb-pages">
          <p class="wpfb-muted">Template pages</p>
          {#each catalog.collection.pages as page (page.id)}
            <button type="button" class="button button-small" onclick={() => usePage(page)}>{human(page.id)}</button>
          {/each}
        </div>
      {/if}
      <input class="wpfb-search" type="search" placeholder="Search blocks" bind:value={search} aria-label="Search blocks" />
      <ul class="wpfb-blocks">
        {#each filtered as b (b.id)}
          <li>
            <button type="button" class="wpfb-block-head" aria-expanded={openBlock === b.id} onclick={() => (openBlock = openBlock === b.id ? null : b.id)}>
              <span>{b.title}</span><span class="wpfb-muted">{b.variants.length}</span>
            </button>
            {#if openBlock === b.id}
              <p class="wpfb-docs">{b.docs}</p>
              <ul class="wpfb-variants">
                {#each b.variants as v (v.id)}
                  <li>
                    <button type="button" class="wpfb-variant" title="Drag onto the page or click to add"
                      onpointerdown={(e) => pointerDown(e, { kind: "new", block: b.id, variant: v.id, label: `${b.title} · ${human(v.id)}` })}
                      onclick={() => { if (!drag) add(b.id, v.id, selected ? items.findIndex((i) => i.id === selected) + 1 : items.length); }}>
                      {human(v.id)}
                    </button>
                  </li>
                {/each}
              </ul>
            {/if}
          </li>
        {/each}
      </ul>
    </aside>

    <section class="wpfb-canvas" aria-label="Preview">
      {#if frameSrc}
        <div class="wpfb-frame-wrap" class:is-mobile={device === "mobile"}>
          {#key reloadKey}
            <iframe bind:this={frame} src={frameSrc} title="Page preview" onload={framed}></iframe>
          {/key}
        </div>
      {/if}
      {#if items.length === 0 && status !== "loading"}
        <p class="wpfb-empty">Drag a block from the left, or pick a template page.</p>
      {/if}
    </section>

    <aside class="wpfb-inspector" aria-label="Structure and properties">
      <h2>Structure</h2>
      <ol class="wpfb-structure" bind:this={list}>
        {#each items as it, i (it.id)}
          {#if dropAt === i}<li class="wpfb-drop" aria-hidden="true"></li>{/if}
          <li data-index={i} class:is-selected={selected === it.id}>
            <button type="button" class="wpfb-handle" aria-label="Drag to reorder"
              onpointerdown={(e) => pointerDown(e, { kind: "move", from: i, label: blocks.get(it.block)?.title ?? it.block })}>⋮⋮</button>
            <button type="button" class="wpfb-item" onclick={() => (selected = it.id)}>
              {blocks.get(it.block)?.title ?? it.block} <span class="wpfb-muted">{human(it.variant)}</span>
            </button>
            <button type="button" class="wpfb-icon" aria-label="Move up" disabled={i === 0} onclick={() => move(i, i - 1)}>↑</button>
            <button type="button" class="wpfb-icon" aria-label="Move down" disabled={i === items.length - 1} onclick={() => move(i, i + 2)}>↓</button>
          </li>
        {/each}
        {#if dropAt === items.length}<li class="wpfb-drop" aria-hidden="true"></li>{/if}
      </ol>

      {#if current && currentBlock}
        <div class="wpfb-props">
          <h2>{currentBlock.title}</h2>
          <div class="wpfb-row">
            <label class="wpfb-field">
              <span>Variant</span>
              <select value={current.variant} onchange={(e) => update((it) => ({ ...it, variant: (e.currentTarget as HTMLSelectElement).value, bindings: {} }))}>
                {#each currentBlock.variants as v (v.id)}<option value={v.id}>{human(v.id)}</option>{/each}
              </select>
            </label>
          </div>
          <div class="wpfb-row">
            <button type="button" class="button button-small" onclick={() => duplicate(current.id)}>Duplicate</button>
            <button type="button" class="button button-small" onclick={() => update((it) => ({ ...it, props: {} }))}>Reset texts</button>
            <button type="button" class="button button-small wpfb-danger" onclick={() => remove(current.id)}>Remove</button>
          </div>

          {#each bindable(currentBlock, current.variant) as bp (bp.path)}
            {@const bound = current.bindings[bp.path]}
            <fieldset class="wpfb-group wpfb-binding">
              <legend>Data: {human(bp.path)} <span class="wpfb-muted">({bp.kind})</span></legend>
              <label class="wpfb-field">
                <span>Source</span>
                <select value={bound?.source ?? ""} onchange={(e) => {
                  const v = (e.currentTarget as HTMLSelectElement).value;
                  update((it) => { const b = { ...it.bindings }; if (v) b[bp.path] = { source: v, limit: bound?.limit ?? 6 }; else delete b[bp.path]; return { ...it, bindings: b }; });
                }}>
                  <option value="">Sample data</option>
                  {#each (catalog?.dataSources ?? []).filter((d) => d.kinds.includes(bp.kind)) as d (d.id)}<option value={d.id}>{d.label}</option>{/each}
                </select>
              </label>
              {#if bound}
                <label class="wpfb-field"><span>How many</span>
                  <input type="number" min="1" max="24" value={bound.limit} onchange={(e) => update((it) => ({ ...it, bindings: { ...it.bindings, [bp.path]: { ...bound, limit: Number((e.currentTarget as HTMLInputElement).value) } } }))} />
                </label>
                <label class="wpfb-field"><span>Term slug (optional)</span>
                  <input type="text" placeholder="e.g. design" value={bound.term ?? ""} onchange={(e) => update((it) => ({ ...it, bindings: { ...it.bindings, [bp.path]: { ...bound, term: (e.currentTarget as HTMLInputElement).value } } }))} />
                </label>
                {#if bound.source === "products"}
                  <label class="wpfb-check"><input type="checkbox" checked={bound.sale === true} onchange={(e) => update((it) => { const b = { ...bound }; if ((e.currentTarget as HTMLInputElement).checked) b.sale = true; else delete b.sale; return { ...it, bindings: { ...it.bindings, [bp.path]: b } }; })} /> On sale only</label>
                {/if}
              {/if}
            </fieldset>
          {/each}

          {#each currentBlock.props.filter((p) => p.name !== "id" && !current.bindings[p.name]) as p (p.name)}
            <Field name={p.name} path={[p.name]} value={currentProps[p.name]} options={enumOf(p.type)} axes={currentBlock.axes}
              docs={p.docs} {overridden} onchange={setProp} />
          {/each}
        </div>
      {:else}
        <p class="wpfb-muted">Select a block in the preview or in the list.</p>
      {/if}
    </aside>
  </div>

  {#if drag}
    <div class="wpfb-ghost" style="left:{ghost.x + 12}px;top:{ghost.y + 12}px">{drag.label}</div>
  {/if}
</div>

<style>
  /* editor chrome: every class is wpfb-prefixed, shared by App and Field */
  :global {
    #wpfooter { display: none; }
    #wpbody-content { padding-bottom: 0; }
    .wpfb-wrap { margin: 0 16px 0 0; }
    .wpfb-app { --wpfb-line: #dcdcde; --wpfb-accent: #2271b1; display: flex; flex-direction: column; height: calc(100vh - 32px - 16px); min-height: 560px; margin: 8px 0; background: #fff; border: 1px solid var(--wpfb-line); }
    .wpfb-app.is-dragging, .wpfb-app.is-dragging * { cursor: grabbing !important; user-select: none; }
    .wpfb-app.is-dragging iframe { pointer-events: none; }
    .wpfb-bar { display: flex; gap: 16px; align-items: center; padding: 8px 16px; border-bottom: 1px solid var(--wpfb-line); }
    .wpfb-title { display: flex; flex-direction: column; min-width: 0; }
    .wpfb-status { flex: 1; color: #50575e; font-size: 12px; white-space: pre-line; }
    .wpfb-actions { display: flex; gap: 8px; align-items: center; }
    .wpfb-seg { display: inline-flex; border: 1px solid var(--wpfb-line); border-radius: 4px; overflow: hidden; }
    .wpfb-seg button { border: 0; background: #fff; padding: 4px 8px; cursor: pointer; }
    .wpfb-seg button.is-on { background: var(--wpfb-accent); color: #fff; }
    .wpfb-main { flex: 1; display: grid; grid-template-columns: 248px 1fr 320px; min-height: 0; }
    .wpfb-palette, .wpfb-inspector { overflow: auto; padding: 16px; border-right: 1px solid var(--wpfb-line); }
    .wpfb-inspector { border-right: 0; border-left: 1px solid var(--wpfb-line); }
    .wpfb-inspector h2 { font-size: 13px; margin: 0 0 8px; }
    .wpfb-canvas { position: relative; background: #f0f0f1; overflow: hidden; display: flex; justify-content: center; }
    .wpfb-frame-wrap { width: 100%; height: 100%; }
    .wpfb-frame-wrap.is-mobile { width: 390px; border-inline: 1px solid var(--wpfb-line); }
    .wpfb-frame-wrap iframe { width: 100%; height: 100%; border: 0; background: #fff; display: block; }
    .wpfb-empty { position: absolute; top: 40%; color: #50575e; }
    .wpfb-field { display: flex; flex-direction: column; gap: 4px; margin: 0 0 8px; }
    .wpfb-field > span, .wpfb-field > label, .wpfb-field > summary { font-size: 12px; color: #1d2327; }
    .wpfb-field.is-set > label { font-weight: 600; }
    .wpfb-check { display: flex; align-items: center; gap: 6px; font-size: 12px; margin: 0 0 8px; }
    .wpfb-field input[type="text"], .wpfb-field input[type="number"], .wpfb-field select, .wpfb-field textarea, .wpfb-search { width: 100%; max-width: none; }
    .wpfb-check { flex-direction: row; }
    .wpfb-group { border: 1px solid var(--wpfb-line); border-radius: 4px; padding: 8px; margin: 0 0 8px; }
    .wpfb-group legend { font-size: 12px; font-weight: 600; padding: 0 4px; }
    .wpfb-json textarea { font-family: ui-monospace, monospace; font-size: 11px; }
    .wpfb-muted { color: #646970; font-weight: 400; font-size: 12px; }
    .wpfb-docs { color: #646970; font-size: 12px; margin: 0 0 8px; }
    .wpfb-error { color: #b32d2e; }
    .wpfb-danger { color: #b32d2e !important; border-color: #b32d2e !important; }
    .wpfb-search { margin: 8px 0; }
    .wpfb-pages { display: flex; flex-wrap: wrap; gap: 4px; margin: 0 0 8px; }
    .wpfb-pages p { width: 100%; margin: 0; }
    .wpfb-blocks, .wpfb-variants, .wpfb-structure { list-style: none; margin: 0; padding: 0; }
    .wpfb-block-head { width: 100%; display: flex; justify-content: space-between; background: none; border: 0; border-bottom: 1px solid #f0f0f1; padding: 8px 0; cursor: pointer; text-align: left; }
    .wpfb-variants { display: flex; flex-wrap: wrap; gap: 4px; padding: 0 0 8px; }
    .wpfb-variant { border: 1px solid var(--wpfb-line); background: #f6f7f7; border-radius: 4px; padding: 4px 8px; cursor: grab; touch-action: none; font-size: 12px; }
    .wpfb-variant:hover { border-color: var(--wpfb-accent); }
    .wpfb-structure li { display: flex; align-items: center; gap: 4px; border: 1px solid var(--wpfb-line); border-radius: 4px; margin: 0 0 4px; background: #fff; }
    .wpfb-structure li.is-selected { border-color: var(--wpfb-accent); box-shadow: inset 0 0 0 1px var(--wpfb-accent); }
    .wpfb-structure li.wpfb-drop { height: 4px; border: 0; background: var(--wpfb-accent); }
    .wpfb-handle { cursor: grab; border: 0; background: none; padding: 8px 4px; color: #646970; touch-action: none; }
    .wpfb-item { flex: 1; text-align: left; border: 0; background: none; padding: 8px 0; cursor: pointer; }
    .wpfb-icon { border: 0; background: none; cursor: pointer; padding: 4px; }
    .wpfb-icon:disabled { opacity: 0.3; }
    .wpfb-row { display: flex; gap: 4px; flex-wrap: wrap; margin: 0 0 8px; }
    .wpfb-props { margin-top: 16px; border-top: 1px solid var(--wpfb-line); padding-top: 16px; }
    .wpfb-ghost { position: fixed; z-index: 100000; pointer-events: none; background: var(--wpfb-accent); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
  }
</style>
