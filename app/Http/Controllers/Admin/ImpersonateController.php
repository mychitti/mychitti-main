<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating. Only superadmin (role_id = 1) can do this.
     */
    public function start(Request $request): RedirectResponse
    {
        $currentAdmin = auth('admin')->user();

        if ($currentAdmin->role_id !== 1) {
            abort(403, 'Only superadmin can impersonate.');
        }

        if (session()->has('impersonator_id')) {
            return back()->with('error', 'Exit current impersonation before starting a new one.');
        }

        $guard          = $request->input('guard', 'admin');
        $userId         = $request->input('user_id');
        $redirectUrl    = $request->input('redirect_url', '');
        $redirectMethod = strtoupper($request->input('redirect_method', 'GET'));

        if ($guard === 'admin') {
            $target = Admin::findOrFail($userId);

            session([
                'impersonator_id'    => $currentAdmin->id,
                'impersonator_guard' => 'admin',
                'impersonated_id'    => $target->id,
                'impersonated_guard' => 'admin',
                'impersonated_label' => 'Admin #' . $target->id . ' — ' . ($target->f_name ?? $target->email ?? ''),
            ]);
            auth('admin')->login($target);

            return redirect($redirectUrl ?: '/admin');
        }

        if ($guard === 'vendor') {
            $store = Store::withoutGlobalScopes()->findOrFail($userId);
            $vendor = $store->vendor;

            if (!$vendor) {
                return back()->with('error', 'No vendor found for this store.');
            }

            $vendorPanelBase = rtrim(config('app.vendor_panel_url', url('/store-panel')), '/');
            $isSameOrigin    = parse_url($vendorPanelBase, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST);

            // JS already sends referer as redirect_url for POST errors
            $safeRedirectUrl = $redirectUrl ?: $vendorPanelBase;

            if ($isSameOrigin) {
                // Same server — direct session login
                session([
                    'impersonator_id'    => $currentAdmin->id,
                    'impersonator_guard' => 'admin',
                    'impersonated_id'    => $store->id,
                    'impersonated_guard' => 'vendor',
                    'impersonated_label' => 'Store #' . $store->id . ' — ' . ($store->name ?? ''),
                ]);
                auth('vendor')->login($vendor);
                return redirect($safeRedirectUrl ?: $vendorPanelBase);
            }

            // Different server — signed one-time token via cache
            $token = Str::random(64);
            Cache::put('impersonate_token_' . $token, [
                'vendor_id'       => $vendor->id,
                'store_id'        => $store->id,
                'store_name'      => $store->name,
                'admin_id'        => $currentAdmin->id,
                'redirect_url'    => $safeRedirectUrl,
                'admin_panel_url' => config('app.admin_panel_url', url('/admin')),
            ], now()->addSeconds(60)); // 60s window to use the token

            $tokenUrl = $vendorPanelBase . '/impersonate?token=' . $token;
            return redirect()->away($tokenUrl);
        }

        return back()->with('error', 'Unknown guard.');
    }

    /**
     * Exit impersonation — works for admin-to-admin impersonation.
     * Vendor-side exit is handled by VendorImpersonateController.
     */
    public function stop(): RedirectResponse
    {
        if (!session()->has('impersonator_id')) {
            return redirect('/admin');
        }

        $originalAdminId   = session('impersonator_id');
        $impersonatedGuard = session('impersonated_guard', 'admin');

        if ($impersonatedGuard === 'vendor') {
            auth('vendor')->logout();
        } else {
            auth('admin')->logout();
        }

        $original = Admin::findOrFail($originalAdminId);
        auth('admin')->login($original);

        session()->forget([
            'impersonator_id',
            'impersonator_guard',
            'impersonated_id',
            'impersonated_guard',
            'impersonated_label',
        ]);

        return redirect('/admin')->with('success', 'Impersonation ended. Welcome back.');
    }
}
