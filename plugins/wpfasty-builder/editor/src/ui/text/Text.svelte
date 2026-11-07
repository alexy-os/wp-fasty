<!-- Generated from ui8kit/codegen Text brick (Svelte 5). Kit zone. -->
<!--
  Text renders the requested text element (default p).
-->
<script lang="ts" module>
	import type { Snippet } from "svelte";
	import type { TextTag } from "../../utils/tags";

	export type TextProps = {
		tag?: TextTag | string;
		class?: string;
		children?: Snippet;
		[key: string]: unknown;
	};
</script>

<script lang="ts">
	import { safeRest } from "../../utils/expr";
	import { resolveTag, TagGroup } from "../../utils/tags";

	let { tag, class: className, children, ...rawRest }: TextProps = $props();

	const rest = $derived(safeRest(rawRest));
	const resolvedTagValue = $derived(resolveTag(tag, "p", TagGroup.Text));
	const cls = $derived(className);
</script>

<svelte:element this={resolvedTagValue} {...(cls ? { class: cls } : {})} {...rest}>
	{@render children?.()}
</svelte:element>
