<?php

namespace App\Imports;

use App\Models\LabOrder;
use App\Models\LabOrderResult;
use App\Services\LabResults;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Results, as an analyser or an outsourced lab hands them over.
 *
 *   Sample ID | Parameter | Value
 *
 * Every value is judged on the way in by exactly the rule the result-entry screen uses — the same
 * LabResults::evaluate — so a potassium of 7.2 raises the same critical flag whether a technician
 * typed it or a machine sent it. That is the entire reason this importer does not do its own
 * arithmetic.
 *
 * Two things it deliberately refuses:
 *
 *  - It never VERIFIES. An order with imported values lands on 'resulted', which means the numbers
 *    are in and nobody has checked them. Verification is a person putting their name to a report,
 *    and a spreadsheet cannot do that.
 *  - It never invents parameters. A row naming something the ordered tests do not measure is
 *    skipped and listed, rather than appended as a free-floating line on the report.
 */
class LabResultImport implements ToCollection
{
    public array $skipped = [];
    public int $updated = 0;
    public int $critical = 0;

    /** @var array<int,int> order id => values written, for the summary and the status sweep */
    public array $touched = [];

    public function __construct(protected int $storeId)
    {
    }

    public function collection(Collection $rows): void
    {
        $orders = [];

        foreach ($rows as $index => $row) {
            $cells = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v)->values()->all();
            $line  = $index + 1;

            if ($index === 0 && strtolower((string) ($cells[0] ?? '')) === 'sample id') {
                continue;
            }
            if (blank($cells[0] ?? null) && blank($cells[1] ?? null)) {
                continue;
            }

            $sample    = (string) ($cells[0] ?? '');
            $parameter = (string) ($cells[1] ?? '');
            $value     = (string) ($cells[2] ?? '');

            if (blank($sample) || blank($parameter)) {
                $this->skipped[] = "Row {$line}: needs both a sample id and a parameter.";
                continue;
            }
            if (blank($value)) {
                // A blank value is not an error and not a result — the machine had nothing for
                // that line. Passing it through would wipe a value somebody had already typed.
                continue;
            }

            if (!array_key_exists($sample, $orders)) {
                $order = LabOrder::where('store_id', $this->storeId)
                    ->where('order_no', $sample)
                    ->with('items')
                    ->first();

                // Rows are seeded on the way in, so results can be imported for an order nobody
                // has opened on the result-entry screen yet — which is the normal case for a batch
                // coming back from a machine.
                if ($order) {
                    LabResults::materialise($order);
                }

                $orders[$sample] = $order;
            }

            $order = $orders[$sample];
            if (!$order) {
                $this->skipped[] = "Row {$line}: no sample {$sample} in this lab.";
                continue;
            }

            if (in_array($order->status, ['verified', 'sent'], true)) {
                $this->skipped[] = "Row {$line}: {$sample} is already reported — reopen it before importing values.";
                continue;
            }

            $result = LabOrderResult::where('lab_order_id', $order->id)
                ->whereRaw('LOWER(parameter_name) = ?', [mb_strtolower($parameter)])
                ->first();

            if (!$result) {
                $this->skipped[] = "Row {$line}: {$sample} has no parameter called \"{$parameter}\".";
                continue;
            }

            LabResults::apply($result, $value);

            $this->updated++;
            $this->touched[$order->id] = ($this->touched[$order->id] ?? 0) + 1;
            if ($result->is_critical) {
                $this->critical++;
            }
        }

        // Moved on only as far as the values justify: 'resulted' says the numbers are in. A human
        // still has to verify the report before it can reach a patient.
        foreach (array_keys($this->touched) as $orderId) {
            $order = LabOrder::find($orderId);
            if ($order && in_array($order->status, ['ordered', 'in_progress'], true)) {
                $order->status = 'resulted';
                if (!$order->collected_at) {
                    $order->collected_at = now();
                }
                $order->save();
                $order->items()->update(['status' => 'completed']);
            }
        }
    }
}
