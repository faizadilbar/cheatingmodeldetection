@extends('layouts.app')
@section('title', 'Exam Ticket – ' . ($ticket['quiz_code'] ?? ''))
@section('page-title', 'Exam Ticket')
@section('page-subtitle', 'Review your details before starting')

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item active"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('content')
<style>
/* ── Ticket Container ───────────────────────────── */
.ticket-wrap {
    max-width: 480px;
    margin: 32px auto 0;
}

/* ── Ticket Card ────────────────────────────────── */
.ticket-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 24px;
    box-shadow: 0 16px 60px rgba(61,82,160,0.12);
    overflow: hidden;
    animation: ticketIn .45s cubic-bezier(.22,1,.36,1);
}
@keyframes ticketIn {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}

/* ── Ticket Top ─────────────────────────────────── */
.ticket-top {
    padding: 24px 24px 20px;
    background: linear-gradient(135deg, rgba(112,145,230,0.06) 0%, transparent 60%);
}

/* ── Dashed Divider ─────────────────────────────── */
.ticket-divider {
    display: flex;
    align-items: center;
    position: relative;
    margin: 0;
}
.ticket-divider::before,
.ticket-divider::after {
    content: '';
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--bg);
    border: 1.5px solid var(--border);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.ticket-divider::before { margin-left: -9px; }
.ticket-divider::after  { margin-right: -9px; }
.ticket-dash {
    flex: 1;
    height: 1.5px;
    background: repeating-linear-gradient(to right, var(--border) 0, var(--border) 6px, transparent 6px, transparent 12px);
}

/* ── Ticket Bottom ──────────────────────────────── */
.ticket-bottom {
    padding: 20px 24px 24px;
}

/* ── Info Rows ──────────────────────────────────── */
.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    color: var(--text2);
    margin-bottom: 10px;
    font-weight: 500;
}
.info-row .icon-dot {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 12px;
}

/* ── Warning Box ────────────────────────────────── */
.warn-box {
    background: rgba(245, 158, 11, 0.07);
    border: 1.5px solid rgba(245,158,11,0.25);
    border-radius: 14px;
    padding: 12px 14px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 16px 0 20px;
    font-size: 12.5px;
    color: #92400e;
    font-weight: 600;
    line-height: 1.5;
}

/* ── Buttons ────────────────────────────────────── */
.btn-start {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #5B7BE8 0%, #7C3AED 100%);
    color: #fff;
    font-family: var(--font-display);
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.4px;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(91,123,232,0.35);
    transition: transform .15s, box-shadow .15s;
}
.btn-start:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(91,123,232,0.45);
}
.btn-start:active { transform: translateY(0); }

.btn-cancel {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px;
    background: transparent;
    color: var(--text3);
    font-family: var(--font-display);
    font-size: 14px;
    font-weight: 700;
    border: 1.5px solid var(--border);
    border-radius: 14px;
    cursor: pointer;
    margin-top: 10px;
    text-decoration: none;
    transition: border-color .15s, color .15s;
}
.btn-cancel:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* ── Avatar initials ─────────────────────────────── */
.stu-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg,rgba(112,145,230,.3),rgba(124,58,237,.2));
    border: 2px solid rgba(112,145,230,.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-display);
    font-size: 16px;
    font-weight: 900;
    color: var(--primary);
    flex-shrink: 0;
}
</style>

<div class="ticket-wrap">

    <!-- ── EXAM TICKET CARD ── -->
    <div class="ticket-card">

        <!-- TOP: Student credentials -->
        <div class="ticket-top">
            <!-- TICKET label row -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(112,145,230,.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-id-card" style="font-size:12px;color:var(--primary);"></i>
                    </div>
                    <span style="font-family:var(--font-display);font-size:11px;font-weight:900;letter-spacing:1.4px;color:var(--primary);text-transform:uppercase;">Exam Ticket</span>
                </div>
                <span style="background:rgba(16,185,129,.1);color:#059669;font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;padding:4px 10px;border-radius:20px;border:1px solid rgba(16,185,129,.2);">ACTIVE</span>
            </div>

            <!-- Student Row -->
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                <div class="stu-avatar">
                    {{ strtoupper(substr(str_replace(' ','',($ticket['student_name'] ?? 'S')), 0, 2)) }}
                </div>
                <div>
                    <div style="font-family:var(--font-display);font-size:16px;font-weight:900;color:var(--text1);letter-spacing:-.2px;">
                        {{ $ticket['student_name'] ?? 'Student' }}
                    </div>
                    @if(!empty($ticket['roll_no']))
                    <div style="font-size:11.5px;color:var(--text3);font-weight:600;margin-top:2px;">
                        <i class="fas fa-id-badge" style="margin-right:4px;"></i>{{ $ticket['roll_no'] }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Course -->
            @if(!empty($ticket['course_name']))
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--text3);font-weight:600;">
                <span>Course</span>
                <span style="color:var(--text1);font-weight:800;">{{ $ticket['course_name'] }}</span>
            </div>
            @endif
        </div>

        <!-- DASHED DIVIDER -->
        <div class="ticket-divider">
            <div class="ticket-dash"></div>
        </div>

        <!-- BOTTOM: Quiz details -->
        <div class="ticket-bottom">
            <div style="font-family:var(--font-display);font-size:17px;font-weight:900;color:var(--text1);margin-bottom:16px;letter-spacing:-.2px;">
                {{ $ticket['quiz_name'] ?: 'Quiz Assessment' }}
            </div>

            <!-- Info rows -->
            <div class="info-row">
                <div class="icon-dot" style="background:rgba(59,130,246,.1);color:#2563EB;"><i class="fas fa-hashtag"></i></div>
                <span>Quiz Code: <strong style="color:var(--text1);letter-spacing:1.5px;">{{ $ticket['quiz_code'] }}</strong></span>
            </div>

            @if(!empty($ticket['quiz_date']))
            <div class="info-row">
                <div class="icon-dot" style="background:rgba(124,58,237,.1);color:#7C3AED;"><i class="fas fa-calendar-day"></i></div>
                <span>Date: <strong style="color:var(--text1);">{{ \Carbon\Carbon::parse($ticket['quiz_date'])->format('d M Y') }}</strong></span>
            </div>
            @endif

            @if(!empty($ticket['start_time']) && !empty($ticket['end_time']))
            <div class="info-row">
                <div class="icon-dot" style="background:rgba(245,158,11,.1);color:#D97706;"><i class="fas fa-clock"></i></div>
                <span>Window: <strong style="color:var(--text1);">
                    {{ \Carbon\Carbon::parse($ticket['start_time'])->format('h:i A') }}
                    – {{ \Carbon\Carbon::parse($ticket['end_time'])->format('h:i A') }}
                </strong></span>
            </div>
            @endif

            <div class="info-row">
                <div class="icon-dot" style="background:rgba(16,185,129,.1);color:#059669;"><i class="fas fa-list-ol"></i></div>
                <span>Questions: <strong style="color:var(--text1);">{{ $ticket['total_questions'] }}</strong>
                    &nbsp;·&nbsp; Marks: <strong style="color:var(--text1);">{{ $ticket['total_marks'] }}</strong>
                </span>
            </div>

            <!-- Warning Box -->
            <div class="warn-box">
                <i class="fas fa-exclamation-triangle" style="color:#D97706;margin-top:1px;flex-shrink:0;"></i>
                <span>Important: Once started, the timer cannot be paused. Switching tabs or minimizing the window will flag your attempt.</span>
            </div>

            <!-- START FORM -->
            <form method="POST" action="{{ route('student.quiz.start') }}" id="startForm">
                @csrf
                <input type="hidden" name="quiz_code" value="{{ $ticket['quiz_code'] }}">
                <input type="hidden" name="course_id"   value="{{ $ticket['course_id'] ?? 0 }}">

                <button type="submit" class="btn-start" id="startBtn">
                    <i class="fas fa-play-circle"></i>
                    START EXAM NOW
                </button>
            </form>

            <!-- CANCEL -->
            <a href="{{ url()->previous() ?: route('student.dashboard') }}" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Cancel – Go Back
            </a>
        </div>
    </div>

</div>

<script>
// Prevent double-submit
document.getElementById('startForm').addEventListener('submit', function () {
    const btn = document.getElementById('startBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting…';
});
</script>
@endsection
