<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProSubscription;
use App\Models\User;
use App\Notifications\InvoiceReminderSentNotification;
use App\Services\InvoiceFollowUpService;
use App\Support\DocumentLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lot 3 : suivi des factures impayées (retard automatique pour tous, rappel
 * unique en Premium) et logo de l'entreprise sur les devis et factures.
 */
class InvoiceFollowUpAndLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sent_invoice_past_its_due_date_becomes_overdue_for_every_account(): void
    {
        $pro = $this->professional();
        $late = $this->invoice($pro, ['due_date' => now()->subDay()]);
        $onTime = $this->invoice($pro, ['due_date' => now()->addDays(5)]);
        $noDate = $this->invoice($pro, ['due_date' => null]);
        $paid = $this->invoice($pro, ['due_date' => now()->subDays(10), 'status' => 'paid', 'paid_at' => now()]);
        $draft = $this->invoice($pro, ['due_date' => now()->subDays(10), 'status' => 'draft', 'finalized_at' => null]);

        $this->artisan('pro-invoices:follow-up')->assertSuccessful();

        $this->assertSame('overdue', $late->fresh()->status);
        $this->assertSame('sent', $onTime->fresh()->status);
        $this->assertSame('sent', $noDate->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);

        // Une facture en retard peut encore être payée ou renvoyée au client.
        Mail::fake();
        $this->actingAs($pro)->post(route('pro.invoices.sendEmail', $late), ['email' => 'client@example.test'])
            ->assertSessionHas('success');
        $this->put(route('pro.invoices.status', $late), ['status' => 'paid', 'payment_method' => 'virement'])
            ->assertSessionHas('success');
        $this->assertSame('paid', $late->fresh()->status);
    }

    public function test_sending_an_invoice_plans_one_reminder_for_premium_only(): void
    {
        Mail::fake();
        $pro = $this->professional();
        $invoice = $this->invoice($pro, ['status' => 'draft', 'finalized_at' => null, 'due_date' => now()->addDays(10)]);

        // Sans abonnement : la case n'est pas proposée et la demande est ignorée.
        $this->actingAs($pro)->get(route('pro.invoices.show', $invoice))->assertOk()->assertDontSee('pkInvoiceAutoReminder');
        $this->post(route('pro.invoices.sendEmail', $invoice), ['email' => 'client@example.test', 'auto_reminder' => '1'])
            ->assertSessionHas('success');
        $fresh = $invoice->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertSame('client@example.test', $fresh->sent_to_email);
        $this->assertFalse($fresh->reminder_enabled);
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('Voir Premium');
        $this->put(route('pro.invoices.reminder', $invoice), ['enabled' => '1'])->assertRedirect(route('pro.subscription'));
        $this->assertFalse($invoice->fresh()->reminder_enabled);

        $this->subscribe($pro);
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('pkInvoiceAutoReminder');
        $this->put(route('pro.invoices.reminder', $invoice), ['enabled' => '1'])->assertSessionHas('success');
        $this->assertTrue($invoice->fresh()->reminder_enabled);
        $planned = now()->addDays(10 + InvoiceFollowUpService::DELAY_DAYS)->format('d/m/Y');
        $this->get(route('pro.invoices.show', $invoice))->assertOk()->assertSee('Rappel automatique prévu le '.$planned);

        $this->put(route('pro.invoices.reminder', $invoice), ['enabled' => '0'])->assertSessionHas('success');
        $this->assertFalse($invoice->fresh()->reminder_enabled);
    }

    public function test_the_reminder_is_sent_once_three_days_after_the_due_date_while_unpaid(): void
    {
        Mail::fake();
        Notification::fake();
        $pro = $this->professional();
        $this->subscribe($pro);
        $base = ['sent_to_email' => 'client@example.test', 'reminder_enabled' => true];
        $due = $this->invoice($pro, $base + ['due_date' => now()->subDays(4)]);
        $tooEarly = $this->invoice($pro, $base + ['due_date' => now()->subDay()]);
        $paid = $this->invoice($pro, $base + ['due_date' => now()->subDays(4), 'status' => 'paid', 'paid_at' => now()]);
        $notAsked = $this->invoice($pro, ['sent_to_email' => 'client@example.test', 'due_date' => now()->subDays(4)]);
        $tooLate = $this->invoice($pro, $base + ['due_date' => now()->subDays(45)]);
        $other = $this->professional();
        $unsubscribed = $this->invoice($other, $base + ['due_date' => now()->subDays(4)]);

        $result = app(InvoiceFollowUpService::class)->run();

        $this->assertSame(1, $result['sent']);
        $this->assertSame(0, $result['failed']);
        $this->assertNotNull($due->fresh()->reminder_sent_at);
        $this->assertSame('overdue', $due->fresh()->status);
        foreach ([$tooEarly, $paid, $notAsked, $tooLate, $unsubscribed] as $invoice) {
            $this->assertNull($invoice->fresh()->reminder_sent_at);
        }
        Notification::assertSentTo($pro, InvoiceReminderSentNotification::class);

        // Un seul rappel : le passage suivant n'envoie plus rien.
        $this->assertSame(0, app(InvoiceFollowUpService::class)->run()['sent']);
        $this->actingAs($pro)->get(route('pro.invoices.show', $due))->assertOk()
            ->assertSee('Il n’y a pas de second rappel automatique');
        $this->put(route('pro.invoices.reminder', $due), ['enabled' => '1'])->assertSessionHas('error');
    }

    public function test_follow_up_is_scheduled_hourly_and_never_at_night(): void
    {
        $eventAt = function (string $utc) {
            $this->travelTo(\Illuminate\Support\Carbon::parse("2026-11-13 {$utc}:00", 'UTC'));
            $this->app->instance(\Illuminate\Console\Scheduling\Schedule::class, new \Illuminate\Console\Scheduling\Schedule());
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            require base_path('routes/console.php');

            return collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
                ->first(fn ($event) => str_contains((string) $event->command, 'pro-invoices:follow-up'));
        };

        $this->assertSame('0 * * * *', $eventAt('10:00')->expression);
        foreach (['04:00' => false, '05:00' => true, '17:00' => true, '18:00' => false] as $utc => $allowed) {
            $this->assertSame($allowed, $eventAt($utc)->filtersPass($this->app), "Passage de {$utc} UTC.");
        }
    }

    public function test_the_company_logo_is_a_premium_setting_and_appears_on_documents(): void
    {
        Storage::fake(config('filesystems.default', 'public'));
        $pro = $this->professional();
        $invoice = $this->invoice($pro);
        $profile = ['company_name' => 'Entreprise Test'];

        // Sans abonnement : présentation de l'avantage, envoi refusé.
        $this->actingAs($pro)->get(route('pro.profile.edit'))->assertOk()
            ->assertSee('data-document-logo', false)->assertSee('Voir Premium')->assertDontSee('name="company_logo"', false);
        $this->put(route('pro.profile.update'), $profile + ['company_logo' => $this->logo()])
            ->assertSessionHasErrors('company_logo');
        $this->assertNull($pro->fresh()->company_logo);

        $this->subscribe($pro);
        $this->get(route('pro.profile.edit'))->assertOk()->assertSee('name="company_logo"', false);
        $this->put(route('pro.profile.update'), $profile + ['company_logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('company_logo');
        $this->put(route('pro.profile.update'), $profile + ['company_logo' => $this->logo()])->assertSessionHasNoErrors();

        $path = $pro->fresh()->company_logo;
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk(config('filesystems.default', 'public'))->assertExists($path);

        $pdf = view('pro.invoice-pdf', ['user' => $pro->fresh(), 'invoice' => $invoice])->render();
        $this->assertStringContainsString('data:image/jpeg;base64,', $pdf);

        // Abonnement terminé : le logo est conservé, mais n'apparaît plus sur les documents.
        ProSubscription::where('user_id', $pro->id)->update(['ends_at' => now()->subDay()]);
        $this->assertNull(DocumentLogo::dataUri($pro->fresh()));
        $this->assertStringNotContainsString('data:image/jpeg;base64,',
            view('pro.invoice-pdf', ['user' => $pro->fresh(), 'invoice' => $invoice])->render());
        $this->get(route('pro.profile.edit'))->assertOk()->assertSee('Votre logo est conservé');

        // Le retrait reste possible sans abonnement.
        $this->put(route('pro.profile.update'), $profile + ['remove_company_logo' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($pro->fresh()->company_logo);
        Storage::disk(config('filesystems.default', 'public'))->assertMissing($path);
    }

    private function logo(): UploadedFile
    {
        // Un vrai JPEG : produit par la bibliothèque d'images si elle est là, sinon un JPEG minimal.
        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(40, 20);
            ob_start();
            imagejpeg($image);
            $bytes = (string) ob_get_clean();
        } else {
            $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        }

        return UploadedFile::fake()->createWithContent('logo.jpg', $bytes);
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

    private function subscribe(User $user): void
    {
        ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    private function invoice(User $user, array $overrides = []): ProInvoice
    {
        static $number = 0;
        $number++;

        return ProInvoice::create(array_merge(['user_id' => $user->id, 'invoice_number' => 'FAC-TEST-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'subject' => 'Travaux de peinture',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'finalized_at' => now(), 'sent_at' => now(), 'due_date' => now()->addDays(30)], $overrides));
    }
}
