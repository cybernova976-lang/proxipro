<?php

namespace Tests\Feature\Mail;

use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmailComposerTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['subject' => 'Actualités', 'headline' => 'Bonjour', 'body' => 'Découvrez Prokejem', 'audience' => 'all'];
    }

    public function test_three_photos_are_saved_and_four_are_rejected(): void
    {
        Mail::fake();
        Storage::fake(config('filesystems.default'));
        $admin = User::factory()->create(['role' => 'admin', 'newsletter_subscribed' => false]);
        User::factory()->create(['newsletter_subscribed' => true, 'email_notifications' => true, 'is_active' => true]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $images = array_map(fn ($i) => UploadedFile::fake()->createWithContent("photo-$i.png", $png), range(1, 3));
        $this->actingAs($admin)->post(route('admin.emails.store'), [...$this->data(), 'images' => $images])->assertRedirect();
        $this->assertCount(3, ManagedEmail::sole()->image_paths);
        foreach (ManagedEmail::sole()->image_paths as $path) {
            Storage::disk(config('filesystems.default'))->assertExists($path);
        }
        $images[] = UploadedFile::fake()->createWithContent('fourth.png', $png);
        $this->postJson(route('admin.emails.store'), [...$this->data(), 'images' => $images])->assertUnprocessable();
        $this->assertDatabaseCount('managed_emails', 1);
        Mail::assertNothingSent();
    }

    public function test_all_prepares_only_eligible_recipients_without_sending(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'newsletter_subscribed' => false]);
        $eligible = User::factory()->create(['newsletter_subscribed' => true, 'email_notifications' => true, 'is_active' => true]);
        User::factory()->create(['newsletter_subscribed' => false]);
        $this->actingAs($admin)->get(route('admin.emails.create'))->assertOk()->assertSee('Studio e-mail');
        $this->post(route('admin.emails.store'), $this->data())->assertRedirect(route('admin.emails.index'));
        $this->assertSame($eligible->id, ManagedEmail::sole()->user_id);
        $this->assertSame('pending', ManagedEmail::sole()->status);
        Mail::assertNothingSent();
    }

    public function test_selected_recipient_and_preferences_rechecked_before_approval(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['newsletter_subscribed' => true, 'email_notifications' => true, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.emails.store'), array_merge($this->data(), ['audience' => 'selected', 'recipients' => [$user->id]]))->assertRedirect();
        $user->update(['newsletter_subscribed' => false]);
        $this->post(route('admin.emails.approve', ManagedEmail::sole()))->assertStatus(409);
        Mail::assertNothingSent();
    }

    public function test_non_admin_cannot_compose_and_invalid_selection_is_rejected(): void
    {
        $user = User::factory()->create(['newsletter_subscribed' => false]);
        $this->actingAs($user)->get(route('admin.emails.create'))->assertForbidden();
        $this->post(route('admin.emails.store'), $this->data())->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.emails.store'), array_merge($this->data(), ['audience' => 'selected', 'recipients' => [$user->id]]))->assertUnprocessable();
        $this->assertDatabaseCount('managed_emails', 0);
    }
}
