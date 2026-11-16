<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Trop de tentatives - Prokejem</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f8fafc;font:16px/1.6 system-ui,sans-serif;color:#475569}main{max-width:560px}h1{color:#17243a;font-size:1.6rem}nav{display:flex;flex-wrap:wrap;gap:16px}a{color:#4338ca;min-height:44px;display:inline-flex;align-items:center}a:first-child{padding:10px 18px;background:#4f46e5;color:white;border-radius:10px;text-decoration:none}
    </style>
</head>
<body><main>
    <h1>Trop de tentatives</h1>
    <p>Vous avez répété cette action trop souvent en peu de temps. Patientez une minute, puis réessayez : rien n’a été perdu.</p>
    <nav><a href="{{ url()->previous() !== url()->current() ? url()->previous() : (auth()->check() ? route('feed') : route('homepage')) }}">Revenir à la page précédente</a><a href="{{ route('contact.index') }}">Nous contacter</a></nav>
</main></body>
</html>
