<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>WMS (Warehouse Management System)</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        @production
            {!! \App\Helpers\ViteHelper::asset('index.html') !!}
        @endproduction
    </head>
    <body>
        <div id="app"></div>
        @env('local')
            {!! \App\Helpers\ViteHelper::assetDev('src/main.ts') !!}
        @endenv
    </body>
</html>