@extends('layouts.app')
@section('title', 'Messagerie - Prokejem')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/messaging.css') }}?v={{ filemtime(public_path('css/messaging.css')) }}">
@endpush
@section('content')
<div class="pk-messaging" data-messaging>
    <div class="msg-workspace">
        @include('messages.partials.inbox')
        <section class="msg-welcome">
            <span class="msg-welcome-icon"><i class="far fa-comments" aria-hidden="true"></i></span>
            <h1>Vos échanges, au même endroit</h1>
            <p>Précisez votre besoin, discutez des disponibilités et retrouvez les réponses de vos interlocuteurs.</p>
            <button type="button" class="msg-primary" data-bs-toggle="modal" data-bs-target="#newConversationModal">Nouvelle discussion</button>
            <small><i class="fas fa-user-shield" aria-hidden="true"></i> Accès réservé aux participants</small>
        </section>
    </div>
</div>
<div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="newConversationTitle">Nouvelle discussion</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
        <form action="{{ route('messages.create.conversation') }}" method="POST">
            @csrf
            <div class="modal-body">
                <label for="recipientId" class="form-label">Destinataire</label>
                <select class="form-select mb-3" id="recipientId" name="recipient_id" required>
                    <option value="">Choisir un utilisateur</option>
                    @foreach($recipients as $recipient)<option value="{{ $recipient->id }}" @selected(old('recipient_id') == $recipient->id)>{{ $recipient->name }}</option>@endforeach
                </select>
                <label for="firstMessage" class="form-label">Votre message</label>
                <textarea class="form-control" id="firstMessage" name="message" rows="4" maxlength="3000" required>{{ old('message') }}</textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button><button type="submit" class="msg-primary">Envoyer</button></div>
        </form>
    </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/messaging.js') }}?v={{ filemtime(public_path('js/messaging.js')) }}" defer></script>
@endpush
