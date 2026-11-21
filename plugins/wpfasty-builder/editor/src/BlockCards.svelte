<script lang="ts">
  // Block cards of the left column (ADR-007): one card per block, drawn as the live, script-free prototype of its
  // selected variant (the page preview URL with the block's static prototype sheet; the variant is the fragment, so
  // switching does not reload the frame). The switcher under the card picks the variant by design axis (one button
  // row per axis) or, when the axes do not tell the variants apart, from the labelled variants. A click or Enter on the
  // card adds the selected variant; the card drags onto the canvas or the structure (data: MIME, "<block>/<variant>").
  import Icon from "./Icon.svelte";
  import { LayoutGrid, Search } from "./icons";
  import { cardsOf, columnsOf, filterCards, GAP, groupsOf, MIME, pick, selectedOf, type Card } from "./cards";
  import { prototypeSource } from "./prototype";
  import type { BlockSpec } from "./types";

  let { blocks, dark = false, previewUrl = "", onadd, ondrag }: {
    blocks: BlockSpec[];
    /** the editor's dark mode: the prototypes render in the dark scheme */
    dark?: boolean;
    /** the actual authenticated server preview of this page */
    previewUrl?: string;
    onadd: (block: string, variant: string) => void;
    /** a card drag starts (the card) or ends (null) */
    ondrag: (card: Card | null) => void;
  } = $props();

  let search = $state("");
  let group = $state("");
  let width = $state(0);
  /** the variant chosen on each card (block id → variant id); unset = the block's first variant */
  let chosen = $state<Record<string, string>>({});

  const all = $derived(cardsOf(blocks));
  const groups = $derived(groupsOf(blocks));
  const shown = $derived(filterCards(all, search, group));
  const grid = $derived(columnsOf(width));
  const scale = $derived((grid.cardWidth - 2) / 1024);

  /** container width, kept current by a ResizeObserver */
  const measure = (el: HTMLElement) => {
    const ro = new ResizeObserver(([e]) => (width = e!.contentRect.width));
    ro.observe(el);
    return () => ro.disconnect();
  };

  function dragStart(e: DragEvent, c: Card, variant: string, label: string) {
    if (!e.dataTransfer) return;
    e.dataTransfer.effectAllowed = "copy";
    e.dataTransfer.setData(MIME, `${c.block}/${variant}`);
    e.dataTransfer.setData("text/plain", `${c.title} · ${label}`);
    ondrag(c);
  }
</script>

<div class="wpfb-cards">
  <div class="wpfb-cards-filter">
    <div class="wpfb-cards-search">
      <Icon icon={Search} size={16} />
      <input type="search" placeholder="Search blocks" aria-label="Search blocks" aria-controls="wpfb-cards-grid"
        value={search} oninput={(e) => (search = e.currentTarget.value)} />
    </div>
    <select class="wpfb-search" aria-label="Block category" value={group} onchange={(e) => (group = e.currentTarget.value)}>
      <option value="">All categories ({all.length})</option>
      {#each groups as g (g.id)}<option value={g.id}>{g.label} ({g.count})</option>{/each}
    </select>
  </div>
  <p class="wpfb-cards-count" aria-live="polite"><Icon icon={LayoutGrid} size={12} /> {shown.length} of {all.length} blocks</p>
  <div class="wpfb-cards-scroll wpfb-scroll" id="wpfb-cards-grid" {@attach measure}>
    {#if !shown.length}
      <p class="wpfb-docs">No blocks match.</p>
    {:else}
      <ul class="wpfb-cards-grid" aria-label="Blocks: click or Enter adds the selected variant, drag onto the page"
        style:--wpfb-cards-cols={grid.cols} style:gap="{GAP}px">
        {#each shown as c (c.key)}
          {@const v = selectedOf(c, chosen[c.block])}
          <li class="wpfb-card" draggable="true" data-block={c.block} data-variant={v.id}
            ondragstart={(e) => dragStart(e, c, v.id, v.label)} ondragend={() => ondrag(null)}>
            <button type="button" class="wpfb-card-main" title="{c.title} · {v.label}: click or Enter to add, drag onto the page"
              onclick={() => onadd(c.block, v.id)}>
              <span class="wpfb-card-media" style:height="{Math.round(((grid.cardWidth - 2) * 9) / 16)}px">
                {#if c.prototype && previewUrl}
                  <iframe class="wpfb-card-prototype" src={prototypeSource(previewUrl, c.block, v.id, dark)}
                    title={`${c.title} · ${v.label}`} sandbox="allow-same-origin" loading="lazy" aria-hidden="true" tabindex="-1"
                    width="1024" height="576" style:transform="scale({scale})"></iframe>
                {/if}
              </span>
              <span class="wpfb-card-title">{c.title}</span>
              <span class="wpfb-card-variant">{v.label}</span>
            </button>
            {#if c.variants.length > 1}
              <div class="wpfb-card-switch" role="group" aria-label="{c.title}: variant">
                {#if c.axes.length}
                  {#each c.axes as a (a.name)}
                    <div class="wpfb-card-axis" role="group" aria-label={a.label}>
                      <span class="wpfb-card-axis-name" aria-hidden="true">{a.label}</span>
                      {#each a.values as x (x.value)}
                        <button type="button" class="wpfb-card-chip" aria-pressed={v.set[a.name] === x.value}
                          onclick={() => (chosen[c.block] = pick(c, v.id, a.name, x.value))}>{x.label}</button>
                      {/each}
                    </div>
                  {/each}
                {:else}
                  <select class="wpfb-card-variants" aria-label="{c.title}: variant" value={v.id}
                    onchange={(e) => (chosen[c.block] = e.currentTarget.value)}>
                    {#each c.variants as o (o.id)}<option value={o.id}>{o.label}</option>{/each}
                  </select>
                {/if}
              </div>
            {/if}
          </li>
        {/each}
      </ul>
    {/if}
  </div>
</div>

<style>
  .wpfb-card-media { position: relative; overflow: hidden; }
  .wpfb-card-prototype { position: absolute; top: 0; left: 0; width: 1024px; height: 576px; border: 0; pointer-events: none; transform-origin: top left; }
</style>
