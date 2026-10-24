<script lang="ts">
  import { onMount, untrack } from "svelte";
  import FieldRenderer from "./fields/FieldRenderer.svelte";
  import type { FieldCtx } from "./fields/context";
  import { bindableOf, blockConfig } from "./fields/schema";
  import Icon from "./Icon.svelte";
  import Overlay from "./Overlay.svelte";
  import { Braces, ChevronDown, ChevronUp, Copy, GripVertical, Monitor, RotateCcw, Smartphone, Trash2 } from "./icons";
  import { api } from "./api";
  import type { Binding, Box, Catalog, Config, Item, Json, Obj, RecordField } from "./types";
  import { clone, getPath, human, isObj, kindOf, merge, newId, setPath } from "./util";

  let { config: initial }: { config: Config } = $props();
  // the config comes once from wp_add_inline_script and never changes
  const config = untrack(() => initial);
  const client = api(config);

  let catalog = $state<Catalog | null>(null);
  let items = $state<Item[]>([]);
  let selected = $state<string | null>(null);
  let hovered = $state<string | null>(null);
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
  let device = $state<"desktop" | "mobile">("desktop");
  let advanced = $state(false);
  let advancedError = $state("");
  let renderMs = $state<number | null>(null);
  let scrollY = 0;
  let timer: ReturnType<typeof setTimeout> | undefined;
  /** the preview must reload after the next save (it does not show the saved layout yet) */
  let reloadAfterSave = false;

  const blocks = $derived(new Map((catalog?.blocks ?? []).map((b) => [b.id, b])));
  const filtered = $derived(
    (catalog?.blocks ?? []).filter((b) => !search || `${b.title} ${b.id} ${b.group}`.toLowerCase().includes(search.toLowerCase())),
  );
  const current = $derived(items.find((i) => i.id === selected) ?? null);
  const currentIndex = $derived(items.findIndex((i) => i.id === selected));
  const currentBlock = $derived(current ? blocks.get(current.block) ?? null : null);
  const currentVariant = $derived(currentBlock?.variants.find((v) => v.id === current?.variant) ?? null);
  const currentProps = $derived(current && currentVariant ? merge(currentVariant.props, current.props) : {});
  const records = $derived((catalog?.meta.records ?? {}) as unknown as Record<string, RecordField[]>);
  const blockCfg = $derived(currentBlock ? blockConfig(currentBlock, records) : null);
  const fieldCtx = $derived<FieldCtx | null>(
    current && currentBlock
      ? {
          overridden: (path) => getPath(current.props, path) !== undefined,
          bindable: bindableOf(currentBlock, currentVariant ?? undefined),
          bindings: current.bindings,
          dataSources: catalog?.dataSources ?? [],
          setBinding,
        }
      : null,
  );
  const frameSrc = $derived(previewUrl ? `${previewUrl}&wpfb_r=${reloadKey}` : "");
  const titleOf = (it: Item) => blocks.get(it.block)?.title ?? it.block;

  onMount(() => {
    init();
    window.addEventListener("resize", schedule);
    window.addEventListener("keydown", keydown);
    return () => {
      window.removeEventListener("resize", schedule);
      window.removeEventListener("keydown", keydown);
    };
  });

  async function init() {
    try {
      const layout = await client.layout();
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
  }

  async function loadCatalog() {
    catalog = await client.catalog(source, collection);
    if (catalog.collection === null) collection = "";
  }

  function fail(e: unknown) {
    status = "error";
    message = (e as Error).message;
  }

  // ---- changes ------------------------------------------------------------------------------------------------
  /**
   * The one way the layout changes: `next` replaces the blocks; `ids` are the blocks whose HTML changed. The preview
   * updates in place (those blocks re-rendered by POST /render-block, wrappers re-ordered) and the draft is saved
   * after a short pause; `reload` (bulk replace) reloads the preview after the save instead.
   */
  function commit(next: Item[], ids: string[] = [], reload = false) {
    items = next;
    changed(ids, reload);
  }

  function changed(ids: string[] = [], reload = false) {
    const fresh = reload ? null : syncPreview();
    if (fresh === null) reloadAfterSave = true;
    else ids.filter((id) => !fresh.includes(id)).forEach(renderBlock);
    if (selected && !items.some((i) => i.id === selected)) selected = null;
    status = "saving";
    clearTimeout(timer);
    timer = setTimeout(() => save(false), 600);
    schedule();
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
      schedule();
    } catch {
      reloadAfterSave = true;
    }
  }

  function wrapper(id: string | null): HTMLElement | null {
    if (!id) return null;
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

  // ---- block operations -----------------------------------------------------------------------------------------
  /** a new block; lists bind to WordPress data by default when a source fits the kind */
  function makeItem(block: string, variant: string, taken: Item[]): Item {
    const item: Item = { id: newId(taken, block), block, variant, props: {}, bindings: {} };
    const b = blocks.get(block);
    for (const bind of b?.variants.find((v) => v.id === variant)?.bindings ?? []) {
      const kind = b ? kindOf(b, bind.data) : "";
      const src = catalog?.dataSources.find((d) => d.kinds.includes(kind) && (kind !== "post" || d.id === "posts") && (kind !== "product" || d.id === "products"));
      if (src && ["post", "product", "project", "category", "topic"].includes(kind)) item.bindings[bind.path.join(".")] = { source: src.id, limit: 6 };
    }
    return item;
  }

  function add(block: string, variant: string, at = items.length) {
    const item = makeItem(block, variant, items);
    commit([...items.slice(0, at), item, ...items.slice(at)], [item.id]);
    select(item.id, true);
  }

  /** moves the block at `from` to the insertion point `to` (an index of the list before the move) */
  function move(from: number, to: number) {
    if (from < 0 || from === to || from + 1 === to || to < 0 || to > items.length) return;
    const next = [...items];
    const [it] = next.splice(from, 1);
    next.splice(to > from ? to - 1 : to, 0, it);
    commit(next);
    if (it.id === selected) requestAnimationFrame(() => reveal(it.id));
  }

  function remove(id: string) {
    commit(items.filter((i) => i.id !== id));
  }

  function duplicate(id: string) {
    const i = items.findIndex((x) => x.id === id);
    if (i < 0) return;
    const copy = { ...clone(items[i]), id: newId(items, items[i].block) };
    commit([...items.slice(0, i + 1), copy, ...items.slice(i + 1)], [copy.id]);
    select(copy.id, true);
  }

  function update(fn: (it: Item) => Item) {
    if (!current) return;
    const id = current.id;
    commit(items.map((it) => (it.id === id ? fn(clone(it)) : it)), [id]);
  }

  function setProp(path: string[], value: Json | undefined) {
    update((it) => ({ ...it, props: setPath(it.props, path, value) }));
  }

  function setBinding(path: string, b: Binding | null) {
    update((it) => {
      const bindings = { ...it.bindings };
      if (b) bindings[path] = b;
      else delete bindings[path];
      return { ...it, bindings };
    });
  }

  function commitAdvanced(text: string) {
    try {
      const v = JSON.parse(text);
      if (!isObj(v)) throw new Error("the overrides must be a JSON object");
      update((it) => ({ ...it, props: v }));
      advancedError = "";
    } catch (e) {
      advancedError = (e as Error).message;
    }
  }

  function usePage(page: NonNullable<Catalog["collection"]>["pages"] extends (infer P)[] | undefined ? P : never) {
    if (items.length && !confirm("Replace the blocks of this page with the template page?")) return;
    const next: Item[] = [];
    for (const b of page.blocks) next.push({ ...makeItem(b.block, b.variant, next), props: clone(b.props) });
    selected = null;
    commit(next, [], true);
  }

  async function switchCollection(id: string) {
    collection = id;
    await loadCatalog();
    const url = new URL(location.href);
    if (id) url.searchParams.set("collection", id);
    else url.searchParams.delete("collection");
    history.replaceState(null, "", url);
  }

  function select(id: string | null, scroll = false) {
    selected = id;
    advancedError = "";
    if (scroll && id) requestAnimationFrame(() => reveal(id));
  }

  /** scrolls the preview to a block when it is out of view */
  function reveal(id: string) {
    const el = wrapper(id);
    const win = frame?.contentWindow;
    if (!el || !win) return;
    const r = el.getBoundingClientRect();
    if (r.top < 0 || r.top > win.innerHeight - 48) el.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function keydown(e: KeyboardEvent) {
    if (e.key === "Escape" && selected && !(e.target instanceof HTMLElement && e.target.closest("input, textarea, select"))) select(null);
  }

  // ---- drag and drop (pointer events, no library): palette → list/preview, list handle or overlay grip → list/preview
  type Drag = { kind: "new"; block: string; variant: string; label: string } | { kind: "move"; from: number; label: string };
  let drag = $state<Drag | null>(null);
  let pending: { d: Drag; x: number; y: number } | null = null;
  let ghost = $state({ x: 0, y: 0 });
  let dropAt = $state<number | null>(null);
  let dropIn = $state<"list" | "frame" | null>(null);

  function pointerDown(e: PointerEvent, d: Drag) {
    if (e.button !== 0) return;
    e.preventDefault();
    pending = { d, x: e.clientX, y: e.clientY };
    window.addEventListener("pointermove", pointerMove);
    window.addEventListener("pointerup", pointerUp, { once: true });
  }

  function pointerMove(e: PointerEvent) {
    if (pending && !drag && Math.hypot(e.clientX - pending.x, e.clientY - pending.y) > 4) drag = pending.d;
    if (!drag) return;
    e.preventDefault();
    ghost = { x: e.clientX, y: e.clientY };
    dropAt = dropIndex(e.clientX, e.clientY);
    schedule();
  }

  function pointerUp() {
    window.removeEventListener("pointermove", pointerMove);
    const d = drag;
    const at = dropAt;
    drag = null;
    pending = null;
    dropAt = null;
    dropIn = null;
    schedule();
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
      dropIn = "list";
      return indexBy([...list.querySelectorAll<HTMLElement>("[data-index]")].map((el) => el.getBoundingClientRect()), y);
    }
    if (frame && inside(frame.getBoundingClientRect())) {
      dropIn = "frame";
      const top = frame.getBoundingClientRect().top;
      return indexBy(items.map((it) => {
        const r = wrapper(it.id)?.getBoundingClientRect();
        return r ? { top: r.top + top, bottom: r.bottom + top } : { top: -1, bottom: -1 };
      }), y);
    }
    dropIn = null;
    return null;
  }

  // ---- preview: same-origin iframe of the real page; selection is drawn by the overlay in this document -----------
  let hoverBox = $state<Box | null>(null);
  let selBox = $state<Box | null>(null);
  let dropY = $state<number | null>(null);
  let raf = 0;

  /** re-measures the overlay on the next frame (scroll, resize, render, selection, drag) */
  function schedule() {
    if (raf) return;
    raf = requestAnimationFrame(() => {
      raf = 0;
      measure();
    });
  }

  function boxOf(id: string | null): Box | null {
    const el = wrapper(id);
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return r.height > 0 ? { top: r.top, left: r.left, width: r.width, height: r.height } : null;
  }

  function measure() {
    selBox = boxOf(selected);
    hoverBox = drag || hovered === selected ? null : boxOf(hovered);
    if (drag && dropIn === "frame" && dropAt !== null) {
      const at = items[dropAt] ? boxOf(items[dropAt].id) : boxOf(items[items.length - 1]?.id ?? null);
      dropY = at ? (items[dropAt] ? at.top : at.top + at.height) : 0;
    } else dropY = null;
  }

  $effect(() => {
    void selected;
    void hovered;
    void device;
    schedule();
  });

  function framed() {
    const doc = frame?.contentDocument;
    const win = frame?.contentWindow;
    if (!doc || !win) return;
    doc.addEventListener("click", (e) => {
      const el = (e.target as HTMLElement).closest("[data-wpfb-block]");
      e.preventDefault();
      if (el) select(el.getAttribute("data-wpfb-block"));
    }, true);
    doc.addEventListener("submit", (e) => e.preventDefault(), true);
    doc.addEventListener("mouseover", (e) => {
      hovered = (e.target as HTMLElement).closest?.("[data-wpfb-block]")?.getAttribute("data-wpfb-block") ?? null;
    });
    doc.documentElement.addEventListener("mouseleave", () => (hovered = null));
    doc.addEventListener("keydown", keydown);
    win.addEventListener("scroll", schedule, { passive: true });
    win.addEventListener("resize", schedule);
    new ResizeObserver(schedule).observe(doc.documentElement);
    win.scrollTo(0, scrollY);
    schedule();
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
        <button type="button" class:is-on={device === "desktop"} aria-pressed={device === "desktop"} onclick={() => (device = "desktop")}><Icon icon={Monitor} /> Desktop</button>
        <button type="button" class:is-on={device === "mobile"} aria-pressed={device === "mobile"} onclick={() => (device = "mobile")}><Icon icon={Smartphone} /> Mobile</button>
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
        <select value={collection} onchange={(e) => switchCollection(e.currentTarget.value)}>
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
                      onclick={() => { if (!drag) add(b.id, v.id, selected ? currentIndex + 1 : items.length); }}>
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
          <Overlay hover={hoverBox} selected={selBox} drop={dropY} label={current ? `${titleOf(current)} · ${human(current.variant)}` : ""}
            first={currentIndex <= 0} last={currentIndex === items.length - 1}
            onup={() => move(currentIndex, currentIndex - 1)} ondown={() => move(currentIndex, currentIndex + 2)}
            onduplicate={() => current && duplicate(current.id)} onremove={() => current && remove(current.id)}
            ondragstart={(e) => current && pointerDown(e, { kind: "move", from: currentIndex, label: titleOf(current) })} />
        </div>
      {/if}
      {#if items.length === 0 && status !== "loading"}
        <p class="wpfb-empty">Drag a block from the left, or pick a template page.</p>
      {/if}
    </section>

    <aside class="wpfb-inspector" aria-label="Structure and properties">
      <h2>Structure</h2>
      <ol class="wpfb-structure" bind:this={list} onmouseleave={() => (hovered = null)}>
        {#each items as it, i (it.id)}
          {#if dropIn === "list" && dropAt === i}<li class="wpfb-drop" aria-hidden="true"></li>{/if}
          <li data-index={i} class:is-selected={selected === it.id} class:is-hover={hovered === it.id} onmouseenter={() => (hovered = it.id)}>
            <button type="button" class="wpfb-handle" aria-label="Drag {titleOf(it)} to reorder"
              onpointerdown={(e) => pointerDown(e, { kind: "move", from: i, label: titleOf(it) })}><Icon icon={GripVertical} /></button>
            <button type="button" class="wpfb-item" aria-current={selected === it.id} title="Alt+↑ / Alt+↓ to move"
              onclick={() => select(it.id, true)}
              onkeydown={(e) => { if (e.altKey && e.key === "ArrowUp") { e.preventDefault(); move(i, i - 1); } if (e.altKey && e.key === "ArrowDown") { e.preventDefault(); move(i, i + 2); } }}>
              {titleOf(it)} <span class="wpfb-muted">{human(it.variant)}</span>
            </button>
            <button type="button" class="wpfb-icon" aria-label="Move up" disabled={i === 0} onclick={() => move(i, i - 1)}><Icon icon={ChevronUp} /></button>
            <button type="button" class="wpfb-icon" aria-label="Move down" disabled={i === items.length - 1} onclick={() => move(i, i + 2)}><Icon icon={ChevronDown} /></button>
          </li>
        {/each}
        {#if dropIn === "list" && dropAt === items.length}<li class="wpfb-drop" aria-hidden="true"></li>{/if}
      </ol>

      {#if current && currentBlock}
        <div class="wpfb-props">
          <div class="wpfb-props-head">
            <h2>{currentBlock.title}</h2>
            <div class="wpfb-tools" role="toolbar" aria-label="Block">
              <button type="button" class="wpfb-icon" title="Duplicate" aria-label="Duplicate" onclick={() => duplicate(current.id)}><Icon icon={Copy} /></button>
              <button type="button" class="wpfb-icon" title="Reset all texts to the variant sample" aria-label="Reset texts" onclick={() => update((it) => ({ ...it, props: {} }))}><Icon icon={RotateCcw} /></button>
              <button type="button" class="wpfb-icon" class:is-on={advanced} title="Advanced: edit the overrides as JSON" aria-label="Advanced (JSON)" aria-pressed={advanced} onclick={() => (advanced = !advanced)}><Icon icon={Braces} /></button>
              <button type="button" class="wpfb-icon wpfb-danger" title="Remove" aria-label="Remove" onclick={() => remove(current.id)}><Icon icon={Trash2} /></button>
            </div>
          </div>
          <div class="wpfb-field">
            <label for="wpfb-variant">Variant</label>
            <select id="wpfb-variant" value={current.variant} onchange={(e) => { const v = e.currentTarget.value; update((it) => ({ ...it, variant: v, bindings: {} })); }}>
              {#each currentBlock.variants as v (v.id)}<option value={v.id}>{human(v.id)}</option>{/each}
            </select>
          </div>

          {#if advanced}
            <div class="wpfb-field wpfb-json">
              <label for="wpfb-advanced">Overrides of the variant sample (JSON)</label>
              <textarea id="wpfb-advanced" rows="16" spellcheck="false" value={JSON.stringify(current.props, null, 2)}
                onchange={(e) => commitAdvanced(e.currentTarget.value)}></textarea>
              {#if advancedError}<p class="wpfb-error">{advancedError}</p>{/if}
            </div>
          {:else if blockCfg && fieldCtx}
            {#each Object.entries(blockCfg.fields) as [k, f] (k)}
              <FieldRenderer field={f} path={[k]} value={currentProps[k]} onchange={setProp} ctx={fieldCtx} />
            {/each}
          {/if}
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
