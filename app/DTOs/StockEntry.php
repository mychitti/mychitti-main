<?php

namespace App\DTOs;

class StockEntry
{
    public $date;
    public $item_id;
    public $item_name;
    public $invoice_pdf;
    public $invoice_id;
    public $type;
    public $qty;
    public $remaining_stock;

    public function __construct(array $data)
    {
        $this->date            = $data['date'] ?? null;
        $this->item_id         = $data['item_id'] ?? null;
        $this->item_name       = $data['item_name'] ?? null;
        $this->invoice_pdf     = $data['invoice_pdf'] ?? null;
        $this->invoice_id      = $data['invoice_id'] ?? null;
        $this->type            = $data['type'] ?? null;
        $this->qty             = $data['qty'] ?? 0;
        $this->remaining_stock = $data['remaining_stock'] ?? 0;
    }

    // Optional: factory methods for each type
    public static function fromSupplyOrderItem($item): self
    {
        return new self([
            'date'            => $item->created_at,
            'item_id'         => $item->item?->id,
            'item_name'       => ucwords($item->item?->item_name),
            'invoice_pdf'     => $item->order?->invoice?->pdf,
            'invoice_id'      => $item->order?->invoice?->invoice_id,
            'type'            => 'Stock-in',
            'qty'             => $item->qty,
            'remaining_stock' => $item->item?->stock,
        ]);
    }

    public static function fromItemEntry($item): self
    {
        return new self([
            'date'            => $item->created_at,
            'item_id'         => $item->item?->id,
            'item_name'       => ucwords($item->item?->item_name),
            'invoice_pdf'     => $item->invoice?->pdf,
            'invoice_id'      => $item->bill_number,
            'type'            => 'Stock-in',
            'qty'             => $item->quantity,
            'remaining_stock' => $item->item?->stock,
        ]);
    }

    /**
     * An accepted damaged / theft / leaked write-off.
     *
     * Stock leaves when the manager accepts, so the decision is the movement and the row is
     * dated to it. $row->qty has already had any "kept for resale" portion taken off by the
     * caller, so what appears here is only what actually left the shelf.
     */
    public static function fromWriteoff($row): self
    {
        $labels = ['damaged' => 'Damaged', 'theft' => 'Theft', 'leaked' => 'Leaked'];
        $ref = 'WO-' . $row->id . ' ' . ($labels[$row->type] ?? ucfirst((string) $row->type));

        if (!empty($row->dispositions)) {
            $ref .= ' (' . $row->dispositions . ')';
        }
        if (!empty($row->branch_name)) {
            $ref .= ' at ' . $row->branch_name;
        }

        return new self([
            'date'            => \Carbon\Carbon::parse($row->decided_at),
            'item_id'         => $row->item_name ? $row->inventory_item_id : null,
            'item_name'       => ucwords((string) $row->item_name),
            'invoice_pdf'     => null,
            'invoice_id'      => $ref,
            'type'            => 'Stock-out',
            'qty'             => $row->qty,
            'remaining_stock' => $row->stock,
        ]);
    }

    public static function fromInventoryOrderDetail($item): self
    {
        return new self([
            'date'            => $item->created_at,
            'item_id'         => $item->item?->id,
            'item_name'       => ucwords($item->item?->item_name),
            'invoice_pdf'     => $item->order?->invoice?->pdf,
            'invoice_id'      => $item->order?->invoice_id,
            'type'            => 'Stock-out',
            'qty'             => $item->qty,
            'remaining_stock' => $item->item?->stock,
        ]);
    }
}
