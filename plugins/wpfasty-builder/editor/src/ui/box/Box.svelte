<!-- Generated from ui8kit/codegen Box brick (Svelte 5). Kit zone. -->
<!--
  Box renders the requested structural HTML element (default div).
-->
<script lang="ts" module>
	import type { Snippet } from "svelte";
	import type { BoxTag } from "../../utils/tags";

	export type BoxProps = {
		tag?: BoxTag | string;
		class?: string;
		children?: Snippet;
		[key: string]: unknown;
	};
</script>

<script lang="ts">
	import { safeRest } from "../../utils/expr";
	import { resolveTag, TagGroup } from "../../utils/tags";

	let { tag, class: className, children, ...rawRest }: BoxProps = $props();

	const rest = $derived(safeRest(rawRest));
	const resolvedTagValue = $derived(resolveTag(tag, "div", TagGroup.Box));
	const cls = $derived(className);
</script>

<svelte:element this={resolvedTagValue} {...(cls ? { class: cls } : {})} {...rest}>
	{@render children?.()}
</svelte:element>
