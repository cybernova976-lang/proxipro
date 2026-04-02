@extends('layouts.app')

@section('title', 'Conversation - ProxiPro')

@push('styles')
<style>
    /* ===== WHATSAPP STYLE CHAT ===== */
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
    
    .header-title {
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .header-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: white;
        font-size: 1rem;
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
    
    .conv-preview-text {
        font-size: 0.875rem;
        color: #667781;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* ===== RIGHT PANEL - CHAT AREA ===== */
    .chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #efeae2;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d1d5db' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    /* Chat Header */
    .chat-header {
        padding: 12px 20px;
        background: #f0f2f5;
        border-bottom: 1px solid #e9edef;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .chat-user-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .chat-user-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00a884, #25d366);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .chat-user-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .chat-user-details h5 {
        margin: 0 0 2px;
        font-size: 1rem;
        font-weight: 600;
        color: #111b21;
    }
    
    .chat-user-details span {
        font-size: 0.8rem;
        color: #667781;
    }
    
    .chat-user-details span.online {
        color: #25d366;
    }
    
    .chat-user-details span.blocked {
        color: #dc3545;
    }
    
    .chat-header-actions {
        display: flex;
        gap: 8px;
    }
    
    .chat-header-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: #54656f;
        font-size: 1.1rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .chat-header-btn:hover {
        background: rgba(0,0,0,0.05);
    }
    
    /* Messages Container */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px 60px;
        display: flex;
        flex-direction: column;
    }
    
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }
    
    .chat-messages::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 3px;
    }
    
    /* Date Separator */
    .date-separator {
        text-align: center;
        margin: 20px 0;
    }
    
    .date-separator span {
        background: rgba(255,255,255,0.9);
        color: #54656f;
        font-size: 0.75rem;
        padding: 6px 14px;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    /* Message Bubble */
    .message {
        max-width: 65%;
        margin-bottom: 4px;
        display: flex;
        flex-direction: column;
    }
    
    .message.own {
        align-self: flex-end;
    }
    
    .message.other {
        align-self: flex-start;
    }
    
    .message-bubble {
        padding: 8px 12px;
        border-radius: 8px;
        position: relative;
        line-height: 1.4;
        font-size: 0.95rem;
        word-wrap: break-word;
    }
    
    .message.own .message-bubble {
        background: #d9fdd3;
        color: #111b21;
        border-top-right-radius: 0;
    }
    
    .message.other .message-bubble {
        background: #ffffff;
        color: #111b21;
        border-top-left-radius: 0;
        box-shadow: 0 1px 1px rgba(0,0,0,0.05);
    }
    
    .message-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        margin-top: 2px;
        padding-right: 2px;
    }
    
    .message-time {
        font-size: 0.68rem;
        color: #667781;
    }
    
    .message-status {
        font-size: 0.85rem;
    }
    
    .message-status.sent {
        color: #8696a0;
    }
    
    .message-status.read {
        color: #53bdeb;
    }
    
    /* Typing Indicator */
    .typing-indicator {
        display: none;
        align-self: flex-start;
        background: white;
        padding: 12px 16px;
        border-radius: 8px;
        border-top-left-radius: 0;
        margin-bottom: 10px;
    }
    
    .typing-dots {
        display: flex;
        gap: 4px;
    }
    
    .typing-dots span {
        width: 8px;
        height: 8px;
        background: #8696a0;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }
    
    .typing-dots span:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .typing-dots span:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes typing {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-6px); }
    }
    
    /* Chat Input */
    .chat-input {
        padding: 12px 20px;
        background: #f0f2f5;
        position: relative;
    }
    
    .chat-input-form {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .input-actions {
        display: flex;
        gap: 4px;
    }
    
    .input-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: #54656f;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .input-btn:hover {
        background: rgba(0,0,0,0.05);
    }
    
    .message-input-wrapper {
        flex: 1;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: flex-end;
        padding: 0 12px;
    }
    
    .message-input {
        flex: 1;
        border: none;
        padding: 12px 8px;
        font-size: 0.95rem;
        color: #111b21;
        outline: none;
        background: transparent;
        resize: none;
        overflow: hidden;
        max-height: 150px;
        line-height: 1.4;
        display: block;
        height: auto;
    }
    
    .message-input::placeholder {
        color: #8696a0;
    }
    
    .btn-send {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        background: #00a884;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-send:hover {
        background: #008f72;
    }

    .emoji-picker {
        position: absolute;
        bottom: 70px;
        left: 20px;
        width: 280px;
        background: #ffffff;
        border: 1px solid #e9edef;
        border-radius: 12px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.12);
        padding: 12px;
        display: none;
        z-index: 10;
    }

    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 6px;
    }

    .emoji-btn {
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 1.1rem;
        padding: 4px;
        border-radius: 6px;
        transition: background 0.15s;
    }

    .emoji-btn:hover {
        background: #f0f2f5;
    }
    
    .message-actions {
        display: inline-flex;
        gap: 8px;
        margin-left: 8px;
        align-items: center;
    }

    .message-action-btn {
        border: none;
        background: transparent;
        color: #667781;
        font-size: 0.8rem;
        cursor: pointer;
        padding: 2px 4px;
    }

    .message-action-btn:hover {
        color: #111b21;
    }

    .message-edited {
        font-size: 0.75rem;
        color: #8696a0;
        margin-left: 6px;
    }

    .message-edit-input {
        width: 100%;
        border: 1px solid #e9edef;
        border-radius: 6px;
        padding: 6px 8px;
        font-size: 0.95rem;
        outline: none;
    }

    .message-edit-actions {
        display: flex;
        gap: 8px;
        margin-top: 6px;
    }

    .message-edit-actions button {
        border: none;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .message-edit-save {
        background: #00a884;
        color: #ffffff;
    }

    .message-edit-cancel {
        background: #e9edef;
        color: #111b21;
    }
    
    /* Blocked Notice */
    .blocked-notice {
        text-align: center;
        padding: 16px;
        background: #f0f2f5;
        color: #667781;
        font-size: 0.9rem;
    }
    
    .blocked-notice i {
        color: #dc3545;
        margin-right: 8px;
    }
    
    /* Dropdown */
    .dropdown-menu {
        border-radius: 8px;
        border: 1px solid #e9edef;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 4px 0;
    }
    
    .dropdown-item {
        padding: 10px 16px;
        font-size: 0.9rem;
        color: #3b4a54;
    }
    
    .dropdown-item:hover {
        background: #f0f2f5;
    }
    
    .dropdown-item.text-danger:hover {
        background: #fee2e2;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .conversations-panel {
            display: none;
        }
        .chat-messages {
            padding: 20px;
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
        .chat-messages {
            padding: 14px;
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
        .chat-messages {
            padding: 10px;
        }
        .message-bubble {
            max-width: 85%;
            font-size: 0.88rem;
        }
    }
</style>
@endpush

@php
    $otherUser = $conversation->user1_id == auth()->id() ? $conversation->user2 : $conversation->user1;
@endphp

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="messaging-container">
    <div class="messaging-wrapper">
        <!-- Left Panel - Conversations -->
        <div class="conversations-panel">
            <!-- Header -->
            <div class="conversations-header">
                <span class="header-title"><i class="fas fa-comments"></i> Messages</span>
                <a href="{{ route('messages.index') }}" class="header-btn" title="Toutes les conversations">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            
            <!-- Search -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher..." id="searchConversations">
                </div>
            </div>
            
            <!-- Conversations List -->
            <div class="conversations-list">
                @foreach($conversations as $conv)
                @php
                    $convOtherUser = $conv->user1_id == auth()->id() ? $conv->user2 : $conv->user1;
                    $lastMsg = $conv->lastMessage;
                @endphp
                <a href="{{ route('messages.show', $conv->id) }}" class="conversation-item {{ $conv->id == $conversation->id ? 'active' : '' }}" data-name="{{ strtolower($convOtherUser->name ?? '') }}">
                    <div class="conv-avatar">
                        <div class="conv-avatar-circle">
                            @if($convOtherUser && $convOtherUser->avatar)
                                <img src="{{ storage_url($convOtherUser->avatar) }}" alt="{{ $convOtherUser->name }}">
                            @else
                                {{ strtoupper(substr($convOtherUser->name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                    </div>
                    <div class="conv-content">
                        <div class="conv-header">
                            <span class="conv-name">{{ $convOtherUser->name ?? 'Utilisateur' }}</span>
                            <span class="conv-time">
                                @if($lastMsg)
                                    {{ $lastMsg->created_at->isToday() ? $lastMsg->created_at->format('H:i') : $lastMsg->created_at->format('d/m') }}
                                @endif
                            </span>
                        </div>
                        <span class="conv-preview-text">
                            @if($lastMsg)
                                {{ Str::limit($lastMsg->content, 30) }}
                            @endif
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        
        <!-- Right Panel - Chat Area -->
        <div class="chat-panel">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-user-info">
                    <a href="{{ route('messages.index') }}" class="d-lg-none me-2" style="color: #54656f;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="chat-user-avatar">
                        @if($otherUser && $otherUser->avatar)
                            <img src="{{ storage_url($otherUser->avatar) }}" alt="{{ $otherUser->name }}">
                        @else
                            {{ strtoupper(substr($otherUser->name ?? 'U', 0, 1)) }}
                        @endif
                    </div>
                    <div class="chat-user-details">
                        <h5>{{ $otherUser->name ?? 'Utilisateur' }}</h5>
                        @if($conversation->is_blocked)
                            <span class="blocked"><i class="fas fa-ban me-1"></i>Bloqué</span>
                        @else
                            <span class="online">En ligne</span>
                        @endif
                    </div>
                </div>
                <div class="chat-header-actions">
                    <button class="chat-header-btn" title="Rechercher">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="dropdown">
                        <button class="chat-header-btn" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.public', $otherUser->id ?? 0) }}"><i class="fas fa-user me-2"></i>Voir le profil</a></li>
                            @if($conversation->is_blocked)
                                <li><a class="dropdown-item" href="#" onclick="unblockConversation({{ $conversation->id }})"><i class="fas fa-unlock me-2 text-success"></i>Débloquer</a></li>
                            @else
                                <li><a class="dropdown-item" href="#" onclick="blockConversation({{ $conversation->id }})"><i class="fas fa-ban me-2 text-warning"></i>Bloquer</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteConversation({{ $conversation->id }})"><i class="fas fa-trash me-2"></i>Supprimer</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                @php
                    $lastDate = null;
                @endphp
                @foreach($messages as $message)
                    @php
                        $messageDate = $message->created_at->format('Y-m-d');
                        $showDate = $lastDate !== $messageDate;
                        $lastDate = $messageDate;
                    @endphp
                    
                    @if($showDate)
                    <div class="date-separator">
                        <span>
                            @if($message->created_at->isToday())
                                Aujourd'hui
                            @elseif($message->created_at->isYesterday())
                                Hier
                            @else
                                {{ $message->created_at->format('d/m/Y') }}
                            @endif
                        </span>
                    </div>
                    @endif
                    
                    <div class="message {{ $message->sender_id == auth()->id() ? 'own' : 'other' }}" data-message-id="{{ $message->id }}" data-created-at="{{ $message->created_at->timestamp }}">
                        <div class="message-bubble">
                            <div class="message-text">{{ $message->content }}</div>
                            <div class="message-meta">
                                <span class="message-time">{{ $message->created_at->format('H:i') }}</span>
                                @if($message->updated_at->gt($message->created_at))
                                    <span class="message-edited">Modifié</span>
                                @endif
                                @if($message->sender_id == auth()->id())
                                    @if($message->is_read)
                                        <i class="fas fa-check-double message-status read"></i>
                                    @else
                                        <i class="fas fa-check-double message-status sent"></i>
                                    @endif
                                    @if($message->created_at->gt(now()->subMinutes(5)))
                                        <span class="message-actions">
                                            <button type="button" class="message-action-btn message-edit" data-message-id="{{ $message->id }}">Modifier</button>
                                            <button type="button" class="message-action-btn message-delete" data-message-id="{{ $message->id }}">Supprimer</button>
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                
                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
            
            <!-- Chat Input -->
            @if(!$conversation->is_blocked || $conversation->blocked_by != auth()->id())
            <div class="chat-input">
                <form class="chat-input-form" id="messageForm">
                    <div class="input-actions">
                        <button type="button" class="input-btn" id="emojiToggle" title="Emoji">
                            <i class="far fa-smile"></i>
                        </button>
                    </div>
                    <div class="message-input-wrapper">
                        <textarea class="message-input" id="messageInput" placeholder="Tapez un message" autocomplete="off" rows="1"></textarea>
                    </div>
                    <button type="submit" class="btn-send" title="Envoyer">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="emoji-picker" id="emojiPicker">
                    <div class="emoji-grid">
                        <button type="button" class="emoji-btn" data-emoji="😀">😀</button>
                        <button type="button" class="emoji-btn" data-emoji="😁">😁</button>
                        <button type="button" class="emoji-btn" data-emoji="😂">😂</button>
                        <button type="button" class="emoji-btn" data-emoji="🤣">🤣</button>
                        <button type="button" class="emoji-btn" data-emoji="😊">😊</button>
                        <button type="button" class="emoji-btn" data-emoji="😍">😍</button>
                        <button type="button" class="emoji-btn" data-emoji="😘">😘</button>
                        <button type="button" class="emoji-btn" data-emoji="😎">😎</button>
                        <button type="button" class="emoji-btn" data-emoji="🤔">🤔</button>
                        <button type="button" class="emoji-btn" data-emoji="😢">😢</button>
                        <button type="button" class="emoji-btn" data-emoji="😭">😭</button>
                        <button type="button" class="emoji-btn" data-emoji="😡">😡</button>
                        <button type="button" class="emoji-btn" data-emoji="👍">👍</button>
                        <button type="button" class="emoji-btn" data-emoji="🙏">🙏</button>
                        <button type="button" class="emoji-btn" data-emoji="👏">👏</button>
                        <button type="button" class="emoji-btn" data-emoji="🔥">🔥</button>
                        <button type="button" class="emoji-btn" data-emoji="🎉">🎉</button>
                        <button type="button" class="emoji-btn" data-emoji="❤️">❤️</button>
                        <button type="button" class="emoji-btn" data-emoji="💡">💡</button>
                        <button type="button" class="emoji-btn" data-emoji="✅">✅</button>
                        <button type="button" class="emoji-btn" data-emoji="❌">❌</button>
                        <button type="button" class="emoji-btn" data-emoji="📎">📎</button>
                        <button type="button" class="emoji-btn" data-emoji="📷">📷</button>
                        <button type="button" class="emoji-btn" data-emoji="📝">📝</button>
                    </div>
                </div>
            </div>
            @else
            <div class="blocked-notice">
                <i class="fas fa-ban"></i>Vous avez bloqué cette conversation. <a href="#" onclick="unblockConversation({{ $conversation->id }})">Débloquer</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const conversationId = {{ $conversation->id }};
    const chatMessages = document.getElementById('chatMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const emojiToggle = document.getElementById('emojiToggle');
    const emojiPicker = document.getElementById('emojiPicker');
    const updateUrlTemplate = "{{ route('messages.update', ['message' => '__ID__']) }}";
    const deleteUrlTemplate = "{{ route('messages.delete', ['message' => '__ID__']) }}";
    let lastMessageId = {{ $messages->last()?->id ?? 0 }};
    
    // Scroll to bottom on load
    chatMessages.scrollTop = chatMessages.scrollHeight;
    document.querySelectorAll('.message.own').forEach(scheduleActionExpiry);
    
    // Send message
    messageForm?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();

        if (!content) return;
        
        try {
            const response = await fetch('{{ route("messages.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    content: content
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const now = new Date();
                const time = now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                const msgDiv = document.createElement('div');
                msgDiv.className = 'message own';
                msgDiv.dataset.messageId = data.message?.id || '';
                msgDiv.dataset.createdAt = Math.floor(Date.now() / 1000);
                msgDiv.innerHTML = buildMessageHtml({
                    content,
                    time,
                    isOwn: true,
                    isRead: false,
                    canEdit: true,
                    isEdited: false
                });
                
                const typingIndicator = document.getElementById('typingIndicator');
                chatMessages.insertBefore(msgDiv, typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                messageInput.value = '';
                messageInput.style.height = 'auto';
                scheduleActionExpiry(msgDiv);
                
                if (data.message?.id) {
                    lastMessageId = data.message.id;
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    });
    
    // Auto-resize textarea and send on Enter (Shift+Enter for newline)
    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 150) + 'px';
    }

    messageInput?.addEventListener('input', function() {
        autoResize(this);
    });

    messageInput?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm?.requestSubmit();
        }
    });

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function buildMessageHtml({ content, time, isOwn, isRead, canEdit, isEdited }) {
        const contentHtml = content ? `<div class="message-text">${escapeHtml(content)}</div>` : '';
        const editedHtml = isEdited ? `<span class="message-edited">Modifié</span>` : '';
        const actionsHtml = isOwn && canEdit
            ? `<span class="message-actions">
                    <button type="button" class="message-action-btn message-edit">Modifier</button>
                    <button type="button" class="message-action-btn message-delete">Supprimer</button>
               </span>`
            : '';
        const statusHtml = isOwn
            ? `<i class="fas fa-check-double message-status ${isRead ? 'read' : 'sent'}"></i>`
            : '';
        return `
            <div class="message-bubble">
                ${contentHtml}
                <div class="message-meta">
                    <span class="message-time">${time}</span>
                    ${editedHtml}
                    ${statusHtml}
                    ${actionsHtml}
                </div>
            </div>
        `;
    }

    function insertAtCursor(input, text) {
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const value = input.value;
        input.value = value.slice(0, start) + text + value.slice(end);
        const cursor = start + text.length;
        input.setSelectionRange(cursor, cursor);
        input.focus();
    }

    function scheduleActionExpiry(messageEl) {
        const createdAt = parseInt(messageEl.dataset.createdAt || '0', 10);
        if (!createdAt) return;
        const expiresAt = (createdAt + 300) * 1000;
        const delay = expiresAt - Date.now();
        if (delay <= 0) {
            removeMessageActions(messageEl);
            return;
        }
        setTimeout(() => removeMessageActions(messageEl), delay);
    }

    function removeMessageActions(messageEl) {
        const actions = messageEl.querySelector('.message-actions');
        if (actions) actions.remove();
    }

    emojiToggle?.addEventListener('click', () => {
        const isVisible = emojiPicker.style.display === 'block';
        emojiPicker.style.display = isVisible ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!emojiPicker || !emojiToggle) return;
        if (emojiPicker.contains(e.target) || emojiToggle.contains(e.target)) return;
        emojiPicker.style.display = 'none';
    });

    emojiPicker?.addEventListener('click', (e) => {
        const btn = e.target.closest('.emoji-btn');
        if (!btn) return;
        const emoji = btn.getAttribute('data-emoji') || '';
        if (emoji) {
            insertAtCursor(messageInput, emoji);
        }
    });
    
    // Poll for new messages
    setInterval(async () => {
        try {
            const response = await fetch(`/messages/${conversationId}/poll?last_id=${lastMessageId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();
            
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    if (msg.sender_id != {{ auth()->id() }}) {
                        const time = new Date(msg.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'message other';
                        msgDiv.innerHTML = buildMessageHtml({
                            content: msg.content || '',
                            time,
                            isOwn: false,
                            isRead: false,
                            canEdit: false,
                            isEdited: msg.updated_at && msg.updated_at !== msg.created_at
                        });
                        const typingIndicator = document.getElementById('typingIndicator');
                        chatMessages.insertBefore(msgDiv, typingIndicator);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 3000);
    
    // Search conversations
    document.getElementById('searchConversations')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.conversation-item').forEach(item => {
            const name = item.dataset.name || '';
            item.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });

    chatMessages?.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.message-edit');
        const deleteBtn = e.target.closest('.message-delete');

        if (editBtn) {
            const messageEl = editBtn.closest('.message');
            if (!messageEl || messageEl.classList.contains('editing')) return;
            startEditMessage(messageEl);
        }

        if (deleteBtn) {
            const messageEl = deleteBtn.closest('.message');
            if (!messageEl) return;
            const messageId = messageEl.dataset.messageId;
            if (!messageId) return;
            if (!confirm('Supprimer ce message ?')) return;
            await deleteMessage(messageEl, messageId);
        }
    });

    function startEditMessage(messageEl) {
        const textEl = messageEl.querySelector('.message-text');
        if (!textEl) return;
        const originalText = textEl.textContent || '';

        const input = document.createElement('textarea');
        input.className = 'message-edit-input';
        input.value = originalText;
        input.rows = 1;
        input.style.resize = 'none';
        input.style.overflow = 'hidden';
        input.addEventListener('input', function() {
            autoResize(this);
        });

        const actions = document.createElement('div');
        actions.className = 'message-edit-actions';
        actions.innerHTML = `
            <button type="button" class="message-edit-save">Enregistrer</button>
            <button type="button" class="message-edit-cancel">Annuler</button>
        `;

        const container = document.createElement('div');
        container.appendChild(input);
        container.appendChild(actions);

        textEl.replaceWith(container);
        messageEl.classList.add('editing');
        input.focus();

        actions.querySelector('.message-edit-cancel').addEventListener('click', () => {
            container.replaceWith(textEl);
            messageEl.classList.remove('editing');
        });

        actions.querySelector('.message-edit-save').addEventListener('click', async () => {
            const newText = input.value.trim();
            if (!newText) return;
            const messageId = messageEl.dataset.messageId;
            if (!messageId) return;

            const success = await updateMessage(messageId, newText);
            if (success) {
                textEl.textContent = newText;
                container.replaceWith(textEl);
                messageEl.classList.remove('editing');
                ensureEditedLabel(messageEl);
            }
        });
    }

    async function updateMessage(messageId, content) {
        try {
            const response = await fetch(updateUrlTemplate.replace('__ID__', messageId), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ content })
            });
            const data = await response.json();
            return !!data.success;
        } catch (error) {
            console.error('Erreur:', error);
            return false;
        }
    }

    async function deleteMessage(messageEl, messageId) {
        try {
            const response = await fetch(deleteUrlTemplate.replace('__ID__', messageId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();
            if (data.success) {
                messageEl.remove();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    function ensureEditedLabel(messageEl) {
        const meta = messageEl.querySelector('.message-meta');
        if (!meta || meta.querySelector('.message-edited')) return;
        const label = document.createElement('span');
        label.className = 'message-edited';
        label.textContent = 'Modifié';
        const timeEl = meta.querySelector('.message-time');
        if (timeEl && timeEl.nextSibling) {
            meta.insertBefore(label, timeEl.nextSibling);
        } else {
            meta.appendChild(label);
        }
    }
    
    function blockConversation(id) {
        if (confirm('Voulez-vous bloquer cette conversation ?')) {
            fetch(`/messages/${id}/block`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => location.reload());
        }
    }
    
    function unblockConversation(id) {
        fetch(`/messages/${id}/unblock`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => location.reload());
    }
    
    function deleteConversation(id) {
        if (confirm('Supprimer cette conversation ? Cette action est irréversible.')) {
            fetch(`/messages/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => window.location.href = '{{ route("messages.index") }}');
        }
    }
</script>
@endpush
