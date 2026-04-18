<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomePageController extends Controller
{
    public function index()
    {
        // Logged-in users go directly to the feed
        if (Auth::check()) {
            return redirect()->route('feed');
        }

        // Statistiques réelles
        try {
            $totalAds = Ad::where('status', 'active')->count();
        } catch (\Exception $e) {
            $totalAds = 0;
        }

        try {
            $totalPros = User::where(function ($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere('is_service_provider', true);
            })->count();
        } catch (\Exception $e) {
            $totalPros = 0;
        }

        try {
            $totalUsers = User::count();
        } catch (\Exception $e) {
            $totalUsers = 0;
        }

        // Mega menu categories with subcategories + counts
        $categoriesWithSubs = [
            'Bricolage & Travaux' => [
                'icon' => 'fas fa-tools',
                'color' => '#EAB308',
                'subs' => [
                    ['name' => 'Plomberie', 'icon' => 'fas fa-faucet'],
                    ['name' => 'Électricité', 'icon' => 'fas fa-bolt'],
                    ['name' => 'Peinture', 'icon' => 'fas fa-paint-roller'],
                    ['name' => 'Menuiserie', 'icon' => 'fas fa-hammer'],
                    ['name' => 'Carrelage', 'icon' => 'fas fa-border-all'],
                    ['name' => 'Maçonnerie', 'icon' => 'fas fa-cubes'],
                    ['name' => 'Serrurerie', 'icon' => 'fas fa-key'],
                    ['name' => 'Vitrier', 'icon' => 'fas fa-window-maximize'],
                    ['name' => 'Plâtrier', 'icon' => 'fas fa-trowel'],
                    ['name' => 'Climatisation', 'icon' => 'fas fa-fan'],
                    ['name' => 'Panneaux solaires', 'icon' => 'fas fa-solar-panel'],
                    ['name' => 'Manitas à domicile', 'icon' => 'fas fa-wrench'],
                ]
            ],
            'Jardinage' => [
                'icon' => 'fas fa-leaf',
                'color' => '#22C55E',
                'subs' => [
                    ['name' => 'Tonte de pelouse', 'icon' => 'fas fa-fan'],
                    ['name' => 'Taille de haies', 'icon' => 'fas fa-cut'],
                    ['name' => 'Élagage', 'icon' => 'fas fa-tree'],
                    ['name' => 'Entretien de jardin', 'icon' => 'fas fa-seedling'],
                    ['name' => 'Plantation', 'icon' => 'fas fa-spa'],
                    ['name' => 'Arrosage automatique', 'icon' => 'fas fa-tint'],
                    ['name' => 'Paysagiste', 'icon' => 'fas fa-mountain'],
                    ['name' => 'Entretien piscines', 'icon' => 'fas fa-swimming-pool'],
                ]
            ],
            'Nettoyage & Foyer' => [
                'icon' => 'fas fa-broom',
                'color' => '#06B6D4',
                'subs' => [
                    ['name' => 'Nettoyage à domicile', 'icon' => 'fas fa-home'],
                    ['name' => 'Nettoyage bureaux', 'icon' => 'fas fa-building'],
                    ['name' => 'Nettoyage fin de chantier', 'icon' => 'fas fa-hard-hat'],
                    ['name' => 'Nettoyage voitures', 'icon' => 'fas fa-car'],
                    ['name' => 'Repassage à domicile', 'icon' => 'fas fa-tshirt'],
                    ['name' => 'Couturière', 'icon' => 'fas fa-cut'],
                ]
            ],
            'Déménagement & Transport' => [
                'icon' => 'fas fa-truck-moving',
                'color' => '#8B5CF6',
                'subs' => [
                    ['name' => 'Entreprises de déménagement', 'icon' => 'fas fa-dolly'],
                    ['name' => 'Petits transports', 'icon' => 'fas fa-truck'],
                    ['name' => 'Transport Ikea', 'icon' => 'fas fa-box'],
                    ['name' => 'Location camionnette avec chauffeur', 'icon' => 'fas fa-shuttle-van'],
                    ['name' => 'Taxi', 'icon' => 'fas fa-taxi'],
                ]
            ],
            'Cours de langues' => [
                'icon' => 'fas fa-language',
                'color' => '#3B82F6',
                'subs' => [
                    ['name' => 'Anglais', 'icon' => 'fas fa-flag-usa'],
                    ['name' => 'Français', 'icon' => 'fas fa-flag'],
                    ['name' => 'Allemand', 'icon' => 'fas fa-globe-europe'],
                    ['name' => 'Espagnol', 'icon' => 'fas fa-globe-americas'],
                    ['name' => 'Italien', 'icon' => 'fas fa-pizza-slice'],
                    ['name' => 'Japonais', 'icon' => 'fas fa-torii-gate'],
                    ['name' => 'Arabe', 'icon' => 'fas fa-mosque'],
                    ['name' => 'Chinois', 'icon' => 'fas fa-yin-yang'],
                ]
            ],
            'Cours particuliers' => [
                'icon' => 'fas fa-graduation-cap',
                'color' => '#0F766E',
                'subs' => [
                    ['name' => 'Soutien scolaire', 'icon' => 'fas fa-book-reader'],
                    ['name' => 'Mathématiques', 'icon' => 'fas fa-calculator'],
                    ['name' => 'Physique', 'icon' => 'fas fa-atom'],
                    ['name' => 'Cours de musique', 'icon' => 'fas fa-music'],
                    ['name' => 'Piano', 'icon' => 'fas fa-piano'],
                    ['name' => 'Guitare', 'icon' => 'fas fa-guitar'],
                    ['name' => 'Cours de dessin', 'icon' => 'fas fa-pencil-alt'],
                    ['name' => 'Cours de danse', 'icon' => 'fas fa-user-friends'],
                ]
            ],
            'Aide à domicile' => [
                'icon' => 'fas fa-hands-helping',
                'color' => '#EF4444',
                'subs' => [
                    ['name' => 'Garde personnes âgées', 'icon' => 'fas fa-user-nurse'],
                    ['name' => 'Baby-sitting', 'icon' => 'fas fa-baby'],
                    ['name' => 'Nounou', 'icon' => 'fas fa-baby-carriage'],
                    ['name' => 'Sortie d\'école', 'icon' => 'fas fa-school'],
                    ['name' => 'Aide aux devoirs', 'icon' => 'fas fa-book'],
                    ['name' => 'Courses', 'icon' => 'fas fa-shopping-bag'],
                    ['name' => 'Accompagnement', 'icon' => 'fas fa-walking'],
                    ['name' => 'Préparation repas', 'icon' => 'fas fa-utensils'],
                ]
            ],
            'Animaux' => [
                'icon' => 'fas fa-paw',
                'color' => '#EC4899',
                'subs' => [
                    ['name' => 'Garde animaux', 'icon' => 'fas fa-house-user'],
                    ['name' => 'Promenade chiens', 'icon' => 'fas fa-dog'],
                    ['name' => 'Toilettage chiens', 'icon' => 'fas fa-shower'],
                    ['name' => 'Toilettage chats', 'icon' => 'fas fa-cat'],
                    ['name' => 'Dressage', 'icon' => 'fas fa-bone'],
                    ['name' => 'Pension canine', 'icon' => 'fas fa-home'],
                    ['name' => 'Vétérinaire à domicile', 'icon' => 'fas fa-stethoscope'],
                ]
            ],
            'Beauté & Bien-être' => [
                'icon' => 'fas fa-spa',
                'color' => '#F472B6',
                'subs' => [
                    ['name' => 'Coiffure à domicile', 'icon' => 'fas fa-cut'],
                    ['name' => 'Barbier', 'icon' => 'fas fa-user'],
                    ['name' => 'Maquillage professionnel', 'icon' => 'fas fa-palette'],
                    ['name' => 'Manucure', 'icon' => 'fas fa-hand-sparkles'],
                    ['name' => 'Massage à domicile', 'icon' => 'fas fa-hands'],
                    ['name' => 'Ostéopathie', 'icon' => 'fas fa-bone'],
                    ['name' => 'Tatoueur', 'icon' => 'fas fa-pen-nib'],
                ]
            ],
            'Événements' => [
                'icon' => 'fas fa-calendar-star',
                'color' => '#A855F7',
                'subs' => [
                    ['name' => 'DJ', 'icon' => 'fas fa-headphones'],
                    ['name' => 'Magicien', 'icon' => 'fas fa-hat-wizard'],
                    ['name' => 'Photographe mariage', 'icon' => 'fas fa-camera'],
                    ['name' => 'Traiteur', 'icon' => 'fas fa-utensils'],
                    ['name' => 'Wedding planner', 'icon' => 'fas fa-ring'],
                    ['name' => 'Décoration événements', 'icon' => 'fas fa-gift'],
                    ['name' => 'Musiciens mariage', 'icon' => 'fas fa-music'],
                ]
            ],
            'Sports & Fitness' => [
                'icon' => 'fas fa-dumbbell',
                'color' => '#14B8A6',
                'subs' => [
                    ['name' => 'Coach personnel', 'icon' => 'fas fa-running'],
                    ['name' => 'Yoga', 'icon' => 'fas fa-pray'],
                    ['name' => 'Pilates', 'icon' => 'fas fa-spa'],
                    ['name' => 'Natation', 'icon' => 'fas fa-swimmer'],
                    ['name' => 'Tennis', 'icon' => 'fas fa-table-tennis'],
                    ['name' => 'Boxe', 'icon' => 'fas fa-fist-raised'],
                    ['name' => 'Arts martiaux', 'icon' => 'fas fa-user-ninja'],
                ]
            ],
            'Informatique' => [
                'icon' => 'fas fa-laptop-code',
                'color' => '#6366F1',
                'subs' => [
                    ['name' => 'Dépannage PC', 'icon' => 'fas fa-desktop'],
                    ['name' => 'Informaticien à domicile', 'icon' => 'fas fa-laptop'],
                    ['name' => 'Réparation ordinateurs', 'icon' => 'fas fa-tools'],
                    ['name' => 'Création site web', 'icon' => 'fas fa-globe'],
                    ['name' => 'Formation informatique', 'icon' => 'fas fa-chalkboard-teacher'],
                ]
            ],
            'Avocats & Conseil' => [
                'icon' => 'fas fa-balance-scale',
                'color' => '#64748B',
                'subs' => [
                    ['name' => 'Avocats', 'icon' => 'fas fa-gavel'],
                    ['name' => 'Avocats divorce', 'icon' => 'fas fa-heart-broken'],
                    ['name' => 'Avocats travail', 'icon' => 'fas fa-briefcase'],
                    ['name' => 'Notaire', 'icon' => 'fas fa-stamp'],
                    ['name' => 'Conseiller fiscal', 'icon' => 'fas fa-file-invoice-dollar'],
                ]
            ],
            'Santé & Médecine' => [
                'icon' => 'fas fa-heartbeat',
                'color' => '#EF4444',
                'subs' => [
                    ['name' => 'Kinésithérapeute', 'icon' => 'fas fa-user-md'],
                    ['name' => 'Infirmière à domicile', 'icon' => 'fas fa-syringe'],
                    ['name' => 'Psychologue', 'icon' => 'fas fa-brain'],
                    ['name' => 'Nutritionniste', 'icon' => 'fas fa-apple-alt'],
                    ['name' => 'Podologue', 'icon' => 'fas fa-shoe-prints'],
                    ['name' => 'Orthophoniste', 'icon' => 'fas fa-comments'],
                ]
            ],
            'Services Pro' => [
                'icon' => 'fas fa-briefcase',
                'color' => '#0EA5E9',
                'subs' => [
                    ['name' => 'Photographe', 'icon' => 'fas fa-camera-retro'],
                    ['name' => 'Détective privé', 'icon' => 'fas fa-user-secret'],
                    ['name' => 'Mécanicien à domicile', 'icon' => 'fas fa-car'],
                    ['name' => 'Traducteur', 'icon' => 'fas fa-language'],
                ]
            ],
            'Covoiturage' => [
                'icon' => 'fas fa-car-side',
                'color' => '#10B981',
                'subs' => [
                    ['name' => 'Trajet quotidien', 'icon' => 'fas fa-route'],
                    ['name' => 'Longue distance', 'icon' => 'fas fa-road'],
                    ['name' => 'Aéroport', 'icon' => 'fas fa-plane-departure'],
                    ['name' => 'Événements', 'icon' => 'fas fa-calendar-alt'],
                ]
            ],
            'Vente' => [
                'icon' => 'fas fa-shopping-cart',
                'color' => '#F59E0B',
                'subs' => [
                    ['name' => 'Électroménager', 'icon' => 'fas fa-blender'],
                    ['name' => 'Meubles', 'icon' => 'fas fa-couch'],
                    ['name' => 'Vêtements', 'icon' => 'fas fa-tshirt'],
                    ['name' => 'High-tech', 'icon' => 'fas fa-mobile-alt'],
                    ['name' => 'Véhicules', 'icon' => 'fas fa-car'],
                    ['name' => 'Immobilier', 'icon' => 'fas fa-building'],
                ]
            ],
            'Emploi' => [
                'icon' => 'fas fa-id-card',
                'color' => '#3B82F6',
                'subs' => [
                    ['name' => 'CDI', 'icon' => 'fas fa-file-contract'],
                    ['name' => 'CDD', 'icon' => 'fas fa-clock'],
                    ['name' => 'Intérim', 'icon' => 'fas fa-hourglass-half'],
                    ['name' => 'Stage', 'icon' => 'fas fa-graduation-cap'],
                    ['name' => 'Freelance', 'icon' => 'fas fa-laptop-house'],
                    ['name' => 'Temps partiel', 'icon' => 'fas fa-user-clock'],
                ]
            ],
            'Location' => [
                'icon' => 'fas fa-key',
                'color' => '#8B5CF6',
                'subs' => [
                    ['name' => 'Appartements', 'icon' => 'fas fa-building'],
                    ['name' => 'Maisons', 'icon' => 'fas fa-home'],
                    ['name' => 'Voitures', 'icon' => 'fas fa-car'],
                    ['name' => 'Utilitaires', 'icon' => 'fas fa-truck'],
                    ['name' => 'Vélos & Trottinettes', 'icon' => 'fas fa-bicycle'],
                    ['name' => 'Matériel de bricolage', 'icon' => 'fas fa-tools'],
                    ['name' => 'Matériel de jardinage', 'icon' => 'fas fa-leaf'],
                    ['name' => 'Équipement photo/vidéo', 'icon' => 'fas fa-camera'],
                    ['name' => 'Matériel de sono', 'icon' => 'fas fa-volume-up'],
                    ['name' => 'Matériel événementiel', 'icon' => 'fas fa-tent'],
                    ['name' => 'Mobilier', 'icon' => 'fas fa-couch'],
                    ['name' => 'Équipement sportif', 'icon' => 'fas fa-football-ball'],
                    ['name' => 'Électroménager', 'icon' => 'fas fa-blender'],
                    ['name' => 'Vêtements & Costumes', 'icon' => 'fas fa-tshirt'],
                    ['name' => 'Jeux & Consoles', 'icon' => 'fas fa-gamepad'],
                ]
            ],
            'Perdu/disparu' => [
                'icon' => 'fas fa-search-location',
                'color' => '#F97316',
                'subs' => [
                    ['name' => 'Téléphones & Tablettes', 'icon' => 'fas fa-mobile-alt'],
                    ['name' => 'Portefeuilles & Papiers', 'icon' => 'fas fa-wallet'],
                    ['name' => 'Clés', 'icon' => 'fas fa-key'],
                    ['name' => 'Bijoux & Montres', 'icon' => 'fas fa-gem'],
                    ['name' => 'Sacs & Bagages', 'icon' => 'fas fa-suitcase'],
                    ['name' => 'Animaux perdus', 'icon' => 'fas fa-paw'],
                    ['name' => 'Personnes disparues', 'icon' => 'fas fa-user-slash'],
                    ['name' => 'Véhicules volés', 'icon' => 'fas fa-car-crash'],
                    ['name' => 'Électronique', 'icon' => 'fas fa-laptop'],
                    ['name' => 'Vêtements & Accessoires', 'icon' => 'fas fa-tshirt'],
                    ['name' => 'Lunettes', 'icon' => 'fas fa-glasses'],
                    ['name' => 'Autres objets', 'icon' => 'fas fa-box'],
                ]
            ],
        ];

        try {
            foreach ($categoriesWithSubs as &$category) {
                $total = 0;
                foreach ($category['subs'] as &$sub) {
                    $subCount = Ad::where('category', $sub['name'])
                        ->where('status', 'active')
                        ->count();
                    $sub['count'] = $subCount;
                    $total += $subCount;
                }
                $category['total'] = $total;
            }
            unset($category, $sub);
        } catch (\Exception $e) {
            foreach ($categoriesWithSubs as &$category) {
                $category['total'] = 0;
                foreach ($category['subs'] as &$sub) {
                    $sub['count'] = 0;
                }
            }
            unset($category, $sub);
        }

        // Dernieres annonces (pour une eventuelle section)
        try {
            $latestAds = Ad::where('status', 'active')
                ->latest()
                ->take(6)
                ->get();
        } catch (\Exception $e) {
            $latestAds = collect();
        }

        // Prestataires mis en avant (avec abonnement Pro ou les plus actifs)
        try {
            $featuredPros = User::where(function ($q) {
                    $q->where('user_type', 'professionnel')
                      ->orWhere('is_service_provider', true);
                })
                ->whereNotNull('profession')
                ->withCount(['ads as active_ads_count' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->orderByRaw("CASE WHEN stripe_id IS NOT NULL THEN 0 ELSE 1 END")
                ->orderByDesc('active_ads_count')
                ->take(6)
                ->get();
        } catch (\Exception $e) {
            $featuredPros = collect();
        }

        return view('pages.home', compact('totalAds', 'totalPros', 'totalUsers', 'latestAds', 'featuredPros', 'categoriesWithSubs'));
    }
}
