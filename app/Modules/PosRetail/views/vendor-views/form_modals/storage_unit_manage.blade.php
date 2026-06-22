<div class="modal fade" id="storageUnitManageModal" tabindex="-1" aria-labelledby="storageUnitManageLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="storageUnitManageLabel">Manage Storage Unit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('vendor.inventory.item.assign-storage-unit') }}" method="post">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Select an item and the storage unit where it is kept, then save.</p>

                    <div class="form-group">
                        <label class="input-label">Item <span class="text-danger">*</span></label>
                        <select name="item_id" id="su_item_select" class="form-control js-select2-custom" required>
                            <option value="">---{{ translate('messages.select') }} Item---</option>
                            @foreach ($items as $item)
                                <option value="{{ $item['id'] }}" data-storage="{{ $item['storage_unit_id'] }}">
                                    {{ $item['item_name'] . ' | ' . ($item['company_sku_id'] ?? $item['sku_id']) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="input-label mb-0">Storage Unit <span class="text-danger">*</span></label>
                            <a href="{{ route('vendor.inventory.storage-spaces') }}" class="small font-weight-bold">
                                <i class="tio-add-circle-outlined"></i> Add Storage Unit
                            </a>
                        </div>
                        <select name="storage_unit_id" id="su_unit_select" class="form-control js-select2-custom" required>
                            <option value="">---{{ translate('messages.select') }} Storage Unit---</option>
                            @php $storage_units_manage = \App\Models\StorageUnit::with('parent')->where('store_id', \App\CentralLogics\Helpers::get_store_id())->get(); @endphp
                            @foreach ($storage_units_manage as $unit)
                                <option value="{{ $unit['id'] }}">
                                    {{ $unit->parent ? $unit->parent->name . ' > ' : '' }}{{ $unit['name'] . ' (' . $unit->type . ')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script_2')
    <script>
        $(document).on('change', '#su_item_select', function () {
            // Pre-select the item's currently assigned storage unit (if any).
            var current = $(this).find('option:selected').data('storage') || '';
            $('#su_unit_select').val(String(current)).trigger('change');
        });
    </script>
@endpush
