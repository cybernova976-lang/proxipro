<?php

namespace Tests\Feature\Account;

use App\Http\Controllers\AdController;
use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Notifications\NewAdMatchingNotification;
use App\Services\ProviderSkills;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Signalement du 24/11/2026 : « Plombier » supprimé de « Mes compétences »
 * restait le métier principal, gardait « Bricolage & Travaux » dans les
 * catégories du profil et continuait d'amener des demandes de plomberie
 * dans « Mes opportunités » et dans les notifications.
 */
class SkillRemovalEverywhereTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deleted_skill_disappears_from_profile_opportunities_and_notifications(): void
    {
        $provider = $this->plumberAndWebmaster();
        $plumber = $provider->services()->where('subcategory', 'Plombier')->firstOrFail();
        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $plumbingDemand = $this->demand($client, 'Bricolage & Travaux', 'Plombier', 'Fuite sous évier à réparer');
        $websiteDemand = $this->demand($client, 'Informatique & Tech', 'Webmaster', 'Refonte du site de la boulangerie');

        // Avant : la plomberie fait bien partie des opportunités.
        $this->actingAs($provider)->get(route('pro.opportunities'))->assertOk()
            ->assertSee('Fuite sous évier à réparer');

        $this->deleteJson(route('service-provider.delete-service', $plumber->id))->assertOk();

        // Compétences et profil.
        $this->assertDatabaseMissing('user_services', ['id' => $plumber->id]);
        $this->assertDatabaseHas('user_services', ['user_id' => $provider->id, 'subcategory' => 'Webmaster', 'is_active' => true]);
        $provider->refresh();
        $this->assertSame('Webmaster', $provider->profession);
        $this->assertSame('Informatique & Tech', $provider->service_category);
        $this->assertSame(['Webmaster'], $provider->service_subcategories);
        $this->assertSame(['Informatique & Tech'], $provider->pro_service_categories);

        // Profil affiché : plus de métier « Plombier » ni de « Bricolage & Travaux ».
        $this->get(route('profile.show'))->assertOk()
            ->assertDontSee('Plombier')->assertDontSee('Bricolage &amp; Travaux', false);

        // Mes opportunités : la demande de plomberie n'y est plus, celle de webmaster reste.
        $this->get(route('pro.opportunities'))->assertOk()
            ->assertDontSee('Fuite sous évier à réparer')
            ->assertSee('Refonte du site de la boulangerie');

        // Notifications de nouvelles demandes.
        Notification::fake();
        app(AdController::class)->notifyMatchingProfessionals($plumbingDemand);
        Notification::assertNotSentTo($provider, NewAdMatchingNotification::class);
        app(AdController::class)->notifyMatchingProfessionals($websiteDemand);
        Notification::assertSentTo($provider, NewAdMatchingNotification::class);
    }

    public function test_a_paused_skill_no_longer_brings_requests_until_it_is_resumed(): void
    {
        $provider = $this->plumberAndWebmaster();
        $plumber = $provider->services()->where('subcategory', 'Plombier')->firstOrFail();
        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        $this->demand($client, 'Bricolage & Travaux', 'Plombier', 'Fuite sous évier à réparer');

        $this->actingAs($provider)->putJson(route('service-provider.update-service', $plumber->id), ['is_active' => false])->assertOk();
        $this->assertSame(['Webmaster'], $provider->fresh()->service_subcategories);
        $this->get(route('pro.opportunities'))->assertOk()->assertDontSee('Fuite sous évier à réparer');

        $this->putJson(route('service-provider.update-service', $plumber->id), ['is_active' => true])->assertOk();
        $this->get(route('pro.opportunities'))->assertOk()->assertSee('Fuite sous évier à réparer');
    }

    public function test_profiles_already_out_of_date_are_repaired_from_their_remaining_skills(): void
    {
        // État laissé en base par l'ancienne suppression : la ligne a disparu, pas les colonnes.
        $provider = $this->plumberAndWebmaster();
        $provider->services()->where('subcategory', 'Plombier')->delete();

        // Même avant la réparation, les correspondances ne lisent plus les colonnes périmées.
        $this->assertSame(['Informatique & Tech', 'Webmaster'], app(ProviderSkills::class)->matchingCategories($provider)->all());

        $migration = require database_path('migrations/2026_11_24_160000_resync_provider_profiles_from_their_skills.php');
        $migration->up();

        $provider->refresh();
        $this->assertSame('Webmaster', $provider->profession);
        $this->assertSame('Informatique & Tech', $provider->service_category);
        $this->assertSame(['Webmaster'], $provider->service_subcategories);
    }

    public function test_a_freely_written_job_title_is_kept_and_accounts_without_skills_keep_their_profile(): void
    {
        $provider = $this->plumberAndWebmaster(['profession' => 'Artisan multiservice']);
        $plumber = $provider->services()->where('subcategory', 'Plombier')->firstOrFail();
        $this->actingAs($provider)->deleteJson(route('service-provider.delete-service', $plumber->id))->assertOk();
        $this->assertSame('Artisan multiservice', $provider->fresh()->profession);

        // Ancien parcours, aucune compétence enregistrée : les colonnes du profil font foi.
        $legacy = User::factory()->create([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'pro_notifications_realtime' => true, 'profession' => 'Électricien',
            'service_category' => 'Bricolage & Travaux', 'service_subcategories' => ['Électricien'],
            'pro_service_categories' => ['bricolage-travaux'],
        ]);
        $categories = app(ProviderSkills::class)->matchingCategories($legacy)->all();
        $this->assertContains('Électricien', $categories);
        $this->assertContains('Bricolage & Travaux', $categories);

        $client = User::factory()->create(['user_type' => 'particulier', 'account_type' => 'particulier']);
        Notification::fake();
        app(AdController::class)->notifyMatchingProfessionals($this->demand($client, 'Bricolage & Travaux', 'Électricien', 'Prise à remplacer'));
        Notification::assertSentTo($legacy, NewAdMatchingNotification::class);
    }

    public function test_adding_a_first_skill_keeps_the_trades_declared_on_the_old_profile(): void
    {
        $member = User::factory()->create([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'profession' => 'Électricien', 'service_category' => 'Bricolage & Travaux',
            'service_subcategories' => ['Électricien'],
        ]);

        $skills = app(ProviderSkills::class);
        $skills->absorbLegacyProfile($member);
        UserService::create(['user_id' => $member->id, 'main_category' => 'Informatique & Tech',
            'subcategory' => 'Webmaster', 'experience_years' => 0, 'is_active' => true]);
        $skills->sync($member);

        $member->refresh();
        $this->assertDatabaseHas('user_services', ['user_id' => $member->id, 'main_category' => 'Bricolage & Travaux', 'subcategory' => 'Électricien']);
        $this->assertSame('Électricien', $member->profession);
        $this->assertSame(['Électricien', 'Webmaster'], $member->service_subcategories);
        $this->assertSame('Bricolage & Travaux, Informatique & Tech', $member->service_category);
    }

    public function test_trades_chosen_in_the_category_window_become_skills_for_a_member_who_has_some(): void
    {
        $provider = $this->plumberAndWebmaster();
        $provider->services()->where('subcategory', 'Plombier')->delete();

        $this->actingAs($provider)->postJson(route('profile.save-categories'), [
            'service_category' => 'Bricolage & Travaux',
            'service_subcategories' => ['Électricien'],
        ])->assertOk();

        $provider->refresh();
        $this->assertDatabaseHas('user_services', ['user_id' => $provider->id, 'subcategory' => 'Électricien', 'is_active' => true]);
        // La fusion « additive » de l'ancienne fenêtre ne ressuscite pas « Plombier ».
        $this->assertSame(['Webmaster', 'Électricien'], $provider->service_subcategories);
        $this->assertSame('Webmaster', $provider->profession);
        $this->assertContains('Électricien', app(ProviderSkills::class)->matchingCategories($provider)->all());
    }

    private function plumberAndWebmaster(array $attributes = []): User
    {
        $provider = User::factory()->create(array_merge([
            'user_type' => 'professionnel', 'account_type' => 'professionnel', 'is_service_provider' => true,
            'pro_notifications_realtime' => true,
            'profession' => 'Plombier',
            'service_category' => 'Bricolage & Travaux, Informatique & Tech',
            'service_subcategories' => ['Plombier', 'Webmaster'],
            'pro_service_categories' => ['Bricolage & Travaux', 'Informatique & Tech'],
        ], $attributes));

        UserService::create(['user_id' => $provider->id, 'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier', 'experience_years' => 5, 'is_active' => true]);
        UserService::create(['user_id' => $provider->id, 'main_category' => 'Informatique & Tech',
            'subcategory' => 'Webmaster', 'experience_years' => 3, 'is_active' => true]);

        return $provider->fresh();
    }

    private function demand(User $client, string $main, string $category, string $title): Ad
    {
        return Ad::create(['user_id' => $client->id, 'title' => $title, 'description' => 'Description de la demande de test.',
            'main_category' => $main, 'category' => $category, 'service_type' => 'demande',
            'location' => 'Paris', 'status' => 'active', 'expires_at' => now()->addDays(10)]);
    }
}
