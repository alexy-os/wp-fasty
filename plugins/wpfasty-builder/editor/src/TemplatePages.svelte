<script lang="ts">
  // "Template pages" of a template collection: a button per page (click: put its blocks on the page) and, on hover or
  // keyboard focus of a button, a side panel with the page's tall preview shot — beside the left column, at the top of
  // the editor area under the bar, scrolling inside. Opens after SHOW_DELAY, follows the pointer from button to
  // button at once, stays open while the pointer is over it (to scroll), closes on leave, blur, Esc or a click.
  import { Peek } from "./peek";
  import { previewSrc } from "./theme";
  import type { TemplatePage } from "./types";
  import { human } from "./util";

  let { pages, collection, dark = false, onuse }: {
    pages: TemplatePage[];
    /** label of the collection, for the panel heading */
    collection: string;
    /** the editor's dark mode: the dark shot */
    dark?: boolean;
    onuse: (page: TemplatePage) => void;
  } = $props();

  const PANEL_ID = "wpfb-page-peek";
  let open = $state<TemplatePage | null>(null);
  /** the panel's place: the right edge of the left column and the top of the editor area (viewport px) */
  let at = $state({ left: 0, top: 0, height: 0 });
  /** the dark shot failed to load: show the light one */
  let darkFailed = $state(false);
  let anchor: HTMLElement | undefined;
  const peek = new Peek<TemplatePage>((p) => { if (p) place(); darkFailed = false; open = p; });

  const preview = $derived(open?.preview);
  const src = $derived(preview ? previewSrc(preview, dark && !darkFailed) : "");

  function place() {
    const column = anchor?.closest(".wpfb-palette");
    const main = anchor?.closest(".wpfb-main");
    if (!column || !main) return;
    const c = column.getBoundingClientRect(), m = main.getBoundingClientRect();
    at = { left: Math.round(c.right), top: Math.round(m.top), height: Math.round(m.height) };
  }

  function keydown(e: KeyboardEvent) {
    if (e.key === "Escape" && open) {
      e.preventDefault();
      e.stopPropagation();
      peek.close();
    }
  }

  function use(page: TemplatePage) {
    peek.close();
    onuse(page);
  }

  // pending timers end with the component (collection changed, column closed): no panel opening afterwards
  $effect(() => () => peek.close());
</script>

<svelte:window onresize={() => open && place()} />

<div class="wpfb-chips" {@attach (el) => { anchor = el; }}>
  {#each pages as page (page.id)}
    <button type="button" class={["wpfb-chip", { "is-peek": open?.id === page.id }]} title={human(page.id)}
      aria-describedby={open?.id === page.id && page.preview ? PANEL_ID : undefined}
      onpointerenter={(e) => { if (e.pointerType !== "touch" && page.preview) peek.enter(page); }}
      onpointerleave={() => peek.leave()}
      onfocus={() => page.preview && peek.enter(page)}
      onblur={() => peek.leave()}
      onkeydown={keydown}
      onclick={() => use(page)}>{human(page.id)}</button>
  {/each}
</div>

{#if open && preview}
  <div id={PANEL_ID} class="wpfb-peek" role="tooltip" style:left="{at.left}px" style:top="{at.top}px" style:max-height="{at.height}px"
    onpointerenter={() => peek.hold()} onpointerleave={() => peek.leave()}>
    <div class="wpfb-peek-head">
      <span class="wpfb-peek-title">{collection} · {human(open.id)}</span>
      <span class="wpfb-peek-meta">{open.blocks.length} {open.blocks.length === 1 ? "block" : "blocks"} · click to use</span>
    </div>
    <div class="wpfb-peek-body">
      <img {src} alt="Preview of the {human(open.id)} template page" width={preview.width * 0.75} height={preview.height * 0.75}
        decoding="async" onerror={() => { if (dark && !darkFailed && preview.dark) darkFailed = true; }} />
    </div>
  </div>
{/if}
