<!-- Generated from ui8kit/codegen Textarea brick (Svelte 5). Kit zone. -->
<!--
  Textarea renders a native textarea; Value is the initial content.
-->
<script lang="ts" module>
	export type TextareaProps = {
		id?: string;
		name?: string;
		value?: string;
		placeholder?: string;
		rows?: string | number;
		required?: boolean;
		disabled?: boolean;
		readOnly?: boolean;
		"aria-invalid"?: string;
		"aria-describedby"?: string;
		class?: string;
		[key: string]: unknown;
	};
</script>

<script lang="ts">
	import { orUndef, safeRest } from "../../utils/expr";

	let {
		id,
		name,
		value = $bindable(""),
		placeholder,
		rows,
		required,
		disabled,
		readOnly,
		"aria-invalid": ariaInvalid,
		"aria-describedby": ariaDescribedBy,
		class: className,
		...rawRest
	}: TextareaProps = $props();

	const rest = $derived(safeRest(rawRest));
	const cls = $derived(className);
</script>

<textarea
	id={orUndef(id)}
	name={orUndef(name)}
	placeholder={orUndef(placeholder)}
	rows={rows === undefined || rows === "" ? undefined : Number(rows)}
	required={required || undefined}
	disabled={disabled || undefined}
	readonly={readOnly || undefined}
	aria-invalid={orUndef(ariaInvalid) as "true" | "false" | "grammar" | "spelling" | undefined}
	aria-describedby={orUndef(ariaDescribedBy)}
	{...(cls ? { class: cls } : {})}
	bind:value
	{...rest}
></textarea>
