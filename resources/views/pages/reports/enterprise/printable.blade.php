@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('app.enterprise.printable.title') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/theme/css/Theme.css') }}">
    <style>
        body { font-family: Arial, 'Tajawal', sans-serif; margin: 20px; background: var(--page-bg); color: var(--text-color); }
        h2 { margin-top: 1.2rem; margin-bottom: 0.6rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; background: var(--surface-bg); }
        td, th { border: 1px solid var(--border-color); padding: 6px; text-align: {{ $isRtl ? 'right' : 'left' }}; }
        thead th { background: var(--surface-soft); }
        @media print { body { background: #fff; color: #111827; } }
    </style>
</head>
<body>
<h2>{{ __('app.roles.reports.agenda.title') }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ __('app.roles.reports.agenda.table.date') }}</th>
            <th>{{ __('app.roles.reports.agenda.table.event') }}</th>
            <th>{{ __('app.roles.reports.agenda.table.status') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($agenda as $event)
            <tr>
                <td>{{ optional($event->event_date)->format('Y-m-d') }}</td>
                <td>{{ $event->event_name }}</td>
                <td>{{ data_get($event, 'workflow_summary.status_label', $event->status) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">{{ __('app.roles.reports.agenda.table.empty') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>

<h2>{{ __('app.roles.reports.monthly.title') }}</h2>
<table>
    <thead>
        <tr>
            <th>{{ __('app.roles.reports.monthly.table.date') }}</th>
            <th>{{ __('app.roles.reports.monthly.table.activity') }}</th>
            <th>{{ __('app.roles.reports.monthly.table.status') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($activities as $activity)
            <tr>
                <td>{{ optional($activity->proposed_date)->format('Y-m-d') }}</td>
                <td>{{ $activity->title }}</td>
                <td>{{ data_get($activity, 'workflow_summary.status_label', $activity->status) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">{{ __('app.roles.reports.monthly.table.empty') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
