<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle candidature reçue</title>
</head>
<body style="margin:0;padding:0;background:#eff6ff;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(59,130,246,.12);">
            <div style="background:linear-gradient(135deg,#2563eb,#3b82f6 55%,#60a5fa);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Candidature annonce</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">📩 Nouvelle candidature reçue</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Un utilisateur est intéressé par votre annonce.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Bonjour {{ $recipientName }},</p>

                <div style="background:#f8fafc;border:1px solid #bfdbfe;border-radius:14px;padding:20px;margin:0 0 22px;">
                    <div style="font-size:14px;font-weight:700;color:#1d4ed8;margin-bottom:10px;">Annonce concernée</div>
                    <div style="font-size:18px;font-weight:700;color:#1e3a8a;margin-bottom:10px;">{{ $adTitle }}</div>
                    <div style="font-size:14px;line-height:1.8;color:#334155;">
                        <div><strong>Candidat :</strong> {{ $candidateName }}</div>
                        @if($candidateMessage)
                            <div style="margin-top:10px;"><strong>Message :</strong> {{ $candidateMessage }}</div>
                        @endif
                    </div>
                </div>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $adUrl }}" style="display:inline-block;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(59,130,246,.22);">
                        Voir l'annonce
                    </a>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#475569;">
                    Vous pouvez maintenant échanger avec cette personne depuis la messagerie de la plateforme.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#2563eb;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>