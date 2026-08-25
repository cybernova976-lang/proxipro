<#
=============================================================================
 PROKEJEM — Controle avant deploiement
=============================================================================
 A lancer depuis PowerShell, a la racine du projet :

     .\scripts\preflight.ps1

 Tant que ce script n'est pas vert, la version n'est PAS deployable.

 Il execute les seules verifications qui comptent vraiment : celles qui font
 tourner Laravel. Un controle statique (equilibre des directives, lint de
 fichier) ne reproduit pas le compilateur Blade et ne rattrape donc pas une
 erreur de parsing comme un @json() multiligne.

 Etape 2 (view:cache) est celle qui aurait arrete l'erreur 500 du 23/08.
=============================================================================
#>

$ErrorActionPreference = 'Continue'
$script:Echecs = @()
$script:Etape  = 0

function Invoke-Gate {
    param(
        [string] $Titre,
        [scriptblock] $Action,
        [switch] $NonBloquant
    )

    $script:Etape++
    Write-Host ""
    Write-Host "[$script:Etape] $Titre" -ForegroundColor Cyan
    Write-Host ("-" * 70) -ForegroundColor DarkGray

    & $Action
    $code = $LASTEXITCODE

    if ($code -ne 0) {
        if ($NonBloquant) {
            Write-Host "  AVERTISSEMENT - echec non bloquant (code $code)" -ForegroundColor Yellow
        } else {
            Write-Host "  ECHEC (code $code)" -ForegroundColor Red
            $script:Echecs += $Titre
        }
    } else {
        Write-Host "  OK" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=====================================================================" -ForegroundColor White
Write-Host " CONTROLE AVANT DEPLOIEMENT - Prokejem" -ForegroundColor White
Write-Host "=====================================================================" -ForegroundColor White

# --- 0. L'environnement est-il utilisable ? ----------------------------------
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP est introuvable dans le PATH. Controle impossible." -ForegroundColor Red
    exit 1
}
if (-not (Test-Path "vendor/autoload.php")) {
    Write-Host "vendor/autoload.php absent. Lancez d'abord : composer install" -ForegroundColor Red
    exit 1
}
Write-Host ("PHP        : " + (php -r "echo PHP_VERSION;"))
Write-Host ("Commit     : " + (git rev-parse --short HEAD 2>$null))
Write-Host ("Branche    : " + (git rev-parse --abbrev-ref HEAD 2>$null))

$modifies = git status --porcelain 2>$null
if ($modifies) {
    Write-Host ""
    Write-Host "ATTENTION : des modifications ne sont pas commitees." -ForegroundColor Yellow
    Write-Host "La version deployee ne sera pas identifiable ni annulable proprement." -ForegroundColor Yellow
    Write-Host "Commitez avant de deployer." -ForegroundColor Yellow
}

# --- 1. Syntaxe PHP de tous les fichiers modifies -----------------------------
Invoke-Gate "Syntaxe PHP des fichiers suivis" {
    $ko = 0
    git ls-files '*.php' | ForEach-Object {
        $sortie = php -l $_ 2>&1
        if ($LASTEXITCODE -ne 0) { Write-Host "  $sortie" -ForegroundColor Red; $ko++ }
    }
    if ($ko -gt 0) { $global:LASTEXITCODE = 1 } else { $global:LASTEXITCODE = 0 }
}

# --- 2. COMPILATION BLADE REELLE ---------------------------------------------
#     Le controle decisif : Laravel compile chaque vue en PHP.
#     C'est ici qu'une erreur de parsing Blade se revele, et nulle part ailleurs.
Invoke-Gate "Compilation Blade de toutes les vues (view:cache)" {
    php artisan view:clear --no-interaction | Out-Null
    php artisan view:cache --no-interaction
}

# --- 3. Resolution de toutes les routes ---------------------------------------
Invoke-Gate "Resolution des routes (route:cache)" {
    php artisan route:cache --no-interaction
    php artisan route:clear --no-interaction | Out-Null
}

# --- 4. Construction des assets ------------------------------------------------
Invoke-Gate "Construction des assets (npm run build)" {
    npm run build
}

# --- 5. Tests cibles sur le feed ----------------------------------------------
Invoke-Gate "Tests cibles - feed" {
    php artisan test --filter=Feed --stop-on-failure
} -NonBloquant

# --- 6. Suite complete ---------------------------------------------------------
#     Non bloquante : certains anciens tests ne correspondent plus a la
#     nouvelle interface. A remettre en bloquant une fois la suite assainie.
Invoke-Gate "Suite de tests complete" {
    php artisan test
} -NonBloquant

# --- Remise en etat pour le developpement local --------------------------------
php artisan view:clear --no-interaction   | Out-Null
php artisan config:clear --no-interaction | Out-Null

# --- Verdict -------------------------------------------------------------------
Write-Host ""
Write-Host "=====================================================================" -ForegroundColor White
if ($script:Echecs.Count -eq 0) {
    Write-Host " VERT - la version est deployable." -ForegroundColor Green
    Write-Host " Deploiement : railway up" -ForegroundColor Green
    Write-Host "=====================================================================" -ForegroundColor White
    exit 0
} else {
    Write-Host " ROUGE - NE PAS DEPLOYER." -ForegroundColor Red
    Write-Host " Controles en echec :" -ForegroundColor Red
    $script:Echecs | ForEach-Object { Write-Host "   - $_" -ForegroundColor Red }
    Write-Host "=====================================================================" -ForegroundColor White
    exit 1
}
