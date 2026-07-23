@extends('layouts.app')

@section('title', 'Messagerie - Lunamars')

@push('styles')
<style>
    /* ===== WHATSAPP STYLE MESSAGING ===== */
    body { background: #f0f2f5; }
    
    .messaging-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        height: calc(100vh - 84px);
    }
    
    .messaging-wrapper {
        display: flex;
        height: 100%;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    /* ===== LEFT PANEL - CONVERSATIONS LIST ===== */
    .conversations-panel {
        width: 380px;
        border-right: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
        background: #ffffff;
    }
    
    .conversations-header {
        padding: 16px 20px;
        background: #00a884;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar-main {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .user-avatar-main img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .header-title {
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .header-actions {
        display: flex;
        gap: 8px;
    }
    
    .header-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: white;
        font-size: 1.1rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .header-btn:hover {
        background: rgba(255,255,255,0.1);
    }
    
    /* Search Bar */
    .search-container {
        padding: 10px 16px;
        background: #ffffff;
        border-bottom: 1px solid #e9edef;
    }
    
    .search-box {
        display: flex;
        align-items: center;
        background: #f0f2f5;
        border-radius: 8px;
        padding: 8px 12px;
        gap: 12px;
    }
    
    .search-box i {
        color: #54656f;
        font-size: 0.9rem;
    }
    
    .search-box input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.9rem;
        color: #111b21;
        outline: none;
    }
    
    .search-box input::placeholder {
        color: #8696a0;
    }
    
    /* Conversations List */
    .conversations-list {
        flex: 1;
        overflow-y: auto;
    }
    
    .conversations-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .conversations-list::-webkit-scrollbar-thumb {
        background: #c5c5c5;
        border-radius: 3px;
    }
    
    .conversation-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        gap: 14px;
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        border-bottom: 1px solid #f0f2f5;
    }
    
    .conversation-item:hover {
        background: #f5f6f6;
    }
    
    .conversation-item.active {
        background: #f0f2f5;
    }
    
    .conversation-item.unread {
        background: #f0faf7;
    }
    
    .conv-avatar {
        position: relative;
        flex-shrink: 0;
    }
    
    .conv-avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00a884, #25d366);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
    }
    
    .conv-avatar-circle img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .online-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background: #25d366;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    .conv-content {
        flex: 1;
        min-width: 0;
    }
    
    .conv-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    
    .conv-name {
        font-size: 1rem;
        font-weight: 500;
        color: #111b21;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .conv-time {
        font-size: 0.75rem;
        color: #667781;
    }
    
    .conversation-item.unread .conv-time {
        color: #25d366;
    }
    
    .conv-preview {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .conv-preview-text {
        flex: 1;
        font-size: 0.875rem;
        color: #667781;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .conversation-item.unread .conv-preview-text {
        color: #111b21;
        font-weight: 500;
    }
    
    .conv-preview .fa-check-double {
        color: #53bdeb;
        font-size: 0.85rem;
    }
    
    .unread-badge {
        background: #25d366;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
    }
    
    /* ===== RIGHT PANEL - CHAT PLACEHOLDER ===== */
    .chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f0f2f5;
    }
    
    .chat-placeholder {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
    }
    
    .placeholder-content {
        text-align: center;
        max-width: 500px;
        padding: 40px;
    }
    
    .placeholder-icon {
        width: 350px;
        margin: 0 auto 30px;
    }
    
    .placeholder-icon svg {
        width: 100%;
        height: auto;
    }
    
    .placeholder-title {
        font-size: 2rem;
        font-weight: 300;
        color: #41525d;
        margin-bottom: 16px;
    }
    
    .placeholder-text {
        color: #667781;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    
    .placeholder-divider {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 30px;
    }
    
    .placeholder-divider hr {
        flex: 1;
        border: none;
        border-top: 1px solid #e9edef;
    }
    
    .placeholder-divider span {
        color: #8696a0;
        font-size: 0.8rem;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        color: #667781;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #c5c5c5;
        margin-bottom: 20px;
    }
    
    .empty-state h5 {
        color: #41525d;
        margin-bottom: 10px;
    }
    
    .btn-new-chat {
        background: #00a884;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 24px;
        font-weight: 500;
        margin-top: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .btn-new-chat:hover {
        background: #008f72;
        color: white;
    }
    
    /* Modal Styling */
    .modal-content {
        border: none;
        border-radius: 16px;
    }
    
    .modal-header {
        background: #00a884;
        color: white;
        border-radius: 16px 16px 0 0;
        padding: 20px 24px;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body {
        padding: 24px;
    }
    
    .modal-footer {
        border-top: 1px solid #e9edef;
        padding: 16px 24px;
    }
    
    .form-label {
        font-weight: 500;
        color: #111b21;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border: 1px solid #e9edef;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #00a884;
        box-shadow: none;
    }
    
    .btn-send-modal {
        background: #00a884;
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn-send-modal:hover {
        background: #008f72;
        color: white;
    }
    
    /* Dropdown menu styling */
    .dropdown-menu {
        border-radius: 8px;
        border: 1px solid #e9edef;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .dropdown-item {
        padding: 10px 16px;
        font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
        background: #f0f2f5;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .conversations-panel {
            width: 100%;
        }
        .chat-panel {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .messaging-container {
            padding: 10px;
            height: calc(100vh - 70px);
        }
        .messaging-wrapper {
            border-radius: 12px;
        }
        .conversations-header {
            padding: 12px 14px;
        }
        .user-avatar-main {
            width: 36px;
            height: 36px;
        }
    }

    @media (max-width: 576px) {
        .messaging-container {
            padding: 0;
            height: calc(100vh - 60px);
        }
        .messaging-wrapper {
            border-radius: 0;
        }
        .conversations-header {
            padding: 10px 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="messaging-container">
    <div class="messaging-wrapper">
        <!-- Left Panel - Conversations -->
        <div class="conversations-panel">
            <!-- Header -->
            <div class="conversations-header">
                <div class="user-profile">
                    <div class="user-avatar-main">
                        @if(Auth::user()->avatar)
                            <img src="{{ storage_url(Auth::user()->avatar) }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <span class="header-title">Messages</span>
                </div>
                <div class="header-actions">
                    <button class="header-btn" data-bs-toggle="modal" data-bs-target="#newConversationModal" title="Nouvelle discussion">
                        <i class="fas fa-comment-medical"></i>
                    </button>
                    <div class="dropdown">
                        <button class="header-btn" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('messages.markAllRead') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="fas fa-check-double me-2"></i>Tout marquer comme lu</button>
                                </form>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('homepage') }}"><i class="fas fa-home me-2"></i>Accueil</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher ou démarrer une discussion" id="searchConversations">
                </div>
            </div>
            
            <!-- Conversations List -->
            <div class="conversations-list">
                @forelse($conversations as $conv)
                @php
                    $otherUser = $conv->user1_id == auth()->id() ? $conv->user2 : $conv->user1;
                    $unreadCount = $conv->messages()->where('sender_id', '!=', auth()->id())->where('is_read', false)->count();
                    $lastMsg = $conv->lastMessage;
                @endphp
                <a href="{{ route('messages.show', $conv->id) }}" class="conversation-item {{ $unreadCount > 0 ? 'unread' : '' }}" data-name="{{ strtolower($otherUser->name ?? '') }}">
                    <div class="conv-avatar">
                        <div class="conv-avatar-circle">
                            @if($otherUser && $otherUser->avatar)
                                <img src="{{ storage_url($otherUser->avatar) }}" alt="{{ $otherUser->name }}">
                            @else
                                {{ strtoupper(substr($otherUser->name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                    </div>
                    <div class="conv-content">
                        <div class="conv-header">
                            <span class="conv-name">{{ $otherUser->name ?? 'Utilisateur' }}</span>
                            <span class="conv-time">
                                @if($lastMsg)
                                    {{ $lastMsg->created_at->isToday() ? $lastMsg->created_at->format('H:i') : $lastMsg->created_at->format('d/m/Y') }}
                                @endif
                            </span>
                        </div>
                        <div class="conv-preview">
                            @if($lastMsg && $lastMsg->sender_id == auth()->id())
                                <i class="fas fa-check-double"></i>
                            @endif
                            <span class="conv-preview-text">
                                @if($lastMsg)
                                    {{ Str::limit($lastMsg->content, 35) }}
                                @else
                                    <em>Aucun message</em>
                                @endif
                            </span>
                            @if($unreadCount > 0)
                                <span class="unread-badge">{{ $unreadCount }}</span>
                            @endif
                        </div>
                    </div>
                </a>
                @empty
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h5>Aucune conversation</h5>
                    <p>Démarrez une nouvelle discussion</p>
                    <button class="btn-new-chat" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                        <i class="fas fa-plus me-2"></i>Nouvelle discussion
                    </button>
                </div>
                @endforelse
            </div>
        </div>
        
        <!-- Right Panel - Chat Placeholder -->
        <div class="chat-panel">
            <div class="chat-placeholder">
                <div class="placeholder-content">
                    <div class="placeholder-icon">
                        <svg viewBox="0 0 303 172" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M229.565 160.229C262.212 149.245 286.931 118.241 283.39 73.4194C278.009 5.31929 212.315 -11.5304 176.214 6.28589C167.47 10.8631 149.903 12.5054 131.229 10.5879C107.679 8.13574 82.0299 1.17908 60.4797 0.0926629C24.8158 -1.68895 -4.43715 36.4606 0.459613 73.0297C4.09599 101.396 26.1045 134.253 60.1271 147.66C87.0982 158.241 105.956 163.404 136.511 168.283C153.685 170.974 207.076 167.676 229.565 160.229Z" fill="#DAF7F3"/>
                            <path d="M100.5 96C100.5 96 115.5 66 151.5 66C187.5 66 202.5 96 202.5 96" stroke="#00A884" stroke-width="6" stroke-linecap="round"/>
                            <circle cx="117" cy="50" r="8" fill="#00A884"/>
                            <circle cx="186" cy="50" r="8" fill="#00A884"/>
                        </svg>
                    </div>
                    <h3 class="placeholder-title">Lunamars Messagerie</h3>
                    <p class="placeholder-text">
                        Envoyez et recevez des messages en toute sécurité.<br>
                        Sélectionnez une conversation pour commencer.
                    </p>
                    <button class="btn-new-chat" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                        <i class="fas fa-plus me-2"></i>Démarrer une discussion
                    </button>
                    <div class="placeholder-divider">
                        <hr>
                        <span><i class="fas fa-lock me-1"></i> Chiffrement de bout en bout</span>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Conversation -->
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-comment-medical me-2"></i>Nouvelle discussion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('messages.create.conversation') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">Destinataire</label>
                        <select class="form-select" name="recipient_id" required>
                            <option value="">Choisir un utilisateur...</option>
                            @php
                                $users = \App\Models\User::where('id', '!=', auth()->id())->orderBy('name')->get();
                            @endphp
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" placeholder="Écrivez votre message..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-send-modal"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Search conversations
    document.getElementById('searchConversations')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.conversation-item').forEach(item => {
            const name = item.dataset.name || '';
            item.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });
</script>
@endpush
