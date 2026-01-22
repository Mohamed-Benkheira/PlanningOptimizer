<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Exam Scheduling System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            position: relative;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef3 25%, #f9f9f9 50%, #e3e9f0 75%, #f5f7fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating shapes */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            opacity: 0.03;
            z-index: -1;
            animation: float 25s ease-in-out infinite;
        }

        .bg-shape:nth-child(1) {
            width: 300px;
            height: 300px;
            background: #111;
            top: -150px;
            left: -150px;
        }

        .bg-shape:nth-child(2) {
            width: 400px;
            height: 400px;
            background: #333;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 24px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(221, 221, 221, 0.5);
            border-radius: 12px;
            padding: 48px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: #111;
            border-radius: 12px;
            font-size: 32px;
            margin: 0 auto 24px;
            color: white;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        h1 {
            font-size: 26px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
            color: #111;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        label i {
            margin-right: 6px;
            color: #777;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #111;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin: 0;
            cursor: pointer;
        }

        .remember-me label {
            margin: 0;
            font-size: 14px;
            font-weight: 400;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            background: rgba(255, 243, 243, 0.95);
            border: 1px solid #ffcccc;
            color: #cc0000;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(221, 221, 221, 0.5);
        }

        .back-link a {
            color: #777;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            color: #111;
            transform: translateX(-2px);
        }

        .admin-link {
            text-align: center;
            margin-top: 12px;
            font-size: 12px;
            color: #999;
        }

        .admin-link a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
        }

        .admin-link a:hover {
            color: #111;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 16px;
            }
            .login-card {
                padding: 36px 28px;
            }
            .bg-shape {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to access your exam schedule</p>

            @if ($errors->any())
                <div class="error-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">
                        <i class="fa-solid fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                        placeholder="your.email@university.edu"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fa-solid fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Enter your password"
                    >
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <div class="back-link">
                <a href="{{ route('home') }}">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to home</span>
                </a>
            </div>

            <div class="admin-link">
                Administrator? <a href="/admin/login">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill credentials if passed via URL or sessionStorage
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Check URL parameters first
            const urlParams = new URLSearchParams(window.location.search);
            const urlEmail = urlParams.get('email');
            
            // Check sessionStorage
            const storedEmail = sessionStorage.getItem('prefill_email');
            const storedPassword = sessionStorage.getItem('prefill_password');
            
            // Pre-fill email from URL or storage
            if (urlEmail) {
                emailInput.value = urlEmail;
            } else if (storedEmail) {
                emailInput.value = storedEmail;
            }
            
            // Pre-fill password from storage
            if (storedPassword) {
                passwordInput.value = storedPassword;
            }
            
            // Clear stored credentials after using them
            sessionStorage.removeItem('prefill_email');
            sessionStorage.removeItem('prefill_password');
            
            // Focus password field if email is filled, otherwise focus email
            if (emailInput.value) {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
