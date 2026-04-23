<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle annonce pour vous</title>
</head>
<body style="margin:0;padding:0;background:#f6f8fc;font-family:Arial,sans-serif;color:#10213a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(37,99,235,.10);">
            <div style="background:linear-gradient(135deg,#0f766e,#14b8a6 55%,#38bdf8);padding:28px 30px;color:#ffffff;">
                <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">P</div>
                    <div style="font-size:18px;font-weight:700;letter-spacing:.02em;">{{ $appName }}</div>
                </div>
                <div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.82;">Alerte opportunité</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">Nouvelle {{ $serviceTypeLabel }} dans votre domaine</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.6;opacity:.92;">Une publication correspondant à vos compétences vient d'être ajoutée sur {{ $appName }}.</p>
            </div>

            <div style="padding:30px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Bonjour {{ $recipientName }},</p>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin:0 0 22px;">
                    <div style="font-size:20px;font-weight:700;color:#0f172a;margin-bottom:10px;">{{ $adTitle }}</div>
                    <div style="font-size:14px;line-height:1.8;color:#475569;">
                        <div><strong>Catégorie :</strong> {{ $category }}</div>
                        <div><strong>Lieu :</strong> {{ $location }}</div>
                        @if($budget)
                            <div><strong>Budget :</strong> {{ $budget }}</div>
                        @endif
                        <div><strong>Publié par :</strong> {{ $publisherName }}</div>
                    </div>
                </div>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $adUrl }}" style="display:inline-block;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 24px;border-radius:14px;box-shadow:0 10px 24px rgba(20,184,166,.22);">
                        Voir l'annonce et postuler
                    </a>
                </div>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#475569;">
                    Vous recevez cet e-mail car vos compétences correspondent à la catégorie « {{ $category }} ». Vous pouvez gérer vos préférences depuis votre Espace Pro.
                </p>

                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                    Besoin d'aide ? Contactez-nous à <a href="mailto:{{ $supportEmail }}" style="color:#0f766e;text-decoration:none;font-weight:600;">{{ $supportEmail }}</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>