<script lang="ts">
import ChatPanel from "./ChatPanel.svelte";
import type {FeatureContext} from "../../../plugins/wpfasty-builder/editor/src/features";
import type {ChatHistory, ChatProgress, ChatResult} from "./client";
let {context}: {context: FeatureContext} = $props();
const path = () => `/chat/${context.config.page}`;
const history = () => context.request<ChatHistory>(`${path()}/history`, {cache: "no-store"});
const progress = (requestId: string, signal: AbortSignal) => context.request<ChatProgress>(`${path()}/progress?requestId=${encodeURIComponent(requestId)}`, {signal, cache: "no-store"});
async function send(content: string, requestId: string) {await context.beforeOperation(); return context.request<ChatResult>(`${path()}/turn`, {method: "POST", body: JSON.stringify({content, requestId})});}
const apply = (requestId: string) => context.apply(requestId, id => context.request<{version: number}>(`${path()}/apply`, {method: "POST", body: JSON.stringify({requestId: id})}));
</script>
<ChatPanel source={context.source} items={context.items} blocks={context.blocks} selected={context.selected} {history} {progress} {send} {apply} />
