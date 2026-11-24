<?php

namespace Tests\Feature\Notifications;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use App\Notifications\BoostExpiringNotification;
use App\Support\BellNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Demande du fondateur du 24/11/2026 : des interrupteurs par type pour les notifications
 * de la cloche, tout activé par défaut. Les notifications vitales (messages, commandes,
 * paiements, compte) restent toujours affichées.
 */
class BellPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['user_type' => 'particulier', 'is_service_provider' => false], $attributes));
    }

    private function ad(User $owner): Ad
    {
        return Ad::create(['user_id' => $owner->id, 'title' => 'Peinture intérieure', 'description' => 'Description.',
            'category' => 'Peintre', 'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public', 'location' => 'Mamoudzou']);
    }

    public function test_everything_is_on_by_default_and_each_switch_is_listed(): void
    {
        $member = $this->member();
        $this->assertNull($member->fresh()->muted_bell_categories);

        $html = $this->actingAs($member)->get(route('settings.index'))->assertOk()
            ->assertSee('id="cloche"', false)->assertSee('Dans la cloche')
            ->assertSee('action="'.route('settings.bell').'"', false)
            ->assertSee('Toujours affichées dans la cloche : '.BellNotifications::ALWAYS_SHOWN.'.')
            ->getContent();

        foreach (['new_ads', 'proposals', 'boosts', 'referral'] as $key) {
            $this->assertMatchesRegularExpression('/id="bell_'.$key.'" name="bell\['.$key.'\]" value="1"\s+checked/', $html, $key);
        }
        // Le réglage se trouve depuis la cloche, la page Notifications et le tableau de bord.
        $this->get(route('feed'))->assertOk()->assertSee('href="'.route('settings.index').'#cloche" data-bell-settings', false);
        $this->get(route('notifications.index'))->assertOk()->assertSee(route('settings.index').'#cloche', false);
        $this->get(route('client.dashboard'))->assertOk()->assertSee('Choisir ce qui s’affiche dans la cloche');

        // Les catégories de l'Espace Pro ne sont proposées qu'aux prestataires.
        $this->assertStringNotContainsString('id="bell_quotes"', $html);
        $this->assertStringNotContainsString('id="bell_reports"', $html);

        $provider = User::factory()->create(['user_type' => 'professionnel', 'is_service_provider' => true]);
        $this->actingAs($provider)->get(route('settings.index'))->assertOk()
            ->assertSee('id="bell_quotes"', false)->assertSee('id="bell_reports"', false);
    }

    public function test_a_switched_off_category_no_longer_reaches_the_bell_but_vital_ones_always_do(): void
    {
        $member = $this->member();
        $ad = $this->ad($member);

        // Par défaut, la fin d'un boost entre dans la cloche.
        $member->notifyNow(new BoostExpiringNotification($ad, 'boost', 24), ['database']);
        $this->assertSame(1, $member->notifications()->count());

        // Le membre coupe « Mise en avant » et garde le reste.
        $this->actingAs($member)->post(route('settings.bell'), ['bell' => ['new_ads' => '1', 'proposals' => '1', 'referral' => '1']])
            ->assertRedirect(route('settings.index').'#cloche')
            ->assertSessionHas('success', 'Préférences de la cloche enregistrées.');
        $this->assertSame(['boosts'], $member->fresh()->muted_bell_categories);
        $this->get(route('settings.index'))->assertOk()
            ->assertDontSee('id="bell_boosts" name="bell[boosts]" value="1" checked', false)
            ->assertSee('id="bell_new_ads" name="bell[new_ads]" value="1" checked', false);

        $member->refresh()->notifyNow(new BoostExpiringNotification($ad, 'boost', 12), ['database']);
        $this->assertSame(1, $member->notifications()->count(), 'La fin du boost ne doit plus entrer dans la cloche.');

        // Une notification vitale passe toujours, même si tout est coupé.
        $this->post(route('settings.bell'), []);
        $this->assertEqualsCanonicalizing(['new_ads', 'proposals', 'boosts', 'referral'], $member->fresh()->muted_bell_categories);
        AccountStatusNotification::dispatch($member->fresh(), 'account_verified');
        $this->assertSame(2, $member->notifications()->count());
        $this->assertNull(BellNotifications::categoryOf(new AccountStatusNotification('account_verified')));

        // Tout recocher revient au réglage par défaut.
        $this->post(route('settings.bell'), ['bell' => ['new_ads' => '1', 'proposals' => '1', 'boosts' => '1', 'referral' => '1']]);
        $this->assertNull($member->fresh()->muted_bell_categories);
    }

    public function test_a_member_cannot_switch_off_a_category_he_is_not_offered_or_an_unknown_one(): void
    {
        // Un prestataire coupe « Devis et factures » ; s'il repasse particulier, ce choix est conservé.
        $member = $this->member(['muted_bell_categories' => ['quotes']]);
        $this->actingAs($member)->post(route('settings.bell'), ['bell' => ['new_ads' => '1', 'proposals' => '1', 'boosts' => '1', 'referral' => '1']]);
        $this->assertSame(['quotes'], $member->fresh()->muted_bell_categories);

        // Une valeur inattendue est refusée.
        $this->post(route('settings.bell'), ['bell' => ['new_ads' => 'oui']])->assertSessionHasErrors('bell.new_ads');
    }

    public function test_every_notification_with_a_bell_entry_is_either_switchable_or_explicitly_vital(): void
    {
        $vital = ['AccountStatusNotification', 'AdminReplyNotification', 'NewMessageNotification', 'ServiceOrderRequestedNotification',
            'ServiceOrderStatusNotification', 'ServiceReminderNotification', 'SubscriptionInvoiceNotification',
            'VerificationApproved', 'VerificationDocumentRejected', 'PushTestNotification'];
        $switchable = collect(BellNotifications::categories())->flatMap(fn ($c) => $c['notifications'])
            ->map(fn ($class) => class_basename($class))->all();

        foreach (glob(app_path('Notifications/*.php')) as $file) {
            $name = basename($file, '.php');
            $this->assertTrue(in_array($name, $vital, true) || in_array($name, $switchable, true),
                "{$name} : nouvelle notification à classer dans App\\Support\\BellNotifications (interrupteur) ou parmi les vitales de ce test.");
        }
    }
}
