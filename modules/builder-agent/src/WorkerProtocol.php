<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Public no-model handshake. Presence of credentials is not proof of protocol compatibility. */
final class WorkerProtocol
{
    public static function compatible(mixed $status): bool
    {
        if (!is_array($status) || 'wpfasty-agent/1' !== ($status['protocol'] ?? null) || !is_array($status['capabilities'] ?? null)) {
            return false;
        }

        return in_array('local-search/1', $status['capabilities'], true) && in_array('policy/1', $status['capabilities'], true) && in_array('diagnostics/1', $status['capabilities'], true);
    }
}
