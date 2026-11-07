<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Pure versioned instruction document. Data only; no WordPress, SDK, storage or secrets. */
final class AgentPolicyDefinition
{
    public const TOOLS = ['search_catalog', 'get_block_details', 'propose_layout'];

    /**
     * @return array<string, mixed> */
    public static function defaults(): array
    {
        return ['product' => 'Converse, explain and review. For layout changes search the installed catalog, inspect exact block details, then propose a draft. Never invent facts or publish.', 'site' => ['language' => '', 'tone' => '', 'audience' => '', 'brand' => '', 'facts' => ''], 'tools' => self::TOOLS, 'skills' => [
            ['id' => 'block-selection', 'version' => 1, 'enabled' => true, 'purpose' => 'Select compatible installed blocks', 'triggers' => ['new section', 'layout change'], 'instructions' => 'Map the visitor goal to structural roles such as hero, products, pricing or menu. A niche such as sneakers or sushi is content, not a new filter or invented block. Use valid facets, search a shortlist, inspect exact details, preserve existing blocks and propose only on request.', 'requiredTools' => self::TOOLS],
            ['id' => 'text-editing', 'version' => 1, 'enabled' => true, 'purpose' => 'Edit copy without changing structure', 'triggers' => ['headline', 'copy edit'], 'instructions' => 'Inspect the exact current block and variant. Change only requested text props, preserving ids, variant, bindings and other props. Ask for missing factual details; never invent prices or claims. Propose; do not apply.', 'requiredTools' => ['get_block_details', 'propose_layout']],
            ['id' => 'page-review', 'version' => 1, 'enabled' => true, 'purpose' => 'Review the provided saved layout', 'triggers' => ['review', 'critique'], 'instructions' => 'Review the provided layout and user brief. Explain structure, accessibility and copy limitations. Use installed details if needed. Do not imply browser, web, analytics or visual inspection; do not change layout unless requested.', 'requiredTools' => ['get_block_details']],
        ]];
    }

    /**
     * @return array<string, mixed> */
    public static function normalize(mixed $value, bool $page = false): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \InvalidArgumentException('invalid_policy');
        }
        if ($page) {
            self::keys($value, ['brief']);

            return ['brief' => self::text($value['brief'] ?? null, 4000)];
        }
        self::keys($value, ['product', 'site', 'tools', 'skills']);
        $site = $value['site'] ?? null;
        if (!is_array($site) || array_is_list($site)) {
            throw new \InvalidArgumentException('invalid_policy_site');
        }
        self::keys($site, ['language', 'tone', 'audience', 'brand', 'facts']);
        $normalizedSite = [];
        foreach (['language', 'tone', 'audience', 'brand', 'facts'] as $key) {
            $normalizedSite[$key] = self::text($site[$key] ?? null, 2000);
        }
        $tools = self::strings($value['tools'] ?? null, 3, 40);
        if (array_diff($tools, self::TOOLS)) {
            throw new \InvalidArgumentException('invalid_policy_tools');
        }
        $skills = $value['skills'] ?? null;
        if (!is_array($skills) || !array_is_list($skills) || count($skills) > 12) {
            throw new \InvalidArgumentException('invalid_policy_skills');
        }
        $normalized = [];
        $ids = [];
        foreach ($skills as $skill) {
            if (!is_array($skill)) {
                throw new \InvalidArgumentException('invalid_skill');
            }
            self::keys($skill, ['id', 'version', 'enabled', 'purpose', 'triggers', 'instructions', 'requiredTools']);
            $id = self::text($skill['id'] ?? null, 64);
            $version = $skill['version'] ?? null;
            $enabled = $skill['enabled'] ?? null;
            if (!preg_match('/^[a-z][a-z0-9-]{0,63}$/', $id) || in_array($id, ['constructor', 'prototype'], true) || isset($ids[$id]) || !is_int($version) || $version < 1 || $version > 1000000 || !is_bool($enabled)) {
                throw new \InvalidArgumentException('invalid_skill_identity');
            }
            $required = self::strings($skill['requiredTools'] ?? null, 3, 40);
            if (array_diff($required, self::TOOLS)) {
                throw new \InvalidArgumentException('invalid_skill_tools');
            }
            $ids[$id] = true;
            $normalized[] = ['id' => $id, 'version' => $version, 'enabled' => $enabled, 'purpose' => self::text($skill['purpose'] ?? null, 300), 'triggers' => self::strings($skill['triggers'] ?? null, 6, 100), 'instructions' => self::text($skill['instructions'] ?? null, 6000), 'requiredTools' => $required];
        }
        $result = ['product' => self::text($value['product'] ?? null, 4000), 'site' => $normalizedSite, 'tools' => $tools, 'skills' => $normalized];
        if (strlen((string) json_encode($result)) > 64000) {
            throw new \InvalidArgumentException('policy_too_large');
        }

        return $result;
    }

    /**
     * @return array<string, mixed> */
    public static function initial(bool $page = false): array
    {
        return ['revision' => 0, 'activeVersion' => 1, 'versions' => [['version' => 1, 'actor' => 0, 'createdAt' => 'bundled-default', 'definition' => $page ? ['brief' => ''] : self::defaults()]]];
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed> */
    public static function entry(array $state, int $version): array
    {
        $versions = $state['versions'] ?? [];
        if (is_array($versions)) {
            foreach ($versions as $entry) {
                if (is_array($entry) && ($entry['version'] ?? null) === $version) {
                    return ['version' => $version, 'actor' => $entry['actor'] ?? 0, 'createdAt' => $entry['createdAt'] ?? '', 'definition' => $entry['definition'] ?? []];
                }
            }
        }
        throw new \InvalidArgumentException('policy_version_not_found');
    }

    /** Pure CAS transition; adapter commits only if old serialized state still matches.
     * @param array<string, mixed> $state
     *
     * @return array<string, mixed>
     */
    public static function transition(array $state, int $expected, string $action, mixed $payload, int $actor, string $time, bool $page = false): array
    {
        if (($state['revision'] ?? null) !== $expected) {
            throw new \InvalidArgumentException('policy_revision_conflict');
        }
        $versions = $state['versions'] ?? [];
        if (!is_array($versions) || !array_is_list($versions)) {
            throw new \InvalidArgumentException('invalid_policy_state');
        }
        $next = $expected + 1;
        if ('activate' === $action) {
            if (!is_int($payload)) {
                throw new \InvalidArgumentException('invalid_policy_version');
            }
            self::entry($state, $payload);
            if ($payload < ($state['activeVersion'] ?? 1)) {
                throw new \InvalidArgumentException('policy_rollback_requires_new_version');
            }
            $state['activeVersion'] = $payload;
        } elseif (in_array($action, ['draft', 'rollback'], true)) {
            $definition = 'rollback' === $action && is_int($payload) ? self::entry($state, $payload)['definition'] : $payload;
            $normalized = self::normalize($definition, $page);
            // Revision also counts activations, so version ids never repeat after retention.
            $version = $next + 1;
            if (!$page) {
                // Same skill version may not silently acquire different content.
                foreach ($versions as $old) {
                    $oldDefinition = is_array($old) ? ($old['definition'] ?? []) : [];
                    $oldSkills = is_array($oldDefinition) ? ($oldDefinition['skills'] ?? []) : [];
                    foreach (is_array($oldSkills) ? $oldSkills : [] as $oldSkill) {
                        foreach (is_array($normalized['skills']) ? $normalized['skills'] : [] as $skill) {
                            if (is_array($oldSkill) && is_array($skill) && ($oldSkill['id'] ?? null) === ($skill['id'] ?? null) && ($oldSkill['version'] ?? null) === ($skill['version'] ?? null) && $oldSkill !== $skill) {
                                throw new \InvalidArgumentException('skill_version_must_change');
                            }
                        }
                    }
                }
            }
            $versions[] = ['version' => $version, 'actor' => $actor, 'createdAt' => $time, 'definition' => $normalized];
            $active = $state['activeVersion'] ?? 1;
            $kept = array_slice($versions, -19);
            if (!array_filter($kept, static fn (mixed $v): bool => is_array($v) && ($v['version'] ?? null) === $active)) {
                array_unshift($kept, self::entry($state, is_int($active) ? $active : 1));
            }
            $state['versions'] = $kept;
        } else {
            throw new \InvalidArgumentException('invalid_policy_action');
        }
        $state['revision'] = $next;

        return $state;
    }

    /**
     * @param array<array-key, mixed> $value
     * @param list<string>            $allowed */
    private static function keys(array $value, array $allowed): void
    {
        if (array_diff(array_keys($value), $allowed) || array_diff($allowed, array_keys($value))) {
            throw new \InvalidArgumentException('invalid_policy_fields');
        }
    }

    private static function text(mixed $value, int $max): string
    {
        if (!is_string($value) || strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            throw new \InvalidArgumentException('invalid_policy_text');
        }

        return trim($value);
    }

    /**
     * @return list<string> */
    private static function strings(mixed $value, int $max, int $length): array
    {
        if (!is_array($value) || !array_is_list($value) || count($value) > $max) {
            throw new \InvalidArgumentException('invalid_policy_list');
        }
        $result = [];
        foreach ($value as $item) {
            $result[] = self::text($item, $length);
        }

        return array_values(array_unique($result));
    }
}
