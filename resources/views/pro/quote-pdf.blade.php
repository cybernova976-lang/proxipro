<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 3px solid #2563eb; padding-bottom: 20px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
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
        .company-info { font-size: 10px; color: #64748b; line-height: 1.6; }
        .doc-title { font-size: 28px; font-weight: bold; color: #2563eb; margin-bottom: 8px; }
        .doc-info { font-size: 10px; color: #64748b; }
        .doc-info strong { color: #0f172a; }

        /* Client */
        .client-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .client-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: bold; margin-bottom: 6px; }
        .client-name { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .client-info { font-size: 10px; color: #64748b; }

        /* Subject */
        .subject { margin-bottom: 20px; font-size: 12px; }
        .subject strong { color: #0f172a; }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #0f172a; color: white; padding: 10px 12px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .items-table th:last-child, .items-table th:nth-child(3), .items-table th:nth-child(4) { text-align: right; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        .items-table td:last-child, .items-table td:nth-child(3), .items-table td:nth-child(4) { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #f8fafc; }

        /* Totals */
        .totals-wrapper { display: table; width: 100%; }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box { display: table-cell; width: 45%; }
        .totals { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .total-row { display: table; width: 100%; padding: 8px 14px; font-size: 11px; }
        .total-row .label { display: table-cell; width: 60%; color: #64748b; }
        .total-row .value { display: table-cell; width: 40%; text-align: right; font-weight: bold; color: #0f172a; }
        .total-row.grand { background: #0f172a; color: white; padding: 12px 14px; font-size: 14px; }
        .total-row.grand .label, .total-row.grand .value { color: white; font-weight: bold; }

        /* Notes */
        .notes-section { margin-top: 30px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .notes-title { font-size: 10px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .notes-text { font-size: 10px; color: #64748b; white-space: pre-line; line-height: 1.5; }

        /* Footer */
        .footer { position: fixed; bottom: 30px; left: 40px; right: 40px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #fef3c7; color: #d97706; }
        .status-accepted { background: #d1fae5; color: #059669; }
        .status-rejected, .status-refused { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="header-left">
                <div class="company-logo">{{ $user->company_name ?? $user->name }}</div>
                <div class="company-info">
                    @if($user->address){{ $user->address }}<br>@endif
                    @if($user->city){{ $user->city }}@if($user->country), {{ $user->country }}@endif<br>@endif
                    @if($user->phone)Tél : {{ $user->phone }}<br>@endif
                    {{ $user->email }}
                    @if($user->siret)<br>SIRET : {{ $user->siret }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="doc-title">DEVIS</div>
                <div class="doc-info">
                    <strong>N° :</strong> {{ $quote->quote_number }}<br>
                    <strong>Date :</strong> {{ $quote->created_at->format('d/m/Y') }}<br>
                    @if($quote->valid_until)
                    <strong>Validité :</strong> {{ $quote->valid_until->format('d/m/Y') }}<br>
                    @endif
                    <span class="status-badge status-{{ $quote->status }}">{{ $quote->getStatusLabel() }}</span>
                </div>
            </div>
        </div>

        {{-- Client --}}
        <div class="client-box">
            <div class="client-label">Destinataire</div>
            <div class="client-name">{{ $quote->client_name }}</div>
            <div class="client-info">
                @if($quote->client_address){{ $quote->client_address }}<br>@endif
                @if($quote->client_email){{ $quote->client_email }}<br>@endif
                @if($quote->client_phone){{ $quote->client_phone }}@endif
            </div>
        </div>

        {{-- Subject --}}
        <div class="subject">
            <strong>Objet :</strong> {{ $quote->subject }}
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%; text-align: center;">Quantité</th>
                    <th style="width: 17%;">Prix unitaire HT</th>
                    <th style="width: 18%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td style="text-align: center;">{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['unit_price'], 2, ',', ' ') }} €</td>
                    <td>{{ number_format($item['total'] ?? ($item['quantity'] * $item['unit_price']), 2, ',', ' ') }} €</td>
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
                        <span class="value">{{ number_format($quote->subtotal, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row">
                        <span class="label">TVA ({{ $quote->tax_rate ?? 20 }}%)</span>
                        <span class="value">{{ number_format($quote->tax ?? $quote->tax_amount ?? 0, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row grand">
                        <span class="label">Total TTC</span>
                        <span class="value">{{ number_format($quote->total, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes & Conditions --}}
        @if($quote->notes || $quote->conditions)
        <div class="notes-section">
            @if($quote->notes)
            <div style="margin-bottom: 12px;">
                <div class="notes-title">Notes</div>
                <div class="notes-text">{{ $quote->notes }}</div>
            </div>
            @endif
            @if($quote->conditions)
            <div>
                <div class="notes-title">Conditions</div>
                <div class="notes-text">{{ $quote->conditions }}</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            {{ $user->company_name ?? $user->name }} — Document généré le {{ now()->format('d/m/Y à H:i') }} via ProxiPro
        </div>
    </div>
</body>
</html>
