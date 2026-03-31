@extends('layouts.app')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <h1 class="h4 mb-2">Workflow Builder / منشئ سير الاعتماد</h1>
        </div></div>

        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 mb-3">Create Workflow / إنشاء سير اعتماد</h2>
            <form method="POST" action="{{ route('role.super_admin.workflows.store') }}" class="row g-2">@csrf
                <div class="col-md-3"><input class="form-control" name="module" placeholder="monthly_activities" required></div>
                <div class="col-md-2"><input class="form-control" name="code" placeholder="code" required></div>
                <div class="col-md-3"><input class="form-control" name="name_ar" placeholder="الاسم بالعربية"></div>
                <div class="col-md-3"><input class="form-control" name="name_en" placeholder="Name in English"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Create</button></div>
            </form>
        </div></div>

        @foreach($workflows as $workflow)
        <div class="card shadow-sm mb-3"><div class="card-body">
            <form method="POST" action="{{ route('role.super_admin.workflows.update', $workflow) }}" class="row g-2 mb-2">@csrf @method('PUT')
                <div class="col-md-2"><input class="form-control form-control-sm" name="module" value="{{ $workflow->module }}" required></div>
                <div class="col-md-2"><input class="form-control form-control-sm" name="code" value="{{ $workflow->code }}" required></div>
                <div class="col-md-3"><input class="form-control form-control-sm" name="name_ar" value="{{ $workflow->name_ar }}"></div>
                <div class="col-md-3"><input class="form-control form-control-sm" name="name_en" value="{{ $workflow->name_en }}"></div>
                <div class="col-md-1"><button class="btn btn-outline-primary btn-sm w-100">Save</button></div>
            </form>
            <form method="POST" action="{{ route('role.super_admin.workflows.destroy', $workflow) }}" class="text-end mb-3">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete Workflow</button></form>

            <h3 class="h6">Steps / المراحل</h3>
            @foreach($workflow->steps as $step)
                <div class="border rounded p-2 mb-2">
                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="row g-2">@csrf @method('PUT')
                        <div class="col-md-2"><input class="form-control form-control-sm" name="step_key" value="{{ $step->step_key }}" required></div>
                        <div class="col-md-1"><input class="form-control form-control-sm" name="step_order" type="number" min="1" value="{{ $step->step_order }}" required></div>
                        <div class="col-md-1"><input class="form-control form-control-sm" name="approval_level" type="number" min="1" value="{{ $step->approval_level }}" required></div>
                        <div class="col-md-1"><select class="form-select form-select-sm" name="step_type"><option value="sub" @selected($step->step_type==='sub')>sub</option><option value="main" @selected($step->step_type==='main')>main</option></select></div>
                        <div class="col-md-2"><select class="form-select form-select-sm" name="role_id"><option value="">-role-</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($step->role_id===$role->id)>{{ $role->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select class="form-select form-select-sm" name="permission_id"><option value="">-permission-</option>@foreach($permissions as $permission)<option value="{{ $permission->id }}" @selected($step->permission_id===$permission->id)>{{ $permission->name }}</option>@endforeach</select></div>
                        <div class="col-md-1"><input class="form-control form-control-sm" name="name_ar" value="{{ $step->name_ar }}"></div>
                        <div class="col-md-1"><input class="form-control form-control-sm" name="name_en" value="{{ $step->name_en }}"></div>
                        <div class="col-md-1"><button class="btn btn-outline-primary btn-sm w-100">Save</button></div>
                    </form>
                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.destroy', $step) }}" class="text-end mt-2">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete Step</button></form>
                </div>
            @endforeach

            <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="row g-2 mt-2">@csrf
                <div class="col-md-2"><input class="form-control form-control-sm" name="step_key" placeholder="step_key" required></div>
                <div class="col-md-1"><input class="form-control form-control-sm" name="step_order" type="number" min="1" placeholder="order" required></div>
                <div class="col-md-1"><input class="form-control form-control-sm" name="approval_level" type="number" min="1" placeholder="level" required></div>
                <div class="col-md-1"><select class="form-select form-select-sm" name="step_type"><option value="sub">sub</option><option value="main">main</option></select></div>
                <div class="col-md-2"><select class="form-select form-select-sm" name="role_id"><option value="">-role-</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-select form-select-sm" name="permission_id"><option value="">-permission-</option>@foreach($permissions as $permission)<option value="{{ $permission->id }}">{{ $permission->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><input class="form-control form-control-sm" name="name_ar" placeholder="AR"></div>
                <div class="col-md-1"><input class="form-control form-control-sm" name="name_en" placeholder="EN"></div>
                <div class="col-md-1"><button class="btn btn-success btn-sm w-100">+</button></div>
            </form>
        </div></div>
        @endforeach
    </div>
</div>
@endsection
