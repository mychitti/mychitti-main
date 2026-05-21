
@if (count($combinations) > 0)
    <table class="table table-borderless table--vertical-middle">
        <thead class="thead-light __bg-7">
            <tr>
                <th class="text-center border-0">
                    <span class="control-label m-0">{{ translate('messages.Variant') }}</span>
                </th>
              
                @if ($stock)
                    <th class="text-center border-0">
                        <span class="control-label text-capitalize">{{ translate('messages.stock') }}</span>
                    </th>
                @endif
              
            </tr>
        </thead>
        <tbody>

            @foreach ($combinations as $key => $combination)
                <tr>
                    <input type="hidden" name="vrtblid_{{ $combination['type'] }}"
                        value="{{ $combination['variations_table_id'] }}">
                    <td class="text-center">
                        <label class="control-label m-0">{{ $combination['type'] }}</label>
                        <input value="{{ $combination['type'] }}" name="type[]" type="hidden">
                    </td>
                   
                    @if ($stock)
                        <td >
                            <input type="number" onkeyup="update_qty()" name="stock_{{ $combination['type'] }}"
                                value="{{ $combination['stock'] ?? 0 }}" min="0" step="0.001"
                                class="form-control" required>
                        </td>
                    @endif
                   

                </tr>
              
            
            @endforeach
        </tbody>
        <tbody id="newVariationsRows">
        </tbody>
    </table>
@endif
