@if($contactAd = \App\Support\DemandPublicationContinuation::pendingAd(request()))
<div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
    <p class="font-semibold mb-1">Retrouvez cette publication après connexion</p>
    <p class="mb-0">{{ Str::limit($contactAd->title, 100) }}</p>
</div>
@endif
