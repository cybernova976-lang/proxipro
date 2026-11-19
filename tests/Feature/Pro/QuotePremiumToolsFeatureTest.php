<?php

namespace Tests\Feature\Pro;

use App\Models\ProQuote;
use App\Models\ProQuoteTemplate;
use App\Models\ProSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\QuoteReminderSentNotification;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Services Premium des devis : modèles réutilisables et relance automatique
 * unique d'un devis resté sans réponse.
 */
class QuotePremiumToolsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_are_a_premium_service_and_never_copy_client_data(): void
    {
        $user = $this->professional();
        $quote = $this->quote($user, ['conditions' => 'Acompte de 30 % à la commande.', 'deposit_percentage' => 30]);

        // Sans abonnement : présentation, aucun enregistrement.
        $this->actingAs($user)->get(route('pro.quotes.show', $quote))->assertOk()
            ->assertSee('Voir Premium')->assertDontSee('Enregistrer comme modèle');
        $this->post(route('pro.quotes.template.store', $quote), ['name' => 'Peinture type'])
            ->assertRedirect(route('pro.subscription'));
        $this->assertDatabaseCount('pro_quote_templates', 0);

        $this->subscribe($user);
        $this->get(route('pro.quotes.show', $quote))->assertOk()->assertSee('Enregistrer comme modèle');
        $this->post(route('pro.quotes.template.store', $quote), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('pro.quotes.template.store', $quote), ['name' => 'Peinture type'])->assertSessionHas('success');

        $template = ProQuoteTemplate::sole();
        $this->assertSame($user->id, $template->user_id);
        $this->assertSame('Travaux de peinture', $template->subject);
        $this->assertEquals([['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]], $template->items);
        $this->assertSame('Acompte de 30 % à la commande.', $template->conditions);
        $this->assertStringNotContainsString('client@example.test', json_encode($template->getAttributes()));
        $this->assertStringNotContainsString('Client Exemple', json_encode($template->getAttributes()));

        $this->get(route('pro.quotes'))->assertOk()->assertSee('Peinture type')->assertSee('Utiliser ce modèle');
        $this->get(route('pro.quotes.create', ['modele' => $template->id]))->assertOk()
            ->assertSee('Formulaire prérempli avec le modèle « Peinture type »')
            ->assertSee('id="pkQuoteTemplate"', false)
            ->assertSee('"subject":"Travaux de peinture"', false)
            ->assertSee('"unit_price":50', false)
            ->assertSee('js/pro-quote-template.js', false);
        $this->assertDatabaseCount('pro_quotes', 1);

        // Le modèle d'un autre prestataire reste invisible.
        $stranger = $this->professional();
        $this->subscribe($stranger);
        $this->actingAs($stranger)->get(route('pro.quotes.create', ['modele' => $template->id]))->assertNotFound();
        $this->delete(route('pro.quote-templates.destroy', $template->id))->assertNotFound();
        $this->post(route('pro.quotes.template.store', $quote), ['name' => 'Copie'])->assertNotFound();

        // Abonnement terminé : les modèles sont conservés, leur utilisation est suspendue, la suppression reste possible.
        ProSubscription::where('user_id', $user->id)->update(['ends_at' => now()->subDay()]);
        $this->actingAs($user)->get(route('pro.quotes'))->assertOk()
            ->assertSee('Vos modèles sont conservés')->assertDontSee('Utiliser ce modèle');
        $this->get(route('pro.quotes.create', ['modele' => $template->id]))->assertRedirect(route('pro.subscription'));
        $this->delete(route('pro.quote-templates.destroy', $template->id))->assertSessionHas('success');
        $this->assertDatabaseCount('pro_quote_templates', 0);
    }

    public function test_template_count_is_capped(): void
    {
        $user = $this->professional();
        $this->subscribe($user);
        $quote = $this->quote($user);
        foreach (range(1, ProQuoteTemplate::LIMIT) as $i) {
            $user->proQuoteTemplates()->create(['name' => "Modèle {$i}", 'subject' => 'Sujet', 'items' => []]);
        }

        $this->actingAs($user)->post(route('pro.quotes.template.store', $quote), ['name' => 'Un de trop'])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('pro_quote_templates', ProQuoteTemplate::LIMIT);
    }

    public function test_sending_a_quote_by_email_plans_one_reminder_for_premium_only(): void
    {
        $premium = $this->professional();
        $this->subscribe($premium);
        $free = $this->professional();

        $planned = $this->quote($premium, ['status' => 'draft', 'sent_at' => null]);
        $this->actingAs($premium)->get(route('pro.quotes.show', $planned))->assertOk()->assertSee('name="auto_reminder"', false);
        $this->post(route('pro.quotes.sendEmail', $planned), ['email' => 'autre-adresse@example.test', 'auto_reminder' => '1'])
            ->assertSessionHas('success');
        $planned->refresh();
        $this->assertSame('sent', $planned->status);
        $this->assertSame('autre-adresse@example.test', $planned->sent_to_email);
        $this->assertTrue($planned->reminder_enabled);
        $this->get(route('pro.quotes.show', $planned))->assertSee('Relance automatique prévue le '.now()->addDays(7)->format('d/m/Y'));

        // Le prestataire peut annuler puis réactiver la relance.
        $this->put(route('pro.quotes.reminder', $planned), ['enabled' => '0'])->assertSessionHas('success');
        $this->assertFalse($planned->fresh()->reminder_enabled);
        $this->put(route('pro.quotes.reminder', $planned), ['enabled' => '1'])->assertSessionHas('success');
        $this->assertTrue($planned->fresh()->reminder_enabled);

        // Case décochée : aucune relance.
        $declined = $this->quote($premium, ['status' => 'draft', 'sent_at' => null]);
        $this->post(route('pro.quotes.sendEmail', $declined), ['email' => 'client@example.test', 'auto_reminder' => '0']);
        $this->assertFalse($declined->fresh()->reminder_enabled);

        // Sans abonnement : la case n'est pas proposée et un champ forgé est ignoré.
        $unpaid = $this->quote($free, ['status' => 'draft', 'sent_at' => null]);
        $this->actingAs($free)->get(route('pro.quotes.show', $unpaid))->assertOk()->assertDontSee('name="auto_reminder"', false);
        $this->post(route('pro.quotes.sendEmail', $unpaid), ['email' => 'client@example.test', 'auto_reminder' => '1']);
        $this->assertFalse($unpaid->fresh()->reminder_enabled);
        $this->put(route('pro.quotes.reminder', $unpaid), ['enabled' => '1'])->assertRedirect(route('pro.subscription'));
        $this->assertFalse($unpaid->fresh()->reminder_enabled);
    }

    public function test_reminder_is_sent_once_after_seven_days_only_while_the_quote_awaits_an_answer(): void
    {
        $owner = $this->professional(['company_name' => 'Atelier du Lagon']);
        $this->subscribe($owner);
        $lapsed = $this->professional();
        ProSubscription::create(['user_id' => $lapsed->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDay()]);

        $sent = fn (array $extra = [], ?User $user = null) => $this->quote($user ?? $owner, array_merge([
            'sent_at' => now()->subDays(8), 'sent_to_email' => 'client@example.test', 'reminder_enabled' => true], $extra));
        $due = $sent(['sent_to_email' => 'attente@example.test']);
        $cases = [
            'trop récent' => $sent(['sent_at' => now()->subDays(6)]),
            'accepté' => $sent(['status' => 'accepted', 'accepted_at' => now()->subDay()]),
            'refusé' => $sent(['status' => 'refused']),
            'validité passée' => $sent(['valid_until' => now()->subDay()]),
            'relance non demandée' => $sent(['reminder_enabled' => false]),
            'envoyé par messagerie' => $sent(['sent_to_email' => null]),
            'trop ancien' => $sent(['sent_at' => now()->subDays(22)]),
            'abonnement terminé' => $sent([], $lapsed),
        ];

        $this->artisan('pro-quotes:send-reminders')->assertSuccessful();
        $this->artisan('pro-quotes:send-reminders')->assertSuccessful();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages, 'Une seule relance, pour le seul devis encore en attente.');
        $email = $messages->first()->getOriginalMessage();
        $this->assertSame('attente@example.test', $email->getTo()[0]->getAddress());
        $this->assertCount(1, $email->getReplyTo(), 'Les réponses vont au prestataire, pas à la plateforme.');
        $this->assertSame($owner->email, $email->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('Rappel — Devis '.$due->quote_number, $email->getSubject());
        $this->assertStringContainsString('rappel unique', $email->getHtmlBody());
        $this->assertStringContainsString('Atelier du Lagon', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());

        $this->assertNotNull($due->fresh()->reminder_sent_at);
        foreach ($cases as $label => $quote) {
            $this->assertNull($quote->fresh()->reminder_sent_at, $label);
        }
        $notification = $owner->notifications()->where('type', QuoteReminderSentNotification::class)->sole();
        $this->assertSame($due->id, $notification->data['quote_id']);

        // Après la relance : affichée sur le devis, et pas de seconde relance possible.
        $this->actingAs($owner)->get(route('pro.quotes.show', $due))->assertOk()
            ->assertSee('Relance envoyée à attente@example.test')->assertDontSee('name="auto_reminder"', false);
        $this->put(route('pro.quotes.reminder', $due), ['enabled' => '1'])->assertSessionHas('error');
    }

    public function test_reminders_are_scheduled_hourly_and_never_at_night(): void
    {
        $eventAt = function (string $utc) {
            $this->travelTo(\Illuminate\Support\Carbon::parse("2026-11-13 {$utc}:00", 'UTC'));
            $this->app->instance(Schedule::class, new Schedule());
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            require base_path('routes/console.php');

            return collect(app(Schedule::class)->events())
                ->first(fn ($event) => str_contains((string) $event->command, 'pro-quotes:send-reminders'));
        };

        $this->assertSame('0 * * * *', $eventAt('10:00')->expression);
        // 04:00 UTC = 07:00 à Mayotte ; 05:00 UTC = 08:00 ; 17:00 UTC = 20:00 ; 18:00 UTC = 21:00.
        foreach (['04:00' => false, '05:00' => true, '17:00' => true, '18:00' => false] as $utc => $allowed) {
            $this->assertSame($allowed, $eventAt($utc)->filtersPass($this->app), "Passage de {$utc} UTC.");
        }
        $this->travelBack();
    }

    public function test_new_services_are_listed_and_added_to_an_untouched_stored_plan_only(): void
    {
        $new = config('provider_subscriptions.plans.monthly.features');
        $this->assertContains('Modèles de devis et relance automatique des devis sans réponse (comptes professionnels)', $new);
        // Liste par défaut d'avant le 13/11/2026, telle que la migration la reconnaît.
        $previous = [
            'Badge « Premium » sur votre profil public',
            'Vos offres signalées « Sponsorisé » et affichées avant les offres non sponsorisées sur l’accueil des clients, sans place fixe',
            'Profil présenté avant celui des prestataires non abonnés dans la liste proposée après une demande',
            'Gestion de clientèle et Espace Pro',
            'Historique de facturation Stripe',
            'Jusqu\'à 4 photos par annonce',
            'Réduction de 20 % sur les boosts et l’option Urgent',
            'Alertes dans la plateforme',
        ];
        $migration = require database_path('migrations/2026_11_13_100100_add_premium_services_to_stored_subscription_features.php');

        Setting::set('provider_subscription_plans', json_encode(['monthly' => ['amount' => 12.34, 'features' => $previous]], JSON_UNESCAPED_UNICODE), 'subscriptions');
        $migration->up();
        $this->assertSame($new, ProviderSubscriptionPlans::get('monthly')['features']);
        $this->assertSame(12.34, ProviderSubscriptionPlans::get('monthly')['amount']);

        Setting::set('provider_subscription_plans', json_encode(['monthly' => ['features' => ['Catalogue personnalisé']]], JSON_UNESCAPED_UNICODE), 'subscriptions');
        $migration->up();
        $this->assertSame(['Catalogue personnalisé'], ProviderSubscriptionPlans::get('monthly')['features']);

        $this->get(route('public.provider-offers'))->assertOk()
            ->assertSee('Rapport d’activité mensuel')->assertSee('Modèles de devis et relance des devis sans réponse');
    }

    private function professional(array $overrides = []): User
    {
        return User::factory()->completeForVerification()->create(array_merge([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_service_provider' => true,
            'identity_verified' => true, 'company_name' => 'Entreprise Test', 'siret' => '12345678900012',
            'business_type' => 'entreprise', 'is_active' => true,
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ], $overrides));
    }

    private function subscribe(User $user): void
    {
        ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    private function quote(User $user, array $overrides = []): ProQuote
    {
        static $number = 0;
        $number++;

        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'DEV-TEST-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'currency' => 'EUR', 'is_free' => true,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'sent_at' => now(), 'issued_at' => now(), 'valid_until' => now()->addDays(30)], $overrides));
    }
}
