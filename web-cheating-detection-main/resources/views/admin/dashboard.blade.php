@extends('layouts.app')
@section('title','Admin Dashboard')
@section('page-title','Dashboard')
@section('page-subtitle') Welcome back, {{ session('user')['name'] ?? 'Admin' }} @endsection

@section('sidebar-nav')
<span class="nav-section">Admin Panel</span>
<a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<a href="{{ route('admin.teachers') }}" class="nav-item {{ request()->routeIs('admin.teachers') ? 'active' : '' }}"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
<a href="{{ route('admin.students') }}" class="nav-item {{ request()->routeIs('admin.students*') ? 'active' : '' }}"><i class="fas fa-user-graduate"></i> Students</a>
<a href="{{ route('admin.courses') }}" class="nav-item {{ request()->routeIs('admin.courses') ? 'active' : '' }}"><i class="fas fa-book-open"></i> Courses</a>
@endsection

@section('content')
<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4F46E5,#6D28D9);">
            <i class="fas fa-chalkboard-teacher" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ count($teachers) }}</div>
            <div class="label">Total Teachers</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10B981,#059669);">
            <i class="fas fa-user-graduate" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ count($students) }}</div>
            <div class="label">Total Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706);">
            <i class="fas fa-book-open" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">{{ count($courses) }}</div>
            <div class="label">Total Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#0EA5E9,#0284C7);">
            <i class="fas fa-robot" style="color:#fff;font-size:22px;"></i>
        </div>
        <div class="stat-info">
            <div class="value">AI</div>
            <div class="label">Powered Grading</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card fade-in">
        <div class="card-header">
            <h3><i class="fas fa-chalkboard-teacher" style="color:#4F46E5;margin-right:8px;"></i>Recent Teachers</h3>
            <a href="{{ route('admin.teachers') }}" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th></tr></thead>
                <tbody>
                @forelse(array_slice($teachers,0,5) as $t)
                    <tr>
                        <td><div style="font-weight:600;color:var(--text1);">{{ $t['name'] }}</div></td>
                        <td>{{ $t['email'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="text-align:center;color:var(--text3);padding:30px;">No teachers yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card fade-in">
        <div class="card-header">
            <h3><i class="fas fa-book-open" style="color:#10B981;margin-right:8px;"></i>Courses Overview</h3>
            <a href="{{ route('admin.courses') }}" class="btn btn-sm btn-secondary">Manage</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Course</th><th>Teacher</th><th>Status</th></tr></thead>
                <tbody>
                @forelse(array_slice($courses,0,5) as $c)
                    <tr>
                        <td><div style="font-weight:600;color:var(--text1);">{{ $c['course_title'] }}</div><div style="font-size:11px;color:var(--text3);">{{ $c['course_code'] }}</div></td>
                        <td>{{ $c['teacher']['name'] ?? '—' }}</td>
                        <td><span class="badge {{ $c['is_active'] ? 'badge-green' : 'badge-gray' }}">{{ $c['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--text3);padding:30px;">No courses yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
