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
            ->get(route('public.provider-offers'))
            ->assertOk()
            ->assertSee('5,00 €')
            ->assertSee('Le contrôle d’identité se règle par carte')
            ->assertDontSee('Satisfait ou remboursé');

        $this->actingAs($user)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJsonPath('profile_verification_price', 5)
            ->assertJsonMissingPath('profile_verification_points')
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
            ->assertSee('Abonnement Premium momentanément indisponible')
            ->assertSee('Aucun paiement n’est demandé et aucun abonnement n’est activé automatiquement.', false)
            // Décision du fondateur (11/11/2026) : aucune gratuité « de lancement » n'est annoncée.
            ->assertDontSee('Accès lancement')
            ->assertDontSee('gratuit pendant')
            ->assertDontSee('Activez votre abonnement pour recevoir des demandes clients');
    }

    /**
     * Décision du fondateur (11/11/2026) : tout ce qui est payant le reste dès le
     * premier jour. Aucun écran ne doit annoncer une gratuité « de lancement »,
     * même dans l'état où les souscriptions sont coupées.
     */
    public function test_no_screen_announces_a_free_launch_period(): void
    {
        $forbidden = ['gratuit pendant', 'phase de lancement', 'accès lancement', 'pendant le lancement',
            'prestataire au lancement', 'offre de lancement', 'pendant cette phase'];
        $files = array_merge(
            iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS))),
            [new \SplFileInfo(app_path('Support/PlatformFeatures.php'))]
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = mb_strtolower(file_get_contents($file->getPathname()));
            foreach ($forbidden as $wording) {
                $this->assertStringNotContainsString($wording, $content, $file->getPathname().' annonce « '.$wording.' ».');
            }
        }
    }

    /** Aucun chiffre inventé : l'espace Pro d'un non-prestataire ne montre plus de faux devis. */
    public function test_professional_teaser_shows_no_invented_figures(): void
    {
        $views = file_get_contents(resource_path('views/pro/dashboard.blade.php'))
            .file_get_contents(resource_path('views/pro/dashboard/join.blade.php'));

        foreach (['Jean Dupont', 'Marie Martin', 'Société ABC', 'Données de démonstration', 'DEV-001'] as $fake) {
            $this->assertStringNotContainsString($fake, $views);
        }
    }
}
