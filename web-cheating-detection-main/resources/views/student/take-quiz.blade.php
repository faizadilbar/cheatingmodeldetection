<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $quiz['quiz_name'] ?? 'Quiz' }} – Exam Mode</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Bricolage+Grotesque:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --deep: #3D52A0; --mid: #7091E6; --slate: #8697C4;
            --mist: #ADBBDA; --snow: #EDE8F5; --bg: #F4F6FF;
            --white: #FFFFFF; --text1: #18213D; --text2: #4A5478;
            --text3: #8697C4; --border: rgba(112,145,230,0.15);
            --red: #EF4444; --green: #059669; --amber: #D97706;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text1);
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── TOP APP BAR ─────────────────────────────── */
        .app-bar {
            background: var(--deep);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
            box-shadow: 0 4px 20px rgba(61,82,160,0.25);
        }
        .app-bar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .quiz-name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 260px;
        }
        .quiz-code-tag {
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.8);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .timer-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 8px 16px;
            flex-shrink: 0;
        }
        .timer-wrap.warning {
            background: rgba(239,68,68,0.2);
            border-color: rgba(239,68,68,0.4);
            animation: blink 0.8s linear infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.6} }
        #countdown {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: #fff;
            min-width: 55px;
            text-align: center;
        }
        .timer-icon { color: rgba(255,255,255,0.7); font-size: 14px; }

        /* ── PROGRESS BAR ─────────────────────────────── */
        .prog-bar-wrap { height: 4px; background: rgba(61,82,160,0.15); flex-shrink: 0; }
        .prog-bar-fill {
            height: 4px;
            background: linear-gradient(90deg, var(--green), #34D399);
            transition: width 0.4s ease;
            position: relative;
        }
        .prog-bar-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

        /* ── CANDIDATE STRIP ─────────────────────────── */
        .candidate-strip {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .candidate-left { display: flex; align-items: center; gap: 12px; }
        .cand-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--deep), var(--mid));
            border-radius: 50%; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 14px;
            flex-shrink: 0;
        }
        .cand-name {
            font-weight: 800; font-size: 13.5px; color: var(--text1);
            font-family: 'Bricolage Grotesque', sans-serif;
        }
        .cand-sub { font-size: 11px; color: var(--text3); font-weight: 600; margin-top: 2px; }
        .secure-badge {
            background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.25);
            color: var(--green);
            font-size: 9px; font-weight: 900; letter-spacing: 0.5px;
            padding: 3px 8px; border-radius: 6px;
            display: flex; align-items: center; gap: 4px;
        }
        .secure-badge::before { content: ''; width: 5px; height: 5px; background: var(--green); border-radius: 50%; animation: secpulse 1s infinite; }
        @keyframes secpulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
        .q-counter-badge {
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 11px;
            font-weight: 900;
            padding: 5px 12px;
            border-radius: 8px;
        }

        /* ── QUESTION MAP STRIP ──────────────────────── */
        .q-map-strip {
            background: rgba(244,246,255,0.6);
            border-bottom: 1px solid var(--border);
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            flex-shrink: 0;
            scrollbar-width: none;
        }
        .q-map-strip::-webkit-scrollbar { display: none; }
        .q-map-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: 1.5px solid var(--mist);
            background: var(--white);
            color: var(--text2);
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            position: relative;
        }
        .q-map-btn.current {
            background: linear-gradient(135deg, var(--deep), var(--mid));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(61,82,160,0.35);
            transform: scale(1.08);
        }
        .q-map-btn.answered {
            background: linear-gradient(135deg, #059669, #10B981);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 3px 8px rgba(5,150,105,0.25);
        }
        .q-map-btn.flagged {
            background: linear-gradient(135deg, #D97706, #FBBF24);
            color: #fff;
            border-color: transparent;
        }
        .q-map-btn.flagged::after { content: '⚑'; position: absolute; top: 1px; right: 2px; font-size: 7px; }

        /* ── MAIN SCROLL AREA ────────────────────────── */
        .main-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        /* ── QUESTION CARD ───────────────────────────── */
        .q-panel {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 6px 24px rgba(61,82,160,0.06);
            overflow: hidden;
        }
        .q-panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--snow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .q-num {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--deep);
        }
        .q-type-tag {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .q-type-tag.mcq { background: rgba(112,145,230,0.12); color: var(--mid); }
        .q-type-tag.short { background: rgba(5,150,105,0.1); color: var(--green); }
        .q-type-tag.fill { background: rgba(217,119,6,0.1); color: var(--amber); }

        .q-body { padding: 20px; }
        .q-text {
            font-size: 17px;
            font-weight: 600;
            line-height: 1.65;
            color: var(--text1);
            margin-bottom: 20px;
        }

        /* ── MCQ OPTIONS ─────────────────────────────── */
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .opt {
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--snow);
            user-select: none;
        }
        .opt:hover { border-color: var(--mid); }
        .opt.selected {
            border-color: var(--mid);
            background: rgba(112,145,230,0.1);
            box-shadow: 0 3px 14px rgba(112,145,230,0.15);
        }
        .opt-radio {
            width: 22px; height: 22px; border-radius: 50%;
            border: 2px solid var(--slate);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: 0.2s; background: #fff;
        }
        .opt.selected .opt-radio { border-color: var(--mid); background: var(--mid); }
        .opt.selected .opt-radio::after { content: ''; width: 7px; height: 7px; background: #fff; border-radius: 50%; }
        .opt-letter {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 800; font-size: 13px; color: var(--slate);
            flex-shrink: 0;
        }
        .opt-text { font-size: 14.5px; color: var(--text1); font-weight: 500; }

        /* ── TEXT INPUTS ─────────────────────────────── */
        .text-input {
            width: 100%; padding: 14px 16px;
            background: var(--snow);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            color: var(--text1);
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: 0.2s;
        }
        .text-input:focus { outline: none; border-color: var(--mid); background: #fff; box-shadow: 0 0 0 4px rgba(112,145,230,0.12); }
        textarea.text-input { min-height: 120px; resize: vertical; }

        /* ── BOTTOM ACTION DOCK ──────────────────────── */
        .action-dock {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 14px 16px;
            flex-shrink: 0;
            box-shadow: 0 -4px 20px rgba(61,82,160,0.05);
        }
        .dock-row1 { display: flex; gap: 10px; margin-bottom: 10px; }
        .dock-row2 { /* submit row */ }

        .btn-prev {
            width: 48px; height: 48px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: var(--bg);
            color: var(--deep);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .btn-prev:hover:not(:disabled) { border-color: var(--mid); background: var(--snow); }
        .btn-prev:disabled { opacity: 0.4; cursor: not-allowed; }

        .btn-flag {
            flex: 1; height: 48px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: transparent;
            color: var(--text2);
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 12px; font-weight: 800; letter-spacing: 0.5px;
            cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-flag.flagged {
            border-color: rgba(217,119,6,0.6);
            background: rgba(217,119,6,0.08);
            color: var(--amber);
        }
        .btn-flag:hover { border-color: var(--amber); color: var(--amber); }

        .btn-save-next {
            flex: 2; height: 48px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #059669, #10B981);
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 12px; font-weight: 900; letter-spacing: 0.5px;
            cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        }
        .btn-save-next:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(5,150,105,0.4); }

        .btn-next-skip {
            width: 48px; height: 48px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: var(--bg);
            color: var(--deep);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .btn-next-skip:hover:not(:disabled) { border-color: var(--mid); background: var(--snow); }
        .btn-next-skip:disabled { opacity: 0.4; cursor: not-allowed; }

        .btn-submit {
            width: 100%; height: 44px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #EF4444, #DC2626, #B91C1C);
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 12px; font-weight: 900; letter-spacing: 1px;
            cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }
        .btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(239,68,68,0.4); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── LEGEND ──────────────────────────────────── */
        .map-legend {
            display: flex; gap: 14px; font-size: 10.5px; color: var(--text3);
            font-weight: 600; align-items: center; flex-shrink: 0; white-space: nowrap;
        }
        .legend-dot {
            width: 10px; height: 10px;
            border-radius: 3px;
            display: inline-block; margin-right: 4px;
        }

        /* ── MODALS ───────────────────────────────────── */
        .overlay { position: fixed; inset: 0; background: rgba(24,33,61,0.8); z-index: 9999; display: none; align-items: flex-end; justify-content: center; backdrop-filter: blur(6px); }
        .overlay.show { display: flex; }
        .modal-sheet {
            background: var(--white);
            border-radius: 24px 24px 0 0;
            padding: 24px 24px 32px;
            width: 100%; max-width: 640px;
            animation: slideUp 0.3s cubic-bezier(0.34,1.2,0.64,1);
        }
        @keyframes slideUp { from{transform:translateY(100%)} to{transform:translateY(0)} }
        .sheet-handle { width: 38px; height: 4px; background: var(--mist); border-radius: 10px; margin: 0 auto 20px; }
        .sheet-title { font-family: 'Bricolage Grotesque', sans-serif; font-size: 18px; font-weight: 800; color: var(--text1); text-align: center; }
        .sheet-sub { font-size: 12px; color: var(--text3); text-align: center; margin-top: 4px; margin-bottom: 24px; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px; }
        .summary-box {
            border-radius: 14px; padding: 14px 12px; text-align: center;
        }
        .summary-box .sb-num { font-family: 'Bricolage Grotesque', sans-serif; font-size: 28px; font-weight: 900; }
        .summary-box .sb-label { font-size: 11px; font-weight: 700; margin-top: 2px; opacity: 0.8; }
        .sb-blue { background: linear-gradient(135deg,var(--deep),var(--mid)); color: #fff; }
        .sb-green { background: linear-gradient(135deg,#059669,#10B981); color: #fff; }
        .sb-amber { background: linear-gradient(135deg,#D97706,#FBBF24); color: #fff; }
        .sb-red { background: linear-gradient(135deg,#EF4444,#DC2626); color: #fff; }
        .sheet-actions { display: flex; gap: 10px; }

        /* ── BADGES ──────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.2px;
            border: 1px solid transparent;
        }
        .badge-blue { background: rgba(107, 163, 214, 0.12); color: #4A8BC4; border-color: rgba(107, 163, 214, 0.25); }
        .badge-green { background: rgba(16, 185, 129, 0.1); color: #059669; border-color: rgba(16, 185, 129, 0.2); }
        .badge-red { background: rgba(239, 68, 68, 0.1); color: #DC2626; border-color: rgba(239, 68, 68, 0.2); }
        .badge-amber { background: rgba(245, 158, 11, 0.1); color: #D97706; border-color: rgba(245, 158, 11, 0.2); }
        .badge-purple { background: rgba(168, 85, 247, 0.1); color: #7C3AED; border-color: rgba(168, 85, 247, 0.2); }
        .badge-gray { background: rgba(100, 116, 139, 0.1); color: #64748B; border-color: rgba(100, 116, 139, 0.2); }

        /* ── SPINNER ─────────────────────────────────── */
        .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; display: none; }
        @keyframes spin { to{transform:rotate(360deg)} }

        /* ── WARNING OVERLAY ─────────────────────────── */
        .warn-overlay { position: fixed; inset: 0; background: rgba(24,33,61,0.9); z-index: 99999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(10px); }
        .warn-overlay.show { display: flex; }
        .warn-box { background: var(--white); border-radius: 20px; padding: 36px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.34,1.56,0.64,1); }
        @keyframes popIn { from{transform:scale(0.85);opacity:0} to{transform:scale(1);opacity:1} }

        /* ── SCROLLBAR ─────────────────────────────── */
        .main-scroll::-webkit-scrollbar { width: 4px; }
        .main-scroll::-webkit-scrollbar-track { background: transparent; }
        .main-scroll::-webkit-scrollbar-thumb { background: var(--mist); border-radius: 10px; }
    </style>
</head>
<body>

@php
    $questions = $quiz['questions'] ?? [];
    $totalQ    = count($questions);
    $quizId    = $quiz['quiz_id'] ?? 0;
    $studentName = $student['name'] ?? 'Student';
    $studentId   = $student['id'] ?? 0;
    $initials    = strtoupper(substr($studentName, 0, 1));
@endphp

<!-- ══ APP BAR ══════════════════════════════════════════ -->
<div class="app-bar">
    <div class="app-bar-left">
        <div>
            <div class="quiz-name">{{ $quiz['quiz_name'] ?? 'Exam' }}</div>
        </div>
        <span class="quiz-code-tag">{{ $quiz['quiz_code'] ?? '' }}</span>
    </div>
    <div class="timer-wrap" id="timerBox">
        <i class="fas fa-clock timer-icon"></i>
        <span id="countdown">--:--</span>
    </div>
</div>

<!-- ══ PROGRESS BAR ════════════════════════════════════ -->
<div class="prog-bar-wrap">
    <div class="prog-bar-fill" id="progressBar" style="width:0%"></div>
</div>

<!-- ══ CANDIDATE STRIP ══════════════════════════════════ -->
<div class="candidate-strip">
    <div class="candidate-left">
        <div class="cand-avatar">{{ $initials }}</div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <div class="cand-name">{{ $studentName }}</div>
                <span class="secure-badge">SECURE</span>
                <button type="button" id="viewLiveProctorBtn" onclick="toggleProctorModal(true)" style="background:rgba(61,82,160,0.1); border:1px solid rgba(61,82,160,0.25); color:var(--deep); font-size:10px; font-weight:800; padding:4px 10px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:5px; margin-left:6px; font-family:'Bricolage Grotesque',sans-serif; text-transform:uppercase; letter-spacing:0.5px;">
                    <i class="fas fa-chart-line"></i> Live Behavior
                </button>
            </div>
            <div class="cand-sub">ID: {{ $studentId }} &bull; Code: {{ $quiz['quiz_code'] ?? '' }}</div>
        </div>
    </div>
    <div class="q-counter-badge" id="qCountBadge">Q: 1 / {{ $totalQ }}</div>
</div>

<!-- ══ QUESTION MAP STRIP ════════════════════════════════ -->
<div class="q-map-strip" id="qMapStrip">
    @for($i = 0; $i < $totalQ; $i++)
        <button class="q-map-btn" id="qmap_{{ $i }}" onclick="jumpTo({{ $i }})">{{ $i + 1 }}</button>
    @endfor
    <div style="flex-shrink:0;padding-left:10px;border-left:1px solid var(--border);margin-left:4px;">
        <div class="map-legend">
            <span><span class="legend-dot" style="background:var(--deep);"></span>Current</span>
            <span><span class="legend-dot" style="background:#059669;"></span>Saved</span>
            <span><span class="legend-dot" style="background:var(--amber);"></span>Flagged</span>
        </div>
    </div>
</div>

<!-- ══ SCROLLABLE QUESTION AREA ═════════════════════════ -->
<div class="main-scroll" id="mainScroll">
    @if($totalQ === 0)
        <div style="text-align:center;padding:60px 16px;">
            <i class="fas fa-exclamation-circle" style="font-size:40px;color:var(--red);margin-bottom:16px;display:block;"></i>
            <h3 style="font-family:'Bricolage Grotesque';font-size:20px;color:var(--text1);">No Questions Found</h3>
            <p style="margin-top:8px;color:var(--text3);">This quiz has no questions available.</p>
        </div>
    @else
        @foreach($questions as $i => $q)
        @php
            $type = $q['type'] ?? 'mcq';
            $qId  = $q['question_id'] ?? $q['id'] ?? ($i + 1);
        @endphp
        <div class="q-panel" id="qcard_{{ $i }}" style="display:{{ $i === 0 ? 'block' : 'none' }};">
            <div class="q-panel-header">
                <div class="q-num">Question {{ $i + 1 }}</div>
                <span class="q-type-tag {{ $type }}">
                    @if($type === 'mcq') Multiple Choice
                    @elseif($type === 'short') Short Answer
                    @else Fill in Blank
                    @endif
                </span>
            </div>
            <div class="q-body">
                <div class="q-text">{{ $q['question'] ?? '' }}</div>

                @if($type === 'mcq')
                    <div class="options-list">
                    @foreach(['a','b','c','d'] as $opt)
                        @if(!empty($q['option_'.$opt]))
                        <div class="opt" id="opt_{{ $qId }}_{{ $opt }}"
                             onclick="selectMCQ({{ $qId }}, '{{ strtoupper($opt) }}', '{{ $opt }}', {{ $i }}, this)">
                            <div class="opt-radio"></div>
                            <span class="opt-letter">{{ strtoupper($opt) }}</span>
                            <span class="opt-text">{{ $q['option_'.$opt] }}</span>
                        </div>
                        @endif
                    @endforeach
                    </div>
                    <input type="hidden" id="ans_{{ $qId }}" data-qid="{{ $qId }}" data-qidx="{{ $i }}" class="answer-field">

                @elseif($type === 'short')
                    <textarea class="text-input answer-field"
                              id="ans_{{ $qId }}" data-qid="{{ $qId }}" data-qidx="{{ $i }}"
                              placeholder="Type your answer here…"
                              oninput="onTextInput({{ $i }})"></textarea>

                @elseif($type === 'fill')
                    <input type="text" class="text-input answer-field"
                           id="ans_{{ $qId }}" data-qid="{{ $qId }}" data-qidx="{{ $i }}"
                           placeholder="Fill in the blank…"
                           oninput="onTextInput({{ $i }})">
                @endif
            </div>
        </div>
        @endforeach
    @endif
</div>

<!-- ══ BOTTOM ACTION DOCK ════════════════════════════════ -->
@if($totalQ > 0)
<div class="action-dock">
    <div class="dock-row1">
        <button class="btn-prev" id="btnPrev" onclick="prevQ()" disabled title="Previous">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="btn-flag" id="btnFlag" onclick="toggleFlag()">
            <i class="fas fa-flag"></i> FLAG
        </button>
        <button class="btn-save-next" id="btnSaveNext" onclick="saveAndNext()">
            <i class="fas fa-save"></i> <span id="saveNextLabel">SAVE &amp; NEXT</span>
        </button>
        <button class="btn-next-skip" id="btnNextSkip" onclick="nextQ()" title="Next without saving">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    <div class="dock-row2">
        <button class="btn-submit" id="submitBtn" onclick="confirmSubmit()">
            <i class="fas fa-check-circle" id="submitIcon"></i>
            <div class="spinner" id="submitSpinner"></div>
            SUBMIT QUIZ / EXAM
        </button>
    </div>
</div>
@endif

<!-- ══ TAB SWITCH WARNING ════════════════════════════════ -->
<div class="warn-overlay" id="warningOverlay">
    <div class="warn-box">
        <div style="font-size:50px;color:var(--red);margin-bottom:16px;"><i class="fas fa-shield-exclamation"></i></div>
        <h2 style="font-family:'Bricolage Grotesque';font-size:20px;color:var(--text1);margin-bottom:10px;">Quiz Locked!</h2>
        <p style="color:var(--text3);font-size:13.5px;line-height:1.6;margin-bottom:24px;">
            You switched tabs or minimized the window. As per quiz policy, your attempt has been <strong style="color:var(--red);">locked</strong>.<br><br>
            Please request your teacher to unlock your attempt.
        </p>
        <a href="{{ route('student.dashboard') }}"
           style="display:block;padding:12px;background:var(--deep);color:#fff;border-radius:12px;font-family:'Bricolage Grotesque';font-weight:800;text-decoration:none;text-align:center;">
            Return to Dashboard
        </a>
    </div>
</div>

<!-- ══ CONFIRM SUBMIT SHEET ══════════════════════════════ -->
<div class="overlay" id="confirmOverlay">
    <div class="modal-sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-title">Exam Submission Summary</div>
        <div class="sheet-sub">Please review your attempt details before final submission</div>
        <div class="summary-grid">
            <div class="summary-box sb-blue">
                <div class="sb-num">{{ $totalQ }}</div>
                <div class="sb-label">Total</div>
            </div>
            <div class="summary-box sb-green">
                <div class="sb-num" id="sheetSaved">0</div>
                <div class="sb-label">Saved</div>
            </div>
            <div class="summary-box sb-amber">
                <div class="sb-num" id="sheetFlagged">0</div>
                <div class="sb-label">Flagged</div>
            </div>
            <div class="summary-box sb-red">
                <div class="sb-num" id="sheetUnsaved">{{ $totalQ }}</div>
                <div class="sb-label">Unsaved</div>
            </div>
        </div>
        <div class="sheet-actions">
            <button onclick="document.getElementById('confirmOverlay').classList.remove('show')"
                    style="flex:1;padding:13px;border:1.5px solid var(--border);background:var(--snow);border-radius:12px;font-weight:700;font-family:'DM Sans';cursor:pointer;color:var(--text2);">
                Cancel
            </button>
            <button onclick="doSubmit()"
                    style="flex:2;padding:13px;background:linear-gradient(135deg,var(--red),#DC2626);border:none;border-radius:12px;font-weight:900;font-family:'Bricolage Grotesque';cursor:pointer;color:#fff;letter-spacing:0.5px;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fas fa-paper-plane" id="sheetSubmitIcon"></i>
                <div class="spinner" id="sheetSpinner"></div>
                Confirm Submit
            </button>
        </div>
    </div>
</div>

<!-- ══ LIVE PROCTOR BEHAVIOR SHEET ══════════════════════ -->
<div class="overlay" id="proctorModalOverlay">
    <div class="modal-sheet" style="max-width: 480px; border-radius: 24px 24px 0 0;">
        <div class="sheet-handle"></div>
        <div class="sheet-title" style="display:flex; align-items:center; justify-content:center; gap:8px;"><i class="fas fa-shield-halved" style="color:var(--deep);"></i> Live Session Behavior</div>
        <div class="sheet-sub">Real-time AI proctoring detection flags and status</div>
        
        <div style="padding: 10px 0;">
            <!-- Status Card -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:16px; margin-bottom:15px;">
                <div style="font-weight:700; font-size:13.5px; color:var(--text1);">AI Proctor Status</div>
                <span class="badge badge-gray" id="liveProctorStatus" style="font-size:11px; padding:4px 12px; font-weight:800; border-radius:8px;">Calibrating...</span>
            </div>
            
            <!-- Summary stats -->
            <div class="summary-grid" style="grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                <div class="summary-box sb-blue" style="padding:12px;">
                    <div class="sb-num" id="liveProctorRisk" style="font-size:26px;">0%</div>
                    <div class="sb-label" style="font-size:10px;">Cheating Risk</div>
                </div>
                <div class="summary-box sb-red" style="padding:12px;">
                    <div class="sb-num" id="liveProctorAlarms" style="font-size:26px;">0</div>
                    <div class="sb-label" style="font-size:10px;">Alarms Triggered</div>
                </div>
            </div>
            
            <!-- Indicators -->
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; font-size:12.5px;">
                    <span style="color:var(--text2); font-weight:600; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-eye" style="width:16px; color:var(--deep);"></i> Eye Movement (Gaze)
                    </span>
                    <strong style="color:var(--text1); font-size:14px;" id="liveGazeCount">0</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; font-size:12.5px;">
                    <span style="color:var(--text2); font-weight:600; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-arrows-up-down-left-right" style="width:16px; color:var(--deep);"></i> Head Movements
                    </span>
                    <strong style="color:var(--text1); font-size:14px;" id="liveHeadCount">0</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; font-size:12.5px;">
                    <span style="color:var(--text2); font-weight:600; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-user-slash" style="width:16px; color:#EF4444;"></i> No Face Detected
                    </span>
                    <strong style="color:var(--text1); font-size:14px;" id="liveNoFaceCount">0</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; font-size:12.5px;">
                    <span style="color:var(--text2); font-weight:600; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-users" style="width:16px; color:#EF4444;"></i> Multiple Faces Detected
                    </span>
                    <strong style="color:var(--text1); font-size:14px;" id="liveMultiFaceCount">0</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; font-size:12.5px;">
                    <span style="color:var(--text2); font-weight:600; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-eye-slash" style="color:#10B981;"></i> Blinks Registered
                    </span>
                    <strong style="color:var(--text1); font-size:14px;" id="liveBlinkCount">0</strong>
                </div>
            </div>
        </div>
        
        <div class="sheet-actions">
            <button onclick="toggleProctorModal(false)"
                    style="width:100%; padding:12px; background:var(--deep); border:none; border-radius:12px; font-weight:800; font-family:'Bricolage Grotesque'; cursor:pointer; color:#fff;">
                Close Behavior View
            </button>
        </div>
    </div>
</div>

<script>
function toggleProctorModal(show) {
    const el = document.getElementById('proctorModalOverlay');
    if (show) {
        el.classList.add('show');
    } else {
        el.classList.remove('show');
    }
}

const QUIZ_ID    = {{ $quizId }};
const STUDENT_ID = {{ $studentId }};
const TOTAL_Q    = {{ $totalQ }};
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;

// ── NAVIGATION STATE ────────────────────────────────────
let currentQ   = 0;
const flagged  = new Set();
const saved    = new Set(); // indices of saved (answered) questions

function jumpTo(index) {
    document.getElementById('qcard_' + currentQ)?.style.setProperty('display','none');
    document.getElementById('qcard_' + index)?.style.setProperty('display','block');
    currentQ = index;

    // Scroll to top of question area
    document.getElementById('mainScroll').scrollTo({ top: 0, behavior: 'smooth' });

    updateAllUI();
    saveAnswersToStorage();
}

function prevQ() { if(currentQ > 0) jumpTo(currentQ - 1); }
function nextQ() { if(currentQ < TOTAL_Q - 1) jumpTo(currentQ + 1); }

function saveAndNext() {
    const qcard = document.getElementById('qcard_' + currentQ);
    if(!qcard) return;

    const field = qcard.querySelector('.answer-field');
    if(!field || field.value.trim() === '') {
        // Flash the question
        qcard.style.borderColor = 'var(--red)';
        setTimeout(() => qcard.style.borderColor = '', 500);
        showToast('Please select or type an answer to save!', true);
        return;
    }

    saved.add(currentQ);
    updateQMap(currentQ);
    updateProgressBar();
    saveAnswersToStorage();

    showToast('Answer saved!', false);
    if(currentQ < TOTAL_Q - 1) jumpTo(currentQ + 1);
}

function toggleFlag() {
    if(flagged.has(currentQ)) {
        flagged.delete(currentQ);
    } else {
        flagged.add(currentQ);
    }
    updateAllUI();
    saveAnswersToStorage();
}

// ── MCQ SELECT ─────────────────────────────────────────
function selectMCQ(qId, letter, optKey, qidx, el) {
    const qcard = document.getElementById('qcard_' + qidx);
    qcard.querySelectorAll('.opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('ans_' + qId).value = letter;
    saveAnswersToStorage();
    // Auto-mark as saved when MCQ is selected
    saved.add(qidx);
    updateQMap(qidx);
    updateProgressBar();
}

function onTextInput(qidx) {
    const qcard = document.getElementById('qcard_' + qidx);
    const field = qcard?.querySelector('.answer-field');
    if(field && field.value.trim() !== '') {
        saved.add(qidx);
    } else {
        saved.delete(qidx);
    }
    updateQMap(qidx);
    updateProgressBar();
    saveAnswersToStorage();
}

// ── UI UPDATE FUNCTIONS ─────────────────────────────────
function updateAllUI() {
    updateQMap(currentQ);
    updateBtnStates();
    updateCounterBadge();
    updateProgressBar();
    updateFlagBtn();
    updateSaveNextLabel();
}

function updateQMap(idx) {
    for(let i = 0; i < TOTAL_Q; i++) {
        const btn = document.getElementById('qmap_' + i);
        if(!btn) continue;
        btn.className = 'q-map-btn';
        if(i === currentQ) btn.classList.add('current');
        else if(flagged.has(i)) btn.classList.add('flagged');
        else if(saved.has(i)) btn.classList.add('answered');
    }
    // Scroll q map to current button
    const curBtn = document.getElementById('qmap_' + currentQ);
    if(curBtn) curBtn.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
}

function updateBtnStates() {
    document.getElementById('btnPrev').disabled    = (currentQ === 0);
    document.getElementById('btnNextSkip').disabled = (currentQ === TOTAL_Q - 1);
}

function updateCounterBadge() {
    document.getElementById('qCountBadge').textContent = `Q: ${currentQ + 1} / ${TOTAL_Q}`;
}

function updateProgressBar() {
    const pct = TOTAL_Q > 0 ? (saved.size / TOTAL_Q) * 100 : 0;
    document.getElementById('progressBar').style.width = pct + '%';
}

function updateFlagBtn() {
    const btn = document.getElementById('btnFlag');
    if(flagged.has(currentQ)) {
        btn.classList.add('flagged');
        btn.innerHTML = '<i class="fas fa-flag"></i> FLAGGED';
    } else {
        btn.classList.remove('flagged');
        btn.innerHTML = '<i class="fas fa-flag"></i> FLAG';
    }
}

function updateSaveNextLabel() {
    const lbl = document.getElementById('saveNextLabel');
    lbl.textContent = (currentQ < TOTAL_Q - 1) ? 'SAVE & NEXT' : 'SAVE ANSWER';
}

// ── COLLECT & STORE ANSWERS ─────────────────────────────
function collectAnswers() {
    const ans = {};
    document.querySelectorAll('.answer-field').forEach(el => {
        if(el.value.trim()) ans[el.dataset.qid] = el.value.trim();
    });
    return ans;
}

const STORAGE_KEY = `quiz_answers_${QUIZ_ID}_${STUDENT_ID}`;

function saveAnswersToStorage() {
    try {
        const state = {
            answers: collectAnswers(),
            saved: [...saved],
            flagged: [...flagged],
            currentQ: currentQ,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch(e) {}
}

function restoreFromStorage() {
    try {
        const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        if(!raw) return;

        // Restore answers
        const answers = raw.answers || {};
        Object.entries(answers).forEach(([qid, val]) => {
            const field = document.getElementById('ans_' + qid);
            if(!field) return;
            field.value = val;
            // Restore MCQ highlight
            const optKey = val.toLowerCase();
            const optEl = document.getElementById(`opt_${qid}_${optKey}`);
            if(optEl) optEl.classList.add('selected');
        });

        // Restore saved set
        (raw.saved || []).forEach(i => saved.add(i));
        // Restore flagged set
        (raw.flagged || []).forEach(i => flagged.add(i));

        if(Object.keys(answers).length > 0) {
            showToast('Previous answers restored!', false);
        }
    } catch(e) {}
}

function clearStorage() {
    try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
}

// ── TIMER ──────────────────────────────────────────────
const targetEndTime = new Date("{{ $targetEndTimeIso }}").getTime();
let timerInterval;

function updateTimer() {
    const diff = targetEndTime - Date.now();
    if(diff <= 0) {
        clearInterval(timerInterval);
        document.getElementById('countdown').textContent = '00:00';
        alert('Time is up! Submitting your exam…');
        doSubmit();
        return;
    }
    const sec = Math.floor(diff / 1000);
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;

    let timeStr = '';
    if (h > 0) {
        timeStr = h.toString().padStart(2, '0') + ':' + 
                  m.toString().padStart(2, '0') + ':' + 
                  s.toString().padStart(2, '0');
    } else {
        timeStr = m.toString().padStart(2, '0') + ':' + 
                  s.toString().padStart(2, '0');
    }

    document.getElementById('countdown').textContent = timeStr;
    if(sec <= 300) document.getElementById('timerBox').classList.add('warning');
}
if(targetEndTime) {
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
}

// ── CONFIRM & SUBMIT ────────────────────────────────────
function confirmSubmit() {
    const answered = saved.size;
    document.getElementById('sheetSaved').textContent    = answered;
    document.getElementById('sheetFlagged').textContent  = flagged.size;
    document.getElementById('sheetUnsaved').textContent  = TOTAL_Q - answered;
    document.getElementById('confirmOverlay').classList.add('show');
}

async function doSubmit() {
    document.getElementById('confirmOverlay').classList.remove('show');
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitIcon').style.display = 'none';
    document.getElementById('submitSpinner').style.display = 'block';
    clearInterval(timerInterval);

    try {
        const res = await fetch('{{ route("student.quiz.submit") }}', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify({ quiz_id: QUIZ_ID, student_id: STUDENT_ID, answers: collectAnswers() })
        });
        const data = await res.json();
        const ok = data.status === true || data.status === 1 || res.ok;

        await fetch('{{ route("student.quiz.mark-submitted") }}', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF },
            body: JSON.stringify({ quiz_id: QUIZ_ID })
        }).catch(()=>{});

        if(ok) {
            clearStorage();
            window.location.href = '{{ route("student.result.detail", ":id") }}'.replace(':id', QUIZ_ID);
        } else {
            alert(data.message || 'Submission failed. Please try again.');
            btn.disabled = false;
            document.getElementById('submitIcon').style.display = 'block';
            document.getElementById('submitSpinner').style.display = 'none';
        }
    } catch(err) {
        alert('Network error. Please check your connection.');
        btn.disabled = false;
        document.getElementById('submitIcon').style.display = 'block';
        document.getElementById('submitSpinner').style.display = 'none';
    }
}

// ── HEARTBEAT (30s) ────────────────────────────────────
setInterval(() => {
    fetch('{{ route("student.quiz.heartbeat") }}', {
        method:'POST',
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({ quiz_id: QUIZ_ID })
    }).catch(()=>{});
}, 30000);

// ── ANTI-CHEAT ─────────────────────────────────────────
let tabSwitchTriggered = false, isUnloading = false, antiCheatReady = false;

function triggerTabSwitch() {
    if(tabSwitchTriggered || !antiCheatReady) return;
    tabSwitchTriggered = true;
    clearInterval(timerInterval);
    saveAnswersToStorage();
    fetch('{{ route("student.quiz.tab-switch") }}', {
        method:'POST',
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF },
        body: JSON.stringify({ quiz_id: QUIZ_ID })
    }).finally(() => document.getElementById('warningOverlay').classList.add('show'));
}

window.addEventListener('beforeunload', () => { isUnloading = true; saveAnswersToStorage(); });

// Detect tab switching and window minimization using Visibility API (highly reliable)
document.addEventListener('visibilitychange', () => {
    if (document.hidden && !isUnloading && !tabSwitchTriggered && antiCheatReady) {
        triggerTabSwitch();
    }
});

// Detect when student switches to another application/window, with a short delay to ignore false positives (like scrollbar clicks)
window.addEventListener('blur', () => {
    setTimeout(() => {
        if (!document.hasFocus() && !isUnloading && !tabSwitchTriggered && antiCheatReady) {
            triggerTabSwitch();
        }
    }, 250);
});

function checkWindowSize() {
    if(isUnloading || !antiCheatReady || window.screen.width <= 768) return;
    if(window.innerWidth < window.screen.width * 0.82 || window.innerHeight < window.screen.availHeight * 0.82) {
        if(!tabSwitchTriggered) triggerTabSwitch();
    }
}
window.addEventListener('resize', checkWindowSize);

// Grace period: wait 3 seconds before activating anti-cheat.
// This prevents false positives from browser focus changes during page redirect/load.
setTimeout(() => {
    antiCheatReady = true;
    checkWindowSize(); // run one size check after grace period
}, 3000);


// ── COPY / PASTE / RIGHT-CLICK BLOCK ───────────────────────────
// Prevent students from copying questions or pasting answers during exam.

// Block copy
document.addEventListener('copy', (e) => {
    e.preventDefault();
    showToast('⛔ Copying is not allowed during the exam.', true);
});

// Block cut
document.addEventListener('cut', (e) => {
    e.preventDefault();
    showToast('⛔ Cutting text is not allowed during the exam.', true);
});

// Block paste into any input/textarea
document.addEventListener('paste', (e) => {
    // Only block if target is an input or textarea (answer fields)
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        e.preventDefault();
        showToast('⛔ Pasting is not allowed during the exam.', true);
    }
});

// Block right-click context menu
document.addEventListener('contextmenu', (e) => {
    e.preventDefault();
    showToast('⛔ Right-click is disabled during the exam.', true);
});

// Block keyboard shortcuts: Ctrl+C, Ctrl+X, Ctrl+V, Ctrl+A (select all)
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
        if (['c', 'x', 'v', 'a'].includes(e.key.toLowerCase())) {
            // Allow Ctrl+A only inside text areas for answering
            if (e.key.toLowerCase() === 'a' && (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT')) {
                return; // allow select-all inside answer box
            }
            e.preventDefault();
            showToast('⛔ Keyboard shortcuts are disabled during the exam.', true);
        }
    }
});


// ── TOAST ──────────────────────────────────────────────
function showToast(msg, isError = false) {
    let t = document.getElementById('__toast__');
    if(!t) {
        t = document.createElement('div');
        t.id = '__toast__';
        t.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:12px;font-size:13px;font-weight:700;z-index:999999;box-shadow:0 6px 20px rgba(0,0,0,.15);transition:opacity .3s;white-space:nowrap;font-family:Bricolage Grotesque,sans-serif;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = isError ? '#EF4444' : '#059669';
    t.style.color = '#fff';
    t.style.opacity = '1';
    clearTimeout(t._h);
    t._h = setTimeout(() => t.style.opacity = '0', 2500);
}

// ── INIT ───────────────────────────────────────────────
restoreFromStorage();
if(TOTAL_Q > 0) {
    jumpTo(0);
}
setInterval(saveAnswersToStorage, 15000);
</script>

<!-- AI Proctoring Integration -->
<audio id="proctor-alarm-audio" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-120.wav" preload="auto"></audio>
<script>
    window.PROCTOR_QUIZ_CODE   = "{{ $quiz['quiz_code'] ?? '' }}";
    window.PROCTOR_QUIZ_ID     = "{{ $quiz['quiz_id'] ?? 0 }}";
    window.PROCTOR_COURSE_NAME = "{{ $quiz['course_name'] ?? 'Unknown Course' }}";
    window.PROCTOR_QUIZ_DATE   = "{{ $quiz['quiz_date'] ?? '' }}";
    window.PROCTOR_START_TIME  = "{{ $quiz['start_time'] ?? '' }}";
    window.PROCTOR_END_TIME    = "{{ $quiz['end_time'] ?? '' }}";

    window.PROCTOR_START_URL   = "{{ route('student.proctor.start') }}";
    window.PROCTOR_FRAME_URL   = "{{ route('student.proctor.frame') }}";
    window.PROCTOR_METRICS_URL = "{{ route('student.proctor.metrics') }}";
    window.PROCTOR_STOP_URL    = "{{ route('student.proctor.stop') }}";
</script>
<script src="{{ asset('js/proctor.js') }}"></script>
</body>
</html>
