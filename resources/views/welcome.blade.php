<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exam Scheduling System</title>
    
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
            margin: 24px;
            position: relative;
            color: #333;
            min-height: 100vh;
            overflow-x: hidden;
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

        /* Floating shapes for subtle depth */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            opacity: 0.03;
            z-index: -1;
            animation: float 25s ease-in-out infinite;
        }

        .bg-shape:nth-child(1) {
            width: 400px;
            height: 400px;
            background: #111;
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 500px;
            height: 500px;
            background: #333;
            bottom: -250px;
            right: -250px;
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 350px;
            height: 350px;
            background: #555;
            top: 50%;
            right: -175px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -30px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* User Info Bar */
        .user-info {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 100;
            display: flex;
            gap: 12px;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid rgba(221, 221, 221, 0.5);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .user-name {
            font-size: 13px;
            color: #555;
        }

        .user-role {
            font-size: 11px;
            color: #999;
            text-transform: capitalize;
        }

        .btn-logout {
            padding: 6px 12px;
            font-size: 12px;
            background: #fff;
            color: #111;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #f5f5f5;
            border-color: #111;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 48px 0 32px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: #111;
            border-radius: 12px;
            font-size: 28px;
            margin-bottom: 16px;
            color: white;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111;
        }

        .header p {
            color: #777;
            font-size: 15px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Section Title */
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #111;
            margin: 32px 0 16px;
            text-align: center;
        }

        /* Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(221, 221, 221, 0.5);
            border-radius: 8px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: #111;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: #f5f5f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            color: #333;
            transition: all 0.3s ease;
        }

        .card:hover .card-icon {
            background: #111;
            color: white;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #111;
        }

        .card-description {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .credentials {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .credential-item {
            background: rgba(249, 249, 249, 0.8);
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .credential-item:hover {
            background: rgba(245, 245, 245, 0.9);
            transform: translateX(4px);
        }

        .credential-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .credential-value {
            font-family: monospace;
            font-size: 14px;
            color: #111;
            user-select: all;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #333;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Student Section */
        .student-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(221, 221, 221, 0.5);
            border-radius: 8px;
            padding: 40px 32px;
            text-align: center;
            margin-bottom: 32px;
            transition: all 0.3s ease;
        }

        .student-section:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .student-icon {
            width: 64px;
            height: 64px;
            background: #f5f5f5;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .student-section h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #111;
        }

        .student-section p {
            color: #777;
            font-size: 14px;
            margin-bottom: 24px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .btn-schedule {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-schedule:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: white;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 32px 0;
            border-top: 1px solid rgba(238, 238, 238, 0.5);
        }

        .footer-text {
            color: #999;
            font-size: 13px;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #111;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { margin: 16px; }
            .header { padding: 32px 0 24px; }
            .header h1 { font-size: 24px; }
            .cards-grid { grid-template-columns: 1fr; }
            .student-section { padding: 32px 24px; }
            .bg-shape { display: none; }
            .user-info { top: 8px; right: 8px; padding: 6px 12px; }
        }
    </style>
</head>
<body>
    <!-- Floating background shapes -->
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

    <!-- User Info (if authenticated) -->
    @auth
    <div class="user-info">
        <div>
            <div class="user-name">{{ auth()->user()->name }}</div>
            <div class="user-role">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </form>
    </div>
    @endauth

    <div class="container">
        
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h1>Exam Scheduling System</h1>
            <p>Streamlined university exam management with automated scheduling and real-time analytics</p>
        </header>

        <!-- Admin Access Section -->
        <h3 class="section-title">👥 Administrative Access</h3>

        <!-- Access Cards -->
        <div class="cards-grid">
            
            <!-- Exam Admin -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="card-title">Exam Administrator</h3>
                </div>
                <p class="card-description">
                    Complete system control. Generate schedules, manage exam sessions, and configure all system settings.
                </p>
                <div class="credentials">
                    <div class="credential-item" onclick="copyText(this, 'admin@university.edu')">
                        <div class="credential-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">admin@university.edu</div>
                    </div>
                    <div class="credential-item" onclick="copyText(this, 'admin123')">
                        <div class="credential-label">
                            <i class="fa-solid fa-key"></i>
                            Password
                        </div>
                        <div class="credential-value">admin123</div>
                    </div>
                </div>
                <a href="/admin/login?email=admin@university.edu" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Admin Login
                </a>
            </div>

            <!-- Dean -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <h3 class="card-title">Dean / Vice-Dean</h3>
                </div>
                <p class="card-description">
                    Strategic oversight. Monitor university-wide statistics, approve schedules, and view performance metrics.
                </p>
                <div class="credentials">
                    <div class="credential-item" onclick="copyText(this, 'dean@university.edu')">
                        <div class="credential-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">dean@university.edu</div>
                    </div>
                    <div class="credential-item" onclick="copyText(this, 'dean123')">
                        <div class="credential-label">
                            <i class="fa-solid fa-key"></i>
                            Password
                        </div>
                        <div class="credential-value">dean123</div>
                    </div>
                </div>
                <a href="/admin/login?email=dean@university.edu" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Admin Login
                </a>
            </div>

            <!-- Department Head -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h3 class="card-title">Department Head</h3>
                </div>
                <p class="card-description">
                    Department management. Access your department's schedules, validate exams, and manage resources.
                </p>
                <div class="credentials">
                    <div class="credential-item" onclick="copyText(this, 'head@cs.university.edu')">
                        <div class="credential-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">head@cs.university.edu</div>
                    </div>
                    <div class="credential-item" onclick="copyText(this, 'head123')">
                        <div class="credential-label">
                            <i class="fa-solid fa-key"></i>
                            Password
                        </div>
                        <div class="credential-value">head123</div>
                    </div>
                </div>
                <a href="/admin/login?email=head@cs.university.edu" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Admin Login
                </a>
            </div>

        </div>

        <!-- User Access Section -->
        <h3 class="section-title">🎓 User Access</h3>

        <div class="cards-grid">
            
            <!-- Professor -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <h3 class="card-title">Professor</h3>
                </div>
                <p class="card-description">
                    View supervision assignments, check your exam schedule, and access department timetables.
                </p>
                <div class="credentials">
                    <div class="credential-item" onclick="copyText(this, 'professor@university.edu')">
                        <div class="credential-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">professor@university.edu</div>
                    </div>
                    <div class="credential-item" onclick="copyText(this, 'professor123')">
                        <div class="credential-label">
                            <i class="fa-solid fa-key"></i>
                            Password
                        </div>
                        <div class="credential-value">professor123</div>
                    </div>
                </div>
                <a href="{{ route('login') }}?email=professor@university.edu" class="btn-login" onclick="storeCredentials('professor@university.edu', 'professor123')">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Professor Login
                </a>
            </div>

            <!-- Student -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <h3 class="card-title">Student</h3>
                </div>
                <p class="card-description">
                    Access your personalized exam schedule with dates, times, rooms, and all exam details.
                </p>
                <div class="credentials">
                    <div class="credential-item" onclick="copyText(this, 'student@university.edu')">
                        <div class="credential-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">student@university.edu</div>
                    </div>
                    <div class="credential-item" onclick="copyText(this, 'student123')">
                        <div class="credential-label">
                            <i class="fa-solid fa-key"></i>
                            Password
                        </div>
                        <div class="credential-value">student123</div>
                    </div>
                </div>
                <a href="{{ route('login') }}?email=student@university.edu" class="btn-login" onclick="storeCredentials('student@university.edu', 'student123')">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Student Login
                </a>
            </div>

        </div>

        <!-- Quick Access Section -->
        <section class="student-section">
            <div class="student-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <h2>Exam Schedule Viewer</h2>
            <p>
                View personalized exam schedules with detailed timetables, room assignments, and professor supervision information. 
                Filter by department, specialty, level, and group to find your exams.
            </p>
            @auth
                <a href="{{ route('planning.index') }}" class="btn-schedule">
                    <i class="fa-solid fa-calendar-check"></i>
                    View Exam Schedule
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-schedule">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login to View Schedule
                </a>
            @endauth
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p class="footer-text">
                © 2026 University Exam Scheduling System. All rights reserved.
            </p>
        </footer>

    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fa-solid fa-check"></i>
        Copied to clipboard
    </div>

    <script>
        function copyText(element, text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }

        function storeCredentials(email, password) {
            sessionStorage.setItem('prefill_email', email);
            sessionStorage.setItem('prefill_password', password);
        }
    </script>
</body>
</html>
