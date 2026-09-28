<aside class="msg-inbox" aria-label="Vos conversations">
    <header class="msg-inbox-header">
        <div><span class="msg-eyebrow">VOS ÉCHANGES</span><h2>Messages</h2></div>
        <a class="msg-icon-button" href="{{ route('messages.index') }}" aria-label="Toutes les conversations"><i class="fas fa-comments" aria-hidden="true"></i></a>
    </header>
    <form class="msg-search" action="{{ route('messages.index') }}" method="GET" role="search">
        <label class="visually-hidden" for="conversationSearch">Rechercher une conversation</label>
        <input type="search" id="conversationSearch" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nom ou sujet de la discussion">
        @if(request('filter') === 'unread')<input type="hidden" name="filter" value="unread">@endif
        <button type="submit" class="msg-icon-button" aria-label="Rechercher"><i class="fas fa-search" aria-hidden="true"></i></button>
    </form>
    <div class="msg-filters">
        <a href="{{ route('messages.index', ['q' => request('q')]) }}" @if(request('filter') !== 'unread') aria-current="page" @endif>Toutes</a>
        <a href="{{ route('messages.index', ['q' => request('q'), 'filter' => 'unread']) }}" @if(request('filter') === 'unread') aria-current="page" @endif>Non lus</a>
        <form action="{{ route('messages.markAllRead') }}" method="POST">@csrf<button type="submit" title="Tout marquer comme lu" aria-label="Tout marquer comme lu"><i class="fas fa-check-double" aria-hidden="true"></i></button></form>
    </div>
    <div class="msg-conversation-list">
        @forelse($conversations as $conv)
            @php($other = $conv->other_user)
            <a href="{{ route('messages.show', $conv) }}" class="msg-conversation {{ isset($conversation) && $conversation->id === $conv->id ? 'is-selected' : '' }} {{ $conv->unread_count ? 'is-unread' : '' }}" @if(isset($conversation) && $conversation->id === $conv->id) aria-current="page" @endif>
                @include('messages.partials.avatar', ['person' => $other])
                <div class="msg-conversation-copy">
                    <div class="msg-conversation-top"><strong>{{ $other?->name ?? 'Compte indisponible' }}</strong><time>{{ $conv->lastMessage?->created_at->isToday() ? $conv->lastMessage->created_at->format('H:i') : $conv->lastMessage?->created_at->format('d/m') }}</time></div>
                    @include('messages.partials.presence', ['presenceConversation' => $conv])
                    @if($conv->subject)<span class="msg-subject">{{ $conv->subject }}</span>@endif
                    <div class="msg-preview"><span>{{ $conv->lastMessage?->sender_id == auth()->id() ? 'Vous : ' : '' }}{{ $conv->lastMessage?->content ?? 'Aucun message' }}</span>@if($conv->unread_count)<b aria-label="{{ $conv->unread_count }} messages non lus">{{ $conv->unread_count }}</b>@endif</div>
                    @if($conv->is_blocked)<small>Conversation bloquée</small>@endif
                </div>
            </a>
        @empty
            <div class="msg-empty"><i class="far fa-comments" aria-hidden="true"></i><h3>{{ request('q') || request('filter') ? 'Aucune conversation correspondante' : 'Pas encore de conversation' }}</h3><p>{{ request('q') || request('filter') ? 'Essayez une autre recherche ou affichez toutes les conversations.' : 'Contactez un membre depuis son profil ou une annonce pour commencer.' }}</p><a href="{{ route('feed') }}">Explorer les annonces</a></div>
        @endforelse
        @if($conversations->hasPages())<div class="msg-pagination">{{ $conversations->links('pagination::bootstrap-5') }}</div>@endif
    </div>
    @if(!isset($conversation))<div class="msg-inbox-footer"><button class="msg-primary" type="button" data-bs-toggle="modal" data-bs-target="#newConversationModal">Nouvelle discussion</button></div>@endif
</aside>
