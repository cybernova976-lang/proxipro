<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer"><title>{{ $texts['page'] }} — PROKEJEM</title>
<style>body{margin:0;background:#f5f7fb;color:#17243b;font:16px/1.6 system-ui,sans-serif}main{max-width:520px;margin:10vh auto;padding:28px;background:white;border:1px solid #dde4ef;border-radius:16px}h1{font-size:1.6rem;line-height:1.3}button{min-height:48px;background:#4338ca;border:0;color:white;border-radius:9px;padding:12px 20px;font:inherit;cursor:pointer}a{color:#4338ca}button:focus-visible,a:focus-visible{outline:3px solid #818cf8;outline-offset:4px}@media(max-width:640px){main{margin:24px 16px;padding:24px}}</style></head>
<body><main><p>PROKEJEM · Vos préférences</p>
@if($unsubscribed)<h1>{{ $texts['done_title'] }}</h1><p>{{ $texts['done_text'] }}</p>
@else<h1>{{ $texts['question'] }}</h1><p>{{ $texts['explanation'] }}</p><form method="POST" action="{{ $unsubscribeUrl }}">@csrf<button type="submit">{{ $texts['button'] }}</button></form>@endif
<p><a href="{{ route('settings.index') }}#notifications">Gérer toutes mes préférences</a></p><a href="{{ route('homepage') }}">Retour à PROKEJEM</a></main></body></html>
