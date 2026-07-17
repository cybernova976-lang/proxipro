<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Facture {{ $invoice->invoice_number }}</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#334155;">
<div style="max-width:620px;margin:0 auto;background:#fff;">
    <div style="padding:28px;background:#312e81;color:#fff;text-align:center;">
        <h1 style="margin:0 0 6px;font-size:22px;">Facture {{ $invoice->invoice_number }}</h1>
        <div style="opacity:.8;">{{ $user->company_name ?? $user->name }}</div>
    </div>
    <div style="padding:28px;line-height:1.55;">
        <p>Bonjour{{ $invoice->client_name ? ' '.$invoice->client_name : '' }},</p>
        <p>Veuillez trouver en pièce jointe la facture émise par <strong>{{ $user->company_name ?? $user->name }}</strong>.</p>
        @if(!empty($customMessage))<div style="margin:20px 0;padding:14px 16px;background:#eff6ff;border-left:4px solid #3b82f6;">{{ $customMessage }}</div>@endif
        <div style="margin:20px 0;padding:18px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
            <div><strong>Objet :</strong> {{ $invoice->subject }}</div>
            <div><strong>Date d’émission :</strong> {{ ($invoice->finalized_at ?? $invoice->created_at)->format('d/m/Y') }}</div>
            @if($invoice->due_date)<div><strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>@endif
            <div style="margin-top:12px;font-size:20px;color:#312e81;"><strong>Total TTC : {{ number_format($invoice->total, 2, ',', ' ') }} €</strong></div>
        </div>
        <p style="font-size:13px;color:#64748b;">La facture complète est jointe au format PDF. Pour toute question, contactez directement l’émetteur.</p>
    </div>
    <div style="padding:18px;background:#f8fafc;text-align:center;font-size:11px;color:#94a3b8;">Document transmis via ProxiPro.</div>
</div>
</body>
</html>
