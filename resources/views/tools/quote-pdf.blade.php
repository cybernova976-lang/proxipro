<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $document->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; padding: 30px; }

        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .header-left { max-width: 55%; }
        .header-right { text-align: right; }

        /* Company name as logo */
        .company-logo {
            background: #4f46e5;
            color: #ffffff;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 12px 24px;
            display: inline-block;
            line-height: 1;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .company-info { font-size: 9px; color: #64748b; line-height: 1.6; }

        .doc-title { font-size: 22px; font-weight: 700; color: #4f46e5; margin-bottom: 4px; }
        .doc-number { font-size: 11px; color: #64748b; margin-bottom: 2px; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #dbeafe; color: #2563eb; }
        .status-accepted { background: #dcfce7; color: #16a34a; }

        .info-boxes { display: flex; gap: 20px; margin-bottom: 25px; }
        .info-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
        .info-box-title { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
        .info-box-name { font-size: 12px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
        .info-box-detail { font-size: 9px; color: #64748b; }

        .subject { background: #eef2ff; border-left: 3px solid #4f46e5; padding: 10px 14px; margin-bottom: 20px; border-radius: 0 6px 6px 0; }
        .subject-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #4f46e5; }
        .subject-text { font-size: 12px; font-weight: 600; color: #1e293b; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th { background: #1e293b; color: white; padding: 8px 12px; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        .items-table thead th:first-child { border-radius: 6px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        .items-table thead th.text-right { text-align: right; }
        .items-table thead th.text-center { text-align: center; }
        .items-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .items-table tbody tr:nth-child(even) { background: #fafbfc; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tbody td.text-center { text-align: center; }

        .totals { width: 280px; margin-left: auto; margin-bottom: 25px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 10px; }
        .totals-row.total { background: #1e293b; color: white; padding: 10px 14px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-top: 6px; }

        .notes-section { margin-bottom: 15px; }
        .notes-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 4px; }
        .notes-content { font-size: 9px; color: #475569; white-space: pre-line; background: #f8fafc; padding: 10px; border-radius: 6px; }

        .footer { position: fixed; bottom: 20px; left: 30px; right: 30px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-logo">{{ $emitter['company'] ?? $emitter['name'] }}</div>
        </div>
        <div class="header-right">
            <div class="doc-title">DEVIS</div>
            <div class="doc-number">N° {{ $document->quote_number }}</div>
            <div class="doc-number">Date : {{ $document->created_at->format('d/m/Y') }}</div>
            @if($document->valid_until)
                <div class="doc-number">Valide jusqu'au : {{ \Carbon\Carbon::parse($document->valid_until)->format('d/m/Y') }}</div>
            @endif
            <div style="margin-top: 6px;">
                <span class="status-badge status-{{ $document->status }}">{{ ucfirst($document->status) }}</span>
            </div>
        </div>
    </div>

    <div class="info-boxes">
        <div class="info-box">
            <div class="info-box-title">Émetteur</div>
            <div class="info-box-name">{{ $emitter['company'] ?? $emitter['name'] }}</div>
            @if($emitter['company'] && $emitter['name'] !== $emitter['company'])
                <div class="info-box-detail">{{ $emitter['name'] }}</div>
            @endif
            @if($emitter['address'])<div class="info-box-detail">{{ $emitter['address'] }}</div>@endif
            @if($emitter['email'])<div class="info-box-detail">{{ $emitter['email'] }}</div>@endif
            @if($emitter['phone'])<div class="info-box-detail">Tél : {{ $emitter['phone'] }}</div>@endif
            @if($emitter['siret'])<div class="info-box-detail">SIRET : {{ $emitter['siret'] }}</div>@endif
        </div>
        <div class="info-box">
            <div class="info-box-title">Destinataire</div>
            <div class="info-box-name">{{ $document->client_name }}</div>
            @if($document->client_address)<div class="info-box-detail">{{ $document->client_address }}</div>@endif
            @if($document->client_email)<div class="info-box-detail">{{ $document->client_email }}</div>@endif
            @if($document->client_phone)<div class="info-box-detail">{{ $document->client_phone }}</div>@endif
        </div>
    </div>

    @if($document->subject)
        <div class="subject">
            <div class="subject-label">Objet</div>
            <div class="subject-text">{{ $document->subject }}</div>
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-center" style="width: 12%;">Quantité</th>
                <th class="text-right" style="width: 19%;">Prix unitaire HT</th>
                <th class="text-right" style="width: 19%;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($item['total'], 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Sous-total HT</span>
            <span>{{ number_format($document->subtotal, 2, ',', ' ') }} €</span>
        </div>
        <div class="totals-row">
            <span>TVA ({{ $document->tax_rate ? number_format($document->tax_rate, 0) : 20 }}%)</span>
            <span>{{ number_format($document->tax_amount, 2, ',', ' ') }} &euro;</span>
        </div>
        <div class="totals-row total">
            <span>Total TTC</span>
            <span>{{ number_format($document->total, 2, ',', ' ') }} €</span>
        </div>
    </div>

    @if($document->notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $document->notes }}</div>
        </div>
    @endif

    @if($document->conditions)
        <div class="notes-section">
            <div class="notes-title">Conditions</div>
            <div class="notes-content">{{ $document->conditions }}</div>
        </div>
    @endif

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} via ProxiPro — {{ $emitter['company'] ?? $emitter['name'] }}
    </div>
</body>
</html>
