<?php

namespace Tests\Feature\ServiceOrder;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une commande en cours, terminée ou remboursée ne disparaît jamais parce
 * qu'une annonce ou un compte est supprimé : la base efface les commandes
 * d'une annonce supprimée et d'un compte purgé.
 */
class OrdersSurviveDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function ad(User $owner, array $attributes = []): Ad
    {
        return Ad::create($attributes + [
            'user_id' => $owner->id, 'title' => 'Réparer une fuite sous l’évier',
            'description' => 'Fuite sous l’évier de la cuisine, à réparer rapidement.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou', 'postal_code' => '97600',
            'service_type' => 'demande', 'status' => 'active', 'visibility' => 'public',
        ]);
    }

    private function order(Ad $ad, User $buyer, User $seller, string $status, string $paymentStatus): ServiceOrder
    {
        return ServiceOrder::create([
            'order_number' => 'CMD-TEST-'.fake()->unique()->numerify('#####'),
            'ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'amount' => 100, 'commission_amount' => 10, 'seller_amount' => 90,
            'status' => $status, 'payment_status' => $paymentStatus,
        ]);
    }

    public function test_an_ad_with_an_order_in_progress_cannot_be_deleted(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();

        foreach ([
            [ServiceOrder::STATUS_PENDING_ACCEPTANCE, ServiceOrder::PAYMENT_AWAITING],
            [ServiceOrder::STATUS_AWAITING_PAYMENT, ServiceOrder::PAYMENT_AWAITING],
            [ServiceOrder::STATUS_FUNDED, ServiceOrder::PAYMENT_PAID],
            [ServiceOrder::STATUS_DISPUTED, ServiceOrder::PAYMENT_DISPUTED],
        ] as [$status, $paymentStatus]) {
            $ad = $this->ad($client);
            $order = $this->order($ad, $client, $provider, $status, $paymentStatus);

            $this->actingAs($client)->delete(route('ads.destroy', $ad))
                ->assertRedirect()
                ->assertSessionHas('info', fn ($message) => str_contains($message, 'porte une commande en cours'));

            $this->assertDatabaseHas('ads', ['id' => $ad->id, 'status' => 'active']);
            $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'status' => $status]);
        }
    }

    public function test_an_ad_with_a_finished_order_is_unpublished_instead_of_deleted(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();

        foreach ([
            [ServiceOrder::STATUS_COMPLETED, ServiceOrder::PAYMENT_RELEASED],
            [ServiceOrder::STATUS_REFUNDED, ServiceOrder::PAYMENT_REFUNDED],
        ] as [$status, $paymentStatus]) {
            $ad = $this->ad($client);
            $order = $this->order($ad, $client, $provider, $status, $paymentStatus);

            $this->actingAs($client)->delete(route('ads.destroy', $ad))
                ->assertRedirect()
                ->assertSessionHas('info', fn ($message) => str_contains($message, 'retirée de la publication'));

            $this->assertDatabaseHas('ads', ['id' => $ad->id, 'status' => 'inactive']);
            $this->assertDatabaseHas('service_orders', ['id' => $order->id]);
        }
    }

    public function test_an_ad_without_any_order_to_keep_is_still_deleted(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();
        $free = $this->ad($client);
        $withClosedOrders = $this->ad($client);
        $this->order($withClosedOrders, $client, $provider, ServiceOrder::STATUS_CANCELED, ServiceOrder::PAYMENT_CANCELED);
        $this->order($withClosedOrders, $client, $provider, ServiceOrder::STATUS_REFUSED, ServiceOrder::PAYMENT_CANCELED);

        // Depuis la page de l'annonce, on revient à l'accueil avec un message juste.
        $this->actingAs($client)->from(route('ads.show', $free))->delete(route('ads.destroy', $free))
            ->assertRedirect(route('feed'))->assertSessionHas('info', 'Annonce supprimée.');
        $this->actingAs($client)->from(route('feed'))->delete(route('ads.destroy', $withClosedOrders))
            ->assertRedirect(route('feed'))->assertSessionHas('success', 'Annonce supprimée avec succès.');

        $this->assertDatabaseMissing('ads', ['id' => $free->id]);
        $this->assertDatabaseMissing('ads', ['id' => $withClosedOrders->id]);
    }

    public function test_no_code_path_can_delete_an_ad_that_carries_a_kept_order(): void
    {
        $client = User::factory()->create();
        $ad = $this->ad($client);
        $order = $this->order($ad, $client, User::factory()->create(), ServiceOrder::STATUS_FUNDED, ServiceOrder::PAYMENT_PAID);

        // Dernier rempart, au niveau du modèle.
        $this->assertFalse($ad->delete());
        $this->assertDatabaseHas('service_orders', ['id' => $order->id]);

        // L'administration dépublie au lieu de supprimer.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete(route('admin.ads.delete', $ad->id))
            ->assertRedirect(route('admin.ads'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'retirée de la publication'));
        $this->assertDatabaseHas('ads', ['id' => $ad->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('service_orders', ['id' => $order->id]);
    }

    public function test_an_account_with_an_order_in_progress_cannot_be_deleted(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();
        $order = $this->order($this->ad($client), $client, $provider, ServiceOrder::STATUS_FUNDED, ServiceOrder::PAYMENT_PAID);

        // Ni le client ni le prestataire ne peuvent partir en laissant la commande ouverte.
        foreach ([$client, $provider] as $user) {
            $this->actingAs($user)->post(route('settings.delete-account'), ['password' => 'password', 'confirm_delete' => '1'])
                ->assertSessionHasErrors(['delete' => 'Vous avez une commande en cours. Terminez-la ou annulez-la avant de supprimer votre compte.']);
            $this->assertNull($user->fresh()->deleted_at);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete(route('admin.users.delete', $provider->id))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'commande en cours'));
        $this->assertNull($provider->fresh()->deleted_at);
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'status' => ServiceOrder::STATUS_FUNDED]);
    }

    public function test_deleting_an_account_keeps_its_finished_orders(): void
    {
        $client = User::factory()->create();
        $provider = User::factory()->create();
        $keptAd = $this->ad($client, ['title' => 'Demande avec commande terminée']);
        $order = $this->order($keptAd, $client, $provider, ServiceOrder::STATUS_COMPLETED, ServiceOrder::PAYMENT_RELEASED);
        $plainAd = $this->ad($client, ['title' => 'Demande sans commande']);

        $this->actingAs($client)->post(route('settings.delete-account'), ['password' => 'password', 'confirm_delete' => '1'])
            ->assertRedirect(route('homepage'));

        $this->assertNotNull(User::withTrashed()->find($client->id)->deleted_at);
        $this->assertDatabaseMissing('ads', ['id' => $plainAd->id]);
        $this->assertDatabaseHas('ads', ['id' => $keptAd->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'status' => ServiceOrder::STATUS_COMPLETED]);

        // Le prestataire retrouve toujours sa commande, avec un client anonymisé.
        $this->actingAs($provider)->get(route('service-orders.index'))->assertOk()
            ->assertSee($order->order_number)->assertSee('Utilisateur supprimé');
    }

    public function test_an_account_that_carries_orders_is_never_purged(): void
    {
        $client = User::factory()->create(['email' => 'ancien-client@example.test']);
        $provider = User::factory()->create();
        $order = $this->order($this->ad($provider, ['service_type' => 'offre']), $client, $provider, ServiceOrder::STATUS_COMPLETED, ServiceOrder::PAYMENT_RELEASED);
        $admin = User::factory()->create(['role' => 'admin']);
        $client->delete();

        // Purge manuelle depuis l'administration.
        $this->actingAs($admin)->delete(route('admin.accounts.force-delete', $client->id))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'porte des commandes à conserver'));
        // Purge directe et purge programmée.
        $this->assertFalse(User::withTrashed()->find($client->id)->forceDelete());
        $this->artisan('cleanup:orphaned-data', ['--force' => true]);

        $this->assertNotNull(User::withTrashed()->find($client->id));
        $this->assertDatabaseHas('service_orders', ['id' => $order->id]);

        // Un compte supprimé sans commande reste purgeable.
        $empty = User::factory()->create();
        $empty->delete();
        $this->actingAs($admin)->delete(route('admin.accounts.force-delete', $empty->id))
            ->assertSessionHas('success');
        $this->assertNull(User::withTrashed()->find($empty->id));
    }
}
