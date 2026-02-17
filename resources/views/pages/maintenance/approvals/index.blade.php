@extends('layouts.app')

@php
    $title = __('app.roles.maintenance.approvals.title');
    $subtitle = __('app.roles.maintenance.approvals.subtitle');
@endphp

@section('sidebar')
    @include('pages.maintenance.partials.sidebar')
@endsection

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.maintenance.approvals.table.request') }}</th>
                            <th>{{ __('app.roles.maintenance.approvals.table.status') }}</th>
                            <th>{{ __('app.roles.maintenance.approvals.table.branch') }}</th>
                            <th class="text-end">{{ __('app.roles.maintenance.approvals.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>{{ $request->status }}</td>
                                <td>{{ $request->branch?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#approve-{{ $request->id }}">
                                        {{ __('app.roles.maintenance.approvals.actions.review') }}
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="approve-{{ $request->id }}">
                                <td colspan="4">
                                    <form method="POST" action="{{ route('role.maintenance.approvals.update', $request) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">{{ __('app.roles.maintenance.approvals.fields.decision') }}</label>
                                            <select class="form-select" name="decision" required>
                                                <option value="approved">{{ __('app.roles.maintenance.approvals.decisions.approved') }}</option>
                                                <option value="changes_requested">{{ __('app.roles.maintenance.approvals.decisions.changes_requested') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <label class="form-label">{{ __('app.roles.maintenance.approvals.fields.comment') }}</label>
                                            <input class="form-control" name="comment">
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">
                                                {{ __('app.roles.maintenance.approvals.actions.submit') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('app.roles.maintenance.approvals.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
