<?php

namespace Tests\Feature\Admin;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 1, piste D3 : écran /admin/feed-sante, réservé à l'administration et en lecture seule.
 */
class FeedHealthAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_and_member_is_refused(): void
    {
        $this->get('/admin/feed-sante')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/admin/feed-sante')->assertForbidden();
    }

    public function test_admin_sees_measures_by_territory_and_the_lists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mayotte = User::factory()->create([
            'name' => 'Prestataire Mayotte', 'postal_code' => null, 'country' => 'Mayotte', 'city' => 'Mamoudzou',
            'is_service_provider' => true,
        ]);
        $paris = User::factory()->create(['name' => 'Auteur Paris']);
        $planOnly = User::factory()->create(['name' => 'Plan seulement', 'plan' => 'PRO']);

        $this->ad($mayotte, ['title' => 'Plomberie à Mamoudzou', 'is_boosted' => true, 'boost_end' => now()->addDays(3), 'boost_type' => 'vip']);
        $this->ad($mayotte, ['title' => 'Cherche un plombier pour samedi']);
        $this->ad($mayotte, ['title' => 'Demande à Dembeni', 'service_type' => 'demande', 'created_at' => now()->subDays(10)]);
        $this->ad($mayotte, ['title' => 'Demande ancienne', 'service_type' => 'demande', 'created_at' => now()->subDays(60)]);
        $this->ad($paris, ['title' => 'Jardinage à Paris', 'postal_code' => '75011', 'country' => 'France', 'city' => 'Paris', 'location' => 'Paris']);
        $this->ad($paris, ['title' => 'Offre archivée', 'status' => 'inactive']);

        $response = $this->actingAs($admin)->get('/admin/feed-sante')->assertOk()
            ->assertSee('Santé du feed')
            ->assertSee('Département 75')
            ->assertSee('Boosts en cours (1)')
            ->assertSee('vip')
            ->assertSee('Cherche un plombier pour samedi')
            ->assertSee('href="'.route('ads.show', Ad::where('title', 'Cherche un plombier pour samedi')->value('id')).'"', false)
            ->assertSee('Plan seulement')
            ->assertSee('Prestataire Mayotte')
            ->assertDontSee('Offre archivée');

        $territories = collect($response->viewData('pkHealth')['territories'])->keyBy('code');
        $this->assertSame(
            ['offers' => 2, 'promoted' => 1, 'providers' => 1, 'requests_30' => 1, 'requests_90' => 2],
            collect($territories['976'])->only(['offers', 'promoted', 'providers', 'requests_30', 'requests_90'])->all()
        );
        $this->assertSame(1, $territories['75']['offers']);
        $this->assertSame(0, $territories['75']['promoted']);
        $this->assertSame([$planOnly->id], $response->viewData('pkHealth')['plan_only_subscribers']->pluck('id')->all());

        $authors = collect($response->viewData('pkHealth')['offer_authors'])->keyBy('id');
        $this->assertSame(2, $authors[$mayotte->id]['offers_count']);
        $this->assertSame(1, $authors[$paris->id]['offers_count']);
    }

    public function test_territory_counts_match_what_clients_see_and_legacy_default_position_is_unknown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $internal = User::factory()->create(['name' => 'Compte interne']);
        $this->ad($internal, ['title' => 'Offre du compte interne']);
        $this->ad(User::factory()->create(), ['title' => 'Offre réservée aux pros', 'visibility' => 'pro_targeted']);
        // Prestataire à la position par défaut posée avant le marquage geo_source = default.
        User::factory()->create(['name' => 'Prestataire sans lieu', 'is_service_provider' => true,
            'latitude' => -12.8275, 'longitude' => 45.1662, 'detected_city' => 'Mamoudzou',
            'detected_country' => 'Mayotte', 'geo_source' => null]);
        config(['feed.hidden_author_ids' => [$internal->id]]);

        $response = $this->actingAs($admin)->get('/admin/feed-sante')->assertOk()
            ->assertSee('Ils sont traités comme territoire inconnu.')
            ->assertDontSee('rattachés à Mayotte');
        $health = $response->viewData('pkHealth');
        $territories = collect($health['territories'])->keyBy(fn ($row) => $row['code'] ?? '');

        $this->assertSame(0, $territories['976']['offers'] ?? 0);
        $this->assertSame(0, $territories['976']['providers'] ?? 0);
        $this->assertSame(1, $territories['']['providers']);
        $this->assertSame(1, $health['default_location_users']);
        // L'auteur masqué reste listé, pour la colonne « Masqué ».
        $this->assertTrue(collect($health['offer_authors'])->contains('id', $internal->id));
    }

    public function test_admin_page_writes_nothing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['country' => 'Mayotte', 'postal_code' => null, 'city' => 'Mamoudzou']);
        $this->ad($author, ['title' => 'Cherche quelqu’un', 'is_boosted' => true, 'boost_end' => now()->addDay()]);
        $this->actingAs($admin);

        $before = $this->snapshot();
        $writes = [];
        // Seul le suivi d'inactivité de session (App\Services\SessionInactivity),
        // commun à toutes les pages connectées, est toléré : il ne touche aucune donnée métier.
        DB::listen(function ($query) use (&$writes) {
            $isWrite = preg_match('/^\s*(insert|update|delete|replace|create|drop|alter)\b/i', $query->sql) === 1;
            $isSessionTracking = preg_match('/^\s*\S+(\s+or\s+ignore)?\s+((into|from)\s+)?"?(session_user_activity|sessions)"?\s/i', $query->sql) === 1;
            if ($isWrite && ! $isSessionTracking) {
                $writes[] = $query->sql;
            }
        });

        $this->get('/admin/feed-sante')->assertOk();

        $this->assertSame([], $writes, 'L’écran de santé du feed ne doit rien écrire : '.implode(' | ', $writes));
        $this->assertSame($before, $this->snapshot());
    }

    private function snapshot(): array
    {
        return [
            'ads' => DB::table('ads')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'users' => DB::table('users')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }

    private function ad(User $user, array $attributes = []): Ad
    {
        $ad = new Ad;
        $ad->forceFill(array_merge([
            'user_id' => $user->id,
            'title' => 'Annonce '.Str::random(6),
            'description' => 'Description suffisamment longue pour une annonce de test.',
            'category' => 'Plombier',
            'main_category' => 'Bricolage & Travaux',
            'publication_domain' => 'service',
            'service_type' => 'offre',
            'status' => 'active',
            'visibility' => 'public',
            'price' => 30,
            'price_type' => 'fixed',
            'location' => 'Mamoudzou',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'postal_code' => '97600',
        ], $attributes))->save();

        return $ad;
    }
}
