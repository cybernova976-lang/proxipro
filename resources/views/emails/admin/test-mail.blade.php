<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test e-mail ProxiPro</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.08);">
            <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);padding:24px 28px;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;opacity:.85;">Diagnostic e-mail</div>
                <h1 style="margin:8px 0 0;font-size:28px;line-height:1.2;">Test de messagerie ProxiPro</h1>
            </div>

            <div style="padding:28px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                    Cet e-mail confirme que la configuration d'envoi est opérationnelle.
                </p>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:20px 0;">
                    <h2 style="margin:0 0 14px;font-size:16px;color:#0f172a;">Résumé de la configuration</h2>
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr><td style="padding:8px 0;color:#64748b;">Mailer</td><td style="padding:8px 0;font-weight:600;">{{ $details['mailer'] ?? 'n/a' }}</td></tr>
                        <tr><td style="padding:8px 0;color:#64748b;">Adresse d'envoi</td><td style="padding:8px 0;font-weight:600;">{{ $details['from_name'] ?? '' }} &lt;{{ $details['from_address'] ?? 'n/a' }}&gt;</td></tr>
                        <tr><td style="padding:8px 0;color:#64748b;">Adresse de réponse</td><td style="padding:8px 0;font-weight:600;">{{ $details['reply_to_name'] ?? '' }} &lt;{{ $details['reply_to_address'] ?? 'n/a' }}&gt;</td></tr>
                        <tr><td style="padding:8px 0;color:#64748b;">Adresse admin</td><td style="padding:8px 0;font-weight:600;">{{ $details['admin_email'] ?? 'n/a' }}</td></tr>
                        <tr><td style="padding:8px 0;color:#64748b;">Environnement</td><td style="padding:8px 0;font-weight:600;">{{ $details['environment'] ?? 'n/a' }}</td></tr>
                        <tr><td style="padding:8px 0;color:#64748b;">Envoyé le</td><td style="padding:8px 0;font-weight:600;">{{ $details['sent_at'] ?? 'n/a' }}</td></tr>
                    </table>
                </div>

                <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
                    Si vous recevez cet e-mail avec la bonne adresse d'expéditeur et la bonne adresse de réponse,
                    la configuration Brevo/Laravel est correctement branchée.
                </p>
            </div>
        </div>
    </div>
</body>
</html>