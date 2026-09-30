<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionInactivity
{
    public const TIMEOUT_SECONDS = 3600;

    public function key(Request $request): string
    {
        return hash('sha256', $request->session()->get('inactivity_guard_key', '').':'.$request->user()->id);
    }

    public function lastInteraction(Request $request): ?int
    {
        if (! $request->session()->has('inactivity_guard_key')) {
            return null;
        }
        $value = DB::table('session_user_activity')->where('session_key', $this->key($request))
            ->where('user_id', $request->user()->id)->value('last_interaction_at');

        return $value === null ? null : (int) $value;
    }

    public function start(Request $request): int
    {
        $now = now()->timestamp;
        if (! $request->session()->has('inactivity_guard_key')) {
            $request->session()->put('inactivity_guard_key', Str::random(64));
        }
        DB::table('session_user_activity')->insertOrIgnore([
            'session_key' => $this->key($request),
            'user_id' => $request->user()->id,
            'last_interaction_at' => $now,
        ]);
        $request->session()->put('inactivity_guard_started', (int) $request->user()->id);

        return $this->lastInteraction($request) ?? $now;
    }

    public function touch(Request $request, int $ageMilliseconds): void
    {
        $now = now()->timestamp;
        $interaction = $now - (int) ceil($ageMilliseconds / 1000);
        // An expired session cannot be revived, nor can an older tab move time back.
        DB::table('session_user_activity')->where('session_key', $this->key($request))
            ->where('user_id', $request->user()->id)
            ->where('last_interaction_at', '>', $now - self::TIMEOUT_SECONDS)
            ->where('last_interaction_at', '<', $interaction)
            ->update(['last_interaction_at' => $interaction]);
    }
}
