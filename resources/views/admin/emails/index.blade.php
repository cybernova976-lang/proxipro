@extends('admin.layouts.app')

@section('title', 'E-mails')

@section('content')
<a href="{{ route('admin.emails.create') }}" class="btn btn-primary mb-3"><i class="fas fa-pen me-2"></i>Rédiger un e-mail</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h1 class="h3 fw-bold mb-1"><i class="fas fa-envelope-open-text text-primary me-2"></i>E-mails</h1>
        <p class="text-muted mb-0">Les newsletters ne partent qu'après votre validation. Les rappels de demande inachevée partent automatiquement : un seul par brouillon, 24 heures après la dernière modification, entre 8 h et 20 h (heure de Mayotte), avec un lien de désinscription.</p></div>
    <span class="badge bg-primary fs-6">{{ $emails->total() }} message{{ $emails->total() > 1 ? 's' : '' }}</span>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($dueDrafts > 0)
    <section class="card card-body mb-3" aria-labelledby="due-draft-reminders">
        <h2 id="due-draft-reminders" class="h5">Rappels de brouillons dus</h2>
        <p>{{ $dueDrafts }} {{ $dueDrafts === 1 ? 'demande est restée inachevée' : 'demandes sont restées inachevées' }} depuis au moins 24 heures. Le rappel partira au prochain passage automatique (toutes les 15 minutes, entre 8 h et 20 h) ; vous pouvez aussi l'envoyer maintenant.</p>
        <form method="POST" action="{{ route('admin.emails.send-draft-reminders') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">{{ $dueDrafts > 50 ? 'Envoyer les 50 prochains rappels' : 'Envoyer les rappels dus' }}</button>
        </form>
    </section>
@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($sendingCount > 0)<div class="alert alert-info" role="status">{{ $sendingCount }} message{{ $sendingCount > 1 ? 's' : '' }} en cours d’envoi en arrière-plan. Actualisez la page pour suivre l’avancement.</div>@endif
@foreach($batches as $lot)
    <section class="card card-body mb-3" aria-label="Lot à valider">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2"><span class="badge bg-warning text-dark">Lot à valider</span><span class="badge bg-light text-dark border">{{ $lot['type'] === \App\Support\ManagedEmailAudience::SERVICE ? 'Message de service' : 'Newsletter' }}</span><small class="text-muted">{{ $lot['created_at']->format('d/m/Y H:i') }}</small></div>
        <h2 class="h5 fw-bold">{{ $lot['subject'] }}</h2>
        <p class="mb-3">{{ $lot['count'] }} messages identiques, un par destinataire. Vérifiez-en un, puis validez l’envoi de tout le lot : les messages partent un par un, en arrière-plan.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.emails.show', $lot['first']) }}" class="btn btn-outline-primary">Vérifier un message</a>
            <form method="POST" action="{{ route('admin.emails.batch.approve', $lot['batch']) }}" data-confirm-batch="Valider et envoyer ces {{ $lot['count'] }} messages ? Cet envoi ne peut pas être rappelé.">@csrf<button type="submit" class="btn btn-success">Valider et envoyer les {{ $lot['count'] }} messages</button></form>
            <form method="POST" action="{{ route('admin.emails.batch.cancel', $lot['batch']) }}" data-confirm-batch="Annuler ces {{ $lot['count'] }} envois ? Aucun e-mail ne sera transmis.">@csrf<button type="submit" class="btn btn-outline-danger">Annuler le lot</button></form>
        </div>
    </section>
@endforeach

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['' => 'Tous', 'pending' => 'À valider', 'sending' => 'Envoi en cours', 'sent' => 'Envoyés', 'failed' => 'Échecs', 'cancelled' => 'Annulés'] as $value => $label)
        <a class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.emails.index', $value ? ['status' => $value] : []) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="row g-3">
@forelse($emails as $email)
    @php($tone = ['pending'=>'warning','sending'=>'info','sent'=>'success','failed'=>'danger','cancelled'=>'secondary'][$email->status] ?? 'secondary')
    <div class="col-12">
        <article class="card border-0 shadow-sm"><div class="card-body d-flex flex-column flex-lg-row gap-3 align-items-lg-center">
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2"><span class="badge bg-{{ $tone }}">{{ ['pending'=>'À valider','sending'=>'Envoi en cours','sent'=>'Envoyé','failed'=>'Échec','cancelled'=>'Annulé'][$email->status] ?? $email->status }}</span>@if($email->type === \App\Services\DemandDraftReminderService::TYPE)<span class="badge bg-light text-dark border">Rappel automatique</span>@elseif($email->type === \App\Support\ManagedEmailAudience::SERVICE)<span class="badge bg-light text-dark border">Message de service</span>@endif<small class="text-muted">{{ $email->created_at->format('d/m/Y H:i') }}</small></div>
                <h2 class="h5 fw-bold mb-1">{{ $email->subject }}</h2>
                <p class="mb-1 text-muted"><i class="fas fa-user me-1"></i>{{ $email->recipient_name ?: 'Utilisateur' }} · {{ $email->recipient_email }}</p>
                <small class="text-muted">{{ Str::limit($email->body, 150) }}</small>
            </div>
            <a href="{{ route('admin.emails.show', $email) }}" class="btn btn-primary">Vérifier le message <i class="fas fa-arrow-right ms-1"></i></a>
        </div></article>
    </div>
@empty
    <div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body py-5 text-center"><i class="fas fa-circle-check text-success fs-1"></i><h2 class="h5 mt-3">Aucun message dans cette file</h2></div></div></div>
@endforelse
</div>
<div class="mt-4">{{ $emails->links('admin.partials.pagination') }}</div>
<script defer src="{{ asset('js/admin-email-batches.js') }}?v={{ filemtime(public_path('js/admin-email-batches.js')) }}"></script>
@endsection
