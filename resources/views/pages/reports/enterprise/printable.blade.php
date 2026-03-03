<!doctype html><html><head><meta charset="utf-8"><title>Printable report</title><style>body{font-family:Arial}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px}</style></head><body>
<h2>Agenda Report</h2>
<table><thead><tr><th>Date</th><th>Event</th><th>Status</th></tr></thead><tbody>@foreach($agenda as $event)<tr><td>{{ optional($event->event_date)->format('Y-m-d') }}</td><td>{{ $event->event_name }}</td><td>{{ $event->status }}</td></tr>@endforeach</tbody></table>
<h2>Monthly Activities</h2>
<table><thead><tr><th>Date</th><th>Title</th><th>Status</th></tr></thead><tbody>@foreach($activities as $activity)<tr><td>{{ optional($activity->proposed_date)->format('Y-m-d') }}</td><td>{{ $activity->title }}</td><td>{{ $activity->status }}</td></tr>@endforeach</tbody></table>
</body></html>
