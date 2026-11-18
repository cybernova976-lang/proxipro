<?php

namespace App\Http\Controllers;

use App\Models\TermsVersion;
use Illuminate\Http\Request;

/** Page où un membre lit le résumé d'une nouvelle version des conditions et l'accepte. */
class TermsAcceptanceController extends Controller
{
    public function show(Request $request)
    {
        $version = TermsVersion::current();
        if (! $version || TermsVersion::isPaused() || $request->user()->hasAcceptedTerms($version)) {
            return redirect()->intended(route('feed'));
        }

        return view('legal.accept-terms', [
            'version' => $version,
            'continueUrl' => $request->session()->get('url.intended', route('feed')),
        ]);
    }

    public function store(Request $request)
    {
        $version = TermsVersion::current();
        if (! $version) {
            return redirect()->route('feed');
        }

        $request->validate(
            ['accept' => ['accepted']],
            ['accept.accepted' => 'Cochez la case pour accepter les conditions.']
        );

        $request->user()->acceptTerms($version, $request->ip(), 'prompt');

        return redirect()->intended(route('feed'))
            ->with('info', 'Merci : vous avez accepté les conditions d’utilisation.');
    }
}
