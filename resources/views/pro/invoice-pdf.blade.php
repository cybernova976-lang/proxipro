<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->documentLabel() }} {{ $invoice->invoice_number }}</title>
    <style>
@include('pro.partials.pdf-styles')
    </style>
</head>
<body>
@include('pro.partials.invoice-sheet')
</body>
</html>
