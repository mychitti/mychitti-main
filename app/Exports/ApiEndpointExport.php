<?php

namespace App\Exports;

use App\Models\ApiEndpoint;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApiEndpointExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $endpoints;

    public function __construct($endpoints)
    {
        $this->endpoints = $endpoints;
    }

    public function collection()
    {
        return collect($this->endpoints)->map(function ($e) {
            return [
                'folder'          => $e->folder,
                'name'            => $e->name,
                'method'          => $e->method,
                'endpoint'        => $e->endpoint,
                'auth'            => $e->auth_type,
                'description'     => $e->description,
                // Params and headers travel as a readable "key = value" block so the sheet stays
                // hand-editable; the importer reads that back into the stored JSON rows.
                'params'          => ApiEndpoint::rowsToText($e->params),
                'headers'         => ApiEndpoint::rowsToText($e->headers),
                'request_body'    => $e->request_body,
                'response_sample' => $e->response_sample,
                'status_code'     => $e->status_code,
                'usage_note'      => $e->usage_note,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Folder', 'Name', 'Method', 'Endpoint', 'Auth', 'Description',
            'Params', 'Headers', 'Request Body', 'Response Sample', 'Status Code', 'Usage Note',
        ];
    }
}
