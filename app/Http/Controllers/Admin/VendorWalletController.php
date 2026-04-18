<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\BusinessSetting;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\Store;
use App\Models\StoreWallet;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;


class VendorWalletController extends Controller
{
    public function index()
    {
        $storeWallets = StoreWallet::with('vendorStore', 'lastRecharge')->paginate(config('default_pagination'));
        return view('admin-views.vendor.wallet', compact('storeWallets'));
    }
    public function view(Request $request, $id)
    {
        $search = $request->query('search');
        $wallet = StoreWallet::with('vendorStore')->where('id', $id)->firstOrFail();
        $transactions = $wallet->transactions()
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        })
        ->where('method', 'wallet')
        ->orderBy('created_at', 'desc')->paginate(config('default_pagination'));
        return view('admin-views.vendor.wallet_view', compact('wallet', 'transactions'));
    }
    public function recharge(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount'   => 'required|numeric|min:1',
        ]);

        $store_id = $request->store_id;
        $amount  = round($request->amount, 2); // amount paid
        $vendor_id = Store::where('id', $store_id)->value('vendor_id');
        $actionType = 'retail_recharge_wallet';
        $payload = ['store_id' => $store_id, 'vendor_id' => $vendor_id, 'amount' => $amount, 'billing' => $request->billing];
        $data = [
            'actionType' =>  $actionType,
            'payload' => $payload,
            'requestedBy' => auth('admin')->id()
        ];
        if (!$request->billing) {
            // create pending action
            Helpers::createPendingAction($data);
            return response()->json(['status' => true, 'msg' => 'OTP Send to master admin. Please verify', 'actionType' => $actionType]);
        } else {
            return $this->recharge_proceed($payload);
        }
    }
    public static function recharge_proceed($data)
    {
        $store_id   = $data['store_id'];
        $vendor_id   = $data['vendor_id'];
        $amount  = round($data['amount'], 2); // amount paid
        $store   = Store::findOrFail($store_id);

        /* ================= WALLET ================= */
        $wallet = StoreWallet::firstOrCreate(
            ['vendor_id' => $vendor_id],
            [
                'total_earning'    => 0,
                'total_withdrawn'  => 0,
                'pending_withdraw' => 0,
            ]
        );

        $wallet->increment('total_earning', $amount);
        $gstStatus = BusinessSetting::where('key', 'wallet_recharge_gst_status')
            ->value('value') ?? 'included';

        /* ================= TRANSACTION ================= */
        if ($data['billing']) {

            $accountTransaction = new AccountTransaction();
            $accountTransaction->current_balance = $wallet->total_earning;
            $accountTransaction->from_type = 'store';
            $accountTransaction->from_id = $vendor_id;
            $accountTransaction->amount = $amount;
            $accountTransaction->method = 'wallet';
            $accountTransaction->action = 'credit';
            $accountTransaction->reason = 'Wallet Recharge';
            $accountTransaction->created_by = 'admin';
            $accountTransaction->save();

            /* ================= GST SETTINGS ================= */
            $gstPercent = BusinessSetting::where('key', 'wallet_recharge_gst_percent')->value('value'); // 15
            $hsn        = BusinessSetting::where('key', 'wallet_recharge_hsn')->value('value');

            $halfGst = $gstPercent / 2;

            /* ================= GST CALCULATION ================= */

            // Example rule: derive taxable from business logic
            if ($gstStatus === 'included') {

                // Amount includes GST
                $taxable = round($amount / (1 + ($gstPercent / 100)), 2);

                $cgst = round($taxable * ($halfGst / 100), 2);
                $sgst = round($taxable * ($halfGst / 100), 2);

                $taxTotal = round($cgst + $sgst, 2);
                $computedTotal = round($taxable + $taxTotal, 2);
                $roundOff = round($amount - $computedTotal, 2);

                $finalAmount = $amount;
            } else {

                // Amount excludes GST
                $taxable = round($amount, 2);

                $cgst = round($taxable * ($halfGst / 100), 2);
                $sgst = round($taxable * ($halfGst / 100), 2);

                $taxTotal = round($cgst + $sgst, 2);
                $roundOff = 0;

                $finalAmount = round($taxable + $taxTotal, 2);
            }


            /* ================= INVOICE ================= */
            $invoice = new ManualInvoice();
            $invoice->invoice_id      = Helpers::generateInvoiceIdAdmin();
            $invoice->invoice_serial  = BusinessSetting::where('key', 'admin_bill_serial_number')->value('value') - 1;
            $invoice->vendor_id       = null;
            $invoice->bill_to         = $store_id;
            $invoice->bill_to_type    = 'vendor';
            $invoice->module_id       = $store->module_id;
            $invoice->subtotal_amount = $taxable;
            $invoice->cgst            = $cgst;
            $invoice->sgst            = $sgst;
            $invoice->igst            = $sgst + $cgst;
            $invoice->final_tax       = $taxTotal;
            $invoice->taxable_amount  = $taxable;
            $invoice->round_off       = $roundOff;
            $invoice->total_amount    = $finalAmount;
            $invoice->payment_method  = 'Cash';
            $invoice->tax_type        = 'gst';
            $invoice->payment_status  = 'Paid';
            $invoice->payment_date    = now()->toDateString();
            $invoice->generated_by    = 'admin';
            $invoice->save();

            // prx($invoice->final_tax);
            /* ================= INVOICE ITEM ================= */
            $item = new InvoiceItem();
            $item->rand_invoice_id  = $invoice->invoice_id;
            $item->manual_invoice_id = $invoice->id;
            $item->name            = 'Wallet Recharge';
            $item->qty             = 1;
            $item->price           = $taxable;
            $item->cgst_rate       = $halfGst;
            $item->cgst_amount     = $cgst;
            $item->sgst_rate       = $halfGst;
            $item->sgst_amount     = $sgst;
            $item->hsn             = $hsn;
            $item->total           = $finalAmount;
            $item->save();

            // ledger entry 
            $credit_account = Helpers::ensureWalletRevenueAccount();
            $debit_account = Helpers::ensurePurchaseAccount('Wallet Recharge', $store_id);

            $data = [
                'date' => now(),
                'amount' => $invoice->total_amount,
                'voucher_type' => 'Purchase',
                'status' => 'approved',
            ];
            $voucher =  _masterLedgerEntry($data, $credit_account, $debit_account, 'admin', 'admin', null);
            _saveDayBookEntry($invoice->total_amount, 'debit', $store_id, 'Wallet Recharge', $invoice->id, $voucher?->id);
            try {
                $data = _createBillPdf($invoice, 'admin');
                $invoice->update(['pdf' => $data['pdf']]);
                return response()->json(['status' => true, 'msg' => 'Recharged Successfully', 'redirect_url' => $data['url']]);
                // return redirect($data['url']);
            } catch (\Throwable $th) {
                //
            }
        } else {
            return response()->json(['status' => true, 'msg' => 'Recharged Successfully']);

            // Toastr::success('Wallet Recharged Successfully');
            // return back();
        }
    }
    private function calculateGst(float $amount, float $gstPercent, string $status = 'included'): array
    {
        if ($status === 'included') {
            $subtotal = round(($amount * 100) / (100 + $gstPercent), 2);
            $gstAmount = round($amount - $subtotal, 2);
            $total = round($amount, 2);
        } else {
            $subtotal = round($amount, 2);
            $gstAmount = round(($amount * $gstPercent) / 100, 2);
            $total = round($subtotal + $gstAmount, 2);
        }

        return [
            'subtotal' => $subtotal,
            'gst'      => $gstAmount,
            'total'    => $total,
        ];
    }
}
