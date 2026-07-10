<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeHead;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Store;
use App\Models\Student;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class FeeController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('fee_heads')) {
            DB::statement("CREATE TABLE IF NOT EXISTS fee_heads (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                name VARCHAR(150) NOT NULL, gst_percent DECIMAL(5,2) DEFAULT 0, status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('fee_structures')) {
            DB::statement("CREATE TABLE IF NOT EXISTS fee_structures (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                academic_session_id BIGINT UNSIGNED NULL, school_class_id BIGINT UNSIGNED NOT NULL,
                items LONGTEXT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('fee_invoices')) {
            DB::statement("CREATE TABLE IF NOT EXISTS fee_invoices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                student_id BIGINT UNSIGNED NOT NULL, academic_session_id BIGINT UNSIGNED NULL,
                invoice_no VARCHAR(50) NULL, invoice_date DATE NULL, items LONGTEXT NULL,
                total_amount DECIMAL(12,2) DEFAULT 0, concession DECIMAL(12,2) DEFAULT 0,
                gst_amount DECIMAL(12,2) DEFAULT 0, paid_amount DECIMAL(12,2) DEFAULT 0,
                due_amount DECIMAL(12,2) DEFAULT 0, status VARCHAR(20) DEFAULT 'unpaid',
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id), KEY idx_student (student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!Schema::hasTable('fee_payments')) {
            DB::statement("CREATE TABLE IF NOT EXISTS fee_payments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                fee_invoice_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
                receipt_no VARCHAR(50) NULL, amount DECIMAL(12,2) DEFAULT 0,
                payment_mode VARCHAR(30) NULL, paid_on DATE NULL, collected_by VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (Schema::hasTable('fee_invoices') && !Schema::hasColumn('fee_invoices', 'concession_note')) {
            DB::statement("ALTER TABLE `fee_invoices` ADD COLUMN `concession_note` VARCHAR(255) NULL");
        }
    }

    /* ===== Landing: dues / defaulters ===== */
    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $search = trim($request->query('search', ''));

        $dues = FeeInvoice::where('store_id', $store_id)->where('due_amount', '>', 0)
            ->with('student')
            ->when($search, fn($q) => $q->whereHas('student', fn($s) => $s->where('name', 'like', "%$search%")->orWhere('admission_no', 'like', "%$search%")))
            ->orderByDesc('id')->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.fees.index', compact('dues', 'search'));
    }

    /* ===== Fee Heads ===== */
    public function heads()
    {
        $this->ensureSchema();
        $heads = FeeHead::where('store_id', Helpers::get_store_id())->orderBy('name')->get();
        return view('school::vendor.fees.heads', compact('heads'));
    }

    public function head_store(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['name' => 'required|string|max:150', 'gst_percent' => 'nullable|numeric|min:0|max:100']);
        FeeHead::updateOrCreate(
            ['id' => $request->id, 'store_id' => Helpers::get_store_id()],
            ['store_id' => Helpers::get_store_id(), 'name' => $request->name, 'gst_percent' => (float) $request->gst_percent, 'status' => 1]
        );
        Toastr::success('Fee head saved.');
        return back();
    }

    public function head_delete($id)
    {
        FeeHead::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Fee head removed.');
        return back();
    }

    /* ===== Fee Structure (per class + session) ===== */
    public function structure(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $classId   = $request->query('class_id');
        $sessionId = $request->query('session_id') ?: AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id');

        $classes  = SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->get();
        $sessions = AcademicSession::where('store_id', $store_id)->orderByDesc('is_current')->get();
        $heads    = FeeHead::where('store_id', $store_id)->orderBy('name')->get();

        $structure = null;
        if ($classId) {
            $structure = FeeStructure::where('store_id', $store_id)->where('school_class_id', $classId)
                ->where('academic_session_id', $sessionId)->first();
        }
        $items = collect($structure->items ?? [])->keyBy('head_id');

        return view('school::vendor.fees.structure', compact('classes', 'sessions', 'heads', 'classId', 'sessionId', 'items'));
    }

    public function structure_save(Request $request)
    {
        $this->ensureSchema();
        $request->validate(['class_id' => 'required|integer']);
        $store_id = Helpers::get_store_id();

        $items = [];
        foreach ($request->input('amount', []) as $headId => $amount) {
            if ((float) $amount <= 0) continue;
            $head = FeeHead::where('store_id', $store_id)->find($headId);
            if (!$head) continue;
            $items[] = [
                'head_id'   => (int) $headId,
                'head_name' => $head->name,
                'amount'    => (float) $amount,
                'frequency' => $request->input("frequency.$headId", 'Annual'),
                'gst_percent' => (float) $head->gst_percent,
            ];
        }

        FeeStructure::updateOrCreate(
            ['store_id' => $store_id, 'school_class_id' => $request->class_id, 'academic_session_id' => $request->session_id ?: null],
            ['items' => $items]
        );
        Toastr::success('Fee structure saved.');
        return back();
    }

    /* ===== Collect Fee ===== */
    public function collect($studentId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = Student::where('store_id', $store_id)->with('currentEnrollment')->findOrFail($studentId);

        $sessionId = $student->currentEnrollment?->academic_session_id
            ?: AcademicSession::where('store_id', $store_id)->where('is_current', 1)->value('id');
        $classId = $student->currentEnrollment?->school_class_id;

        $structure = $classId ? FeeStructure::where('store_id', $store_id)->where('school_class_id', $classId)
            ->where('academic_session_id', $sessionId)->first() : null;
        $items = $structure->items ?? [];

        $grossTotal = collect($items)->sum(fn($i) => (float) ($i['amount'] ?? 0));
        $auto = ['amount' => 0, 'schemes' => []];
        if (Schema::hasTable('student_concessions')) {
            $auto = \App\Models\StudentConcession::concessionFor($student, $sessionId, $grossTotal);
        }

        return view('school::vendor.fees.collect', compact('student', 'items', 'auto'));
    }

    public function collect_store(Request $request, $studentId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $student  = Student::where('store_id', $store_id)->with('currentEnrollment')->findOrFail($studentId);

        $request->validate([
            'name'         => 'required|array|min:1',
            'amount'       => 'required|array',
            'payment_mode' => 'required|string|max:30',
            'concession'   => 'nullable|numeric|min:0',
            'amount_paid'  => 'nullable|numeric|min:0',
        ]);

        $items = [];
        $total = 0;
        $gstTotal = 0;
        foreach ($request->name as $k => $name) {
            $amt = (float) ($request->amount[$k] ?? 0);
            if ($amt <= 0) continue;
            $gstP = (float) ($request->gst_percent[$k] ?? 0);
            $gst  = $gstP > 0 ? round($amt - ($amt / (1 + $gstP / 100)), 2) : 0;
            $items[] = ['name' => $name, 'amount' => $amt, 'gst_percent' => $gstP, 'gst_amount' => $gst];
            $total += $amt;
            $gstTotal += $gst;
        }

        $concession = (float) ($request->concession ?? 0);
        $net        = max(0, $total - $concession);
        $paid       = $request->filled('amount_paid') ? min((float) $request->amount_paid, $net) : $net;
        $due        = round($net - $paid, 2);
        $status     = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        $sessionId = $student->currentEnrollment?->academic_session_id;
        $invoiceNo = 'FEE-' . school_next_serial(FeeInvoice::class, $store_id, 'invoice_no');

        $concessionNote = null;
        if ($concession > 0 && Schema::hasTable('student_concessions')) {
            $auto = \App\Models\StudentConcession::concessionFor($student, $sessionId, $total);
            if (!empty($auto['schemes'])) {
                $concessionNote = collect($auto['schemes'])
                    ->map(fn($s) => $s['name'] . ' (' . Helpers::format_currency($s['amount']) . ')')->implode(', ');
            }
        }

        $invoice = FeeInvoice::create([
            'store_id'            => $store_id,
            'student_id'          => $student->id,
            'academic_session_id' => $sessionId,
            'invoice_no'          => $invoiceNo,
            'invoice_date'        => now()->toDateString(),
            'items'               => $items,
            'total_amount'        => $total,
            'concession'          => $concession,
            'concession_note'     => $concessionNote,
            'gst_amount'          => $gstTotal,
            'paid_amount'         => $paid,
            'due_amount'          => $due,
            'status'              => $status,
        ]);

        if ($paid > 0) {
            $receiptNo = 'RCPT-' . school_next_serial(FeePayment::class, $store_id, 'receipt_no');
            FeePayment::create([
                'store_id'       => $store_id,
                'fee_invoice_id' => $invoice->id,
                'student_id'     => $student->id,
                'receipt_no'     => $receiptNo,
                'amount'         => $paid,
                'payment_mode'   => $request->payment_mode,
                'paid_on'        => now()->toDateString(),
                'collected_by'   => $this->currentUserName(),
            ]);
            $this->postToLedger($store_id, $paid, $request->payment_mode, $student->name, $invoiceNo);
 
            // Notify parent about fee payment receipt
            $receiptAmt = Helpers::format_currency($paid);
            $msg = "Dear Parent, a fee payment of {$receiptAmt} has been received for {$student->name} against Invoice: {$invoiceNo}. Receipt: {$receiptNo}. Thank you.";
            $push = [
                'title' => 'Fee Payment Received',
                'description' => "Received {$receiptAmt} for {$student->name}. Receipt: {$receiptNo}."
            ];
            _sendSchoolNotification($student, 'fee_receipt', $msg, $push);
        }

        Toastr::success('Fee collected.');
        return redirect()->route('vendor.school.fees.receipt', $invoice->id);
    }

    public function receipt($id)
    {
        $this->ensureSchema();
        $invoice = FeeInvoice::withoutGlobalScope('schoolBranch')->where('store_id', Helpers::get_store_id())->with(['student.currentEnrollment.schoolClass', 'payments'])->findOrFail($id);
        $store   = Store::withoutGlobalScopes()->find($invoice->store_id);
        $branch  = $invoice->branch_id ? \App\Models\Branch::find($invoice->branch_id) : null;
        return view('school::vendor.fees.receipt', ['invoice' => $invoice, 'store' => $store, 'branch' => $branch, 'pdf' => false]);
    }

    public function receipt_pdf($id)
    {
        $this->ensureSchema();
        $invoice = FeeInvoice::withoutGlobalScope('schoolBranch')->where('store_id', Helpers::get_store_id())->with(['student.currentEnrollment.schoolClass', 'payments'])->findOrFail($id);
        $store   = Store::withoutGlobalScopes()->find($invoice->store_id);
        $branch  = $invoice->branch_id ? \App\Models\Branch::find($invoice->branch_id) : null;

        $html = View::make('school::vendor.fees.receipt', ['invoice' => $invoice, 'store' => $store, 'branch' => $branch, 'pdf' => true])->render();
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [150, 180], 'margin_left' => 6, 'margin_right' => 6, 'margin_top' => 6, 'margin_bottom' => 6, 'tempDir' => storage_path('tmp')]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('fee_receipt_' . $invoice->invoice_no . '.pdf', 'I');
    }

    public function payments(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        $payments = FeePayment::where('store_id', $store_id)
            ->whereBetween('paid_on', [$from, $to])
            ->with(['student', 'invoice'])->orderByDesc('id')
            ->paginate(config('default_pagination'))->withQueryString();
        $total = FeePayment::where('store_id', $store_id)->whereBetween('paid_on', [$from, $to])->sum('amount');

        return view('school::vendor.fees.payments', compact('payments', 'from', 'to', 'total'));
    }

    /* ===== helpers ===== */
    private function postToLedger($store_id, $amount, $mode, $studentName, $invoiceNo): void
    {
        try {
            $isCash        = in_array(strtolower($mode), ['cash', 'wallet']);
            $creditAccount = Helpers::ensureSchoolFeeRevenueAccount();
            $debitAccount  = $isCash ? Helpers::ensureCashAccount() : Helpers::ensureBankAccount();

            $data = [
                'date'         => now(),
                'amount'       => $amount,
                'voucher_type' => 'Receipt',
                'invoice_id'   => 'fee-' . $invoiceNo,
                'status'       => 'approved',
                'description'  => 'School Fee — ' . $studentName . ' (' . $invoiceNo . ')',
                'payment_mode' => $isCash ? 'cash' : 'bank',
            ];
            $voucher = _masterLedgerEntry($data, $creditAccount, $debitAccount, 'store', 'store', null);
            _saveDayBookEntry($amount, 'credit', $store_id, 'School Fee — ' . $studentName, null, $voucher?->id, now(), null, $isCash ? 'cash' : 'bank');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('School fee ledger post failed: ' . $e->getMessage());
        }
    }

    public function sendReminder($invoiceId)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $invoice = FeeInvoice::where('store_id', $store_id)->with('student')->findOrFail($invoiceId);
        
        $student = $invoice->student;
        if ($student) {
            $dueAmt = Helpers::format_currency($invoice->due_amount);
            $msg = "Dear Parent, this is a friendly reminder that a pending fee balance of {$dueAmt} is outstanding for {$student->name} against Invoice: {$invoice->invoice_no}. Please make the payment soon. Thank you.";
            $push = [
                'title' => 'Fee Payment Reminder',
                'description' => "Pending fee dues of {$dueAmt} outstanding for {$student->name}."
            ];
            _sendSchoolNotification($student, 'fee_reminder', $msg, $push);
            Toastr::success('Fee reminder sent to parent.');
        } else {
            Toastr::error('Student details not found.');
        }
        
        return back();
    }

    private function currentUserName(): string
    {
        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: 'Staff';
        }
        $v = auth('vendor')->user();
        return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Admin';
    }
}
