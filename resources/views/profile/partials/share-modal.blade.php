@php
    $profileShareModalId = $modalId ?? 'shareProfileModal';
    $profileShareTriggerId = $triggerId ?? 'shareProfileBtn';
    $profileShareName = $user->company_name ?: $user->name;
    $profileShareRole = $user->profession ?: ($user->services->first()?->subcategory ?? null);
    $profileShareUrl = route('profile.public', $user->id);
    $profileShareTitle = $profileShareName.($profileShareRole ? ' — '.$profileShareRole : '').' | '.config('app.name', 'ProxiPro');
    $profileShareText = 'Découvrez le profil de '.$profileShareName.($profileShareRole ? ', '.$profileShareRole : '').' sur '.config('app.name', 'ProxiPro').'.';
    $profileShareLocation = collect([$user->city, $user->country])->filter()->implode(', ');
@endphp

<style>
    .profile-share-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 20px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .2);
    }

    .profile-share-preview {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: linear-gradient(145deg, #f8fafc, #fff);
    }

    .profile-share-avatar {
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: linear-gradient(135deg, #3a86ff, #7c3aed);
        color: #fff;
        font-size: 1.35rem;
        font-weight: 800;
    }

    .profile-share-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-share-options {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .profile-share-option {
        min-width: 0;
        padding: 12px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        color: #334155;
        text-align: center;
        text-decoration: none;
        font-size: .76rem;
        font-weight: 600;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .profile-share-option:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 8px 18px rgba(59, 130, 246, .1);
        color: #1e293b;
    }

    .profile-share-option i {
        display: block;
        margin-bottom: 7px;
        font-size: 1.25rem;
    }

    .profile-share-native {
        display: none;
        width: 100%;
        margin-bottom: 14px;
        padding: 12px 16px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #3a86ff, #2563eb);
        color: #fff;
        font-weight: 700;
    }

    .profile-share-copy .form-control {
        min-width: 0;
        font-size: .8rem;
        background: #f8fafc;
    }

    .profile-share-status {
        min-height: 22px;
        margin-top: 9px;
        color: #15803d;
        text-align: center;
        font-size: .8rem;
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .profile-share-modal .modal-dialog {
            align-items: flex-end;
            min-height: calc(100% - 1rem);
            margin: .5rem;
        }

        .profile-share-modal .modal-content {
            border-radius: 20px 20px 12px 12px;
        }
    }
</style>

<div class="modal fade profile-share-modal" id="{{ $profileShareModalId }}" tabindex="-1" aria-labelledby="{{ $profileShareModalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="{{ $profileShareModalId }}Label">Partager ce profil</h5>
                    <p class="text-muted small mb-0">Envoyez ce profil à une personne susceptible d’être intéressée.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="profile-share-preview mb-3">
                    <div class="profile-share-avatar">
                        @if($user->avatar)
                            <img src="{{ storage_url($user->avatar) }}" alt="Photo de {{ $profileShareName }}">
                        @else
                            {{ mb_strtoupper(mb_substr($profileShareName, 0, 1)) }}
                        @endif
                    </div>
                    <div style="min-width: 0;">
                        <div class="fw-bold text-dark text-truncate">{{ $profileShareName }}</div>
                        @if($profileShareRole)<div class="small text-muted text-truncate">{{ $profileShareRole }}</div>@endif
                        @if($profileShareLocation)<div class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i>{{ $profileShareLocation }}</div>@endif
                    </div>
                </div>

                <button type="button" class="profile-share-native" data-profile-native-share>
                    <i class="fas fa-share-nodes me-2"></i>Partager avec mon appareil
                </button>

                <div class="profile-share-options mb-3">
                    <a class="profile-share-option" data-share-platform="whatsapp" href="https://wa.me/?text={{ urlencode($profileShareText.' '.$profileShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur WhatsApp">
                        <i class="fab fa-whatsapp" style="color:#16a34a"></i>WhatsApp
                    </a>
                    <a class="profile-share-option" data-share-platform="facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($profileShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur Facebook">
                        <i class="fab fa-facebook-f" style="color:#1877f2"></i>Facebook
                    </a>
                    <a class="profile-share-option" data-share-platform="linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($profileShareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur LinkedIn">
                        <i class="fab fa-linkedin-in" style="color:#0a66c2"></i>LinkedIn
                    </a>
                    <a class="profile-share-option" data-share-platform="x" href="https://twitter.com/intent/tweet?url={{ urlencode($profileShareUrl) }}&text={{ urlencode($profileShareText) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur X">
                        <i class="fab fa-twitter" style="color:#111827"></i>X
                    </a>
                    <a class="profile-share-option" data-share-platform="telegram" href="https://t.me/share/url?url={{ urlencode($profileShareUrl) }}&text={{ urlencode($profileShareText) }}" target="_blank" rel="noopener noreferrer" aria-label="Partager sur Telegram">
                        <i class="fab fa-telegram" style="color:#229ed9"></i>Telegram
                    </a>
                    <a class="profile-share-option" data-share-platform="email" href="mailto:?subject={{ rawurlencode($profileShareTitle) }}&body={{ rawurlencode($profileShareText."\n\n".$profileShareUrl) }}" aria-label="Partager par e-mail">
                        <i class="fas fa-envelope" style="color:#64748b"></i>E-mail
                    </a>
                </div>

                <label for="{{ $profileShareModalId }}Url" class="form-label small fw-semibold">Lien direct du profil</label>
                <div class="input-group profile-share-copy">
                    <input type="text" class="form-control" id="{{ $profileShareModalId }}Url" value="{{ $profileShareUrl }}" readonly>
                    <button type="button" class="btn btn-primary px-3" data-profile-copy-link>
                        <i class="fas fa-copy me-1"></i><span>Copier</span>
                    </button>
                </div>
                <div class="profile-share-status" data-profile-share-status role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById(@json($profileShareTriggerId));
    const modalElement = document.getElementById(@json($profileShareModalId));
    if (!trigger || !modalElement) return;

    const shareData = {
        title: @json($profileShareTitle),
        text: @json($profileShareText),
        url: @json($profileShareUrl),
    };
    const recordUrl = @json(route('profile.share.record', $user->id));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const modal = new bootstrap.Modal(modalElement);
    const nativeButton = modalElement.querySelector('[data-profile-native-share]');
    const copyButton = modalElement.querySelector('[data-profile-copy-link]');
    const status = modalElement.querySelector('[data-profile-share-status]');

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '#15803d';
    }

    function recordShare(platform) {
        fetch(recordUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ platform }),
        }).catch(() => {});
    }

    async function copyLink() {
        let copied = false;

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(shareData.url);
                copied = true;
            } catch (error) {
                copied = false;
            }
        }

        if (!copied) {
            const fallback = document.createElement('textarea');
            fallback.value = shareData.url;
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

        recordShare('copy');
        copyButton.querySelector('i').className = 'fas fa-check me-1';
        copyButton.querySelector('span').textContent = 'Copié';
        setStatus('Lien copié dans le presse-papiers.');
        window.setTimeout(() => {
            copyButton.querySelector('i').className = 'fas fa-copy me-1';
            copyButton.querySelector('span').textContent = 'Copier';
        }, 2200);
    }

    trigger.addEventListener('click', () => {
        setStatus('');
        modal.show();
    });

    copyButton.addEventListener('click', copyLink);

    modalElement.querySelectorAll('[data-share-platform]').forEach(link => {
        link.addEventListener('click', () => recordShare(link.dataset.sharePlatform));
    });

    if (navigator.share) {
        nativeButton.style.display = 'block';
        nativeButton.addEventListener('click', async () => {
            try {
                await navigator.share(shareData);
                recordShare('native');
                setStatus('Profil partagé avec succès.');
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    setStatus('Le partage natif est indisponible. Utilisez une option ci-dessous.', true);
                }
            }
        });
    }
});
</script>
