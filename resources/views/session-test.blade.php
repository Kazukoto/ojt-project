<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Session & Auth Test</h1>

    <div class="box">
        <h2>@auth Check (Laravel Auth Guard)</h2>
        @auth
            <p style="color: green;"><strong>✅ @auth = TRUE</strong></p>
            <p>User ID: {{ Auth::user()?->id }}</p>
            <p>Username: {{ Auth::user()?->username }}</p>
        @else
            <p style="color: red;"><strong>❌ @auth = FALSE</strong></p>
        @endauth
    </div>

    <div class="box">
        <h2>Session Data</h2>
        <p>Session ID: {{ Session::getId() }}</p>
        <p>All Session Data:</p>
        <pre>{{ json_encode(Session::all(), JSON_PRETTY_PRINT) }}</pre>
    </div>

    <div class="box">
        <h2>Navigation</h2>
        <ul>
            <li><a href="/session-test">Reload this page</a></li>
            <li><a href="/test-session/set">Set test session</a></li>
            <li><a href="/test-session/check-after">Check session after set</a></li>
            <li><a href="/login">Go to login</a></li>
        </ul>
    </div>
</body>
</html>