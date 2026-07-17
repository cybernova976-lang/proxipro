@php
    $siteShareName = config('app.name', 'ProxiPro');
    $siteShareUrl = url('/');
    $siteShareTitle = $siteShareName.' — Le bon service, près de chez vous';
    $siteShareDescription = 'La plateforme qui met en relation particuliers et professionnels pour trouver ou proposer des services de proximité.';
    $siteShareText = 'Découvrez '.$siteShareName.' : '.$siteShareDescription;
    $siteShareImage = asset('images/social-card.png');
@endphp

<style>
    .site-share-overlay[hidden] {
        display: none !important;
    }

    .site-share-overlay {
        position: fixed;
        inset: 0;
        z-index: 12000;
        display: grid;
        place-items: center;
        padding: 20px;
        background: rgba(15, 23, 42, .68);
        opacity: 0;
        backdrop-filter: blur(7px);
        transition: opacity .18s ease;
    }

    .site-share-overlay.is-open {
        opacity: 1;
    }

    .site-share-dialog {
        width: min(540px, 100%);
        max-height: calc(100vh - 32px);
        overflow-y: auto;
        border: 0;
        border-radius: 22px;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 30px 90px rgba(15, 23, 42, .3);
        transform: translateY(12px) scale(.985);
        transition: transform .18s ease;
    }

    .site-share-overlay.is-open .site-share-dialog {
        transform: translateY(0) scale(1);
    }

    .site-share-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 20px 14px;
    }

    .site-share-head h2 {
        margin: 0 0 4px;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .site-share-head p {
        margin: 0;
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
    }

    .site-share-close {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border: 0;
        border-radius: 50%;
        background: #f1f5f9;
        color: #475569;
        font-size: 1.25rem;
        line-height: 1;
    }

    .site-share-body {
        padding: 0 20px 20px;
    }

    .site-share-preview {
        overflow: hidden;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
    }

    .site-share-preview img {
        display: block;
        width: 100%;
        aspect-ratio: 1200 / 630;
        object-fit: cover;
    }

    .site-share-preview-copy {
        padding: 12px 14px 14px;
    }

    .site-share-preview-copy strong {
        display: block;
        margin-bottom: 3px;
        color: #0f172a;
        font-size: .92rem;
    }

    .site-share-preview-copy span {
        display: block;
        color: #64748b;
        font-size: .78rem;
        line-height: 1.4;
    }

    .site-share-native {
        display: none;
        width: 100%;
        min-height: 46px;
        margin-bottom: 9px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        font-weight: 750;
    }

    .site-share-native:disabled {
        opacity: .7;
    }

    .site-share-photo-hint {
        margin: 0 0 14px;
        color: #64748b;
        text-align: center;
        font-size: .74rem;
    }

    .site-share-options {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 9px;
        margin-bottom: 16px;
    }

    .site-share-option {
        min-width: 0;
        padding: 11px 7px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        color: #334155 !important;
        text-align: center;
        text-decoration: none !important;
        font-size: .74rem;
        font-weight: 650;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .site-share-option:hover {
        transform: translateY(-2px);
        border-color: #c7d2fe;
        box-shadow: 0 8px 18px rgba(79, 70, 229, .1);
    }

    .site-share-option i {
        display: block;
        margin-bottom: 6px;
        font-size: 1.2rem;
    }

    .site-share-copy {
        display: flex;
        gap: 8px;
    }

    .site-share-copy input {
        min-width: 0;
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        background: #f8fafc;
        color: #475569;
        font-size: .78rem;
    }

    .site-share-copy button {
        flex: 0 0 auto;
        padding: 10px 14px;
        border: 0;
        border-radius: 10px;
        background: #4f46e5;
        color: #fff;
        font-size: .78rem;
        font-weight: 750;
    }

    .site-share-status {
        min-height: 20px;
        margin-top: 8px;
        color: #15803d;
        text-align: center;
        font-size: .76rem;
        font-weight: 650;
    }

    @media (max-width: 576px) {
        .site-share-overlay {
            align-items: end;
            padding: 8px;
        }

        .site-share-dialog {
            border-radius: 20px 20px 12px 12px;
        }

        .site-share-options {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .site-share-copy {
            flex-direction: column;
        }
    }
</style>

<div class="site-share-overlay" id="sitePlatformShareModal" hidden aria-hidden="true">
    <section class="site-share-dialog" role="dialog" aria-modal="true" aria-labelledby="sitePlatformShareTitle">
        <header class="site-share-head">
            <div>
                <h2 id="sitePlatformShareTitle">Partager {{ $siteShareName }}</h2>
                <p>Faites découvrir la plateforme à vos proches, clients ou partenaires.</p>
            </div>
            <button type="button" class="site-share-close" data-site-share-close aria-label="Fermer">&times;</button>
        </header>

        <div class="site-share-body">
            <div class="site-share-preview">
                <img src="{{ $siteShareImage }}" alt="Aperçu de {{ $siteShareName }}" width="1200" height="630">
                <div class="site-share-preview-copy">
                    <strong>{{ $siteShareTitle }}</strong>
                    <span>{{ $siteShareDescription }}</span>
                </div>
            </div>

            <button type="button" class="site-share-native" data-site-native-share>
                <i class="fas fa-share-nodes me-2"></i>Partager avec mon appareil
            </button>
            <p class="site-share-photo-hint"><i class="fas fa-image me-1"></i>Le visuel de la plateforme sera joint lorsque l’application l’accepte.</p>

            <div class="site-share-options">
                <a class="site-share-option" href="https://wa.me/?text={{ rawurlencode($siteShareText.' '.$siteShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur WhatsApp">
                    <i class="fab fa-whatsapp" style="color:#16a34a"></i>WhatsApp
                </a>
                <a class="site-share-option" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($siteShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur Facebook">
                    <i class="fab fa-facebook-f" style="color:#1877f2"></i>Facebook
                </a>
                <a class="site-share-option" href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($siteShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur LinkedIn">
                    <i class="fab fa-linkedin-in" style="color:#0a66c2"></i>LinkedIn
                </a>
                <a class="site-share-option" href="https://twitter.com/intent/tweet?url={{ rawurlencode($siteShareUrl) }}&text={{ rawurlencode($siteShareText) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur X">
                    <i class="fab fa-twitter" style="color:#111827"></i>X
                </a>
                <a class="site-share-option" href="https://t.me/share/url?url={{ rawurlencode($siteShareUrl) }}&text={{ rawurlencode($siteShareText) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur Telegram">
                    <i class="fab fa-telegram" style="color:#229ed9"></i>Telegram
                </a>
                <a class="site-share-option" href="mailto:?subject={{ rawurlencode($siteShareTitle) }}&body={{ rawurlencode($siteShareText."\n\n".$siteShareUrl) }}" aria-label="Partager par e-mail">
                    <i class="fas fa-envelope" style="color:#64748b"></i>E-mail
                </a>
            </div>

            <div class="site-share-copy">
                <input type="text" value="{{ $siteShareUrl }}" readonly aria-label="Adresse de la plateforme">
                <button type="button" data-site-share-copy><i class="fas fa-copy me-1"></i><span>Copier le message</span></button>
            </div>
            <div class="site-share-status" data-site-share-status role="status" aria-live="polite"></div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('sitePlatformShareModal');
    const triggers = document.querySelectorAll('[data-site-share-trigger]');
    if (!overlay || triggers.length === 0) return;

    const closeButton = overlay.querySelector('[data-site-share-close]');
    const nativeButton = overlay.querySelector('[data-site-native-share]');
    const copyButton = overlay.querySelector('[data-site-share-copy]');
    const status = overlay.querySelector('[data-site-share-status]');
    const shareData = {
        title: @json($siteShareTitle),
        text: @json($siteShareText),
        url: @json($siteShareUrl),
    };
    const shareImage = @json($siteShareImage);
    const completeShareText = `${shareData.text}\n\n${shareData.url}`;
    let previousFocus = null;

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '#15803d';
    }

    function openShareDialog() {
        previousFocus = document.activeElement;
        setStatus('');
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            closeButton.focus();
        });
    }

    function closeShareDialog() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => {
            overlay.hidden = true;
            previousFocus?.focus?.();
        }, 180);
    }

    async function copyShareMessage() {
        let copied = false;

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(completeShareText);
                copied = true;
            } catch (error) {
                copied = false;
            }
        }

        if (!copied) {
            const fallback = document.createElement('textarea');
            fallback.value = completeShareText;
            fallback.setAttribute('readonly', '');
            fallback.style.position = 'fixed';
            fallback.style.opacity = '0';
            document.body.appendChild(fallback);
            fallback.select();
            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            } finally {
                fallback.remove();
            }
        }

        if (!copied) {
            setStatus('La copie automatique a échoué. Sélectionnez le lien manuellement.', true);
            return;
        }

        copyButton.querySelector('i').className = 'fas fa-check me-1';
        copyButton.querySelector('span').textContent = 'Copié';
        setStatus('Le message de présentation et le lien ont été copiés.');
        window.setTimeout(() => {
            copyButton.querySelector('i').className = 'fas fa-copy me-1';
            copyButton.querySelector('span').textContent = 'Copier le message';
        }, 2200);
    }

    async function buildNativeShareData() {
        if (typeof navigator.canShare !== 'function' || typeof File === 'undefined') return shareData;

        try {
            const response = await fetch(shareImage, { credentials: 'same-origin' });
            if (!response.ok) return shareData;
            const blob = await response.blob();
            if (blob.type !== 'image/png') return shareData;

            const imageFile = new File([blob], 'presentation-{{ Str::slug($siteShareName) ?: 'plateforme' }}.png', { type: 'image/png' });
            const fileShareData = {
                title: shareData.title,
                text: completeShareText,
                files: [imageFile],
            };

            return navigator.canShare(fileShareData) ? fileShareData : shareData;
        } catch (error) {
            return shareData;
        }
    }

    triggers.forEach(trigger => trigger.addEventListener('click', event => {
        event.preventDefault();
        openShareDialog();
    }));
    closeButton.addEventListener('click', closeShareDialog);
    copyButton.addEventListener('click', copyShareMessage);
    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeShareDialog();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !overlay.hidden) closeShareDialog();
    });

    if (navigator.share) {
        nativeButton.style.display = 'block';
        nativeButton.addEventListener('click', async () => {
            try {
                nativeButton.disabled = true;
                const nativeShareData = await buildNativeShareData();
                await navigator.share(nativeShareData);
                setStatus('Plateforme partagée avec succès.');
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    setStatus('Le partage natif est indisponible. Utilisez une option ci-dessous.', true);
                }
            } finally {
                nativeButton.disabled = false;
            }
        });
    }
});
</script>
