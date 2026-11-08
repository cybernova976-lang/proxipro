<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\User;
use App\Support\AdDisclosure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Le statut choisi dans la modale d'activation (entreprise, auto-entrepreneur)
 * est conservé : la mention « Particulier / Professionnel » montrée aux
 * clients (AdDisclosure::advertiserStatus) ne doit pas le contredire.
 */
class BecomeProviderDeclaredStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_auto_entrepreneur_chosen_in_the_modal_is_shown_as_professional(): void
    {
        $user = $this->verifiedMember();

        $this->actingAs($user)->postJson(route('become-provider.store'), $this->payload([
            'business_type' => 'auto_entrepreneur',
            'company_name' => 'Amina Services',
        ]))->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $this->assertSame('professionnel', $user->account_type);
        $this->assertSame('professionnel', $user->user_type);
        $this->assertSame('auto_entrepreneur', $user->business_type);
        $this->assertSame('Amina Services', $user->company_name);
        $this->assertSame('Professionnel', AdDisclosure::advertiserStatus($user));
    }

    public function test_member_without_declared_business_type_stays_particular(): void
    {
        $user = $this->verifiedMember();

        $this->actingAs($user)->postJson(route('become-provider.store'), $this->payload())
            ->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $this->assertSame('particulier', $user->account_type);
        $this->assertSame('Particulier', AdDisclosure::advertiserStatus($user));
    }

    private function verifiedMember(): User
    {
        return User::factory()->completeForVerification()->create([
            'user_type' => 'particulier',
            'account_type' => 'particulier',
            'is_service_provider' => false,
            'identity_verified' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'Nettoyage & Entretien',
            'subcategory' => 'Femme/Homme de ménage',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
        ], $overrides);
    }
}
