<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();

        $sessions = AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->orderByDesc('id')->get();
        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
        $sections = ClassSection::where('store_id', $store_id)->with('schoolClass')->get();

        $srcSession = $request->query('src_session') ?: optional($sessions->firstWhere('is_current', true))->id;
        $srcClass   = $request->query('src_class');
        $srcSection = $request->query('src_section');

        $roster = collect();
        if ($srcClass) {
            $roster = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->where('academic_session_id', $srcSession)
                ->where('school_class_id', $srcClass)
                ->when($srcSection, fn($q) => $q->where('class_section_id', $srcSection))
                ->with(['student', 'section'])
                ->get()
                ->filter(fn($e) => $e->student)
                ->sortBy(fn($e) => (int) $e->roll_no)->values();
        }

        // Suggest the next class (by numeric order, else next id).
        $srcClassModel = $srcClass ? $classes->firstWhere('id', (int) $srcClass) : null;
        $nextClass = null;
        if ($srcClassModel) {
            $nextClass = $classes->where('numeric_order', '>', $srcClassModel->numeric_order)->sortBy('numeric_order')->first()
                ?: $classes->firstWhere('id', '>', $srcClassModel->id);
        }
        $isFinalClass = $srcClassModel && !$nextClass;

        return view('school::vendor.promotion.index', compact(
            'sessions', 'classes', 'sections', 'srcSession', 'srcClass', 'srcSection',
            'roster', 'nextClass', 'isFinalClass'
        ));
    }

    public function process(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $request->validate([
            'action'      => 'required|in:promote,graduate',
            'student_ids' => 'required|array|min:1',
            'src_session' => 'required|integer',
            'src_class'   => 'required|integer',
        ]);

        if ($request->action === 'promote') {
            $request->validate([
                'target_session' => 'required|integer',
                'target_class'   => 'required|integer',
            ]);
        }

        $ids = $request->student_ids;
        $count = 0;

        // Running roll number for the target section.
        $nextRoll = 0;
        if ($request->action === 'promote') {
            $nextRoll = (int) StudentEnrollment::where('store_id', $store_id)
                ->where('academic_session_id', $request->target_session)
                ->where('school_class_id', $request->target_class)
                ->when($request->target_section, fn($q) => $q->where('class_section_id', $request->target_section))
                ->where('status', 1)
                ->get()->pluck('roll_no')->map(fn($r) => (int) $r)->max();
        }

        foreach ($ids as $sid) {
            $student = Student::where('store_id', $store_id)->find($sid);
            if (!$student) continue;

            // Close the current enrollment.
            StudentEnrollment::where('store_id', $store_id)->where('student_id', $sid)->where('status', 1)->update(['status' => 0]);

            if ($request->action === 'graduate') {
                $student->update(['status' => 2]); // 2 = alumni / passed out
            } else {
                StudentEnrollment::create([
                    'store_id'            => $store_id,
                    'student_id'          => $sid,
                    'academic_session_id' => $request->target_session,
                    'school_class_id'     => $request->target_class,
                    'class_section_id'    => $request->target_section ?: null,
                    'roll_no'             => ++$nextRoll,
                    'status'              => 1,
                ]);
            }
            $count++;
        }

        $verb = $request->action === 'graduate' ? 'graduated' : 'promoted';
        Toastr::success("$count student(s) $verb.");

        return redirect()->route('vendor.school.promotion.index', array_filter([
            'src_session' => $request->action === 'promote' ? $request->target_session : $request->src_session,
            'src_class'   => $request->action === 'promote' ? $request->target_class : null,
            'src_section' => $request->action === 'promote' ? $request->target_section : null,
        ]));
    }
}
