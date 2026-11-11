<div class="d-flex gap-2 flex-wrap">
    @if($driver->trashed())
        @can('restore', $driver)
            <form method="POST" action="{{ route('movement.drivers.restore', $driver) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('movement.common.restore') }}</button></form>
        @endcan
    @else
        <a class="btn btn-sm btn-outline-primary" href="{{ route('movement.drivers.show', $driver) }}">{{ __('movement.common.view') }}</a>
        @can('update', $driver)<a class="btn btn-sm btn-outline-secondary" href="{{ route('movement.drivers.edit', $driver) }}">{{ __('movement.common.edit') }}</a>@endcan
    @endif
</div>
