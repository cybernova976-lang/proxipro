<?php

namespace Tests\Feature\Demand;

use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use App\Models\User;
use App\Mail\ManagedEmailMail;
use App\Services\GeocodingService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AbandonedDemandReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_sync_a_text_only_demand_draft(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('demand.draft.save'), [
                'current_step' => 3,
                'payload' => [
                    'version' => 2,
                    'main_category' => 'Bricolage & Travaux',
                    'category' => 'Plombier',
                    'title' => 'Réparer une fuite',
                    'description' => 'La fuite se situe sous le lavabo.',
                    'photos' => ['data:image/png;base64,interdit'],
                ],
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $draft = DemandDraft::sole();
        $this->assertSame($user->id, $draft->user_id);
        $this->assertSame(3, $draft->current_step);
        $this->assertSame('Réparer une fuite', $draft->payload['title']);
        $this->assertArrayNotHasKey('photos', $draft->payload);
    }

    public function test_guest_cannot_create_a_server_side_draft(): void
    {
        $this->putJson(route('demand.draft.save'), [
            'current_step' => 2,
            'payload' => ['title' => 'Brouillon invité'],
        ])->assertUnauthorized();

        $this->assertDatabaseCount('demand_drafts', 0);
    }

    public function test_authenticated_user_can_resume_the_server_draft_on_another_device(): void
    {
        $user = User::factory()->create();
        DemandDraft::create([
            'user_id' => $user->id,
            'payload' => [
                'version' => 2,
                'main_category' => 'Bricolage & Travaux',
                'category' => 'Plombier',
                'title' => 'Réparer une fuite distante',
            ],
            'current_step' => 3,
            'last_activity_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('demand.create', ['resume' => 1]));

        $response->assertOk()
            ->assertSee('const serverDemandDraft =', false);
        $this->assertSame('Réparer une fuite distante', $response->viewData('serverDraft')['title']);
        $this->assertSame(3, $response->viewData('serverDraft')['current_step']);
    }

    public function test_due_draft_is_reminded_automatically_once_with_an_unsubscribe_link(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_notifications' => true]);
        $draft = DemandDraft::create([
            'user_id' => $user->id,
            'payload' => ['version' => 2, 'category' => 'Plombier', 'title' => 'Réparer une fuite'],
            'current_step' => 4,
            'last_activity_at' => now()->subHours(24)->subMinute(),
        ]);

        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('managed_emails', 1);
        $email = ManagedEmail::sole();
        $this->assertSame(ManagedEmail::STATUS_SENT, $email->status);
        $this->assertNotNull($email->sent_at);
        $this->assertNull($email->approved_by, 'Aucune validation manuelle n’est attendue.');
        $this->assertTrue($email->metadata['automatic']);
        $this->assertSame(1, $email->metadata['attempts']);
        $this->assertSame($user->email, $email->recipient_email);
        $this->assertNotNull($draft->fresh()->review_requested_at);

        Mail::assertSent(ManagedEmailMail::class, 1);
        Mail::assertSent(ManagedEmailMail::class, fn ($mail) => $mail->hasTo($user->email) && $mail->managedEmail->is($email));
        $html = (new ManagedEmailMail($email))->render();
        $this->assertStringContainsString('Réparer une fuite', $html);
        $this->assertStringContainsString('resume=1', $html);
        $this->assertStringContainsString('Ne plus recevoir de rappels de demande inachevée', $html);
        $this->assertStringContainsString('/communications/rappels-brouillon/'.$user->id.'?signature=', $html);
    }

    public function test_resumed_and_abandoned_again_draft_is_not_reminded_twice(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_notifications' => true]);
        $draft = DemandDraft::create(['user_id' => $user->id, 'payload' => ['title' => 'Réparer une fuite'],
            'current_step' => 2, 'last_activity_at' => now()->subDays(2)]);
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->actingAs($user)->putJson(route('demand.draft.save'), ['current_step' => 3, 'payload' => ['title' => 'Réparer une fuite']])->assertOk();
        $this->travel(3)->days();
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->assertNotNull($draft->fresh()->review_requested_at);
        Mail::assertSent(ManagedEmailMail::class, 1);
    }

    public function test_admin_can_send_due_reminders_now_without_waiting_for_the_scheduler(): void
    {
        Mail::fake();
        $member = User::factory()->create(['email_notifications' => true, 'draft_reminders_enabled' => true]);
        DemandDraft::create(['user_id' => $member->id, 'payload' => ['title' => 'Réparer une fuite'],
            'current_step' => 3, 'last_activity_at' => now()->subHours(25)]);
        $action = route('admin.emails.send-draft-reminders');
        $this->actingAs($member)->post($action)->assertForbidden();
        Mail::assertNothingSent();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.emails.index'))->assertOk()
            ->assertSee('Envoyer les rappels dus')->assertSee('partent automatiquement');
        $this->post($action)->assertRedirect(route('admin.emails.index'))
            ->assertSessionHas('success', '1 rappel(s) envoyé(s).');
        $this->assertSame(ManagedEmail::STATUS_SENT, ManagedEmail::sole()->status);
        Mail::assertSent(ManagedEmailMail::class, 1);

        $this->get(route('admin.emails.index'))->assertDontSee('Envoyer les rappels dus')->assertSee('Rappel automatique');
        $this->post($action)->assertSessionHas('success', 'Aucun rappel de brouillon n’était dû.');
        Mail::assertSent(ManagedEmailMail::class, 1);
    }

    public function test_recent_stale_unverified_or_opted_out_drafts_are_not_reminded(): void
    {
        Mail::fake();
        $cases = [
            [User::factory()->create(['email_notifications' => true]), now()->subHours(23)],
            [User::factory()->create(['email_notifications' => true]), now()->subDays(31)],
            [User::factory()->create(['email_notifications' => false]), now()->subDays(2)],
            [User::factory()->create(['draft_reminders_enabled' => false]), now()->subDays(2)],
            [User::factory()->unverified()->create(), now()->subDays(2)],
        ];

        foreach ($cases as [$user, $activity]) {
            DemandDraft::create([
                'user_id' => $user->id,
                'payload' => ['version' => 2, 'title' => 'Demande inachevée'],
                'current_step' => 2,
                'last_activity_at' => $activity,
            ]);
        }

        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('managed_emails', 0);
        Mail::assertNothingSent();
    }

    public function test_reminder_left_pending_by_the_former_manual_circuit_is_sent_or_cancelled(): void
    {
        Mail::fake();
        $legacy = function (User $user, ?DemandDraft $draft): ManagedEmail {
            $draft?->update(['review_requested_at' => now()]);

            return ManagedEmail::create(['user_id' => $user->id, 'source_key' => 'abandoned-demand:legacy-'.$user->id,
                'type' => 'abandoned_demand', 'recipient_email' => $user->email, 'recipient_name' => $user->name,
                'subject' => 'Votre demande Prokejem est presque terminée', 'headline' => 'Vous y étiez presque',
                'body' => 'Votre demande n’est pas encore publiée.', 'status' => ManagedEmail::STATUS_PENDING,
                'metadata' => ['demand_draft_id' => $draft?->id ?? 999999]]);
        };
        $waiting = User::factory()->create(['email_notifications' => true]);
        $resumed = User::factory()->create(['email_notifications' => true]);
        $removed = User::factory()->create(['email_notifications' => true]);
        $stillDue = $legacy($waiting, DemandDraft::create(['user_id' => $waiting->id, 'payload' => ['title' => 'A'],
            'current_step' => 2, 'last_activity_at' => now()->subDays(2)]));
        $obsolete = $legacy($resumed, DemandDraft::create(['user_id' => $resumed->id, 'payload' => ['title' => 'B'],
            'current_step' => 2, 'last_activity_at' => now()->subHours(2)]));
        $orphan = $legacy($removed, null);

        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->assertSame(ManagedEmail::STATUS_SENT, $stillDue->fresh()->status);
        $this->assertSame(ManagedEmail::STATUS_CANCELLED, $obsolete->fresh()->status);
        $this->assertSame(ManagedEmail::STATUS_CANCELLED, $orphan->fresh()->status);
        Mail::assertSent(ManagedEmailMail::class, 1);
        Mail::assertSent(ManagedEmailMail::class, fn ($mail) => $mail->hasTo($waiting->email));
    }

    public function test_mail_server_failure_is_recorded_then_retried_after_an_hour_three_times_at_most(): void
    {
        $user = User::factory()->create(['email_notifications' => true]);
        DemandDraft::create(['user_id' => $user->id, 'payload' => ['title' => 'Réparer une fuite'],
            'current_step' => 2, 'last_activity_at' => now()->subDays(2)]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Serveur SMTP indisponible'));
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $email = ManagedEmail::sole();
        $this->assertSame(ManagedEmail::STATUS_FAILED, $email->status);
        $this->assertSame('Serveur SMTP indisponible', $email->failure_message);
        $this->assertSame(1, $email->metadata['attempts']);

        // Pas de nouvel essai avant une heure.
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->assertSame(1, $email->fresh()->metadata['attempts']);

        $this->travel(61)->minutes();
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->travel(61)->minutes();
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->assertSame(3, $email->fresh()->metadata['attempts']);

        // Après trois échecs, plus d'essai automatique : l'administrateur peut le relancer à la main.
        $this->travel(61)->minutes();
        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();
        $this->assertSame(3, $email->fresh()->metadata['attempts']);
        $this->assertSame(ManagedEmail::STATUS_FAILED, $email->fresh()->status);
    }

    public function test_signed_link_stops_draft_reminders_in_one_click_without_login(): void
    {
        $user = User::factory()->create(['email_notifications' => true, 'draft_reminders_enabled' => true, 'newsletter_subscribed' => true]);
        $pending = ManagedEmail::create(['user_id' => $user->id, 'source_key' => 'abandoned-demand:pending', 'type' => 'abandoned_demand',
            'recipient_email' => $user->email, 'subject' => 'Rappel', 'headline' => 'Rappel', 'body' => 'Rappel',
            'status' => ManagedEmail::STATUS_PENDING]);
        $url = URL::signedRoute('draft-reminders.unsubscribe', ['user' => $user]);

        $this->get($url)->assertOk()->assertSee('Arrêter les rappels de demande inachevée ?');
        $this->assertTrue($user->fresh()->draft_reminders_enabled, 'Ouvrir le lien ne doit rien modifier.');
        $this->post(route('draft-reminders.unsubscribe.store', $user))->assertForbidden();
        $this->assertTrue($user->fresh()->draft_reminders_enabled);

        // Bouton « Se désabonner » de la messagerie (RFC 8058) : POST sans session.
        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertRedirect($url);
        $this->assertFalse($user->fresh()->draft_reminders_enabled);
        $this->assertTrue($user->fresh()->email_notifications);
        $this->assertTrue($user->fresh()->newsletter_subscribed);
        $this->assertSame(ManagedEmail::STATUS_CANCELLED, $pending->fresh()->status);
        $this->get($url)->assertOk()->assertSee('Vous ne recevrez plus de rappels de demande inachevée.');

        // La messagerie n'a pas de jeton de session : ces adresses sont exemptées du contrôle CSRF.
        $this->assertContains('communications/*', app(ValidateCsrfToken::class)->getExcludedPaths());
    }

    public function test_reminders_and_newsletters_carry_one_click_unsubscribe_headers(): void
    {
        $user = User::factory()->create();
        $make = fn (string $type) => ManagedEmail::create(['user_id' => $user->id, 'source_key' => 'headers-'.$type, 'type' => $type,
            'recipient_email' => $user->email, 'subject' => 'Objet', 'headline' => 'Titre', 'body' => 'Texte', 'status' => ManagedEmail::STATUS_PENDING]);

        $mailer = Mail::mailer('array');
        foreach (['abandoned_demand', 'newsletter', 'transactional'] as $type) {
            $mailer->to($user->email)->send(new ManagedEmailMail($make($type)));
        }
        $headers = $mailer->getSymfonyTransport()->messages()
            ->map(fn ($sent) => $sent->getOriginalMessage()->getHeaders())->values();

        $this->assertStringContainsString('/communications/rappels-brouillon/'.$user->id.'?', $headers[0]->get('List-Unsubscribe')->getBodyAsString());
        $this->assertStringContainsString('/communications/newsletter/'.$user->id.'?', $headers[1]->get('List-Unsubscribe')->getBodyAsString());
        foreach ([0, 1] as $index) {
            $this->assertSame('List-Unsubscribe=One-Click', $headers[$index]->get('List-Unsubscribe-Post')->getBodyAsString());
        }
        $this->assertNull($headers[2]->get('List-Unsubscribe'), 'Pas de lien de désinscription sur un message de service.');
    }

    public function test_managed_email_uses_the_custom_resume_template(): void
    {
        $user = User::factory()->create(['name' => 'Haradali']);
        $email = ManagedEmail::create([
            'user_id' => $user->id,
            'source_key' => 'test-template',
            'type' => 'abandoned_demand',
            'recipient_email' => $user->email,
            'recipient_name' => $user->name,
            'subject' => 'Votre demande est presque terminée',
            'eyebrow' => 'Plombier · Demande en cours',
            'headline' => 'Vous y étiez presque, Haradali',
            'body' => 'Votre demande « Réparer une fuite » est encore disponible.',
            'cta_label' => 'Terminer ma demande',
            'cta_url' => route('demand.create', ['resume' => 1]),
            'status' => ManagedEmail::STATUS_PENDING,
        ]);

        $mail = new ManagedEmailMail($email);

        $this->assertStringContainsString('Réparer une fuite', $mail->render());
        $this->assertStringContainsString('resume=1', $mail->render());
    }

    public function test_admin_can_edit_add_images_preview_and_approve_a_managed_email(): void
    {
        Storage::fake('public');
        Mail::fake();
        config(['filesystems.default' => 'public']);
        $admin = User::factory()->create(['role' => 'admin']);
        $recipient = User::factory()->create(['email_notifications' => true, 'is_active' => true]);
        $draft = DemandDraft::create(['user_id' => $recipient->id, 'payload' => ['title' => 'Demande en attente'], 'current_step' => 2, 'last_activity_at' => now()->subDays(2)]);
        $email = ManagedEmail::create([
            'metadata' => ['demand_draft_id' => $draft->id],
            'user_id' => $recipient->id,
            'source_key' => 'admin-review-test',
            'type' => 'abandoned_demand',
            'recipient_email' => $recipient->email,
            'recipient_name' => $recipient->name,
            'subject' => 'Ancien objet',
            'headline' => 'Ancien titre',
            'body' => 'Ancien message',
            'status' => ManagedEmail::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->get(route('admin.emails.index'))->assertOk()->assertSee('Ancien objet');
        $this->actingAs($admin)->put(route('admin.emails.update', $email), [
            'subject' => 'Nouvel objet contrôlé',
            'eyebrow' => 'Demande en cours',
            'headline' => 'Reprenez votre demande',
            'body' => 'Message relu et validable.',
            'cta_label' => 'Continuer',
            'cta_url' => route('demand.create', ['resume' => 1]),
            'images' => [UploadedFile::fake()->createWithContent(
                'illustration.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            )],
        ])->assertRedirect();

        $email->refresh();
        $this->assertSame('Nouvel objet contrôlé', $email->subject);
        $this->assertCount(1, $email->image_paths);
        Storage::disk('public')->assertExists($email->image_paths[0]);
        $this->actingAs($admin)->get(route('admin.emails.preview', $email))->assertOk()->assertSee('Message relu et validable.');

        $this->actingAs($admin)->post(route('admin.emails.approve', $email))->assertRedirect();
        Mail::assertSent(ManagedEmailMail::class, fn ($mail) => $mail->managedEmail->is($email));
        $this->assertSame(ManagedEmail::STATUS_SENT, $email->fresh()->status);
    }

    public function test_publishing_the_demand_removes_its_server_draft(): void
    {
        Notification::fake();
        $this->mock(GeocodingService::class, function ($mock): void {
            $mock->shouldReceive('geocode')->once()->andReturn(null);
        });
        $user = User::factory()->create();
        DemandDraft::create([
            'user_id' => $user->id,
            'payload' => ['version' => 2, 'title' => 'Réparer une fuite'],
            'current_step' => 5,
            'last_activity_at' => now(),
        ]);

        $this->actingAs($user)->post(route('demand.store'), [
            'main_category' => 'Bricolage & Travaux',
            'category' => 'Plombier',
            'country' => 'Mayotte',
            'city' => 'Mamoudzou',
            'location' => 'Mamoudzou',
            'desired_date' => today()->addDays(3)->toDateString(),
            'time_window' => 'morning',
            'title' => 'Réparer une fuite après abandon',
            'description' => 'Une fuite légère apparaît sous le lavabo lorsque le robinet est ouvert.',
            'price_type' => 'negotiable',
            'service_details' => ['work_scope' => 'repair', 'site_type' => 'house'],
            'publication_confirmed' => '1',
        ])->assertRedirect();

        $this->assertDatabaseMissing('demand_drafts', ['user_id' => $user->id]);
    }
}
