<?php

namespace App\Imports;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * A batch of lab orders, raised from a spreadsheet.
 *
 * The case this exists for is the one nobody can do by hand: a health camp or a corporate checkup
 * arrives as a list of two hundred people against the same two or three tests. Ordering those one
 * at a time through the form is a day's work, and the list already exists as a file.
 *
 *   Patient UID | Test Code(s) | Department | Priority | Referred By | Clinical Notes
 *
 * Each row becomes one order carrying however many tests are named in it, with its own sample id,
 * landing in the worklist as Pending exactly like an order placed at the counter.
 *
 * What it will NOT do is create patients. A lab sheet has a name and maybe a phone number on it,
 * which is not enough to open a medical record against — and a patient invented by an import is a
 * duplicate somebody has to merge later, with results already attached to the wrong one. Rows
 * whose UID is not on file are skipped and listed.
 */
class LabOrderImport implements ToCollection
{
    public array $skipped = [];
    public int $created = 0;
    public int $tests = 0;

    /** @var array<int> ids of the orders raised, so the caller can report or link to them */
    public array $orderIds = [];

    public function __construct(
        protected int $storeId,
        protected ?int $actorId = null,
        protected ?string $actorType = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        // The catalog, read once and matched on either code or name — a lab writing its own
        // camp list will use whichever of the two it thinks in.
        $catalog = LabTest::where('store_id', $this->storeId)->get();
        $byCode  = $catalog->filter(fn($t) => filled($t->code))->keyBy(fn($t) => mb_strtolower($t->code));
        $byName  = $catalog->keyBy(fn($t) => mb_strtolower($t->name));

        foreach ($rows as $index => $row) {
            $cells = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v)->values()->all();
            $line  = $index + 1;

            if ($index === 0 && strtolower((string) ($cells[0] ?? '')) === 'patient uid') {
                continue;
            }
            if (blank($cells[0] ?? null) && blank($cells[1] ?? null)) {
                continue;
            }

            $uid = (string) ($cells[0] ?? '');
            if (blank($uid)) {
                $this->skipped[] = "Row {$line}: no patient UID.";
                continue;
            }

            $patient = Patient::where('store_id', $this->storeId)->where('patient_uid', $uid)->first();
            if (!$patient) {
                $this->skipped[] = "Row {$line}: no patient with UID {$uid} — register them first.";
                continue;
            }

            $wanted = collect(preg_split('/\s*,\s*/', (string) ($cells[1] ?? '')))->filter();
            if ($wanted->isEmpty()) {
                $this->skipped[] = "Row {$line}: no tests named for {$uid}.";
                continue;
            }

            $selected = $wanted
                ->map(fn($t) => $byCode[mb_strtolower($t)] ?? $byName[mb_strtolower($t)] ?? null)
                ->filter()
                ->unique('id')
                ->values();

            $missing = $wanted->reject(fn($t) => isset($byCode[mb_strtolower($t)]) || isset($byName[mb_strtolower($t)]));
            if ($selected->isEmpty()) {
                $this->skipped[] = "Row {$line}: none of these tests are in your catalog — " . $missing->implode(', ') . '.';
                continue;
            }
            if ($missing->isNotEmpty()) {
                // Partial rather than nothing: the tests that exist are ordered, and the ones that
                // do not are named, so a typo costs a line in the summary rather than the batch.
                $this->skipped[] = "Row {$line}: ordered without " . $missing->implode(', ') . " — not in the catalog.";
            }

            $this->raise($patient, $selected, $cells);
        }
    }

    /** One order, built exactly as the order form builds one. */
    protected function raise(Patient $patient, Collection $selected, array $cells): void
    {
        DB::transaction(function () use ($patient, $selected, $cells) {
            $samples = $selected->pluck('sample_type')
                ->flatMap(fn($s) => preg_split('/\s*,\s*/', (string) $s))
                ->map(fn($s) => trim($s))
                ->filter()
                ->unique(fn($s) => mb_strtolower($s))
                ->values();

            $priority = mb_strtolower((string) ($cells[3] ?? '')) ?: 'routine';

            $order = LabOrder::create([
                'store_id'        => $this->storeId,
                'patient_id'      => $patient->id,
                'source'          => 'walkin',
                'department'      => mb_substr((string) ($cells[2] ?? ''), 0, 60) ?: 'OPD',
                'priority'        => in_array($priority, ['routine', 'urgent', 'stat'], true) ? $priority : 'routine',
                'status'          => 'ordered',
                'sample_type'     => $samples->isEmpty() ? null : mb_substr($samples->implode(', '), 0, 255),
                'clinical_notes'  => mb_substr((string) ($cells[5] ?? ''), 0, 500) ?: null,
                'referred_by'     => mb_substr((string) ($cells[4] ?? ''), 0, 190) ?: null,
                'total_amount'    => $selected->sum('price'),
                'created_by'      => $this->actorId,
                'created_by_type' => $this->actorType,
            ]);

            $order->order_no = 'LAB-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $order->save();

            foreach ($selected as $test) {
                LabOrderItem::create([
                    'lab_order_id' => $order->id,
                    'lab_test_id'  => $test->id,
                    'test_name'    => $test->name,
                    'department'   => $test->department,
                    'price'        => $test->price,
                    'status'       => 'pending',
                ]);
                $this->tests++;
            }

            $this->created++;
            $this->orderIds[] = (int) $order->id;
        });
    }
}
