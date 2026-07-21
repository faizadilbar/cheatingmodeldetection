<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — EduQuiz</title>

    <!-- Bricolage Grotesque & DM Sans — matching landing page -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --deep:  #3D52A0;
            --mid:   #7091E6;
            --slate: #8697C4;
            --mist:  #ADBBDA;
            --snow:  #EDE8F5;
            --bg-bright: linear-gradient(135deg, #F5F7FF 0%, #FFFDF5 100%);
            --white: #FFFFFF;
            --text-h:#18213D;
            --text-b:#4A5478;
            --text-m:#8697C4;
            --border:rgba(112,145,230,0.16);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; width: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            background: #F4F6FC;
            padding-top: calc(env(safe-area-inset-top, 0px) + 6px);
        }

        /* ═══════════════════════════════════════
           SPLIT CONTAINER
        ═══════════════════════════════════════ */
        .split-container {
            display: flex;
            width: 100%;
            height: 100vh;
            min-height: 650px;
            background: var(--white);
            overflow: hidden;
            position: relative;
        }

        /* ═══════════════════════════════════════
           LEFT PANEL: BRIGHT SHINY FORM AREA
        ═══════════════════════════════════════ */
        .left-panel {
            width: 540px;
            flex-shrink: 0;
            background: var(--bg-bright);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: calc(env(safe-area-inset-top, 0px) + 50px) 50px 44px;
            position: relative;
            z-index: 10;
            overflow-y: auto;
            border-right: 1px solid rgba(112,145,230,0.12);
        }

        /* Interactive soft glowing halo behind card content */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(112,145,230,0.18) 0%, transparent 70%);
            border-radius: 50%;
            top: 25%; left: 15%;
            z-index: -1;
            filter: blur(40px);
        }

        .form-wrap {
            margin: auto 0;
            width: 100%;
            animation: formReveal 0.7s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        @keyframes formReveal {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* LOGO HEADER */
        .logo-header {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 40px;
        }

        .logo-box {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
            box-shadow: 0 6px 18px rgba(61,82,160,0.22);
        }

        .logo-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 28px; font-weight: 800;
            color: var(--text-h);
        }
        .logo-text span { color: var(--mid); }

        /* FORM HEADER */
        .form-title {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--text-h);
            letter-spacing: -0.8px;
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 14.5px;
            color: var(--text-m);
            margin-bottom: 32px;
            line-height: 1.55;
        }

        /* ── ALERTS ── */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 14px;
            color: #991B1B;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 22px;
            animation: alertIn 0.3s ease;
        }

        @keyframes alertIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* ── DYNAMIC GLOW CONTAINER FOR INPUTS ── */
        .form-card-glow {
            position: relative;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(112, 145, 230, 0.15);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(61, 82, 160, 0.05);
            backdrop-filter: blur(10px);
            margin-bottom: 24px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* Glow effect surrounding the form card */
        .form-card-glow:focus-within {
            border-color: rgba(112, 145, 230, 0.4);
            box-shadow: 0 12px 40px rgba(112, 145, 230, 0.15),
                        0 0 0 1px rgba(112, 145, 230, 0.1);
        }

        /* ── PILL INPUT FIELDS ── */
        .f-group {
            margin-bottom: 22px;
        }

        .f-group:last-child {
            margin-bottom: 0;
        }

        .f-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-b);
            margin-bottom: 8px;
            padding-left: 4px;
        }

        .input-box {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate);
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .f-input {
            width: 100%;
            padding: 15px 16px 15px 48px;
            background: #fff;
            border: 1.5px solid rgba(112, 145, 230, 0.16);
            border-radius: 50px; /* Rounded pill style */
            color: var(--text-h);
            font-family: inherit;
            font-size: 14.5px;
            outline: none;
            transition: all 0.25s ease;
            box-shadow: inset 0 1px 3px rgba(61, 82, 160, 0.03);
        }

        .f-input::placeholder {
            color: var(--mist);
        }

        .f-input:focus {
            border-color: var(--mid);
            box-shadow: 0 0 0 4px rgba(112, 145, 230, 0.14),
                        inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .input-box:focus-within .input-icon {
            color: var(--mid);
        }

        /* Eye toggle */
        .btn-eye {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--slate);
            font-size: 14px;
            padding: 4px;
            transition: color 0.2s ease;
        }
        .btn-eye:hover { color: var(--mid); }

        /* ── ROUNDED PILL BUTTON ── */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff;
            border: none;
            border-radius: 50px; /* Pill shape matches inspiration */
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(61,82,160,0.25);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Shimmer */
        .btn-submit::after {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.22), transparent);
            transform: skewX(-20deg);
            animation: sweep 3s infinite;
        }

        @keyframes sweep {
            0% { left: -100%; }
            45%, 100% { left: 140%; }
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(61,82,160,0.35);
        }

        .btn-submit:active { transform: translateY(0); }

        .btn-submit.loading .btn-txt { display: none; }
        .btn-submit.loading .spin-wrap { display: flex; }

        .spin-wrap {
            display: none; align-items: center; gap: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 14.5px;
        }

        .spinner {
            width: 18px; height: 18px;
            border: 2.5px solid rgba(255,255,255,0.35);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* BACK BUTTON */
        .back-wrap {
            text-align: center;
            margin-top: 24px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            color: var(--text-m);
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            color: var(--deep);
            transform: translateX(-2px);
        }
        .back-btn i { font-size: 11px; }

        /* ═══════════════════════════════════════
           RIGHT PANEL: PHOTO WITH FLOATING GLASS UI
        ═══════════════════════════════════════ */
        .right-panel {
            flex: 1.3;
            position: relative;
            background: #111;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0.95;
            z-index: 1;
        }

        /* Vignette gradient overlay */
        .right-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(244,246,252,0.1) 0%, transparent 60%),
                        linear-gradient(to bottom, rgba(16,22,58,0.2) 0%, rgba(16,22,58,0.65) 100%);
            z-index: 2;
        }

        /* ── FLOATING GLASS UI ELEMENTS ── */
        .glass-element {
            position: absolute;
            z-index: 5;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(16,22,58,0.18);
            animation: floatUpDown 5s ease-in-out infinite;
        }

        @keyframes floatUpDown {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Glass Element 1: Calendar scheduling strip */
        .glass-calendar {
            bottom: 60px;
            left: 5%;
            right: 5%;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            animation-delay: 0s;
        }

        .cal-days {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .cal-day {
            text-align: center;
            padding: 6px 10px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .cal-day-name {
            font-size: 11px;
            color: var(--text-b);
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .cal-day-num {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-h);
        }

        .cal-day.active {
            background: linear-gradient(135deg, var(--deep), var(--mid));
            box-shadow: 0 4px 12px rgba(61,82,160,0.3);
        }
        .cal-day.active .cal-day-name { color: rgba(255,255,255,0.8); }
        .cal-day.active .cal-day-num { color: #fff; }

        /* Glass Element 2: Scheduled Quiz Agenda Card */
        .glass-agenda {
            top: 40px;
            left: 40px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 250px;
            animation-delay: 1.5s;
        }

        .agenda-icon {
            width: 38px; height: 38px;
            background: rgba(112, 145, 230, 0.15);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--deep); font-size: 15px;
        }

        .agenda-info {}
        .agenda-title { font-size: 13.5px; font-weight: 700; color: var(--text-h); }
        .agenda-time { font-size: 11.5px; color: var(--text-b); margin-top: 2px; }

        /* Glass Element 3: Floating avatars */
        .glass-avatars {
            top: 140px;
            right: 40px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation-delay: 3s;
        }

        .avatar-pile {
            display: flex;
        }

        .avatar-circle {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            background: linear-gradient(135deg, var(--mid), var(--deep));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 9px; font-weight: 700;
            margin-left: -6px;
        }
        .avatar-circle:first-child { margin-left: 0; }

        .avatar-text { font-size: 11.5px; font-weight: 600; color: var(--text-h); }

        /* ═══════════════════════════════════════
           RESPONSIVE DESIGN
        ═══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .left-panel {
                width: 460px;
                padding: 40px;
            }
            .form-title { font-size: 28px; }
            .glass-agenda { left: 20px; top: 20px; }
            .glass-avatars { right: 20px; top: 100px; }
        }

        @media (max-width: 900px) {
            .split-container {
                height: auto;
                min-height: 100vh;
                background: linear-gradient(135deg, #F5F7FF 0%, #FFFDF5 100%);
                justify-content: center;
                align-items: center;
                padding: 30px 16px;
            }
            .left-panel {
                width: 100%;
                max-width: 440px;
                height: auto;
                background: transparent;
                border-right: none;
                padding: 0;
            }
            .form-card-glow {
                background: rgba(255, 255, 255, 0.95);
                box-shadow: 0 16px 48px rgba(61, 82, 160, 0.1);
            }
            .right-panel {
                display: none; /* Hide photo side on mobile */
            }
        }

        @media (max-height: 680px) and (min-width: 901px) {
            .left-panel { padding-top: 30px; padding-bottom: 30px; }
            .logo-header { margin-bottom: 20px; }
            .form-title { font-size: 26px; }
            .form-subtitle { margin-bottom: 20px; }
            .form-card-glow { padding: 22px 24px; }
            .f-group { margin-bottom: 16px; }
        }
    </style>
</head>
<body>

    <div class="split-container">
        <!-- ═══ LEFT PANEL (FORM AREA) ═══ -->
        <div class="left-panel">

            <!-- Top Header Logo -->
            <a href="{{ route('home.landing') }}" class="logo-header">
                <div class="logo-box">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="logo-text">Edu<span>Quiz</span></span>
            </a>

            <div class="form-wrap">
                <h2 class="form-title">Welcome back</h2>
                <p class="form-subtitle">Enter your credentials to access your dashboard.</p>

                <!-- Error messages -->
                @if($errors->any())
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Form Card Glow wrapper -->
                <div class="form-card-glow">
                    <form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
                        @csrf

                        <div class="f-group">
                            <label class="f-label" for="email_field">Email or Roll Number</label>
                            <div class="input-box">
                                <i class="fas fa-user input-icon"></i>
                                <input
                                    type="text"
                                    id="email_field"
                                    name="email"
                                    class="f-input"
                                    placeholder="your@email.com or roll number"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <div class="f-group">
                            <label class="f-label" for="pw_field">Password</label>
                            <div class="input-box">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="pw_field"
                                    name="password"
                                    class="f-input"
                                    placeholder="Enter your password"
                                    required
                                >
                                <button type="button" class="btn-eye" id="pwToggle" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="loginBtn">
                            <span class="btn-txt">
                                <i class="fas fa-sign-in-alt"></i>&ensp;Sign In to Portal
                            </span>
                            <span class="spin-wrap">
                                <span class="spin spinner"></span> Signing in...
                            </span>
                        </button>
                    </form>
                </div>

                <div class="back-wrap">
                    <a href="{{ route('home.landing') }}" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to landing page
                    </a>
                </div>
            </div>

            <!-- Footer indicator -->
            <div style="font-size: 11.5px; text-align: center; color: var(--text-m); padding-top: 20px;">
                Built with ❤️ for Education
            </div>
        </div>

        <!-- ═══ RIGHT PANEL (PHOTO + DYNAMIC SCHEDULE GLASS UI) ═══ -->
        <div class="right-panel">
            <img src="/images/study_collaboration.png" class="hero-bg-img" alt="Students studying together">

            <!-- Agenda Schedule Card Overlay -->
            <div class="glass-element glass-agenda">
                <div class="agenda-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="agenda-info">
                    <div class="agenda-title">Live Proctoring</div>
                    <div class="agenda-time">Next Quiz: 10:30am</div>
                </div>
            </div>

            <!-- Avatars Card Overlay -->
            <div class="glass-element glass-avatars">
                <div class="avatar-pile">
                    <div class="avatar-circle">AK</div>
                    <div class="avatar-circle">SR</div>
                    <div class="avatar-circle">MN</div>
                </div>
                <span class="avatar-text">Active Students</span>
            </div>

            <!-- Calendar schedule strip Overlay -->
            <div class="glass-element glass-calendar">
                <div class="cal-days">
                    <div class="cal-day">
                        <div class="cal-day-name">Sun</div>
                        <div class="cal-day-num">22</div>
                    </div>
                    <div class="cal-day">
                        <div class="cal-day-name">Mon</div>
                        <div class="cal-day-num">23</div>
                    </div>
                    <div class="cal-day">
                        <div class="cal-day-name">Tue</div>
                        <div class="cal-day-num">24</div>
                    </div>
                    <div class="cal-day active">
                        <div class="cal-day-name">Wed</div>
                        <div class="cal-day-num">25</div>
                    </div>
                    <div class="cal-day">
                        <div class="cal-day-name">Thu</div>
                        <div class="cal-day-num">26</div>
                    </div>
                    <div class="cal-day">
                        <div class="cal-day-name">Fri</div>
                        <div class="cal-day-num">27</div>
                    </div>
                    <div class="cal-day">
                        <div class="cal-day-name">Sat</div>
                        <div class="cal-day-num">28</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Submit handler
        document.getElementById('loginForm').addEventListener('submit', () => {
            document.getElementById('loginBtn').classList.add('loading');
        });

        // Password visibility toggle
        const pwField  = document.getElementById('pw_field');
        const pwToggle = document.getElementById('pwToggle');
        const eyeIcon  = document.getElementById('eyeIcon');

        pwToggle.addEventListener('click', () => {
            const isHidden = pwField.type === 'password';
            pwField.type   = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    </script>

</body>
</html>
