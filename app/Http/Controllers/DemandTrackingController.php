<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DemandTrackingController extends Controller
{
    public function __invoke(Request $request)
    {
        return redirect()->route('home', $request->only(['demande', 'etat', 'page']));
    }
}
