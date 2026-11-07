<script lang="ts">
import {onMount} from "svelte";
import UiBox from "./ui/box/Box.svelte";
import type {FeatureContext, FeatureAdapter} from "./features";
let {id, context}: {id: string; context: FeatureContext} = $props();
let adapter = $state.raw<FeatureAdapter | null>(null);
let error = $state("");
const unavailable = "Optional panel unavailable. Manual Builder remains available.";
onMount(() => {const node = document.getElementById("wpfb-optional-panel"); const factory = window.WPFastyBuilderFeatures?.[id];
try {if (!node || !factory) throw new Error(unavailable); adapter = factory(node, context);} catch {error = unavailable;}
return () => {try {adapter?.destroy();} catch {/* Optional teardown cannot break manual Builder. */} adapter = null;};});
$effect(() => {try {adapter?.update(context);} catch {error = unavailable;}});
</script>
<UiBox tag="div" id="wpfb-optional-panel" class="wpfb-feature" />
{#if error}<UiBox tag="p" role="status">{error}</UiBox>{/if}
