@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">{{ __('ramadan_iftars.navigation.approvals') }}</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('ramadan_iftars.sections.planning') }}</th><th>{{ __('ramadan_iftars.fields.branch') }}</th><th>{{ __('ramadan_iftars.fields.planned_date') }}</th><th>{{ __('ramadan_iftars.fields.current_step') }}</th><th></th></tr></thead>
        <tbody>@forelse($iftars as $iftar)<tr>
            <td>{{ $iftar->title }}</td><td>{{ optional($iftar->branch)->name }}</td><td>{{ optional($iftar->planned_date)->format('Y-m-d') }}</td>
            <td>{{ optional(optional($iftar->workflowInstance)->currentStep)->name_en }}</td>
            <td><a class="btn btn-sm btn-primary" href="{{ route('events.ramadan.approvals.show', $iftar) }}">{{ __('ramadan_iftars.actions.review_approval') }}</a></td>
        </tr>@empty<tr><td colspan="5" class="text-center text-muted">{{ __('ramadan_iftars.empty.iftars') }}</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $iftars->links() }}</div>
</div>
@endsection
