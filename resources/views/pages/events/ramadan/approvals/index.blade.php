@extends('layouts.app')
@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ __('ramadan_iftars.navigation.approvals') }}</li></ol></nav>
    <h1 class="h3 mb-3">{{ __('ramadan_iftars.navigation.approvals') }}</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('ramadan_iftars.sections.planning') }}</th><th>{{ __('ramadan_iftars.fields.branch') }}</th><th>{{ __('ramadan_iftars.fields.submitted_by') }}</th><th>{{ __('ramadan_iftars.fields.submitted_at') }}</th><th>{{ __('ramadan_iftars.fields.current_step') }}</th><th></th></tr></thead>
        <tbody>@forelse($iftars as $iftar)<tr>
            <td>{{ $iftar->title }}</td><td>{{ optional($iftar->branch)->name }}</td><td>{{ optional($iftar->creator)->name ?: '—' }}</td><td>{{ optional($iftar->submitted_at)->format('Y-m-d H:i') ?: '—' }}</td>
            <td>{{ (app()->getLocale()==='ar' ? optional(optional($iftar->workflowInstance)->currentStep)->name_ar : optional(optional($iftar->workflowInstance)->currentStep)->name_en) }}</td>
            <td><a class="btn btn-sm btn-primary" href="{{ route('events.ramadan.approvals.show', $iftar) }}">{{ __('ramadan_iftars.actions.review_approval') }}</a></td>
        </tr>@empty<tr><td colspan="6" class="text-center text-muted">{{ __('ramadan_iftars.empty.iftars') }}</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $iftars->links() }}</div>
</div>
@endsection
