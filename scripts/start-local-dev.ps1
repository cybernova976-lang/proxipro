param(
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'

$workspaceRoot = Split-Path -Parent $PSScriptRoot
$emergentRoot = Join-Path (Split-Path -Parent $workspaceRoot) 'Emergent'
$massiwaniRoot = $workspaceRoot

$massiwaniCommand = 'php artisan serve --host=127.0.0.1 --port=8001'
$backendCommand = '.\\venv\\Scripts\\Activate.ps1; python -m uvicorn server:app --reload --host 127.0.0.1 --port 8000'
$frontendCommand = 'npm start'

$targets = @(
    @{
        Name = 'MASSIWANI V2';
        Path = $massiwaniRoot;
        Command = $massiwaniCommand;
    },
    @{
        Name = 'Emergent backend';
        Path = Join-Path $emergentRoot 'backend';
        Command = $backendCommand;
    },
    @{
        Name = 'Emergent frontend';
        Path = Join-Path $emergentRoot 'frontend';
        Command = $frontendCommand;
    }
)

foreach ($target in $targets) {
    if (-not (Test-Path $target.Path)) {
        throw "Dossier introuvable pour $($target.Name): $($target.Path)"
    }
}

Write-Host 'Ports attendus:' -ForegroundColor Cyan
Write-Host '  MASSIWANI V2     -> http://127.0.0.1:8001' -ForegroundColor Cyan
Write-Host '  Emergent backend -> http://127.0.0.1:8000' -ForegroundColor Cyan
Write-Host '  Emergent frontend-> http://127.0.0.1:3000' -ForegroundColor Cyan

foreach ($target in $targets) {
    $launchCommand = "Set-Location '$($target.Path)'; $($target.Command)"

    if ($DryRun) {
        Write-Host "[dry-run] $($target.Name): $launchCommand"
        continue
    }

    Start-Process powershell -ArgumentList @(
        '-NoExit',
        '-ExecutionPolicy', 'Bypass',
        '-Command', $launchCommand
    ) | Out-Null

    Write-Host "Lance: $($target.Name)" -ForegroundColor Green
}

if (-not $DryRun) {
    Write-Host 'Les trois processus ont été ouverts dans des terminaux séparés.' -ForegroundColor Green
}