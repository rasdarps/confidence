<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DarpPOS | @yield('title')</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!--offline files-->
    <!-- Bootstrap Css -->
    <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('backend/assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />


    <!-- Local DataTables baseline -->
    <link href="{{ asset('vendor/datatables/css/buttons.dataTables-2.2.3.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables-1.13.6.min.css') }}" rel="stylesheet">
    <link href="{{ asset('notiflix/notiflix-3.2.8.min.css') }}" rel="stylesheet">
    
<style>
    /* body{
        
        background-color: #1C3B3B !important;
        background-image: linear-gradient(rgba(15, 17, 17, 0.9), rgba(12, 12, 12, 0.9)), url({{ asset('images/posbg1.jpg') }});
        background-repeat:no-repeat;
        background-position:center center;
        background-attachment:fixed;
        background-size:cover;

     } */

</style>
     
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light navbar-laravel" style="background:#000; font-weight:bold; background:transparent;">
            <div class="container">
                <span class="navbar-brand"></span>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
    
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto"></ul>
                </div>
            </div>
        </nav>


        <main class="py-4">
            <div class="container">
            @yield('content')
            </div>
        </main>
    </div>

@include('layout.scripts')

@yield('scripts')

</body>
</html>