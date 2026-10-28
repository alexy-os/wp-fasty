<script lang="ts">
  // "New page" panel: a modal sheet (native <dialog>: focus kept inside, Esc closes) with the title, the slug (made
  // from the title until it is edited) and the starter set — Empty or a demo landing available on this site. Creates
  // a draft page (POST /pages: the starter's blocks go into its Builder draft, the page template is not touched) and
  // opens it in the Builder. Used by the editor bar and the Builder page chooser.
  import Icon from "./Icon.svelte";
  import { Briefcase, File, LayoutTemplate, Newspaper, ShoppingBag, X } from "./icons";
  import { slugOf } from "./slug";
  import type { NewPage, Starter } from "./types";

  let { open = $bindable(false), starters, create, beforeleave }: {
    open?: boolean;
    starters: Starter[];
    create: (title: string, slug: string, starter: string) => Promise<NewPage>;
    /** runs before the browser leaves for the new page (the editor saves a pending draft) */
    beforeleave?: () => Promise<void>;
  } = $props();

  let title = $state("");
  let typed = $state("");
  let edited = $state(false);
  let starter = $state("empty");
  let busy = $state(false);
  let error = $state("");

  const slug = $derived(slugOf(title, typed, edited));
  const icons: Record<string, typeof File> = { shop: ShoppingBag, folio: Briefcase, press: Newspaper };
  const choices = $derived([
    { id: "empty", label: "Empty", description: "No blocks: start from a blank page.", blocks: 0, icon: File },
    ...starters.map((s) => ({ ...s, icon: icons[s.id] ?? LayoutTemplate })),
  ]);

  /** the dialog follows `open`; closing it (Esc, Cancel, the close button) sets `open` back */
  const modal = (d: HTMLDialogElement) => {
    if (open && !d.open) {
      error = "";
      d.showModal();
    } else if (!open && d.open) d.close();
  };

  function editSlug(v: string) {
    typed = v;
    // clearing the field goes back to the slug of the title
    edited = v !== "";
  }

  async function submit(e: SubmitEvent) {
    e.preventDefault();
    if (busy || !title.trim()) return;
    busy = true;
    error = "";
    try {
      const page = await create(title.trim(), slug, starter);
      await beforeleave?.();
      window.location.assign(page.url);
    } catch (err) {
      error = (err as Error).message;
      busy = false;
    }
  }
</script>

<dialog class="wpfb-sheet" aria-labelledby="wpfb-new-title" {@attach modal} onclose={() => (open = false)}>
  <form class="wpfb-sheet-form" onsubmit={submit}>
    <header class="wpfb-sheet-head">
      <h2 id="wpfb-new-title">New page</h2>
      <button type="button" class="wpfb-icon" aria-label="Close" title="Close (Esc)" onclick={() => (open = false)}><Icon icon={X} /></button>
    </header>
    <div class="wpfb-sheet-body wpfb-scroll">
      <div class="wpfb-sheet-field">
        <label for="wpfb-new-page-title">Title</label>
        <!-- svelte-ignore a11y_autofocus -->
        <input id="wpfb-new-page-title" type="text" required autocomplete="off" autofocus bind:value={title} />
      </div>
      <div class="wpfb-sheet-field">
        <label for="wpfb-new-page-slug">Slug</label>
        <input id="wpfb-new-page-slug" type="text" autocomplete="off" spellcheck="false" aria-describedby="wpfb-new-page-slug-hint"
          value={slug} oninput={(e) => editSlug(e.currentTarget.value)} />
        <p id="wpfb-new-page-slug-hint" class="wpfb-sheet-hint">{edited ? "Clear the field to make it from the title again." : "Made from the title; edit it to choose your own."}</p>
      </div>
      <fieldset class="wpfb-sheet-field wpfb-starters">
        <legend>Start with</legend>
        {#each choices as c (c.id)}
          <label class={["wpfb-starter", { "is-on": starter === c.id }]}>
            <input type="radio" name="wpfb-starter" value={c.id} bind:group={starter} />
            <span class="wpfb-starter-icon"><Icon icon={c.icon} /></span>
            <span class="wpfb-starter-text">
              <span class="wpfb-starter-label">{c.label}{#if c.blocks}<span class="wpfb-muted">{` · ${c.blocks} blocks`}</span>{/if}</span>
              <span class="wpfb-starter-docs">{c.description}</span>
            </span>
          </label>
        {/each}
        <p class="wpfb-sheet-hint">A starter set only adds blocks; the page template stays as it is.</p>
      </fieldset>
      {#if error}<p class="wpfb-error" role="alert">{error}</p>{/if}
    </div>
    <footer class="wpfb-sheet-foot">
      <button type="button" class="button" onclick={() => (open = false)}>Cancel</button>
      <button type="submit" class="button button-primary" disabled={busy || !title.trim()}>{busy ? "Creating…" : "Create page"}</button>
    </footer>
  </form>
</dialog>
