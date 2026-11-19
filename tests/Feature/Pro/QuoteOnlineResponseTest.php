<?php

namespace Tests\Feature\Pro;

use App\Models\ProQuote;
use App\Models\User;
use App\Notifications\QuoteAnsweredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lot 3 : le client répond à un devis en ligne, par son lien personnel et sans
 * compte. La réponse est horodatée, porte le nom saisi, et figure sur le devis.
 */
class QuoteOnlineResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_reads_the_quote_and_accepts_it_without_an_account(): void
    {
        Notification::fake();
        $pro = $this->professional();
        $quote = $this->quote($pro);
        $url = $quote->responseUrl();
        $this->assertSame(40, strlen($quote->fresh()->public_token));
        $this->assertSame($url, $quote->fresh()->responseUrl(), 'Le lien ne change plus.');

        $this->get($url)->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Entreprise Test')->assertSee('Travaux de peinture')
            ->assertSee('120,00 €')->assertSee('Accepter le devis')->assertSee('Refuser ce devis');

        // Le nom et la case « Bon pour accord » sont exigés.
        $this->post($url, ['decision' => 'accept', 'name' => ''])->assertSessionHasErrors(['name', 'agree']);
        $this->assertSame('sent', $quote->fresh()->status);

        $this->travelTo(now()->setTime(14, 5));
        $this->post($url, ['decision' => 'accept', 'name' => ' Jeanne Martin ', 'agree' => '1'])
            ->assertRedirect($url)->assertSessionHas('success');

        $fresh = $quote->fresh();
        $this->assertSame('accepted', $fresh->status);
        $this->assertSame('Jeanne Martin', $fresh->response_name);
        $this->assertSame('online', $fresh->response_channel);
        $this->assertNotNull($fresh->accepted_at);
        // L'heure est donnée dans un fuseau nommé (les dates sont enregistrées en UTC).
        $label = 'Accepté en ligne par Jeanne Martin le '.now()->timezone('Europe/Paris')->format('d/m/Y à H:i').' (heure de Paris)';
        $this->assertStringContainsString(' à 1', $label);
        $this->assertSame($label, $fresh->onlineResponseLabel());
        Notification::assertSentTo($pro, QuoteAnsweredNotification::class);

        // Une seule réponse : la page l'affiche, un second envoi est sans effet.
        $this->get($url)->assertOk()->assertSee($label)->assertDontSee('Accepter le devis');
        $this->post($url, ['decision' => 'refuse', 'name' => 'Quelqu’un d’autre'])->assertSessionHas('error');
        $this->assertSame('accepted', $quote->fresh()->status);
        Notification::assertSentToTimes($pro, QuoteAnsweredNotification::class, 1);

        // Le prestataire le voit sur la fiche du devis, et la mention est sur le PDF.
        $this->actingAs($pro)->get(route('pro.quotes.show', $quote))->assertOk()->assertSee($label);
        $this->assertStringContainsString($label, view('pro.quote-pdf', ['user' => $pro, 'quote' => $fresh])->render());
    }

    public function test_client_can_refuse_and_the_planned_reminder_is_cancelled(): void
    {
        Notification::fake();
        $pro = $this->professional();
        $quote = $this->quote($pro, ['reminder_enabled' => true, 'sent_to_email' => 'client@example.test']);

        $this->post($quote->responseUrl(), ['decision' => 'refuse', 'name' => 'Jeanne Martin'])->assertSessionHas('success');

        $fresh = $quote->fresh();
        $this->assertSame('refused', $fresh->status);
        $this->assertNotNull($fresh->refused_at);
        $this->assertFalse($fresh->reminder_enabled);
        $this->assertStringStartsWith('Refusé en ligne par Jeanne Martin', $fresh->onlineResponseLabel());
    }

    public function test_a_draft_an_expired_quote_or_a_wrong_link_cannot_be_answered(): void
    {
        $pro = $this->professional();

        $draft = $this->quote($pro, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null]);
        $this->assertNull($draft->responseUrl());
        $draft->forceFill(['public_token' => str_repeat('a', 40)])->save();
        $this->get(route('quotes.respond', str_repeat('a', 40)))->assertNotFound();

        $this->get(route('quotes.respond', str_repeat('b', 40)))->assertNotFound();
        $this->get(route('quotes.respond', 'trop-court'))->assertNotFound();

        $expired = $this->quote($pro, ['valid_until' => now()->subDay()]);
        $url = route('quotes.respond', $expired->forceFill(['public_token' => str_repeat('c', 40)])->save() ? str_repeat('c', 40) : '');
        $this->assertNull($expired->isAwaitingResponse() ? 'x' : null);
        $this->get($url)->assertOk()->assertSee('La validité de ce devis est dépassée')->assertDontSee('Accepter le devis');
        $this->post($url, ['decision' => 'accept', 'name' => 'Jeanne Martin', 'agree' => '1'])->assertSessionHas('error');
        $this->assertSame('sent', $expired->fresh()->status);
    }

    public function test_the_link_travels_with_the_email_and_a_manual_answer_is_never_shown_as_online(): void
    {
        Mail::fake();
        $pro = $this->professional();
        $quote = $this->quote($pro, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null, 'quote_number' => 'BROUILLON-1']);

        $this->actingAs($pro)->post(route('pro.quotes.sendEmail', $quote), ['email' => 'client@example.test'])
            ->assertSessionHas('success');
        $fresh = $quote->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertNotNull($fresh->public_token);

        // L'e-mail porte le lien, et le PDF joint n'est plus un brouillon.
        $mail = view('emails.quote', ['quote' => $fresh, 'user' => $pro, 'customMessage' => null,
            'responseUrl' => $fresh->responseUrl()])->render();
        $this->assertStringContainsString(route('quotes.respond', $fresh->public_token), $mail);
        $this->assertStringContainsString('Répondre au devis en ligne', $mail);

        $this->get(route('pro.quotes.show', $quote))->assertOk()
            ->assertSee('Copier le lien')->assertSee($fresh->responseUrl());

        // Réponse saisie par le prestataire : notée « manuelle », sans mention en ligne.
        $this->put(route('pro.quotes.status', $quote), ['status' => 'accepted'])->assertSessionHas('success');
        $this->assertSame('manual', $quote->fresh()->response_channel);
        $this->assertNull($quote->fresh()->onlineResponseLabel());
        $this->get(route('quotes.respond', $fresh->public_token))->assertOk()
            ->assertSee('Ce devis a été accepté le')->assertDontSee('en ligne par');
    }

    private function professional(): User
    {
        return User::factory()->completeForVerification()->create([
            'account_type' => 'professionnel', 'user_type' => 'professionnel', 'is_service_provider' => true,
            'identity_verified' => true, 'company_name' => 'Entreprise Test', 'siret' => '12345678900012',
            'business_type' => 'entreprise', 'is_active' => true,
            'pro_terms_accepted_at' => now(), 'pro_terms_version' => config('legal.pro_terms_version'),
        ]);
    }

    private function quote(User $user, array $overrides = []): ProQuote
    {
        static $number = 0;
        $number++;

        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'DEV-REP-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'currency' => 'EUR', 'is_free' => true,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'sent_at' => now(), 'issued_at' => now(), 'valid_until' => now()->addDays(30)], $overrides));
    }
}
