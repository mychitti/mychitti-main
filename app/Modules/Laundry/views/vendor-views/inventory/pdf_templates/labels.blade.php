<table style="border-collapse: collapse; width: 100%; height: 100%;">
    @for($r = 0; $r < $rows; $r++)
        <tr style="height: {{ 100/$rows }}%;">
            @for($c = 0; $c < $cols; $c++)
                @php 
                    $index = $r * $cols + $c; 
                @endphp

                @if($index < $repeat)
                    <td style="text-align: center; vertical-align: top; padding: 5px; width: {{ 100/$cols }}%;">
                        @include('vendor-views.inventory.pdf_templates.single_label', ['label' => $label])
                    </td>
                @else
                    <td></td> {{-- keeps grid intact --}}
                @endif
            @endfor
        </tr>
    @endfor
</table>
