<?php

namespace App\Console\Commands;

use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bridges the POS dashboard's sales figure to the Profit & Loss report's revenue, over the same
 * window, and names every rupee of the difference.
 *
 * The two read different tables — the dashboard sums manual_invoices.total_amount, the P&L sums the
 * sale-order mirror in inventory_order_details — so they are not expected to be identical. What
 * matters is that every gap is accounted for: tax, round-off, discounts, bills sold outside the POS
 * and rows the mirror never got. Anything left over is a real defect.
 *
 *   php artisan inventory:reconcile-pnl --store=12
 *   php artisan inventory:reconcile-pnl --store=12 --days=14
 *   php artisan inventory:reconcile-pnl --store=12 --from=2026-07-04 --to=2026-08-03
 */
class ReconcilePnl extends Command
{
    protected $signature = 'inventory:reconcile-pnl
        {--store= : Store id}
        {--days=30 : Window ending now}
        {--from= : Start date, overrides --days}
        {--to= : End date, overrides --days}';

    protected $description = 'Explain the difference between POS dashboard sales and P&L revenue';

    public function handle(): int
    {
        Helpers::_ensureInvOrderCostColumns();

        $storeId = (int) $this->option('store');
        if (!$storeId) {
            $this->error('--store is required.');
            return self::FAILURE;
        }

        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : Carbon::now()->subDays((int) $this->option('days'));
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : Carbon::now();

        $this->info('Store ' . $storeId . '  ' . $from->toDateTimeString() . ' → ' . $to->toDateTimeString());
        $this->newLine();

        // ── POS side ──────────────────────────────────────────────────────────
        $pos = DB::table('manual_invoices')
            ->where('vendor_id', $storeId)->where('type', 'manual')->where('pos_status', 'final')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) bills, COALESCE(SUM(total_amount),0) sales, '
                . 'COALESCE(SUM(discount_amount),0) disc, COALESCE(SUM(final_tax),0) tax, '
                . 'COALESCE(SUM(round_off),0) roff')
            ->first();

        $posLineValue = (float) DB::table('invoice_items as ii')
            ->join('manual_invoices as mi', 'mi.id', '=', 'ii.manual_invoice_id')
            ->where('mi.vendor_id', $storeId)->where('mi.type', 'manual')->where('mi.pos_status', 'final')
            ->whereBetween('mi.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(ii.price * ii.qty),0) v')->value('v');

        $this->line('POS DASHBOARD');
        $this->row('Sales (total_amount)', (float) $pos->sales, $pos->bills . ' bills');
        $this->row('Line value (price x qty)', $posLineValue);
        $this->row('  less bill discounts', -(float) $pos->disc);
        $this->row('  plus tax / round-off', (float) $pos->sales - $posLineValue + (float) $pos->disc);
        $this->newLine();

        // ── Sale-order mirror ─────────────────────────────────────────────────
        $mirror = fn() => DB::table('inventory_order_details as d')
            ->join('inventory_orders as o', 'o.order_id', '=', 'd.order_id')
            ->leftJoin('manual_invoices as mi', function ($j) {
                $j->on('mi.invoice_id', '=', 'o.invoice_id')->on('mi.vendor_id', '=', 'o.store_id');
            })
            ->where('o.store_id', $storeId)
            ->whereBetween('d.created_at', [$from, $to]);

        $gross = 'COALESCE(SUM(d.qty * d.unit_price),0)';
        $disc  = 'COALESCE(SUM(COALESCE(d.line_discount,0)),0)';

        $posMirror = (clone $mirror())->where('mi.pos_status', 'final')
            ->selectRaw("{$gross} g, {$disc} dsc, COUNT(*) n, "
                . 'SUM(CASE WHEN d.line_discount IS NULL THEN 1 ELSE 0 END) no_disc')->first();

        $voidMirror = (clone $mirror())->where('mi.pos_status', 'void')
            ->selectRaw("{$gross} g, COUNT(*) n")->first();

        $otherMirror = (clone $mirror())->whereNull('mi.pos_status')
            ->selectRaw("{$gross} g, {$disc} dsc, COUNT(*) n")->first();

        // Rows the report still cannot see: the item row itself is gone, so the inner join on
        // inventory_items drops them and their cost cannot be worked out either.
        $orphan = (clone $mirror())->leftJoin('inventory_items as i', 'i.id', '=', 'd.item_id')
            ->whereNull('i.id')->selectRaw("{$gross} g, COUNT(*) n")->first();

        $this->line('SALE-ORDER MIRROR (inventory_order_details)');
        $this->row('From POS bills, gross', (float) $posMirror->g, $posMirror->n . ' lines');
        $this->row('  discount recorded', -(float) $posMirror->dsc,
            $posMirror->no_disc . ' line(s) with no discount share yet');
        $this->row('From non-POS invoices, net', (float) $otherMirror->g - (float) $otherMirror->dsc,
            $otherMirror->n . ' lines');
        $this->row('On voided bills (excluded)', (float) $voidMirror->g, $voidMirror->n . ' lines');
        $this->row('Orphaned item (dropped)', (float) $orphan->g, $orphan->n . ' lines');
        $this->newLine();

        // ── The bridge ────────────────────────────────────────────────────────
        $plRevenue = (float) $posMirror->g - (float) $posMirror->dsc
            + (float) $otherMirror->g - (float) $otherMirror->dsc
            - (float) $orphan->g;

        $missingMirror = $posLineValue - (float) $posMirror->g;

        $this->line('BRIDGE');
        $this->row('POS dashboard sales', (float) $pos->sales);
        $this->row('less tax / round-off', -((float) $pos->sales - $posLineValue + (float) $pos->disc));
        $this->row('add back bill discounts', (float) $pos->disc);
        $this->row('less discount recorded on lines', -(float) $posMirror->dsc);
        $this->row('less POS lines with no mirror row', -$missingMirror);
        $this->row('add non-POS inventory sales', (float) $otherMirror->g - (float) $otherMirror->dsc);
        $this->row('less orphaned-item lines', -(float) $orphan->g);
        $this->line(str_repeat('-', 62));
        $this->row('= P&L total revenue', $plRevenue);
        $this->newLine();

        if ($posMirror->no_disc > 0) {
            $this->warn($posMirror->no_disc . ' POS line(s) carry no discount share — run '
                . 'inventory:backfill-line-discount to net the historical bill discounts off revenue.');
        }
        if (abs($missingMirror) > 1) {
            $this->warn('POS line value and mirrored line value differ by ' . round($missingMirror, 2)
                . ' — some finalised bills never produced a sale order.');
        }

        return self::SUCCESS;
    }

    private function row(string $label, float $amount, ?string $note = null): void
    {
        $this->line(str_pad($label, 40) . str_pad(number_format($amount, 2), 16, ' ', STR_PAD_LEFT)
            . ($note ? '  ' . $note : ''));
    }
}
