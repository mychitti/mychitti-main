<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        table.labels {
            width: 100%;
            border-collapse: collapse;
        }

        td.label {
            width: {{ $labelWidth }}mm;
            height: {{ $labelHeight }}mm;
            text-align: center;
            vertical-align: middle;
            padding: 4px;
            margin: 0;
            border: 1px dashed lightgrey;
        }


        img.barcode {
            width: 37.313mm;
            /* 1.469 in */
            height: 25.908mm;
            /* 1.02 in */
        }
    </style>
</head>

<body>
    <table class="labels">
        @for ($row = 0; $row < $rowsPerPage; $row++)
            <tr>
                @for ($col = 0; $col < $labelsPerRow; $col++)
                    <td class="label">
                        <div class="">
                            @if ($item->barcode)
                                <img src="{{ asset('storage/app/public/inventory-item/barcode/') . '/' . $item->barcode }}"
                                    class="barcode">
                            @else
                                @php
                                    $barcodeBase64 = DNS1D::getBarcodePNG(
                                        $item->sku_id,
                                        'C128',
                                        2,
                                        60,
                                        [0, 0, 0],
                                        false,
                                    );
                                @endphp
                                <img src="data:image/png;base64,{{ $barcodeBase64 }}" class="barcode">
                            @endif
                            <span>{{ $item->sku_id }}</span>
                        </div>
                    </td>
                @endfor
            </tr>
        @endfor
    </table>
</body>

</html>
