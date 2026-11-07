<script lang="ts">
  import { proposalPresentation } from "./proposal";
  import { onMount, tick } from "svelte";
  import type { ChatHistory, ChatResult, ChatTurn, ChatProgress } from "./client";
  import { watchProgress } from "./progress";
  import { usageLabel } from "./presentation";
  import MessageBody from "./MessageBody.svelte";
  import CodeBox from "./CodeBox.svelte";
  import Block from "../compose/Block.svelte";
  import Text from "../ui/text/Text.svelte";
  import Box from "../ui/box/Box.svelte";
  import Button from "../ui/button/Button.svelte";
  import Textarea from "../ui/textarea/Textarea.svelte";
  import type { BlockSpec, Item } from "../types";
  import { builderContext } from "./context";
  import { chatCopy as copy } from "./content";

  let { source, items, blocks, selected, history, progress, send, apply }: {
    source: string; items: Item[]; blocks: BlockSpec[]; selected: string | null;
    history: () => Promise<ChatHistory>;
    progress: (requestId: string, signal: AbortSignal) => Promise<ChatProgress>;
    send: (content: string, requestId: string) => Promise<ChatResult>;
    apply: (requestId: string) => Promise<void>;
  } = $props();
  let turns = $state<ChatTurn[]>([]);
  let draft = $state("");
  let busy = $state(false);
  let error = $state("");
  let ready = $state(false);
  let showContext = $state(false);
  let showProposals = $state<Record<string, boolean>>({});
  let sending = $state(false);
  let activity = $state<ChatProgress | null>(null);
  let progressMissing = $state(false);
  let stopObserving: (() => void) | undefined;
  let disposed = false;
  let follow = true;
  async function scrollToLatest() {
    await tick();
    if (disposed || !follow) return;
    const log = document.getElementById("wpfb-chat-transcript");
    if (log) log.scrollTop = log.scrollHeight;
  }
  function scroll(event: Event) {
    const log = event.currentTarget as HTMLElement;
    follow = log.scrollHeight - log.scrollTop - log.clientHeight < 64;
  }
  let pendingId = $state<string | null>(null);
  let pendingContent = $state("");
  async function load() {
    try { const data = await history(); if (disposed) return; turns = data.turns; ready = data.availability?.configured === true; error = ready ? "" : data.availability?.message ?? copy.unavailable; }
    catch (e) { if (!disposed) { error = (e as Error).message; ready = false; } }
    await scrollToLatest();
  }
  onMount(() => { void load(); return () => { disposed = true; stopObserving?.(); }; });
  async function submit() {
    if (!ready || busy || !draft.trim()) return;
    busy = true; sending = true; activity = null; progressMissing = false; error = ""; follow = true;
    const content = draft.trim();
    const requestId = pendingContent === content && pendingId ? pendingId : crypto.randomUUID();
    pendingId = requestId; pendingContent = content;
    stopObserving = watchProgress(progress, requestId, value => { activity = value; }, () => { progressMissing = true; });
    void scrollToLatest();
    try { await send(content, requestId); draft = ""; pendingId = null; await load(); }
    catch(e) { error = (e as Error).message; await load(); error = (e as Error).message; }
    finally { stopObserving?.(); stopObserving = undefined; sending = false; activity = null; busy = false; }
  }
  async function accept(requestId: string) {
    if (busy) return;
    busy = true;
    try { await apply(requestId); await load(); }
    catch(e) { await load(); error = (e as Error).message; }
    finally { busy = false; }
  }
  const context = $derived(builderContext(source, items, blocks, selected));
</script>

<Block tag="section" class="wpfb-section wpfb-chat" aria-label={copy.panel}>
  <Box tag="header" class="wpfb-chat-header">
    <Text tag="h2" class="wpfb-heading">{copy.panel}</Text>
    <Text tag="p" class="wpfb-chat-caption">{copy.runtime}</Text>
  </Box>
  {#if !ready}<Text tag="p" class="wpfb-chat-notice" role="status">{error || copy.unavailable}</Text>{/if}
  <Box tag="div" id="wpfb-chat-transcript" class="wpfb-chat-transcript wpfb-scroll" role="log" aria-label={copy.panel} onscroll={scroll} tabindex="0">
    {#if !turns.length}<Text tag="p" class="wpfb-chat-caption">{copy.empty}</Text>{/if}
    {#each turns as turn (turn.requestId)}
      {@const application = proposalPresentation(turn)}
      <Box tag="div" class="wpfb-chat-turn">
        <Box tag="div" class="wpfb-chat-message wpfb-chat-user">
          <Text tag="p" class="wpfb-chat-role">{copy.user}</Text>
          <Text tag="p" class="wpfb-chat-prose">{turn.content ?? ""}</Text>
        </Box>
        <Box tag="div" class="wpfb-chat-message wpfb-chat-assistant">
          <Text tag="p" class="wpfb-chat-role">{copy.assistant}</Text>
          <MessageBody value={turn.answer ?? turn.error ?? turn.state} />
          {#if turn.proposal}
            <Box tag="div" class="wpfb-chat-proposal">
              <Text tag="p" class="wpfb-chat-role">{copy.proposal}</Text>
              <Text tag="p" class="wpfb-chat-caption">{application.state === "applied" ? copy.appliedProposal(application.appliedVersion!, application.currentVersion!) : application.state === "pending" ? copy.pendingProposal : application.state === "stale" ? copy.staleProposal : copy.unknownProposal}</Text>
              {#if application.canApply}<Button class="button" disabled={busy} onclick={() => accept(turn.requestId)}>{copy.apply}</Button>{/if}
              <Button class="button wpfb-chat-disclosure" aria-expanded={showProposals[turn.requestId] ? "true" : "false"} onclick={() => { showProposals[turn.requestId] = !showProposals[turn.requestId]; }}>{showProposals[turn.requestId] ? copy.hideProposal : copy.showProposal}</Button>
              {#if showProposals[turn.requestId]}<CodeBox value={JSON.stringify(turn.proposal, null, 2)} />{/if}
            </Box>
          {/if}
          <Text tag="p" class="wpfb-chat-usage">{usageLabel(turn.usage)}</Text>
        </Box>
      </Box>
    {/each}
    {#if sending}
      <Box tag="div" class="wpfb-chat-message wpfb-chat-user"><Text tag="p" class="wpfb-chat-role">{copy.user}</Text><Text tag="p" class="wpfb-chat-prose">{pendingContent}</Text></Box>
      <Text tag="p" class="wpfb-chat-notice" role="status">{progressMissing ? copy.progressUnavailable : activity?.tool ? `${activity.tool.name === "catalog_lookup" ? copy.catalogTool : copy.proposalTool} · ${copy.toolStatus[activity.tool.status]}` : copy.requestPending}</Text>
    {/if}
  </Box>
  <Box tag="div" class="wpfb-chat-composer">
    <Textarea bind:value={draft} disabled={!ready || busy} aria-label={copy.label} placeholder={copy.label} rows="3" />
    <Button disabled={!ready || busy || !draft.trim()} class="button button-primary" onclick={submit}>{busy ? copy.pending : pendingId && draft.trim() === pendingContent ? copy.retry : copy.send}</Button>
    {#if ready && error}<Text tag="p" class="wpfb-chat-notice" role="alert">{error}</Text>{/if}
    {#if sending}<Text tag="p" class="wpfb-chat-caption">{copy.observation}</Text>{/if}
    <Button class="button wpfb-chat-disclosure" aria-expanded={showContext ? "true" : "false"} onclick={() => { showContext = !showContext; }}>{showContext ? copy.hideContext : copy.showContext}</Button>
    {#if showContext}<Box tag="div" class="wpfb-chat-context wpfb-scroll" aria-label={copy.context}><Text tag="p" class="wpfb-chat-caption">{copy.details}</Text><CodeBox value={JSON.stringify(context, null, 2)} /></Box>{/if}
  </Box>
</Block>
