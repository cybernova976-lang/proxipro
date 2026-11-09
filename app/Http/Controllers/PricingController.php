<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        return redirect()->route('public.provider-offers');
    }

    public function subscribe(Request $request)
    {
        return redirect()->route('pro.subscription');
    }

    public function cancel()
    {
        return redirect()->route('pro.subscription');
    }

    public function resume()
    {
        return redirect()->route('pro.subscription');
    }

    /**
     * Ancien formulaire conservé pour répondre explicitement aux liens obsolètes.
     */
    public function purchasePoints(Request $request)
    {
        abort(410, \App\Support\RetiredPoints::MESSAGE);
    }
}
