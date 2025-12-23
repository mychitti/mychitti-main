<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class POExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $headings;

    /**  
     * Constructor to initialize data and headings.
     *
     * @param array $data
     * @param array $headings 
     */
    public function __construct(array $data, array $headings)
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    /**
     * Collection method to return the data.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Headings method to return the column headers.
     *
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
