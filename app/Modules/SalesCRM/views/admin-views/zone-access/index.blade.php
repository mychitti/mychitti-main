@extends('layouts.admin.app')
@section('title', translate('CRM Zone Access'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">{{ translate('CRM Zone Access') }}</h1>
                <ol class="breadcrumb breadcrumb-no-gutter">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ translate('messages.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ translate('Zone Access') }}</li>
                </ol>
            </div>
        </div>
    </div>

    @if($admins->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                {{ translate('No admins with Sales CRM role found. Create a role with "Sales & Marketing CRM" permission and assign it to an employee first.') }}
            </div>
        </div>
    @else
    <div class="card">
        <div class="card-header py-3">
            <h5 class="mb-0">{{ translate('Assign Zones per CRM Admin') }}</h5>
            <small class="text-muted">{{ translate('Leave all unchecked to allow access to all zones.') }}</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle" style="font-size:.85rem">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width:180px">{{ translate('Admin') }}</th>
                            <th style="min-width:120px">{{ translate('Role') }}</th>
                            @foreach($zones as $zone)
                                <th class="text-center" style="min-width:90px">{{ $zone->name }}</th>
                            @endforeach
                            <th class="text-center" style="min-width:80px">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admins as $admin)
                        @php $assignedIds = $admin->salesCrmZones->pluck('id')->toArray(); @endphp
                        <tr>
                            <form action="{{ route('admin.sales-crm.zone-access.update', $admin->id) }}" method="POST">
                            @csrf
                            <td>
                                <div class="font-weight-bold">{{ $admin->f_name }} {{ $admin->l_name }}</div>
                                <small class="text-muted">{{ $admin->email }}</small>
                            </td>
                            <td>{{ $admin->role?->name ?? '—' }}</td>
                            @foreach($zones as $zone)
                                <td class="text-center">
                                    <input type="checkbox" name="zone_ids[]" value="{{ $zone->id }}"
                                        {{ in_array($zone->id, $assignedIds) ? 'checked' : '' }}>
                                </td>
                            @endforeach
                            <td class="text-center">
                                <button type="submit" class="btn btn-primary btn-xs px-3">{{ translate('Save') }}</button>
                            </td>
                            </form>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer py-2">
            <small class="text-muted"><i class="tio-info-outined mr-1"></i>{{ translate('If no zones are checked for an admin, they can see data from all zones.') }}</small>
        </div>
    </div>
    @endif
</div>
@endsection
