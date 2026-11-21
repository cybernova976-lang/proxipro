<?php

namespace Tests\Feature\Subscriptions;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SubscriptionInvoiceNotification;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

/**
 * Facture d'un paiement d'abonnement : envoyée par e-mail dès le paiement
 * reçu, et téléchargeable depuis la page « Facturation », paiement par paiement.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SubscriptionInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_simulated']);
        // Préférence « pas d'e-mails » : la facture part quand même.
        $this->member = User::factory()->create(['stripe_id' => 'cus_inv', 'pro_status' => 'active', 'email_notifications' => false]);
        ProSubscription::create(['user_id' => $this->member->id, 'plan' => 'monthly', 'amount' => 9.99,
            'status' => 'active', 'stripe_status' => 'active', 'stripe_subscription_id' => 'sub_inv', 'auto_renew' => true,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonth()]);
    }

    private function withStripe(callable $respond, callable $run): void
    {
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')->andReturnUsing(fn ($method, $url, $headers, $params) => [
            json_encode($respond(strtoupper($method), parse_url($url, PHP_URL_PATH), $params ?? [])), 200, [],
        ]);
        $original = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $run();
        } finally {
            ApiRequestor::setHttpClient($original);
        }
    }

    private function remoteSubscription(): array
    {
        return ['id' => 'sub_inv', 'object' => 'subscription', 'customer' => 'cus_inv', 'status' => 'active',
            'cancel_at_period_end' => false, 'canceled_at' => null, 'pause_collection' => null,
            'current_period_start' => now()->subDay()->timestamp, 'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => ['type' => 'pro_subscription', 'user_id' => (string) $this->member->id, 'plan' => 'monthly'],
            'items' => ['object' => 'list', 'data' => [['id' => 'si_1', 'price' => ['unit_amount' => 999]]]]];
    }

    private function paidEvent(string $eventId): object
    {
        return json_decode(json_encode(['id' => $eventId, 'type' => 'invoice.paid', 'data' => ['object' => [
            'id' => 'in_month', 'subscription' => 'sub_inv', 'amount_paid' => 999, 'billing_reason' => 'subscription_cycle',
            'number' => 'PKJ-0007', 'invoice_pdf' => 'https://pay.stripe.com/invoice/acct_1/in_month/pdf',
        ]]]));
    }

    public function test_the_invoice_is_emailed_once_when_the_payment_is_received(): void
    {
        Notification::fake();

        $this->withStripe(fn () => $this->remoteSubscription(), function () {
            app(StripeWebhookService::class)->process($this->paidEvent('evt_paid_1'));
            // Stripe renvoie parfois le même paiement sous un autre événement.
            app(StripeWebhookService::class)->process($this->paidEvent('evt_paid_2'));
        });

        $payment = Transaction::where('user_id', $this->member->id)->where('type', 'SUBSCRIPTION')->sole();
        $this->assertSame('PKJ-0007', $payment->metadata['invoice_number']);

        Notification::assertSentToTimes($this->member, SubscriptionInvoiceNotification::class, 1);
        Notification::assertSentTo($this->member, SubscriptionInvoiceNotification::class,
            function (SubscriptionInvoiceNotification $notification, array $channels) use ($payment) {
                return $notification->transactionId === $payment->id
                    && $notification->invoiceNumber === 'PKJ-0007'
                    && in_array('mail', $channels, true);
            });
    }

    public function test_the_email_carries_the_pdf_and_falls_back_to_the_link_when_stripe_does_not_answer(): void
    {
        $payment = Transaction::create(['user_id' => $this->member->id, 'amount' => 9.99, 'type' => 'SUBSCRIPTION',
            'status' => 'completed', 'description' => 'Paiement Mensuel', 'stripe_session_id' => 'in_month']);
        $notification = new SubscriptionInvoiceNotification($payment->id, 'PKJ-0007', 'https://pay.stripe.com/invoice/acct_1/in_month/pdf');

        Http::fake(['pay.stripe.com/*' => Http::response('%PDF-1.4 facture', 200)]);
        $mail = $notification->toMail($this->member);
        $this->assertSame(route('billing.invoice', $payment), $mail->actionUrl);
        $this->assertCount(1, $mail->rawAttachments);
        $this->assertSame('Facture-Prokejem-PKJ-0007.pdf', $mail->rawAttachments[0]['name']);
        $text = implode(' ', array_map('strval', $mail->introLines));
        $this->assertStringContainsString('9,99 €', $text);
        $this->assertStringContainsString('facture n° PKJ-0007 est jointe', $text);

        // Stripe ne répond pas : l'e-mail part quand même, avec le lien de téléchargement.
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['pay.stripe.com/*' => Http::response('indisponible', 503)]);
        $mail = $notification->toMail($this->member);
        $this->assertCount(0, $mail->rawAttachments);
        $this->assertStringContainsString('est disponible dans votre compte', implode(' ', array_map('strval', $mail->introLines)));
        $this->assertSame(route('billing.invoice', $payment), $mail->actionUrl);
    }

    public function test_each_payment_downloads_its_own_invoice_and_nobody_else_can(): void
    {
        $payment = Transaction::create(['user_id' => $this->member->id, 'amount' => 9.99, 'type' => 'SUBSCRIPTION',
            'status' => 'completed', 'description' => 'Paiement Mensuel', 'stripe_session_id' => 'in_month']);

        $this->get(route('billing.invoice', $payment))->assertRedirect(route('login'));

        $invoice = ['id' => 'in_month', 'object' => 'invoice', 'customer' => 'cus_inv',
            'invoice_pdf' => 'https://pay.stripe.com/invoice/acct_1/in_month/pdf'];

        $this->withStripe(fn () => $invoice, function () use ($payment) {
            $this->actingAs($this->member)->get(route('billing.index'))->assertOk()
                ->assertSee(route('billing.invoice', $payment), false)
                ->assertDontSee('Télécharger mes factures');
            $this->get(route('billing.invoice', $payment))
                ->assertRedirect('https://pay.stripe.com/invoice/acct_1/in_month/pdf');
        });

        // Facture d'un autre client Stripe : jamais servie.
        $this->withStripe(fn () => ['customer' => 'cus_other'] + $invoice, function () use ($payment) {
            $this->actingAs($this->member)->get(route('billing.invoice', $payment))
                ->assertRedirect(route('billing.index'))->assertSessionHas('error');
        });

        // Paiement d'un autre membre : introuvable.
        $other = User::factory()->create(['stripe_id' => 'cus_other']);
        $this->actingAs($other)->get(route('billing.invoice', $payment))->assertNotFound();
    }
}
