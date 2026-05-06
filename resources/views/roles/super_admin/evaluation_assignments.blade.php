@extends('layouts.app')

@section('content')
<div class="card mb-4"><div class="card-body"><h1 class="h5 mb-0">إسناد مسؤول التقييم للفروع</h1></div></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@foreach($officers as $officer)
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('role.super_admin.evaluation_assignments.update', $officer) }}">
        @csrf
        @method('PUT')
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>{{ $officer->name }}</strong>
            <span class="text-muted">{{ $officer->email }}</span>
        </div>
        <label class="form-label">الفروع المسندة</label>
        <select class="form-select" name="assigned_branch_ids[]" multiple size="6">
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($officer->assignedBranches->contains('id', $branch->id))>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="mt-3 text-end"><button class="btn btn-primary" type="submit">حفظ الإسناد</button></div>
    </form>
</div></div>
@endforeach
@endsection
