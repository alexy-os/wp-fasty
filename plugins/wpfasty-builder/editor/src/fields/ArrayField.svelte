<script lang="ts">
  import Icon from "../Icon.svelte";
  import { ChevronUp, ChevronDown, Copy, Trash2, Plus } from "../icons";
  import type { Json, Obj } from "../types";
  import type { Field } from "./schema";
  import { plain, type FieldCtx, type OnChange } from "./context";
  import { clone, isObj, setPath } from "../util";
  import FieldRenderer from "./FieldRenderer.svelte";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const f = $derived(field.type === "array" ? field : null);
  const list = $derived(Array.isArray(value) ? value : []);
  let open = $state<number | null>(null);

  function put(next: Json[]) {
    onchange(path, next);
  }
  /** a change inside item i: item paths are relative to the item */
  function itemChange(i: number, sub: string[], v: Json | undefined) {
    const next = clone(list);
    if (f?.item.type === "object") next[i] = setPath(isObj(next[i]) ? (next[i] as Obj) : {}, sub, v ?? "");
    else next[i] = v ?? "";
    put(next);
  }
  function move(i: number, to: number) {
    if (to < 0 || to >= list.length) return;
    const next = clone(list);
    const [it] = next.splice(i, 1);
    next.splice(to, 0, it!);
    open = open === i ? to : open;
    put(next);
  }
  function dup(i: number) {
    const next = clone(list);
    const copy = clone(list[i]!);
    if (isObj(copy) && typeof copy.id === "string") copy.id = `${copy.id}-copy`;
    next.splice(i + 1, 0, copy);
    put(next);
  }
  function remove(i: number) {
    put(list.filter((_, j) => j !== i));
    open = null;
  }
  function add() {
    if (!f) return;
    put([...clone(list), f.defaultItem()]);
    open = list.length;
  }
</script>

{#if f}
  <fieldset class="wpfb-group wpfb-array">
    <legend><Label label={f.label} {path} {ctx} {onchange} tag="span" /> <span class="wpfb-muted">{list.length}</span></legend>
    <ol class="wpfb-list">
      {#each list as item, i (i)}
        <li>
          <div class="wpfb-li wpfb-item-head" class:is-open={open === i}>
            <button type="button" class="wpfb-li-main wpfb-item-title" aria-expanded={open === i} onclick={() => (open = open === i ? null : i)}>
              <span>{f.summary(item, i)}</span>
            </button>
            <button type="button" class="wpfb-icon" aria-label="Move up" disabled={i === 0} onclick={() => move(i, i - 1)}><Icon icon={ChevronUp} size={16} /></button>
            <button type="button" class="wpfb-icon" aria-label="Move down" disabled={i === list.length - 1} onclick={() => move(i, i + 1)}><Icon icon={ChevronDown} size={16} /></button>
            <button type="button" class="wpfb-icon" aria-label="Duplicate" onclick={() => dup(i)}><Icon icon={Copy} size={16} /></button>
            <button type="button" class="wpfb-icon wpfb-danger" aria-label="Remove" onclick={() => remove(i)}><Icon icon={Trash2} size={16} /></button>
          </div>
          {#if open === i}
            <div class="wpfb-sub wpfb-item-body">
              {#if f.item.type === "object"}
                {#each Object.entries(f.arrayFields) as [k, sub] (k)}
                  <FieldRenderer field={sub} path={[...path, String(i), k]} value={isObj(item) ? item[k] : undefined}
                    onchange={(p, v) => itemChange(i, p.slice(path.length + 1), v)} ctx={plain} />
                {/each}
              {:else}
                <FieldRenderer field={f.item} path={[...path, String(i)]} value={item} onchange={(_, v) => itemChange(i, [], v)} ctx={plain} />
              {/if}
            </div>
          {/if}
        </li>
      {/each}
    </ol>
    <button type="button" class="button wpfb-add" onclick={add}><Icon icon={Plus} size={16} /> Add {f.summary(null, list.length).replace(/ \d+$/, "").toLowerCase()}</button>
  </fieldset>
{/if}
