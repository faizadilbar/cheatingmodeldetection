@extends('layouts.app')
@section('title','My Courses')
@section('page-title','My Courses')
@section('page-subtitle') Welcome, {{ $student['name'] ?? 'Student' }} @endsection

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('topbar-actions')
<a href="{{ route('student.quiz.enter') }}" class="btn btn-primary"><i class="fas fa-keyboard"></i> Enter Quiz Code</a>
@endsection

@section('content')
<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4F46E5,#6D28D9);">
            <i class="fas fa-book-open" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ count($courses) }}</div>
            <div class="label">Enrolled Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706);">
            <i class="fas fa-id-card" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value" style="font-size:18px;">{{ $student['rollno'] ?? 'N/A' }}</div>
            <div class="label">Roll Number</div>
        </div>
    </div>
</div>

@if(count($courses) > 0)
<h2 style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;margin-bottom:16px;color:var(--text1);">My Enrolled Courses</h2>
<div class="courses-grid fade-in">
    @foreach($courses as $i => $course)
    @php $colors=['#4F46E5','#10B981','#6D28D9','#F59E0B','#14B8A6']; $c=$colors[$i%count($colors)]; @endphp
    <a href="{{ route('student.course.detail', $course['id']) }}" class="course-card">
        <div class="course-card-header" style="background:linear-gradient(135deg,{{ $c }},{{ $c }}BB);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-book" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <h3>{{ $course['course_title'] }}</h3>
                    <p>{{ $course['course_code'] ?? '' }}</p>
                </div>
            </div>
        </div>
        <div class="course-card-body" style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;color:var(--text3);">View quizzes & results</span>
            <i class="fas fa-chevron-right" style="color:var(--text3);font-size:12px;"></i>
        </div>
    </a>
    @endforeach
</div>
@else
<div class="empty-state fade-in">
    <div class="empty-icon"><i class="fas fa-book-open"></i></div>
    <h3>No Courses Yet</h3>
    <p>Contact your admin to get enrolled in courses</p>
</div>
@endif
@endsection
