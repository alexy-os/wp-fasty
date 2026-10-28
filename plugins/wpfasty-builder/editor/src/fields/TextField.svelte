<script lang="ts">
  import Icon from "../Icon.svelte";
  import { Braces, TriangleAlert } from "../icons";
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";
  import LinkPicker from "./LinkPicker.svelte";
  import { applyMacro, expandText, insertMacro, parseMacro, resolveMacro, tokenMode } from "../links";
  import { getLinks } from "../links.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const text = $derived(value === undefined || value === null ? "" : String(value));
  /** what the input shows: the value, or what is being typed until it is committed (the hint follows it) */
  let draft = $derived(text);
  const id = $derived(fid(path));
  /** link fields: link tokens (replace the token, keep the suffix); other text fields: site data tokens (inserted) */
  const mode = $derived(tokenMode(path[path.length - 1] ?? "", field));
  const links = getLinks();
  const macro = $derived(mode === "link" ? parseMacro(draft) : null);
  /** the hint under the field: what the tokens turn into, what is missing */
  const hint = $derived.by(() => {
    if (!mode || !links.index) return null;
    if (mode === "link") {
      const r = resolveMacro(draft, links.index);
      if (!r) return null;
      return "error" in r ? { error: r.error, shown: "", title: "", warnings: [] } : { error: "", shown: r.shown, title: r.url, warnings: r.warning ? [r.warning] : [] };
    }
    const r = expandText(draft, links.index);
    return r ? { error: "", shown: r.text, title: r.text, warnings: r.warnings } : null;
  });
  let open = $state(false);
  let box: HTMLDivElement | undefined = $state();
  let input: HTMLInputElement | HTMLTextAreaElement | undefined = $state();

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
    const current = input?.value ?? draft;
    let caret: number | null = null;
    let v: string;
    if (mode === "link") v = applyMacro(current, token);
    else ({ value: v, caret } = insertMacro(current, token, input?.selectionStart, input?.selectionEnd));
    draft = v;
    commit(v);
    close(true);
    if (caret !== null && input) {
      input.value = v;
      input.setSelectionRange(caret, caret);
    }
  }
  function close(refocus: boolean) {
    open = false;
    if (refocus) input?.focus();
  }
  function keys(e: KeyboardEvent) {
    if (mode && e.key === "ArrowDown" && e.altKey) {
      e.preventDefault();
      toggle();
    }
  }
  const described = $derived(hint ? id + "-hint" : undefined);
</script>

<div class="wpfb-field">
  <Label label={field.label} {path} {ctx} {onchange} />
  <div class={["wpfb-link-field", { "is-text": mode === "text", "is-area": field.type === "textarea", "has-token": !!mode }]} bind:this={box}>
    {#if field.type === "textarea"}
      <textarea {id} rows="3" value={draft} bind:this={input} aria-describedby={described}
        oninput={(e) => typed(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)} onkeydown={keys}></textarea>
    {:else}
      <input {id} type="text" value={draft} bind:this={input} aria-describedby={described}
        oninput={(e) => typed(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)} onkeydown={keys} />
    {/if}
    {#if mode}
      <button type="button" class={["wpfb-link-icon", { "is-open": open }]} title="{mode === 'link' ? 'Link tokens' : 'Site data tokens'} (Alt+↓)"
        aria-label={mode === "link" ? "Link tokens" : "Site data tokens"} aria-haspopup="listbox" aria-expanded={open}
        aria-controls={open ? id + "-picker-list" : undefined} onclick={toggle}>
        <Icon icon={Braces} size={16} />
      </button>
      {#if open && box}
        <LinkPicker id="{id}-picker" anchor={box} {links} {mode} current={macro?.token ?? ""} onpick={pick} onclose={close} />
      {/if}
    {/if}
  </div>
  {#if hint}
    <div class="wpfb-field-hint" id="{id}-hint">
      {#if hint.error}
        <p class="is-error" role="alert">{hint.error}</p>
      {:else}
        <p title={hint.title}>→ {hint.shown}</p>
      {/if}
      {#each hint.warnings as w (w)}
        <p class="is-warning"><Icon icon={TriangleAlert} size={12} /> {w}</p>
      {/each}
    </div>
  {/if}
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
