@extends('layouts.app')
@section('title', 'Mon suivi - Prokejem')
@push('styles')
<style>
    /* ===== CLEAN FLAT DASHBOARD ===== */
    .main-content-with-sidebar {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
        min-height: 100vh;
    }
    .dash-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 28px 20px 60px;
    }

    /* Greeting */
    .dash-greeting {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dash-greeting h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .dash-greeting h1 span {
        color: #64748b;
        font-weight: 400;
    }
    .dash-greeting p {
        margin: 4px 0 0;
        font-size: 0.88rem;
        color: #94a3b8;
    }
    .btn-publish {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: white;
        border: none;
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-publish:hover {
        background: linear-gradient(135deg, #6d28d9, #5b21b6);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);
    }

    /* Stats + Points Row */
    .top-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 280px;
        gap: 16px;
        margin-bottom: 24px;
    }

    .mini-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: border-color 0.2s;
    }
    .mini-stat:hover { border-color: #cbd5e1; }

    .mini-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .mini-stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    .mini-stat-label {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    /* Points compact card */
    .points-card {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #fcd34d;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }
    .points-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(252, 211, 77, 0.4);
        color: inherit;
    }
    .points-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .points-card-top i {
        font-size: 1.2rem;
        color: #f59e0b;
    }
    .points-card-val {
        font-size: 1.6rem;
        font-weight: 800;
        color: #92400e;
        line-height: 1;
    }
    .points-card-label {
        font-size: 0.75rem;
        color: #a16207;
        font-weight: 500;
    }
    .points-card-actions {
        display: flex;
        gap: 6px;
        margin-top: 10px;
    }
    .points-card-actions a {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.15s;
    }
    .pts-buy {
        background: #f59e0b;
        color: white;
    }
    .pts-buy:hover { background: #d97706; color: white; }
    .pts-history {
        background: rgba(146, 64, 14, 0.1);
        color: #92400e;
    }
    .pts-history:hover { background: rgba(146, 64, 14, 0.18); color: #78350f; }

    /* Quick Actions Row */
    .actions-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .action-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.2s;
    }
    .action-card:hover {
        border-color: #a78bfa;
        background: #faf5ff;
        color: inherit;
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .action-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .action-card h5 {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 3px;
    }
    .action-card p {
        font-size: 0.76rem;
        color: #94a3b8;
        margin: 0;
        line-height: 1.3;
    }

    /* Table Section */
    .table-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
    }
    .table-section-head {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-section-head h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .table-section-head h3 i { color: #7c3aed; font-size: 0.95rem; }

    .dash-table {
        width: 100%;
        margin: 0;
    }
    .dash-table thead th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        border: none;
        padding: 12px 18px;
    }
    .dash-table tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        color: #334155;
    }
    .dash-table tbody tr:last-child td { border-bottom: none; }
    .dash-table tbody tr:hover { background: #fafbfd; }

    .dash-table a.ad-title-link {
        color: #1e293b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.15s;
    }
    .dash-table a.ad-title-link:hover { color: #7c3aed; }

    .cat-badge {
        background: #f1f5f9;
        color: #64748b;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .status-dot {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 500;
    }
    .status-dot::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-active::before { background: #22c55e; }
    .status-active { color: #166534; }
    .status-pending::before { background: #f59e0b; }
    .status-pending { color: #b45309; }
    .status-expired::before { background: #ef4444; }
    .status-expired { color: #dc2626; }

    .btn-table-action {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        font-size: 0.82rem;
        text-decoration: none;
        transition: all 0.15s;
    }
    .btn-table-action:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    .btn-table-boost {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
    }
    .btn-table-boost:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        color: white;
    }
    .btn-table-delete {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fecaca;
    }
    .btn-table-delete:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    .empty-box {
        text-align: center;
        padding: 50px 20px;
    }
    .empty-box i {
        font-size: 3rem;
        color: #e2e8f0;
        margin-bottom: 16px;
    }
    .empty-box h5 {
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
    }
    .empty-box p {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .top-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .top-row .points-card {
            grid-column: span 2;
        }
        .actions-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dash-wrap { padding: 20px 14px 40px; }
        .dash-greeting { margin-bottom: 20px; }
        .dash-greeting h1 { font-size: 1.25rem; }
        .dash-greeting p { font-size: 0.82rem; }
        .btn-publish { padding: 8px 16px; font-size: 0.85rem; }
        .top-row { gap: 12px; margin-bottom: 20px; }
        .mini-stat { padding: 16px; gap: 10px; border-radius: 12px; }
        .mini-stat-icon { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
        .mini-stat-val { font-size: 1.3rem; }
        .mini-stat-label { font-size: 0.73rem; }
        .actions-row { gap: 12px; margin-bottom: 20px; }
        .action-card { padding: 16px 14px; gap: 10px; border-radius: 12px; }
        .action-icon { width: 40px; height: 40px; font-size: 1rem; border-radius: 10px; }
        .action-card h5 { font-size: 0.82rem; }
        .action-card p { font-size: 0.72rem; }
        .table-section { border-radius: 12px; }
        .table-section-head { padding: 14px 16px; }
        .table-section-head h3 { font-size: 0.92rem; }
        .points-card { padding: 14px 16px; border-radius: 12px; }
        .points-card-val { font-size: 1.4rem; }
    }

    @media (max-width: 576px) {
        .top-row {
            grid-template-columns: 1fr 1fr;
        }
        .actions-row {
            grid-template-columns: 1fr;
        }
        .dash-wrap { padding: 14px 10px 30px; }
        .dash-greeting h1 { font-size: 1.1rem; }
        .dash-greeting { gap: 8px; }
        .btn-publish { padding: 7px 14px; font-size: 0.82rem; gap: 6px; }
        .mini-stat { padding: 14px 12px; border-radius: 10px; }
        .mini-stat-icon { width: 34px; height: 34px; font-size: 0.9rem; }
        .mini-stat-val { font-size: 1.15rem; }
        .action-card { padding: 14px 12px; border-radius: 10px; }
        .action-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .table-section-head { padding: 12px 14px; }
        .dash-table thead th { font-size: 0.72rem; padding: 10px 8px; }
        .dash-table tbody td { font-size: 0.78rem; padding: 10px 8px; }
    }

    @media (max-width: 420px) {
        .dash-wrap { padding: 10px 8px 24px; }
        .top-row { gap: 8px; }
        .mini-stat { padding: 12px 10px; }
        .mini-stat-val { font-size: 1.05rem; }
        .points-card { padding: 12px; }
        .points-card-val { font-size: 1.2rem; }
        .points-card-actions a { font-size: 0.68rem; padding: 3px 8px; }
        .action-card h5 { font-size: 0.78rem; }
        .action-card p { font-size: 0.68rem; }
        .dash-table { font-size: 0.72rem; }
    }

    /* Transaction History Styles */
    .tx-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .tx-type-points {
        background: #fef3c7;
        color: #92400e;
    }
    .tx-type-subscription {
        background: #f3e8ff;
        color: #7c3aed;
    }
    .tx-type-boost {
        background: #fff7ed;
        color: #ea580c;
    }
    .tx-type-other {
        background: #f1f5f9;
        color: #64748b;
    }
    .tx-amount {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.9rem;
    }
    .tx-points {
        font-weight: 700;
        font-size: 0.9rem;
    }
    .tx-points-positive {
        color: #16a34a;
    }
    .tx-points-negative {
        color: #ef4444;
    }

    /* Active Subscriptions / Purchases Section */
    .active-subs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        padding: 20px;
    }
    .sub-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        transition: all 0.2s;
    }
    .sub-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .sub-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .sub-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        color: white;
    }
    .sub-card-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 2px;
    }
    .sub-card-subtitle {
        font-size: 0.76rem;
        color: #94a3b8;
        margin: 0;
    }
    .sub-card-badge {
        margin-left: auto;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
    }
    .sub-card-progress {
        margin-bottom: 8px;
    }
    .sub-progress-bar {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .sub-progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease;
    }
    .sub-progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .sub-card-actions {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .sub-card-actions a {
        font-size: 0.76rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/activity-dashboard.css') }}?v={{ filemtime(public_path('css/activity-dashboard.css')) }}">
@endpush
@section('content')
<div id="dashboardContent">
    @include('dashboard.partials.overview')
</div>
@endsection
