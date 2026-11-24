<?php

use App\Models\User;
use App\Services\ProviderSkills;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Signalement du 24/11/2026 : une compétence supprimée de « Mes compétences »
 * restait dans le métier principal, les catégories du profil et les demandes
 * proposées, car la suppression ne touchait que user_services.
 *
 * Recalcule, une seule fois, les colonnes du profil (profession,
 * service_category, service_subcategories, pro_service_categories) de chaque
 * membre qui a des compétences enregistrées, à partir de ses compétences
 * actives. Les membres sans compétence enregistrée ne sont pas touchés.
 *
 * Ces colonnes ne disent pas d'où vient une valeur : « Plombier » supprimé de
 * « Mes compétences » et « Jardinier » choisi à la configuration de l'Espace Pro
 * s'y ressemblent. « Mes compétences » fait foi, mais les anciennes valeurs de
 * chaque fiche modifiée sont d'abord copiées dans provider_profile_skill_backups :
 * down() les remet en place.
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'provider_profile_skill_backups';

    private const COLUMNS = ['profession', 'service_category', 'service_subcategories', 'pro_service_categories'];

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                // Valeurs brutes de la base, telles quelles (JSON compris), pour une restauration exacte.
                $table->text('profession')->nullable();
                $table->text('service_category')->nullable();
                $table->text('service_subcategories')->nullable();
                $table->text('pro_service_categories')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        $skills = app(ProviderSkills::class);
        $failures = 0;

        DB::table('user_services')
            ->select('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->chunk(200)
            ->each(function ($userIds) use ($skills, &$failures) {
                User::query()->whereIn('id', $userIds->all())->get()
                    ->each(function (User $user) use ($skills, &$failures) {
                        try {
                            // Une transaction par membre (un SAVEPOINT dans celle de la
                            // migration) : sous PostgreSQL, une requête en échec annule
                            // toute la transaction en cours si on ne revient pas en arrière.
                            DB::transaction(fn () => $this->resync($user, $skills));
                        } catch (\Throwable $exception) {
                            if ($this->isSchemaError($exception)) {
                                throw $exception;
                            }

                            $failures++;
                            Log::warning('Resynchronisation du profil prestataire impossible : fiche laissée telle quelle.', [
                                'user_id' => $user->id,
                                'error' => $exception->getMessage(),
                            ]);
                        }
                    });
            });

        if ($failures > 0) {
            Log::warning('Resynchronisation des profils prestataires : '.$failures.' fiche(s) non modifiée(s).');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::table(self::BACKUP_TABLE)->chunkById(200, function ($backups) {
            foreach ($backups as $backup) {
                DB::table('users')->where('id', $backup->user_id)->update([
                    'profession' => $backup->profession,
                    'service_category' => $backup->service_category,
                    'service_subcategories' => $backup->service_subcategories,
                    'pro_service_categories' => $backup->pro_service_categories,
                ]);
            }
        });

        Schema::drop(self::BACKUP_TABLE);
    }

    private function resync(User $user, ProviderSkills $skills): void
    {
        $before = $this->rawColumns($user->id);
        $skills->sync($user);
        $after = $this->rawColumns($user->id);

        // Seules les fiches réellement modifiées sont sauvegardées : down() ne
        // doit pas écraser plus tard des fiches que cette migration n'a pas touchées.
        if ($before === null || $before == $after) {
            return;
        }

        DB::table(self::BACKUP_TABLE)->insert(array_merge(
            ['user_id' => $user->id, 'created_at' => now()],
            $before,
        ));
    }

    /** @return array<string, mixed>|null */
    private function rawColumns(int $userId): ?array
    {
        $row = DB::table('users')->where('id', $userId)->first(self::COLUMNS);

        return $row ? (array) $row : null;
    }

    /**
     * Table ou colonne absente, droits insuffisants (SQLSTATE de classe 42) :
     * la migration doit échouer, pas avancer fiche après fiche en silence.
     */
    private function isSchemaError(\Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return str_starts_with($sqlState, '42') || str_starts_with($sqlState, 'HY');
    }
};
