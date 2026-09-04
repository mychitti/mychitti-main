@extends('layouts.admin.app')

@section('title', $project->name . ' — ' . translate('API Endpoints'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin-views.api-endpoints._styles')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-6 col-12">
                    <h1 class="page-header-title mb-1">
                        <span class="page-header-icon"><i class="tio-code"></i></span>
                        <span>{{ $project->name }}</span>
                    </h1>
                    <div class="api-help">
                        <a href="{{ route('admin.api-endpoints.index') }}">{{ translate('API Endpoints') }}</a>
                        / {{ $project->name }}
                        @if ($project->base_url)
                            · <span class="api-path">{{ $project->base_url }}</span>
                        @endif
                        @if ($project->version)
                            · {{ $project->version }}
                        @endif
                        · {{ count($endpoints) }} {{ translate('endpoints') }}
                    </div>
                </div>
                <div class="col-md-6 col-12 text-md-right">
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                        data-target="#importModal">
                        <i class="tio-upload"></i> {{ translate('Import Excel') }}
                    </button>
                    <a href="{{ route('admin.api-endpoints.export', $project->id) }}" class="btn btn-outline-success">
                        <i class="tio-download"></i> {{ translate('Export Excel') }}
                    </a>
                    <button type="button" class="btn btn--primary" data-toggle="modal" data-target="#addEndpointModal">
                        <i class="tio-add"></i> {{ translate('Add Endpoint') }}
                    </button>
                </div>
            </div>
        </div>

        @if ($project->description)
            <div class="alert alert-soft-info">{{ $project->description }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body py-3">
                <form class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ $search }}" class="form-control"
                                placeholder="{{ translate('Search endpoint, name, folder or usage note') }}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="method" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All Methods') }}</option>
                            @foreach (\App\Models\ApiEndpoint::METHODS as $m)
                                <option value="{{ $m }}" {{ $method == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.api-endpoints.show', $project->id) }}"
                            class="btn btn-outline-secondary w-100">{{ translate('Reset') }}</a>
                    </div>
                </form>
            </div>
        </div>

        @php $currentFolder = '__none__'; @endphp
        @forelse ($endpoints as $endpoint)
            @if ($endpoint->folder !== $currentFolder)
                @php $currentFolder = $endpoint->folder; @endphp
                <div class="api-help text-uppercase font-weight-bold mt-3 mb-2">
                    <i class="tio-folder"></i> {{ $endpoint->folder ?: translate('Ungrouped') }}
                </div>
            @endif

            <div class="card mb-2">
                <div class="card-body p-2 d-flex align-items-center" style="gap:10px; cursor:pointer;"
                    data-toggle="collapse" data-target="#ep-{{ $endpoint->id }}">
                    <span class="api-method m-{{ $endpoint->method }}">{{ $endpoint->method }}</span>
                    <span class="api-path flex-grow-1">{{ $endpoint->endpoint }}</span>
                    @if ($endpoint->name)
                        <span class="api-help d-none d-md-inline">{{ Str::limit($endpoint->name, 40) }}</span>
                    @endif
                    @if (count($endpoint->image_list))
                        <span class="badge badge-soft-secondary"><i class="tio-image"></i>
                            {{ count($endpoint->image_list) }}</span>
                    @endif
                    @if ($endpoint->auth_type)
                        <span class="badge badge-soft-secondary">{{ $endpoint->auth_type }}</span>
                    @endif
                    @if ($endpoint->status_code)
                        <span class="badge badge-soft-info">{{ $endpoint->status_code }}</span>
                    @endif
                </div>

                <div class="collapse" id="ep-{{ $endpoint->id }}">
                    <div class="border-top p-3">
                        @if ($endpoint->description)
                            <p class="mb-3">{!! nl2br(e($endpoint->description)) !!}</p>
                        @endif

                        @if ($endpoint->usage_note)
                            <div class="api-note mb-3">
                                <strong>{{ translate('Used in') }}:</strong>
                                {!! nl2br(e($endpoint->usage_note)) !!}
                            </div>
                        @endif

                        <div class="row">
                            @if (count($endpoint->param_list))
                                <div class="col-md-6 mb-3">
                                    <div class="api-help mb-1">{{ translate('Params') }}</div>
                                    <table class="api-kv-table">
                                        <thead>
                                            <tr>
                                                <th>{{ translate('Key') }}</th>
                                                <th>{{ translate('Value') }}</th>
                                                <th>{{ translate('Note') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($endpoint->param_list as $row)
                                                <tr>
                                                    <td>{{ $row['key'] }}</td>
                                                    <td>{{ $row['value'] }}</td>
                                                    <td class="api-help">{{ $row['note'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if (count($endpoint->header_list))
                                <div class="col-md-6 mb-3">
                                    <div class="api-help mb-1">{{ translate('Headers') }}</div>
                                    <table class="api-kv-table">
                                        <thead>
                                            <tr>
                                                <th>{{ translate('Key') }}</th>
                                                <th>{{ translate('Value') }}</th>
                                                <th>{{ translate('Note') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($endpoint->header_list as $row)
                                                <tr>
                                                    <td>{{ $row['key'] }}</td>
                                                    <td>{{ $row['value'] }}</td>
                                                    <td class="api-help">{{ $row['note'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        @if ($endpoint->request_body)
                            <div class="api-help mb-1">{{ translate('Request Body') }}</div>
                            <pre class="api-code mb-3">{{ $endpoint->request_body }}</pre>
                        @endif

                        @if ($endpoint->response_sample)
                            <div class="api-help mb-1">{{ translate('Response') }}</div>
                            <pre class="api-code mb-3">{{ $endpoint->response_sample }}</pre>
                        @endif

                        @if (count($endpoint->image_list))
                            <div class="api-help mb-1">{{ translate('Screenshots') }}</div>
                            <div class="d-flex flex-wrap mb-3" style="gap:8px;">
                                @foreach ($endpoint->image_list as $img)
                                    <div class="text-center">
                                        <a href="{{ asset('storage/app/public/api_endpoints/' . $img['stored_name']) }}"
                                            target="_blank" rel="noopener">
                                            <img src="{{ asset('storage/app/public/api_endpoints/' . $img['stored_name']) }}"
                                                class="api-shot" alt="{{ $img['file_name'] ?? '' }}">
                                        </a>
                                        <form
                                            action="{{ route('admin.api-endpoints.endpoints.image-delete', $endpoint->id) }}"
                                            method="post">
                                            @csrf
                                            <input type="hidden" name="stored_name"
                                                value="{{ $img['stored_name'] }}">
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0"
                                                style="font-size:11px;">{{ translate('remove') }}</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                data-target="#editEndpoint{{ $endpoint->id }}">
                                <i class="tio-edit"></i> {{ translate('Edit') }}
                            </button>
                            <a class="btn btn-sm btn-outline-danger form-alert" href="javascript:"
                                data-id="ep-del-{{ $endpoint->id }}"
                                data-message="{{ translate('Remove this endpoint?') }}">
                                <i class="tio-delete-outlined"></i> {{ translate('Remove') }}
                            </a>
                            <form action="{{ route('admin.api-endpoints.endpoints.delete', $endpoint->id) }}"
                                method="post" id="ep-del-{{ $endpoint->id }}">
                                @csrf @method('delete')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center py-5">
                    <p class="mb-2 api-help">
                        {{ $search || $method ? translate('No endpoint matches that filter.') : translate('No endpoints in this project yet.') }}
                    </p>
                    @if (!$search && !$method)
                        <button type="button" class="btn btn--primary btn-sm" data-toggle="modal"
                            data-target="#addEndpointModal">{{ translate('Add one') }}</button>
                        <span class="api-help mx-2">{{ translate('or') }}</span>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal"
                            data-target="#importModal">{{ translate('import an Excel sheet') }}</button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- Add endpoint --}}
    <div class="modal fade" id="addEndpointModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form action="{{ route('admin.api-endpoints.endpoints.store', $project->id) }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Add Endpoint') }} — {{ $project->name }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('admin-views.api-endpoints._endpoint_form', ['endpoint' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Add Endpoint') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit endpoint --}}
    @foreach ($endpoints as $endpoint)
        <div class="modal fade" id="editEndpoint{{ $endpoint->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <form action="{{ route('admin.api-endpoints.endpoints.update', $endpoint->id) }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Edit Endpoint') }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            @include('admin-views.api-endpoints._endpoint_form', ['endpoint' => $endpoint])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Save Endpoint') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Import Excel --}}
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.api-endpoints.import', $project->id) }}" method="post"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Import Endpoints from Excel') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Excel file') }} <span
                                    class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="replace" value="1"
                                id="importReplace">
                            <label class="form-check-label"
                                for="importReplace">{{ translate('Replace the existing endpoints') }}</label>
                        </div>
                        <div class="alert alert-soft-info mb-0">
                            <strong>{{ translate('Expected columns') }}:</strong>
                            <code>Folder, Name, Method, Endpoint, Auth, Description, Params, Headers, Request Body, Response Sample, Status Code, Usage Note</code>
                            <div class="api-help mt-2">
                                {{ translate('Params and Headers are one "key = value" per line, with an optional "# note" after the value. Export first to get the exact layout.') }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Import') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        // Repeatable key/value rows for params and headers.
        $(document).on('click', '.kv-add', function() {
            var $wrap = $('#' + $(this).data('target'));
            var $row = $wrap.find('.kv-row').first().clone();
            $row.find('input').val('');
            $wrap.append($row);
        });

        $(document).on('click', '.kv-remove', function() {
            var $wrap = $(this).closest('.kv-wrap');
            if ($wrap.find('.kv-row').length > 1) {
                $(this).closest('.kv-row').remove();
            } else {
                $(this).closest('.kv-row').find('input').val('');
            }
        });
    </script>
@endpush
