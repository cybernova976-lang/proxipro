<?php

namespace Tests\Feature\Feed;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSpaceNavigationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** Les quatorze pages du panneau latéral, dans l'ordre, groupe par groupe. */
    private const MENU_LABELS = ['Vue d’ensemble', 'Mes demandes', 'Mes commandes', 'Messages', 'Favoris',
        'Mes alertes', 'Offres d’emploi', 'Proposer mes services',
        'Vérification d’identité', 'Abonnement et factures', 'Paramètres', 'Conseils pratiques',
        'Classement des offres', 'Assistance'];

    public function test_client_can_open_every_section_with_one_current_tab_and_no_duplicate_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        // Depuis le 23/11/2026, l'accueil n'a ni onglets ni panneau : tout l'espace est rangé derrière
        // le bouton « Tableau de bord » de l'en-tête, présent sur toutes les pages du parcours.
        $home = $this->xpath($this->get(route('feed'))->assertOk()->getContent());
        $this->assertSame(0, $home->query('//nav[@aria-label="Mon espace particulier"]')->length);
        $this->assertSame(route('client.dashboard'), $home->query('//a[@data-header-dashboard]')->item(0)->getAttribute('href'));
        $this->assertSame(1, $home->query('//a[@data-header-dashboard-mobile]')->length);
        $this->assertSame(1, $home->query('//nav[@class="pk-tabbar"]')->length);
        $this->assertSame(0, $home->query('//main[contains(@class,"main-content-with-sidebar")]')->length);

        // Chaque page de l'espace s'affiche dans le cadre du tableau de bord : le même panneau
        // latéral, une seule entrée active. « Offres d'emploi » reste active sur la page d'un territoire.
        foreach (['client.dashboard' => 'overview', 'home' => 'requests', 'messages.index' => 'messages',
            'saved-ads.index' => 'saved', 'employment.territory' => 'employment'] as $route => $key) {
            $response = $this->get(route($route, $route === 'employment.territory' ? ['territoire' => 'departement-75'] : []))->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(14, $xpath->query('//aside[@data-space-menu]//nav[@aria-label="Mon espace particulier"]//a[@data-space-section]')->length, $route);
            $this->assertSame(1, $xpath->query('//button[@data-space-menu-open][@aria-controls="pkSpaceMenu"]')->length, $route);
            // Le bouton de l'en-tête est actif sur toutes les pages du tableau de bord.
            $this->assertSame(1, $xpath->query('//a[@data-header-dashboard][contains(@class,"active")]')->length, $route);
            $current = $xpath->query('//a[@data-space-section][@aria-current="page"]');
            $this->assertSame(1, $current->length, $route);
            $this->assertSame($key, $current->item(0)->getAttribute('data-space-section'));
            // La barre du bas est rendue partout ; client-space.css l'affiche sous 640 px
            // et la masque au-dessus.
            $this->assertSame(1, $xpath->query('//nav[@class="pk-tabbar"]')->length, $route);
            $this->assertSame(0, $xpath->query('//main[contains(@class,"main-content-with-sidebar")]')->length, $route);
        }
    }

    public function test_every_client_account_page_uses_the_same_tabs_instead_of_the_old_sidebar(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]));

        foreach (['service-orders.index' => 'orders', 'saved-searches.index' => 'alerts',
            'settings.index' => 'settings',
            'verification.index' => 'identity', 'billing.index' => 'billing', 'guides.index' => 'guides',
            'legal.platform-rules' => 'ranking', 'contact.index' => 'contact'] as $route => $key) {
            $xpath = $this->xpath($this->get(route($route))->assertOk()->getContent());
            $this->assertSame(14, $xpath->query('//nav[@aria-label="Mon espace particulier"]//a[@data-space-section]')->length, $route);
            $current = $xpath->query('//a[@data-space-section][@aria-current="page"]');
            $this->assertSame(1, $current->length, $route);
            $this->assertSame($key, $current->item(0)->getAttribute('data-space-section'), $route);
            $this->assertSame(0, $xpath->query('//aside[@id="sidebarNav"]')->length, "{$route} : ancienne barre latérale.");
            $this->assertSame(1, $xpath->query('//nav[@class="pk-tabbar"]')->length, $route);
        }
    }

    /**
     * Demande du fondateur du 23/11/2026 : sur téléphone, l'en-tête écrit le nom de chaque
     * icône au lieu de ne montrer que des pictogrammes, et le statut du membre sous sa photo.
     */
    public function test_phone_header_names_its_icons_and_shows_the_member_status(): void
    {
        $member = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $xpath = $this->xpath($this->actingAs($member)->get(route('feed'))->assertOk()->getContent());
        $text = fn (string $query) => trim($xpath->query($query)->item(0)?->textContent ?? '');

        // Le lien (ou le bouton) enveloppe l'icône et son nom : toucher le nom fait la même chose.
        $this->assertSame('Tableau de bord', $text('//a[@data-header-dashboard-mobile]/span[contains(@class,"mobile-header-label")]'));
        $this->assertSame('Devenir pro', $text('//a[@data-header-pro-mobile]/span[contains(@class,"mobile-header-label")]'));
        // La cloche reste une icône seule, pour gagner de la place : son nom est porté par le bouton.
        $this->assertSame(0, $xpath->query('//div[@id="notificationDropdown"]//span[contains(@class,"mobile-header-label")]')->length);
        $this->assertSame(1, $xpath->query('//div[@id="notificationDropdown"]/button[@data-bs-toggle="dropdown"][@aria-label="Notifications"]/span[contains(@class,"header-notif-btn")]')->length);
        $this->assertSame(1, $xpath->query('//a[@data-header-dashboard-mobile]/span[contains(@class,"header-mobile-action")]')->length);
        // Le statut est le même que sur ordinateur, écrit dans le bouton du compte.
        $this->assertSame('Particulier', $text('//button[contains(@class,"user-menu-btn")]/span[@data-header-status]'));
        $this->assertSame('Particulier', $text('//span[contains(@class,"user-type-badge")]'));
        // Les anciens libellés tronqués ont disparu.
        foreach (['Tableau', 'Pro', 'Notif', 'Notifications'] as $old) {
            $this->assertSame(0, $xpath->query('//span[contains(@class,"mobile-header-label")][normalize-space()="'.$old.'"]')->length, $old);
        }

        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $pro = $this->xpath($this->actingAs($provider)->get(route('feed', ['mode' => 'provider']))->assertOk()->getContent());
        $this->assertSame('Espace Pro', trim($pro->query('//a[@data-header-pro-mobile]/span[contains(@class,"mobile-header-label")]')->item(0)->textContent));
        $this->assertSame($provider->getAccountTypeLabel(), trim($pro->query('//span[@data-header-status]')->item(0)->textContent));
        $this->assertSame(0, $pro->query('//a[@data-header-dashboard-mobile]')->length);

        // Les noms s'affichent sous 992 px ; le statut sous la photo seulement sur téléphone.
        $layout = preg_replace('/\s+/', ' ', file_get_contents(resource_path('views/layouts/app.blade.php')));
        $this->assertMatchesRegularExpression('/@media \(max-width: 991\.98px\) \{.*?\.mobile-header-label \{ display: block !important;/', $layout);
        $this->assertStringContainsString('@media (min-width: 768px) { /* À partir de la tablette, le nom et le statut sont déjà écrits à côté de la photo. */ .mobile-header-status { display: none !important; } }', $layout);
    }

    /** Décision du fondateur du 23/11/2026 : ces deux pages ne font pas partie du tableau de bord. */
    public function test_notifications_and_profile_stay_outside_the_dashboard_frame(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]));

        foreach (['notifications.index', 'profile.show', 'profile.edit'] as $route) {
            $xpath = $this->xpath($this->get(route($route))->assertOk()->getContent());
            // Pleine page : ni panneau latéral, ni bouton « Menu », ni ancienne barre latérale.
            $this->assertSame(0, $xpath->query('//aside[@data-space-menu]')->length, $route);
            $this->assertSame(0, $xpath->query('//button[@data-space-menu-open]')->length, $route);
            $this->assertSame(0, $xpath->query('//aside[@id="sidebarNav"]')->length, $route);
            $this->assertSame(0, $xpath->query('//body[contains(@class,"has-space-menu")]')->length, $route);
            $this->assertSame(0, $xpath->query('//a[@data-header-dashboard][contains(@class,"active")]')->length, $route);
            // Le profil s'ouvre depuis le menu du compte, les notifications depuis la cloche.
            $this->assertSame(1, $xpath->query('//a[contains(@class,"dropdown-item")][@href="'.route('profile.show').'"]')->length, $route);
            $this->assertGreaterThanOrEqual(1, $xpath->query('//a[@href="'.route('notifications.index').'"]')->length, $route);
            $this->assertSame(1, $xpath->query('//nav[@class="pk-tabbar"]')->length, $route);
        }

        // Le panneau ne les propose plus, et la vue d'ensemble n'y renvoie plus.
        $board = $this->xpath($this->get(route('client.dashboard'))->assertOk()->getContent());
        foreach ([route('notifications.index'), route('profile.show')] as $url) {
            $this->assertSame(0, $board->query('//aside[@data-space-menu]//a[@href="'.$url.'"]')->length, $url);
            $this->assertSame(0, $board->query('//*[@data-client-dashboard]//a[@href="'.$url.'"]')->length, $url);
        }
    }

    public function test_each_page_has_one_name_and_the_publish_button_one_label_and_one_address(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]));
        $labels = self::MENU_LABELS;

        foreach (['feed', 'client.dashboard', 'home', 'messages.index', 'saved-ads.index', 'settings.index', 'ads.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            foreach (['Demander un service', 'Publier un besoin', 'Mon suivi', 'Suivi de mes demandes', 'Découvrir</span>'] as $old) {
                $this->assertStringNotContainsString($old, $html, "{$route} affiche encore « {$old} ».");
            }
            $xpath = $this->xpath($html);
            // Un seul nom pour le tableau de bord, dans l'en-tête de chaque page.
            $this->assertSame('Tableau de bord', trim($xpath->query('//a[@data-header-dashboard]/span')->item(0)->textContent), $route);
            if (! in_array($route, ['ads.index', 'feed'], true)) {
                $tabs = [];
                foreach ($xpath->query('//nav[@aria-label="Mon espace particulier"]//a[@data-space-section]/span[1]') as $span) {
                    $tabs[] = trim($span->textContent);
                }
                $this->assertSame($labels, $tabs, $route);
            }
            // Seule abréviation : « Demandes » dans la barre du bas, faute de place à 375 px.
            $this->assertSame(['Accueil', 'Demandes', 'Publier', 'Messages', 'Profil'], $this->tabbarLabels($xpath), $route);
            $this->assertSame([route('demand.create')], $this->publishTargets($xpath), $route);
            $this->assertStringContainsString('Publier une demande', $html, $route);
        }
    }

    public function test_provider_journey_publishes_an_offer_from_the_header_and_the_bottom_bar(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $html = $this->actingAs($provider)->get(route('feed', ['mode' => 'provider']))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame([route('ads.create', ['type' => 'offre'])], $this->publishTargets($xpath));
        $this->assertStringContainsString('Publier une offre', $html);
        $this->assertStringNotContainsString('Demander un service', $html);
        $this->assertSame(['Accueil', 'Annonces', 'Publier', 'Messages', 'Profil'], $this->tabbarLabels($xpath));
    }

    /** @return array<int, string> */
    private function tabbarLabels(DOMXPath $xpath): array
    {
        $labels = [];
        foreach ($xpath->query('//nav[@class="pk-tabbar"]/a/span') as $span) {
            $labels[] = trim($span->textContent);
        }

        return $labels;
    }

    /** Adresses distinctes des boutons de publication : en-tête (ordinateur et téléphone) et barre du bas. */
    private function publishTargets(DOMXPath $xpath): array
    {
        $targets = [];
        foreach ($xpath->query('//a[contains(@class,"header-nav-btn-primary")] | //a[contains(@class,"mobile-service-request")] | //nav[@class="pk-tabbar"]/a[contains(@class,"pk-tabbar__pub")]') as $link) {
            $targets[] = $link->getAttribute('href');
        }

        return array_values(array_unique($targets));
    }

    public function test_provider_only_sees_client_tabs_when_using_the_client_journey(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $this->actingAs($provider)->get(route('feed'))->assertOk()
            ->assertDontSee('data-client-space', false)->assertDontSee('data-header-dashboard', false);
        // Parcours particulier : le bouton « Tableau de bord » dans l'en-tête, le panneau latéral sur les pages de l'espace.
        $this->get(route('feed', ['mode' => 'client']))->assertOk()
            ->assertDontSee('data-client-space', false)->assertSee('data-header-dashboard', false);
        $this->get(route('saved-ads.index'))->assertOk()->assertSee('data-client-space', false);
        $this->get(route('client.dashboard'))->assertOk()
            ->assertSee('data-client-dashboard', false)->assertSee('data-client-space', false);
        // Parcours prestataire : son poste de pilotage est l'espace Pro.
        $this->get(route('feed', ['mode' => 'provider']))->assertOk()
            ->assertDontSee('data-client-space', false)->assertDontSee('data-header-dashboard', false);
        $this->get(route('saved-ads.index'))->assertOk()->assertDontSee('data-client-space', false);
        $this->get(route('contact.index'))->assertOk()->assertDontSee('data-client-space', false);
        $this->get(route('client.dashboard'))->assertRedirect(route('pro.dashboard'));
        $this->assertTrue($provider->fresh()->is_service_provider);
    }

    // Lot 1 (D1) : la page emploi devient publique, reste « private » sans « no-store »,
    // et /emploi mène un membre au territoire connu vers sa page.
    public function test_employment_section_is_public_and_keeps_external_applications_separate(): void
    {
        $this->get(route('employment.index'))->assertOk()->assertHeader('Cache-Control', 'private');
        $this->get(route('employment.territory', ['territoire' => 'departement-75']))->assertOk()
            ->assertHeader('Cache-Control', 'private');
        // Connecté, EnforceSessionInactivity impose « private, no-store » à toutes les
        // pages (règle de session, hors de la page emploi) : on vérifie seulement « private ».
        // Un membre sans lieu déclaré reçoit la liste des territoires ; un membre à Paris
        // est conduit à la page de son département.
        $this->actingAs(User::factory()->create())->get(route('employment.index'))->assertOk();
        $this->actingAs(User::factory()->create(['country' => 'France', 'city' => 'Paris', 'postal_code' => '75002']))
            ->get(route('employment.index'))
            ->assertRedirect(route('employment.territory', ['territoire' => 'departement-75']));
        $member = $this->get(route('employment.territory', ['territoire' => 'departement-75']))->assertOk();
        $this->assertStringContainsString('private', (string) $member->headers->get('Cache-Control'));
        $member->assertSee('data-space-section="employment"', false)
            ->assertSee(e(route('employment.territory', ['territoire' => 'departement-75'])), false)
            ->assertSee('Les offres d’emploi publiées par les membres de Prokejem')
            ->assertDontSee('France Travail')->assertDontSee('Site externe')
            ->assertDontSee('notre partenaire')->assertDontSee('Commander');
    }

    public function test_public_home_and_focused_publication_form_do_not_gain_client_tabs(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-client-space', false);
        // Les pages d'aide et d'emploi restent des pages publiques ordinaires pour un visiteur.
        foreach (['guides.index', 'contact.index', 'legal.platform-rules', 'employment.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('data-client-space', false);
        }
        $this->actingAs(User::factory()->create())->get(route('demand.create'))
            ->assertOk()->assertDontSee('data-client-space', false);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }
}
