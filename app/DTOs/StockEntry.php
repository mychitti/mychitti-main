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
     * A damaged / theft / leaked write-off.
     *
     * Stock leaves inventory when the request is RAISED, not when a manager decides it, so the
     * request itself is the movement. Scrap and return-to-supplier keep the quantity out for
     * good; anything that comes back is reported separately by fromWriteoffReturn().
     */
    public static function fromWriteoff($row): self
    {
        $labels = ['damaged' => 'Damaged', 'theft' => 'Theft', 'leaked' => 'Leaked'];
        $ref = 'WO-' . $row->id . ' ' . ($labels[$row->type] ?? ucfirst((string) $row->type));

        if (!empty($row->dispositions)) {
            $ref .= ' (' . $row->dispositions . ')';
        } elseif (($row->status ?? '') === 'pending') {
            $ref .= ' (awaiting approval)';
        }
        if (!empty($row->branch_name)) {
            $ref .= ' at ' . $row->branch_name;
        }

        return new self([
            'date'            => \Carbon\Carbon::parse($row->created_at),
            'item_id'         => $row->item_name ? $row->inventory_item_id : null,
            'item_name'       => ucwords((string) $row->item_name),
            'invoice_pdf'     => null,
            'invoice_id'      => $ref,
            'type'            => 'Stock-out',
            'qty'             => $row->qty,
            'remaining_stock' => $row->stock,
        ]);
    }

    /**
     * Stock a write-off gave back — the manager rejected the request, or accepted part of it as
     * "convert to resell". Dated to the decision, because that is when the quantity returned.
     */
    public static function fromWriteoffReturn($row, $qty, string $reason): self
    {
        return new self([
            'date'            => \Carbon\Carbon::parse($row->decided_at),
            'item_id'         => $row->item_name ? $row->inventory_item_id : null,
            'item_name'       => ucwords((string) $row->item_name),
            'invoice_pdf'     => null,
            'invoice_id'      => 'WO-' . $row->id . ' ' . $reason,
            'type'            => 'Stock-in',
            'qty'             => $qty,
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
