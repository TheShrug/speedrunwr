<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @if (isset($meta))
        <title>Random Speedrun World Record Videos | Speedrun Web Randomizer</title>
        <meta name="description" content="{{ $meta['description'] }}">
        <meta property="og:title" content="{{ $meta['title'] }}" />
        <meta property="og:description" content="{{ $meta['description'] }}" />
        <meta property="og:image" content="{{ $meta['image'] }}" />
    @else
        <title>Random Speedrun World Record Videos | Speedrun Web Randomizer</title>
        <meta property="og:title" content="Random Speedrun World Record Videos | Speedrun Web Randomizer" />
        <meta name="description" content="Find new interesting speedgames, speedruns, or speedrunners by watching only the best.">
    @endif

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">


</head>
<body>


    @yield('content')
</body>
</html>
