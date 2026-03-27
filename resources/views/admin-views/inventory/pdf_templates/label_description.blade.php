<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            font-size: 14px !important;
        }

        table.outer-grid { 
            width: 100%;
            border-collapse: collapse;
        }

        td.label {
            width: {{ $labelWidth }}mm;
            height: {{ $labelHeight - 10 }}mm;
            border: 1px dashed #d6d6d6ff;
            padding: 7px;
            vertical-align: top;
        }



        table.label-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            border: 1px solid black ; 

        }

        .header-cell {
            {{-- font-weight: bold; --}} text-align: left;
            font-size: 14px;
        }

        .description-cell {
            height: 20px;
        }

        .barcode-cell {
            text-align: center;
            padding-top: 6px;
        }

        .barcode {
            display: block;
            margin: 0 auto 3px auto;
            width: 37.313mm;
            /* matches your original */
            height: 25mm;
        }

        .barcode-text {
            font-size: 14px;
            letter-spacing: 2px;
        }

        .border {
            border: 1px solid black;
            padding: 10px;
        }

        .border-right-0 {
            border-right: 0px;
        }

        .border-left-0 {
            border-left: 0px;
        }
    </style>
</head>

<body>
    <table class="outer-grid">
        @for ($row = 0; $row < $rowsPerPage; $row++)
            <tr>
                @for ($col = 0; $col < $labelsPerRow; $col++)
                    <td class="label">
                        <div class="scissors-left"></div>
                        <div class="scissors-right"></div>
                        <table class="label-table">
                            <tr>
                                <td class="header-cell description-cell" colspan="2">
                                    <b> Product <br>
                                        description:</b> {{ $item->item_name }}
                                </td>
                            </tr>
                            <tr>
                                <td class="header-cell border border-left-0" style="width:50%;">
                                    <b> MRP:</b> {{ _price($item->mrp) }}<br>
                                </td>
                                <td class="header-cell border border-right-0" style="width:50%;">
                                    <b> Selling Price:</b> {{ _price($item->selling_price) }}<br>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-cell border border-left-0 " style="width:50%;">
                                    <b>SKU ID:</b> {{ $item->sku_id }}<br>
                                </td>

                                <td class="header-cell border border-right-0" style="width:50%;">
                                    <b>Qty:</b> <br>
                                </td>
                            </tr>

                        </table>
                    </td>
                @endfor
            </tr>
        @endfor
    </table>
</body>

</html>
