<script lang="ts">
  // The "New page" panel on the Builder page chooser (no page open): the heading's "New page" button
  // (Admin::newPageButton, #wpfb-new-page) opens it; ?new=1 ("Add New with Builder" on Pages) opens it on load.
  import { untrack } from "svelte";
  import NewPage from "./NewPage.svelte";
  import { api } from "./api";
  import type { Config } from "./types";

  let { config: initial }: { config: Config } = $props();
  const config = untrack(() => initial);
  const client = api(config);
  let open = $state(config.newPage === true);

  /** the server-rendered heading button opens the panel */
  const trigger = () => {
    const button = document.getElementById("wpfb-new-page");
    const show = () => (open = true);
    button?.addEventListener("click", show);
    return () => button?.removeEventListener("click", show);
  };
</script>

<div class="wpfb-tokens" {@attach trigger}>
  <NewPage bind:open starters={config.starters ?? []} create={client.createPage} />
</div>
