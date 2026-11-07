<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;

final class BuilderAgentBoundaryTest extends TestCase
{
    public function testManualCoreHasNoAgentBootOrWorkerResource(): void
    {
        $root = dirname(__DIR__);
        $boot = file_get_contents($root.'/plugins/wpfasty-builder/src/Plugin.php');
        self::assertStringNotContainsString('Chat::register', $boot);
        self::assertStringNotContainsString('AgentPolicy::register', $boot);
        self::assertDirectoryDoesNotExist($root.'/plugins/wpfasty-builder/worker');
        self::assertFileDoesNotExist($root.'/plugins/wpfasty-builder/src/Chat.php');
        $app = file_get_contents($root.'/plugins/wpfasty-builder/editor/src/App.svelte');
        self::assertStringNotContainsString('ChatPanel', $app);
        self::assertStringNotContainsString('chatHistory', file_get_contents($root.'/plugins/wpfasty-builder/editor/src/api.ts'));
        foreach (['builder-wp-contract.php', 'builder-policy-contract.php'] as $fixture) {
            self::assertStringNotContainsString('/plugins/wpfasty-builder/wpfasty-builder.php', file_get_contents($root.'/tools/sdk/'.$fixture), 'Integration must exercise the active packaged core, not boot a second source copy.');
        }
    }

    public function testAgentUsesRealOptionalModuleLifecycleAndPreservesData(): void
    {
        $root = dirname(__DIR__).'/modules/builder-agent';
        self::assertFileExists($root.'/module.json');
        $manifest = json_decode(file_get_contents($root.'/module.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('builder-agent', $manifest['id']);
        self::assertSame('WPFastyBuilder\\AgentModule', $manifest['entry']);
        $entry = file_get_contents($root.'/src/AgentModule.php');
        self::assertStringContainsString('implements ModuleInterface', $entry);
        self::assertStringNotContainsString('delete_post_meta', $entry);
        self::assertStringNotContainsString('delete_option', $entry);
    }

    public function testWorkerHandshakeFailsClosedForOldAndMalformedResources(): void
    {
        foreach ([['protocol' => 'wpfasty-agent/1', 'capabilities' => ['local-search/1', 'policy/1']], null, [], ['active' => false, 'credentials' => 'managed'], ['protocol' => 'wpfasty-agent/2', 'capabilities' => ['local-search/1', 'policy/1', 'diagnostics/1']], ['protocol' => 'wpfasty-agent/1', 'capabilities' => ['local-search/1']]] as $status) {
            self::assertFalse(\WPFastyBuilder\WorkerProtocol::compatible($status));
        }
        self::assertTrue(\WPFastyBuilder\WorkerProtocol::compatible(['protocol' => 'wpfasty-agent/1', 'capabilities' => ['local-search/1', 'policy/1', 'diagnostics/1']]));
    }
}
