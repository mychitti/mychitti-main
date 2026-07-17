@if ($combinations && count($combinations) > 0)
    <table class="table table-borderless table-responsive table--vertical-middle">
        <thead class="thead-light __bg-7">
            <tr>
                <th class="text-center border-0">
                    <span class="control-label m-0">{{ translate('messages.Variant') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant MRP') }}</span>
                </th>
                <th class="text-center border-0"> 
                     <span class="control-label">{{ translate('messages.Selling Price') }}</span>
                </th>
                 <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Purchase Price') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.SKU') }}</span>
                </th>
                @if ($secondary_unit)
                    {{-- <th class="text-center border-0">
                        <span
                            class="control-label text-capitalize">{{ $secondary_unit ? 'Qty(' . $secondary_unit . ')' : 'Stock' }}</span>
                    </th> --}}
                    <th class="text-center border-0">
                        <span
                            class="control-label text-capitalize">{{ $primary_unit ? 'Opening Stock (' . $primary_unit . ')' : 'Stock' }}</span>
                    </th>
                @else
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
                        <input oninput="calcValues()" style="min-width: 90px;" type="number" name="mrpprice_{{ $combination['type'] }}"
                            value="{{ isset($combination['mrpprice']) ? $combination['mrpprice'] : 0 }}" min="0"
                            step="0.000001" class="form-control" required>
                    </td>
                    <td>
                        <input type="number" name="askingprice_{{ $combination['type'] }}"
                            value="{{ $combination['price'] }}" min="0" step="0.000001" class="form-control"
                            >
                    </td>
                    <td>
                        {{-- <div class="input-group mb-3"> --}}
                            <input oninput="calcValues()" type="number" value="{{ $combination['purchaseprice'] }}"
                                min="0" name="purchaseprice_{{ $combination['type'] }}" class="form-control"
                                step="0.000001">

                            {{-- <span class="input-group-text" id="remaining_price_show_{{ $combination['type'] }}">{{ isset($combination['remainingprice']) ? '+' .  number_format($combination['remainingprice'],2) : '' }}</span> --}}
                        {{-- </div> --}}

                    </td>
                       <td>
                        <input type="text" style="min-width: 90px;" name="sku_{{ $combination['type'] }}"
                            value="{{ isset($combination['variations_table_id']) ? _getInvVrDetails($combination['variations_table_id'])->sku : '' }}" class="form-control"
                            >
                    </td>

                    <td>
                        <input type="number" style="min-width: 90px;" name="stock_{{ $combination['type'] }}"
                            placeholder="{{ $primary_unit ?? '' }}" value="{{ $combination['stock'] ?? 0 }}"
                            min="0" class="form-control update_qty" required>
                    </td>

                </tr>
                <tr class="border border-2 border-top-0 mb-2 variant-extra-row">
                    <td colspan="2">
                        <label class="control-label m-0">Description</label>
                        <textarea name="descs_{{ $combination['type'] }}" class="form-control" required>{{ isset($combination['variations_table_id']) ? _getInvVrDetails($combination['variations_table_id'])->description : '' }}</textarea>
                    </td>
                    <td colspan="2">
                        <label class="control-label m-0">Specifications</label>
                        <textarea id="specs_{{ $combination['type'] }}" class="editor">{{ isset($combination['variations_table_id']) ? _getInvVrDetails($combination['variations_table_id'])->specifications : '' }}</textarea>
                    </td>
                    <td colspan="2" class="">
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
                                    @if (isset($combination['variations_table_id']) && _getInvVrDetails($combination['variations_table_id'])->images)
                                        @if (!count(json_decode(_getInvVrDetails($combination['variations_table_id'])->images)))
                                            No Images Yet...
                                        @elseif(isset($combination['variations_table_id']))
                                            @foreach (json_decode(_getInvVrDetails($combination['variations_table_id'])->images) as $key => $img)
                                                <a target="_blank" style="cursor:zoom-in; margin:5px;"
                                                    href="{{ $img ? asset('storage/app/public/inventory-variations') . '/' . $img : '#' }}"><img
                                                        class="border onerror-image"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper(
                                                            $img ?? '',
                                                            asset('storage/app/public/inventory-variations') . '/' . $img ?? '',
                                                            asset('public/assets/admin/img/upload-img.png'),
                                                            'inventory-variations/',
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
    <script type="importmap">
        {
            "imports": {
                "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.js",
                "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.0.0/"
            }
        }
        </script>
    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Bold,
            Italic,
            Font,
            Paragraph,
            Table,
            TableToolbar
        } from 'ckeditor5';

        // Explicitly create a global object if it doesn't exist
        window.ckeditorInstances = window.ckeditorInstances || {};

        document.querySelectorAll('.editor').forEach((editorElement, index) => {
            ClassicEditor
                .create(editorElement, {
                    plugins: [Essentials, Bold, Italic, Font, Paragraph, Table, TableToolbar],
                    toolbar: {
                        items: [
                            'undo', 'redo', '|', 'bold', 'italic', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                            'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells'
                        ]
                    }
                })
                .then(editor => {
                    // Use the element's ID as the key
                    window.ckeditorInstances[editorElement.id] = editor;

                    // Optional: Log to verify
                    console.log('Editors assigned:', window.ckeditorInstances);
                })
                .catch(error => {
                    console.error('There was a problem initializing the editor.', error);
                });
        });
    </script>
@endif
