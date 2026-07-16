@php
    $storedAdDetails = isset($ad) && is_array($ad->ad_details) ? $ad->ad_details : [];
    $domainFieldValues = old('ad_details', $storedAdDetails);
    $initialPublicationDomain = old('publication_domain', isset($ad) ? $ad->publication_domain : null);
@endphp

<style>
    .domain-section {
        border: 1px solid #dbeafe;
        background: linear-gradient(145deg, #f8fbff 0%, #ffffff 70%);
    }

    .domain-intro {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 22px;
        padding: 14px 16px;
        border-radius: 12px;
        background: #eff6ff;
        color: #36516f;
        font-size: .9rem;
        line-height: 1.5;
    }

    .domain-intro-icon {
        font-size: 1.35rem;
        line-height: 1;
    }

    .domain-fields-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .domain-field label {
        display: block;
        margin-bottom: 8px;
        color: #26384a;
        font-weight: 600;
        font-size: .9rem;
    }

    .domain-field .form-control,
    .domain-field .form-select {
        min-height: 48px;
        border-color: #d7e1eb;
        border-radius: 10px;
    }

    .domain-field.is-conditional-hidden {
        display: none;
    }

    @media (max-width: 768px) {
        .domain-fields-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 15px;
        }

        .domain-intro {
            margin-bottom: 18px;
        }
    }
</style>

<input type="hidden" name="publication_domain" id="publication_domain" value="{{ $initialPublicationDomain }}">

<div class="form-section domain-section" id="publication-domain-section" style="display: {{ $initialPublicationDomain ? 'block' : 'none' }};">
    <div class="section-header">
        <div class="section-icon"><i class="fas fa-clipboard-list"></i></div>
        <h4 class="section-title" id="publication-domain-title">Informations spécifiques</h4>
    </div>

    @foreach($publicationSchemas as $domain => $schema)
        <div class="publication-domain-panel" data-publication-domain="{{ $domain }}" style="display: {{ $initialPublicationDomain === $domain ? 'block' : 'none' }};">
            <div class="domain-intro">
                <span class="domain-intro-icon">{{ $schema['icon'] }}</span>
                <span>{{ $schema['introduction'] }}</span>
            </div>

            <div class="domain-fields-grid">
                @foreach($schema['fields'] as $key => $field)
                    @php
                        $value = data_get($domainFieldValues, $key, $field['default'] ?? null);
                        $showWhen = $field['show_when'] ?? null;
                        $requiredWhen = $field['required_when'] ?? null;
                        $isInitiallyEnabled = $initialPublicationDomain === $domain;
                    @endphp
                    <div class="domain-field"
                         data-domain-field-wrapper="{{ $key }}"
                         @if($showWhen)
                             data-show-when-field="{{ array_key_first($showWhen) }}"
                             data-show-when-value="{{ reset($showWhen) }}"
                         @endif
                         @if($requiredWhen)
                             data-required-when-field="{{ array_key_first($requiredWhen) }}"
                             data-required-when-value="{{ reset($requiredWhen) }}"
                         @endif>
                        <label for="ad_details_{{ $domain }}_{{ $key }}">
                            {{ $field['label'] }}
                            @if(!empty($field['required']))<span class="text-danger">*</span>@endif
                        </label>

                        @if(($field['type'] ?? 'text') === 'select')
                            <select class="form-select @error('ad_details.'.$key) is-invalid @enderror"
                                    id="ad_details_{{ $domain }}_{{ $key }}"
                                    name="ad_details[{{ $key }}]"
                                    data-detail-key="{{ $key }}"
                                    data-base-required="{{ !empty($field['required']) ? '1' : '0' }}"
                                    @disabled(!$isInitiallyEnabled)
                                    @required(!empty($field['required']) && $isInitiallyEnabled)>
                                @if(!empty($field['placeholder']))
                                    <option value="">{{ $field['placeholder'] }}</option>
                                @endif
                                @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif(($field['type'] ?? 'text') === 'checkbox')
                            <div class="form-check pt-2">
                                <input class="form-check-input @error('ad_details.'.$key) is-invalid @enderror"
                                       type="checkbox"
                                       id="ad_details_{{ $domain }}_{{ $key }}"
                                       name="ad_details[{{ $key }}]"
                                       value="1"
                                       data-detail-key="{{ $key }}"
                                       data-base-required="0"
                                       @checked((bool) $value)
                                       @disabled(!$isInitiallyEnabled)>
                                <label class="form-check-label" for="ad_details_{{ $domain }}_{{ $key }}">Oui</label>
                            </div>
                        @else
                            <input type="{{ $field['type'] ?? 'text' }}"
                                   class="form-control @error('ad_details.'.$key) is-invalid @enderror"
                                   id="ad_details_{{ $domain }}_{{ $key }}"
                                   name="ad_details[{{ $key }}]"
                                   value="{{ $value }}"
                                   data-detail-key="{{ $key }}"
                                   data-base-required="{{ !empty($field['required']) ? '1' : '0' }}"
                                   @if(isset($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                   @if(isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                   @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                   @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                   @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                   @disabled(!$isInitiallyEnabled)
                                   @required(!empty($field['required']) && $isInitiallyEnabled)>
                        @endif

                        @error('ad_details.'.$key)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
