@extends('layouts.vendor.app')
@section('title', 'Hostel Management')

@php $cur = fn($v) => \App\CentralLogics\Helpers::format_currency($v); @endphp

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-hotel" style="font-size:22px;"></i></span>
            Hostel Management
        </h1>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-blocks">Blocks</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-rooms">Rooms</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-alloc">Student Allocations</a></li>
    </ul>

    <div class="tab-content">

        {{-- ===== BLOCKS ===== --}}
        <div class="tab-pane fade show active" id="tab-blocks">
            <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Add Hostel Block</h6></div>
                <div class="card-body">
                    <form action="{{ route('vendor.school.hostel.block.store') }}" method="POST" class="form-row align-items-end">
                        @csrf
                        <div class="col-md-4 mb-2 mb-md-0"><label class="input-label">Block Name *</label>
                            <input name="name" class="form-control form-control-sm" placeholder="e.g. Sunrise Block" required></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Type *</label>
                            <select name="type" class="form-control form-control-sm js-select2-custom" required>
                                @foreach(\App\Models\SchoolHostelBlock::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select></div>
                        <div class="col-md-3 mb-2 mb-md-0"><label class="input-label">Warden Name</label>
                            <input name="warden_name" class="form-control form-control-sm"></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Warden Phone</label>
                            <input name="warden_phone" class="form-control form-control-sm"></div>
                        <div class="col-md-1">@if(hasPermission("hostel","add"))<button class="btn btn-sm btn--primary btn-block">Save</button>@endif</div>
                    </form>
                </div>
            </div>
            @if(hasPermission("hostel","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Block</th><th>Type</th><th>Warden</th><th>Occupied</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($blocks as $b)
                        <tr>
                            <td class="font-weight-bold">{{ $b->name }}</td>
                            <td><span class="badge badge-soft-info">{{ $b->typeLabel() }}</span></td>
                            <td>{{ $b->warden_name ?? '—' }}<br><small class="text-muted">{{ $b->warden_phone }}</small></td>
                            <td>{{ $b->allocations_count }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if(hasPermission("hostel","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.hostel.block.delete', $b->id) }}" onclick="return confirm('Delete this block, its rooms and allocations?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-4">No hostel blocks yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== ROOMS ===== --}}
        <div class="tab-pane fade" id="tab-rooms">
            <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Add Room</h6></div>
                <div class="card-body">
                    @if($blocks->isEmpty())
                        <div class="text-muted">Create a block first.</div>
                    @else
                    <form action="{{ route('vendor.school.hostel.room.store') }}" method="POST" class="form-row align-items-end">
                        @csrf
                        <div class="col-md-3 mb-2 mb-md-0"><label class="input-label">Block *</label>
                            <select name="school_hostel_block_id" class="form-control form-control-sm js-select2-custom" required>
                                @foreach($blocks as $b)<option value="{{ $b->id }}">{{ $b->name }} ({{ $b->typeLabel() }})</option>@endforeach
                            </select></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Room No *</label>
                            <input name="room_no" class="form-control form-control-sm" placeholder="e.g. 101" required></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Floor</label>
                            <input name="floor" class="form-control form-control-sm" placeholder="e.g. Ground"></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Capacity *</label>
                            <input type="number" name="capacity" class="form-control form-control-sm" min="1" value="2" required></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Monthly Rent *</label>
                            <input type="number" step="0.01" name="rent" class="form-control form-control-sm" min="0" value="0" required></div>
                        <div class="col-md-1">@if(hasPermission("hostel","add"))<button class="btn btn-sm btn--primary btn-block">Save</button>@endif</div>
                    </form>
                    @endif
                </div>
            </div>
            @if(hasPermission("hostel","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Block</th><th>Room</th><th>Floor</th><th>Occupancy</th><th class="text-right">Rent</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($rooms as $r)
                        <tr>
                            <td>{{ $r->block?->name ?? '—' }}</td>
                            <td class="font-weight-bold">{{ $r->room_no }}</td>
                            <td>{{ $r->floor ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $r->allocations_count >= $r->capacity ? 'badge-soft-danger' : 'badge-soft-success' }}">
                                    {{ $r->allocations_count }} / {{ $r->capacity }}
                                </span>
                            </td>
                            <td class="text-right">{{ $cur($r->rent) }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if(hasPermission("hostel","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.hostel.room.delete', $r->id) }}" onclick="return confirm('Delete this room and its allocations?')"><i class="tio-delete"></i> Delete</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="6" class="text-center text-muted py-4">No rooms yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

        {{-- ===== ALLOCATIONS ===== --}}
        <div class="tab-pane fade" id="tab-alloc">
            <div class="card mb-3"><div class="card-header py-2"><h6 class="mb-0">Allocate Student to Room</h6></div>
                <div class="card-body">
                    @if($rooms->isEmpty() || $students->isEmpty())
                        <div class="text-muted">Add rooms and admit students first.</div>
                    @else
                    <form action="{{ route('vendor.school.hostel.allocation.store') }}" method="POST" class="form-row align-items-end">
                        @csrf
                        <input type="hidden" name="school_hostel_block_id" id="alloc_block">
                        <div class="col-md-4 mb-2 mb-md-0"><label class="input-label">Student *</label>
                            <select name="student_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">Select student</option>
                                @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->name }} @if($s->admission_no)({{ $s->admission_no }})@endif</option>@endforeach
                            </select></div>
                        <div class="col-md-3 mb-2 mb-md-0"><label class="input-label">Room *</label>
                            <select name="school_hostel_room_id" id="alloc_room" class="form-control form-control-sm js-select2-custom" required onchange="syncRoom()">
                                <option value="">Select room</option>
                                @foreach($rooms as $r)
                                    <option value="{{ $r->id }}" data-block="{{ $r->school_hostel_block_id }}" data-rent="{{ $r->rent }}" {{ $r->allocations_count >= $r->capacity ? 'disabled' : '' }}>
                                        {{ $r->block?->name }} · Room {{ $r->room_no }} ({{ $r->allocations_count }}/{{ $r->capacity }}) — {{ $cur($r->rent) }}
                                    </option>
                                @endforeach
                            </select></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Allocated On</label>
                            <input type="date" name="allocated_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
                        <div class="col-md-2 mb-2 mb-md-0"><label class="input-label">Monthly Fee</label>
                            <input type="number" step="0.01" name="monthly_fee" id="alloc_fee" class="form-control form-control-sm" min="0" placeholder="Room rent"></div>
                        <div class="col-md-1">@if(hasPermission("hostel","add"))<button class="btn btn-sm btn--primary btn-block">Save</button>@endif</div>
                    </form>
                    @endif
                </div>
            </div>
            @if(hasPermission("hostel","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                    <thead class="thead-light"><tr><th>Student</th><th>Class</th><th>Block</th><th>Room</th><th>Since</th><th class="text-right">Fee/mo</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($allocations as $a)
                        <tr>
                            <td class="font-weight-bold">{{ $a->student?->name }}<br><small class="text-muted">{{ $a->student?->admission_no }}</small></td>
                            <td>{{ $a->student?->currentEnrollment?->schoolClass?->name }} {{ $a->student?->currentEnrollment?->section ? '- '.$a->student->currentEnrollment->section->name : '' }}</td>
                            <td>{{ $a->block?->name ?? '—' }}</td>
                            <td>{{ $a->room?->room_no ?? '—' }}</td>
                            <td>{{ $a->allocated_on?->format('d M Y') ?? '—' }}</td>
                            <td class="text-right">{{ $cur($a->monthly_fee) }}</td>
                            <td class="text-right">
                                <div class="dropdown sch-actions">
                                    <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if(hasPermission("hostel","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.hostel.allocation.delete', $a->id) }}" onclick="return confirm('Remove this allocation?')"><i class="tio-delete"></i> Vacate</a>@endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="7" class="text-center text-muted py-4">No students allocated yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div></div></div>@endif
        </div>

    </div>
</div>
@endsection

@push('script_2')
<script>
function syncRoom(){
    var sel = document.getElementById('alloc_room');
    var opt = sel.options[sel.selectedIndex];
    if(!opt) return;
    document.getElementById('alloc_block').value = opt.getAttribute('data-block') || '';
    var fee = document.getElementById('alloc_fee');
    if(fee && !fee.value) fee.value = opt.getAttribute('data-rent') || '';
}

// Keep the active tab after a save/delete reload.
$(function () {
    var KEY = 'hostelActiveTab';
    var saved = sessionStorage.getItem(KEY);
    if (saved && $('.nav-tabs a[href="' + saved + '"]').length) {
        $('.nav-tabs a[href="' + saved + '"]').tab('show');
    }
    $('.nav-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        sessionStorage.setItem(KEY, $(e.target).attr('href'));
    });
});
</script>
@endpush
