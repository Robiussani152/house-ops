<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-white p-6 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <main class="flex max-w-sm flex-col items-center gap-6 text-center">
            <div class="flex size-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.95-8.95a1.5 1.5 0 0 1 2.1 0L22.25 12M4.5 9.75V20a.75.75 0 0 0 .75.75H9.75v-5.25h4.5v5.25h4.5a.75.75 0 0 0 .75-.75V9.75" />
                </svg>
            </div>

            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-semibold tracking-tight">{{ config('app.name') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Run your household operations — staff, shifts and payroll in one place.</p>
            </div>

            <a href="{{ url('/admin') }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950">
                Sign in
            </a>
        </main>
    </body>
</html>
