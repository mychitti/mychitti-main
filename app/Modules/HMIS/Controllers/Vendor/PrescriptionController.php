<?php

namespace App\Modules\HMIS\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\InventoryItem;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\ServiceRequest;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class PrescriptionController extends Controller
{
    private function storeId()
    {
        return Helpers::get_store_id();
    }

    private function currentUserId(): int
    {
        return auth('vendor_employee')->id() ?? auth('vendor')->id();
    }

    private function currentUserType(): string
    {
        return auth('vendor_employee')->check() ? 'vendor_employee' : 'vendor';
    }

    private function canEdit(Prescription $rx): bool
    {
        if (is_null($rx->created_by)) {
            return true;
        }

        if ($rx->created_by === $this->currentUserId() && $rx->created_by_type === $this->currentUserType()) {
            return true;
        }

        if ($rx->appointment_id && auth('vendor_employee')->check()) {
            $appt = $rx->relationLoaded('appointment') ? $rx->appointment : Appointment::find($rx->appointment_id);
            if ($appt) {
                return DoctorProfile::where('id', $appt->doctor_profile_id)
                    ->where('emp_id', auth('vendor_employee')->id())
                    ->exists();
            }
        }

        return false;
    }

    public function index(Request $request)
    {
        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $query = Prescription::where('store_id', $this->storeId())
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['patient', 'doctorProfile.employee', 'appointment:id,doctor_profile_id'])
            ->latest();

        if ($request->filled('patient')) {
            $search = $request->patient;
            $query->whereHas('patient', fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('patient_uid', 'like', "%{$search}%"));
        }
        if ($request->filled('doctor')) {
            $query->where('doctor_profile_id', $request->doctor);
        }

        $prescriptions = $query->paginate(20)->withQueryString();
        $doctors = DoctorProfile::where('store_id', $this->storeId())->with('employee')->get();

        $myDoctorProfileId = null;
        if (auth('vendor_employee')->check()) {
            $myDoctorProfileId = DoctorProfile::where('emp_id', auth('vendor_employee')->id())
                ->where('store_id', $this->storeId())
                ->value('id');
        }

        return view('hmis::vendor.prescription.index', compact('prescriptions', 'doctors', 'preset', 'from', 'to', 'myDoctorProfileId'));
    }

    public function export(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('prescription', 'export')) abort(403);
        $preset = $request->get('date_range', 'this_month');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $query = Prescription::where('store_id', $this->storeId())
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['patient', 'doctorProfile.employee', 'items'])
            ->latest();

        if ($request->filled('patient')) {
            $search = $request->patient;
            $query->whereHas('patient', fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('patient_uid', 'like', "%{$search}%"));
        }
        if ($request->filled('doctor')) {
            $query->where('doctor_profile_id', $request->doctor);
        }

        $headings = ['#', 'Patient', 'UID', 'Doctor', 'Diagnosis', 'Medicines', 'Status', 'Follow-Up', 'Date'];
        $data = $query->get()->map(fn($rx) => [
            $rx->id,
            $rx->patient?->name,
            $rx->patient?->patient_uid,
            'Dr. ' . trim(($rx->doctorProfile?->employee?->f_name ?? '') . ' ' . ($rx->doctorProfile?->employee?->l_name ?? '')),
            $rx->diagnosis,
            $rx->items->count(),
            $rx->is_finalized ? 'Finalized' : 'Draft',
            $rx->follow_up_date?->format('d M Y'),
            $rx->created_at->format('d M Y'),
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'prescriptions_' . $from . '_' . $to . '.xlsx'
        );
    }

    public function create(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('prescription', 'add')) abort(403);
        $storeId = $this->storeId();
        $doctors = DoctorProfile::where('store_id', $storeId)->with('employee')->get();

        $appointment     = null;
        $serviceRequest  = null;
        $patient         = null;
        $doctorProfileId = null;

        if ($request->filled('appointment_id')) {
            $appointment = Appointment::where('store_id', $storeId)
                ->with(['patient', 'doctorProfile.employee'])
                ->findOrFail($request->appointment_id);
            $patient         = $appointment->patient;
            $doctorProfileId = $appointment->doctor_profile_id;
        } elseif ($request->filled('service_request_id')) {
            $serviceRequest = ServiceRequest::with('item')->findOrFail($request->service_request_id);
            $patient = Patient::where('store_id', $storeId)
                ->where('user_id', $serviceRequest->user_id)
                ->first();
            $doctorProfileId = $serviceRequest->preferred_doctor_id;
        } elseif ($request->filled('patient_id')) {
            $patient = Patient::where('store_id', $storeId)->findOrFail($request->patient_id);
        }

        $patients = Patient::where('store_id', $storeId)
            ->select('id', 'name', 'patient_uid')
            ->orderBy('name')
            ->get();

        $appointments = collect();
        if (!$appointment && !$serviceRequest) {
            $appointments = Appointment::where('store_id', $storeId)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->with(['patient:id,name,patient_uid', 'doctorProfile.employee'])
                ->latest('appointment_date')
                ->limit(100)
                ->get();
        }

        return view('hmis::vendor.prescription.create', compact(
            'doctors', 'patients', 'appointments', 'appointment', 'serviceRequest',
            'patient', 'doctorProfileId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|integer',
            'doctor_profile_id' => 'required|integer',
            'diagnosis'         => 'nullable|string|max:1000',
            'notes'             => 'nullable|string|max:2000',
            'follow_up_date'    => 'nullable|date|after_or_equal:today',
            'medicines'         => 'nullable|array',
            'medicines.*.medicine_name' => 'required_with:medicines|string|max:200',
        ]);

        DB::beginTransaction();
        try {
            $rx = Prescription::create([
                'store_id'           => $this->storeId(),
                'patient_id'         => $request->patient_id,
                'doctor_profile_id'  => $request->doctor_profile_id,
                'appointment_id'     => $request->appointment_id ?: null,
                'service_request_id' => $request->service_request_id ?: null,
                'diagnosis'          => $request->diagnosis,
                'notes'              => $request->notes,
                'follow_up_date'     => $request->follow_up_date ?: null,
                'is_finalized'       => $request->has('finalize'),
                'created_by'         => $this->currentUserId(),
                'created_by_type'    => $this->currentUserType(),
            ]);

            foreach (($request->medicines ?? []) as $med) {
                if (empty(trim($med['medicine_name'] ?? ''))) continue;
                PrescriptionItem::create([
                    'prescription_id'   => $rx->id,
                    'medicine_name'     => $med['medicine_name'],
                    'inventory_item_id' => $med['inventory_item_id'] ?: null,
                    'dosage'            => $med['dosage'] ?? null,
                    'frequency'         => $med['frequency'] ?? null,
                    'duration'          => $med['duration'] ?? null,
                    'instructions'      => $med['instructions'] ?? null,
                    'quantity'          => $med['quantity'] ?? null,
                ]);
            }

            DB::commit();

            $action = $request->has('finalize') ? 'finalized' : 'created';
            $rx->load('patient');
            $patName = $rx->patient?->name . ' (' . $rx->patient?->patient_uid . ')';
            \App\Models\HospitalActivityLog::record(
                $this->storeId(), 'prescription', $rx->id, $action,
                "Prescription #{$rx->id} {$action} for {$patName}" . ($rx->appointment_id ? " (Appointment #{$rx->appointment_id})" : ''),
                ['is_finalized' => $rx->is_finalized]
            );

            Toastr::success('Prescription saved successfully');

            if ($request->appointment_id) {
                return Redirect::route('vendor.appointment.show', $request->appointment_id)
                    ->with('rx_saved', $rx->id);
            }
            return Redirect::route('vendor.prescription.show', $rx->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed to save: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id)
    {
        if (!auth('vendor')->check() && !hasPermission('prescription', 'print')) abort(403);
        $rx = Prescription::where('store_id', $this->storeId())
            ->with(['patient', 'doctorProfile.employee', 'items', 'store', 'appointment'])
            ->findOrFail($id);

        $canEditRx = $this->canEdit($rx);

        return view('hmis::vendor.prescription.show', compact('rx', 'canEditRx'));
    }

    public function edit($id)
    {
        if (!auth('vendor')->check() && !hasPermission('prescription', 'edit')) abort(403);
        $storeId = $this->storeId();
        $rx = Prescription::where('store_id', $storeId)->with('items')->findOrFail($id);

        if (!$this->canEdit($rx)) {
            Toastr::error('You can only edit prescriptions you created.');
            return Redirect::route('vendor.prescription.show', $id);
        }

        $doctors  = DoctorProfile::where('store_id', $storeId)->with('employee')->get();
        $patients = Patient::where('store_id', $storeId)->select('id', 'name', 'patient_uid')->orderBy('name')->get();

        return view('hmis::vendor.prescription.edit', compact('rx', 'doctors', 'patients'));
    }

    public function dispenseQueue(Request $request)
    {
        $storeId = $this->storeId();

        $preset = $request->get('date_range', 'today');
        $custom = $request->get('custom_date_range');
        $range  = Helpers::calculatePresetDates($preset, $custom);
        $from   = $range['start']->toDateString();
        $to     = $range['end']->toDateString();

        $query = Prescription::where('store_id', $storeId)
            ->where('is_finalized', true)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['patient', 'doctorProfile.employee', 'items.inventoryItem'])
            ->latest();

        if ($request->filled('patient')) {
            $q = $request->patient;
            $query->whereHas('patient', fn($s) => $s->where('name', 'like', "%{$q}%")
                ->orWhere('patient_uid', 'like', "%{$q}%"));
        }

        if ($request->get('filter') !== 'all') {
            $query->whereHas('items', fn($s) => $s->where('dispensed', false));
        }

        $prescriptions = $query->paginate(20)->withQueryString();

        return view('hmis::vendor.prescription.dispense_queue', compact('prescriptions', 'preset', 'from', 'to'));
    }

    public function dispenseExport(Request $request)
    {
        if (!auth('vendor')->check() && !hasPermission('pharmacy_dispense_queue', 'export')) abort(403);
        $storeId = $this->storeId();
        $preset  = $request->get('date_range', 'today');
        $custom  = $request->get('custom_date_range');
        $range   = Helpers::calculatePresetDates($preset, $custom);
        $from    = $range['start']->toDateString();
        $to      = $range['end']->toDateString();

        $query = Prescription::where('store_id', $storeId)
            ->where('is_finalized', true)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['patient', 'doctorProfile.employee', 'items'])
            ->latest();

        if ($request->filled('patient')) {
            $q = $request->patient;
            $query->whereHas('patient', fn($s) => $s->where('name', 'like', "%{$q}%")
                ->orWhere('patient_uid', 'like', "%{$q}%"));
        }
        if ($request->get('filter') !== 'all') {
            $query->whereHas('items', fn($s) => $s->where('dispensed', false));
        }

        $headings = ['#', 'Patient', 'UID', 'Doctor', 'Date', 'Total Medicines', 'Dispensed', 'Pending'];
        $data = $query->get()->map(fn($rx) => [
            $rx->id,
            $rx->patient?->name,
            $rx->patient?->patient_uid,
            'Dr. ' . trim(($rx->doctorProfile?->employee?->f_name ?? '') . ' ' . ($rx->doctorProfile?->employee?->l_name ?? '')),
            $rx->created_at->format('d M Y'),
            $rx->items->count(),
            $rx->items->where('dispensed', true)->count(),
            $rx->items->where('dispensed', false)->count(),
        ])->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data, $headings),
            'dispense_queue_' . $from . '_' . $to . '.xlsx'
        );
    }

    public function dispenseShow($id)
    {
        if (!auth('vendor')->check() && !hasPermission('pharmacy_dispense_queue', 'dispense')) abort(403);
        $storeId = $this->storeId();
        $rx = Prescription::where('store_id', $storeId)
            ->where('is_finalized', true)
            ->with(['patient', 'doctorProfile.employee', 'items.inventoryItem'])
            ->findOrFail($id);

        $pharmacyInvoices = ManualInvoice::where('vendor_id', $storeId)
            ->where('bill_to', $rx->patient_id)
            ->where('bill_to_type', 'patient')
            ->whereJsonContains('meta->source', 'pharmacy')
            ->orderByDesc('created_at')
            ->get();

        return view('hmis::vendor.prescription.dispense_form', compact('rx', 'pharmacyInvoices'));
    }

    public function dispenseProcess(Request $request, $id)
    {
        $storeId = $this->storeId();
        $rx = Prescription::where('store_id', $storeId)
            ->where('is_finalized', true)
            ->with('items.inventoryItem', 'patient')
            ->findOrFail($id);

        $request->validate([
            'items'            => 'required|array',
            'items.*.dispense' => 'nullable|boolean',
            'items.*.qty'      => 'nullable|integer|min:1',
            'tax_type'         => 'nullable|in:gst,non-gst',
        ]);

        $taxType = $request->input('tax_type', 'non-gst');

        DB::beginTransaction();
        try {
            $billLines = [];

            foreach ($request->items as $itemId => $data) {
                if (empty($data['dispense'])) continue;

                $rxItem = $rx->items->firstWhere('id', $itemId);
                if (!$rxItem || $rxItem->dispensed) continue;

                $qty = (int) ($data['qty'] ?? $rxItem->quantity ?? 1);

                if ($rxItem->inventory_item_id && $rxItem->inventoryItem) {
                    $invItem = $rxItem->inventoryItem;
                    $invItem->stock = max(0, ($invItem->stock ?? 0) - $qty);
                    $invItem->save();
                }

                $rxItem->update([
                    'dispensed'     => true,
                    'dispensed_at'  => now(),
                    'dispensed_qty' => $qty,
                ]);

                $price = $rxItem->inventoryItem?->selling_price ?? 0;
                if ($price > 0) {
                    $billLines[] = [
                        'name'     => $rxItem->medicine_name,
                        'qty'      => $qty,
                        'price'    => $price,
                        'inv_id'   => $rxItem->inventory_item_id,
                        'hsn'      => $rxItem->inventoryItem?->hsn ?? null,
                        'gst_rate' => $taxType === 'gst' ? ($rxItem->inventoryItem?->gst_rate ?? 0) : 0,
                    ];
                }
            }

            if (!empty($billLines)) {
                $this->appendToPharmacyInvoice($rx, $billLines, $storeId, $taxType);
            }

            DB::commit();
            Toastr::success('Medicines dispensed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Dispense failed: ' . $e->getMessage());
        }

        return Redirect::route('vendor.prescription.dispense.show', $id);
    }

    private function appendToPharmacyInvoice(Prescription $rx, array $lines, int $storeId, string $taxType = 'non-gst'): void
    {
        $patientId = $rx->patient_id;

        $invoice = ManualInvoice::where('vendor_id', $storeId)
            ->where('bill_to', $patientId)
            ->where('bill_to_type', 'patient')
            ->where('payment_status', 'unpaid')
            ->where('tax_type', $taxType)
            ->whereJsonContains('meta->source', 'pharmacy')
            ->whereDate('created_at', today())
            ->first();

        if (!$invoice) {
            $store  = \App\Models\Store::find($storeId);
            $serial = Helpers::generateInvoiceId('P', true, null, $taxType, $store);
            $invoice = ManualInvoice::create([
                'vendor_id'      => $storeId,
                'bill_to'        => $patientId,
                'bill_to_type'   => 'patient',
                'user_type'      => 'hospital_patient',
                'invoice_id'     => $serial,
                'invoice_serial' => (int) substr($serial, strrpos($serial, '_') + 1),
                'invoice_date'   => today(),
                'payment_status' => 'unpaid',
                'tax_type'       => $taxType,
                'total_amount'   => 0,
                'financial_year' => _currentFinancialYear(),
                'meta'           => ['source' => 'pharmacy', 'prescription_id' => $rx->id],
            ]);
        }

        $total = 0;
        foreach ($lines as $line) {
            $base      = $line['price'] * $line['qty'];
            $gstRate   = $taxType === 'gst' ? (float) $line['gst_rate'] : 0;
            $lineTotal = $taxType === 'gst' ? round($base * (1 + $gstRate / 100), 2) : $base;
            $total    += $lineTotal;

            InvoiceItem::create([
                'rand_invoice_id'   => $invoice->invoice_id,
                'manual_invoice_id' => $invoice->id,
                'name'              => $line['name'],
                'price'             => $line['price'],
                'qty'               => $line['qty'],
                'inv_id'            => $line['inv_id'],
                'hsn'               => $line['hsn'],
                'gst_status'        => 'excluding',
                'tax'               => $gstRate,
            ]);
        }

        $invoice->increment('total_amount', $total);

        try {
            $invoice->refresh();
            $data = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $data['pdf']]);
        } catch (\Throwable) {
        }
    }

    public function searchMedicines(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $items = InventoryItem::where('store_id', $this->storeId())
            ->where('item_type', 'product')
            ->where('item_name', 'like', "%{$q}%")
            ->select('id', 'item_name', 'unit', 'selling_price')
            ->limit(15)
            ->get();

        return response()->json($items->map(fn($i) => [
            'id'    => $i->id,
            'name'  => $i->item_name,
            'price' => $i->selling_price,
        ]));
    }

    public function update(Request $request, $id)
    {
        $storeId = $this->storeId();
        $rx = Prescription::where('store_id', $storeId)->findOrFail($id);

        if (!$this->canEdit($rx)) {
            Toastr::error('You can only edit prescriptions you created.');
            return Redirect::route('vendor.prescription.show', $id);
        }

        $request->validate([
            'diagnosis'      => 'nullable|string|max:1000',
            'notes'          => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
            'medicines'      => 'nullable|array',
            'medicines.*.medicine_name' => 'required_with:medicines|string|max:200',
        ]);

        DB::beginTransaction();
        try {
            $rx->update([
                'diagnosis'      => $request->diagnosis,
                'notes'          => $request->notes,
                'follow_up_date' => $request->follow_up_date ?: null,
                'is_finalized'   => $request->has('finalize'),
            ]);

            $rx->items()->delete();
            foreach (($request->medicines ?? []) as $med) {
                if (empty(trim($med['medicine_name'] ?? ''))) continue;
                PrescriptionItem::create([
                    'prescription_id'   => $rx->id,
                    'medicine_name'     => $med['medicine_name'],
                    'inventory_item_id' => $med['inventory_item_id'] ?: null,
                    'dosage'            => $med['dosage'] ?? null,
                    'frequency'         => $med['frequency'] ?? null,
                    'duration'          => $med['duration'] ?? null,
                    'instructions'      => $med['instructions'] ?? null,
                    'quantity'          => $med['quantity'] ?? null,
                ]);
            }

            DB::commit();

            $action = $request->has('finalize') ? 'finalized' : 'updated';
            \App\Models\HospitalActivityLog::record(
                $storeId, 'prescription', $rx->id, $action,
                "Prescription #{$rx->id} {$action}",
                ['is_finalized' => $rx->is_finalized]
            );

            Toastr::success('Prescription updated');

            if ($rx->appointment_id) {
                return Redirect::route('vendor.appointment.show', $rx->appointment_id)
                    ->with('rx_saved', $rx->id);
            }
            return Redirect::route('vendor.prescription.show', $rx->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Failed: ' . $e->getMessage());
            return back()->withInput();
        }
    }
}
