<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'QMO Approver | Supply Hub' }}</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/supply-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; }
        .bento-card { background: #ffffff; border-radius: 1.25rem; border: none; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); padding: 1.5rem; }
        .input-modern { border-radius: 0.75rem; border: 1px solid #e2e8f0; background-color: #f8fafc; padding: 0.6rem 1rem; width: 100%; }
        .btn-modern { border-radius: 0.75rem; font-weight: 700; padding: 0.6rem 1.2rem; }
        
        .table-clean th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; }
        .table-clean td { vertical-align: middle; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }

        @media (max-width: 768px) {
            .nav-actions { flex-direction: column; width: 100%; }
            .nav-actions a { width: 100%; margin-bottom: 0.5rem; }
            .brand-logo { height: 60px !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid container-lg py-4">
        <nav class="d-flex flex-wrap justify-content-between align-items-center mb-5 gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" class="brand-logo" style="height: 70px; width: auto;">
                <div>
                    <h4 class="mb-0 fw-bolder">Supply Hub</h4>
                    <span class="text-primary small fw-bold">Quality Management Office</span>
                </div>
            </div>
            
            <div class="d-flex flex-wrap align-items-center gap-2 nav-actions">
                {{-- These link strictly to her specific routes --}}
                <a href="/approver/dashboard" class="btn btn-light btn-modern shadow-sm border"><i class="bi bi-grid-fill me-2"></i>Dashboard</a>
                <a href="/approver/inventory" class="btn btn-light btn-modern shadow-sm border"><i class="bi bi-box-seam-fill me-2"></i>View Inventory</a>
                
                {{-- User Profile & Logout --}}
                <div class="ms-md-3 d-flex align-items-center gap-3 border-start ps-md-3 pt-2 pt-md-0 border-secondary-subtle">
                    @auth
                        <span class="fw-bold text-dark"><i class="bi bi-person-circle text-primary me-1"></i> {{ Auth::user()->name }}</span>
                        <form method="POST" action="/logout" class="d-inline m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-modern shadow-sm">
                                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </nav>

        @if(session('success')) <div class="alert alert-success bento-card mb-4 text-success fw-bold py-3"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div> @endif
        @if(session('danger')) <div class="alert alert-danger bento-card mb-4 text-danger fw-bold py-3"><i class="bi bi-x-circle-fill me-2"></i>{{ session('danger') }}</div> @endif

        {{ $slot }}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{ $scripts ?? '' }}
</body>
</html>