@extends('layouts.admin.app')

@section('title', translate('API Endpoints'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin-views.api-endpoints._styles')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-6 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon"><i class="tio-code"></i></span>
                        <span>{{ translate('API Endpoints') }}</span>
                    </h1>
                    <p class="api-help mb-0">
                        {{ translate('One project per app — User App, Vendor App, Admin Panel — each with its own endpoints.') }}
                    </p>
                </div>
                <div class="col-md-6 col-12 text-md-right">
                    <a href="{{ route('admin.api-endpoints.all') }}" class="btn btn-outline-secondary">
                        <i class="tio-search"></i> {{ translate('Search All Endpoints') }}
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                        data-target="#postmanModal">
                        <i class="tio-cloud-upload"></i> {{ translate('Postman Collection') }}
                    </button>
                    <button type="button" class="btn btn--primary" data-toggle="modal" data-target="#addProjectModal">
                        <i class="tio-add"></i> {{ translate('New Project') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-body api-stat" style="background-color:#1a73e814;">
                    <h3>{{ $counts['projects'] }}</h3><small class="api-help">{{ translate('Projects') }}</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-body api-stat" style="background-color:#00b89421;">
                    <h3>{{ $counts['endpoints'] }}</h3><small class="api-help">{{ translate('Endpoints') }}</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2 border-0">
                <form class="w-100">
                    <div class="input-group input--group" style="max-width:420px;">
                        <input type="search" name="search" value="{{ $search }}" class="form-control"
                            placeholder="{{ translate('Search projects') }}">
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                </form>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('SL') }}</th>
                            <th class="border-0">{{ translate('Project') }}</th>
                            <th class="border-0">{{ translate('Base URL') }}</th>
                            <th class="border-0 text-center">{{ translate('Version') }}</th>
                            <th class="border-0 text-center">{{ translate('Endpoints') }}</th>
                            <th class="border-0 text-center">{{ translate('Status') }}</th>
                            <th class="border-0 text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $key => $project)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <span class="api-project-dot"
                                        style="background: {{ $project->color ?? '#1a73e8' }};"></span>
                                    <a href="{{ route('admin.api-endpoints.show', $project->id) }}"
                                        class="ml-1 font-weight-bold">{{ $project->name }}</a>
                                    @if ($project->description)
                                        <div class="api-help">{{ Str::limit($project->description, 80) }}</div>
                                    @endif
                                </td>
                                <td class="api-path">{{ $project->base_url ?: '—' }}</td>
                                <td class="text-center">{{ $project->version ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-soft-dark">{{ $project->endpoints_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($project->status)
                                        <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                    @else
                                        <span class="badge badge-soft-secondary">{{ translate('Hidden') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.api-endpoints.show', $project->id) }}"
                                            title="{{ translate('Open') }}"><i class="tio-visible"></i></a>
                                        <a class="btn action-btn btn--warning btn-outline-warning" href="javascript:"
                                            data-toggle="modal" data-target="#editProject{{ $project->id }}"
                                            title="{{ translate('Edit') }}"><i class="tio-edit"></i></a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert"
                                            href="javascript:" data-id="proj-{{ $project->id }}"
                                            data-message="{{ translate('Delete this project and all of its endpoints?') }}"
                                            title="{{ translate('Delete') }}"><i class="tio-delete-outlined"></i></a>
                                        <form action="{{ route('admin.api-endpoints.projects.delete', $project->id) }}"
                                            method="post" id="proj-{{ $project->id }}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <p class="mb-2">{{ translate('No API projects yet.') }}</p>
                                    <button type="button" class="btn btn--primary btn-sm" data-toggle="modal"
                                        data-target="#addProjectModal">{{ translate('Create one') }}</button>
                                    <span class="api-help mx-2">{{ translate('or') }}</span>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                        data-target="#postmanModal">{{ translate('upload a Postman collection') }}</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add project --}}
    <div class="modal fade" id="addProjectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.api-endpoints.projects.store') }}" method="post">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('New API Project') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                placeholder="{{ translate('User App') }}">
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('Base URL') }}</label>
                            <input type="text" name="base_url" class="form-control"
                                placeholder="https://api.mychitti.net">
                        </div>
                        <div class="row">
                            <div class="col-6 form-group">
                                <label class="input-label">{{ translate('Version') }}</label>
                                <input type="text" name="version" class="form-control" placeholder="v1">
                            </div>
                            <div class="col-6 form-group">
                                <label class="input-label">{{ translate('Colour') }}</label>
                                <input type="color" name="color" class="form-control" value="#1a73e8"
                                    style="height:40px; padding:4px;">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="input-label">{{ translate('Description') }}</label>
                            <textarea name="description" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Create Project') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Postman --}}
    <div class="modal fade" id="postmanModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.api-endpoints.postman') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Upload Postman Collection') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="input-label">{{ translate('Collection JSON') }} <span
                                    class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".json" required>
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{ translate('Import into') }}</label>
                            <select name="project_id" class="form-control">
                                <option value="">{{ translate('Create a new project from the collection name') }}
                                </option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="replace" value="1"
                                id="postmanReplace">
                            <label class="form-check-label"
                                for="postmanReplace">{{ translate("Replace the project's existing endpoints") }}</label>
                        </div>
                        <div class="alert alert-soft-info mb-0 api-help">
                            {{ translate('In Postman: right-click the collection → Export → Collection v2.1 → Save. Folders, methods, URLs, auth, query and path params, headers, request bodies and saved example responses are all read.') }}
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

    {{-- Edit project --}}
    @foreach ($projects as $project)
        <div class="modal fade" id="editProject{{ $project->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ route('admin.api-endpoints.projects.update', $project->id) }}" method="post">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Edit Project') }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                    value="{{ $project->name }}">
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Base URL') }}</label>
                                <input type="text" name="base_url" class="form-control"
                                    value="{{ $project->base_url }}">
                            </div>
                            <div class="row">
                                <div class="col-6 form-group">
                                    <label class="input-label">{{ translate('Version') }}</label>
                                    <input type="text" name="version" class="form-control"
                                        value="{{ $project->version }}">
                                </div>
                                <div class="col-6 form-group">
                                    <label class="input-label">{{ translate('Colour') }}</label>
                                    <input type="color" name="color" class="form-control"
                                        value="{{ $project->color ?: '#1a73e8' }}" style="height:40px; padding:4px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="input-label">{{ translate('Description') }}</label>
                                <textarea name="description" rows="2" class="form-control">{{ $project->description }}</textarea>
                            </div>
                            <div class="form-group form-check mb-0">
                                <input type="checkbox" class="form-check-input" name="status" value="1"
                                    id="projStatus{{ $project->id }}" {{ $project->status ? 'checked' : '' }}>
                                <label class="form-check-label"
                                    for="projStatus{{ $project->id }}">{{ translate('Active') }}</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
