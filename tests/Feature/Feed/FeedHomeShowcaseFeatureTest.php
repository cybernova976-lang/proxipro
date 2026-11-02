<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHomeShowcaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_home_prioritizes_providers_and_service_offers_instead_of_other_clients_requests(): void
    {
        $viewer = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);
        $requester = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        collect(range(1, 7))->each(function (int $index) use ($requester): void {
            Ad::create([
                'title' => 'Demande récente '.$index,
                'description' => 'Une demande réelle présentée dans le nouveau feed unifié.',
                'category' => 'Plomberie',
                'location' => 'Mamoudzou',
                'service_type' => 'demande',
                'status' => 'active',
                'visibility' => 'public',
                'user_id' => $requester->id,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        });

        $urgentRequest = Ad::create([
            'title' => 'Demande urgente prioritaire',
            'description' => 'Cette demande urgente doit apparaître en tête du nouveau feed.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $requester->id,
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $provider = User::factory()->create([
            'name' => 'Artisan recommandé',
            'user_type' => 'professionnel',
            'is_service_provider' => true,
            'plan' => 'pro',
            'profession' => 'Plombier',
            'city' => 'Mamoudzou',
            'is_verified' => true,
            'hourly_rate' => 35,
            'show_hourly_rate' => true,
            'specialties' => ['Dépannage rapide', 'Recherche de fuite'],
            'bio' => 'Intervient pour les dépannages et travaux de plomberie.',
        ]);

        $serviceOffer = Ad::create([
            'title' => 'Dépannage plomberie disponible',
            'description' => 'Intervention de plomberie proposée aux clients de la zone.',
            'category' => 'Plomberie',
            'location' => 'Mamoudzou',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $provider->id,
        ]);

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response
            ->assertOk()
            ->assertViewIs('feed.index')
            ->assertViewHas('pkRole', 'client')
            ->assertViewHas('pkFeedAds', function ($ads) use ($serviceOffer): bool {
                return $ads->count() === 1 && $ads->first()?->is($serviceOffer);
            })
            ->assertSee('id="pkFeedList"', false)
            ->assertSee('Services à découvrir')
            ->assertSee('Dépannage plomberie disponible')
            ->assertDontSee('Demande urgente prioritaire')
            ->assertSee('Prestataires à découvrir')
            ->assertSee('Artisan recommandé')
            ->assertSee('35 €/h')
            ->assertSee('Dépannage rapide')
            ->assertSee('pk-pro__visual', false)
            ->assertSee('pk-pro__identity', false)
            ->assertSee('pk-pro__verified', false)
            ->assertSee('Voir le profil')
            ->assertSee('pk-pro__actions', false)
            ->assertSee('Trouver un prestataire')
            ->assertSee('Publier une demande')
            ->assertSee(route('profile.public', $provider->id), false)
            ->assertDontSee('Bonjour, je souhaite demander vos services')
            ->assertDontSee('service en ligne')
            ->assertDontSee('Profil vérifié')
            ->assertSee('Voir tous les services, la carte et les filtres')
            ->assertDontSee('home-showcase-section', false)
            ->assertDontSee('adsFeedMap', false);

        $html = $response->getContent();
        $this->assertGreaterThan(strpos($html, 'id="pkFeedList"'), strpos($html, 'id="pkProviderList"'),
            'Les profils complètent les annonces, sans repousser le flux principal.');
        $this->assertSame(1, substr_count($html, 'id="pkIntentForm"'));
        $this->assertStringContainsString('Raccourcis personnels', $html);

    }

    public function test_provider_home_is_a_demand_inbox_without_competitor_profiles(): void
    {
        $provider = User::factory()->create([
            'name' => 'Prestataire plomberie',
            'user_type' => 'professionnel',
            'account_type' => 'professionnel',
            'is_service_provider' => true,
            'profession' => 'Plombier',
            'service_category' => 'Plombier',
            'pro_status' => 'active',
        ]);
        $requester = User::factory()->create(['user_type' => 'particulier']);
        $request = Ad::create([
            'title' => 'Fuite sous évier à réparer',
            'description' => 'Une demande réelle à laquelle le prestataire peut répondre.',
            'category' => 'Plombier',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'user_id' => $requester->id,
        ]);

        $response = $this->withoutMiddleware()->actingAs($provider)->get(route('feed'));

        $response->assertOk()->assertViewHas('pkRole', 'provider');
        $this->assertTrue(
            $response->viewData('pkFeedAds')->contains(fn ($ad) => $ad->is($request)),
            'La demande compatible doit etre presente dans le feed prestataire.'
        );

        $html = $response->getContent();
        foreach ([
            'Métier ou service…',
            'Voir les demandes',
            'Demandes qui correspondent à votre métier',
            'Fuite sous évier à réparer',
            'Proposer mes services',
            'Décrochez vos missions',
        ] as $expected) {
            $this->assertTrue(str_contains($html, $expected), 'Texte prestataire manquant : '.$expected);
        }
        $this->assertFalse(str_contains($html, 'Prestataires recommandés'), 'Le prestataire ne doit pas voir les profils concurrents.');

        $javascript = file_get_contents(public_path('js/feed.js'));
        $this->assertStringContainsString('config.requestsUrl', $javascript);
        $this->assertStringContainsString("params.push('search='", $javascript);
    }

    public function test_card_keeps_one_main_photo_and_the_true_gallery_count(): void
    {
        $author = User::factory()->create(['name' => 'Abdou OUSSENI Chebani Razamakotoravolani']);
        $viewer = User::factory()->create();
        $this->actingAs($viewer);
        foreach ([0, 1, 2, 4] as $count) {
            $photos = $count ? array_map(fn ($i) => 'ads/feed-photo-'.$i.'.webp', range(1, $count)) : [];
            $ad = Ad::create(['user_id' => $author->id, 'title' => 'Installation de sanitaires',
                'description' => 'Pose et remplacement des équipements.', 'category' => 'Plombier',
                'location' => 'Mamoudzou', 'service_type' => 'offre', 'status' => 'active',
                'visibility' => 'public', 'photos' => $photos]);
            $html = view('feed.partials.ad-card', ['ad' => $ad, 'pkRole' => 'client',
                'pkSaved' => collect([$ad->id])])->render();
            $this->assertSame($count ? 1 : 0, substr_count($html, 'data-pk-feed-photo'));
            if ($count > 0) $this->assertStringContainsString(storage_url($photos[0]), $html);
            if ($count > 1) $this->assertStringContainsString($count.' photos', $html);
            $this->assertStringNotContainsString(storage_url('ads/feed-photo-2.webp'), $html);
            $this->assertStringContainsString('Photo non fournie', $html);
            $this->assertStringContainsString('Service proposé', $html);
            $this->assertStringContainsString('aria-pressed="true"', $html);
            $this->assertStringContainsString('href="'.route('ads.show', $ad).'"', $html);
            $this->assertStringContainsString('href="'.route('profile.public', $author->id).'"', $html);
            $this->assertStringContainsString('aria-label="Voir le profil de '.$author->name.'"', $html);
            $this->assertGreaterThan(strpos($html, '<h3>'), strpos($html, 'pk-ad__authorbar'));
            $this->assertGreaterThan(strpos($html, 'pk-ad__authorbar'), strpos($html, 'pk-ad__foot'));
        }
    }

    public function test_request_card_keeps_its_type_and_response_action_in_both_modes(): void
    {
        $author = User::factory()->create();
        $ad = Ad::create(['user_id' => $author->id, 'title' => 'Fuite sous évier', 'description' => 'Un raccord à réparer.',
            'category' => 'Plombier', 'location' => 'Mamoudzou', 'service_type' => 'demande',
            'status' => 'active', 'visibility' => 'public']);
        foreach (['client' => 'Voir la demande', 'provider' => 'Proposer mes services'] as $role => $action) {
            $html = view('feed.partials.ad-card', ['ad' => $ad, 'pkRole' => $role])->render();
            $this->assertStringContainsString('Demande de service', $html);
            $this->assertStringContainsString($action, $html);
            $this->assertStringNotContainsString('Service proposé', $html);
        }
    }
    /**
     * La barre d'onglets est rendue hors de .pk-feed, et desormais sur toutes
     * les pages. Elle doit donc porter ses propres variables : un var()
     * introuvable rend la declaration invalide, et son fond blanc retombe sur
     * transparent — on voyait les cartes defiler au travers.
     */
    public function test_the_mobile_tabbar_carries_its_own_palette(): void
    {
        // Les commentaires sont retires d'abord : celui qui documente ce piege
        // cite .pk-tabbar, et le laisser en place suffirait a faire passer le
        // test alors que le selecteur, lui, aurait perdu la barre.
        $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents(public_path('css/tabbar.css')));

        preg_match('/([^{};]*)\{[^{}]*--pk-surface:/', $css, $m);

        $this->assertNotEmpty($m, 'Le bloc declarant --pk-surface est introuvable dans tabbar.css.');
        $this->assertStringContainsString(
            '.pk-tabbar',
            $m[1],
            'Les variables ne sont pas portees par .pk-tabbar : son fond redeviendra transparent.'
        );

        foreach (['--pk-rule', '--pk-ink-3', '--pk-600'] as $variable) {
            $this->assertStringContainsString(
                $variable.':',
                $css,
                "La barre utilise {$variable} sans la declarer : filet, libelles ou bouton « Publier » perdront leur couleur."
            );
        }
    }

    /**
     * La barre doit etre rendue par le gabarit commun, et une seule fois : le
     * feed l'incluait avant, l'y laisser en afficherait deux.
     */
    public function test_the_mobile_tabbar_is_rendered_once_by_the_shared_layout(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $feed = file_get_contents(resource_path('views/feed/index.blade.php'));
        $tabbar = file_get_contents(resource_path('views/feed/partials/mobile-tabbar.blade.php'));

        $this->assertStringContainsString(
            "@include('feed.partials.mobile-tabbar')",
            $layout,
            "Le gabarit commun n'inclut plus la barre : elle disparaitrait de toutes les pages."
        );

        $this->assertStringContainsString(
            'css/tabbar.css',
            $layout,
            "Le gabarit commun ne charge plus tabbar.css : la barre s'afficherait sans style."
        );

        $this->assertFileExists(public_path('js/tabbar.js'));
        $this->assertStringContainsString(
            'js/tabbar.js',
            $layout.$tabbar,
            'Ni le gabarit commun ni le composant ne charge le retour visuel immediat de la barre mobile.'
        );

        $this->assertStringNotContainsString(
            "@include('feed.partials.mobile-tabbar')",
            $feed,
            'Le feed inclut de nouveau la barre : elle s y afficherait en double.'
        );

        // La preuve qui compte : la page reellement rendue, pas le gabarit.
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $html = $this->actingAs($user)->get(route('feed'))->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, '<nav class="pk-tabbar"'),
            'Le feed doit afficher exactement une barre d onglets.'
        );
        $this->assertSame(5, substr_count($html, 'data-pk-tab'));
        $this->assertStringContainsString(asset('js/tabbar.js'), $html);
    }

    /**
     * Un appui doit produire un retour visuel avant meme la navigation. Un
     * second appui sur l'onglet deja ouvert ne doit pas recharger toute la
     * page (particulierement couteuse pour le feed).
     */
    public function test_the_mobile_tabbar_gives_immediate_feedback_and_avoids_same_page_reload(): void
    {
        $css = file_get_contents(public_path('css/tabbar.css'));
        $js = file_get_contents(public_path('js/tabbar.js'));

        $this->assertStringContainsString('touch-action: manipulation', $css);
        $this->assertMatchesRegularExpression('/\.pk-tabbar\s+a\.is-pending\b/', $css);

        $this->assertStringContainsString('data-pk-tab', $js);
        $this->assertStringContainsString('is-pending', $js);
        $this->assertStringContainsString('window.location.href', $js);
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*isExactCurrentUrl\(link\)\s*\)\s*\{[^}]*event\.preventDefault\(\)/s',
            $js,
            "Le script doit empecher le rechargement lorsque l'onglet vise correspond exactement a l'URL ouverte."
        );
    }

    /**
     * Un visiteur non connecte ne doit pas voir la barre : quatre de ses cinq
     * liens menent a des pages qui exigent une session.
     */
    /**
     * Le fond de la demande : la barre existait, mais seulement sur /feed.
     * Elle doit maintenant accompagner l'utilisateur partout, avec le bon
     * lien de publication selon qu'il est particulier ou professionnel.
     */
    public function test_the_mobile_tabbar_appears_outside_the_feed_with_the_right_publish_link(): void
    {
        $particulier = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $this->actingAs($particulier)
            ->get(route('ads.index'))
            ->assertOk()
            ->assertSee('pk-tabbar', false)
            ->assertSee('pk-tabbar-spacer', false)
            ->assertSee(route('demand.create'), false);

        $professionnel = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $this->actingAs($professionnel)
            ->get(route('ads.index'))
            ->assertOk()
            ->assertSee('pk-tabbar', false)
            ->assertSee(route('ads.create', ['type' => 'offre']), false);
    }

    /** L'onglet de parcours suit le rôle et la route courante. */
    public function test_the_mobile_tabbar_highlights_the_current_page(): void
    {
        $client = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $this->actingAs($client)->get(route('demands.tracking'))->assertRedirect(route('home'));
        $clientHtml = $this->get(route('home'))->assertOk()->getContent();

        preg_match('#<nav class="pk-tabbar".*?</nav>#s', $clientHtml, $clientNav);
        $this->assertNotEmpty($clientNav, 'La barre est absente de la page de suivi.');

        preg_match_all('/<a\s[^>]*class="([^"]*)"[^>]*>.*?<span>([^<]+)<\/span>/s', $clientNav[0], $clientLinks, PREG_SET_ORDER);

        $clientActifs = [];
        foreach ($clientLinks as $link) {
            if (str_contains($link[1], 'is-active')) {
                $clientActifs[] = trim($link[2]);
            }
        }

        $this->assertSame(['Suivi'], $clientActifs);

        $provider = User::factory()->create([
            'user_type' => 'professionnel',
            'is_service_provider' => true,
        ]);

        $providerHtml = $this->actingAs($provider)->get(route('ads.index'))->assertOk()->getContent();
        preg_match('#<nav class="pk-tabbar".*?</nav>#s', $providerHtml, $providerNav);
        preg_match_all('/<a\s[^>]*class="([^"]*)"[^>]*>.*?<span>([^<]+)<\/span>/s', $providerNav[0], $providerLinks, PREG_SET_ORDER);

        $providerActifs = [];
        foreach ($providerLinks as $link) {
            if (str_contains($link[1], 'is-active')) {
                $providerActifs[] = trim($link[2]);
            }
        }

        $this->assertSame(['Annonces'], $providerActifs);
    }

    public function test_the_mobile_tabbar_stays_hidden_for_guests(): void
    {
        // /ads utilise le gabarit commun et reste ouvert aux visiteurs : c'est
        // donc bien la garde @auth du partial qui est eprouvee ici, et non le
        // simple fait que la page emploie un autre gabarit.
        $this->get(route('ads.index'))
            ->assertOk()
            ->assertDontSee('<nav class="pk-tabbar"', false);
    }
}
