{{-- Messages Partial --}}
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Messages</h1>
            <p class="text-muted mb-0">Vos conversations récentes</p>
        </div>
    </div>

    @if($conversations->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-envelope fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-muted">Aucun message pour le moment</h5>
                <p class="text-muted">Vos conversations apparaîtront ici.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush">
                @foreach($conversations as $conversation)
                @php
                    $otherUser = $conversation->user1_id === Auth::id() ? $conversation->user2 : $conversation->user1;
                    $lastMessage = $conversation->lastMessage;
                @endphp
                <a href="{{ route('messages.show', $conversation->id) }}" class="list-group-item list-group-item-action p-3">
                    <div class="d-flex align-items-center gap-3">
                        @if($otherUser && $otherUser->avatar)
                            <img src="{{ asset('storage/' . $otherUser->avatar) }}" alt="" 
                                 class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                 style="width: 48px; height: 48px; font-size: 1.1rem;">
                                {{ $otherUser ? strtoupper(substr($otherUser->name, 0, 1)) : '?' }}
                            </div>
                        @endif
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-semibold">{{ $otherUser ? $otherUser->name : 'Utilisateur supprimé' }}</h6>
                                @if($lastMessage)
                                    <small class="text-muted">{{ $lastMessage->created_at->diffForHumans() }}</small>
                                @endif
                            </div>
                            @if($lastMessage)
                                <p class="mb-0 text-muted small text-truncate">
                                    @if($lastMessage->sender_id === Auth::id())
                                        <span class="text-primary">Vous :</span>
                                    @endif
                                    {{ Str::limit($lastMessage->content, 60) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="text-center mt-3">
        <a href="{{ route('messages.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-external-link-alt me-1"></i>Ouvrir la messagerie complète
        </a>
    </div>
</div>
