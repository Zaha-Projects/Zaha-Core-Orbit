<?php

namespace App\Http\Controllers\Web\Finance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ZahaTimeBooking;
use Illuminate\Http\Request;

class ZahaTimeController extends Controller
{
    public function index()
    {
        $bookings = ZahaTimeBooking::with(['branch'])->orderByDesc('booking_date')->get();
        $branches = Branch::orderBy('name')->get();
        return view('pages.finance.zaha_time.index', compact('bookings', 'branches'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('pages.finance.zaha_time.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'booking_date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i'],
            'entity_type' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'children_count' => ['nullable', 'integer', 'min:0'],
            'payment_cash_ref' => ['nullable', 'string', 'max:255'],
            'payment_electronic_ref' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['nullable'],
        ]);

        ZahaTimeBooking::create([
            'received_at' => $data['received_at'],
            'booking_date' => $data['booking_date'],
            'time_from' => $data['time_from'],
            'time_to' => $data['time_to'],
            'entity_type' => $data['entity_type'],
            'contact_person' => $data['contact_person'],
            'phone' => $data['phone'],
            'children_count' => $data['children_count'] ?? 0,
            'payment_cash_ref' => $data['payment_cash_ref'] ?? null,
            'payment_electronic_ref' => $data['payment_electronic_ref'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_reason' => $data['discount_reason'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => null,
        ]);

        return redirect()
            ->route('role.finance.zaha_time.index')
            ->with('status', __('app.roles.finance.zaha_time.created'));
    }

    public function edit(ZahaTimeBooking $zahaTimeBooking)
    {
        $branches = Branch::orderBy('name')->get();
        return view('pages.finance.zaha_time.edit', compact('zahaTimeBooking', 'branches'));
    }

    public function update(Request $request, ZahaTimeBooking $zahaTimeBooking)
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'booking_date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i'],
            'entity_type' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'children_count' => ['nullable', 'integer', 'min:0'],
            'payment_cash_ref' => ['nullable', 'string', 'max:255'],
            'payment_electronic_ref' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['nullable'],
        ]);

        $zahaTimeBooking->update([
            'received_at' => $data['received_at'],
            'booking_date' => $data['booking_date'],
            'time_from' => $data['time_from'],
            'time_to' => $data['time_to'],
            'entity_type' => $data['entity_type'],
            'contact_person' => $data['contact_person'],
            'phone' => $data['phone'],
            'children_count' => $data['children_count'] ?? 0,
            'payment_cash_ref' => $data['payment_cash_ref'] ?? null,
            'payment_electronic_ref' => $data['payment_electronic_ref'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_reason' => $data['discount_reason'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => null,
        ]);

        return redirect()
            ->route('role.finance.zaha_time.index')
            ->with('status', __('app.roles.finance.zaha_time.updated', ['booking' => $zahaTimeBooking->contact_person]));
    }
}
