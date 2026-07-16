@extends('admin.layouts.app')

@section('title', 'Comptes supprimés')

@section('content')
<div class="row align-items-center g-3 mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-user-slash text-danger me-2"></i>Comptes Supprimés
        </h2>
        <p class="text-muted mb-0">Historique des comptes utilisateurs supprimés avec possibilité de restauration</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.blocked-emails.index') }}" class="btn btn-outline-danger">
            <i class="fas fa-envelope-circle-xmark me-2"></i>Gérer les e-mails bloqués
        </a>
    </div>
</div>

<!-- Liste des comptes supprimés -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if(isset($deletedUsers) && $deletedUsers->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 py-3 ps-4">ID</th>
                        <th class="border-0 py-3">Utilisateur</th>
                        <th class="border-0 py-3">Email original</th>
                        <th class="border-0 py-3">Type</th>
                        <th class="border-0 py-3">Motif de suppression</th>
                        <th class="border-0 py-3">Réinscription</th>
                        <th class="border-0 py-3">Dates</th>
                        <th class="border-0 py-3 pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deletedUsers as $user)
                    @php
                        $log = $deletionLogs[$user->id] ?? null;
                        $originalName = $log->name ?? $user->name;
                        $originalEmail = $log->email ?? $user->email;
                        $reason = $log->reason ?? 'Non spécifié';
                        $accountType = $log->account_type ?? 'particulier';
                        $dataSummary = $log ? json_decode($log->data_summary, true) : null;
                        $normalizedEmail = \App\Models\BlockedEmail::normalize($originalEmail);
                        $blockedEmail = $blockedEmails->get($normalizedEmail);
                    @endphp
                    <tr>
                        <td class="ps-4">{{ $user->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-secondary text-white me-2">
                                    {{ strtoupper(substr($originalName, 0, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $originalName }}</strong>
                                    @if($dataSummary)
                                        <br><small class="text-muted">
                                            {{ $dataSummary['ads_count'] ?? 0 }} annonces · 
                                            {{ $dataSummary['messages_count'] ?? 0 }} messages · 
                                            {{ $dataSummary['reviews_received'] ?? 0 }} avis reçus
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><span class="text-muted">{{ $originalEmail }}</span></td>
                        <td>
                            @if($accountType === 'professionnel')
                                <span class="badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.72rem;">PRO</span>
                            @else
                                <span class="badge bg-secondary" style="font-size: 0.72rem;">Particulier</span>
                            @endif
                            @if($dataSummary && !empty($dataSummary['is_service_provider']))
                                <span class="badge bg-info text-white" style="font-size: 0.7rem;">Prestataire</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $reasonColors = [
                                    'Mauvaise expérience' => 'danger',
                                    'Je n\'utilise plus le service' => 'secondary',
                                    'Problèmes de confidentialité' => 'warning',
                                    'Trop de notifications' => 'info',
                                    'Autre' => 'dark',
                                ];
                                $badgeColor = $reasonColors[$reason] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}" style="font-size: 0.75rem;">
                                {{ $reason }}
                            </span>
                        </td>
                        <td>
                            @if($blockedEmail)
                                <span class="badge bg-danger mb-2">
                                    <i class="fas fa-ban me-1"></i>E-mail bloqué
                                </span>
                                <form action="{{ route('admin.blocked-emails.destroy', $blockedEmail) }}" method="POST"
                                      onsubmit="return confirm(@js('Autoriser à nouveau '.$originalEmail.' à créer un compte ?'));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-lock-open me-1"></i>Autoriser
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-success border border-success mb-2">
                                    Réinscription autorisée
                                </span>
                                <form action="{{ route('admin.blocked-emails.from-deleted-account', $user->id) }}" method="POST"
                                      onsubmit="return confirm(@js('Empêcher '.$originalEmail.' de créer un nouveau compte ?'));">
                                    @csrf
                                    <input type="hidden" name="reason" value="Blocage décidé depuis l’historique du compte supprimé #{{ $user->id }}.">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-ban me-1"></i>Bloquer l’e-mail
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted d-block">Inscrit : {{ $user->created_at->format('d/m/Y') }}</small>
                            <span class="text-danger" style="font-size: 0.85rem;">
                                Supprimé : {{ $user->deleted_at->format('d/m/Y H:i') }}
                            </span>
                            <br>
                            <small class="text-muted">{{ $user->deleted_at->diffForHumans() }}</small>
                        </td>
                        <td class="pe-4 text-end">
                            <form action="{{ route('admin.accounts.restore', $user->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Restaurer le compte de {{ $user->name }} ?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Restaurer">
                                    <i class="fas fa-undo me-1"></i>Restaurer
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.accounts.force-delete', $user->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('ATTENTION: Cette action est IRRÉVERSIBLE ! Supprimer définitivement le compte de {{ $user->name }} et toutes ses données ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer définitivement">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($deletedUsers->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $deletedUsers->links() }}
        </div>
        @endif
        @else
        <div class="text-center py-5">
            <i class="fas fa-user-check fa-4x text-success mb-4"></i>
            <h5 class="text-muted">Aucun compte supprimé</h5>
            <p class="text-muted mb-0">
                Tous les comptes utilisateurs sont actifs.<br>
                Les comptes supprimés apparaîtront ici avec la possibilité de les restaurer.
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Info sur le soft delete -->
<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Note:</strong> Les comptes supprimés sont conservés pendant 30 jours avant d'être automatiquement purgés. 
    Vous pouvez restaurer un compte à tout moment pendant cette période.
</div>

<style>
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }
</style>
@endsection
