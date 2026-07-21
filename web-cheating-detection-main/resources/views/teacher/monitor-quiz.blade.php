@extends('layouts.app')
@section('title','Quiz Monitor')
@section('page-title','Live Monitor')
@section('page-subtitle') Quiz: {{ $quizName }} &middot; Code: {{ $code }} @endsection

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@section('topbar-actions')
<a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
<button class="btn btn-primary" id="refreshBtn" onclick="fetchAttempts(true)">
    <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh Now
</button>
@endsection

@push('styles')
<style>
/* ── STATUS DOTS ─────────────────────────────────────── */
.status-dot{width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:5px;flex-shrink:0;}
.dot-active   {background:#10B981;animation:pulse-g 1.5s infinite;}
.dot-abandoned{background:#EF4444;}
.dot-submitted{background:#059669;}
.dot-unlocked {background:#F59E0B;animation:pulse-a 2s infinite;}
.dot-not-started{background:#94A3B8;}
@keyframes pulse-g{0%,100%{box-shadow:0 0 0 3px rgba(16,185,129,.25)}50%{box-shadow:0 0 0 7px rgba(16,185,129,.07)}}
@keyframes pulse-a{0%,100%{box-shadow:0 0 0 3px rgba(245,158,11,.25)}50%{box-shadow:0 0 0 7px rgba(245,158,11,.07)}}

/* ── AUTO-REFRESH BAR ───────────────────────────────── */
.refresh-bar{
    display:flex;align-items:center;gap:10px;
    padding:9px 20px;border-bottom:1px solid var(--border);
    background:var(--surface-alt);font-size:12px;
}
.refresh-bar .refresh-label{font-weight:600;color:var(--primary);}
.refresh-bar .refresh-label.paused{color:var(--text3);}
.toggle-switch{position:relative;width:38px;height:22px;flex-shrink:0;cursor:pointer;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-track{position:absolute;inset:0;background:#CBD5E1;border-radius:11px;transition:.25s;}
.toggle-switch input:checked~.toggle-track{background:var(--primary);}
.toggle-thumb{position:absolute;left:3px;top:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.18);}
.toggle-switch input:checked~.toggle-track~.toggle-thumb,
.toggle-switch input:checked+.toggle-track+.toggle-thumb{left:19px;}

/* ── COUNTDOWN RING ────────────────────────────────── */
.cring{width:20px;height:20px;flex-shrink:0;}
.cring svg{transform:rotate(-90deg);}
.cring circle{transition:stroke-dashoffset .9s linear;}

/* ── FILTER CHIPS ───────────────────────────────────── */
.filter-bar{display:flex;gap:8px;padding:12px 20px;border-bottom:1px solid var(--border);overflow-x:auto;flex-wrap:nowrap;}
.filter-chip{
    padding:4px 14px;border-radius:50px;font-size:10.5px;font-weight:700;
    letter-spacing:.3px;cursor:pointer;border:1.5px solid var(--border);
    background:var(--surface-alt);color:var(--text2);white-space:nowrap;transition:.15s;
}
.filter-chip:hover{border-color:var(--primary);color:var(--primary);}
.filter-chip.active-chip{background:var(--primary);border-color:var(--primary);color:#fff;}
.filter-chip.chip-active{background:#10B981;border-color:#10B981;color:#fff;}
.filter-chip.chip-submitted{background:#059669;border-color:#059669;color:#fff;}
.filter-chip.chip-abandoned{background:#EF4444;border-color:#EF4444;color:#fff;}
.filter-chip.chip-alerts{background:#EF4444;border-color:#EF4444;color:#fff;}

/* ── ATTEMPT CARD ───────────────────────────────────── */
.attempt-card{
    padding:14px 20px 14px 20px;border-bottom:1px solid var(--border);
    transition:.2s;
}
.attempt-card:hover{background:#F8FAFC;}
.attempt-card.has-alert{border-left:3px solid #EF4444;padding-left:17px;}
.attempt-card:last-child{border-bottom:none;}
.student-avatar{
    width:36px;height:36px;border-radius:9px;flex-shrink:0;
    background:linear-gradient(135deg,var(--primary),var(--secondary));
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:13px;font-weight:700;
}
.row2{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);flex-wrap:wrap;}
.integrity-chip{
    display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
    border-radius:7px;font-size:10.5px;font-weight:700;
}
.chip-ok{background:rgba(5,150,105,.08);color:#059669;border:1px solid rgba(5,150,105,.2);}
.chip-warn{background:rgba(239,68,68,.08);color:#EF4444;border:1px solid rgba(239,68,68,.2);}
.last-active{font-size:10.5px;color:var(--text3);display:flex;align-items:center;gap:4px;}
.unlock-section{display:flex;align-items:center;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);}
.btn-unlock{
    background:#059669;color:#fff;border:none;padding:5px 14px;border-radius:8px;
    font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:.15s;
}
.btn-unlock:hover{background:#047857;}
.btn-unlock:disabled{opacity:.55;cursor:not-allowed;}

/* ── EMPTY STATE ────────────────────────────────────── */
.empty-monitor{padding:48px;text-align:center;color:var(--text3);}
.empty-monitor i{font-size:28px;margin-bottom:12px;opacity:.35;}

.fade-in-card{animation:fadeInCard .3s ease;}
@keyframes fadeInCard{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}

/* ── PROCTORING MODAL ───────────────────────────────── */
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.6);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999999;
}
.overlay.show {
    display: flex;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@section('content')
{{-- STATS BAR --}}
<div class="stats-grid fade-in" id="statsBar">
    <div class="stat-card"><div class="stat-icon" style="background:linear-gradient(135deg,#10B981,#059669);"><i class="fas fa-circle-notch fa-spin" style="color:#fff;"></i></div><div class="stat-info"><div class="value" id="cnt-active">–</div><div class="label">Active</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:linear-gradient(135deg,#059669,#047857);"><i class="fas fa-check-double" style="color:#fff;"></i></div><div class="stat-info"><div class="value" id="cnt-submitted">–</div><div class="label">Submitted</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:linear-gradient(135deg,#EF4444,#DC2626);"><i class="fas fa-exclamation-triangle" style="color:#fff;"></i></div><div class="stat-info"><div class="value" id="cnt-alerts">–</div><div class="label">Alerts</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:linear-gradient(135deg,#94A3B8,#64748B);"><i class="fas fa-users" style="color:#fff;"></i></div><div class="stat-info"><div class="value" id="cnt-total">–</div><div class="label">Total</div></div></div>
</div>

<div class="card fade-in" style="padding:0;overflow:hidden;">
    {{-- AUTO-REFRESH BAR --}}
    <div class="refresh-bar">
        <div class="cring">
            <svg width="20" height="20" viewBox="0 0 20 20">
                <circle cx="10" cy="10" r="8" fill="none" stroke="#E2E8F0" stroke-width="2.5"/>
                <circle id="cringCircle" cx="10" cy="10" r="8" fill="none"
                    stroke="var(--primary)" stroke-width="2.5"
                    stroke-dasharray="50.3" stroke-dashoffset="0"/>
            </svg>
        </div>
        <span class="refresh-label" id="refreshLabel">Auto-refreshing in 8s</span>
        <span style="flex:1;"></span>
        <span style="font-size:12px;color:var(--text3);font-weight:500;">Auto Update</span>
        <label class="toggle-switch" title="Toggle auto-refresh">
            <input type="checkbox" id="autoToggle" checked onchange="toggleAuto(this.checked)">
            <div class="toggle-track"></div>
            <div class="toggle-thumb"></div>
        </label>
        <span id="lastUpdated" style="font-size:11px;color:var(--text3);margin-left:6px;"></span>
    </div>

    {{-- FILTER CHIPS --}}
    <div class="filter-bar" id="filterBar">
        <span class="filter-chip active-chip" data-filter="all"    onclick="setFilter('all',this)">ALL</span>
        <span class="filter-chip"             data-filter="active"    onclick="setFilter('active',this)">ACTIVE</span>
        <span class="filter-chip"             data-filter="submitted" onclick="setFilter('submitted',this)">SUBMITTED</span>
        <span class="filter-chip"             data-filter="abandoned" onclick="setFilter('abandoned',this)">ABANDONED</span>
        <span class="filter-chip"             data-filter="alerts"    onclick="setFilter('alerts',this)">⚠ ALERTS</span>
    </div>

    {{-- ATTEMPTS LIST --}}
    <div id="attemptsContainer"></div>
</div>

<!-- ══ LIVE STUDENT BEHAVIOR MODAL (TEACHER VIEW) ══════════════════════ -->
<div class="overlay" id="teacherProctorModalOverlay">
    <div class="modal-sheet" style="background:#fff; border-radius:24px; max-width:480px; width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; border:1px solid rgba(226,232,240,0.8); display:flex; flex-direction:column; animation: modalSlideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <!-- Modal Header -->
        <div style="background:linear-gradient(135deg,#3D52A0,#7091E6); padding:24px; color:#fff; position:relative;">
            <button onclick="closeTeacherProctorModal()" style="position:absolute; right:20px; top:20px; background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:16px; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">×</button>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <i class="fas fa-satellite-dish" style="font-size:20px; color:#A3F9B5; animation: pulse-g 1.5s infinite;"></i>
                <span style="font-family:'Bricolage Grotesque'; font-size:11px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:rgba(255,255,255,0.9);">Live AI Proctoring</span>
            </div>
            <h3 id="teacherModalStudentName" style="font-family:'Bricolage Grotesque'; font-size:20px; font-weight:800; margin:0; line-height:1.2;">Student Name</h3>
            <p id="teacherModalStudentId" style="font-family:'DM Sans'; font-size:12px; color:rgba(255,255,255,0.75); margin:4px 0 0 0;">ID: student-id</p>
        </div>

        <!-- Modal Body -->
        <div style="padding:24px; overflow-y:auto; max-height:calc(85vh - 120px);">
            <!-- Status Card -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:16px; margin-bottom:20px;">
                <div>
                    <div style="font-size:11px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:0.5px;">Current Status</div>
                    <div style="font-size:14px; font-weight:700; color:var(--text1); margin-top:2px;">Proctoring Session</div>
                </div>
                <span class="badge" id="teacherModalStatus" style="font-size:12px; padding:6px 14px; font-weight:800; border-radius:10px;">Active</span>
            </div>

            <!-- Stats Grid -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                <div style="background:linear-gradient(135deg,#3D52A0,#7091E6); padding:20px; border-radius:20px; color:#fff; box-shadow:0 10px 20px rgba(61,82,160,0.15); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                    <div id="teacherModalRisk" style="font-family:'Bricolage Grotesque'; font-size:32px; font-weight:900; line-height:1;">0%</div>
                    <div style="font-family:'DM Sans'; font-size:11px; font-weight:700; color:rgba(255,255,255,0.8); margin-top:6px; text-transform:uppercase; letter-spacing:0.5px;">Cheating Risk</div>
                </div>
                <div style="background:linear-gradient(135deg,#EF4444,#DC2626); padding:20px; border-radius:20px; color:#fff; box-shadow:0 10px 20px rgba(239,68,68,0.15); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                    <div id="teacherModalAlarms" style="font-family:'Bricolage Grotesque'; font-size:32px; font-weight:900; line-height:1;">0</div>
                    <div style="font-family:'DM Sans'; font-size:11px; font-weight:700; color:rgba(255,255,255,0.8); margin-top:6px; text-transform:uppercase; letter-spacing:0.5px;">Total Violations</div>
                </div>
            </div>

            <!-- Detailed Indicators -->
            <div style="font-family:'Bricolage Grotesque'; font-size:13px; font-weight:800; color:var(--text1); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.5px;">AI Detector Metrics</div>
            
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px;">
                    <span style="font-family:'DM Sans'; font-size:13.5px; color:var(--text2); font-weight:600; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-eye" style="color:#3D52A0; font-size:15px; width:20px; text-align:center;"></i> Gaze Away Count
                    </span>
                    <strong style="font-family:'Bricolage Grotesque'; font-size:15px; color:var(--text1);" id="teacherModalGaze">0</strong>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px;">
                    <span style="font-family:'DM Sans'; font-size:13.5px; color:var(--text2); font-weight:600; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-arrows-up-down-left-right" style="color:#3D52A0; font-size:15px; width:20px; text-align:center;"></i> Head Movements
                    </span>
                    <strong style="font-family:'Bricolage Grotesque'; font-size:15px; color:var(--text1);" id="teacherModalHead">0</strong>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px;">
                    <span style="font-family:'DM Sans'; font-size:13.5px; color:var(--text2); font-weight:600; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-user-slash" style="color:#EF4444; font-size:15px; width:20px; text-align:center;"></i> No Face Visible
                    </span>
                    <strong style="font-family:'Bricolage Grotesque'; font-size:15px; color:var(--text1);" id="teacherModalNoFace">0</strong>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px;">
                    <span style="font-family:'DM Sans'; font-size:13.5px; color:var(--text2); font-weight:600; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-users" style="color:#EF4444; font-size:15px; width:20px; text-align:center;"></i> Multiple Persons
                    </span>
                    <strong style="font-family:'Bricolage Grotesque'; font-size:15px; color:var(--text1);" id="teacherModalMultiFace">0</strong>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:14px;">
                    <span style="font-family:'DM Sans'; font-size:13.5px; color:var(--text2); font-weight:600; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-eye-slash" style="color:#10B981; font-size:15px; width:20px; text-align:center;"></i> Blinks Registered
                    </span>
                    <strong style="font-family:'Bricolage Grotesque'; font-size:15px; color:var(--text1);" id="teacherModalBlink">0</strong>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding:16px 24px; background:#F8FAFC; border-top:1px solid #E2E8F0; display:flex; justify-content:flex-end;">
            <button onclick="closeTeacherProctorModal()" style="background:#3D52A0; color:#fff; border:none; border-radius:12px; padding:12px 24px; font-family:'Bricolage Grotesque'; font-size:14px; font-weight:800; cursor:pointer; width:100%; transition:0.2s;" onmouseover="this.style.background='#2d3f80'" onmouseout="this.style.background='#3D52A0'">
                Dismiss Monitor
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const POLL_CODE  = '{{ $code }}';
const POLL_URL   = '{{ route("teacher.quiz.attempts-json", $code) }}';
const UNLOCK_URL = '{{ route("teacher.quiz.unlock") }}';
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const POLL_SECS  = 5; // 5-Second Live API Polling
const CIRC       = 50.3; // circumference of r=8 circle

let autoOn       = true;
let secsLeft     = POLL_SECS;
let pollTimer    = null;
let cdTimer      = null;
let allAttempts  = [];
let currentFilter= 'all';

// ── HELPERS ──────────────────────────────────────────
function toInt(v) {
    if (v == null) return 0;
    if (typeof v === 'number') return Math.floor(v);
    return parseInt(v, 10) || 0;
}

function resolveStatus(a) {
    const status = (a.status || '').toLowerCase();
    if (status === 'submitted')  return 'submitted';
    if (status === 'abandoned')  return 'abandoned';
    if (status === 'started' || status === 'not_started') {
        if (a.is_active) return 'active';
        if (a.allowed_reentry == 1 || a.allowed_reentry === true) return 'unlocked';
        return 'not-started';
    }
    return 'not-started';
}

function statusLabel(sc) {
    return {active:'Active',unlocked:'Unlocked',submitted:'Submitted',abandoned:'Abandoned','not-started':'Not Started'}[sc] ?? sc;
}
function statusBadgeClass(sc) {
    return {active:'badge-green',unlocked:'badge-green',submitted:'badge-purple',abandoned:'badge-red','not-started':'badge-gray'}[sc] ?? 'badge-gray';
}
function dotClass(sc) {
    return `dot-${sc}`;
}

function formatLastActive(ts) {
    if (!ts) return 'N/A';
    try {
        const dt   = new Date(ts);
        const now  = new Date();
        const diff = Math.floor((now - dt) / 1000);
        if (diff < 15)        return 'Active just now';
        if (diff < 60)        return `Active ${diff}s ago`;
        if (diff < 3600)      return `Active ${Math.floor(diff/60)}m ago`;
        const hh = dt.getHours().toString().padStart(2,'0');
        const mm = dt.getMinutes().toString().padStart(2,'0');
        return `Active ${hh}:${mm}`;
    } catch(e) {
        return ts ? 'Active ' + String(ts).substring(11,16) : 'N/A';
    }
}

// ── RENDER ────────────────────────────────────────────
function buildCard(a) {
    const sc         = resolveStatus(a);
    const sl         = statusLabel(sc);
    const bc         = statusBadgeClass(sc);
    const tabs       = toInt(a.tab_switch_count);
    const hasAlert   = tabs > 0;
    const isAbandoned= (a.status || '').toLowerCase() === 'abandoned';
    const isSubmitted= (a.status || '').toLowerCase() === 'submitted';
    const reentryAllowed = (a.allowed_reentry == 1 || a.allowed_reentry === true);
    const canUnlock  = (a.id) && !isSubmitted;
    const initial    = ((a.student_name || '?').charAt(0)).toUpperCase();

    // Integrity chip (tab switches)
    // Show warning if tabs > 0 OR if the attempt is abandoned (tab switch happened even if count = 0)
    const hasViolation = tabs > 0 || isAbandoned;
    const tabLabel = tabs > 0 ? `${tabs} Tab Switch${tabs>1?'es':''}` : (isAbandoned ? '1 Tab Switch' : '');
    const integrityChip = hasViolation && !isSubmitted
        ? `<span class="integrity-chip chip-warn"><i class="fas fa-exclamation-triangle" style="font-size:10px;"></i> ${tabLabel}</span>`
        : `<span class="integrity-chip chip-ok"><i class="fas fa-shield-alt" style="font-size:10px;"></i> No violations</span>`;

    // Unlock section (available for all non-submitted attempts)
    let unlockSection = '';
    if (canUnlock) {
        if (reentryAllowed) {
            unlockSection = `
            <div class="unlock-section">
                <span style="font-size:11.5px;font-weight:700;color:#059669;">
                    <i class="fas fa-lock-open"></i> Re-entry Allowed
                </span>
            </div>`;
        } else {
            unlockSection = `
            <div class="unlock-section">
                <span style="font-size:11.5px;font-weight:700;color:${isAbandoned ? '#EF4444' : 'var(--text3)'};">
                    <i class="fas ${isAbandoned ? 'fa-lock' : 'fa-lock-open'}"></i> ${isAbandoned ? 'Re-entry Blocked' : 'Quiz In Progress'}
                </span>
                <button class="btn-unlock" onclick="unlockAttempt(${toInt(a.id)}, this)">
                    <i class="fas fa-unlock-alt"></i> Unlock Quiz
                </button>
            </div>`;
        }
    }

    // Proctoring section
    let proctorSection = '';
    if (a.proctor_session) {
        const ps = a.proctor_session;
        if (sc === 'active') {
            proctorSection = `
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                <span style="font-size: 11.5px; font-weight: 700; color: #10B981; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-satellite-dish" style="animation: pulse-g 1.5s infinite;"></i> AI Monitoring Active
                </span>
                <button type="button" class="btn-unlock" onclick="openTeacherProctorModal('${a.student_identifier}', '${(a.student_name || 'Unknown').replace(/'/g, "\\'")}')" style="background:#3D52A0; padding: 4px 10.5px; border-radius: 6px; font-size: 10.5px; margin: 0; width: auto; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fas fa-satellite-dish"></i> Live Report
                </button>
            </div>`;
        } else if (isSubmitted || isAbandoned) {
            if (ps.id) {
                const reportUrl = `/teacher/proctor/reports/${ps.id}`;
                proctorSection = `
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <span style="font-size: 11.5px; font-weight: 700; color: var(--text3); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-circle-check" style="color:#059669;"></i> AI Report Saved
                    </span>
                    <a href="${reportUrl}" class="btn-unlock" style="background:var(--secondary); text-decoration:none; padding: 4px 10.5px; border-radius: 6px; font-size: 10.5px; margin: 0; width: auto; display: inline-flex; align-items: center; gap: 4px; color: #fff;">
                        <i class="fas fa-file-invoice"></i> View Report
                    </a>
                </div>`;
            }
        }
    }

    const hasReport = a.proctor_session && a.proctor_session.id;
    const reportUrl = hasReport ? `/teacher/proctor/reports/${a.proctor_session.id}` : '#';
    const viewReportBtn = hasReport
        ? `<a href="${reportUrl}" class="btn-unlock" style="background:#3D52A0; text-decoration:none; padding:5px 12px; border-radius:8px; font-size:11px; font-weight:700; color:#fff; display:flex; align-items:center; gap:5px; transition:.15s;" onmouseover="this.style.background='#2d3f80'" onmouseout="this.style.background='#3D52A0'">
               <i class="fas fa-file-invoice"></i> View Report
           </a>`
        : `<button class="btn-unlock" style="background:#F1F5F9; color:#94A3B8; border:1px solid #E2E8F0; padding:5px 12px; border-radius:8px; font-size:11px; font-weight:700; display:flex; align-items:center; gap:5px; cursor:not-allowed;" disabled>
               <i class="fas fa-file-invoice"></i> View Report
           </button>`;

    const card = document.createElement('div');
    card.className = `attempt-card${hasViolation && !isSubmitted ? ' has-alert' : ''} fade-in-card`;
    card.dataset.sid    = String(a.student_identifier || a.id || '');
    card.dataset.status = (a.status || '').toLowerCase();
    card.dataset.alert  = hasViolation ? '1' : '0';
    card.innerHTML = `
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="student-avatar">${initial}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:13.5px;color:var(--text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${a.student_name ?? 'Unknown'}</div>
                <div style="font-size:11px;color:var(--text3);">${a.student_identifier ?? ''}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span class="badge ${bc}" style="white-space:nowrap;">
                    <span class="status-dot ${dotClass(sc)}"></span>${sl}
                </span>
                ${viewReportBtn}
            </div>
        </div>
        <div class="row2">
            ${integrityChip}
            <span class="last-active">
                <i class="fas fa-clock" style="font-size:10px;"></i>
                ${formatLastActive(a.last_active_at)}
            </span>
        </div>
        ${unlockSection}
        ${proctorSection}`;
    return card;
}

function renderList() {
    const container = document.getElementById('attemptsContainer');
    let visible = allAttempts;
    if (currentFilter === 'active')    visible = allAttempts.filter(a => a.is_active);
    if (currentFilter === 'submitted') visible = allAttempts.filter(a => (a.status||'').toLowerCase() === 'submitted');
    if (currentFilter === 'abandoned') visible = allAttempts.filter(a => (a.status||'').toLowerCase() === 'abandoned');
    if (currentFilter === 'alerts')    visible = allAttempts.filter(a => toInt(a.tab_switch_count) > 0);

    if (visible.length === 0) {
        container.innerHTML = `<div class="empty-monitor"><i class="fas fa-person-circle-question"></i><br>No students match this filter.</div>`;
        return;
    }

    // Diff: update existing cards by student_identifier, insert new ones
    const existingIds = new Set([...container.querySelectorAll('.attempt-card')].map(c => c.dataset.sid));
    const newIds      = new Set(visible.map(a => String(a.student_identifier || a.id || '')));

    // Remove cards no longer visible
    container.querySelectorAll('.attempt-card').forEach(card => {
        if (!newIds.has(card.dataset.sid)) card.remove();
    });

    // Update/insert cards
    visible.forEach((a, idx) => {
        const sid = String(a.student_identifier || a.id || '');
        const existing = container.querySelector(`.attempt-card[data-sid="${CSS.escape(sid)}"]`);
        const newCard  = buildCard(a);
        if (existing) {
            existing.replaceWith(newCard);
            newCard.classList.remove('fade-in-card'); // no animation on update
        } else {
            container.appendChild(newCard);
        }
    });

    updateStats();
    updateTeacherProctorModalValues();
}

function updateStats() {
    const active    = allAttempts.filter(a => a.is_active).length;
    const submitted = allAttempts.filter(a => (a.status||'').toLowerCase() === 'submitted').length;
    // Count alerts: tab_switch_count > 0 OR status is abandoned
    const alerts    = allAttempts.filter(a => toInt(a.tab_switch_count) > 0 || (a.status||'').toLowerCase() === 'abandoned').length;
    document.getElementById('cnt-active').textContent    = active;
    document.getElementById('cnt-submitted').textContent = submitted;
    document.getElementById('cnt-alerts').textContent    = alerts;
    document.getElementById('cnt-total').textContent     = allAttempts.length;
}

// ── FILTER ────────────────────────────────────────────
function setFilter(f, el) {
    currentFilter = f;
    document.querySelectorAll('.filter-chip').forEach(c => {
        c.className = 'filter-chip';
        const map = {all:'active-chip',active:'chip-active',submitted:'chip-submitted',abandoned:'chip-abandoned',alerts:'chip-alerts'};
        if (c.dataset.filter === f) c.classList.add(map[f] || 'active-chip');
    });
    renderList();
}

// ── FETCH ─────────────────────────────────────────────
async function fetchAttempts(manual = false) {
    if (manual) document.getElementById('refreshIcon').classList.add('fa-spin');
    try {
        const r    = await fetch(POLL_URL, { headers: {'X-Requested-With':'XMLHttpRequest'} });
        const data = await r.json();
        if (data.status && Array.isArray(data.attempts)) {
            allAttempts = data.attempts;
            renderList();
            updateLastUpdated();
        }
    } catch(e) { /* silent */ }
    if (manual) document.getElementById('refreshIcon').classList.remove('fa-spin');
}

// ── UNLOCK ────────────────────────────────────────────
function unlockAttempt(id, btn) {
    if (!id) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Unlocking...';
    fetch(UNLOCK_URL, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ attempt_id: id })
    }).then(r => r.json()).then(data => {
        if (data.status) {
            fetchAttempts(); // refresh immediately
        } else {
            alert(data.message || 'Could not unlock');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock-alt"></i> Unlock Quiz';
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-unlock-alt"></i> Unlock Quiz';
    });
}

// ── COUNTDOWN RING ────────────────────────────────────
function updateRing(secs) {
    const c = document.getElementById('cringCircle');
    if (c) c.style.strokeDashoffset = CIRC * (1 - secs / POLL_SECS);
}

function startCountdown() {
    clearInterval(cdTimer);
    secsLeft = POLL_SECS;
    updateRing(secsLeft);
    const lbl = document.getElementById('refreshLabel');
    cdTimer = setInterval(() => {
        secsLeft = Math.max(0, secsLeft - 1);
        updateRing(secsLeft);
        if (autoOn && lbl) lbl.textContent = `Auto-refreshing in ${secsLeft}s`;
    }, 1000);
}

function schedulePoll() {
    clearTimeout(pollTimer);
    if (!autoOn) return;
    startCountdown();
    pollTimer = setTimeout(async () => {
        await fetchAttempts();
        schedulePoll();
    }, POLL_SECS * 1000);
}

// ── AUTO-REFRESH TOGGLE ───────────────────────────────
function toggleAuto(on) {
    autoOn = on;
    const lbl = document.getElementById('refreshLabel');
    const ring = document.getElementById('cringCircle');
    if (autoOn) {
        lbl.textContent = 'Auto-refreshing in 8s';
        lbl.className = 'refresh-label';
        schedulePoll();
    } else {
        clearTimeout(pollTimer);
        clearInterval(cdTimer);
        lbl.textContent = 'Auto-refresh paused';
        lbl.className = 'refresh-label paused';
        if (ring) ring.style.strokeDashoffset = CIRC;
    }
}

function updateLastUpdated() {
    const now = new Date();
    const t = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')}`;
    const el = document.getElementById('lastUpdated');
    if (el) el.textContent = `· ${t}`;
}

// ── LIVE PROCTORING MODAL FUNCTIONS ──────────────────
let activeProctorStudentId = null;

async function openTeacherProctorModal(studentId, studentName) {
    activeProctorStudentId = studentId;
    document.getElementById('teacherModalStudentName').textContent = studentName;
    document.getElementById('teacherModalStudentId').textContent = 'ID: ' + studentId;
    
    // Update values immediately from current state
    updateTeacherProctorModalValues();

    // Fetch fresh live metrics directly from backend endpoint
    try {
        const metricsRes = await fetch("{{ route('student.proctor.metrics') }}", { headers: {'Accept':'application/json'} });
        if (metricsRes.ok) {
            const data = await metricsRes.json();
            const gaze = data.gaze_away_count || 0;
            const head = data.head_turn_count || 0;
            const noFace = data.no_face_count || 0;
            const multi = data.multiple_face_count || 0;
            const alarms = data.alarm_count || data.total_alarms || (gaze + head + noFace + multi);
            const risk = Math.round(data.risk_score || data.max_risk_score || data.avg_risk_score || 0);

            const rEl = document.getElementById('teacherModalRisk'); if (rEl) rEl.textContent = risk + '%';
            const aEl = document.getElementById('teacherModalAlarms'); if (aEl) aEl.textContent = alarms;
            const gEl = document.getElementById('teacherModalGaze'); if (gEl) gEl.textContent = gaze;
            const hEl = document.getElementById('teacherModalHead'); if (hEl) hEl.textContent = head;
            const nEl = document.getElementById('teacherModalNoFace'); if (nEl) nEl.textContent = noFace;
            const mEl = document.getElementById('teacherModalMultiFace'); if (mEl) mEl.textContent = multi;
            const bEl = document.getElementById('teacherModalBlink'); if (bEl) bEl.textContent = data.blink_count || 0;

            const stEl = document.getElementById('teacherModalStatus');
            if (stEl) {
                const lvl = (data.alarm_level || 'none').toLowerCase();
                stEl.textContent = lvl === 'none' ? 'NORMAL' : lvl.toUpperCase();
                stEl.className = 'badge';
                const badgeClasses = { none: 'badge-green', low: 'badge-amber', medium: 'badge-amber', high: 'badge-red', critical: 'badge-red', calibrating: 'badge-gray' };
                stEl.classList.add(badgeClasses[lvl] || 'badge-green');
            }
        }
    } catch(e) {}
    
    // Show overlay
    const overlay = document.getElementById('teacherProctorModalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.classList.add('show');
    }
}

function closeTeacherProctorModal() {
    activeProctorStudentId = null;
    const overlay = document.getElementById('teacherProctorModalOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        overlay.classList.remove('show');
    }
}

function updateTeacherProctorModalValues() {
    if (!activeProctorStudentId) return;
    
    const attempt = allAttempts.find(a => String(a.student_identifier) === String(activeProctorStudentId) || String(a.student_id) === String(activeProctorStudentId));
    if (!attempt) return;
    
    const ps = attempt.proctor_session || {};
    
    const gazeVal  = ps.gaze_away_count || 0;
    const headVal  = ps.head_turn_count || 0;
    const noFaceVal= ps.no_face_count || 0;
    const multiVal = ps.multiple_face_count || 0;
    const alarmsVal= ps.alarm_count || ps.total_alarms || (gazeVal + headVal + noFaceVal + multiVal);

    // Risk Score
    const riskVal = Math.round(ps.risk_score || ps.max_risk_score || ps.avg_risk_score || 0);
    const riskEl = document.getElementById('teacherModalRisk');
    if (riskEl) riskEl.textContent = riskVal + '%';
    
    // Alarms Count
    const alarmsEl = document.getElementById('teacherModalAlarms');
    if (alarmsEl) alarmsEl.textContent = alarmsVal;
    
    // Detailed indicators
    const gazeEl = document.getElementById('teacherModalGaze');
    if (gazeEl) gazeEl.textContent = gazeVal;
    
    const headEl = document.getElementById('teacherModalHead');
    if (headEl) headEl.textContent = headVal;
    
    const noFaceEl = document.getElementById('teacherModalNoFace');
    if (noFaceEl) noFaceEl.textContent = noFaceVal;
    
    const multiFaceEl = document.getElementById('teacherModalMultiFace');
    if (multiFaceEl) multiFaceEl.textContent = multiVal;
    
    const blinkEl = document.getElementById('teacherModalBlink');
    if (blinkEl) blinkEl.textContent = ps.blink_count || 0;
    
    // Status Badge
    const statusEl = document.getElementById('teacherModalStatus');
    if (statusEl) {
        const level = (ps.alarm_level || 'none').toLowerCase();
        const levelUpper = level === 'none' ? 'NORMAL' : level.toUpperCase();
        statusEl.textContent = levelUpper;
        statusEl.className = 'badge';
        
        const colors = {
            none: 'badge-green',
            low: 'badge-amber',
            medium: 'badge-amber',
            high: 'badge-red',
            critical: 'badge-red',
            calibrating: 'badge-gray'
        };
        statusEl.classList.add(colors[level] || 'badge-green');
    }
}

// ── INIT ──────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    // Load server-side initial data as JSON immediately
    fetchAttempts().then(() => schedulePoll());
});
</script>
@endpush
