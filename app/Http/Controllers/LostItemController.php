<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LostItem;

class LostItemController extends Controller
{
    /**
     * Afficher la liste des objets perdus/trouvés
     */
    public function index(Request $request)
    {
        $query = LostItem::with('user')->latest();

        // Filtrer par type (perdu/trouvé)
        if ($request->has('type') && in_array($request->type, ['lost', 'found'])) {
            $query->where('type', $request->type);
        }

        // Filtrer par catégorie
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Recherche par mot-clé
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
            });
        }

        $items = $query->paginate(12);

        $categories = [
            'documents' => 'Documents',
            'electronics' => 'Électronique',
            'jewelry' => 'Bijoux',
            'keys' => 'Clés',
            'bags' => 'Sacs',
            'clothing' => 'Vêtements',
            'pets' => 'Animaux',
            'other' => 'Autres',
        ];

        return view('lost-items.index', compact('items', 'categories'));
    }

    /**
     * Formulaire de création d'un objet perdu/trouvé
     */
    public function create()
    {
        $categories = [
            'documents' => 'Documents',
            'electronics' => 'Électronique',
            'jewelry' => 'Bijoux',
            'keys' => 'Clés',
            'bags' => 'Sacs',
            'clothing' => 'Vêtements',
            'pets' => 'Animaux',
            'other' => 'Autres',
        ];

        return view('lost-items.create', compact('categories'));
    }

    /**
     * Enregistrer un nouvel objet perdu/trouvé
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:lost,found',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'category' => 'required|string',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'contact_phone' => 'nullable|string|max:20',
            'reward' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('lost-items', config('filesystems.default', config('filesystems.default', 'public')));
            }
        }

        LostItem::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'location' => $request->location,
            'date' => $request->date,
            'contact_phone' => $request->contact_phone,
            'reward' => $request->reward,
            'images' => json_encode($images),
            'status' => 'active',
        ]);

        return redirect()->route('lost-items.index')
            ->with('success', 'Votre annonce a été publiée avec succès !');
    }

    /**
     * Afficher un objet perdu/trouvé
     */
    public function show($id)
    {
        $item = LostItem::with('user')->findOrFail($id);
        
        // Incrémenter les vues
        $item->increment('views');

        return view('lost-items.show', compact('item'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit($id)
    {
        $item = LostItem::findOrFail($id);
        
        // Vérifier que l'utilisateur est le propriétaire
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $categories = [
            'documents' => 'Documents',
            'electronics' => 'Électronique',
            'jewelry' => 'Bijoux',
            'keys' => 'Clés',
            'bags' => 'Sacs',
            'clothing' => 'Vêtements',
            'pets' => 'Animaux',
            'other' => 'Autres',
        ];

        return view('lost-items.edit', compact('item', 'categories'));
    }

    /**
     * Mettre à jour un objet
     */
    public function update(Request $request, $id)
    {
        $item = LostItem::findOrFail($id);
        
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'category' => 'required|string',
            'location' => 'required|string|max:255',
            'status' => 'required|in:active,resolved,closed',
        ]);

        $item->update($request->only(['title', 'description', 'category', 'location', 'status']));

        return redirect()->route('lost-items.show', $item->id)
            ->with('success', 'Annonce mise à jour avec succès !');
    }

    /**
     * Supprimer un objet
     */
    public function destroy($id)
    {
        $item = LostItem::findOrFail($id);
        
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('lost-items.index')
            ->with('success', 'Annonce supprimée avec succès !');
    }
}
