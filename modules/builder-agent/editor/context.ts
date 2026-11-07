import type { BlockSpec, Item } from "../../../plugins/wpfasty-builder/editor/src/types";

/** Read-only structural context for a future server agent. Never a summary DTO or HTML. */
export function builderContext(source: string, items: Item[], blocks: BlockSpec[], selected: string | null) {
  const catalog = new Map(blocks.map((block) => [block.id, block]));
  return {
    format: "wpfasty-builder-chat-context/1",
    source,
    selected,
    blocks: items.map((item) => {
      const definition = catalog.get(item.block);
      const variant = definition?.variants.find((v) => v.id === item.variant);
      return {
        instanceId: item.id,
        blockId: item.block,
        variant: item.variant,
        props: item.props,
        bindings: item.bindings,
        template: variant?.template ?? null,
        catalogValid: Boolean(variant),
      };
    }),
  };
}
