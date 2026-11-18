<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\ViewErrorBag;

/**
 * Messages de retour après une action (« Annonce signalée », « Complétez votre profil »…).
 *
 * Le gabarit principal n'affichait que les messages d'information : sur les
 * pages qui n'affichaient pas elles-mêmes les succès et les erreurs, un
 * formulaire envoyé rechargeait la page sans rien dire.
 *
 * Le gabarit les affiche désormais partout, sauf si la page vient déjà de les
 * écrire elle-même : on cherche le texte dans le contenu rendu, ce qui évite
 * tout doublon sans avoir à déclarer quoi que ce soit dans chaque vue.
 */
class FlashMessages
{
    /** Clé de session => [ton, icône, rôle ARIA]. */
    private const TONES = [
        'success' => ['success', 'fa-circle-check', 'status'],
        'error' => ['error', 'fa-circle-exclamation', 'alert'],
        'warning' => ['warning', 'fa-triangle-exclamation', 'alert'],
    ];

    /**
     * @return array<int, array{tone: string, icon: string, role: string, lines: array<int, string>}>
     */
    public static function fromSession(?Session $session, string $rendered): array
    {
        if ($session === null) {
            return [];
        }

        $flashes = [];
        foreach (array_keys(self::TONES) as $key) {
            $flashes[$key] = $session->get($key);
        }

        $errors = $session->get('errors');

        return self::pending($flashes, $errors instanceof ViewErrorBag ? $errors->all() : [], $rendered);
    }

    /**
     * @param  array<string, mixed>  $flashes  messages de session, par clé (success, error, warning)
     * @param  array<int, mixed>  $validationErrors  erreurs de validation du formulaire
     * @param  string  $rendered  contenu déjà rendu par la page
     * @return array<int, array{tone: string, icon: string, role: string, lines: array<int, string>}>
     */
    public static function pending(array $flashes, array $validationErrors, string $rendered): array
    {
        $messages = [];

        foreach (self::TONES as $key => [$tone, $icon, $role]) {
            $text = self::text($flashes[$key] ?? null);
            if ($text !== '' && ! self::alreadyShown($text, $rendered)) {
                $messages[] = ['tone' => $tone, 'icon' => $icon, 'role' => $role, 'lines' => [$text]];
            }
        }

        $lines = [];
        foreach ($validationErrors as $error) {
            $text = self::text($error);
            if ($text !== '' && ! in_array($text, $lines, true) && ! self::alreadyShown($text, $rendered)) {
                $lines[] = $text;
            }
        }

        if ($lines !== []) {
            [$tone, $icon, $role] = self::TONES['error'];
            $messages[] = ['tone' => $tone, 'icon' => $icon, 'role' => $role, 'lines' => $lines];
        }

        return $messages;
    }

    private static function text(mixed $value): string
    {
        return is_string($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }

    /**
     * La page a-t-elle déjà écrit ce message ? On le cherche tel quel, échappé
     * pour le HTML, et sous ses formes JSON (messages passés à un script).
     */
    private static function alreadyShown(string $text, string $rendered): bool
    {
        if ($rendered === '') {
            return false;
        }

        $forms = [
            $text,
            e($text),
            trim((string) json_encode($text), '"'),
            trim((string) json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '"'),
            trim((string) json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), '"'),
        ];

        foreach (array_unique($forms) as $form) {
            if ($form !== '' && str_contains($rendered, $form)) {
                return true;
            }
        }

        return false;
    }
}
