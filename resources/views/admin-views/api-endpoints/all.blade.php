@extends('layouts.admin.app')

@section('title', translate('All API Endpoints'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin-views.api-endpoints._styles')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-8 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-search"></i></span>
                        <span>{{ translate('All API Endpoints') }}
                            <span class="badge badge-soft-dark ml-2">{{ $endpoints->total() }}</span>
                        </span>
                    </h1>
                    <p class="api-help mb-0">
                        {{ translate('Every endpoint across every project — search by path or by where it is used.') }}
                    </p>
                </div>
                <div class="col-md-4 col-12 text-md-right">
                    <a href="{{ route('admin.api-endpoints.index') }}" class="btn btn-outline-secondary">
                        <i class="tio-back-ui"></i> {{ translate('Projects') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="row g-2 w-100 align-items-end">
                    <div class="col-md-5">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search path, name, description or usage note') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="project" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Projects') }}</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" {{ $project == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="method" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Methods') }}</option>
                            @foreach (\App\Models\ApiEndpoint::METHODS as $m)
                                <option value="{{ $m }}" {{ $method == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.api-endpoints.all') }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('Project') }}</th>
                            <th class="border-0">{{ translate('Method') }}</th>
                            <th class="border-0">{{ translate('Endpoint') }}</th>
                            <th class="border-0">{{ translate('Used In') }}</th>
                            <th class="border-0 text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($endpoints as $endpoint)
                            <tr>
                                <td>
                                    <span class="api-project-dot"
                                        style="background: {{ $endpoint->project->color ?? '#1a73e8' }};"></span>
                                    <span class="ml-1">{{ $endpoint->project->name ?? '—' }}</span>
                                    @if ($endpoint->folder)
                                        <div class="api-help">{{ $endpoint->folder }}</div>
                                    @endif
                                </td>
                                <td><span class="api-method m-{{ $endpoint->method }}">{{ $endpoint->method }}</span>
                                </td>
                                <td>
                                    <div class="api-path">{{ $endpoint->endpoint }}</div>
                                    @if ($endpoint->name)
                                        <div class="api-help">{{ $endpoint->name }}</div>
                                    @endif
                                </td>
                                <td class="api-help">{{ Str::limit($endpoint->usage_note, 90) ?: '—' }}</td>
                                <td class="text-center">
                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                        href="{{ route('admin.api-endpoints.show', $endpoint->project_id) }}#ep-{{ $endpoint->id }}"
                                        title="{{ translate('Open in project') }}"><i class="tio-visible"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 api-help">
                                    {{ translate('No endpoints match.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($endpoints) !== 0)
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        {!! $endpoints->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
