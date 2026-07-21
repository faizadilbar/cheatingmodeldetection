@extends('layouts.app')
@section('title','View Quiz')
@section('page-title') {{ $quiz['quiz_name'] ?? 'Quiz' }} @endsection
@section('page-subtitle') Code: {{ $code }} @endsection

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@section('topbar-actions')
<a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
<a href="{{ route('teacher.quiz.monitor', $code) }}" class="btn btn-primary"><i class="fas fa-satellite-dish"></i> Monitor</a>
@endsection

@section('content')
@if(!($quiz['status'] ?? true))
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $quiz['message'] ?? 'Quiz not found' }}</div>
@else
<div class="card fade-in" style="margin-bottom:20px;">
    <div class="card-body">
        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
            <span class="tag"><i class="fas fa-calendar" style="color:var(--primary);"></i> {{ $quiz['quiz_date'] ?? '' }}</span>
            <span class="tag"><i class="fas fa-clock" style="color:#10B981;"></i> {{ substr($quiz['start_time']??'',0,5) }} – {{ substr($quiz['end_time']??'',0,5) }}</span>
            <span class="tag"><i class="fas fa-question-circle" style="color:#F59E0B;"></i> {{ $quiz['total_questions'] ?? 0 }} Questions</span>
            <span class="tag"><i class="fas fa-star" style="color:var(--secondary);"></i> {{ $quiz['total_marks'] ?? 0 }} Marks</span>
            @if($quiz['is_poll'] ?? false)<span class="badge badge-purple">Poll</span>@endif
        </div>
    </div>
</div>

@foreach($quiz['questions'] ?? [] as $i => $q)
@php $type = $q['type']; @endphp
<div class="q-card fade-in">
    <span style="display:inline-block;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;margin-bottom:10px;">Q{{ $i+1 }}</span>
    <span class="badge badge-{{ $type === 'mcq' ? 'blue' : ($type === 'short' ? 'green' : 'amber') }}" style="margin-left:6px;">{{ strtoupper($type) }}</span>
    <div style="font-size:14px;font-weight:600;color:var(--text1);margin:10px 0;">{{ $q['question'] }}</div>

    @if($type === 'mcq')
    <div class="mcq-options" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
        @foreach(['A','B','C','D'] as $l)
        @php $key = 'option_'.strtolower($l); $val = $q[$key] ?? null; $isCorrect = ($q['correct_answer']??'') === $l; @endphp
        @if($val)
        <div style="padding:8px 12px;border-radius:8px;font-size:13px;background:{{ $isCorrect?'#ECFDF5':'#F8FAFC' }};border:1.5px solid {{ $isCorrect?'#10B981':'var(--border)' }};color:{{ $isCorrect?'#065F46':'var(--text2)' }};">
            <strong>{{ $l }}.</strong> {{ $val }}
            @if($isCorrect) <i class="fas fa-check-circle" style="color:#10B981;float:right;"></i> @endif
        </div>
        @endif
        @endforeach
    </div>
    @else
    <div style="margin-top:8px;padding:8px 12px;background:#F0FDF4;border-radius:8px;font-size:12.5px;color:#065F46;">
        <i class="fas fa-key" style="margin-right:6px;"></i>{{ $q['correct_answer'] }}
    </div>
    @endif
</div>
@endforeach
@endif
@endsection

@push('styles')
<style>
.q-card { background:#F8FAFC; border:1.5px solid var(--border); border-radius:12px; padding:16px; margin-bottom:12px; }
.mcq-options {}
</style>
@endpush
