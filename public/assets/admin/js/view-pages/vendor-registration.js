"use strict";

$(document).ready(function () {
    $(".js-example-basic-single").select2();
});
$(document).ready(function () {
    $(".js-example-basic-multiple").select2({
        maximumSelectionLength: 10,
    });
    $(".select_2_max_20").select2({
        maximumSelectionLength: 20,
    });
    $(".select_2_unlimited").select2({
    });
});

$("#exampleInputPassword ,#exampleRepeatPassword").on("keyup", function () {
    let pass = $("#exampleInputPassword").val();
    let passRepeat = $("#exampleRepeatPassword").val();
    if (pass === passRepeat) {
        $(".pass").hide();
    } else {
        $(".pass").show();
    }
});

function readURL(input, viewer) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#" + viewer).attr("src", e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}


let zone_id = 0;
$("#choice_zones").on("change", function () {
    if ($(this).val()) {
        zone_id = $(this).val();
    }
});
function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(
        browserHasGeolocation
        ? "Error: The Geolocation service failed."
        : "Error: Your browser doesn't support geolocation."
    );
    infoWindow.open(map);
}

initMap();
function initMap() {
    infoWindow.open(map);
    infoWindow = new google.maps.InfoWindow();
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                myLatlng = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                infoWindow.setPosition(myLatlng);
                infoWindow.setContent("Location found.");
                infoWindow.open(map);
                map.setCenter(myLatlng);
            },
            () => {
                handleLocationError(true, infoWindow, map.getCenter());
            }
        );
    } else {
        handleLocationError(false, infoWindow, map.getCenter());
    }
    
    const input = document.getElementById("pac-input");
    const searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
    
    // Bias the SearchBox results towards current map's viewport
    map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
    });
    
    let markers = [];
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length === 0) {
            return;
        }
        
        // Clear old search markers
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];
        
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
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
            
            markers.push(
                new google.maps.Marker({
                    map,
                    icon,
                    title: place.name,
                    position: place.geometry.location,
                })
            );

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
        
        // Move the draggable marker to the first search result
        if (places.length > 0 && places[0].geometry) {
            createOrUpdateMarker(places[0].geometry.location);
            updateAddress(places[0].geometry.location);
        }
    });
}
$("#customFileEg1").change(function () {
    readURL(this, "logoImageViewer");
});

$("#coverImageUpload").change(function () {
    readURL(this, "coverImageViewer");
});

$(".lang_link").click(function (e) {
    e.preventDefault();
    $(".lang_link").removeClass("active");
    $(".lang_form").addClass("d-none");
    $(this).addClass("active");
    let form_id = this.id;
    let lang = form_id.substring(0, form_id.length - 5);
    $("#" + lang + "-form").removeClass("d-none");
});
