@extends('admin.layouts.app')

@section('title', 'Vérifier un e-mail')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><a href="{{ route('admin.emails.index') }}" class="text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Retour à la file</a><h1 class="h3 fw-bold mt-2 mb-1">Vérifier avant l'envoi</h1><p class="text-muted mb-0">Destinataire : <strong>{{ $managedEmail->recipient_name }}</strong> · {{ $managedEmail->recipient_email }}</p></div>
    <span class="badge {{ $managedEmail->status === 'pending' ? 'bg-warning text-dark' : ($managedEmail->status === 'sent' ? 'bg-success' : 'bg-secondary') }} fs-6">{{ $managedEmail->status }}</span>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Le message n'a pas été enregistré.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
        <form action="{{ route('admin.emails.update', $managedEmail) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            @php($editable = in_array($managedEmail->status, ['pending','failed']))
            <fieldset {{ $editable ? '' : 'disabled' }}>
                <div class="mb-3"><label class="form-label fw-bold">Objet</label><input class="form-control" name="subject" maxlength="255" required value="{{ old('subject', $managedEmail->subject) }}"></div>
                <div class="mb-3"><label class="form-label fw-bold">Sur-titre</label><input class="form-control" name="eyebrow" maxlength="255" value="{{ old('eyebrow', $managedEmail->eyebrow) }}"></div>
                <div class="mb-3"><label class="form-label fw-bold">Grand titre</label><input class="form-control" name="headline" maxlength="255" required value="{{ old('headline', $managedEmail->headline) }}"></div>
                <div class="mb-3"><label class="form-label fw-bold">Message</label><textarea class="form-control" name="body" rows="7" maxlength="5000" required>{{ old('body', $managedEmail->body) }}</textarea></div>
                <div class="row g-2"><div class="col-md-5 mb-3"><label class="form-label fw-bold">Texte du bouton</label><input class="form-control" name="cta_label" maxlength="80" value="{{ old('cta_label', $managedEmail->cta_label) }}"></div><div class="col-md-7 mb-3"><label class="form-label fw-bold">Lien du bouton</label><input type="url" class="form-control" name="cta_url" value="{{ old('cta_url', $managedEmail->cta_url) }}"></div></div>
                <div class="mb-3"><label class="form-label fw-bold">Ajouter jusqu'à 3 images</label><input type="file" class="form-control" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><small class="text-muted">JPEG, PNG ou WebP · 5 Mo maximum par image.</small></div>
                @if(count($managedEmail->image_paths ?? []))<div class="row g-2 mb-3">@foreach($managedEmail->image_paths as $path)<div class="col-4"><div class="border rounded p-1"><img src="{{ storage_url($path) }}" alt="" class="w-100 rounded" style="height:90px;object-fit:cover"><label class="small d-block mt-1"><input type="checkbox" name="remove_images[]" value="{{ $path }}"> Retirer</label></div></div>@endforeach</div>@endif
                <button class="btn btn-outline-primary w-100" type="submit"><i class="fas fa-save me-1"></i>Enregistrer et actualiser l'aperçu</button>
            </fieldset>
        </form>
        @if($editable)<div class="d-grid gap-2 mt-3"><form action="{{ route('admin.emails.approve', $managedEmail) }}" method="POST">@csrf<button class="btn btn-success w-100" onclick="return confirm('Valider et envoyer maintenant cet e-mail à {{ $managedEmail->recipient_email }} ?')"><i class="fas fa-paper-plane me-1"></i>Valider et envoyer</button></form><form action="{{ route('admin.emails.cancel', $managedEmail) }}" method="POST">@csrf<button class="btn btn-outline-danger w-100" onclick="return confirm('Annuler définitivement cet envoi ?')">Annuler l'envoi</button></form></div>@endif
        @if($managedEmail->failure_message)<div class="alert alert-danger mt-3 small">{{ $managedEmail->failure_message }}</div>@endif
        </div></div>
    </div>
    <div class="col-xl-7"><div class="card border-0 shadow-sm overflow-hidden"><div class="card-header bg-white fw-bold">Aperçu réel du message</div><iframe title="Aperçu de l'e-mail" src="{{ route('admin.emails.preview', $managedEmail) }}" style="display:block;width:100%;height:760px;border:0;background:#f3f6fb;"></iframe></div></div>
</div>
@endsection
