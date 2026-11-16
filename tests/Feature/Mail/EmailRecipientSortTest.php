<?php

namespace Tests\Feature\Mail;

use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le Studio e-mail trie ses destinataires en particuliers et professionnels,
 * sans jamais y ajouter un compte qui n'a pas accepté la newsletter.
 */
class EmailRecipientSortTest extends TestCase
{
    use RefreshDatabase;

    private function reachable(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['newsletter_subscribed' => true, 'email_notifications' => true, 'is_active' => true]);
    }

    private function professional(array $attributes = []): array
    {
        return $attributes + ['account_type' => 'professionnel', 'user_type' => 'professionnel'];
    }

    public function test_studio_counts_reachable_accounts_of_each_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'newsletter_subscribed' => false]);
        $this->reachable(['name' => 'Paul Particulier']);
        $this->reachable(['name' => 'Fatima Bricoleuse', 'is_service_provider' => true]);
        $pro = $this->reachable($this->professional(['name' => 'Ali Plombier']));
        // Professionnel qui n'a pas accepté la newsletter : il n'est ni listé ni compté.
        User::factory()->create($this->professional(['name' => 'Société Sans Accord', 'newsletter_subscribed' => false]));

        $response = $this->actingAs($admin)->get(route('admin.emails.create'));

        $response->assertOk()
            ->assertSee('data-joignables="tous">3<', false)
            ->assertSee('data-joignables="particuliers">2<', false)
            ->assertSee('data-joignables="professionnels">1<', false)
            ->assertSee('Paul Particulier')->assertSee('Fatima Bricoleuse')->assertSee('Ali Plombier')
            ->assertDontSee('Société Sans Accord')
            ->assertSee('js/admin-email-recipients.js', false);

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'data-type="particuliers"'));
        $this->assertSame(1, substr_count($html, 'data-type="professionnels"'));
        $this->assertMatchesRegularExpression(
            '/data-type="professionnels"><input type="checkbox" name="recipients\[\]" value="'.$pro->id.'"/',
            $html
        );
    }

    public function test_selecting_the_professionals_prepares_emails_for_them_only(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'newsletter_subscribed' => false]);
        $this->reachable(['name' => 'Paul Particulier']);
        $pro = $this->reachable($this->professional(['name' => 'Ali Plombier']));
        $withoutConsent = User::factory()->create($this->professional(['newsletter_subscribed' => false]));
        $message = ['subject' => 'Actualités', 'headline' => 'Bonjour', 'body' => 'Découvrez Prokejem', 'audience' => 'selected'];

        $this->actingAs($admin)->post(route('admin.emails.store'), $message + ['recipients' => [$pro->id]])
            ->assertRedirect(route('admin.emails.index'));
        $this->assertSame($pro->id, ManagedEmail::sole()->user_id);

        // Un professionnel sans accord ne peut pas être ajouté, même en forçant son identifiant.
        $this->postJson(route('admin.emails.store'), $message + ['recipients' => [$pro->id, $withoutConsent->id]])
            ->assertUnprocessable();
        $this->assertDatabaseCount('managed_emails', 1);
        Mail::assertNothingSent();
    }

    public function test_studio_stays_usable_without_any_reachable_professional(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'newsletter_subscribed' => false]);
        $this->reachable(['name' => 'Paul Particulier']);

        $this->actingAs($admin)->get(route('admin.emails.create'))->assertOk()
            ->assertSee('data-joignables="particuliers">1<', false)
            ->assertSee('data-joignables="professionnels">0<', false)
            ->assertSee('Aucun compte joignable dans cette liste.');
    }
}
