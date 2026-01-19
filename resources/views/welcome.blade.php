<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Scheduling System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #06b6d4;
            --primary-dark: #0891b2;
            --primary-light: #22d3ee;
            --bg-dark: #0f172a;
            --bg-darker: #020617;
            --card-bg: #1e293b;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-darker);
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
        }

        /* Header */
        .header {
            padding: 80px 0 60px;
            text-align: center;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 16px;
            font-size: 2rem;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .access-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .access-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: rgba(6, 182, 212, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .card-description {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .credentials {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .credential-item {
            background: var(--bg-darker);
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .credential-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-color);
            margin-bottom: 6px;
        }

        .credential-value {
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            font-size: 0.95rem;
            color: #fff;
            user-select: all;
        }

        /* Student Section */
        .student-section {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.05), rgba(34, 211, 238, 0.05));
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 20px;
            padding: 48px 32px;
            text-align: center;
            margin-bottom: 48px;
        }

        .student-icon {
            width: 80px;
            height: 80px;
            background: rgba(6, 182, 212, 0.1);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 24px;
        }

        .student-section h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .student-section p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 24px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-schedule {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            color: #fff;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-schedule:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.3);
            color: #fff;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--primary-color);
        }

        .footer-text {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 60px 0 40px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .student-section {
                padding: 32px 24px;
            }
        }

        /* Copy Animation */
        @keyframes copied {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .copied-message {
            animation: copied 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        
        <!-- Header -->
        <header class="header">
            <div class="logo">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <h1>Exam Scheduling System</h1>
            <p>Streamlined university exam management with automated scheduling and real-time analytics</p>
        </header>

        <!-- Access Cards -->
        <div class="cards-grid">
            
            <!-- Exam Admin -->
            <div class="access-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h3 class="card-title">Exam Administrator</h3>
                </div>
                <p class="card-description">
                    Complete system control. Generate schedules, manage exam sessions, and configure all system settings.
                </p>
                <div class="credentials">
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">admin@university.edu</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-key"></i>
                            Password
                        </div>
                        <div class="credential-value">admin123</div>
                    </div>
                </div>
            </div>

            <!-- Dean -->
            <div class="access-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <h3 class="card-title">Dean / Vice-Dean</h3>
                </div>
                <p class="card-description">
                    Strategic oversight. Monitor university-wide statistics, approve schedules, and view performance metrics.
                </p>
                <div class="credentials">
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">dean@university.edu</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-key"></i>
                            Password
                        </div>
                        <div class="credential-value">dean123</div>
                    </div>
                </div>
            </div>

            <!-- Department Head -->
            <div class="access-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <h3 class="card-title">Department Head</h3>
                </div>
                <p class="card-description">
                    Department management. Access your department's schedules, validate exams, and manage resources.
                </p>
                <div class="credentials">
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </div>
                        <div class="credential-value">head@cs.university.edu</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">
                            <i class="bi bi-key"></i>
                            Password
                        </div>
                        <div class="credential-value">head123</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Student Section -->
        <section class="student-section">
            <div class="student-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h2>Students Access</h2>
            <p>
                Students can view their personalized exam schedule without authentication. 
                Simply access the public schedule page and filter by your group to see all your upcoming exams.
            </p>
            <a href="/schedule" class="btn-schedule">
                <i class="bi bi-calendar3"></i>
                View Exam Schedule
            </a>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-links">
                <a href="#" class="footer-link">
                    <i class="bi bi-info-circle"></i> About
                </a>
                <a href="#" class="footer-link">
                    <i class="bi bi-question-circle"></i> Help Center
                </a>
                <a href="#" class="footer-link">
                    <i class="bi bi-file-text"></i> Documentation
                </a>
                <a href="#" class="footer-link">
                    <i class="bi bi-envelope"></i> Contact
                </a>
            </div>
            <p class="footer-text">
                © 2026 University Exam Scheduling System. All rights reserved.
            </p>
        </footer>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Copy to Clipboard -->
    <script>
        document.querySelectorAll('.credential-value').forEach(element => {
            element.style.cursor = 'pointer';
            element.title = 'Click to copy';
            
            element.addEventListener('click', function() {
                const text = this.textContent.trim();
                navigator.clipboard.writeText(text).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<span class="copied-message" style="color: var(--primary-color);">✓ Copied to clipboard</span>';
                    
                    setTimeout(() => {
                        this.innerHTML = original;
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
