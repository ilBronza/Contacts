<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('contacts::verification.confirmed_title') }}</title>
</head>
<body>
    <main>
        <h1>{{ __('contacts::verification.confirmed_title') }}</h1>
        <p>{{ __('contacts::verification.confirmed_message') }}</p>
    </main>
</body>
</html>
