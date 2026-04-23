<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message reçu</title>
</head>
<body style="margin:0;padding:0;background:#f0fdf4;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(34,197,94,.12);">
            <div style="background:linear-gradient(135deg,#15803d,#22c55e 55%,#0ea5e9);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Messagerie</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">💬 Nouveau message de {{ $senderName }}</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Un nouveau message vous attend dans votre conversation.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Bonjour {{ $recipientName }},</p>

                <div style="background:#f7fee7;border:1px solid #bbf7d0;border-radius:14px;padding:20px;margin:0 0 22px;">
                    <div style="font-size:14px;font-weight:700;color:#166534;margin-bottom:10px;">Message de {{ $senderName }}</div>
                    <div style="font-size:15px;line-height:1.8;color:#365314;font-style:italic;">"{{ $preview }}"</div>
                </div>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $conversationUrl }}" style="display:inline-block;background:linear-gradient(135deg,#15803d,#22c55e);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(34,197,94,.22);">
                        Lire la conversation
                    </a>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#475569;">
                    Vous pouvez répondre directement depuis la messagerie de la plateforme.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#15803d;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>