@extends('layouts.app')
@section('content')
<div class="card"><div class="card-body"><h4>Branch performance {{ $year }}</h4>
<table class="table"><thead><tr><th>Branch</th><th>Total events</th><th>Participation rate</th><th>Approval success</th><th>Activity completion</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row['branch'] }}</td><td>{{ $row['total_events_participated'] }}</td><td>{{ $row['participation_rate'] }}%</td><td>{{ $row['approval_success_rate'] }}%</td><td>{{ $row['activity_completion_rate'] }}%</td></tr>@endforeach</tbody></table>
</div></div>
@endsection
