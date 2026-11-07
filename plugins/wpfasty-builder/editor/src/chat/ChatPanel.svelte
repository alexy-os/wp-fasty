<script lang="ts">
  import Block from "../compose/Block.svelte";
  import Text from "../ui/text/Text.svelte";
  import Box from "../ui/box/Box.svelte";
  import Button from "../ui/button/Button.svelte";
  import Textarea from "../ui/textarea/Textarea.svelte";
  import type { BlockSpec, Item } from "../types";
  import { builderContext } from "./context";
  import { chatCopy as copy } from "./content";

  let { source, items, blocks, selected }: {
    source: string; items: Item[]; blocks: BlockSpec[]; selected: string | null;
  } = $props();
  const context = $derived(builderContext(source, items, blocks, selected));
</script>

<Block tag="section" class="wpfb-section wpfb-chat" aria-label={copy.panel}>
  <Text tag="h2" class="wpfb-heading">{copy.panel}</Text>
  <Text tag="p" role="status">{copy.unavailable}</Text>
  <Text tag="p" class="wpfb-docs">{copy.runtime}</Text>
  <Textarea disabled aria-label={copy.prompt} placeholder={copy.prompt} rows="3" />
  <Button disabled class="button">{copy.send}</Button>
  <Text tag="h3" class="wpfb-heading">{copy.context}</Text>
  <Text tag="p" class="wpfb-docs">{copy.details}</Text>
  <Box tag="div" class="wpfb-chat-context" aria-label={copy.context}>
    <Text tag="pre">{JSON.stringify(context, null, 2)}</Text>
  </Box>
</Block>
