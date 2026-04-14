@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endpush

@section('content')
<div class="workflow-ui">
    <div class="wf-card card mb-4 wf-hero-card">
        <div class="card-body d-flex flex-column gap-2">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="wf-page-title mb-1">{{ __('workflow_ui.builder.title') }}</h1>
                    <p class="wf-muted mb-0">{{ __('workflow_ui.builder.subtitle') }}</p>
                </div>
                <div class="wf-chip-row">
                    <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.builder.active_modules', ['count' => $workflows->where('is_active', true)->count()]) }}</span>
                    <span class="wf-chip">{{ __('workflow_ui.builder.total_workflows', ['count' => $workflows->count()]) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="wf-card card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                    <h2 class="h6 mb-1">{{ __('workflow_ui.builder.create_workflow') }}</h2>
                    <p class="wf-muted mb-0">{{ __('workflow_ui.builder.create_workflow_hint') }}</p>
                </div>
            </div>

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
                <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="form-check form-switch">
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
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <h2 class="h5 mb-0">{{ $workflow->name_ar ?: $workflow->name_en ?: $workflow->code }}</h2>
                            <span class="wf-status-badge {{ $workflow->is_active ? 'wf-status-approved' : 'wf-status-default' }}">{{ $workflow->is_active ? __('workflow_ui.common.active') : __('workflow_ui.builder.inactive') }}</span>
                        </div>
                        <div class="wf-chip-row">
                            <span class="wf-chip">{{ __('workflow_ui.common.module') }}: {{ $workflow->module }}</span>
                            <span class="wf-chip">{{ __('workflow_ui.common.code') }}: {{ $workflow->code }}</span>
                            <span class="wf-chip wf-chip-soft">{{ __('workflow_ui.builder.steps_count', ['count' => $workflow->steps->count()]) }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('role.super_admin.workflows.update', $workflow) }}" class="wf-grid mb-4">@csrf @method('PUT')
                    <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.module') }}</label><input class="form-control" name="module" value="{{ $workflow->module }}" required></div>
                    <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.code') }}</label><input class="form-control" name="code" value="{{ $workflow->code }}" required></div>
                    <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_ar') }}</label><input class="form-control" name="name_ar" value="{{ $workflow->name_ar }}"></div>
                    <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_en') }}</label><input class="form-control" name="name_en" value="{{ $workflow->name_en }}"></div>
                    <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active-{{ $workflow->id }}" {{ $workflow->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-active-{{ $workflow->id }}">{{ __('workflow_ui.common.active') }}</label>
                        </div>
                        <button class="btn btn-outline-primary">{{ __('workflow_ui.common.save') }}</button>
                    </div>
                </form>

                <div class="row g-3">
                    <div class="col-xl-8">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h3 class="h6 mb-0">{{ __('workflow_ui.builder.steps') }}</h3>
                            <span class="wf-muted">{{ __('workflow_ui.builder.steps_help') }}</span>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @foreach($workflow->steps as $step)
                                <div class="wf-step-card border rounded-3 p-3">
                                    <div class="wf-chip-row mb-3">
                                        <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.builder.preview_order') }} {{ $step->step_order }}</span>
                                        <span class="wf-chip">{{ __('workflow_ui.builder.preview_level') }} {{ $step->approval_level }}</span>
                                        <span class="wf-chip wf-chip-soft">{{ $step->step_type === 'sub' ? __('workflow_ui.builder.step_types.sub') : __('workflow_ui.builder.step_types.main') }}</span>
                                        @if($step->hasCondition())
                                            <span class="wf-chip wf-chip-warning">{{ __('workflow_ui.builder.condition_summary', ['field' => $step->condition_field, 'value' => $step->condition_value]) }}</span>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="wf-grid">@csrf @method('PUT')
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_key') }}</label><input class="form-control" name="step_key" value="{{ $step->step_key }}" required></div>
                                        <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.step_order') }}</label><input class="form-control" name="step_order" type="number" min="1" value="{{ $step->step_order }}" required></div>
                                        <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.approval_level') }}</label><input class="form-control" name="approval_level" type="number" min="1" value="{{ $step->approval_level }}" required></div>
                                        <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.type') }}</label><select class="form-select" name="step_type"><option value="sub" {{ $step->step_type === 'sub' ? 'selected' : '' }}>{{ __('workflow_ui.builder.step_types.sub') }}</option><option value="main" {{ $step->step_type === 'main' ? 'selected' : '' }}>{{ __('workflow_ui.builder.step_types.main') }}</option></select></div>
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.role') }}</label><select class="form-select" name="role_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}" {{ (int) $step->role_id === (int) $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>@endforeach</select></div>
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_ar') }}</label><input class="form-control" name="name_ar" value="{{ $step->name_ar }}"></div>
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_en') }}</label><input class="form-control" name="name_en" value="{{ $step->name_en }}"></div>
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.builder.condition') }}</label><input class="form-control" name="condition_field" value="{{ $step->condition_field }}" placeholder="requires_programs"></div>
                                        <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.builder.condition_value') }}</label><input class="form-control" name="condition_value" value="{{ $step->condition_value }}" placeholder="1"></div>
                                        <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_editable" value="1" id="editable-{{ $step->id }}" {{ $step->is_editable ? 'checked' : '' }}><label class="form-check-label" for="editable-{{ $step->id }}">{{ __('workflow_ui.common.editable') }}</label></div>
                                            <div class="wf-actions">
                                                <button class="btn btn-outline-primary btn-sm">{{ __('workflow_ui.common.save') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.destroy', $step) }}" class="mt-2" onsubmit="return confirm('{{ __('workflow_ui.builder.delete_step_confirm') }}')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('workflow_ui.common.delete') }}</button></form>
                                </div>
                            @endforeach
                        </div>

                        <div class="border rounded-3 p-3 mt-4 wf-panel-soft">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                <div>
                                    <h4 class="h6 mb-1">{{ __('workflow_ui.builder.add_step') }}</h4>
                                    <p class="wf-muted mb-0">{{ __('workflow_ui.builder.add_step_hint') }}</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="wf-grid">@csrf
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.step_key') }}</label><input class="form-control" name="step_key" required></div>
                                <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.step_order') }}</label><input class="form-control" type="number" min="1" name="step_order" required></div>
                                <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.approval_level') }}</label><input class="form-control" type="number" min="1" name="approval_level" required></div>
                                <div class="wf-col-2"><label class="form-label">{{ __('workflow_ui.common.type') }}</label><select class="form-select" name="step_type"><option value="sub">{{ __('workflow_ui.builder.step_types.sub') }}</option><option value="main">{{ __('workflow_ui.builder.step_types.main') }}</option></select></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.role') }}</label><select class="form-select" name="role_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->display_name }}</option>@endforeach</select></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_ar') }}</label><input class="form-control" name="name_ar"></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.common.name_en') }}</label><input class="form-control" name="name_en"></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.builder.condition') }}</label><input class="form-control" name="condition_field" placeholder="requires_programs"></div>
                                <div class="wf-col-3"><label class="form-label">{{ __('workflow_ui.builder.condition_value') }}</label><input class="form-control" name="condition_value" placeholder="1"></div>
                                <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_editable" value="1" id="new-edit-{{ $workflow->id }}" checked><label class="form-check-label" for="new-edit-{{ $workflow->id }}">{{ __('workflow_ui.common.editable') }}</label></div>
                                    <button class="btn btn-success">{{ __('workflow_ui.builder.add_step') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="border rounded-3 p-3 mb-3 wf-panel-soft">
                            <h3 class="h6 mb-3">{{ __('workflow_ui.builder.preview') }}</h3>
                            @if($workflow->steps->isEmpty())
                                <p class="wf-muted mb-0">{{ __('workflow_ui.builder.preview_empty') }}</p>
                            @else
                                <ol class="wf-stepper mt-3">
                                    @foreach($workflow->steps as $previewStep)
                                        <li class="wf-step">
                                            <span class="wf-step-dot"><i class="feather-check"></i></span>
                                            <div class="fw-semibold">{{ $previewStep->name_ar ?? $previewStep->name_en ?? __('workflow_ui.common.unknown_step') }}</div>
                                            <div class="wf-kv">{{ __('workflow_ui.builder.preview_role') }}: {{ $previewStep->role?->display_name ?? __('workflow_ui.common.none_option') }}</div>
                                            <div class="wf-kv">{{ __('workflow_ui.builder.preview_type') }}: {{ $previewStep->step_type === 'sub' ? __('workflow_ui.builder.step_types.sub') : __('workflow_ui.builder.step_types.main') }}</div>
                                            @if($previewStep->hasCondition())
                                                <div class="wf-kv">{{ __('workflow_ui.builder.condition_summary', ['field' => $previewStep->condition_field, 'value' => $previewStep->condition_value]) }}</div>
                                            @endif
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
