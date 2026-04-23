<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre boost expire bientôt</title>
</head>
<body style="margin:0;padding:0;background:#fff7ed;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(245,158,11,.14);">
            <div style="background:linear-gradient(135deg,#f59e0b,#f97316 55%,#ef4444);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Visibilité annonce</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">{{ $icon }} Votre {{ $label }} expire bientôt</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Votre annonce va bientôt perdre son niveau de visibilité renforcée.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Bonjour {{ $recipientName }},</p>

                <div style="background:#fffaf0;border:1px solid #fed7aa;border-radius:14px;padding:20px;margin:0 0 22px;">
                    <div style="font-size:20px;font-weight:700;color:#9a3412;margin-bottom:10px;">{{ $adTitle }}</div>
                    <div style="font-size:14px;line-height:1.8;color:#7c2d12;">
                        <div><strong>Option :</strong> {{ $label }}</div>
                        <div><strong>Expire dans :</strong> {{ $timeText }}</div>
                    </div>
                </div>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $renewUrl }}" style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#f97316);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(245,158,11,.24);">
                        Renouveler maintenant
                    </a>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#57534e;">
                    Renouvelez cette option pour continuer à bénéficier d'une visibilité maximale sur la plateforme.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#78716c;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#c2410c;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>