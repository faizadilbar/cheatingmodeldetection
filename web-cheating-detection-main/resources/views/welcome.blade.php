<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduQuiz — AI-Powered Exam & Proctoring Platform</title>
    <meta name="description" content="Build custom AI-powered quizzes, monitor students in real-time, and evaluate results automatically with EduQuiz.">

    <!-- ── FONTS: Bricolage Grotesque (headings) + DM Sans (body) ── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ─── COLOR PALETTE ─── */
        :root {
            --deep:   #3D52A0;
            --mid:    #7091E6;
            --slate:  #8697C4;
            --mist:   #ADBBDA;
            --snow:   #EDE8F5;
            --bg:     #F8F9FF;
            --white:  #FFFFFF;
            --text-h: #18213D;
            --text-b: #4A5478;
            --text-m: #8697C4;
            --border: rgba(112,145,230,0.14);
            --sh-sm:  0 2px 14px rgba(61,82,160,0.07);
            --sh-md:  0 8px 36px rgba(61,82,160,0.11);
            --sh-lg:  0 20px 64px rgba(61,82,160,0.15);
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text-b);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        h1,h2,h3,h4 { font-family: 'Bricolage Grotesque', sans-serif; }

        /* ─── SCROLLBAR ─── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--snow); }
        ::-webkit-scrollbar-thumb { background: var(--mid); border-radius: 20px; }


        /* ══════════════════════════════════
           NAVBAR
        ══════════════════════════════════ */
        .nav {
            position: fixed; top: env(safe-area-inset-top, 0px); left:0; right:0; z-index:1000;
            height: 92px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 7% 0 7%;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border);
            box-shadow: var(--sh-sm);
            transition: box-shadow 0.3s ease;
        }

        .nav-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .nav-logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px;
            box-shadow: 0 4px 14px rgba(61,82,160,0.28);
        }
        .nav-logo-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 28px; font-weight: 800;
            color: var(--text-h); letter-spacing: -0.5px;
        }
        .nav-logo-text span { color: var(--mid); }

        .nav-links { display: flex; align-items: center; gap: 4px; }
        .nav-link {
            padding: 7px 15px; border-radius: 9px;
            font-size: 14px; font-weight: 500;
            text-decoration: none; color: var(--text-b);
            transition: all 0.2s ease;
        }
        .nav-link:hover { background: var(--snow); color: var(--deep); }

        .nav-btn {
            margin-left: 8px;
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 22px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff; border-radius: 12px;
            font-size: 14px; font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(61,82,160,0.28);
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            font-family: 'DM Sans', sans-serif;
        }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(61,82,160,0.4); }
        .nav-btn:active { transform: scale(0.97); }


        /* ══════════════════════════════════
           HERO SECTION
        ══════════════════════════════════ */
        body { padding-top: env(safe-area-inset-top, 0px); }

        .hero-section {
            padding-top: calc(92px + env(safe-area-inset-top, 0px));
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 0;
            background: linear-gradient(150deg, #fff 50%, var(--snow) 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background dots */
        .hero-bg-dots {
            position: absolute; inset: 0; z-index: 0;
            background-image: radial-gradient(var(--mist) 1.2px, transparent 1.2px);
            background-size: 30px 30px;
            opacity: 0.45;
        }

        /* Animated blobs */
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(70px); opacity: 0.35; z-index: 0;
        }
        .blob-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(112,145,230,0.6), transparent 70%);
            top: -150px; right: -100px;
            animation: blobFloat 10s ease-in-out infinite;
        }
        .blob-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(61,82,160,0.4), transparent 70%);
            bottom: -100px; left: 30%;
            animation: blobFloat 14s ease-in-out infinite reverse;
        }
        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(20px,-30px) scale(1.04); }
            66%      { transform: translate(-20px,20px) scale(0.96); }
        }

        .hero-left {
            position: relative; z-index: 2;
            padding: 80px 5% 80px 8%;
        }

        .hero-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 14px 5px 7px;
            background: rgba(112,145,230,0.1);
            border: 1px solid rgba(112,145,230,0.25);
            border-radius: 50px;
            font-size: 12px; font-weight: 600; color: var(--deep);
            margin-bottom: 24px;
            animation: fadeSlideDown 0.7s ease both;
        }
        .pill-dot {
            width: 22px; height: 22px; border-radius: 50%;
            background: linear-gradient(135deg, var(--mid), var(--deep));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 9px;
        }
        .pill-live {
            display: inline-block;
            width: 7px; height: 7px; border-radius: 50%;
            background: #10B981;
            box-shadow: 0 0 0 0 rgba(16,185,129,0.5);
            animation: livePulse 1.5s infinite;
            margin-right: 2px;
        }
        @keyframes livePulse {
            0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.5); }
            70%  { box-shadow: 0 0 0 7px rgba(16,185,129,0); }
            100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }

        .hero-h1 {
            font-size: 56px; font-weight: 800;
            line-height: 1.1; letter-spacing: -2px;
            color: var(--text-h);
            margin-bottom: 22px;
            animation: fadeSlideUp 0.8s 0.1s ease both;
        }
        .hero-h1 .line-accent {
            display: inline-block;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }
        .hero-h1 .line-accent::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--deep), var(--mid));
            border-radius: 99px;
            animation: lineGrow 0.8s 0.8s ease both;
            transform-origin: left;
        }
        @keyframes lineGrow {
            from { transform: scaleX(0); }
            to   { transform: scaleX(1); }
        }

        .hero-desc {
            font-size: 16px; line-height: 1.75; color: var(--text-b);
            max-width: 480px; margin-bottom: 36px;
            font-weight: 400;
            animation: fadeSlideUp 0.8s 0.2s ease both;
        }

        .hero-btns {
            display: flex; gap: 14px; flex-wrap: wrap;
            animation: fadeSlideUp 0.8s 0.3s ease both;
        }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff; border-radius: 14px;
            font-size: 15px; font-weight: 700;
            text-decoration: none;
            box-shadow: 0 6px 24px rgba(61,82,160,0.3);
            transition: all 0.25s ease;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(61,82,160,0.42); }
        .btn-primary:active { transform: scale(0.97); }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 14px 28px;
            background: transparent; color: var(--deep);
            border-radius: 14px; font-size: 15px; font-weight: 700;
            text-decoration: none;
            border: 2px solid rgba(61,82,160,0.2);
            transition: all 0.25s ease;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-ghost:hover { background: var(--snow); border-color: var(--mist); transform: translateY(-2px); }

        .hero-trust {
            display: flex; align-items: center; gap: 14px;
            margin-top: 36px;
            animation: fadeSlideUp 0.8s 0.4s ease both;
        }
        .trust-avs { display: flex; }
        .trust-av {
            width: 34px; height: 34px; border-radius: 50%;
            border: 2.5px solid #fff;
            background: linear-gradient(135deg, var(--mid), var(--deep));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 10px; font-weight: 700;
            margin-left: -9px;
        }
        .trust-av:first-child { margin-left: 0; }
        .trust-copy { font-size: 12.5px; color: var(--text-m); line-height: 1.45; }
        .trust-copy strong { color: var(--text-h); display: block; font-weight: 700; font-size: 13px; }


        /* ── HERO RIGHT — SLIDESHOW ── */
        .hero-right {
            position: relative; z-index: 2;
            height: 100%; min-height: 100vh;
            overflow: hidden;
        }

        .slideshow {
            position: relative; width: 100%; height: 100%;
        }

        .slide {
            position: absolute; inset: 0;
            opacity: 0;
            transition: opacity 1.2s cubic-bezier(0.4,0,0.2,1);
        }
        .slide.active { opacity: 1; z-index: 1; }
        .slide.prev   { opacity: 0; z-index: 0; }

        .slide img {
            width: 100%; height: 100%;
            object-fit: cover; object-position: center;
            display: block;
        }

        /* Gradient overlay on slides */
        .slide::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(to right, rgba(248,249,255,0.45) 0%, transparent 50%);
        }

        /* Slide dots */
        .slide-dots {
            position: absolute;
            bottom: 32px; right: 32px;
            z-index: 10;
            display: flex; gap: 8px;
        }
        .slide-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .slide-dot.active {
            width: 28px; border-radius: 99px;
            background: #fff;
        }

        /* Floating stat cards on hero */
        .hero-float {
            position: absolute; z-index: 10;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            border-radius: 18px; padding: 14px 18px;
            box-shadow: 0 10px 36px rgba(61,82,160,0.18);
            border: 1.5px solid rgba(255,255,255,0.85);
            display: flex; align-items: center; gap: 12px;
        }
        .hero-float-1 {
            bottom: 60px; left: -30px;
            animation: floatY 4s ease-in-out infinite;
        }
        .hero-float-2 {
            top: 80px; right: -20px;
            animation: floatY 5s ease-in-out 1s infinite;
        }
        @keyframes floatY {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-12px); }
        }

        .float-icon {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; flex-shrink: 0;
        }
        .fi-blue  { background: rgba(112,145,230,0.12); color: var(--mid); }
        .fi-green { background: rgba(16,185,129,0.10);  color: #10B981; }

        .float-num {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 20px; font-weight: 800; color: var(--text-h);
        }
        .float-lbl { font-size: 11px; color: var(--text-m); font-weight: 600; }


        /* ══════════════════════════════════
           MARQUEE TICKER
        ══════════════════════════════════ */
        .ticker-wrap {
            background: linear-gradient(135deg, var(--deep), var(--mid));
            padding: 16px 0;
            overflow: hidden;
            white-space: nowrap;
        }
        .ticker-track {
            display: inline-flex; gap: 0;
            animation: tickerScroll 30s linear infinite;
        }
        .ticker-track:hover { animation-play-state: paused; }
        .ticker-item {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 0 36px;
            font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.85);
            border-right: 1px solid rgba(255,255,255,0.15);
        }
        .ticker-item i { color: rgba(255,255,255,0.55); font-size: 13px; }
        @keyframes tickerScroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }






        /* ══════════════════════════════════
           FEATURES
        ══════════════════════════════════ */
        .section { padding: 100px 7%; max-width: 1300px; margin: 0 auto; }

        .sec-eye {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 12px; font-weight: 700;
            color: var(--mid); text-transform: uppercase; letter-spacing: 1.5px;
            margin-bottom: 14px;
        }
        .sec-eye::before {
            content: '';
            display: inline-block; width: 18px; height: 2px;
            background: var(--mid); border-radius: 99px;
        }

        .sec-h2 {
            font-size: 40px; font-weight: 800; letter-spacing: -1.2px;
            color: var(--text-h); line-height: 1.15; margin-bottom: 16px;
        }
        .sec-sub {
            font-size: 15.5px; color: var(--text-b); max-width: 520px;
            line-height: 1.65; margin-bottom: 60px; font-weight: 400;
        }

        /* CARDS */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .card-reveal {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--sh-sm);
            transition: transform 0.5s cubic-bezier(0.4,0,0.2,1),
                        box-shadow 0.3s ease, border-color 0.3s ease, opacity 0.5s ease;
            position: relative;
            transform: translateY(40px);
            opacity: 0;
        }
        .card-reveal.shown { transform: translateY(0); opacity: 1; }

        .card-reveal:nth-child(1) { transition-delay: 0s; }
        .card-reveal:nth-child(2) { transition-delay: 0.12s; }
        .card-reveal:nth-child(3) { transition-delay: 0.24s; }

        .card-reveal:hover {
            transform: translateY(-9px);
            box-shadow: var(--sh-lg);
            border-color: var(--mist);
        }

        /* Bottom accent line that draws on hover */
        .card-reveal::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--deep), var(--mid));
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.45s cubic-bezier(0.4,0,0.2,1);
        }
        .card-reveal:hover::after { transform: scaleX(1); }

        .card-img-wrap { overflow: hidden; height: 210px; }
        .card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .card-reveal:hover .card-img { transform: scale(1.07); }

        .card-body { padding: 26px; }
        .card-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
            background: var(--snow); color: var(--deep); margin-bottom: 14px;
        }
        .card-h3 {
            font-size: 20px; font-weight: 800; color: var(--text-h);
            margin-bottom: 10px; letter-spacing: -0.4px;
        }
        .card-p { font-size: 14px; color: var(--text-b); line-height: 1.65; font-weight: 400; }
        .card-link {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 18px; font-size: 13px; font-weight: 700; color: var(--mid);
            text-decoration: none;
            transition: gap 0.2s ease;
        }
        .card-reveal:hover .card-link { gap: 10px; }


        /* ══════════════════════════════════
           HOW IT WORKS
        ══════════════════════════════════ */
        .how-bg {
            background: linear-gradient(160deg, var(--snow) 0%, var(--bg) 100%);
            padding: 100px 7%;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .how-inner { max-width: 1200px; margin: 0 auto; }

        .steps-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px;
            margin-top: 60px;
        }
        .step-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 22px; text-align: center;
            box-shadow: var(--sh-sm);
            position: relative;
            transition: transform 0.5s cubic-bezier(0.4,0,0.2,1),
                        box-shadow 0.3s ease, opacity 0.5s ease;
            transform: translateY(40px); opacity: 0;
        }
        .step-box.shown { transform: translateY(0); opacity: 1; }
        .step-box:nth-child(1) { transition-delay: 0s; }
        .step-box:nth-child(2) { transition-delay: 0.1s; }
        .step-box:nth-child(3) { transition-delay: 0.2s; }
        .step-box:nth-child(4) { transition-delay: 0.3s; }
        .step-box:hover { transform: translateY(-6px); box-shadow: var(--sh-md); }
        .step-box:not(:last-child)::after {
            content: '→';
            position: absolute; right: -18px; top: 38px;
            color: var(--mist); font-size: 22px; z-index: 1;
        }

        .step-n {
            width: 54px; height: 54px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 24px; font-weight: 800; color: #fff;
            margin: 0 auto 22px;
            box-shadow: 0 6px 20px rgba(61,82,160,0.24);
        }
        .step-title { font-size: 16px; font-weight: 800; color: var(--text-h); margin-bottom: 10px; }
        .step-desc { font-size: 13px; color: var(--text-b); line-height: 1.6; font-weight: 400; }


        /* ══════════════════════════════════
           REVIEWS
        ══════════════════════════════════ */
        .reviews-sec { padding: 100px 7%; max-width: 1300px; margin: 0 auto; }
        .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 60px; }

        .rev-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 22px; padding: 30px;
            box-shadow: var(--sh-sm);
            transition: transform 0.5s cubic-bezier(0.4,0,0.2,1),
                        box-shadow 0.3s ease, border-color 0.3s ease, opacity 0.5s ease;
            transform: translateY(40px); opacity: 0;
        }
        .rev-card.shown { transform: translateY(0); opacity: 1; }
        .rev-card:nth-child(1) { transition-delay: 0s; }
        .rev-card:nth-child(2) { transition-delay: 0.12s; }
        .rev-card:nth-child(3) { transition-delay: 0.24s; }
        .rev-card:hover { transform: translateY(-5px); box-shadow: var(--sh-md); border-color: var(--mist); }

        .rev-stars { color: #F59E0B; letter-spacing: 2px; font-size: 14px; margin-bottom: 16px; }
        .rev-text { font-size: 14.5px; line-height: 1.7; color: var(--text-b); font-style: italic; margin-bottom: 22px; }
        .rev-person { display: flex; align-items: center; gap: 13px; }
        .rev-av {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--mid), var(--deep));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 14px; flex-shrink: 0;
        }
        .rev-name { font-size: 14px; font-weight: 700; color: var(--text-h); }
        .rev-role { font-size: 12px; color: var(--text-m); }


        /* ══════════════════════════════════
           CTA BANNER
        ══════════════════════════════════ */
        .cta-banner {
            background: linear-gradient(135deg, var(--deep) 0%, var(--mid) 100%);
            padding: 90px 7%; text-align: center;
            position: relative; overflow: hidden;
        }
        .cta-banner::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 22px 22px;
        }
        /* Animated shimmer ring */
        .cta-ring {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
            width: 600px; height: 600px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.06);
            animation: ringPulse 3s ease-in-out infinite;
        }
        .cta-ring:nth-child(2) { width: 800px; height: 800px; animation-delay: 1s; }
        .cta-ring:nth-child(3) { width: 1000px; height: 1000px; animation-delay: 2s; }
        @keyframes ringPulse {
            0%,100% { opacity: 0.5; transform: translate(-50%,-50%) scale(1); }
            50%      { opacity: 0.15; transform: translate(-50%,-50%) scale(1.05); }
        }
        .cta-inner { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; }
        .cta-h2 { font-size: 42px; font-weight: 800; color: #fff; margin-bottom: 16px; letter-spacing: -1px; }
        .cta-sub { font-size: 16px; color: rgba(255,255,255,0.72); margin-bottom: 40px; line-height: 1.65; }
        .btn-cta {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 15px 36px;
            background: #fff; color: var(--deep);
            border-radius: 14px; font-size: 16px; font-weight: 800;
            text-decoration: none;
            box-shadow: 0 8px 28px rgba(0,0,0,0.18);
            transition: all 0.25s ease;
            font-family: 'Bricolage Grotesque', sans-serif;
        }
        .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(0,0,0,0.22); }


        /* ══════════════════════════════════
           FOOTER
        ══════════════════════════════════ */
        footer {
            background: var(--text-h); padding: 50px 7% 30px;
            text-align: center;
        }
        .footer-logo {
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none; margin-bottom: 18px;
        }
        .footer-logo-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px;
        }
        .footer-logo-text {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 20px; font-weight: 800; color: #fff;
        }
        .footer-copy { font-size: 13px; color: rgba(255,255,255,0.3); margin-top: 10px; }


        /* ─── KEYFRAMES ─── */
        @keyframes fadeSlideDown {
            from { opacity:0; transform: translateY(-16px); }
            to   { opacity:1; transform: translateY(0); }
        }
        @keyframes fadeSlideUp {
            from { opacity:0; transform: translateY(20px); }
            to   { opacity:1; transform: translateY(0); }
        }


        /* ─── RESPONSIVE ─── */
        @media(max-width: 1024px) {
            .cards-grid    { grid-template-columns: repeat(2,1fr); }
            .reviews-grid  { grid-template-columns: repeat(2,1fr); }
            .steps-grid    { grid-template-columns: repeat(2,1fr); }
            .step-box::after { display: none; }
            .hero-h1       { font-size: 46px; }
        }
        @media(max-width: 768px) {
            .hero-section { grid-template-columns: 1fr; }
            .hero-right   { height: 55vw; min-height: 300px; }
            .hero-h1      { font-size: 36px; }
            .cards-grid   { grid-template-columns: 1fr; }
            .reviews-grid { grid-template-columns: 1fr; }
            .steps-grid   { grid-template-columns: 1fr; }
            .nav-link     { display: none; }
            .hero-left    { padding: 50px 6%; }
            .cta-h2       { font-size: 30px; }
        }
    </style>
</head>
<body>

<!-- ══════ NAVBAR ══════ -->
<nav class="nav" id="mainNav">
    <a href="{{ route('home.landing') }}" class="nav-logo">
        <div class="nav-logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <span class="nav-logo-text">Edu<span>Quiz</span></span>
    </a>
    <div class="nav-links">
        <a href="#features" class="nav-link">Features</a>
        <a href="#how" class="nav-link">How It Works</a>
        <a href="#reviews" class="nav-link">Reviews</a>
        @auth
            <a href="{{ url('/home') }}" class="nav-btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="nav-btn"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        @endauth
    </div>
</nav>

<!-- ══════ HERO ══════ -->
<section class="hero-section">
    <div class="hero-bg-dots"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Left Content -->
    <div class="hero-left">
        <div class="hero-pill">
            <div class="pill-dot"><i class="fas fa-bolt"></i></div>
            <span class="pill-live"></span>
            Live · AI-Powered · Proctored Exams
        </div>

        <h1 class="hero-h1">
            The Smarter Way<br>to <span class="line-accent">Exam &amp; Evaluate</span>
        </h1>

        <p class="hero-desc">
            Generate topic-specific AI quizzes, monitor students live with automatic integrity locks, and get instant score analysis — all in one elegant platform.
        </p>

        <div class="hero-btns">
            @auth
                <a href="{{ url('/home') }}" class="btn-primary"><i class="fas fa-chart-line"></i> Open Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary"><i class="fas fa-rocket"></i> Get Started Free</a>
                <a href="#features" class="btn-ghost"><i class="fas fa-play-circle"></i> See Features</a>
            @endauth
        </div>

        <div class="hero-trust">
            <div class="trust-avs">
                <div class="trust-av">AK</div>
                <div class="trust-av">SR</div>
                <div class="trust-av">MN</div>
                <div class="trust-av" style="font-size:9px">+97</div>
            </div>
            <div class="trust-copy">
                <strong>500+ Educators Trust EduQuiz</strong>
                Trusted across universities &amp; schools
            </div>
        </div>
    </div>

    <!-- Right Slideshow -->
    <div class="hero-right">
        <div class="slideshow" id="heroSlideshow">
            <div class="slide active">
                <img src="/images/hero_ai_tutor.png" alt="AI tutor helping student with exam">
            </div>
            <div class="slide">
                <img src="/images/slide_monitoring.png" alt="Student taking online exam">
            </div>
            <div class="slide">
                <img src="/images/slide_classroom.png" alt="Modern exam classroom">
            </div>
        </div>

        <!-- Floating stat cards -->
        <div class="hero-float hero-float-1">
            <div class="float-icon fi-green"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="float-lbl">Quizzes Done</div>
                <div class="float-num">12,480+</div>
            </div>
        </div>
        <div class="hero-float hero-float-2">
            <div class="float-icon fi-blue"><i class="fas fa-shield-alt"></i></div>
            <div>
                <div class="float-lbl">Proctoring Accuracy</div>
                <div class="float-num">99.8%</div>
            </div>
        </div>

        <!-- Slide dots -->
        <div class="slide-dots" id="slideDots">
            <button class="slide-dot active" data-index="0"></button>
            <button class="slide-dot" data-index="1"></button>
            <button class="slide-dot" data-index="2"></button>
        </div>
    </div>
</section>

<!-- ══════ MARQUEE TICKER ══════ -->
<div class="ticker-wrap">
    <div class="ticker-track">
        <!-- Double items for seamless loop -->
        <span class="ticker-item"><i class="fas fa-magic"></i> AI Quiz Generator</span>
        <span class="ticker-item"><i class="fas fa-shield-alt"></i> Real-time Proctoring</span>
        <span class="ticker-item"><i class="fas fa-desktop"></i> Live Monitor Panel</span>
        <span class="ticker-item"><i class="fas fa-chart-bar"></i> Instant Results</span>
        <span class="ticker-item"><i class="fas fa-lock"></i> Anti-cheat Detection</span>
        <span class="ticker-item"><i class="fas fa-graduation-cap"></i> Course Management</span>
        <span class="ticker-item"><i class="fas fa-bolt"></i> Auto Grading</span>
        <span class="ticker-item"><i class="fas fa-users"></i> Multi-role Access</span>
        <!-- Duplicate for seamless scroll -->
        <span class="ticker-item"><i class="fas fa-magic"></i> AI Quiz Generator</span>
        <span class="ticker-item"><i class="fas fa-shield-alt"></i> Real-time Proctoring</span>
        <span class="ticker-item"><i class="fas fa-desktop"></i> Live Monitor Panel</span>
        <span class="ticker-item"><i class="fas fa-chart-bar"></i> Instant Results</span>
        <span class="ticker-item"><i class="fas fa-lock"></i> Anti-cheat Detection</span>
        <span class="ticker-item"><i class="fas fa-graduation-cap"></i> Course Management</span>
        <span class="ticker-item"><i class="fas fa-bolt"></i> Auto Grading</span>
        <span class="ticker-item"><i class="fas fa-users"></i> Multi-role Access</span>
    </div>
</div>




<!-- ══════ FEATURES ══════ -->
<section class="section" id="features">
    <div class="sec-eye">Platform Features</div>
    <h2 class="sec-h2">Everything You Need<br>In One Platform</h2>
    <p class="sec-sub">From AI quiz generation to live proctoring — EduQuiz handles every step of the evaluation lifecycle elegantly.</p>

    <div class="cards-grid">
        <div class="card-reveal">
            <div class="card-img-wrap">
                <img src="/images/hero_ai_tutor.png" class="card-img" alt="AI Quiz Maker">
            </div>
            <div class="card-body">
                <div class="card-tag"><i class="fas fa-magic"></i> AI Powered</div>
                <h3 class="card-h3">AI Quiz Generator</h3>
                <p class="card-p">Generate difficulty-calibrated, topic-specific questions in seconds using advanced AI — saving hours of manual work per exam.</p>
                <a href="#" class="card-link">Explore <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="card-reveal">
            <div class="card-img-wrap">
                <img src="/images/feature_proctoring.png" class="card-img" alt="Live Proctoring">
            </div>
            <div class="card-body">
                <div class="card-tag"><i class="fas fa-eye"></i> Security</div>
                <h3 class="card-h3">Live Proctoring</h3>
                <p class="card-p">Detect tab switching and screen resizing automatically. Get instant violation alerts with a real-time live monitor dashboard.</p>
                <a href="#" class="card-link">Explore <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="card-reveal">
            <div class="card-img-wrap">
                <img src="/images/feature_analytics.png" class="card-img" alt="Smart Analytics">
            </div>
            <div class="card-body">
                <div class="card-tag"><i class="fas fa-chart-bar"></i> Analytics</div>
                <h3 class="card-h3">Smart Analytics</h3>
                <p class="card-p">Deep per-question analysis, score cards, and AI performance summaries available the moment a student submits.</p>
                <a href="#" class="card-link">Explore <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- ══════ HOW IT WORKS ══════ -->
<section class="how-bg" id="how">
    <div class="how-inner">
        <div class="sec-eye">The Process</div>
        <h2 class="sec-h2">How EduQuiz Works</h2>
        <p class="sec-sub">Four simple steps from quiz creation to final graded results — fast, secure, and fully automated.</p>
        <div class="steps-grid">
            <div class="step-box">
                <div class="step-n">1</div>
                <div class="step-title">Create Course</div>
                <p class="step-desc">Admin sets up course channels and assigns teachers and students within the platform.</p>
            </div>
            <div class="step-box">
                <div class="step-n">2</div>
                <div class="step-title">Generate with AI</div>
                <p class="step-desc">Teacher builds a quiz — topic, difficulty, number of questions — using the AI engine instantly.</p>
            </div>
            <div class="step-box">
                <div class="step-n">3</div>
                <div class="step-title">Students Take Exam</div>
                <p class="step-desc">Students enter a quiz code and sit a proctored, secure exam with real-time integrity enforcement.</p>
            </div>
            <div class="step-box">
                <div class="step-n">4</div>
                <div class="step-title">Auto Evaluation</div>
                <p class="step-desc">Scores, question-level breakdown, and AI feedback are generated instantly upon submission.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════ REVIEWS ══════ -->
<section class="reviews-sec" id="reviews">
    <div class="sec-eye">Testimonials</div>
    <h2 class="sec-h2">Loved by Educators</h2>
    <p class="sec-sub">What teachers and professors are saying about EduQuiz.</p>
    <div class="reviews-grid">
        <div class="rev-card">
            <div class="rev-stars">★★★★★</div>
            <p class="rev-text">"EduQuiz transformed how I run assessments. The AI quiz generator is incredibly precise — students can't predict topics in advance."</p>
            <div class="rev-person">
                <div class="rev-av">AP</div>
                <div>
                    <div class="rev-name">Dr. Asad Patel</div>
                    <div class="rev-role">Associate Professor, Computer Science</div>
                </div>
            </div>
        </div>
        <div class="rev-card">
            <div class="rev-stars">★★★★★</div>
            <p class="rev-text">"The live monitor is outstanding. I can see every student's status, unlock blocked attempts, and track violations in real-time."</p>
            <div class="rev-person">
                <div class="rev-av">SR</div>
                <div>
                    <div class="rev-name">Sarah Rahman</div>
                    <div class="rev-role">High School Teacher, Biology</div>
                </div>
            </div>
        </div>
        <div class="rev-card">
            <div class="rev-stars">★★★★★</div>
            <p class="rev-text">"Setup took under five minutes. Results were immediate and detailed. This is exactly what online testing was missing."</p>
            <div class="rev-person">
                <div class="rev-av">MN</div>
                <div>
                    <div class="rev-name">Muhammad Naveed</div>
                    <div class="rev-role">Dept. Head, University of Lahore</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════ CTA BANNER ══════ -->
<section class="cta-banner">
    <div class="cta-ring"></div>
    <div class="cta-ring"></div>
    <div class="cta-ring"></div>
    <div class="cta-inner">
        <h2 class="cta-h2">Start Smarter Exams Today</h2>
        <p class="cta-sub">Join hundreds of educators saving time and improving exam integrity with EduQuiz.</p>
        @auth
            <a href="{{ url('/home') }}" class="btn-cta"><i class="fas fa-tachometer-alt"></i> Open My Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn-cta"><i class="fas fa-rocket"></i> Get Started Free</a>
        @endauth
    </div>
</section>

<!-- ══════ FOOTER ══════ -->
<footer>
    <a href="{{ route('home.landing') }}" class="footer-logo">
        <div class="footer-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <span class="footer-logo-text">EduQuiz</span>
    </a>
    <p class="footer-copy">&copy; 2026 EduQuiz Evaluation System · All rights reserved</p>
</footer>

<!-- ══════ SCRIPTS ══════ -->
<script>
/* ─── Hero Slideshow ─── */
const slides  = document.querySelectorAll('.slide');
const dots    = document.querySelectorAll('.slide-dot');
let current   = 0;
let slideTimer;

function goToSlide(n) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (n + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
}

function startAutoplay() {
    slideTimer = setInterval(() => goToSlide(current + 1), 4500);
}

dots.forEach(dot => {
    dot.addEventListener('click', () => {
        clearInterval(slideTimer);
        goToSlide(parseInt(dot.dataset.index));
        startAutoplay();
    });
});

startAutoplay();

/* ─── Scroll Reveal (Drawer Cards) ─── */
const revealEls = document.querySelectorAll('.card-reveal, .step-box, .rev-card');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('shown');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });

revealEls.forEach(el => revealObs.observe(el));

/* ─── Count-up Animation ─── */
function countUp(el) {
    const target = parseInt(el.dataset.target);
    const suffix = el.dataset.suffix || '';
    const dur    = 1800;
    let start    = null;
    const step   = ts => {
        if (!start) start = ts;
        const p = Math.min((ts - start) / dur, 1);
        const val = Math.floor(p * target);
        el.textContent = val.toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const statsObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            document.querySelectorAll('.stat-num[data-target]').forEach(countUp);
            statsObs.disconnect();
        }
    });
}, { threshold: 0.4 });

const statsEl = document.querySelector('.stats-strip');
if (statsEl) statsObs.observe(statsEl);

/* ─── Navbar elevation on scroll ─── */
window.addEventListener('scroll', () => {
    document.getElementById('mainNav').style.boxShadow =
        window.scrollY > 10
        ? '0 4px 28px rgba(61,82,160,0.13)'
        : '0 2px 14px rgba(61,82,160,0.07)';
});
</script>
</body>
</html>
