<?php

namespace Tests\Feature\Feed;

use App\Models\User;
use App\Models\UserService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderDirectorySqlProjectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_lookup_selects_only_country_while_filtered_directory_loads(): void
    {
        $viewer = User::factory()->create(['user_type' => 'particulier']);
        $local = $this->provider('Prestataire de Mamoudzou', 'Mamoudzou', 'Mayotte');
        $elsewhere = $this->provider('Prestataire de Paris', 'Paris', 'France');

        foreach ([$local, $elsewhere] as $provider) {
            UserService::create([
                'user_id' => $provider->id,
                'main_category' => 'Bricolage & Travaux',
                'subcategory' => 'Plombier',
                'is_active' => true,
            ]);
        }

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $response = $this->actingAs($viewer)->get(route('feed.professionals', [
            'category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier',
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
        ]));

        $response->assertOk()->assertViewIs('feed.professionals')
            ->assertViewHas('professionals', fn ($profiles) => $profiles->total() === 1
                && $profiles->first()->id === $local->id)
            ->assertViewHas('directoryCountries', fn ($countries) => $countries->contains('Mayotte')
                && $countries->contains('France'));

        // Normaliser les guillemets d'identifiants de SQLite/PostgreSQL tout en
        // vérifiant la projection exacte, sans users.* ni agrégats d'avis.
        $countryQueries = collect($queries)
            ->map(fn (string $sql) => preg_replace('/\s+/', ' ', str_replace(['"', '`'], '', trim($sql))))
            ->filter(fn (string $sql) => preg_match('/^select distinct\b.*\bfrom users\b/i', $sql));

        $this->assertCount(1, $countryQueries, 'Une seule requête DISTINCT doit lister les pays depuis users.');
        $this->assertMatchesRegularExpression(
            '/^select distinct users\.country from users\b/i',
            $countryQueries->first(),
            'La liste des pays ne doit projeter ni users.* ni les sous-requêtes des avis.'
        );
    }

    private function provider(string $name, string $city, string $country): User
    {
        return User::factory()->create([
            'name' => $name,
            'user_type' => 'professionnel',
            'profile_public' => true,
            'profession' => 'Plombier',
            'city' => $city,
            'country' => $country,
        ]);
    }
}
