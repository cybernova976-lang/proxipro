<?php

namespace Tests\Feature\Feed;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHomeShowcaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_home_feed_prioritizes_requests_and_limits_the_main_list(): void
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

        User::factory()->create([
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

        $response = $this->withoutMiddleware()->actingAs($viewer)->get(route('feed'));

        $response
            ->assertOk()
            ->assertViewIs('feed.index')
            ->assertViewHas('pkRole', 'client')
            ->assertViewHas('pkFeedAds', function ($ads) use ($urgentRequest): bool {
                return $ads->count() === 6 && $ads->first()?->is($urgentRequest);
            })
            ->assertSee('id="pkFeedList"', false)
            ->assertSee('Demande urgente prioritaire')
            ->assertSee('Prestataires recommandés')
            ->assertSee('Artisan recommandé')
            ->assertSee('35 €/h')
            ->assertSee('Dépannage rapide')
            ->assertSee('pk-pro__visual', false)
            ->assertSee('pk-pro__identity', false)
            ->assertSee('pk-pro__verified', false)
            ->assertSee('Voir le profil')
            ->assertSee('pk-pro__actions', false)
            ->assertSee('Demander ce service')
            ->assertSee(route('messages.create.conversation'), false)
            ->assertSee('pk-replies--waiting', false)
            ->assertSee('En attente de réponses')
            ->assertDontSee('service en ligne')
            ->assertDontSee('Profil vérifié')
            ->assertSee('Voir toutes les annonces, la carte et les filtres')
            ->assertDontSee('home-showcase-section', false)
            ->assertDontSee('adsFeedMap', false);

        $css = file_get_contents(public_path('css/feed.css'));

        $this->assertStringContainsString('linear-gradient(135deg, #f8fbff', $css);
        $this->assertStringContainsString('.pk-pro__visual', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('height: 250px', $css);
        $this->assertStringContainsString('grid-template-rows: 195px 55px', $css);
        $this->assertStringContainsString('.pk-pro__actions', $css);
        $this->assertStringContainsString('.pk-pro__action--request', $css);
        $this->assertStringContainsString('border-radius: var(--pk-r-lg)', $css);
        $this->assertStringContainsString('border-radius: inherit; object-fit: cover', $css);
        $this->assertStringContainsString('.pk-pro__verified', $css);
        $this->assertStringContainsString('.pk-replies--waiting', $css);
        $this->assertStringContainsString('.pk-ad__media--3', $css);
        $this->assertStringContainsString('height: 138px', $css);
        $this->assertStringContainsString('grid-column: 1 / -1', $css);
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $css);
        $this->assertStringNotContainsString('linear-gradient(150deg, var(--pk-950), var(--pk-800))', $css);
    }

    public function test_feed_ad_card_displays_at_most_three_photos_in_the_compact_layout(): void
    {
        $requester = User::factory()->create([
            'name' => 'Auteur de la demande',
            'user_type' => 'particulier',
        ]);
        $ad = Ad::create([
            'title' => 'Besoin de plusieurs interventions',
            'description' => 'Une description utile qui accompagne la galerie sans augmenter inutilement la hauteur de la carte.',
            'category' => 'Bricolage',
            'location' => 'Mamoudzou',
            'service_type' => 'demande',
            'status' => 'active',
            'visibility' => 'public',
            'photos' => [
                'ads/feed-photo-1.webp',
                'ads/feed-photo-2.webp',
                'ads/feed-photo-3.webp',
                'ads/feed-photo-4.webp',
            ],
            'user_id' => $requester->id,
        ]);

        $html = view('feed.partials.ad-card', [
            'ad' => $ad,
            'pkRole' => 'provider',
            'pkSaved' => collect(),
        ])->render();

        $this->assertStringContainsString('pk-ad__media pk-ad__media--3', $html);
        $this->assertSame(3, substr_count($html, 'data-pk-feed-photo'));
        $this->assertStringContainsString(storage_url('ads/feed-photo-1.webp'), $html);
        $this->assertStringContainsString(storage_url('ads/feed-photo-2.webp'), $html);
        $this->assertStringContainsString(storage_url('ads/feed-photo-3.webp'), $html);
        $this->assertStringNotContainsString(storage_url('ads/feed-photo-4.webp'), $html);
        $this->assertStringContainsString('4 photos', $html);
        $this->assertStringContainsString('pk-ad__desc', $html);
        $this->assertStringContainsString('pk-ad__foot', $html);
        $this->assertStringContainsString('Proposer mes services', $html);
    }

    /**
     * La maquette montre le compteur des la premiere photo (« 1 photo »),
     * et deux apercus de largeur egale quand il y en a deux.
     */
    public function test_feed_ad_card_photo_layout_matches_the_mockup_for_one_and_two_photos(): void
    {
        $requester = User::factory()->create(['user_type' => 'particulier']);

        $makeAd = function (array $photos) use ($requester): Ad {
            return Ad::create([
                'title' => 'Cherche aide pour personne âgée',
                'description' => 'Je recherche une personne sérieuse pour accompagner une personne âgée.',
                'category' => 'Aide aux personnes âgées',
                'location' => 'Mamoudzou',
                'service_type' => 'demande',
                'status' => 'active',
                'visibility' => 'public',
                'photos' => $photos,
                'user_id' => $requester->id,
            ]);
        };

        $render = fn (Ad $ad): string => view('feed.partials.ad-card', [
            'ad' => $ad,
            'pkRole' => 'provider',
            'pkSaved' => collect(),
        ])->render();

        $one = $render($makeAd(['ads/feed-photo-1.webp']));
        $this->assertStringContainsString('pk-ad__media pk-ad__media--1', $one);
        $this->assertSame(1, substr_count($one, 'data-pk-feed-photo'));
        $this->assertStringContainsString('1 photo', $one);
        $this->assertStringNotContainsString('1 photos', $one);

        $two = $render($makeAd(['ads/feed-photo-1.webp', 'ads/feed-photo-2.webp']));
        $this->assertStringContainsString('pk-ad__media pk-ad__media--2', $two);
        $this->assertSame(2, substr_count($two, 'data-pk-feed-photo'));
        $this->assertStringContainsString('2 photos', $two);

        // Sans photo, aucune galerie et aucun compteur.
        $none = $render($makeAd([]));
        $this->assertStringNotContainsString('pk-ad__media', $none);
        $this->assertStringNotContainsString('pk-ad__photo-count', $none);
        $this->assertStringContainsString('pk-ad--nothumb', $none);
    }

    /**
     * Piege de cascade : .pk-ad--nothumb et .pk-ad ont la meme specificite, et
     * une media query n'en ajoute pas. Toute media query qui redefinit les
     * colonnes de .pk-ad ecrase donc le cas « sans photo » par simple ordre du
     * fichier, et l'annonce garde une colonne vide. Ce test verifie que chacune
     * rappelle .pk-ad--nothumb.
     */
    public function test_every_media_query_that_resizes_the_ad_grid_restores_the_photoless_card(): void
    {
        $css = file_get_contents(public_path('css/feed.css'));

        preg_match_all('/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', $css, $blocks);

        $checked = 0;

        foreach ($blocks[0] as $block) {
            if (! preg_match('/\.pk-ad\s*\{[^}]*grid-template-columns/', $block)) {
                continue;
            }

            $checked++;

            $this->assertMatchesRegularExpression(
                '/\.pk-ad--nothumb\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)/',
                $block,
                'Une media query redefinit les colonnes de .pk-ad sans rappeler .pk-ad--nothumb : '
                .'les annonces sans photo y garderont une colonne vide.'
            );
        }

        $this->assertGreaterThan(0, $checked, 'Aucune media query ne redimensionne la grille des cartes.');
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
        $feed   = file_get_contents(resource_path('views/feed/index.blade.php'));

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
            ->assertSee(route('ads.create', ['type' => 'service']), false);
    }

    /**
     * L'onglet actif suit la route courante : sur /ads c'est « Annonces », pas
     * « Accueil » comme le codait la version precedente, figee sur le feed.
     */
    public function test_the_mobile_tabbar_highlights_the_current_page(): void
    {
        $user = User::factory()->create([
            'user_type' => 'particulier',
            'is_service_provider' => false,
        ]);

        $html = $this->actingAs($user)->get(route('ads.index'))->assertOk()->getContent();

        preg_match('#<nav class="pk-tabbar".*?</nav>#s', $html, $nav);
        $this->assertNotEmpty($nav, "La barre est absente de la page /ads.");

        preg_match_all('/<a\s[^>]*class="([^"]*)"[^>]*>.*?<span>([^<]+)<\/span>/s', $nav[0], $links, PREG_SET_ORDER);

        $actifs = [];
        foreach ($links as $link) {
            if (str_contains($link[1], 'is-active')) {
                $actifs[] = trim($link[2]);
            }
        }

        $this->assertSame(['Annonces'], $actifs, "Un seul onglet doit etre actif, et c'est « Annonces » sur /ads.");
    }

    public function test_the_mobile_tabbar_stays_hidden_for_guests(): void
    {
        // /ads utilise le gabarit commun et reste ouvert aux visiteurs : c'est
        // donc bien la garde @auth du partial qui est eprouvee ici, et non le
        // simple fait que la page emploie un autre gabarit.
        $this->get(route('ads.index'))
            ->assertOk()
            ->assertDontSee('pk-tabbar', false);
    }
}
