@extends('layouts.app')
@section('title','Teacher Dashboard')
@section('page-title','My Courses')
@section('page-subtitle') Welcome, {{ $teacher['name'] ?? 'Teacher' }} @endsection

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@push('styles')
<style>
/* ─── HERO BANNER ─── */
.hero-banner {
    position: relative;
    width: 100%;
    height: 220px;
    border-radius: 24px;
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: 0 12px 40px rgba(61,82,160,0.14);
}

.hero-slide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    padding: 0 40px;
    opacity: 0;
    transition: opacity 0.9s cubic-bezier(0.4,0,0.2,1);
    z-index: 1;
}
.hero-slide.active { opacity: 1; z-index: 2; }

/* Slide backgrounds */
.hero-slide:nth-child(1) {
    background: linear-gradient(135deg, #3D52A0 0%, #7091E6 50%, #ADBBDA 100%);
}
.hero-slide:nth-child(2) {
    background: linear-gradient(135deg, #0F2057 0%, #3D52A0 45%, #7091E6 100%);
}
.hero-slide:nth-child(3) {
    background: linear-gradient(135deg, #1a1a4e 0%, #3D52A0 40%, #8697C4 100%);
}

/* Decorative orbs */
.hero-slide::before {
    content: '';
    position: absolute;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    top: -80px; right: -60px;
    pointer-events: none;
}
.hero-slide::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
    bottom: -60px; right: 160px;
    pointer-events: none;
}

/* Slide text content */
.hero-text { flex: 1; position: relative; z-index: 3; }

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 30px;
    padding: 5px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: rgba(255,255,255,0.92);
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}
.hero-badge i { font-size: 10px; color: #A3F9B5; }

.hero-title {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 26px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.6px;
    line-height: 1.2;
    margin-bottom: 8px;
}
.hero-title span { color: #C5D5FF; }

.hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: rgba(255,255,255,0.72);
    max-width: 360px;
    line-height: 1.6;
}

/* Floating Images */
.hero-image-wrap {
    position: relative;
    z-index: 3;
    height: 100%;
    width: 320px;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
}
.hero-image-wrap img {
    height: 180px;
    object-fit: contain;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.15));
    animation: imageFloat 6s ease-in-out infinite;
    mask-image: linear-gradient(to top, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%);
    -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%);
}

@keyframes imageFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* Dot indicators */
.hero-dots {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 10;
}
.hero-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,0.35);
    transition: all 0.35s ease;
    cursor: pointer;
}
.hero-dot.active {
    width: 22px;
    border-radius: 3px;
    background: #fff;
}

/* Floating particles */
.hero-particles { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 2; }
.particle {
    position: absolute;
    width: 4px; height: 4px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0%   { transform: translateY(0) scale(1); opacity: 0.3; }
    50%  { opacity: 0.7; }
    100% { transform: translateY(-220px) scale(0.4); opacity: 0; }
}

/* ─── STAT CARDS ─── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px,1fr));
    gap: 18px;
    margin-bottom: 28px;
}

/* ─── SECTION HEADER ─── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}
.section-header h2 {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 19px;
    font-weight: 800;
    color: var(--text1);
    letter-spacing: -0.3px;
}
.section-header .count-badge {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    font-family: 'DM Sans', sans-serif;
}
</style>
@endpush

@section('content')

{{-- ─── HERO BANNER SLIDESHOW ─── --}}
<div class="hero-banner fade-in" id="heroBanner">

    {{-- Slide 1: Welcome --}}
    <div class="hero-slide active">
        <div class="hero-particles" id="particles1"></div>
        <div class="hero-text">
            <div class="hero-badge"><i class="fas fa-circle"></i> AI-Powered Platform</div>
            <div class="hero-title">Create Smarter<br><span>Exams & Quizzes</span></div>
            <div class="hero-sub">Build AI-generated quizzes, monitor students live, and get instant result analytics.</div>
        </div>
        <div class="hero-image-wrap">
            <img src="{{ asset('images/dashboard/slide3.png') }}" alt="Classroom Overview">
        </div>
    </div>

    {{-- Slide 2: Create Quiz --}}
    <div class="hero-slide">
        <div class="hero-particles" id="particles2"></div>
        <div class="hero-text">
            <div class="hero-badge"><i class="fas fa-magic"></i> AI Generation</div>
            <div class="hero-title">Generate Questions<br><span>Automatically</span></div>
            <div class="hero-sub">Use AI to instantly generate MCQs, short answers, and fill-in-the-blank questions from any topic.</div>
        </div>
        <div class="hero-image-wrap">
            <img src="{{ asset('images/dashboard/slide1.png') }}" alt="AI Question Generation">
        </div>
    </div>

    {{-- Slide 3: Proctoring --}}
    <div class="hero-slide">
        <div class="hero-particles" id="particles3"></div>
        <div class="hero-text">
            <div class="hero-badge"><i class="fas fa-shield-alt"></i> Live Proctoring</div>
            <div class="hero-title">Monitor Students<br><span>In Real-Time</span></div>
            <div class="hero-sub">Watch live quiz sessions, unlock/block students, and view submission logs instantly.</div>
        </div>
        <div class="hero-image-wrap">
            <img src="{{ asset('images/dashboard/slide2.png') }}" alt="Live Monitoring">
        </div>
    </div>

    {{-- Dot Navigation --}}
    <div class="hero-dots">
        <div class="hero-dot active" data-slide="0"></div>
        <div class="hero-dot" data-slide="1"></div>
        <div class="hero-dot" data-slide="2"></div>
    </div>
</div>

{{-- ─── STAT CARDS ROW ─── --}}
<div class="stats-row fade-in">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#7091E6,#3D52A0);">
            <i class="fas fa-book-open" style="color:#fff;font-size:20px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ count($courses) }}</div>
            <div class="label">My Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10B981,#059669);">
            <i class="fas fa-users" style="color:#fff;font-size:20px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ $totalStudents ?? 0 }}</div>
            <div class="label">Total Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706);">
            <i class="fas fa-file-alt" style="color:#fff;font-size:20px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">—</div>
            <div class="label">Quizzes Created</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#8B5CF6,#6D28D9);">
            <i class="fas fa-chart-bar" style="color:#fff;font-size:20px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">—</div>
            <div class="label">Avg Score</div>
        </div>
    </div>
</div>

{{-- ─── COURSES GRID ─── --}}
@if(count($courses) > 0)
<div class="section-header">
    <h2>My Courses</h2>
    <span class="count-badge">{{ count($courses) }} Course{{ count($courses) !== 1 ? 's' : '' }}</span>
</div>
<div class="courses-grid fade-in">
    @foreach($courses as $i => $course)
    @php $colors = ['#7091E6','#10B981','#8697C4','#F59E0B','#14B8A6']; $c = $colors[$i % count($colors)]; @endphp
    <div class="course-card" style="text-decoration:none;">
        <div class="course-card-header" style="background:linear-gradient(135deg,{{ $c }},{{ $c }}BB);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-book" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <h3>{{ $course['course_title'] }}</h3>
                    <p>{{ $course['course_code'] ?? '' }}</p>
                </div>
            </div>
        </div>
        <div class="course-card-body">
            <div style="display:flex;gap:8px;margin-top:4px;">
                <a href="{{ route('teacher.quiz.create', $course['id']) }}" class="btn btn-primary btn-sm" style="flex:1;justify-content:center;">
                    <i class="fas fa-plus"></i> Create Quiz
                </a>
                <a href="{{ route('teacher.course.quizzes', $course['id']) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
                    <i class="fas fa-list"></i> View Quizzes
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="empty-state fade-in">
    <div class="empty-icon"><i class="fas fa-book-open"></i></div>
    <h3>No Courses Assigned</h3>
    <p>Contact admin to get courses assigned to you</p>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const slides = document.querySelectorAll('#heroBanner .hero-slide');
    const dots   = document.querySelectorAll('#heroBanner .hero-dot');
    let current  = 0;
    let timer;

    /* ── Spawn particles ── */
    slides.forEach((slide, si) => {
        const container = slide.querySelector('.hero-particles');
        if (!container) return;
        for (let i = 0; i < 18; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.cssText = `
                left: ${Math.random() * 100}%;
                bottom: ${Math.random() * 20}px;
                width:  ${2 + Math.random() * 4}px;
                height: ${2 + Math.random() * 4}px;
                animation-duration: ${4 + Math.random() * 6}s;
                animation-delay:    ${Math.random() * 5}s;
                opacity: ${0.2 + Math.random() * 0.4};
            `;
            container.appendChild(p);
        }
    });

    /* ── Go to slide ── */
    function goTo(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    /* ── Auto advance ── */
    function startTimer() {
        timer = setInterval(() => goTo(current + 1), 4500);
    }
    function resetTimer() {
        clearInterval(timer);
        startTimer();
    }

    startTimer();

    /* ── Dot click ── */
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            goTo(parseInt(dot.dataset.slide));
            resetTimer();
        });
    });

    /* ── Touch swipe ── */
    let startX = 0;
    const banner = document.getElementById('heroBanner');
    banner.addEventListener('touchstart', e => startX = e.touches[0].clientX, { passive: true });
    banner.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) { goTo(dx < 0 ? current + 1 : current - 1); resetTimer(); }
    }, { passive: true });
})();
</script>
@endpush
