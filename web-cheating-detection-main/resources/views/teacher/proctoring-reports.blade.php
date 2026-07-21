@extends('layouts.app')
@section('title', 'AI Proctoring Reports')
@section('page-title', 'Proctoring Reports')
@section('page-subtitle') View cheating logs and proctoring metrics for all quiz sessions @endsection

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item active"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@push('styles')
<style>
    /* ─── SEARCH / FILTER BAR ─── */
    .filter-section {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: 0 4px 18px rgba(61,82,160,0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }
    .search-input-wrap {
        position: relative;
        flex: 1;
        min-width: 250px;
    }
    .search-input-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text3);
        font-size: 13.5px;
    }
    .search-input {
        width: 100%;
        padding: 10px 14px 10px 38px;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        font-family: var(--font-body);
        font-size: 13.5px;
        color: var(--text1);
        transition: 0.2s;
        background: var(--bg);
    }
    .search-input:focus {
        outline: none;
        border-color: var(--mid);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(112,145,230,0.12);
    }
    .filter-actions {
        display: flex;
        gap: 10px;
    }

    /* ─── REPORT CARDS / LIST ─── */
    .reports-table-wrap {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 24px rgba(61,82,160,0.04);
    }
    .reports-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .reports-table th {
        padding: 16px 20px;
        background: var(--bg);
        border-bottom: 1.5px solid var(--border);
        font-family: var(--font-display);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text2);
    }
    .reports-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 13.5px;
        color: var(--text1);
        vertical-align: middle;
    }
    .reports-table tr:last-child td {
        border-bottom: none;
    }
    .reports-table tr:hover {
        background: rgba(244,246,255,0.4);
    }

    /* ─── STATUS & RISK BADGES ─── */
    .risk-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        font-family: var(--font-display);
    }
    .risk-critical { background: rgba(239,68,68,0.12); color: #EF4444; border: 1.5px solid rgba(239,68,68,0.2); }
    .risk-high { background: rgba(239,68,68,0.08); color: #EF4444; border: 1.5px solid rgba(239,68,68,0.15); }
    .risk-medium { background: rgba(245,158,11,0.08); color: #D97706; border: 1.5px solid rgba(245,158,11,0.15); }
    .risk-low { background: rgba(16,185,129,0.08); color: #059669; border: 1.5px solid rgba(16,185,129,0.15); }
    .risk-none { background: rgba(16,185,129,0.05); color: #059669; border: 1.5px solid rgba(16,185,129,0.1); }

    .student-meta {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .student-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--deep), var(--mid));
        color: #fff;
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(112,145,230,0.25);
    }
    .student-name {
        font-weight: 700;
        font-family: var(--font-display);
        color: var(--text1);
    }
    .student-id {
        font-size: 11px;
        color: var(--text3);
        margin-top: 2px;
    }

    .btn-view-report {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        background: var(--snow);
        border: 1.5px solid var(--border);
        color: var(--deep);
        font-family: var(--font-display);
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s;
        cursor: pointer;
    }
    .btn-view-report:hover {
        background: var(--deep);
        color: #fff;
        border-color: var(--deep);
        box-shadow: 0 4px 12px rgba(61,82,160,0.25);
    }

    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 48px;
        color: var(--mist);
        margin-bottom: 16px;
    }
    .empty-state h3 {
        font-family: var(--font-display);
        font-size: 18px;
        font-weight: 800;
        color: var(--text1);
    }
    .empty-state p {
        font-size: 13px;
        color: var(--text3);
        margin-top: 6px;
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

    {{-- Filter section --}}
    <div class="filter-section">
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="reportsSearch" class="search-input" placeholder="Search by student name, roll number, course or quiz code..." onkeyup="filterReports()">
        </div>
        <div class="filter-actions">
            <select id="riskFilter" class="search-input" style="padding-left:14px; min-width:150px; cursor:pointer;" onchange="filterReports()">
                <option value="all">All Risk Levels</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
                <option value="none">None</option>
            </select>
        </div>
    </div>

    {{-- Main Reports List --}}
    <div class="reports-table-wrap">
        @if(count($sessions) > 0)
            <table class="reports-table" id="reportsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course / Quiz</th>
                        <th>Exam Date</th>
                        <th style="text-align:center;">Risk Score</th>
                        <th>Alarm Level</th>
                        <th style="text-align:center;">Violations</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $s)
                        @php
                            $studentName = $s['student_name'] ?? 'Student';
                            $initials    = strtoupper(substr($studentName, 0, 1));

                            $gazeCount   = (int)($s['gaze_away_count'] ?? 0);
                            $headCount   = (int)($s['head_turn_count'] ?? 0);
                            $noFaceCount = (int)($s['no_face_count'] ?? 0);
                            $multiCount  = (int)($s['multiple_face_count'] ?? 0);

                            $rawRisk     = (float)($s['risk_score'] ?? $s['max_risk_score'] ?? $s['avg_risk_score'] ?? 0);
                            if ($rawRisk <= 0 && ($gazeCount > 0 || $headCount > 0 || $noFaceCount > 0 || $multiCount > 0)) {
                                $rawRisk = min(100, ($gazeCount * 10) + ($headCount * 8) + ($noFaceCount * 20) + ($multiCount * 25));
                            }
                            $riskScore   = round($rawRisk);

                            $alarmLevel  = strtolower($s['alarm_level'] ?? 'none');
                            if (($alarmLevel === 'none' || $alarmLevel === '' || $alarmLevel === 'calibrating') && $riskScore > 0) {
                                if ($riskScore >= 75) $alarmLevel = 'critical';
                                elseif ($riskScore >= 50) $alarmLevel = 'high';
                                elseif ($riskScore >= 25) $alarmLevel = 'medium';
                                else $alarmLevel = 'low';
                            }

                            $violations  = $s['total_violations'] ?? $s['total_alarms'] ?? ($gazeCount + $headCount + $noFaceCount + $multiCount);

                            $riskClass = 'risk-none';
                            if ($alarmLevel === 'critical') $riskClass = 'risk-critical';
                            elseif ($alarmLevel === 'high') $riskClass = 'risk-high';
                            elseif ($alarmLevel === 'medium') $riskClass = 'risk-medium';
                            elseif ($alarmLevel === 'low') $riskClass = 'risk-low';
                        @endphp
                        <tr class="report-row" data-name="{{ strtolower($studentName) }}" data-id="{{ strtolower($s['student_id'] ?? '') }}" data-course="{{ strtolower($s['course_name'] ?? '') }}" data-code="{{ strtolower($s['quiz_code'] ?? '') }}" data-risk="{{ $alarmLevel }}">
                            <td>
                                <div class="student-meta">
                                    <div class="student-avatar">{{ $initials }}</div>
                                    <div>
                                        <div class="student-name">{{ $studentName }}</div>
                                        <div class="student-id">ID: {{ $s['student_id'] ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--text1);">{{ $s['course_name'] ?? 'Unknown Course' }}</div>
                                <div style="font-size:11px; color:var(--text3); margin-top:2px;">Quiz Code: <strong style="color:var(--mid);">{{ $s['quiz_code'] ?? '' }}</strong></div>
                            </td>
                            <td>
                                <div style="font-weight:500;">{{ $s['exam_date'] ?? '' }}</div>
                                <div style="font-size:11px; color:var(--text3); margin-top:2px;">{{ $s['start_time'] ?? '' }} - {{ $s['end_time'] ?? '' }}</div>
                            </td>
                            <td style="text-align:center;">
                                <strong style="font-size:15px; color: {{ $alarmLevel === 'critical' || $alarmLevel === 'high' ? '#EF4444' : ($alarmLevel === 'medium' ? '#D97706' : '#059669') }};">
                                    {{ $riskScore }}%
                                </strong>
                            </td>
                            <td>
                                <span class="risk-badge {{ $riskClass }}">
                                    {{ $alarmLevel ?: 'None' }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:700; color: {{ $violations > 0 ? '#EF4444' : 'var(--text3)' }};">
                                    {{ $violations }}
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a href="{{ route('teacher.proctor.report-detail', $s['id'] ?? $s['session_id']) }}" class="btn-view-report">
                                    <i class="fas fa-chart-line"></i> View Report
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <i class="fas fa-shield-halved"></i>
                <h3>No Proctoring Sessions Found</h3>
                <p>Sessions will appear here once students start a proctored quiz.</p>
            </div>
        @endif
    </div>
</div>

<script>
    function filterReports() {
        const query = document.getElementById('reportsSearch').value.toLowerCase();
        const risk = document.getElementById('riskFilter').value.toLowerCase();
        const rows = document.querySelectorAll('.report-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const id = row.getAttribute('data-id');
            const course = row.getAttribute('data-course');
            const code = row.getAttribute('data-code');
            const rowRisk = row.getAttribute('data-risk');

            const matchesQuery = name.includes(query) || id.includes(query) || course.includes(query) || code.includes(query);
            const matchesRisk = risk === 'all' || rowRisk === risk;

            if (matchesQuery && matchesRisk) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
