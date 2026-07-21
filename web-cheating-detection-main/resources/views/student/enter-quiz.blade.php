@extends('layouts.app')
@section('title','Enter Quiz Code')
@section('page-title','Enter Quiz Code')
@section('page-subtitle', request('course_name') ? 'Course: '.request('course_name') : 'Join an active exam/poll')

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item active"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('content')
<div style="max-width:560px;margin:0 auto;margin-top:32px;">

    {{-- ═══ LOCKED / RE-ENTRY BLOCKED ═══ --}}
    @if(session('locked'))
    <div style="background:#fff;border:1.5px solid rgba(232,83,63,.25);border-radius:18px;padding:28px 28px 20px;margin-bottom:24px;box-shadow:0 8px 30px rgba(232,83,63,.08);">
        <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:20px;">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(232,83,63,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-lock" style="font-size:20px;color:#E8533F;"></i>
            </div>
            <div>
                <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#E8533F;margin-bottom:6px;">Attempt Locked</div>
                <p style="font-size:13.5px;color:var(--text2);line-height:1.6;margin:0;">
                    Your previous attempt was flagged. This happens when you <strong>switch tabs</strong> or <strong>minimize the window</strong> during a quiz.
                </p>
            </div>
        </div>

        <div style="background:rgba(232,83,63,.05);border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:13px;color:var(--text2);line-height:1.7;">
            <div style="font-weight:700;color:var(--text1);margin-bottom:6px;"><i class="fas fa-info-circle" style="color:var(--mid);margin-right:6px;"></i>What can you do?</div>
            <div>• Ask your <strong>teacher</strong> to unlock your attempt from the Monitor panel.</div>
            <div>• Or if you want to try a <strong>different quiz</strong>, clear this session below.</div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('student.quiz.clear-lock') }}" style="display:inline;">
                @csrf
                <button type="submit" style="background:rgba(232,83,63,.1);border:1.5px solid rgba(232,83,63,.3);color:#E8533F;padding:9px 18px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;font-family:'DM Sans',sans-serif;display:flex;align-items:center;gap:7px;">
                    <i class="fas fa-sync-alt"></i> Try Different Code
                </button>
            </form>
            <div style="color:var(--text3);font-size:12px;display:flex;align-items:center;gap:4px;font-weight:600;">
                <i class="fas fa-exclamation-circle"></i>
                <span>Your locked attempt still requires teacher approval</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ GENERAL ERRORS ═══ --}}
    @if($errors->has('quiz_code'))
    <div style="background:#fff;border:1.5px solid rgba(232,83,63,.2);border-radius:14px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
        <i class="fas fa-exclamation-triangle" style="color:#E8533F;margin-top:2px;font-size:16px;"></i>
        <div style="font-size:14px;color:var(--text1);line-height:1.6;font-weight:500;">{{ $errors->first('quiz_code') }}</div>
    </div>
    @endif

    {{-- ═══ MAIN CARD ═══ --}}
    <div class="card fade-in">
        <div class="card-header">
            <h3><i class="fas fa-key" style="color:var(--mid);margin-right:8px;"></i>Join Active Exam</h3>
        </div>
        <div class="card-body">

            @if(request('course_id'))
            <div style="background:rgba(112,145,230,.08);border:1px solid rgba(112,145,230,.2);border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:10px;margin-bottom:20px;font-size:13px;color:var(--text2);">
                <i class="fas fa-lock" style="color:var(--mid);"></i>
                <span>Enter quiz code for <strong style="color:var(--text1);">{{ request('course_name', 'this course') }}</strong> only.</span>
            </div>
            @endif

            <form method="POST" action="{{ route('student.quiz.confirm') }}">
                @csrf
                @if(request('course_id'))
                    <input type="hidden" name="course_id" value="{{ request('course_id') }}">
                    <input type="hidden" name="course_name" value="{{ request('course_name') }}">
                @endif
                <div class="form-group">
                    <label class="form-label">Quiz Code</label>
                    <input type="text" name="quiz_code" class="form-control"
                           placeholder="E.G. AB12CD"
                           style="text-transform:uppercase;font-size:22px;letter-spacing:4px;text-align:center;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;padding:18px;"
                           value="{{ old('quiz_code') }}"
                           required autofocus>
                    <div class="form-text" style="text-align:center;margin-top:8px;">
                        @if(request('course_id'))
                            This code must belong to your <strong>{{ request('course_name', 'enrolled') }}</strong> course.
                        @else
                            Ask your teacher for the 6-character quiz code.
                        @endif
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:15px;margin-top:10px;">
                    <i class="fas fa-search"></i> Preview Exam Ticket
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
