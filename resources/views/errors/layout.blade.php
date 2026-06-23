<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: 'Yekan Bakh';
            src: url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.0.0/fonts/webfonts/Vazirmatn-Regular.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.0.0/fonts/webfonts/Vazirmatn-Regular.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'Yekan Bakh', 'Vazirmatn', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-300 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
<div class="absolute top-0 inset-x-0 h-2 !bg-red-800 opacity-80"></div>

    <div class="max-w-md w-full bg-white rounded-3xl shadow-sm border border-gray-100 p-8 text-center space-y-6">

        <div class="flex justify-center">
            <div class="absolute top-0 inset-x-0 h-2 !bg-red-500 opacity-80"></div>

            <div class="p-4 rounded-full bg-gray-50 text-gray-400">
                @yield('icon')
            </div>
        </div>

        <div class="space-y-3">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">@yield('heading')</h1>
            <p class="text-base text-gray-500 leading-relaxed">@yield('message')</p>
        </div>

        <div class="pt-4 flex flex-col space-y-3">
            <a href="/" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors">
                بازگشت به صفحه اصلی
            </a>
            @hasSection('action')
                @yield('action')
            @endif
        </div>
    </div>
<div class="absolute bottom-0 inset-x-0 h-2 !bg-red-800 opacity-80"></div>
</body>
</html>
