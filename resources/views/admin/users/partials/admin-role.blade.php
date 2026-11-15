@php
    $targetIsAdmin = ($user->role ?? 'user') === 'admin';
    // Seuls les privilèges d’un administrateur en poste sont précochés : d’anciens
    // privilèges restés sur un compte redevenu simple utilisateur ne doivent pas
    // être réattribués par mégarde lors d’une nouvelle nomination.
    $targetPrivileges = $targetIsAdmin ? $user->adminPrivilegeList() : [];
@endphp
<div class="card border-0 shadow-sm h-100" id="adminUserRole">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-user-shield me-2 text-danger"></i>Rôle administrateur</h6>
        @if($targetIsAdmin)
            <span class="badge bg-danger">Administrateur</span>
        @else
            <span class="badge bg-secondary">Utilisateur</span>
        @endif
    </div>
    <div class="card-body">
        @if(! $viewerIsPrincipalAdmin)
            <p class="text-muted small mb-0">
                <i class="fas fa-lock me-1"></i>
                Seul l’administrateur principal peut nommer un administrateur ou lui retirer ses droits.
            </p>
        @else
            <form method="POST" action="{{ $targetIsAdmin ? route('admin.admins.privileges', $user->id) : route('admin.admins.promote', $user->id) }}"
                  @if(! $targetIsAdmin) onsubmit="return confirm('Nommer {{ $user->name }} administrateur ? Ce compte aura accès à l’administration.');" @endif>
                @csrf
                @if($targetIsAdmin)
                    @method('PUT')
                @endif
                <p class="small fw-semibold mb-2">Privilèges</p>
                @foreach($adminPrivilegeCatalog as $privilegeKey => $privilege)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="privileges[]" value="{{ $privilegeKey }}"
                               id="userPrivilege_{{ $privilegeKey }}" {{ in_array($privilegeKey, $targetPrivileges, true) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="userPrivilege_{{ $privilegeKey }}" title="{{ $privilege['description'] ?? '' }}">
                            {{ $privilege['label'] }}
                        </label>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-{{ $targetIsAdmin ? 'primary' : 'danger' }} w-100 mt-3">
                    @if($targetIsAdmin)
                        <i class="fas fa-save me-2"></i>Enregistrer les privilèges
                    @else
                        <i class="fas fa-user-shield me-2"></i>Nommer administrateur
                    @endif
                </button>
            </form>

            @if($targetIsAdmin)
                <form method="POST" action="{{ route('admin.admins.revoke', $user->id) }}" class="mt-2"
                      onsubmit="return confirm('Retirer les droits administrateur de {{ $user->name }} ?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fas fa-user-minus me-2"></i>Retirer les droits administrateur
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>
