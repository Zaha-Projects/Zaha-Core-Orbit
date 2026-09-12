@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Ramadan Iftars</h1><div>
        @can('ramadan_iftars.approve')<a class="btn btn-outline-primary" href="{{ route('events.ramadan.approvals.index') }}">Approval queue</a>@endcan
        @can('ramadan_iftars.create')<a class="btn btn-primary" href="{{ route('events.ramadan.iftars.create') }}">Create Ramadan Iftar</a>@endcan
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>ID</th><th>Title</th><th>Branch</th><th>Date</th><th>Relations officer</th><th>Planning</th><th>Execution</th><th>Closure</th><th>Attendance</th><th>Meals</th><th>Workflow</th></tr></thead>
        <tbody>@forelse($iftars as $iftar)<tr>
            <td>{{ $iftar->id }}</td><td><a href="{{ route('events.ramadan.iftars.show', $iftar) }}">{{ $iftar->title }}</a></td><td>{{ optional($iftar->branch)->name }}</td>
            <td>{{ optional($iftar->planned_date)->format('Y-m-d') }}</td><td>{{ optional($iftar->relationsOfficer)->name }}</td>
            <td><span class="badge bg-secondary">{{ $iftar->status }}</span></td><td><span class="badge bg-info text-dark">{{ $iftar->execution_status }}</span></td><td><span class="badge {{ $iftar->closed_at ? 'bg-dark' : 'bg-light text-dark' }}">{{ $iftar->closed_at ? 'Closed' : 'Open' }}</span></td>
            <td>{{ $iftar->actual_attendance ?? '—' }} / {{ $iftar->expected_attendance }}</td><td>{{ $iftar->actual_meals_count ?? '—' }} / {{ $iftar->planned_meals_count }}</td>
            <td>{{ optional(optional($iftar->workflowInstance)->currentStep)->name_en ?? '—' }}</td>
        </tr>@empty<tr><td colspan="11" class="text-center text-muted">No Ramadan Iftars are available.</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $iftars->links() }}</div>
</div>
@endsection
