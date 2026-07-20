<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | RMPH Supply Hub</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/supply-logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f1f5f9; 
            color: #0f172a; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .bento-card { 
            background: #ffffff; 
            border-radius: 1.5rem; 
            border: none; 
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); 
            padding: 3rem 2.5rem; 
            width: 100%; 
            max-width: 420px; 
        }
        .input-modern { 
            border-radius: 0.75rem; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc; 
            padding: 0.75rem 1rem; 
            width: 100%; 
            transition: all 0.2s;
        }
        .input-modern:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            outline: none;
            background-color: #ffffff;
        }
        .btn-modern { 
            border-radius: 0.75rem; 
            font-weight: 700; 
            padding: 0.75rem 1.2rem; 
        }
        .brand-logo { 
            height: 85px; 
            width: auto; 
            margin-bottom: 1.5rem; 
        }
    </style>
</head>
<body>

    <div class="bento-card text-center">
        {{-- Logo & Header --}}
        <img src="{{ asset('images/supply-logo2.png') }}" alt="RMPH Supply Logo" class="brand-logo">
        <h4 class="fw-bolder mb-1">Welcome Back</h4>
        <p class="text-muted small mb-4">Sign in to access the Supply Hub</p>

        {{-- Login Form --}}
        <form method="POST" action="{{ route('login') }}" class="text-start">
            @csrf
            
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control input-modern border-start-0 rounded-end-4 pl-0 @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="name@rmph.gov.ph">
                </div>
                @error('email')
                    <div class="text-danger small fw-bold mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label text-muted small fw-bold text-uppercase">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-4"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control input-modern border-start-0 rounded-end-4 pl-0 @error('password') is-invalid @enderror" name="password" required placeholder="••••••••">
                </div>
                @error('password')
                    <div class="text-danger small fw-bold mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-primary btn-modern shadow-sm py-3">
                    Sign In <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
    </div>

</body>
</html>