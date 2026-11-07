import type { Item } from "../types";
export interface ChatResult { answer: string; content: string; proposal: Item[] | null; baseVersion: number }
export interface ChatTurn extends Partial<ChatResult> { requestId: string; state: string; error?: string }

export interface ChatHistory { turns: ChatTurn[]; availability?: { configured: boolean; model: string; message: string } }
