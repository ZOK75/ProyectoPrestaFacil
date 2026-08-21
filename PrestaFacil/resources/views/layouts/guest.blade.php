<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PrestaFácil') }}</title>

    <!-- Favicon PrestaFácil -->
    <link rel="icon" type="image/jpeg" href="{{ asset('img/Prestamo.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('img/Prestamo.jpg') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased bg-white min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="w-full flex items-center justify-center">
        {{ $slot }}
    </div>
</body>
</html>