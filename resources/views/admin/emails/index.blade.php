@extends('admin.layouts.app')

@section('title', 'E-mails à valider')

@section('content')
<a href="{{ route('admin.emails.create') }}" class="btn btn-primary mb-3"><i class="fas fa-pen me-2"></i>Rédiger un e-mail</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h1 class="h3 fw-bold mb-1"><i class="fas fa-envelope-open-text text-primary me-2"></i>E-mails à valider</h1><p class="text-muted mb-0">Aucun message de relance n'est envoyé sans votre validation.</p></div>
    <span class="badge bg-primary fs-6">{{ $emails->total() }} message{{ $emails->total() > 1 ? 's' : '' }}</span>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['' => 'Tous', 'pending' => 'À valider', 'sent' => 'Envoyés', 'failed' => 'Échecs', 'cancelled' => 'Annulés'] as $value => $label)
        <a class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.emails.index', $value ? ['status' => $value] : []) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="row g-3">
@forelse($emails as $email)
    @php($tone = ['pending'=>'warning','sending'=>'info','sent'=>'success','failed'=>'danger','cancelled'=>'secondary'][$email->status] ?? 'secondary')
    <div class="col-12">
        <article class="card border-0 shadow-sm"><div class="card-body d-flex flex-column flex-lg-row gap-3 align-items-lg-center">
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2"><span class="badge bg-{{ $tone }}">{{ ['pending'=>'À valider','sending'=>'Envoi en cours','sent'=>'Envoyé','failed'=>'Échec','cancelled'=>'Annulé'][$email->status] ?? $email->status }}</span><small class="text-muted">{{ $email->created_at->format('d/m/Y H:i') }}</small></div>
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
<div class="mt-4">{{ $emails->links() }}</div>
@endsection
