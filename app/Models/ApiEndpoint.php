<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'project_id', 'folder', 'name', 'method', 'endpoint', 'auth_type', 'description',
        'params', 'headers', 'request_body', 'response_sample', 'status_code',
        'usage_note', 'images', 'sort_order',
    ];

    const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    public function project()
    {
        return $this->belongsTo(ApiProject::class, 'project_id');
    }

    /* params / headers / images are JSON arrays. They are read far more often than written, so
       the decoding is centralised here rather than repeated in the views. */

    public function getParamListAttribute(): array
    {
        return self::decodeRows($this->params);
    }

    public function getHeaderListAttribute(): array
    {
        return self::decodeRows($this->headers);
    }

    public function getImageListAttribute(): array
    {
        $decoded = json_decode((string) $this->images, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * Repeatable key/value rows posted by the form, dropped where the key is blank.
     */
    public static function encodeRows($keys, $values, $notes = null): ?string
    {
        $rows = [];
        foreach ((array) $keys as $i => $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'key'   => $key,
                'value' => trim((string) (((array) $values)[$i] ?? '')),
                'note'  => trim((string) (((array) $notes)[$i] ?? '')),
            ];
        }
        return $rows ? json_encode($rows) : null;
    }

    /**
     * Accepts either the JSON this model writes or a plain "key = value" block, which is what a
     * hand-edited Excel sheet and the pre-JSON rows contain.
     */
    public static function decodeRows($raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $rows = [];
            foreach ($decoded as $row) {
                if (is_array($row) && isset($row['key'])) {
                    $rows[] = ['key' => $row['key'], 'value' => $row['value'] ?? '', 'note' => $row['note'] ?? ''];
                }
            }
            return $rows;
        }

        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode('=', $line, 2);
            $rows[] = ['key' => trim($parts[0]), 'value' => trim($parts[1] ?? ''), 'note' => ''];
        }
        return $rows;
    }

    /**
     * The "key = value" rendering used by the Excel export.
     */
    public static function rowsToText($raw): string
    {
        return implode("\n", array_map(
            fn($r) => $r['key'] . ' = ' . $r['value'] . ($r['note'] !== '' ? '   # ' . $r['note'] : ''),
            self::decodeRows($raw)
        ));
    }
}
