@extends('layouts.app')

@section('title', 'Notifications - Prokejem')

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v=20261116">
@endpush

@section('content')
<div class="pk-notifs">
    <header class="pk-notifs__head">
        <div>
            <h1>Notifications</h1>
            <p>
                @if($unreadCount > 0)
                    {{ $unreadCount }} {{ $unreadCount > 1 ? 'notifications non lues' : 'notification non lue' }}
                @else
                    Vous êtes à jour.
                @endif
            </p>
        </div>
        <div class="pk-notifs__actions">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="pk-notifs__btn">Tout marquer comme lu</button>
                </form>
            @endif
            <a href="{{ route('settings.index') }}#notifications" class="pk-notifs__link">Régler mes notifications</a>
        </div>
    </header>

    @if(session('success'))
        <p class="pk-notifs__flash" role="status">{{ session('success') }}</p>
    @endif

    @if($notifications->isEmpty())
        <div class="pk-notifs__empty">
            <i class="far fa-bell-slash" aria-hidden="true"></i>
            <p>Aucune notification pour l’instant.</p>
            <p class="pk-notifs__hint">Vous serez prévenu ici à chaque message, proposition, commande ou changement sur votre compte.</p>
        </div>
    @else
        <ul class="pk-notifs__list">
            @foreach($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp
                <li>
                    <a href="{{ route('notifications.open', $notification->id) }}" @class(['pk-notifs__item', 'is-unread' => $isUnread])>
                        <span class="pk-notifs__icon"><i class="{{ $data['icon'] ?? 'fas fa-bell' }}" aria-hidden="true"></i></span>
                        <span class="pk-notifs__body">
                            <span class="pk-notifs__title">
                                {{ $data['title'] ?? 'Notification' }}
                                @if($isUnread)<span class="visually-hidden">(non lue)</span>@endif
                            </span>
                            <span class="pk-notifs__message">{{ $data['message'] ?? '' }}</span>
                            <span class="pk-notifs__time">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                        @if($isUnread)<span class="pk-notifs__dot" aria-hidden="true"></span>@endif
                    </a>
                </li>
            @endforeach
        </ul>

        @if($notifications->hasPages())
            <nav class="pk-notifs__pages" aria-label="Pages de notifications">
                @if($notifications->onFirstPage())
                    <span class="is-disabled">Plus récentes</span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" rel="prev">Plus récentes</a>
                @endif
                <span>Page {{ $notifications->currentPage() }} sur {{ $notifications->lastPage() }}</span>
                @if($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" rel="next">Plus anciennes</a>
                @else
                    <span class="is-disabled">Plus anciennes</span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
