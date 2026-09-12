<?php

namespace App\Modules\SalesCRM\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Pipeline (Kanban board) and Sales Queries (list) used to be two separate pages over the same
// SalesQuery records; the list was folded into the board (which gained search/zone filter and
// delete) so there's now a single page. This route survives only so old bookmarks/links to
// /sales-crm/pipeline keep working.
class PipelineController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.sales-crm.query.index', $request->query());
    }
}
