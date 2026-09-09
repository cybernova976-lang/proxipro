<?php

namespace Tests\Feature\Demand;

use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use App\Models\User;
use App\Mail\ManagedEmailMail;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_draft_enters_admin_review_once_only_after_twenty_four_hours(): void
    {
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
        $this->assertSame(ManagedEmail::STATUS_PENDING, $email->status);
        $this->assertSame($user->email, $email->recipient_email);
        $this->assertStringContainsString('Réparer une fuite', $email->body);
        $this->assertNotNull($draft->fresh()->review_requested_at);
    }

    public function test_recent_or_opted_out_drafts_are_not_reminded(): void
    {
        $recentUser = User::factory()->create(['email_notifications' => true]);
        $optedOutUser = User::factory()->create(['email_notifications' => false]);

        foreach ([[$recentUser, now()->subHours(23)], [$optedOutUser, now()->subDays(2)]] as [$user, $activity]) {
            DemandDraft::create([
                'user_id' => $user->id,
                'payload' => ['version' => 2, 'title' => 'Demande inachevée'],
                'current_step' => 2,
                'last_activity_at' => $activity,
            ]);
        }

        $this->artisan('demand-drafts:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('managed_emails', 0);
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
        $recipient = User::factory()->create();
        $email = ManagedEmail::create([
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
