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

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Liste des conversations
    public function index()
    {
        $user = Auth::user();
        
        $conversations = Conversation::with(['user1', 'user2', 'lastMessage.sender'])
            ->where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('messages.index', compact('conversations'));
    }

    // Voir une conversation
    public function show($id)
    {
        $user = Auth::user();
        $conversation = Conversation::with(['user1', 'user2'])->findOrFail($id);
        
        // Vérifier l'accès
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        // Marquer les messages comme lus
        $conversation->markAsRead();

        // Récupérer les messages
        $messages = Message::with('sender')
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Récupérer toutes les conversations pour la sidebar
        $conversations = Conversation::with(['user1', 'user2', 'lastMessage.sender'])
            ->where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->get();

        return view('messages.show', compact('conversation', 'messages', 'conversations'));
    }

    // Envoyer un message
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:3000'
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Vérifier les permissions
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Vérifier si la conversation est bloquée
        if (!$conversation->canSendMessage($user->id)) {
            return response()->json(['error' => 'Cette conversation est bloquée'], 403);
        }

        // Créer le message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->content
        ]);

        // Notifier le destinataire par email et notification interne
        $recipientId = $conversation->user1_id === $user->id ? $conversation->user2_id : $conversation->user1_id;
        $recipient = User::find($recipientId);
        if ($recipient) {
            $recipient->notify(new NewMessageNotification($message, $conversation, $user));
        }

        return response()->json([
            'success' => true,
            'message' => $message->load('sender')
        ]);
    }

    // Démarrer une nouvelle conversation
    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'ad_id' => 'nullable|exists:ads,id',
            'message' => 'required|string|max:3000'
        ]);

        $currentUser = Auth::user();
        $otherUser = User::findOrFail($request->recipient_id);

        // Empêcher de démarrer une conversation avec soi-même
        if ($currentUser->id == $otherUser->id) {
            return back()->with('error', 'Vous ne pouvez pas démarrer une conversation avec vous-même');
        }

        // Vérifier la restriction de réponse si liée à une annonce
        if ($request->ad_id) {
            $ad = Ad::find($request->ad_id);
            if ($ad && $ad->user_id !== $currentUser->id) {
                $restriction = $ad->reply_restriction ?? 'everyone';

                if ($restriction === 'pro_only') {
                    $isPro = $currentUser->user_type === 'professionnel'
                          || $currentUser->hasActiveProSubscription()
                          || $currentUser->hasCompletedProOnboarding();
                    if (!$isPro) {
                        return back()->with('error', 'Cette annonce est réservée aux professionnels. Seuls les comptes Pro peuvent contacter l\'annonceur.');
                    }
                }

                if ($restriction === 'verified_only') {
                    if (!$currentUser->is_verified) {
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
                $request->ad_id ? "Annonce #" . $request->ad_id : null
            );

            // Envoyer le premier message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $currentUser->id,
                'content' => $request->message
            ]);

            // Notifier le destinataire par email et notification interne
            $otherUser->notify(new NewMessageNotification($message, $conversation, $currentUser));

            DB::commit();

            return redirect()->route('messages.show', $conversation->id)
                           ->with('success', 'Message envoyé avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'envoi du message: ' . $e->getMessage());
        }
    }

    // Bloquer une conversation
    public function block($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        $conversation->update([
            'is_blocked' => true,
            'blocked_by' => $user->id
        ]);

        return response()->json(['success' => true]);
    }

    // Débloquer une conversation
    public function unblock($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            abort(403);
        }

        $conversation->update([
            'is_blocked' => false,
            'blocked_by' => null
        ]);

        return response()->json(['success' => true]);
    }

    // Supprimer une conversation
    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);
        $user = Auth::user();

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
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

        return response()->json(['success' => true]);
    }

    // Poll for new messages (real-time like)
    public function poll(Request $request, $id)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);
        
        // Vérifier l'accès
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $lastId = $request->input('last_id', 0);
        
        // Récupérer les nouveaux messages
        $messages = Message::where('conversation_id', $id)
            ->where('id', '>', $lastId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer les messages de l'autre utilisateur comme lus
        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    // Modifier un message (<= 5 minutes)
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:3000'
        ]);

        $user = Auth::user();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        if ($message->created_at->lte(now()->subMinutes(5))) {
            return response()->json(['error' => 'Délai dépassé'], 403);
        }

        $message->update([
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
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
