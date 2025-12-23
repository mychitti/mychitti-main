<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Location Sender</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<script>
    var latitude, longitude;

    function sendLocationToServer(userId) {
        // Start interval **after** getting initial location
        setInterval(function () {
            if (typeof latitude !== "undefined" && typeof longitude !== "undefined") {

                latitude += (Math.random() - 0.5) / 1000;  // Simulate slight change in latitude
                longitude += (Math.random() - 0.5) / 1000;

                $.ajax({
                    url: "/update-live-location/user/" + userId + "?latitude=" + latitude + "&longitude=" + longitude,
                    type: "GET",
                    success: function (response) {
                        console.log("Location updated:", response);
                    },
                    error: function (err) {
                        console.error("Error updating location:", err);
                    }
                });
            } else {
                console.warn("Latitude or longitude is undefined.");
            }
        },10000); // Update every 20 seconds
    }

    // Get the user's current location
    function initLocationUpdate(userId) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;

                // Start sending location after we get initial coordinates
                sendLocationToServer(userId);
            }, function (err) {
                console.error("Geolocation error:", err.message);
            });
        } else {
            console.warn("Geolocation not supported.");
        }
    }

    $(document).ready(function () {
        const userId = {{$staff_id}}; // You can extract this from the page or dynamically from URL
        initLocationUpdate(userId);
    });
</script>

</body>

</html>
