<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserPresence
{
    public const HEARTBEAT_SECONDS = 25;

    public const LEASE_SECONDS = 75;

    public function heartbeat(User $user, string $sessionId, string $tabId, int $sequence, bool $active): void
    {
        $key = ['user_id' => $user->id, 'session_key' => hash('sha256', $sessionId), 'tab_id' => $tabId];
        if (! $user->is_active) {
            $this->forgetSession($user->id, $sessionId);

            return;
        }

        // L'insertion et la mise à jour conditionnelle sont atomiques. Un signal
        // actif retardé ne peut pas annuler un signal de fermeture plus récent.
        DB::table('user_presence_leases')->insertOrIgnore($key + ['sequence' => 0, 'expires_at' => now()]);
        DB::table('user_presence_leases')->where($key)->where('sequence', '<', $sequence)->update([
            'sequence' => $sequence,
            'expires_at' => $active ? now()->addSeconds(self::LEASE_SECONDS) : now(),
        ]);
    }

    public function forgetSession(int $userId, string $sessionId): void
    {
        DB::table('user_presence_leases')->where('user_id', $userId)->where('session_key', hash('sha256', $sessionId))->delete();
    }

    public function forConversations(Collection $conversations, int $viewerId): array
    {
        $conversations = $conversations->filter(fn ($conversation) => in_array($viewerId, [(int) $conversation->user1_id, (int) $conversation->user2_id], true));
        $otherIds = $conversations->map(fn ($conversation) => $conversation->user1_id == $viewerId ? $conversation->user2_id : $conversation->user1_id)->unique();
        $activeIds = User::whereIn('id', $otherIds)->where('is_active', true)->pluck('id');
        $leases = DB::table('user_presence_leases')->whereIn('user_id', $activeIds)->where('expires_at', '>', now())
            ->selectRaw('user_id, MAX(expires_at) AS expires_at')->groupBy('user_id')->pluck('expires_at', 'user_id');

        return $conversations->mapWithKeys(function ($conversation) use ($viewerId, $activeIds, $leases) {
            $otherId = $conversation->user1_id == $viewerId ? $conversation->user2_id : $conversation->user1_id;
            if ($conversation->is_blocked) {
                $state = 'hidden';
            } elseif (! $activeIds->contains($otherId)) {
                $state = 'unavailable';
            } else {
                $state = $leases->has($otherId) ? 'online' : 'offline';
            }

            $validFor = $state === 'online' ? max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($leases[$otherId]), false)) : self::LEASE_SECONDS;

            return [$conversation->id => [
                'state' => $state,
                'label' => match ($state) {
                    'online' => 'En ligne',
                    'offline' => 'Hors ligne',
                    'hidden' => 'Présence masquée',
                    default => 'Compte indisponible',
                },
                'valid_for_seconds' => $validFor,
                'checked_at' => now()->getTimestampMs(),
            ]];
        })->all();
    }
}
