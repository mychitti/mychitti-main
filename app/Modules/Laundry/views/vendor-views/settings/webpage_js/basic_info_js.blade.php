  {{-- <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script> --}}
     @include('admin-views.partials.tel_input')

     <script>
         var map, marker, geocoder;

         function initMap() {
             var defaultLat = {{ $storeConfig?->webpage_latitude ?? $store->latitude }};
             var defaultLng = {{ $storeConfig?->webpage_longitude ?? $store->longitude }};

             loadMap(defaultLat, defaultLng);

         }

         function loadMap(latitude, longitude) {
             console.log("Map Loaded:", latitude, longitude);

             const location = {
                 lat: latitude,
                 lng: longitude,
             };

             map = new google.maps.Map(document.getElementById("map"), {
                 zoom: 15,
                 center: location,
             });

             marker = new google.maps.Marker({
                 position: location,
                 map: map,
                 draggable: true,
             });

             geocoder = new google.maps.Geocoder();

             updateLatLng(latitude, longitude);

             google.maps.event.addListener(marker, "dragend", function(event) {
                 updateLatLng(event.latLng.lat(), event.latLng.lng());
             });

             const input = document.getElementById("searchInput");
             const autocomplete = new google.maps.places.Autocomplete(input);

             autocomplete.addListener("place_changed", function() {
                 const place = autocomplete.getPlace();
                 if (!place.geometry) return alert("No details found!");

                 map.setCenter(place.geometry.location);
                 marker.setPosition(place.geometry.location);

                 updateLatLng(
                     place.geometry.location.lat(),
                     place.geometry.location.lng()
                 );
             });
         }

         function updateLatLng(lat, lng) {
             $("#latitude").val(lat);
             $("#longitude").val(lng);
         }
     </script>

     <script
         src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&callback=initMap"
         async defer></script>

     <script>
         function addPhoneNumber() {
             const container = document.getElementById('phoneContainer');
             const phoneItem = document.createElement('div');
             phoneItem.className = 'settings-phone-item col-md-6';
             phoneItem.innerHTML = `
                <input type="text" name="phone[]" class="form-control intl_input phone-input" placeholder="Phone Number">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePhoneNumber(this)">
                    <span><i class="tio-delete-outlined"></i></span>
                </button>
            `;
             container.appendChild(phoneItem);

             initIntlPhone(phoneItem.querySelector('.intl_input'));
         }

         function removePhoneNumber(button) {
             const phoneItems = document.querySelectorAll('.settings-phone-item');
             if (phoneItems.length > 1) {
                 button.parentElement.remove();
             }
         }
     </script>