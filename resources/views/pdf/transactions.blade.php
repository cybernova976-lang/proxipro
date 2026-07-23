<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Historique des transactions - Lunamars</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Header */
        .pdf-header {
            display: table;
            width: 100%;
            padding: 20px 0 15px;
            border-bottom: 3px solid #7c3aed;
            margin-bottom: 20px;
        }
        .pdf-header-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }
        .brand-mark {
            width: 46px;
            height: 46px;
            object-fit: contain;
            margin-bottom: 8px;
        }
        .pdf-header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 40%;
        }
        .brand-name {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
            letter-spacing: -1px;
        }
        .brand-tagline {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
        }
        .doc-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }

        /* User Info */
        .user-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .user-info-title {
            font-size: 12px;
            font-weight: bold;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .user-info-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .user-info-label {
            display: table-cell;
            width: 120px;
            font-weight: bold;
            color: #64748b;
            font-size: 10px;
        }
        .user-info-value {
            display: table-cell;
            color: #1e293b;
            font-size: 10px;
        }

        /* Section Title */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        .section-title i {
            color: #7c3aed;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table thead th {
            background: #7c3aed;
            color: white;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        table thead th:first-child {
            border-radius: 6px 0 0 0;
        }
        table thead th:last-child {
            border-radius: 0 6px 0 0;
        }
        table tbody td {
            padding: 8px 10px;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        table tbody tr:nth-child(even) {
            background: #fafbfc;
        }
        .amount-cell {
            font-weight: bold;
            color: #1e293b;
        }
        .points-positive {
            color: #16a34a;
            font-weight: bold;
        }
        .points-negative {
            color: #ef4444;
            font-weight: bold;
        }
        .status-completed {
            color: #16a34a;
            font-weight: bold;
        }
        .status-pending {
            color: #f59e0b;
            font-weight: bold;
        }
        .status-failed {
            color: #ef4444;
            font-weight: bold;
        }
        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .type-points { background: #fef3c7; color: #92400e; }
        .type-subscription { background: #f3e8ff; color: #7c3aed; }
        .type-boost { background: #fff7ed; color: #ea580c; }
        .type-other { background: #f1f5f9; color: #64748b; }

        /* Summary */
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .summary-label {
            display: table-cell;
            color: #64748b;
            font-weight: bold;
            font-size: 10px;
            width: 60%;
        }
        .summary-value {
            display: table-cell;
            font-weight: bold;
            color: #1e293b;
            text-align: right;
            font-size: 11px;
        }

        /* Footer */
        .pdf-footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="pdf-header">
        <div class="pdf-header-left">
            <img src="{{ public_path('images/brand/lunamars-mark.png') }}" alt="" class="brand-mark">
            <div class="brand-name">{{ config('app.name', 'Lunamars') }}</div>
            <div class="brand-tagline">Plateforme internationale de services</div>
        </div>
        <div class="pdf-header-right">
            <div class="doc-title">Historique des transactions</div>
            <div class="doc-date">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    <!-- User Info -->
    <div class="user-info">
        <div class="user-info-title">Informations du compte</div>
        <div class="user-info-row">
            <div class="user-info-label">Nom complet :</div>
            <div class="user-info-value">{{ $user->name }}</div>
        </div>
        <div class="user-info-row">
            <div class="user-info-label">Email :</div>
            <div class="user-info-value">{{ $user->email }}</div>
        </div>
        <div class="user-info-row">
            <div class="user-info-label">Points actuels :</div>
            <div class="user-info-value">{{ $user->available_points ?? 0 }} points</div>
        </div>
        <div class="user-info-row">
            <div class="user-info-label">Membre depuis :</div>
            <div class="user-info-value">{{ $user->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary-box">
        <div class="summary-row">
            <div class="summary-label">Nombre total de paiements :</div>
            <div class="summary-value">{{ $transactions->count() }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Montant total dépensé :</div>
            <div class="summary-value">{{ number_format($transactions->where('status', 'completed')->sum('amount'), 2, ',', ' ') }} €</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Nombre de transactions de points :</div>
            <div class="summary-value">{{ $pointTransactions->count() }}</div>
        </div>
    </div>

    <!-- Financial Transactions -->
    <div class="section-title">Paiements et achats</div>
    @if($transactions->isEmpty())
        <p class="no-data">Aucun paiement enregistré</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <span class="type-badge type-{{ strtolower($tx->type ?? 'other') }}">
                        @if($tx->type === 'POINTS') Achat de points
                        @elseif($tx->type === 'SUBSCRIPTION') Abonnement
                        @elseif($tx->type === 'BOOST') Boost
                        @else {{ $tx->type ?? 'Paiement' }}
                        @endif
                    </span>
                </td>
                <td>{{ $tx->description ?? '-' }}</td>
                <td class="amount-cell">{{ number_format($tx->amount, 2, ',', ' ') }} €</td>
                <td>
                    <span class="status-{{ $tx->status == 'completed' ? 'completed' : ($tx->status == 'pending' ? 'pending' : 'failed') }}">
                        {{ $tx->status == 'completed' ? 'Complété' : ($tx->status == 'pending' ? 'En attente' : ucfirst($tx->status ?? 'Inconnu')) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Point Transactions -->
    <div class="section-title">Transactions de points</div>
    @if($pointTransactions->isEmpty())
        <p class="no-data">Aucune transaction de points enregistrée</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Source</th>
                <th>Description</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pointTransactions as $ptx)
            <tr>
                <td>{{ $ptx->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <span class="type-badge type-points">{{ ucfirst($ptx->source ?? $ptx->type ?? 'Système') }}</span>
                </td>
                <td>{{ $ptx->description ?? '-' }}</td>
                <td class="{{ $ptx->points >= 0 ? 'points-positive' : 'points-negative' }}">
                    {{ $ptx->points >= 0 ? '+' : '' }}{{ $ptx->points }} pts
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Footer -->
    <div class="pdf-footer">
        <p>Ce document a été généré automatiquement par Lunamars — {{ $generatedAt->format('d/m/Y à H:i') }}</p>
        <p>Pour toute question, contactez-nous sur la plateforme.</p>
    </div>
</body>
</html>
