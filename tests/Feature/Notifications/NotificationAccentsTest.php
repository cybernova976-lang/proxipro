<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Les textes affichés aux membres portent leurs accents, y compris dans les
 * notifications déjà enregistrées avant la correction.
 */
class NotificationAccentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_and_proposal_texts_carry_their_accents(): void
    {
        $sources = '';
        foreach (['Notifications/ServiceOrderStatusNotification', 'Notifications/ServiceOrderRequestedNotification',
            'Notifications/ServiceProposalReceivedNotification', 'Notifications/ServiceProposalStatusNotification',
            'Models/ServiceOrder', 'Models/ServiceProposal', 'Http/Controllers/ServiceOrderController',
            'Http/Controllers/ServiceProposalController'] as $file) {
            $sources .= file_get_contents(app_path($file.'.php'));
        }

        foreach (['securisee', 'acceptee', 'refusee', 'remboursee', ' a ete ', 'ont ete', 'Fonds liberes', 'bloques', 'Terminee', ' EUR'] as $unaccented) {
            $this->assertStringNotContainsString($unaccented, $sources, "Texte sans accent : « {$unaccented} ».");
        }
        $this->assertStringContainsString('Paiement sécurisé reçu', $sources);
    }

    public function test_migration_restores_accents_in_stored_notifications_only_where_needed(): void
    {
        $member = User::factory()->create();
        $insert = fn (string $type, array $data) => tap((string) Str::uuid(), fn ($id) => DB::table('notifications')->insert([
            'id' => $id, 'type' => $type, 'notifiable_type' => User::class, 'notifiable_id' => $member->id,
            'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now(),
        ]));

        $order = $insert('App\Notifications\ServiceOrderStatusNotification', [
            'title' => 'Paiement securise recu', 'action_url' => '/service-orders',
            'message' => 'Le paiement Stripe de la commande "Recherche un electricien" a ete confirme et les fonds sont bloques en attente de liberation.',
        ]);
        $proposal = $insert('App\Notifications\ServiceProposalStatusNotification', [
            'title' => 'Proposition acceptee', 'message' => 'Votre proposition pour « Peinture » a ete acceptee.',
        ]);
        // Une notification d'un autre type n'est jamais réécrite.
        $other = $insert('App\Notifications\NewMessageNotification', ['title' => 'Nouveau message', 'message' => 'Un texte a ete envoye']);

        $migration = require database_path('migrations/2026_11_16_100000_restore_accents_in_stored_notifications.php');
        $migration->up();
        $migration->up(); // Sans effet la seconde fois.

        $data = fn (string $id) => json_decode(DB::table('notifications')->where('id', $id)->value('data'), true);
        $this->assertSame('Paiement sécurisé reçu', $data($order)['title']);
        $this->assertSame('Le paiement Stripe de la commande "Recherche un electricien" a été confirmé et les fonds sont bloqués en attente de libération.', $data($order)['message']);
        $this->assertSame('/service-orders', $data($order)['action_url']);
        $this->assertSame('Proposition acceptée', $data($proposal)['title']);
        $this->assertSame('Votre proposition pour « Peinture » a été acceptée.', $data($proposal)['message']);
        $this->assertSame('Un texte a ete envoye', $data($other)['message']);
    }
}
