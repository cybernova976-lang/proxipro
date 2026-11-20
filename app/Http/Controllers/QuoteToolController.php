<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Ancien « outil devis / facture » à crédits, retiré le 20/11/2026.
 *
 * Il émettait des devis et des factures numérotés pour n'importe quel membre,
 * sans les contrôles de l'espace Pro (statut professionnel, checklist de
 * conformité, mentions de facture, identité du vendeur), et vendait des packs
 * de crédits que personne n'a achetés. Les devis et factures se créent
 * désormais uniquement dans l'espace Pro, sans crédits.
 *
 * Il reste ici : la page publique, qui présente l'espace Pro, les redirections
 * des anciennes adresses, et le téléchargement des documents déjà créés.
 */
class QuoteToolController extends Controller
{
    public const RETIRED_MESSAGE = 'Cet outil n’est plus proposé : les devis et les factures se créent maintenant dans l’espace Pro, sans crédits.';

    /** Page publique : présentation des devis et factures de l'espace Pro. */
    public function landing()
    {
        return view('tools.quote-landing');
    }

    /** Anciennes adresses de création et d'achat de crédits. */
    public function retired()
    {
        return redirect()->route('pro.dashboard')->with('warning', self::RETIRED_MESSAGE);
    }

    /** Document déjà créé avec l'ancien outil : son auteur peut toujours le télécharger. */
    public function downloadDocument($token)
    {
        $user = Auth::user();
        // Identité saisie à l'époque, si la session l'a encore ; sinon celle du compte.
        $emitter = session('quote_tool_emitter', [
            'name' => $user->company_name ?? $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'company' => $user->company_name,
            'siret' => $user->siret,
        ]);

        $token = (string) $token;
        $isInvoice = str_starts_with($token, 'inv-');
        $id = (int) ($isInvoice ? substr($token, 4) : $token);

        if ($isInvoice) {
            $document = $user->proInvoices()->findOrFail($id);
            $type = 'invoice';
            $view = 'pro.invoice-pdf';
            $filename = 'Facture_'.$document->invoice_number.'.pdf';
        } else {
            $document = $user->proQuotes()->findOrFail($id);
            $type = 'quote';
            $view = 'tools.quote-pdf';
            $filename = 'Devis_'.$document->quote_number.'.pdf';
        }

        if (request()->has('pdf')) {
            $pdfData = [
                'document' => $document,
                'emitter' => $emitter,
                'user' => $user,
            ];

            if ($isInvoice) {
                $pdfUser = clone $user;
                $pdfUser->name = $emitter['name'] ?? $pdfUser->name;
                $pdfUser->company_name = $emitter['company'] ?? $pdfUser->company_name;
                $pdfUser->email = $emitter['email'] ?? $pdfUser->email;
                $pdfUser->phone = $emitter['phone'] ?? $pdfUser->phone;
                $pdfUser->address = $emitter['address'] ?? $pdfUser->address;
                $pdfUser->siret = $emitter['siret'] ?? $pdfUser->siret;

                $pdfData = [
                    'invoice' => $document,
                    'user' => $pdfUser,
                    'statusLabel' => $document->status === 'paid' ? 'Payée' : 'Non payée',
                ];
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData);
            $pdf->setPaper('a4');

            return $pdf->download($filename);
        }

        return view('tools.document-download', [
            'document' => $document,
            'type' => $type,
            'token' => $token,
            'emitter' => $emitter,
            'user' => $user,
        ]);
    }
}
