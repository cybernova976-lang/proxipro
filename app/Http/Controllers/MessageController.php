<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Liste des conversations
    private function inbox(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'filter' => 'nullable|in:all,unread']);

        return Conversation::forParticipant($request->user()->id)
            ->with(['user1:id,name,avatar', 'user2:id,name,avatar', 'lastMessage'])
            ->withCount(['messages as unread_messages_count' => fn ($q) => $q
                ->where('sender_id', '!=', $request->user()->id)->where('is_read', false)])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.mb_strtolower(trim((string) $request->input('q'))).'%';
                $q->where(fn ($search) => $search->whereRaw('LOWER(subject) LIKE ?', [$term])
                    ->orWhereHas('user1', fn ($u) => $u->where('id', '!=', $request->user()->id)->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('user2', fn ($u) => $u->where('id', '!=', $request->user()->id)->whereRaw('LOWER(name) LIKE ?', [$term])));
            })
            ->when($request->input('filter') === 'unread', fn ($q) => $q->whereHas('messages', fn ($m) => $m
                ->where('sender_id', '!=', $request->user()->id)->where('is_read', false)))
            ->orderByDesc('last_message_at')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    private function payload(Message $message): array
    {
        return $message->only(['id', 'conversation_id', 'sender_id', 'content', 'is_read', 'created_at', 'read_at', 'edited_at']);
    }

    private function notifyRecipient(Message $message, Conversation $conversation, User $sender): void
    {
        $recipientId = $conversation->user1_id == $sender->id ? $conversation->user2_id : $conversation->user1_id;
        try {
            User::find($recipientId)?->notify(new NewMessageNotification($message, $conversation, $sender));
        } catch (\Throwable $error) {
            Log::error('Notification de message non distribuée', ['message_id' => $message->id, 'exception' => $error]);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $conversations = $this->inbox($request);
        $recipients = User::where('id', '!=', $user->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return response()->view('messages.index', compact('conversations', 'recipients'))->header('Cache-Control', 'private, no-store');
    }

    // Voir une conversation
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $conversation = Conversation::with(['user1', 'user2'])->findOrFail($id);

        // Vérifier l'accès
        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        // Marquer les messages comme lus
        $conversation->markAsRead();

        // Récupérer les messages
        $messages = Message::where('conversation_id', $id)->orderByDesc('id')->limit(51)->get();
        $hasOlder = $messages->count() > 50;
        $messages = $messages->take(50)->reverse()->values();

        // Récupérer toutes les conversations pour la sidebar
        $conversations = $this->inbox($request);

        return response()->view('messages.show', compact('conversation', 'messages', 'conversations', 'hasOlder'))->header('Cache-Control', 'private, no-store');
    }

    // Envoyer un message
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:3000',
            'client_token' => 'nullable|uuid',
        ]);

        $content = trim($request->content);
        if ($content === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['content' => 'Écrivez un message avant de l’envoyer.']);
        }

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Vérifier les permissions
        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Vérifier si la conversation est bloquée
        if (! $conversation->canSendMessage($user->id)) {
            return response()->json(['error' => 'Cette conversation est bloquée'], 403);
        }

        $created = false;
        $message = DB::transaction(function () use ($request, $conversation, $user, $content, &$created) {
            $locked = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            if ($request->filled('client_token')) {
                $existing = Message::where('sender_id', $user->id)->where('client_token', $request->client_token)->first();
                if ($existing) {
                    abort_unless($existing->conversation_id == $conversation->id, 409);

                    return $existing;
                }
            }
            abort_unless($locked->canSendMessage($user->id), 403, 'Cette conversation est bloquée.');
            abort_unless($locked->other_user?->is_active, 403, 'Ce destinataire n’est plus disponible.');
            $created = true;

            return Message::create(['conversation_id' => $locked->id, 'sender_id' => $user->id, 'content' => $content, 'client_token' => $request->client_token]);
        });
        if ($created) {
            $this->notifyRecipient($message, $conversation, $user);
        }

        return response()->json([
            'success' => true,
            'message' => $this->payload($message),
        ]);
    }

    // Démarrer une nouvelle conversation
    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'ad_id' => 'nullable|exists:ads,id',
            'message' => 'required|string|max:3000',
        ]);

        $currentUser = Auth::user();
        $otherUser = User::findOrFail($request->recipient_id);
        $content = trim($request->message);
        if ($content === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['message' => 'Écrivez un message avant de l’envoyer.']);
        }
        abort_unless($otherUser->is_active, 403);

        // Empêcher de démarrer une conversation avec soi-même
        if ($currentUser->id == $otherUser->id) {
            return back()->with('error', 'Vous ne pouvez pas démarrer une conversation avec vous-même');
        }

        // Vérifier la restriction de réponse si liée à une annonce
        if ($request->ad_id) {
            $ad = Ad::find($request->ad_id);
            abort_unless($ad && in_array($ad->user_id, [$currentUser->id, $otherUser->id]), 422);
            if ($ad && $ad->user_id !== $currentUser->id) {
                $restriction = $ad->reply_restriction ?? 'everyone';

                if ($restriction === 'pro_only') {
                    $isPro = $currentUser->isProfessionnel();
                    if (! $isPro) {
                        return back()->with('error', 'Cette annonce est réservée aux professionnels. Seuls les comptes Pro peuvent contacter l\'annonceur.');
                    }
                }

                if ($restriction === 'verified_only') {
                    if (! $currentUser->is_verified) {
                        return back()->with('error', 'Cette annonce est réservée aux profils vérifiés. Veuillez vérifier votre identité pour contacter l\'annonceur.');
                    }
                }
            }
        }

        DB::beginTransaction();

        try {
            // Créer ou récupérer la conversation
            $conversation = Conversation::getOrCreate(
                $currentUser->id,
                $otherUser->id,
                $request->ad_id ? 'Annonce #'.$request->ad_id : null
            );
            $conversation = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_unless($conversation->canSendMessage($currentUser->id), 403, 'Cette conversation est bloquée.');

            // Envoyer le premier message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $currentUser->id,
                'content' => $content,
            ]);

            // Notifier le destinataire par email et notification interne
            DB::commit();
            $this->notifyRecipient($message, $conversation, $currentUser);

            return redirect()->route('messages.show', $conversation->id)
                ->with('success', 'Message envoyé avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $e;
            }
            Log::error('Echec de creation d une conversation', [
                'user_id' => $currentUser->id,
                'recipient_id' => $otherUser->id,
                'exception' => $e,
            ]);

            return back()->with('error', 'Le message n a pas pu etre envoye. Veuillez reessayer.');
        }
    }

    // Bloquer une conversation
    public function block($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        DB::transaction(function () use ($conversation, $user) {
            $locked = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_blocked && $locked->blocked_by != $user->id, 403);
            $locked->update(['is_blocked' => true, 'blocked_by' => $user->id]);
        });

        return response()->json(['success' => true]);
    }

    // Débloquer une conversation
    public function unblock($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        DB::transaction(function () use ($conversation, $user) {
            $locked = Conversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->blocked_by == $user->id, 403);
            $locked->update(['is_blocked' => false, 'blocked_by' => null]);
        });

        return response()->json(['success' => true]);
    }

    // Supprimer une conversation
    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        $conversation->delete();

        return response()->json(['success' => true]);
    }

    // Marquer tous les messages comme lus
    public function markAllAsRead()
    {
        $user = Auth::user();

        $conversations = Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->get();

        foreach ($conversations as $conversation) {
            $conversation->markAsRead();
        }

        return request()->expectsJson() ? response()->json(['success' => true]) : back()->with('success', 'Tous vos messages sont marqués comme lus.');
    }

    // Poll for new messages (real-time like)
    public function poll(Request $request, $id)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        // Vérifier l'accès
        if (! in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $request->validate(['last_id' => 'nullable|integer|min:0', 'before_id' => 'nullable|integer|min:1', 'visible_ids' => 'nullable|array|max:200', 'visible_ids.*' => 'integer|min:1']);
        $conversation->markAsRead($user->id);
        if ($request->filled('before_id')) {
            $older = Message::where('conversation_id', $id)->where('id', '<', $request->integer('before_id'))->orderByDesc('id')->limit(51)->get();

            return response()->json(['success' => true, 'messages' => $older->take(50)->reverse()->values()->map(fn ($m) => $this->payload($m)), 'has_more' => $older->count() > 50])->header('Cache-Control', 'private, no-store');
        }
        $lastId = $request->integer('last_id');

        // Récupérer les nouveaux messages
        $messages = Message::where('conversation_id', $id)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        // Marquer les messages de l'autre utilisateur comme lus
        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $visible = Message::where('conversation_id', $id)->whereIn('id', $request->input('visible_ids', []))->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn ($m) => $this->payload($m)),
            'visible_messages' => $visible->map(fn ($m) => $this->payload($m)),
            'visible_ids' => $visible->pluck('id'),
            'is_blocked' => $conversation->is_blocked,
            'blocked_by' => $conversation->blocked_by,
        ])->header('Cache-Control', 'private, no-store');
    }

    // Modifier un message (<= 5 minutes)
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:3000',
        ]);

        $user = Auth::user();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        if ($message->created_at->lte(now()->subMinutes(5))) {
            return response()->json(['error' => 'Délai dépassé'], 403);
        }

        abort_unless($message->conversation->canSendMessage($user->id), 403, 'Cette conversation est bloquée.');
        $content = trim($request->content);
        if ($content === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['content' => 'Le message ne peut pas être vide.']);
        }

        $message->update([
            'content' => $content,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->payload($message),
        ]);
    }

    // Supprimer un message (<= 5 minutes)
    public function deleteMessage($id)
    {
        $user = Auth::user();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        if ($message->created_at->lte(now()->subMinutes(5))) {
            return response()->json(['error' => 'Délai dépassé'], 403);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }
}
