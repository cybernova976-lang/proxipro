<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\User;
use App\Models\Message;
use App\Models\Review;
use App\Models\Conversation;
use App\Models\PointTransaction;
use App\Models\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-data {--force : Supprimer définitivement les utilisateurs soft-deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les données orphelines (annonces, messages, etc.) des utilisateurs supprimés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Début du nettoyage des données orphelines...');
        $this->newLine();

        // Email de l'admin à préserver
        $adminEmail = 'hardali.soudj@gmail.com';

        // 1. Afficher les utilisateurs soft-deleted
        $softDeletedUsers = User::onlyTrashed()->where('email', '!=', $adminEmail)->get();
        
        if ($softDeletedUsers->count() > 0) {
            $this->warn("📋 Utilisateurs soft-deleted trouvés: {$softDeletedUsers->count()}");
            
            foreach ($softDeletedUsers as $user) {
                $this->line("  - ID: {$user->id} | Email: {$user->email} | Supprimé le: {$user->deleted_at}");
            }
            $this->newLine();

            if ($this->option('force')) {
                $this->info('🗑️ Suppression définitive des utilisateurs soft-deleted et leurs données...');
                
                foreach ($softDeletedUsers as $user) {
                    if ($user->hasKeptServiceOrders()) {
                        $this->line("  ⏭️ Utilisateur {$user->id} conservé : il porte des commandes à garder");

                        continue;
                    }

                    $this->deleteUserData($user);
                    $user->forceDelete();
                    $this->line("  ✅ Utilisateur {$user->email} supprimé définitivement");
                }
                $this->newLine();
            } else {
                $this->warn('⚠️ Utilisez --force pour supprimer définitivement ces utilisateurs');
                $this->newLine();
            }
        } else {
            $this->info('✅ Aucun utilisateur soft-deleted trouvé');
            $this->newLine();
        }

        // 2. Nettoyer les annonces orphelines (user_id inexistant)
        $orphanedAds = Ad::whereNotIn('user_id', User::withTrashed()->pluck('id'))->get();
        
        if ($orphanedAds->count() > 0) {
            $this->warn("📋 Annonces orphelines trouvées: {$orphanedAds->count()}");
            Ad::whereNotIn('user_id', User::withTrashed()->pluck('id'))->delete();
            $this->info("  ✅ {$orphanedAds->count()} annonces orphelines supprimées");
        } else {
            $this->info('✅ Aucune annonce orpheline trouvée');
        }
        $this->newLine();

        // 3. Nettoyer les messages orphelins
        $orphanedMessages = Message::whereNotIn('sender_id', User::withTrashed()->pluck('id'))->count();
        if ($orphanedMessages > 0) {
            Message::whereNotIn('sender_id', User::withTrashed()->pluck('id'))->delete();
            $this->info("✅ {$orphanedMessages} messages orphelins supprimés");
        }

        // 4. Nettoyer les conversations orphelines
        $orphanedConversations = Conversation::where(function($query) {
            $query->whereNotIn('user1_id', User::withTrashed()->pluck('id'))
                  ->orWhereNotIn('user2_id', User::withTrashed()->pluck('id'));
        })->count();
        
        if ($orphanedConversations > 0) {
            Conversation::where(function($query) {
                $query->whereNotIn('user1_id', User::withTrashed()->pluck('id'))
                      ->orWhereNotIn('user2_id', User::withTrashed()->pluck('id'));
            })->delete();
            $this->info("✅ {$orphanedConversations} conversations orphelines supprimées");
        }

        // 5. Nettoyer les avis orphelins
        $orphanedReviews = Review::where(function($query) {
            $query->whereNotIn('reviewer_id', User::withTrashed()->pluck('id'))
                  ->orWhereNotIn('reviewed_user_id', User::withTrashed()->pluck('id'));
        })->count();
        
        if ($orphanedReviews > 0) {
            Review::where(function($query) {
                $query->whereNotIn('reviewer_id', User::withTrashed()->pluck('id'))
                      ->orWhereNotIn('reviewed_user_id', User::withTrashed()->pluck('id'));
            })->delete();
            $this->info("✅ {$orphanedReviews} avis orphelins supprimés");
        }

        // 6. Nettoyer les transactions de points orphelines
        $orphanedTransactions = PointTransaction::whereNotIn('user_id', User::withTrashed()->pluck('id'))->count();
        if ($orphanedTransactions > 0) {
            PointTransaction::whereNotIn('user_id', User::withTrashed()->pluck('id'))->delete();
            $this->info("✅ {$orphanedTransactions} transactions de points orphelines supprimées");
        }

        // 7. Nettoyer la table saved_ads
        $orphanedSavedAds = DB::table('saved_ads')
            ->whereNotIn('user_id', User::withTrashed()->pluck('id'))
            ->orWhereNotIn('ad_id', Ad::pluck('id'))
            ->count();
        
        if ($orphanedSavedAds > 0) {
            DB::table('saved_ads')
                ->whereNotIn('user_id', User::withTrashed()->pluck('id'))
                ->orWhereNotIn('ad_id', Ad::pluck('id'))
                ->delete();
            $this->info("✅ {$orphanedSavedAds} favoris orphelins supprimés");
        }

        $this->newLine();
        $this->info('🎉 Nettoyage terminé!');
        
        // Résumé
        $this->newLine();
        $this->table(
            ['Élément', 'Nombre actuel'],
            [
                ['Utilisateurs actifs', User::count()],
                ['Utilisateurs soft-deleted', User::onlyTrashed()->count()],
                ['Annonces', Ad::count()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Supprime toutes les données liées à un utilisateur
     */
    private function deleteUserData(User $user): void
    {
        // Supprimer les annonces
        $user->ads()->delete();
        
        // Supprimer les transactions de points
        $user->pointTransactions()->delete();
        
        // Supprimer les services
        if (method_exists($user, 'services')) {
            $user->services()->delete();
        }
        
        // Supprimer les avis
        if (method_exists($user, 'reviewsReceived')) {
            $user->reviewsReceived()->delete();
        }
        if (method_exists($user, 'reviewsGiven')) {
            $user->reviewsGiven()->delete();
        }
        
        // Détacher les badges
        if (method_exists($user, 'badges')) {
            $user->badges()->detach();
        }
        
        // Détacher les annonces sauvegardées
        if (method_exists($user, 'savedAds')) {
            $user->savedAds()->detach();
        }
        
        // Supprimer les messages
        $user->sentMessages()->delete();
        
        // Supprimer les conversations
        Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->delete();
    }
}
