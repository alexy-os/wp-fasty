<script lang="ts">
  import Text from "../ui/text/Text.svelte";
  import { jsonTokens } from "./presentation";
  let { value, json = true }: { value: string; json?: boolean } = $props();
  const tokens = $derived(json ? jsonTokens(value) : [{text: value, kind: "plain"}]);
</script>
<Text tag="pre" class="wpfb-chat-code wpfb-scroll"><Text tag="code">{#each tokens as token, i (i)}<Text tag="span" class={`wpfb-code-${token.kind}`}>{token.text}</Text>{/each}</Text></Text>
