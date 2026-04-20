<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0">{{ __('app.enterprise.branch_performance.title') }}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
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
                @foreach($branchMetrics as $row)
                    <tr>
                        <td>{{ $row['branch'] }}</td>
                        <td>{{ $row['total_events_participated'] }}</td>
                        <td>{{ $row['participation_rate'] }}</td>
                        <td>{{ $row['approval_success_rate'] }}</td>
                        <td>{{ $row['activity_completion_rate'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer small text-muted">
        عدد الفروع: {{ count($branchMetrics ?? []) }}
    </div>
</div>
