@if (count($combinations) > 0)
    <table class="table table-borderless table--vertical-middle">
        <thead class="thead-light __bg-7">
            <tr>
                <th class="text-center border-0">
                    <span class="control-label m-0">{{ translate('messages.Variant') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant MRP') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Asking Price') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Selling Price') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Discount') }}</span>
                </th>
                {{-- <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant Tax') }}(%)</span>
                </th> --}}
                @if ($stock)
                    <th class="text-center border-0">
                        <span class="control-label text-capitalize">{{ translate('messages.stock') }}</span>
                    </th>
                @endif
            </tr>
        </thead>
        <tbody id="variationsRows">

            @foreach ($combinations as $key => $combination)
                <tr>
                    <td class="text-center">
                        <label class="control-label m-0">{{ $combination['type'] }}</label>
                        <input value="{{ $combination['type'] }}" name="type[]" type="hidden">
                    </td>
                    <td>
                        <input oninput="calcValues()" type="number" name="mrpprice_{{ $combination['type'] }}"
                            value="{{ isset($combination['mrpprice']) ? $combination['mrpprice'] : 0 }}" min="0"
                            step="0.000001" class="form-control" required>
                    </td>
                    <td>
                        <div class="input-group mb-3">
                            <input oninput="calcValues()" type="number"
                                value="{{ isset($combination['askingprice']) ? (isset($combination['remainingprice']) ? $combination['askingprice'] - $combination['remainingprice'] : $combination['askingprice']) : 0 }}"
                                min="0" name="askingprice_{{ $combination['type'] }}" class="form-control"
                                step="0.000001" required>

                            {{-- <span class="input-group-text" id="remaining_price_show_{{ $combination['type'] }}">{{ isset($combination['remainingprice']) ? '+' .  number_format($combination['remainingprice'],2) : '' }}</span> --}}
                        </div>
                        {{-- <input type="hidden" min="0" max="999999999999.99"  value="{{ isset($combination['remainingprice']) ?  $combination['remainingprice'] : 0 }}"
                            name="remainingprice_{{ $combination['type'] }}" class="form-control" step="0.000001"> --}}

                    </td>
                    <td>
                        <input type="number" name="price_{{ $combination['type'] }}"
                            value="{{ $combination['price'] }}" min="0" step="0.000001" class="form-control"
                            readonly>
                    </td>
                    <td>
                        <input type="number" name="discount_{{ $combination['type'] }}"
                            value="{{ isset($combination['discount']) ? $combination['discount'] : 0 }}"
                            min="0" step="0.000001" class="form-control" readonly>
                    </td>
                    {{-- <td>
                        <input type="number" name="tax_{{ $combination['type'] }}"
                            oninput="if(this.value > 99) this.value = 99;"
                            value="{{ isset($combination['tax']) ? $combination['tax'] : 0 }}" min="0"
                            step="0.000001" class="form-control" required>
                    </td> --}}
                    @if ($stock)
                        <td>
                            <input type="number" name="stock_{{ $combination['type'] }}"
                                value="{{ $combination['stock'] ?? 0 }}" min="0" class="form-control update_qty"
                                required>
                        </td>
                    @endif


                </tr>
                <tr class="border border-2 border-top-0 mb-2">
                    <td colspan="2">
                        <label class="control-label m-0">Description</label>
                        <textarea name="descs_{{ $combination['type'] }}" class="form-control" required>{{ isset($combination['variations_table_id']) ? _getVrDetails($combination['variations_table_id'])->description : '' }}</textarea>
                    </td>
                    <td colspan="2">
                        <label class="control-label m-0">Specifications</label>
                        <textarea id="specs_{{ $combination['type'] }}" class="editor">{{ isset($combination['variations_table_id']) ? _getVrDetails($combination['variations_table_id'])->specifications : '' }}</textarea>
                    </td>
                    <td class="d-flex align-items-center">
                        <input type="file" name="imgs_{{ $combination['type'] }}[]" multiple class="form-control">
                        <a type="button" data-toggle="modal" data-target="#imagesModal_{{ $key }}"
                            title="View Current Images" class="btn action-btn btn--warning btn-outline-warning"><i
                                class="tio-visible"></i>
                        </a>

                    </td>
                </tr>
                <div class="modal fade" id="imagesModal_{{ $key }}" tabindex="-1"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Current Images</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex flex-wrap">
                                    @if (isset($combination['variations_table_id']) && _getVrDetails($combination['variations_table_id'])->images)
                                        @if (!count(json_decode(_getVrDetails($combination['variations_table_id'])->images)))
                                            No Images Yet...
                                        @elseif(isset($combination['variations_table_id']))
                                            @foreach (json_decode(_getVrDetails($combination['variations_table_id'])->images) as $key => $img)
                                                <a target="_blank" style="cursor:zoom-in; margin:5px;"
                                                    href="{{ $img ? asset('storage/app/public/product-variations') . '/' . $img : '#' }}"><img
                                                        class="border onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $img ?? '',
                                                            asset('storage/app/public/product-variations') . '/' . $img ?? '',
                                                            asset('public/assets/admin/img/upload-img.png'),
                                                            'product-variations/',
                                                        ) }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                        alt="thumbnail"
                                                        style="width:100px; height:100px; object-fit:contain" /></a>
                                            @endforeach
                                        @else
                                            No Images Yet...
                                        @endif
                                    @else
                                        No Images Yet...
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </tbody>
        <tbody id="newVariationsRows">
        </tbody>
    </table>
@endif
