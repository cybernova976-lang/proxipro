<?php

namespace Tests\Feature\Notifications;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\NewAdMatchingNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\PushTestNotification;
use App\Notifications\ServiceOrderRequestedNotification;
use App\Notifications\ServiceOrderStatusNotification;
use App\Notifications\ServiceProposalReceivedNotification;
use App\Notifications\ServiceProposalStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class WebPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://push.example.test/subscriptions/device-1';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('webpush.vapid.subject', 'https://www.prokejem.fr');
        config()->set('webpush.vapid.public_key', 'test-public-key');
        config()->set('webpush.vapid.private_key', 'test-private-key');
    }

    public function test_push_subscription_routes_require_authentication(): void
    {
        $this->postJson(route('push-subscriptions.store'), $this->subscriptionPayload())
            ->assertUnauthorized();

        $this->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => self::ENDPOINT])
            ->assertUnauthorized();

        $this->postJson(route('push-subscriptions.test'))
            ->assertUnauthorized();
    }

    public function test_user_can_register_update_and_remove_their_device_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.store'), $this->subscriptionPayload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'endpoint' => self::ENDPOINT,
            'public_key' => 'public-key-1',
            'auth_token' => 'auth-token-1',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.store'), $this->subscriptionPayload('public-key-2'))
            ->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => self::ENDPOINT,
            'public_key' => 'public-key-2',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => self::ENDPOINT])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => self::ENDPOINT]);
    }

    public function test_one_user_cannot_delete_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');

        $this->actingAs($otherUser)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => self::ENDPOINT])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $owner->id,
            'endpoint' => self::ENDPOINT,
        ]);
    }

    public function test_test_endpoint_sends_a_push_notification_to_the_authenticated_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.test'))
            ->assertOk()
            ->assertJsonPath('message', 'Notification de test envoyée.');

        Notification::assertSentTo($user, PushTestNotification::class);
    }

    public function test_test_endpoint_requires_a_subscription_on_the_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('push-subscriptions.test'))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_relevant_business_notifications_use_web_push_when_a_device_is_subscribed(): void
    {
        $recipient = User::factory()->create([
            'email_notifications' => false,
            'pro_notifications_email' => false,
            'pro_notifications_realtime' => true,
        ]);
        $sender = User::factory()->create();
        $recipient->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');

        $notifications = [
            new NewMessageNotification(new Message, new Conversation, $sender),
            new ServiceProposalReceivedNotification(new ServiceProposal),
            new ServiceProposalStatusNotification(new ServiceProposal),
            new NewAdMatchingNotification(new Ad, $sender),
            new ServiceOrderRequestedNotification(new ServiceOrder),
            new ServiceOrderStatusNotification(new ServiceOrder, 'accepted'),
        ];

        foreach ($notifications as $notification) {
            $this->assertContains(WebPushChannel::class, $notification->via($recipient));
        }
    }

    public function test_push_payload_contains_the_expected_content_and_safe_destination(): void
    {
        $recipient = User::factory()->create();
        $notification = new PushTestNotification;

        $payload = $notification->toWebPush($recipient, $notification)->toArray();

        $this->assertSame('Notifications Prokejem activées', $payload['title']);
        $this->assertStringContainsString('nouveaux messages, propositions et demandes', $payload['body']);
        $this->assertSame('/pwa/icon-192.png', $payload['icon']);
        $this->assertSame('fr', $payload['lang']);
        $this->assertSame('push_test', $payload['data']['type']);
        $this->assertStringStartsWith(config('app.url'), $payload['data']['url']);
    }

    public function test_soft_deleted_user_loses_all_device_subscriptions(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');

        $user->delete();

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
        ]);
    }

    public function test_push_channel_is_not_used_without_server_keys_or_a_device_subscription(): void
    {
        $recipient = User::factory()->create(['email_notifications' => false]);
        $notification = new NewMessageNotification(new Message, new Conversation, User::factory()->create());

        $this->assertNotContains(WebPushChannel::class, $notification->via($recipient));

        $recipient->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');
        config()->set('webpush.vapid.private_key', null);

        $this->assertNotContains(WebPushChannel::class, $notification->via($recipient));
    }

    public function test_settings_page_exposes_device_notification_controls(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('settings.index'));

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Notifications sur cet appareil', $content);
        $this->assertStringContainsString('Recevez les nouveaux messages, propositions et demandes', $content);
        $this->assertStringContainsString('data-push-notification-controls', $content);
        $this->assertStringContainsString('Notification.requestPermission()', $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(string $publicKey = 'public-key-1'): array
    {
        return [
            'endpoint' => self::ENDPOINT,
            'keys' => [
                'p256dh' => $publicKey,
                'auth' => 'auth-token-1',
            ],
            'content_encoding' => 'aes128gcm',
        ];
    }
}
