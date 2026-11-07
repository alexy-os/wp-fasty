import type { Item } from "../types";
export interface ChatResult { answer: string; content: string; proposal: Item[] | null; baseVersion: number; usage?: TokenUsage | null; agentPolicy?: AgentPolicyProvenance | null }
export interface ChatTurn extends Partial<ChatResult> { requestId: string; state: string; error?: string; application?: ProposalApplication }

export interface ChatHistory { turns: ChatTurn[]; availability?: { configured: boolean; model: string; message: string } }

export interface ProposalApplication { state: "pending" | "applied" | "stale"; baseVersion: number; appliedVersion: number | null; currentVersion: number; canApply: boolean }

/** Actual cumulative counts of this SDK run; legacy history or unreported telemetry stays null. */
export interface TokenUsage { inputTokens: number; outputTokens: number; cacheReadTokens: number; cacheWriteTokens: number; totalTokens: number; reasoningTokens?: number }
export interface ChatProgress { requestId: string; state: "unknown" | "pending" | "done" | "failed"; sequence: number; tool: { name: "catalog_lookup" | "search_catalog" | "get_block_details" | "get_skill" | "propose_layout"; status: "running" | "completed" | "error" } | null }

export interface AgentPolicyProvenance { policyVersion: number; briefVersion: number; policyDigest: string; loadedSkills: { id: string; version: number }[] }
