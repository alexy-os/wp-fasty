/** Application copy, not content baked into block templates. */
export const chatCopy = {
  tab: "Chat",
  blocks: "Blocks",
  panel: "Builder assistant",
  unavailable: "Agent runtime not connected. No messages are sent and no page changes are made here.",
  runtime: "Grok via the local Node worker. Changes are proposals until you apply them to this draft; publishing stays manual.",
  context: "Current Builder structure",
  details: "Live draft context: catalog blockId, variant, props and bindings. Preview stays on the existing Latte/Twig canvas.",
  prompt: "Message (unavailable until a server-side runtime is configured)",
  send: "Send to Grok",
  retry: "Retry same request (no duplicate turn)",
  apply: "Apply proposal to draft",
  pending: "Waiting for local worker…",
  failed: "Request failed. Check history before retrying; no automatic paid retry.",
  label: "Message to Builder assistant",
  proposal: "Proposed structural draft (not applied)",
} as const;
