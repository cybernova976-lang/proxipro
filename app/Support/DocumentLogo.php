<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logo de l'entreprise sur les devis et les factures (avantage Premium).
 *
 * Le logo est toujours enregistré en JPEG sur fond blanc, réduit à une taille
 * d'en-tête : c'est le format que le moteur PDF intègre sans dépendance, et le
 * fichier reste léger. Il n'apparaît sur les documents que pendant un
 * abonnement actif ; le fichier est conservé si l'abonnement s'arrête.
 */
final class DocumentLogo
{
    public const MAX_KILOBYTES = 2048;

    private const MAX_WIDTH = 600;

    private const MAX_HEIGHT = 240;

    /** Enregistre le logo du prestataire. Faux si l'image ne peut pas être traitée. */
    public static function store(User $user, UploadedFile $file): bool
    {
        $jpeg = self::toJpeg((string) file_get_contents($file->getRealPath()), (string) $file->getMimeType());
        if ($jpeg === null) {
            return false;
        }

        $disk = Storage::disk(config('filesystems.default', 'public'));
        $path = 'logos/'.$user->id.'-'.Str::lower(Str::random(16)).'.jpg';
        if (! $disk->put($path, $jpeg)) {
            return false;
        }

        $previous = $user->company_logo;
        $user->forceFill(['company_logo' => $path])->save();
        if ($previous && $previous !== $path) {
            $disk->delete($previous);
        }

        return true;
    }

    public static function remove(User $user): void
    {
        if ($user->company_logo) {
            Storage::disk(config('filesystems.default', 'public'))->delete($user->company_logo);
            $user->forceFill(['company_logo' => null])->save();
        }
    }

    /** Le logo doit-il figurer sur les documents de ce prestataire aujourd'hui ? */
    public static function shows(User $user): bool
    {
        return (bool) $user->company_logo && $user->hasActiveProSubscription();
    }

    /** Image prête à être intégrée dans un PDF, ou null s'il n'y a rien à afficher. */
    public static function dataUri(User $user): ?string
    {
        if (! self::shows($user)) {
            return null;
        }

        try {
            $bytes = Storage::disk(config('filesystems.default', 'public'))->get($user->company_logo);
        } catch (\Throwable $error) {
            Log::warning('Logo de document illisible', ['user_id' => $user->id, 'exception' => $error->getMessage()]);

            return null;
        }

        return is_string($bytes) && $bytes !== '' ? 'data:image/jpeg;base64,'.base64_encode($bytes) : null;
    }

    private static function toJpeg(string $bytes, string $mime): ?string
    {
        if ($bytes === '') {
            return null;
        }
        // Sans la bibliothèque d'images, seul un JPEG est accepté, tel quel.
        if (! function_exists('imagecreatefromstring')) {
            return $mime === 'image/jpeg' ? $bytes : null;
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_WIDTH / max(1, $width), self::MAX_HEIGHT / max(1, $height));
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        // Fond blanc : un PNG transparent ne devient pas noir une fois converti.
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $jpeg !== '' ? $jpeg : null;
    }
}
