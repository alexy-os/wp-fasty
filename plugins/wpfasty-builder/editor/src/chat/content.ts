/** Application copy, not content baked into block templates. */
export const chatCopy = {
  tab: "Chat",
  blocks: "Blocks",
  panel: "Builder assistant",
  unavailable: "Agent runtime not connected. No messages are sent and no page changes are made here.",
  runtime: "Cursor Agent SDK needs a server-side Node runtime; a browser bundle alone is not sufficient. CR is not required by this panel.",
  context: "Current Builder structure",
  details: "Live draft context: catalog blockId, variant, props and bindings. Preview stays on the existing Latte/Twig canvas.",
  prompt: "Message (unavailable until a server-side runtime is configured)",
  send: "Send unavailable",
} as const;
