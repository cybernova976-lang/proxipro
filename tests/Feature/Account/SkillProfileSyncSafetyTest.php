<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Services\ProviderSkills;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Relecture du 24/11/2026 de la resynchronisation des compétences : clé de
 * l'index unique de user_services, catégorie choisie sans métier, longueur des
 * colonnes varchar, pause d'une compétence, sauvegarde restaurable de la
 * migration et bouton de « Mes opportunités » sans métier.
 */
class SkillProfileSyncSafetyTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_24_160000_resync_provider_profiles_from_their_skills.php';

    public function test_a_trade_moved_to_another_category_is_updated_instead_of_violating_the_unique_index(): void
    {
        // Ancienne ligne : « Maçon » était rangé dans « Bricolage & Travaux ».
        $provider = $this->provider();
        $this->skill($provider, 'Bricolage & Travaux', 'Maçon');

        app(ProviderSkills::class)->adoptSelections($provider, ['Maçonnerie & Gros œuvre'], ['Maçon']);

        $this->assertSame(1, $provider->services()->where('subcategory', 'Maçon')->count());
        $this->assertDatabaseHas('user_services', [
            'user_id' => $provider->id, 'subcategory' => 'Maçon',
            'main_category' => 'Maçonnerie & Gros œuvre', 'is_active' => true,
        ]);
        $this->assertSame(['Maçonnerie & Gros œuvre'], $provider->fresh()->pro_service_categories);
    }

    public function test_registering_a_trade_already_saved_under_its_old_category_does_not_fail(): void
    {
        $provider = $this->provider();
        $this->skill($provider, 'Bricolage & Travaux', 'Maçon');

        $this->actingAs($provider)->postJson(route('service-provider.register'), [
            'services' => [['main_category' => 'Maçonnerie & Gros œuvre', 'subcategory' => 'Maçon', 'experience_years' => 4]],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, $provider->services()->where('subcategory', 'Maçon')->count());
        $this->assertDatabaseHas('user_services', [
            'user_id' => $provider->id, 'subcategory' => 'Maçon',
            'main_category' => 'Maçonnerie & Gros œuvre', 'experience_years' => 4,
        ]);
    }

    public function test_absorbing_the_old_profile_uses_the_unique_index_key(): void
    {
        $member = User::factory()->create([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'profession' => 'Maçon', 'service_category' => 'Bricolage & Travaux',
            'service_subcategories' => ['Maçon', 'Électricien'],
        ]);

        app(ProviderSkills::class)->absorbLegacyProfile($member);

        $this->assertDatabaseHas('user_services', ['user_id' => $member->id, 'subcategory' => 'Maçon', 'main_category' => 'Maçonnerie & Gros œuvre']);
        $this->assertDatabaseHas('user_services', ['user_id' => $member->id, 'subcategory' => 'Électricien', 'main_category' => 'Bricolage & Travaux']);
        $this->assertSame(2, $member->services()->count());
    }

    public function test_a_category_chosen_without_any_trade_becomes_a_whole_category_skill(): void
    {
        $provider = $this->provider(['profession' => 'Webmaster']);
        $this->skill($provider, 'Informatique & Tech', 'Webmaster');

        app(ProviderSkills::class)->adoptSelections($provider, ['jardinage-exterieur', 'Informatique & Tech'], []);

        // Forme reconnue par wholeProviderCategories() : métier = nom de la catégorie.
        $this->assertDatabaseHas('user_services', [
            'user_id' => $provider->id, 'main_category' => 'Jardinage & Extérieur',
            'subcategory' => 'Jardinage & Extérieur', 'is_active' => true,
        ]);
        // Une catégorie déjà couverte par une compétence ne reçoit pas de compétence « entière ».
        $this->assertDatabaseMissing('user_services', ['user_id' => $provider->id, 'subcategory' => 'Informatique & Tech']);

        $provider->refresh();
        $this->assertSame(['Informatique & Tech', 'Jardinage & Extérieur'], $provider->pro_service_categories);
        $this->assertSame('Informatique & Tech, Jardinage & Extérieur', $provider->service_category);
        // Ce n'est pas un métier : ni dans les métiers du profil, ni métier principal.
        $this->assertSame(['Webmaster'], $provider->service_subcategories);
        $this->assertSame('Webmaster', $provider->profession);

        // La catégorie entière amène les demandes de tous ses métiers.
        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $this->demand($client, 'Jardinage & Extérieur', 'Tonte de pelouse', 'Pelouse de 300 m² à tondre');
        $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()
            ->assertSee('Pelouse de 300 m² à tondre')
            ->assertSee('Jardinage &amp; Extérieur', false);
    }

    public function test_profile_columns_never_exceed_their_varchar_length(): void
    {
        $provider = $this->provider(['profession' => null]);
        $longNames = [];
        foreach (range(1, 4) as $index) {
            $longNames[] = $name = 'Catégorie '.$index.' '.str_repeat('x', 85);
            $this->skill($provider, $name, 'Métier '.$index);
        }

        app(ProviderSkills::class)->sync($provider);
        $provider->refresh();

        $this->assertLessThanOrEqual(255, mb_strlen($provider->service_category));
        // Catégories entières seulement, la principale (la première) d'abord.
        $this->assertSame($longNames[0].', '.$longNames[1], $provider->service_category);
        $this->assertSame($longNames, $provider->pro_service_categories);
        $this->assertLessThanOrEqual(255, mb_strlen((string) $provider->profession));
        $this->assertSame('Métier 1', $provider->profession);
    }

    public function test_pausing_a_skill_keeps_the_main_trade_for_when_it_resumes(): void
    {
        $provider = $this->provider(['profession' => 'Plombier']);
        $plumber = $this->skill($provider, 'Bricolage & Travaux', 'Plombier');
        $this->skill($provider, 'Informatique & Tech', 'Webmaster');

        $this->actingAs($provider)->putJson(route('service-provider.update-service', $plumber->id), ['is_active' => false])->assertOk();
        $provider->refresh();
        $this->assertSame('Plombier', $provider->profession);
        $this->assertSame(['Webmaster'], $provider->service_subcategories);
        $this->assertSame(['Informatique & Tech'], $provider->pro_service_categories);

        $this->putJson(route('service-provider.update-service', $plumber->id), ['is_active' => true])->assertOk();
        $provider->refresh();
        $this->assertSame('Plombier', $provider->profession);
        $this->assertSame(['Plombier', 'Webmaster'], $provider->service_subcategories);
    }

    public function test_the_migration_backs_up_changed_profiles_and_down_restores_them(): void
    {
        // « Jardinier » choisi à la configuration de l'Espace Pro, absent de « Mes compétences ».
        $changed = $this->provider([
            'profession' => 'Jardinier', 'service_category' => 'Jardinage & Extérieur, Informatique & Tech',
            'service_subcategories' => ['Jardinier', 'Webmaster'],
            'pro_service_categories' => ['Jardinage & Extérieur', 'Informatique & Tech'],
        ]);
        $this->skill($changed, 'Informatique & Tech', 'Webmaster');
        $before = $this->rawColumns($changed->id);

        // Fiche déjà à jour : ni modifiée, ni sauvegardée.
        $upToDate = $this->provider([
            'profession' => 'Webmaster', 'service_category' => 'Informatique & Tech',
            'service_subcategories' => ['Webmaster'], 'pro_service_categories' => ['Informatique & Tech'],
        ]);
        $this->skill($upToDate, 'Informatique & Tech', 'Webmaster');

        $migration = $this->migration();
        $migration->up();

        $changed->refresh();
        $this->assertSame('Webmaster', $changed->profession);
        $this->assertSame(['Webmaster'], $changed->service_subcategories);
        $this->assertSame(1, DB::table('provider_profile_skill_backups')->count());
        $backup = DB::table('provider_profile_skill_backups')->first();
        $this->assertSame($changed->id, (int) $backup->user_id);
        $this->assertSame('Jardinier', $backup->profession);

        $migration->down();

        $this->assertSame($before, $this->rawColumns($changed->id));
        $this->assertSame(['Jardinier', 'Webmaster'], $changed->fresh()->service_subcategories);
        $this->assertFalse(Schema::hasTable('provider_profile_skill_backups'));
    }

    public function test_one_failing_profile_does_not_block_the_migration(): void
    {
        $broken = $this->provider(['profession' => 'Plombier', 'service_subcategories' => ['Plombier', 'Webmaster']]);
        $this->skill($broken, 'Informatique & Tech', 'Webmaster');
        $healthy = $this->provider(['profession' => 'Plombier', 'service_subcategories' => ['Plombier', 'Webmaster']]);
        $this->skill($healthy, 'Informatique & Tech', 'Webmaster');

        $this->app->instance(ProviderSkills::class, new class($broken->id) extends ProviderSkills
        {
            public function __construct(private int $brokenId) {}

            public function sync(User $user, array $removed = []): void
            {
                if ($user->id === $this->brokenId) {
                    // Écriture partielle puis échec : elle doit être annulée.
                    DB::table('users')->where('id', $user->id)->update(['profession' => 'À moitié écrit']);
                    throw new \RuntimeException('value too long for type character varying(255)');
                }

                parent::sync($user, $removed);
            }
        });
        Log::spy();

        $this->migration()->up();

        $this->assertSame('Plombier', $broken->fresh()->profession);
        $this->assertSame('Webmaster', $healthy->fresh()->profession);
        $this->assertSame(1, DB::table('provider_profile_skill_backups')->count());
        Log::shouldHaveReceived('warning')->withArgs(
            fn ($message, $context = []) => ($context['user_id'] ?? null) === $broken->id
        )->once();
    }

    public function test_a_schema_error_still_stops_the_migration(): void
    {
        $provider = $this->provider();
        $this->skill($provider, 'Informatique & Tech', 'Webmaster');

        $this->app->instance(ProviderSkills::class, new class extends ProviderSkills
        {
            public function sync(User $user, array $removed = []): void
            {
                $pdo = new \PDOException('SQLSTATE[42703]: Undefined column');
                $pdo->errorInfo = ['42703', 7, 'column "profession" does not exist'];

                throw new QueryException('pgsql', 'update "users" set "profession" = ?', [], $pdo);
            }
        });

        $this->expectException(QueryException::class);
        $this->migration()->up();
    }

    public function test_opportunities_without_any_trade_point_to_the_skills_page(): void
    {
        $provider = $this->provider(['profession' => null, 'service_category' => null,
            'service_subcategories' => null, 'pro_service_categories' => null]);

        $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()
            ->assertSee('Ajouter mes compétences')
            ->assertSee(route('service-provider.mes-services'), false)
            ->assertDontSee('Configurer mes services');
    }

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION);
    }

    /** @return array<string, mixed> */
    private function rawColumns(int $userId): array
    {
        return (array) DB::table('users')->where('id', $userId)
            ->first(['profession', 'service_category', 'service_subcategories', 'pro_service_categories']);
    }

    private function provider(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'pro_notifications_realtime' => true,
        ], $attributes));
    }

    private function skill(User $user, string $main, string $subcategory): UserService
    {
        return UserService::create(['user_id' => $user->id, 'main_category' => $main,
            'subcategory' => $subcategory, 'experience_years' => 2, 'is_active' => true]);
    }

    private function demand(User $client, string $main, string $category, string $title): Ad
    {
        return Ad::create(['user_id' => $client->id, 'title' => $title, 'description' => 'Description de la demande de test.',
            'main_category' => $main, 'category' => $category, 'service_type' => 'demande',
            'location' => 'Paris', 'status' => 'active', 'expires_at' => now()->addDays(10)]);
    }
}
