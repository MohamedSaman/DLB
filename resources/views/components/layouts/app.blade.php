<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HARDMEN PVT LTD' }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <!-- Custom CSS -->
    <style>
        /* Login page styling */
        .login-container {
            height: 100vh;
            width: 100vw;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../images/bgproduct.jpg');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }

        .login-form-overlay {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            z-index: 1;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .user-icon-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .user-icon-container i {
            font-size: 3rem;
            color: #000000;
            background: #f0f0f0;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #ddd;
        }

        .form-options {
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .forgot-link {
            color: #000000;
            text-decoration: none;
        }

        .login-btn {
            width: 100%;
            border-radius: 25px;
            padding: 10px;
            background-color: #0d6efd;
            border: none;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: #ddd;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            display: inline-block;
            padding: 0 10px;
            background-color: #fff;
            position: relative;
            z-index: 1;
            color: #777;
            font-size: 0.9rem;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #f5f5f5;
            color: #000000;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
    @stack('styles')
</head>

<body>
    {{ $slot }}

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireScripts
    <script>
        // Handle Livewire session/CSRF expiry (HTTP 419) - prevents buttons getting permanently stuck disabled
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Session Expired',
                            text: 'Your session has expired. The page will reload automatically.',
                            confirmButtonText: 'Reload Now',
                            confirmButtonColor: '#2a83df',
                            timer: 4000,
                            timerProgressBar: true,
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>

</html>