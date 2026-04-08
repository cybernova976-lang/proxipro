@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 24px;">
                <div style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); padding: 2rem; color: white;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width: 58px; height: 58px; border-radius: 18px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Définissez un nouveau mot de passe</h1>
                            <p class="mb-0" style="opacity: 0.92;">Choisissez un mot de passe sécurisé pour protéger votre compte.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                    <form method="POST" action="{{ route('password.update') }}" class="d-grid gap-4">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="form-label fw-semibold text-dark">Adresse e-mail</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 14px 0 0 14px;">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="exemple@domaine.com" style="border-radius: 0 14px 14px 0;">
                            </div>
                            @error('email')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label fw-semibold text-dark">Nouveau mot de passe</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 14px 0 0 14px;">
                                    <i class="fas fa-key text-muted"></i>
                                </span>
                                <input id="password" type="password" class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Saisissez votre nouveau mot de passe">
                                <button type="button" class="input-group-text bg-white toggle-password" data-target="password" style="border-radius: 0 14px 14px 0; cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password-confirm" class="form-label fw-semibold text-dark">Confirmer le mot de passe</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 14px 0 0 14px;">
                                    <i class="fas fa-shield-alt text-muted"></i>
                                </span>
                                <input id="password-confirm" type="password" class="form-control border-start-0 border-end-0" name="password_confirmation" required autocomplete="new-password" placeholder="Confirmez votre mot de passe">
                                <button type="button" class="input-group-text bg-white toggle-password" data-target="password-confirm" style="border-radius: 0 14px 14px 0; cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-lg text-white fw-semibold" style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); border: none; border-radius: 14px; padding: 0.95rem 1.2rem;">
                            <i class="fas fa-rotate-right me-2"></i>Réinitialiser mon mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (!input || !icon) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });
    });
});
</script>
@endsection
