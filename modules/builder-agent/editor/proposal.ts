import type { ChatTurn } from "./client";
/** Fail closed; worker generation state alone never authorizes draft application. */
export function proposalPresentation(turn: ChatTurn) {
  const value = turn.application;
  if (!value) return { state: "unknown", canApply: false, appliedVersion: null, currentVersion: null };
  if (value.state === "applied" && Number.isInteger(value.appliedVersion)) return { ...value, canApply: false };
  return { ...value, canApply: value.state === "pending" && value.appliedVersion === null && value.baseVersion === value.currentVersion && value.canApply === true };
}
