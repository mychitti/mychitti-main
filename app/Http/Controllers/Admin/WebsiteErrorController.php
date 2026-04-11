<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteError;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteErrorController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = WebsiteError::latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%");
            });
        }

        $websiteErrors = $query->paginate(20)->appends($request->query());

        return view('admin-views.logs.website-error-logs', compact('websiteErrors', 'status'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,resolved,reopen,closed']);

        WebsiteError::findOrFail($id)->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id): JsonResponse
    {
        WebsiteError::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->ids;

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected']);
        }

        $count = WebsiteError::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => "{$count} error(s) deleted"]);
    }
}
