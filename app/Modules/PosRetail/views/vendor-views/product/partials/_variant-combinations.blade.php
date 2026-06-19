@if (count($combinations[0]) > 0)
    <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">{{ translate('messages.Variant MRP') }}</span>
                </th>
                <th class="text-center border-0">
                    <span class="control-label">Asking Price</span>
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
            @if (strlen($str) > 0)
                <tr>
                    <td>
                        <label class="control-label">{{ $str }}</label>
                    </td>
                    <td>
                        <input type="number" name="mrpprice_{{ $str }}" value="{{ $price }}"
                            min="0" step="0.001" class="form-control" required>
                    </td>
                    <td>
                        <input  type="number" name="askingprice_{{ $str }}" 
                            min="0" step="0.001" class="form-control" required>
                    </td>
                    {{-- <td>
                        <input type="number" name="tax_{{ $str }}" value="0"
                           oninput="if(this.value > 99) this.value = 99;"  min="0"  step="0.001" class="form-control" required>
                    </td> --}}
                    @if ($stock)
                        <td><input type="number" name="stock_{{ $str }}" value="1" min="0"
                                step="0.001" class="form-control" required></td>
                    @endif
                  
                </tr>
                <tr class="border border-2 border-top-0 mb-2">
                    <td colspan="2">
                        <label class="control-label m-0">Description</label>

                        <textarea name="descs_{{ $str }}" class="form-control"></textarea>
                    </td>
                    <td colspan="2">
                        <label class="control-label m-0">Specifications</label>
                        <textarea id="specs_{{ $str }}" class="editor"></textarea>
                    </td>
                      <td>
                        <input type="file" name="imgs_{{ $str }}[]" multiple class="form-control" required>
                    </td>
                </tr>
            @endif
        @endforeach
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
    <script>
        update_qty();

        function update_qty() {
            let total_qty = 0;
            let qty_elements = $('input[name^="stock_"]');
            for (let i = 0; i < qty_elements.length; i++) {
                total_qty += parseInt(qty_elements.eq(i).val());
            }
            if (qty_elements.length > 0) {

                $('input[name="current_stock"]').attr("readonly", true);
                $('input[name="current_stock"]').val(total_qty);
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
    </script>
@endif
