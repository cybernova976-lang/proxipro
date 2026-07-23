<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->quote_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .email-header { background: linear-gradient(135deg, #0f172a, #1e3a5f); padding: 32px 28px; text-align: center; }
        .email-header h1 { color: white; font-size: 22px; margin: 0 0 4px; }
        .email-header p { color: rgba(255,255,255,0.65); font-size: 13px; margin: 0; }
        .email-body { padding: 28px; }
        .greeting { font-size: 15px; color: #334155; margin-bottom: 16px; line-height: 1.5; }
        .quote-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .quote-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
        .quote-number { font-size: 16px; font-weight: 700; color: #0f172a; }
        .quote-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        .status-sent { background: #fef3c7; color: #d97706; }
        .status-accepted { background: #d1fae5; color: #059669; }
        .quote-detail { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .quote-detail .label { color: #64748b; }
        .quote-detail .value { color: #0f172a; font-weight: 600; }
        .quote-total { background: #0f172a; color: white; border-radius: 8px; padding: 14px 20px; margin-top: 12px; display: flex; justify-content: space-between; align-items: center; }
        .quote-total .label { font-size: 13px; color: rgba(255,255,255,0.7); }
        .quote-total .value { font-size: 20px; font-weight: 800; }
        .custom-message { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 13px; color: #334155; line-height: 1.5; }
        .custom-message strong { display: block; color: #1e40af; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .email-footer { background: #f8fafc; padding: 20px 28px; text-align: center; border-top: 1px solid #e2e8f0; }
        .email-footer p { font-size: 11px; color: #94a3b8; margin: 0; line-height: 1.5; }
        .note { font-size: 12px; color: #64748b; margin-top: 16px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>📄 Devis {{ $quote->quote_number }}</h1>
            <p>{{ $user->company_name ?? $user->name }}</p>
        </div>

        <div class="email-body">
            <p class="greeting">
                Bonjour{{ $quote->client_name ? ' ' . $quote->client_name : '' }},<br><br>
                Veuillez trouver ci-joint le devis <strong>{{ $quote->quote_number }}</strong> émis par <strong>{{ $user->company_name ?? $user->name }}</strong>.
            </p>

            @if(!empty($customMessage))
            <div class="custom-message">
                <strong>💬 Message du prestataire</strong>
                {{ $customMessage }}
            </div>
            @endif

            <div class="quote-card">
                <div style="margin-bottom: 14px;">
                    <span class="quote-number">{{ $quote->quote_number }}</span>
                    <span class="quote-status status-{{ $quote->status }}" style="margin-left: 8px;">{{ $quote->getStatusLabel() }}</span>
                </div>

                <div class="quote-detail">
                    <span class="label">📋 Objet</span>
                    <span class="value">{{ $quote->subject }}</span>
                </div>
                <div class="quote-detail">
                    <span class="label">📅 Date d'émission</span>
                    <span class="value">{{ $quote->created_at->format('d/m/Y') }}</span>
                </div>
                @if($quote->valid_until)
                <div class="quote-detail">
                    <span class="label">⏳ Valide jusqu'au</span>
                    <span class="value">{{ $quote->valid_until->format('d/m/Y') }}</span>
                </div>
                @endif
                <div class="quote-detail">
                    <span class="label">Sous-total HT</span>
                    <span class="value">{{ number_format($quote->subtotal, 2, ',', ' ') }} €</span>
                </div>
                <div class="quote-detail">
                    <span class="label">TVA</span>
                    <span class="value">{{ number_format($quote->tax ?? $quote->tax_amount ?? 0, 2, ',', ' ') }} €</span>
                </div>

                <div class="quote-total">
                    <span class="label">Total TTC</span>
                    <span class="value">{{ number_format($quote->total, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <p class="note">
                📎 Le devis complet est joint à cet email au format PDF.<br>
                Pour toute question, n'hésitez pas à contacter <strong>{{ $user->company_name ?? $user->name }}</strong> directement.
            </p>
        </div>

        <div class="email-footer">
            <p>Cet email a été envoyé via <strong>{{ config('app.name', 'Lunamars') }}</strong> — La plateforme des professionnels de proximité.</p>
        </div>
    </div>
</body>
</html>
