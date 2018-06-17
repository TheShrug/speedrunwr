<!DOCTYPE html>
<html>
<head>
    <title>Welcome Email</title>
</head>

<body>
<h2>Password Reset</h2>
<p>Hello!</p>
<p>You are receiving this email because we received a password reset request for your account.</p>
<br/>
<a href="{{url('password/reset', $token)}}">{{url('password/reset', $token)}}</a>
</body>

</html>