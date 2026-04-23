<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
</head>
<body style="margin:0;padding:0;background:#eef4ff;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(37,99,235,.12);">
            <div style="background:linear-gradient(135deg,#1d4ed8,#2563eb 55%,#38bdf8);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Sécurité du compte</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">Réinitialisez votre mot de passe</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Une demande de changement de mot de passe vient d'être enregistrée pour votre compte {{ $appName }}.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">
                    Bonjour {{ $userName ?: 'utilisateur' }},
                </p>

                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
                    Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Pour votre sécurité, ce lien reste valide pendant <strong>60 minutes</strong>.
                </p>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $resetUrl }}" style="display:inline-block;background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(37,99,235,.25);">
                        Réinitialiser mon mot de passe
                    </a>
                </div>

                <div style="background:#f8fafc;border:1px solid #dbeafe;border-radius:14px;padding:18px 20px;margin:20px 0;">
                    <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#1d4ed8;margin-bottom:8px;">Lien direct</div>
                    <div style="font-size:13px;line-height:1.7;color:#475569;word-break:break-all;">{{ $resetUrl }}</div>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#475569;">
                    Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail. Aucun changement ne sera appliqué à votre compte.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#1d4ed8;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>