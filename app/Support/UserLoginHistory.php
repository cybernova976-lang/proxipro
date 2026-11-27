<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserLoginHistory
{
    private const LIMIT = 3;

    public static function record(User $user, string $method): void
    {
        $loggedInAt = now('UTC');

        try {
            DB::transaction(function () use ($user, $method, $loggedInAt): void {
                // Sérialiser les connexions simultanées du même compte avant de purger l’historique.
                $account = DB::table('users')->where('id', $user->id)->lockForUpdate()->first(['id']);
                if ($account === null) {
                    return;
                }

                DB::table('user_logins')->insert([
                    'user_id' => $user->id,
                    'logged_in_at' => $loggedInAt,
                    'method' => $method,
                ]);

                $recentIds = self::recent($user)->pluck('id');
                DB::table('user_logins')->where('user_id', $user->id)->whereNotIn('id', $recentIds)->delete();
            }, 3);
        } catch (QueryException $exception) {
            // Une erreur d’historique ne doit pas interrompre l’accès au compte.
            report($exception);
        }
    }

    public static function recent(User $user): Collection
    {
        return DB::table('user_logins')->where('user_id', $user->id)
            ->orderByDesc('logged_in_at')->orderByDesc('id')->limit(self::LIMIT)
            ->get(['id', 'logged_in_at', 'method']);
    }
}
