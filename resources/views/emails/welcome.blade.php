<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur {{ $appName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fb; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1e293b;">

    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="display: inline-block; width: 56px; height: 56px; background: linear-gradient(135deg, #0f766e, #14b8a6); border-radius: 16px; color: white; font-size: 1.5rem; font-weight: 700; line-height: 56px; text-align: center; box-shadow: 0 4px 15px rgba(20, 184, 166, 0.30);">P</div>
            <h1 style="margin: 12px 0 0; font-size: 1.8rem; background: linear-gradient(135deg, #0f766e, #2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $appName }}</h1>
        </div>

        {{-- Main Card --}}
        <div style="background: white; border-radius: 16px; padding: 40px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">

            <h2 style="margin: 0 0 8px; font-size: 1.5rem; color: #1e293b;">Bienvenue {{ $user->name }} !</h2>
            <p style="color: #64748b; font-size: 1rem; line-height: 1.7; margin: 0 0 24px;">
                Nous sommes ravis de vous compter parmi la communauté {{ $appName }}. Votre compte a été créé avec succès et vous disposez déjà de <strong style="color: #0f766e;">5 points de bienvenue</strong> pour démarrer.
            </p>

            {{-- Ce que vous pouvez faire --}}
            <h3 style="font-size: 1.1rem; color: #1e293b; margin: 0 0 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Ce que vous pouvez faire dès maintenant</h3>

            <div style="margin-bottom: 24px;">
                <div style="display: flex; align-items: flex-start; margin-bottom: 14px;">
                    <div style="width: 36px; height: 36px; min-width: 36px; background: #eff6ff; border-radius: 10px; text-align: center; line-height: 36px; font-size: 1rem; margin-right: 12px;">📢</div>
                    <div>
                        <strong style="color: #1e293b;">Publiez vos annonces</strong>
                        <p style="margin: 2px 0 0; color: #64748b; font-size: 0.9rem; line-height: 1.5;">Proposez vos services ou recherchez un prestataire qualifié près de chez vous.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; margin-bottom: 14px;">
                    <div style="width: 36px; height: 36px; min-width: 36px; background: #f0fdf4; border-radius: 10px; text-align: center; line-height: 36px; font-size: 1rem; margin-right: 12px;">💬</div>
                    <div>
                        <strong style="color: #1e293b;">Échangez en direct</strong>
                        <p style="margin: 2px 0 0; color: #64748b; font-size: 0.9rem; line-height: 1.5;">Contactez les prestataires et les clients via notre messagerie intégrée.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; margin-bottom: 14px;">
                    <div style="width: 36px; height: 36px; min-width: 36px; background: #fefce8; border-radius: 10px; text-align: center; line-height: 36px; font-size: 1rem; margin-right: 12px;">⭐</div>
                    <div>
                        <strong style="color: #1e293b;">Avis & réputation</strong>
                        <p style="margin: 2px 0 0; color: #64748b; font-size: 0.9rem; line-height: 1.5;">Construisez votre réputation grâce aux avis vérifiés de vos clients.</p>
                    </div>
                </div>
            </div>

            {{-- Bloc PRO --}}
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%); border-radius: 14px; padding: 24px; margin-bottom: 24px; border: 1px solid rgba(99, 102, 241, 0.15);">
                <h3 style="margin: 0 0 10px; font-size: 1.15rem; color: #0f766e;">💼 Passez au niveau supérieur avec le compte Pro</h3>
                <p style="color: #475569; font-size: 0.92rem; line-height: 1.6; margin: 0 0 16px;">
                    Les professionnels sur {{ $appName }} bénéficient d'avantages exclusifs pour développer leur activité :
                </p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Visibilité prioritaire</strong> — Vos annonces apparaissent en premier dans les résultats
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Alertes en temps réel</strong> — Recevez par email les nouvelles demandes qui correspondent à vos compétences
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Outils Pro</strong> — Créez des devis et factures professionnels en quelques clics
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Badge vérifié</strong> — Inspirez confiance avec un profil professionnel certifié
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Gestion clients</strong> — Suivez vos clients, devis envoyés et factures depuis votre espace dédié
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top; width: 28px;">
                            <span style="color: #22c55e; font-weight: bold;">✓</span>
                        </td>
                        <td style="padding: 8px 0; color: #334155; font-size: 0.9rem;">
                            <strong>Boost & Urgent</strong> — Mettez vos annonces en avant pour toucher plus de clients
                        </td>
                    </tr>
                </table>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ url('/pricing') }}" style="display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #0f766e, #2563eb); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                        Découvrir les offres Pro →
                    </a>
                </div>
            </div>

            {{-- CTA Principal --}}
            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ url('/feed') }}" style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #3a86ff, #2667cc); color: white; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 1rem; box-shadow: 0 4px 12px rgba(58, 134, 255, 0.35);">
                    Commencer maintenant
                </a>
                <p style="margin: 14px 0 0; color: #94a3b8; font-size: 0.85rem;">
                    Accédez au fil d'actualité et découvrez les offres autour de vous
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div style="text-align: center; margin-top: 32px; color: #94a3b8; font-size: 0.8rem; line-height: 1.5;">
            <p style="margin: 0;">© {{ date('Y') }} {{ $appName }} — La plateforme des services de proximité</p>
            <p style="margin: 6px 0 0;">Cet email a été envoyé à {{ $user->email }}</p>
            <p style="margin: 6px 0 0;">Support : <a href="mailto:{{ $supportEmail }}" style="color: #0f766e; text-decoration: none; font-weight: 600;">{{ $supportEmail }}</a></p>
        </div>
    </div>
</body>
</html>
