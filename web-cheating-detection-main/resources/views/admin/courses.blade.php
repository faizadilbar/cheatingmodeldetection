@extends('layouts.app')
@section('title','Manage Courses')
@section('page-title','Courses')
@section('page-subtitle','Assign teachers to courses')

@section('sidebar-nav')
<span class="nav-section">Admin Panel</span>
<a href="{{ route('admin.dashboard') }}" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<a href="{{ route('admin.teachers') }}" class="nav-item"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
<a href="{{ route('admin.students') }}" class="nav-item"><i class="fas fa-user-graduate"></i> Students</a>
<a href="{{ route('admin.courses') }}" class="nav-item active"><i class="fas fa-book-open"></i> Courses</a>
@endsection

@section('content')
<div class="card fade-in">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h3><i class="fas fa-book-open" style="color:#F59E0B;margin-right:8px;"></i>All Courses <span class="badge badge-amber" style="margin-left:8px;">{{ count($courses) }}</span></h3>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('addCourseModal').classList.add('show')">
            <i class="fas fa-plus"></i> Add Course
        </button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Course</th><th>Code</th><th>Teacher</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($courses as $i => $c)
                <tr>
                    <td style="color:var(--text3);font-size:12px;">{{ $i+1 }}</td>
                    <td><div style="font-weight:600;color:var(--text1);">{{ $c['course_title'] }}</div></td>
                    <td><span class="badge badge-blue">{{ $c['course_code'] }}</span></td>
                    <td>{{ $c['teacher']['name'] ?? '—' }}</td>
                    <td><span class="badge {{ $c['is_active'] ? 'badge-green' : 'badge-gray' }}">{{ $c['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick="openAssignModal({{ $c['id'] }}, '{{ addslashes($c['course_title']) }}')">
                            <i class="fas fa-user-plus"></i> Assign Teacher
                        </button>
                        @if(isset($c['teacher']))
                        <form method="POST" action="{{ route('admin.courses.remove-teacher') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $c['id'] }}">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove teacher?')">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fas fa-book-open"></i></div><h3>No Courses Found</h3></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ADD COURSE MODAL -->
<div class="modal-overlay" id="addCourseModal">
    <div class="modal-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <h2 class="modal-title" style="margin:0;"><i class="fas fa-book-open" style="color:#F59E0B;margin-right:10px;"></i>Add New Course</h2>
            <button onclick="document.getElementById('addCourseModal').classList.remove('show')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text3);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.courses.add') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Course Title</label>
                <input type="text" name="course_title" class="form-control" value="{{ old('course_title') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Course Code</label>
                <input type="text" name="course_code" class="form-control" value="{{ old('course_code') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                <label class="form-label" style="margin:0;">Active</label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('addCourseModal').classList.remove('show')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Course</button>
            </div>
        </form>
    </div>
</div>

<!-- ASSIGN TEACHER MODAL -->
<div class="modal-overlay" id="assignModal">
    <div class="modal-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <h2 class="modal-title" style="margin:0;"><i class="fas fa-chalkboard-teacher" style="color:#4F46E5;margin-right:10px;"></i>Assign Teacher</h2>
            <button onclick="document.getElementById('assignModal').classList.remove('show')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text3);">&times;</button>
        </div>
        <p id="assignCourseLabel" style="margin-bottom:16px;color:var(--text2);font-size:14px;"></p>
        <form method="POST" action="{{ route('admin.courses.assign-teacher') }}" id="assignForm">
            @csrf
            <input type="hidden" name="course_ids[]" id="assignCourseId">
            <div class="form-group">
                <label class="form-label">Select Teacher</label>
                <select name="teacher_id" class="form-control form-select" required>
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t['id'] }}">{{ $t['name'] }} ({{ $t['email'] }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('assignModal').classList.remove('show')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Assign</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function openAssignModal(courseId, courseTitle) {
    document.getElementById('assignCourseId').value = courseId;
    document.getElementById('assignCourseLabel').textContent = 'Course: ' + courseTitle;
    document.getElementById('assignModal').classList.add('show');
}
</script>
@endpush
