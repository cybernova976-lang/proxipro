<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuidedDemandPublicationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_form_exposes_five_short_steps_and_local_draft_controls(): void
    {
        $response = $this->get(route('demand.create'));

        $response->assertOk()
            ->assertSee('Étape 1 sur 5')
            ->assertSee('Étape 5 sur 5')
            ->assertSee('name="desired_date"', false)
            ->assertSee('name="time_window"', false)
            ->assertSee('name="price_type"', false)
            ->assertSee('name="publication_confirmed"', false)
            ->assertSee('Deux précisions pour mieux vous orienter')
            ->assertSee('const intakeSchemas', false)
            ->assertSee('service_details[${key}]', false)
            ->assertSee('prokejem-demand-draft-v2-', false)
            ->assertSee('const isGuestDemand = true', false)
            ->assertSee('markDemandFields', false)
            ->assertSee('id="publicationConfirmed"', false)
            ->assertSee('<span>Continuer</span>', false)
            ->assertSee(asset('js/form-validation.js').'?v=20260902', false)
            ->assertDontSee('onclick="window.location.href=', false)
            ->assertSee('Les photos ne sont jamais enregistrées dans le brouillon local');
    }

    public function test_guest_finishing_step_five_is_redirected_to_login_before_publication(): void
    {
        $response = $this->post(route('demand.store'), [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => today()->addDays(3)->toDateString(),
            'time_window' => 'morning',
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'service_details' => [
                'work_scope' => 'repair',
                'site_type' => 'house',
            ],
            'publication_confirmed' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_registration_form_uses_shared_required_field_highlighting(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(asset('css/form-validation.css').'?v=20260902', false)
            ->assertSee(asset('js/form-validation.js').'?v=20260902', false);
    }

    public function test_authenticated_client_can_publish_a_complete_guided_demand(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocode')->once()->andReturn(null);
        });

        $client = User::factory()->create();
        $desiredDate = today()->addDays(3)->toDateString();

        $response = $this->actingAs($client)->post(route('demand.store'), [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => $desiredDate,
            'time_window' => 'morning',
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'urgency' => 'urgent',
            'service_details' => [
                'work_scope' => 'repair',
                'site_type' => 'house',
            ],
            'publication_confirmed' => '1',
        ]);

        $ad = $client->ads()->sole();

        $response->assertRedirect(route('demand.matching', $ad));
        $this->assertSame('Bricolage & Travaux', $ad->main_category);
        $this->assertSame('service', $ad->publication_domain);
        $this->assertSame('negotiable', $ad->price_type);
        $this->assertNull($ad->price);
        $this->assertSame($desiredDate, $ad->ad_details['desired_date']);
        $this->assertSame('morning', $ad->ad_details['time_window']);
        $this->assertSame('repair', $ad->ad_details['service_details']['work_scope']);
        $this->assertSame('house', $ad->ad_details['service_details']['site_type']);
        $this->assertTrue($ad->is_urgent);
        $this->assertNotNull($ad->publication_terms_accepted_at);
        $this->assertTrue($ad->expires_at->isAfter(now()->addDays(29)));

        $this->get(route('ads.show', $ad))
            ->assertOk()
            ->assertSee('Réparation ou dépannage')
            ->assertSee('Maison')
            ->assertSee('Date souhaitée')
            ->assertSee('Moment de la journée');
    }

    public function test_fixed_budget_and_publication_confirmation_are_validated(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->from(route('demand.create'))
            ->post(route('demand.store'), [
                'main_category' => 'Bricolage & Travaux',
                'category' => 'Plombier',
                'country' => 'Mayotte',
                'city' => 'Mamoudzou',
                'desired_date' => today()->addDay()->toDateString(),
                'time_window' => 'flexible',
                'title' => 'Réparer une fuite sous mon évier',
                'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
                'price_type' => 'fixed',
                'service_details' => [
                    'work_scope' => 'repair',
                    'site_type' => 'house',
                ],
            ])
            ->assertRedirect(route('demand.create'))
            ->assertSessionHasErrors(['price', 'publication_confirmed']);

        $this->assertDatabaseCount('ads', 0);
    }

    public function test_guided_publication_rejects_a_recent_duplicate_even_with_spacing_and_case_changes(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocode')->once()->andReturn(null);
        });

        $client = User::factory()->create();
        $payload = [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => today()->addDays(3)->toDateString(),
            'time_window' => 'flexible',
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'service_details' => [
                'work_scope' => 'repair',
                'site_type' => 'house',
            ],
            'publication_confirmed' => '1',
        ];

        $this->actingAs($client)->post(route('demand.store'), $payload)->assertRedirect();

        $this->actingAs($client)
            ->from(route('demand.create'))
            ->post(route('demand.store'), array_merge($payload, [
                'title' => '  RÉPARER   UNE FUITE SOUS MON ÉVIER  ',
            ]))
            ->assertRedirect(route('demand.create'))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('ads', 1);
    }

    public function test_guided_publication_rejects_details_that_do_not_match_the_selected_trade_family(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->from(route('demand.create'))
            ->post(route('demand.store'), [
                'main_category' => 'Bricolage & Travaux',
                'category' => 'Plombier',
                'country' => 'Mayotte',
                'city' => 'Mamoudzou',
                'desired_date' => today()->addDay()->toDateString(),
                'time_window' => 'flexible',
                'title' => 'Réparer une fuite sous mon évier',
                'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
                'price_type' => 'negotiable',
                'service_details' => [
                    'work_scope' => 'passenger',
                    'site_type' => 'house',
                ],
                'publication_confirmed' => '1',
            ])
            ->assertRedirect(route('demand.create'))
            ->assertSessionHasErrors('service_details.work_scope');

        $this->assertDatabaseCount('ads', 0);
    }

    public function test_generic_ad_edit_keeps_the_guided_service_answers_when_the_trade_family_is_unchanged(): void
    {
        $client = User::factory()->create();
        $ad = $this->demand($client);
        $ad->update([
            'publication_domain' => 'service',
            'ad_details' => [
                'desired_date' => today()->addDays(3)->toDateString(),
                'time_window' => 'morning',
                'urgency' => 'normal',
                'service_details' => [
                    'work_scope' => 'repair',
                    'site_type' => 'house',
                ],
            ],
        ]);

        $this->actingAs($client)->put(route('ads.update', $ad), [
            'title' => 'Réparer une fuite sous mon évier rapidement',
            'description' => $ad->description,
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
        ])->assertRedirect(route('ads.show', $ad));

        $ad->refresh();
        $this->assertSame('repair', $ad->ad_details['service_details']['work_scope']);
        $this->assertSame('house', $ad->ad_details['service_details']['site_type']);
        $this->assertSame('morning', $ad->ad_details['time_window']);
    }

    public function test_matching_page_explains_the_no_provider_fallback_without_false_notification_claim(): void
    {
        $client = User::factory()->create();
        $ad = $this->demand($client);

        $response = $this->actingAs($client)->get(route('demand.matching', $ad));

        $response->assertOk()
            ->assertSee('Aucun prestataire compatible immédiatement')
            ->assertSee('Préciser ma demande')
            ->assertSee('Partager la demande')
            ->assertSee('Suivre depuis le feed')
            ->assertDontSee('Les professionnels correspondants ont été notifiés');
    }

    public function test_matching_page_displays_nearby_alternatives_when_no_exact_provider_exists(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create(['name' => 'Électricité Mahoraise']);
        UserService::create([
            'user_id' => $provider->id,
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Électricien',
            'is_active' => true,
        ]);

        $response = $this->actingAs($client)->get(route('demand.matching', $this->demand($client)));

        $response->assertOk()
            ->assertSee('Alternatives dans « Bricolage &amp; Travaux »', false)
            ->assertSee('Électricité Mahoraise')
            ->assertDontSee('Aucun prestataire compatible immédiatement');
    }

    private function demand(User $client): Ad
    {
        return Ad::create([
            'title' => 'Réparer une fuite sous mon évier',
            'description' => 'Une fuite légère apparaît sous l’évier lorsque le robinet est ouvert.',
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'publication_domain' => 'services',
            'ad_details' => [
                'desired_date' => today()->addDays(3)->toDateString(),
                'time_window' => 'morning',
            ],
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'price_type' => 'negotiable',
            'service_type' => 'demande',
            'status' => 'active',
            'user_id' => $client->id,
        ]);
    }
}
