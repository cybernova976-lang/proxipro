<?php

namespace App\Session;

use App\Http\Middleware\BackgroundSignal;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Arr;

/**
 * Sessions en base : un signal d'arrière-plan ne réécrit pas la session.
 *
 * En base, chaque requête relit toute la session puis la réécrit toute à la
 * fin : la dernière écriture gagne. Les signaux de présence, d'activité et de
 * mesure d'usage partent en continu, souvent en même temps qu'un envoi de
 * formulaire. Constaté le 19/11/2026 dans le journal des requêtes de
 * production : un signal avait lu la session avant qu'une action y inscrive
 * son message de confirmation, puis l'avait réécrite juste après, sans le
 * message.
 *
 * Un signal qui n'a rien changé à la session ne la réécrit donc plus : il se
 * contente de noter que la session est toujours active. S'il a réellement
 * changé quelque chose (première clé de garde d'inactivité, déconnexion…),
 * l'écriture normale a lieu.
 */
class SignalAwareDatabaseSessionHandler extends DatabaseSessionHandler
{
    /** Contenu lu au début de la requête, tel que stocké. */
    private ?string $loaded = null;

    public function read($sessionId): string|false
    {
        $data = parent::read($sessionId);
        $this->loaded = is_string($data) ? $data : null;

        return $data;
    }

    public function write($sessionId, $data): bool
    {
        if ($this->exists && $this->isBackgroundSignal() && $this->sameContent($this->loaded, $data)) {
            // Garder la session en vie sans toucher à son contenu.
            $this->getQuery()->where('id', $sessionId)->update(['last_activity' => $this->currentTime()]);

            return true;
        }

        return parent::write($sessionId, $data);
    }

    private function isBackgroundSignal(): bool
    {
        if (! $this->container || ! $this->container->bound('request')) {
            return false;
        }

        return (bool) $this->container->make('request')->attributes->get(BackgroundSignal::ATTRIBUTE, false);
    }

    /**
     * Le signal a-t-il laissé la session telle qu'il l'a trouvée ? On ignore ce
     * que toute requête modifie machinalement : le vieillissement des messages
     * de retour et l'adresse précédente. En cas de doute, on écrit.
     */
    private function sameContent(?string $before, string $after): bool
    {
        if ($before === null || $before === '') {
            return false;
        }

        $before = @unserialize($before);
        $after = @unserialize($after);
        if (! is_array($before) || ! is_array($after)) {
            return false;
        }

        $flashKeys = array_merge(
            (array) Arr::get($before, '_flash.old', []), (array) Arr::get($before, '_flash.new', []),
            (array) Arr::get($after, '_flash.old', []), (array) Arr::get($after, '_flash.new', []),
        );
        $ignored = array_merge(['_flash', '_previous'], array_filter($flashKeys, 'is_string'));

        return Arr::except($before, $ignored) == Arr::except($after, $ignored);
    }
}
