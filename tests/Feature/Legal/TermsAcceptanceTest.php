<?php

namespace Tests\Feature\Legal;

use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une nouvelle version des conditions doit être acceptée par tous les membres,
 * sans exception : proposée avant sa date d'entrée en vigueur, obligatoire
 * ensuite. L'administrateur la publie lui-même et suit les acceptations.
 */
class TermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function publish(array $attributes = []): TermsVersion
    {
        $version = TermsVersion::create($attributes + [
            'version' => '2026-11-18', 'effective_at' => now()->subMinute(),
            'summary' => 'La fin d’une prestation payée en ligne doit être confirmée par le client.',
        ]);
        TermsVersion::forgetCurrent();

        return $version;
    }

    public function test_nothing_is_asked_while_no_version_is_published(): void
    {
        $this->actingAs(User::factory()->create())->get(route('service-orders.index'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('terms.accept.show'))->assertRedirect(route('feed'));
        $this->get(route('legal.terms'))->assertOk()->assertDontSee('data-terms-version', false);
    }

    public function test_a_mandatory_version_blocks_every_member_until_accepted(): void
    {
        $this->publish();
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));
        $this->actingAs($member)->post(route('admin.terms.publish'))->assertRedirect(route('terms.accept.show'));
        $this->actingAs($member)->getJson(route('service-orders.index'))->assertStatus(409)
            ->assertJson(['reason' => 'terms_update', 'accept_url' => route('terms.accept.show')]);

        // Ce qui reste ouvert : lire les textes, écrire au support, se déconnecter.
        $this->actingAs($member)->get(route('legal.terms'))->assertOk();
        $this->actingAs($member)->get(route('legal.privacy'))->assertOk();
        $this->actingAs($member)->get(route('contact.index'))->assertOk();

        $this->actingAs($member)->get(route('terms.accept.show'))->assertOk()
            ->assertSee('Nos conditions d’utilisation ont changé')
            ->assertSee('La fin d’une prestation payée en ligne doit être confirmée par le client.')
            ->assertSee('Lire les conditions complètes')
            ->assertSee('css/terms-accept.css', false)
            ->assertDontSee('data-terms-later', false);
    }

    public function test_no_one_is_exempt_not_even_an_administrator(): void
    {
        $this->publish();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('terms.accept.show'));
        $this->actingAs($admin)->get(route('admin.terms.index'))->assertRedirect(route('terms.accept.show'));
        // Un visiteur non connecté n'est pas concerné.
        $this->app['auth']->forgetGuards();
        $this->get(route('legal.terms'))->assertOk();
    }

    public function test_accepting_records_the_proof_and_returns_where_the_member_was_going(): void
    {
        $version = $this->publish();
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));

        // Sans cocher la case, rien n'est enregistré.
        $this->actingAs($member)->from(route('terms.accept.show'))->post(route('terms.accept.store'))
            ->assertRedirect(route('terms.accept.show'))
            ->assertSessionHasErrors(['accept' => 'Cochez la case pour accepter les conditions.']);
        $this->assertSame(0, TermsAcceptance::count());

        $this->actingAs($member)->post(route('terms.accept.store'), ['accept' => '1'])
            ->assertRedirect(route('service-orders.index'))
            ->assertSessionHas('info', 'Merci : vous avez accepté les conditions d’utilisation.');

        $acceptance = TermsAcceptance::sole();
        $this->assertSame([$member->id, $version->id, 'prompt'], [$acceptance->user_id, $acceptance->terms_version_id, $acceptance->source]);
        $this->assertNotNull($acceptance->accepted_at);
        $this->assertNotNull($acceptance->ip);
        $this->assertSame($version->id, (int) $member->fresh()->accepted_terms_version_id);

        $this->actingAs($member->fresh())->get(route('service-orders.index'))->assertOk();
        $this->actingAs($member->fresh())->get(route('terms.accept.show'))->assertRedirect();
    }

    public function test_before_its_date_a_version_is_only_offered_then_becomes_mandatory(): void
    {
        $this->travelTo('2026-11-18 10:00:00');
        $this->publish(['effective_at' => '2026-12-03 00:00:00']);
        $member = User::factory()->create();

        // Proposée une fois par session, avec la possibilité de remettre à plus tard.
        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));
        $this->actingAs($member)->get(route('terms.accept.show'))->assertOk()
            ->assertSee('à partir du')->assertSee('03/12/2026')
            ->assertSee('data-terms-later', false)
            ->assertSee('href="'.route('service-orders.index').'" data-terms-later', false);
        $this->actingAs($member)->get(route('service-orders.index'))->assertOk();
        $this->actingAs($member)->getJson(route('service-orders.index'))->assertOk();

        // À la date d'entrée en vigueur, plus moyen de continuer sans accepter.
        $this->travelTo('2026-12-03 09:00:00');
        $this->flushSession();
        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));
        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));
    }

    public function test_a_newer_version_asks_again_everyone_who_accepted_the_previous_one(): void
    {
        $first = $this->publish();
        $member = User::factory()->create();
        $member->acceptTerms($first, '127.0.0.1', 'prompt');
        $this->actingAs($member->fresh())->get(route('service-orders.index'))->assertOk();

        $second = $this->publish(['version' => '2026-12-01']);
        $this->actingAs($member->fresh())->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));

        $member->fresh()->acceptTerms($second, '127.0.0.1', 'prompt');
        $this->assertSame(2, TermsAcceptance::where('user_id', $member->id)->count());
        $this->actingAs($member->fresh())->get(route('service-orders.index'))->assertOk();
    }

    public function test_the_administrator_publishes_a_version_and_follows_acceptances(): void
    {
        $this->travelTo('2026-11-18 10:00:00');
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Test']);
        $member = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.terms.index'))->assertOk()
            ->assertSee('Aucune version n’a encore été publiée')
            ->assertSee('value="2026-12-03"', false);

        // Résumé trop court, date passée ou confirmation absente : rien n'est publié.
        $this->actingAs($admin)->post(route('admin.terms.publish'), ['summary' => 'Court', 'effective_date' => '2026-11-01'])
            ->assertSessionHasErrors(['summary', 'effective_date', 'confirm']);
        $this->assertSame(0, TermsVersion::count());

        $summary = 'La fin d’une prestation payée doit être confirmée par le client ; sans réponse, Prokejem décide.';
        $this->actingAs($admin)->post(route('admin.terms.publish'), ['summary' => $summary, 'effective_date' => '2026-11-18', 'confirm' => '1'])
            ->assertRedirect(route('admin.terms.index'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'dès sa prochaine visite'));

        $version = TermsVersion::sole();
        $this->assertSame(['2026-11-18', $summary, $admin->id], [$version->version, $version->summary, $version->published_by]);
        $this->assertTrue($version->isMandatory());
        // L'auteur l'accepte en la publiant ; tous les autres doivent l'accepter.
        $this->assertSame('publication', TermsAcceptance::sole()->source);
        $this->actingAs($member)->get(route('service-orders.index'))->assertRedirect(route('terms.accept.show'));

        $this->actingAs($admin->fresh())->get(route('admin.terms.index'))->assertOk()
            ->assertSee('data-terms-accepted>1 sur 2<', false)
            ->assertSee('Admin Test');
        $member->acceptTerms($version, '127.0.0.1', 'prompt');
        $this->actingAs($admin->fresh())->get(route('admin.terms.index'))->assertOk()
            ->assertSee('data-terms-accepted>2 sur 2<', false);

        // Deuxième publication le même jour, avec préavis : version distincte, simplement proposée.
        $this->actingAs($admin->fresh())->post(route('admin.terms.publish'), ['summary' => $summary, 'effective_date' => '2026-12-03', 'confirm' => '1'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'à partir du 03/12/2026'));
        $this->assertSame('2026-11-18-2', TermsVersion::current()->version);
        $this->assertFalse(TermsVersion::current()->isMandatory());

        $this->get(route('legal.terms'))->assertOk()
            ->assertSee('Version des conditions : 2026-11-18-2, applicable à partir du 03/12/2026.');
    }

    public function test_only_an_administrator_can_publish(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->post(route('admin.terms.publish'), [
            'summary' => 'Un résumé suffisamment long pour passer la validation.', 'effective_date' => now()->format('Y-m-d'), 'confirm' => '1',
        ]);

        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertSame(0, TermsVersion::count());
    }

    public function test_the_terms_state_the_confirmation_rule_and_how_updates_are_accepted(): void
    {
        $this->get(route('legal.terms'))->assertOk()
            ->assertSee('Aucun versement n’est effectué automatiquement à l’expiration d’un délai.')
            ->assertSee('décide soit de verser la somme au prestataire, soit de rembourser le client')
            ->assertSee('à compter de cette date, l’utilisation du compte suppose de l’avoir acceptée');
    }
}
