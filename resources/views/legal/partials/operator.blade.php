<p>
    La plateforme est exploitée par <strong>{{ \App\Support\PlatformFeatures::legalValue('legal_entity_name', 'entity_name') }}</strong>,
    {{ \App\Support\PlatformFeatures::legalValue('legal_entity_form', 'entity_form') }},
    au capital de {{ \App\Support\PlatformFeatures::legalValue('legal_share_capital', 'share_capital') }},
    identifiée sous le SIREN {{ \App\Support\PlatformFeatures::legalValue('legal_registration_number', 'registration_number') }}.
    Siège social : {{ \App\Support\PlatformFeatures::legalValue('legal_address', 'address') }}.
    Retrouvez ses coordonnées dans les <a href="{{ route('legal.mentions') }}">mentions légales</a>.
</p>
