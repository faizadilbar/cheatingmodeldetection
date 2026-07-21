@extends('layouts.app')
@section('title','Course Quizzes')
@section('page-title','Course Quizzes')
@section('page-subtitle','All quizzes for this course')

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@section('topbar-actions')
<a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
<a href="{{ route('teacher.quiz.create', $courseId) }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Quiz</a>
@endsection

@section('content')
@if(count($quizzes) > 0)
<div class="fade-in">
@foreach($quizzes as $quiz)
<div class="quiz-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:linear-gradient(135deg,#4F46E5,#6D28D9);border-radius:10px;">
                    <i class="fas fa-{{ $quiz['is_poll'] ? 'poll' : 'clipboard-list' }}" style="color:#fff;font-size:15px;"></i>
                </span>
                <div>
                    <div style="font-weight:700;font-size:15px;color:var(--text1);">{{ $quiz['quiz_name'] }}</div>
                    <div style="font-size:11px;color:var(--text3);">Code: <strong style="color:#4F46E5;">{{ $quiz['quiz_code'] }}</strong></div>
                </div>
                @if($quiz['is_poll'])<span class="badge badge-purple">Poll</span>@endif
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <span class="tag"><i class="fas fa-calendar" style="color:#4F46E5;"></i> {{ $quiz['quiz_date'] }}</span>
                <span class="tag"><i class="fas fa-clock" style="color:#10B981;"></i> {{ substr($quiz['start_time'],0,5) }} – {{ substr($quiz['end_time'],0,5) }}</span>
                <span class="tag"><i class="fas fa-question-circle" style="color:#F59E0B;"></i> {{ $quiz['total_questions'] }} Qs</span>
                <span class="tag"><i class="fas fa-star" style="color:#6D28D9;"></i> {{ $quiz['total_marks'] ?? $quiz['total_questions'] }} Marks</span>
                <span class="badge badge-{{ $quiz['difficulty'] === 'easy' ? 'green' : ($quiz['difficulty'] === 'hard' ? 'red' : 'amber') }}">{{ ucfirst($quiz['difficulty']) }}</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('teacher.quiz.view', $quiz['quiz_code']) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i> View</a>
            <a href="{{ route('teacher.quiz.monitor', $quiz['quiz_code']) }}" class="btn btn-sm btn-primary"><i class="fas fa-satellite-dish"></i> Monitor</a>
        </div>
    </div>
</div>
@endforeach
</div>
@else
<div class="empty-state fade-in">
    <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
    <h3>No Quizzes Yet</h3>
    <p>Create your first quiz for this course</p>
    <a href="{{ route('teacher.quiz.create', $courseId) }}" class="btn btn-primary" style="margin-top:16px;"><i class="fas fa-plus"></i> Create Quiz</a>
</div>
@endif
@endsection
