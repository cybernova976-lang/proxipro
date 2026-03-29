<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion & Inscription - ProxiPro</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf3 50%, #f0f4f8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }
        
        /* Background animation */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(124, 58, 237, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(240, 147, 251, 0.08) 0%, transparent 40%);
            z-index: -1;
            animation: bgMove 20s ease-in-out infinite;
        }
        
        @keyframes bgMove {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .auth-container {
            width: 100%;
            max-width: 1100px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .auth-row {
            display: flex;
            min-height: 650px;
        }
        
        /* Login Side */
        .login-side, .register-side {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
        }
        
        .login-side {
            background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .register-side {
            background: linear-gradient(180deg, rgba(118, 75, 162, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .auth-header .logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }
        
        .auth-header .logo i {
            font-size: 30px;
            color: white;
        }
        
        .auth-header h2 {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .auth-header p {
            color: #718096;
            font-size: 0.95rem;
        }
        
        /* Form styling */
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating .form-control {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            color: #2d3748;
            padding: 20px 20px 10px 50px;
            height: 60px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            background: white;
            border-color: #7c3aed;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15);
            outline: none;
        }
        
        .form-floating .form-control::placeholder {
            color: transparent;
        }
        
        .form-floating label {
            color: #718096;
            padding-left: 50px;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #7c3aed;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 10;
            pointer-events: none;
        }
        
        .form-floating:focus-within .input-icon {
            color: #7c3aed;
        }
        
        /* Checkbox */
        .form-check-input {
            background-color: #f7fafc;
            border-color: #e2e8f0;
        }
        
        .form-check-input:checked {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }
        
        .form-check-label {
            color: #4a5568;
        }
        
        /* Submit Button */
        .btn-auth {
            width: 100%;
            padding: 16px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
            color: white;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.3);
        }
        
        .btn-auth::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-auth:hover::after {
            left: 100%;
        }
        
        /* Social Login */
        .social-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #a0aec0;
        }
        
        .social-divider::before,
        .social-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .social-divider span {
            padding: 0 15px;
            font-size: 0.9rem;
        }
        
        .social-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn-social {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .btn-social:hover {
            background: white;
            transform: translateY(-2px);
            color: #7c3aed;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        /* Link */
        .auth-link {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: #f093fb;
        }
        
        /* Error messages */
        .invalid-feedback {
            color: #f5576c;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .alert {
            background: rgba(245, 87, 108, 0.2);
            border: 1px solid rgba(245, 87, 108, 0.5);
            color: #ff8fa3;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-color: rgba(40, 167, 69, 0.5);
            color: #7dcea0;
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #7c3aed;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .auth-row {
                flex-direction: column;
            }
            
            .login-side {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .login-side, .register-side {
                padding: 30px 25px;
            }
            
            .auth-header h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Loading spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn-auth.loading .spinner {
            display: inline-block;
        }
        
        .btn-auth.loading .btn-text {
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-row">
            <!-- Login Side -->
            <div class="login-side">
                <div class="auth-header">
                    <div class="logo">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h2>Connexion</h2>
                    <p>Bienvenue sur ProxiPro</p>
                </div>
                
                @if(session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="loginEmail" name="email" value="{{ old('email') }}" 
                               placeholder="Email" required autofocus>
                        <label for="loginEmail">Adresse email</label>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="loginPassword" name="password" placeholder="Mot de passe" required>
                        <label for="loginPassword">Mot de passe</label>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('loginPassword', this)"></i>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="auth-link small">Mot de passe oublié ?</a>
                        @endif
                    </div>
                    
                    <button type="submit" class="btn btn-auth btn-login">
                        <span class="spinner"></span>
                        <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Se connecter</span>
                    </button>
                </form>
                
                <div class="social-divider">
                    <span>ou continuer avec</span>
                </div>
                
                <div class="social-buttons">
                    <button class="btn btn-social">
                        <i class="fab fa-google"></i>
                    </button>
                </div>
            </div>
            
            <!-- Register Side -->
            <div class="register-side">
                <div class="auth-header">
                    <div class="logo" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Inscription</h2>
                    <p>Rejoignez notre communauté</p>
                </div>
                
                <form method="POST" action="{{ route('register') }}" id="registerForm">
                    @csrf
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="registerName" name="name" value="{{ old('name') }}" 
                               placeholder="Nom complet" required>
                        <label for="registerName">Nom complet</label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control @error('register_email') is-invalid @enderror" 
                               id="registerEmail" name="email" value="{{ old('register_email') }}" 
                               placeholder="Email" required>
                        <label for="registerEmail">Adresse email</label>
                        @error('register_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control @error('register_password') is-invalid @enderror" 
                               id="registerPassword" name="password" placeholder="Mot de passe" required>
                        <label for="registerPassword">Mot de passe</label>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('registerPassword', this)"></i>
                        @error('register_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-floating position-relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" 
                               id="registerPasswordConfirm" name="password_confirmation" 
                               placeholder="Confirmer le mot de passe" required>
                        <label for="registerPasswordConfirm">Confirmer le mot de passe</label>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('registerPasswordConfirm', this)"></i>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            J'accepte les <a href="#" class="auth-link">conditions d'utilisation</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-auth btn-register">
                        <span class="spinner"></span>
                        <span class="btn-text"><i class="fas fa-user-plus me-2"></i>Créer mon compte</span>
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p style="color: rgba(255,255,255,0.6);">
                        🎁 <strong style="color: #f093fb;">50 points offerts</strong> à l'inscription !
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Back to home -->
    <a href="{{ url('/') }}" class="position-fixed" style="top: 20px; left: 20px; color: white; text-decoration: none; z-index: 1000;">
        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('.btn-auth');
                btn.classList.add('loading');
                btn.disabled = true;
            });
        });
    </script>
</body>
</html>
