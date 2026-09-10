@extends('admin.layouts.app')
@section('title', 'Studio e-mail')
@section('content')
<a href="{{ route('admin.emails.index') }}">← E-mails à valider</a>
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
<label for="cta_label" class="form-label mt-3">Texte du bouton (facultatif)</label><input id="cta_label" name="cta_label" class="form-control" maxlength="80" value="{{ old('cta_label') }}">
<label for="cta_url" class="form-label mt-3">Destination du bouton</label><input id="cta_url" name="cta_url" type="url" class="form-control" value="{{ old('cta_url') }}" placeholder="https://www.prokejem.fr/">
</div></section>
<section class="card border-0 shadow-sm"><div class="card-body">
<h2 class="h5">2. Vos destinataires</h2>
<p class="small text-muted">{{ $recipients->count() }} comptes actifs avec une adresse vérifiée, inscrits à la newsletter et autorisant les e-mails.</p>
<input type="hidden" name="audience" id="audience" value="selected">
<div class="d-flex flex-wrap gap-2 mb-3"><button type="button" id="selectAll" class="btn btn-outline-primary">Tous les utilisateurs éligibles</button><button type="button" id="selectNone" class="btn btn-outline-secondary">Tout désélectionner</button></div>
<label for="recipientSearch" class="visually-hidden">Rechercher un destinataire</label><input id="recipientSearch" class="form-control mb-2" placeholder="Rechercher un nom ou une adresse">
<div style="max-height:280px;overflow:auto" id="recipientList">
@foreach($recipients as $recipient)
<label class="recipient d-flex gap-2 border-bottom py-2"><input type="checkbox" name="recipients[]" value="{{ $recipient->id }}" @checked(in_array($recipient->id, old('recipients', [])))><span style="overflow-wrap:anywhere">{{ $recipient->name }}<small class="d-block text-muted">{{ $recipient->email }}</small></span></label>
@endforeach
</div>
<p class="mt-3 fw-bold" id="selectionCount" aria-live="polite"></p>
<button class="btn btn-primary w-100" type="submit">Préparer les e-mails pour validation</button>
<p class="small text-muted mt-2 mb-0">Chaque destinataire reçoit un message individuel après votre validation dans la file d’envoi.</p>
</div></section></div>
<div class="col-xl-6"><section class="card border-0 shadow-sm" style="position:sticky;top:16px"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Aperçu instantané</strong><button type="button" class="btn btn-sm btn-outline-secondary" id="mobilePreview">Vue mobile</button></div><div style="background:#f3f6fb;padding:12px;overflow:auto"><iframe id="preview" title="Aperçu du message" sandbox="allow-same-origin" style="display:block;width:100%;height:800px;border:0;margin:auto;background:white"></iframe></div></section></div>
</div></form>
<script>
(() => {
 const boxes=[...document.querySelectorAll('.recipient input')], audience=document.getElementById('audience');
 function count(){document.getElementById('selectionCount').textContent=boxes.filter(box=>box.checked).length+' destinataire(s) sélectionné(s)';}
 document.getElementById('selectAll').onclick=()=>{boxes.forEach(box=>box.checked=true);audience.value='all';count();};
 document.getElementById('selectNone').onclick=()=>{boxes.forEach(box=>box.checked=false);audience.value='selected';count();};
 boxes.forEach(box=>box.addEventListener('change',()=>{audience.value='selected';count();}));
 document.getElementById('recipientSearch').oninput=event=>{const query=event.target.value.toLocaleLowerCase();document.querySelectorAll('.recipient').forEach(row=>row.classList.toggle('d-none',!row.textContent.toLocaleLowerCase().includes(query)));};
 document.getElementById('mobilePreview').onclick=event=>{const preview=document.getElementById('preview');const mobile=!preview.style.maxWidth;preview.style.maxWidth=mobile?'390px':'';event.target.textContent=mobile?'Vue ordinateur':'Vue mobile';};
 count();
})();
</script>
@endsection
