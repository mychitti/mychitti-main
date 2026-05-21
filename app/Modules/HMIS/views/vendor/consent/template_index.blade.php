@extends('layouts.vendor.app')
@section('title', 'Consent Form Templates')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-document-text" style="font-size:22px;"></i></span>
            Consent Form Templates
        </h1>
        @if (hasPermission('consent_template', 'add'))
        <a href="{{ route('vendor.consent.template.create') }}" class="btn btn-sm btn--primary">
            <i class="tio-add"></i> New Template
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(hasPermission('consent_template', 'list'))
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th style="width:100px;">Status</th>
                            <th style="width:140px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $t)
                        <tr>
                            <td class="text-muted" style="font-size:12px;">{{ $loop->iteration }}</td>
                            <td>{{ $t->title }}</td>
                            <td>
                                <span class="badge badge-soft-{{ $t->is_active ? 'success' : 'secondary' }}">
                                    {{ $t->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if (hasPermission('consent_template', 'edit'))
                                <a href="{{ route('vendor.consent.template.edit', $t->id) }}"
                                   class="btn btn-xs btn-outline-primary mr-1">
                                    <i class="tio-edit"></i> Edit
                                </a>
                                @endif
                                @if (hasPermission('consent_template', 'delete'))
                                <form action="{{ route('vendor.consent.template.destroy', $t->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this template?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="tio-delete-outlined"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No templates yet.
                                <a href="{{ route('vendor.consent.template.create') }}">Create one</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer">{{ $templates->links() }}</div>
        @endif
    </div>
    @endif
</div>
@endsection
