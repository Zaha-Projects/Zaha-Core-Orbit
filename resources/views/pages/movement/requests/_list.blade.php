{{-- Request list (table on md+, stacked cards below). Params: $requests, $showRequester --}}
@if($requests->isEmpty())
    @include('pages.movement.partials.empty', ['text' => $emptyText])
@else
    <div class="table-responsive d-none d-md-block">
        <table class="table align-middle mb-0">
            <thead><tr>
                <th>{{ __('movement.requests.reference') }}</th>
                @if($showRequester)<th>{{ __('movement.requests.requester') }}</th>@endif
                <th>{{ __('movement.requests.direction') }}</th>
                <th>{{ __('movement.requests.deadline') }}</th>
                <th>{{ __('movement.requests.status') }}</th>
                <th>{{ __('movement.common.actions') }}</th>
            </tr></thead>
            <tbody>
            @foreach($requests as $item)
                @php($first = $item->legs->sortBy('required_arrival_at')->first())
                <tr>
                    <td><strong>{{ $item->reference_no }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->purpose, 60) }}</div></td>
                    @if($showRequester)<td>{{ $item->requester?->name }}</td>@endif
                    <td>{{ __('movement.directions.'.$item->trip_direction) }}</td>
                    <td>{{ $first ? \App\Modules\Movement\Support\MovementTime::toLocal($first->required_arrival_at)->format('Y-m-d H:i') : '—' }}</td>
                    <td>@include('pages.movement.requests._status', ['movementRequest' => $item])</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('movement.requests.show', $item) }}">{{ __('movement.common.view') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-md-none">
        @foreach($requests as $item)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $item->reference_no }}</strong>@include('pages.movement.requests._status', ['movementRequest' => $item])</div>
                @if($showRequester)<div class="small">{{ $item->requester?->name }}</div>@endif
                @foreach($item->legs as $leg)@include('pages.movement.requests._leg_summary', ['leg' => $leg])@endforeach
                <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('movement.requests.show', $item) }}">{{ __('movement.common.view') }}</a>
            </div>
        @endforeach
    </div>
@endif
