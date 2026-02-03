<?php

namespace App\Http\Controllers\Roles\Finance;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Center;
use Illuminate\Http\Request;

class BookingsController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['branch', 'center'])->orderByDesc('booking_date')->get();
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.finance.bookings.index', compact('bookings', 'branches', 'centers'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.finance.bookings.create', compact('branches', 'centers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'booking_date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i'],
            'received_by' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'facility_name' => ['required', 'string', 'max:255'],
            'payment_type' => ['required', 'string', 'max:255'],
            'receipt_ref' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ]);

        Booking::create([
            'received_at' => $data['received_at'],
            'booking_date' => $data['booking_date'],
            'time_from' => $data['time_from'],
            'time_to' => $data['time_to'],
            'received_by' => $data['received_by'],
            'customer_name' => $data['customer_name'],
            'facility_name' => $data['facility_name'],
            'payment_type' => $data['payment_type'],
            'receipt_ref' => $data['receipt_ref'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_reason' => $data['discount_reason'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
        ]);

        return redirect()
            ->route('role.finance.bookings.index')
            ->with('status', __('app.roles.finance.bookings.created'));
    }

    public function edit(Booking $booking)
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.finance.bookings.edit', compact('booking', 'branches', 'centers'));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'booking_date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i'],
            'received_by' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'facility_name' => ['required', 'string', 'max:255'],
            'payment_type' => ['required', 'string', 'max:255'],
            'receipt_ref' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ]);

        $booking->update([
            'received_at' => $data['received_at'],
            'booking_date' => $data['booking_date'],
            'time_from' => $data['time_from'],
            'time_to' => $data['time_to'],
            'received_by' => $data['received_by'],
            'customer_name' => $data['customer_name'],
            'facility_name' => $data['facility_name'],
            'payment_type' => $data['payment_type'],
            'receipt_ref' => $data['receipt_ref'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'discount_reason' => $data['discount_reason'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
        ]);

        return redirect()
            ->route('role.finance.bookings.index')
            ->with('status', __('app.roles.finance.bookings.updated', ['booking' => $booking->customer_name]));
    }
}
