@extends('front-views.layout')

@section('title','Edit Address')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    #map2 {
        height:300px ;
        width: 100%  ;
    }
</style>

@endpush

@section('content')

<!-- Contact Start -->
<div class="container-fluid contact py-5">
    <div class="container py-5">
        <div class="p-5 bg-light rounded">
            <form class="formSubmit addressForm" action="{{route('update-address', [$addr->id])}}" method="post">
                @csrf
                <div class="row ">
                    <div class="col-md-12 row">
                        <div class="row">
                            <div class="rounded p-2 d-flex flex-column position-relative align-items-center" style="height: fit-content;">
                                <div id="map2"></div>
                                <!-- <p id="info"></p> -->
                            </div>

                            <div class="col-12 row">
                                <input type="hidden" name="latitude" id="user_latitude" value="{{$addr->latitude}}">
                                <input type="hidden" name="longitude" id="user_longitude" value="{{$addr->longitude}}">
                                <label for="Delivery-1" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3 mx-1 px-1">
                                    <input type="radio" {{$addr->address_type == 'home'? 'checked' : ''}} style="cursor:pointer" class="form-check-input bg-primary border-0 mx-1" id="Delivery-1" name="address_type" value="home">
                                    Home
                                </label> 
                                <label for="Delivery-2" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio" {{$addr->address_type == 'office'? 'checked' : ''}} class="form-check-input bg-primary border-0 mx-1" id="Delivery-2" name="address_type" value="office">
                                    Work
                                </label>
                                <label for="Delivery-3" style="cursor:pointer" class="col-2 form-check text-start my-3 border rounded border-primary py-3  mx-1 px-1">
                                    <input type="radio" {{$addr->address_type == 'others'? 'checked' : ''}} class="form-check-input bg-primary border-0 mx-1" id="Delivery-3" name="address_type" value="others">
                                    Other
                                </label>
                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Address<sup>*</sup></label>
                                    <input type="text" style="pointer-events:none;" name="address" id="user_address_inp" value="{{$addr->address}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Name<sup>*</sup></label>
                                    <input type="text" name="contact_person_name"  required value="{{$addr->contact_person_name}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Contact Person Mobile</label>
                                    <input value="{{$addr->contact_person_number}}" oninput="this.value = this.value.slice(0, 10)" name="contact_person_number" type="number" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-12">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Street Number</label>
                                    <input value="{{$addr->road}}" name="road" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">House</label>
                                    <input value="{{$addr->house}}" name="house" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Floor</label>
                                    <input value="{{$addr->floor}}" name="floor" type="text" placeholder="(Optional)" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn border-secondary text-primary">Update</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Contact End -->

@endsection

@push('script_2')

    <script>
        function saveLocation() {
            console.log('save location')
            $('#user_city').text($('#user_city2').val());
            $('body').css('pointer-events', 'none')
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: "{{ route('update-location') }}",
                data: {
                    latitude: $('#user_latitude').val(),
                    longitude: $('#user_longitude').val(),
                    address: $('#user_address').val(),
                    city: $('#user_city').val(),
                },
                beforeSend: function() {
                    $('#locBtn').html(
                        `<img style='filter: brightness(0.6); width: 26px;' src='{{ asset('public/assets/front/lib/lightbox/images/loading.gif') }}'>`
                    )
                },
                success: function(data) {},
                complete: function() {
                    $('#locBtn').html('Update Location')
                    window.location.reload();
                }
            });
        }

        function loadScript(src, callback) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initMap(latitude, longitude) {
            var initialLocation = {
                lat: {{$addr->latitude}},
                lng: {{$addr->longitude}}
            };

            var map = new google.maps.Map(document.getElementById('map2'), {
                zoom: 10,
                center: initialLocation
            });

            var marker = new google.maps.Marker({
                position: initialLocation,
                map: map,
                draggable: true
            });

            var geocoder = new google.maps.Geocoder();
            var infoWindow = new google.maps.InfoWindow();

            // Function to geocode and update address fields
            function geocodeLatLng(lat, lng) {
                geocoder.geocode({
                    'location': {
                        lat: lat,
                        lng: lng
                    }
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        let locality = null;

                        // Loop through address components to find 'locality'
                        for (let i = 0; i < results[0].address_components.length; i++) {
                            const component = results[0].address_components[i];
                            if (component.types.includes('locality')) {
                                locality = component.long_name;
                                break;
                            }
                        }

                        console.log(locality);

                        var address = results[0].formatted_address;
                        $('#user_city').text(locality).attr('title', locality);
                        $('#user_location').text(address).attr('title', address);
                        $('#user_address_inp, #searchInput').val(address);
                        $('#user_latitude').val(lat);
                        $('#user_longitude').val(lng);
                    } else {
                        console.warn('Geocoder failed due to:', status);
                    }
                });
            }

            // Geocode initial location
            geocodeLatLng(latitude, longitude);

            // Handle marker drag event
            google.maps.event.addListener(marker, 'dragend', function(event) {
                var lat = event.latLng.lat();
                var lng = event.latLng.lng();
                geocodeLatLng(lat, lng);
            });

            // Add Places Autocomplete
            var input = document.getElementById('searchInput');
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place.geometry) return alert("No details available for input: " + place.name);

                map.setCenter(place.geometry.location);
                map.setZoom(17);
                marker.setPosition(place.geometry.location);

                geocodeLatLng(place.geometry.location.lat(), place.geometry.location.lng());
            });
        }

        function getCurrentLocation() {
            var defaultLat = {{$addr->latitude}};
            var defaultLng = {{$addr->longitude}};

          
                // Load default location first
                initMap(defaultLat, defaultLng);
            

        }

        function init() {
            loadScript(
                "https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=getCurrentLocation"
            );
        }

        // Run initialization when document is ready
        document.addEventListener("DOMContentLoaded", init);


      
    </script>
@endpush