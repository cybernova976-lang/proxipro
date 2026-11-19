<?php

namespace Tests\Feature\Mail;

use App\Jobs\SendManagedEmail;
use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use App\Models\User;
use App\Support\LaunchEmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Lot 4 : les deux messages d'ouverture. Le message de service part à tous les
 * comptes actifs et ne vend rien ; l'argumentaire ne part qu'aux inscrits à la
 * newsletter. Un lot se valide d'un seul geste et part en arrière-plan.
 */
class LaunchEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => false]);
    }

    private function account(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['is_active' => true, 'email_verified_at' => now(),
            'newsletter_subscribed' => false, 'email_notifications' => false], $overrides));
    }

    private function layout(array $blocks): string
    {
        return json_encode($blocks, JSON_UNESCAPED_UNICODE);
    }

    private function message(array $overrides = []): array
    {
        return array_merge(['subject' => 'Ouverture', 'headline' => 'Merci', 'audience' => 'all',
            'content_layout' => $this->layout([['type' => 'text', 'text' => 'Voici le mode d’emploi.']])], $overrides);
    }

    public function test_a_service_message_reaches_every_active_account_and_sells_nothing(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $silent = $this->account();
        $subscribed = $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $this->account(['is_active' => false]);
        $this->account(['email_verified_at' => null]);

        $this->actingAs($admin)->get(route('admin.emails.create', ['type' => 'service']))->assertOk()
            ->assertSee('name="type" value="service"', false)
            ->assertSee('2 comptes actifs avec une adresse vérifiée, quelles que soient leurs préférences de newsletter.')
            ->assertSee('1 compte actif n’a pas confirmé son adresse et n’est pas joignable.')
            ->assertDontSee('id="add-comparison-block"', false);

        // Le comparatif des offres est refusé dans un message de service.
        $this->postJson(route('admin.emails.store'), $this->message(['type' => 'service', 'content_layout' => $this->layout([
            ['type' => 'text', 'text' => 'Bonjour'], ['type' => 'comparison'],
        ])]))->assertUnprocessable()->assertJsonValidationErrors('content_layout');
        $this->assertDatabaseCount('managed_emails', 0);

        $this->post(route('admin.emails.store'), $this->message(['type' => 'service']))->assertRedirect(route('admin.emails.index'));
        $this->assertEqualsCanonicalizing([$silent->id, $subscribed->id], ManagedEmail::pluck('user_id')->all());
        $this->assertSame(['service'], ManagedEmail::distinct()->pluck('type')->all());
        Mail::assertNothingSent();

        // Pas de lien de désinscription de newsletter, et la raison de l'envoi est dite.
        $email = ManagedEmail::where('user_id', $silent->id)->sole();
        $mail = new ManagedEmailMail($email);
        $html = $mail->render();
        $this->assertStringContainsString('Vous recevez ce message d’information parce que vous avez un compte Prokejem.', $html);
        $this->assertStringNotContainsString('communications/newsletter', $html);
        $this->assertSame([], $mail->headers()->text);

        // Le comparatif ne s'ajoute pas non plus en modifiant le message.
        $this->putJson(route('admin.emails.update', $email), ['subject' => 'Ouverture', 'headline' => 'Merci',
            'content_layout' => $this->layout([['type' => 'text', 'text' => 'Bonjour'], ['type' => 'comparison']])])
            ->assertUnprocessable();

        // Compte désactivé entre-temps : l'envoi individuel est refusé.
        $silent->forceFill(['is_active' => false])->save();
        $this->post(route('admin.emails.approve', $email))->assertStatus(409);
        Mail::assertNothingSent();
    }

    public function test_the_sales_message_carries_the_live_comparison_to_subscribers_only(): void
    {
        Mail::fake();
        $subscribed = $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $this->account();

        $this->actingAs($this->admin())->get(route('admin.emails.create'))->assertOk()
            ->assertSee('name="type" value="newsletter"', false)->assertSee('id="add-comparison-block"', false);

        $this->post(route('admin.emails.store'), $this->message(['content_layout' => $this->layout([
            ['type' => 'text', 'text' => 'Pourquoi payer ?'], ['type' => 'comparison'], ['type' => 'comparison'],
        ])]))->assertRedirect();

        $email = ManagedEmail::sole();
        $this->assertSame($subscribed->id, $email->user_id);
        $this->assertSame('newsletter', $email->type);
        // Un seul comparatif, et aucun prix enregistré avec le message.
        $this->assertSame([['type' => 'text', 'text' => 'Pourquoi payer ?'], ['type' => 'comparison']], $email->metadata['content_blocks']);

        $html = (new ManagedEmailMail($email))->render();
        foreach (['Gratuit', 'À la carte', 'Premium', 'De 4,00 € (3 jours) à 15,00 € (30 jours), par offre',
            'Votre logo sur vos devis et vos factures', 'ne garantit ni demande, ni mission, ni revenu'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString('communications/newsletter', $html);

        // Les prix sont ceux du jour de l'envoi : ils suivent un changement de règles.
        config(['feed.sponsored.effective_at' => now()->subDay()->format('Y-m-d')]);
        $this->assertStringContainsString('5,90 € (7 jours) ou 14,90 € (30 jours), par offre', (new ManagedEmailMail($email))->render());
    }

    public function test_launch_templates_prefill_the_studio_with_the_right_audience(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.emails.create', ['modele' => 'lancement-service']))->assertOk()
            ->assertSee('name="type" value="service"', false)
            ->assertSee('Prokejem ouvre officiellement : votre mode d’emploi')
            ->assertSee('Merci d’être là depuis le début')
            ->assertSee('VOUS CHERCHEZ UN PRESTATAIRE');

        $this->get(route('admin.emails.create', ['modele' => 'lancement-offres']))->assertOk()
            ->assertSee('name="type" value="newsletter"', false)
            ->assertSee('Publier est gratuit sur Prokejem. Alors, pourquoi payer ?')
            ->assertSee('{"type":"comparison"}', false);

        // Le message de service ne contient ni comparatif, ni prix, ni nom d'une offre payante.
        $service = LaunchEmailTemplates::get('lancement-service');
        $text = collect($service['blocks'])->map(fn ($block) => $block['text'] ?? $block['label'] ?? '')->implode(' ');
        $this->assertNotContains('comparison', array_column($service['blocks'], 'type'));
        foreach (['Premium', 'abonnement', 'Sponsorisé', 'À la une', '€'] as $word) {
            $this->assertStringNotContainsString($word, $text);
        }
        // Les deux modèles tiennent dans la limite de texte du studio.
        foreach (LaunchEmailTemplates::all() as $template) {
            $body = collect($template['blocks'])->where('type', 'text')->pluck('text')->implode("\n\n");
            $this->assertLessThanOrEqual(5000, mb_strlen($body));
        }
        $this->assertNull(LaunchEmailTemplates::get('inconnu'));
    }

    public function test_a_batch_is_approved_once_and_sent_in_the_background(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $first = $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $second = $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);
        $leaving = $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);

        $this->actingAs($admin)->post(route('admin.emails.store'), $this->message())->assertRedirect();
        $batch = ManagedEmail::first()->metadata['batch'];
        $this->assertSame(3, ManagedEmail::where('status', 'pending')->count());

        $this->get(route('admin.emails.index'))->assertOk()
            ->assertSee('Lot à valider')->assertSee('Valider et envoyer les 3 messages')
            ->assertSee(route('admin.emails.batch.approve', $batch), false);

        // Un compte ordinaire ne valide pas un lot.
        $this->actingAs($first)->post(route('admin.emails.batch.approve', $batch))->assertForbidden();
        $this->assertSame(3, ManagedEmail::where('status', 'pending')->count());

        // Désinscription entre la préparation et l'envoi : respectée.
        $leaving->update(['newsletter_subscribed' => false]);

        $this->actingAs($admin)->post(route('admin.emails.batch.approve', $batch))
            ->assertRedirect(route('admin.emails.index', ['status' => 'sending']))->assertSessionHas('success');

        Mail::assertSent(ManagedEmailMail::class, 2);
        $this->assertSame('sent', ManagedEmail::where('user_id', $first->id)->value('status'));
        $this->assertSame('sent', ManagedEmail::where('user_id', $second->id)->value('status'));
        $cancelled = ManagedEmail::where('user_id', $leaving->id)->sole();
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertStringContainsString('rien n’a été transmis', $cancelled->failure_message);
        $this->assertSame($admin->id, ManagedEmail::where('user_id', $first->id)->value('approved_by'));

        // Second clic : plus rien à valider, aucun second envoi.
        $this->post(route('admin.emails.batch.approve', $batch))->assertSessionHas('error');
        Mail::assertSent(ManagedEmailMail::class, 2);
        $this->get(route('admin.emails.index'))->assertOk()->assertDontSee('Lot à valider');
    }

    public function test_batch_messages_are_spaced_out_and_a_batch_can_be_cancelled(): void
    {
        Queue::fake();
        Mail::fake();
        config(['mail.managed_batch_interval' => 20]);
        $admin = $this->admin();
        foreach (range(1, 3) as $i) {
            $this->account(['newsletter_subscribed' => true, 'email_notifications' => true]);
        }

        $this->actingAs($admin)->post(route('admin.emails.store'), $this->message())->assertRedirect();
        $batch = ManagedEmail::first()->metadata['batch'];
        $this->freezeTime();
        $this->post(route('admin.emails.batch.approve', $batch))->assertSessionHas('success');

        $this->assertSame(3, ManagedEmail::where('status', 'sending')->count());
        $delays = [];
        Queue::assertPushed(SendManagedEmail::class, function (SendManagedEmail $job) use (&$delays) {
            $delays[] = now()->diffInSeconds($job->delay);

            return true;
        });
        sort($delays);
        $this->assertEquals([0, 20, 40], array_map('intval', $delays));
        Mail::assertNothingSent();

        // Un autre lot, annulé avant validation : rien ne part.
        $this->post(route('admin.emails.store'), $this->message(['subject' => 'Second lot']))->assertRedirect();
        $second = ManagedEmail::where('subject', 'Second lot')->first()->metadata['batch'];
        $this->post(route('admin.emails.batch.cancel', $second))->assertSessionHas('success');
        $this->assertSame(3, ManagedEmail::where('subject', 'Second lot')->where('status', 'cancelled')->count());
        $this->post(route('admin.emails.batch.approve', $second))->assertSessionHas('error');
        Queue::assertPushed(SendManagedEmail::class, 3);

        // Un identifiant de lot mal formé n'atteint pas le contrôleur.
        $this->post('/admin/emails/lots/pas-un-lot/approve')->assertNotFound();
    }
}
