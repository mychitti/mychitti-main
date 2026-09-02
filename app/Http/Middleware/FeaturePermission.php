<?php

namespace App\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "May this role touch this feature at all?" — regardless of which action.
 *
 * The screen a feature lives on is not a permission of its own. Granting somebody Create on
 * campaigns and then hiding the campaigns page behind List gives them a permission they cannot
 * reach, which is exactly what happened the first time these were rolled out.
 *
 * So: landing and viewing routes take `feature:<name>` and open for anyone holding any action on
 * it; everything that changes something keeps its own `permission:<feature>,<action>`.
 *
 * Several names are ORed — `feature:whatsapp_connection,whatsapp_billing` for a page that shows
 * both.
 */
class FeaturePermission
{
    public function handle($request, Closure $next, ...$features)
    {
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            if ($admin->role_id == 1) {
                return $next($request);
            }

            try {
                $allowed = DB::table('admin_role_feature_permissions as arfp')
                    ->join('feature_permissions as fp', 'arfp.feature_permission_id', '=', 'fp.id')
                    ->join('features as f', 'fp.feature_id', '=', 'f.id')
                    ->where('arfp.admin_role_id', $admin->role_id)
                    ->whereIn('f.name', $features)
                    ->exists();
            } catch (\Exception $e) {
                // Table may not exist yet — allow rather than lock the panel out.
                return $next($request);
            }

            if ($allowed) {
                return $next($request);
            }

            Toastr::error(translate('messages.access_denied'));
            return redirect()->route('admin.dashboard');
        }

        // The store owner is never gated by a role they do not have.
        if (auth('vendor')->check()) {
            return $next($request);
        }

        $user = Auth::guard('vendor_employee')->user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => translate('messages.unauthorized')], 403);
            }
            Toastr::error(translate('messages.unauthorized'));
            return back();
        }

        $allowed = DB::table('role_feature_permissions as rfp')
            ->join('feature_permissions as fp', 'rfp.feature_permission_id', '=', 'fp.id')
            ->join('features as f', 'fp.feature_id', '=', 'f.id')
            ->where('rfp.role_id', $user->employee_role_id)
            ->whereIn('f.name', $features)
            ->exists();

        if ($allowed) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => false, 'message' => translate('messages.access_denied')], 403);
        }

        Toastr::error(translate('messages.access_denied'));
        return back();
    }
}
