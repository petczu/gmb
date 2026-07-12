<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAgent;
use PHPUnit\Framework\TestCase;

/**
 * Instruction assembly: persona + knowledge + the workspace's shared reply
 * rules (style corrections that must reach EVERY agent).
 */
class AgentInstructionsTest extends TestCase
{
    public function test_shared_rules_are_appended_after_persona_and_knowledge(): void
    {
        $out = AiAgent::buildInstructions(
            'You are a friendly host.',
            'We have 5 rooms.',
            'Say "Raum", never "Room" inside a German sentence.',
        );

        $this->assertStringContainsString('You are a friendly host.', $out);
        $this->assertStringContainsString('We have 5 rooms.', $out);
        $this->assertStringContainsString('Workspace-wide reply rules', $out);
        $this->assertStringContainsString('never "Room" inside a German sentence', $out);
        $this->assertGreaterThan(
            strpos($out, 'We have 5 rooms.'),
            strpos($out, 'Workspace-wide reply rules'),
        );
    }

    public function test_empty_rules_and_knowledge_leave_the_persona_untouched(): void
    {
        $this->assertSame(
            'You are a friendly host.',
            AiAgent::buildInstructions('You are a friendly host.', null, ''),
        );
    }

    public function test_rules_apply_even_without_a_knowledge_base(): void
    {
        $out = AiAgent::buildInstructions('Persona.', null, 'No discounts.');

        $this->assertStringContainsString('Workspace-wide reply rules', $out);
        $this->assertStringContainsString('No discounts.', $out);
    }
}
