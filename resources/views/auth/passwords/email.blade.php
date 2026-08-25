@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                <div style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); padding: 2rem; color: white;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width: 58px; height: 58px; border-radius: 18px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-key"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Mot de passe oublié ?</h1>
                            <p class="mb-0" style="opacity: 0.92;">Recevez un lien sécurisé pour définir un nouveau mot de passe.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                    <p class="text-muted mb-4" style="font-size: 0.98rem; line-height: 1.65;">
                        Saisissez l'adresse e-mail associée à votre compte. Nous vous enverrons un lien de réinitialisation valable pendant une durée limitée.
                    </p>

                    {{--
                        Confirmation d'envoi, en deux blocs distincts :
                          · vert  = ce qui vient de se passer, avec l'adresse exacte
                          · ambre = ce que l'utilisateur peut avoir a faire
                        Separer les deux vaut mieux qu'un encart unique ou tout
                        se melange : chaque bloc porte un seul message.
                    --}}
                    @if (session('status'))
                        <div class="alert border-0 mb-3" role="alert" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #166534; border-radius: 18px; padding: 1rem 1.1rem;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width: 42px; height: 42px; border-radius: 14px; background: rgba(22, 101, 52, 0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-circle-check"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <div class="fw-bold mb-1">Lien envoyé</div>

                                    @if (session('reset_link_email'))
                                        <div>Nous venons d'envoyer le lien de réinitialisation à&nbsp;:</div>
                                        <div class="fw-bold mt-2"
                                             style="display: inline-block; max-width: 100%; padding: 0.45rem 0.85rem; background: rgba(255,255,255,0.75); border: 1px solid rgba(22,101,52,0.22); border-radius: 10px; word-break: break-all; line-height: 1.4;">
                                            <i class="fas fa-envelope me-2" style="opacity: 0.7;"></i>{{ session('reset_link_email') }}
                                        </div>
                                        <div class="small mt-2" style="opacity: 0.85;">
                                            Ce n'est pas la bonne adresse ? Corrigez-la ci-dessous et renvoyez le lien.
                                        </div>
                                    @else
                                        <div>{{ session('status') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Le terme « courriers indesirables » est le mot que
                             l'utilisateur doit reperer : il porte l'emphase. --}}
                        <div class="mb-4" role="note"
                             style="background: #fffbeb; border: 1px solid #fde68a; color: #78350f; border-radius: 18px; padding: 0.9rem 1.1rem;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width: 34px; height: 34px; border-radius: 11px; background: rgba(180,83,9,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-folder-open" style="color: #b45309;"></i>
                                </div>
                                <div style="font-size: 0.92rem; line-height: 1.6;">
                                    Le message peut mettre une minute à arriver. Si vous ne le voyez pas dans
                                    votre boîte de réception, regardez dans vos
                                    <strong style="color: #9a3412;">courriers indésirables</strong>
                                    <span style="opacity: 0.85;">(dossier «&nbsp;Spam&nbsp;» chez certains fournisseurs)</span>.
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($errors->has('email'))
                        <div class="alert border-0 mb-4" role="alert" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); color: #991b1b; border-radius: 18px; padding: 1rem 1.1rem;">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width: 42px; height: 42px; border-radius: 14px; background: rgba(153, 27, 27, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <div class="fw-bold mb-1">Envoi impossible</div>
                                    <div>{{ $errors->first('email') }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="d-grid gap-4">
                        @csrf

                        <div>
                            <label for="email" class="form-label fw-semibold text-dark">Adresse e-mail</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 14px 0 0 14px;">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="exemple@domaine.com" style="border-radius: 0 14px 14px 0;">
                            </div>
                            @error('email')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-lg text-white fw-semibold" style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); border: none; border-radius: 14px; padding: 0.95rem 1.2rem;">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le lien de réinitialisation
                        </button>

                        {{-- Une seule proposition, sans preambule interrogatif :
                             c'est l'action qui est mise en avant, pas la question. --}}
                        <div class="text-center">
                            <a href="{{ route('login') }}"
                               class="fw-semibold text-decoration-none d-inline-flex align-items-center gap-2"
                               style="color: #0f766e; font-size: 0.98rem;">
                                <i class="fas fa-arrow-left"></i>
                                Revenir à la page de connexion
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
