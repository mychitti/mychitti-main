<?php

namespace App\Imports;

use App\Models\LabTest;
use App\Models\LabTestParameter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * The lab test catalog, loaded from a spreadsheet.
 *
 * A catalog is the one thing a new lab cannot work without and the one thing nobody wants to type
 * in: two hundred tests, each with its own parameters, units and reference ranges, all of which
 * already exist in whatever the lab was using before. So the file is the shape a lab already
 * thinks in — one row per PARAMETER, with the test's own columns repeated down its rows:
 *
 *   Test Name | Code | Department | Sample Type | Price | TAT | Active |
 *   Parameter | Unit | Normal Low | Normal High | Reference Range | Critical Low | Critical High
 *
 * Rows sharing a code (or a name, where there is no code) are one test. A test with no parameters
 * at all is a single row with the parameter columns left empty, which is exactly how a lab writes
 * down something like an X-ray fee.
 *
 * Matched on code first, then name, both within the store — so importing the same file twice
 * updates the catalog rather than doubling it, and the export → edit → import round trip works.
 */
class LabTestImport implements ToCollection
{
    /** Rows that could not be used, with the reason, for the summary shown afterwards. */
    public array $skipped = [];

    public int $created = 0;
    public int $updated = 0;
    public int $parameters = 0;

    public function __construct(protected int $storeId)
    {
    }

    public function collection(Collection $rows): void
    {
        // Grouped before anything is written: a test's parameters are only complete once every
        // row has been read, and writing test-by-test as the rows arrive would leave a half
        // imported test behind if row 40 turned out to be malformed.
        $tests = [];

        foreach ($rows as $index => $row) {
            $cells = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v)->values()->all();

            // The header, however it is spelled, plus the blank rows spreadsheets leave behind.
            if ($index === 0 && strtolower((string) ($cells[0] ?? '')) === 'test name') {
                continue;
            }
            if (blank($cells[0] ?? null) && blank($cells[1] ?? null)) {
                continue;
            }

            $name = (string) ($cells[0] ?? '');
            $code = (string) ($cells[1] ?? '');

            if (blank($name) && blank($code)) {
                continue;
            }
            if (blank($name)) {
                $this->skipped[] = 'Row ' . ($index + 1) . ': no test name.';
                continue;
            }

            $key = mb_strtolower(filled($code) ? 'c:' . $code : 'n:' . $name);

            if (!isset($tests[$key])) {
                $tests[$key] = [
                    'name'        => mb_substr($name, 0, 190),
                    'code'        => filled($code) ? mb_substr($code, 0, 60) : null,
                    'department'  => mb_substr((string) ($cells[2] ?? ''), 0, 100) ?: null,
                    'sample_type' => mb_substr((string) ($cells[3] ?? ''), 0, 100) ?: null,
                    'price'       => is_numeric($cells[4] ?? null) ? (float) $cells[4] : 0,
                    'tat_text'    => mb_substr((string) ($cells[5] ?? ''), 0, 100) ?: null,
                    // Anything but a plain no is a yes: a blank column in a file somebody prepared
                    // by hand means "I did not think about it", not "switch this test off".
                    'is_active'   => !in_array(mb_strtolower((string) ($cells[6] ?? '')), ['0', 'no', 'false', 'inactive'], true),
                    'parameters'  => [],
                ];
            }

            $parameter = (string) ($cells[7] ?? '');
            if (blank($parameter)) {
                continue;
            }

            $tests[$key]['parameters'][] = [
                'name'           => mb_substr($parameter, 0, 190),
                'unit'           => mb_substr((string) ($cells[8] ?? ''), 0, 50) ?: null,
                'normal_low'     => is_numeric($cells[9] ?? null) ? (float) $cells[9] : null,
                'normal_high'    => is_numeric($cells[10] ?? null) ? (float) $cells[10] : null,
                'ref_range_text' => mb_substr((string) ($cells[11] ?? ''), 0, 190) ?: null,
                'critical_low'   => is_numeric($cells[12] ?? null) ? (float) $cells[12] : null,
                'critical_high'  => is_numeric($cells[13] ?? null) ? (float) $cells[13] : null,
            ];
        }

        foreach ($tests as $data) {
            $this->save($data);
        }
    }

    /**
     * One test and its parameters, written as a unit.
     *
     * Parameters are replaced rather than merged. A catalog row edited in a spreadsheet is the
     * lab's statement of what that test measures now, and merging would leave a parameter somebody
     * deliberately deleted sitting in the results form forever.
     */
    protected function save(array $data): void
    {
        DB::transaction(function () use ($data) {
            $query = LabTest::where('store_id', $this->storeId);

            $existing = filled($data['code'])
                ? (clone $query)->where('code', $data['code'])->first()
                : (clone $query)->where('name', $data['name'])->first();

            $fields = [
                'store_id'    => $this->storeId,
                'name'        => $data['name'],
                'code'        => $data['code'],
                'department'  => $data['department'],
                'sample_type' => $data['sample_type'],
                'price'       => $data['price'],
                'tat_text'    => $data['tat_text'],
                'is_active'   => $data['is_active'],
            ];

            if ($existing) {
                $existing->fill($fields)->save();
                $test = $existing;
                $this->updated++;
            } else {
                $test = LabTest::create($fields);
                $this->created++;
            }

            if ($data['parameters']) {
                LabTestParameter::where('lab_test_id', $test->id)->delete();

                foreach ($data['parameters'] as $i => $parameter) {
                    LabTestParameter::create($parameter + [
                        'lab_test_id' => $test->id,
                        'sort_order'  => $i + 1,
                    ]);
                    $this->parameters++;
                }
            }
        });
    }
}
