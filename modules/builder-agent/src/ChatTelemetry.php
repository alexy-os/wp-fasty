<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Pure transport contract; no WordPress, SDK, persistence, billing estimates or raw event payloads. */
final class ChatTelemetry
{
    /**
     * Missing/invalid telemetry is unknown, never a fabricated zero.
     *
     * @return array<string, int>|null
     */
    public static function usage(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }
        $safe = [];
        foreach (['inputTokens', 'outputTokens', 'cacheReadTokens', 'cacheWriteTokens', 'totalTokens'] as $key) {
            $count = $value[$key] ?? null;
            if (!is_int($count) || $count < 0 || $count > 1_000_000_000_000) {
                return null;
            }
            $safe[$key] = $count;
        }
        if (array_key_exists('reasoningTokens', $value)) {
            $count = $value['reasoningTokens'];
            if (!is_int($count) || $count < 0 || $count > 1_000_000_000_000) {
                return null;
            }
            $safe['reasoningTokens'] = $count;
        }

        return $safe;
    }

    /**
     * Only the requested turn, fixed tool names and statuses; args/results/thinking never cross the proxy.
     *
     * @return array{requestId: string, state: string, sequence: int, tool: array{name: string, status: string}|null}
     */
    public static function progress(mixed $value, string $requestId): array
    {
        $safe = ['requestId' => $requestId, 'state' => 'unknown', 'sequence' => 0, 'tool' => null];
        if (!is_array($value) || ($value['requestId'] ?? null) !== $requestId || !in_array($value['state'] ?? null, ['pending', 'done', 'failed', 'unknown'], true)) {
            return $safe;
        }
        $safe['state'] = $value['state'];
        $safe['sequence'] = is_int($value['sequence'] ?? null) && $value['sequence'] >= 0 ? $value['sequence'] : 0;
        $tool = $value['tool'] ?? null;
        if ('pending' === $safe['state'] && is_array($tool) && in_array($tool['name'] ?? null, ['catalog_lookup', 'search_catalog', 'get_block_details', 'get_skill', 'propose_layout'], true) && in_array($tool['status'] ?? null, ['running', 'completed', 'error'], true)) {
            $safe['tool'] = ['name' => $tool['name'], 'status' => $tool['status']];
        }

        return $safe;
    }
}
