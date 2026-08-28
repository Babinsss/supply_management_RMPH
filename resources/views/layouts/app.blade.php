<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Supply Hub | RMPH') }}</title>

    <!-- Unified Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Global Styles -->
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9; 
            color: #0f172a; 
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar-modern {
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 2px solid #e2e8f0;
        }
        .btn-modern { 
            border-radius: 1rem; 
            font-weight: 600; 
            transition: all 0.2s;
        }
        .btn-modern:hover { transform: translateY(-2px); }
        
        /* Native Dark Mode Support */
        [data-bs-theme="dark"] body { background-color: #0f172a; color: #f8fafc; }
        [data-bs-theme="dark"] .navbar-modern { background: #1e293b; border-color: #334155; }
        [data-bs-theme="dark"] .dropdown-menu { background: #1e293b; border-color: #334155; }
        [data-bs-theme="dark"] .dropdown-item { color: #f8fafc; }
        [data-bs-theme="dark"] .dropdown-item:hover { background: #334155; }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-modern py-3">
            <div class="container">
                <a class="navbar-brand fw-bolder tracking-tight" href="{{ url('/') }}">
                    <i class="bi bi-box-seam-fill text-primary me-2"></i> {{ config('app.name', 'Supply Hub') }}
                </a>
                
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <i class="bi bi-list fs-2"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto"></ul>

                    <ul class="navbar-nav ms-auto align-items-center gap-3">
                        <!-- Dark Mode Toggle Button -->
                        <li class="nav-item">
                            <button class="btn btn-sm btn-outline-secondary rounded-circle px-2" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                                <i class="bi bi-moon-fill" id="themeIcon"></i>
                            </button>
                        </li>

                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link fw-bold" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="btn btn-primary btn-modern px-4 py-2" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle fw-bold" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4 mt-2 p-2">
                                    <a class="dropdown-item py-2 rounded-3 text-danger fw-bold" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-5">
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme Toggle Logic -->
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            
            if (html.getAttribute('data-bs-theme') === 'dark') {
                html.setAttribute('data-bs-theme', 'light');
                icon.className = 'bi bi-moon-fill';
            } else {
                html.setAttribute('data-bs-theme', 'dark');
                icon.className = 'bi bi-sun-fill text-warning';
            }
        }
    </script>
</body>
</html>