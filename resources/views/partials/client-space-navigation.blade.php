<div class="pk-client-space" data-client-space>
    <div class="pk-client-space__inner">
        <div class="pk-client-space__heading">
            <p>Mon espace particulier</p>
            <span>Les services, mes demandes et mes échanges</span>
        </div>
        <nav class="pk-client-space__tabs" aria-label="Mon espace particulier">
            @foreach($pkClientSpaceTabs as $spaceTab)
                <a class="pk-client-space__tab" href="{{ $spaceTab['url'] }}"
                   data-space-section="{{ $spaceTab['key'] }}" @if($spaceTab['active']) aria-current="page" @endif>
                    <i class="{{ $spaceTab['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $spaceTab['label'] }}</span>
                    @if($spaceTab['key'] === 'messages' && ($unreadCount ?? 0) > 0)
                        <span class="pk-client-space__badge" aria-label="{{ $unreadCount }} messages non lus">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</div>
