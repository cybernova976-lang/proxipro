<?php

namespace Tests\Feature\Admin;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Models\User;
use App\Support\StatusLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les états sont enregistrés en anglais ; l'administration les affiche en français.
 * Constaté en production : « Active », « Cancelled », « Pending » sur la page Abonnements.
 */
class AdminFrenchStatusLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_labels_are_french_and_unknown_values_stay_readable(): void
    {
        $this->assertSame('Annulé', StatusLabels::providerSubscription('cancelled'));
        $this->assertSame('Suspendu ou en attente', StatusLabels::providerSubscription('pending'));
        $this->assertSame('Paiement en retard', StatusLabels::stripeSubscription('past_due'));
        $this->assertSame('—', StatusLabels::stripeSubscription(null));
        $this->assertSame('Expirée', StatusLabels::ad('expired'));
        $this->assertSame('Nouvel etat', StatusLabels::ad('nouvel_etat'));
    }

    public function test_subscriptions_page_shows_french_states_and_separates_the_old_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = User::factory()->create();
        $provider->forceFill(['is_service_provider' => true])->save();
        ProSubscription::create(['user_id' => $provider->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'cancelled',
            'starts_at' => now()->subMonth(), 'ends_at' => now(), 'stripe_subscription_id' => 'sub_test', 'stripe_status' => 'canceled']);

        $html = $this->actingAs($admin)->get(route('admin.subscriptions'))->assertOk()->getContent();

        $this->assertStringContainsString('>Annulé</span>', $html);
        $this->assertStringContainsString('Annulé chez Stripe', $html);
        $this->assertStringNotContainsString('>Cancelled</span>', $html);
        $this->assertStringContainsString('ces compteurs ne comptent pas les abonnements Premium', $html);
        $this->assertStringNotContainsString('>Total Premium<', $html);
    }

    public function test_admin_ads_list_shows_french_ad_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Ad::create(['user_id' => User::factory()->create()->id, 'title' => 'Annonce expirée', 'description' => 'Texte.',
            'category' => 'Plombier', 'location' => 'Mamoudzou', 'service_type' => 'offre', 'status' => 'expired', 'visibility' => 'public']);

        $this->actingAs($admin)->get(route('admin.ads'))->assertOk()
            ->assertSee('Expirée')
            ->assertDontSee('>Expired<', false);
    }
}
