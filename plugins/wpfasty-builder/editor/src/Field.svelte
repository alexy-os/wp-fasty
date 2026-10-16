<script lang="ts">
  import Field from "./Field.svelte";
  import type { Json, Obj } from "./types";
  import { human, isObj } from "./util";

  interface Props {
    name: string;
    path: string[];
    value: Json | undefined;
    options?: string[] | null;
    axes: Record<string, string[]>;
    overridden: (path: string[]) => boolean;
    onchange: (path: string[], value: Json | undefined) => void;
    docs?: string;
  }
  let { name, path, value, options = null, axes, overridden, onchange, docs = "" }: Props = $props();
  const id = $derived("wpfb-f-" + path.join("-"));
  const opts = $derived(options ?? axes[path.join(".")] ?? null);
  let json = $state("");
  let jsonError = $state("");
  $effect(() => {
    json = JSON.stringify(value ?? null, null, 2);
  });
  function commitJson() {
    try {
      onchange(path, JSON.parse(json));
      jsonError = "";
    } catch (e) {
      jsonError = (e as Error).message;
    }
  }
</script>

{#if opts}
  <div class="wpfb-field" class:is-set={overridden(path)}>
    <label for={id}>{human(name)}</label>
    <select {id} value={String(value ?? "")} onchange={(e) => onchange(path, (e.currentTarget as HTMLSelectElement).value)}>
      {#each opts as o (o)}<option value={o}>{o}</option>{/each}
    </select>
  </div>
{:else if typeof value === "boolean"}
  <div class="wpfb-field wpfb-check" class:is-set={overridden(path)}>
    <label><input type="checkbox" checked={value} onchange={(e) => onchange(path, (e.currentTarget as HTMLInputElement).checked)} /> {human(name)}</label>
  </div>
{:else if typeof value === "number"}
  <div class="wpfb-field" class:is-set={overridden(path)}>
    <label for={id}>{human(name)}</label>
    <input {id} type="number" {value} onchange={(e) => onchange(path, Number((e.currentTarget as HTMLInputElement).value))} />
  </div>
{:else if typeof value === "string" || value === undefined || value === null}
  <div class="wpfb-field" class:is-set={overridden(path)}>
    <label for={id}>{human(name)}</label>
    {#if String(value ?? "").length > 60}
      <textarea {id} rows="3" value={String(value ?? "")} onchange={(e) => onchange(path, (e.currentTarget as HTMLTextAreaElement).value)}></textarea>
    {:else}
      <input {id} type="text" value={String(value ?? "")} onchange={(e) => onchange(path, (e.currentTarget as HTMLInputElement).value)} />
    {/if}
    {#if docs}<p class="wpfb-docs">{docs}</p>{/if}
  </div>
{:else if isObj(value)}
  <fieldset class="wpfb-group">
    <legend>{human(name)}</legend>
    {#each Object.entries(value as Obj) as [k, v] (k)}
      <Field name={k} path={[...path, k]} value={v} {axes} {overridden} {onchange} />
    {/each}
  </fieldset>
{:else}
  <details class="wpfb-field wpfb-json" class:is-set={overridden(path)}>
    <summary>{human(name)} <span class="wpfb-muted">({(value as Json[]).length} items, sample data)</span></summary>
    <textarea rows="8" bind:value={json} onchange={commitJson} spellcheck="false"></textarea>
    {#if jsonError}<p class="wpfb-error">{jsonError}</p>{/if}
  </details>
{/if}
