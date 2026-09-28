@extends('layouts.app')
@section('title', 'Conversation - Prokejem')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/messaging.css') }}?v={{ filemtime(public_path('css/messaging.css')) }}">
@endpush
@php($other = $conversation->other_user)
@php($unavailable = !$other || !$other->is_active)
@section('content')
<div class="pk-messaging has-chat" data-messaging data-unavailable="{{ $unavailable ? 'true' : 'false' }}" data-conversation="{{ $conversation->id }}" data-user="{{ auth()->id() }}" data-poll-url="{{ route('messages.poll', $conversation) }}" data-update-url="{{ route('messages.update', '__ID__') }}" data-delete-url="{{ route('messages.delete', '__ID__') }}">
    <div class="msg-workspace">
        @include('messages.partials.inbox')
        <section class="msg-chat" aria-label="Conversation avec {{ $other?->name ?? 'Compte indisponible' }}">
            <header class="msg-chat-header">
                <a href="{{ route('messages.index') }}" class="msg-icon-button msg-back" aria-label="Retour aux conversations"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
                @include('messages.partials.avatar', ['person' => $other])
                <div class="msg-chat-person"><h1>{{ $other?->name ?? 'Compte indisponible' }}</h1><span>{{ $conversation->subject ?: 'Discussion privée sur Prokejem' }}</span></div>
                <div class="dropdown"><button class="msg-icon-button" type="button" data-bs-toggle="dropdown" aria-label="Options de la conversation" aria-expanded="false"><i class="fas fa-ellipsis-v" aria-hidden="true"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if($other)<li><a class="dropdown-item" href="{{ route('profile.public', $other) }}">Voir le profil</a></li>@endif
                        @if($conversation->is_blocked)
                            @if($conversation->blocked_by == auth()->id())<li><button type="button" class="dropdown-item" data-conversation-action="{{ route('messages.unblock', $conversation) }}">Débloquer la conversation</button></li>@endif
                        @else<li><button type="button" class="dropdown-item" data-conversation-action="{{ route('messages.block', $conversation) }}" data-confirm="Bloquer cette conversation ? Aucun participant ne pourra envoyer de message tant qu’elle reste bloquée.">Bloquer la conversation</button></li>@endif
                    </ul>
                </div>
            </header>
            <p class="msg-connection" id="messageConnection" role="status" hidden></p>
            <div class="msg-thread" id="chatMessages" aria-label="Messages">
                @if($hasOlder)<button type="button" class="msg-history" id="loadOlder">Afficher les messages précédents</button>@endif
                @php($lastDate = null)
                @foreach($messages as $message)
                    @if($lastDate !== $message->created_at->format('Y-m-d'))<div class="msg-date">{{ $message->created_at->isToday() ? 'Aujourd’hui' : ($message->created_at->isYesterday() ? 'Hier' : $message->created_at->format('d/m/Y')) }}</div>@php($lastDate = $message->created_at->format('Y-m-d'))@endif
                    <article class="msg-message {{ $message->sender_id == auth()->id() ? 'is-own' : '' }}" data-message-id="{{ $message->id }}" data-created-at="{{ $message->created_at->toIso8601String() }}">
                        <div class="msg-bubble"><div class="msg-text">{{ $message->content }}</div><div class="msg-meta"><time>{{ $message->created_at->format('H:i') }}</time><span class="msg-edited">{{ $message->edited_at ? 'Modifié' : '' }}</span>@if($message->sender_id == auth()->id())<span class="msg-delivery">{{ $message->is_read ? 'Lu' : 'Envoyé' }}</span>@endif</div>
                            @if($message->sender_id == auth()->id() && $message->created_at->gt(now()->subMinutes(5)))<div class="msg-message-actions"><button type="button" data-message-action="edit">Modifier</button><button type="button" data-message-action="delete">Supprimer</button></div>@endif
                        </div>
                    </article>
                @endforeach
            </div>
            <button type="button" class="msg-new-messages" id="newMessages" hidden>Nouveaux messages ↓</button>
            <div class="msg-composer">
                <div class="msg-emojis" id="messageEmojis" aria-label="Émoticônes" hidden>@foreach(['😊', '👍', '🙏', '✅', '📍', '📅', '🔧', '💬'] as $emoji)<button type="button" data-emoji="{{ $emoji }}" aria-label="Insérer {{ $emoji }}">{{ $emoji }}</button>@endforeach</div>
                <p class="msg-blocked" id="blockedNotice" @if(!$conversation->is_blocked) hidden @endif>Cette conversation est bloquée. L’historique reste accessible.</p>
                @if($unavailable)<p class="msg-blocked">Ce compte n’est plus disponible.</p>@endif
                <form id="messageForm" action="{{ route('messages.store') }}" method="POST" class="msg-compose-form">
                    @csrf<input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                    <button type="button" class="msg-emoji-toggle" id="emojiToggle" aria-label="Afficher les émoticônes" aria-controls="messageEmojis" aria-expanded="false"><i class="far fa-smile" aria-hidden="true"></i></button>
                    <label class="visually-hidden" for="messageInput">Votre message</label>
                    <textarea id="messageInput" name="content" rows="1" maxlength="3000" placeholder="Écrivez votre message…" required @disabled($conversation->is_blocked || $unavailable)></textarea>
                    <button type="submit" class="msg-send" id="sendMessage" aria-label="Envoyer le message" @disabled($conversation->is_blocked || $unavailable)><i class="fas fa-paper-plane" aria-hidden="true"></i><span>Envoyer</span></button>
                </form>
                <div class="msg-compose-help"><span>Sur ordinateur : Entrée pour envoyer · Maj + Entrée pour une nouvelle ligne</span><span id="charCounter">0 / 3000</span></div>
                <p class="msg-feedback" id="messageFeedback" role="status" aria-live="polite" hidden></p>
            </div>
        </section>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/messaging.js') }}?v={{ filemtime(public_path('js/messaging.js')) }}" defer></script>
@endpush
