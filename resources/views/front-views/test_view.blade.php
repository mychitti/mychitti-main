<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="post" action="{{route('send-test-notification')}}">
    @csrf
    <input type="email" name="email" id="">
    <button class="btn">send notification</button>
    </form>
</body>
</html>