<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('contacts::verification.subject') }}</title></head>
<body>
    <p>{{ __('contacts::verification.introduction') }}</p>
    <p><a href="{{ $verificationUrl }}">{{ __('contacts::verification.action') }}</a></p>
    <p>{{ __('contacts::verification.expiration', ['days' => $expiresInDays]) }}</p>
    <p>{{ __('contacts::verification.ignore') }}</p>
</body>
</html>
