@extends('layouts.app')
@section('title', $filterCourseName ? $filterCourseName.' Results' : 'My Results')
@section('page-title','My Results')
@section('page-subtitle', $filterCourseName ? 'Showing results for: '.$filterCourseName : 'Track your grading and feedback')

@section('sidebar-nav')
<span class="nav-section">Student Panel</span>
<a href="{{ route('student.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('student.quiz.enter') }}" class="nav-item"><i class="fas fa-keyboard"></i> Enter Quiz</a>
<a href="{{ route('student.results') }}" class="nav-item active"><i class="fas fa-chart-bar"></i> My Results</a>
@endsection

@section('topbar-actions')
@if($filterCourseId)
<a href="{{ route('student.results') }}" class="btn btn-secondary"><i class="fas fa-list"></i> Show All Results</a>
@endif
@endsection

@section('content')
<div class="card fade-in">
    <div class="card-header" style="justify-content:space-between;">
        <h3>
            <i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i>
            @if($filterCourseName)
                {{ $filterCourseName }} — Quiz Results
            @else
                Past Quizzes &amp; Exams
            @endif
        </h3>
        @if($filterCourseId)
        <span class="badge badge-blue" style="font-size:11px;">
            <i class="fas fa-filter" style="margin-right:4px;"></i>
            {{ count($results) }} result{{ count($results) !== 1 ? 's' : '' }}
        </span>
        @endif
    </div>
    @if(count($results) > 0)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Quiz Name</th>
                    @if(!$filterCourseId)<th>Course</th>@endif
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Date Taken</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $r)
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--text1);">{{ $r['quiz_name'] }}</div>
                            <div style="font-size:11px;color:var(--text3);">Code: {{ $r['quiz_code'] }}</div>
                        </td>
                        @if(!$filterCourseId)<td>{{ $r['course_name'] }}</td>@endif
                        <td>
                            <span class="badge badge-blue">
                                {{ $r['score'] }} / {{ $r['total_marks'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $r['percentage'] >= 50 ? 'badge-green' : 'badge-red' }}">
                                {{ $r['percentage'] }}%
                            </span>
                        </td>
                        <td style="font-size:12.5px;">{{ substr($r['submitted_at'],0,10) }}</td>
                        <td>
                            <a href="{{ route('student.result.detail', $r['quiz_id']) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-eye"></i> View Feedback
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
        <h3>No Results Yet</h3>
        @if($filterCourseName)
            <p>You haven't completed any exams in <strong>{{ $filterCourseName }}</strong> yet.</p>
            <a href="{{ route('student.results') }}" class="btn btn-secondary" style="margin-top:16px;">
                <i class="fas fa-list"></i> View All My Results
            </a>
        @else
            <p>You haven't completed any exams yet.</p>
        @endif
    </div>
    @endif
</div>
@endsection
