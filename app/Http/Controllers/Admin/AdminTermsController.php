<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Conditions d'utilisation côté administration : publier une nouvelle version
 * (tous les membres devront l'accepter) et suivre le nombre d'acceptations.
 */
class AdminTermsController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $current = TermsVersion::current();
        $members = User::count();

        return view('admin.terms.index', [
            'current' => $current,
            'paused' => TermsVersion::isPaused(),
            'members' => $members,
            // Nombres mesurés : membres actifs ayant accepté la version en cours.
            'accepted' => $current ? User::where('accepted_terms_version_id', $current->id)->count() : 0,
            'versions' => TermsVersion::with('publisher')->withCount('acceptances')->latest('id')->get(),
            'defaultEffectiveDate' => now()->addDays(15)->format('Y-m-d'),
        ]);
    }

    /** Interrupteur : suspendre ou reprendre la demande d'acceptation, sans rien effacer. */
    public function pause(Request $request)
    {
        $data = $request->validate(['paused' => ['required', 'in:0,1']]);
        TermsVersion::setPaused($data['paused'] === '1');

        return redirect()->route('admin.terms.index')->with('success', $data['paused'] === '1'
            ? 'Demande d’acceptation suspendue : plus aucun membre n’est sollicité. Les acceptations déjà données sont conservées.'
            : 'Demande d’acceptation reprise : les membres qui n’ont pas encore accepté sont de nouveau sollicités.');
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'summary' => ['required', 'string', 'min:20', 'max:2000'],
            'effective_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'confirm' => ['accepted'],
        ], [
            'summary.required' => 'Décrivez ce qui change : ce résumé est montré à chaque membre.',
            'summary.min' => 'Le résumé doit faire au moins 20 caractères.',
            'effective_date.after_or_equal' => 'La date d’entrée en vigueur ne peut pas être passée.',
            'confirm.accepted' => 'Cochez la case pour confirmer la publication.',
        ]);

        // Une version par publication ; deux publications le même jour restent distinctes.
        $label = now()->format('Y-m-d');
        $suffix = TermsVersion::where('version', 'like', $label.'%')->count();

        $version = TermsVersion::create([
            'version' => $suffix > 0 ? $label.'-'.($suffix + 1) : $label,
            'summary' => trim($data['summary']),
            // Le jour même : obligatoire tout de suite. Sinon, à la première heure du jour choisi.
            'effective_at' => $data['effective_date'] === $label ? now() : $data['effective_date'].' 00:00:00',
            'published_by' => $request->user()->id,
        ]);
        TermsVersion::forgetCurrent();

        // L'auteur de la publication l'accepte en la publiant.
        $request->user()->acceptTerms($version, $request->ip(), 'publication');

        return redirect()->route('admin.terms.index')
            ->with('success', 'Nouvelle version publiée. Chaque membre devra l’accepter'
                .($version->isMandatory() ? ' dès sa prochaine visite.' : ' à partir du '.$version->effective_at->format('d/m/Y').' ; d’ici là, elle lui est proposée.'));
    }
}
