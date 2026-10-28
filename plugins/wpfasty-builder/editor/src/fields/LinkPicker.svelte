<script lang="ts">
  // Token picker of a field: search + columns as one listbox driven from the search box (combobox pattern: focus stays
  // in the search, arrows move the active option, Enter picks, Esc closes). Link fields: Site, Pages, Sections, Contact,
  // then Posts, Products and Terms on a second row (four columns per row, each column scrolls); text fields: Site data
  // (site data and dates), Prices and Stock. The product columns only when the site has a product type.
  import Icon from "../Icon.svelte";
  import { BadgeDollarSign, FileText, FolderTree, House, Info, Newspaper, Package, Phone, Search, ShoppingBag, Tag } from "../icons";
  import { filterIndex, relative } from "../links";
  import type { LinkStore } from "../links.svelte";

  let { id, anchor, links, mode, current, onpick, onclose }: {
    id: string;
    /** the field box the picker opens under (position, outside clicks) */
    anchor: HTMLElement;
    links: LinkStore;
    mode: "link" | "text";
    /** the token in the field, marked in the list */
    current: string;
    onpick: (token: string) => void;
    /** refocus: true when the field should get the focus back (Esc, a pick) */
    onclose: (refocus: boolean) => void;
  } = $props();

  interface Option { token: string; label: string; meta: string; empty: boolean }

  let query = $state("");
  let active = $state(0);
  let root: HTMLDivElement | undefined = $state();
  let pos = $state({ top: 0, left: 0, up: false });

  const home = $derived(links.index?.home ?? "");
  const cols = $derived(filterIndex(links.index, query));
  const url = (u: string): Option["meta"] => relative(u, home);
  const text = (t: { token: string; label: string; value: string }, none: string): Option => ({ token: t.token, label: t.label, meta: t.value === "" ? none : t.value, empty: t.value === "" });
  const columns = $derived.by(() => {
    if (mode === "text") {
      const list = [{ key: "data", title: "Site data", icon: Info, items: cols.data.map((t) => text(t, "not set")) }];
      if (cols.prices) list.push({ key: "prices", title: "Prices", icon: BadgeDollarSign, items: cols.prices.map((t) => text(t, "no price")) });
      if (cols.stock) list.push({ key: "stock", title: "Stock", icon: Package, items: cols.stock.map((t) => text(t, "—")) });
      return list;
    }
    const list = [
      { key: "site", title: "Site", icon: House, items: cols.site.map((t) => ({ token: t.token, label: t.label, meta: url(t.url), empty: false })) },
      { key: "pages", title: "Pages", icon: FileText, items: cols.pages.map((t) => ({ token: t.token, label: t.label, meta: t.slug ?? url(t.url), empty: false })) },
      { key: "sections", title: "Sections", icon: FolderTree, items: cols.sections.map((t) => ({ token: t.token, label: t.label, meta: t.kind ?? url(t.url), empty: false })) },
      { key: "contact", title: "Contact", icon: Phone, items: cols.contact.map((t) => ({ token: t.token, label: t.label, meta: t.url === "#" ? `no ${t.source ?? "value"}` : t.url, empty: t.url === "#" })) },
      { key: "posts", title: "Posts", icon: Newspaper, items: cols.posts.map((t) => ({ token: t.token, label: t.label, meta: t.slug ?? url(t.url), empty: false })) },
    ];
    if (cols.products) list.push({ key: "products", title: "Products", icon: ShoppingBag, items: cols.products.map((t) => ({ token: t.token, label: t.label, meta: [t.price || "no price", t.stock].join(" · "), empty: false })) });
    list.push({ key: "terms", title: "Terms", icon: Tag, items: cols.terms.map((t) => ({ token: t.token, label: t.label, meta: t.kind ?? t.taxonomy ?? url(t.url), empty: false })) });
    return list;
  });
  /** columns per row: four; a second row when there are more (each column scrolls on its own then) */
  const perRow = $derived(Math.min(columns.length, 4));
  const flat = $derived<Option[]>(columns.flatMap((c) => c.items));
  const optId = (i: number) => `${id}-o${i}`;
  /** index of the first option of each column in `flat` */
  const starts = $derived(columns.reduce<number[]>((acc, _c, i) => [...acc, i === 0 ? 0 : acc[i - 1]! + columns[i - 1]!.items.length], []));

  const width = $derived(perRow > 1 ? 608 : 320);
  const HEIGHT = 400;
  function place() {
    const r = anchor.getBoundingClientRect();
    const left = Math.max(8, Math.min(r.right - width, window.innerWidth - width - 8));
    const up = window.innerHeight - r.bottom < HEIGHT + 16 && r.top > window.innerHeight - r.bottom;
    pos = { top: up ? r.top - 4 : r.bottom + 4, left, up };
  }
  place();

  function key(e: KeyboardEvent) {
    const n = flat.length;
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      e.preventDefault();
      if (n) active = (active + (e.key === "ArrowDown" ? 1 : n - 1)) % n;
      document.getElementById(optId(active))?.scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter") {
      e.preventDefault();
      const t = flat[active];
      if (t) onpick(t.token);
    } else if (e.key === "Escape") {
      e.preventDefault();
      e.stopPropagation();
      onclose(true);
    } else if (e.key === "Tab") {
      onclose(false);
    }
  }

  function outside(e: PointerEvent) {
    const t = e.target as Node | null;
    if (t && !root?.contains(t) && !anchor.contains(t)) onclose(false);
  }

  const focus = (el: HTMLInputElement) => {
    el.focus();
  };
</script>

<svelte:document onpointerdown={outside} onscrollcapture={(e) => { if (!root?.contains(e.target as Node)) place(); }} />
<svelte:window onresize={place} />

<div class={["wpfb-linkpicker", { "is-up": pos.up }]} bind:this={root} style:top="{pos.top}px" style:left="{pos.left}px" style:--wpfb-picker-w="{width}px">
  <div class="wpfb-linkpicker-search">
    <Icon icon={Search} size={16} />
    <input type="search" placeholder={mode === "link" ? "Search pages, posts, products, terms…" : "Search site data, prices, stock"} aria-label="Search tokens"
      role="combobox" aria-expanded="true" aria-controls="{id}-list" aria-autocomplete="list" aria-activedescendant={flat.length ? optId(active) : undefined}
      value={query} oninput={(e) => { query = e.currentTarget.value; active = 0; }} onkeydown={key} {@attach focus} />
  </div>
  {#if links.error && !links.index}
    <p class="wpfb-linkpicker-note is-error">Could not load links: {links.error}
      <button type="button" class="button" onclick={() => links.ensure()}>Retry</button></p>
  {:else if !links.index}
    <p class="wpfb-linkpicker-note">Loading…</p>
  {:else}
    <div class="wpfb-linkpicker-cols wpfb-scroll" data-rows={columns.length > perRow ? "" : undefined} style:--wpfb-picker-cols={perRow} id="{id}-list" role="listbox" aria-label={mode === "link" ? "Link tokens" : "Site data tokens"}>
      {#each columns as col, c (col.key)}
        <div class="wpfb-linkpicker-col" role="group" aria-labelledby="{id}-h{c}">
          <div class="wpfb-linkpicker-head" id="{id}-h{c}" role="presentation"><Icon icon={col.icon} size={12} /> {col.title}</div>
          <div class="wpfb-linkpicker-list wpfb-scroll">
          {#each col.items as t, k (t.token)}
            {@const i = starts[c]! + k}
            <div class={["wpfb-linkpicker-option", { "is-active": i === active, "is-current": t.token === current, "is-empty": t.empty }]} id={optId(i)}
              role="option" aria-selected={i === active} tabindex="-1" title={t.token}
              onpointermove={() => (active = i)} onpointerdown={(e) => e.preventDefault()} onclick={() => onpick(t.token)}
              onkeydown={(e) => { if (e.key === "Enter") onpick(t.token); }}>
              <span class="wpfb-linkpicker-label">{t.label}</span>
              <span class="wpfb-linkpicker-meta">{t.meta}</span>
            </div>
          {:else}
            <p class="wpfb-linkpicker-empty">{query ? "No matches" : "Nothing here"}</p>
          {/each}
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
