<?php

namespace Tests\Feature\Pro;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\ProSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommercialDocumentAllowance;
use App\Support\PlatformFeatures;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Décision du fondateur du 25/11/2026 : sans Premium, le PDF d'un devis, d'une
 * facture ou d'un avoir porte le filigrane « Prokejem · version gratuite », et
 * le membre en est prévenu avant de créer le document. Un document émis pendant
 * une période Premium reste propre pour toujours. Un brouillon ne porte que son
 * filigrane « BROUILLON ». La règle tient en une méthode :
 * CommercialDocumentAllowance::carriesFreeWatermark().
 */
class FreeDocumentWatermarkTest extends TestCase
{
    use RefreshDatabase;

    private const WATERMARK = 'PROKEJEM · VERSION GRATUITE';

    private const FOOTER = 'Document créé avec la version gratuite de Prokejem — prokejem.fr';

    private const NOTICE = 'le filigrane « Prokejem · version gratuite ».';

    public function test_a_free_member_gets_the_watermark_on_every_issued_document(): void
    {
        $member = $this->member();
        $quote = $this->quote($member);
        $invoice = $this->invoice($member);
        $credit = $this->invoice($member, ['kind' => ProInvoice::KIND_CREDIT_NOTE, 'status' => ProInvoice::STATUS_ISSUED,
            'parent_invoice_id' => $invoice->id, 'invoice_number' => 'AV-FILIGRANE-1', 'credit_reason' => 'Erreur de prix']);

        foreach ([$quote, $invoice, $credit] as $document) {
            $this->assertTrue($this->rule()->carriesFreeWatermark($member, $document));
        }

        $pdfs = [
            view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render(),
            view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render(),
            view('pro.invoice-pdf', ['user' => $member, 'invoice' => $credit])->render(),
        ];
        foreach ($pdfs as $html) {
            $this->assertStringContainsString('data-watermark="free"', $html);
            $this->assertStringContainsString(self::WATERMARK, $html);
            $this->assertStringContainsString(self::FOOTER, $html);
            // Jamais deux filigranes : celui du brouillon n'est pas superposé.
            $this->assertStringNotContainsString('BROUILLON', $html);
        }
    }

    public function test_a_premium_member_has_no_watermark(): void
    {
        $member = $this->member();
        $this->subscribe($member);
        $quote = $this->quote($member);
        $invoice = $this->invoice($member);

        $this->assertFalse($this->rule()->carriesFreeWatermark($member));
        $this->assertFalse($this->rule()->carriesFreeWatermark($member, $quote));
        $this->assertFalse($this->rule()->carriesFreeWatermark($member, $invoice));

        foreach ([
            view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render(),
            view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render(),
        ] as $html) {
            $this->assertStringNotContainsString('data-watermark', $html);
            $this->assertStringNotContainsString(self::WATERMARK, $html);
            $this->assertStringNotContainsString('version gratuite', $html);
            $this->assertStringNotContainsString('prokejem.fr', $html);
        }
    }

    public function test_a_document_issued_under_premium_stays_clean_after_premium_ends(): void
    {
        Mail::fake();
        $member = $this->member();
        $this->subscribe($member);
        $this->actingAs($member);

        // Devis : émis par le changement de statut. Facture : émise par l'envoi par e-mail.
        $this->post(route('pro.quotes.store'), $this->quotePayload())->assertSessionHas('success');
        $quote = ProQuote::sole();
        $this->assertFalse($quote->issued_with_premium, 'Un brouillon n’est pas encore émis.');
        $this->put(route('pro.quotes.status', $quote), ['status' => 'sent'])->assertSessionHas('success');

        $this->post(route('pro.invoices.store'), $this->invoicePayload())->assertSessionHas('success');
        $invoice = ProInvoice::sole();
        $this->post(route('pro.invoices.sendEmail', $invoice), ['email' => 'client@example.test'])->assertSessionHas('success');

        // Avoir : émis directement, sous Premium lui aussi.
        $this->post(route('pro.invoices.credit-note', $invoice), ['reason' => 'Remise accordée', 'scope' => 'partial',
            'amount_ht' => '10', 'confirm' => '1'])->assertSessionHasNoErrors();
        $credit = ProInvoice::where('kind', ProInvoice::KIND_CREDIT_NOTE)->sole();

        foreach ([$quote->fresh(), $invoice->fresh(), $credit] as $document) {
            $this->assertTrue($document->issued_with_premium);
        }

        // L'abonnement se termine : ces documents restent sans filigrane, pour toujours.
        ProSubscription::where('user_id', $member->id)->update(['ends_at' => now()->subDay()]);
        $member = $member->fresh();
        $this->assertFalse($member->hasActiveProSubscription());
        $this->assertTrue($this->rule()->carriesFreeWatermark($member), 'Un nouveau document porterait le filigrane.');

        foreach ([$quote->fresh(), $invoice->fresh(), $credit->fresh()] as $document) {
            $this->assertFalse($this->rule()->carriesFreeWatermark($member, $document));
        }
        $this->assertStringNotContainsString(self::WATERMARK, view('pro.quote-pdf', ['user' => $member, 'quote' => $quote->fresh()])->render());
        $this->assertStringNotContainsString(self::WATERMARK, view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice->fresh()])->render());
        $this->assertStringNotContainsString(self::FOOTER, view('pro.invoice-pdf', ['user' => $member, 'invoice' => $credit->fresh()])->render());

        // Un avoir émis après la fin de Premium suit la règle : il porte le filigrane.
        $this->post(route('pro.invoices.credit-note', $invoice), ['reason' => 'Seconde remise', 'scope' => 'partial',
            'amount_ht' => '5', 'confirm' => '1'])->assertSessionHasNoErrors();
        $late = ProInvoice::where('kind', ProInvoice::KIND_CREDIT_NOTE)->latest('id')->first();
        $this->assertFalse($late->issued_with_premium);
        $this->assertStringContainsString(self::WATERMARK, view('pro.invoice-pdf', ['user' => $member, 'invoice' => $late])->render());
    }

    public function test_a_free_document_loses_the_watermark_only_while_premium_is_active(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post(route('pro.quotes.store'), $this->quotePayload());
        $quote = ProQuote::sole();
        $this->put(route('pro.quotes.status', $quote), ['status' => 'sent'])->assertSessionHas('success');
        $quote->refresh();

        $this->assertFalse($quote->issued_with_premium);
        $this->assertTrue($this->rule()->carriesFreeWatermark($member, $quote));

        // Premium souscrit ensuite : le filigrane disparaît au rendu, sans réécrire le document.
        $subscription = $this->subscribe($member);
        $this->assertFalse($this->rule()->carriesFreeWatermark($member->fresh(), $quote));
        $this->assertFalse($quote->fresh()->issued_with_premium);

        // Premium terminé : ce document émis en version gratuite le retrouve.
        $subscription->update(['ends_at' => now()->subDay()]);
        $this->assertTrue($this->rule()->carriesFreeWatermark($member->fresh(), $quote));
        $this->assertStringContainsString(self::WATERMARK, view('pro.quote-pdf', ['user' => $member->fresh(), 'quote' => $quote])->render());
    }

    public function test_a_draft_only_carries_the_draft_watermark(): void
    {
        $member = $this->member();
        $quote = $this->quote($member, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null, 'quote_number' => 'BROUILLON-DEV-FILIGRANE']);
        $invoice = $this->invoice($member, ['status' => 'draft', 'finalized_at' => null, 'invoice_number' => 'BROUILLON-FAC-FILIGRANE']);

        foreach ([
            [$quote, view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render()],
            [$invoice, view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render()],
        ] as [$document, $html]) {
            $this->assertFalse($this->rule()->carriesFreeWatermark($member, $document));
            $this->assertStringContainsString('data-watermark="draft"', $html);
            $this->assertStringContainsString('BROUILLON — NON CONTRACTUEL', $html);
            $this->assertStringNotContainsString('data-watermark="free"', $html);
            $this->assertStringNotContainsString(self::WATERMARK, $html);
            $this->assertStringNotContainsString(self::FOOTER, $html);
        }
        // Sur la fiche du brouillon, le membre est prévenu de ce qui l'attend à l'émission.
        $this->actingAs($member)->get(route('pro.quotes.show', $quote))->assertOk()
            ->assertSee('Version gratuite : le PDF de ce devis portera '.self::NOTICE);
    }

    public function test_every_issuing_path_records_whether_premium_was_active(): void
    {
        Mail::fake();
        $member = $this->member();
        $this->subscribe($member);
        $this->actingAs($member);

        // Devis accepté avec acompte : facture d'acompte, puis facture du devis.
        $quote = $this->quote($member, ['status' => 'accepted', 'accepted_at' => now(), 'deposit_percentage' => 30, 'issued_with_premium' => true]);
        $this->post(route('pro.quotes.deposit-invoice', $quote))->assertSessionHas('success');
        $deposit = ProInvoice::where('kind', ProInvoice::KIND_DEPOSIT)->sole();
        $this->assertFalse($deposit->issued_with_premium, 'Le brouillon d’acompte n’est pas encore émis.');
        $this->put(route('pro.invoices.status', $deposit), ['status' => 'sent'])->assertSessionHas('success');
        $this->assertTrue($deposit->fresh()->issued_with_premium);

        $this->get(route('pro.invoices.create', ['quoteId' => $quote->id]))->assertOk();
        $this->post(route('pro.invoices.store'), $this->invoicePayload() + ['quote_id' => $quote->id])->assertSessionHas('success');
        $final = ProInvoice::where('kind', ProInvoice::KIND_INVOICE)->sole();
        $this->put(route('pro.invoices.status', $final), ['status' => 'sent'])->assertSessionHas('success');
        $this->assertTrue($final->fresh()->issued_with_premium);

        // La copie d'un devis émis sous Premium est un nouveau brouillon : elle ne reprend pas la marque.
        $this->post(route('pro.quotes.duplicate', $quote))->assertSessionHas('success');
        $copy = ProQuote::latest('id')->first();
        $this->assertSame('draft', $copy->status);
        $this->assertFalse($copy->issued_with_premium);

        // Émise après la fin de Premium, elle porte le filigrane ; l'original reste propre.
        ProSubscription::where('user_id', $member->id)->update(['ends_at' => now()->subDay()]);
        $this->post(route('pro.quotes.sendEmail', $copy), ['email' => 'client@example.test'])->assertSessionHas('success');
        $copy->refresh();
        $this->assertSame('sent', $copy->status);
        $this->assertFalse($copy->issued_with_premium);
        $this->assertTrue($this->rule()->carriesFreeWatermark($member->fresh(), $copy));
        $this->assertFalse($this->rule()->carriesFreeWatermark($member->fresh(), $quote->fresh()));
    }

    public function test_a_draft_resent_after_a_failed_send_takes_the_premium_mark_of_the_real_sending(): void
    {
        $member = $this->member();
        $this->actingAs($member);
        // Premier envoi échoué en version gratuite : numéro attribué, document revenu en brouillon.
        $invoice = $this->invoice($member, ['status' => 'draft', 'invoice_number' => 'FAC-ECHEC', 'issued_with_premium' => false]);
        $this->subscribe($member);

        $this->put(route('pro.invoices.status', $invoice), ['status' => 'sent'])->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('FAC-ECHEC', $invoice->invoice_number, 'Le numéro déjà attribué est gardé.');
        $this->assertTrue($invoice->issued_with_premium);
        ProSubscription::where('user_id', $member->id)->update(['ends_at' => now()->subDay()]);
        $this->assertFalse($this->rule()->carriesFreeWatermark($member->fresh(), $invoice));
    }

    public function test_the_creation_forms_warn_a_free_member_before_the_save_button(): void
    {
        $member = $this->member();
        $this->actingAs($member);
        $this->assertFalse(PlatformFeatures::proSubscriptionsEnabled());
        // Les brouillons et le devis accepté (sa conversion en facture passe par le même
        // formulaire) restent dans les documents offerts : 2 devis, 1 facture.
        $draftQuote = $this->quote($member, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null, 'quote_number' => 'BROUILLON-DEV-A']);
        $draftInvoice = $this->invoice($member, ['status' => 'draft', 'finalized_at' => null, 'invoice_number' => 'BROUILLON-FAC-A']);
        $forms = [
            [route('pro.quotes.create'), 'ce devis', 'Enregistrer le devis'],
            [route('pro.invoices.create'), 'cette facture', 'Créer la facture'],
            [route('pro.quotes.edit', $draftQuote), 'ce devis', 'Enregistrer les modifications'],
            [route('pro.invoices.edit', $draftInvoice), 'cette facture', 'Enregistrer les modifications'],
        ];
        foreach ($forms as [$url, $label, $button]) {
            $html = $this->get($url)->assertOk()
                ->assertSee('Version gratuite : '.$label.' portera '.self::NOTICE)
                // Abonnement fermé : la même phrase, sans promesse de Premium.
                ->assertDontSee('Premium le retire')->assertDontSee('data-free-watermark-premium', false)
                ->getContent();
            $this->assertSame(1, substr_count($html, 'data-free-watermark-notice'), 'Un seul avertissement par écran : '.$url);
            $this->assertLessThan(strpos($html, $button), strpos($html, 'data-free-watermark-notice'),
                'L’avertissement précède le bouton d’enregistrement : '.$url);
        }

        $accepted = $this->quote($member, ['status' => 'accepted', 'accepted_at' => now(), 'quote_number' => 'DEV-FILIGRANE-ACC']);
        $this->get(route('pro.invoices.create', ['quoteId' => $accepted->id]))->assertOk()
            ->assertSee('Facture depuis devis DEV-FILIGRANE-ACC')
            ->assertSee('Version gratuite : cette facture portera '.self::NOTICE);

        // Abonnement ouvert : Premium est proposé par un simple lien, pas par un bouton.
        $this->openSubscriptions();
        $draftQuote->delete();
        $html = $this->get(route('pro.quotes.create'))->assertOk()
            ->assertSee('Version gratuite : ce devis portera '.self::NOTICE)
            ->assertSee('Premium le retire.')
            ->getContent();
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('pro.subscription'), '#').'" data-free-watermark-premium>Voir Premium</a>#', $html);
        $this->assertSame(1, substr_count($html, 'btn-pro-primary'), 'Le bouton d’enregistrement reste le seul bouton plein.');
        $this->get(route('pro.invoices.create'))->assertOk()->assertSee('Premium le retire.')
            ->assertSee(route('pro.subscription'), false);
    }

    public function test_the_forms_show_no_warning_to_a_premium_member(): void
    {
        $member = $this->member();
        $this->subscribe($member);
        $this->openSubscriptions();
        $draft = $this->quote($member, ['status' => 'draft', 'issued_at' => null, 'sent_at' => null, 'quote_number' => 'BROUILLON-DEV-P']);
        $issued = $this->invoice($member, ['issued_with_premium' => true]);
        $this->actingAs($member);

        foreach ([route('pro.quotes.create'), route('pro.invoices.create'), route('pro.quotes.edit', $draft),
            route('pro.quotes.show', $draft), route('pro.invoices.show', $issued)] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('data-free-watermark-notice', false)
                ->assertDontSee('version gratuite');
        }
    }

    public function test_the_download_buttons_remind_which_documents_carry_the_watermark(): void
    {
        $member = $this->member();
        $quote = $this->quote($member);
        $invoice = $this->invoice($member);
        // Document émis pendant une ancienne période Premium : aucun rappel, il est propre.
        $clean = $this->invoice($member, ['invoice_number' => 'FAC-FILIGRANE-PROPRE', 'issued_with_premium' => true]);
        $this->actingAs($member);

        $this->get(route('pro.quotes.show', $quote))->assertOk()
            ->assertSee('Version gratuite : le PDF de ce devis porte '.self::NOTICE);
        // Fiche d'une facture : le rappel du téléchargement, et celui de l'avoir à émettre.
        $html = $this->get(route('pro.invoices.show', $invoice))->assertOk()
            ->assertSee('Version gratuite : le PDF de cette facture porte '.self::NOTICE)
            ->assertSee('Version gratuite : cet avoir portera '.self::NOTICE)
            ->getContent();
        $this->assertLessThan(strpos($html, 'Émettre l’avoir'), strrpos($html, 'data-free-watermark-notice'));

        $this->get(route('pro.invoices.show', $clean))->assertOk()
            ->assertDontSee('le PDF de cette facture porte')
            // Un avoir émis aujourd'hui, sans Premium, porterait le filigrane.
            ->assertSee('Version gratuite : cet avoir portera '.self::NOTICE);

        // Ancien outil : le rappel accompagne aussi son bouton de téléchargement.
        $this->get(route('quote-tool.download', ['token' => $quote->id]))->assertOk()
            ->assertSee('Version gratuite : le PDF de ce devis porte '.self::NOTICE);
        $this->get(route('quote-tool.download', ['token' => 'inv-'.$clean->id]))->assertOk()
            ->assertDontSee('data-free-watermark-notice', false);
    }

    public function test_the_client_downloads_a_watermarked_pdf_from_the_public_link(): void
    {
        $member = $this->member();
        $quote = $this->quote($member);
        $url = $quote->responseUrl();
        $this->assertNotNull($url);

        // Le rendu réel de la requête : qui est l'auteur, quel document, quel filigrane.
        $rendered = [];
        View::composer('pro.partials.pdf-furniture', function ($view) use (&$rendered) {
            $rendered[] = $view->getData();
        });

        $response = $this->get(route('quotes.respond.pdf', $quote->fresh()->public_token))->assertOk();
        $this->assertStringContainsString('Devis_'.$quote->quote_number.'.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        $this->assertCount(1, $rendered);
        $this->assertTrue($rendered[0]['pkOwner']->is($member));
        $this->assertTrue($rendered[0]['pkDocument']->is($quote));
        $this->assertTrue($this->rule()->carriesFreeWatermark($rendered[0]['pkOwner'], $rendered[0]['pkDocument']));

        // Même lien, auteur passé à Premium : le client reçoit un PDF sans filigrane.
        $this->subscribe($member);
        $rendered = [];
        $this->get(route('quotes.respond.pdf', $quote->fresh()->public_token))->assertOk();
        $this->assertCount(1, $rendered);
        $this->assertFalse($this->rule()->carriesFreeWatermark($rendered[0]['pkOwner'], $rendered[0]['pkDocument']));
        // La page de réponse du client ne parle jamais de la version gratuite.
        $this->get($url)->assertOk()->assertDontSee('version gratuite');
    }

    public function test_the_three_pdf_views_render_and_keep_every_mention(): void
    {
        $member = $this->member(['company_name' => 'Atelier Exemple', 'siret' => '12345678900012', 'tva_number' => 'FR12123456789',
            'address' => '1 rue du Test', 'postal_code' => '75001', 'city' => 'Paris', 'phone' => '0199001234']);
        $items = [
            ['description' => 'Pose de carrelage', 'quantity' => 12.5, 'unit_price' => 40, 'discount_percent' => 10, 'total' => 450],
            ['description' => 'Fourniture', 'quantity' => 1, 'unit_price' => 200, 'tax_rate' => 5.5, 'total' => 200],
        ];
        $quote = $this->quote($member, ['items' => $items, 'subtotal' => 650, 'tax_amount' => 101, 'total' => 751,
            'seller_snapshot' => $member->commercialIdentitySnapshot(), 'client_company' => 'SCI Exemple',
            'client_registration_number' => '98765432100011', 'execution_location' => 'Paris 1er', 'deposit_percentage' => 30,
            'notes' => 'Note du devis', 'conditions' => 'Conditions du devis']);
        $invoice = $this->invoice($member, ['items' => $items, 'subtotal' => 650, 'tax_amount' => 101, 'total' => 751,
            'seller_snapshot' => $member->commercialIdentitySnapshot(), 'client_type' => 'business', 'client_company' => 'SCI Exemple',
            'client_vat_number' => 'FR40987654321', 'delivery_address' => '3 quai Nord', 'purchase_order_number' => 'BC-77',
            'late_penalty_rate' => 12, 'payment_method' => 'virement', 'notes' => 'Note de facture', 'quote_id' => $quote->id]);

        $quoteHtml = view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render();
        foreach (['DEVIS', 'N° '.$quote->quote_number, 'Atelier Exemple', 'SIRET : 12345678900012', 'TVA : FR12123456789',
            '1 rue du Test', '75001 Paris', 'Tél : 0199001234', 'Destinataire', 'SCI Exemple', 'Immatriculation : 98765432100011',
            'Valable jusqu’au', 'Prestation de services', 'Paris 1er', 'Établissement du devis', 'gratuit', '30 %', 'Travaux de peinture',
            'Pose de carrelage', '12,5', 'Remise de 10 %', 'TVA 5,5 %', 'TVA 20 %', 'Total HT', '650,00 €',
            // Base de chaque taux : insécable, la coupure ne tombe jamais dans le montant.
            '<span style="white-space: nowrap;">sur 450,00 €</span>',
            '<span style="white-space: nowrap;">sur 200,00 €</span>',
            'Total TTC', '751,00 €', 'Note du devis', 'Conditions du devis', 'Bon pour accord', 'Envoyé', 'Page '] as $text) {
            $this->assertStringContainsString($text, $quoteHtml, 'Devis : « '.$text.' »');
        }

        $invoiceHtml = view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render();
        foreach (['FACTURE', 'N° '.$invoice->invoice_number, 'Atelier Exemple', 'SIRET : 12345678900012', 'Date d’émission', 'Date de l’opération',
            'Échéance', 'Réf. devis', $quote->quote_number, 'Bon de commande', 'BC-77', 'Prestations de services', 'Client', 'SCI Exemple',
            'TVA : FR40987654321', 'Adresse de livraison : 3 quai Nord', 'Pose de carrelage', 'Remise de 10 %', 'Total TTC', '751,00 €',
            'Conditions de paiement :', 'Paiement à 30 jours', 'Échéance au', 'Mode de paiement :', 'Virement bancaire',
            'Escompte pour paiement anticipé :', 'Néant', 'pénalités au taux annuel de 12,00 %', 'indemnité forfaitaire de 40 €',
            'Note de facture', 'Envoyée'] as $text) {
            $this->assertStringContainsString($text, $invoiceHtml, 'Facture : « '.$text.' »');
        }

        // Ancien outil : même présentation, identité saisie à l'époque, même règle de filigrane.
        $toolHtml = view('tools.quote-pdf', ['document' => $quote, 'user' => $member, 'emitter' => [
            'name' => 'Auteur Exemple', 'company' => 'Société Ancienne', 'email' => 'ancien@example.test',
            'phone' => '0199005678', 'address' => '9 rue Ancienne', 'siret' => '11122233300044']])->render();
        foreach (['DEVIS', 'Société Ancienne', 'Auteur Exemple', '9 rue Ancienne', 'SIRET : 11122233300044', 'Pose de carrelage',
            'Total TTC', '751,00 €', 'Note du devis', 'Conditions du devis', self::WATERMARK, self::FOOTER] as $text) {
            $this->assertStringContainsString($text, $toolHtml, 'Ancien outil : « '.$text.' »');
        }

        // Le moteur PDF accepte les trois vues (aucune image, polices fournies avec DomPDF).
        foreach ([['pro.quote-pdf', ['user' => $member, 'quote' => $quote]], ['pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice]],
            ['tools.quote-pdf', ['document' => $quote, 'user' => $member, 'emitter' => ['name' => 'Auteur Exemple']]]] as [$view, $data]) {
            $this->assertStringStartsWith('%PDF', Pdf::loadView($view, $data)->setPaper('a4')->output(), $view);
        }
        $this->actingAs($member)->get(route('quote-tool.download', ['token' => $quote->id]).'?pdf=1')->assertOk();
        $this->get(route('pro.invoices.download', $invoice))->assertOk();
    }

    public function test_long_texts_move_below_the_totals_without_losing_anything(): void
    {
        $member = $this->member();
        $long = trim(str_repeat('Toute modification demandée en cours de chantier fait l’objet d’un avenant chiffré. ', 8));
        $quote = $this->quote($member, ['notes' => $long, 'conditions' => 'Conditions longues. '.$long]);
        $invoice = $this->invoice($member, ['payment_terms' => $long, 'notes' => 'Note longue. '.$long,
            'status' => 'paid', 'paid_at' => now(), 'payment_method' => 'other']);

        $quoteHtml = view('pro.quote-pdf', ['user' => $member, 'quote' => $quote])->render();
        $this->assertSame(2, substr_count($quoteHtml, e($long)));
        $this->assertStringContainsString('Conditions longues.', $quoteHtml);
        $this->assertStringContainsString('pk-half-right', $quoteHtml);
        $this->assertStringContainsString('Bon pour accord', $quoteHtml);

        $invoiceHtml = view('pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice])->render();
        $this->assertSame(2, substr_count($invoiceHtml, e(rtrim($long, '.'))));
        foreach (['pk-paid-below', 'Paiement reçu', 'Payée le '.now()->format('d/m/Y'), 'Note longue.'] as $text) {
            $this->assertStringContainsString($text, $invoiceHtml);
        }
        // « Autre » ne dit rien au client : le mode de paiement n'est alors pas affiché.
        $this->assertStringNotContainsString('Mode :', $invoiceHtml);
        $this->assertStringNotContainsString('Other', $invoiceHtml);

        foreach ([['pro.quote-pdf', ['user' => $member, 'quote' => $quote]], ['pro.invoice-pdf', ['user' => $member, 'invoice' => $invoice]]] as [$view, $data]) {
            $this->assertStringStartsWith('%PDF', Pdf::loadView($view, $data)->setPaper('a4')->output());
        }
    }

    public function test_the_public_page_and_the_terms_announce_the_watermark(): void
    {
        $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('2 devis et 2 factures offerts, sans justificatif')
            ->assertSee('Leurs PDF portent le filigrane « Prokejem · version gratuite ».')
            ->assertDontSee('Premium le retire')
            ->assertSee('Vos devis et vos factures sans le filigrane de la version gratuite');
        $this->get(route('legal.pro-terms'))->assertOk()
            ->assertSee('sans fournir de justificatif.')
            ->assertSee('porte le filigrane « Prokejem · version gratuite » et une mention en pied de page');

        $this->openSubscriptions();
        $this->get(route('quote-tool.landing'))->assertOk()
            ->assertSee('Leurs PDF portent le filigrane « Prokejem · version gratuite » ; Premium le retire.');
    }

    public function test_the_migration_marks_documents_already_issued_during_a_paid_period(): void
    {
        $member = $this->member();
        // Abonnement terminé depuis dix jours, qui a couru pendant un mois.
        ProSubscription::create(['user_id' => $member->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'cancelled',
            'starts_at' => now()->subDays(40), 'ends_at' => now()->subDays(10)]);
        $during = $this->quote($member, ['quote_number' => 'DEV-PENDANT', 'issued_at' => now()->subDays(20)]);
        $after = $this->quote($member, ['quote_number' => 'DEV-APRES', 'issued_at' => now()->subDays(2)]);
        $draft = $this->quote($member, ['quote_number' => 'BROUILLON-DEV-M', 'status' => 'draft', 'issued_at' => null]);
        $paidInvoice = $this->invoice($member, ['invoice_number' => 'FAC-PENDANT', 'finalized_at' => now()->subDays(15)]);
        // Abonnement renouvelé : starts_at ne garde que la période en cours, created_at date la souscription.
        $renewed = $this->member();
        $renewal = ProSubscription::create(['user_id' => $renewed->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDays(20), 'ends_at' => now()->addDays(10)]);
        $renewal->forceFill(['created_at' => now()->subDays(80)])->saveQuietly();
        $beforeRenewal = $this->quote($renewed, ['quote_number' => 'DEV-RENOUVELE', 'issued_at' => now()->subDays(50)]);
        $freeInvoice = $this->invoice($member, ['invoice_number' => 'FAC-AVANT', 'finalized_at' => now()->subDays(60)]);
        // Souscription jamais réglée : elle n'ouvre aucune période payée.
        $unpaid = $this->member();
        ProSubscription::create(['user_id' => $unpaid->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'pending',
            'starts_at' => now()->subDays(40), 'ends_at' => now()->addDays(10)]);
        $neverPaid = $this->quote($unpaid, ['quote_number' => 'DEV-IMPAYE', 'issued_at' => now()->subDays(20)]);

        // On rejoue la migration sur des tables qui n'ont pas encore la colonne.
        foreach (['pro_quotes', 'pro_invoices'] as $table) {
            Schema::table($table, fn ($blueprint) => $blueprint->dropColumn('issued_with_premium'));
        }
        $migration = require database_path('migrations/2026_11_25_100000_add_issued_with_premium_to_pro_documents.php');
        $migration->up();
        // Rejouable : un second passage ne change rien et ne plante pas.
        $migration->up();

        $this->assertTrue($during->fresh()->issued_with_premium);
        $this->assertTrue($paidInvoice->fresh()->issued_with_premium);
        $this->assertTrue($beforeRenewal->fresh()->issued_with_premium, 'Émis pendant une période payée antérieure au dernier renouvellement.');
        foreach ([$after, $draft, $freeInvoice, $neverPaid] as $document) {
            $this->assertFalse($document->fresh()->issued_with_premium, $document->quote_number ?? $document->invoice_number);
        }
        // L'ancien abonné n'a plus Premium : seul le document émis pendant sa période payée reste propre.
        $this->assertFalse($this->rule()->carriesFreeWatermark($member, $during->fresh()));
        $this->assertTrue($this->rule()->carriesFreeWatermark($member, $after->fresh()));
    }

    private function rule(): CommercialDocumentAllowance
    {
        return app(CommercialDocumentAllowance::class);
    }

    private function member(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'particulier', 'user_type' => 'particulier', 'is_service_provider' => false,
            'identity_verified' => false, 'siret' => null, 'company_name' => null, 'is_active' => true,
        ], $overrides));
    }

    private function subscribe(User $user): ProSubscription
    {
        return ProSubscription::create(['user_id' => $user->id, 'plan' => 'monthly', 'amount' => 9.99, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    private function quote(User $user, array $overrides = []): ProQuote
    {
        static $number = 0;
        $number++;

        return ProQuote::create(array_merge(['user_id' => $user->id, 'quote_number' => 'DEV-FILIGRANE-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'currency' => 'EUR', 'is_free' => true,
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'sent_at' => now(), 'issued_at' => now(), 'valid_until' => now()->addDays(30)], $overrides));
    }

    private function invoice(User $user, array $overrides = []): ProInvoice
    {
        static $number = 0;
        $number++;

        return ProInvoice::create(array_merge(['user_id' => $user->id, 'invoice_number' => 'FAC-FILIGRANE-'.$number,
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_type' => 'individual',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'service_date' => now(), 'currency' => 'EUR',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50, 'total' => 100]],
            'subtotal' => 100, 'tax_rate' => 20, 'tax_amount' => 20, 'total' => 120, 'status' => 'sent',
            'due_date' => now()->addDays(30), 'payment_terms' => 'Paiement à 30 jours.', 'early_payment_discount' => 'Néant',
            'finalized_at' => now(), 'sent_at' => now()], $overrides));
    }

    /** @return array<string, mixed> */
    private function quotePayload(): array
    {
        return [
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_address' => '1 rue du Client',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services',
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20, 'valid_until' => now()->addDays(30)->format('Y-m-d'), 'is_free' => 1,
        ];
    }

    /** @return array<string, mixed> */
    private function invoicePayload(): array
    {
        return [
            'client_name' => 'Client Exemple', 'client_email' => 'client@example.test', 'client_type' => 'individual',
            'subject' => 'Travaux de peinture', 'operation_type' => 'services', 'service_date' => now()->format('Y-m-d'),
            'items' => [['description' => 'Peinture', 'quantity' => 2, 'unit_price' => 50]],
            'tax_rate' => 20, 'due_date' => now()->addDays(30)->format('Y-m-d'),
            'payment_terms' => 'Paiement à 30 jours.', 'early_payment_discount' => 'Néant',
        ];
    }

    private function openSubscriptions(): void
    {
        config([
            'services.stripe.key' => 'pk_live_configured',
            'services.stripe.secret' => 'sk_live_configured',
            'services.stripe.webhook_secret' => 'whsec_configured',
            'app.url' => 'https://marketplace.example',
        ]);
        Setting::set('legal_entity_name', 'Marketplace Test', 'legal');
        Setting::set('legal_registration_number', 'REG-123', 'legal');
        Setting::set('legal_address', '1 rue du Test', 'legal');
        Setting::set('legal_publication_director', 'Direction Test', 'legal');
        Setting::set('platform_public_url', 'https://marketplace.example', 'legal');
        Setting::set('stripe_billing_portal_configured', '1', 'subscriptions');
        PlatformFeatures::setProSubscriptionsEnabled(true);
        $this->assertTrue(PlatformFeatures::proSubscriptionsEnabled());
    }
}
