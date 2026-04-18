<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AcceptedServiceRequest;
use App\Models\Department;
use App\Models\EmployeeRole;
use App\Models\InventoryGatepass;
use App\Models\InventoryItem;
use App\Models\JobCard;
use App\Models\ReceivableReceipt;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreTask;
use App\Models\Vendor;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Faker\Extension\Helper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class LibraryController extends Controller
{
    public function select_template(Request $request)
    {
        $value  = $request->value;
        $action = $request->action ?? 'pos_token_template';

        $allowed = ['pos_token_template', 'invoice_template'];
        if (!in_array($action, $allowed)) {
            $action = 'pos_token_template';
        }

        StoreConfig::updateOrInsert(['store_id' => Helpers::get_store_id()], [
            $action => $value,
        ]);
        Toastr::success('Updated Successfully');
        return back();
    }
    public function index(Request $request)
    {
        $store_config = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        return view('admin-views.library.index', compact('store_config'));
    }
    public function job_card(Request $request, $action = 'view', $task_id = null, $lead_id = null)
    {
        $data['store'] = Helpers::get_store_data();
        $data['task_id'] = '';
        if ($task_id) {
            $task = StoreTask::where('id', $task_id)->first();
            $data['task_id'] = $task ? $task->task_id : '';
        }
        $data['job_card_number'] = Helpers::generate_jobcard_number($data['store'], $action);
        if ($request->customer) {
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $request->customer)
                ->first();
        }
        if ($request->employee_id) {
            $data['employee'] = VendorEmployee::where('id', $request->employee_id)
                ->first();
        } else {
            $vendor_id = Helpers::get_store_data()->vendor_id;
            $data['employee'] = Vendor::where('id', $vendor_id)->first();
        }
        $labels = $request->header_label ?? [];
        $fields = $request->header_field ?? [];
        $columns['title'] = $request->title ?? '';

        foreach ($labels as $index => $label) {
            $fieldValue = $fields[$index] ?? '';

            $columns[$label] = $fieldValue;
        }
        $columns['remarks'] = $request->description ?? '';
        $data['columns'] = $columns;
        $data['payment_method'] = $request->payment_method;
        // prx($data['payment_method']);

        $data['charges'] = [];

        // Store optional charges
        if ($request->service_charges) {
            $data['charges']['service_charges'] = (float) $request->service_charges;
        }
        if ($request->visit_charges) {
            $data['charges']['visit_charges'] = (float) $request->visit_charges;
        }

        $data['discount'] = (float) ($request->discount ?? 0);
        $items = $request->name ?? [];
        $sparePartsPrice = 0;

        foreach ($items as $index => $item) {
            $unitPrice = (float) ($request->price[$index] ?? 0);
            $quantity = (float) ($request->qty[$index] ?? 0);
            $tax = (float) ($request->tax[$index] ?? 0);

            if (!empty($request->inventory_item_id[$index])) {
                $invItem = InventoryItem::find($request->inventory_item_id[$index]);
                if ($invItem) {
                    $unitPrice = $unitPrice ?: $invItem->selling_price;
                }
            }

            $lineTotal = _taxIncludedPrice($unitPrice * $quantity, $tax);
            $sparePartsPrice += $lineTotal;

            $items[$index] = [
                'inv_item_id' => $request->inventory_item_id[$index] ?? 0,
                'name' => $item,
                'unitprice' => $unitPrice,
                'tax' => $tax,
                'quantity' => $quantity,
            ];
        }

        $chargesTotal = array_sum($data['charges']);
        $totalAmount = $chargesTotal + $sparePartsPrice - $data['discount'];

        $data['total_amount'] = number_format($totalAmount, 3, '.', '');

        $data['charges']['spare_parts'] = $sparePartsPrice;
        $data['items'] = $items;
        $data['status'] = 0;
        if ($action == 'save') {
            if (!Storage::disk('public')->exists('store/jobcards/')) {
                Storage::disk('public')->makeDirectory('store/jobcards/');
            }
            $fileName = 'job_card_' . time() . '.pdf';
            //save to db 
            $jobCard = new JobCard();
            $jobCard->store_id = $data['store']->id;
            $jobCard->task_id = $task_id ?? null;
            $jobCard->lead_id = $lead_id ?? null;
            $jobCard->pdf =  $fileName ?? null;
            $jobCard->spare_parts =  json_encode($items);
            $jobCard->custom_fields =  json_encode($data['columns']);
            $jobCard->charges = json_encode($data['charges']);
            $jobCard->discount = $data['discount'];
            $jobCard->job_card_number = $data['job_card_number'];
            $jobCard->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
            $jobCard->save();
            $data['job_id'] = $jobCard->id;
        }

        $jobcard = View::make('document_templates/job_card', compact('data'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($jobcard);

        if ($action == 'save') {
            $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/jobcards', $fileName);
            return ['success' => true, 'file_name' => $fileName,  'url'  => asset('storage/app/public/store/jobcards/' . $fileName)];
        } else {
            $mpdf->Output();
        }
    }
    public function recievable_reciept_create(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = Helpers::get_store_data();
        $rls = EmployeeRole::where('store_id', $store_id)->get();
        $departments = Department::where('vendor_id', $store_id)->where('status', '1')->get();
        $staff = VendorEmployee::with('role')->where('store_id', $store_id)->where('status', 1)->get();
        $data['tasks'] = StoreTask::where('store_id', $store_id)->whereNull('parent_id')->get();
        $data['leads'] = AcceptedServiceRequest::where('vendor_id', $store_id)->get();

        return view('admin-views.library.receivable_receipt', compact('data',  'staff', 'rls', 'departments'));
    }
    public function jobcard_create(Request $request)
    {
        $store_id = Helpers::get_store_id();
        $store = Helpers::get_store_data();
        $rls = EmployeeRole::where('store_id', $store_id)->get();
        $departments = Department::where('vendor_id', $store_id)->where('status', '1')->get();
        $staff = VendorEmployee::with('role')->where('store_id', $store_id)->where('status', 1)->get();
        $data['tasks'] = StoreTask::where('store_id', $store_id)->whereNull('parent_id')->get();
        $data['leads'] = AcceptedServiceRequest::where('vendor_id', $store_id)->get();
        return view('admin-views.library.jobcard', compact('data', 'staff', 'rls', 'departments'));
    }
    public function check_serial_number(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receipt_serial' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $store = Helpers::get_store_data();
        $exists = ReceivableReceipt::where('store_id', $store->id)
            ->where('receipt_serial', $request->receipt_serial)
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'Receipt serial number already exists'], 422);
        }
        return response()->json(['success' => 'Receipt serial number is available']);
    }

    public static function validateRRSerial(Request $request)
    {
        return ReceivableReceipt::where('receipt_serial', $request->receipt_serial)->where('store_id', Helpers::get_store_id())
            ->exists();
    }

    public function recievable_store(Request $request, $action = 'view', $task_id = null, $lead_id = null)
    {
        // prx($request->all());


        $request->validate([
            'task_id' => 'required_without:lead_id|nullable',
            'lead_id' => 'required_without:task_id|nullable',
        ]);
        if ($request->has('task_id')) {
            $task_id = $request->task_id;
        }
        if ($request->has('lead_id')) {
            $lead_id = $request->lead_id;
        }
        $exists =  $this->validateRRSerial($request);
        if ($exists == 'exists') {
            Toastr::error('Receipt serial already used.');
            return back();
        }

        $save =  $this->recievable_reciept($request, 'save',  $task_id, $lead_id);
        $url = $save['url'] ?? '';
        // echo 'fjsd'; 
        return redirect()->to($url);
    }
    public function job_card_store(Request $request, $action = 'view', $task_id = null, $lead_id = null)
    {
        $request->validate([
            'task_id' => 'required_without:lead_id|nullable',
            'lead_id' => 'required_without:task_id|nullable',
        ]);
        if ($request->has('task_id')) {
            $task_id = $request->task_id;
        }
        if ($request->has('lead_id')) {
            $lead_id = $request->lead_id;
        }
        $exists =  $this->validateRRSerial($request);
        if ($exists == 'exists') {
            Toastr::error('Receipt serial already used.');
            return back();
        }

        $save =  $this->job_card($request, 'save',  $task_id, $lead_id);
        $url = $save['url'] ?? '';
        return redirect()->to($url);
    }

    public function recievable_reciept(Request $request, $action = 'view', $task_id = null, $lead_id = null)
    {

        $exists = self::validateRRSerial($request);
        // prx($request->all());
        if ($exists) {
            Toastr::error('Receipt serial already used.');
            return redirect()->back();
        }
        $data['task_id'] = '';
        if ($task_id) {
            $task = StoreTask::where('id', $task_id)->first();
            $data['task_id'] = $task ? $task->task_id : '';
        }

        $data['store'] = Helpers::get_store_data();

        $serial_number =  Helpers::generate_RR_serial_number($data['store'], $action, $request->receipt_serial ?? null);
        $reciept_prefix  = $request->receipt_prefix ?? Helpers::generate_RR_prefix($data['store'], $action);


        $data['receipt_number'] = $reciept_prefix . $serial_number;
        if ($request->customer) {
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $request->customer)
                ->first();
        } else if (isset($task) && $task->user_id) {
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $task->user_id)
                ->first();
        }
        $received_by = $request->received_by ?? $request->employee_id;
        $employee_id = $request->employee_id ?? $request->received_by;
        $emp =  $received_by  ?? $employee_id;
        if ($received_by || $employee_id) {
            $data['employee'] = VendorEmployee::where('id', $emp)
                ->first();
        } else {
            $vendor_id = Helpers::get_store_data()->vendor_id;
            $data['employee'] = Vendor::where('id', $vendor_id)->first();
        }

        $items = $request->pr_name ?? [];
        $sparePartsPrice = 0;
        foreach ($items as $index => $item) {
            // Single image upload
            $image = $request->hasFile("image.$index")
                ? Helpers::upload('store/recivable-receipts/images/', 'png', $request->file("image.$index"))
                : null;

            $webcam_files = [];

            if ($request->hasFile("webcam_file.$index")) {
                $files = $request->file("webcam_file.$index");
            } elseif ($request->hasFile("webcam_file.$index.*")) {
                $files = $request->file("webcam_file.$index.*");
            }

            if (!empty($files)) {
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $extension = $file->getClientOriginalExtension() ?: 'png';
                        $webcam_files[] = Helpers::upload('store/recivable-receipts/images/', $extension, $file);
                    }
                }
            }


            $items[$index] = [
                'name' => $item,
                'webcam_file' => $webcam_files,
                'image' => $image,
                'brand' => $request->brand[$index] ?? '',
                'serial_no' => $request->serial_no[$index] ?? '',
                'accessories_given' => $request->accessories_given[$index] ?? '',
                'value' => $request->value[$index] ?? 0,
                'issue' => $request->issue_for[$index] ?? '',
            ];
        }

        $data['items'] = $items;
        // prx($data['items']);
        $data['has_value'] = false;
        if (!empty(array_filter($request->value, fn($v) => trim($v) !== ''))) {
            $data['has_value'] = true;
        }


        if ($action == 'save') {
            if (!Storage::disk('public')->exists('store/recivable-receipts/')) {
                Storage::disk('public')->makeDirectory('store/recivable-receipts/');
            }
            $fileName = 'receipt_' . time() . '.pdf';
            $reciept = new ReceivableReceipt();
            $reciept->type =  $request->rr_type;
            $reciept->task_id =  $task_id;
            $reciept->lead_id =  $lead_id;
            $reciept->received_by =   $received_by;
            $reciept->receipt_number =  $reciept_prefix  . $serial_number;
            $reciept->receipt_serial = $serial_number;
            $reciept->store_id = 0;
            $reciept->client_id = $data['client']->id ?? null;
            $reciept->employee_id = $employee_id;
            $reciept->created_by = auth('admin')->id() ;
            $reciept->items = $data['items'] ? json_encode($data['items']) : null;
            $reciept->pdf = $fileName;

            $reciept->save();

            $data['rr_id'] = $reciept->id;
        }



        if ($request->rr_type == 'returnable') {
            // $jobcard = View::make('document_templates/template_2', compact('data'))->render();
            $jobcard = View::make('document_templates/returnable_reciept', compact('data'))->render();
        } else {
            $jobcard = View::make('document_templates/non_returnable_reciept', compact('data'))->render();
        }
        // return $jobcard;

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($jobcard);

        if ($action == 'save') {
            $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/recivable-receipts', $fileName);

            return ['success' => true, 'file_name' => $fileName, 'fileUrl' => $fileUrl, 'url'  => asset('storage/app/public/store/recivable-receipts/' . $fileName)];
        } else {
            $mpdf->Output();
        }
    }
    public function inventory_gatepass_delete(Request $request, $id){
        $gp = InventoryGatepass::findOrFail($id);
        if($gp->pdf){
            Helpers::delete_file('inventory/gatepass/',  $gp->pdf);
        }
        $gp->delete();
        Toastr::success("Deleted Gatepass Successfully");
        return back();

    }
}
