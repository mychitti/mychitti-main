<?php

namespace App\Modules\Laundry;

use App\CentralLogics\Helpers as AppHelpers;
use App\Models\LaundryOrder;

class LaundryHelpers
{
    public static function laundry_calendar(): array
    {
        $storeId = AppHelpers::get_store_id();

        $orders = LaundryOrder::where('store_id', $storeId)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('YEAR(drop_date) as year, MONTH(drop_date) as month, DAY(drop_date) as day, SUM(total_amount) as amount, COUNT(id) as tokens')
            ->groupBy('year', 'month', 'day')
            ->orderBy('year')->orderBy('month')->orderBy('day')
            ->get();

        $structured     = [];
        $monthlyAmounts = [];

        foreach ($orders as $row) {
            $structured[$row->year][$row->month][$row->day] = [
                'amount'   => (float) $row->amount,
                'tokens'   => (int) $row->tokens,
                'category' => 'low',
            ];
            $monthlyAmounts[$row->year][$row->month][] = (float) $row->amount;
        }

        foreach ($structured as $year => $months) {
            foreach ($months as $month => $days) {
                $amounts = $monthlyAmounts[$year][$month] ?? [0];
                $max     = max($amounts);
                $avg     = array_sum($amounts) / count($amounts);
                foreach ($days as $day => $data) {
                    $amt = $data['amount'];
                    if ($max > 0) {
                        $structured[$year][$month][$day]['category'] = $amt >= $avg * 1.5 ? 'high' : ($amt >= $avg * 0.5 ? 'medium' : 'low');
                    }
                }
            }
        }

        return $structured;
    }
}
