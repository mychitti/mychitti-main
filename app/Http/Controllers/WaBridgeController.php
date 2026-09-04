<?php

namespace App\Http\Controllers;

use App\Services\WaChatArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ingest endpoint for the Baileys bridge (wa-bridge/). The bridge is the only caller and it
 * authenticates with a shared secret, so this route sits outside the admin session entirely.
 */
class WaBridgeController extends Controller
{
    public function ingest(Request $request)
    {
        $expected = (string) config('services.wa_bridge.secret');
        $given    = (string) $request->header('X-Bridge-Secret');

        if ($expected === '' || !hash_equals($expected, $given)) {
            Log::warning('WA bridge ingest rejected', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'messages'                   => 'required|array|min:1|max:200',
            'messages.*.wa_message_id'   => 'required|string|max:128',
            'messages.*.chat_jid'        => 'required|string|max:128',
            'messages.*.chat_type'       => 'nullable|string|in:dm,group',
            'messages.*.body'            => 'nullable|string',
            'messages.*.sent_at'         => 'nullable|string|max:64',
        ]);

        $result = WaChatArchive::store($validated['messages']);

        return response()->json($result);
    }

    /** Liveness probe so the bridge box can confirm the secret and URL before pairing. */
    public function ping(Request $request)
    {
        $expected = (string) config('services.wa_bridge.secret');
        $ok = $expected !== '' && hash_equals($expected, (string) $request->header('X-Bridge-Secret'));

        return response()->json(['ok' => $ok], $ok ? 200 : 403);
    }
}
