{{-- ============================================== --}}
{{-- SMART SUGGESTIONS PRO - Bannière intelligente --}}
{{-- Affichée dans le feed pour les professionnels --}}
{{-- ============================================== --}}

@if(Auth::check() && (Auth::user()->isProfessionnel() || Auth::user()->isServiceProvider()) && !empty($proSuggestions ?? []))
@php
    $primaryProSuggestion = $proSuggestions[0];
    $remainingProSuggestions = array_slice($proSuggestions, 1);
@endphp
<style>
/* ============================================
   PRO SUGGESTIONS BANNER
   ============================================ */
.pro-suggest-banner {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.pro-suggest-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
}

.pro-suggest-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.pro-suggest-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #1e293b;
}
.pro-suggest-title i {
    color: #6366f1;
}

/* Progress bar */
.pro-suggest-progress {
    display: flex;
    align-items: center;
    gap: 8px;
}
.pro-suggest-progress-bar {
    width: 100px;
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.pro-suggest-progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s ease;
}
.pro-suggest-progress-text {
    font-size: 0.72rem;
    font-weight: 700;
}

/* Suggestion items */
.pro-suggest-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pro-suggest-more {
    margin-top: 10px;
    border-top: 1px solid #eef2f7;
    padding-top: 9px;
}
.pro-suggest-more summary {
    color: #4f46e5;
    font-size: .72rem;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    list-style: none;
}
.pro-suggest-more summary::-webkit-details-marker { display: none; }
.pro-suggest-more[open] summary { margin-bottom: 9px; }
.pro-suggest-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 10px;
    transition: all 0.2s;
    border: 1px solid transparent;
}
.pro-suggest-item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
}
.pro-suggest-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.pro-suggest-info {
    flex: 1;
    min-width: 0;
}
.pro-suggest-info strong {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1px;
}
.pro-suggest-info span {
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.3;
}
.pro-suggest-action {
    padding: 6px 14px;
    border-radius: 8px;
    border: none;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    color: #fff;
    flex-shrink: 0;
}
.pro-suggest-action:hover {
    opacity: 0.85;
    transform: translateY(-1px);
}

/* Dismiss */
.pro-suggest-dismiss {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 0.7rem;
    cursor: pointer;
    padding: 2px 4px;
}
.pro-suggest-dismiss:hover {
    color: #64748b;
}

@media (max-width: 640px) {
    .pro-suggest-item {
        flex-wrap: wrap;
        gap: 8px;
    }
    .pro-suggest-action {
        width: 100%;
        text-align: center;
        padding: 8px;
    }
    .pro-suggest-progress-bar {
        width: 60px;
    }
}
</style>

<div class="pro-suggest-banner" id="proSuggestBanner">
    <div class="pro-suggest-header">
        <div class="pro-suggest-title">
            <i class="fas fa-lightbulb"></i>
            Votre prochaine action
        </div>
        <div class="pro-suggest-progress">
            <div class="pro-suggest-progress-bar">
                <div class="pro-suggest-progress-fill" style="width: {{ $proProfileCompletion ?? 0 }}%; background: {{ ($proProfileCompletion ?? 0) >= 80 ? '#10b981' : (($proProfileCompletion ?? 0) >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
            </div>
            <span class="pro-suggest-progress-text" style="color: {{ ($proProfileCompletion ?? 0) >= 80 ? '#10b981' : (($proProfileCompletion ?? 0) >= 50 ? '#f59e0b' : '#ef4444') }};">
                {{ $proProfileCompletion ?? 0 }}%
            </span>
            <button class="pro-suggest-dismiss" onclick="document.getElementById('proSuggestBanner').style.display='none'" title="Masquer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="pro-suggest-list">
        @php $suggestion = $primaryProSuggestion; @endphp
        <div class="pro-suggest-item">
            <div class="pro-suggest-icon" style="background: {{ $suggestion['color'] }}15; color: {{ $suggestion['color'] }};">
                <i class="{{ $suggestion['icon'] }}"></i>
            </div>
            <div class="pro-suggest-info">
                <strong>{{ $suggestion['title'] }}</strong>
                <span>{{ $suggestion['description'] }}</span>
            </div>
            <button class="pro-suggest-action" style="background: {{ $suggestion['color'] }};"
                onclick="{{ $suggestion['action'] }}">
                {{ $suggestion['action_label'] }}
            </button>
        </div>
    </div>

    @if(count($remainingProSuggestions) > 0)
    <details class="pro-suggest-more">
        <summary><i class="fas fa-chevron-down me-1"></i> Voir les {{ count($remainingProSuggestions) }} autres étapes</summary>
        <div class="pro-suggest-list">
        @foreach($remainingProSuggestions as $suggestion)
        <div class="pro-suggest-item">
            <div class="pro-suggest-icon" style="background: {{ $suggestion['color'] }}15; color: {{ $suggestion['color'] }};">
                <i class="{{ $suggestion['icon'] }}"></i>
            </div>
            <div class="pro-suggest-info">
                <strong>{{ $suggestion['title'] }}</strong>
                <span>{{ $suggestion['description'] }}</span>
            </div>
            <button class="pro-suggest-action" style="background: {{ $suggestion['color'] }};"
                onclick="{{ $suggestion['action'] }}">
                {{ $suggestion['action_label'] }}
            </button>
        </div>
        @endforeach
        </div>
    </details>
    @endif
</div>
@endif
