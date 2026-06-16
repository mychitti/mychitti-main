<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\MarkEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('school::vendor.reports.index', $this->gather($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $d = $this->gather($request);
        $sessionName = optional($d['sessions']->firstWhere('id', $d['sessionId']))->name ?? 'All Sessions';
        $cur = fn($v) => number_format((float) $v, 2, '.', '');

        $rows = [];
        $rows[] = ['School Report', $sessionName];
        $rows[] = ['Generated', now()->format('d M Y H:i')];
        $rows[] = [];
        $rows[] = ['Summary'];
        $rows[] = ['Active Students', $d['totalStudents']];
        $rows[] = ['Enrolled This Session', $d['enrollTotal']];
        $rows[] = ['Collected This Month', $cur($d['collectedMonth'])];
        $rows[] = ['Outstanding Dues', $cur($d['totalDues'])];
        $rows[] = ['Attendance This Month %', $d['attPct']];
        $rows[] = [];
        $rows[] = ['Enrollment by Class'];
        $rows[] = ['Class', 'Students'];
        foreach ($d['enrollByClass'] as $r) $rows[] = [$r->name, $r->count];
        $rows[] = [];
        $rows[] = ['Gender Split'];
        $rows[] = ['Male', $d['gender']['male']];
        $rows[] = ['Female', $d['gender']['female']];
        $rows[] = ['Other', $d['gender']['other']];
        $rows[] = [];
        $rows[] = ['Fee Collection (Last 6 Months)'];
        $rows[] = ['Month', 'Amount'];
        foreach ($d['collectionTrend'] as $t) $rows[] = [$t->label, $cur($t->total)];
        $rows[] = [];
        $rows[] = ['Attendance Trend (Last 6 Months)'];
        $rows[] = ['Month', 'Attendance %'];
        foreach ($d['attendanceTrend'] as $t) $rows[] = [$t->label, $t->pct];
        if ($d['examStats']) {
            $rows[] = [];
            $rows[] = ['Exam Performance', $d['examStats']['name']];
            $rows[] = ['Appeared', $d['examStats']['appeared']];
            $rows[] = ['Passed', $d['examStats']['pass']];
            $rows[] = ['Pass %', $d['examStats']['pass_pct']];
            $rows[] = ['Class Average %', $d['examStats']['avg_pct']];
            $rows[] = [];
            $rows[] = ['Subject Averages'];
            $rows[] = ['Subject', 'Average %'];
            foreach ($d['subjectAvg'] as $s) $rows[] = [$s->name, $s->avg];
            $rows[] = [];
            $rows[] = ['Top Performers'];
            $rows[] = ['Student', 'Admission No', 'Percentage'];
            foreach ($d['topPerformers'] as $p) $rows[] = [$p->name, $p->admission_no, $p->pct];
        }
        $rows[] = [];
        $rows[] = ['Top Defaulters'];
        $rows[] = ['Student', 'Admission No', 'Due'];
        foreach ($d['defaulters'] as $df) $rows[] = [$df->student?->name ?? '—', $df->student?->admission_no ?? '', $cur($df->due)];

        $filename = 'school-report-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function gather(Request $request): array
    {
        $store_id = Helpers::get_store_id();

        $sessions  = Schema::hasTable('academic_sessions')
            ? AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->orderByDesc('id')->get()
            : collect();
        $sessionId = $request->query('session') ?: optional($sessions->firstWhere('is_current', true))->id;

        // ---- Headline numbers ----
        $totalStudents = Schema::hasTable('school_students')
            ? Student::where('store_id', $store_id)->where('status', 1)->count() : 0;

        $collectedMonth = Schema::hasTable('fee_payments')
            ? (float) FeePayment::where('store_id', $store_id)
                ->whereBetween('paid_on', [now()->startOfMonth()->toDateString(), now()->toDateString()])->sum('amount') : 0;

        $totalDues = Schema::hasTable('fee_invoices')
            ? (float) FeeInvoice::where('store_id', $store_id)->where('due_amount', '>', 0)->sum('due_amount') : 0;

        $attPct = 0; $attMarked = 0; $attPresent = 0;
        if (Schema::hasTable('student_attendances')) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
            $attMarked  = StudentAttendance::where('store_id', $store_id)->whereBetween('attendance_date', [$from, $to])->count();
            $attPresent = StudentAttendance::where('store_id', $store_id)->whereBetween('attendance_date', [$from, $to])
                ->whereIn('status', ['present', 'late', 'half_day'])->count();
            $attPct = $attMarked > 0 ? round($attPresent / $attMarked * 100) : 0;
        }

        // ---- Enrollment by class ----
        $enrollByClass = collect();
        if (Schema::hasTable('student_enrollments') && Schema::hasTable('school_classes')) {
            $counts = StudentEnrollment::where('store_id', $store_id)->where('status', 1)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->select('school_class_id', DB::raw('COUNT(*) as c'))->groupBy('school_class_id')->pluck('c', 'school_class_id');
            $classes = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get();
            $enrollByClass = $classes->map(fn($c) => (object) ['name' => $c->name, 'count' => (int) ($counts[$c->id] ?? 0)]);
        }
        $enrollMax = max(1, (int) ($enrollByClass->max('count') ?? 0));
        $enrollTotal = (int) $enrollByClass->sum('count');

        // ---- Gender split (active students) ----
        $gender = ['male' => 0, 'female' => 0, 'other' => 0];
        if (Schema::hasTable('school_students')) {
            foreach (Student::where('store_id', $store_id)->where('status', 1)
                ->select('gender', DB::raw('COUNT(*) as c'))->groupBy('gender')->pluck('c', 'gender') as $g => $c) {
                $key = strtolower((string) $g);
                if (isset($gender[$key])) $gender[$key] = (int) $c; else $gender['other'] += (int) $c;
            }
        }

        // ---- Fee collection — last 6 months ----
        $collectionTrend = collect();
        if (Schema::hasTable('fee_payments')) {
            for ($i = 5; $i >= 0; $i--) {
                $m = now()->subMonths($i);
                $sum = (float) FeePayment::where('store_id', $store_id)
                    ->whereBetween('paid_on', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()])
                    ->sum('amount');
                $collectionTrend->push((object) ['label' => $m->format('M'), 'total' => $sum]);
            }
        }
        $trendMax = max(1, (float) ($collectionTrend->max('total') ?? 0));

        // ---- Attendance — last 6 months ----
        $attendanceTrend = collect();
        if (Schema::hasTable('student_attendances')) {
            for ($i = 5; $i >= 0; $i--) {
                $m = now()->subMonths($i);
                $range = [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()];
                $mk = StudentAttendance::where('store_id', $store_id)->whereBetween('attendance_date', $range)->count();
                $pr = StudentAttendance::where('store_id', $store_id)->whereBetween('attendance_date', $range)
                    ->whereIn('status', ['present', 'late', 'half_day'])->count();
                $attendanceTrend->push((object) ['label' => $m->format('M'), 'pct' => $mk > 0 ? round($pr / $mk * 100) : 0]);
            }
        }

        // ---- Exam performance (selected exam in session) ----
        $exams = collect();
        $examId = $request->query('exam');
        $examStats = null;
        $subjectAvg = collect();
        $topPerformers = collect();
        if (Schema::hasTable('school_exams') && Schema::hasTable('exam_subjects') && Schema::hasTable('mark_entries')) {
            $exams = Exam::where('store_id', $store_id)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->with('schoolClass')->orderByDesc('id')->get();
            $examId = $examId ?: optional($exams->first())->id;
            $exam = $examId ? $exams->firstWhere('id', (int) $examId) : null;

            if ($exam) {
                $subjects = ExamSubject::where('store_id', $store_id)->where('exam_id', $exam->id)->with('subject')->get();
                $totalMax = (float) $subjects->sum('max_marks');
                // keep exam metrics consistent with the active branch (Student is branch-scoped)
                $branchStudentIds = Student::where('store_id', $store_id)->pluck('id');
                $marks = MarkEntry::where('store_id', $store_id)->where('exam_id', $exam->id)
                    ->whereIn('student_id', $branchStudentIds)->get();
                $byStudent = $marks->groupBy('student_id');

                $studentPcts = []; $passCount = 0; $appeared = 0;
                foreach ($byStudent as $sid => $sMarks) {
                    $keyed = $sMarks->keyBy('exam_subject_id');
                    $obtained = 0; $fail = false; $hasMark = false;
                    foreach ($subjects as $es) {
                        $mk = $keyed->get($es->id);
                        if (!$mk) continue;
                        if ($mk->is_absent) { $fail = true; continue; }
                        $hasMark = true;
                        $got = (float) $mk->marks_obtained;
                        $obtained += $got;
                        if ($got < $es->pass_marks) $fail = true;
                    }
                    if (!$hasMark) continue;
                    $appeared++;
                    $studentPcts[$sid] = $totalMax > 0 ? round($obtained / $totalMax * 100, 1) : 0;
                    if (!$fail) $passCount++;
                }

                $examStats = [
                    'name'     => ($exam->schoolClass?->name ? $exam->schoolClass->name . ' — ' : '') . $exam->name,
                    'appeared' => $appeared,
                    'pass'     => $passCount,
                    'pass_pct' => $appeared > 0 ? round($passCount / $appeared * 100) : 0,
                    'avg_pct'  => $appeared > 0 ? round(collect($studentPcts)->avg(), 1) : 0,
                ];

                $subjectAvg = $subjects->map(function ($es) use ($marks) {
                    $rows = $marks->where('exam_subject_id', $es->id)->where('is_absent', false);
                    $avg = $rows->count() && $es->max_marks > 0
                        ? round($rows->avg('marks_obtained') / $es->max_marks * 100, 1) : 0;
                    return (object) ['name' => $es->subject?->name ?? '—', 'avg' => $avg];
                });

                $topIds = collect($studentPcts)->sortDesc()->take(5);
                $studentMap = Student::where('store_id', $store_id)->whereIn('id', $topIds->keys())->get()->keyBy('id');
                $topPerformers = $topIds->map(fn($pct, $sid) => (object) [
                    'name'         => $studentMap[$sid]->name ?? '—',
                    'admission_no' => $studentMap[$sid]->admission_no ?? '',
                    'pct'          => $pct,
                ])->values();
            }
        }
        $subjectAvgMax = max(1, (float) ($subjectAvg->max('avg') ?? 0));

        $defaulters = $this->defaulters($store_id);

        return compact(
            'sessions', 'sessionId', 'totalStudents', 'collectedMonth', 'totalDues',
            'attPct', 'attMarked', 'attPresent', 'enrollByClass', 'enrollMax', 'enrollTotal',
            'gender', 'collectionTrend', 'trendMax', 'attendanceTrend', 'defaulters',
            'exams', 'examId', 'examStats', 'subjectAvg', 'subjectAvgMax', 'topPerformers'
        );
    }

    private function defaulters($store_id)
    {
        if (!Schema::hasTable('fee_invoices')) return collect();
        return FeeInvoice::where('store_id', $store_id)->where('due_amount', '>', 0)
            ->select('student_id', DB::raw('SUM(due_amount) as due'))
            ->groupBy('student_id')->orderByDesc('due')->limit(8)->with('student')->get();
    }
}
