@extends('admin.layouts.app')
@section('title', 'Studio e-mail')
@section('content')
<a href="{{ route('admin.emails.index') }}">← E-mails</a>
<h1 class="h3 fw-bold mt-3">Studio e-mail Prokejem</h1>
<p class="text-muted">Composez votre message, placez vos photos et choisissez vos destinataires.</p>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.emails.store') }}" method="POST" enctype="multipart/form-data" id="composer">
@csrf
<div class="row g-4"><div class="col-xl-6">
<section class="card border-0 shadow-sm mb-3"><div class="card-body">
<h2 class="h5">1. Votre message</h2>
@foreach(['subject'=>'Objet du message', 'eyebrow'=>'Sur-titre (facultatif)', 'headline'=>'Grand titre'] as $name=>$label)
<label for="{{ $name }}" class="form-label mt-3">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" class="form-control" maxlength="255" value="{{ old($name) }}" @if($name !== 'eyebrow') required @endif>
@endforeach
@include('admin.emails.blocks')
</div></section>
<section class="card border-0 shadow-sm"><div class="card-body">
<h2 class="h5">2. Vos destinataires</h2>
<p class="small text-muted">{{ $recipients->count() }} comptes actifs avec une adresse vérifiée, inscrits à la newsletter et autorisant les e-mails.</p>
<input type="hidden" name="audience" id="audience" value="selected">
@php
    $recipientFilters = [
        'tous' => ['Tous', $recipients->count()],
        'particuliers' => ['Particuliers', $recipients->count() - $professionalCount],
        'professionnels' => ['Professionnels', $professionalCount],
    ];
@endphp
<div class="btn-group flex-wrap mb-3" role="group" aria-label="Trier les destinataires">
@foreach($recipientFilters as $filter => [$label, $total])
<button type="button" class="btn btn-outline-secondary {{ $filter === 'tous' ? 'active' : '' }}" data-recipient-filter="{{ $filter }}" aria-pressed="{{ $filter === 'tous' ? 'true' : 'false' }}">{{ $label }} <span class="badge bg-light text-dark border ms-1" data-joignables="{{ $filter }}">{{ $total }}</span></button>
@endforeach
</div>
<div class="d-flex flex-wrap gap-2 mb-3"><button type="button" id="selectAll" class="btn btn-sm btn-outline-primary">Sélectionner la liste affichée</button><button type="button" id="selectNone" class="btn btn-sm btn-outline-secondary">Tout désélectionner</button></div>
<label for="recipientSearch" class="visually-hidden">Rechercher un destinataire</label><input id="recipientSearch" class="form-control mb-2" placeholder="Rechercher un nom ou une adresse">
<div style="max-height:280px;overflow:auto" id="recipientList">
@foreach($recipients as $recipient)
@php $isProfessional = $recipient->isRegisteredAsProfessional(); @endphp
<label class="recipient d-flex gap-2 border-bottom py-2" data-type="{{ $isProfessional ? 'professionnels' : 'particuliers' }}"><input type="checkbox" name="recipients[]" value="{{ $recipient->id }}" @checked(in_array($recipient->id, old('recipients', [])))><span style="overflow-wrap:anywhere">{{ $recipient->name }}@if($isProfessional) <span class="badge bg-light text-dark border ms-1">Professionnel</span>@endif<small class="d-block text-muted">{{ $recipient->email }}</small></span></label>
@endforeach
<p class="small text-muted py-3 mb-0 d-none" id="recipientEmpty">Aucun compte joignable dans cette liste.</p>
</div>
<p class="mt-3 fw-bold" id="selectionCount" aria-live="polite"></p>
<button class="btn btn-primary w-100" type="submit">Préparer les e-mails pour validation</button>
<p class="small text-muted mt-2 mb-0">Chaque destinataire reçoit un message individuel après votre validation dans la file d’envoi.</p>
</div></section></div>
<div class="col-xl-6"><section class="card border-0 shadow-sm" style="position:sticky;top:16px"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Aperçu instantané</strong><button type="button" class="btn btn-sm btn-outline-secondary" id="mobilePreview">Vue mobile</button></div><div style="background:#f3f6fb;padding:12px;overflow:auto"><iframe id="preview" title="Aperçu du message" sandbox="allow-same-origin" style="display:block;width:100%;height:800px;border:0;margin:auto;background:white"></iframe></div></section></div>
</div></form>
<script defer src="{{ asset('js/admin-email-recipients.js') }}?v={{ filemtime(public_path('js/admin-email-recipients.js')) }}"></script>
<script>
(() => {
 document.getElementById('mobilePreview').onclick=event=>{const preview=document.getElementById('preview');const mobile=!preview.style.maxWidth;preview.style.maxWidth=mobile?'390px':'';event.target.textContent=mobile?'Vue ordinateur':'Vue mobile';};
})();
</script>
@endsection
