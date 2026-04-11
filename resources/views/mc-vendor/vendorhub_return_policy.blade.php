<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MCVendorHub — Return Policy</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/toastr.css">
    <style>
        .my-nav-link {
            position: relative; color: #000; text-decoration: none;
            padding-bottom: 5px; font-weight: 500; transition: color 0.3s ease;
        }
        .my-nav-link::after {
            content: ""; position: absolute; bottom: 0; left: 0;
            height: 2px; width: 100%; background-color: #81c408;
            transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease;
        }
        .my-nav-link.active::after { transform: scaleX(1); }
    </style>
</head>
<body>
    @include('mc-vendor.partials.nav')

    <div class="container-fluid py-5" style="min-height: 70vh;">
        <div class="container py-5">
            <h2 class="fw-bold mb-4">Return Policy</h2>
            @if($return_policy)
                {!! $return_policy->value !!}
            @else
                <p class="text-muted">Return policy content coming soon.</p>
            @endif
        </div>
    </div>

    @include('mc-vendor.partials.footer')
</body>
</html>
