<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Welcome</title>
  </head>
  <body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111;">
    @php
      $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
      $display = $name !== '' ? $name : ($user->email ?? 'there');
    @endphp

    <p>Hi {{ $display }},</p>

    <p>Welcome to Alonti.</p>

    <p>
      You can sign in any time at
      <a href="{{ url('/login') }}">{{ url('/login') }}</a>.
    </p>

    <p>If you did not sign up, you can ignore this email.</p>
  </body>
</html>

