@extends('layouts.app')
@section('title', 'Proctoring Report Detail')
@section('page-title', 'Report Details')
@section('page-subtitle') Detailed proctoring log and violation breakdown @endsection

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item active"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@section('topbar-actions')
<a href="{{ route('teacher.proctor.reports') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Reports</a>
@endsection

@push('styles')
<style>
    /* ─── GRID LAYOUT ─── */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ─── PREMIUM CARD ─── */
    .detail-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 24px rgba(61,82,160,0.04);
        margin-bottom: 24px;
    }
    .detail-card-title {
        font-family: var(--font-display);
        font-size: 16px;
        font-weight: 800;
        color: var(--text1);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ─── PROFILE CARD ─── */
    .profile-card {
        text-align: center;
        padding-top: 15px;
    }
    .large-avatar {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--deep), var(--mid));
        color: #fff;
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        box-shadow: 0 8px 24px rgba(112,145,230,0.3);
    }
    .student-h1 {
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 800;
        color: var(--text1);
    }
    .info-list {
        text-align: left;
        margin-top: 24px;
        border-top: 1px solid var(--border);
        padding-top: 18px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 13px;
        border-bottom: 1px dashed rgba(112,145,230,0.08);
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        color: var(--text3);
        font-weight: 600;
    }
    .info-value {
        color: var(--text1);
        font-weight: 700;
    }

    /* ─── RISK SCORE PANEL ─── */
    .risk-circle-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    .risk-gauge {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
    }
    .risk-gauge-inner {
        width: 116px;
        height: 116px;
        border-radius: 50%;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }
    .risk-gauge-score {
        font-family: var(--font-display);
        font-size: 32px;
        font-weight: 900;
    }
    .risk-gauge-lbl {
        font-size: 10px;
        color: var(--text3);
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 2px;
    }

    /* ─── METRIC BOXES ─── */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
    }
    .metric-box {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 14px 12px;
        text-align: center;
    }
    .metric-box-val {
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 800;
        color: var(--text1);
    }
    .metric-box-val.alert-val {
        color: #EF4444;
    }
    .metric-box-lbl {
        font-size: 11px;
        font-weight: 600;
        color: var(--text3);
        margin-top: 4px;
    }

    /* ─── VIOLATIONS TIMELINE ─── */
    .timeline-wrap {
        position: relative;
        padding-left: 28px;
        margin-top: 15px;
    }
    .timeline-wrap::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: var(--border);
    }
    .timeline-item {
        position: relative;
        margin-bottom: 24px;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: -28px;
        top: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 9px;
    }
    .dot-crit  { background: #EF4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.2); }
    .dot-warn  { background: #F59E0B; box-shadow: 0 0 0 4px rgba(245,158,11,0.2); }
    .dot-info  { background: #7091E6; box-shadow: 0 0 0 4px rgba(112,145,230,0.2); }

    .timeline-content {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px 16px;
    }
    .timeline-time {
        font-size: 11px;
        color: var(--text3);
        font-weight: 700;
    }
    .timeline-title {
        font-family: var(--font-display);
        font-size: 13.5px;
        font-weight: 800;
        color: var(--text1);
        margin-top: 4px;
    }
    .timeline-desc {
        font-size: 12px;
        color: var(--text2);
        margin-top: 4px;
        line-height: 1.5;
    }
</style>
@endpush

@section('content')
<div class="fade-in">
    @if($error)
        <div style="background:rgba(239,68,68,0.08); border:1.5px solid rgba(239,68,68,0.2); border-radius:14px; padding:15px 20px; color:#EF4444; font-size:13.5px; display:flex; align-items:center; gap:10px; margin-bottom:24px;">
            <i class="fas fa-exclamation-triangle" style="font-size:16px;"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    <div class="detail-grid">
        {{-- Profile and Risk panel --}}
        <div>
            {{-- Profile Card --}}
            <div class="detail-card profile-card">
                @php
                    $studentName = $session['student_name'] ?? 'Student';
                    $initials    = strtoupper(substr($studentName, 0, 1));

                    $gazeCount   = (int)($session['gaze_away_count'] ?? 0);
                    $headCount   = (int)($session['head_turn_count'] ?? 0);
                    $noFaceCount = (int)($session['no_face_count'] ?? 0);
                    $multiCount  = (int)($session['multiple_face_count'] ?? 0);
                    $blinkCount  = (int)($session['blink_count'] ?? $session['total_blinks'] ?? 0);

                    $rawRisk     = (float)($session['risk_score'] ?? $session['max_risk_score'] ?? $session['avg_risk_score'] ?? 0);
                    if ($rawRisk <= 0 && ($gazeCount > 0 || $headCount > 0 || $noFaceCount > 0 || $multiCount > 0)) {
                        $rawRisk = min(100, ($gazeCount * 10) + ($headCount * 8) + ($noFaceCount * 20) + ($multiCount * 25));
                    }
                    $riskScore   = round($rawRisk);

                    $alarmLevel  = strtolower($session['alarm_level'] ?? 'none');
                    if (($alarmLevel === 'none' || $alarmLevel === '' || $alarmLevel === 'calibrating') && $riskScore > 0) {
                        if ($riskScore >= 75) $alarmLevel = 'critical';
                        elseif ($riskScore >= 50) $alarmLevel = 'high';
                        elseif ($riskScore >= 25) $alarmLevel = 'medium';
                        else $alarmLevel = 'low';
                    }

                    $totalViolations = $session['total_violations'] ?? $session['total_alarms'] ?? ($gazeCount + $headCount + $noFaceCount + $multiCount);
                @endphp
                <div class="large-avatar">{{ $initials }}</div>
                <h1 class="student-h1">{{ $studentName }}</h1>
                <div style="font-size:12px; color:var(--text3); margin-top:4px;">ID: {{ $session['student_id'] ?? 'N/A' }}</div>

                <div class="info-list">
                    <div class="info-row">
                        <span class="info-label">Course:</span>
                        <span class="info-value">{{ $session['course_name'] ?? 'Unknown Course' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quiz Code:</span>
                        <span class="info-value" style="color:var(--mid);">{{ $session['quiz_code'] ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Exam Date:</span>
                        <span class="info-value">{{ $session['exam_date'] ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Time Logged:</span>
                        <span class="info-value">{{ $session['start_time'] ?? '' }} - {{ $session['end_time'] ?? '' }}</span>
                    </div>
                </div>
            </div>

            {{-- Risk Circular Gauge Card --}}
            <div class="detail-card" style="text-align:center;">
                <div class="detail-card-title" style="justify-content:center;">
                    <i class="fas fa-bullseye"></i> Cheating Risk Score
                </div>
                <div class="risk-circle-wrap">
                    @php
                        $gaugeColor = '#10B981'; // Green
                        if ($alarmLevel === 'critical' || $alarmLevel === 'high') $gaugeColor = '#EF4444'; // Red
                        elseif ($alarmLevel === 'medium') $gaugeColor = '#F59E0B'; // Orange
                        elseif ($alarmLevel === 'low') $gaugeColor = '#3D52A0'; // Blue
                    @endphp
                    <div class="risk-gauge" style="background: conic-gradient({{ $gaugeColor }} {{ $riskScore * 3.6 }}deg, var(--bg) 0deg);">
                        <div class="risk-gauge-inner">
                            <div class="risk-gauge-score" style="color: {{ $gaugeColor }};">{{ $riskScore }}%</div>
                            <div class="risk-gauge-lbl">{{ $alarmLevel ?: 'None' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail metrics breakdown and violations logs --}}
        <div>
            {{-- Metrics Breakdown --}}
            <div class="detail-card">
                <div class="detail-card-title">
                    <i class="fas fa-chart-pie"></i> Detection Flags Breakdown
                </div>
                <div class="metric-grid">
                    <div class="metric-box">
                        <div class="metric-box-val alert-val">{{ $session['gaze_away_count'] ?? 0 }}</div>
                        <div class="metric-box-lbl">Gaze Away</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-box-val alert-val">{{ $session['head_turn_count'] ?? 0 }}</div>
                        <div class="metric-box-lbl">Head Turns</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-box-val alert-val">{{ $session['no_face_count'] ?? 0 }}</div>
                        <div class="metric-box-lbl">No Face</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-box-val alert-val">{{ $session['multiple_face_count'] ?? 0 }}</div>
                        <div class="metric-box-lbl">Multiple Faces</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-box-val" style="color: var(--teal);">{{ $session['blink_count'] ?? 0 }}</div>
                        <div class="metric-box-lbl">Blinks Logged</div>
                    </div>
                </div>
            </div>

            {{-- Violations Logs Timeline --}}
            <div class="detail-card">
                <div class="detail-card-title">
                    <i class="fas fa-list-ul"></i> Proctoring Violations Timeline
                </div>

                @if(count($violations) > 0)
                    <div class="timeline-wrap">
                        @foreach($violations as $v)
                            @php
                                $vType = strtoupper($v['type'] ?? 'NONE');
                                $vScore = round($v['risk_score'] ?? 0);
                                $vLevel = strtolower($v['alarm_level'] ?? 'none');

                                $dotClass = 'dot-info';
                                if ($vLevel === 'critical' || $vLevel === 'high') $dotClass = 'dot-crit';
                                elseif ($vLevel === 'medium') $dotClass = 'dot-warn';
                            @endphp
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $dotClass }}">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-time">{{ $v['timestamp'] ?? '' }}</div>
                                    <div class="timeline-title" style="color: {{ $vLevel === 'critical' || $vLevel === 'high' ? '#EF4444' : 'var(--text1)' }};">
                                        {{ $vType }} DETECTED
                                    </div>
                                    <div class="timeline-desc">
                                        Risk score recorded at <strong>{{ $vScore }}%</strong> with alarm level <strong>{{ $vLevel }}</strong>.
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align:center; padding:30px 10px; color:var(--text3); font-size:13px;">
                        <i class="fas fa-check-circle" style="font-size:24px; color:#10B981; margin-bottom:8px; display:block;"></i>
                        No violations recorded during this exam session. Excellent integrity!
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
