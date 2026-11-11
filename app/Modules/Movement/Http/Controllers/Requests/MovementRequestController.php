<?php

namespace App\Modules\Movement\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Http\Requests\Requests\MovementRequestForm;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Services\MovementRequestWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Requester screens (plan §16 screen 6, my requests, details, cancel). Thin: policy → Form Request → workflow. */
class MovementRequestController extends Controller
{
    private const TOKEN_SESSION_KEY = 'movement.request_tokens';

    public function __construct(private MovementRequestWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', MovementRequest::class);

        $status = $request->validate(['status' => ['nullable', Rule::in(MovementRequest::STATUSES)]])['status'] ?? null;
        $requests = MovementRequest::query()
            ->with(['legs.originLocation', 'legs.destinationLocation'])
            ->where('requester_id', $request->user()->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.movement.requests.index', ['requests' => $requests, 'status' => $status]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', MovementRequest::class);

        return view('pages.movement.requests.form', $this->formOptions($request, new MovementRequest([
            'trip_direction' => MovementRequest::DIRECTION_ONE_WAY,
            'branch_id' => $request->user()->branch_id,
        ]), $this->issueToken($request)));
    }

    public function store(MovementRequestForm $form)
    {
        $token = $form->validated()['client_token'] ?? null;
        $issued = in_array($token, $form->session()->get(self::TOKEN_SESSION_KEY, []), true);
        $existing = $token ? MovementRequest::withTrashed()->where('client_token', $token)->where('requester_id', $form->user()->id)->first() : null;

        if (! $issued && ! $existing) {
            throw ValidationException::withMessages(['client_token' => __('movement.requests.errors.form_expired')]);
        }

        $movementRequest = $this->workflow->create($form->user(), $form->payload(), $token);

        if (! $movementRequest->wasRecentlyCreated) {
            // §11.7: a repeat with the same token returns the existing request.
            return redirect()->route('movement.requests.show', $movementRequest)->with('info', __('movement.requests.flash.already_saved'));
        }

        if ($form->input('submit_action') === 'submit') {
            return $this->runTransition(fn () => $this->workflow->submit($movementRequest, $form->user()), $movementRequest, 'movement.requests.flash.submitted');
        }

        return redirect()->route('movement.requests.show', $movementRequest)->with('success', __('movement.requests.flash.saved'));
    }

    public function show(MovementRequest $movementRequest)
    {
        $this->authorize('view', $movementRequest);

        return view('pages.movement.requests.show', [
            'movementRequest' => $movementRequest->load(['requester', 'decider', 'canceller', 'branch', 'department', 'legs.originLocation', 'legs.destinationLocation']),
            'timeline' => AuditLog::query()->with('user')
                ->where('entity_type', MovementRequest::class)->where('entity_id', $movementRequest->id)
                ->orderBy('id')->get(),
        ]);
    }

    public function edit(Request $request, MovementRequest $movementRequest)
    {
        $this->authorize('update', $movementRequest);
        abort_unless($movementRequest->isEditable(), 403);

        return view('pages.movement.requests.form', $this->formOptions($request, $movementRequest->load('legs'), null));
    }

    public function update(MovementRequestForm $form, MovementRequest $movementRequest)
    {
        try {
            $this->workflow->update($movementRequest, $form->payload());
        } catch (IllegalMovementTransition $e) {
            return redirect()->route('movement.requests.show', $movementRequest)->withErrors(['request' => $e->getMessage()]);
        }

        if ($form->input('submit_action') === 'submit') {
            return $this->runTransition(fn () => $this->workflow->submit($movementRequest, $form->user()), $movementRequest, 'movement.requests.flash.submitted');
        }

        return redirect()->route('movement.requests.show', $movementRequest)->with('success', __('movement.requests.flash.saved'));
    }

    public function submit(Request $request, MovementRequest $movementRequest)
    {
        $this->authorize('submit', $movementRequest);

        return $this->runTransition(fn () => $this->workflow->submit($movementRequest, $request->user()), $movementRequest, 'movement.requests.flash.submitted');
    }

    public function cancel(Request $request, MovementRequest $movementRequest)
    {
        $this->authorize('cancel', $movementRequest);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:2000']])['reason'] ?? null;

        return $this->runTransition(fn () => $this->workflow->cancel($movementRequest, $request->user(), $reason), $movementRequest, 'movement.requests.flash.cancelled');
    }

    public function destroy(MovementRequest $movementRequest)
    {
        $this->authorize('delete', $movementRequest);

        try {
            $this->workflow->deleteDraft($movementRequest);
        } catch (IllegalMovementTransition $e) {
            return redirect()->route('movement.requests.show', $movementRequest)->withErrors(['request' => $e->getMessage()]);
        }

        return redirect()->route('movement.requests.index')->with('success', __('movement.requests.flash.deleted'));
    }

    private function runTransition(callable $transition, MovementRequest $movementRequest, string $successKey)
    {
        try {
            $transition();
        } catch (IllegalMovementTransition $e) {
            return redirect()->route('movement.requests.show', $movementRequest)->withErrors(['request' => $e->getMessage()]);
        }

        return redirect()->route('movement.requests.show', $movementRequest)->with('success', __($successKey));
    }

    /** Server-generated idempotency token (§11.7), remembered in the session for this form. */
    private function issueToken(Request $request): string
    {
        $token = (string) Str::uuid();
        $tokens = array_slice(array_merge($request->session()->get(self::TOKEN_SESSION_KEY, []), [$token]), -20);
        $request->session()->put(self::TOKEN_SESSION_KEY, $tokens);

        return $token;
    }

    private function formOptions(Request $request, MovementRequest $movementRequest, ?string $token): array
    {
        return [
            'movementRequest' => $movementRequest,
            'clientToken' => $token,
            'locations' => Location::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->where('is_active', true)->orWhere('id', $movementRequest->department_id)->orderBy('sort_order')->orderBy('id')->get(['id', 'name']),
            'indicativeHours' => (int) config('movement.requests.indicative_window_hours', 3),
        ];
    }
}
