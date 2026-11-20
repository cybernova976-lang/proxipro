<div class="modal fade" id="profilePhotoModal" tabindex="-1" aria-labelledby="profilePhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="profilePhotoModalLabel">Photo de profil de {{ $user->name }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ storage_url($user->avatar) }}" alt="Photo de profil de {{ $user->name }}" class="img-fluid rounded" loading="lazy">
            </div>
        </div>
    </div>
</div>
