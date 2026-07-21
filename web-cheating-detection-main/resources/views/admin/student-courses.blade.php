@extends('layouts.app')

@section('title', 'Student Courses')

{{-- ✅ FIX: @section with Blade expressions MUST use @section + @endsection, NOT single-quoted string --}}
@section('page-title')
Student: {{ $student['name'] ?? $student['student_name'] ?? 'Student' }}
@endsection

@section('page-subtitle', 'Manage course enrollments')

@section('sidebar-nav')
<span class="nav-section">Admin Panel</span>
<a href="{{ route('admin.dashboard') }}" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<a href="{{ route('admin.teachers') }}" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
<a href="{{ route('admin.students') }}" class="nav-item active"><i class="fas fa-user-graduate"></i> Students</a>
<a href="{{ route('admin.courses') }}" class="nav-item"><i class="fas fa-book-open"></i> Courses</a>
@endsection

@section('topbar-actions')
<a href="{{ route('admin.students') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Students</a>
@endsection

@section('content')

{{-- Student info card --}}
@if($student)
<div class="card fade-in" style="margin-bottom:20px;padding:20px 24px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#4F46E5,#6D28D9);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif;">
            {{ strtoupper(substr($student['name'] ?? 'S', 0, 1)) }}
        </div>
        <div>
            <div style="font-size:17px;font-weight:700;color:#1E293B;">{{ $student['name'] ?? 'Student' }}</div>
            <div style="font-size:13px;color:#64748B;margin-top:2px;">
                <span style="margin-right:16px;"><i class="fas fa-envelope" style="margin-right:4px;"></i>{{ $student['email'] ?? '—' }}</span>
                <span><i class="fas fa-id-card" style="margin-right:4px;"></i>Roll No: {{ $student['rollno'] ?? $student['roll_no'] ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    {{-- ── ENROLLED COURSES ── --}}
    <div class="card fade-in">
        <div class="card-header">
            <h3><i class="fas fa-check-circle" style="color:#10B981;margin-right:8px;"></i>Enrolled Courses
                <span style="background:#DCFCE7;color:#15803D;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;">{{ count($courses) }}</span>
            </h3>
        </div>

        @if(count($courses) > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($courses as $i => $c)
                    <tr>
                        <td style="color:#94A3B8;font-size:12px;">{{ $i + 1 }}</td>
                        <td style="font-weight:600;">{{ $c['course_title'] ?? '—' }}</td>
                        <td><span class="badge badge-blue">{{ $c['course_code'] ?? '—' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.student.remove-course') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="user_id"   value="{{ $id }}">
                                <input type="hidden" name="course_id" value="{{ $c['course_id'] ?? $c['id'] ?? '' }}">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Remove this course from student?')"
                                        title="Remove course">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-book-open"></i></div>
            <h3>No Courses Enrolled</h3>
            <p>Use the panel on the right to assign courses.</p>
        </div>
        @endif
    </div>

    {{-- ── ASSIGN COURSE ── --}}
    <div class="card fade-in">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle" style="color:#4F46E5;margin-right:8px;"></i>Assign Course</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:16px;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom:16px;">
                    <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.student.assign-course') }}">
                @csrf
                <input type="hidden" name="user_ids[]" value="{{ $id }}">

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Select Course</label>
                    <select name="course_id" class="form-control form-select" required>
                        <option value="">-- Select a Course --</option>
                        @foreach($allCourses as $c)
                            @php
                                $courseId    = $c['id'] ?? 0;
                                $courseTitle = $c['course_title'] ?? 'Unknown';
                                $courseCode  = $c['course_code'] ?? '';
                                // Mark already enrolled courses
                                $enrolled    = collect($courses)->contains(fn($e) =>
                                    ($e['course_id'] ?? $e['id'] ?? 0) == $courseId
                                );
                            @endphp
                            <option value="{{ $courseId }}" {{ $enrolled ? 'disabled style=color:#94A3B8' : '' }}>
                                {{ $courseTitle }}{{ $courseCode ? ' ('.$courseCode.')' : '' }}{{ $enrolled ? ' ✓ Enrolled' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-plus"></i> Assign Course
                </button>
            </form>

            @if(count($allCourses) === 0)
            <div style="margin-top:16px;padding:14px;background:#FFF7ED;border-radius:10px;font-size:13px;color:#9A3412;">
                <i class="fas fa-exclamation-triangle"></i> No courses available. Add courses from the backend first.
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
