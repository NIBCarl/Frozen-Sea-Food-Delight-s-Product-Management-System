<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Frozen Sea Food Delight's Product Management</title>

        <!-- PWA Meta Tags -->
        <meta name="description" content="Frozen seafood product management and ordering system">
        <meta name="theme-color" content="#1976d2">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="apple-mobile-web-app-title" content="Seafood Mgmt">
        
        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">
        
        <!-- App Icons -->
        <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
        <link rel="apple-touch-icon" href="/images/icon-192x192.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
