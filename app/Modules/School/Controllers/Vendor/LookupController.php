<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\VendorEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LookupController extends Controller
{
    /** Teacher / staff search for select2 ajax. */
    public function teachers(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q = trim($request->query('q', ''));

        $rows = VendorEmployee::where('store_id', $store_id)
            ->when($q, fn($x) => $x->where(fn($w) => $w
                ->where('f_name', 'like', "%$q%")
                ->orWhere('l_name', 'like', "%$q%")
                ->orWhereRaw("CONCAT(COALESCE(f_name,''),' ',COALESCE(l_name,'')) like ?", ["%$q%"])))
            ->orderBy('f_name')->limit(30)->get();

        return response()->json($rows->map(fn($e) => [
            'id'   => $e->id,
            'text' => trim(($e->f_name ?? '') . ' ' . ($e->l_name ?? '')) ?: ('Staff #' . $e->id),
        ]));
    }

    /** Student search for select2 ajax (name / admission no / roll no). */
    public function students(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $q = trim($request->query('q', ''));
        if (!Schema::hasTable('school_students')) return response()->json([]);

        $rows = Student::where('store_id', $store_id)->where('status', 1)
            ->with('currentEnrollment')
            ->when($q, fn($x) => $x->where(fn($w) => $w
                ->where('name', 'like', "%$q%")
                ->orWhere('admission_no', 'like', "%$q%")
                ->orWhereHas('currentEnrollment', fn($e) => $e->where('roll_no', 'like', "%$q%"))))
            ->orderBy('name')->limit(30)->get();

        return response()->json($rows->map(function ($s) {
            $t = $s->name;
            if ($s->admission_no) $t .= ' (' . $s->admission_no . ')';
            if ($s->currentEnrollment?->roll_no) $t .= ' · Roll ' . $s->currentEnrollment->roll_no;
            return ['id' => $s->id, 'text' => $t];
        }));
    }
}
