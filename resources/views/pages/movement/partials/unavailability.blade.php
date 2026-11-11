{{-- Unavailability windows for one vehicle or driver. Params: $subjectField ('vehicle_id'|'driver_id'), $subjectId, $windows, $windowTypes, $canManage --}}
@php($tz = \App\Modules\Movement\Support\MovementTime::timezone())
<div class="card event-card mb-4">
    <div class="card-body">
        <h2 class="event-section-title"><i class="fas fa-calendar-xmark"></i> {{ __('movement.unavailability.title') }}</h2>
        @if($canManage)
            <form method="POST" action="{{ route('movement.unavailabilities.store') }}" class="row g-3 mb-3">
                @csrf
                <input type="hidden" name="{{ $subjectField }}" value="{{ $subjectId }}">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="window-type">{{ __('movement.unavailability.type') }}</label>
                    <select class="form-select" id="window-type" name="type" required>
                        @foreach($windowTypes as $type)
                            <option value="{{ $type }}" {{ (old('type') === $type) ? 'selected' : '' }}>{{ __('movement.unavailability.types.'.$type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="window-starts">{{ __('movement.unavailability.starts_at') }}</label>
                    <input class="form-control" id="window-starts" name="starts_at" value="{{ old('starts_at') }}" data-movement-datetime required autocomplete="off">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="window-ends">{{ __('movement.unavailability.ends_at') }}</label>
                    <input class="form-control" id="window-ends" name="ends_at" value="{{ old('ends_at') }}" data-movement-datetime required autocomplete="off">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="window-reason">{{ __('movement.unavailability.reason') }}</label>
                    <input class="form-control" id="window-reason" name="reason" value="{{ old('reason') }}" maxlength="2000">
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted small">{{ __('movement.unavailability.timezone_hint', ['timezone' => $tz]) }}</span>
                    <button class="btn btn-outline-primary"><i class="fas fa-plus"></i> {{ __('movement.unavailability.add') }}</button>
                </div>
            </form>
        @endif

        @forelse($windows as $window)
            <div class="d-flex justify-content-between align-items-start gap-2 border-top py-2 flex-wrap">
                <div>
                    <strong>{{ __('movement.unavailability.types.'.$window->type) }}</strong>
                    <span class="text-muted small d-block">{{ \App\Modules\Movement\Support\MovementTime::toLocal($window->starts_at)->format('Y-m-d H:i') }} → {{ \App\Modules\Movement\Support\MovementTime::toLocal($window->ends_at)->format('Y-m-d H:i') }}</span>
                    @if($window->reason)<span class="small d-block">{{ $window->reason }}</span>@endif
                </div>
                @if($canManage)
                    <form method="POST" action="{{ route('movement.unavailabilities.destroy', $window) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> {{ __('movement.common.delete') }}</button>
                    </form>
                @endif
            </div>
        @empty
            @include('pages.movement.partials.empty', ['text' => __('movement.unavailability.empty')])
        @endforelse
    </div>
</div>
