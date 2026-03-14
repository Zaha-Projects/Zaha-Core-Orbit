@extends('layouts.app')

@section('page_title', __('app.enterprise.branch_performance.report_title'))
@section('page_breadcrumb', __('app.enterprise.branch_performance.report_title'))

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">{{ __('app.enterprise.branch_performance.report_title_with_year', ['year' => $year]) }}</h4>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.enterprise.branch_performance.branch') }}</th>
                            <th>{{ __('app.enterprise.branch_performance.total') }}</th>
                            <th>{{ __('app.enterprise.branch_performance.participation') }}</th>
                            <th>{{ __('app.enterprise.branch_performance.approval') }}</th>
                            <th>{{ __('app.enterprise.branch_performance.completion') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['branch'] }}</td>
                                <td>{{ $row['total_events_participated'] }}</td>
                                <td>{{ $row['participation_rate'] }}%</td>
                                <td>{{ $row['approval_success_rate'] }}%</td>
                                <td>{{ $row['activity_completion_rate'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('app.enterprise.branch_performance.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
