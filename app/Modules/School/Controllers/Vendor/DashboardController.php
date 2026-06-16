<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $today    = now()->toDateString();

        $stats = [
            'students'   => 0, 'classes' => 0, 'sections' => 0,
            'fees_today' => 0, 'fees_month' => 0, 'dues' => 0,
            'present'    => 0, 'marked' => 0, 'attendance_pct' => 0,
        ];
        $recentPayments = collect();
        $recentStudents = collect();

        if (Schema::hasTable('school_students')) {
            $stats['students'] = Student::where('store_id', $store_id)->where('status', 1)->count();
            $recentStudents = Student::where('store_id', $store_id)
                ->with('currentEnrollment.schoolClass')->orderByDesc('id')->limit(5)->get();
        }
        if (Schema::hasTable('school_classes')) {
            $stats['classes'] = SchoolClass::where('store_id', $store_id)->count();
        }
        if (Schema::hasTable('class_sections')) {
            $stats['sections'] = ClassSection::where('store_id', $store_id)->count();
        }
        if (Schema::hasTable('fee_payments')) {
            $stats['fees_today'] = (float) FeePayment::where('store_id', $store_id)->whereDate('paid_on', $today)->sum('amount');
            $stats['fees_month'] = (float) FeePayment::where('store_id', $store_id)
                ->whereBetween('paid_on', [now()->startOfMonth()->toDateString(), $today])->sum('amount');
            $recentPayments = FeePayment::where('store_id', $store_id)->with('student')->orderByDesc('id')->limit(5)->get();
        }
        if (Schema::hasTable('fee_invoices')) {
            $stats['dues'] = (float) FeeInvoice::where('store_id', $store_id)->where('due_amount', '>', 0)->sum('due_amount');
        }
        if (Schema::hasTable('student_attendances')) {
            $marked = StudentAttendance::where('store_id', $store_id)->whereDate('attendance_date', $today)->count();
            $present = StudentAttendance::where('store_id', $store_id)->whereDate('attendance_date', $today)
                ->whereIn('status', ['present', 'late', 'half_day'])->count();
            $stats['marked'] = $marked;
            $stats['present'] = $present;
            $stats['attendance_pct'] = $marked > 0 ? round($present / $marked * 100) : 0;
        }

        return view('school::vendor.dashboard', compact('stats', 'recentPayments', 'recentStudents'));
    }
}
