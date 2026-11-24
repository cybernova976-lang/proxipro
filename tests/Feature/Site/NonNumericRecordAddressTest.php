<?php

namespace Tests\Feature\Site;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le 24/11/2026, /ads/mine répondait par une erreur 500 en production : PostgreSQL
 * refuse de comparer un numéro à « mine ». Une adresse d'enregistrement qui n'est pas
 * un numéro doit répondre « Page introuvable ».
 */
class NonNumericRecordAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_record_address_that_is_not_a_number_is_a_404_not_a_server_error(): void
    {
        // SQLite (tests) rend 404 de lui-même ; c'est la règle d'adresse qui protège PostgreSQL.
        $this->assertSame('[0-9]+', app('router')->getPatterns()['ad'] ?? null);
        $this->assertSame('[0-9]+', app('router')->getPatterns()['serviceOrder'] ?? null);

        $member = User::factory()->create();

        foreach (['/ads/mine', '/ads/mine/edit', '/facturation/factures/abc'] as $path) {
            $this->actingAs($member)->get($path)->assertNotFound();
        }
        $this->get('/ads/mine')->assertNotFound();

        // Les adresses numériques et les pages nommées marchent toujours.
        $ad = Ad::create(['user_id' => $member->id, 'title' => 'Peinture intérieure', 'description' => 'Description.',
            'category' => 'Peintre en bâtiment', 'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public', 'location' => 'Mamoudzou']);
        $this->get(route('ads.show', $ad))->assertOk();
        $this->actingAs($member)->get(route('ads.create'))->assertOk();
    }
}
