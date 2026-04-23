<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre adresse e-mail</title>
</head>
<body style="margin:0;padding:0;background:#effcf6;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(16,185,129,.12);">
            <div style="background:linear-gradient(135deg,#0f766e,#10b981 55%,#34d399);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Activation du compte</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">Confirmez votre adresse e-mail</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Une dernière étape pour activer pleinement votre compte {{ $appName }}.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">
                    Bonjour {{ $userName ?: 'utilisateur' }},
                </p>

                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                    Cliquez sur le bouton ci-dessous pour vérifier votre adresse e-mail et finaliser l'activation de votre compte.
                </p>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $verificationUrl }}" style="display:inline-block;background:linear-gradient(135deg,#0f766e,#10b981);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(16,185,129,.22);">
                        Vérifier mon adresse e-mail
                    </a>
                </div>

                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:18px 20px;margin:20px 0;">
                    <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#0f766e;margin-bottom:8px;">Lien direct</div>
                    <div style="font-size:13px;line-height:1.7;color:#475569;word-break:break-all;">{{ $verificationUrl }}</div>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#475569;">
                    Si vous n'avez pas créé de compte, vous pouvez ignorer cet e-mail en toute sécurité.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#0f766e;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>