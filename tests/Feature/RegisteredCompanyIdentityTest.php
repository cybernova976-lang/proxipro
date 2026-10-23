<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\PlatformFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredCompanyIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_identity_is_displayed_consistently_on_legal_pages(): void
    {
        foreach (['legal.mentions', 'legal.terms', 'legal.privacy', 'legal.pro-terms'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('PROKEJEM')->assertSee('131 775 942')
                ->assertSee('SASU')->assertSee('1 EUR')->assertSee('Passamainti');
        }

        $this->get(route('legal.mentions'))->assertOk()
            ->assertSee('131 775 942 00017')->assertSee('23/10/2026')
            ->assertSee('6312Z')->assertSee('Hardali SAID')
            ->assertSee('Railway Corporation')->assertSee('+1 415 707 7675')
            ->assertSee('contact@prokejem.fr')->assertSee('+262 639 71 94 22')
            ->assertDontSee('Document à finaliser avant la mise en production');
        $this->get(route('contact.index'))->assertOk()
            ->assertSee('contact@prokejem.fr')->assertSee('+262 639 71 94 22');
        $this->get(route('legal.terms'))->assertDontSee('l’identité et le pays d’établissement de l’éditeur seront finalisés');
        $this->assertFalse(PlatformFeatures::proSubscriptionsRequested());
    }

    public function test_empty_legacy_settings_fall_back_to_registered_identity_without_hiding_overrides(): void
    {
        Setting::set('legal_entity_name', '', 'legal');
        Setting::set('legal_registration_number', '   ', 'legal');
        $this->assertSame('PROKEJEM', PlatformFeatures::legalValue('legal_entity_name', 'entity_name'));
        $this->assertSame('131 775 942', PlatformFeatures::legalValue('legal_registration_number', 'registration_number'));
        Setting::set('legal_entity_name', 'Nouvelle dénomination', 'legal');
        $this->assertSame('Nouvelle dénomination', PlatformFeatures::legalValue('legal_entity_name', 'entity_name'));
        config(['legal.vat_number' => null]);
        $this->assertNull(PlatformFeatures::legalValue('legal_vat_number', 'vat_number'));
    }

    public function test_admin_can_update_new_registration_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fields = [
            'legal_siret' => '131 775 942 00017',
            'legal_share_capital' => '2 EUR',
            'legal_registration_date' => '23/10/2026',
            'legal_ape_code' => '6312Z — Portails Internet',
        ];
        $this->actingAs($admin)->post(route('admin.settings.legal'), $fields)
            ->assertRedirect()->assertSessionHasNoErrors();
        foreach ($fields as $key => $value) {
            $this->assertSame($value, Setting::get($key));
        }
        $this->get(route('admin.settings'))->assertOk()->assertSee('legal_siret', false);
    }

    public function test_invalid_registration_fields_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.settings.legal'), [
            'legal_siret' => '123', 'legal_registration_date' => '31/02/2026',
        ])->assertSessionHasErrors(['legal_siret', 'legal_registration_date']);
    }
}
