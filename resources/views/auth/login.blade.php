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
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
            background:
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.10), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.10), transparent 30%),
                #f5f8fc;

            display: flex;
            align-items: center;
            justify-content: center;

            position: relative;
            overflow: hidden;
        }

        /* =========================
           BACKGROUND DECORATIONS
        ========================= */

        .background-shape {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .shape-one {
            width: 420px;
            height: 420px;
            top: -230px;
            left: -150px;
            background: rgba(37, 99, 235, 0.055);
        }

        .shape-two {
            width: 500px;
            height: 500px;
            right: -270px;
            bottom: -280px;
            background: rgba(14, 165, 233, 0.055);
        }

        .shape-three {
            width: 180px;
            height: 180px;
            right: 12%;
            top: 8%;
            border: 35px solid rgba(37, 99, 235, 0.025);
        }

        /* =========================
           MAIN CONTAINER
        ========================= */

        .login-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 24px;
            position: relative;
            z-index: 2;
        }

        /* =========================
           LOGIN CARD
        ========================= */

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 26px;
            padding: 42px 40px 36px;

            box-shadow:
                0 25px 60px rgba(15, 23, 42, 0.08),
                0 5px 20px rgba(15, 23, 42, 0.03);

            position: relative;
            overflow: hidden;
        }

        /* Top blue accent */
        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(
                90deg,
                #1d4ed8,
                #2563eb,
                #0ea5e9
            );
        }

        /* =========================
           BRAND
        ========================= */

        .brand-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-container {
            width: 105px;
            height: 105px;
            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #ffffff;
            border-radius: 24px;

            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08);

            border: 1px solid #eef2f7;
        }

        .brand-logo {
            max-width: 82px;
            max-height: 82px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .brand-name {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.4px;
            color: #2563eb;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .welcome-title {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.7px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 13.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.6;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 21px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);

            color: #94a3b8;
            font-size: 17px;

            pointer-events: none;
            transition: color 0.2s ease;

            z-index: 3;
        }

        .form-control-modern {
            width: 100%;
            height: 52px;

            padding: 0 48px;

            border: 1px solid #dbe3ed;
            border-radius: 13px;

            background: #f8fafc;

            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background-color 0.2s ease;
        }

        .form-control-modern::placeholder {
            color: #a8b3c2;
        }

        .form-control-modern:hover {
            border-color: #cbd5e1;
        }

        .form-control-modern:focus {
            background: #ffffff;
            border-color: #2563eb;

            box-shadow:
                0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        .input-wrapper:focus-within .input-icon {
            color: #2563eb;
        }

        /* Password button */

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #94a3b8;
            font-size: 17px;

            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            cursor: pointer;
            transition: all 0.2s ease;

            z-index: 3;
        }

        .password-toggle:hover {
            background: #eef2f7;
            color: #2563eb;
        }

        /* =========================
           VALIDATION
        ========================= */

        .input-error {
            border-color: #ef4444 !important;
            background: #fffafa;
        }

        .input-error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10);
        }

        .error-message {
            display: flex;
            align-items: center;
            gap: 5px;

            margin-top: 7px;

            font-size: 11.5px;
            font-weight: 600;

            color: #dc2626;
        }

        /* =========================
           REMEMBER ME
        ========================= */

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -4px 0 20px;
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }

        .remember-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
            border-radius: 4px;
        }

        /* =========================
           SIGN IN BUTTON
        ========================= */

        .btn-signin {
            width: 100%;
            height: 53px;

            border: none;
            border-radius: 13px;

            background: linear-gradient(
                135deg,
                #1d4ed8,
                #2563eb
            );

            color: #ffffff;

            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            cursor: pointer;

            box-shadow:
                0 8px 18px rgba(37, 99, 235, 0.22);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .btn-signin:hover {
            transform: translateY(-1px);

            box-shadow:
                0 12px 25px rgba(37, 99, 235, 0.28);

            filter: brightness(1.03);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        .btn-signin i {
            font-size: 17px;
            transition: transform 0.2s ease;
        }

        .btn-signin:hover i {
            transform: translateX(3px);
        }

        /* =========================
           SECURITY NOTE
        ========================= */

        .security-note {
            margin-top: 25px;
            padding-top: 21px;

            border-top: 1px solid #eef2f7;

            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;

            font-size: 10.5px;
            color: #94a3b8;
        }

        .security-note i {
            color: #64748b;
            font-size: 13px;
        }

        /* =========================
           FOOTER
        ========================= */

        .login-footer {
            text-align: center;
            margin-top: 18px;

            font-size: 10.5px;
            color: #94a3b8;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 576px) {

            body {
                align-items: center;
            }

            .login-wrapper {
                padding: 16px;
            }

            .login-card {
                padding: 36px 24px 28px;
                border-radius: 22px;
            }

            .logo-container {
                width: 90px;
                height: 90px;
                border-radius: 20px;
            }

            .brand-logo {
                max-width: 70px;
                max-height: 70px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .welcome-subtitle {
                font-size: 12.5px;
            }

            .shape-three {
                display: none;
            }
        }

        @media (max-height: 700px) {

            .login-card {
                padding-top: 32px;
                padding-bottom: 25px;
            }

            .brand-section {
                margin-bottom: 24px;
            }

            .logo-container {
                width: 82px;
                height: 82px;
                margin-bottom: 14px;
            }

            .brand-logo {
                max-width: 65px;
                max-height: 65px;
            }
        }
    </style>
</head>

<body>

    {{-- Background Decorations --}}
    <div class="background-shape shape-one"></div>
    <div class="background-shape shape-two"></div>
    <div class="background-shape shape-three"></div>


    <main class="login-wrapper">

        <section class="login-card">

            {{-- =========================
                 BRAND / HEADER
            ========================== --}}
            <div class="brand-section">

                <div class="logo-container">
                    <img
                        src="{{ asset('images/supply-logo2.png') }}"
                        alt="RMPH Supply Hub Logo"
                        class="brand-logo"
                    >
                </div>

                <div class="brand-name">
                    RMPH Supply Hub
                </div>

                <h1 class="welcome-title">
                    Welcome Back
                </h1>

                <p class="welcome-subtitle">
                    Sign in to manage and access your<br class="d-none d-sm-block">
                    supply management workspace.
                </p>

            </div>


            {{-- =========================
                 LOGIN FORM
            ========================== --}}
            @if(session('warning'))
                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 mb-3 small fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-clockwise fs-6"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="form-group">

                    <label for="email" class="form-label">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-envelope input-icon"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control-modern @error('email') input-error @enderror"
                            placeholder="name@rmph.gov.ph"
                            autocomplete="email"
                            required
                            autofocus
                        >

                    </div>

                    @error('email')
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                </div>


                {{-- Password --}}
                <div class="form-group">

                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-lock input-icon"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control-modern @error('password') input-error @enderror"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                    @error('password')
                        <div class="error-message">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                </div>


                {{-- Keep Me Signed In --}}
                <div class="remember-row">
                    <label for="remember" class="remember-label">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            class="remember-checkbox"
                            {{ old('remember', true) ? 'checked' : '' }}
                        >
                        <span>Keep me signed in</span>
                    </label>
                </div>


                {{-- Sign In --}}
                <button type="submit" class="btn-signin">
                    <span>Sign In</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>


            {{-- Security --}}
            <div class="security-note">
                <i class="bi bi-shield-check"></i>
                <span>Your connection is protected and secure</span>
            </div>

        </section>


        {{-- Footer --}}
        <div class="login-footer">
            © {{ date('Y') }} RMPH Supply Hub &nbsp;•&nbsp; Supply Management System
        </div>

    </main>


    {{-- =========================
         PASSWORD VISIBILITY
    ========================== --}}
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {

            const isPassword = password.type === 'password';

            password.type = isPassword ? 'text' : 'password';

            this.innerHTML = isPassword
                ? '<i class="bi bi-eye-slash"></i>'
                : '<i class="bi bi-eye"></i>';

            this.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );
        });
    </script>

</body>
</html>

