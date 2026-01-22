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
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;


class VendorWalletController extends Controller
{
    public function index()
    {
        return view('admin-views.vendor.wallet');
    }
    public function recharge(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|integer|min:1',
        ]);
        $store_id = $request->store_id;
        $store = Store::find($store_id);
        $wallet = $store->vendor->wallet;
        $amount = $request->amount;


        $wallet =  StoreWallet::where('vendor_id', $store_id)->first();
        if ($wallet) {
            $wallet->increment('total_earning', $amount);
            $wallet->save();
        } else {
            $store = Store::find($store_id);
            $wallet = new StoreWallet();
            $wallet->vendor_id = $store->vendor->id;
            $wallet->total_earning = $amount;
            $wallet->total_withdrawn = 0.0;
            $wallet->pending_withdraw = 0.0;
            $wallet->created_at = now();
            $wallet->updated_at = now();
            $wallet->save();
        }

        if ($request->billing) {

            //insert into transactions 
            $account_transaction = new AccountTransaction();
            $account_transaction->current_balance = $wallet->sum('total_earning') + $amount;
            $account_transaction->from_type = 'store';
            $account_transaction->amount = $amount;
            $account_transaction->from_id = $store_id;
            $account_transaction->method = 'wallet';
            $account_transaction->action = 'credit';
            $account_transaction->reason = 'Wallet Recharge';
            $account_transaction->created_by = 'admin';
            $account_transaction->save();

            $wallet_recharge_gst_percent = \App\Models\BusinessSetting::where('key', 'wallet_recharge_gst_percent')->first();
            $wallet_recharge_hsn = \App\Models\BusinessSetting::where('key', 'wallet_recharge_hsn')->first();

            // generate bill 
            $invoice = new ManualInvoice();
            $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
            $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
            $invoice->vendor_id = NULL;
            $invoice->bill_to = $store_id;
            $invoice->bill_to_type = 'vendor';
            $invoice->module_id =  $store->module_id;
            $invoice->total_amount = floor(_taxIncludedPrice($amount, $wallet_recharge_gst_percent->value, 'actual'));
            $invoice->payment_method = 'Cash';
            $invoice->tax_type =  'gst';
            $invoice->payment_status =  'Paid';
            $invoice->payment_date =  date('Y-m-d');
            $invoice->generated_by =  'admin';
            $invoice->save();

            $InvoiceItem = new InvoiceItem();
            $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
            $InvoiceItem->name = 'Wallet Recharge';
            $InvoiceItem->qty = 1;
            $InvoiceItem->price = $amount;
            $InvoiceItem->tax =  $wallet_recharge_gst_percent->value;
            $InvoiceItem->hsn =  $wallet_recharge_hsn->value;
            $InvoiceItem->save();

            // ledger entry 
            $credit_account = Helpers::ensureWalletRevenueAccount();
            $debit_account = Helpers::ensurePurchaseAccount('Wallet Recharge', $store_id);

            $data = [
                'date' => now(),
                'amount' => $invoice->total_amount,
                'voucher_type' => 'Purchase',
                'status' => 'approved',
            ];
            $voucher =  _masterLedgerEntry($data, $credit_account, $debit_account, 'store', 'admin', null);
            _saveDayBookEntry($invoice->total_amount, 'debit', $store_id, 'Wallet Recharge', $invoice->id, $voucher?->id);
            try {
                $data = _createBillPdf($invoice, 'admin');
                $invoice->update(['pdf' => $data['pdf']]);
                    return redirect($data['url']);
            } catch (\Throwable $th) {
                //
            }
        } else {
            Toastr::success('Wallet Recharged Successfully');
            return back();
        }
    }
}
