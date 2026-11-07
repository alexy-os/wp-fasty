<script lang="ts">
  import Text from "../../../plugins/wpfasty-builder/editor/src/ui/text/Text.svelte";
  import CodeBox from "./CodeBox.svelte";
  import { messageParts, inlineParts } from "./presentation";
  let { value }: { value: string } = $props();
  const parts = $derived(messageParts(value));
</script>
{#each parts as part, i (i)}
  {#if part.kind === "code"}<CodeBox value={part.text} json={part.json} />
  {:else}<Text tag={part.kind === "heading" ? "h4" : "p"} class="wpfb-chat-prose">{#each inlineParts(part.text) as token, j (j)}{#if token.kind === "strong"}<Text tag="strong">{token.text}</Text>{:else if token.kind === "code"}<Text tag="code">{token.text}</Text>{:else}{token.text}{/if}{/each}</Text>{/if}
{/each}
