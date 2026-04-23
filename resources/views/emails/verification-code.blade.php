<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification {{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                    
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding:32px 40px; text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:10px;">
                                <div style="width:42px; height:42px; border-radius:14px; background:rgba(255,255,255,0.16); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:20px; font-weight:700;">P</div>
                                <h1 style="color:#ffffff; font-size:22px; font-weight:700; margin:0; letter-spacing:-0.3px;">{{ $appName }}</h1>
                            </div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:40px 40px 20px 40px;">
                            <h2 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0 0 8px 0; text-align:center;">
                                Vérifiez votre adresse e-mail
                            </h2>
                            <p style="color:#6b7280; font-size:14px; line-height:1.6; margin:0 0 28px 0; text-align:center;">
                                Bonjour {{ $userName }},<br>
                                Voici votre code de vérification pour finaliser votre inscription sur {{ $appName }}.
                            </p>
                        </td>
                    </tr>

                    {{-- Code --}}
                    <tr>
                        <td style="padding:0 40px 28px 40px; text-align:center;">
                            <div style="background-color:#f0f4ff; border:2px dashed #2563eb; border-radius:12px; padding:24px; display:inline-block;">
                                <span style="font-size:36px; font-weight:800; letter-spacing:10px; color:#1d4ed8; font-family:monospace;">{{ $code }}</span>
                            </div>
                        </td>
                    </tr>

                    {{-- Info --}}
                    <tr>
                        <td style="padding:0 40px 32px 40px;">
                            <p style="color:#9ca3af; font-size:13px; line-height:1.5; text-align:center; margin:0;">
                                Ce code expire dans <strong style="color:#6b7280;">15 minutes</strong>.<br>
                                Si vous n'avez pas créé de compte, ignorez cet e-mail.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 40px 32px 40px;">
                            <div style="background-color:#fef3c7; border-radius:8px; padding:14px 18px;">
                                <p style="color:#92400e; font-size:12px; line-height:1.5; margin:0;">
                                    🔒 <strong>Sécurité :</strong> Ne partagez jamais ce code. L'équipe ProxiPro ne vous demandera jamais votre code de vérification.
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f9fafb; padding:24px 40px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="color:#9ca3af; font-size:12px; margin:0;">
                                © {{ date('Y') }} {{ $appName }} — Trouvez le bon pro, près de chez vous.
                            </p>
                            <p style="color:#9ca3af; font-size:12px; margin:8px 0 0;">
                                Support : <a href="mailto:{{ $supportEmail }}" style="color:#2563eb; text-decoration:none; font-weight:600;">{{ $supportEmail }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
