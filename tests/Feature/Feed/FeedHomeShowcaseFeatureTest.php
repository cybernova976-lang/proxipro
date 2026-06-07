<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHomeShowcaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_home_showcase_is_split_into_three_prioritized_scrollable_blocks(): void
    {
        $viewer = User::factory()->create();
        $particular = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $professional = User::factory()->create([
            'user_type' => 'professionnel',
            'plan' => 'FREE',
        ]);

        $oldPersonalRequests = collect(range(1, 6))->map(function ($index) use ($particular) {
            return Ad::create([
                'title' => 'Demande particulier ' . $index,
                'description' => 'Besoin d un professionnel pour une mission locale',
                'category' => 'Plomberie',
                'location' => 'Mamoudzou',
                'price' => 80 + $index,
                'service_type' => 'demande',
                'status' => 'active',
                'visibility' => 'public',
                'user_id' => $particular->id,
                'created_at' => now()->subDays(20 + $index),
                'updated_at' => now()->subDays(20 + $index),
            ]);
        });

        $urgentPersonalRequest = Ad::create([
            'title' => 'Demande urgente prioritaire',
            'description' => 'Cette demande urgente doit rester dans le premier bloc',
            'category' => 'Urgence',
            'location' => 'Mamoudzou',
            'price' => 120,
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $particular->id,
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);

        $oldProfessionalOffers = collect(range(1, 6))->map(function ($index) use ($professional) {
            return Ad::create([
                'title' => 'Offre professionnelle ' . $index,
                'description' => 'Prestation ou materiel propose par un professionnel',
                'category' => 'Bricolage',
                'location' => 'Koungou',
                'price' => 130 + $index,
                'service_type' => 'offre',
                'status' => 'active',
                'visibility' => 'public',
                'user_id' => $professional->id,
                'created_at' => now()->subDays(30 + $index),
                'updated_at' => now()->subDays(30 + $index),
            ]);
        });

        $boostedProfessionalOffer = Ad::create([
            'title' => 'Offre pro boostee prioritaire',
            'description' => 'Cette offre boostee doit rester dans le deuxieme bloc',
            'category' => 'Electricite',
            'location' => 'Kaweni',
            'price' => 180,
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $professional->id,
            'is_boosted' => true,
            'boost_type' => 'vip',
            'boost_end' => now()->addDay(),
            'created_at' => now()->subDays(95),
            'updated_at' => now()->subDays(95),
        ]);

        $boostedProfessionalRequest = Ad::create([
            'title' => 'Demande pro affichee cote particuliers',
            'description' => 'Un compte professionnel peut aussi chercher un service',
            'category' => 'Nettoyage',
            'location' => 'Mamoudzou',
            'price' => 90,
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $professional->id,
            'is_boosted' => true,
            'boost_end' => now()->addDays(7),
            'created_at' => now()->subDays(80),
            'updated_at' => now()->subDays(80),
        ]);

        $freeParticularOffer = Ad::create([
            'title' => 'Offre rubrique professionnelle',
            'description' => 'Une annonce service_type offre alimente le bloc des offres professionnelles',
            'category' => 'Divers',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $particular->id,
        ]);

        $reviewer = User::factory()->create();
        Ad::create([
            'title' => 'Annonce du reviewer',
            'description' => 'Annonce permettant de verifier les avis',
            'category' => 'Service',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $reviewer->id,
        ]);

        $topProfessionalProfile = User::factory()->create([
            'name' => 'Top Artisan',
            'user_type' => 'professionnel',
            'plan' => 'pro',
            'profession' => 'Plombier',
            'bio' => 'Intervient rapidement pour les depannages et petits travaux.',
        ]);

        Review::create([
            'reviewer_id' => $reviewer->id,
            'reviewed_user_id' => $topProfessionalProfile->id,
            'rating' => 5,
            'comment' => 'Tres bon service',
        ]);

        $paidProfiles = collect(range(1, 5))->map(function ($index) {
            return User::factory()->create([
                'name' => 'Pro visible ' . $index,
                'user_type' => 'professionnel',
                'plan' => 'pro',
                'profession' => 'Metier ' . $index,
                'bio' => 'Description courte du profil professionnel ' . $index,
            ]);
        });

        $freeProvider = User::factory()->create([
            'name' => 'Prestataire gratuit',
            'user_type' => 'particulier',
            'is_service_provider' => true,
            'plan' => 'FREE',
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response->assertOk();
        $content = $response->getContent();
        $showcaseHtml = $this->extractHomeShowcaseHtml($content);

        $this->assertStringContainsString('id="feedResultsSection" hidden', $content);
        $this->assertStringContainsString('id="infiniteScrollTrigger" style="display: none;"', $content);
        $this->assertStringContainsString('class="header-nav-center d-none d-lg-flex', $content);
        $this->assertStringNotContainsString('nav-messages-btn position-relative d-none d-md-flex', $content);
        $this->assertStringNotContainsString('title="Espace Pro"', $content);

        $this->assertStringContainsString('Pour vous', $showcaseHtml);
        $this->assertStringContainsString('Sélectionnés selon vos intérêts', $showcaseHtml);
        $this->assertStringContainsString('Offres des particuliers', $showcaseHtml);
        $this->assertStringContainsString('Offres de professionnels', $showcaseHtml);
        $this->assertStringContainsString('Profils de professionnels', $showcaseHtml);
        $this->assertStringContainsString('home-showcase-fixed-row', $showcaseHtml);
        $this->assertStringContainsString('home-showcase-row2-carousel', $showcaseHtml);
        $this->assertStringContainsString('home-showcase-professional-offers-carousel', $showcaseHtml);
        $this->assertSame(2, substr_count($showcaseHtml, 'data-mobile-layout="stacked-limited"'));
        $this->assertSame(3, substr_count($showcaseHtml, 'home-showcase-carousel is-scrollable'));
        $this->assertSame(6, substr_count($showcaseHtml, 'data-showcase-scroll-dir='));
        $this->assertStringContainsString(
            '.home-showcase-block--personal-requests .home-showcase-carousel-arrow',
            $content
        );
        $this->assertStringContainsString(
            '.home-showcase-professional-offers-carousel .home-showcase-ads-grid > :nth-child(n + 5)',
            $content
        );

        preg_match_all('/data-showcase-kind="personal-request"\s+data-showcase-ad-id="(\d+)"/', $showcaseHtml, $personalMatches);
        $personalRequestIds = array_map('intval', $personalMatches[1]);

        $this->assertCount(9, $personalRequestIds);
        $this->assertSame($urgentPersonalRequest->id, $personalRequestIds[0]);
        $this->assertContains($urgentPersonalRequest->id, $personalRequestIds);
        $this->assertContains($boostedProfessionalRequest->id, $personalRequestIds);
        $this->assertContains($oldPersonalRequests->last()->id, $personalRequestIds);
        $this->assertStringContainsString('120 €/h', $showcaseHtml);
        $this->assertStringContainsString($urgentPersonalRequest->created_at->format('d/m/Y'), $showcaseHtml);

        preg_match_all('/data-showcase-kind="professional-offer"\s+data-showcase-ad-id="(\d+)"/', $showcaseHtml, $professionalMatches);
        $professionalOfferIds = array_map('intval', $professionalMatches[1]);

        $this->assertCount(8, $professionalOfferIds);
        $this->assertSame($boostedProfessionalOffer->id, $professionalOfferIds[0]);
        $this->assertContains($boostedProfessionalOffer->id, $professionalOfferIds);
        $this->assertContains($oldProfessionalOffers->last()->id, $professionalOfferIds);
        $this->assertContains($freeParticularOffer->id, $professionalOfferIds);
        $this->assertStringContainsString('180 €', $showcaseHtml);

        preg_match_all('/data-showcase-kind="professional-profile"\s+data-showcase-pro-id="(\d+)"/', $showcaseHtml, $profileMatches);
        $profileIds = array_map('intval', $profileMatches[1]);

        $this->assertCount(8, $profileIds);
        $this->assertContains($topProfessionalProfile->id, $profileIds);
        $this->assertContains($professional->id, $profileIds);
        $this->assertNotEmpty(array_intersect($paidProfiles->pluck('id')->all(), $profileIds));
        $this->assertContains($freeProvider->id, $profileIds);

        $this->assertStringContainsString('Top Artisan', $showcaseHtml);
        $this->assertStringContainsString('PRO', $showcaseHtml);
        $this->assertStringContainsString('Plombier', $showcaseHtml);
        $this->assertStringContainsString('1 avis', $showcaseHtml);
        $this->assertStringContainsString('Top prestataire', $showcaseHtml);
        $this->assertStringContainsString('Intervient rapidement', $showcaseHtml);

        $this->assertStringNotContainsString('Nouveau', $showcaseHtml);
        $this->assertStringNotContainsString('Mis en avant', $showcaseHtml);
        $this->assertStringNotContainsString('annonces actives', $showcaseHtml);
    }

    private function extractHomeShowcaseHtml(string $content): string
    {
        $start = strpos($content, '<section class="home-showcase-section"');
        $this->assertNotFalse($start, 'Home showcase section was not rendered.');

        $end = strpos($content, '</section>', $start);
        $this->assertNotFalse($end, 'Home showcase section closing tag was not found.');

        return substr($content, $start, $end - $start);
    }
}
