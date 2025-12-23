<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VendorEmployee;
use App\Models\Salary;
use App\Models\Attendance;
use Carbon\Carbon;
use DB;

class GenerateMonthlySalaries extends Command
{
    protected $signature = 'salary:generate {--month=}';
    protected $description = 'Generate salaries for all vendor employees for the previous or specified month';

    public function handle()
    {
        // Use passed --month=2025-07 or default to previous month
        $monthInput = $this->option('month') ?? Carbon::now()->subMonth()->format('Y-m');
        [$year, $month] = explode('-', $monthInput);

        $this->info("Generating salaries for $monthInput...");

        VendorEmployee::chunk(100, function ($employees) use ($month, $year) {
            foreach ($employees as $empInfo) {
                try {
                    $alreadyExists = Salary::where('employee_id', $empInfo->id)
                        ->where('salary_month', "$year-$month")
                        ->exists();

                    if ($alreadyExists) continue;

                    $attendance = Attendance::where([
                        'vendor_id' => $empInfo->vendor_id,
                        'employee_type' => 'vendor_employee',
                        'employee_id' => $empInfo->id,
                        'month' => $month,
                        'year' => $year,
                    ])->get();

                    $vacation_or_leave = 0;

                    foreach ($attendance as $att) {
                        if (in_array($att->label, ['HDF', 'HDS'])) {
                            $vacation_or_leave += 0.5;
                        } elseif (!in_array($att->label, ['Sun', 'HL', 'P'])) {
                            $vacation_or_leave += 1;
                        }
                    }

                    $base_salary = $empInfo->base_salary ?? 0;
                    $unpaid_leaves = $vacation_or_leave;
                    $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);
                    $per_day_salary = $total_days_in_month ? ($base_salary / $total_days_in_month) : 0;
                    $payable_salary = $base_salary - ($per_day_salary * $unpaid_leaves);

                    $bonus = 0;
                    $deductions = 0;

                    $allowances = json_decode($empInfo->monthly_allowances, true) ?? [];
                    $total_allowance = 0;

                    foreach ($allowances as $a) {
                        $value = (float) ($a['amount'] ?? 0);
                        $per_day = $total_days_in_month ? $value / $total_days_in_month : 0;
                        $adjusted = $value - ($per_day * $unpaid_leaves);
                        $total_allowance += $adjusted;
                    }

                    $total_payable = $payable_salary + $total_allowance + $bonus - $deductions;

                    Salary::create([
                        'vendor_id' => $empInfo->vendor_id,
                        'employee_id' => $empInfo->id,
                        'base_salary' => $base_salary,
                        'payable_salary' => round($payable_salary, 2),
                        'total_payable' => round($total_payable, 2),
                        'salary_month' => "$year-$month",
                        'bonus_incentives' => $bonus,
                        'allowance_amount' => round($total_allowance, 2),
                        'allowance' => json_encode($allowances),
                        'deductions' => $deductions,
                        'created_at' => now(),
                    ]);

                    $this->line("✅ Salary created for employee ID {$empInfo->id}");
                } catch (\Exception $e) {
                    $this->error("Error for employee ID {$empInfo->id}: " . $e->getMessage());
                }
            }
        });

        $this->info("✅ All salaries processed.");
    }
}
