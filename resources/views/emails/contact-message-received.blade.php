<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dfe7f1;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px;background:#4338ca;color:#ffffff;">
                            <div style="font-size:22px;font-weight:700;">{{ config('app.name', 'Prokejem') }}</div>
                            <div style="margin-top:5px;font-size:14px;opacity:.9;">Nouveau message reçu par le formulaire de contact</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;line-height:1.6;">
                            <p style="margin:0 0 6px;font-size:14px;color:#566176;"><strong>De :</strong> {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;</p>
                            <p style="margin:0 0 18px;font-size:14px;color:#566176;"><strong>Sujet :</strong> {{ $contactMessage->subject }}</p>

                            <div style="padding:18px;background:#f6f8fc;border-left:4px solid #4f46e5;border-radius:8px;font-size:15px;">{!! nl2br(e($contactMessage->message)) !!}</div>

                            <p style="margin:26px 0 0;text-align:center;">
                                <a href="{{ $adminUrl }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 22px;border-radius:10px;">Répondre depuis l’administration</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#f6f8fc;color:#6d778a;font-size:12px;line-height:1.5;">
                            Répondre depuis l’administration conserve la réponse avec le message. Répondre à cet e-mail écrit directement à {{ $contactMessage->email }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
