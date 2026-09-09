<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $email->subject }}</title></head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $email->headline }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;border-collapse:collapse;"><tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border-radius:20px;overflow:hidden;border-collapse:separate;box-shadow:0 14px 40px rgba(37,99,235,.10);">
<tr><td style="padding:24px 28px 18px;text-align:center;border-bottom:1px solid #e8eef8;"><img src="{{ asset('images/brand/prokejem-logo.png') }}" width="180" alt="Prokejem" style="display:inline-block;width:180px;max-width:70%;height:auto;"></td></tr>
@if(count($email->image_paths ?? []))
<tr><td style="padding:0;">
    @foreach(array_slice($email->image_paths, 0, 3) as $path)
        <img src="{{ storage_url($path) }}" alt="Illustration" width="620" style="display:block;width:100%;max-width:620px;height:auto;">
    @endforeach
</td></tr>
@endif
<tr><td style="padding:34px 30px 30px;">
    @if($email->eyebrow)<div style="margin-bottom:14px;color:#2563eb;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">{{ $email->eyebrow }}</div>@endif
    <h1 style="margin:0 0 14px;color:#111827;font-size:28px;line-height:1.22;">{{ $email->headline }}</h1>
    <div style="margin:0 0 22px;color:#536176;font-size:16px;line-height:1.65;white-space:pre-line;">{{ $email->body }}</div>
    @if($email->cta_label && $email->cta_url)<div style="text-align:center;margin:28px 0 10px;"><a href="{{ $email->cta_url }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;font-size:16px;font-weight:700;line-height:1;padding:17px 28px;border-radius:12px;box-shadow:0 10px 22px rgba(37,99,235,.22);">{{ $email->cta_label }} &nbsp;→</a></div>@endif
</td></tr>
<tr><td style="padding:20px 28px;background:#f8fafc;text-align:center;color:#758195;font-size:12px;line-height:1.6;">Besoin d'aide ? <a href="mailto:{{ $supportEmail }}" style="color:#2563eb;text-decoration:none;">{{ $supportEmail }}</a><br><a href="{{ route('settings.index') }}#notifications">Gérer mes préférences e-mail</a></td></tr>
</table></td></tr></table>
</body></html>
