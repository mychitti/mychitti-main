<?php

namespace App\Imports;

use App\Models\ApiEndpoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Loads endpoint rows into one API project, from the sheet ApiEndpointExport writes.
 */
class ApiEndpointImport implements ToCollection, WithHeadingRow
{
    protected $projectId;
    protected $replace;

    public int $imported = 0;

    public function __construct($projectId, bool $replace = false)
    {
        $this->projectId = $projectId;
        $this->replace = $replace;
    }

    public function collection(Collection $rows)
    {
        if ($this->replace) {
            ApiEndpoint::where('project_id', $this->projectId)->delete();
        }

        $sort = (int) ApiEndpoint::where('project_id', $this->projectId)->max('sort_order');

        foreach ($rows as $row) {
            $endpoint = trim((string) ($row['endpoint'] ?? ''));
            if ($endpoint === '') {
                continue;
            }

            $method = Str::upper(trim((string) ($row['method'] ?? 'GET')));
            if (!in_array($method, ApiEndpoint::METHODS, true)) {
                $method = 'GET';
            }

            ApiEndpoint::create([
                'project_id'      => $this->projectId,
                'folder'          => $this->text($row['folder'] ?? null),
                'name'            => $this->text($row['name'] ?? null),
                'method'          => $method,
                'endpoint'        => Str::limit($endpoint, 500, ''),
                'auth_type'       => $this->text($row['auth'] ?? ($row['auth_type'] ?? null)),
                'description'     => $this->text($row['description'] ?? null),
                'params'          => $this->rows($row['params'] ?? null),
                'headers'         => $this->rows($row['headers'] ?? null),
                'request_body'    => $this->text($row['request_body'] ?? null),
                'response_sample' => $this->text($row['response_sample'] ?? null),
                'status_code'     => $this->text($row['status_code'] ?? null),
                'usage_note'      => $this->text($row['usage_note'] ?? ($row['note'] ?? null)),
                'sort_order'      => ++$sort,
            ]);
            $this->imported++;
        }
    }

    private function text($value): ?string
    {
        return trim((string) $value) ?: null;
    }

    /**
     * The sheet holds "key = value   # note" lines; decodeRows understands that and JSON alike.
     */
    private function rows($value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $parsed = [];
        foreach (ApiEndpoint::decodeRows($raw) as $row) {
            $value = $row['value'];
            $note = $row['note'];
            if ($note === '' && str_contains($value, '#')) {
                [$value, $note] = array_map('trim', explode('#', $value, 2));
            }
            $parsed[] = ['key' => $row['key'], 'value' => $value, 'note' => $note];
        }

        return $parsed ? json_encode($parsed) : null;
    }
}
