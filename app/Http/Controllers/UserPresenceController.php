<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\UserPresence;
use Illuminate\Http\Request;

class UserPresenceController extends Controller
{
    public function __invoke(Request $request, UserPresence $presence)
    {
        $data = $request->validate([
            'tab_id' => 'required|uuid',
            'sequence' => 'required|integer|min:1|max:2147483647',
            'active' => 'required|boolean',
            'conversation_ids' => 'sometimes|array|max:30',
            'conversation_ids.*' => 'integer|min:1|distinct',
        ]);
        $user = $request->user();
        abort_unless($user->is_active, 403);
        $presence->heartbeat($user, $request->session()->getId(), $data['tab_id'], (int) $data['sequence'], $request->boolean('active'));
        // La présence n'est consultable que pour ses propres conversations.
        $conversations = Conversation::withoutEagerLoads()->forParticipant($user->id)
            ->whereIn('id', $data['conversation_ids'] ?? [])->get();

        return response()->json([
            'presences' => (object) $presence->forConversations($conversations, $user->id),
        ])->header('Cache-Control', 'private, no-store');
    }
}
