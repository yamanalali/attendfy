<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>
    @if(! config('adminlte.enabled_laravel_mix'))
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">

    @include('adminlte::plugins', ['type' => 'css'])

    @yield('adminlte_css_pre')

    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

    @yield('adminlte_css')

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <link rel="stylesheet" href="{{ asset('css/main.css') . '?v=' . rand(99999,999999) }}">

    @else
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @endif

    @yield('meta_tags')

    @if(config('adminlte.use_ico_only'))
    <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/'. config('adminlte.name_favicon')) }}">
    @endif
</head>

<body class="@yield('classes_body')" @yield('body_data')>
    <div class="reload hide"></div>
    @yield('body')

    @if(! config('adminlte.enabled_laravel_mix'))
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>

    @include('adminlte::plugins', ['type' => 'js'])

    @yield('adminlte_js')
    <script>
        function togglePasswordVisibility(element) {
            var input = element.closest('.input-group').querySelector('input');
            if (input) { // Check if input is not null
                if (input.type === "password") {
                    input.type = "text";
                    element.classList.remove('fa-eye-slash');
                    element.classList.add('fa-eye');
                } else {
                    input.type = "password";
                    element.classList.remove('fa-eye');
                    element.classList.add('fa-eye-slash');
                }
            }
        }
    </script>
    @else
    <script src="{{ mix('js/app.js') }}"></script>
    @endif

</body>

</html>