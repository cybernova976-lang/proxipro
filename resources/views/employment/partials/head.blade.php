{{-- En-tête commun des pages emploi : hors index tant qu'aucune offre n'est affichée. --}}
@if($pkOffers->total() === 0)
    @push('meta')
        <meta name="robots" content="noindex">
    @endpush
@endif
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/client-space.css') }}?v={{ filemtime(public_path('css/client-space.css')) }}">
@endpush
