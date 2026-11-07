<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\AgentPolicy;
use WPFastyBuilder\AgentPolicyDefinition as Policy;

/** NON-LIVE pure instruction document/ledger tests. No WordPress or model API. */
final class AgentPolicyDefinitionTest extends TestCase
{
    public function testDraftInactiveUntilActivationAndRollbackCreatesNewVersion(): void
    {
        $initial = Policy::initial();
        $definition = Policy::defaults();
        $definition['product'] = 'Approved NON-LIVE draft';
        $draft = Policy::transition($initial, 0, 'draft', $definition, 10, 'fixture');
        self::assertSame(1, $draft['activeVersion']);
        self::assertSame(2, Policy::entry($draft, 2)['version']);
        $active = Policy::transition($draft, 1, 'activate', 2, 10, 'fixture');
        self::assertSame(2, $active['activeVersion']);
        $rollback = Policy::transition($active, 2, 'rollback', 1, 10, 'fixture');
        self::assertSame(2, $rollback['activeVersion']);
        self::assertSame(Policy::defaults(), Policy::entry($rollback, 4)['definition']);
        $activated = Policy::transition($rollback, 3, 'activate', 4, 10, 'fixture');
        self::assertSame(4, $activated['activeVersion']);
        self::assertSame(Policy::initial(), $initial);
        $this->expectExceptionMessage('policy_rollback_requires_new_version');
        Policy::transition($activated, 4, 'activate', 1, 10, 'fixture');
    }

    public function testCasConflictAndBoundedHistoryKeepsActive(): void
    {
        $state = Policy::initial();
        for ($i = 0; $i < 30; ++$i) {
            $state = Policy::transition($state, $i, 'draft', Policy::defaults(), 10, 'fixture');
        }
        self::assertCount(20, $state['versions']);
        self::assertSame(Policy::defaults(), Policy::entry($state, 1)['definition']);
        $this->expectExceptionMessage('policy_revision_conflict');
        Policy::transition($state, 0, 'draft', Policy::defaults(), 11, 'stale');
    }

    public function testSkillContentChangeNeedsNewVersionAndCannotEnableArbitraryTools(): void
    {
        $definition = Policy::defaults();
        $definition['skills'][0]['instructions'] = 'changed';
        try {
            Policy::transition(Policy::initial(), 0, 'draft', $definition, 10, 'fixture');
            self::fail('Same skill version must not change content');
        } catch (\InvalidArgumentException $error) {
            self::assertSame('skill_version_must_change', $error->getMessage());
        }
        $definition['skills'][0]['version'] = 2;
        self::assertSame(1, Policy::transition(Policy::initial(), 0, 'draft', $definition, 10, 'fixture')['revision']);
        $definition['tools'][0] = 'shell';
        $this->expectExceptionMessage('invalid_policy_tools');
        Policy::normalize($definition);
    }

    public function testInvalidFieldsAndXssRemainInertData(): void
    {
        $definition = Policy::defaults();
        $definition['site']['brand'] = '<script>NONLIVE</script>';
        self::assertSame('<script>NONLIVE</script>', Policy::normalize($definition)['site']['brand']);
        $this->expectExceptionMessage('invalid_policy_fields');
        Policy::normalize($definition + ['cursor_key' => 'NONLIVE']);
    }

    public function testBundledDefaultAndEffectivePreviewMatchActualWorkerPayload(): void
    {
        $bundled = json_decode((string) file_get_contents(dirname(__DIR__).'/modules/builder-agent/worker/policy-default.json'), true);
        self::assertSame(Policy::defaults(), $bundled['definition']);
        $preview = AgentPolicy::preview($bundled);
        self::assertSame(json_decode((string) file_get_contents(__DIR__.'/fixtures/agent-policy/effective.v1.json'), true), $preview);
        self::assertCount(3, $preview['skillIndex']);
        self::assertArrayNotHasKey('instructions', $preview['skillIndex'][0]);
        self::assertSame(Policy::TOOLS, $preview['allowedTools']);
        self::assertSame(['brief' => 'page only'], Policy::normalize(['brief' => 'page only'], true));
    }
}
