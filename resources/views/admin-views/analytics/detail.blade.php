@extends('layouts.admin.app')

@section('title', 'Analytics - ' . $entityName)

@section('content')
    <div class="content container-fluid">
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
                    <i class="tio-back-ui"></i>
                </a>
                <span>{{ ucfirst(str_replace('_', ' ', $type)) }} - {{ $entityName }}</span>
            </h1>
        </div>
 
        {{-- Date Filter --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="form-label mb-1">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('admin.analytics.detail', ['type' => $type, 'id' => $id]) }}"
                            class="btn btn-secondary btn-sm">Reset</a>
                    </div>
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
                                <th>User</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $key => $log)
                                <tr>
                                    <td>{{ $key + $logs->firstItem() }}</td>
                                    <td>
                                        @if ($log->user_id)
                                            {{ $log->f_name . ' ' . $log->l_name }}
                                        @else
                                            <span class="text-muted">Guest</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->phone ?? '-' }}</td>
                                    <td>{{ $log->email ?? '-' }}</td>
                                    <td><code>{{ $log->ip }}</code></td>
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No logs found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-0 pt-0">
                <div class="d-flex justify-content-center justify-content-sm-end">
                    {!! $logs->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
