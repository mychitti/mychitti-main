<?php

namespace App\CentralLogics;

require __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;
use App\Models\Order;
use App\Models\Zone;
use App\Models\AddOn;
use App\Models\Store;
use App\Models\Module;
use App\Models\Review;
use App\Models\Expense;
use App\Mail\PlaceOrder;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DMReview;
use App\Models\DataSetting;
use App\Models\Translation;
use Illuminate\Support\Str;
use App\Models\FlashSaleItem;
use Illuminate\Support\Carbon;
use App\Models\BusinessSetting;
use App\CentralLogics\StoreLogic;
use Illuminate\Support\Facades\DB;
use App\Mail\OrderVerificationMail;
use App\Models\AcceptedServiceRequest;
use App\Models\AdminAction;
use App\Models\CouponCondition;
use App\Models\DayBook;
use App\Models\FreeTrialHistory;
use App\Models\InventoryGatepass;
use App\Models\InventoryGatepassItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InvItemVariationDetail;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\ItemVariationDetail;
use App\Models\LedgerAccountType;
use App\Models\ManualInvoice;
use App\Models\NotificationMessage;
use App\Models\PosToken;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\ServiceCoupon;
use App\Models\ServiceInvoice;
use App\Models\ServiceRequest;
use App\Models\StoreAccount;
use App\Models\StoreBankTransaction;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreEnabledModule;
use App\Models\StoreTask;
use App\Models\StoreVoucher;
use App\Models\SubModule;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Laravelpkg\Laravelchk\Http\Controllers\LaravelchkController;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Illuminate\Http\File as HttpFile;

class Helpers
{
    public static function savePdfToPublic($mpdf, $relativePath, $fileName)
    {
        $tempPath = storage_path('app/tmp/' . $fileName);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $mpdf->Output($tempPath, 'F');
        return  Storage::disk('public')->putFileAs($relativePath, new HttpFile($tempPath), $fileName);

        @unlink($tempPath);

        return asset('storage/app/public/' . trim($relativePath, '/') . '/' . $fileName);
    }

    public static function alotServiceCoupon($service_id)
    {
        $service = ServiceRequest::with('acceptance')->find($service_id);

        if (
            !$service ||
            !$service->acceptance ||
            $service->acceptance->current_status !== 'Completed'
        ) {
            return false;
        }

        $completedCount = ServiceRequest::where('user_id', $service->user_id)
            ->whereHas('acceptance', function ($q) {
                $q->where('current_status', 'Completed');
            })
            ->count();

        // map booking count => coupon_key
        $couponMap = [
            1      => 'first_booking_completion',
            10     => '10_booking_completion',
            20     => '20_booking_completion',
            100000 => '100000_booking_completion',
        ];

        if (!isset($couponMap[$completedCount])) {
            return false;
        }

        $couponCondition = CouponCondition::with('coupon')
            ->where('coupon_key', $couponMap[$completedCount])
            ->first();

        if (!$couponCondition || !$couponCondition->coupon) {
            return false;
        }

        $coupon = $couponCondition->coupon;

        $customerIds = json_decode($coupon->customer_id, true);

        if (!is_array($customerIds)) {
            $customerIds = [];
        }

        if (in_array((string)$service->user_id, $customerIds, true)) {
            return false;
        }

        $customerIds[] = (string)$service->user_id;

        $coupon->customer_id = json_encode(array_values($customerIds));
        $coupon->save();

        return $coupon->id;
    }



    public static function createPendingAction(array $data)
    {
        $otp = rand(1000, 9999);

        AdminAction::create([
            'action_type' => $data['actionType'],
            'action_payload' => json_encode($data['payload']),
            'requested_by' => $data['requestedBy'],
            'otp' => $otp,
            'status' => 'pending',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP to master admin
        $admin_phone = BusinessSetting::where('key', 'phone')->first()->value ?? '8777966552';
        _send_confirmation_sms('otp', $admin_phone, $otp);
    }
    public static function generateInventoryReturnGatepass($gatepass)
    {
        $gatepass_items = InventoryGatepassItem::where('gatepass_id', $gatepass->id)->get();
        $gatePassNumber = $gatepass->gatepass_number;
        $driver_data = json_decode($gatepass->driver_data);

        $store = Helpers::get_store_data();
        $html = View::make('document_templates/inventory_return_gatepass', compact('gatepass_items', 'driver_data', 'gatepass', 'store', 'gatePassNumber'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->WriteHTML($html);
        // $mpdf->Output('Purchase_Gatepass.pdf', \Mpdf\Output\Destination::INLINE);


        $pdfName = ucfirst($gatepass->type) . '_Return_Gatepass_' . date('YmdHis') . rand(100000, 999999) . '.pdf';
        $fileUrl = Helpers::savePdfToPublic($mpdf, 'inventory/gatepass', $pdfName);

        $gatepass->return_pdf = $pdfName;
        $gatepass->save();

        return [
            'pdf' => $pdfName,
            'url' => asset('storage/app/public/inventory/gatepass') . '/' . $pdfName
        ];
    }
    public static function generateInventoryGatepass($invoice, $driver_data, $type, $request = null)
    {
        $gatepassNum = Helpers::invGatepassNumber($type);
        $gatePassNumber = $gatepassNum['gp_id'];
        $gatePassSerial = $gatepassNum['gp_serial'];

        $pass = new InventoryGatepass();
        $pass->type = $type;
        $pass->gatepass_number = $gatePassNumber;
        $pass->serial_number = $gatePassSerial;
        $pass->store_id = Helpers::get_store_id();
        $pass->invoice_id = $invoice ? $invoice->invoice_id : null;
        $pass->driver_data = json_encode($driver_data);
        $pass->route = $request ? $request->route : null;
        $pass->vehicle_number = $request ? $request->vehicle_number : null;
        $pass->save();
        $total_amount  = 0;

        // GATEPASS FOR INVOICE
        if ($invoice) {
            $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->get();
            foreach ($invoice_items as $key => $value) {
                $gpItem = new InventoryGatepassItem();
                $gpItem->gatepass_id = $pass->id;
                $gpItem->name = $value->name;
                $gpItem->qty = $value->qty;
                $gpItem->unit = $value->unit;
                $gpItem->price = $value->price;
                $gpItem->tax =  $value->tax;
                $gpItem->hsn =  $value->hsn;
                $gpItem->save();
            }
            $total_amount = $invoice->total_amount;
        }
        // GATEPASS WITHOUT INOVICE 
        else {
            if ($request->has('item_name') && !empty('item_name')) {
                foreach ($request->item_name as $key => $value) {
                    $gpItem = new InventoryGatepassItem();
                    $gpItem->gatepass_id = $pass->id;
                    $gpItem->name = $value;
                    $gpItem->qty = $request->item_qty[$key];
                    $gpItem->unit = $request->item_unit[$key];
                    $gpItem->price = $request->item_price[$key];
                    $gpItem->save();

                    $total_amount += ($request->item_price[$key] * $request->item_qty[$key]);
                }
            }
        }
        $pass->total_amount = $total_amount;
        $pass->save();
        $gpItems = InventoryGatepassItem::where('gatepass_id', $pass->id)->get();
        // prx($driver_data);
        $store = Helpers::get_store_data();
        $html = View::make('document_templates/inventory_gatepass', compact('pass', 'gpItems', 'driver_data', 'pass', 'store', 'gatePassNumber'))->render();
        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
        ]);
        $mpdf->WriteHTML($html);
        // $mpdf->Output('Purchase_Gatepass.pdf', \Mpdf\Output\Destination::INLINE);


        $pdfName = ucfirst($pass->type)  . '_Gatepass_' . date('YmdHis') . rand(100000, 999999) . '.pdf';
        $fileUrl = Helpers::savePdfToPublic($mpdf, 'inventory/gatepass', $pdfName);

        $pass->pdf = $pdfName;
        $pass->save();

        return [
            'pdf' => $pdfName,
            'url' => asset('storage/app/public/inventory/gatepass') . '/' . $pdfName
        ];
    }
    public static function createSupplyOrder($invoice)
    {
        if ($invoice->gst_type == 'gst') {
            $po_number =  Helpers::generatePoNumber('gst')['po_number'];
            $column = 'gst_serial_number';
        } else {
            $po_number =  Helpers::generatePoNumber('non-gst')['po_number'];
            $column = 'nongst_serial_number';
        }
        $parts = explode("_", $po_number);
        $lastNumber = (int) end($parts);

        $supplyOrder = new SupplyOrder();
        $supplyOrder->$column = $lastNumber;
        $supplyOrder->store_id = $invoice->bill_to;
        $supplyOrder->po_number = $po_number;
        $supplyOrder->gst_type = $invoice->tax_type;
        $supplyOrder->vendor_id = $invoice->store_vendor_id;
        $supplyOrder->purchase_date = $invoice->invoice_date ?? now();
        $supplyOrder->invoice_id = $invoice->invoice_id;
        $supplyOrder->save();

        $totalTaxAmount = 0;
        $subTotalAmount = 0;

        $invoice_items = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->whereNotNull("inv_id")->get();

        foreach ($invoice_items as $key => $item) {
            $price = $item->price ?? 0;
            $qty   = $item->qty ?? 1;

            $taxRate = $invoice->tax_type == 'gst' ? $item->tax : 0;
            $totalAmount = $price * $qty;
            $taxAmount   = ($totalAmount * $taxRate) / 100;

            $totalTaxAmount += $taxAmount;
            $subTotalAmount += $totalAmount;

            $orderItem = new SupplyOrderItem();
            $orderItem->order_table_id  = $supplyOrder->id;
            $orderItem->item_id = $item->inv_id;
            $orderItem->item_name = $item->name;
            $orderItem->unit_price = $price;
            $orderItem->tax_rate = $taxRate;
            $orderItem->total_amount = $totalAmount + $taxAmount;
            $orderItem->tax_amount = $taxAmount;
            $orderItem->qty = $qty;
            $orderItem->save();
        }

        $supplyOrder->tax_amount = $totalTaxAmount;
        $supplyOrder->subtotal_amount = $subTotalAmount;
        $supplyOrder->total_amount = $subTotalAmount + $totalTaxAmount;
        $supplyOrder->save();

        $data = Helpers::_generateSupplyOrderPDF($supplyOrder, false);
        $supplyOrder->update(['pdf' => $data['pdf']]);

        return [
            'order' => $supplyOrder,
            'pdf_url' => $data['url']
        ];
    }
    public static function _generateSupplyOrderPDF($supplyOrder, $renderOnly)
    {
        $shipping_address =  null;
        $invoice = $supplyOrder;
        $bill_to = Store::withoutGlobalScopes()->where('id', Helpers::get_store_id())->first();
        $bill_data['total_amount'] = $supplyOrder->total_amount;
        $bill_data['invoice_number'] = $supplyOrder->po_number;
        $bill_data['invoice_date'] = $supplyOrder->purchase_date;
        $bill_data['footer_text'] =  Helpers::get_settings('footer_text');
        $bill_data['invoice_items'] = SupplyOrderItem::where('order_table_id', $supplyOrder->id)->get();


        $bill_to['address'] = '';
        $shipping_address = new \stdClass();

        // if ($supplyOrder->store_id) {
        // $supplyOrder->store_id
        $uDetails = Store::withoutGlobalScopes()->where('id', Helpers::get_store_id())->first();
        $bill_to['address']  = $uDetails->address;
        $bill_to['logo']  = $uDetails->logo;
        $shipping_address->address = $uDetails->address;
        $shipping_address->contact_person_name = $uDetails->name;
        $shipping_address->email  = $uDetails->email;
        $shipping_address->contact_person_number  = $uDetails->phone;
        $bill_to['full_name'] = $uDetails->name;
        // } else {
        //     $uDetails = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $supplyOrder->vendor_id)->first();
        //     $sAddr = $uDetails->shipping_address;
        //     $bAddr = $uDetails->billing_address;
        //     if ($bAddr) {
        //         $bill_to['address']  = $bAddr->address1 . ', ' . $bAddr->address2 . ', ' . $bAddr->state . ', ' . $bAddr->city . '- ' . $bAddr->pincode;
        //     }
        //     if ($sAddr) {
        //         $shipping_address->address = $sAddr->address1 . ', ' . $sAddr->address2 . ', ' . $sAddr->state . ', ' . $sAddr->city . '- ' . $sAddr->pincode;
        //         $shipping_address->contact_person_name = $uDetails->f_name;
        //         $shipping_address->email  = $uDetails->email;
        //         $shipping_address->contact_person_number  = $uDetails->phone;
        //     }
        //     $bill_to['full_name'] = $uDetails->f_name . ' ' . $uDetails->l_name;
        // }

        $bill_to['gst'] = $uDetails->gst;
        $pin_code = $uDetails->pin_code;

        if (!$pin_code) {
            if (preg_match('/\b\d{6}\b/', $uDetails->address, $matches)) {
                $pin_code = $matches[0];
            }
        }
        $bill_to['state_code'] = getStateCodeFromPincode($pin_code);
        $bill_to['pin_code'] = $pin_code;

        $bill_to['phone'] = $uDetails->phone;
        $bill_to['email'] = $uDetails->email;

        $storeVendor = StoreCustomer::with('billing_address', 'shipping_address')->where('id', $supplyOrder->vendor_id)->first();
        $billfromaddress = $storeVendor?->billing_address ? $storeVendor?->billing_address?->address1 . ', ' . $storeVendor?->billing_address?->address2 . ', ' . $storeVendor?->billing_address?->state . ', ' . $storeVendor?->billing_address?->city . '- ' . $storeVendor?->billing_address?->pincode : '';
        $bill_from_type = 'store_vendor';
        $bill_data['store'] = $storeVendor;
        $bill_data['vendor_typ'] = 'service';
        $bill_from['id'] = $storeVendor->id;
        $bill_from['logo'] = $storeVendor->logo;
        $bill_from['name'] = $storeVendor->f_name;
        $bill_from['gst'] = $storeVendor->gst;
        $bill_from['phone'] = $storeVendor->phone;
        $bill_from['email'] = $storeVendor->email;
        $bill_from['address'] = $billfromaddress ?? $storeVendor->address;
        $bill_from['state_code'] = getStateCodeFromPincode($storeVendor->pin_code ?? $storeVendor?->billing_address?->pincode);
        $bill_from['pin_code'] = $storeVendor->pin_code ?? $storeVendor?->billing_address?->pincode;
        $bill_from['cin_number'] = null;
        if ($storeVendor->gst) {
            $bill_data['tax_type'] = $invoice->gst_type ?? 'non-gst';
        } else {
            $bill_data['tax_type'] = 'non-gst';
        }

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        $bill_from_type = 'vendor_to_vendor';

        $html = View::make('invoice_template.purchase_order', compact('invoice', 'bill_from', 'bill_to', 'bill_data', 'bill_from_type', 'shipping_address'))->render();
        if ($renderOnly) {
            return $html;
        }
        $mpdf->WriteHTML($html);
        $pdfName = 'po_' . date('YmdHis') . rand(100000, 999999) . '.pdf';

        $dir = 'purchase-order/';
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $fileUrl = Helpers::savePdfToPublic($mpdf, $dir, $pdfName);

        $data['pdf'] = $pdfName;
        $data['url'] = asset('storage/app/public/') . '/' . $dir .  $pdfName;

        return $data;
    }
    public static function _financialYears()
    {

        $startYear = 2015; // first financial year start
        $endYear = date('Y'); // last financial year start

        $financialYears = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $financialYears[] = (object) [
                'id' => $year - $startYear + 1,
                'label' => $year . '-' . ($year + 1),
                'start_date' => $year . '-04-01',
                'end_date' => $year + 1 . '-03-31',
            ];
        }
        return $financialYears;
    }

    public static function _generatePurchaseReturnPDF($data, $renderOnly)
    {

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        $html = View::make('invoice_template.purchase_return', compact('data'))->render();

        if ($renderOnly) {
            return $html;
        }

        $mpdf->WriteHTML($html);

        $pdfName = 'pr_' . date('YmdHis') . rand(100000, 999999) . '.pdf';
        $dir = 'purchase-order/';

        $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        Storage::disk('public')->put($dir . $pdfName, $pdfContent, 'public');

        // $mpdf->Output('Purchase_Return.pdf', \Mpdf\Output\Destination::INLINE);


        $data['pdf'] = $pdfName;
        $data['url'] = asset('storage/app/public/') . '/' . $dir .  $pdfName;

        return $data;
    }
    public static function _nextTokenNumber()
    {
        $lastToken = PosToken::where("store_id", Helpers::get_store_id())->orderBy('serial_number', 'desc')->first();
        $nextNumber = $lastToken ? $lastToken->serial_number + 1 : 1;
        $nextNumber =  str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        return $nextNumber;
    }
    public static function ensureDefaultExpensesAccount()
    {
        $storeId = self::get_store_id();
        $defaultExpLedger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Default Expenses')
            ->first();

        if (!$defaultExpLedger) {
            $expenseTypeId = LedgerAccountType::where('name', 'Expenses')->first()->id ?? 1;
            $defaultExpLedger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $expenseTypeId,
                'parent_id' => null,
                'code' => _accountCode($expenseTypeId, null),
                'name' => 'Default Expenses',
                'description' => 'Default Expenses',
                'level' => 1,
                'entity_type' => 'store'
            ]);
        }

        return $defaultExpLedger;
    }
    public static function ensureSalesAccount()
    {
        $storeId = self::get_store_id();
        $salesLedger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Sales')
            ->where('ledger_account_type_id', '3')
            ->whereNull('parent_id')
            ->first();

        if (!$salesLedger) {
            $incomeTypeId = LedgerAccountType::where('name', 'Revenue')->first()->id ?? 1;

            $salesLedger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $incomeTypeId,
                'parent_id' => null,
                'code' => _accountCode($incomeTypeId, null),
                'name' => 'Sales',
                'description' => 'Sales Ledger',
                'level' => 1,
                'entity_type' => 'store',
            ]);
        }

        return $salesLedger;
    }
    public static function ensureSalaryLedger($storeId)
    {
        $salaryLedger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Salary')
            ->first();

        if (!$salaryLedger) {
            $expenseTypeId = LedgerAccountType::where('name', 'Expenses')->first()->id ?? 1;
            $salaryLedger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $expenseTypeId,
                'parent_id' => null,
                'code' => _accountCode($expenseTypeId, null),
                'name' => 'Salary',
                'description' => 'Salary Ledger',
                'level' => 1,
                'entity_type' => 'store'
            ]);
        }

        return $salaryLedger;
    }
    public static function ensureDepartmentLedger($department)
    {
        $storeId = Helpers::get_store_id();

        $salaryLedger = self::ensureSalaryLedger($storeId);

        $deptAccount = StoreAccount::find($department->ledger_account_id);

        if (!$deptAccount) {
            $deptAccount = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $salaryLedger->ledger_account_type_id,
                'parent_id' => $salaryLedger->id,
                'code' => _accountCode($salaryLedger->ledger_account_type_id, $salaryLedger->id),
                'name' => $department->title,
                'description' => "Department Ledger for {$department->title}",
                'level' => $salaryLedger->level + 1,
                'entity_type' => 'store',
            ]);

            $department->update(['ledger_account_id' => $deptAccount->id]);
        }

        return $deptAccount;
    }
    public static function ensureFixedAssetAccount($category)
    {
        $storeId = self::get_store_id();

        $topLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Company Assets'
            ],
            [
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Asset')->first()->id ?? 1,
                'parent_id' => null,
                'code' => _accountCode(LedgerAccountType::where('name', 'Asset')->first()->id ?? 1, null),
                'description' => 'All Company Fixed Assets',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        $subLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => $category,
                'parent_id' => $topLedger->id
            ],
            [
                'ledger_account_type_id' => $topLedger->ledger_account_type_id,
                'code' => _accountCode($topLedger->ledger_account_type_id, $topLedger->id),
                'description' => $category,
                'entity_type' => 'store',
                'level' => 2,
            ]
        );

        return $subLedger;
    }
    public static function ensurePurchaseAccount($category, $store_id = null)
    {
        $storeId = $store_id ?? self::get_store_id();

        $expenseTypeId = LedgerAccountType::where('name', 'Expense')->first()->id ?? 1;
        $topLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Purchases'
            ],
            [
                'ledger_account_type_id' => $expenseTypeId,
                'parent_id' => null,
                'code' => _accountCode($expenseTypeId, null, $store_id),
                'description' => 'All Purchase Accounts',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        if ($category) {
            $subLedger = StoreAccount::firstOrCreate(
                [
                    'store_id' => $storeId,
                    'name' => $category,
                    'parent_id' => $topLedger->id
                ],
                [
                    'ledger_account_type_id' => $topLedger->ledger_account_type_id,
                    'code' => _accountCode($topLedger->ledger_account_type_id, $topLedger->id, $store_id),
                    'description' => "Purchases - {$category}",
                    'entity_type' => 'store',
                    'level' => 2,
                ]
            );

            return $subLedger;
        }

        return $topLedger;
    }

    public static function ensureInventoryAccount()
    {
        $storeId = self::get_store_id();

        $currentAssets = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Current Assets'
            ],
            [
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Asset')->first()->id ?? 1,
                'parent_id' => null,
                'code' => _accountCode(LedgerAccountType::where('name', 'Asset')->first()->id ?? 1, null),
                'description' => 'All Current Assets',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        $inventoryLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Inventory',
                'parent_id' => $currentAssets->id
            ],
            [
                'ledger_account_type_id' => $currentAssets->ledger_account_type_id,
                'code' => _accountCode($currentAssets->ledger_account_type_id, $currentAssets->id),
                'description' => 'Inventory Ledger Account',
                'entity_type' => 'store',
                'level' => 2,
            ]
        );

        return $inventoryLedger;
    }
    public static function ensureSubscriptionRevenueAccount()
    {
        // $storeId = self::get_store_id();

        $revenueLedgerType = LedgerAccountType::where('name', 'Revenue')->first();
        $ledgerTypeId = $revenueLedgerType->id ?? 1;

        $revenueGroup = StoreAccount::firstOrCreate(
            [
                'store_id' => 0,
                'name' => 'Mychitti Revenue',
            ],
            [
                'ledger_account_type_id' => $ledgerTypeId,
                'parent_id' => null,
                'code' => _accountCode($ledgerTypeId, null, 0),
                'description' => 'All Revenue Accounts',
                'entity_type' => 'mychitti',
                'level' => 1,
            ]
        );

        return $revenueGroup;
    }
    public static function ensureMaintenanceExpenseAccount($storeId = null)
    {
        if (!$storeId) {
            $storeId = self::get_store_id();
        }

        $expenseLedgerType = LedgerAccountType::where('name', 'Expenses')->first();
        $ledgerTypeId = $expenseLedgerType->id ?? 1;

        $expenseGroup = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Expenses',
            ],
            [
                'ledger_account_type_id' => $ledgerTypeId,
                'parent_id' => null,
                'code' => _accountCode($ledgerTypeId, null),
                'description' => 'All Expense Accounts',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        $maintenanceExpense = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Maintenance Expense',
                'parent_id' => $expenseGroup->id,
            ],
            [
                'ledger_account_type_id' => $expenseGroup->ledger_account_type_id,
                'code' => _accountCode($expenseGroup->ledger_account_type_id, $expenseGroup->id, $storeId),
                'description' => 'All maintenance related expenses',
                'entity_type' => 'store',
                'level' => 2,
            ]
        );


        return $maintenanceExpense;
    }
    public static function ensureWalletRevenueAccount()
    {
        $revenueLedgerType = LedgerAccountType::where('name', 'Revenue')->first();
        $ledgerTypeId = $revenueLedgerType->id ?? 1;

        $revenueGroup = StoreAccount::firstOrCreate(
            [
                'store_id' => 0,
                'name' => 'Mychitti Revenue',
            ],
            [
                'ledger_account_type_id' => $ledgerTypeId,
                'parent_id' => null,
                'code' => _accountCode($ledgerTypeId, null),
                'description' => 'All Revenue Accounts',
                'entity_type' => 'mychitti',
                'level' => 1,
            ]
        );

        // $subscriptionRevenue = StoreAccount::firstOrCreate(
        //     [
        //         'store_id' => $storeId,
        //         'name' => 'Wallet Revenue',
        //         'parent_id' => $revenueGroup->id,
        //     ],
        //     [
        //         'ledger_account_type_id' => $revenueGroup->ledger_account_type_id,
        //         'code' => _accountCode($revenueGroup->ledger_account_type_id, $revenueGroup->id),
        //         'description' => 'Revenue earned from vendor wallet recharge',
        //         'level' => 2,
        //     ]
        // );

        return $revenueGroup;
    }
    public static function ensureSalesRevenueAccount()
    {
        $storeId = self::get_store_id();

        $revenueLedgerType = LedgerAccountType::where('name', 'Revenue')->first();
        $ledgerTypeId = $revenueLedgerType->id ?? 1;

        $revenueGroup = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Revenue',
            ],
            [
                'ledger_account_type_id' => $ledgerTypeId,
                'parent_id' => null,
                'code' => _accountCode($ledgerTypeId, null),
                'description' => 'All Revenue Accounts',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        $subscriptionRevenue = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Sales Revenue',
                'parent_id' => $revenueGroup->id,
            ],
            [
                'ledger_account_type_id' => $revenueGroup->ledger_account_type_id,
                'code' => _accountCode($revenueGroup->ledger_account_type_id, $revenueGroup->id),
                'description' => 'Revenue earned from vendor wallet recharge',
                'entity_type' => 'store',
                'level' => 2,
            ]
        );

        return $subscriptionRevenue;
    }
    public static function ensureServiceRevenueAccount()
    {
        $storeId = self::get_store_id();

        $revenueLedgerType = LedgerAccountType::where('name', 'Revenue')->first();
        $ledgerTypeId = $revenueLedgerType->id ?? 1;

        $revenueGroup = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Revenue',
            ],
            [
                'ledger_account_type_id' => $ledgerTypeId,
                'parent_id' => null,
                'code' => _accountCode($ledgerTypeId, null),
                'description' => 'All Revenue Accounts',
                'entity_type' => 'store',
                'level' => 1,
            ]
        );

        $subscriptionRevenue = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Leads Revenue',
                'parent_id' => $revenueGroup->id,
            ],
            [
                'ledger_account_type_id' => $revenueGroup->ledger_account_type_id,
                'code' => _accountCode($revenueGroup->ledger_account_type_id, $revenueGroup->id),
                'description' => 'Revenue earned from leads',
                'entity_type' => 'store',
                'level' => 2,
            ]
        );

        return $subscriptionRevenue;
    }
    public static function ensureEmployeeLedger($employee)
    {
        $storeId = Helpers::get_store_id();
        $fullName = trim($employee->f_name . ' ' . $employee->l_name);

        $deptAccount = self::ensureDepartmentLedger($employee->department);

        $ledger = StoreAccount::find($employee->ledger_account_id);

        if (!$ledger) {
            $ledger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $deptAccount->ledger_account_type_id,
                'parent_id' => $deptAccount->id,
                'code' => _accountCode($deptAccount->ledger_account_type_id, $deptAccount->id),
                'name' => $fullName,
                'description' => "Salary Ledger for {$fullName}",
                'level' => $deptAccount->level + 1,
                'entity_type' => 'other',
            ]);

            $employee->update(['ledger_account_id' => $ledger->id]);
        } else {
            $ledger->update([
                'name' => $fullName,
                'description' => "Salary Ledger for {$fullName}",
            ]);
        }

        return $ledger;
    }
    public static function ensureAccountsReceivableLedger()
    {
        $storeId = Helpers::get_store_id();

        $ledger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Accounts Receivable')
            ->first();

        if (!$ledger) {
            $assetsParent = StoreAccount::where('store_id', $storeId)
                ->where('name', 'Current Assets')
                ->first();

            $expenseTypeId = LedgerAccountType::where('name', 'Assets')->first()->id ?? 1;
            if (!$assetsParent) {

                $assetsParent = StoreAccount::create([
                    'store_id' => $storeId,
                    'name' => 'Current Assets',
                    'acc_type' => 'debit',
                    'code' => _accountCode($expenseTypeId, null),
                    'account_type' => 'common',
                    'ledger_account_type_id' => $expenseTypeId,
                    'acc_type' => 'debit',
                    'level' => 1,
                ]);
            }

            $ledger = StoreAccount::create([
                'store_id' => $storeId,
                'parent_id' => $assetsParent->id,
                'account_type' => 'common',
                'acc_type' => 'debit',
                'name' => 'Accounts Receivable',
                'code' => _accountCode($expenseTypeId, $assetsParent->id),
                'ledger_account_type_id' => $expenseTypeId,
                'description' => 'Parent ledger for all customer accounts',
                'level' => $assetsParent->level + 1,
            ]);
        }

        return $ledger;
    }
    public static function ensureOtherCustomerLedger()
    {
        $storeId = Helpers::get_store_id();

        $parentAccount = self::ensureAccountsReceivableLedger();

        $ledger = StoreAccount::where('name', 'Customers')->where("store_id", $storeId)->first();
        // print_r($ledger);
        $expenseTypeId = LedgerAccountType::where('name', 'Assets')->first()->id ?? 1;

        if (!$ledger) {
            $ledger = StoreAccount::create([
                'store_id' => $storeId,
                'parent_id' => $parentAccount->id,
                'account_type' => 'common',
                'acc_type' => 'debit',
                'code' => _accountCode($expenseTypeId, $parentAccount->id),
                'name' =>  'Customers',
                'ledger_account_type_id' => $expenseTypeId,
                'description' => "Ledger for Customers",
                'level' => $parentAccount->level + 1,
                'status' => 1,
                'entity_type' => 'customer'
            ]);
        }

        return $ledger;
    }
    public static function ensureCustomerLedger($customer)
    {
        $storeId = Helpers::get_store_id();
        $fullName = trim($customer->f_name . ' ' . $customer->l_name);

        $parentAccount = self::ensureAccountsReceivableLedger();

        $ledger = StoreAccount::find($customer->ledger_account_id);
        $expenseTypeId = LedgerAccountType::where('name', 'Assets')->first()->id ?? 1;

        if (!$ledger) {
            $ledger = StoreAccount::create([
                'store_id' => $storeId,
                'parent_id' => $parentAccount->id,
                'account_type' => 'common',
                'acc_type' => 'debit',
                'code' => _accountCode($expenseTypeId, $parentAccount->id),
                'name' =>  $fullName,
                'ledger_account_type_id' => $expenseTypeId,
                'description' => "Ledger for Customer {$fullName}",
                'level' => $parentAccount->level + 1,
                'status' => 1,
                'entity_type' => 'customer'
            ]);

            $customer->update(['ledger_account_id' => $ledger->id]);
        } else {
            $ledger->update([
                'name' => $fullName,
                'description' => "Ledger for Customer {$fullName}",
            ]);
        }

        return $ledger;
    }
    public static function _generateVoucherNumber($storeId)
    {
        $lastVoucher = StoreVoucher::where("store_id", $storeId)->orderBy('id', 'desc')->first();
        $nextNumber  = $lastVoucher ? intval(substr($lastVoucher->voucher_number, 4)) + 1 : 1;
        $voucherNo   = 'VCH-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $voucherNo;
    }

    public static  function _validatePhoneNumber(string $phone): bool
    {
        // Regex explanation:
        // ^\+          => must start with +
        // \d{1,3}      => country code (1 to 3 digits)
        // \d{10}$      => exactly 10 digits for local number
        return preg_match('/^\d{1,3}\d{10}$/', $phone) === 1;
    }

    public static function _generateTaskId($increment = false)
    {
        $storeId = Helpers::get_store_id();
        $store = Helpers::get_store_data();

        $storeConfig = StoreConfig::where("store_id", $store->id)->first();

        $prefix = $storeConfig && $storeConfig->task_id_format !== null ? $storeConfig->task_id_format : self::storePrefix() . '-';
        $next_serial = $storeConfig ? $storeConfig->task_id_serial : 1;

        $taskId = $prefix  . str_pad($next_serial, 3, '0', STR_PAD_LEFT);

        if ($increment) {
            StoreConfig::updateOrInsert(['store_id' => $storeId], [
                'task_id_serial' => $next_serial + 1,
            ]);
        }

        return $taskId;
    }
    public static function _generateTaskSerial()
    {
        $storeId = Helpers::get_store_id();

        $lastTask = StoreTask::where("store_id", $storeId)
            ->where('task_id', 'REGEXP', '^[A-Z0-9-]+-[0-9]+$')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastTask && preg_match('/(\d+)$/', $lastTask->task_id, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        $taskId =  str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return $taskId;
    }

    public static function storePrefix()
    {
        $store = Helpers::get_store_data();
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $store->name), 0, 3));
        return $prefix;
    }
    public static function generatePoNumber($gst_type)
    {
        $store = Helpers::get_store_data();
        $store_prefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3);
        $storeId = Helpers::get_store_id();

        $column = $gst_type == 'gst' ? 'gst_serial_number' : 'nongst_serial_number';

        $lastToken = SupplyOrder::where("store_id", $storeId)
            ->orderBy($column, 'desc')
            ->value($column);

        $nextNumber = $lastToken ? $lastToken + 1 : 1;

        $paddedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        if ($gst_type == 'gst') {
            if (date('m') > 3) { // after March = new FY
                $year = date('y') . '-' . (date('y') + 1) . '_';
            } else {
                $year = (date('y') - 1) . '-' . date('y') . '_';
            }
        } else {
            $year = '';
        }

        $po_number = 'PO_' . $store_prefix . '_' . $year . $paddedNumber;

        return ['po_number' => $po_number, 'serial_number' => $nextNumber];
    }
    public static function _placeInventoryOrder($invoice)
    {
        $invItemsInInvoice = InvoiceItem::where('rand_invoice_id', $invoice->invoice_id)->whereNotNull('inv_id')->get();

        $order_id = self::_invOrderIdGenerate();
        if (count($invItemsInInvoice)) {
            $subtotal_amount = 0;
            $tax_amount = 0;

            // order 
            $invOrder = new InventoryOrder();
            $invOrder->order_id = $order_id;
            $invOrder->store_id = Helpers::get_store_id();
            $invOrder->invoice_id = $invoice->invoice_id;
            $invOrder->status = 'pending';
            $invOrder->save();

            // order details 
            foreach ($invItemsInInvoice as $key => $item) {
                $invOrderDetail = new InventoryOrderDetail();
                $invOrderDetail->order_id = $order_id;
                $invOrderDetail->item_id = $item->inv_id;
                $invOrderDetail->qty = $item->qty;
                $invOrderDetail->unit_price = $item->price;
                $invOrderDetail->total_price = $item->price * $item->qty;
                $invOrderDetail->tax_rate    = $item->tax;
                $invOrderDetail->status    = 'pending';

                $lineTax = ($invOrderDetail->total_price * $invOrderDetail->tax_rate) / 100;

                $invOrderDetail->tax_amount = $lineTax;
                $invOrderDetail->save();

                $subtotal_amount += $invOrderDetail->total_price;
                $tax_amount      += $lineTax;
            }

            // update order 
            $invOrder->subtotal_amount = $subtotal_amount;
            $invOrder->tax_amount = $tax_amount;
            $invOrder->total_amount = $tax_amount  +  $subtotal_amount;
            $invOrder->save();
        }
    }
    public static function _invOrderIdGenerate()
    {
        $storeId = Helpers::get_store_id();

        $lastOrder = InventoryOrder::where('store_id', $storeId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastOrder ? intval(substr($lastOrder->order_id, -6)) + 1 : 1;

        $orderId = 'S' . $storeId . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return $orderId;
    }
    public static function _saveCategoryIfNotExists($category)
    {

        if (is_numeric($category)) {
            return $category;
        }
        $category_exist = Category::whereRaw('LOWER(name) = ?', [strtolower($category)])->where('status', 1)->first();
        if ($category_exist) {
            $category_id = $category_exist->id;
        } else {
            $category = Category::create([
                'name' => $category,
                'parent_id' => 0,
                'position' => 0,
                'module_id' => 6,
                'added_by' => Helpers::get_store_id() ?? 0
            ]);
            $category_id = $category->id;
        }

        return $category_id;
    }
    public static function _copyToItemTable($inventory_item, $uploaded_images = false)
    {
        $item = $item_exists =  Item::withoutGlobalScopes()->where('inventory_item_id', $inventory_item->id)->first();
        // prx($item);
        if (!$item) {
            $item = new Item();
            $item->inventory_item_id = $inventory_item->id;
        }

        $item->name = $inventory_item->item_name;

        $category = [];
        $category_id = $inventory_item->category_id ?? 0;
        if ($inventory_item->category_id != null) {
            array_push($category, [
                'id' => $category_id,
                'position' => 1,
            ]);
        }

        $item->item_type = $inventory_item->item_type;
        $item->category_id = $inventory_item->category_id;
        $item->category_ids = json_encode($category);

        $item->description = $inventory_item->description;
        $item->specifications = $inventory_item->specifications;
        $item->choice_options = $inventory_item->choice_options;

        $item->attributes = $inventory_item->attributes;

        if (!$uploaded_images) {

            if ($inventory_item->image) {
                $newImage = $inventory_item->image;

                if (!$item_exists || $item->image !== $newImage) {

                    $oldPath = "inventory-item/{$newImage}";
                    $newPath = "product/{$newImage}";


                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->copy($oldPath, $newPath);
                    }

                    if ($item_exists && $item->image && $item->image !== $newImage) {
                        $oldFile = "product/{$item->image}";
                        if (Storage::disk('public')->exists($oldFile)) {
                            Storage::disk('public')->delete($oldFile);
                        }
                    }
                }
            }

            if ($inventory_item->images) {

                $newImages = is_array($inventory_item->images)
                    ? $inventory_item->images
                    : json_decode($inventory_item->images, true);

                foreach ($newImages as $img) {
                    $oldPath = "inventory-item/{$img}";
                    $newPath = "product/{$img}";

                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->copy($oldPath, $newPath);
                    }
                }

                if ($item_exists && $item->images) {

                    $oldImages = is_array($item->images)
                        ? $item->images
                        : json_decode($item->images, true);

                    $toDelete = array_diff($oldImages, $newImages);

                    foreach ($toDelete as $img) {
                        $oldFile = "product/{$img}";
                        if (Storage::disk('public')->exists($oldFile)) {
                            Storage::disk('public')->delete($oldFile);
                        }
                    }
                }
            }
        }


        if (!$item_exists) {
            $item->image = $inventory_item->image ?? null;
        } elseif ($item->image !== $inventory_item->image) {
            $item->image = $inventory_item->image ?? null;
        }

        if (!$item_exists) {
            $item->images = $inventory_item->images ?? [];
        } elseif (
            array_diff($inventory_item->images ?? [], $item->images ?? []) ||
            array_diff($item->images ?? [], $inventory_item->images ?? [])
        ) {
            $item->images = $inventory_item->images ?? [];
        }

        $item->asking_price =  $inventory_item->selling_price;
        $item->price =  $inventory_item->selling_price ?? 0;
        $item->mrp_price = $inventory_item->mrp;
        $item->available_time_starts =  '00:00:00';
        $item->available_time_ends =  '23:59:59';
        $item->hsn_code = $inventory_item->hsn;
        $item->tax = $inventory_item->gst_rate;
        $item->tax_type = 'percent';
        $item->discount = 0;
        $item->discount_type = 'percent';
        $item->unit_id = $inventory_item->unit;
        $item->add_ons =  json_encode([]);
        $item->store_id = null;
        $item->store_ids = Helpers::get_store_id();
        $item->maximum_cart_quantity = 100;

        $item->module_id = 6;
        $item->organic =  0;
        $item->stock = $inventory_item->stock;
        $item->save();

        $invItemVariationDetails = InvItemVariationDetail::where('item_id', $inventory_item->id)->get();

        $allVariations = [];
        $decodedVariations = json_decode($inventory_item->variations, true) ?? [];
        $usedVariationIndices = []; // Track which variations we've already used

        foreach ($invItemVariationDetails as $index => $invItemVariationDetail) {
            $data = $invItemVariationDetail->toArray();
            $data['item_id'] = $item->id;

            unset($data['id'], $data['created_at'], $data['updated_at']);

            $itemVariationDetail = ItemVariationDetail::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'type'    => $data['type'] ?? null,
                ],
                $data
            );

            $correspondingVariation = null;
            $foundIndex = null;

            // First, try to match by type
            foreach ($decodedVariations as $varIndex => $variation) {
                $variation = (array) $variation;

                if (
                    !in_array($varIndex, $usedVariationIndices) &&
                    isset($variation['type']) &&
                    $variation['type'] === $invItemVariationDetail->type
                ) {
                    $correspondingVariation = $variation;
                    $foundIndex = $varIndex;
                    break;
                }
            }

            // Fallback: match by index if not found by type and index is available
            if (
                !$correspondingVariation &&
                isset($decodedVariations[$index]) &&
                !in_array($index, $usedVariationIndices)
            ) {
                $correspondingVariation = (array) $decodedVariations[$index];
                $foundIndex = $index;
            }

            if ($correspondingVariation && $foundIndex !== null) {
                $correspondingVariation['variations_table_id'] = $itemVariationDetail->id;
                $allVariations[] = $correspondingVariation;
                $usedVariationIndices[] = $foundIndex; // Mark this variation as used
            }
        }

        $item->variations = json_encode($allVariations);
        $item->save();

        return true;
    }

    public static function generatePOSTokenNumber($custom_prefix)
    {
        $lastToken = PosToken::where("store_id", Helpers::get_store_id())->orderBy('serial_number', 'desc')->first();
        $nextNumber = $lastToken ? $lastToken->serial_number + 1 : 1;

        // Prefix + padded number
        $prefix = ucfirst($custom_prefix) ?? 'TKN';
        $tokenNumber = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        return $tokenNumber;
    }
    public static function invGatepassNumber($type) // purchase gatepass (inventory)
    {
        $lastGP = InventoryGatepass::where("store_id", Helpers::get_store_id())->where('type', $type)->orderBy('serial_number', 'desc')->first();
        $nextNumber = $lastGP ? $lastGP->serial_number + 1 : 1;
        $prefix = strtoupper(substr($type, 0, 1));

        $gpNumber = $prefix . 'G_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        return ['gp_id' =>  $gpNumber, 'gp_serial' => $nextNumber];
    }
    public static function pdf_parse(string $text): array
    {
        return [
            'vendor_name' => self::tryPatterns([
                '/(?:Sold\s*By|Supplier|Vendor|Billed\s*By|From)\s*[:\-]?\s*(.+)/i',
            ], $text),

            'vendor_gstin' => self::tryPatterns([
                '/GST\s*NO\s*[:\-]?\s*([0-9A-Z]{15})/i',
                '/GSTIN\s*[:\-]?\s*([0-9A-Z]{15})/i',
            ], $text),

            'vendor_pan' => self::tryPatterns([
                '/PAN\s*NO\s*[:\-]?\s*([A-Z0-9]{10})/i',
            ], $text),

            'vendor_cin' => self::tryPatterns([
                '/CIN\s*NO\s*[:\-]?\s*([A-Z0-9]+)/i',
            ], $text),

            'billing_address' => self::tryPatterns([
                '/Billing\s*Address\s*:\s*(.+?)(?:Invoice\s*No|$)/is',
            ], $text),

            'invoice_no' => self::tryPatterns([
                '/Invoice\s*No\.?\s*[:\-]?\s*([A-Z0-9\-\/\.]+)/i',
                '/Inv\s*#\s*[:\-]?\s*([A-Z0-9\-\/\.]+)/i',
            ], $text),

            'invoice_date' => self::tryPatterns([
                '/Invoice\s*Date\s*[:\-]?\s*(\d{4}[-\/.]\d{2}[-\/.]\d{2})/i',
                '/Invoice\s*Date\s*[:\-]?\s*([0-3]?\d[-\/.][0-1]?\d[-\/.](?:20)?\d{2})/i',
            ], $text),

            'payment_date' => self::tryPatterns([
                '/Payment\s*Date\s*[:\-]?\s*(\d{4}[-\/.]\d{2}[-\/.]\d{2})/i',
                '/Payment\s*Date\s*[:\-]?\s*([0-3]?\d[-\/.][0-1]?\d[-\/.](?:20)?\d{2})/i',
            ], $text),

            'po_no' => self::tryPatterns([
                '/PO\s*No\.?\s*[:\-]?\s*([A-Z0-9\-\/]+)/i',
                '/Purchase\s*Order\s*[:\-]?\s*([A-Z0-9\-\/]+)/i',
            ], $text),

            'place_of_supply' => self::tryPatterns([
                '/Place\s*of\s*Supply\s*[:\-]?\s*([A-Za-z ,]+)/i',
            ], $text),

            // Amounts
            'subtotal' => self::tryPatterns([
                '/Sub\s*Total\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
                '/Taxable\s*Amount\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'taxable_amount' => self::tryPatterns([
                '/Total\s*Taxable\s*Amount\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'cgst' => self::tryPatterns([
                '/CGST[^\d]*₹?\s*([0-9,]+\.\d{2})/i',
                '/CGST\s*[:\-]?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'sgst' => self::tryPatterns([
                '/SGST[^\d]*₹?\s*([0-9,]+\.\d{2})/i',
                '/SGST\s*[:\-]?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'igst' => self::tryPatterns([
                '/IGST[^\d]*₹?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'gst_total' => self::tryPatterns([
                '/GST\s*Summary.*?([0-9,]+\.\d{2})/is',
            ], $text),

            'total' => self::tryPatterns([
                '/Grand\s*Total\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
                '/Total\s*Amount\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
                '/Net\s*Payable\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'rounded_off' => self::tryPatterns([
                '/Rounded\s*Off\s*[:\-]?\s*₹?\s*([0-9,]+\.\d{2})/i',
            ], $text),

            'amount_in_words' => self::tryPatterns([
                '/INVOICE\s*AMOUNT\s*IN\s*WORDS\s*(.+?)\s*Sub\s*Total/is',
            ], $text),

            'currency' => Str::contains($text, '₹') ? 'INR' : 'INR',
            'raw_preview' => Str::limit($text, 2000),
        ];
    }

    private static function tryPatterns(array $patterns, string $text)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    public static function parseInvoice(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $invoice = [];

        // ---------------- SELLER DETAILS ----------------
        $invoice['seller'] = [
            'name'    => self::match('/Sold By:\s*(.*)/i', $text),
            'address' => self::match('/Sold By:.*?\n(.*?)\n/i', $text),
            'gstin'   => self::match('/GST No:\s*([A-Z0-9]+)/i', $text),
            'pan'     => self::match('/PAN No:\s*([A-Z0-9]+)/i', $text),
            'cin'     => self::match('/CIN No:\s*([A-Z0-9]+)/i', $text),
        ];

        // ---------------- INVOICE META ----------------
        $invoice['invoice'] = [
            'number'       => self::match('/Invoice No:\s*([^\s]+)/i', $text),
            'date'         => self::match('/Invoice Date:\s*([0-9\-]+)/i', $text),
            'payment_date' => self::match('/Payment Date:\s*([0-9\-]+)/i', $text),
            'place'        => self::match('/Place of Supply:\s*([^\n]+)/i', $text),
        ];

        // ---------------- BUYER DETAILS ----------------
        $invoice['buyer'] = [
            'name'    => self::match('/Billing Address:\s*([\s\S]*?)Invoice No:/i', $text),
            'email'   => self::match('/Email:\s*([^\s]+)/i', $text),
            'phone'   => self::match('/Ph No:\s*([0-9]+)/i', $text),
        ];

        // ---------------- ITEM TABLE ----------------
        $invoice['items'] = self::parseItems($text);
        // prx($invoice['items']);

        // ---------------- TOTALS ----------------
        $invoice['totals'] = [
            'subtotal'      => self::match('/Sub\s*Total:\s*₹?([\d.,]+)/i', $text),
            'taxable_total' => self::match('/Total Taxable Amount:\s*₹?([\d.,]+)/i', $text),
            'tax_amount'    => self::match('/Total Tax Amount:\s*₹?([\d.,]+)/i', $text),
            'grand_total'   => self::match('/Grand Total:\s*₹?([\d.,]+)/i', $text),
        ];

        return $invoice;
    }

    private static function parseItems(string $text): array
    {
        prx($text);
        $items = [];
        $lines = preg_split("/\r\n|\n|\r/", $text);

        // Preload all items to reduce DB queries
        $itemMap = \App\Models\InventoryItem::all()->keyBy('item_name');

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || preg_match('/^Sl\s+/i', $line)) continue; // skip header

            // Regex: sl_no, description, qty, mrp, disc, price, total
            if (preg_match(
                '/^(\d+)\s+(.+?)\s+(\d+(?:\s*\w+)?)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)$/',
                $line,
                $m
            )) {
                $sl           = $m[1];
                $description  = trim($m[2]);
                $qty          = $m[3];
                $mrp          = (float) str_replace(',', '', $m[4]);
                $disc         = (float) str_replace(',', '', $m[5]);
                $price        = (float) str_replace(',', '', $m[6]);
                $total        = (float) str_replace(',', '', $m[7]);

                // calculate purchase price
                $purchasePrice = $mrp - $disc;

                // Lookup item_id
                $item_id = $itemMap[$description]->id ?? null;

                $items[] = [
                    'item_id'        => $item_id,
                    'description'    => $description,
                    'qty'            => $qty,
                    'mrp'            => $mrp,
                    'disc'           => $disc,
                    'purchase_price' => $purchasePrice,
                    'price'          => $price,
                    'total'          => $total,
                ];
            }
        }

        return $items;
    }




    // ---------- Helper Regex ----------
    private static function match(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public static function generatePOSToken($token, $action = 'generate')
    {
        $store = Helpers::get_store_data();
        $store_config = StoreConfig::where('store_id', $store->id)->first();
        $template_id = $store_config && $store_config->pos_token_template ? $store_config->pos_token_template : 1;
        $kitchen_token_enabled = $token->token_type == 'kitchen' || $token->token_type == 'both' ? 1 : 0;
        $normal_token_enabled = $token->token_type == 'token' || $token->token_type == 'both' ? 1 : 0;
        $kitchen = false; // for template use only

        $tempDir = storage_path('app/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0775, true);
        }


        if (!$normal_token_enabled && !$kitchen_token_enabled) {
            return [
                'success' => false,
                'file_name' => '',
                'url' => ''
            ];
        }
        // ========== MAIN TOKEN (measure height first) ==========
        if ($normal_token_enabled) {


            $mpdfMeasure = new \Mpdf\Mpdf([
                'tempDir' => $tempDir,
                'mode' => 'utf-8',
                'format' => [80, 297], // temporary
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_top' => 2,
                'margin_bottom' => 2,
            ]);
            $tokenHTML = View::make('document_templates/pos_token/token_' . $template_id, compact('store', 'store_config', 'token', 'kitchen'))->render();
            $mpdfMeasure->WriteHTML($tokenHTML);
            $pageHeight = $mpdfMeasure->y;

            // Create mpdf with exact height
            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => $tempDir,
                'mode' => 'utf-8',
                'format' => [80, $pageHeight + 10],
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_top' => 2,
                'margin_bottom' => 2,
            ]);
            $mpdf->WriteHTML($tokenHTML);
        }
        // ========== KITCHEN TOKEN (optional 2nd page) ==========
        if ($kitchen_token_enabled && $action != 're-generate') { // regenerate is for payment status update
            $kitchen = true;
            // measure kitchen height
            $mpdfMeasureKitchen = new \Mpdf\Mpdf([
                'tempDir' => $tempDir,
                'mode' => 'utf-8',
                'format' => [80, 297],
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_top' => 2,
                'margin_bottom' => 2,
            ]);
            $kitchenHTML = View::make('document_templates/pos_token/token_' . $template_id, compact('store', 'store_config', 'token', 'kitchen'))->render();
            $mpdfMeasureKitchen->WriteHTML($kitchenHTML);
            $kitchenHeight = $mpdfMeasureKitchen->y;

            if (!isset($mpdf)) {
                $mpdf = new \Mpdf\Mpdf([
                    'tempDir' => $tempDir,
                    'mode' => 'utf-8',
                    'format' => [80, $kitchenHeight + 10],
                    'margin_left' => 2,
                    'margin_right' => 2,
                    'margin_top' => 2,
                    'margin_bottom' => 2,
                ]);
            } else {
                // add new page with exact height
                $mpdf->AddPageByArray([

                    'margin-left' => 2,
                    'margin-right' => 2,
                    'margin-top' => 2,
                    'margin-bottom' => 2,
                    'orientation' => 'P',
                    'sheet-size' => [80, $kitchenHeight + 10],
                ]);
            }
            $mpdf->WriteHTML($kitchenHTML);
        }

        // ========== SAVE FILE ==========
        if (!Storage::disk('public')->exists('store/tokens/')) {
            Storage::disk('public')->makeDirectory('store/tokens/');
        }
        $fileName = 'token_' . time() . '.pdf';
        $token->pdf = $fileName;
        $token->save();

        $fileUrl = Helpers::savePdfToPublic($mpdf, 'store/tokens', $fileName);

        return [
            'success' => true,
            'file_name' => $fileName,
            'url' => asset('storage/app/public/store/tokens/' . $fileName)
        ];
    }

    public static function pos_calendar_sales($branch_id = null)
    {
        // prx($branch_id );
        // Fetch all sales data
        $sales = PosToken::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, DAY(created_at) as day, SUM(total) as amount, COUNT(id) as tokens')
            ->groupBy('year', 'month', 'day')
            ->where('store_id', Helpers::get_store_id())
            ->when($branch_id && $branch_id !== 'all', function ($query) use ($branch_id) {
                return $query->where('branch_id', $branch_id);
            })
            ->whereNot('payment_status', 'cancelled')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $structuredData = [];

        // --- Step 1: Organize by year & month ---
        $monthlyData = [];
        foreach ($sales as $sale) {
            $monthlyData[$sale->year][$sale->month][] = (int) $sale->amount;
        }

        // --- Step 2: Compute monthly averages ---
        $monthlyAvg = [];
        foreach ($monthlyData as $year => $months) {
            foreach ($months as $month => $amounts) {
                $avg = array_sum($amounts) / count($amounts);
                $monthlyAvg[$year][$month] = $avg;
            }
        }

        // --- Step 3: Structure data with category ---
        foreach ($sales as $sale) {
            $avg = $monthlyAvg[$sale->year][$sale->month];

            if ($sale->amount < $avg * 0.8) {
                $category = "low";
            } elseif ($sale->amount <= $avg * 1.2) {
                $category = "medium";
            } else {
                $category = "high";
            }

            $structuredData[$sale->year][$sale->month][$sale->day] = [
                'amount'   => (int) $sale->amount,
                'tokens'   => (int) $sale->tokens,
                'category' => $category
            ];
        }

        return $structuredData;
    }
    public static function inv_calendar()
    {
        $sales = ManualInvoice::join('invoice_items', 'invoice_items.rand_invoice_id', 'manual_invoices.invoice_id')
            ->select('manual_invoices.total_amount', 'invoice_items.qty', 'manual_invoices.invoice_id', 'manual_invoices.created_at')
            ->whereNotNull('invoice_items.inv_id')
            ->where('vendor_id', Helpers::get_store_id())
            ->get();

        $structured = [];

        foreach ($sales as $sale) {
            $date = \Carbon\Carbon::parse($sale->created_at); // assuming `created_at` exists
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            // Initialize if not exists
            if (!isset($structured[$year][$month][$day])) {
                $structured[$year][$month][$day] = [
                    'amount' => 0,
                    'tokens' => 0,
                    'category' => 'low', // default, will calculate later
                ];
            }

            // Aggregate values
            $structured[$year][$month][$day]['amount'] += $sale->total_amount; // replace with your column
            $structured[$year][$month][$day]['tokens'] += $sale->qty ?? 1; // replace with your column

            // Determine category
            $amount = $structured[$year][$month][$day]['amount'];
            if ($amount > 50000) {
                $structured[$year][$month][$day]['category'] = 'high';
            } elseif ($amount > 200) {
                $structured[$year][$month][$day]['category'] = 'medium';
            } else {
                $structured[$year][$month][$day]['category'] = 'low';
            }
        }

        return $structured;
    }
    public static function inv_order_calendar()
    {
        $sales = InventoryOrderDetail::with('order')->whereHas('order', function ($q) {
            // $q->where('store_id', Helpers::get_store_id());
        })->get();

        $structured = [];

        foreach ($sales as $sale) {
            $date = \Carbon\Carbon::parse($sale->created_at); // assuming `created_at` exists
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            // Initialize if not exists
            if (!isset($structured[$year][$month][$day])) {
                $structured[$year][$month][$day] = [
                    'amount' => 0,
                    'tokens' => 0,
                    'category' => 'low', // default, will calculate later
                ];
            }

            // Aggregate values
            $structured[$year][$month][$day]['amount'] += $sale->total_price; // replace with your column
            $structured[$year][$month][$day]['tokens'] += $sale->qty ?? 1; // replace with your column

            // Determine category
            $amount = $structured[$year][$month][$day]['amount'];
            if ($amount > 50000) {
                $structured[$year][$month][$day]['category'] = 'high';
            } elseif ($amount > 200) {
                $structured[$year][$month][$day]['category'] = 'medium';
            } else {
                $structured[$year][$month][$day]['category'] = 'low';
            }
        }

        return $structured;
    }
    public static function task_calendar()
    {
        $sales = StoreTask::whereNull('parent_id')
            ->where('store_id', Helpers::get_store_id())
            ->where('task_type', 'common')
            ->get();

        $structured = [];

        foreach ($sales as $sale) {
            $date = \Carbon\Carbon::parse($sale->created_at); // assuming `created_at` exists
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            // Initialize if not exists
            if (!isset($structured[$year][$month][$day])) {
                $structured[$year][$month][$day] = [
                    'task' => 0,
                    'created_task' => 0,
                    'pending_task' => 0,
                    'completed_task' => 0,
                    'cancelled_task' => 0,
                    'category' => 'low', // default, will calculate later
                ];
            }

            // Aggregate values
            $structured[$year][$month][$day]['task'] += 1; // replace with your column
            $structured[$year][$month][$day]['created_task'] += 1; // replace with your column
            $structured[$year][$month][$day]['pending_task'] += ($sale->status != 'Completed' && $sale->status != 'Cancelled') ? 1 : 0; // replace with your column
            $structured[$year][$month][$day]['completed_task'] += $sale->status == 'Completed' ? 1 : 0; // replace with your column
            $structured[$year][$month][$day]['cancelled_task'] += $sale->status == 'Cancelled' ? 1 : 0; // replace with your column

            // Determine category
            $amount = $structured[$year][$month][$day]['task'];
            if ($amount > 10) {
                $structured[$year][$month][$day]['category'] = 'high';
            } elseif ($amount > 200) {
                $structured[$year][$month][$day]['category'] = 'medium';
            } else {
                $structured[$year][$month][$day]['category'] = 'low';
            }
        }

        return $structured;
    }
    public static function cashbook_calendar()
    {
        $sales = DayBook::where('store_id', Helpers::get_store_id())
            ->get();

        $structured = [];

        foreach ($sales as $sale) {
            $date = \Carbon\Carbon::parse($sale->entry_date); // assuming `created_at` exists
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            // Initialize if not exists
            if (!isset($structured[$year][$month][$day])) {
                $structured[$year][$month][$day] = [
                    'txn' => 0,
                    'debit' => 0,
                    'credit' => 0,
                    'category' => 'low', // default, will calculate later
                ];
            }

            // Aggregate values
            $structured[$year][$month][$day]['txn'] += 1;
            $structured[$year][$month][$day]['credit'] += ($sale->type != 'credit') ? $sale->amount : 0;
            $structured[$year][$month][$day]['debit'] += ($sale->type != 'debit') ? $sale->amount : 0;

            // Determine category
            $amount = $structured[$year][$month][$day]['txn'];
            if ($amount > 10) {
                $structured[$year][$month][$day]['category'] = 'high';
            } elseif ($amount > 200) {
                $structured[$year][$month][$day]['category'] = 'medium';
            } else {
                $structured[$year][$month][$day]['category'] = 'low';
            }
        }

        return $structured;
    }

    public static function bank_transactions_calendar($bank_account_id = null)
    {
        if ($bank_account_id == 0) {
            $data = StoreBankTransaction::where('store_id', Helpers::get_store_id())
                ->get();
        } else {
            $data = StoreBankTransaction::where('bank_id', $bank_account_id)
                ->where('store_id', Helpers::get_store_id())
                ->get();
        }

        $structured = [];

        foreach ($data as $row) {
            $date = \Carbon\Carbon::parse($row->txn_date); // assuming `created_at` exists
            $year = $date->year;
            $month = $date->month;
            $day = $date->day;

            if (!isset($structured[$year][$month][$day])) {
                $structured[$year][$month][$day] = [
                    'debit' => 0,
                    'credit' => 0,
                ];
            }

            $structured[$year][$month][$day]['credit'] += $row->type == 'credit' ? $row->amount : 0;
            $structured[$year][$month][$day]['debit'] += $row->type == 'debit' ? $row->amount : 0;
        }
        return $structured;
    }
    public static function excelToCarbon($value)
    {
        // 1️⃣ If it's numeric (Excel serial date)
        if (is_numeric($value)) {
            // Excel serial to timestamp
            return Carbon::createFromTimestamp(strtotime('1900-01-01') + ($value - 2) * 86400);
        }

        // 2️⃣ If it's a string, try common date formats
        $formats = [
            'd-m-y',
            'd-m-Y',      // dashes
            'd/m/y',
            'd/m/Y',      // slashes
            'Y-m-d',
            'm/d/Y',
            'd-M-Y',
            'M d, Y'
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 3️⃣ Fallback: let Carbon parse it automatically
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null; // could not parse
        }
    }



    public static function error_processor($validator)
    {
        $err_keeper = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            array_push($err_keeper, ['code' => $index, 'message' => $error[0]]);
        }
        return $err_keeper;
    }

    public static function generate_RR_number($store,  $action2 = 'view', $custom_number = null)
    {
        $store_prefix = self::_storePrefix($store->name);
        $ser_number = $custom_number ?? $store->receivable_receipt_serial_number;

        $receipt_number = $store_prefix . 'RR-'  . $ser_number;

        if ($action2 === 'view') {
            return $receipt_number;
        }

        $new_num = $custom_number ? $custom_number + 1 : $receipt_number + 1;
        DB::table('stores')
            ->where('id', Helpers::get_store_id())
            ->update(['receivable_receipt_serial_number' => $new_num]);

        return $receipt_number;
    }


    public static function generate_RR_prefix($store)
    {
        $store_prefix = self::_storePrefix($store->name);
        $receipt_number = $store_prefix . 'RR-';
        return $receipt_number;
    }
    public static function generate_RR_serial_number($store = null, $action2 = 'view', $custom_number = null)
    {
        if (!$store) {
            $store = DB::table('stores')->where('id', Helpers::get_store_id())->first();
        }

        $receipt_number = $custom_number ?? $store->receivable_receipt_serial_number;
        if ($action2 === 'view') {
            return $custom_number ?? $receipt_number;
        }
        $new_num = $custom_number ? $custom_number + 1 : $receipt_number + 1;
        DB::table('stores')
            ->where('id', Helpers::get_store_id())
            ->update(['receivable_receipt_serial_number' => $new_num]);

        return $custom_number ?? $receipt_number;
    }
    public static function generate_jobcard_number($store = null, $action2 = 'view')
    {
        $store_prefix = self::_storePrefix($store->name);
        $prefix = $store_prefix . 'JC-';

        if (!$store) {
            $store = DB::table('stores')->where('id', Helpers::get_store_id())->first();
        }
        $receipt_number =  $store->jobcard_serial_number;
        if ($action2 === 'view') {
            return $prefix .  $receipt_number;
        }
        $new_num =  $receipt_number + 1;
        DB::table('stores')
            ->where('id', Helpers::get_store_id())
            ->update(['jobcard_serial_number' => $new_num]);

        return $prefix . $receipt_number;
    }
    public static function ensureCashAccount()
    {
        $storeId = Helpers::get_store_id();

        // Ensure top-level Cash ledger exists
        $cashLedger = StoreAccount::firstOrCreate(
            [
                'store_id'     => $storeId,
                'name' => 'Cash Account',
            ],
            [
                'account_type' => 'common',
                'balance'      => 0.00,
                'status'       => 'active',
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Assets')->first()->id,
                'parent_id'    => null,   // top-level
                'level'        => 1,
                'code'         => _accountCode(LedgerAccountType::where('name', 'Assets')->first()->id ?? 1, null),
            ]
        );

        return $cashLedger;
    }

    public static function ensureBankAccount()
    {
        $storeId = Helpers::get_store_id();

        // Ensure top-level Bank ledger exists
        $bankLedger = StoreAccount::firstOrCreate(
            [
                'store_id'     => $storeId,
                'name' => 'Bank Account',
            ],
            [
                'account_type' => 'common',
                'balance'      => 0.00,
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Assets')->first()->id,
                'entity_type' => 'store',
                'status'       => 'active',
                'parent_id'    => null,   // top-level
                'level'        => 1,
                'code'         => _accountCode(LedgerAccountType::where('name', 'Assets')->first()->id ?? 1, null),
            ]
        );

        return $bankLedger;
    }
    public static function ensureOtherBankAccount()
    {

        // Ensure top-level Bank ledger exists
        $bankLedger = StoreAccount::firstOrCreate(
            [
                'store_id'     => 0,
                'name' => 'Bank Account',
            ],
            [
                'account_type' => 'common',
                'balance'      => 0.00,
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Assets')->first()->id,
                'entity_type' => 'other',
                'status'       => 'active',
                'parent_id'    => null,   // top-level
                'level'        => 1,
                'code'         => _accountCode(LedgerAccountType::where('name', 'Assets')->first()->id ?? 1, null),
            ]
        );

        return $bankLedger;
    }

    public static function ensureDepreciationExpenseAccount()
    {
        $storeId = Helpers::get_store_id();

        $expenseLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => 0,
                'name' => 'Depreciation Expense',
            ],
            [
                'account_type' => 'common',
                'balance' => 0.00,
                'status' => 'active',
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Expenses')->first()->id,
                'entity_type' => 'other',
                'parent_id' => null,   // top-level or assign P&L parent if you have one
                'level' => 1,
                'code' => _accountCode(
                    LedgerAccountType::where('name', 'Expenses')->first()->id ?? 2,
                    null,
                    0
                ),
            ]
        );

        return $expenseLedger;
    }
    public static function ensureAccumulatedDepreciationAccount($assetName, $storeId = null)
    {
        if (!$storeId) {
            $storeId = Helpers::get_store_id();
        }

        $accumLedger = StoreAccount::firstOrCreate(
            [
                'store_id' => $storeId,
                'name' => 'Accumulated Depreciation - ' . $assetName,
            ],
            [
                'account_type' => 'common',  // contra-asset
                'balance' => 0.00,
                'ledger_account_type_id' => LedgerAccountType::where('name', 'Assets')->first()->id,
                'status' => 'active',
                'parent_id' => null,   // top-level or under Assets
                'level' => 1,
                'entity_type' => 'store',
                'code' => _accountCode(
                    LedgerAccountType::where('name', 'Assets')->first()->id ?? 1,
                    null,
                    $storeId
                ),
            ]
        );

        return $accumLedger;
    }




    public static function _createDefaultLedgerAccounts()
    {
        self::ensureBankAccount();
        self::ensureCashAccount();
    }

    public static function _assignFreeTrial($store)
    {
        $submodules = SubModule::where('free_trial_days', '>', 0)->get();
        foreach ($submodules as $key1 =>  $submodule) {

            $storeModule = StoreEnabledModule::where('store_id', $store->id)->where('submodule_id', $submodule->id)->first();
            if (!$storeModule) {
                $storeModule =  new StoreEnabledModule();
            }

            $startDate = now();
            $storeModule->store_id = $store->id;
            $storeModule->submodule_id = $submodule->id;
            $storeModule->start_date = $startDate;
            $storeModule->type = 'free';

            // Add trial days to start_date and subtract 1 to get last trial day
            $trialEndsOn = Carbon::parse($startDate)->addDays($submodule->free_trial_days - 1);
            $storeModule->free_trial_extended_until = $trialEndsOn;
            $storeModule->save();
            $submoduleId = (int) $submodule->id;

            //  Save history
            $h =  new FreeTrialHistory();
            $h->store_id = $store->id;
            $h->submodule_id = $submoduleId;
            $h->assigned_by = 2;
            $h->start_date = $startDate;
            $h->trial_days = $submodule->free_trial_days;
            $h->trial_ends_on = $storeModule->free_trial_extended_until;
            $h->save();
        }
    }
    public static function calc_tax($price, $tax)
    {
        $price_tax = ($price * $tax) / (100 +  $tax);
        return $price_tax;
    }
    public static function product_discount_calculate_vr($variation)
    {
        $price = $variation[0]['mrpprice'] ?? $variation[0]['price'];
        $disc = $variation[0]['discount'] ?? 0;

        $price_discount = ($price / 100) * $disc;

        return [
            'discount_type' =>  'product_discount',
            'discount_amount' => $price_discount
        ];
    }

    public static function _storePrefix($storename)
    {
        $store_prefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $storename)), 0, 3);
        return $store_prefix . "_";
    }
    public static function generateInvoiceId($infix,   $update = true, $serial_num = null, $tax_type = 'gst', $store = null) // for stores only
    {
        if (!$store) {
            $store = Store::find(Helpers::get_store_id());
        }
        $store_prefix = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3);
        $store_serial = $serial_num ?? ($tax_type == 'gst' ? $store->bill_serial_number : $store->non_gst_sno);
        $infix = $infix ?? 'INV';

        $today = now();
        $currentYear = $today->year;
        $financialYearStart = Carbon::createFromDate($currentYear, 4, 1);

        if ($today->month < 4) {
            $financialYearStart->subYear();
        }
        $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // 31 March
        $year = $financialYearStart->format('y') . '-' . $financialYearEnd->format('y');

        do {
            if ($tax_type == 'gst') {
                $invoice_id = $store_prefix . '_' . $infix . '_' . $year . '_' . $store_serial;
            } else {
                $invoice_id = $store_prefix . '_' . $store_serial;
            }

            $manualExists = DB::table('manual_invoices')
                ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
                ->where('invoice_id', $invoice_id)
                ->exists();

            $serviceExists = DB::table('service_invoices')
                ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
                ->where('invoice_id', $invoice_id)
                ->exists();

            if ($manualExists || $serviceExists) {
                $store_serial++;
            }
        } while ($manualExists || $serviceExists);

        if ($update !== false) {
            if ($tax_type == 'gst') {
                $store->bill_serial_number = $store_serial + 1;
            } else {
                $store->non_gst_sno = $store_serial + 1;
            }
            $store->save();
        }
        return $invoice_id;
    }

    public static function generateInvoiceIdAdmin($module = 6)
    {
        if ($module == 6) {
            $prefix = 'MSM';
        } else {
            $prefix = 'MCS';
        }

        $setting = BusinessSetting::where('key', 'admin_bill_serial_number')->first();

        $serial = $setting->value;

        if (date('m') > 3) { // march
            $year = date('y') . '-' . date('y') + 1;
        } else {
            $year = date('y') - 1 . '-' . date('y');
        }

        $invoice_id = $prefix . '_' . $year . '_' .  $serial;

        // Increment the value
        $setting->value = (int) $setting->value + 1;
        $setting->save();

        return $invoice_id;
    }
    public static function generateInvoiceSerialAdmin($module = 6)
    {
        if ($module == 6) {
            $prefix = 'MSM';
        } else {
            $prefix = 'MCS';
        }

        $setting = BusinessSetting::where('key', 'admin_bill_serial_number')->first();

        $serial = $setting->value;

        if (date('m') > 3) { // march
            $year = date('y') . '-' . date('y') + 1;
        } else {
            $year = date('y') - 1 . '-' . date('y');
        }

        $invoice_id = $prefix . '_' . $year . '_' .  $serial;

        // Increment the value
        $setting->value = (int) $setting->value + 1;
        $setting->save();

        return $invoice_id;
    }

    public static function get_store_range($item_id, $zone_ids, $user_id, $storeId = null)
    {
        $store_limit = Helpers::get_settings('leads_distribut_vendor');
        $zId = json_decode($zone_ids, true);

        $item = DB::table('items')->where('id', $item_id)->first();

        if (!$item || empty($item->store_ids)) return [];

        // Step 1: Clean store IDs
        $storeIds = array_unique(array_filter(explode(',', trim($item->store_ids))));
        $storeIds = array_map('intval', $storeIds); // Ensure all are integers

        // Step 2: Get only active, zone-matching stores
        $existingStoreIds = DB::table('stores')
            ->whereIn('id', $storeIds)
            ->where('status', 1)
            ->whereIn('zone_id', $zId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $storeIds = array_values(array_intersect($storeIds, $existingStoreIds));

        if (empty($storeIds)) return [];

        // Step 3: Track usage
        $oldIds = _getIdsFrist($item_id);
        _trackStoreIds('get_store_range', implode(',', $storeIds), $item_id, '-', $user_id . '_user', $oldIds);

        // Step 4: Get last distribution
        $lastDistribution = DB::table('leads_distributions')
            ->where('item_id', $item_id)
            ->orderBy('id', 'desc')
            ->first();

        $index = 0;
        if ($lastDistribution) {
            $lastAssignedStoreId = (int)$lastDistribution->to_id;
            $lastIndex = array_search($lastAssignedStoreId, $storeIds);

            if ($lastIndex !== false) {
                $index = ($lastIndex + 1) % count($storeIds);
            }
        }

        // Step 5: Prepare store chunk
        $prioritized = [];

        // Check if storeId is valid and exists in the filtered list
        if ($storeId && in_array((int)$storeId, $storeIds)) {
            $prioritized[] = (int)$storeId;
            // Remove storeId from the list to avoid duplication
            $storeIds = array_values(array_diff($storeIds, [$storeId]));

            // Start rotation after the prioritized storeId
            $index = array_search((int)$storeId, $storeIds);
            $index = $index === false ? 0 : ($index + 1) % count($storeIds);
        }

        // Rotate the remaining store IDs based on last distribution index
        $rotated = array_merge(array_slice($storeIds, $index), array_slice($storeIds, 0, $index));

        // Fill the rest of the chunk after prioritizing
        $chunkSize = $store_limit - count($prioritized);
        $storesChunk = array_merge($prioritized, array_slice($rotated, 0, $chunkSize));

        $storesChunk = array_unique($storesChunk);

        $storesChunk = array_unique($storesChunk);
        $id_from = reset($storesChunk);
        $id_to = end($storesChunk);

        // Step 6: Save
        $data = [
            'from_id' => $id_from,
            'to_id' => $id_to,
            'item_id' => $item_id,
            'store_ids' => implode(',', $storesChunk),
            'updated_at' => now(),
        ];

        if ($lastDistribution) {
            DB::table('leads_distributions')->where('item_id', $item_id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('leads_distributions')->insert($data);
        }

        return $storesChunk;
    }


    public static function clean_item_store_ids($item_id)
    {
        $item = DB::table('items')->where('id', $item_id)->first();

        if (!$item || empty($item->store_ids)) {
            return false; // Nothing to process
        }

        $storeIds = array_filter(explode(',', $item->store_ids));

        if (empty($storeIds)) {
            return false;
        }

        // Get stores where service_1 or service_2 includes this item_id
        $validStores = DB::table('stores')
            ->whereIn('id', $storeIds)
            ->where(function ($query) use ($item_id) {
                $query->whereRaw('FIND_IN_SET(?, services_1)', [$item_id])
                    ->orWhereRaw('FIND_IN_SET(?, services_2)', [$item_id]);
            })
            ->pluck('id')
            ->toArray();

        // Keep only valid stores
        $filteredStoreIds = array_intersect($storeIds, $validStores);

        // Only update if there is a change
        if (implode(',', $storeIds) !== implode(',', $filteredStoreIds)) {
            DB::table('items')
                ->where('id', $item_id)
                ->update(['store_ids' => implode(',', $filteredStoreIds)]);

            return true; // Updated
        }

        return false; // No change
    }




    public static function schedule_order()
    {
        return (bool)BusinessSetting::where(['key' => 'schedule_order'])->first()->value;
    }
    public static function get_lead_exp_minutes()
    {

        $unit =  BusinessSetting::where(['key' => 'exp_unit'])->first()->value;
        $count =  BusinessSetting::where(['key' => 'exp_count'])->first()->value;

        if ($unit == 'hours') {
            $exp_time = $count * 60;
        } else {
            $exp_time = $count;
        }
        return $exp_time;
    }
    public static function get_lead_exp_time()
    {

        $unit =  BusinessSetting::where(['key' => 'exp_unit'])->first()->value;
        $count =  BusinessSetting::where(['key' => 'exp_count'])->first()->value;

        if ($unit == 'hours') {
            $exp_time = $count * 60;
        } else {
            $exp_time = $count;
        }
        return $exp_time . ' ' . $unit;
    }
    public static function _addWelcomeCouponsIfExist($store)
    {
        try {
            $coupons =  ServiceCoupon::where('user_type_id', 0)->where('user_type', 'new_stores')->get();
            foreach ($coupons as $coupon) {
                // print_r($coupon);
                $coupon2 = new ServiceCoupon();
                $coupon2->user_type_id = $store->id;
                $coupon2->user_type = 'store';
                $coupon2->code = $coupon->code;
                $coupon2->amount = $coupon->amount;
                $coupon2->use_limit = $coupon->use_limit;
                $coupon2->save();

                $smsTemplate = "Great news! You've got a COUPON: " . $coupon->code . ". Use it now! Check the My Chitti Vendor App for details. - My Chitti Team";
                _sendSMS($store->phone, $smsTemplate);
            }
            return true;
        } catch (\Throwable $th) {
            throw $th;
            return true;
        }
    }

    //======================= ZONE (TRANSFER TO SEPERATE ZONE HELPER) ======================

    public static function getAreasByZoneIds(array $zoneIds, int $limit = 5)
    {
        // Safety: keep only numeric IDs
        $zoneIds = array_values(array_filter($zoneIds, 'is_numeric'));

        if (empty($zoneIds)) {
            return collect();
        }

        $randomZoneId = collect($zoneIds)->random();

        $areas = \Cache::remember(
            "zone_areas_$randomZoneId",
            86400, // 24 hours
            function () use ($randomZoneId) {

                $zone = Zone::find($randomZoneId);

                if (!$zone || !$zone->coordinates) {
                    return [];
                }

                return self::getAreasFromGoogleForZone($zone->coordinates);
            }
        );

        if (empty($areas)) {
            return collect();
        }

        return collect(self::removeDuplicates($areas))
            ->pluck('name')
            ->shuffle()
            ->take($limit)
            ->values();
    }


    public static function getAreasFromGoogleForZone($coordinates)
    {
        // Get API key from BusinessSetting table
        $apiKey = \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value ?? config('services.google.api_key');

        if (!$apiKey) {
            echo "ERROR: Google API key not configured\n";
            return ['error' => 'Google API key not configured'];
        }


        $polygon = self::parseZoneCoordinates($coordinates);

        if (empty($polygon)) {
            echo "ERROR: Failed to parse coordinates\n";
            return [];
        }

        $bounds = self::calculateBounds($polygon);

        // Get areas using flexible grid sampling
        $areas = self::getFlexibleGridAreas($bounds, $apiKey);

        return $areas;
    }

    public static function parseZoneCoordinates($coordinates)
    {
        $polygon = [];

        try {

            if (preg_match('/POLYGON\s*\(\((.*?)\)\)/i', $coordinates, $matches)) {

                $coordString = $matches[1];

                // Split by comma to get individual coordinate pairs
                $coordPairs = explode(',', $coordString);


                $lastCord = null;

                foreach ($coordPairs as $index => $pair) {
                    $coords = preg_split('/\s+/', trim($pair));

                    if (count($coords) >= 2) {
                        $lng = (float) $coords[0];
                        $lat = (float) $coords[1];

                        if ($index == 0) {
                            $lastCord = ['lat' => $lat, 'lng' => $lng];
                        }

                        $polygon[] = [
                            'lat' => $lat,
                            'lng' => $lng
                        ];
                    } else {
                        echo "  -> WARNING: Invalid coordinate pair: " . $pair . "\n";
                    }
                }
            } else {

                $coordinates = preg_replace('/\s+/', '', $coordinates);
                $coordPairs = explode('),(', trim($coordinates, '()'));

                $lastCord = null;

                foreach ($coordPairs as $index => $pair) {
                    $coords = explode(',', trim($pair, '()'));

                    if (count($coords) >= 2) {
                        $lat = (float) trim($coords[0]);
                        $lng = (float) trim($coords[1]);

                        echo "Pair $index: lat=$lat, lng=$lng\n";

                        if ($index == 0) {
                            $lastCord = ['lat' => $lat, 'lng' => $lng];
                        }

                        $polygon[] = [
                            'lat' => $lat,
                            'lng' => $lng
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            echo "ERROR parsing coordinates: " . $e->getMessage() . "\n";
        }

        return $polygon;
    }

    public static function calculateBounds($polygon)
    {
        $lats = array_column($polygon, 'lat');
        $lngs = array_column($polygon, 'lng');

        return [
            'north' => max($lats),
            'south' => min($lats),
            'east' => max($lngs),
            'west' => min($lngs),
            'center' => [
                'lat' => (max($lats) + min($lats)) / 2,
                'lng' => (max($lngs) + min($lngs)) / 2
            ]
        ];
    }


    public static function getFlexibleGridAreas($bounds, $apiKey)
    {
        $areas = [];

        // Start with smaller grid for testing - 3x3 = 9 points
        $gridSize = 3;

        $points = self::generateGridPoints($bounds, $gridSize);

        // echo "Generated " . count($points) . " grid points\n";

        $apiCallCount = 0;

        foreach ($points as $index => $point) {
            try {
                // echo "Checking point $index: " . $point['lat'] . ',' . $point['lng'] . "\n";

                // Request ALL types, we'll filter after
                $response = \Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => $point['lat'] . ',' . $point['lng'],
                    'key' => $apiKey
                ]);

                $apiCallCount++;

                if ($response->successful()) {
                    $data = $response->json();

                    // echo "API Response status: " . $response->status() . "\n";

                    if (isset($data['status']) && $data['status'] === 'OK') {
                        if (isset($data['results']) && !empty($data['results'])) {
                            // echo "Got " . count($data['results']) . " results for point $index\n";

                            // Process ALL results to extract area names
                            foreach ($data['results'] as $result) {
                                $extracted = self::extractAreaNames($result);

                                if (!empty($extracted)) {
                                    $areas = array_merge($areas, $extracted);
                                }
                            }
                        } else {
                            // echo "WARNING: No results for point $index\n";
                        }
                    } else {
                        // echo "ERROR: Geocoding API error: " . ($data['status'] ?? 'unknown') . "\n";
                        // echo "Error message: " . ($data['error_message'] ?? 'none') . "\n";
                    }
                } else {
                    // echo "ERROR: HTTP request failed: " . $response->status() . "\n";
                }

                // Delay to respect rate limits
                // usleep(150000); // 0.15 second

            } catch (\Exception $e) {
                // echo "ERROR for point $index: " . $e->getMessage() . "\n";
            }
        }

        // echo "Total API calls made: $apiCallCount\n";
        // echo "Total areas before dedup: " . count($areas) . "\n";

        // Remove duplicates and return
        $unique = self::removeDuplicates($areas);

        // echo "Total unique areas: " . count($unique) . "\n";

        return $unique;
    }

    public static function extractAreaNames($result)
    {
        $extracted = [];

        // Define what we consider "areas" with priority
        $areaTypes = [
            'sublocality_level_1' => 4,
            'sublocality_level_2' => 4,
            'sublocality_level_3' => 4,
            'sublocality_level_4' => 4,
            'sublocality' => 2,
            'administrative_area_level_3' => 1,
            // 'postal_code' => 5,
            'locality' => 3,
            'political' => 1,
        ];

        foreach ($result['address_components'] as $component) {
            foreach ($component['types'] as $type) {
                // echo $type . '<br>'; 
                // Check if this is an area type we want
                if (isset($areaTypes[$type])) {
                    $extracted[] = [
                        'name' => $component['long_name'],
                        'type' => $type,
                        'priority' => $areaTypes[$type],
                        // 'formatted_address' => $result['formatted_address'] ?? '',
                        // 'latitude' => $result['geometry']['location']['lat'] ?? null,
                        // 'longitude' => $result['geometry']['location']['lng'] ?? null,
                        // 'place_id' => $result['place_id'] ?? null
                    ];
                }
            }
        }

        return $extracted;
    }

    public static function generateGridPoints($bounds, $gridSize)
    {
        $points = [];

        $latStep = ($bounds['north'] - $bounds['south']) / ($gridSize + 1);
        $lngStep = ($bounds['east'] - $bounds['west']) / ($gridSize + 1);

        for ($i = 1; $i <= $gridSize; $i++) {
            for ($j = 1; $j <= $gridSize; $j++) {
                $points[] = [
                    'lat' => $bounds['south'] + ($latStep * $i),
                    'lng' => $bounds['west'] + ($lngStep * $j)
                ];
            }
        }

        return $points;
    }

    public static function removeDuplicates($areas)
    {
        $unique = [];
        $seen = [];

        foreach ($areas as $area) {

            // ✅ SAFETY CHECK
            if (!isset($area['name'])) {
                continue;
            }

            $priority = $area['priority'] ?? 999; // fallback priority
            $key = strtolower(trim($area['name']));

            if (!isset($seen[$key]) || $priority < ($seen[$key]['priority'] ?? 999)) {
                $seen[$key] = [
                    'name' => $area['name'],
                    'type' => $area['type'] ?? null,
                    'priority' => $priority,
                ];
            }
        }

        return collect($seen)
            ->sortBy('name')
            ->values()
            ->toArray();
    }


    // ZONE HELPER ===========================================


    public static function _setLocation()
    {

        $defaultLat = 13.637410054671042;
        $defaultLon = 79.50621055192981;
        $defaultCity = 'Tirupati';

        // $defaultAddr = $location['city'] . ',' . $location['region'] . ',' . $location['country'];
        $defaultAddr = '17/5051A, Rajendra Nagar, Tilak Nagar, Tirupati, Andhra Pradesh 517501, India';

        if (session()->has('latitude') && session('latitude') != '') {
            $locationRes['latitude'] = session()->get('latitude');
        } else {
            $locationRes['latitude'] = $defaultLat;
            session()->put('latitude', $defaultLat);
        }
        if (session()->has('longitude') && session('longitude') != '') {
            $locationRes['longitude'] = session()->get('longitude');
        } else {
            $locationRes['longitude'] = $defaultLon;
            session()->put('longitude', $locationRes['longitude']);
        }
        if (session()->has('customer_city') && session('customer_city') != '') {
            $locationRes['customer_city'] = session()->get('customer_city');
        } else {
            $locationRes['customer_city'] = $defaultCity;
            session()->put('customer_city', $locationRes['customer_city']);
        }

        if (session()->has('customer_address')) {
            $locationRes['customer_address'] = session()->get('customer_address');
        } else {
            session()->put('customer_address', $defaultAddr);
        }

        $mId  = 5;
        session(['moduleId' =>  $mId]);
        $locationRes['module'] = Module::find($mId);

        $zone_ids = array_column(Zone::query()->where('status', 1)->whereContains('coordinates', new Point($locationRes['latitude'], $locationRes['longitude'], POINT_SRID))->latest()->get(['id'])->toArray(), 'id');
        $locationRes['zone_id'] = json_encode($zone_ids);
        session()->put('zone_ids', json_encode($zone_ids));
        Config::set('module.current_module_data', $locationRes['module']);
        Config::set('module.current_module_id', $mId);

        return $locationRes;
    }



    public static function combinations($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public static function variation_price($product, $variation)
    {
        $match = json_decode($variation, true)[0];
        $result = ['price' => 0, 'discount' => 0, 'mrpprice' => 0, 'stock' => 0];
        foreach (json_decode($product['variations'], true) as $property => $value) {
            if ($value['type'] == $match['type']) {
                $discount = $value['discount'] ?? 0;
                $mrpprice = $value['mrpprice'] ?? $value['price'];
                $askingprice = $value['askingprice'] ?? $value['price'];
                $result = ['price' => $value['price'], 'discount' => $discount, 'askingprice' => $askingprice,  'mrpprice' => $mrpprice, 'stock' => $value['stock'] ?? 0];
            }
        }
        return $result;
    }

    public static function address_data_formatting($data)
    {
        foreach ($data as $key => $item) {
            $data[$key]['zone_ids'] = array_column(Zone::query()->whereContains('coordinates', new Point($item->latitude, $item->longitude, POINT_SRID))->latest()->get(['id'])->toArray(), 'id');
        }
        return $data;
    }

    public static function cart_product_data_formatting(
        $data,
        $selected_variation,
        $selected_addons,
        $selected_addon_quantity,
        $trans = false,
        $local = 'en'
    ) {
        $variations = [];
        $categories = [];
        $category_ids = gettype($data['category_ids']) == 'array' ? $data['category_ids'] : json_decode($data['category_ids'], true);
        foreach ($category_ids as $value) {
            $category_name = Category::where('id', $value['id'])->pluck('name');
            $categories[] = ['id' => (string)$value['id'], 'position' => $value['position'], 'name' => data_get($category_name, '0', 'NA')];
        }
        $data['category_ids'] = $categories;
        $attributes = gettype($data['attributes']) == 'array' ? $data['attributes'] : json_decode($data['attributes'], true);
        $data['attributes'] = $attributes;
        $choice_options = gettype($data['choice_options']) == 'array' ? $data['choice_options'] : json_decode($data['choice_options'], true);
        $data['choice_options'] = $choice_options;
        $add_ons = gettype($data['add_ons']) == 'array' ? $data['add_ons'] : json_decode($data['add_ons'], true);
        $data_addons = self::addon_data_formatting(AddOn::whereIn('id', $add_ons)->active()->get(), true, $trans, $local);
        $selected_data = array_combine($selected_addons, $selected_addon_quantity);
        foreach ($data_addons as $addon) {
            $addon_id = $addon['id'];
            if (in_array($addon_id, $selected_addons)) {
                $addon['isChecked'] = true;
                $addon['quantity'] = $selected_data[$addon_id];
            } else {
                $addon['isChecked'] = false;
                $addon['quantity'] = 0;
            }
        }
        $data['addons'] = $data_addons;
        $data_variations = gettype($data['variations']) == 'array' ? $data['variations'] : json_decode($data['variations'], true);
        foreach ($data_variations as $var) {
            array_push($variations, [
                'type' => $var['type'],
                'price' => (float)$var['price'],
                'stock' => (int)($var['stock'] ?? 0)
            ]);
        }
        if ($data->title) {
            $data['name'] = $data->title;
            unset($data['title']);
        }
        if ($data->start_time) {
            $data['available_time_starts'] = $data->start_time->format('H:i');
            unset($data['start_time']);
        }
        if ($data->end_time) {
            $data['available_time_ends'] = $data->end_time->format('H:i');
            unset($data['end_time']);
        }
        if ($data->start_date) {
            $data['available_date_starts'] = $data->start_date->format('Y-m-d');
            unset($data['start_date']);
        }
        if ($data->end_date) {
            $data['available_date_ends'] = $data->end_date->format('Y-m-d');
            unset($data['end_date']);
        }
        $data['variations'] = $variations;
        $data_variation = $data['food_variations'] ? (gettype($data['food_variations']) == 'array' ? $data['food_variations'] : json_decode($data['food_variations'], true)) : [];
        if ($data->module->module_type == 'food') {
            foreach ($selected_variation as $selected_item) {
                foreach ($data_variation as &$all_item) {
                    if ($selected_item["name"] === $all_item["name"]) {
                        foreach ($all_item["values"] as &$value) {
                            if (in_array($value["label"], $selected_item["values"]["label"])) {
                                $value["isSelected"] = true;
                            } else {
                                $value["isSelected"] = false;
                            }
                        }
                    }
                }
            }
        }
        $data['food_variations'] = $data_variation;
        $data['store_name'] = $data->store->name;
        $data['is_campaign'] = $data->store?->campaigns_count > 0 ? 1 : 0;
        $data['module_type'] = $data->module->module_type;
        $data['zone_id'] = $data->store->zone_id;
        $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
            $query->Active()->Running();
        })
            ->where(['item_id' => $data['id']])->first();
        $data['flash_sale'] = (int) (($running_flash_sale) ? 1 : 0);
        $data['stock'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->available_stock : $data['stock'];
        $data['discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount : $data['discount'];
        $data['discount_type'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount_type : $data['discount_type'];
        $data['store_discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 0 : (self::get_store_discount($data->store) ? $data->store?->discount->discount : 0);
        $data['schedule_order'] = $data->store->schedule_order;
        $data['rating_count'] = (int)($data->rating ? array_sum(json_decode($data->rating, true)) : 0);
        $data['avg_rating'] = (float)($data->avg_rating ? $data->avg_rating : 0);
        $data['min_delivery_time'] =  (int) explode('-', $data->store->delivery_time)[0] ?? 0;
        $data['max_delivery_time'] =  (int) explode('-', $data->store->delivery_time)[1] ?? 0;
        $data['common_condition_id'] =  (int) $data->pharmacy_item_details?->common_condition_id ?? 0;
        $data['is_basic'] =  (int) $data->pharmacy_item_details?->is_basic ?? 0;

        unset($data['pharmacy_item_details']);
        unset($data['store']);
        unset($data['rating']);


        return $data;
    }

    public static function product_data_formatting($data, $multi_data = false, $trans = false, $local = 'en', $temp_product = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $variations = [];
                if ($item->title) {
                    $item['name'] = $item->title;
                    unset($item['title']);
                }
                if ($item->start_time) {
                    $item['available_time_starts'] = $item->start_time->format('H:i');
                    unset($item['start_time']);
                }
                if ($item->end_time) {
                    $item['available_time_ends'] = $item->end_time->format('H:i');
                    unset($item['end_time']);
                }

                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }
                $item['recommended'] = (int) $item->recommended;
                $categories = [];
                foreach (json_decode($item['category_ids']) as $value) {
                    $category_name = Category::where('id', $value->id)->pluck('name');
                    $categories[] = ['id' => (string)$value->id, 'position' => $value->position, 'name' => data_get($category_name, '0', 'NA')];
                }
                $item['category_ids'] = $categories;
                $item['attributes'] = json_decode($item['attributes']);
                $item['choice_options'] = json_decode($item['choice_options']);
                $item['add_ons'] = self::addon_data_formatting(AddOn::withoutGlobalScope('translate')->whereIn('id', json_decode($item['add_ons'], true))->active()->get(), true, $trans, $local);
                foreach (json_decode($item['variations'], true) as $var) {
                    $vrDets = ItemVariationDetail::where('item_id', $item['id'])->where('type', $var['type'])->first();

                    array_push($variations, [
                        'type' => $var['type'],
                        'price' => (float) round($var['price']),
                        'mrpprice' => isset($var['mrpprice']) ? (float) round($var['mrpprice']) : (float) round($var['price']),
                        'description' => $vrDets ?  $vrDets->description : null,
                        'specifications' => $vrDets ? $vrDets->specifications : null,
                        'images' => $vrDets ?  json_decode($vrDets->images) : null,
                        'stock' => (int)($var['stock'] ?? 0)
                    ]);
                }
                $item['variations'] = $variations;
                $item['food_variations'] = $item['food_variations'] ? json_decode($item['food_variations'], true) : '';
                $item['module_type'] = $item->module->module_type;
                $item['store_name'] = $item->store?->name;
                $item['is_campaign'] = $item->store?->campaigns_count > 0 ? 1 : 0;
                $item['zone_id'] = $item->store?->zone_id;
                $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
                    $query->Active()->Running();
                })
                    ->where(['item_id' => $item['id']])->first();
                $item['flash_sale'] = (int) ((($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 1 : 0));
                $item['stock'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->available_stock : $item['stock'];
                $item['discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount : $item['discount'];
                $item['discount_type'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount_type : $item['discount_type'];
                $item['store_discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 0 : (self::get_store_discount($item->store) ? $item->store?->discount->discount : 0);
                $item['schedule_order'] = $item->store?->schedule_order;
                $item['delivery_time'] = $item->store?->delivery_time;
                $item['free_delivery'] = $item->store?->free_delivery;
                $item['tax'] = $item->tax;
                $item['unit'] = $item->unit;
                $item['rating_count'] = (int)($item->rating ? array_sum(json_decode($item->rating, true)) : 0);
                $item['avg_rating'] = (float)($item->avg_rating ? $item->avg_rating : 0);
                $item['recommended'] = (int) $item->recommended;
                $item['min_delivery_time'] = isset(explode('-', $item->store?->delivery_time)[0]) ? (int) explode('-', $item->store?->delivery_time)[0] : 0;
                $item['max_delivery_time'] = isset(explode('-', $item->store?->delivery_time)[1]) ? (int) explode('-', $item->store?->delivery_time)[1] : 0;
                $item['common_condition_id'] =  (int) $item->pharmacy_item_details?->common_condition_id ?? 0;
                $item['is_basic'] =  (int) $item->pharmacy_item_details?->is_basic ?? 0;

                unset($item['pharmacy_item_details']);
                unset($item['store']);
                unset($item['rating']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            $variations = [];
            $categories = [];
            foreach (json_decode($data['category_ids']) as $value) {
                $category_name = Category::where('id', $value->id)->pluck('name');
                $categories[] = ['id' => (string)$value->id, 'position' => $value->position, 'name' => data_get($category_name, '0', 'NA')];
            }
            $data['category_ids'] = $categories;

            $data['attributes'] = json_decode($data['attributes']);
            $data['choice_options'] = json_decode($data['choice_options']);
            $data['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($data['add_ons']))->active()->get(), true, $trans, $local);
            foreach (json_decode($data['variations'], true) as $var) {

                $vrDets = ItemVariationDetail::where('item_id', $data['id'])->where('type', $var['type'])->first();

                array_push($variations, [
                    'type' => $var['type'],
                    'price' => (float)$var['price'],
                    'mrpprice' =>  isset($var['mrpprice']) ? (float) round($var['mrpprice']) : (float) round($var['price']),
                    'description' => $vrDets ?  $vrDets->description : null,
                    'specifications' => $vrDets ? $vrDets->specifications : null,
                    'images' => $vrDets ?  json_decode($vrDets->images) : null,
                    'stock' => (int)($var['stock'] ?? 0)
                ]);
            }
            if ($data->title) {
                $data['name'] = $data->title;
                unset($data['title']);
            }
            if ($data->start_time) {
                $data['available_time_starts'] = $data->start_time->format('H:i');
                unset($data['start_time']);
            }
            if ($data->end_time) {
                $data['available_time_ends'] = $data->end_time->format('H:i');
                unset($data['end_time']);
            }
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }
            $data['variations'] = $variations;
            $data['food_variations'] = $data['food_variations'] ? json_decode($data['food_variations'], true) : '';
            $data['store_name'] = $data->store?->name;
            $data['is_campaign'] = $data->store?->campaigns_count > 0 ? 1 : 0;
            $data['module_type'] = $data->module->module_type;
            $data['zone_id'] = $data->store?->zone_id;
            $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
                $query->Active()->Running();
            })
                ->where(['item_id' => $data['id']])->first();
            $data['flash_sale'] = (int) (($running_flash_sale) ? 1 : 0);
            $data['stock'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->available_stock : $data['stock'];
            $data['discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount : $data['discount'];
            $data['discount_type'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount_type : $data['discount_type'];
            $data['store_discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 0 : (self::get_store_discount($data->store) ? $data->store?->discount->discount : 0);
            $data['schedule_order'] = $data->store?->schedule_order;
            $data['rating_count'] = (int)($data->rating ? array_sum(json_decode($data->rating, true)) : 0);
            $data['avg_rating'] = (float)($data->avg_rating ? $data->avg_rating : 0);
            $data['min_delivery_time'] = (int) ($data->store?->delivery_time ? explode('-', $data->store->delivery_time)[0] ?? 0 : 0);
            $data['max_delivery_time'] = (int) ($data->store?->delivery_time ? explode('-', $data->store->delivery_time)[1] ?? 0 : 0);

            $data['common_condition_id'] =  (int) $data->pharmacy_item_details?->common_condition_id ?? 0;
            $data['is_basic'] =  (int) $data->pharmacy_item_details?->is_basic ?? 0;
            if ($temp_product == true) {
                $data['tags'] = \App\Models\Tag::whereIn('id', json_decode($data?->tag_ids))->get(['tag', 'id']);
            }
            unset($data['pharmacy_item_details']);
            unset($data['store']);
            unset($data['rating']);
        }

        return $data;
    }

    public static function product_data_formatting_translate($data, $multi_data = false, $trans = false, $local = 'en')
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $variations = [];
                if ($item->title) {
                    $item['name'] = $item->title;
                    unset($item['title']);
                }
                if ($item->start_time) {
                    $item['available_time_starts'] = $item->start_time->format('H:i');
                    unset($item['start_time']);
                }
                if ($item->end_time) {
                    $item['available_time_ends'] = $item->end_time->format('H:i');
                    unset($item['end_time']);
                }

                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }
                $item['recommended'] = (int) $item->recommended;
                $categories = [];
                foreach (json_decode($item['category_ids']) as $value) {
                    $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
                }
                $item['category_ids'] = $categories;
                $item['attributes'] = json_decode($item['attributes']);
                $item['choice_options'] = json_decode($item['choice_options']);
                $item['add_ons'] = self::addon_data_formatting(AddOn::withoutGlobalScope('translate')->whereIn('id', json_decode($item['add_ons'], true))->active()->get(), true, $trans, $local);
                foreach (json_decode($item['variations'], true) as $var) {
                    array_push($variations, [
                        'type' => $var['type'],
                        'price' => (float)$var['price'],
                        'stock' => (int)($var['stock'] ?? 0)
                    ]);
                }
                $item['variations'] = $variations;
                $item['food_variations'] = $item['food_variations'] ? json_decode($item['food_variations'], true) : '';
                $item['module_type'] = $item->module->module_type;
                $item['store_name'] = $item->store->name;
                $item['zone_id'] = $item->store->zone_id;
                $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
                    $query->Active()->Running();
                })
                    ->where(['item_id' => $data['id']])->first();
                $data['flash_sale'] = (int) (($running_flash_sale) ? 1 : 0);
                $data['stock'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->available_stock : $data['stock'];
                $data['discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount : $data['discount'];
                $data['discount_type'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount_type : $data['discount_type'];
                $data['store_discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 0 : (self::get_store_discount($data->store) ? $data->store?->discount->discount : 0);
                $item['schedule_order'] = $item->store->schedule_order;
                $item['tax'] = $item->store->tax;
                $item['rating_count'] = (int)($item->rating ? array_sum(json_decode($item->rating, true)) : 0);
                $item['avg_rating'] = (float)($item->avg_rating ? $item->avg_rating : 0);
                $item['recommended'] = (int) $item->recommended;

                if ($trans) {
                    $item['translations'][] = [
                        'translationable_type' => 'App\Models\Item',
                        'translationable_id' => $item->id,
                        'locale' => 'en',
                        'key' => 'name',
                        'value' => $item->name
                    ];

                    $item['translations'][] = [
                        'translationable_type' => 'App\Models\Item',
                        'translationable_id' => $item->id,
                        'locale' => 'en',
                        'key' => 'description',
                        'value' => $item->description
                    ];
                }

                if (count($item['translations']) > 0) {
                    foreach ($item['translations'] as $translation) {
                        if ($translation['locale'] == $local) {
                            if ($translation['key'] == 'name') {
                                $item['name'] = $translation['value'];
                            }

                            if ($translation['key'] == 'title') {
                                $item['name'] = $translation['value'];
                            }

                            if ($translation['key'] == 'description') {
                                $item['description'] = $translation['value'];
                            }
                        }
                    }
                }
                if (!$trans) {
                    unset($item['translations']);
                }

                unset($item['store']);
                unset($item['rating']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            $variations = [];
            $categories = [];
            foreach (json_decode($data['category_ids']) as $value) {
                $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
            }
            $data['category_ids'] = $categories;

            $data['attributes'] = json_decode($data['attributes']);
            $data['choice_options'] = json_decode($data['choice_options']);
            $data['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($data['add_ons']))->active()->get(), true, $trans, $local);
            foreach (json_decode($data['variations'], true) as $var) {
                array_push($variations, [
                    'type' => $var['type'],
                    'price' => (float)$var['price'],
                    'stock' => (int)($var['stock'] ?? 0)
                ]);
            }
            if ($data->title) {
                $data['name'] = $data->title;
                unset($data['title']);
            }
            if ($data->start_time) {
                $data['available_time_starts'] = $data->start_time->format('H:i');
                unset($data['start_time']);
            }
            if ($data->end_time) {
                $data['available_time_ends'] = $data->end_time->format('H:i');
                unset($data['end_time']);
            }
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }
            $data['variations'] = $variations;
            $data['food_variations'] = $data['food_variations'] ? json_decode($data['food_variations'], true) : '';
            $data['store_name'] = $data->store->name;
            $data['module_type'] = $data->module->module_type;
            $data['zone_id'] = $data->store->zone_id;
            $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
                $query->Active()->Running();
            })
                ->where(['item_id' => $data['id']])->first();
            $data['flash_sale'] = (int) (($running_flash_sale) ? 1 : 0);
            $data['stock'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->available_stock : $data['stock'];
            $data['discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount : $data['discount'];
            $data['discount_type'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? $running_flash_sale->discount_type : $data['discount_type'];
            $data['store_discount'] = ($running_flash_sale && ($running_flash_sale->available_stock > 0)) ? 0 : (self::get_store_discount($data->store) ? $data->store?->discount->discount : 0);
            $data['schedule_order'] = $data->store->schedule_order;
            $data['rating_count'] = (int)($data->rating ? array_sum(json_decode($data->rating, true)) : 0);
            $data['avg_rating'] = (float)($data->avg_rating ? $data->avg_rating : 0);

            if ($trans) {
                $data['translations'][] = [
                    'translationable_type' => 'App\Models\Item',
                    'translationable_id' => $data->id,
                    'locale' => 'en',
                    'key' => 'name',
                    'value' => $data->name
                ];

                $data['translations'][] = [
                    'translationable_type' => 'App\Models\Item',
                    'translationable_id' => $data->id,
                    'locale' => 'en',
                    'key' => 'description',
                    'value' => $data->description
                ];
            }

            if (count($data['translations']) > 0) {
                foreach ($data['translations'] as $translation) {
                    if ($translation['locale'] == $local) {
                        if ($translation['key'] == 'name') {
                            $data['name'] = $translation['value'];
                        }

                        if ($translation['key'] == 'title') {
                            $item['name'] = $translation['value'];
                        }

                        if ($translation['key'] == 'description') {
                            $data['description'] = $translation['value'];
                        }
                    }
                }
            }
            if (!$trans) {
                unset($data['translations']);
            }

            unset($data['store']);
            unset($data['rating']);
        }

        return $data;
    }

    public static function addon_data_formatting($data, $multi_data = false, $trans = false, $local = 'en')
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                // if ($trans) {
                //     $item['translations'][] = [
                //         'translationable_type' => 'App\Models\AddOn',
                //         'translationable_id' => $item->id,
                //         'locale' => 'en',
                //         'key' => 'name',
                //         'value' => $item->name
                //     ];
                // }
                // if (count($item->translations) > 0) {
                //     foreach ($item['translations'] as $translation) {
                //         if ($translation['locale'] == $local && $translation['key'] == 'name') {
                //             $item['name'] = $translation['value'];
                //         }
                //     }
                // }

                // if (!$trans) {
                //     unset($item['translations']);
                // }

                $storage[] = $item;
            }
            $data = $storage;
        } else if (isset($data)) {
            // if ($trans) {
            //     $data['translations'][] = [
            //         'translationable_type' => 'App\Models\AddOn',
            //         'translationable_id' => $data->id,
            //         'locale' => 'en',
            //         'key' => 'name',
            //         'value' => $data->name
            //     ];
            // }

            // if (count($data->translations) > 0) {
            //     foreach ($data['translations'] as $translation) {
            //         if ($translation['locale'] == $local && $translation['key'] == 'name') {
            //             $data['name'] = $translation['value'];
            //         }
            //     }
            // }

            // if (!$trans) {
            //     unset($data['translations']);
            // }
        }
        return $data;
    }

    public static function category_data_formatting($data, $multi_data = false, $trans = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                if (count($item->translations) > 0) {
                    $item->name = $item->translations[0]['value'];
                }

                if (!$trans) {
                    unset($item['translations']);
                }

                $storage[] = $item;
            }
            $data = $storage;
        } else if (isset($data)) {
            if (count($data->translations) > 0) {
                $data->name = $data->translations[0]['value'];
            }

            if (!$trans) {
                unset($data['translations']);
            }
        }
        return $data;
    }

    public static function parcel_category_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                // if (count($item['translations']) > 0) {
                //     $translate = array_column($item['translations']->toArray(), 'value', 'key');
                //     $item['name'] = $translate['name'];
                //     $item['description'] = $translate['description'];
                //     unset($item['translations']);
                // }
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            // if (count($data['translations']) > 0) {
            //     $translate = array_column($data['translations']->toArray(), 'value', 'key');
            //     $data['title'] = $translate['title'];
            //     $data['description'] = $translate['description'];
            //     unset($data['translations']);
            // }
        }
        return $data;
    }

    public static function basic_campaign_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $variations = [];

                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }

                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }
        }

        return $data;
    }

    public static function store_data_formatting_limited($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $ratings = StoreLogic::calculate_store_rating($item['rating']);
                $item['positive_rating'] = $ratings['positive_rating'];

                array_push($storage, $item);
            }
            $data = $storage;
        } else {

            $ratings = StoreLogic::calculate_store_rating($data['rating']);
            unset($data['rating']);
            // $data['avg_rating'] = $ratings['rating'];
            // $data['rating_count'] = $ratings['total'];
            $data['positive_rating'] = $ratings['positive_rating'];
        }

        return $data;
    }
    public static function store_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $item->load('storeConfig');
                $ratings = StoreLogic::calculate_store_rating($item['rating']);
                unset($item['rating']);
                $item['avg_rating'] = $ratings['rating'];
                $item['rating_count'] = $ratings['total'];
                $item['positive_rating'] = $ratings['positive_rating'];
                $item['total_items'] = $item['items_count'];
                $item['total_campaigns'] = $item['campaigns_count'];
                $item['is_recommended'] = false;
                if ($item->storeConfig && $item->storeConfig->is_recommended_deleted == 0) {
                    $item['is_recommended'] = $item->storeConfig->is_recommended;
                }
                unset($item['items_count']);
                unset($item['campaigns_count']);
                unset($item['storeConfig']);
                unset($item['campaigns']);
                unset($item['pivot']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            $data->load('storeConfig');
            $data['is_recommended'] = false;
            if ($data->storeConfig && $data->storeConfig->is_recommended_deleted == 0) {
                $data['is_recommended'] = $data->storeConfig->is_recommended;
            }
            $ratings = StoreLogic::calculate_store_rating($data['rating']);
            unset($data['rating']);
            $data['avg_rating'] = $ratings['rating'];
            $data['rating_count'] = $ratings['total'];
            $data['positive_rating'] = $ratings['positive_rating'];
            $data['total_items'] = $data['items_count'];
            $data['total_campaigns'] = $data['campaigns_count'];
            unset($data['items_count']);
            unset($data['campaigns_count']);
            unset($data['campaigns']);
            unset($data['storeConfig']);
            unset($data['pivot']);
        }

        return $data;
    }

    public static function wishlist_data_formatting($data, $multi_data = false)
    {
        $items = [];
        $stores = [];
        if ($multi_data == true) {

            foreach ($data as $temp) {
                if ($temp->item) {
                    $items[] = self::product_data_formatting($temp->item, false, false, app()->getLocale());
                }
                if ($temp->store) {
                    $stores[] = self::store_data_formatting($temp->store);
                }
            }
        } else {
            if ($data->item) {
                $items[] = self::product_data_formatting($data->item, false, false, app()->getLocale());
            }
            if ($data->store) {
                $stores[] = self::store_data_formatting($data->store);
            }
        }

        return ['item' => $items, 'store' => $stores];
    }

    public static function order_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data) {
            foreach ($data as $item) {
                if (isset($item['store'])) {
                    $item['store_name'] = $item['store']['name'];
                    $item['store_address'] = $item['store']['address'];
                    $item['store_phone'] = $item['store']['phone'];
                    $item['store_lat'] = $item['store']['latitude'];
                    $item['store_lng'] = $item['store']['longitude'];
                    $item['store_logo'] = $item['store']['logo'];
                    $item['min_delivery_time'] =  (int) explode('-', $item['store']['delivery_time'])[0] ?? 0;
                    $item['max_delivery_time'] =  (int) explode('-', $item['store']['delivery_time'])[1] ?? 0;
                    unset($item['store']);
                } else {
                    $item['store_name'] = null;
                    $item['store_address'] = null;
                    $item['store_phone'] = null;
                    $item['store_lat'] = null;
                    $item['store_lng'] = null;
                    $item['store_logo'] = null;
                    $item['min_delivery_time'] = null;
                    $item['max_delivery_time'] = null;
                }
                $item['item_campaign'] = 0;
                foreach ($item->details as $d) {
                    if ($d->item_campaign_id != null) {
                        $item['item_campaign'] = 1;
                    }
                }

                $item['delivery_address'] = $item->delivery_address ? json_decode($item->delivery_address, true) : null;
                $item['details_count'] = (int)$item->details->count();
                $item['min_delivery_time'] =  $item->store ? (int)explode('-', $item->store?->delivery_time)[0] ?? 0 : 0;
                $item['max_delivery_time'] =  $item->store ? (int)explode('-', $item->store?->delivery_time)[1] ?? 0 : 0;

                unset($item['details']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            if (isset($data['store'])) {
                $data['store_name'] = $data['store']['name'];
                $data['store_address'] = $data['store']['address'];
                $data['store_phone'] = $data['store']['phone'];
                $data['store_lat'] = $data['store']['latitude'];
                $data['store_lng'] = $data['store']['longitude'];
                $data['store_logo'] = $data['store']['logo'];
                $data['min_delivery_time'] =  $data['store'] ? (int) explode('-', $data['store']['delivery_time'])[0] ?? 0 : 0;
                $data['max_delivery_time'] =  $data['store'] ? (int) explode('-', $data['store']['delivery_time'])[1] ?? 0 : 0;
                unset($data['store']);
            } else {
                $data['store_name'] = null;
                $data['store_address'] = null;
                $data['store_phone'] = null;
                $data['store_lat'] = null;
                $data['store_lng'] = null;
                $data['store_logo'] = null;
                $data['min_delivery_time'] = null;
                $data['max_delivery_time'] = null;
            }

            $data['item_campaign'] = 0;
            foreach ($data->details as $d) {
                if ($d->item_campaign_id != null) {
                    $data['item_campaign'] = 1;
                }
            }
            $data['delivery_address'] = $data->delivery_address ? json_decode($data->delivery_address, true) : null;
            $data['details_count'] = (int)$data->details->count();

            unset($data['details']);
        }
        return $data;
    }

    public static function order_details_data_formatting($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $item['add_ons'] = json_decode($item['add_ons']);
            $item['variation'] = json_decode($item['variation'], true);
            $item['item_details'] = json_decode($item['item_details'], true);
            array_push($storage, $item);
        }
        $data = $storage;

        return $data;
    }

    public static function deliverymen_list_formatting($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $storage[] = [
                'id' => $item['id'],
                'name' => $item['f_name'] . ' ' . $item['l_name'],
                'image' => $item['image'],
                'assigned_order_count' => $item['assigned_order_count'],
                'lat' => $item->last_location ? $item->last_location->latitude : false,
                'lng' => $item->last_location ? $item->last_location->longitude : false,
                'location' => $item->last_location ? $item->last_location->location : '',
            ];
        }
        $data = $storage;

        return $data;
    }

    public static function deliverymen_data_formatting($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $item['avg_rating'] = (float)(count($item->rating) ? (float)$item->rating[0]->average : 0);
            $item['rating_count'] = (int)(count($item->rating) ? $item->rating[0]->rating_count : 0);
            $item['lat'] = $item->last_location ? $item->last_location->latitude : null;
            $item['lng'] = $item->last_location ? $item->last_location->longitude : null;
            $item['location'] = $item->last_location ? $item->last_location->location : null;
            if ($item['rating']) {
                unset($item['rating']);
            }
            if ($item['last_location']) {
                unset($item['last_location']);
            }
            $storage[] = $item;
        }
        $data = $storage;

        return $data;
    }

    public static function get_business_settings($name)
    {
        $config = null;

        $paymentmethod = BusinessSetting::where('key', $name)->first();

        if ($paymentmethod) {
            $config = json_decode($paymentmethod->value, true);
        }

        return $config;
    }

    public static function get_business_data($name)
    {
        $config = null;

        $paymentmethod = BusinessSetting::where('key', $name)->first();

        if ($paymentmethod) {
            $config = $paymentmethod->value;
        }

        return $config;
    }

    public static function currency_code()
    {
        if (!config('currency')) {
            $currency = BusinessSetting::where(['key' => 'currency'])->first()?->value;
            Config::set('currency', $currency);
        } else {
            $currency = config('currency');
        }

        return $currency;
    }


    public static function currency_symbol()
    {
        if (!config('currency_symbol')) {
            $currency_symbol = Currency::where(['currency_code' => Helpers::currency_code()])->first()?->currency_symbol;
            Config::set('currency_symbol', $currency_symbol);
        } else {
            $currency_symbol = config('currency_symbol');
        }
        return $currency_symbol;
    }



    public static function format_currency($value)
    {
        if (!config('currency_symbol_position')) {
            $currency_symbol_position = BusinessSetting::where(['key' => 'currency_symbol_position'])->first()?->value;
            Config::set('currency_symbol_position', $currency_symbol_position);
        } else {
            $currency_symbol_position = config('currency_symbol_position');
        }

        return $currency_symbol_position == 'right' ? number_format($value, config('round_up_to_digit')) . ' ' . self::currency_symbol() : self::currency_symbol() . ' ' . number_format($value, config('round_up_to_digit'));
    }
    public static function getAccessToken()
    {
        // Path to your service account JSON file
        $serviceAccountFile =  dirname(__DIR__, 2) . '/service-account-key.json';

        // Load the service account file and extract the client_email and private_key
        $serviceAccount = json_decode(file_get_contents($serviceAccountFile), true);
        $clientEmail = $serviceAccount['client_email'];
        $privateKey = $serviceAccount['private_key'];

        // Create JWT claim set
        $now = time();
        $expires = $now + 3600; // Token expires in 1 hour

        $jwt = [
            'iss' => $clientEmail, // Issuer
            'scope' => 'https://www.googleapis.com/auth/cloud-platform', // Scope
            'aud' => 'https://oauth2.googleapis.com/token', // Audience
            'iat' => $now, // Issued at time
            'exp' => $expires, // Expiry time
        ];

        // Encode the JWT with the private key
        $jwtEncoded = JWT::encode($jwt, $privateKey, 'RS256');

        // Prepare the request to Google's OAuth 2.0 server
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwtEncoded,
        ]);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the request
        $response = curl_exec($ch);
        curl_close($ch);

        // Parse and display the OAuth 2.0 access token
        if ($response !== false) {
            $responseDecoded = json_decode($response, true);
            if (isset($responseDecoded['access_token'])) {
                $accessToken = $responseDecoded['access_token'];
                return $accessToken;
            } else {
                return $responseDecoded['error_description'];
            }
        } else {
            return curl_error($ch);
        }
    }



    public static function send_push_notif_to_device_new($fcm_token, $data, $web_push_link = null)
    {
        // Get OAuth 2.0 access token
        $accessToken = self::getAccessToken();
        // prx($accessToken);
        $url = "https://fcm.googleapis.com/v1/projects/fcm-3-e0206/messages:send"; // Replace with your project ID

        // Set headers for cURL request
        $header = array(
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        );

        // Optional data fields
        $message = isset($data['message']) ? $data['message'] : '';
        $conversation_id = isset($data['conversation_id']) ? $data['conversation_id'] : '';
        $sender_type = isset($data['sender_type']) ? $data['sender_type'] : '';
        $module_id = isset($data['module_id']) ? $data['module_id'] : '';
        $order_type = isset($data['order_type']) ? $data['order_type'] : '';

        // Optional click action for web push notifications
        $click_action = $web_push_link ? $web_push_link : '';

        // Build the payload in the correct FCM v1 format
        $postdata = json_encode([
            "message" => [
                "token" => $fcm_token,
                "data" => [
                    "title" => $data['title'],
                    "body" => $data['description'],
                    "image" => $data['image'],
                    "order_id" => (string) $data['order_id'],
                    "type" => $data['type'],
                    "conversation_id" => $conversation_id,
                    "sender_type" => $sender_type,
                    "module_id" => $module_id,
                    "order_type" => $order_type,
                    "is_read" => "0" // Ensure all values are strings
                ],
                "notification" => [
                    "title" => $data['title'],
                    "body" => $data['description'],
                    "image" => $data['image'],
                ],
                "android" => [ // Add Android-specific configuration
                    "notification" => [
                        "sound" => "default" // Use the default notification sound
                    ]
                ]

            ]
        ]);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Execute cURL request and get response
        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return 'cURL Error: ' . $error;
        } else {
            // prx($result);
        }

        // Close cURL and return the result
        curl_close($ch);
        // echo 547823 ; 
        // prx($result);
    }


    public static function send_push_notif_to_device($fcm_token, $data, $web_push_link = null)
    {
        // Get OAuth 2.0 access token
        $accessToken = self::getAccessToken();
        // prx($accessToken);
        $url = "https://fcm.googleapis.com/v1/projects/fcm-3-e0206/messages:send";

        // Set headers for cURL request
        $header = array(
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        );

        // Optional data fields
        $message = isset($data['message']) ? $data['message'] : '';
        $conversation_id = isset($data['conversation_id']) ? $data['conversation_id'] : '';
        $sender_type = isset($data['sender_type']) ? $data['sender_type'] : '';
        $module_id = isset($data['module_id']) ? $data['module_id'] : '';
        $order_type = isset($data['order_type']) ? $data['order_type'] : '';

        // Optional click action for web push notifications
        $click_action = $web_push_link ? $web_push_link : '';

        // Build the payload in the correct FCM v1 format
        $postdata = json_encode([
            "message" => [
                "token" => $fcm_token,
                "data" => [
                    "title" => $data['title'],
                    "body" => $data['description'],
                    "image" => $data['image'],
                    "order_id" => (string) $data['order_id'],
                    "type" => $data['type'],
                    "conversation_id" => $conversation_id,
                    "sender_type" => $sender_type,
                    "module_id" => $module_id,
                    "order_type" => $order_type,
                    "is_read" => "0" // Ensure all values are strings
                ],
                "notification" => [
                    "title" => $data['title'],
                    "body" => $data['description'],
                    "image" => $data['image'],
                ],
                "android" => [ // Add Android-specific configuration
                    "notification" => [
                        "sound" => "default" // Use the default notification sound
                    ]
                ]

            ]
        ]);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Execute cURL request and get response
        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return 'cURL Error: ' . $error;
        } else {
            // prx($result);
        }

        // Close cURL and return the result
        curl_close($ch);
        // echo 547823 ;  
        // prx($result);
    }

    public static function send_push_notif_to_topic($data, $topic, $type, $web_push_link = null)
    {
        // info([$data, $topic, $type, $web_push_link]);
        $key = BusinessSetting::where(['key' => 'push_notification_key'])->first()->value;

        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array(
            "authorization: key=" . $key . "",
            "content-type: application/json"
        );
        if (isset($data['module_id'])) {
            $module_id = $data['module_id'];
        } else {
            $module_id = '';
        }
        if (isset($data['order_type'])) {
            $order_type = $data['order_type'];
        } else {
            $order_type = '';
        }
        if (isset($data['zone_id'])) {
            $zone_id = $data['zone_id'];
        } else {
            $zone_id = '';
        }

        $click_action = "";
        if ($web_push_link) {
            $click_action = ',
            "click_action": "' . $web_push_link . '"';
        }

        if (isset($data['order_id'])) {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "module_id":"' . $module_id . '",
                    "order_type":"' . $order_type . '",
                    "zone_id":"' . $zone_id . '",
                    "is_read": 0,
                    "type":"' . $type . '"
                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "order_id":"' . $data['order_id'] . '",
                    "title_loc_key":"' . $data['order_id'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification.wav",
                    "android_channel_id": "MyChitti"
                    ' . $click_action . '
                  }
            }';
        } else {
            $postdata = '{
                "to" : "/topics/' . $topic . '",
                "mutable_content": true,
                "data" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "is_read": 0,
                    "type":"' . $type . '"
                },
                "notification" : {
                    "title":"' . $data['title'] . '",
                    "body" : "' . $data['description'] . '",
                    "image" : "' . $data['image'] . '",
                    "body_loc_key":"' . $type . '",
                    "type":"' . $type . '",
                    "is_read": 0,
                    "icon" : "new",
                    "sound": "notification.wav",
                    "android_channel_id": "MyChitti"
                    ' . $click_action . '
                  }
            }';
        }


        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }


    public static function calculatePresetDates($preset, $customRange = null)
    {

        $now = Carbon::now();
        $today = Carbon::today();
        switch ($preset) {
            case 'today':
                return [
                    'start' => $today,
                    'end'   => $today->copy()->endOfDay(),
                ];

            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                return [
                    'start' => $yesterday,
                    'end'   => $yesterday->copy()->endOfDay(),
                ];

            case 'this_week':
                return [
                    'start' => $today->copy()->startOfWeek(),
                    'end'   => $now,
                ];
            case 'last_week':
                $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(); // last Monday
                $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();     // last Sunday

                return [
                    'start' => $lastWeekStart->startOfDay(),
                    'end'   => $lastWeekEnd->endOfDay(),
                ];

            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end'   => $now,
                ];
            case 'last_month':
                $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth(); // 1st day of last month
                $lastMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();     // last day of last month

                return [
                    'start' => $lastMonthStart->startOfDay(),
                    'end'   => $lastMonthEnd->endOfDay(),
                ];
            case 'last_3_month':
                $end   = Carbon::now()->endOfDay();                  // today end of day
                $start = Carbon::now()->subMonths(3)->startOfDay();  // exactly 3 months ago

                return [
                    'start' => $start,
                    'end'   => $end,
                ];

            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end'   => $now,
                ];
            case 'last_year':
                $lastYearStart = Carbon::now()->subYear()->startOfYear(); // January 1 of last year
                $lastYearEnd = Carbon::now()->subYear()->endOfYear();     // December 31 of last year

                return [
                    'start' => $lastYearStart->startOfDay(),
                    'end'   => $lastYearEnd->endOfDay(),
                ];
            case 'quarter':
                $start = Carbon::now()->firstOfQuarter()->startOfDay();
                $end = Carbon::now()->endOfDay(); // Up to now, or use ->lastOfQuarter()->endOfDay() for full quarter

                return [
                    'start' => $start,
                    'end' => $end,
                ];

            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(30),
                    'end'   => $now,
                ];

            case 'fy_24_25':
                return [
                    'start' => Carbon::create(2024, 4, 1)->startOfDay(),
                    'end'   => Carbon::create(2025, 3, 31)->endOfDay(),
                ];
            case 'fy_25_26':
                return [
                    'start' => Carbon::create(2025, 4, 1)->startOfDay(),
                    'end'   => Carbon::create(2026, 3, 31)->endOfDay(),
                ];

            case 'custom':
                if (!$customRange) {
                    throw new \InvalidArgumentException("Custom date range not provided.");
                }

                $customRange = urldecode(trim($customRange));

                // Split on " - " (only once, to separate two dates)
                $parts = preg_split('/\s+-\s+/', $customRange);

                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException("Custom date range format must be 'YYYY-MM-DD - YYYY-MM-DD'.");
                }

                try {
                    return [
                        'start' => Carbon::parse($parts[0])->startOfDay(),
                        'end'   => Carbon::parse($parts[1])->endOfDay(),
                    ];
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException("Invalid dates in custom range.");
                }

            default:
                throw new \InvalidArgumentException("Unknown preset: {$preset}");
        }
    }

    public static function rating_count($item_id, $rating)
    {
        return Review::where(['item_id' => $item_id, 'rating' => $rating])->count();
    }

    public static function dm_rating_count($deliveryman_id, $rating)
    {
        return DMReview::where(['delivery_man_id' => $deliveryman_id, 'rating' => $rating])->count();
    }

    public static function tax_calculate($item, $price)
    {
        if ($item['tax_type'] == 'percent') {
            $price_tax = ($price / 100) * $item['tax'];
        } else {
            $price_tax = $item['tax'];
        }
        return $price_tax;
    }
    public static function tax_calculate_new($tax, $tax_type,  $price)
    {
        if ($tax_type == 'percent') {
            $price_tax = ($price / 100) * $tax;
        } else {
            $price_tax = $tax;
        }
        return $price_tax;
    }
    public static function tax_calculate_product($id)
    {
        $product = Item::find($id);

        $price_tax = ($product->price / 100) * $product->tax;

        return $price_tax;
    }

    public static function discount_calculate($product, $price)
    {
        if ($product['store_discount']) {
            $price_discount = ($price / 100) * $product['store_discount'];
        } else if ($product['discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $product['discount'];
        } else {
            $price_discount = $product['discount'];
        }
        return $price_discount;
    }

    public static function get_product_discount($product)
    {
        $store_discount = self::get_store_discount($product->store);
        if ($store_discount) {
            $discount = $store_discount['discount'] . ' %';
        } else if ($product['discount_type'] == 'percent') {
            $discount = $product['discount'] . ' %';
        } else {
            $discount = self::format_currency($product['discount']);
        }
        return $discount;
    }

    public static function product_discount_calculate($product, $price, $store)
    {
        $running_flash_sale = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
            $query->Active()->Running();
        })
            ->where(['item_id' => $product->id])->first();

        if ($running_flash_sale) {
            if ($running_flash_sale['discount_type'] == 'percent') {
                $price_discount = ($price / 100) * $running_flash_sale['discount'];
            } else {
                $price_discount = $running_flash_sale['discount'];
            }
            return [
                'discount_type' => 'flash_sale',
                'discount_amount' => $price_discount,
                'admin_discount_amount' => ($price_discount * $running_flash_sale->flashSale->admin_discount_percentage) / 100,
                'vendor_discount_amount' => ($price_discount * $running_flash_sale->flashSale->vendor_discount_percentage) / 100,
            ];
        }

        $store_discount = self::get_store_discount($store);
        if (isset($store_discount)) {
            $price_discount = ($price / 100) * $store_discount['discount'];
        } else if ($product['discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $product['discount'];
        } else {
            $price_discount = $product['discount'];
        }

        return [
            'discount_type' => isset($store_discount) ? 'store_discount' : 'product_discount',
            'discount_amount' => $price_discount
        ];
    }

    public static function get_price_range($product, $discount = false)
    {
        $lowest_price = $product->price;
        $highest_price = $product->price;
        if ($product->variations && is_array(json_decode($product['variations'], true))) {
            foreach (json_decode($product->variations) as $key => $variation) {
                if ($lowest_price > $variation->price) {
                    $lowest_price = round($variation->price, 2);
                }
                if ($highest_price < $variation->price) {
                    $highest_price = round($variation->price, 2);
                }
            }
        }

        if ($discount) {
            $lowest_price -= self::product_discount_calculate($product, $lowest_price, $product->store)['discount_amount'];
            $highest_price -= self::product_discount_calculate($product, $highest_price, $product->store)['discount_amount'];
        }
        $lowest_price = self::format_currency($lowest_price);
        $highest_price = self::format_currency($highest_price);

        if ($lowest_price == $highest_price) {
            return $lowest_price;
        }
        return $lowest_price . ' - ' . $highest_price;
    }
    public static function get_food_price_range($product, $discount = false)
    {
        $lowest_price = $product->price;


        if ($discount) {
            $lowest_price -= self::product_discount_calculate($product, $lowest_price, $product->store)['discount_amount'];
        }
        $lowest_price = self::format_currency($lowest_price);
        return $lowest_price;
    }

    public static function get_store_discount($store)
    {
        if ($store && $store->discount) {
            if (date('Y-m-d', strtotime($store->discount->start_date)) <= now()->format('Y-m-d') && date('Y-m-d', strtotime($store->discount->end_date)) >= now()->format('Y-m-d') && date('H:i', strtotime($store->discount->start_time)) <= now()->format('H:i') && date('H:i', strtotime($store->discount->end_time)) >= now()->format('H:i')) {
                return [
                    'discount' => $store->discount->discount,
                    'min_purchase' => $store->discount->min_purchase,
                    'max_discount' => $store->discount->max_discount
                ];
            }
        }
        return null;
    }

    public static function max_earning()
    {
        $data = Order::where(['order_status' => 'delivered'])->select('id', 'created_at', 'order_amount')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += $order['order_amount'];
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    public static function max_orders()
    {
        $data = Order::select('id', 'created_at')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += 1;
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    public static function order_status_update_message($status, $module_type, $lang = 'en')
    {
        if ($status == 'pending') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_pending_message')->first();
        } elseif ($status == 'confirmed') {
            $data =  NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_confirmation_msg')->first();
        } elseif ($status == 'processing') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_processing_message')->first();
        } elseif ($status == 'picked_up') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'out_for_delivery_message')->first();
        } elseif ($status == 'handover') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_handover_message')->first();
        } elseif ($status == 'delivered') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_delivered_message')->first();
        } elseif ($status == 'delivery_boy_delivered') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'delivery_boy_delivered_message')->first();
        } elseif ($status == 'accepted') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'delivery_boy_assign_message')->first();
        } elseif ($status == 'canceled') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_cancled_message')->first();
        } elseif ($status == 'refunded') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'order_refunded_message')->first();
        } elseif ($status == 'refund_request_canceled') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'refund_request_canceled')->first();
        } elseif ($status == 'offline_verified') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'offline_order_accept_message')->first();
        } elseif ($status == 'offline_denied') {
            $data = NotificationMessage::with(['translations' => function ($query) use ($lang) {
                $query->where('locale', $lang);
            }])->where('module_type', $module_type)->where('key', 'offline_order_deny_message')->first();
        } else {
            $data = ["status" => "0", "message" => "", 'translations' => []];
        }

        if ($data) {
            if ($data['status'] == 0) {
                return 0;
            }
            return count($data->translations) > 0 ? $data->translations[0]->value : $data['message'];
        } else {
            return false;
        }
    }

    public static function send_order_place_notification($order)
    {
        $fcm_token = User::find($order->user_id)->cm_firebase_token;
        $value = self::order_status_update_message($order->order_status, 'ecommerce', 'en');
        $value = self::text_variable_data_format(value: $value, store_name: $order->store?->name, order_id: $order->id, user_name: "{$order?->customer?->f_name} {$order?->customer?->l_name}", delivery_man_name: "{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");
        $data = [
            'title' => "Order Placed successfully #" . $order->id,
            'description' => $value,
            'order_id' => $order->id,
            'image' => '',
            'type' => 'block'
        ];
        self::send_push_notif_to_device($fcm_token, $data, '');
    }
    public static function send_order_status_notification($order)
    {
        $fcm_token = User::find($order->user_id)->cm_firebase_token;
        $value = self::order_status_update_message($order->order_status, 'ecommerce', 'en');
        $value = self::text_variable_data_format(value: $value, store_name: $order->store?->name, order_id: $order->id, user_name: "{$order?->customer?->f_name} {$order?->customer?->l_name}", delivery_man_name: "{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");
        $data = [
            'title' => "Order #" . $order->id . " is " . $order->order_status,
            'description' => $value,
            'order_id' => $order->id,
            'image' => '',
            'type' => 'block'
        ];
        self::send_push_notif_to_device($fcm_token, $data, '');
    }
    public static function send_order_notification($order)
    {

        try {


            if ((in_array($order->payment_method, ['cash_on_delivery', 'offline_payment'])  && $order->order_status == 'pending') || (!in_array($order->payment_method, ['cash_on_delivery', 'offline_payment']) && $order->order_status == 'confirmed')) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                    'module_id' => $order->module_id,
                    'order_type' => $order->order_type,
                    'zone_id' => $order->zone_id,
                    'type' => 'new_order',
                ];
                self::send_push_notif_to_topic($data, 'admin_message', 'order_request', url('/') . '/order/list/all');
            }

            $status = ($order->order_status == 'delivered' && $order->delivery_man) ? 'delivery_boy_delivered' : $order->order_status;


            if ($order->is_guest) {
                $customer_details = json_decode($order['delivery_address'], true);
                $value = self::order_status_update_message($status, $order->module->module_type, 'en');
                $value = self::text_variable_data_format(value: $value, store_name: $order->store?->name, order_id: $order->id, user_name: "{$customer_details['contact_person_name']}", delivery_man_name: "{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");
                $user_fcm = $order->guest->fcm_token;
            } else {

                $value = self::order_status_update_message($status, $order->module->module_type, $order->customer ?
                    $order->customer->current_language_key : 'en');
                $value = self::text_variable_data_format(value: $value, store_name: $order->store?->name, order_id: $order->id, user_name: "{$order->customer?->f_name} {$order->customer?->l_name}", delivery_man_name: "{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");

                $user_fcm = $order?->customer?->cm_firebase_token;
            }

            if ($value) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                self::send_push_notif_to_device($user_fcm, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            if ($status == 'picked_up') {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                if ($order->store && $order->store->vendor) {
                    self::send_push_notif_to_device($order->store->vendor->firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $order->store->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $status == 'pending' && $order->payment_method == 'cash_on_delivery' && config('order_confirmation_model') == 'deliveryman') {
                if ($order->store->self_delivery_system) {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'module_id' => $order->module_id,
                        'order_type' => $order->order_type,
                        'image' => '',
                        'type' => 'new_order',
                    ];
                    if ($order->store && $order->store->vendor) {
                        self::send_push_notif_to_device($order->store->vendor->firebase_token, $data);
                        $web_push_link = url('/') . '/order/list/all';
                        self::send_push_notif_to_topic($data, "store_panel_{$order->store_id}_message", 'new_order', $web_push_link);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($data),
                            'vendor_id' => $order->store->vendor_id,
                            // 'module_id' => $order->module_id,
                            'order_type' => $order->order_type,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                } else {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'module_id' => $order->module_id,
                        'order_type' => $order->order_type,
                        'image' => '',
                    ];
                    if ($order->zone) {
                        if ($order->dm_vehicle_id) {

                            $topic = 'delivery_man_' . $order->zone_id . '_' . $order->dm_vehicle_id;
                            self::send_push_notif_to_topic($data, $topic, 'order_request');
                        }
                        self::send_push_notif_to_topic($data, $order->zone->deliveryman_wise_topic, 'order_request');
                    }
                }
            }

            if ($order->order_type == 'parcel' && in_array($order->order_status, ['pending', 'confirmed'])) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'module_id' => $order->module_id,
                    'order_type' => 'parcel_order',
                    'image' => '',
                ];
                if ($order->zone) {
                    if ($order->dm_vehicle_id) {

                        $topic = 'delivery_man_' . $order->zone_id . '_' . $order->dm_vehicle_id;
                        self::send_push_notif_to_topic($data, $topic, 'order_request');
                    }
                    self::send_push_notif_to_topic($data, $order->zone->deliveryman_wise_topic, 'order_request');
                }
                // self::send_push_notif_to_topic($data, 'admin_message', 'order_request');
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $order->order_status == 'pending' && $order->payment_method == 'cash_on_delivery' && config('order_confirmation_model') == 'store') {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'module_id' => $order->module_id,
                    'order_type' => $order->order_type,
                    'image' => '',
                    'type' => 'new_order',
                ];
                if ($order->store && $order->store->vendor) {
                    self::send_push_notif_to_device($order->store->vendor->firebase_token, $data);
                    $web_push_link = url('/') . '/order/list/all';
                    self::send_push_notif_to_topic($data, "store_panel_{$order->store_id}_message", 'new_order', $web_push_link);
                    // self::send_push_notif_to_topic($data, 'admin_message', 'order_request');
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $order->store->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            if (!$order->scheduled && (($order->order_type == 'take_away' && $order->order_status == 'pending') || ($order->payment_method != 'cash_on_delivery' && $order->order_status == 'confirmed'))) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'new_order',
                ];
                if ($order->store && $order->store->vendor) {
                    self::send_push_notif_to_device($order->store->vendor->firebase_token, $data);
                    $web_push_link = url('/') . '/order/list/all';
                    self::send_push_notif_to_topic($data, "store_panel_{$order->store_id}_message", 'new_order', $web_push_link);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $order->store->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            if ($order->order_status == 'confirmed' && $order->order_type != 'take_away' && config('order_confirmation_model') == 'deliveryman' && $order->payment_method == 'cash_on_delivery') {
                if ($order->store->self_delivery_system) {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'module_id' => $order->module_id,
                        'order_type' => $order->order_type,
                        'image' => '',
                    ];

                    self::send_push_notif_to_topic($data, "restaurant_dm_" . $order->store_id, 'new_order');
                } else {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'module_id' => $order->module_id,
                        'order_type' => $order->order_type,
                        'image' => '',
                        'type' => 'new_order',
                    ];
                    if ($order->store && $order->store->vendor) {
                        self::send_push_notif_to_device($order->store->vendor->firebase_token, $data);
                        $web_push_link = url('/') . '/order/list/all';
                        self::send_push_notif_to_topic($data, "store_panel_{$order->store_id}_message", 'new_order', $web_push_link);
                        DB::table('user_notifications')->insert([
                            'data' => json_encode($data),
                            'vendor_id' => $order->store->vendor_id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $order->order_status == 'confirmed'  && ($order->payment_method != 'cash_on_delivery' || config('order_confirmation_model') == 'store')) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'module_id' => $order->module_id,
                    'order_type' => $order->order_type,
                    'image' => '',
                ];
                if ($order->store->self_delivery_system) {
                    self::send_push_notif_to_topic($data, "restaurant_dm_" . $order->store_id, 'order_request');
                } else {
                    if ($order->zone) {
                        if ($order->dm_vehicle_id) {

                            $topic = 'delivery_man_' . $order->zone_id . '_' . $order->dm_vehicle_id;
                            self::send_push_notif_to_topic($data, $topic, 'order_request');
                        }
                        self::send_push_notif_to_topic($data, $order->zone->deliveryman_wise_topic, 'order_request');
                    }
                }
            }

            if (in_array($order->order_status, ['processing', 'handover']) && $order->delivery_man) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $order->order_status == 'processing' ? translate('messages.Proceed_for_cooking') : translate('messages.ready_for_delivery'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status'
                ];
                self::send_push_notif_to_device($order->delivery_man->fcm_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'delivery_man_id' => $order->delivery_man->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            $mail_status = Helpers::get_mail_status('place_order_mail_status_user');
            try {
                if ($order->order_status == 'confirmed' && $order->payment_method != 'cash_on_delivery' && config('mail.status') && $mail_status == '1' && $order->is_guest == 0) {
                    Mail::to($order->customer->email)->send(new PlaceOrder($order->id));
                }
                $order_verification_mail_status = Helpers::get_mail_status('order_verification_mail_status_user');
                if ($order->order_status == 'pending' && config('order_delivery_verification') == 1 && $order_verification_mail_status == '1' && $order->is_guest == 0) {
                    Mail::to($order->customer->email)->send(new OrderVerificationMail($order->otp, $order->customer->f_name));
                }
            } catch (\Exception $ex) {
                info($ex->getMessage());
            }
            return true;
        } catch (\Exception $e) {
            info($e->getMessage());
        }
        return false;
    }

    public static function send_order_status_mail($to, $from, $status)
    {
        Mail::to()->send(new OrderVerificationMail($order->otp, $order->customer->f_name));
    }

    public static function day_part()
    {
        $part = "";
        $morning_start = date("h:i:s", strtotime("5:00:00"));
        $afternoon_start = date("h:i:s", strtotime("12:01:00"));
        $evening_start = date("h:i:s", strtotime("17:01:00"));
        $evening_end = date("h:i:s", strtotime("21:00:00"));

        if (time() >= $morning_start && time() < $afternoon_start) {
            $part = "morning";
        } elseif (time() >= $afternoon_start && time() < $evening_start) {
            $part = "afternoon";
        } elseif (time() >= $evening_start && time() <= $evening_end) {
            $part = "evening";
        } else {
            $part = "night";
        }

        return $part;
    }

    public static function env_update($key, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key . '=' . env($key),
                $key . '=' . $value,
                file_get_contents($path)
            ));
        }
    }

    public static function env_key_replace($key_from, $key_to, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key_from . '=' . env($key_from),
                $key_to . '=' . $value,
                file_get_contents($path)
            ));
        }
    }

    public static  function remove_dir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") Helpers::remove_dir($dir . "/" . $object);
                    else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function get_store_id()
    {
        if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->store->id;
        } else if (auth('vendor')->check()) {
            return auth('vendor')->user()->stores[0]->id;
        } else {
            return 0;
        }
    }

    public static function get_vendor_id()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->id();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->vendor_id;
        }
        return 0;
    }

    public static function get_vendor_data()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->user();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->vendor;
        }
        return 0;
    }

    public static function get_loggedin_user()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->user();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user();
        }
        return 0;
    }

    public static function get_store_data()
    {
        if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->store;
        }
        return auth('vendor')->user()->stores[0];
    }

    public static function upload(string $dir, string $format, $image = null, $imageName = null)
    {
        if ($image != null) {
            $imageName = $imageName ?? \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }
            Storage::disk('public')->putFileAs($dir, $image, $imageName);
        } else {
            $imageName = 'def.png';
        }

        return $imageName;
    }
    public static function getDateRangeFromRequest()
    {
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;

        $range = self::calculatePresetDates($preset, $custom);

        return [
            'start' => $range['start'],
            'end'   => $range['end'],
        ];
    }

    public static function get_financial_year($year = null)
    {
        $today = now();

        $year = $year ?? $today->year;

        $financialYearStart = Carbon::createFromDate($year, 4, 1);

        if ($today->lt($financialYearStart)) {
            $financialYearStart->subYear();
        }

        $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // 31 March

        $fyear = $financialYearStart->format('y') . '-' . $financialYearEnd->format('y');

        return $fyear;
    }
    public static function getChartData(string $preset, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        $rangeType = Helpers::getRangeTypeFromPreset($preset, $from, $to);
        $stepConfig = Helpers::getChartStepByRangeType($rangeType, $from, $to);

        $groupByFormat = match ($stepConfig['unit']) {
            'hour'  => '%Y-%m-%d %H:00:00',
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
        };

        $labels = [];

        $cursor = $from->copy();

        while ($cursor <= $to) {

            $labels[] = $cursor->format($stepConfig['label_format']);

            switch ($stepConfig['unit']) {
                case 'hour':
                    $cursor->addHours($stepConfig['step']);
                    break;
                case 'day':
                    $cursor->addDays($stepConfig['step']);
                    break;
                case 'week':
                    $cursor->addWeeks($stepConfig['step']);
                    break;
                case 'month':
                    $cursor->addMonths($stepConfig['step']);
                    break;
            }
        }

        $storeId = Helpers::get_store_id();

        /* ---------------- Completed leads ---------------- */
        $completedLeadsRaw = AcceptedServiceRequest::where('vendor_id', $storeId)
            ->where('current_status', 'Completed')
            ->whereBetween('assigned_at', [$from, $to])
            ->selectRaw("
            DATE_FORMAT(assigned_at, '{$groupByFormat}') as bucket,
            COUNT(*) as c
        ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('c', 'bucket');

        /* ---------------- New leads ---------------- */
        $newLeadsRaw = AcceptedServiceRequest::where('vendor_id', $storeId)
            ->where('current_status', 'New')
            ->whereBetween('assigned_at', [$from, $to])
            ->selectRaw("
            DATE_FORMAT(assigned_at, '{$groupByFormat}') as bucket,
            COUNT(*) as c
        ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('c', 'bucket');

        /* ---------------- Tasks (completed) ---------------- */
        $tasksRaw = StoreTask::where('store_id', $storeId)
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
            DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
            COUNT(*) as c
        ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('c', 'bucket');

        /* ---------------- Projects completed ---------------- */
        $completedProjectsRaw = Project::where('vendor_id', $storeId)
            ->where('progress_status', 'Completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
            DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
            COUNT(*) as c
        ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('c', 'bucket');

        /* ---------------- Projects active ---------------- */
        $activeProjectsRaw = Project::where('vendor_id', $storeId)
            ->whereNotIn('progress_status', ['Completed', 'Cancelled'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
            DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
            COUNT(*) as c
        ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('c', 'bucket');

        /* ---------------- Paid bills ---------------- */
        // $paidServiceBillsRaw = ServiceInvoice::where('vendor_id', $storeId)
        //     ->where('payment_status', 'Paid')
        //     ->whereBetween('created_at', [$from, $to])
        //     ->selectRaw("
        //     DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
        //     COUNT(*) as c
        // ")
        //     ->groupBy('bucket')
        //     ->orderBy('bucket')
        //     ->pluck('c', 'bucket');

        // $paidManualBillsRaw = ManualInvoice::where('vendor_id', $storeId)
        //     ->where('payment_status', 'Paid')
        //     ->whereBetween('created_at', [$from, $to])
        //     ->selectRaw("
        //     DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
        //     COUNT(*) as c
        // ")
        //     ->groupBy('bucket')
        //     ->orderBy('bucket')
        //     ->pluck('c', 'bucket');

        /* ---------------- Unpaid bills ---------------- */
        // $unpaidServiceBillsRaw = ServiceInvoice::where('vendor_id', $storeId)
        //     ->where('payment_status', 'Unpaid')
        //     ->whereBetween('created_at', [$from, $to])
        //     ->selectRaw("
        //     DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
        //     COUNT(*) as c
        // ")
        //     ->groupBy('bucket')
        //     ->orderBy('bucket')
        //     ->pluck('c', 'bucket');

        // $unpaidManualBillsRaw = ManualInvoice::where('vendor_id', $storeId)
        //     ->where('payment_status', 'Unpaid')
        //     ->whereBetween('created_at', [$from, $to])
        //     ->selectRaw("
        //     DATE_FORMAT(created_at, '{$groupByFormat}') as bucket,
        //     COUNT(*) as c
        // ")
        //     ->groupBy('bucket')
        //     ->orderBy('bucket')
        //     ->pluck('c', 'bucket');
        // paid bills
        $paidBillsCount = ManualInvoice::where('vendor_id', $storeId)
            ->where('payment_status', 'Paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount') +  ServiceInvoice::where('vendor_id', $storeId)
            ->where('payment_status', 'Paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        // unpaid bills
        $unpaidBillsCount = ManualInvoice::where('vendor_id', $storeId)
            ->where('payment_status', 'Unpaid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount') + ServiceInvoice::where('vendor_id', $storeId)
            ->where('payment_status', 'Unpaid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        $completedLeadsData   = [];
        $newLeadsData         = [];
        $tasksData            = [];
        $completedProjectsData = [];
        $activeProjectsData   = [];
        // $paidBillsData        = [];
        // $unpaidBillsData      = [];

        $cursor = $from->copy();

        while ($cursor <= $to) {

            $bucket = match ($stepConfig['unit']) {
                'hour'  => $cursor->format('Y-m-d H:00:00'),
                'day'   => $cursor->format('Y-m-d'),
                'week'  => $cursor->format('Y-W'),
                'month' => $cursor->format('Y-m'),
            };

            $completedLeadsData[]   = $completedLeadsRaw->get($bucket, 0);
            $newLeadsData[]         = $newLeadsRaw->get($bucket, 0);
            $tasksData[]            = $tasksRaw->get($bucket, 0);
            $completedProjectsData[] = $completedProjectsRaw->get($bucket, 0);
            $activeProjectsData[]   = $activeProjectsRaw->get($bucket, 0);

            // $paidBillsData[] =
            //     $paidServiceBillsRaw->get($bucket, 0)
            //     + $paidManualBillsRaw->get($bucket, 0);

            // $unpaidBillsData[] =
            //     $unpaidServiceBillsRaw->get($bucket, 0)
            //     + $unpaidManualBillsRaw->get($bucket, 0);

            switch ($stepConfig['unit']) {
                case 'hour':
                    $cursor->addHours($stepConfig['step']);
                    break;
                case 'day':
                    $cursor->addDays($stepConfig['step']);
                    break;
                case 'week':
                    $cursor->addWeeks($stepConfig['step']);
                    break;
                case 'month':
                    $cursor->addMonths($stepConfig['step']);
                    break;
            }
        }

        return [
            'months' => $labels,

            'completed_leads'   => $completedLeadsData,
            'new_leads'         => $newLeadsData,
            'tasks'             => $tasksData,
            'completed_projects' => $completedProjectsData,
            'active_projects'   => $activeProjectsData,
            // 'paid_bills'        => $paidBillsData,
            // 'unpaid_bills'      => $unpaidBillsData,
            'bills'             => [$paidBillsCount, $unpaidBillsCount]
        ];
    }

    public static function getChartStepByRangeType(string $rangeType): array
    {
        switch ($rangeType) {

            case 'day':       // today / single day
                return ['unit' => 'hour', 'step' => 3, 'label_format' => 'H'];

            case 'week':
                return ['unit' => 'day', 'step' => 1, 'label_format' => 'd M'];

            case 'month':
                return ['unit' => 'day', 'step' => 2, 'label_format' => 'd M'];

            case 'quarter':   // last 3 months
                return ['unit' => 'week', 'step' => 1, 'label_format' => 'd M'];

            case 'year':
                return ['unit' => 'month', 'step' => 1, 'label_format' => 'M Y'];

            default:          // custom ranges
                return ['unit' => 'day', 'step' => 1, 'label_format' => 'd M'];
        }
    }

    public static function getRangeTypeFromPreset(string $preset, \Carbon\Carbon $from, \Carbon\Carbon $to): string
    {
        switch ($preset) {

            case 'today':
            case 'yesterday':
                return 'day';      // hours

            case 'this_week':
            case 'last_week':
                return 'week';     // days

            case 'this_month':
            case 'last_month':
                return 'month';    // days

            case 'last_3_month':
                return 'multi_month'; // months

            default:
                // custom range
                if ($from->isSameDay($to)) {
                    return 'day';
                }

                if ($from->isSameWeek($to)) {
                    return 'week';
                }

                if ($from->isSameMonth($to)) {
                    return 'month';
                }

                if ($from->isSameYear($to)) {
                    return 'year';
                }

                return 'multi_year';
        }
    }
    public static function upload_to_secure(string $dir, string $format, $image = null, $imageName = null)
    {
        if ($image != null) {
            $imageName = $imageName ?? \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk('secure')->exists($dir)) {
                Storage::disk('secure')->makeDirectory($dir);
            }
            Storage::disk('secure')->putFileAs($dir, $image, $imageName);
        } else {
            $imageName = 'def.png';
        }

        return $imageName;
    }
    public static function delete_file($dir, $file_name)
    {
        try {
            if (Storage::disk('public')->exists($dir . $file_name)) {
                Storage::disk('public')->delete($dir . $file_name);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public static function update(string $dir, $old_image, string $format, $image = null)
    {
        if ($image == null) {
            return $old_image;
        }
        if (Storage::disk('public')->exists($dir . $old_image)) {
            Storage::disk('public')->delete($dir . $old_image);
        }
        $imageName = Helpers::upload($dir, $format, $image);
        return $imageName;
    }

    public static function format_coordiantes($coordinates)
    {
        $data = [];
        foreach ($coordinates as $coord) {
            $data[] = (object)['lat' => $coord[1], 'lng' => $coord[0]];
        }
        return $data;
    }
    public static function existingVendorPlan()
    {
        $v_id = Helpers::get_store_id();
        $subscription = DB::table('vendor_subscriptions')->where('vendor_id', $v_id)->get();

        if (!count($subscription)) {
            return false;
        } else if (date('Y-m-d H:i:s') > $subscription[0]->plan_expiry) {
            return false;
        } else {

            $planDetails = DB::table('plans')->where('id', $subscription[0]->plan_id)->get();
            if (!count($planDetails)) {
                return false;
            } else {
                $detArray = ['planDetails' => $planDetails[0], 'subscription' => $subscription[0]];
                return $detArray;
            }
        }
    }

    public static function permission_check_new($module_name)
    {
        if (auth('admin')->check()) {
            return true;
        }
        $submodule = SubModule::where('Key', $module_name)->first();
        if ($submodule) {
            $subMDet =   _isSubmoduleEnabled($submodule->id);
            if ($subMDet['enabled'] || $subMDet['warning']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public static function permission_check($module_name)
    {
        if (auth('admin')->check()) {
            return true;
        }

        $v_id = Helpers::get_store_id();

        $subscriptions = DB::table('vendor_subscriptions')
            ->where('vendor_id', $v_id)
            ->where('plan_expiry', '>', now())
            ->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $all_modules = [];

        foreach ($subscriptions as $sub) {
            $modules = json_decode($sub->permitted_modules, true);

            if (is_array($modules)) {
                $all_modules = array_merge($all_modules, $modules);
            }
        }

        $all_modules = array_unique($all_modules);

        if (!in_array($module_name, $all_modules)) {
            return false;
        }
        if (
            $module_name != 'service_leads' &&
            (!auth('vendor')->check() && !auth('vendor_employee')->check())
        ) {
            return false;
        }

        if (auth('vendor_employee')->check()) {

            if ($module_name == 'task_manage') {
                return true;
            }

            if (Helpers::employee_module_permission_check($module_name)) {
                return true;
            }

            return false;
        }

        return true;
    }

    public static function quoteId($storeId = null)
    {
        $storeId = $storeId ?? Helpers::get_store_id();
        return (Quotation::where('vendor_id', $storeId)->max('quotation_id') ?? 0) + 1;
    }


    public static function module_permission_check($mod_name)
    {
        if (!auth('admin')->user()->role) {
            return false;
        }

        if ($mod_name == 'zone' && auth('admin')->user()->zone_id) {
            return false;
        }

        $permission = auth('admin')->user()->role->modules;

        if (isset($permission) && in_array($mod_name, (array)json_decode($permission))) {
            return true;
        }

        if (auth('admin')->user()->role_id == 1) {
            return true;
        }

        return false;
    }

    public static function employee_module_permission_check($mod_name)
    {
        if (auth('vendor')->check()) {
            if ($mod_name == 'reviews') {
                return auth('vendor')->user()->stores[0]->reviews_section;
            } else if ($mod_name == 'deliveryman') {
                return auth('vendor')->user()->stores[0]->self_delivery_system;
            // } else if ($mod_name == 'pos') {
                // return auth('vendor')->user()->stores[0]->pos_system;
            } else if ($mod_name == 'addon') {
                return config('module.' . auth('vendor')->user()->stores[0]->module->module_type)['add_on'];
            }
            return true;
        } else if (auth('vendor_employee')->check()) {
            $permission = auth('vendor_employee')->user()->role->modules;
            if (isset($permission) && in_array($mod_name, (array)json_decode($permission)) == true) {
                if ($mod_name == 'reviews') {
                    return auth('vendor_employee')->user()->store->reviews_section;
                } else if ($mod_name == 'deliveryman') {
                    return auth('vendor_employee')->user()->store->self_delivery_system;
                // } else if ($mod_name == 'pos') {
                //     return auth('vendor_employee')->user()->store->pos_system;
                } else if ($mod_name == 'addon') {
                    return config('module.' . auth('vendor_employee')->user()->store->module->module_type)['add_on'];
                }
                return true;
            }
        }

        return false;
    }
    public static function calculate_addon_price($addons, $add_on_qtys)
    {
        $add_ons_cost = 0;
        $data = [];
        if ($addons) {
            foreach ($addons as $key2 => $addon) {
                if ($add_on_qtys == null) {
                    $add_on_qty = 1;
                } else {
                    $add_on_qty = $add_on_qtys[$key2];
                }
                $data[] = ['id' => $addon->id, 'name' => $addon->name, 'price' => $addon->price, 'quantity' => $add_on_qty];
                $add_ons_cost += $addon['price'] * $add_on_qty;
            }
            return ['addons' => $data, 'total_add_on_price' => $add_ons_cost];
        }
        return null;
    }

    public static function get_settings($name)
    {
        $config = null;
        $data = BusinessSetting::where(['key' => $name])->first();
        if (isset($data)) {
            $config = json_decode($data['value'], true);
            if (is_null($config)) {
                $config = $data['value'];
            }
        }
        return $config;
    }

    public static function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        $oldValue = env($envKey);
        if (strpos($str, $envKey) !== false) {
            $str = str_replace("{$envKey}={$oldValue}", "{$envKey}={$envValue}", $str);
        } else {
            $str .= "{$envKey}={$envValue}\n";
        }
        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
        return $envValue;
    }

    public static function requestSender()
    {
        $class = new LaravelchkController();
        $response = $class->actch();
        return json_decode($response->getContent(), true);
    }

    public static function insert_business_settings_key($key, $value = null)
    {
        $data =  BusinessSetting::where('key', $key)->first();
        if (!$data) {
            DB::table('business_settings')->updateOrInsert(['key' => $key], [
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return true;
    }

    public static function insert_data_settings_key($key, $type, $value = null)
    {
        $data =  DataSetting::where('key', $key)->where('type', $type)->first();
        if (!$data) {
            DataSetting::updateOrCreate(['key' => $key, 'type' => $type], [
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return true;
    }




    public static function get_language_name($key)
    {
        $languages = array(
            "af" => "Afrikaans",
            "sq" => "Albanian - shqip",
            "am" => "Amharic - አማርኛ",
            "ar" => "Arabic - العربية",
            "an" => "Aragonese - aragonés",
            "hy" => "Armenian - հայերեն",
            "ast" => "Asturian - asturianu",
            "az" => "Azerbaijani - azərbaycan dili",
            "eu" => "Basque - euskara",
            "be" => "Belarusian - беларуская",
            "bn" => "Bengali - বাংলা",
            "bs" => "Bosnian - bosanski",
            "br" => "Breton - brezhoneg",
            "bg" => "Bulgarian - български",
            "ca" => "Catalan - català",
            "ckb" => "Central Kurdish - کوردی (دەستنوسی عەرەبی)",
            "zh" => "Chinese - 中文",
            "zh-HK" => "Chinese (Hong Kong) - 中文（香港）",
            "zh-CN" => "Chinese (Simplified) - 中文（简体）",
            "zh-TW" => "Chinese (Traditional) - 中文（繁體）",
            "co" => "Corsican",
            "hr" => "Croatian - hrvatski",
            "cs" => "Czech - čeština",
            "da" => "Danish - dansk",
            "nl" => "Dutch - Nederlands",
            "en" => "English",
            "en-AU" => "English (Australia)",
            "en-CA" => "English (Canada)",
            "en-IN" => "English (India)",
            "en-NZ" => "English (New Zealand)",
            "en-ZA" => "English (South Africa)",
            "en-GB" => "English (United Kingdom)",
            "en-US" => "English (United States)",
            "eo" => "Esperanto - esperanto",
            "et" => "Estonian - eesti",
            "fo" => "Faroese - føroyskt",
            "fil" => "Filipino",
            "fi" => "Finnish - suomi",
            "fr" => "French - français",
            "fr-CA" => "French (Canada) - français (Canada)",
            "fr-FR" => "French (France) - français (France)",
            "fr-CH" => "French (Switzerland) - français (Suisse)",
            "gl" => "Galician - galego",
            "ka" => "Georgian - ქართული",
            "de" => "German - Deutsch",
            "de-AT" => "German (Austria) - Deutsch (Österreich)",
            "de-DE" => "German (Germany) - Deutsch (Deutschland)",
            "de-LI" => "German (Liechtenstein) - Deutsch (Liechtenstein)",
            "de-CH" => "German (Switzerland) - Deutsch (Schweiz)",
            "el" => "Greek - Ελληνικά",
            "gn" => "Guarani",
            "gu" => "Gujarati - ગુજરાતી",
            "ha" => "Hausa",
            "haw" => "Hawaiian - ʻŌlelo Hawaiʻi",
            "he" => "Hebrew - עברית",
            "hi" => "Hindi - हिन्दी",
            "hu" => "Hungarian - magyar",
            "is" => "Icelandic - íslenska",
            "id" => "Indonesian - Indonesia",
            "ia" => "Interlingua",
            "ga" => "Irish - Gaeilge",
            "it" => "Italian - italiano",
            "it-IT" => "Italian (Italy) - italiano (Italia)",
            "it-CH" => "Italian (Switzerland) - italiano (Svizzera)",
            "ja" => "Japanese - 日本語",
            "kn" => "Kannada - ಕನ್ನಡ",
            "kk" => "Kazakh - қазақ тілі",
            "km" => "Khmer - ខ្មែរ",
            "ko" => "Korean - 한국어",
            "ku" => "Kurdish - Kurdî",
            "ky" => "Kyrgyz - кыргызча",
            "lo" => "Lao - ລາວ",
            "la" => "Latin",
            "lv" => "Latvian - latviešu",
            "ln" => "Lingala - lingála",
            "lt" => "Lithuanian - lietuvių",
            "mk" => "Macedonian - македонски",
            "ms" => "Malay - Bahasa Melayu",
            "ml" => "Malayalam - മലയാളം",
            "mt" => "Maltese - Malti",
            "mr" => "Marathi - मराठी",
            "mn" => "Mongolian - монгол",
            "ne" => "Nepali - नेपाली",
            "no" => "Norwegian - norsk",
            "nb" => "Norwegian Bokmål - norsk bokmål",
            "nn" => "Norwegian Nynorsk - nynorsk",
            "oc" => "Occitan",
            "or" => "Oriya - ଓଡ଼ିଆ",
            "om" => "Oromo - Oromoo",
            "ps" => "Pashto - پښتو",
            "fa" => "Persian - فارسی",
            "pl" => "Polish - polski",
            "pt" => "Portuguese - português",
            "pt-BR" => "Portuguese (Brazil) - português (Brasil)",
            "pt-PT" => "Portuguese (Portugal) - português (Portugal)",
            "pa" => "Punjabi - ਪੰਜਾਬੀ",
            "qu" => "Quechua",
            "ro" => "Romanian - română",
            "mo" => "Romanian (Moldova) - română (Moldova)",
            "rm" => "Romansh - rumantsch",
            "ru" => "Russian - русский",
            "gd" => "Scottish Gaelic",
            "sr" => "Serbian - српски",
            "sh" => "Serbo-Croatian - Srpskohrvatski",
            "sn" => "Shona - chiShona",
            "sd" => "Sindhi",
            "si" => "Sinhala - සිංහල",
            "sk" => "Slovak - slovenčina",
            "sl" => "Slovenian - slovenščina",
            "so" => "Somali - Soomaali",
            "st" => "Southern Sotho",
            "es" => "Spanish - español",
            "es-AR" => "Spanish (Argentina) - español (Argentina)",
            "es-419" => "Spanish (Latin America) - español (Latinoamérica)",
            "es-MX" => "Spanish (Mexico) - español (México)",
            "es-ES" => "Spanish (Spain) - español (España)",
            "es-US" => "Spanish (United States) - español (Estados Unidos)",
            "su" => "Sundanese",
            "sw" => "Swahili - Kiswahili",
            "sv" => "Swedish - svenska",
            "tg" => "Tajik - тоҷикӣ",
            "ta" => "Tamil - தமிழ்",
            "tt" => "Tatar",
            "te" => "Telugu - తెలుగు",
            "th" => "Thai - ไทย",
            "ti" => "Tigrinya - ትግርኛ",
            "to" => "Tongan - lea fakatonga",
            "tr" => "Turkish - Türkçe",
            "tk" => "Turkmen",
            "tw" => "Twi",
            "uk" => "Ukrainian - українська",
            "ur" => "Urdu - اردو",
            "ug" => "Uyghur",
            "uz" => "Uzbek - o‘zbek",
            "vi" => "Vietnamese - Tiếng Việt",
            "wa" => "Walloon - wa",
            "cy" => "Welsh - Cymraeg",
            "fy" => "Western Frisian",
            "xh" => "Xhosa",
            "yi" => "Yiddish",
            "yo" => "Yoruba - Èdè Yorùbá",
            "zu" => "Zulu - isiZulu",
        );
        return array_key_exists($key, $languages) ? $languages[$key] : $key;
    }

    public static function get_view_keys()
    {
        $keys = BusinessSetting::whereIn('key', ['toggle_veg_non_veg', 'toggle_dm_registration', 'toggle_store_registration'])->get();
        $data = [];
        foreach ($keys as $key) {
            $data[$key->key] = (bool)$key->value;
        }
        return $data;
    }

    public static function default_lang()
    {
        if (strpos(url()->current(), '/api')) {
            $lang = App::getLocale();
        } elseif (auth('admin')?->check() && session()->has('local')) {
            $lang = session('local');
        } elseif ((auth('vendor_employee')?->check() || auth('vendor')?->check()) && session()->has('vendor_local')) {
            $lang = session('vendor_local');
        } elseif (session()->has('landing_local')) {
            $lang = session('landing_local');
        } elseif (session()->has('local')) {
            $lang = session('local');
        } else {
            $data = Helpers::get_business_settings('language');
            $code = 'en';
            $direction = 'ltr';
            foreach ($data as $ln) {
                if (is_array($ln) && array_key_exists('default', $ln) && $ln['default']) {
                    $code = $ln['code'];
                    if (array_key_exists('direction', $ln)) {
                        $direction = $ln['direction'];
                    }
                }
            }
            session()->put('local', $code);
            $lang = $code;
        }
        return $lang;
    }

    public static function system_default_language()
    {
        $languages = json_decode(\App\Models\BusinessSetting::where('key', 'system_language')->first()?->value);
        $lang = 'en';

        foreach ($languages as $key => $language) {
            if ($language->default) {
                $lang = $language->code;
            }
        }
        return $lang;
    }
    public static function system_default_direction()
    {
        $languages = json_decode(\App\Models\BusinessSetting::where('key', 'system_language')->first()?->value);
        $lang = 'en';

        foreach ($languages as $key => $language) {
            if ($language->default) {
                $lang = $language->direction;
            }
        }
        return $lang;
    }

    //Mail Config Check
    public static function remove_invalid_charcaters($str)
    {
        return str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $str);
    }

    //Generate referer code

    public static function generate_referer_code()
    {
        $ref_code = strtoupper(Str::random(10));

        if (self::referer_code_exists($ref_code)) {
            return self::generate_referer_code();
        }

        return $ref_code;
    }

    public static function referer_code_exists($ref_code)
    {
        return User::where('ref_code', '=', $ref_code)->exists();
    }


    public static function generate_reset_password_code()
    {
        $code = strtoupper(Str::random(15));

        if (self::reset_password_code_exists($code)) {
            return self::generate_reset_password_code();
        }

        return $code;
    }

    public static function reset_password_code_exists($code)
    {
        return DB::table('password_resets')->where('token', '=', $code)->exists();
    }

    public static function number_format_short($n)
    {
        if ($n < 900) {
            // 0 - 900
            $n = $n;
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n = $n / 1000;
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n = $n / 1000000;
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n = $n / 1000000000;
            $suffix = 'B';
        } else {
            // 0.9t+
            $n = $n / 1000000000000;
            $suffix = 'T';
        }

        if (!session()->has('currency_symbol_position')) {
            $currency_symbol_position = BusinessSetting::where(['key' => 'currency_symbol_position'])->first()->value;
            session()->put('currency_symbol_position', $currency_symbol_position);
        }
        $currency_symbol_position = session()->get('currency_symbol_position');

        return $currency_symbol_position == 'right' ? number_format($n, config('round_up_to_digit')) . $suffix . ' ' . self::currency_symbol() : self::currency_symbol() . ' ' . number_format($n, config('round_up_to_digit')) . $suffix;
    }
    // public static function export_attributes($collection){
    //     $data = [];
    //     foreach($collection as $key=>$item){
    //         $data[] = [
    //             'SL'=>$key+1,
    //              translate('messages.id') => $item['id'],
    //              translate('messages.name') => $item['name'],
    //         ];
    //     }
    //     return $data;
    // }


    public static function export_store_withdraw($collection)
    {
        $data = [];
        $status = ['pending', 'approved', 'denied'];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.amount') => $item->amount,
                translate('messages.store') => isset($item->vendor) ? $item->vendor->stores[0]->name : '',
                translate('messages.request_time') => date('Y-m-d ' . config('timeformat'), strtotime($item->created_at)),
                translate('messages.status') => isset($status[$item->approved]) ? translate("messages." . $status[$item->approved]) : "",
            ];
        }
        return $data;
    }

    public static function export_account_transaction($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.collect_from') => $item->store ? $item->store?->name : ($item->deliveryman ? $item->deliveryman->f_name . ' ' . $item->deliveryman->l_name : translate('messages.not_found')),
                translate('messages.type') => $item->from_type,
                translate('messages.received_at') => $item->created_at->format('Y-m-d ' . config('timeformat')),
                translate('messages.amount') => $item->amount,
                translate('messages.reference') => $item->ref,
            ];
        }
        return $data;
    }

    public static function export_dm_earning($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.name') => isset($item->delivery_man) ? $item->delivery_man->f_name . ' ' . $item->delivery_man->l_name : translate('messages.not_found'),
                translate('messages.received_at') => $item->created_at->format('Y-m-d ' . config('timeformat')),
                translate('messages.amount') => $item->amount,
                translate('messages.method') => $item->method,
                translate('messages.reference') => $item->ref,
            ];
        }
        return $data;
    }

    public static function export_items($foods, $module_type)
    {
        $storage = [];
        foreach ($foods as $item) {
            $category_id = 0;
            $sub_category_id = 0;
            foreach (json_decode($item->category_ids, true) as $key => $category) {
                if ($key == 0) {
                    $category_id = $category['id'];
                } else if ($key == 1) {
                    $sub_category_id = $category['id'];
                }
            }
            $storage[] = [
                'Id' => $item->id,
                'Name' => $item->name,
                'Description' => $item->description,
                'Image' => $item->image,
                'Images' => $item->images,
                'CategoryId' => $category_id,
                'SubCategoryId' => $sub_category_id,
                'UnitId' => $item->unit_id,
                'Stock' => $item->stock,
                'Price' => $item->price,
                'Discount' => $item->discount,
                'DiscountType' => $item->discount_type,
                'AvailableTimeStarts' => $item->available_time_starts,
                'AvailableTimeEnds' => $item->available_time_ends,
                'Variations' => $module_type == 'food' ? $item->food_variations : $item->variations,
                'AddOns' => str_replace(['"', '[', ']'], '', $item->add_ons),
                'Attributes' => str_replace(['"', '[', ']'], '', $item->attributes),
                'StoreId' => $item->store_id,
                'ModuleId' => $item->module_id,
                'Status' => $item->status == 1 ? 'active' : 'inactive',
                'Veg' => $item->veg == 1 ? 'yes' : 'no',
                'Recommended' => $item->recommended == 1 ? 'yes' : 'no',
            ];
        }

        return $storage;
    }

    public static function export_store_item($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.name') => $item['name'],
                translate('messages.type') => $item->category ? $item->category->name : '',
                translate('messages.price') => $item['price'],
                translate('messages.status') => $item['status'],
            ];
        }
        return $data;
    }

    public static function export_stores($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'id' => $item->id,
                'ownerId' => $item->vendor->id,
                'ownerFirstName' => $item->vendor->f_name,
                'ownerLastName' => $item->vendor->l_name,
                'storeName' => $item->name,
                'phone' => $item->vendor->phone,
                'email' => $item->vendor->email,
                'logo' => $item->logo,
                'CoverPhoto' => $item->cover_photo,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'Address' => $item->address ?? null,
                'zone_id' => $item->zone_id,
                'module_id' => $item->module_id,
                'MinimumOrderAmount' => $item->minimum_order,
                'Comission' => $item->comission ?? 0,
                'Tax' => $item->tax ?? 0,
                'DeliveryTime' => $item->delivery_time ?? '20-30',
                'MinimumDeliveryFee' => $item->minimum_shipping_charge ?? 0,
                'PerKmDeliveryFee' => $item->per_km_shipping_charge ?? 0,
                'MaximumDeliveryFee' => $item->maximum_shipping_charge ?? 0,
                'ScheduleOrder' => $item->schedule_order == 1 ? 'yes' : 'no',
                'Status' => $item->status == 1 ? 'active' : 'inactive',
                'SelfDeliverySystem' => $item->self_delivery_system == 1 ? 'active' : 'inactive',
                'Veg' => $item->veg == 1 ? 'yes' : 'no',
                'NonVeg' => $item->non_veg == 1 ? 'yes' : 'no',
                'FreeDelivery' => $item->free_delivery == 1 ? 'yes' : 'no',
                'TakeAway' => $item->take_away == 1 ? 'yes' : 'no',
                'Delivery' => $item->delivery == 1 ? 'yes' : 'no',
                'ReviewsSection' => $item->reviews_section == 1 ? 'active' : 'inactive',
                // 'PosSystem' => $item->pos_system == 1 ? 'active' : 'inactive',
                'storeOpen' => $item->active == 1 ? 'yes' : 'no',
                'FeaturedStore' => $item->featured == 1 ? 'yes' : 'no',
            ];
        }
        return $data;
    }

    public static function export_units($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.unit') => $item['unit'],
            ];
        }
        return $data;
    }

    public static function export_customers($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.name') => $item->f_name . ' ' . $item->l_name,
                translate('messages.phone') => $item['phone'],
                translate('messages.email') => $item['email'],
                translate('messages.total_order') => $item['order_count'],
                translate('messages.status') => $item['status'],
            ];
        }
        return $data;
    }

    public static function export_day_wise_report($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.order_id') => $item['order_id'],
                translate('messages.store') => $item->order->store ? $item->order->store->name : translate('messages.invalid'),
                translate('messages.customer_name') => $item->order->customer ? $item->order->customer['f_name'] . ' ' . $item->order->customer['l_name'] : translate('messages.invalid_customer_data'),
                translate('total_item_amount') => \App\CentralLogics\Helpers::format_currency($item->order['order_amount'] - $item->order['dm_tips'] - $item->order['delivery_charge'] - $item['tax'] + $item->order['coupon_discount_amount'] + $item->order['store_discount_amount']),
                translate('item_discount') => \App\CentralLogics\Helpers::format_currency($item->order->details->sum('discount_on_item')),
                translate('coupon_discount') => \App\CentralLogics\Helpers::format_currency($item->order['coupon_discount_amount']),
                translate('discounted_amount') => \App\CentralLogics\Helpers::format_currency($item->order['coupon_discount_amount'] + $item->order['store_discount_amount']),
                translate('messages.tax') => \App\CentralLogics\Helpers::format_currency($item->order['total_tax_amount']),
                translate('messages.delivery_charge') => \App\CentralLogics\Helpers::format_currency($item['delivery_charge']),
                translate('messages.total_order_amount') => \App\CentralLogics\Helpers::format_currency($item['order_amount']),
                translate('messages.admin_discount') => \App\CentralLogics\Helpers::format_currency($item['admin_expense']),
                translate('messages.store_discount') => \App\CentralLogics\Helpers::format_currency($item->order['store_discount_amount']),
                translate('messages.admin_commission') => \App\CentralLogics\Helpers::format_currency(($item->admin_commission + $item->admin_expense) - $item->delivery_fee_comission),
                translate('Comission on delivery fee') => \App\CentralLogics\Helpers::format_currency($item['delivery_fee_comission']),
                translate('admin_net_income') => \App\CentralLogics\Helpers::format_currency($item['admin_commission']),
                translate('store_net_income') => \App\CentralLogics\Helpers::format_currency($item['store_amount'] - $item['tax']),
                translate('messages.amount_received_by') => $item['received_by'],
                translate('messages.payment_method') => translate(str_replace('_', ' ', $item->order['payment_method'])),
                translate('messages.payment_status') => $item->status ? translate("messages.refunded") : translate("messages.completed"),
            ];
        }
        return $data;
    }


    public static function export_expense_wise_report($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            if (isset($item->order->customer)) {
                $customer_name = $item->order->customer->f_name . ' ' . $item->order->customer->l_name;
            }
            $data[] = [
                'SL' => $key + 1,
                translate('messages.order_id') => $item['order_id'],
                translate('messages.expense_date') =>  $item['created_at'],
                // translate('messages.expense_date') =>  $item->created_at->format('Y-m-d '.config('timeformat')),
                translate('messages.type') => str::title(str_replace('_', ' ',  $item['type'])),
                translate('messages.customer_name') => $customer_name,
                translate('messages.amount') => $item['amount'],
            ];
        }
        return $data;
    }

    public static function export_item_wise_report($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.name') => $item['name'],
                translate('messages.module') => $item->module ? $item->module->module_name : '',
                translate('messages.store') => $item->store ? $item->store?->name : '',
                translate('messages.order') => $item->orders_count,
                translate('messages.price') => \App\CentralLogics\Helpers::format_currency($item->price),
                translate('messages.total_amount_sold') => \App\CentralLogics\Helpers::format_currency($item->orders_sum_price),
                translate('messages.total_discount_given') => \App\CentralLogics\Helpers::format_currency($item->orders_sum_discount_on_item),
                translate('messages.average_sale_value') => $item->orders_count > 0 ? \App\CentralLogics\Helpers::format_currency(($item->orders_sum_price - $item->orders_sum_discount_on_item) / $item->orders_count) : 0,
                translate('messages.average_ratings') => round($item->avg_rating, 1),
            ];
        }
        return $data;
    }

    public static function export_stock_wise_report($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.name') => $item['name'],
                translate('messages.store') => $item->store ? $item->store?->name : '',
                translate('messages.zone') => ($item->store && $item->store?->zone) ? $item->store?->zone->name : '',
                translate('messages.stock') => $item['stock'],
            ];
        }
        return $data;
    }

    public static function export_delivery_men($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.name') => $item->f_name . ' ' . $item->l_name,
                translate('messages.phone') => $item['phone'],
                translate('messages.zone') => $item->zone ? $item->zone->name : '',
                translate('messages.total_order') => $item['order_count'],
                translate('messages.currently_assigned_orders') => (int) $item['current_orders'],
                translate('messages.status') => $item['status'],
            ];
        }
        return $data;
    }

    public static function hex_to_rbg($color)
    {
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        $output = "$r, $g, $b";
        return $output;
    }

    public static function expenseCreate($amount, $type, $datetime, $created_by, $order_id = null, $store_id = null, $description = '', $delivery_man_id = null, $user_id = null)
    {
        $expense = new Expense();
        $expense->amount = $amount;
        $expense->type = $type;
        $expense->order_id = $order_id;
        $expense->created_by = $created_by;
        $expense->store_id = $store_id;
        $expense->delivery_man_id = $delivery_man_id;
        $expense->user_id = $user_id;
        $expense->description = $description;
        $expense->created_at = now();
        $expense->updated_at = now();
        return $expense->save();
    }

    public static function get_varient(array $product_variations, $variations)
    {
        $result = [];
        $variation_price = 0;

        foreach ($variations as $k => $variation) {
            foreach ($product_variations as  $product_variation) {
                if (isset($variation['values']) && isset($product_variation['values']) && $product_variation['name'] == $variation['name']) {
                    $result[$k] = $product_variation;
                    $result[$k]['values'] = [];
                    foreach ($product_variation['values'] as $key => $option) {
                        if (in_array($option['label'], $variation['values']['label'])) {
                            $result[$k]['values'][] = $option;
                            $variation_price += $option['optionPrice'];
                        }
                    }
                }
            }
        }

        return ['price' => $variation_price, 'variations' => $result];
    }

    public static function food_variation_price($product, $variations)
    {
        // $match = json_decode($variations, true)[0];
        $match = $variations;
        $result = 0;
        // foreach (json_decode($product['variations'], true) as $property => $value) {
        //     if ($value['type'] == $match['type']) {
        //         $result = $value['price'];
        //     }
        // }
        foreach ($product as $product_variation) {
            foreach ($product_variation['values'] as $option) {
                foreach ($match as $variation) {
                    if ($product_variation['name'] == $variation['name'] && isset($variation['values']) && in_array($option['label'], $variation['values']['label'])) {
                        $result += $option['optionPrice'];
                    }
                }
            }
        }

        return $result;
    }

    public static function gen_mpdf($view, $file_prefix, $file_postfix)
    {
        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../storage/tmp', 'default_font' => 'FreeSerif', 'mode' => 'utf-8', 'format' => [190, 250]]);
        /* $mpdf->AddPage('XL', '', '', '', '', 10, 10, 10, '10', '270', '');*/
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf_view = $view;
        $mpdf_view = $mpdf_view->render();
        $mpdf->WriteHTML($mpdf_view);
        $mpdf->Output($file_prefix . $file_postfix . '.pdf', 'D');
    }



    public static function auto_translator($q, $sl, $tl)
    {
        $res = file_get_contents("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" . $tl . "&hl=hl&q=" . urlencode($q), $_SERVER['DOCUMENT_ROOT'] . "/transes.html");
        $res = json_decode($res);
        return str_replace('_', ' ', $res[0][0][0]);
    }

    public static function getLanguageCode(string $country_code): string
    {
        $locales = array(
            'en-English(default)',
            'af-Afrikaans',
            'sq-Albanian - shqip',
            'am-Amharic - አማርኛ',
            'ar-Arabic - العربية',
            'an-Aragonese - aragonés',
            'hy-Armenian - հայերեն',
            'ast-Asturian - asturianu',
            'az-Azerbaijani - azərbaycan dili',
            'eu-Basque - euskara',
            'be-Belarusian - беларуская',
            'bn-Bengali - বাংলা',
            'bs-Bosnian - bosanski',
            'br-Breton - brezhoneg',
            'bg-Bulgarian - български',
            'ca-Catalan - català',
            'ckb-Central Kurdish - کوردی (دەستنوسی عەرەبی)',
            'zh-Chinese - 中文',
            'zh-HK-Chinese (Hong Kong) - 中文（香港）',
            'zh-CN-Chinese (Simplified) - 中文（简体）',
            'zh-TW-Chinese (Traditional) - 中文（繁體）',
            'co-Corsican',
            'hr-Croatian - hrvatski',
            'cs-Czech - čeština',
            'da-Danish - dansk',
            'nl-Dutch - Nederlands',
            'en-AU-English (Australia)',
            'en-CA-English (Canada)',
            'en-IN-English (India)',
            'en-NZ-English (New Zealand)',
            'en-ZA-English (South Africa)',
            'en-GB-English (United Kingdom)',
            'en-US-English (United States)',
            'eo-Esperanto - esperanto',
            'et-Estonian - eesti',
            'fo-Faroese - føroyskt',
            'fil-Filipino',
            'fi-Finnish - suomi',
            'fr-French - français',
            'fr-CA-French (Canada) - français (Canada)',
            'fr-FR-French (France) - français (France)',
            'fr-CH-French (Switzerland) - français (Suisse)',
            'gl-Galician - galego',
            'ka-Georgian - ქართული',
            'de-German - Deutsch',
            'de-AT-German (Austria) - Deutsch (Österreich)',
            'de-DE-German (Germany) - Deutsch (Deutschland)',
            'de-LI-German (Liechtenstein) - Deutsch (Liechtenstein)
            ',
            'de-CH-German (Switzerland) - Deutsch (Schweiz)',
            'el-Greek - Ελληνικά',
            'gn-Guarani',
            'gu-Gujarati - ગુજરાતી',
            'ha-Hausa',
            'haw-Hawaiian - ʻŌlelo Hawaiʻi',
            'he-Hebrew - עברית',
            'hi-Hindi - हिन्दी',
            'hu-Hungarian - magyar',
            'is-Icelandic - íslenska',
            'id-Indonesian - Indonesia',
            'ia-Interlingua',
            'ga-Irish - Gaeilge',
            'it-Italian - italiano',
            'it-IT-Italian (Italy) - italiano (Italia)',
            'it-CH-Italian (Switzerland) - italiano (Svizzera)',
            'ja-Japanese - 日本語',
            'kn-Kannada - ಕನ್ನಡ',
            'kk-Kazakh - қазақ тілі',
            'km-Khmer - ខ្មែរ',
            'ko-Korean - 한국어',
            'ku-Kurdish - Kurdî',
            'ky-Kyrgyz - кыргызча',
            'lo-Lao - ລາວ',
            'la-Latin',
            'lv-Latvian - latviešu',
            'ln-Lingala - lingála',
            'lt-Lithuanian - lietuvių',
            'mk-Macedonian - македонски',
            'ms-Malay - Bahasa Melayu',
            'ml-Malayalam - മലയാളം',
            'mt-Maltese - Malti',
            'mr-Marathi - मराठी',
            'mn-Mongolian - монгол',
            'ne-Nepali - नेपाली',
            'no-Norwegian - norsk',
            'nb-Norwegian Bokmål - norsk bokmål',
            'nn-Norwegian Nynorsk - nynorsk',
            'oc-Occitan',
            'or-Oriya - ଓଡ଼ିଆ',
            'om-Oromo - Oromoo',
            'ps-Pashto - پښتو',
            'fa-Persian - فارسی',
            'pl-Polish - polski',
            'pt-Portuguese - português',
            'pt-BR-Portuguese (Brazil) - português (Brasil)',
            'pt-PT-Portuguese (Portugal) - português (Portugal)',
            'pa-Punjabi - ਪੰਜਾਬੀ',
            'qu-Quechua',
            'ro-Romanian - română',
            'mo-Romanian (Moldova) - română (Moldova)',
            'rm-Romansh - rumantsch',
            'ru-Russian - русский',
            'gd-Scottish Gaelic',
            'sr-Serbian - српски',
            'sh-Serbo-Croatian - Srpskohrvatski',
            'sn-Shona - chiShona',
            'sd-Sindhi',
            'si-Sinhala - සිංහල',
            'sk-Slovak - slovenčina',
            'sl-Slovenian - slovenščina',
            'so-Somali - Soomaali',
            'st-Southern Sotho',
            'es-Spanish - español',
            'es-AR-Spanish (Argentina) - español (Argentina)',
            'es-419-Spanish (Latin America) - español (Latinoamérica)
            ',
            'es-MX-Spanish (Mexico) - español (México)',
            'es-ES-Spanish (Spain) - español (España)',
            'es-US-Spanish (United States) - español (Estados Unidos)
            ',
            'su-Sundanese',
            'sw-Swahili - Kiswahili',
            'sv-Swedish - svenska',
            'tg-Tajik - тоҷикӣ',
            'ta-Tamil - தமிழ்',
            'tt-Tatar',
            'te-Telugu - తెలుగు',
            'th-Thai - ไทย',
            'ti-Tigrinya - ትግርኛ',
            'to-Tongan - lea fakatonga',
            'tr-Turkish - Türkçe',
            'tk-Turkmen',
            'tw-Twi',
            'uk-Ukrainian - українська',
            'ur-Urdu - اردو',
            'ug-Uyghur',
            'uz-Uzbek - o‘zbek',
            'vi-Vietnamese - Tiếng Việt',
            'wa-Walloon - wa',
            'cy-Welsh - Cymraeg',
            'fy-Western Frisian',
            'xh-Xhosa',
            'yi-Yiddish',
            'yo-Yoruba - Èdè Yorùbá',
            'zu-Zulu - isiZulu',
        );

        foreach ($locales as $locale) {
            $locale_region = explode('-', $locale);
            if ($country_code == $locale_region[0]) {
                return $locale_region[0];
            }
        }

        return "en";
    }


    public static function pagination_limit()
    {
        $pagination_limit = BusinessSetting::where('key', 'pagination_limit')->first();
        if ($pagination_limit != null) {
            return $pagination_limit->value;
        } else {
            return 25;
        }
    }

    public static function language_load()
    {
        if (\session()->has('language_settings')) {
            $language = \session('language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('language_settings', $language);
        }
        return $language;
    }

    public static function vendor_language_load()
    {
        if (\session()->has('vendor_language_settings')) {
            $language = \session('vendor_language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('vendor_language_settings', $language);
        }
        return $language;
    }

    public static function landing_language_load()
    {
        if (\session()->has('landing_language_settings')) {
            $language = \session('landing_language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('landing_language_settings', $language);
        }
        return $language;
    }


    public static function product_tax($price, $tax, $is_include = false)
    {
        $price_tax = ($price * $tax) / (100 + ($is_include ? $tax : 0));
        return $price_tax;
    }

    public static function apple_client_secret()
    {
        // Set up the necessary variables
        $keyId = 'U7KA7F82UM';
        $teamId = '7WSYLQ8Y87';
        $clientId = 'com.sixamtech.sixamMartApp';
        $privateKey = file_get_contents('AuthKey_U7KA7F82UM.p8'); // Should be a string containing the contents of the private key file.

        // Create the JWT header
        $header = [
            'alg' => 'ES256',
            'kid' => $keyId,
        ];

        // Create the JWT payload
        $payload = [
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 86400 * 180, // 180 days in seconds
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        // Encode the JWT header and payload
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // Create the signature using the private key and the SHA-256 algorithm
        $dataToSign = $base64Header . '.' . $base64Payload;
        $signature = '';
        openssl_sign($dataToSign, $signature, $privateKey, 'sha256');

        // Encode the signature
        $base64Signature = base64_encode($signature);

        // Create the Apple Client Secret key
        $clientSecret = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        // Output the key
        return $clientSecret;
    }

    public static function error_formater($key, $mesage, $errors = [])
    {
        $errors[] = ['code' => $key, 'message' => $mesage];

        return $errors;
    }

    public static function Export_generator($datas)
    {
        foreach ($datas as $data) {
            yield $data;
        }
        return true;
    }

    public static function export_addons($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'Id' => $item->id,
                'Name' => $item->name,
                'Price' => $item->price,
                'StoreId' => $item->store_id,
                'Status' => $item->status == 1 ? 'active' : 'inactive'
            ];
        }
        return $data;
    }
    public static function export_categories($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'Id' => $item->id,
                'Name' => $item->name,
                'Image' => $item->image,
                'ParentId' => $item->parent_id,
                'Position' => $item->position,
                'Priority' => $item->priority,
                'Status' => $item->status == 1 ? 'active' : 'inactive',
            ];
        }
        return $data;
    }

    public static function get_mail_status($name)
    {
        $status = BusinessSetting::where('key', $name)->first()?->value ?? 0;
        return $status;
    }

    public static function text_variable_data_format($value, $user_name = null, $store_name = null, $delivery_man_name = null, $transaction_id = null, $order_id = null)
    {
        $data = $value;
        if ($value) {
            if ($user_name) {
                $data =  str_replace("{userName}", $user_name, $data);
            }

            if ($store_name) {
                $data =  str_replace("{storeName}", $store_name, $data);
            }

            if ($delivery_man_name) {
                $data =  str_replace("{deliveryManName}", $delivery_man_name, $data);
            }

            if ($transaction_id) {
                $data =  str_replace("{transactionId}", $transaction_id, $data);
            }

            if ($order_id) {
                $data =  str_replace("{orderId}", $order_id, $data);
            }
        }

        return $data;
    }

    public static function get_login_url($type)
    {
        $data = DataSetting::whereIn('key', [
            'store_employee_login_url',
            'store_login_url',
            'admin_employee_login_url',
            'admin_login_url'
        ])->pluck('key', 'value')->toArray();

        return array_search($type, $data);
    }

    public static function react_activation_check($react_domain, $react_license_code)
    {
        $scheme = str_contains($react_domain, 'localhost') ? 'http://' : 'https://';
        $url = empty(parse_url($react_domain)['scheme']) ? $scheme . ltrim($react_domain, '/') : $react_domain;
        $response = Http::post('https://mychitti.net/api/v1/customer/license-check', [
            'domain_name' => str_ireplace('www.', '', parse_url($url, PHP_URL_HOST)),
            'license_code' => $react_license_code
        ]);
        return ($response->successful() && isset($response->json('content')['is_active']) && $response->json('content')['is_active']);
    }

    public static function activation_submit($purchase_key)
    {
        $post = [
            'purchase_key' => $purchase_key
        ];
        $live = 'https://mychitti.net';
        $ch = curl_init($live . '/api/v1/software-check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);

        curl_close($ch);
        $response_body = json_decode($response, true);

        try {
            if ($response_body['is_valid'] && $response_body['result']['item']['id'] == env('REACT_APP_KEY')) {
                $previous_active = json_decode(BusinessSetting::where('key', 'app_activation')->first()->value ?? '[]');
                $found = 0;
                foreach ($previous_active as $key => $item) {
                    if ($item->software_id == env('REACT_APP_KEY')) {
                        $found = 1;
                    }
                }
                if (!$found) {
                    $previous_active[] = [
                        'software_id' => env('REACT_APP_KEY'),
                        'is_active' => 1
                    ];
                    DB::table('business_settings')->updateOrInsert(['key' => 'app_activation'], [
                        'value' => json_encode($previous_active)
                    ]);
                }
                return true;
            }
        } catch (\Exception $exception) {
            info($exception->getMessage());

            $previous_active[] = [
                'software_id' => env('REACT_APP_KEY'),
                'is_active' => 1
            ];
            DB::table('business_settings')->updateOrInsert(['key' => 'app_activation'], [
                'value' => json_encode($previous_active)
            ]);

            return true;
        }
        return false;
    }

    public static function react_domain_status_check()
    {
        $data = self::get_business_settings('react_setup');
        if ($data && isset($data['react_domain']) && isset($data['react_license_code'])) {
            if (isset($data['react_platform']) && $data['react_platform'] == 'codecanyon') {
                $data['status'] = (int)self::activation_submit($data['react_license_code']);
            } elseif (!self::react_activation_check($data['react_domain'], $data['react_license_code'])) {
                $data['status'] = 0;
            } elseif ($data['status'] != 1) {
                $data['status'] = 1;
            }
            DB::table('business_settings')->updateOrInsert(['key' => 'react_setup'], [
                'value' => json_encode($data)
            ]);
        }
    }

    public static function export_order_transaction_report($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[] = [
                'SL' => $key + 1,
                translate('messages.id') => $item['id'],
                translate('messages.vendor_id') => $item['vendor_id'],
                translate('messages.delivery_man_id') => $item['delivery_man_id'],
                translate('messages.order_id') => $item['order_id'],
                translate('messages.order_amount') => $item['order_amount'],
                translate('messages.store_amount') => $item['store_amount'] - $item['tax'],
                translate('messages.admin_commission') => $item['admin_commission'],
                translate('messages.received_by') => $item['received_by'],
                translate('messages.status') => $item['status'],
                translate('messages.created_at') => $item['created_at'],
                translate('messages.updated_at') => $item['updated_at'],
                translate('messages.delivery_charge') => $item['delivery_charge'],
                translate('messages.original_delivery_charge') => $item['original_delivery_charge'],
                translate('messages.tax') => $item['tax'],
                translate('messages.zone_id') => $item['zone_id'],
                translate('messages.module_id') => $item['module_id'],
                translate('messages.parcel_catgory_id') => $item['parcel_catgory_id'],
                translate('messages.dm_tips') => $item['dm_tips'],
                translate('messages.delivery_fee_comission') => $item['delivery_fee_comission'],
                translate('messages.admin_expense') => $item['admin_expense'],
                translate('messages.store_expense') => $item['store_expense'],
                translate('messages.discount_amount_by_store') => $item['discount_amount_by_store'],
            ];
        }
        return $data;
    }

    public static function get_zones_name($zones)
    {
        if (is_array($zones)) {
            $data = Zone::whereIn('id', $zones)->pluck('name')->toArray();
        } else {
            $data = Zone::where('id', $zones)->pluck('name')->toArray();
        }
        $data = implode(', ', $data);
        return $data;
    }

    public static function get_stores_name($stores)
    {
        if (is_array($stores)) {
            $data = Store::whereIn('id', $stores)->pluck('name')->toArray();
        } else {
            $data = Store::where('id', $stores)->pluck('name')->toArray();
        }
        $data = implode(', ', $data);
        return $data;
    }

    public static function get_category_name($id)
    {
        $id = Json_decode($id, true);
        $id = data_get($id, '0.id', 'NA');
        return Category::where('id', $id)->first()?->name;
    }
    public static function get_sub_category_name($id)
    {
        $id = Json_decode($id, true);
        $id = data_get($id, '1.id', 'NA');
        return Category::where('id', $id)->first()?->name;
    }
    public static function get_attributes($choice_options)
    {
        try {
            $data = [];
            foreach ((array)json_decode($choice_options) as $key => $choice) {
                $data[$choice->title] = $choice->options;
            }
            return str_ireplace(['\'', '"', '{', '}', '[', ']', ';', '<', '>', '?'], ' ', json_encode($data));
        } catch (\Exception $ex) {
            info(["line___{$ex->getLine()}", $ex->getMessage()]);
            return 0;
        }
    }

    public static function get_module_name($id)
    {
        return Module::where('id', $id)->first()?->module_name;
    }

    public static function get_food_variations($variations)
    {
        try {
            $data = [];
            $data2 = [];
            foreach ((array)json_decode($variations, true) as $key => $choice) {
                foreach ($choice['values'] as $k => $v) {
                    $data2[$k] =  $v['label'];
                    // if(!next($choice['values'] )) {
                    //     $data2[$k] =  $v['label'].";";
                    // }
                }
                $data[$choice['name']] = $data2;
            }
            return str_ireplace(['\'', '"', '{', '}', '[', ']', '<', '>', '?'], ' ', json_encode($data));
        } catch (\Exception $ex) {
            info(["line___{$ex->getLine()}", $ex->getMessage()]);
            return 0;
        }
    }

    public static function get_customer_name($id)
    {
        $user = User::where('id', $id)->first();

        return $user->f_name . ' ' . $user->l_name;
    }
    public static function get_addon_data($id)
    {
        try {
            $data = [];
            $addon = AddOn::whereIn('id', json_decode($id, true))->get(['name', 'price'])->toArray();
            foreach ($addon as $key => $value) {
                $data[$key] = $value['name'] . ' - ' . \App\CentralLogics\Helpers::format_currency($value['price']);
            }
            return str_ireplace(['\'', '"', '{', '}', '[', ']', '<', '>', '?'], ' ', json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $ex) {
            info(["line___{$ex->getLine()}", $ex->getMessage()]);
            return 0;
        }
    }



    public static function add_or_update_translations($request, $key_data, $name_field, $model_name, $data_id, $data_value)
    {
        try {
            $model = 'App\\Models\\' . $model_name;
            $default_lang = str_replace('_', '-', app()->getLocale());
            foreach ($request->lang as $index => $key) {
                if ($default_lang == $key && !($request->{$name_field}[$index])) {
                    if ($key != 'default') {
                        Translation::updateorcreate(
                            [
                                'translationable_type' =>  $model,
                                'translationable_id' => $data_id,
                                'locale' => $key,
                                'key' => $key_data
                            ],
                            ['value' => $data_value]
                        );
                    }
                } else {
                    if ($request->{$name_field}[$index] && $key != 'default') {
                        Translation::updateorcreate(
                            [
                                'translationable_type' => $model,
                                'translationable_id' => $data_id,
                                'locale' => $key,
                                'key' => $key_data
                            ],
                            ['value' => $request->{$name_field}[$index]]
                        );
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            info(["line___{$e->getLine()}", $e->getMessage()]);
            return false;
        }
    }

    public static function offline_payment_formater($user_data)
    {
        $userInputs = [];

        $user_inputes =  json_decode($user_data->payment_info, true);
        $method_name = $user_inputes['method_name'];
        $method_id = $user_inputes['method_id'];

        foreach ($user_inputes as $key => $value) {
            if (!in_array($key, ['method_name', 'method_id'])) {
                $userInput = [
                    'user_input' => $key,
                    'user_data' => $value,
                ];
                $userInputs[] = $userInput;
            }
        }

        $data = [
            'status' => $user_data->status,
            'method_id' => $method_id,
            'method_name' => $method_name,
            'customer_note' => $user_data->customer_note,
            'admin_note' => $user_data->note,
        ];

        $result = [
            'input' => $userInputs,
            'data' => $data,
            'method_fields' => json_decode($user_data->method_fields, true),
        ];

        return $result;
    }

    public static function time_date_format($data)
    {
        $time = config('timeformat') ?? 'H:i';
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat('d M Y ' . $time);
    }
    public static function date_format($data)
    {
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat('d M Y');
    }
    public static function time_format($data)
    {
        $time = config('timeformat') ?? 'H:i';
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat($time);
    }


    public static function onerror_image_helper($data, $src, $error_src, $path)
    {

        if (isset($data) && strlen($data) > 1 && Storage::disk('public')->exists($path . $data)) {
            return $src;
        }
        return $error_src;
    }
}
