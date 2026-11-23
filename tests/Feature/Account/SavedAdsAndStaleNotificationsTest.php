<?php

namespace Tests\Feature\Account;

use App\Models\Ad;
use App\Models\ServiceProposal;
use App\Models\User;
use App\Notifications\ServiceProposalReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deux retours du fondateur du 23/11/2026 :
 *  - dans « Favoris », rien n'indiquait comment retirer une annonce (une icône de
 *    cœur brisé sur la photo, sans libellé) ;
 *  - une notification « Nouvelle proposition » menait à « Page introuvable »
 *    quand la demande avait été supprimée depuis.
 */
class SavedAdsAndStaleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['user_type' => 'particulier', 'is_service_provider' => false]);
    }

    private function ad(User $owner, string $serviceType, string $title): Ad
    {
        return Ad::create(['user_id' => $owner->id, 'title' => $title, 'description' => 'Description de test.',
            'category' => 'Plombier', 'service_type' => $serviceType, 'status' => 'active', 'visibility' => 'public',
            'location' => 'Mamoudzou', 'expires_at' => now()->addDays(30)]);
    }

    public function test_a_saved_ad_is_removed_with_a_button_that_says_so(): void
    {
        $member = $this->member();
        $neighbour = $this->member();
        $offer = $this->ad(User::factory()->create(), 'offre', 'Peinture intérieure');
        $other = $this->ad(User::factory()->create(), 'offre', 'Jardinage');
        $member->savedAds()->attach([$offer->id, $other->id]);
        $neighbour->savedAds()->attach($offer->id);

        $this->actingAs($member)->get(route('saved-ads.index'))->assertOk()
            // Un bouton écrit en toutes lettres, par annonce, avec confirmation.
            ->assertSee('action="'.route('saved-ads.destroy', $offer).'"', false)
            ->assertSee('data-pk-confirm="Retirer « Peinture intérieure » de vos favoris ?"', false)
            ->assertSee('title="Retirer des favoris" aria-label="Retirer Peinture intérieure de mes favoris"', false)
            // Une seule question de confirmation : le script commun du gabarit, pas un second script de page.
            ->assertSee('js/confirm-submit.js', false)->assertDontSee('js/activity-dashboard.js', false)
            // La carte garde son étiquette de catégorie.
            ->assertSee('<span class="saved-ad-badge">Plombier</span>', false)
            // L'ancienne icône sans libellé et son script en ligne ont disparu.
            ->assertDontSee('fa-heart-broken', false)->assertDontSee('removeSavedAd', false);

        $this->delete(route('saved-ads.destroy', $offer))
            ->assertRedirect(route('saved-ads.index'))->assertSessionHas('success', 'Annonce retirée de vos favoris.');
        $this->assertFalse($member->fresh()->hasSavedAd($offer));
        $this->assertTrue($member->fresh()->hasSavedAd($other));
        // Les favoris d'un autre membre ne sont pas touchés.
        $this->assertTrue($neighbour->fresh()->hasSavedAd($offer));

        // Un second envoi ne remet jamais l'annonce dans la liste.
        $this->delete(route('saved-ads.destroy', $offer))->assertRedirect(route('saved-ads.index'));
        $this->assertFalse($member->fresh()->hasSavedAd($offer));
        $this->get(route('saved-ads.index'))->assertOk()->assertDontSee('Peinture intérieure')->assertSee('Jardinage');

        // Réservé aux membres connectés.
        auth()->logout();
        $this->delete(route('saved-ads.destroy', $other))->assertRedirect(route('login'));
    }

    public function test_a_notification_about_a_deleted_demand_never_leads_to_a_missing_page(): void
    {
        $member = $this->member();
        $provider = User::factory()->create(['name' => 'Amina Plomberie', 'user_type' => 'professionnel', 'is_service_provider' => true]);
        $kept = $this->ad($member, 'demande', 'Réparer une fuite');
        $deleted = $this->ad($member, 'demande', 'Poser une clim');

        foreach ([$kept, $deleted] as $demand) {
            $proposal = ServiceProposal::create(['ad_id' => $demand->id, 'provider_id' => $provider->id, 'amount' => 90,
                'message' => 'Je peux intervenir cette semaine.', 'scheduled_for' => now()->addDays(2),
                'status' => ServiceProposal::STATUS_PENDING]);
            $member->notifyNow(new ServiceProposalReceivedNotification($proposal), ['database']);
        }
        $find = fn (Ad $demand) => $member->notifications()->get()->first(fn ($n) => (int) $n->data['ad_id'] === $demand->id);
        $keptNotification = $find($kept);
        $staleNotification = $find($deleted);
        $deleted->delete();
        $this->assertNull(Ad::find($deleted->id));

        // La cloche de l'en-tête passe par la page d'ouverture, jamais directement par l'adresse enregistrée.
        $this->actingAs($member)->get(route('feed'))->assertOk()
            ->assertSee('href="'.route('notifications.open', $staleNotification->id).'"', false);

        // La demande existe toujours : on arrive sur la comparaison des propositions.
        $this->get(route('notifications.open', $keptNotification->id))
            ->assertRedirect(route('proposals.compare', $kept));
        $this->assertNotNull($keptNotification->fresh()->read_at);
        $this->get(route('proposals.compare', $kept))->assertOk();

        // La demande a été supprimée : un message clair, et la notification sans objet disparaît.
        $this->get(route('notifications.open', $staleNotification->id))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', 'Cette notification concernait une annonce qui a été supprimée : elle a été retirée de la liste.');
        $this->assertNull($staleNotification->fresh());
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('Réparer une fuite')->assertDontSee('Poser une clim');
    }
}
