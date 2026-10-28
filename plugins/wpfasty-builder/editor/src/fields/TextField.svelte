<script lang="ts">
  import Icon from "../Icon.svelte";
  import { Braces } from "../icons";
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";
  import LinkPicker from "./LinkPicker.svelte";
  import { applyMacro, isLinkField, parseMacro, resolveMacro } from "../links";
  import { getLinks } from "../links.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const text = $derived(value === undefined || value === null ? "" : String(value));
  /** what the input shows: the value, or what is being typed until it is committed (the hint follows it) */
  let draft = $derived(text);
  const id = $derived(fid(path));
  const isLink = $derived(isLinkField(path[path.length - 1] ?? "", field));
  const links = getLinks();
  const macro = $derived(isLink ? parseMacro(draft) : null);
  const resolved = $derived(macro && links.index ? resolveMacro(draft, links.index) : null);
  let open = $state(false);
  let box: HTMLDivElement | undefined = $state();
  let input: HTMLInputElement | undefined = $state();

  let timer: ReturnType<typeof setTimeout> | undefined;
  // typing updates the preview after a short pause; leaving the field commits at once
  function typed(v: string) {
    draft = v;
    clearTimeout(timer);
    timer = setTimeout(() => onchange(path, v), 300);
  }
  function commit(v: string) {
    clearTimeout(timer);
    if (v !== text) onchange(path, v);
  }
  function toggle() {
    open = !open;
    if (open) void links.ensure();
  }
  function pick(token: string) {
    const v = applyMacro(input?.value ?? draft, token);
    draft = v;
    commit(v);
    close(true);
  }
  function close(refocus: boolean) {
    open = false;
    if (refocus) input?.focus();
  }
</script>

<div class="wpfb-field">
  <Label label={field.label} {path} {ctx} {onchange} />
  {#if field.type === "textarea"}
    <textarea {id} rows="3" value={draft} oninput={(e) => typed(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)}></textarea>
  {:else if isLink}
    <div class="wpfb-link-field" bind:this={box}>
      <input {id} type="text" value={draft} bind:this={input} aria-describedby={resolved ? id + "-hint" : undefined}
        oninput={(e) => typed(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)}
        onkeydown={(e) => { if (e.key === "ArrowDown" && e.altKey) { e.preventDefault(); toggle(); } }} />
      <button type="button" class={["wpfb-link-icon", { "is-open": open }]} title="Макросы ссылок (Alt+↓)" aria-label="Макросы ссылок"
        aria-haspopup="listbox" aria-expanded={open} aria-controls={open ? id + "-picker-list" : undefined} onclick={toggle}>
        <Icon icon={Braces} size={16} />
      </button>
      {#if open && box}
        <LinkPicker id="{id}-picker" anchor={box} {links} current={macro?.token ?? ""} onpick={pick} onclose={close} />
      {/if}
    </div>
    {#if resolved}
      {#if "error" in resolved}
        <p class="wpfb-field-hint is-error" role="alert" id="{id}-hint">{resolved.error}</p>
      {:else}
        <p class="wpfb-field-hint" id="{id}-hint" title={resolved.url}>→ {resolved.shown}</p>
      {/if}
    {/if}
  {:else}
    <input {id} type="text" value={draft} oninput={(e) => typed(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)} />
  {/if}
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
