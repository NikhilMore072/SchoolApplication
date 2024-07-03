<!DOCTYPE html>
<html>
<head>
    <title>{{ $data['title'] }}</title>
    <style>
        .email-body {
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="email-body">
        Dear {{ $data['teacher']->name }},<br><br>
        Wishing you many happy returns of the day. May the coming year be filled with peace, prosperity, good health, and happiness.<br/><br/>
        Best Wishes,<br/>
        St. Arnolds Central School
    </div>
</body>
</html>
