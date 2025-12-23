<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DayBook;
use App\Models\StoreBankAccount;
use App\Models\StoreBankTransaction;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;


class BankReconciliationController extends Controller
{
  public function index(Request $request)
  {
    $preset = request('date_range') ?? 'today';
    $custom = request('custom_date_range') ?? null;
    $range = Helpers::calculatePresetDates($preset, $custom);
    $formatted_from  = $range['start'];
    $formatted_to = $range['end'];
    $from = $range['start']->toDateString();
    $to  = $range['end']->toDateString();

    $daybook_entries = DayBook::where('store_id', Helpers::get_store_id())->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
    $bank_entries = StoreBankTransaction::with('bankAccount')->where('store_id', Helpers::get_store_id())->whereBetween('value_date', [$formatted_from, $formatted_to])->get();

    $tolerance = 0.5;

    $bankSumsByBillType = $bank_entries
      ->groupBy(fn($b) => ($b->bill_number ?? '') . '|' . ($b->type ?? ''))
      ->map(fn($group) => $group->sum('amount'));

    $bankByTxn = $bank_entries
      ->keyBy(fn($b) => $b->reference_number ?? '');

    $daybookSumsByInvType = $daybook_entries
      ->groupBy(fn($d) => (optional($d->invoice)->invoice_id ?? '') . '|' . ($d->type ?? ''))
      ->map(fn($group) => $group->sum('amount'));

    foreach ($daybook_entries as $entry) {
      $invoiceId = optional($entry->invoice)->invoice_id;
      $invoiceKey = ($invoiceId ?? '') . '|' . ($entry->type ?? '');
      $invoiceSum = $daybookSumsByInvType[$invoiceKey] ?? 0;

      $refs = is_array($entry->reference_number)
        ? $entry->reference_number
        : (json_decode($entry->reference_number, true) ?: []);

      $txnSum = 0;
      foreach ($refs as $txnId) {
        $txnSum += (float) ($bankByTxn[$txnId]->amount ?? 0);
      }

      $bankInvoiceSum = (float) ($bankSumsByBillType[$invoiceKey] ?? 0);

      $txnMatch = $txnSum > 0 && abs($txnSum - $invoiceSum) <= $tolerance;
      $invoiceMatch = $bankInvoiceSum > 0 && abs($bankInvoiceSum - $invoiceSum) <= $tolerance;

      if ($txnMatch || $invoiceMatch) {
        $entry->mismatch = false;
        $entry->mismatch_desc = $txnMatch ? 'matched_txn' : 'matched_invoice';
      } else {
        $entry->mismatch = true;
        $entry->mismatch_desc = 'mismatch';
      }
    }
    $txnToGroup = [];
    foreach ($daybook_entries as $entry) {
      $ref = $entry->reference_number;
      if (is_string($ref)) {
        $ref = @unserialize($ref);
      }
      if (!is_array($ref)) {
        $ref = [$ref];
      }

      $groupKey = optional($entry->invoice)->invoice_id ?: implode(',', $ref);
      foreach ($ref as $txn) {
        if ($txn) {
          $txnToGroup[$txn] = $groupKey;
        }
      }
    }

    $groupedBankEntries = $bank_entries->groupBy(function ($b) use ($txnToGroup) {
      return $b->bill_number
        ?: ($txnToGroup[$b->reference_number] ?? $b->reference_number);
    });
// prx($groupedBankEntries);
    foreach ($groupedBankEntries as $key => $bankGroup) {
      $firstBank = $bankGroup->first();

      $groupTxnIds = $bankGroup->pluck('reference_number')->toArray();

      $relatedDaybookEntries = $daybook_entries->filter(function ($entry) use ($firstBank, $groupTxnIds) {
        $ref = $entry->reference_number;
        if (is_string($ref)) {
          $ref = @unserialize($ref);
        }
        if (!is_array($ref)) {
          $ref = [$ref];
        }

        return (
          count(array_intersect($groupTxnIds, $ref)) > 0 ||
          optional($entry->invoice)->invoice_id == $firstBank->bill_number
        ) && $entry->type == $firstBank->type;
      });

      $bankSum = $bankGroup->sum('amount');
      $daybookSum = $relatedDaybookEntries->sum('amount');
      $diff = abs($bankSum - $daybookSum);
      $tolerance = 0.5;

      foreach ($bankGroup as $bank) {
        if ($diff <= $tolerance) {
          $bank->mismatch = false;
          $bank->mismatch_desc = "matched_group_sum";
        } else {
          $bank->mismatch = true;
          $bank->mismatch_desc = number_format($bankSum - $daybookSum, 2);
        }
      }
    }

    return view('vendor-views.account.bank-reconciliation.index', compact('from', 'to', 'daybook_entries', 'preset', 'bank_entries'));
  }
}
