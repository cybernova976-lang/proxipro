<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationAndPointsIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_published_interface_and_validation_messages_are_french(): void
    {
        $this->assertSame('fr', config('app.locale'));
        $this->assertSame('fr', app()->getLocale());

        $validator = Validator::make(
            ['description' => 'Trop courte'],
            ['description' => ['required', 'min:20']],
        );

        $this->assertTrue($validator->fails());
        $message = $validator->errors()->first('description');
        $this->assertSame(
            'Le champ description doit contenir au moins 20 caractères.',
            $message,
        );
        $this->assertStringNotContainsString('The ', $message);
    }

    public function test_unverifiable_reward_endpoints_are_no_longer_exposed(): void
    {
        $this->assertFalse(Route::has('points.share'));
        $this->assertFalse(Route::has('points.daily'));
        $this->assertFalse(Route::has('social.share'));
        $this->assertFalse(Route::has('social.status'));
    }

    public function test_points_and_pricing_pages_only_present_traceable_rewards(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('points.dashboard'))
            ->assertOk()
            ->assertSee('Inscription validée')
            ->assertSee('Profil vérifié')
            ->assertSee('Parrainage réussi')
            ->assertDontSee('Partager pour gagner')
            ->assertDontSee('Partage réseaux sociaux');

        $this->actingAs($user)
            ->get(route('pricing.index'))
            ->assertOk()
            ->assertSee('Un programme de parrainage traçable est disponible')
            ->assertSee('Un simple clic de partage ne donne aucun point.')
            ->assertDontSee('Partager pour gagner');
    }

    public function test_critical_layouts_keep_mobile_viewport_and_responsive_guards(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $proLayout = file_get_contents(resource_path('views/pro/layout.blade.php'));
        $authLayout = file_get_contents(resource_path('views/layouts/auth.blade.php'));

        foreach ([$appLayout, $proLayout, $authLayout] as $layout) {
            $this->assertStringContainsString('width=device-width, initial-scale=1', $layout);
            $this->assertStringContainsString('@media (max-width:', $layout);
        }

        $this->assertStringContainsString('.table-responsive', $proLayout);
        $this->assertStringContainsString('.pro-card[style*="position: sticky"]', $proLayout);

        $passwordConfirmation = file_get_contents(resource_path('views/auth/passwords/confirm.blade.php'));
        $this->assertStringContainsString('Confirmez votre mot de passe avant de continuer.', $passwordConfirmation);
        $this->assertStringNotContainsString('Please confirm your password', $passwordConfirmation);
    }
}
