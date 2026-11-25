<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->quote_number }}</title>
    <style>
@include('pro.partials.pdf-styles')
    </style>
</head>
<body>
@include('pro.partials.quote-sheet')
</body>
</html>
