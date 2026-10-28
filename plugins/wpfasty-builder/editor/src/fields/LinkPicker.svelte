<script lang="ts">
  // Link token picker of a link field: search + three columns (site, pages, sections) as one listbox driven from the
  // search box (combobox pattern: focus stays in the search, arrows move the active option, Enter picks, Esc closes).
  import Icon from "../Icon.svelte";
  import { FileText, FolderTree, House, Search } from "../icons";
  import { filterIndex, relative, type LinkTarget } from "../links";
  import type { LinkStore } from "../links.svelte";

  let { id, anchor, links, current, onpick, onclose }: {
    id: string;
    /** the field box the picker opens under (position, outside clicks) */
    anchor: HTMLElement;
    links: LinkStore;
    /** the token in the field, marked in the list */
    current: string;
    onpick: (token: string) => void;
    /** refocus: true when the field should get the focus back (Esc, a pick) */
    onclose: (refocus: boolean) => void;
  } = $props();

  let query = $state("");
  let active = $state(0);
  let root: HTMLDivElement | undefined = $state();
  let pos = $state({ top: 0, left: 0, up: false });

  const cols = $derived(filterIndex(links.index, query));
  const columns = $derived([
    { key: "site", title: "Site", icon: House, items: cols.site },
    { key: "pages", title: "Pages", icon: FileText, items: cols.pages },
    { key: "sections", title: "Sections", icon: FolderTree, items: cols.sections },
  ]);
  const flat = $derived(columns.flatMap((c) => c.items));
  const home = $derived(links.index?.home ?? "");
  const optId = (i: number) => `${id}-o${i}`;
  /** index of the first option of each column in `flat` */
  const starts = $derived(columns.reduce<number[]>((acc, _c, i) => [...acc, i === 0 ? 0 : acc[i - 1]! + columns[i - 1]!.items.length], []));

  const WIDTH = 480;
  const HEIGHT = 360;
  function place() {
    const r = anchor.getBoundingClientRect();
    const left = Math.max(8, Math.min(r.right - WIDTH, window.innerWidth - WIDTH - 8));
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
  const hint = (t: LinkTarget) => (t.kind === "archive" ? "archive" : t.kind === "category" ? "category" : (t.slug ?? relative(t.url, home)));
</script>

<svelte:document onpointerdown={outside} onscrollcapture={(e) => { if (!root?.contains(e.target as Node)) place(); }} />
<svelte:window onresize={place} />

<div class={["wpfb-linkpicker", { "is-up": pos.up }]} bind:this={root} style:top="{pos.top}px" style:left="{pos.left}px" style:--wpfb-picker-w="{WIDTH}px">
  <div class="wpfb-linkpicker-search">
    <Icon icon={Search} size={16} />
    <input type="search" placeholder="Search pages and sections" aria-label="Search links" role="combobox" aria-expanded="true"
      aria-controls="{id}-list" aria-autocomplete="list" aria-activedescendant={flat.length ? optId(active) : undefined}
      value={query} oninput={(e) => { query = e.currentTarget.value; active = 0; }} onkeydown={key} {@attach focus} />
  </div>
  {#if links.error && !links.index}
    <p class="wpfb-linkpicker-note is-error">Could not load links: {links.error}
      <button type="button" class="button" onclick={() => links.ensure()}>Retry</button></p>
  {:else if !links.index}
    <p class="wpfb-linkpicker-note">Loading…</p>
  {:else}
    <div class="wpfb-linkpicker-cols" id="{id}-list" role="listbox" aria-label="Link tokens">
      {#each columns as col, c (col.key)}
        <div class="wpfb-linkpicker-col" role="group" aria-labelledby="{id}-h{c}">
          <div class="wpfb-linkpicker-head" id="{id}-h{c}" role="presentation"><Icon icon={col.icon} size={12} /> {col.title}</div>
          {#each col.items as t, k (t.token)}
            {@const i = starts[c]! + k}
            <div class={["wpfb-linkpicker-option", { "is-active": i === active, "is-current": t.token === current }]} id={optId(i)}
              role="option" aria-selected={i === active} tabindex="-1" title={t.token}
              onpointermove={() => (active = i)} onpointerdown={(e) => e.preventDefault()} onclick={() => onpick(t.token)}
              onkeydown={(e) => { if (e.key === "Enter") onpick(t.token); }}>
              <span class="wpfb-linkpicker-label">{t.label}</span>
              <span class="wpfb-linkpicker-meta">{hint(t)}</span>
            </div>
          {:else}
            <p class="wpfb-linkpicker-empty">{query ? "No matches" : "Nothing here"}</p>
          {/each}
        </div>
      {/each}
    </div>
  {/if}
</div>
