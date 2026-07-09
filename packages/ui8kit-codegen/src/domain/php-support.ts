/**
 * PHP runtime support predicate — decides mechanically which parts the Latte
 * and Twig emitters generate. There is no hand-maintained tier list: a part
 * is supported when its render tree fits the template-engine composition
 * model (single linear children chain, no loops, element root). Everything
 * else is skipped and reported by the CLI summary.
 */

import type { Node, PartDef } from "./model";

export type PhpSupport = { ok: true } | { ok: false; reason: string };

function hasForEach(node: Node): boolean {
  switch (node.kind) {
    case "forEach":
      return true;
    case "element":
      return node.children.some(hasForEach);
    case "when":
      return node.then.some(hasForEach) || (node.else ?? []).some(hasForEach);
    default:
      return false;
  }
}

function hasSlot(node: Node): boolean {
  switch (node.kind) {
    case "slot":
      return true;
    case "element":
      return node.children.some(hasSlot);
    case "when":
      return node.then.some(hasSlot) || (node.else ?? []).some(hasSlot);
    case "forEach":
      return node.body.some(hasSlot);
    default:
      return false;
  }
}

/** True when any `when` node carries a slot inside its branches. */
function hasBranchedSlot(node: Node): boolean {
  switch (node.kind) {
    case "when":
      return node.then.some(hasSlot) || (node.else ?? []).some(hasSlot);
    case "element":
      return node.children.some(hasBranchedSlot);
    case "forEach":
      return node.body.some(hasBranchedSlot);
    default:
      return false;
  }
}

/**
 * Supported ⇔ the part renders an element root, without loops, and without
 * `when` branches that contain the children slot (Latte/Twig cannot declare
 * the same slot twice across exclusive branches).
 */
export function phpSupported(part: PartDef): PhpSupport {
  if (part.render.kind !== "element") {
    return { ok: false, reason: "root is not a single element (branched roots need per-branch slots)" };
  }
  if (hasForEach(part.render)) {
    return { ok: false, reason: "forEach over typed items" };
  }
  if (hasBranchedSlot(part.render)) {
    return { ok: false, reason: "slot inside when branches" };
  }
  return { ok: true };
}

/** Parts of a brick the PHP emitters generate. */
export function phpSupportedParts(parts: PartDef[]): PartDef[] {
  return parts.filter((p) => phpSupported(p).ok);
}
