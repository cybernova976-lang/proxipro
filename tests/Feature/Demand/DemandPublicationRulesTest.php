<?php

namespace Tests\Feature\Demand;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Le formulaire « Publier une demande » applique les mêmes règles que la
 * modification d'une annonce : nombre de photos selon l'abonnement, et
 * conditions de publication affichées avant l'acceptation.
 */
class DemandPublicationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Notification::fake();
        Storage::fake(config('filesystems.default', 'public'));
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Réparer une fuite sous l’évier', 'description' => 'Une fuite à réparer dans la cuisine.',
            'main_category' => 'Bricolage & Travaux', 'category' => 'Plombier', 'country' => 'France', 'city' => 'Paris',
            'desired_date' => now()->addDay()->toDateString(), 'time_window' => 'morning', 'price_type' => 'negotiable',
            'publication_confirmed' => '1', 'service_details' => ['work_scope' => 'repair', 'site_type' => 'house'],
        ], $extra);
    }

    private function photos(int $count): array
    {
        return array_map(fn (int $index) => UploadedFile::fake()->create('photo-'.$index.'.jpg', 120, 'image/jpeg'), range(1, $count));
    }

    private function premium(): User
    {
        $user = User::factory()->create();
        $user->proSubscriptions()->create(['plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);

        return $user;
    }

    public function test_the_form_announces_the_photo_limit_of_the_account_and_shows_the_publication_terms(): void
    {
        $free = User::factory()->create();
        $this->assertSame(2, $free->maxPhotosPerAd());
        $this->actingAs($free)->get(route('demand.create'))->assertOk()
            ->assertSee('Ajoutez jusqu’à 2 photos')
            ->assertSee('data-max-photos="2"', false)
            ->assertSee('Conditions de publication')
            ->assertSee('Vous êtes responsable du contenu de votre demande')
            ->assertSee('j’accepte les conditions de publication', false)
            ->assertSee('id="demandPhotoNote"', false);

        $premium = $this->premium();
        $this->assertSame(4, $premium->maxPhotosPerAd());
        $this->actingAs($premium)->get(route('demand.create'))->assertOk()
            ->assertSee('Ajoutez jusqu’à 4 photos')
            ->assertSee('data-max-photos="4"', false);
    }

    public function test_a_premium_member_publishes_four_photos_and_a_free_member_is_told_the_limit(): void
    {
        $premium = $this->premium();
        $this->actingAs($premium)->post(route('demand.store'), $this->payload(['photos' => $this->photos(4)]))
            ->assertSessionHasNoErrors();
        $this->assertCount(4, Ad::where('user_id', $premium->id)->sole()->photos);

        // Une cinquième photo est refusée avec un message clair.
        $this->post(route('demand.store'), $this->payload(['title' => 'Poser un robinet neuf', 'photos' => $this->photos(5)]))
            ->assertSessionHasErrors(['photos' => '4 photos au plus.']);

        $free = User::factory()->create();
        $this->actingAs($free)->post(route('demand.store'), $this->payload(['photos' => $this->photos(3)]))
            ->assertSessionHasErrors(['photos' => '2 photos au plus.']);
        $this->assertSame(0, Ad::where('user_id', $free->id)->count());
    }

    public function test_the_publication_terms_must_be_accepted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('demand.store'), $this->payload(['publication_confirmed' => null]))
            ->assertSessionHasErrors(['publication_confirmed' => 'Acceptez les conditions de publication pour publier votre demande.']);
        $this->assertSame(0, Ad::where('user_id', $user->id)->count());
    }
}
