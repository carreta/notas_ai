<!DOCTYPE html>
<html class="{{ request()->routeIs('history') || request()->routeIs('debug') ? 'dark' : '' }}" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notas IA')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex flex-col">
    @include('partials.nav')
    <main class="grow w-full max-w-container-max mx-auto mt-nav px-lg py-xl flex flex-col items-center">
        @yield('content')
    </main>
    @include('partials.footer')
</body>
</html>