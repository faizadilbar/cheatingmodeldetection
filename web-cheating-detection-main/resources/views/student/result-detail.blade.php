@extends('layouts.app')
@section('title','Quiz Result Detail')
@section('page-title','Quiz Feedback')
@section('page-subtitle', $result['quiz_name'] ?? 'Result Detail')

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item active"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('topbar-actions')
<a href="{{ route('student.results') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
@endsection

@push('styles')
<style>
/* ── Metric summary ── */
.metric-box {
    background: #F8FAFC;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 18px 12px;
    text-align: center;
}
.metric-val { font-family: var(--font-display); font-size: 26px; font-weight: 800; color: var(--text1); margin-bottom: 4px; }
.metric-lbl { font-size: 10.5px; color: var(--text3); text-transform: uppercase; font-weight: 700; letter-spacing: .7px; }

/* ── Q card ── */
.q-result-card {
    background: var(--surface, #fff);
    border: 1.5px solid var(--border);
    border-radius: 18px;
    padding: 22px 20px;
    margin-bottom: 18px;
    box-shadow: 0 2px 12px rgba(61,82,160,.06);
}

/* ── Q number badge ── */
.q-num-badge {
    display: inline-block;
    background: var(--primary, #3D52A0);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 12px;
    border-radius: 20px;
    margin-bottom: 10px;
    letter-spacing: .4px;
}

/* ── Question text ── */
.q-text {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--text1);
    margin-bottom: 14px;
    line-height: 1.55;
}

/* ── Student answer box ── */
.student-answer-box {
    background: #F4F6FF;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13.5px;
    color: var(--text2);
    margin-bottom: 16px;
    line-height: 1.55;
    border-left: 4px solid var(--primary, #3D52A0);
}

/* ── Score pill row ── */
.score-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.pill-keyword {
    background: rgba(112,145,230,.15);
    color: #3D52A0;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 12.5px;
    font-weight: 700;
}
.pill-ai {
    background: rgba(139,92,246,.15);
    color: #6d28d9;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 12.5px;
    font-weight: 700;
}

/* ── Final score row ── */
.final-score-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 800;
    color: var(--text1);
}
.final-score-row.good  { color: #059669; }
.final-score-row.mid   { color: #D97706; }
.final-score-row.bad   { color: #DC2626; }
.final-score-row i { font-size: 16px; }

/* ── AI Feedback box ── */
.ai-feedback-box {
    background: linear-gradient(135deg, rgba(237,252,245,.8), rgba(236,253,245,1));
    border: 1.5px solid #A7F3D0;
    border-radius: 12px;
    padding: 14px 16px;
    color: #065F46;
    font-size: 13px;
    line-height: 1.6;
}
.ai-feedback-box .fb-label {
    font-weight: 800;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Keywords expected chips ── */
.kw-chip {
    display: inline-block;
    background: rgba(61,82,160,.08);
    color: var(--text2);
    border-radius: 6px;
    padding: 2px 9px;
    font-size: 11.5px;
    font-weight: 600;
    margin: 2px 2px 2px 0;
}

/* ── Section divider ── */
.section-divider {
    border: none;
    border-top: 1.5px dashed var(--border);
    margin: 14px 0;
}
</style>
@endpush

@section('content')
@if(!($result['status'] ?? false))
    {{-- LOCKED RESULTS VIEW --}}
    <div class="card fade-in" style="max-width:520px;margin:40px auto;text-align:center;border-radius:20px;box-shadow:0 10px 40px rgba(61,82,160,0.1);border:1px solid var(--border);">
        <div class="card-body" style="padding:48px 32px;">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(112,145,230,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:34px;color:var(--primary);">
                <i class="fas fa-lock"></i>
            </div>
            <h2 style="font-family:var(--font-display);font-size:24px;font-weight:800;color:var(--text1);margin-bottom:12px;">Quiz Submitted! 🎉</h2>
            <p style="color:var(--text2);font-size:14px;line-height:1.6;margin-bottom:28px;">
                Your exam answers have been securely recorded. The detailed scoring, evaluation, and AI-powered conceptual feedback will unlock automatically once the scheduled quiz session ends.
            </p>
            @php
                $unlockDate      = $result['unlock_date']           ?? 'Today';
                $unlockFormatted = $result['unlock_time_formatted']  ?? null;
            @endphp
            <div style="background:rgba(112,145,230,0.05);border:1.5px dashed rgba(112,145,230,0.3);border-radius:16px;padding:20px;display:inline-block;width:100%;max-width:360px;margin:0 auto 32px;">
                <span style="font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--text3);display:block;margin-bottom:10px;">Result Unlock Time</span>
                @if($unlockFormatted)
                    <div style="font-family:'Bricolage Grotesque',var(--font-display),sans-serif;font-size:15px;font-weight:700;color:var(--text2);margin-bottom:6px;">
                        <i class="far fa-calendar-alt" style="margin-right:6px;color:var(--primary);"></i> {{ $unlockDate }}
                    </div>
                    <div style="font-family:'Bricolage Grotesque',var(--font-display),sans-serif;font-size:22px;font-weight:900;color:var(--primary);">
                        <i class="far fa-clock" style="margin-right:6px;font-size:18px;"></i> {{ $unlockFormatted }}
                    </div>
                @else
                    <div style="font-size:13.5px;color:var(--text2);font-weight:600;">
                        <i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px;"></i> Unlock time is set by your teacher.
                    </div>
                @endif
            </div>
            <a href="{{ route('student.dashboard') }}" class="btn btn-primary" style="padding:12px 32px;font-size:14.5px;font-weight:700;border-radius:12px;display:inline-flex;align-items:center;gap:8px;width:100%;justify-content:center;">
                <i class="fas fa-home"></i> Return to Dashboard
            </a>
        </div>
    </div>
@else
    {{-- UNLOCKED RESULTS VIEW --}}

    {{-- ── Score Summary ── --}}
    <div class="card fade-in" style="margin-bottom:24px;">
        <div class="card-header">
            <h3><i class="fas fa-poll" style="color:var(--primary);margin-right:8px;"></i>Score Summary</h3>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;">
                <div class="metric-box">
                    <div class="metric-val" style="color:var(--primary);">{{ $result['score'] }} / {{ $result['total_marks'] }}</div>
                    <div class="metric-lbl">Obtained Marks</div>
                </div>
                <div class="metric-box">
                    <div class="metric-val" style="color:{{ $result['percentage'] >= 50 ? 'var(--green)' : 'var(--red)' }}">{{ $result['percentage'] }}%</div>
                    <div class="metric-lbl">Percentage</div>
                </div>
                <div class="metric-box">
                    <div class="metric-val" style="color:var(--green);">{{ $result['correct_answers'] ?? $result['correct'] ?? 0 }}</div>
                    <div class="metric-lbl">Correct Qs</div>
                </div>
                <div class="metric-box">
                    <div class="metric-val" style="color:var(--red);">{{ $result['wrong_answers'] ?? $result['wrong'] ?? 0 }}</div>
                    <div class="metric-lbl">Wrong Qs</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Short Answer AI Evaluation ── --}}
    @if(!empty($result['short_answers']))
    <h2 style="font-family:var(--font-display);font-size:17px;font-weight:800;margin-bottom:16px;color:var(--text1);">
        <i class="fas fa-robot" style="color:var(--primary);margin-right:8px;font-size:15px;"></i>AI Question-by-Question Evaluation
    </h2>

    @foreach($result['short_answers'] as $i => $sa)
        @php
            $fs       = floatval($sa['final_score'] ?? 0);
            $fsClass  = $fs >= 70 ? 'good' : ($fs >= 40 ? 'mid' : 'bad');
            $fsIcon   = $fs >= 70 ? 'fa-check-circle' : ($fs >= 40 ? 'fa-exclamation-circle' : 'fa-times-circle');
            $kwScore  = $sa['keyword_score']  ?? 0;
            $aiScore  = $sa['ai_score']       ?? 0;
            $kwWeight = $sa['keyword_weight'] ?? 20;
            $aiWeight = $sa['ai_weight']      ?? 80;
            // Weighted contribution points
            $kwContrib = round(($kwScore * $kwWeight) / 100, 1);
            $aiContrib = round(($aiScore * $aiWeight) / 100, 1);
        @endphp
        <div class="q-result-card fade-in">

            {{-- Q number --}}
            <span class="q-num-badge">Q{{ $i + 1 }}</span>

            {{-- Question --}}
            <div class="q-text">{{ $sa['question'] }}</div>

            <hr class="section-divider">

            {{-- Student Answer --}}
            <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:6px;letter-spacing:.5px;">
                <i class="fas fa-pen-nib" style="margin-right:4px;"></i> Your Answer
            </div>
            <div class="student-answer-box">
                {{ $sa['student_answer'] ?: 'No answer submitted' }}
            </div>

            {{-- Score Pills Row --}}
            <div class="score-pills">
                <span class="pill-keyword">
                    <i class="fas fa-key" style="margin-right:4px;font-size:10px;"></i>
                    Keywords ({{ $kwWeight }}%): {{ $kwScore }}
                </span>
                <span class="pill-ai">
                    <i class="fas fa-brain" style="margin-right:4px;font-size:10px;"></i>
                    AI Eval ({{ $aiWeight }}%): {{ $aiScore }}
                </span>
            </div>

            {{-- Final Score Row --}}
            <div class="final-score-row {{ $fsClass }}">
                <i class="fas {{ $fsIcon }}"></i>
                Final Score: {{ $fs }}%
                <span style="font-size:11.5px;font-weight:600;color:var(--text3);margin-left:4px;">
                    (Keywords: {{ $kwContrib }} + AI: {{ $aiContrib }} = {{ round($kwContrib + $aiContrib, 1) }} pts)
                </span>
            </div>

            {{-- Keywords Expected --}}
            @if(!empty($sa['expected_keywords']))
            <div style="margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:6px;letter-spacing:.5px;">
                    <i class="fas fa-tags" style="margin-right:4px;"></i> Expected Keywords
                </div>
                <div>
                    @foreach($sa['expected_keywords'] as $kw)
                        <span class="kw-chip">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- AI Feedback --}}
            <div class="ai-feedback-box">
                <div class="fb-label">
                    <i class="fas fa-robot"></i> AI Feedback
                </div>
                {{ $sa['feedback'] ?? 'No feedback available.' }}
            </div>

        </div>
    @endforeach
    @endif

@endif
@endsection
