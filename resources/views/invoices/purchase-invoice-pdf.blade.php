<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoiceNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 3px solid #2563eb; padding-bottom: 20px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #0f172a; margin-bottom: 6px; }
        .company-info { font-size: 10px; color: #64748b; line-height: 1.6; }
        .doc-title { font-size: 26px; font-weight: bold; color: #2563eb; margin-bottom: 8px; }
        .doc-info { font-size: 10px; color: #64748b; }
        .doc-info strong { color: #0f172a; }

        /* Client */
        .client-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .client-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: bold; margin-bottom: 6px; }
        .client-name { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .client-info { font-size: 10px; color: #64748b; }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #0f172a; color: white; padding: 10px 12px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .items-table th:last-child, .items-table th:nth-child(3) { text-align: right; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        .items-table td:last-child, .items-table td:nth-child(3) { text-align: right; }

        /* Totals */
        .totals-wrapper { display: table; width: 100%; margin-top: 10px; }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box { display: table-cell; width: 45%; }
        .totals { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .total-row { display: table; width: 100%; padding: 8px 14px; font-size: 11px; }
        .total-row .label { display: table-cell; width: 60%; color: #64748b; }
        .total-row .value { display: table-cell; width: 40%; text-align: right; font-weight: bold; color: #0f172a; }
        .total-row.grand { background: #0f172a; color: white; padding: 12px 14px; font-size: 14px; }
        .total-row.grand .label, .total-row.grand .value { color: white; font-weight: bold; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paid { background: #dcfce7; color: #16a34a; }

        /* Footer */
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .footer strong { color: #64748b; }

        /* Info block */
        .info-block { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 10px; color: #1e40af; }
    </style>
</head>
<body>
    @php
        $platformContactEmail = \App\Models\Setting::get(
            'contact_email',
            config('mail.admin_email', config('mail.from.address', 'hello@example.com'))
        );
        $platformPublicUrl = \App\Models\Setting::get('platform_public_url', config('app.url'));
        $platformPublicHost = parse_url((string) $platformPublicUrl, PHP_URL_HOST) ?: $platformPublicUrl;
    @endphp
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ config('app.name', 'Lunamars') }}</div>
                <div class="company-info">
                    Plateforme de services entre particuliers et professionnels<br>
                    {{ $platformContactEmail }}<br>
                    {{ $platformPublicHost }}
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title">FACTURE</div>
                <div class="doc-info">
                    <strong>N°</strong> {{ $invoiceNumber }}<br>
                    <strong>Date :</strong> {{ $date }}<br>
                    <strong>Statut :</strong> <span class="status-badge status-paid">Payée</span>
                </div>
            </div>
        </div>

        {{-- Client info --}}
        <div class="client-box">
            <div class="client-label">Facturé à</div>
            <div class="client-name">{{ $user->name }}</div>
            <div class="client-info">
                {{ $user->email }}<br>
                @if($user->phone){{ $user->phone }}<br>@endif
                @if($user->address){{ $user->address }}@endif
            </div>
        </div>

        {{-- Items table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Désignation</th>
                    <th style="width: 20%;">Quantité</th>
                    <th style="width: 20%;">Montant TTC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $itemDescription }}</strong><br>
                        <span style="color: #94a3b8; font-size: 10px;">{{ $itemDetail }}</span>
                    </td>
                    <td>1</td>
                    <td>{{ number_format($amount, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-wrapper">
            <div class="totals-spacer"></div>
            <div class="totals-box">
                <div class="totals">
                    <div class="total-row">
                        <div class="label">Sous-total HT</div>
                        <div class="value">{{ number_format($amount / 1.20, 2, ',', ' ') }} €</div>
                    </div>
                    <div class="total-row">
                        <div class="label">TVA (20%)</div>
                        <div class="value">{{ number_format($amount - ($amount / 1.20), 2, ',', ' ') }} €</div>
                    </div>
                    <div class="total-row grand">
                        <div class="label">Total TTC</div>
                        <div class="value">{{ number_format($amount, 2, ',', ' ') }} €</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment info --}}
        <div class="info-block" style="margin-top: 24px;">
            <strong>Mode de paiement :</strong> Carte bancaire (Stripe)<br>
            @if($transactionRef)
                <strong>Référence :</strong> {{ $transactionRef }}
            @endif
        </div>

        {{-- Footer --}}
        <div class="footer">
            <strong>{{ config('app.name', 'Lunamars') }}</strong> — Plateforme de mise en relation de services<br>
            Ce document constitue une facture pour l'achat effectué sur la plateforme {{ config('app.name', 'Lunamars') }}.<br>
            Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>
</body>
</html>
