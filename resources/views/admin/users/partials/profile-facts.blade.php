<div class="row g-4 mb-4" id="adminUserProfileFacts">
    @foreach($profileSections as $sectionKey => $section)
        @continue($section['rows'] === [] && $section['empty'] === '')
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100" data-profile-section="{{ $sectionKey }}">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0"><i class="fas {{ $section['icon'] }} me-2 text-primary"></i>{{ $section['title'] }}</h6>
                </div>
                <div class="card-body pt-0">
                    @if($section['rows'] === [])
                        <p class="text-muted small mb-0">{{ $section['empty'] }}</p>
                    @else
                        <dl class="row small mb-0">
                            @foreach($section['rows'] as $row)
                                <dt class="col-sm-5 text-muted fw-normal">{{ $row['label'] }}</dt>
                                <dd class="col-sm-7 mb-2 text-break">
                                    <span class="fw-semibold {{ ! empty($row['warning']) ? 'text-danger' : '' }}">{{ $row['value'] }}</span>
                                    @if(filled($row['href'] ?? null))
                                        <a href="{{ $row['href'] }}" class="d-block" @if(! str_starts_with($row['href'], url('/'))) target="_blank" rel="noopener noreferrer" @endif>
                                            {{ $row['link'] ?? 'Ouvrir' }}<i class="fas fa-arrow-up-right-from-square ms-1 small"></i>
                                        </a>
                                    @endif
                                    @if(filled($row['note'] ?? null))
                                        <span class="d-block {{ ! empty($row['warning']) ? 'text-danger' : 'text-muted' }}">{{ $row['note'] }}</span>
                                    @endif
                                </dd>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
