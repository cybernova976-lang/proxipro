<?php

namespace App\Http\Controllers;

use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsletterPreferenceController extends Controller
{
    public function show(Request $request, User $user)
    {
        return response()->view('settings.newsletter-unsubscribe', [
            'unsubscribeUrl' => $request->fullUrl(),
            'unsubscribed' => ! $user->newsletter_subscribed,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function unsubscribe(Request $request, User $user)
    {
        DB::transaction(function () use ($user) {
            $user->update(['newsletter_subscribed' => false]);
            ManagedEmail::where('user_id', $user->id)->where('type', 'newsletter')
                ->whereIn('status', [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED])
                ->update(['status' => ManagedEmail::STATUS_CANCELLED]);
        });

        return redirect()->to($request->fullUrl());
    }
}
