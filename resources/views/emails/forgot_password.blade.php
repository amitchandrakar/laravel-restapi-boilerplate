<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset password</title>
  </head>
  <body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111;">
    @php
      $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
      $display = $name !== '' ? $name : ($user->email ?? 'there');
    @endphp

    <p>Hi {{ $display }},</p>

    <p>We received a request to reset your password.</p>

    <p>
      <a href="{{ $resetUrl }}">Reset your password</a>
    </p>

    <p>If the link doesn’t work, copy and paste this into your browser:</p>
    <p>{{ $resetUrl }}</p>

    <p>If you did not request a password reset, you can ignore this email.</p>

    <hr />
    <p style="font-size: 12px; color: #555;">
      Reset hash: {{ $hash }}
    </p>
  </body>
</html>

