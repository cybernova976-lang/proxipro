@extends('pro.layout')

@section('title', 'Agenda')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/pro-agenda.css') }}?v={{ filemtime(public_path('css/pro-agenda.css')) }}">
@endpush

@section('content')
<div class="agenda-page">

    {{-- Header --}}
    <div class="pro-content-header">
        <div>
            <h1>Agenda</h1>
            <p class="mb-0 mt-1">Suivez vos rendez-vous, échéances et interactions clients</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="eventFilter" style="width: auto; border-radius: 10px;">
                <option value="all">Tout afficher</option>
                <option value="quote">Devis uniquement</option>
                <option value="invoice">Factures uniquement</option>
                <option value="interaction">Interactions</option>
            </select>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="agenda-stat-card">
                <div class="agenda-stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <span class="agenda-stat-value">{{ $agendaStats['overdue_invoices'] }}</span>
                    <span class="agenda-stat-label">Factures en retard</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="agenda-stat-card">
                <div class="agenda-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <span class="agenda-stat-value">{{ $agendaStats['upcoming_deadlines'] }}</span>
                    <span class="agenda-stat-label">Échéances cette semaine</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="agenda-stat-card">
                <div class="agenda-stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <span class="agenda-stat-value">{{ $agendaStats['active_quotes'] }}</span>
                    <span class="agenda-stat-label">Devis en attente</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="agenda-stat-card">
                <div class="agenda-stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <span class="agenda-stat-value">{{ $agendaStats['today_tasks'] }}</span>
                    <span class="agenda-stat-label">Activités aujourd'hui</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Timeline --}}
        <div class="col-lg-8">
            <div class="agenda-card">
                <div class="agenda-card-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-stream me-2 pro-card-icon"></i>Fil d'activité</h6>
                    <span class="badge bg-light text-dark" id="eventCount">{{ count($events) }} événements</span>
                </div>
                <div class="agenda-card-body">
                    @if(count($events) === 0)
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-day" style="font-size: 3rem; color: #e2e8f0;"></i>
                            <p class="text-muted mt-3">Aucun événement à afficher pour le moment.</p>
                            <p class="text-muted small">Les devis acceptés, factures en attente et interactions clients apparaîtront ici.</p>
                        </div>
                    @else
                        <div class="timeline" id="eventsTimeline">
                            @php
                                $lastDate = null;
                            @endphp
                            @foreach($events as $event)
                                @php
                                    $eventDate = \Carbon\Carbon::parse($event['date']);
                                    $dateLabel = $eventDate->isToday() ? "Aujourd'hui" :
                                                ($eventDate->isYesterday() ? 'Hier' :
                                                ($eventDate->isTomorrow() ? 'Demain' :
                                                $eventDate->translatedFormat('l d F Y')));
                                    $showDateSep = ($lastDate !== $dateLabel);
                                    $lastDate = $dateLabel;
                                @endphp

                                @if($showDateSep)
                                    <div class="timeline-date-separator">
                                        <span>{{ $dateLabel }}</span>
                                    </div>
                                @endif

                                <div class="timeline-item" data-type="{{ $event['type'] }}">
                                    <div class="timeline-dot" style="background: {{ $event['color'] }};"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="timeline-badge" style="background: {{ $event['color'] }}15; color: {{ $event['color'] }};">
                                                    @if($event['type'] === 'quote')
                                                        <i class="fas fa-file-alt me-1"></i>Devis
                                                    @elseif($event['type'] === 'invoice')
                                                        <i class="fas fa-file-invoice-dollar me-1"></i>Facture
                                                    @else
                                                        <i class="fas fa-handshake me-1"></i>Interaction
                                                    @endif
                                                </span>
                                                <h6 class="timeline-title mt-2 mb-1">{{ $event['title'] }}</h6>
                                                <span class="timeline-client">
                                                    <i class="fas fa-user me-1"></i>{{ $event['client'] }}
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <span class="timeline-time">
                                                    <i class="far fa-clock me-1"></i>{{ $eventDate->translatedFormat('H:i') }}
                                                </span>
                                                @if(isset($event['amount']) && $event['amount'])
                                                    <div class="timeline-amount mt-1">
                                                        {{ number_format($event['amount'], 2, ',', ' ') }} €
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Status badge --}}
                                        <div class="mt-2">
                                            @php
                                                $statusConfig = [
                                                    'pending' => ['label' => 'En attente', 'class' => 'bg-warning text-dark'],
                                                    'accepted' => ['label' => 'Accepté', 'class' => 'bg-success'],
                                                    'sent' => ['label' => 'Envoyée', 'class' => 'bg-info'],
                                                    'paid' => ['label' => 'Payée', 'class' => 'bg-success'],
                                                    'rejected' => ['label' => 'Refusé', 'class' => 'bg-danger'],
                                                    'active' => ['label' => 'Actif', 'class' => 'bg-success'],
                                                    'inactive' => ['label' => 'Inactif', 'class' => 'bg-secondary'],
                                                ];
                                                $sc = $statusConfig[$event['status']] ?? ['label' => ucfirst($event['status']), 'class' => 'bg-secondary'];
                                            @endphp
                                            <span class="badge {{ $sc['class'] }}" style="font-size: .7rem;">{{ $sc['label'] }}</span>

                                            @if($event['type'] === 'invoice' && $eventDate->isPast())
                                                <span class="badge bg-danger" style="font-size: .7rem;">
                                                    <i class="fas fa-exclamation-circle me-1"></i>Échue
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="col-lg-4">
            {{-- Today Overview --}}
            <div class="agenda-card mb-3">
                <div class="agenda-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-sun me-2 pro-card-icon"></i>Aujourd'hui</h6>
                </div>
                <div class="agenda-card-body">
                    <div class="today-date text-center mb-3">
                        <div class="today-day">{{ now()->translatedFormat('d') }}</div>
                        <div class="today-month">{{ now()->translatedFormat('F Y') }}</div>
                        <div class="today-weekday text-muted">{{ now()->translatedFormat('l') }}</div>
                    </div>
                    <div class="today-summary">
                        <div class="today-summary-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>{{ $agendaStats['today_tasks'] }} activité(s)</span>
                        </div>
                        <div class="today-summary-item">
                            <i class="fas fa-hourglass-half text-warning"></i>
                            <span>{{ $agendaStats['active_quotes'] }} devis en cours</span>
                        </div>
                        <div class="today-summary-item">
                            <i class="fas fa-bell text-danger"></i>
                            <span>{{ $agendaStats['overdue_invoices'] }} rappel(s)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="agenda-card mb-3">
                <div class="agenda-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-bolt me-2 pro-card-icon"></i>Actions rapides</h6>
                </div>
                <div class="agenda-card-body p-2">
                    <a href="{{ route('pro.quotes.create') }}" class="quick-action-btn">
                        <i class="fas fa-plus-circle text-primary"></i>
                        <span>Créer un devis</span>
                    </a>
                    <a href="{{ route('pro.invoices.create') }}" class="quick-action-btn">
                        <i class="fas fa-file-invoice text-success"></i>
                        <span>Créer une facture</span>
                    </a>
                    <a href="{{ route('pro.clients') }}" class="quick-action-btn">
                        <i class="fas fa-user-plus text-info"></i>
                        <span>Ajouter un client</span>
                    </a>
                    <a href="{{ route('pro.profile.edit') }}" class="quick-action-btn">
                        <i class="fas fa-user-edit text-secondary"></i>
                        <span>Modifier profil</span>
                    </a>
                </div>
            </div>

            {{-- Upcoming Deadlines --}}
            <div class="agenda-card">
                <div class="agenda-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-flag me-2 pro-card-icon"></i>Prochaines échéances</h6>
                </div>
                <div class="agenda-card-body">
                    @php
                        $deadlines = $events->filter(function ($e) {
                            return $e['type'] === 'invoice' && \Carbon\Carbon::parse($e['date'])->isFuture();
                        })->take(5);
                    @endphp
                    @if($deadlines->count() === 0)
                        <p class="text-muted text-center small py-3 mb-0">Aucune échéance à venir</p>
                    @else
                        @foreach($deadlines as $dl)
                            <div class="deadline-item">
                                <div class="deadline-info">
                                    <span class="deadline-title">{{ $dl['title'] }}</span>
                                    <span class="deadline-client">{{ $dl['client'] }}</span>
                                </div>
                                <div class="deadline-date">
                                    {{ \Carbon\Carbon::parse($dl['date'])->translatedFormat('d M') }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const filter = document.getElementById('eventFilter');
    const timeline = document.getElementById('eventsTimeline');
    const countBadge = document.getElementById('eventCount');

    if (filter && timeline) {
        filter.addEventListener('change', function() {
            const type = this.value;
            const items = timeline.querySelectorAll('.timeline-item');
            let visible = 0;

            items.forEach(item => {
                if (type === 'all' || item.dataset.type === type) {
                    item.style.display = '';
                    visible++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (countBadge) {
                countBadge.textContent = visible + ' événement' + (visible > 1 ? 's' : '');
            }
        });
    }
});
</script>
@endsection
