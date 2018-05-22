<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>welcome email</title>
</head>
<body>
<p>
    Hi, <strong>{{ ucwords($user['firstName']) }}</strong>
    Welcome to tutor4all.com. Thank you for sign-up with us..
    Please follow the link below to verify your email address
    <br />
    User Name: {{$user['phone']}}
    <br />
    User Name: {{$user['password']}}
    <br />
    {{ URL::to('register/verify/' . $confirmation_code) }}
</p>
</body>
</html>
