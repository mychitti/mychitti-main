    @if (count($combinations[0]) > 0)
        @foreach ($combinations as $key => $combination)
            @php

                $str = '';
                foreach ($combination as $key => $item) {
                    if ($key > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        $str .= str_replace(' ', '', $item);
                    }
                }
            @endphp
            @if (strlen($str) > 0 )
                <tr class="border border-2 border-bottom-0 ">
                    <td class="text-center">
                        <label class="control-label m-0">{{ $str }}</label>
                    </td>
                      <td>
                        <input oninput="calcValues()" type="number" name="mrpprice_{{ $str }}"
                            value="{{ $price }}" min="0"
                            step="0.000001" class="form-control" required>
                    </td>
                   <td>
                        <div class="input-group mb-3">
                            <input oninput="calcValues()" type="number"
                                min="0" name="askingprice_{{ $str }}" class="form-control"
                                step="0.000001" required>

                            {{-- <span class="input-group-text" id="remaining_price_show_{{ $str }}"></span> --}}
                        </div>
                        {{-- <input type="hidden" min="0" max="999999999999.99"  value="0"
                            name="remainingprice_{{ $str }}" class="form-control" step="0.000001"> --}}
                    </td>
                    <td>
                        <input type="number" name="price_{{ $str }}"
                            value="{{ $str }}" min="0" step="0.000001" class="form-control"
                            readonly>
                    </td>
                     <td>
                        <input type="number" name="discount_{{ $str  }}"
                           
                            min="0" step="0.000001" class="form-control" readonly>
                    </td>
                    {{-- <td>
                        <input type="number" name="tax_{{ $str }}" value="0" min="0"
                            step="0.01" class="form-control" required>
                    </td> --}}
                    @if ($stock)
                        <td><input type="number" name="stock_{{ $str }}" value="1" min="0"
                                class="form-control" required></td>
                    @endif

                </tr>
                <tr class="border border-2 border-top-0 mb-2">
                    <td colspan="2">
                        <label class="control-label m-0">Description</label>

                        <textarea name="descs_{{ $str }}" class="form-control" required></textarea>
                    </td>
                    <td colspan="2">
                        <label class="control-label m-0">Specifications</label>
                        <textarea id="specs_{{ $str }}" class="editor2"></textarea>
                    </td>
                    <td>
                        <input type="file" name="imgs_{{ $str }}[]" multiple class="form-control" required>
                    </td>
                </tr>
            @endif
        @endforeach

        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.0.0/ckeditor5.css" />
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

            document.querySelectorAll('.editor2').forEach((editorElement, index) => {
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
        <script>
            "use strict";
            update_qty();

            function update_qty() {
                let total_qty = 0;
                let qty_elements = $('input[name^="stock_"]');
                for (let i = 0; i < qty_elements.length; i++) {
                    total_qty += parseInt(qty_elements.eq(i).val());
                }
                if (qty_elements.length > 0) {

                    $('input[name="current_stock"]').attr("readonly", true);
                    $('input[name="current_stock"]').val('wrfrwf');
                } else {
                    $('input[name="current_stock"]').attr("readonly", false);
                }
            }
            $('input[name^="stock_"]').on('keyup', function() {
                let total_qty = 0;
                let qty_elements = $('input[name^="stock_"]');
                for (let i = 0; i < qty_elements.length; i++) {
                    total_qty += parseInt(qty_elements.eq(i).val());
                }
                $('input[name="current_stock"]').val(total_qty);
            });

            $('.update_qty').on('keyup', function() {
                update_qty();
            });
        </script>
    @endif
