@extends('layouts.vendor.app')
@section('title', translate('messages.edit_store'))
@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin') }}/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Custom styles for this page -->
    <link href="{{ asset('public/assets/admin/css/croppie.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-row {
            margin-top: 6px;
            padding: 0 12px !important;

        }

        .form-label {
            font-size: 11px;
            font-weight: bold;
            line-height: 19px;
            margin-bottom: 0px !important;
        }

        .form-group {
            padding: 5px !important;
            margin-bottom: 0px !important;

        }
    </style>
@endpush
@section('content')
    <!-- Content Row -->
    <div class="content container-fluid">
        <div class="page-header">
            <h2 class="page-header-title text-capitalize">
                <img class="w--26" src="{{ asset('/public/assets/admin/img/store.png') }}" alt="public">
                <span>
                    {{ translate('messages.edit_store_info') }}
                </span>
                </h1>
        </div>
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        @php($defaultLang = 'en')
        <form action="{{ route('vendor.shop.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body py-0">
                            <div class="row ">
                                <div class="row col-md-7">
                                    <div class="form-group col-md-6">
                                        <label class="form-label"
                                            for="exampleFormControlInput1">{{ translate('messages.name') }}</label>
                                        <input type="text" name="name[]" class="form-control"
                                            value="{{ $shop->getRawOriginal('name') }}"
                                            placeholder="{{ translate('messages.store_name') }}" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="form-label"
                                            for="name">{{ translate('messages.contact_number') }}<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="contact" value="{{ $shop->phone }}"
                                            class="form-control intl_input" id="name" required>
                                    </div>
                                    <input type="hidden" name="lang[]" value="default">
                                    <div class="form-group mb-0 col-md-6">
                                        <label class="form-label"
                                            for="exampleFormControlInput1">{{ translate('messages.address') }}
                                        </label>
                                        <input type="text" name="address[]"
                                            placeholder="{{ translate('messages.store') }}"
                                            value="{{ $shop->getRawOriginal('address') }}" class="form-control"
                                            id="address" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="form-label" for="choice_zones">City<span class="form-label-secondary"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.select_zone_for_map') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.select_zone_for_map') }}"></span></label>
                                        <select name="zone_id" id="choice_zones"
                                            data-placeholder="{{ translate('messages.select_zone') }}"
                                            class="form-control js-select2-custom get_zone_data">
                                            @foreach (\App\Models\Zone::active()->get() as $zone)
                                                <option value="{{ $zone->id }}"
                                                    {{ $shop->zone_id == $zone->id ? 'selected' : '' }}>
                                                    {{ $zone->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title font-regular">
                                                    {{ translate('messages.upload_logo') }}
                                                </h5>
                                            </div>
                                            <div class="card-body d-flex flex-column pt-0">
                                                <div class="text-center my-auto py-1">
                                                    <img class="store-banner onerror-image" id="viewer"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/image-place-holder.png') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($shop->logo, asset('storage/app/public/store/') . '/' . $shop->logo, asset('public/assets/admin/img/image-place-holder.png'), 'store/') }}"
                                                        alt="Product thumbnail" />
                                                </div>
                                                <div class="custom-file">
                                                    <input type="file" name="image"
                                                        accept="image/*,android/allowCamera" id="customFileUpload"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label"
                                                        for="customFileUpload">{{ translate('messages.choose_file') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title font-regular">
                                                    {{ translate('messages.upload_cover_photo') }} <span
                                                        class="text-danger">({{ translate('messages.ratio') }} 2:1)</span>
                                                </h5>
                                            </div>
                                            <div class="card-body d-flex flex-column pt-0">
                                                <div class="text-center my-auto py-1">
                                                    <img class="store-banner onerror-image" id="coverImageViewer"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/restaurant_cover.jpg') }}"
                                                        src="{{ \App\CentralLogics\Helpers::onerror_image_helper($shop->cover_photo, asset('storage/app/public/store/cover/') . '/' . $shop->cover_photo, asset('public/assets/admin/img/restaurant_cover.jpg'), 'store/cover/') }}"
                                                        alt="Product thumbnail" />
                                                </div>
                                                <div class="custom-file">
                                                    <input type="file" name="photo" id="coverImageUpload"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label"
                                                        for="coverImageUpload">{{ translate('messages.choose_file') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="latitude" id = "latitude"
                                        value="{{ $shop->latitude }}">
                                    <input type="hidden" name="longitude" id = "longitude"
                                        value="{{ $shop->longitude }}">
                                    {{-- <div class="invisible" style="height: 0px;">
                                        <label class="form-label"
                                            for="latitude">{{ translate('messages.latitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" id="latitude" name="latitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                            value="{{ $shop->latitude }}" required readonly>
                                    </div>
                                    <div class="invisible" style="height: 0px;">
                                        <label class="form-label"
                                            for="longitude">{{ translate('messages.longitude') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('messages.store_lat_lng_warning') }}"><img
                                                    src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                    alt="{{ translate('messages.store_lat_lng_warning') }}"></span></label>
                                        <input type="text" name="longitude" class="form-control"
                                            placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                            value="{{ $shop->longitude }}" required readonly>
                                    </div> --}}
                                </div>


                                <div class="col-md-5">
                                    <input id="pac-input" class="controls rounded" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('messages.search_your_location_here') }}"
                                        type="text" placeholder="{{ translate('messages.search_here') }}" />
                                    <div id="map"></div>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">{{translate('messages.address')}}<span class="text-danger">*</span></label>
                                        <textarea type="text" rows="4" name="address" value="" class="form-control" id="address"
                                                required>{{$shop->address}}</textarea>
                                    </div>
                                </div> --}}
                        </div>
                    </div>
                </div>
            </div>

    </div>
    <div class="mt-3 justify-content-end btn--container">
        <a class="btn btn--danger text-capitalize"
            href="{{ route('vendor.shop.view') }}">{{ translate('messages.cancel') }}</a>
        <button type="submit" class="btn btn--primary text-capitalize"
            id="btn_update">{{ translate('messages.update') }}</button>
    </div>
    </form>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/vendor/shop-edit.js"></script>
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap&v=3.45.8">
    </script>
     @include('admin-views.partials.tel_input')

    <script>
        let myLatlng = {
            lat: {{ $shop->latitude }},
            lng: {{ $shop->longitude }}
        };
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 13,
            center: myLatlng,
            fullscreenControl: false, // Disable default fullscreen
        });

        let zonePolygon = null;
        let infoWindow = new google.maps.InfoWindow({
            content: "Click the map to get Lat/Lng!",
            position: myLatlng,
        });
        let bounds = new google.maps.LatLngBounds();
        let isMaximized = false;

        function initMap() {
            // Create the initial marker
            new google.maps.Marker({
                position: {
                    lat: {{ $shop->latitude }},
                    lng: {{ $shop->longitude }}
                },
                map,
                title: "{{ $shop->name }}",
            });
            infoWindow.open(map);

            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];

            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                const bounds = new google.maps.LatLngBounds();
                places.forEach((place) => {
                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });

            // Setup custom maximize button
            setupCustomMaximize();
        }

        function setupCustomMaximize() {
            const mapDiv = document.getElementById('map');

            // Create button
            const btn = document.createElement('button');
            btn.id = 'customMaximizeBtn';
            btn.type = 'button';
            btn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                z-index: 1000;
                padding: 10px;
                background: white;
                border: none;
                cursor: pointer;
                border-radius: 2px;
                box-shadow: 0 2px 6px rgba(0,0,0,.3);
            `;
            btn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 18 18">
                    <path d="M0 0v6h2V2h4V0H0zm16 0h-4v2h4v4h2V0h-2zm0 16h-4v2h6v-6h-2v4zM2 12H0v6h6v-2H2v-4z" fill="#666"/>
                </svg>
            `;
            mapDiv.appendChild(btn);

            btn.addEventListener('click', toggleMaximize);
        }

        function toggleMaximize() {
            const mapDiv = document.getElementById('map');
            const btn = document.getElementById('customMaximizeBtn');

            isMaximized = !isMaximized;

            if (isMaximized) {
                // Store original styles
                mapDiv.dataset.originalStyle = mapDiv.style.cssText;

                // Maximize
                mapDiv.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            margin: 0 !important;
        `;

                btn.style.position = 'fixed';
                btn.style.zIndex = '10001';

                // Change icon to minimize
                btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 18 18">
                <path d="M6 0v2H2v4H0V0h6zm10 0v6h-2V2h-4V0h6zM6 16H2v-4H0v6h6v-2zm10 0v2h-6v-2h4v-4h2v4z" fill="#666"/>
            </svg>
        `;
            } else {
                // Restore original styles
                mapDiv.style.cssText = mapDiv.dataset.originalStyle || '';

                btn.style.position = 'absolute';
                btn.style.zIndex = '1000';

                // Change icon to maximize
                btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 18 18">
                <path d="M0 0v6h2V2h4V0H0zm16 0h-4v2h4v4h2V0h-2zm0 16h-4v2h6v-6h-2v4zM2 12H0v6h6v-2H2v-4z" fill="#666"/>
            </svg>
        `;
            }

            // Trigger map resize after state change
            setTimeout(() => {
                google.maps.event.trigger(map, 'resize');
                map.setCenter(myLatlng);
            }, 100);
        }

        initMap();

        // Your existing zone data code
        $('.get_zone_data').on('change', function() {
            let id = $(this).val();
            $.get({
                url: 'https://admin.mychitti.net/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(),
                                null, 2),
                        });
                        let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                            2);
                        coordinates = JSON.parse(coordinates);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });

        $(document).on('ready', function() {
            let id = $('#choice_zones').val();
            $.get({
                url: 'https://admin.mychitti.net/zone/get-coordinates/' + id,
                dataType: 'json',
                success: function(data) {
                    if (zonePolygon) {
                        zonePolygon.setMap(null);
                    }
                    zonePolygon = new google.maps.Polygon({
                        paths: data.coordinates,
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: 'white',
                        fillOpacity: 0,
                    });
                    zonePolygon.setMap(map);
                    zonePolygon.getPaths().forEach(function(path) {
                        path.forEach(function(latlng) {
                            bounds.extend(latlng);
                            map.fitBounds(bounds);
                        });
                    });
                    map.setCenter(data.center);
                    google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                        infoWindow.close();
                        infoWindow = new google.maps.InfoWindow({
                            position: mapsMouseEvent.latLng,
                            content: JSON.stringify(mapsMouseEvent.latLng.toJSON(),
                                null, 2),
                        });
                        let coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                            2);
                        coordinates = JSON.parse(coordinates);

                        document.getElementById('latitude').value = coordinates['lat'];
                        document.getElementById('longitude').value = coordinates['lng'];
                        infoWindow.open(map);
                    });
                },
            });
        });
        $('#reset_btn').click(function() {
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $('#customFileEg1').val(null);
            $('#coverImageViewer').attr('src', "{{ asset('public/assets/admin/img/upload-img.png') }}");
            $('#coverImageUpload').val(null);
            $('#choice_zones').val(null).trigger('change');
            $('#module_id').val(null).trigger('change');
            zonePolygon.setMap(null);
            $('#coordinates').val(null);
            $('#latitude').val(null);
            $('#longitude').val(null);
        })

        let zone_id = 0;
        $('#choice_zones').on('change', function() {
            if ($(this).val()) {
                zone_id = $(this).val();
            }
        });
    </script>
@endpush
