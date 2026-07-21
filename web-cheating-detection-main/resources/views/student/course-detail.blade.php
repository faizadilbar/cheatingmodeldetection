@extends('layouts.app')
@section('title','Course Details')
@section('page-title', $course['course_title'] ?? 'Course Details')
@section('page-subtitle', 'Course Code: ' . ($course['course_code'] ?? ''))

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('topbar-actions')
<a href="{{ route('student.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
<a href="{{ route('student.quiz.enter') }}" class="btn btn-primary"><i class="fas fa-keyboard"></i> Take Quiz</a>
@endsection

@section('content')
<div class="card fade-in">
    <div class="card-header">
        <h3><i class="fas fa-info-circle" style="color:var(--primary);margin-right:8px;"></i>Course Description</h3>
    </div>
    <div class="card-body">
        <p style="color:var(--text2);font-size:14px;line-height:1.6;">
            Welcome to the course page for <strong>{{ $course['course_title'] ?? 'this course' }}</strong>. 
            All quizzes, exam details, and results published by your teacher will be available here. 
            Please use your Quiz Code to join active quizzes.
        </p>
    </div>
</div>

<div class="card fade-in" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="fas fa-running" style="color:#10B981;margin-right:8px;"></i>Quick Options</h3>
    </div>
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('student.quiz.enter') }}?course_id={{ $courseId }}&course_name={{ urlencode($course['course_title'] ?? '') }}" class="btn btn-primary">
            <i class="fas fa-keyboard"></i> Enter Quiz Code
        </a>
        <a href="{{ route('student.results') }}?course_id={{ $courseId }}" class="btn btn-secondary">
            <i class="fas fa-chart-bar"></i> View My Course Results
        </a>
    </div>
</div>
@endsection
