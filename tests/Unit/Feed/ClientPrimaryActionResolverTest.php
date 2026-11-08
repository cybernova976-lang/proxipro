<?php

namespace Tests\Unit\Feed;

use App\Services\Feed\ClientPrimaryActionResolver;
use Tests\TestCase;

/** Priorité du bouton dominant : action attendue > brouillon > « Décrire mon besoin ». */
class ClientPrimaryActionResolverTest extends TestCase
{
    private function activity(int $priority): array
    {
        return ['primary' => ['priority' => $priority, 'short_action' => 'Comparer', 'action_url' => '/ads/1/propositions/comparer']];
    }

    public function test_awaited_action_wins_over_a_draft(): void
    {
        $action = (new ClientPrimaryActionResolver())->resolve($this->activity(1), true);

        $this->assertSame(['state' => 'action', 'label' => 'Comparer', 'url' => '/ads/1/propositions/comparer'], $action);
    }

    public function test_draft_comes_before_publishing(): void
    {
        $this->assertSame('draft', (new ClientPrimaryActionResolver())->resolve(null, true)['state']);
    }

    public function test_waiting_request_keeps_publish_dominant(): void
    {
        $action = (new ClientPrimaryActionResolver())->resolve($this->activity(4), false);

        $this->assertSame('publish', $action['state']);
        $this->assertSame('Décrire mon besoin', $action['label']);
    }
}
