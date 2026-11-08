<?php

namespace Tests\Feature\Feed;

use App\Models\DemandDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zone « Quel est votre problème ? » de l'accueil client (lot 2) et règle
 * « un seul bouton plein par écran » (CLAUDE.md §4).
 */
class FeedProblemPickerTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        return User::factory()->create(['is_service_provider' => false, 'user_type' => 'particulier']);
    }

    /** Boutons pleins (classe pk-btn exacte) dans le contenu du feed. */
    private function filledButtons(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $nodes = (new \DOMXPath($dom))->query('//*[@id="pkFeed"]//*[contains(concat(" ", normalize-space(@class), " "), " pk-btn ")]');

        return array_map(fn ($node) => trim(preg_replace('/\s+/', ' ', $node->textContent)), iterator_to_array($nodes));
    }

    public function test_client_sees_the_search_pill_and_situation_tiles(): void
    {
        $html = $this->actingAs($this->client())->get(route('feed'))->assertOk()
            ->assertSee('Quel est votre problème ?')
            ->assertSee('Ex. : fuite, ménage, clim')
            ->assertSee('Autre besoin')
            ->assertSee(route('demand.create', ['situation' => 'fuite-eau']), false)
            ->assertDontSee('Un besoin ? On vous aide à le décrire.')
            ->assertDontSee('Trouver un prestataire')
            ->getContent();

        $this->assertGreaterThanOrEqual(4, substr_count($html, 'data-pk-situation="'));
        $this->assertStringNotContainsString('id="pkIntentForm"', $html);
        // Le champ n'a pas d'attribut name : le texte tapé ne part jamais dans l'URL.
        $this->assertMatchesRegularExpression('/<input class="pk-search__field" id="pkSearchField"(?![^>]*\bname=)[^>]*>/', $html);
    }

    public function test_publish_state_has_exactly_one_filled_button(): void
    {
        $html = $this->actingAs($this->client())->get(route('feed'))->assertOk()->getContent();

        $this->assertSame(['Décrire mon besoin'], $this->filledButtons($html));
    }

    public function test_draft_state_offers_to_resume_with_the_only_filled_button(): void
    {
        $client = $this->client();
        DemandDraft::create(['user_id' => $client->id, 'payload' => ['category' => 'Bricolage & Travaux'], 'current_step' => 2, 'last_activity_at' => now()]);

        $html = $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertViewHas('pkPrimaryAction', fn ($action) => $action['state'] === 'draft')
            ->getContent();

        $this->assertSame(['Reprendre ma demande'], $this->filledButtons($html));
    }

    public function test_draft_can_be_abandoned_from_the_feed(): void
    {
        $client = $this->client();
        DemandDraft::create(['user_id' => $client->id, 'payload' => ['category' => 'Bricolage & Travaux'], 'current_step' => 2, 'last_activity_at' => now()]);

        $this->actingAs($client)->get(route('feed'))->assertOk()
            ->assertSee('Abandonner')
            ->assertSee('action="'.route('demand.draft.destroy').'"', false);

        $this->delete(route('demand.draft.destroy'))->assertRedirect(route('feed'));
        $this->assertDatabaseMissing('demand_drafts', ['user_id' => $client->id]);

        $this->get(route('feed'))->assertOk()
            ->assertSee('Votre brouillon de demande a été supprimé.')
            ->assertDontSee('Reprendre ma demande')
            ->assertViewHas('pkPrimaryAction', fn ($action) => $action['state'] === 'publish');
    }

    public function test_a_member_cannot_delete_someone_else_s_draft(): void
    {
        $owner = $this->client();
        DemandDraft::create(['user_id' => $owner->id, 'payload' => [], 'current_step' => 1, 'last_activity_at' => now()]);

        $this->actingAs($this->client())->delete(route('demand.draft.destroy'))->assertRedirect(route('feed'));
        $this->assertDatabaseHas('demand_drafts', ['user_id' => $owner->id]);
    }

    public function test_header_publish_button_is_secondary_on_the_client_feed_only(): void
    {
        $client = $this->client();
        $this->actingAs($client)->get(route('feed'))->assertOk()->assertSee('pk-header-secondary', false);
        $this->actingAs($client)->get(route('ads.create'))->assertDontSee('pk-header-secondary', false);
    }

    public function test_provider_mode_keeps_its_own_search(): void
    {
        $provider = User::factory()->create(['is_service_provider' => true, 'user_type' => 'professionnel']);
        $this->actingAs($provider)->get(route('feed', ['mode' => 'provider']))->assertOk()
            ->assertSee('id="pkIntentForm"', false)
            ->assertDontSee('id="pkProblemPicker"', false)
            ->assertDontSee('pk-header-secondary', false);
    }
}
