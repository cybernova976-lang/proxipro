<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; }
        .container { padding: 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; padding-bottom: 24px; border-bottom: 3px solid #6366f1; }
        .header-left { display: table-cell; width: 55%; vertical-align: top; }
        .header-right { display: table-cell; width: 45%; vertical-align: top; text-align: right; }

        /* Company name as logo */
        .company-logo {
            background: #4f46e5;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 14px 28px;
            display: inline-block;
            line-height: 1;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .company-info { font-size: 10px; color: #64748b; line-height: 1.7; margin-top: 4px; }

        .doc-title { font-size: 30px; font-weight: bold; color: #6366f1; letter-spacing: 1px; margin-bottom: 10px; }
        .doc-info { font-size: 10px; color: #64748b; line-height: 1.8; }
        .doc-info strong { color: #0f172a; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 4px 14px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #fef3c7; color: #d97706; }
        .status-paid { background: #d1fae5; color: #059669; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        .status-cancelled { background: #f1f5f9; color: #94a3b8; }

        /* Emitter + Client row */
        .parties-row { display: table; width: 100%; margin-bottom: 24px; }
        .party-left { display: table-cell; width: 50%; vertical-align: top; }
        .party-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
        .party-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; display: inline-block; min-width: 200px; text-align: left; }
        .info-box-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1.2px; color: #94a3b8; font-weight: bold; margin-bottom: 8px; }
        .info-box-name { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .info-box-detail { font-size: 10px; color: #64748b; line-height: 1.6; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table thead th {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 14px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: left;
        }
        .items-table thead th:nth-child(2) { text-align: center; }
        .items-table thead th:nth-child(3),
        .items-table thead th:last-child { text-align: right; }
        .items-table tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #334155;
        }
        .items-table tbody td:nth-child(2) { text-align: center; }
        .items-table tbody td:nth-child(3),
        .items-table tbody td:last-child { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #f8fafc; }

        /* Totals */
        .totals-wrapper { display: table; width: 100%; margin-bottom: 24px; }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box { display: table-cell; width: 45%; }
        .totals { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .total-row { display: table; width: 100%; padding: 9px 16px; font-size: 11px; }
        .total-row .label { display: table-cell; width: 55%; color: #64748b; }
        .total-row .value { display: table-cell; width: 45%; text-align: right; font-weight: bold; color: #0f172a; }
        .total-row.grand {
            background: #6366f1;
            color: #ffffff;
            padding: 14px 16px;
            font-size: 15px;
        }
        .total-row.grand .label,
        .total-row.grand .value { color: #ffffff; font-weight: bold; }

        /* Payment info */
        .payment-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .payment-box-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #16a34a; margin-bottom: 4px; }
        .payment-box-text { font-size: 10px; color: #166534; }

        /* Notes */
        .notes-section { margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .notes-title { font-size: 10px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .notes-text { font-size: 10px; color: #64748b; white-space: pre-line; line-height: 1.5; }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 25px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="company-logo">{{ $user->company_name ?? $user->name }}</div>
                <div class="company-info">
                    @if($user->company_name && $user->name !== $user->company_name){{ $user->name }}<br>@endif
                    @if($user->address){{ $user->address }}<br>@endif
                    @if($user->city){{ $user->city }}@if($user->country), {{ $user->country }}@endif<br>@endif
                    @if($user->phone)Tél : {{ $user->phone }}<br>@endif
                    {{ $user->email }}
                    @if($user->siret)<br>SIRET : {{ $user->siret }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title">FACTURE</div>
                <div class="doc-info">
                    <strong>N° :</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Date :</strong> {{ $invoice->created_at->format('d/m/Y') }}<br>
                    @if($invoice->due_date)
                    <strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}<br>
                    @endif
                    @if($invoice->quote)
                    <strong>Réf. devis :</strong> {{ $invoice->quote->quote_number }}<br>
                    @endif
                    <span class="status-badge status-{{ $invoice->status }}">{{ $statusLabel ?? $invoice->getStatusLabel() }}</span>
                </div>
            </div>
        </div>

        {{-- Client (right-aligned, below header) --}}
        <div class="parties-row">
            <div class="party-left"></div>
            <div class="party-right">
                <div class="party-box">
                    <div class="info-box-label">Client</div>
                    <div class="info-box-name">{{ $invoice->client_name }}</div>
                    @if($invoice->client_address)<div class="info-box-detail">{{ $invoice->client_address }}</div>@endif
                    @if($invoice->client_email)<div class="info-box-detail">{{ $invoice->client_email }}</div>@endif
                    @if($invoice->client_phone)<div class="info-box-detail">{{ $invoice->client_phone }}</div>@endif
                </div>
            </div>
        </div>

        {{-- Subject --}}
        @if($invoice->subject && $invoice->subject !== 'Facture')
        <div style="margin-bottom: 20px; font-size: 12px;">
            <strong>Objet :</strong> {{ $invoice->subject }}
        </div>
        @endif

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 48%;">Désignation</th>
                    <th style="width: 12%;">Quantité</th>
                    <th style="width: 20%;">Prix unitaire HT</th>
                    <th style="width: 20%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['unit_price'], 2, ',', ' ') }} €</td>
                    <td style="font-weight: 600;">{{ number_format($item['total'] ?? ($item['quantity'] * $item['unit_price']), 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-wrapper">
            <div class="totals-spacer"></div>
            <div class="totals-box">
                <div class="totals">
                    <div class="total-row">
                        <span class="label">Sous-total HT</span>
                        <span class="value">{{ number_format($invoice->subtotal, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row">
                        <span class="label">TVA ({{ $invoice->tax_rate ? number_format($invoice->tax_rate, 0) : 20 }}%)</span>
                        <span class="value">{{ number_format($invoice->tax_amount ?? $invoice->tax, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row grand">
                        <span class="label">Total TTC</span>
                        <span class="value">{{ number_format($invoice->total, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        @if($invoice->paid_at)
        <div class="payment-box">
            <div class="payment-box-title">Paiement reçu</div>
            <div class="payment-box-text">
                Payée le {{ $invoice->paid_at->format('d/m/Y') }}
                @if($invoice->payment_method) — Mode : {{ ucfirst($invoice->payment_method) }}@endif
            </div>
        </div>
        @elseif($invoice->payment_method)
        <div class="payment-box">
            <div class="payment-box-title">Mode de paiement</div>
            <div class="payment-box-text">{{ ucfirst($invoice->payment_method) }}</div>
        </div>
        @endif

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes & conditions</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y') }} — {{ $user->company_name ?? $user->name }}
        @if($user->siret) — SIRET : {{ $user->siret }}@endif
    </div>
</body>
</html>
