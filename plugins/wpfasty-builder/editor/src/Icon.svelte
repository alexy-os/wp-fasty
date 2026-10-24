<script lang="ts">
  // Lucide icon (ISC) from its node data: a static <svg>, no per-icon component runtime.
  import type { IconNode } from "lucide";

  let { icon, size = 16 }: { icon: IconNode; size?: number } = $props();
  const esc = (v: unknown) => String(v).replace(/[&"<>]/g, (c) => `&#${c.charCodeAt(0)};`);
  const inner = $derived(icon.map(([tag, attrs]) => `<${tag} ${Object.entries(attrs).map(([k, v]) => `${k}="${esc(v)}"`).join(" ")}/>`).join(""));
</script>

<svg class="wpfb-svg" xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor"
  stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{@html inner}</svg>
