@extends('layouts.app')
@section('title','Manage Teachers')
@section('page-title','Teachers')
@section('page-subtitle','Manage all teachers')

@section('sidebar-nav')
<span class="nav-section">Admin Panel</span>
<a href="{{ route('admin.dashboard') }}" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<a href="{{ route('admin.teachers') }}" class="nav-item active"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
<a href="{{ route('admin.students') }}" class="nav-item"><i class="fas fa-user-graduate"></i> Students</a>
<a href="{{ route('admin.courses') }}" class="nav-item"><i class="fas fa-book-open"></i> Courses</a>
@endsection

@section('topbar-actions')
<button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')">
    <i class="fas fa-plus"></i> Add Teacher
</button>
@endsection

@section('content')
<div class="card fade-in">
    <div class="card-header">
        <h3><i class="fas fa-chalkboard-teacher" style="color:#4F46E5;margin-right:8px;"></i>All Teachers <span class="badge badge-blue" style="margin-left:8px;">{{ count($teachers) }}</span></h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($teachers as $i => $t)
                <tr>
                    <td style="color:var(--text3);font-size:12px;">{{ $i+1 }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;background:linear-gradient(135deg,#4F46E5,#6D28D9);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">{{ strtoupper(substr($t['name'],0,1)) }}</div>
                            <div style="font-weight:600;color:var(--text1);">{{ $t['name'] }}</div>
                        </div>
                    </td>
                    <td>{{ $t['email'] }}</td>
                    <td>
                        <span class="badge badge-blue">ID: {{ $t['id'] }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-chalkboard-teacher"></i></div><h3>No Teachers Yet</h3><p>Add your first teacher to get started</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ADD TEACHER MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <h2 class="modal-title" style="margin:0;"><i class="fas fa-user-plus" style="color:#4F46E5;margin-right:10px;"></i>Add New Teacher</h2>
            <button onclick="document.getElementById('addModal').classList.remove('show')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text3);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.teachers.add') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter teacher name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Set password" required>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="document.getElementById('addModal').classList.remove('show')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Teacher</button>
            </div>
        </form>
    </div>
</div>
@endsection
