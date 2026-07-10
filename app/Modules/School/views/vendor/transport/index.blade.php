@extends('layouts.vendor.app')
@section('title', translate('Transport Management'))

@section('content')
<div class="content container-fluid school-page">
    @include('school::vendor.partials.theme')

    <div class="page-header">
        <h1 class="page-header-title mb-0">
            <span class="page-header-icon"><i class="tio-delivery" style="font-size:22px;"></i></span>
            {{ translate('Transport Management') }}
        </h1>
    </div> 

    <!-- Navigation Tabs --> 
    <ul class="nav nav-tabs mb-3" id="transportTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-routes">{{ translate('Routes') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-stops">{{ translate('Stops & Fares') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-vehicles">{{ translate('Vehicles & Drivers') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-allocations">{{ translate('Student Allocations') }}</a>
        </li>
    </ul>

    <div class="tab-content">
        
        <!-- ===== ROUTES TAB ===== -->
        <div class="tab-pane fade show active" id="tab-routes">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h5 class="card-header-title mb-0">{{ translate('Create New Route') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.school.transport.route.store') }}" method="POST" class="form-row align-items-end">
                        @csrf
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Route Name') }} <span class="text-danger">*</span></label>
                            <input name="name" class="form-control form-control-sm" placeholder="{{ translate('e.g. Route A (North Side)') }}" required>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Start Point') }}</label>
                            <input name="start_point" class="form-control form-control-sm" placeholder="{{ translate('e.g. School Campus') }}">
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('End Point') }}</label>
                            <input name="end_point" class="form-control form-control-sm" placeholder="{{ translate('e.g. Sector-5 Ring Road') }}">
                        </div>
                        <div class="col-md-2">
                            @if(hasPermission("transport","add"))<button type="submit" class="btn btn-sm btn--primary btn-block"><i class="tio-add"></i> {{ translate('Add Route') }}</button>@endif
                        </div>
                    </form>
                </div>
            </div>

            @if(hasPermission("transport","view"))<div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Route ID') }}</th>
                                    <th>{{ translate('Route Name') }}</th>
                                    <th>{{ translate('Start Point') }}</th>
                                    <th>{{ translate('End Point') }}</th>
                                    <th class="text-right">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($routes as $route)
                                    <tr>
                                        <td>#{{ $route->id }}</td>
                                        <td class="font-weight-bold text-dark">{{ $route->name }}</td>
                                        <td>{{ $route->start_point ?? '—' }}</td>
                                        <td>{{ $route->end_point ?? '—' }}</td>
                                        <td class="text-right">
                                            @if(hasPermission("transport","delete"))<a href="{{ route('vendor.school.transport.route.delete', $route->id) }}" 
                                               class="btn btn-outline-danger btn-xs" 
                                               onclick="return confirm('{{ translate('Delete this route? All stops and student allocations for this route will be deleted.') }}')">
                                                <i class="tio-delete"></i> {{ translate('Delete') }}
                                            </a>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ translate('No routes added yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>@endif
        </div>

        <!-- ===== STOPS TAB ===== -->
        <div class="tab-pane fade" id="tab-stops">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h5 class="card-header-title mb-0">{{ translate('Add Stop to Route') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.school.transport.stop.store') }}" method="POST" class="form-row align-items-end">
                        @csrf
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Route') }} <span class="text-danger">*</span></label>
                            <select name="school_transport_route_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">{{ translate('Select Route') }}</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Stop Name') }} <span class="text-danger">*</span></label>
                            <input name="name" class="form-control form-control-sm" placeholder="{{ translate('e.g. Clock Tower Crossing') }}" required>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Monthly Fare') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="fare" class="form-control form-control-sm" placeholder="{{ translate('e.g. 500.00') }}" required>
                        </div>
                        <div class="col-md-2">
                            @if(hasPermission("transport","add"))<button type="submit" class="btn btn-sm btn--primary btn-block"><i class="tio-add"></i> {{ translate('Add Stop') }}</button>@endif
                        </div>
                    </form>
                </div>
            </div>

            @if(hasPermission("transport","view"))<div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Route') }}</th>
                                    <th>{{ translate('Stop Name') }}</th>
                                    <th>{{ translate('Monthly Fare') }}</th>
                                    <th class="text-right">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stops as $stop)
                                    <tr>
                                        <td>
                                            <span class="badge badge-soft-info">{{ $stop->route?->name ?? '—' }}</span>
                                        </td>
                                        <td class="font-weight-bold text-dark">{{ $stop->name }}</td>
                                        <td class="font-weight-bold text-success">{{ number_format($stop->fare, 2) }}</td>
                                        <td class="text-right">
                                            @if(hasPermission("transport","delete"))<a href="{{ route('vendor.school.transport.stop.delete', $stop->id) }}" 
                                               class="btn btn-outline-danger btn-xs" 
                                               onclick="return confirm('{{ translate('Delete this stop? Student allocations pointing to this stop will be deleted.') }}')">
                                                <i class="tio-delete"></i> {{ translate('Delete') }}
                                            </a>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">{{ translate('No stops added yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>@endif
        </div>

        <!-- ===== VEHICLES TAB ===== -->
        <div class="tab-pane fade" id="tab-vehicles">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h5 class="card-header-title mb-0">{{ translate('Register Vehicle & Driver') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.school.transport.vehicle.store') }}" method="POST" class="form-row">
                        @csrf
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Vehicle Number') }} <span class="text-danger">*</span></label>
                            <input name="vehicle_no" class="form-control form-control-sm" placeholder="{{ translate('e.g. DL-1CA-1234') }}" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Vehicle Model') }}</label>
                            <input name="vehicle_model" class="form-control form-control-sm" placeholder="{{ translate('e.g. Tata Starbus 40-Seater') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Driver Name') }}</label>
                            <input name="driver_name" class="form-control form-control-sm" placeholder="{{ translate('Driver full name') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Driver Phone') }}</label>
                            <input name="driver_phone" class="form-control form-control-sm" placeholder="{{ translate('Driver phone no.') }}">
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Driver License No') }}</label>
                            <input name="driver_license" class="form-control form-control-sm" placeholder="{{ translate('License no.') }}">
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Passenger Capacity') }}</label>
                            <input type="number" min="1" name="capacity" class="form-control form-control-sm" placeholder="{{ translate('e.g. 40') }}">
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="input-label">{{ translate('Status') }} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control form-control-sm" required>
                                <option value="1">{{ translate('Active / Operational') }}</option>
                                <option value="0">{{ translate('Inactive / Under Maintenance') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 align-self-end">
                            @if(hasPermission("transport","add"))<button type="submit" class="btn btn-sm btn--primary btn-block"><i class="tio-add"></i> {{ translate('Add Vehicle') }}</button>@endif
                        </div>
                    </form>
                </div>
            </div>

            @if(hasPermission("transport","view"))<div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Vehicle No') }}</th>
                                    <th>{{ translate('Model') }}</th>
                                    <th>{{ translate('Driver Details') }}</th>
                                    <th>{{ translate('Capacity') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th class="text-right">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicles as $vehicle)
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ $vehicle->vehicle_no }}</td>
                                        <td>{{ $vehicle->vehicle_model ?? '—' }}</td>
                                        <td>
                                            @if($vehicle->driver_name)
                                                <div class="font-weight-bold text-xs">{{ $vehicle->driver_name }}</div>
                                                @if($vehicle->driver_phone)
                                                    <div class="text-xxs text-muted"><i class="tio-android-phone-video"></i> {{ $vehicle->driver_phone }}</div>
                                                @endif
                                                @if($vehicle->driver_license)
                                                    <div class="text-xxs text-muted"><i class="tio-document-text"></i> Lic: {{ $vehicle->driver_license }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $vehicle->capacity ?? '—' }}</td>
                                        <td>
                                            @if($vehicle->status === 1)
                                                <span class="badge badge-soft-success">{{ translate('Active') }}</span>
                                            @else
                                                <span class="badge badge-soft-danger">{{ translate('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if(hasPermission("transport","delete"))<a href="{{ route('vendor.school.transport.vehicle.delete', $vehicle->id) }}" 
                                               class="btn btn-outline-danger btn-xs" 
                                               onclick="return confirm('{{ translate('Delete this vehicle? Student allocations pointing to this vehicle will be deleted.') }}')">
                                                <i class="tio-delete"></i> {{ translate('Delete') }}
                                            </a>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">{{ translate('No vehicles registered yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>@endif
        </div>

        <!-- ===== ALLOCATIONS TAB ===== -->
        <div class="tab-pane fade" id="tab-allocations">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h5 class="card-header-title mb-0">{{ translate('Allocate Transport to Student') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.school.transport.allocation.store') }}" method="POST" class="form-row">
                        @csrf
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Student') }} <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">{{ translate('Select Student') }}</option>
                                @foreach($students as $st)
                                    <option value="{{ $st->id }}">
                                        {{ $st->name }} (Adm: {{ $st->admission_no }} 
                                        @if($st->currentEnrollment)
                                            - {{ $st->currentEnrollment->schoolClass?->name }}
                                        @endif)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Route') }} <span class="text-danger">*</span></label>
                            <select name="school_transport_route_id" id="alloc_route_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">{{ translate('Select Route') }}</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Stop') }} <span class="text-danger">*</span></label>
                            <select name="school_transport_stop_id" id="alloc_stop_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">{{ translate('Select Stop') }}</option>
                                @foreach($stops as $stop)
                                    <option value="{{ $stop->id }}" data-route="{{ $stop->school_transport_route_id }}">
                                        {{ $stop->name }} (Fare: {{ number_format($stop->fare, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="input-label">{{ translate('Vehicle / Bus') }} <span class="text-danger">*</span></label>
                            <select name="school_transport_vehicle_id" class="form-control form-control-sm js-select2-custom" required>
                                <option value="">{{ translate('Select Vehicle') }}</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $vehicle->status !== 1 ? 'disabled' : '' }}>
                                        {{ $vehicle->vehicle_no }} @if($vehicle->driver_name)({{ $vehicle->driver_name }})@endif 
                                        @if($vehicle->status !== 1)({{ translate('Maintenance') }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 text-right mt-2">
                            @if(hasPermission("transport","add"))<button type="submit" class="btn btn-sm btn--primary"><i class="tio-save"></i> {{ translate('Save Allocation') }}</button>@endif
                        </div>
                    </form>
                </div>
            </div>

            @if(hasPermission("transport","view"))<div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Student') }}</th>
                                    <th>{{ translate('Class') }}</th>
                                    <th>{{ translate('Assigned Route') }}</th>
                                    <th>{{ translate('Stop Location') }}</th>
                                    <th>{{ translate('Bus / Vehicle') }}</th>
                                    <th>{{ translate('Monthly Fare') }}</th>
                                    <th class="text-right">{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allocations as $alloc)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $alloc->student?->name }}</div>
                                            <div class="text-xxs text-muted">Adm: {{ $alloc->student?->admission_no }}</div>
                                        </td>
                                        <td>
                                            @if($alloc->student?->currentEnrollment)
                                                <span class="badge badge-soft-info">
                                                    {{ $alloc->student->currentEnrollment->schoolClass?->name }}
                                                    @if($alloc->student->currentEnrollment->section)
                                                        - {{ $alloc->student->currentEnrollment->section->name }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $alloc->route?->name ?? '—' }}</td>
                                        <td>{{ $alloc->stop?->name ?? '—' }}</td>
                                        <td>
                                            <span class="font-weight-bold text-xs">{{ $alloc->vehicle?->vehicle_no ?? '—' }}</span>
                                            @if($alloc->vehicle?->driver_name)
                                                <div class="text-xxs text-muted"><i class="tio-user"></i> {{ $alloc->vehicle->driver_name }}</div>
                                            @endif
                                        </td>
                                        <td class="font-weight-bold text-success">
                                            {{ $alloc->stop ? number_format($alloc->stop->fare, 2) : '0.00' }}
                                        </td>
                                        <td class="text-right">
                                            @if(hasPermission("transport","delete"))<a href="{{ route('vendor.school.transport.allocation.delete', $alloc->id) }}" 
                                               class="btn btn-outline-danger btn-xs" 
                                               onclick="return confirm('{{ translate('Remove transport allocation for this student?') }}')">
                                                <i class="tio-delete"></i> {{ translate('Remove') }}
                                            </a>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ translate('No students assigned to transport yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>@endif
        </div>

    </div>
</div>
@endsection

@push('script_2')
<script>
    $(document).on('ready', function() {
        var $routeSelect = $('#alloc_route_id');
        var $stopSelect = $('#alloc_stop_id');
        
        // Clone all option tags to maintain a full database list client-side
        var $stopOptions = $stopSelect.find('option').clone();

        function filterStops() {
            var selectedRoute = $routeSelect.val();
            
            // Clear current list
            $stopSelect.empty();
            
            // Loop and filter options
            $stopOptions.each(function() {
                var optionRoute = $(this).data('route');
                // Append if it's the default placeholder, if no route is selected, or if routes match
                if (!optionRoute || !selectedRoute || String(optionRoute) === String(selectedRoute)) {
                    $stopSelect.append($(this).clone());
                }
            });

            // Set to empty
            $stopSelect.val('');
            
            // Re-initialize Select2 to rebuild options list visually
            if ($stopSelect.hasClass("select2-hidden-accessible")) {
                $stopSelect.select2('destroy');
            }
            if (window.$.HSCore && window.$.HSCore.components && window.$.HSCore.components.HSSelect2) {
                window.$.HSCore.components.HSSelect2.init($stopSelect);
            } else {
                $stopSelect.select2();
            }

            // Force sizing layout recalculation
            setTimeout(function() {
                $('.school-page .select2-container').each(function () {
                    var $cont = $(this);
                    var $sel = $cont.prev('select');
                    var sm = $sel.hasClass('form-control-sm');
                    var h = sm ? 31 : 38;
                    $cont.find('.select2-selection--single').css({
                        height: h + 'px', display: 'flex', 'align-items': 'center'
                    });
                    $cont.find('.select2-selection__rendered').css({
                        'line-height': 'normal', 'padding-top': 0, 'padding-bottom': 0,
                        'font-size': sm ? '.875rem' : ''
                    });
                    $cont.find('.select2-selection__arrow').css({ height: (h - 2) + 'px' });
                });
            }, 50);

            $stopSelect.trigger('change');
        }

        // Trigger dynamic stop listing on change
        $routeSelect.on('change', function() {
            filterStops();
        });

        // Run filter stops on page load if a route was preselected
        if ($routeSelect.val()) {
            filterStops();
        }

        // Deep Link Tab support to open correct tab on back redirect
        var hash = window.location.hash;
        if (hash) {
            $('.nav-tabs a[href="' + hash + '"]').tab('show');
        }
        $('.nav-tabs a').on('shown.bs.tab', function (e) {
            window.location.hash = e.target.hash;
        });
    });
</script>
@endpush
