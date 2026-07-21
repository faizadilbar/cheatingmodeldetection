<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduQuiz')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ─── COLOR SYSTEM ─── */
        :root {
            /* New Theme Tokens */
            --deep:        #3D52A0;
            --mid:         #7091E6;
            --slate:       #8697C4;
            --mist:        #ADBBDA;
            --snow:        #EDE8F5;
            --bg:          #F4F6FF;
            --white:       #FFFFFF;
            --text1:       #18213D;
            --text2:       #4A5478;
            --text3:       #8697C4;
            --border:      rgba(112, 145, 230, 0.13);
            --sidebar-w:   260px;
            --font-body:   'DM Sans', sans-serif;
            --font-display: 'Bricolage Grotesque', sans-serif;
            --ease:        cubic-bezier(0.4, 0, 0.2, 1);
            --ease-out:    cubic-bezier(0.0, 0, 0.2, 1);
            --glow:        rgba(112, 145, 230, 0.18);

            /* Legacy Compatibility */
            --primary:       #7091E6;
            --primary-dark:  #3D52A0;
            --primary-light: #ADBBDA;
            --secondary:     #8697C4;
            --accent:        #5B79D4;
            --surface:       #FFFFFF;
            --surface-alt:   #F4F6FF;
            --green:         #10B981;
            --amber:         #F59E0B;
            --red:           #EF4444;
            --teal:          #14B8A6;
            --glow-primary:  rgba(112, 145, 230, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text2);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* CUSTOM SCROLLBARS */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: var(--mist);
            border-radius: 20px;
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--mid); }

        /* ═══════════════════════════════════════
           CLEAN SIDEBAR  —  Login-page inspired
        ═══════════════════════════════════════ */
        /* ─── APP LAYOUT ─── */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: env(safe-area-inset-top, 0px); left: 0;
            height: calc(100vh - env(safe-area-inset-top, 0px));
            z-index: 100;
            background: #fff;
            border-right: 1px solid rgba(112,145,230,0.13);
            box-shadow: 4px 0 32px rgba(61,82,160,0.06);
            overflow: hidden;
            overflow-y: auto;
        }

        /* Respect safe-area insets / provide extra top spacing for browser chrome */
        @supports(env(safe-area-inset-top)) {
            .sidebar { padding-top: env(safe-area-inset-top); }
        }

        /* Brand logo strip at the top */
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 18px 12px;
            border-bottom: 1px solid rgba(112,145,230,0.10);
        }

        .brand-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--deep) 0%, var(--mid) 100%);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
            box-shadow: 0 6px 18px rgba(112,145,230,0.40);
            flex-shrink: 0;
        }

        .brand-name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--text1);
            letter-spacing: -0.4px;
        }
        .brand-name span {
            color: var(--mid);
        }

        /* User Badge */
        .sidebar-user {
            margin: 14px 14px 8px;
            padding: 12px 14px;
            background: linear-gradient(135deg, rgba(112,145,230,0.07), rgba(61,82,160,0.04));
            border: 1px solid rgba(112,145,230,0.14);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 14px; font-weight: 800; color: #fff;
            box-shadow: 0 4px 12px rgba(112,145,230,0.35);
            flex-shrink: 0;
        }

        .user-info .name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--text1);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 150px;
        }
        .user-info .role {
            font-size: 10.5px;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-top: 2px;
        }

        /* Nav section label */
        .nav-section {
            display: block;
            padding: 16px 22px 6px;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--mist);
            text-transform: uppercase;
            letter-spacing: 1.1px;
            font-family: 'DM Sans', sans-serif;
        }

        /* Sidebar nav container */
        .sidebar-nav {
            padding: 6px 12px;
            flex: 1;
        }

        /* Nav items */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 14px;
            color: var(--text2);
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.22s var(--ease);
            border-radius: 12px;
            margin-bottom: 3px;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%) scaleY(0);
            width: 3px; height: 55%;
            background: linear-gradient(180deg, var(--deep), var(--mid));
            border-radius: 0 4px 4px 0;
            transition: transform 0.22s ease;
        }

        .nav-item:hover {
            background: rgba(112,145,230,0.07);
            color: var(--deep);
            transform: translateX(3px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(112,145,230,0.13), rgba(61,82,160,0.07));
            color: var(--deep);
            font-weight: 600;
            border: 1px solid rgba(112,145,230,0.18);
            box-shadow: 0 2px 10px rgba(112,145,230,0.08);
        }

        .nav-item.active::before { transform: translateY(-50%) scaleY(1); }

        .nav-item i {
            width: 18px; text-align: center;
            font-size: 13.5px;
            color: var(--mid);
            transition: transform 0.22s var(--ease);
            opacity: 0.85;
        }
        .nav-item.active i { opacity: 1; color: var(--deep); }
        .nav-item:hover i { transform: scale(1.12); }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 14px 14px 18px;
            border-top: 1px solid rgba(112,145,230,0.10);
            margin-top: auto;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 10px 14px;
            border-radius: 12px;
            color: var(--slate);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            background: rgba(112,145,230,0.05);
            border: 1px solid rgba(112,145,230,0.13);
            width: 100%;
            transition: all 0.22s ease;
        }

        .logout-btn:hover {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #EF4444;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.1);
        }

        /* ─── MAIN CONTENT CANVAS ─── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
        }

        /* Glowing background overlays */
        .main-content::before {
            content: '';
            position: fixed; top: -15%; right: -5%;
            width: 550px; height: 550px;
            background: radial-gradient(circle, rgba(112, 145, 230, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        /* ─── TOPBAR ─── */
        .topbar {
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 1px 8px rgba(61, 82, 160, 0.02);
        }

        .topbar-left h2 {
            font-family: var(--font-display);
            font-size: 21px;
            font-weight: 800;
            color: var(--text1);
            letter-spacing: -0.4px;
        }

        .topbar-left p {
            font-size: 11.5px;
            color: var(--text3);
            margin-top: 2px;
        }

        .page-body {
            padding: 32px;
            flex: 1;
            position: relative;
            z-index: 1;
            animation: bodyFadeIn 0.5s var(--ease) both;
        }

        @keyframes bodyFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }


        /* ─── CARDS ─── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(61, 82, 160, 0.04);
            transition: all 0.3s var(--ease);
            position: relative;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(61, 82, 160, 0.08);
            border-color: var(--mist);
        }

        .card-header {
            padding: 22px 26px;
            border-bottom: 1px solid var(--border);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-family: var(--font-display);
        }
        .card-body { padding: 26px; }

        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 22px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(91, 127, 214, 0.08);
            border-radius: 20px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.35s var(--ease-out);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            backdrop-filter: blur(10px);
            animation: slideUp 0.55s var(--ease-out) forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s var(--ease-out);
        }

        .stat-card:hover::after { transform: scaleX(1); }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(5) { animation-delay: 0.25s; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 20px 48px rgba(91, 127, 214, 0.14);
            border-color: var(--primary-light);
        }

        .stat-info .value {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 800;
            color: var(--text1);
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: #fff;
            transition: transform 0.3s var(--ease-out);
        }

        .stat-card:hover .stat-icon { transform: rotate(-5deg) scale(1.05); }

        .stat-info .label {
            font-size: 11px;
            color: var(--text3);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 4px;
        }

        /* TABLES */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
            background: rgba(240, 242, 250, 0.7);
        }

        td {
            padding: 15px 20px;
            font-size: 13.5px;
            color: var(--text2);
            border-bottom: 1px solid #F1F5F9;
            transition: background 0.2s;
        }

        tr:last-child td { border-bottom: none; }

        tr:hover td {
            background: rgba(91, 127, 214, 0.04);
        }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .badge-blue { background: rgba(107, 163, 214, 0.12); color: #4A8BC4; border: 1px solid rgba(107, 163, 214, 0.25); }
        .badge-green { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-red { background: rgba(239, 68, 68, 0.1); color: #DC2626; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-amber { background: rgba(245, 158, 11, 0.1); color: #D97706; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-purple { background: rgba(168, 85, 247, 0.1); color: #7C3AED; border: 1px solid rgba(168, 85, 247, 0.2); }
        .badge-gray { background: rgba(100, 116, 139, 0.1); color: #64748B; border: 1px solid rgba(100, 116, 139, 0.2); }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-family: var(--font-display);
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }

        .btn:hover::after { transform: translateX(100%); }

        .btn:active { transform: scale(0.97); }

        .btn-primary {
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff;
            box-shadow: 0 4px 20px rgba(112, 145, 230, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 32px rgba(107, 163, 214, 0.45);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--text2);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #fff;
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.08);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: #EF4444;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .btn-success {
            background: rgba(16, 185, 129, 0.08);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            background: #10B981;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-sm { padding: 7px 14px; font-size: 11.5px; border-radius: 9px; }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            justify-content: center;
            border-radius: 10px;
        }

        /* FORMS */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text2);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 11px 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 13.5px;
            color: var(--text1);
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.25s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(91, 127, 214, 0.12);
            background: #fff;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394A3B8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
            cursor: pointer;
        }

        .form-text { font-size: 11.5px; color: var(--text3); margin-top: 6px; }
        .invalid-feedback { font-size: 12px; color: var(--red); margin-top: 6px; display: block; }
        .is-invalid { border-color: var(--red) !important; }

        /* ALERTS */
        .alert {
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            animation: alertSlide 0.4s ease;
        }

        @keyframes alertSlide {
            from { opacity: 0; transform: translateX(-12px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
        .alert-warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
        .alert-info { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 15, 26, 0.5);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal-overlay.show { display: flex; }

        .modal-box {
            background: var(--surface);
            border-radius: 24px;
            padding: 36px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
            animation: modalIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        @keyframes modalIn {
            from { transform: scale(0.9) translateY(10px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: 21px;
            font-weight: 800;
            margin-bottom: 22px;
            color: var(--text1);
            letter-spacing: -0.4px;
        }

        /* ANIMATIONS */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in { animation: fadeIn 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .pulse { animation: pulse 2s infinite; }

        .grad-badge {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 700;
        }

        /* COURSE CARDS */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .course-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(91, 127, 214, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.35s var(--ease-out);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 4px 20px rgba(27, 42, 74, 0.04);
            animation: slideUp 0.5s ease forwards;
            opacity: 0;
        }

        .course-card:nth-child(1) { animation-delay: 0.1s; }
        .course-card:nth-child(2) { animation-delay: 0.15s; }
        .course-card:nth-child(3) { animation-delay: 0.2s; }
        .course-card:nth-child(4) { animation-delay: 0.25s; }
        .course-card:nth-child(5) { animation-delay: 0.3s; }
        .course-card:nth-child(6) { animation-delay: 0.35s; }

        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(91, 127, 214, 0.18);
            border-color: var(--primary-light);
        }

        .course-card-header {
            padding: 26px 22px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            position: relative;
            overflow: hidden;
        }

        .course-card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: cardOrb 6s ease-in-out infinite;
        }

        .course-card-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Ccircle cx='30' cy='30' r='30'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        @keyframes cardOrb {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-20px, 10px); }
        }

        .course-card-header h3 {
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            position: relative;
            z-index: 1;
            letter-spacing: -0.3px;
        }

        .course-card-header p {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }

        .course-card-body { padding: 18px 22px; }

        /* QUIZ CARDS */
        .quiz-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.08);
            border-radius: 16px;
            padding: 20px 22px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .quiz-card:hover {
            border-color: var(--primary-light);
            box-shadow: 0 8px 28px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        /* PROGRESS */
        .progress {
            height: 8px;
            background: #E2E8F0;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* TAGS */
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: rgba(59, 130, 246, 0.06);
            border-radius: 8px;
            font-size: 11px;
            color: var(--text2);
            font-weight: 600;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 70px 24px;
        }

        .empty-state .empty-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px var(--glow-primary);
            animation: logoPulse 3s ease-in-out infinite;
        }

        .empty-state h3 {
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 800;
            color: var(--text1);
            margin-bottom: 10px;
        }

        .empty-state p { font-size: 13px; color: var(--text3); }

        /* LOADING SPINNER */
        .spinner {
            width: 20px;
            height: 20px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ─── SIDEBAR TRANSITION SMOOTHING ─── */
        .sidebar {
            transition: transform 0.32s cubic-bezier(0.4,0,0.2,1), box-shadow 0.32s ease, width 0.32s cubic-bezier(0.4,0,0.2,1);
        }
        .main-content {
            transition: margin-left 0.32s cubic-bezier(0.4,0,0.2,1);
        }

        /* ─── COLLAPSED SIDEBAR (slides off-screen) ─── */
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .main-content.sidebar-collapsed {
            margin-left: 0;
        }

        /* ─── ZERO-FLICKER PRE-RENDER ─── */
        html.sidebar-initial-collapsed .sidebar {
            transform: translateX(-100%);
        }
        html.sidebar-initial-collapsed .main-content {
            margin-left: 0;
        }

        /* ─── TOGGLE BUTTON ─── */
        .sidebar-toggle-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(112,145,230,0.08);
            border: 1.5px solid rgba(112,145,230,0.15);
            border-radius: 11px;
            cursor: pointer;
            color: var(--text2);
            font-size: 15px;
            transition: all 0.22s ease;
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover {
            background: rgba(112,145,230,0.16);
            color: var(--primary);
            border-color: rgba(112,145,230,0.3);
            transform: scale(1.05);
        }

        /* ─── TOPBAR FONT FIX ─── */
        .topbar h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--text1);
            letter-spacing: -0.5px;
        }
        .topbar p {
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            color: var(--text3);
            font-weight: 400;
        }

        /* ─── RESPONSIVE ─── */
        @media(max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; }
            .sidebar.open { transform: translateX(0); }
            .sidebar.open .sidebar-drawer { opacity: 1 !important; visibility: visible !important; transform: translateX(0) !important; pointer-events: auto !important; }
            .main-content { margin-left: 0 !important; }
            .page-body { padding: 20px; }
            .sidebar-toggle-btn { display: flex; }
        }
    </style>
    @stack('styles')
    <script>
        /* Zero-flicker sidebar state restore */
        (function() {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-initial-collapsed');
            }
        })();
    </script>
</head>
<body>
<div class="app-layout">
    <!-- SIDEBAR (Clean Single Panel) -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand Header -->
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-name">Edu<span>Quiz</span></div>
        </div>

        <!-- User Badge -->
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(session('user.name','?'),0,1)) }}</div>
            <div class="user-info">
                <div class="name">{{ session('user.name') }}</div>
                <div class="role">{{ strtoupper(session('user.role','User')) }}</div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>

        <!-- Sidebar Footer: Logout -->
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left" style="display:flex;align-items:center;gap:14px;">
                <button id="sidebarToggleBtn" class="sidebar-toggle-btn" aria-label="Toggle Sidebar" title="Toggle Sidebar">
                    <i class="fas fa-bars" id="sidebarToggleIcon"></i>
                </button>
                <div>
                    <h2>@yield('page-title', 'Dashboard')</h2>
                    <p>@yield('page-subtitle', '')</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                @yield('topbar-actions')
            </div>
        </div>
        <div class="page-body">
            @if(session('success'))
                <div class="alert alert-success fade-in"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error fade-in"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error fade-in">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
<script>
    // ─── Auto-hide alerts ───
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(() => a.remove(), 500);
        });
    }, 4000);

    // ─── CSRF for AJAX ───
    window.csrfToken = '{{ csrf_token() }}';
    function ajaxPost(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
    }

    // ─── Sidebar Toggle ───
    (function() {
        const sidebar    = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleBtn  = document.getElementById('sidebarToggleBtn');
        const toggleIcon = document.getElementById('sidebarToggleIcon');
        const STORAGE_KEY = 'sidebarCollapsed';

        let collapsed = localStorage.getItem(STORAGE_KEY) === 'true';

        function applyState(animate) {
            if (!animate) {
                sidebar.style.transition = 'none';
                mainContent.style.transition = 'none';
            }
            if (collapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
                toggleIcon.className = 'fas fa-indent';
                toggleBtn.title = 'Expand Sidebar';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                toggleIcon.className = 'fas fa-bars';
                toggleBtn.title = 'Collapse Sidebar';
            }
            if (!animate) {
                requestAnimationFrame(() => {
                    sidebar.style.transition = '';
                    mainContent.style.transition = '';
                });
            }
            // Remove html pre-render class once JS has taken over
            document.documentElement.classList.remove('sidebar-initial-collapsed');
        }

        // Apply initial state without animation
        applyState(false);

        // Toggle on button click
        toggleBtn.addEventListener('click', () => {
            collapsed = !collapsed;
            localStorage.setItem(STORAGE_KEY, collapsed);
            applyState(true);
        });

        // Mobile: close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                    sidebar.classList.remove('open');
                }
            }
        });
    })();

    // ─── Scroll reveal ───
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                revealObs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.card:not(.fade-in):not(.stat-card)').forEach(el => revealObs.observe(el));
</script>
@stack('scripts')
</body>
</html>
