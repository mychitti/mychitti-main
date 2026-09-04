<?php

namespace App\Services;

/**
 * Flattens a Postman collection export into endpoint rows for an API project.
 *
 * Handles both v2.0 and v2.1 schemas — they differ only in details this parser does not read.
 * Folders nest arbitrarily deep, so items are walked recursively and the folder path is kept
 * as a breadcrumb ("Auth / Login") so the endpoint table can group by it.
 */
class PostmanCollectionParser
{
    /**
     * @return array{name: string, endpoints: array<int, array<string, mixed>>}
     */
    public static function parse(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['item']) || !is_array($data['item'])) {
            throw new \RuntimeException('This does not look like a Postman collection export (no "item" list found).');
        }

        $name = $data['info']['name'] ?? 'Postman Collection';
        $variables = self::variableMap($data['variable'] ?? []);

        $endpoints = [];
        self::walk($data['item'], '', $variables, $endpoints);

        return ['name' => $name, 'endpoints' => $endpoints];
    }

    private static function variableMap($vars): array
    {
        $map = [];
        foreach ((array) $vars as $v) {
            if (isset($v['key'])) {
                $map['{{' . $v['key'] . '}}'] = (string) ($v['value'] ?? '');
            }
        }
        return $map;
    }

    private static function walk(array $items, string $folder, array $vars, array &$out): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // A node with its own "item" list is a folder, not a request.
            if (isset($item['item']) && is_array($item['item'])) {
                $child = trim($item['name'] ?? '');
                $path  = $folder === '' ? $child : $folder . ' / ' . $child;
                self::walk($item['item'], $path, $vars, $out);
                continue;
            }

            if (!isset($item['request'])) {
                continue;
            }

            $request = is_array($item['request']) ? $item['request'] : [];
            $out[] = [
                'folder'          => $folder ?: null,
                'name'            => trim((string) ($item['name'] ?? '')) ?: null,
                'method'          => strtoupper($request['method'] ?? 'GET'),
                'endpoint'        => self::url($request['url'] ?? null, $vars),
                'auth_type'       => self::auth($request),
                'description'     => self::description($item, $request),
                'params'          => self::params($request['url'] ?? null),
                'headers'         => self::headers($request['header'] ?? null),
                'request_body'    => self::body($request['body'] ?? null),
                'response_sample' => self::response($item['response'] ?? null),
                'status_code'     => self::statusCode($item['response'] ?? null),
            ];
        }
    }

    private static function url($url, array $vars): string
    {
        if (is_string($url)) {
            return strtr($url, $vars);
        }
        if (is_array($url)) {
            if (!empty($url['raw'])) {
                return strtr((string) $url['raw'], $vars);
            }
            $host = is_array($url['host'] ?? null) ? implode('.', $url['host']) : (string) ($url['host'] ?? '');
            $path = is_array($url['path'] ?? null) ? implode('/', $url['path']) : (string) ($url['path'] ?? '');
            return strtr(trim($host . '/' . $path, '/'), $vars);
        }
        return '';
    }

    private static function auth(array $request): ?string
    {
        $type = $request['auth']['type'] ?? null;
        if ($type) {
            return ucfirst((string) $type);
        }
        foreach ((array) ($request['header'] ?? []) as $header) {
            if (strcasecmp((string) ($header['key'] ?? ''), 'Authorization') === 0) {
                $value = (string) ($header['value'] ?? '');
                return str_starts_with($value, 'Bearer') ? 'Bearer' : 'Header';
            }
        }
        return null;
    }

    private static function description(array $item, array $request): ?string
    {
        $desc = $request['description'] ?? $item['description'] ?? null;
        if (is_array($desc)) {
            $desc = $desc['content'] ?? null;
        }
        $desc = trim((string) $desc);
        $name = trim((string) ($item['name'] ?? ''));

        if ($desc === '') {
            return $name ?: null;
        }
        return $name !== '' ? $name . "\n\n" . $desc : $desc;
    }

    private static function params($url): ?string
    {
        if (!is_array($url)) {
            return null;
        }
        $rows = [];
        foreach ((array) ($url['query'] ?? []) as $q) {
            if (!is_array($q) || ($q['disabled'] ?? false) === true) {
                continue;
            }
            $rows[] = [
                'key'   => (string) ($q['key'] ?? ''),
                'value' => (string) ($q['value'] ?? ''),
                'note'  => trim((string) ($q['description'] ?? '')) ?: 'query',
            ];
        }
        // Path placeholders are documented next to query params, told apart by their note.
        foreach ((array) ($url['variable'] ?? []) as $v) {
            if (!is_array($v)) {
                continue;
            }
            $rows[] = [
                'key'   => ':' . (string) ($v['key'] ?? ''),
                'value' => (string) ($v['value'] ?? ''),
                'note'  => trim((string) ($v['description'] ?? '')) ?: 'path',
            ];
        }
        return $rows ? json_encode($rows) : null;
    }

    private static function headers($headers): ?string
    {
        $rows = [];
        foreach ((array) $headers as $h) {
            if (!is_array($h) || ($h['disabled'] ?? false) === true) {
                continue;
            }
            $key = trim((string) ($h['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'key'   => $key,
                'value' => (string) ($h['value'] ?? ''),
                'note'  => trim((string) ($h['description'] ?? '')),
            ];
        }
        return $rows ? json_encode($rows) : null;
    }

    private static function body($body): ?string
    {
        if (!is_array($body)) {
            return null;
        }
        $mode = $body['mode'] ?? null;

        if ($mode === 'raw') {
            return self::trim((string) ($body['raw'] ?? '')) ?: null;
        }
        if (in_array($mode, ['formdata', 'urlencoded'], true)) {
            $lines = [];
            foreach ((array) ($body[$mode] ?? []) as $f) {
                if (($f['disabled'] ?? false) === true) {
                    continue;
                }
                $lines[] = ($f['key'] ?? '') . ' = ' . ($f['value'] ?? ($f['src'] ?? ''));
            }
            return $lines ? implode("\n", $lines) : null;
        }
        return null;
    }

    private static function response($responses): ?string
    {
        if (!is_array($responses) || empty($responses[0])) {
            return null;
        }
        return self::trim((string) ($responses[0]['body'] ?? '')) ?: null;
    }

    private static function statusCode($responses): ?string
    {
        if (!is_array($responses) || empty($responses[0]['code'])) {
            return null;
        }
        return (string) $responses[0]['code'];
    }

    /**
     * Sample bodies in a big collection are the bulk of the payload; a few KB each is plenty
     * to document the shape and keeps a 40 MB export from becoming a 40 MB set of rows.
     */
    private static function trim(string $value): string
    {
        $value = trim($value);
        return strlen($value) > 8000 ? substr($value, 0, 8000) . "\n… (truncated)" : $value;
    }
}
