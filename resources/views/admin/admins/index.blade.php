@extends('admin.layouts.app')

@section('title', 'Gestion des Administrateurs')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-user-shield text-warning me-2"></i>Gestion des Administrateurs
        </h2>
        <p class="text-muted mb-0">Nommer, gérer et configurer les privilèges des administrateurs</p>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Note:</strong> Seul l'administrateur principal ({{ config('admin.principal_admin.name') }}) peut accéder à cette page.
</div>

<div class="row g-4">
    <!-- Liste des admins actuels -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-users-cog me-2 text-primary"></i>
                    Administrateurs Actuels
                    <span class="badge bg-primary ms-2">{{ $admins->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 py-3 ps-4">Administrateur</th>
                                <th class="border-0 py-3">Privilèges</th>
                                <th class="border-0 py-3 pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admins as $admin)
                            @php
                                $isPrincipal = $admin->email === config('admin.principal_admin.email');
                                $adminPrivileges = $admin->adminPrivilegeList();
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-{{ $isPrincipal ? 'warning' : 'danger' }} text-white me-2">
                                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $admin->name }}</strong>
                                            @if($isPrincipal)
                                                <span class="badge bg-warning text-dark ms-1">Principal</span>
                                            @endif
                                            <br><small class="text-muted">{{ $admin->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($isPrincipal)
                                        <span class="badge bg-success">Tous les privilèges</span>
                                    @elseif(count($adminPrivileges) > 0)
                                        @foreach($adminPrivileges as $priv)
                                            @if(isset($privileges[$priv]))
                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                    {{ $privileges[$priv]['label'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    @else
                                        <span class="text-muted">Aucun privilège spécifique</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    @if(!$isPrincipal)
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editPrivilegesModal{{ $admin->id }}"
                                                title="Modifier les privilèges">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.admins.revoke', $admin->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Révoquer les droits admin de {{ $admin->name }} ?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Révoquer">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary">Protégé</span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Modal Modifier privilèges -->
                            @if(!$isPrincipal)
                            <div class="modal fade" id="editPrivilegesModal{{ $admin->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Privilèges de {{ $admin->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.admins.privileges', $admin->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <p class="text-muted mb-3">Sélectionnez les privilèges à accorder à cet administrateur:</p>
                                                @foreach($privileges as $key => $privilege)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="privileges[]" 
                                                           value="{{ $key }}" 
                                                           id="priv_{{ $key }}_{{ $admin->id }}"
                                                           {{ in_array($key, $adminPrivileges) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="priv_{{ $key }}_{{ $admin->id }}">
                                                        <strong>{{ $privilege['label'] }}</strong>
                                                        <br><small class="text-muted">{{ $privilege['description'] }}</small>
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Enregistrer
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Promouvoir un utilisateur -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2 text-success"></i>
                    Promouvoir un Utilisateur
                </h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="promoteForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Rechercher un utilisateur</label>
                        <input type="text" class="form-control" id="userSearch" placeholder="Nom ou email...">
                        <input type="hidden" name="user_id" id="selectedUserId">
                        <div id="userSearchResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Privilèges à accorder</label>
                        @foreach($privileges as $key => $privilege)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" 
                                   name="privileges[]" 
                                   value="{{ $key }}" 
                                   id="new_priv_{{ $key }}">
                            <label class="form-check-label" for="new_priv_{{ $key }}">
                                {{ $privilege['label'] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" id="promoteBtn" disabled>
                        <i class="fas fa-user-shield me-2"></i>Promouvoir Administrateur
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Légende des privilèges -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2 text-info"></i>
                    Légende des Privilèges
                </h5>
            </div>
            <div class="card-body">
                @foreach($privileges as $key => $privilege)
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="d-block">{{ $privilege['label'] }}</strong>
                    <small class="text-muted">{{ $privilege['description'] }}</small>
                </div>
                @endforeach
            </div>
        </div>
    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    const resultsContainer = document.getElementById('userSearchResults');
    const selectedUserIdInput = document.getElementById('selectedUserId');
    const promoteForm = document.getElementById('promoteForm');
    const promoteBtn = document.getElementById('promoteBtn');
    
    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }
        
        debounceTimer = setTimeout(async function() {
            try {
                const response = await fetch(`{{ route('admin.users') }}?search=${encodeURIComponent(query)}&role=user`);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Parse table rows to get users
                const rows = doc.querySelectorAll('tbody tr');
                let users = [];
                
                rows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    const emailCell = row.querySelector('td:nth-child(3)');
                    const viewLink = row.querySelector('a[href*="/admin/users/"]');
                    
                    if (nameCell && emailCell && viewLink) {
                        const href = viewLink.getAttribute('href');
                        const id = href.split('/').pop();
                        users.push({
                            id: id,
                            name: nameCell.querySelector('strong')?.textContent?.trim() || '',
                            email: emailCell.textContent?.trim() || ''
                        });
                    }
                });
                
                resultsContainer.innerHTML = '';
                
                if (users.length === 0) {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted">Aucun utilisateur trouvé</div>';
                    return;
                }
                
                users.forEach(user => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `<strong>${user.name}</strong><br><small class="text-muted">${user.email}</small>`;
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        selectedUserIdInput.value = user.id;
                        searchInput.value = user.name;
                        resultsContainer.innerHTML = '';
                        promoteBtn.disabled = false;
                        promoteForm.action = `/admin/admins/${user.id}/promote`;
                    });
                    resultsContainer.appendChild(item);
                });
                
            } catch (error) {
                console.error('Erreur de recherche:', error);
            }
        }, 300);
    });
});
</script>
@endsection
