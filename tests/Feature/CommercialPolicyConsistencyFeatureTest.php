<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialPolicyConsistencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_verification_has_one_price_and_card_only_payment(): void
    {
        $this->assertSame(5.0, IdentityVerification::getVerificationPrice('profile_verification'));
        $this->assertSame(5.0, VerificationRequest::getVerificationPrice('profile_verification'));
        $this->assertSame(500, config('admin.pricing.profile_verification.price_eur'));
        $this->assertNull(config('admin.pricing.profile_verification.price_points'));
        $this->assertFalse(IdentityVerification::supportsPointsPayment('profile_verification'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricing.index'))
            ->assertOk()
            ->assertSeeInOrder(['Vérification de profil', '5,00 €', 'Carte uniquement'])
            ->assertDontSee('Satisfait ou remboursé');

        $this->actingAs($user)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJsonPath('profile_verification_price', 5)
            ->assertJsonPath('profile_verification_points', null)
            ->assertJsonPath('profile_verification_payment_methods.0', 'card');
    }

    public function test_messaging_does_not_claim_unimplemented_end_to_end_encryption(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Accès réservé aux participants')
            ->assertDontSee('Chiffrement de bout en bout');
    }

    public function test_subscription_promotions_are_hidden_while_commercialization_is_disabled(): void
    {
        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'account_type' => 'professionnel',
            'is_service_provider' => true,
            'profession' => 'Plombier',
            'service_category' => 'Plombier',
            'pro_status' => 'active',
        ]);
        $requester = User::factory()->create();

        Ad::create([
            'user_id' => $requester->id,
            'title' => 'Recherche un plombier',
            'description' => 'Une demande active pour verifier la politique commerciale du feed.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        $feed = $this->withoutMiddleware()->actingAs($provider)->get(route('feed'));

        $feed
            ->assertOk()
            ->assertViewHas('pkMatchingCount', fn ($count): bool => $count > 0)
            ->assertViewHas('pkShowUpsell', false)
            ->assertDontSee('Découvrir Prokejem Pro');

        $this->withMiddleware();

        $this->actingAs($provider)
            ->get(route('pro.subscription'))
            ->assertOk()
            ->assertSee('Accès lancement')
            ->assertSee("L'espace prestataire est actuellement accessible sans abonnement.", false)
            ->assertDontSee('Activez votre abonnement pour recevoir des demandes clients');
    }

    public function test_professional_teaser_is_explicitly_marked_as_demo_data(): void
    {
        $html = file_get_contents(resource_path('views/pro/dashboard.blade.php'));

        $this->assertStringContainsString('Données de démonstration', $html);
        $this->assertStringContainsString('Jean Dupont', $html);
    }
}
