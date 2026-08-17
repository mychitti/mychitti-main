<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\MessageLog;
use App\Services\MessageReadiness;
use Illuminate\Http\Request;

/**
 * "Why didn't my customer get it?" — answered on a screen instead of in the PHP log.
 */
class MessageLogController extends Controller
{
    public function index(Request $request)
    {
        $storeId = (int) Helpers::get_store_id();

        $filters = [
            'status' => $request->get('status'),
            'key'    => $request->get('key'),
            'search' => trim((string) $request->get('search')),
        ];

        if ($filters['status'] && !in_array($filters['status'], [
            MessageLog::SENT, MessageLog::SKIPPED, MessageLog::FAILED, MessageLog::QUEUED,
        ], true)) {
            $filters['status'] = null;
        }

        $entries = MessageLog::recent($storeId, $filters);
        $summary = MessageLog::summary($storeId);
        $keys    = MessageLog::keysUsed($storeId);

        // The same store-wide banner the settings page shows: a log full of skips is meaningless
        // next to a store that never finished connecting.
        $store = MessageReadiness::store($storeId);

        return view('vendor-views.whatsapp.message-log', compact(
            'entries', 'summary', 'keys', 'filters', 'store'
        ));
    }
}
