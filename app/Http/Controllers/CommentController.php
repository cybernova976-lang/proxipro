<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a new comment on an ad.
     */
    public function store(Request $request, Ad $ad)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $currentUser = Auth::user();

        // Vérifier la restriction de réponse de l'annonce
        if ($ad->user_id !== $currentUser->id) {
            $restriction = $ad->reply_restriction ?? 'everyone';

            if ($restriction === 'pro_only') {
                $isPro = $currentUser->isProfessionnel();
                if (! $isPro) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cette annonce est réservée aux professionnels.',
                            'restriction' => 'pro_only',
                        ], 403);
                    }

                    return back()->with('error', 'Cette annonce est réservée aux professionnels.');
                }
            }

            if ($restriction === 'verified_only') {
                if (! $currentUser->is_verified) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cette annonce est réservée aux profils vérifiés.',
                            'restriction' => 'verified_only',
                        ], 403);
                    }

                    return back()->with('error', 'Cette annonce est réservée aux profils vérifiés.');
                }
            }
        }

        $comment = Comment::create([
            'ad_id' => $ad->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        // Déduire 2 points pour commenter
        try {
            if (Auth::user()->available_points >= 2) {
                Auth::user()->spendPoints(2, 'comment', "Commentaire sur l'annonce: ".$ad->title);
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur spendPoints: '.$e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->avatar ? storage_url($comment->user->avatar) : null,
                        'initial' => strtoupper(substr($comment->user->name, 0, 1)),
                    ],
                ],
                'message' => 'Commentaire ajouté avec succès ! (+2 points)',
            ]);
        }

        return back()->with('success', 'Commentaire ajouté avec succès !');
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment)
    {
        // Check if the user is the owner of the comment or an admin
        if (Auth::id() !== $comment->user_id && (! Auth::user() || Auth::user()->role !== 'admin')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
            }
            abort(403);
        }

        try {
            $comment->delete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Commentaire supprimé avec succès.',
                ]);
            }

            return back()->with('success', 'Commentaire supprimé avec succès.');
        } catch (\Exception $e) {
            \Log::error('Erreur suppression commentaire: '.$e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression du commentaire.',
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression du commentaire.');
        }
    }

    /**
     * Get comments for an ad (AJAX).
     */
    public function index(Ad $ad)
    {
        $comments = $ad->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->avatar ? storage_url($comment->user->avatar) : null,
                        'initial' => strtoupper(substr($comment->user->name, 0, 1)),
                    ],
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'created_at' => $reply->created_at->diffForHumans(),
                            'user' => [
                                'id' => $reply->user->id,
                                'name' => $reply->user->name,
                                'avatar' => $reply->user->avatar ? storage_url($reply->user->avatar) : null,
                                'initial' => strtoupper(substr($reply->user->name, 0, 1)),
                            ],
                        ];
                    }),
                ];
            }),
            'total' => $ad->comments()->count(),
        ]);
    }
}
