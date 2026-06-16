@extends('layouts.admin.app')
@section('title', 'School Plan Tiers')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title"><i class="tio-school"></i> School Plan Tiers</h1>
    </div>

    <div class="row">
        {{-- Form --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-header-title mb-0">Add / Edit Tier</h5></div>
                <form action="{{ route('admin.school-tiers.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="t_id">
                    <div class="card-body">
                        <div class="form-group"><label class="input-label">Tier Name *</label>
                            <input name="tier_name" id="t_name" class="form-control" maxlength="100" placeholder="e.g. Growth" required></div>
                        <input type="hidden" name="min_students" id="t_min" value="0">
                        <div class="form-row">
                            <div class="form-group col-6"><label class="input-label">Max Students</label>
                                <input type="number" name="max_students" id="t_max" class="form-control" min="0" placeholder="blank = unlimited"></div>
                            <div class="form-group col-6"><label class="input-label">Max Branches</label>
                                <input type="number" name="max_branches" id="t_branches" class="form-control" min="1" placeholder="blank = unlimited"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6"><label class="input-label">Price / Month *</label>
                                <input type="number" step="0.01" name="price_monthly" id="t_pm" class="form-control" min="0" value="0" required></div>
                            <div class="form-group col-6"><label class="input-label">Price / Year *</label>
                                <input type="number" step="0.01" name="price_yearly" id="t_py" class="form-control" min="0" value="0" required></div>
                        </div>
                        <div class="form-group"><label class="input-label">Sort Order</label>
                            <input type="number" name="sort_order" id="t_sort" class="form-control" min="0" value="0"></div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="t_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="t_active">Active (available to schools)</label>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="button" class="btn btn-white" onclick="resetTier()">Clear</button>
                        <button class="btn btn-primary"><i class="tio-save"></i> Save Tier</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- List --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-header-title mb-0">Plan Tiers</h5></div>
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light"><tr>
                            <th>Tier</th><th>Students</th><th>Branches</th><th class="text-right">Monthly</th><th class="text-right">Yearly</th><th>Status</th><th class="text-right">Action</th>
                        </tr></thead>
                        <tbody>
                        @forelse($tiers as $t)
                            <tr>
                                <td class="font-weight-bold">{{ $t->tier_name }}</td>
                                <td>{{ $t->student_range }}</td>
                                <td>{{ is_null($t->max_branches) ? 'Unlimited' : $t->max_branches }}</td>
                                <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t->price_monthly) }}</td>
                                <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t->price_yearly) }}</td>
                                <td>
                                    <a href="{{ route('admin.school-tiers.toggle', $t->id) }}" class="badge {{ $t->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                        {{ $t->is_active ? 'Active' : 'Inactive' }}
                                    </a>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-white" onclick='editTier(@json($t))'><i class="tio-edit"></i></button>
                                    <a href="{{ route('admin.school-tiers.delete', $t->id) }}" class="btn btn-sm btn-white text-danger" onclick="return confirm('Delete this tier?')"><i class="tio-delete"></i></a>
                                </td>
                            </tr>
                        @empty<tr><td colspan="7" class="text-center text-muted py-4">No tiers defined yet.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Tiers gate the School module by student count (and branch count). The matching tier for a school is the one whose range covers its active student count.</small>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function editTier(t){
    document.getElementById('t_id').value = t.id;
    document.getElementById('t_name').value = t.tier_name;
    document.getElementById('t_min').value = t.min_students;
    document.getElementById('t_max').value = t.max_students ?? '';
    document.getElementById('t_branches').value = t.max_branches ?? '';
    document.getElementById('t_pm').value = t.price_monthly;
    document.getElementById('t_py').value = t.price_yearly;
    document.getElementById('t_sort').value = t.sort_order;
    document.getElementById('t_active').checked = !!t.is_active;
    window.scrollTo({top:0, behavior:'smooth'});
}
function resetTier(){
    ['t_id','t_name','t_max','t_branches'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('t_min').value=0; document.getElementById('t_pm').value=0;
    document.getElementById('t_py').value=0; document.getElementById('t_sort').value=0;
    document.getElementById('t_active').checked=true;
}
</script>
@endpush
