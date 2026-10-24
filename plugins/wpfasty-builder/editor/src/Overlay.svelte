<script lang="ts">
  // Selection overlay drawn in the editor document over the preview iframe (nothing is injected into the page):
  // a hover outline, the selected outline with its label and block toolbar, and the drop line while dragging.
  import Icon from "./Icon.svelte";
  import { ChevronDown, ChevronUp, Copy, GripVertical, Trash2 } from "./icons";
  import type { Box } from "./types";
  interface Props {
    hover: Box | null;
    selected: Box | null;
    label: string;
    drop: number | null;
    first: boolean;
    last: boolean;
    onup: () => void;
    ondown: () => void;
    onduplicate: () => void;
    onremove: () => void;
    ondragstart: (e: PointerEvent) => void;
  }
  let { hover, selected, label, drop, first, last, onup, ondown, onduplicate, onremove, ondragstart }: Props = $props();
  const box = (b: Box) => `top:${b.top}px;left:${b.left}px;width:${b.width}px;height:${b.height}px`;
  // the toolbar sits above the block, or inside its top edge when the block starts at the top of the preview
  const above = $derived(selected ? selected.top >= 40 : true);
</script>

<div class="wpfb-overlay">
  {#if hover}<div class="wpfb-box is-hover" style={box(hover)}></div>{/if}
  {#if selected}
    <div class="wpfb-box is-selected" style={box(selected)}></div>
    <div class="wpfb-tag" class:is-inside={!above}
      style="top:{above ? selected.top - 32 : selected.top}px;left:{selected.left}px;max-width:{Math.max(160, selected.width)}px">{label}</div>
    <div class="wpfb-toolbar" class:is-inside={!above} role="toolbar" aria-label="Block actions"
      style="top:{above ? selected.top - 32 : selected.top}px;left:{selected.left + selected.width}px">
      <button type="button" class="wpfb-tool wpfb-grip" title="Drag to move" aria-label="Drag to move" onpointerdown={ondragstart}><Icon icon={GripVertical} /></button>
      <button type="button" class="wpfb-tool" title="Move up" aria-label="Move up" disabled={first} onclick={onup}><Icon icon={ChevronUp} /></button>
      <button type="button" class="wpfb-tool" title="Move down" aria-label="Move down" disabled={last} onclick={ondown}><Icon icon={ChevronDown} /></button>
      <button type="button" class="wpfb-tool" title="Duplicate" aria-label="Duplicate" onclick={onduplicate}><Icon icon={Copy} /></button>
      <button type="button" class="wpfb-tool" title="Remove" aria-label="Remove" onclick={onremove}><Icon icon={Trash2} /></button>
    </div>
  {/if}
  {#if drop !== null}<div class="wpfb-dropline" style="top:{drop - 1}px"></div>{/if}
</div>
