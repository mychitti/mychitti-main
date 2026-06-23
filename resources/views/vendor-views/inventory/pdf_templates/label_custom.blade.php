<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        /* mPDF honours position:absolute only for elements that are direct children of <body>
           and that have an explicit width — so each element below sets both. */
        .el { position: absolute; overflow: hidden; }
    </style>
</head>
<body>
    @php
        $LW   = (float) $format->width_mm;
        $cols = isset($cols) ? (int) $cols : 1;
        $gap  = isset($colGap) ? (float) $colGap : 0;
    @endphp
    @for ($c = 0; $c < $cols; $c++)
    @php $xOffset = $c * ($LW + $gap); @endphp
    @foreach ($elements as $el)
        @php
            $type = $el['type'] ?? 'custom_text';
            $text = trim((string) ($el['text'] ?? ''));
            $x = (float) ($el['x'] ?? 0);
            $y = (float) ($el['y'] ?? 0);
            $font = (float) ($el['font'] ?? 8);
            $bold = !empty($el['bold']);
            $bw = (float) ($el['w'] ?? 30);
            $bh = (float) ($el['h'] ?? 10); 
 
            $content = '';
            if ($type === 'store_name') {
                // Blank (or the old "Store" placeholder) => use the actual store name.
                $content = htmlspecialchars(($text !== '' && strtolower($text) !== 'store') ? $text : ($store->name ?? ''));
            } elseif ($type === 'item_name') {
                $val = $item->item_name;
                $prefix = $text;
                if (!empty($el['newline']) && $prefix !== '') {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars(($prefix !== '' ? $prefix . ' ' : '') . $val);
                }
            } elseif ($type === 'sku' || $type === 'code') {
                $val = $item->sku_id;
                $prefix = $text !== '' ? $text : 'Code';
                if (!empty($el['newline'])) {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars($prefix . ' ' . $val);
                }
            } elseif ($type === 'mrp') {
                $val = _price($item->mrp);
                $prefix = $text !== '' ? $text : 'M.R.P';
                if (!empty($el['newline'])) {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars($prefix . ' ' . $val);
                }
            } elseif ($type === 'selling_price') {
                $val = _price($item->selling_price);
                $prefix = $text !== '' ? $text : 'Price';
                if (!empty($el['newline'])) {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars($prefix . ' ' . $val);
                }
            } elseif ($type === 'packing_date') {
                $val = $packingDate ?? '';
                $prefix = $text !== '' ? $text : 'Date';
                if (!empty($el['newline'])) {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars($prefix . ' ' . $val);
                }
            } elseif ($type === 'expiry_date') {
                $val = $expiryDate ?? '';
                $prefix = $text !== '' ? $text : 'Exp';
                if (!empty($el['newline'])) {
                    $content = htmlspecialchars($prefix) . '<br/>' . htmlspecialchars($val);
                } else {
                    $content = htmlspecialchars($prefix . ' ' . $val);
                }
            } else {
                $content = htmlspecialchars($text); // subtitle / custom_text
            }

            // Width available from this element's left edge to the label's right edge.
            $availW = max(8, $LW - $x);
        @endphp

        @if ($type === 'barcode')
            @php
                if ($item->barcode) {
                    $src = asset('storage/app/public/inventory-item/barcode/') . '/' . $item->barcode;
                } else {
                    $src = 'data:image/png;base64,' . DNS1D::getBarcodePNG($item->sku_id ?: '0', 'C128', 2, 50, [0, 0, 0], false);
                }
            @endphp
            <div class="el" style="left: {{ $x + $xOffset }}mm; top: {{ $y }}mm; width: {{ $bw }}mm; text-align:center;">
                <img src="{{ $src }}" style="width: {{ $bw }}mm; height: {{ $bh }}mm;">
                <div style="font-size: {{ max(5, $font - 1) }}pt;">{{ $item->sku_id }}</div>
            </div>
        @else
            <div class="el"
                style="left: {{ $x + $xOffset }}mm; top: {{ $y }}mm; width: {{ $availW }}mm; font-size: {{ $font }}pt; line-height: 1.1; font-weight: {{ $bold ? 'bold' : 'normal' }}; text-align: left; {{ empty($el['newline']) ? 'white-space: nowrap;' : 'white-space: normal;' }}">
                {!! $content !!}
            </div>
        @endif
    @endforeach
    @endfor
</body>
</html>
