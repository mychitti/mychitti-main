@extends('layouts.vendor.app')
@section('title', 'Notice Board')

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0"><i class="tio-comment mr-1"></i> Notice Board</h1>
        @if(hasPermission("notices","add"))<a href="{{ route('vendor.school.notices.create') }}" class="btn btn-sm btn--primary"><i class="tio-add"></i> New Notice</a>@endif
    </div>

    @if(hasPermission("notices","view"))<div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4 mb-2 mb-md-0"><label class="input-label mb-1">Audience</label>
                <select name="audience" class="form-control form-control-sm js-select2-custom" onchange="this.form.submit()">
                    <option value="">All audiences</option>
                    @foreach($audiences as $k => $v)<option value="{{ $k }}" @selected($audience===$k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-5 mb-2 mb-md-0"><label class="input-label mb-1">Search</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Title">
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn--primary"><i class="tio-search"></i> Filter</button>
                @if($audience || $search)<a href="{{ route('vendor.school.notices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>@endif
            </div>
        </form>
    </div></div>@endif

    @if(hasPermission("notices","view"))<div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
            <thead class="thead-light"><tr>
                <th>Title</th><th>Audience</th><th>Scope</th><th>Date</th><th>Status</th><th>By</th><th class="text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($notices as $n)
                <tr>
                    <td class="font-weight-bold">
                        @if($n->is_pinned)<i class="tio-bookmarked text-warning mr-1" title="Pinned"></i>@endif{{ $n->title }}
                    </td>
                    <td><span class="badge badge-soft-info">{{ $n->audienceLabel() }}</span></td>
                    <td>
                        @if($n->branch_id)<span class="badge badge-soft-warning">{{ $n->branch?->name ?? 'Branch' }}</span>
                        @else<span class="badge badge-soft-success">All Branches</span>@endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($n->notice_date)->format('d/m/Y') }}</td>
                    <td>
                        @if($n->is_published)<span class="badge badge-soft-success">Published</span>
                        @else<span class="badge badge-soft-warning">Draft</span>@endif
                    </td>
                    <td><small class="text-muted">{{ $n->created_by ?? '—' }}</small></td>
                    <td class="text-right">
                        <div class="dropdown sch-actions">
                            <button class="btn btn-actions" type="button" data-toggle="dropdown" data-boundary="viewport"><i class="fa fa-bars"></i></button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if(hasPermission("notices","edit"))<a class="dropdown-item" href="{{ route('vendor.school.notices.toggle', $n->id) }}">
                                    <i class="tio-{{ $n->is_published ? 'visible-off' : 'visible' }}"></i> {{ $n->is_published ? 'Unpublish' : 'Publish' }}
                                </a>@endif
                                @if(hasPermission("notices","edit"))<a class="dropdown-item" href="{{ route('vendor.school.notices.edit', $n->id) }}"><i class="tio-edit"></i> Edit</a>@endif
                                <div class="dropdown-divider"></div>
                                @if(hasPermission("notices","delete"))<a class="dropdown-item text-danger" href="{{ route('vendor.school.notices.delete', $n->id) }}" onclick="return confirm('Delete this notice?')"><i class="tio-delete"></i> Delete</a>@endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-5">No notices yet.</td></tr>@endforelse
            </tbody>
        </table>
    </div></div></div>@endif
    @if(hasPermission("notices","view") && count($notices))<div class="mt-3 px-2">{!! $notices->links() !!}</div>@endif
</div>
@endsection
