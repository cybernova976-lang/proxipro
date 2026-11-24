<?php

namespace Tests\Feature\Ads;

use App\Models\User;
use App\Notifications\NewAdMatchingNotification;
use App\Services\AdPublicationSchema;
use App\Services\GeocodingService;
use App\Support\MarketplaceCategoryRegistry;
use App\Support\ServiceDemandIntakeSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Demande du fondateur du 24/11/2026 : à Mayotte, les maisons sont construites en béton
 * et la maçonnerie est très demandée (montée des matériaux sur le toit, coffrage,
 * ferraillage, coulage de la dalle, crépissage des murs).
 */
class MasonryCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const CATEGORY = 'Maçonnerie & Gros œuvre';

    public function test_the_category_follows_bricolage_and_lists_the_building_site_trades(): void
    {
        $services = config('categories.services');
        $names = array_keys($services);
        $this->assertSame(self::CATEGORY, $names[array_search('Bricolage & Travaux', $names, true) + 1]);

        $subcategories = $services[self::CATEGORY]['subcategories'];
        foreach (['Maçon', 'Bétonnage / Coulage de dalle', 'Montée de matériaux sur le toit', 'Coffrage',
            'Ferraillage', 'Montage de murs en parpaings', 'Crépissage / Enduit', 'Aide-maçon / Manœuvre'] as $trade) {
            $this->assertContains($trade, $subcategories);
        }

        // « Maçon » a quitté « Bricolage & Travaux » : chaque métier n'a qu'une catégorie,
        // et une demande de bétonnage peut s'élargir aux maçons déjà inscrits.
        $all = collect($services)->flatMap(fn (array $category) => $category['subcategories']);
        $this->assertSame($all->count(), $all->unique()->count(), 'Un métier figure dans deux catégories.');
        $this->assertNotContains('Maçon', $services['Bricolage & Travaux']['subcategories']);

        // Active par défaut, comme toute catégorie de services, et reconnue à la publication.
        $this->assertArrayHasKey(self::CATEGORY, MarketplaceCategoryRegistry::enabledServices());
        $this->assertSame(self::CATEGORY, app(AdPublicationSchema::class)->resolve(self::CATEGORY, 'Coffrage')['main_category']);
        $this->assertSame(self::CATEGORY, app(AdPublicationSchema::class)->resolve(null, 'Maçon')['main_category']);
    }

    public function test_both_publication_forms_offer_the_category_and_its_own_questions(): void
    {
        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $this->actingAs($provider)->get(route('ads.create'))->assertOk()
            ->assertSee("selectMainCategory('".e(self::CATEGORY)."', '🧱')", false)
            ->assertSee('"Coffrage","Ferraillage"', false);

        $client = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
        $response = $this->actingAs($client)
            ->get(route('demand.create', ['category' => self::CATEGORY, 'subcategory' => 'Bétonnage / Coulage de dalle']))
            ->assertOk()
            ->assertSee('Besoin d’aide pour décrire ?');

        $this->assertArrayHasKey(self::CATEGORY, $response->viewData('categoriesData'));
        $fields = $response->viewData('intakeSchemas')[self::CATEGORY]['fields'];
        $this->assertSame('De quel chantier s’agit-il ?', $fields['project_type']['label']);
        $this->assertSame('Les matériaux sont-ils déjà sur place ?', $fields['materials_on_site']['label']);
        $rules = ServiceDemandIntakeSchema::validationRules(self::CATEGORY);
        $this->assertContains('required', $rules['service_details.project_type']);
        $this->assertContains('in:yes,partial,no,unknown', $rules['service_details.materials_on_site']);
    }

    public function test_the_trade_picker_shown_to_new_providers_offers_the_category(): void
    {
        $provider = \Mockery::mock(User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]))->makePartial();
        $provider->shouldReceive('shouldShowCategorySelectionModal')->andReturn(true);
        $this->actingAs($provider);

        $html = view('partials.category-selection-modal')->render();
        $this->assertStringContainsString("'Maçonnerie & Gros œuvre': {", $html);
        $this->assertStringContainsString('"Coffrage","Ferraillage"', $html);
        $this->assertStringContainsString("'Carreleur','Serrurier'", $html);
    }

    public function test_a_concrete_pouring_demand_is_published_and_reaches_a_registered_provider(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, fn ($mock) => $mock->shouldReceive('geocode')->andReturn(null));

        $mason = User::factory()->create([
            'user_type' => 'professionnel', 'is_service_provider' => true, 'pro_notifications_realtime' => true,
            'service_subcategories' => ['Bétonnage / Coulage de dalle'],
        ]);
        $client = User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);

        $this->actingAs($client)->post(route('demand.store'), [
            'main_category' => self::CATEGORY,
            'category' => 'Bétonnage / Coulage de dalle',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => today()->addDays(5)->toDateString(),
            'time_window' => 'morning',
            'title' => 'Couler la dalle de ma maison',
            'description' => 'Le coffrage et le ferraillage sont prêts, il faut de la main-d’œuvre pour le bétonnage.',
            'price_type' => 'negotiable',
            'service_details' => ['project_type' => 'new_build', 'materials_on_site' => 'yes'],
            'publication_confirmed' => '1',
        ])->assertSessionHasNoErrors();

        $ad = $client->ads()->sole();
        $this->assertSame(self::CATEGORY, $ad->main_category);
        $this->assertSame('Bétonnage / Coulage de dalle', $ad->category);
        $this->assertSame(['project_type' => 'new_build', 'materials_on_site' => 'yes'], $ad->ad_details['service_details']);

        Notification::assertSentTo($mason, NewAdMatchingNotification::class);
    }
}
