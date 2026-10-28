<script lang="ts">
  // Block cards of the left column: a card (preview image, block and variant) per variant of the site's blocks, a
  // search and a category filter; a virtual grid renders only the rows in view. A card adds its variant on click or
  // Enter, and is dragged onto the canvas or the structure with HTML5 drag and drop (data: MIME, "<block>/<variant>").
  import Icon from "./Icon.svelte";
  import { ImageOff, LayoutGrid, Search } from "./icons";
  import { CAPTION, cardsOf, filterCards, GAP, gridOf, groupsOf, MIME, rowOf, windowOf, type Card } from "./cards";
  import type { BlockSpec, Collection } from "./types";

  let { blocks, collections, onadd, ondrag }: {
    blocks: BlockSpec[];
    collections: Pick<Collection, "id" | "label" | "type">[];
    onadd: (block: string, variant: string) => void;
    /** a card drag starts (the card) or ends (null) */
    ondrag: (card: Card | null) => void;
  } = $props();

  let search = $state("");
  let group = $state("");
  let scrollTop = $state(0);
  let width = $state(0);
  let height = $state(0);
  /** the card that takes the focus in the grid (roving tabindex), an index of `shown` */
  let active = $state(0);
  let scroller: HTMLDivElement | undefined = $state();

  const all = $derived(cardsOf(blocks));
  const groups = $derived(groupsOf(blocks, collections));
  const shown = $derived(filterCards(all, search, group));
  const grid = $derived(gridOf(width, shown.length));
  const view = $derived(windowOf(grid, shown.length, scrollTop, height));
  const slice = $derived(shown.slice(view.from, view.to));
  const current = $derived(Math.min(active, Math.max(0, shown.length - 1)));

  /** container size, kept current by a ResizeObserver */
  const measure = (el: HTMLDivElement) => {
    const ro = new ResizeObserver(([e]) => {
      width = e!.contentRect.width;
      height = e!.contentRect.height;
    });
    ro.observe(el);
    return () => ro.disconnect();
  };

  function narrow() {
    active = 0;
    scrollTop = 0;
    if (scroller) scroller.scrollTop = 0;
  }

  /** arrows move the focus card by card (a row up / down), scrolling the target row into the rendered window */
  function key(e: KeyboardEvent, i: number) {
    const step = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: grid.cols, ArrowUp: -grid.cols, Home: -i, End: shown.length - 1 - i }[e.key];
    if (step === undefined) return;
    e.preventDefault();
    const next = Math.max(0, Math.min(shown.length - 1, i + step));
    active = next;
    const top = rowOf(grid, next) * grid.rowHeight;
    if (scroller && (top < scroller.scrollTop || top + grid.rowHeight > scroller.scrollTop + height)) {
      scroller.scrollTop = top < scroller.scrollTop ? top : top + grid.rowHeight - height;
      scrollTop = scroller.scrollTop;
    }
    requestAnimationFrame(() => scroller?.querySelector<HTMLElement>(`[data-card="${next}"]`)?.focus());
  }

  function dragStart(e: DragEvent, c: Card) {
    if (!e.dataTransfer) return;
    e.dataTransfer.effectAllowed = "copy";
    e.dataTransfer.setData(MIME, c.key);
    e.dataTransfer.setData("text/plain", `${c.title} · ${c.label}`);
    const img = (e.currentTarget as HTMLElement).querySelector("img");
    if (img) e.dataTransfer.setDragImage(img, img.width / 2, img.height / 2);
    ondrag(c);
  }
</script>

<div class="wpfb-cards">
  <div class="wpfb-cards-filter">
    <div class="wpfb-cards-search">
      <Icon icon={Search} size={16} />
      <input type="search" placeholder="Search blocks" aria-label="Search blocks" aria-controls="wpfb-cards-grid"
        value={search} oninput={(e) => { search = e.currentTarget.value; narrow(); }} />
    </div>
    <select class="wpfb-search" aria-label="Block category" value={group} onchange={(e) => { group = e.currentTarget.value; narrow(); }}>
      <option value="">All categories ({all.length})</option>
      {#each groups as g (g.id)}<option value={g.id}>{g.label} ({g.count})</option>{/each}
    </select>
  </div>
  <p class="wpfb-cards-count" aria-live="polite"><Icon icon={LayoutGrid} size={12} /> {shown.length} of {all.length} variants</p>
  <div class="wpfb-cards-scroll" id="wpfb-cards-grid" role="group" aria-label="Block variants: Enter adds, drag onto the page"
    bind:this={scroller} onscroll={(e) => (scrollTop = e.currentTarget.scrollTop)} {@attach measure}>
    {#if !shown.length}
      <p class="wpfb-docs">No blocks match.</p>
    {:else}
      <div class="wpfb-cards-space" style:height="{grid.height}px">
        <ul class="wpfb-cards-grid" style:transform="translateY({view.offset}px)" style:--wpfb-cards-cols={grid.cols} style:gap="{GAP}px">
          {#each slice as c, k (c.key)}
            {@const i = view.from + k}
            <li>
              <button type="button" class="wpfb-card" style:height="{grid.imageHeight + CAPTION}px" data-card={i} tabindex={i === current ? 0 : -1} draggable="true"
                title="{c.title} · {c.label}: click or Enter to add, drag onto the page"
                onclick={() => onadd(c.block, c.variant)} onfocus={() => (active = i)} onkeydown={(e) => key(e, i)}
                ondragstart={(e) => dragStart(e, c)} ondragend={() => ondrag(null)}>
                <span class="wpfb-card-media" style:height="{grid.imageHeight - 2}px">
                  {#if c.preview}
                    <img src={c.preview.src} width={c.preview.width} height={c.preview.height} alt="" loading="lazy" decoding="async" draggable="false" />
                  {:else}
                    <Icon icon={ImageOff} size={16} />
                  {/if}
                </span>
                <span class="wpfb-card-title">{c.title}</span>
                <span class="wpfb-card-variant">{c.label}</span>
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}
  </div>
</div>
