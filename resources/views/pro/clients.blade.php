@extends('pro.layout')
@section('title', 'Mes Clients - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <h1>Mes clients</h1>
    </div>
    <button class="btn btn-pro-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="fas fa-plus me-1"></i> Ajouter un client
    </button>
</div>

@if($clients->isEmpty())
    <div class="pro-card">
        <div class="pro-empty">
            <div class="pro-empty-icon" aria-hidden="true"><i class="fas fa-users"></i></div>
            <h5>Pas encore de clients</h5>
            <p>Ajoutez vos clients manuellement ou ils seront ajoutés automatiquement lors de la création d'un devis ou d'une facture.</p>
            <button class="btn btn-pro-outline mt-2" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="fas fa-plus me-1"></i> Ajouter un client
            </button>
        </div>
    </div>
@else
    <div class="pro-card">
        <div class="table-responsive">
            <table class="pro-table pro-table--cards pro-table--clients">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Ville</th>
                        <th>Projets</th>
                        <th class="pk-col-amount">CA généré</th>
                        <th>Statut</th>
                        <th class="pk-col-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                    <tr>
                        <td class="pk-col-id">
                            <div class="d-flex align-items-center gap-2">
                                <div class="pro-table__initial" aria-hidden="true">
                                    {{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $client->name }}</strong>
                                    @if($client->company)
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $client->company }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="pk-col-subject">
                            @if($client->email)<div>{{ $client->email }}</div>@endif
                            @if($client->phone)<div>{{ $client->phone }}</div>@endif
                        </td>
                        <td class="pk-col-date" data-label="Ville">@if($client->city){{ $client->city }}@endif</td>
                        <td class="pk-col-due" data-label="Projets">{{ $client->total_projects ?? 0 }}</td>
                        <td class="pk-col-amount">{{ \App\Support\Money::eur($client->total_revenue ?? 0, 0) }}</td>
                        <td class="pk-col-status"><span class="pro-status pro-status-{{ $client->getStatusColor() }}">{{ $client->getStatusLabel() }}</span></td>
                        <td class="pk-col-actions">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light pro-table__menu" data-bs-toggle="dropdown" aria-label="Actions pour {{ $client->name }}">
                                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="editClient({{ $client->id }}, '{{ $client->name }}', '{{ $client->email }}', '{{ $client->phone }}', '{{ $client->address }}', '{{ $client->city }}', '{{ $client->company }}', '{{ $client->notes }}', '{{ $client->status }}')"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('pro.clients.delete', $client->id) }}" onsubmit="return confirm('Supprimer ce client ?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $clients->links() }}</div>
    </div>
@endif

{{-- Add Client Modal --}}
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid var(--pro-border);">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-primary"></i>Ajouter un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pro.clients.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom complet *</label>
                        <input type="text" name="name" class="form-control" required style="border-radius: 10px;">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="phone" class="form-control" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ville</label>
                            <input type="text" name="city" class="form-control" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Entreprise</label>
                            <input type="text" name="company" class="form-control" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="address" class="form-control" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" style="border-radius: 10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn btn-pro-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Client Modal --}}
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid var(--pro-border);">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Modifier le client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editClientForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom complet *</label>
                        <input type="text" name="name" id="editName" class="form-control" required style="border-radius: 10px;">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ville</label>
                            <input type="text" name="city" id="editCity" class="form-control" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Entreprise</label>
                            <input type="text" name="company" id="editCompany" class="form-control" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" id="editNotes" class="form-control" rows="2" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Statut</label>
                        <select name="status" id="editStatus" class="form-select" style="border-radius: 10px;">
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                            <option value="prospect">Prospect</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn btn-pro-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editClient(id, name, email, phone, address, city, company, notes, status) {
    document.getElementById('editClientForm').action = '/pro/clients/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editCity').value = city;
    document.getElementById('editCompany').value = company;
    document.getElementById('editNotes').value = notes;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editClientModal')).show();
}
</script>
@endsection
