
@if (count($combinations) > 0)
    <table class="table table-borderless table--vertical-middle">
        <thead class="thead-light __bg-7">
            <tr>
                <th class="text-center border-0">
                    <span class="control-label m-0">{{ translate('messages.Variant') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant Price') }}</span>
                </th>
                
            </tr>
        </thead>
        <tbody>

            @foreach ($combinations as $key => $combination)
                <tr>
                <input type="hidden" name="vrtblid_{{ $combination['type'] }}"  value="{{$combination['variations_table_id']}}" >
                    <td class="text-center">
                        <label class="control-label m-0">{{ $combination['type'] }}</label>
                        <input value="{{ $combination['type'] }}" name="type[]" type="hidden">
                    </td>
                    <td>
                        <input type="number" name="price_{{ $combination['type'] }}"
                            value="{{ $combination['price'] }}" min="0" step="0.001" class="form-control"
                            required>
                    </td>
                 
                </tr>
          
          
            @endforeach
        </tbody>
        <tbody id="newVariationsRows">
        </tbody>
    </table>
@endif
