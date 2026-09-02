<?php

namespace App\Services;

use App\Models\LabOrder;
use App\Models\LabOrderResult;
use App\Models\LabTestParameter;

/**
 * The two rules that decide what a lab result means, in one place.
 *
 * Both were private methods on LabController, which was fine while a technician typing into the
 * result-entry form was the only way a value could arrive. It no longer is — an analyser's CSV
 * comes in through an importer — and a second copy of "what counts as high" is how a value typed
 * by hand ends up flagged differently from the same value imported from a machine.
 */
class LabResults
{
    /**
     * Give an order the result rows its tests call for.
     *
     * Reads the parameters off the catalog at the moment work starts rather than when the order was
     * placed: the reference range printed on the report should be the one the lab is working to
     * today. Never touches an item that already has rows — those may already hold values.
     */
    public static function materialise(LabOrder $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (LabOrderResult::where('lab_order_item_id', $item->id)->exists()) {
                continue;
            }

            $params = LabTestParameter::where('lab_test_id', $item->lab_test_id)
                ->orderBy('sort_order')
                ->get();

            foreach ($params as $p) {
                LabOrderResult::create([
                    'lab_order_id'      => $order->id,
                    'lab_order_item_id' => $item->id,
                    'parameter_name'    => $p->name,
                    'unit'              => $p->unit,
                    'normal_low'        => $p->normal_low,
                    'normal_high'       => $p->normal_high,
                    'ref_range_text'    => $p->ref_range_text,
                    'critical_low'      => $p->critical_low,
                    'critical_high'     => $p->critical_high,
                    'sort_order'        => $p->sort_order,
                ]);
            }
        }
    }

    /**
     * A value against its own reference range: [flag, isCritical].
     *
     * Non-numeric results — "Negative", "Trace", a culture's growth — get no flag at all rather
     * than a false normal. A range cannot judge a word, and pretending otherwise would put a
     * reassuring N beside a result nobody has actually read.
     */
    public static function evaluate($value, LabOrderResult $res): array
    {
        if (!is_numeric($value)) {
            return [null, false];
        }

        $v = (float) $value;

        $flag = 'N';
        if ($res->normal_low !== null && $v < (float) $res->normal_low) {
            $flag = 'L';
        } elseif ($res->normal_high !== null && $v > (float) $res->normal_high) {
            $flag = 'H';
        }

        $critical = false;
        if ($res->critical_low !== null && $v < (float) $res->critical_low) {
            $critical = true;
        }
        if ($res->critical_high !== null && $v > (float) $res->critical_high) {
            $critical = true;
        }

        return [$flag, $critical];
    }

    /** Write one value onto a result row, flag and all. Blank clears the row rather than zeroing it. */
    public static function apply(LabOrderResult $res, $value): void
    {
        $value = trim((string) $value);

        [$flag, $critical] = static::evaluate($value, $res);

        $res->result_value = $value === '' ? null : $value;
        $res->result_flag  = $value === '' ? null : $flag;
        $res->is_critical  = $value === '' ? 0 : $critical;
        $res->save();
    }
}
