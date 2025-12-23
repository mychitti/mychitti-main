<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\InventoryGatepass;
use App\Models\JobCard;
use App\Models\ReceivableReceipt;
use App\Models\ServiceReport;
use App\Models\Store;
use App\Models\StoreCustomer;
use App\Models\StoreTask;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class DocumentsController extends Controller
{



    public function recievable_reciepts_delete(Request $request, $id)
    {
        $receipt = ReceivableReceipt::findOrFail($id);
        if ($receipt->pdf) {
            Helpers::delete_file('store/recivable-receipts/', $receipt->pdf);
        }
        $receipt->delete();

        Toastr::success('Deleted Successfully');
        return redirect()->back();
    }
    function base64DecodeUnicode($str)
    {
        $str = strtr($str, '-_', '+/');

        $padding = strlen($str) % 4;
        if ($padding > 0) {
            $str .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($str);

        $decoded = preg_replace_callback('/[\x00-\xFF]/', function ($m) {
            return '%' . strtoupper(bin2hex($m[0]));
        }, $decoded);

        return urldecode($decoded);
    }

    public function service_report(Request $request, $action = 'view', $task_id = null, $lead_id = null)
    {
        // prx($request->all());
        $data['store'] = Helpers::get_store_data();
        $data['task_id'] = '';
        $data['task'] = null;
        if ($task_id) {
            $task = StoreTask::where('id', $task_id)->first();
            if ($task) {
                $reportExists = ServiceReport::where('task_id', $task->id)->exists();
                if ($reportExists) {
                    Toastr::error("This task already has a service report");
                    return back();
                }
            }
            $data['task_id'] = $task ? $task->task_id : '';
            $data['task'] = $task ? $task : null;
        }
        if ($request->customer || $data['task']?->user_id) {
            $customer_id = $request->customer ?? $data['task']?->user_id;
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $customer_id)
                ->first();
        }
        if ($request->employee_id) {
            $data['employee'] = VendorEmployee::where('id', $request->employee_id)
                ->first();
        } else {
            $vendor_id = Helpers::get_store_data()->vendor_id;
            $data['employee'] = Vendor::where('id', $vendor_id)->first();
        }


        if ($action == 'save') {
            if (!Storage::disk('public')->exists('store/service_reports/')) {
                Storage::disk('public')->makeDirectory('store/service_reports/');
            }
            $fileName = 'service_report_' . time() . '.pdf';

            $content = $request->content ? $this->base64DecodeUnicode($request->content) : null;
            $engineer_remark = isset($request->engineer_remark) ? $this->base64DecodeUnicode($request->engineer_remark) : null;
            $customer_remark = isset($request->customer_remark) ? $this->base64DecodeUnicode($request->customer_remark) : null;
            $parts_used = isset($request->parts_used) ? $this->base64DecodeUnicode($request->parts_used) : null;
            $parts_recommended = isset($request->parts_recommended) ? $this->base64DecodeUnicode($request->parts_recommended) : null;

            //  prx($engineer_remark);
            //save to db 
            $serviceReport = new ServiceReport();
            $serviceReport->store_id = $data['store']->id;
            $serviceReport->task_id = $task_id ?? null;
            $serviceReport->lead_id = $lead_id ?? null;
            $serviceReport->pdf =  $fileName ?? null;
            $serviceReport->content =  $content;
            $serviceReport->engineer_remark =  $engineer_remark;
            $serviceReport->customer_remark =  $customer_remark;
            $serviceReport->parts_used =  $parts_used;
            $serviceReport->parts_recommended =  $parts_recommended;
            $serviceReport->service_date =  $request->service_date ?? NOW();
            $serviceReport->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
            $serviceReport->save();
        }

        $serviceReportTemplate = View::make('document_templates/service_report', compact('data', 'serviceReport'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($serviceReportTemplate);

        if ($action == 'save') {
            $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/service_reports', $fileName);
            return ['success' => true, 'file_name' => $fileName,  'url'  => asset('storage/app/public/store/service_reports/' . $fileName)];
        } else {
            $mpdf->Output();
        }
    }
    public function add_gatepass(Request $request, $gp_type)
    {
        $storeId = Helpers::get_store_id();
        $staff = VendorEmployee::where('store_id', $storeId)->get();
        $routes= InventoryGatepass::where('store_id', $storeId)->distinct('route')->pluck('route')->toArray();
        $vehicles= InventoryGatepass::where('store_id', $storeId)->distinct('vehicle_number')->pluck('vehicle_number')->toArray();
        // prx($routes);
        return view('vendor-views.documents.gatepass.index', compact('staff','routes', 'gp_type', 'vehicles'));
    }
    public function store_gatepass(Request $request)
    {
        $controller = new InventoryGatepassController();
        return $controller->store($request);
    }
    public function service_report_store(Request $request, $action = 'view', $task_id = null, $lead_id = null)
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

        $save =  $this->service_report($request, 'save',  $task_id, $lead_id);
        $url = $save['url'] ?? '';
        return ['success' => true,  'url'  => $url];
    }
    public function job_card_approve(Request $request, $id)
    {
        $jobCard = JobCard::find($id);
        $task = StoreTask::where('id', $jobCard->task_id)->first();

        $data['store'] = Store::withoutGlobalScopes()->where('id', $jobCard->store_id)->first();
        $data['job_card_number'] = $jobCard->job_card_number;

        if ($task->user_id) {
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $request->customer)
                ->first();
        }
        if ($task->employee_id) {
            $data['employee'] = VendorEmployee::where('id', $task->employee_id)
                ->first();
        } else {
            $vendor_id = $data['store']->vendor_id;
            $data['employee'] = Vendor::where('id', $vendor_id)->first();
        }

        $columns['remarks'] = $task->description ?? '';
        $data['status'] = $jobCard->status;
        $data['columns'] = json_decode($jobCard->custom_fields, true);
        $data['payment_method'] = $jobCard->payment_method;
        $data['charges'] = json_decode($jobCard->charges, true);
        $data['discount'] = $jobCard->discount;
        $items = json_decode($jobCard->spare_parts, true);
        $items_total = 0;
        $discount = 0;

        if (is_array($items)) {
            foreach ($items as $item) {
                $unitPrice = isset($item['unitprice']) ? floatval($item['unitprice']) : 0;
                $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                $items_total += $unitPrice * $quantity;

                if (isset($item['discount'])) {
                    $discount += floatval($item['discount']); // total discount
                }
            }
        }
        $charges_total = is_array($data['charges']) ? array_sum($data['charges']) : 0;

        $data['total_amount'] = number_format($charges_total + $items_total - $discount, 2);
        $data['items'] = $items;
        $data['charges']['spare_parts'] = $items_total;


        if (!Storage::disk('public')->exists('store/jobcards/')) {
            Storage::disk('public')->makeDirectory('store/jobcards/');
        }
        Helpers::delete_file('store/jobcards/', $jobCard->pdf);

        $fileName = 'job_card_' . time() . '.pdf';
        $jobCard->pdf =  $fileName;
        $jobCard->status = 1;
        $jobCard->save();

        $data['job_id'] = $jobCard->id;

        $jobcard = View::make('document_templates/job_card', compact('data'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($jobcard);

        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/jobcards', $fileName);
        return redirect()->route('approve.success');
    }
    public function recievable_reciepts_approve(Request $request, $id)
    {
        return view('vendor-views.documents.approve_form', compact('id'));
    }
    public function rr_approve_submit(Request $request)
    {
        $id  = $request->id;
        $receivableRec = ReceivableReceipt::find($id);
        $task = StoreTask::where('id', $receivableRec->task_id)->first();

        $data['store'] = Store::withoutGlobalScopes()->where('id', $receivableRec->store_id)->first();

        $data['receipt_number'] = $receivableRec->receipt_number;
        if ($receivableRec->client_id) {
            $data['client'] = StoreCustomer::where('store_id', $data['store']->id)
                ->where('id', $receivableRec->client_id)
                ->first();
        }
        if ($receivableRec->employee_id) {
            $data['employee'] = VendorEmployee::where('id', $receivableRec->employee_id)
                ->first();
        } else {
            $vendor_id = $data['store']->vendor_id;
            $data['employee'] = Vendor::where('id', $vendor_id)->first();
        }

        $items = json_decode($receivableRec->items, true);
        $data['items'] = $items;

        $data['has_value'] = false;

        foreach ($items as $item) {
            if (!empty($item['value']) && $item['value'] > 0) {
                $data['has_value'] = true;
                break;
            }
        }

        if (!Storage::disk('public')->exists('store/recivable-receipts/')) {
            Storage::disk('public')->makeDirectory('store/recivable-receipts/');
        }

        Helpers::delete_file('store/recivable-receipts/', $receivableRec->pdf);

        $fileName = 'receipt_' . time() . '.pdf';
        $receivableRec->pdf = $fileName;
        $receivableRec->status = 1;
        $receivableRec->approved_by = $request->name ?? null;
        $receivableRec->approved_by_phone =  $request->phone ?? null;
        $receivableRec->save();

        $data['rr_id'] = $receivableRec->id;
        $data['approved'] = 1;
        $data['approved_by'] = $receivableRec->approved_by;
        $data['approved_phone'] = $receivableRec->approved_by_phone;





        if ($receivableRec->type == 'returnable') {
            $receivableRec = View::make('document_templates/returnable_reciept', compact('data'))->render();
        } else {
            $receivableRec = View::make('document_templates/non_returnable_reciept', compact('data'))->render();
        }
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($receivableRec);

        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/recivable-receipts', $fileName);

        return redirect()->route('approve.success');
    }
    public function job_card_delete(Request $request, $id)
    {
        $jobcard = JobCard::findOrFail($id);
        if ($jobcard->pdf) {
            Helpers::delete_file('store/jobcards/', $jobcard->pdf);
        }
        $jobcard->delete();
        return redirect()->back();


        Toastr::success('Deleted Successfully');
    }
    public function recievable_reciepts_list(Request $request)
    {
        $receipts = ReceivableReceipt::where('store_id', Helpers::get_store_id())->paginate(10);
        return view('vendor-views.documents.recievable_reciepts_list', compact('receipts'));
    }
    public function job_cards_list(Request $request)
    {
        $job_cards = JobCard::where('store_id', Helpers::get_store_id())->paginate(10);
        return view('vendor-views.documents.job_cards_list', compact('job_cards'));
    }
}
