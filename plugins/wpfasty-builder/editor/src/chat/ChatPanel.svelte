<script lang="ts">
  import { onMount } from "svelte";
  import type { ChatResult, ChatTurn } from "./client";
  import Block from "../compose/Block.svelte";
  import Text from "../ui/text/Text.svelte";
  import Box from "../ui/box/Box.svelte";
  import Button from "../ui/button/Button.svelte";
  import Textarea from "../ui/textarea/Textarea.svelte";
  import type { BlockSpec, Item } from "../types";
  import { builderContext } from "./context";
  import { chatCopy as copy } from "./content";

  let { source, items, blocks, selected, history, send, apply }: {
    source: string; items: Item[]; blocks: BlockSpec[]; selected: string | null;
    history: () => Promise<{turns: ChatTurn[]}>;
    send: (content: string, requestId: string) => Promise<ChatResult>;
    apply: (requestId: string) => Promise<void>;
  } = $props();
  let turns = $state<ChatTurn[]>([]);
  let draft = $state("");
  let busy = $state(false);
  let error = $state("");
  let ready = $state(false);
  let pendingId = $state<string | null>(null);
  let pendingContent = $state("");
  async function load() {
    try { turns = (await history()).turns; ready = true; error = ""; }
    catch (e) { error = (e as Error).message; ready = false; }
  }
  onMount(() => { void load(); });
  async function submit() {
    if (!ready || busy || !draft.trim()) return;
    busy = true; error = "";
    const content = draft.trim();
    const requestId = pendingContent === content && pendingId ? pendingId : crypto.randomUUID();
    pendingId = requestId; pendingContent = content;
    try { await send(content, requestId); draft = ""; pendingId = null; await load(); }
    catch(e) { error = (e as Error).message; await load(); error = (e as Error).message; }
    finally { busy = false; }
  }
  async function accept(requestId: string) {
    if (busy) return;
    busy = true;
    try { await apply(requestId); await load(); }
    catch(e) { error = (e as Error).message; }
    finally { busy = false; }
  }
  const context = $derived(builderContext(source, items, blocks, selected));
</script>

<Block tag="section" class="wpfb-section wpfb-chat" aria-label={copy.panel}>
  <Text tag="h2" class="wpfb-heading">{copy.panel}</Text>
  {#if !ready}<Text tag="p" role="status">{error || copy.unavailable}</Text>{/if}
  {#each turns as turn (turn.requestId)}
    <Box tag="div" class="wpfb-chat-message">
      <Text tag="p">{turn.content ?? ""}</Text>
      <Text tag="p">{turn.answer ?? turn.error ?? turn.state}</Text>
      {#if turn.proposal}
        <Text tag="p">{copy.proposal}</Text>
        <Text tag="pre">{JSON.stringify(turn.proposal, null, 2)}</Text>
        <Button class="button" disabled={busy} onclick={() => accept(turn.requestId)}>{copy.apply}</Button>
      {/if}
    </Box>
  {/each}
  <Textarea bind:value={draft} disabled={!ready || busy} aria-label={copy.label} placeholder={copy.label} rows="3" />
  <Button disabled={!ready || busy || !draft.trim()} class="button" onclick={submit}>{busy ? copy.pending : pendingId && draft.trim() === pendingContent ? copy.retry : copy.send}</Button>
  {#if ready && error}<Text tag="p" role="alert">{error}</Text>{/if}
  <Text tag="h3" class="wpfb-heading">{copy.context}</Text>
  <Text tag="p" class="wpfb-docs">{copy.details}</Text>
  <Box tag="div" class="wpfb-chat-context" aria-label={copy.context}>
    <Text tag="pre">{JSON.stringify(context, null, 2)}</Text>
  </Box>
</Block>
