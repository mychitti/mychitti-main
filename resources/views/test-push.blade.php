<!DOCTYPE html>
<html>
<head>
    <title>Test Push Notification</title>
</head>
<body> 
    <h2>Test Push Notification</h2>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif

    @if($errors->any())
        <p style="color:red;">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('test-push.send') }}">
        @csrf
        <label for="fcm_token">FCM Token:</label><br>
        <input type="text" name="fcm_token" id="fcm_token" style="width:500px;" required><br><br>
        <button type="submit">Send Notification</button>
    </form>
</body>
</html>