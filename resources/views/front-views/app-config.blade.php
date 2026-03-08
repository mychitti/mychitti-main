<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Config</title>
</head> 
<body>
    <h2>App Config</h2>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p style="color:red;">{{ session('error') }}</p>
    @endif

    <form action="{{ route('app-config.update') }}" method="POST">
        @csrf
        @foreach($columns as $column)
            <div>
                <label for="{{ $column }}">{{ ucwords(str_replace('_', ' ', $column)) }}</label><br>
                <input type="text" name="{{ $column }}" id="{{ $column }}" value="{{ $app_config->$column ?? '' }}">
            </div>
            <br>
        @endforeach
        <button type="submit">Update</button>
    </form>
</body>
</html>
