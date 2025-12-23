<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Module Instructions</title>
</head>

<body>

    <div class="container my-4">
    <h1 class="text-center">{{ucfirst($module->name)}}</h1>
    <div class="row d-flex ">
    <div class="col-md-6 p-2">
    <img style="width: 100%;" src="{{asset('storage/app/public/vendor_login/' . $module->image)}}" alt="">
    </div>
    <div class="col-md-6 p-2">
    <img style="width: 100%;" src="{{asset('storage/app/public/vendor_login/' . $module->image2)}}" alt="">
    </div>
    </div>
        {!! $module->content !!}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

</body>

</html>
