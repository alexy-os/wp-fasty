import type { ChatProgress } from "./client";
/** One in-flight GET, bounded observation, no retries of POST /turn. Stopping observation does not cancel the agent. */
export function watchProgress(fetch: (id: string, signal: AbortSignal) => Promise<ChatProgress>, id: string, update: (value: ChatProgress) => void, unavailable: () => void, interval = 800, lifetime = 100000): () => void {
  const controller = new AbortController();
  let timer: ReturnType<typeof setTimeout> | undefined;
  let stopped = false;
  let sequence = -1;
  const stop = () => { stopped = true; clearTimeout(timer); clearTimeout(deadline); controller.abort(); };
  const deadline = setTimeout(() => { if (!stopped) unavailable(); stop(); }, lifetime);
  async function poll() {
    try {
      const result = await fetch(id, controller.signal);
      if (stopped) return;
      if (result.requestId !== id) throw new Error("progress_scope_mismatch");
      if (result.state === "done" || result.state === "failed") { update({...result, tool: null}); stop(); return; }
      // A serialized stream cannot move backwards: worker restart/invalid observation, not current tool activity.
      if (!Number.isSafeInteger(result.sequence) || result.sequence < sequence) throw new Error("progress_sequence_reset");
      sequence = result.sequence; update(result);
      timer = setTimeout(() => { void poll(); }, interval);
    } catch { if (!stopped) { unavailable(); stop(); } }
  }
  void poll();
  return stop;
}
