<!-- Generated from ui8kit/codegen Button brick (Svelte 5). Kit zone. -->
<!--
  Button renders a button element, or an anchor when Href is set.
  Type/Form apply only in button mode; Disabled on anchors uses aria-disabled.
-->
<script lang="ts" module>
	import type { Snippet } from "svelte";

	export type ButtonProps = {
		type?: "button" | "submit" | "reset";
		form?: string;
		href?: string;
		disabled?: boolean;
		"aria-label"?: string;
		"aria-pressed"?: string;
		"aria-expanded"?: string;
		"aria-controls"?: string;
		class?: string;
		children?: Snippet;
		[key: string]: unknown;
	};
</script>

<script lang="ts">
	import { oneOfOr, orUndef, safeRest } from "../../utils/expr";

	let {
		type,
		form,
		href,
		disabled,
		"aria-label": ariaLabel,
		"aria-pressed": ariaPressed,
		"aria-expanded": ariaExpanded,
		"aria-controls": ariaControls,
		class: className,
		children,
		...rawRest
	}: ButtonProps = $props();

	const rest = $derived(safeRest(rawRest));
	const cls = $derived(className);
	const buttonType = $derived(oneOfOr(type, "button", "submit", "reset"));
</script>

{#if href}
	<a
		href={href}
		{...(cls ? { class: cls } : {})}
		aria-label={orUndef(ariaLabel)}
		aria-expanded={orUndef(ariaExpanded) as "true" | "false" | undefined}
		aria-controls={orUndef(ariaControls)}
		aria-disabled={disabled ? "true" : undefined}
		tabindex={disabled ? -1 : undefined}
		{...rest}
	>{@render children?.()}</a>
{:else}
	<button
		type={buttonType}
		{...(cls ? { class: cls } : {})}
		disabled={disabled || undefined}
		form={orUndef(form)}
		aria-label={orUndef(ariaLabel)}
		aria-pressed={orUndef(ariaPressed) as "true" | "false" | "mixed" | undefined}
		aria-expanded={orUndef(ariaExpanded) as "true" | "false" | undefined}
		aria-controls={orUndef(ariaControls)}
		{...rest}
	>{@render children?.()}</button>
{/if}
