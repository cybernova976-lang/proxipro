<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse de {{ config('app.name', 'Prokejem') }}</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dfe7f1;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px;background:#0f56b3;color:#ffffff;">
                            <div style="font-size:22px;font-weight:700;">{{ config('app.name', 'Prokejem') }}</div>
                            <div style="margin-top:5px;font-size:14px;opacity:.9;">Réponse à votre message</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;line-height:1.6;">
                            <p style="margin:0 0 18px;">Bonjour {{ $contactMessage->name }},</p>

                            <div style="margin:0 0 24px;font-size:16px;">{!! nl2br(e($reply)) !!}</div>

                            <div style="margin-top:26px;padding:18px;background:#f6f8fc;border-left:4px solid #0f56b3;border-radius:8px;">
                                <div style="font-weight:700;margin-bottom:8px;">Votre message initial</div>
                                <div style="font-size:14px;color:#566176;"><strong>Sujet :</strong> {{ $contactMessage->subject }}</div>
                                <div style="margin-top:8px;font-size:14px;color:#566176;">{!! nl2br(e($contactMessage->message)) !!}</div>
                            </div>

                            <p style="margin:26px 0 0;">Cordialement,<br>L’équipe {{ config('app.name', 'Prokejem') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#f6f8fc;color:#6d778a;font-size:12px;line-height:1.5;">
                            Vous recevez cet e-mail parce que vous avez utilisé le formulaire de contact de {{ config('app.name', 'Prokejem') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
