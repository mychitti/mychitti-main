<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerListExport implements FromView, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.customer-list', [
            'data' => $this->data,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color'    => ['rgb' => '005D5F'],
        ]);
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setRGB('FFFFFF');
    }
}
