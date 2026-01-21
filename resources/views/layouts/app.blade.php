<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PDS Management System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-blue-900 border-b border-blue-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <img class="h-10 w-auto" src="/images/csc-logo.png" alt="CSC Logo" onerror="this.style.display='none'">
                            <span class="ml-3 text-white text-xl font-bold">PDS Management System</span>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('dashboard') }}" class="border-transparent text-gray-200 hover:border-blue-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('pds.index') }}" class="border-transparent text-gray-200 hover:border-blue-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                PDS Records
                            </a>
                            <a href="{{ route('dashboard.analytics') }}" class="border-transparent text-gray-200 hover:border-blue-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Analytics
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-white mr-4">{{ auth()->user()->name ?? 'Guest' }}</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-blue-900 mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-gray-200 text-sm">
                    © {{ date('Y') }} Civil Service Commission - PDS Management System
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
