<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Location Tracking</title>
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>
</head>

<body>
    <div id="map"></div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <script>
        // Declare global variables for map and marker
        let map;
        let marker;

        // Function to load Google Maps API
        function loadScript(src, callback) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        // Function to initialize the map and place the marker
        function initMap(latitude, longitude) {
            const initialLocation = { lat: latitude, lng: longitude };

            // Create the map only if it is not already created
            if (!map) {
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 10,
                    center: initialLocation,
                });
            }

            // Create the marker only if it is not already created
            if (!marker) {
                marker = new google.maps.Marker({
                    position: initialLocation,
                    map: map,
                    {{-- icon: {
                        url: "{{ asset('storage/app/public/vendor/' . $staff->image) }}",
                        scaledSize: new google.maps.Size(40, 40), // Adjust size as needed
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(20, 20),
                    }, --}}
                });
            } else {
                // Just update the marker position if it's already created
                marker.setPosition(initialLocation);
                map.setCenter(initialLocation); // Update map center
            }
        }

        // Function to smoothly move the marker
        function moveMarkerSmoothly(newLat, newLng) {
            const newLatLng = new google.maps.LatLng(newLat, newLng);
            const currentLatLng = marker.getPosition();
            const steps = 100;
            const delay = 10;
            let i = 0;

            const moveInterval = setInterval(function () {
                i++;
                const lat = currentLatLng.lat() + (newLatLng.lat() - currentLatLng.lat()) * (i / steps);
                const lng = currentLatLng.lng() + (newLatLng.lng() - currentLatLng.lng()) * (i / steps);
                const latLng = new google.maps.LatLng(lat, lng);

                marker.setPosition(latLng); // Update marker position smoothly

                if (i >= steps) {
                    clearInterval(moveInterval); // Stop the movement after steps are complete
                }
            }, delay);
        }

        // Function to get current location from the browser (for demo purposes)
        function getCurrentLocation() {
            const defaultLat = 13.6287557;
            const defaultLng = 79.4191795;
            initMap(defaultLat, defaultLng);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                }, function (error) {
                    console.warn("Geolocation error:", error.message);
                });
            } else {
                console.warn("Geolocation not supported.");
            }
        }

        // Initialize Google Maps API and load the map
        function init() {
            loadScript(
                "https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=getCurrentLocation"
            );
        }

        // Run initialization when document is ready
        document.addEventListener("DOMContentLoaded", init);

        // Initialize Pusher and Laravel Echo
        window.Pusher = Pusher;
        const EchoConstructor = window.Echo.default;
        window.Echo = new EchoConstructor({
            broadcaster: 'pusher',
            key: '0088821d8dedd807f4ef', // Your Pusher key
            cluster: 'ap2', // Your Pusher cluster
            forceTLS: true
        });

        // Listen for the location-updated event from Pusher
        window.Echo.channel('locations')
            .listen('.location-updated', function (data) {
                console.log('Updated Location:', data);

                const latitude = parseFloat(data.latitude); // New latitude from event
                const longitude = parseFloat(data.longitude); // New longitude from event

                // Update the marker position when location changes
                if (marker) {
                    moveMarkerSmoothly(latitude, longitude); // Move the marker smoothly to the new position
                    map.setCenter({ lat: latitude, lng: longitude }); // Update map center
                }
            });
    </script>
</body>

</html>
