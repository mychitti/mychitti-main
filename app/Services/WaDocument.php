<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * A short-lived public copy of a private PDF, parked somewhere Meta can actually fetch it.
 *
 * The attachment on a media template is collected by Meta's own servers, from the public internet,
 * with no cookies and no session. `invoice/` and `receipt/` are auth-gated in routes/web.php by
 * design, so Meta following one of those links is answered with a redirect to the login page — and
 * the 200-OK HTML behind that redirect is what lands in the customer's chat, named Invoice.pdf and
 * opening as nothing. The send still reports success either way: Graph accepts the message long
 * before it tries to collect the file, so the failure is invisible from this side.
 *
 * So the bill is copied under a random name into a directory the storage route serves to anyone,
 * exactly as HmisWhatsAppShare already does for prescriptions and lab reports. The name is the
 * whole protection, and PruneSharedPdfsJob deletes the copy a day later, once Meta has long since
 * fetched it — the original stays where it was, still behind the login.
 */
class WaDocument
{
    /** Whitelisted in routes/web.php ($alwaysPublic) and swept hourly by PruneSharedPdfsJob. */
    const DIR = 'wa-shared';

    /**
     * Copy a file on the public disk to the staging directory; returns the link to send to Meta.
     *
     * Null means the file was not there to copy — the caller must not fall back to the original
     * URL, which is the auth-gated link this exists to avoid.
     */
    public static function stage(?string $storedPath): ?string
    {
        $storedPath = trim((string) $storedPath, '/');
        if ($storedPath === '') {
            return null;
        }

        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($storedPath)) {
                return null;
            }

            $ext  = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
            $name = bin2hex(random_bytes(24)) . ($ext ? '.' . $ext : '');
            $disk->copy($storedPath, self::DIR . '/' . $name);

            return asset('storage/app/public/' . self::DIR) . '/' . $name;
        } catch (\Throwable $e) {
            Log::error('WhatsApp document staging failed for ' . $storedPath . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * The stored path behind one of this app's own /storage/app/public/<dir>/<file> URLs.
     *
     * Callers hold the URL rather than the path — it is what the PDF builders hand back — and a
     * bare filename is accepted too, so either half of that pair works.
     */
    public static function pathFor(string $dir, ?string $urlOrName): ?string
    {
        $name = basename((string) parse_url((string) $urlOrName, PHP_URL_PATH));
        return $name === '' ? null : trim($dir, '/') . '/' . $name;
    }
}
