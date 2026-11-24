<?php

namespace Tests\Feature\SavedSearch;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activer une alerte laisse une trace visible : le bouton change d'état sur
 * place et un message confirme l'action. Repères ne présente plus de compteur.
 */
class AlertConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_alert_button_answers_a_script_with_its_new_state_and_the_count(): void
    {
        $viewer = User::factory()->create();
        $provider = $this->provider();
        $this->actingAs($viewer);

        $response = $this->postJson(route('saved-searches.store'), ['provider' => $provider->id])->assertOk();
        $alert = SavedSearch::sole();
        $response->assertJson([
            'ok' => true, 'active' => true, 'count' => 1,
            'stop_url' => route('saved-searches.destroy', $alert),
        ]);
        $this->assertStringContainsString('Alerte enregistrée', $response->json('message'));
        $this->assertStringContainsString($provider->name, $response->json('message'));

        // Second clic sur une autre carte du même prestataire : même état, pas de doublon.
        $this->postJson(route('saved-searches.store'), ['provider' => $provider->id])->assertOk()
            ->assertJson(['ok' => true, 'active' => true, 'count' => 1, 'stop_url' => route('saved-searches.destroy', $alert)]);
        $this->assertSame(1, SavedSearch::count());

        // Alerte impossible (son propre compte) : refus lisible, rien n'est créé.
        $this->postJson(route('saved-searches.store'), ['provider' => $viewer->id])->assertStatus(422)
            ->assertJson(['ok' => false, 'active' => false, 'count' => 1, 'stop_url' => null]);

        // Sans script, le formulaire répond comme avant : retour à la page avec un message.
        $other = $this->provider();
        $this->from(route('feed'))->post(route('saved-searches.store'), ['provider' => $other->id])
            ->assertRedirect(route('feed'))->assertSessionHas('info', fn (string $message) => str_contains($message, $other->name));
        $this->assertSame(2, SavedSearch::count());
    }

    public function test_the_feed_confirms_each_alert_without_a_landmarks_counter(): void
    {
        $viewer = User::factory()->create(['city' => 'Mamoudzou', 'country' => 'Mayotte']);
        $provider = $this->provider();
        $this->offer($provider);
        $this->actingAs($viewer);

        // Les alertes restent disponibles sur les offres, sans raccourci dans Repères.
        $html = $this->get(route('feed'))->assertOk()
            ->assertSee('js/alert-forms.js', false)->assertSee('css/alert-forms.css', false)
            ->getContent();
        $this->assertStringNotContainsString('data-pk-alert-count', $html);

        // La cloche d'une offre porte son état « alerte active », prêt à être affiché au clic.
        $this->assertMatchesRegularExpression('/<span class="pk-alert-slot" data-pk-alert>\s*<template data-pk-alert-done>\s*<form method="POST" action="__STOP_URL__" class="pk-follow"/', $html);
        $this->assertStringContainsString('aria-pressed="true"', $html);

        $this->postJson(route('saved-searches.store'), ['provider' => $provider->id])->assertOk();
        $this->postJson(route('saved-searches.store'), ['provider' => $this->provider()->id])->assertOk();
        // Une alerte arrêtée par le système ne change pas l'état de celle du prestataire.
        SavedSearch::create(['user_id' => $viewer->id, 'name' => 'Ancienne alerte', 'filters' => [], 'is_active' => false]);

        $html = $this->get(route('feed'))->assertOk()->getContent();
        $alert = SavedSearch::where('user_id', $viewer->id)->where('provider_id', $provider->id)->sole();
        $this->assertStringContainsString('action="'.route('saved-searches.destroy', $alert).'"', $html);
        $this->assertStringContainsString('Alerte active. Cliquez pour ne plus être prévenu', $html);
        $this->assertStringNotContainsString('data-pk-alert-count', $html);
    }

    public function test_the_offer_page_button_also_confirms_in_place(): void
    {
        $viewer = User::factory()->create();
        $provider = $this->provider();
        $ad = $this->offer($provider);

        $html = $this->actingAs($viewer)->get(route('ads.show', $ad))->assertOk()
            ->assertSee('Me prévenir de ses nouvelles offres')->getContent();
        $this->assertMatchesRegularExpression('/<div data-pk-alert>\s*<template data-pk-alert-done>/', $html);
        $this->assertStringContainsString('Alerte active · ne plus me prévenir', $html, 'L’état actif est prêt dans la page.');
        $this->assertStringContainsString('action="__STOP_URL__"', $html);
    }

    private function provider(): User
    {
        return User::factory()->create([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'profession' => 'Plombier', 'service_category' => 'Plombier', 'pro_status' => 'active',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'is_active' => true,
        ]);
    }

    private function offer(User $provider): Ad
    {
        return Ad::create([
            'user_id' => $provider->id, 'title' => 'Plomberie et dépannage', 'description' => 'Interventions rapides à domicile, devis sur demande.',
            'category' => 'Plombier', 'location' => 'Mamoudzou', 'country' => 'Mayotte', 'service_type' => 'offre',
            'price' => 50, 'status' => 'active',
        ]);
    }
}
