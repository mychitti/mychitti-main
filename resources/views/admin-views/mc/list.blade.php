@extends('layouts.admin.app')

@section('title', 'Marketing Campaigns')

@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-layers-outlined" style="font-size:1.4rem"></i>
                </span>
                <span>Marketing Campaigns</span>
            </h1>
            <a href="{{ route('admin.mc.create') }}" class="btn btn--primary">
                <i class="tio-add-circle"></i> New Campaign
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="d-flex flex-wrap gap-2 align-items-center" method="GET">
                <input type="text" name="search" class="form-control" style="max-width:240px"
                    placeholder="Search campaigns..." value="{{ request('search') }}">
                <select name="status" class="form-control" style="max-width:160px">
                    <option value="">All statuses</option>
                    @foreach(['draft','active','ended','cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="btn btn--secondary"><i class="tio-search"></i> Filter</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('admin.mc.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header py-2 border-0 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                All Campaigns
                <span class="badge badge-soft-dark ml-2">{{ $campaigns->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Draw</th>
                            <th>Prize</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $i => $c)
                        <tr>
                            <td>{{ $campaigns->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <small class="text-muted">{{ $c->slug }}</small>
                            </td>
                            <td>
                                @php
                                    $badges = ['draft'=>'secondary','active'=>'success','ended'=>'info','cancelled'=>'danger'];
                                @endphp
                                <span class="badge badge-soft-{{ $badges[$c->status] ?? 'dark' }}">
                                    {{ ucfirst($c->status) }}
                                </span>
                            </td>
                            <td>{{ $c->start_date ? $c->start_date->format('d M Y') : '—' }}</td>
                            <td>{{ $c->end_date   ? $c->end_date->format('d M Y')   : '—' }}</td>
                            <td>{{ $c->draw_date  ? $c->draw_date->format('d M Y')  : '—' }}</td>
                            <td>{{ $c->prize_title ?? '—' }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('admin.mc.edit', $c->id) }}"
                                        class="btn btn-sm btn--primary" title="Edit">
                                        <i class="tio-edit"></i>
                                    </a>
                                    {{-- Status quick-change --}}
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                            <i class="tio-settings"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @foreach(['draft','active','ended','cancelled'] as $s)
                                                @if($s !== $c->status)
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.mc.status', [$c->id, $s]) }}">
                                                    Set {{ ucfirst($s) }}
                                                </a>
                                                @endif
                                            @endforeach
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger"
                                                href="{{ route('admin.mc.destroy', $c->id) }}"
                                                onclick="return confirm('Delete this campaign?')"
                                                data-method="DELETE"
                                                data-token="{{ csrf_token() }}">
                                                <i class="tio-delete-outlined mr-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No campaigns found. <a href="{{ route('admin.mc.create') }}">Create one</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($campaigns->hasPages())
        <div class="card-footer">
            {{ $campaigns->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Delete via form helper --}}
<script>
document.querySelectorAll('[data-method="DELETE"]').forEach(el => {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Delete this campaign? This cannot be undone.')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.href;
        form.innerHTML = `<input type="hidden" name="_token" value="${this.dataset.token}">
                          <input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    });
});
</script>
@endsection
