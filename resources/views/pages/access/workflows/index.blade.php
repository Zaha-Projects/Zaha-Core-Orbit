@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endpush

@section('content')
<div class="workflow-ui">
    <div class="wf-card card mb-4">
        <div class="card-body">
            <h1 class="wf-page-title mb-1">{{ __('workflow_ui.builder.title') }}</h1>
            <p class="wf-muted mb-0">{{ __('workflow_ui.builder.subtitle') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="wf-card card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('workflow_ui.builder.create_workflow') }}</h2>
            <form method="POST" action="{{ route('role.super_admin.workflows.store') }}" class="wf-grid">@csrf
                <div class="wf-col-3">
                    <label class="form-label">{{ __('workflow_ui.common.module') }}</label>
                    <input class="form-control" name="module" required>
                </div>
                <div class="wf-col-3">
                    <label class="form-label">{{ __('workflow_ui.common.code') }}</label>
                    <input class="form-control" name="code" required>
                </div>
                <div class="wf-col-3">
                    <label class="form-label">{{ __('workflow_ui.common.name_ar') }}</label>
                    <input class="form-control" name="name_ar">
                </div>
                <div class="wf-col-3">
                    <label class="form-label">{{ __('workflow_ui.common.name_en') }}</label>
                    <input class="form-control" name="name_en">
                </div>
                <div class="wf-col-12 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="create-active">
                        <label class="form-check-label" for="create-active">{{ __('workflow_ui.common.active') }}</label>
                    </div>
                    <button class="btn btn-primary">{{ __('workflow_ui.common.create') }}</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($workflows as $workflow)
    <div class="wf-card card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('role.super_admin.workflows.update', $workflow) }}" class="wf-grid mb-3">@csrf @method('PUT')
                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.module') }}</label><input class="form-control" name="module" value="{{ $workflow->module }}" required></div>
                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.code') }}</label><input class="form-control" name="code" value="{{ $workflow->code }}" required></div>
                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_ar') }}</label><input class="form-control" name="name_ar" value="{{ $workflow->name_ar }}"></div>
                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_en') }}</label><input class="form-control" name="name_en" value="{{ $workflow->name_en }}"></div>
                <div class="wf-col-12 d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active-{{ $workflow->id }}" {{ $workflow->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-active-{{ $workflow->id }}">{{ __('workflow_ui.common.active') }}</label>
                        </div>
                    </div>
                    <div class="wf-actions">
                        <button class="btn btn-outline-primary">{{ __('workflow_ui.common.save') }}</button>
                    </div>
                </div>
            </form>

            <div class="row g-3">
                <div class="col-lg-8">
                    <h3 class="h6 mb-3">{{ __('workflow_ui.builder.steps') }}</h3>
                    @foreach($workflow->steps as $step)
                        <div class="border rounded-3 p-3 mb-2">
                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="wf-grid">@csrf @method('PUT')
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_key') }}</label><input class="form-control" name="step_key" value="{{ $step->step_key }}" required></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_order') }} <i class="feather-help-circle" data-bs-toggle="tooltip" title="{{ __('workflow_ui.builder.step_order_help') }}"></i></label><input class="form-control" name="step_order" type="number" min="1" value="{{ $step->step_order }}" required></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.approval_level') }} <i class="feather-help-circle" data-bs-toggle="tooltip" title="{{ __('workflow_ui.builder.approval_level_help') }}"></i></label><input class="form-control" name="approval_level" type="number" min="1" value="{{ $step->approval_level }}" required></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.type') }}</label><select class="form-select" name="step_type"><option value="sub" {{ $step->step_type === 'sub' ? 'selected' : '' }}>{{ __('workflow_ui.builder.step_types.sub') }}</option><option value="main" {{ $step->step_type === 'main' ? 'selected' : '' }}>{{ __('workflow_ui.builder.step_types.main') }}</option></select></div>
                                <div class="wf-col-4"><label class="form-label">{{ __('workflow_ui.common.role') }}</label><select class="form-select" name="role_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}" {{ (int) $step->role_id === (int) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>@endforeach</select></div>
                                <div class="wf-col-4"><label class="form-label">{{ __('workflow_ui.common.permission') }}</label><select class="form-select" name="permission_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($permissions as $permission)<option value="{{ $permission->id }}" {{ (int) $step->permission_id === (int) $permission->id ? 'selected' : '' }}>{{ $permission->name }}</option>@endforeach</select></div>
                                <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.ar_short') }}</label><input class="form-control" name="name_ar" value="{{ $step->name_ar }}"></div>
                                <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.en_short') }}</label><input class="form-control" name="name_en" value="{{ $step->name_en }}"></div>
                                <div class="wf-col-12 d-flex justify-content-between align-items-center">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_editable" value="1" id="editable-{{ $step->id }}" {{ $step->is_editable ? 'checked' : '' }}><label class="form-check-label" for="editable-{{ $step->id }}">{{ __('workflow_ui.common.editable') }}</label></div>
                                    <div class="wf-actions">
                                        <button class="btn btn-outline-primary btn-sm">{{ __('workflow_ui.common.save') }}</button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.destroy', $step) }}" class="mt-2" onsubmit="return confirm('{{ __('workflow_ui.builder.delete_step_confirm') }}')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('workflow_ui.common.delete') }}</button></form>
                        </div>
                    @endforeach

                    <div class="border rounded-3 p-3 mt-3">
                        <h4 class="h6 mb-3">{{ __('workflow_ui.builder.add_step') }}</h4>
                        <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="wf-grid">@csrf
                            <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_key') }}</label><input class="form-control" name="step_key" required></div>
                            <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_order') }}</label><input class="form-control" type="number" min="1" name="step_order" required></div>
                            <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.approval_level') }}</label><input class="form-control" type="number" min="1" name="approval_level" required></div>
                            <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.type') }}</label><select class="form-select" name="step_type"><option value="sub">{{ __('workflow_ui.builder.step_types.sub') }}</option><option value="main">{{ __('workflow_ui.builder.step_types.main') }}</option></select></div>
                            <div class="wf-col-4"><label class="form-label">{{ __('workflow_ui.common.role') }}</label><select class="form-select" name="role_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
                            <div class="wf-col-4"><label class="form-label">{{ __('workflow_ui.common.permission') }}</label><select class="form-select" name="permission_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($permissions as $permission)<option value="{{ $permission->id }}">{{ $permission->name }}</option>@endforeach</select></div>
                            <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.ar_short') }}</label><input class="form-control" name="name_ar"></div>
                            <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.en_short') }}</label><input class="form-control" name="name_en"></div>
                            <div class="wf-col-12 d-flex justify-content-between align-items-center">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="is_editable" value="1" id="new-edit-{{ $workflow->id }}" checked><label class="form-check-label" for="new-edit-{{ $workflow->id }}">{{ __('workflow_ui.common.editable') }}</label></div>
                                <button class="btn btn-success">{{ __('workflow_ui.builder.add_step') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 mb-3">
                        <h3 class="h6">{{ __('workflow_ui.builder.preview') }}</h3>
                        @if($workflow->steps->isEmpty())
                            <p class="wf-muted mb-0">{{ __('workflow_ui.builder.preview_empty') }}</p>
                        @else
                            <ol class="wf-stepper mt-3">
                                @foreach($workflow->steps as $previewStep)
                                    <li class="wf-step">
                                        <span class="wf-step-dot"><i class="feather-check"></i></span>
                                        <div class="fw-semibold">{{ $previewStep->name_ar ?? $previewStep->name_en ?? $previewStep->step_key }}</div>
                                        <div class="wf-kv">#{{ $previewStep->step_order }} · L{{ $previewStep->approval_level }}</div>
                                        <div class="wf-kv">{{ $previewStep->role?->name ?? $previewStep->permission?->name ?? '-' }}</div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('role.super_admin.workflows.destroy', $workflow) }}" onsubmit="return confirm('{{ __('workflow_ui.builder.delete_workflow_confirm') }}')">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100">{{ __('workflow_ui.common.delete') }}</button></form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (item) {
            new bootstrap.Tooltip(item);
        });
    });
</script>
@endpush
