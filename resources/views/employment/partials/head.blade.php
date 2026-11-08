{{-- En-tête commun des pages emploi : hors index tant qu'aucune offre vérifiée n'est affichée. --}}
@push('meta')
    <meta name="robots" content="noindex">
@endpush
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/client-space.css') }}?v={{ filemtime(public_path('css/client-space.css')) }}">
@endpush
