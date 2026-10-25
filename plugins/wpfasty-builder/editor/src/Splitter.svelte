<script lang="ts">
  // A column separator (WAI-ARIA window splitter): drag with the mouse, ArrowLeft/ArrowRight by 8px, Home/End to the
  // min/max, double click (or Enter) back to the default width. The value is the width of the column it resizes.
  import { COLUMNS, STEP, type Side } from "./layout";

  interface Props {
    side: Side;
    width: number;
    controls: string;
    label: string;
    onresize: (px: number) => void;
    onreset: () => void;
    ondragging?: (on: boolean) => void;
  }
  let { side, width, controls, label, onresize, onreset, ondragging }: Props = $props();
  const spec = $derived(COLUMNS[side]);
  /** +1: moving the line right widens the column (left column); -1 for the right column */
  const dir = $derived(side === "left" ? 1 : -1);
  let start: { x: number; w: number } | null = null;
  let dragging = $state(false);

  function down(e: PointerEvent) {
    if (e.button !== 0) return;
    e.preventDefault();
    (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
    (e.currentTarget as HTMLElement).focus();
    start = { x: e.clientX, w: width };
    dragging = true;
    ondragging?.(true);
  }

  function move(e: PointerEvent) {
    if (!start) return;
    onresize(start.w + dir * (e.clientX - start.x));
  }

  function up() {
    if (!start) return;
    start = null;
    dragging = false;
    ondragging?.(false);
  }

  function key(e: KeyboardEvent) {
    const by = e.shiftKey ? STEP * 4 : STEP;
    if (e.key === "ArrowLeft") onresize(width - dir * by);
    else if (e.key === "ArrowRight") onresize(width + dir * by);
    else if (e.key === "Home") onresize(spec.min);
    else if (e.key === "End") onresize(spec.max);
    else if (e.key === "Enter") onreset();
    else return;
    e.preventDefault();
  }
</script>

<!-- a focusable separator with a value is an interactive widget (WAI-ARIA window splitter) -->
<!-- svelte-ignore a11y_no_noninteractive_tabindex, a11y_no_noninteractive_element_interactions -->
<div class="wpfb-splitter is-{side}" class:is-dragging={dragging} role="separator" tabindex="0" aria-orientation="vertical"
  aria-label={label} aria-controls={controls} aria-valuenow={width} aria-valuemin={spec.min} aria-valuemax={spec.max}
  aria-valuetext="{width} px" title="{label}: drag or ←/→ to resize, double click to reset"
  onpointerdown={down} onpointermove={move} onpointerup={up} onpointercancel={up} onlostpointercapture={up}
  ondblclick={onreset} onkeydown={key}></div>
