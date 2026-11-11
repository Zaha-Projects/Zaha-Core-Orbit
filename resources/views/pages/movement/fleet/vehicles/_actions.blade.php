<div class="d-flex gap-2 flex-wrap">
    @if($vehicle->trashed())
        <form method="POST" action="{{ route('movement.vehicles.restore', $vehicle) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('movement.common.restore') }}</button></form>
    @else
        <a class="btn btn-sm btn-outline-primary" href="{{ route('movement.vehicles.show', $vehicle) }}">{{ __('movement.common.view') }}</a>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('movement.vehicles.edit', $vehicle) }}">{{ __('movement.common.edit') }}</a>
    @endif
</div>
