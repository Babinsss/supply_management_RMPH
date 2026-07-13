<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'RMPH Supply Hub' }}</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/supply-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/supply-logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; }
        
        /* Bento Box UI Elements */
        .bento-card { background: #ffffff; border-radius: 1.25rem; border: none; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); padding: 1.5rem; transition: transform 0.2s; }
        .bento-card:hover { box-shadow: 0 10px 30px -5px rgba(0,0,0,0.06); transform: translateY(-2px); }
        
        /* Typography & Inputs */
        .text-muted-soft { color: #64748b; }
        .input-modern { border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #f8fafc; padding: 0.6rem 1rem; font-weight: 500; width: 100%; transition: all 0.2s; }
        .input-modern:focus { background-color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); border-color: #3b82f6; outline: none; }
        .btn-modern { border-radius: 0.75rem; font-weight: 700; padding: 0.6rem 1.2rem; transition: all 0.2s; }
        
        /* Clean Tables */
        .table-clean th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; }
        .table-clean td { vertical-align: middle; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container py-4">
        
       {{-- Navigation Header --}}
            <nav class="d-flex justify-content-between align-items-center mb-5">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" style="height: 90px; width: auto;">
                    <div>
                        <h4 class="mb-0 fw-bolder tracking-tight">Supply Hub</h4>
                        <span class="text-muted-soft small fw-bold">Supply Section</span>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <a href="/" class="btn btn-light btn-modern shadow-sm border"><i class="bi bi-grid-fill me-2"></i>Dashboard</a>
                    <a href="/inventory" class="btn btn-light btn-modern shadow-sm border"><i class="bi bi-box-seam-fill me-2"></i>Inventory</a>
                    <a href="/portal" target="_blank" class="btn btn-dark btn-modern shadow-sm"><i class="bi bi-box-arrow-up-right me-2"></i>Portal</a>
                </div>
            </nav>
        {{-- Global Alerts --}}
        @if(session('success')) <div class="alert alert-success bento-card mb-4 text-success fw-bold py-3"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div> @endif
        @if(session('danger')) <div class="alert alert-danger bento-card mb-4 text-danger fw-bold py-3"><i class="bi bi-x-circle-fill me-2"></i>{{ session('danger') }}</div> @endif

        {{-- Page Content Injected Here --}}
        {{ $slot }}

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{ $scripts ?? '' }}
</body>
</html>