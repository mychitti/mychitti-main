<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ImpersonateController extends Controller
{
    /**
     * Consume a one-time impersonation token from the admin panel.
     */
    public function start(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (!$token) {
            abort(403, 'Missing impersonation token.');
        }

        $cacheKey = 'impersonate_token_' . $token;
        $data     = Cache::get($cacheKey);

        if (!$data) {
            abort(403, 'Invalid or expired impersonation token. Tokens are valid for 60 seconds.');
        }

        // One-time use — delete immediately
        Cache::forget($cacheKey);

        $vendor = Vendor::findOrFail($data['vendor_id']);

        auth('vendor')->login($vendor);

        session([
            'impersonator_id'    => $data['admin_id'],
            'impersonator_guard' => 'admin',
            'impersonated_id'    => $data['store_id'],
            'impersonated_guard' => 'vendor',
            'impersonated_label' => 'Store #' . $data['store_id'] . ' — ' . ($data['store_name'] ?? ''),
            'impersonator_panel' => $data['admin_panel_url'] ?? null,
        ]);

        $redirectUrl = $data['redirect_url'] ?: '/store-panel';

        // If redirect URL is on a different origin, strip it to just the path
        $redirectPath = parse_url($redirectUrl, PHP_URL_PATH) ?? '/store-panel';

        return redirect($redirectPath);
    }

    /**
     * Exit impersonation — log out vendor, redirect back to admin panel.
     */
    public function stop(): RedirectResponse
    {
        $adminPanelUrl = session('impersonator_panel', config('app.admin_panel_url', '/admin'));

        auth('vendor')->logout();

        session()->forget([
            'impersonator_id',
            'impersonator_guard',
            'impersonated_id',
            'impersonated_guard',
            'impersonated_label',
            'impersonator_panel',
        ]);

        return redirect()->away($adminPanelUrl);
    }
}
