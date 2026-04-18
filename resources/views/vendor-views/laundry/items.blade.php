@extends('layouts.vendor.app')
@section('title', 'Laundry — Item Master')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <h1 class="page-header-title"><span class="page-header-icon"><i class="tio-washing-machine"></i></span> Item Master</h1>
        <button class="btn btn--primary" data-toggle="modal" data-target="#addItemModal"><i class="tio-add"></i> Add Item</button>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="d-flex align-items-center gap-2" method="GET">
                <select name="category" class="form-control" style="max-width:200px" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <option value="normal"    {{ $category == 'normal'    ? 'selected' : '' }}>Normal Wash</option>
                    <option value="premium"   {{ $category == 'premium'   ? 'selected' : '' }}>Premium</option>
                    <option value="dry_clean" {{ $category == 'dry_clean' ? 'selected' : '' }}>Dry Clean</option>
                </select>
                @if($category)
                <a href="{{ route('vendor.laundry.items') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $key => $item)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>
                                @if($item->category == 'normal') <span class="badge badge-soft-success">Normal</span>
                                @elseif($item->category == 'premium') <span class="badge badge-soft-warning">Premium</span>
                                @else <span class="badge badge-soft-info">Dry Clean</span>
                                @endif
                            </td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ ucfirst($item->unit) }}</td>
                            <td>
                                <label class="toggle-switch toggle-switch-sm">
                                    <input type="checkbox" class="toggle-switch-input item-toggle"
                                        data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-item-btn"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-category="{{ $item->category }}"
                                    data-price="{{ $item->price }}"
                                    data-unit="{{ $item->unit }}"
                                    data-toggle="modal" data-target="#editItemModal">
                                    <i class="tio-edit"></i>
                                </button>
                                <a href="{{ route('vendor.laundry.items.destroy', $item->id) }}"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete this item?')">
                                    <i class="tio-delete"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4">No items yet. Add your first item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('vendor.laundry.items.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Item</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-control js-select2-custom" required>
                            <option value="normal">Normal Wash</option>
                            <option value="premium">Premium</option>
                            <option value="dry_clean">Dry Clean</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Price <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Unit</label>
                                <select name="unit" class="form-control">
                                    <option value="piece">Piece</option>
                                    <option value="kg">Kg</option>
                                    <option value="pair">Pair</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editItemForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Item</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" id="edit_category" class="form-control" required>
                            <option value="normal">Normal Wash</option>
                            <option value="premium">Premium</option>
                            <option value="dry_clean">Dry Clean</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Price <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Unit</label>
                                <select name="unit" id="edit_unit" class="form-control">
                                    <option value="piece">Piece</option>
                                    <option value="kg">Kg</option>
                                    <option value="pair">Pair</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // Edit modal populate
    $('.edit-item-btn').on('click', function () {
        var id = $(this).data('id');
        $('#editItemForm').attr('action', '{{ url("laundry/items/update") }}/' + id);
        $('#edit_name').val($(this).data('name'));
        $('#edit_category').val($(this).data('category'));
        $('#edit_price').val($(this).data('price'));
        $('#edit_unit').val($(this).data('unit'));
    });

    // Active toggle
    $(document).on('change', '.item-toggle', function () {
        var id = $(this).data('id');
        $.post('{{ url("laundry/items/toggle") }}/' + id, {_token: '{{ csrf_token() }}'});
    });
</script>
@endpush
